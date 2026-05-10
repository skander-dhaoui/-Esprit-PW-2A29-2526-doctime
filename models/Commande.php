<?php

require_once __DIR__ . '/../config/database.php';

/**
 * Commandes pharmacie (schéma database.sql : commandes, commande_details).
 */
class Commande {

    private Database $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    /** Codes métier (PharmacieController) → ENUM MySQL */
    private function statutToDb(string $s): string {
        return match ($s) {
            'confirmee', 'confirmée' => 'confirmée',
            'expediee', 'expédiée' => 'expédiée',
            'livree', 'livrée' => 'livrée',
            'annulee', 'annulée' => 'annulée',
            default => 'en_attente',
        };
    }

    private function statutFromDb(?string $s): string {
        return match ($s) {
            'confirmée' => 'confirmee',
            'expédiée' => 'expediee',
            'livrée' => 'livree',
            'annulée' => 'annulee',
            default => 'en_attente',
        };
    }

    public function generateNumero(): string {
        return 'CMD-' . date('Ymd') . '-' . str_pad((string) random_int(1, 9999), 4, '0', STR_PAD_LEFT);
    }

    public function getAll(string $search = '', string $statut = ''): array {
        try {
            $where  = 'WHERE 1=1';
            $params = [];

            if ($search !== '') {
                $where .= ' AND (c.numero_commande LIKE :s OR u.nom LIKE :s OR u.prenom LIKE :s OR u.email LIKE :s)';
                $params['s'] = '%' . $search . '%';
            }

            if ($statut !== '') {
                $where .= ' AND c.status = :st';
                $params['st'] = $this->statutToDb($statut);
            }

            $sql = "SELECT c.*, u.nom AS client_nom, u.prenom AS client_prenom, u.email AS client_email
                    FROM commandes c
                    INNER JOIN users u ON u.id = c.user_id
                    $where
                    ORDER BY c.date_commande DESC";
            $rows = $this->db->query($sql, $params);
            foreach ($rows as &$row) {
                $row['statut'] = $this->statutFromDb($row['status'] ?? null);
            }
            unset($row);
            return $rows;
        } catch (Exception $e) {
            error_log('Erreur Commande::getAll - ' . $e->getMessage());
            return [];
        }
    }

    public function getStats(): array {
        try {
            $total = (int) ($this->db->queryScalar('SELECT COUNT(*) FROM commandes') ?? 0);
            $rows  = $this->db->query('SELECT status, COUNT(*) AS n FROM commandes GROUP BY status');
            $by    = [];
            foreach ($rows ?? [] as $r) {
                $by[$this->statutFromDb($r['status'])] = (int) $r['n'];
            }
            return [
                'total'              => $total,
                'en_attente'         => $by['en_attente'] ?? 0,
                'confirmee'          => $by['confirmee'] ?? 0,
                'expediee'           => $by['expediee'] ?? 0,
                'livree'             => $by['livree'] ?? 0,
                'annulee'            => $by['annulee'] ?? 0,
            ];
        } catch (Exception $e) {
            error_log('Erreur Commande::getStats - ' . $e->getMessage());
            return ['total' => 0];
        }
    }

    public function getById(int $id): ?array {
        try {
            $sql = "SELECT c.*, u.nom AS client_nom, u.prenom AS client_prenom, u.email AS client_email, u.telephone
                    FROM commandes c
                    INNER JOIN users u ON u.id = c.user_id
                    WHERE c.id = :id";
            $r = $this->db->query($sql, ['id' => $id]);
            if (!$r) {
                return null;
            }
            $row = $r[0];
            $row['statut'] = $this->statutFromDb($row['status'] ?? null);
            return $row;
        } catch (Exception $e) {
            error_log('Erreur Commande::getById - ' . $e->getMessage());
            return null;
        }
    }

    public function getDetails(int $commandeId): array {
        try {
            $sql = "SELECT cd.*, p.nom AS produit_nom
                    FROM commande_details cd
                    LEFT JOIN produits p ON p.id = cd.produit_id
                    WHERE cd.commande_id = :cid
                    ORDER BY cd.id ASC";
            return $this->db->query($sql, ['cid' => $commandeId]);
        } catch (Exception $e) {
            error_log('Erreur Commande::getDetails - ' . $e->getMessage());
            return [];
        }
    }

    public function getByUserId(int $userId): array {
        try {
            $sql = "SELECT * FROM commandes WHERE user_id = :uid ORDER BY date_commande DESC";
            $rows = $this->db->query($sql, ['uid' => $userId]);
            foreach ($rows as &$row) {
                $row['statut'] = $this->statutFromDb($row['status'] ?? null);
            }
            unset($row);
            return $rows;
        } catch (Exception $e) {
            error_log('Erreur Commande::getByUserId - ' . $e->getMessage());
            return [];
        }
    }

    public function create(array $data): ?int {
        try {
            $addr = $data['adresse_livraison'] ?? '';
            if (!empty($data['ville'] ?? '') || !empty($data['code_postal'] ?? '')) {
                $addr = trim($addr . "\n" . ($data['code_postal'] ?? '') . ' ' . ($data['ville'] ?? ''));
            }

            $sql = "INSERT INTO commandes
                (numero_commande, user_id, total_ht, total_ttc, status, adresse_livraison, mode_paiement, notes, date_commande)
                VALUES
                (:numero_commande, :user_id, :total_ht, :total_ttc, :status, :adresse_livraison, :mode_paiement, :notes, NOW())";

            $stat = $this->statutToDb($data['statut'] ?? 'en_attente');
            $notes = $data['notes'] ?? '';
            if (!empty($data['telephone'] ?? '')) {
                $notes = trim($notes . "\n[Tel livraison: " . $data['telephone'] . ']');
            }

            $ok = $this->db->execute($sql, [
                'numero_commande'   => $data['numero_commande'],
                'user_id'           => $data['user_id'],
                'total_ht'          => $data['total_ht'],
                'total_ttc'         => $data['total_ttc'],
                'status'            => $stat,
                'adresse_livraison' => $addr ?: '-',
                'mode_paiement'     => $data['mode_paiement'] ?? 'carte',
                'notes'             => $notes !== '' ? $notes : null,
            ]);

            return $ok ? (int) $this->db->lastInsertId() : null;
        } catch (Exception $e) {
            error_log('Erreur Commande::create - ' . $e->getMessage());
            return null;
        }
    }

    public function addDetail(array $ligne): bool {
        try {
            $total = $ligne['total_ligne'] ?? $ligne['total'] ?? 0;
            $sql = "INSERT INTO commande_details (commande_id, produit_id, quantite, prix_unitaire, total)
                    VALUES (:commande_id, :produit_id, :quantite, :prix_unitaire, :total)";
            return $this->db->execute($sql, [
                'commande_id'   => $ligne['commande_id'],
                'produit_id'    => $ligne['produit_id'],
                'quantite'      => $ligne['quantite'],
                'prix_unitaire' => $ligne['prix_unitaire'],
                'total'         => $total,
            ]);
        } catch (Exception $e) {
            error_log('Erreur Commande::addDetail - ' . $e->getMessage());
            return false;
        }
    }

    public function update(int $id, array $data): bool {
        try {
            $fields = [];
            $params = [':id' => $id];

            if (isset($data['adresse_livraison'])) {
                $fields[] = 'adresse_livraison = :adresse_livraison';
                $params[':adresse_livraison'] = $data['adresse_livraison'];
            }
            if (isset($data['ville']) || isset($data['code_postal'])) {
                $cur = $this->getById($id);
                $base = $cur['adresse_livraison'] ?? '';
                $fields[] = 'adresse_livraison = :adresse2';
                $params[':adresse2'] = trim($base . "\n" . ($data['code_postal'] ?? '') . ' ' . ($data['ville'] ?? ''));
            }
            if (isset($data['mode_paiement'])) {
                $fields[] = 'mode_paiement = :mode_paiement';
                $params[':mode_paiement'] = $data['mode_paiement'];
            }
            if (isset($data['notes'])) {
                $fields[] = 'notes = :notes';
                $params[':notes'] = $data['notes'];
            }
            if (isset($data['statut'])) {
                $fields[] = 'status = :status';
                $params[':status'] = $this->statutToDb($data['statut']);
            }

            if (empty($fields)) {
                return false;
            }

            $sql = 'UPDATE commandes SET ' . implode(', ', $fields) . ' WHERE id = :id';
            return $this->db->execute($sql, $params);
        } catch (Exception $e) {
            error_log('Erreur Commande::update - ' . $e->getMessage());
            return false;
        }
    }

    public function updateStatut(int $id, string $statut): bool {
        try {
            $sql = 'UPDATE commandes SET status = :st WHERE id = :id';
            return $this->db->execute($sql, [
                'st' => $this->statutToDb($statut),
                'id' => $id,
            ]);
        } catch (Exception $e) {
            error_log('Erreur Commande::updateStatut - ' . $e->getMessage());
            return false;
        }
    }

    public function delete(int $id): bool {
        try {
            $sql = 'DELETE FROM commandes WHERE id = :id';
            return $this->db->execute($sql, ['id' => $id]);
        } catch (Exception $e) {
            error_log('Erreur Commande::delete - ' . $e->getMessage());
            return false;
        }
    }
}
