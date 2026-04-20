<?php
// models/Commande.php

require_once __DIR__ . '/../config/database.php';

class Commande {

    private Database $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    public function create(array $data): ?int {
        try {
            $sql = "INSERT INTO commandes
                        (numero_commande, user_id, adresse_livraison, ville, code_postal,
                         telephone, mode_paiement, total_ht, tva_montant, total_ttc, statut, notes, created_at, updated_at)
                    VALUES
                        (:numero_commande, :user_id, :adresse_livraison, :ville, :code_postal,
                         :telephone, :mode_paiement, :total_ht, :tva_montant, :total_ttc, :statut, :notes, NOW(), NOW())";
            $result = $this->db->execute($sql, $data);
            return $result ? $this->db->lastInsertId() : null;
        } catch (Exception $e) {
            error_log('Erreur Commande::create - ' . $e->getMessage());
            return null;
        }
    }

    public function addDetail(array $data): bool {
        try {
            $sql = "INSERT INTO commande_details (commande_id, produit_id, quantite, prix_unitaire, total_ligne)
                    VALUES (:commande_id, :produit_id, :quantite, :prix_unitaire, :total_ligne)";
            return $this->db->execute($sql, $data);
        } catch (Exception $e) {
            error_log('Erreur Commande::addDetail - ' . $e->getMessage());
            return false;
        }
    }

    public function getById(int $id): ?array {
        try {
            $sql = "SELECT c.*,
                           u.nom AS user_nom, u.prenom AS user_prenom, u.email AS user_email
                    FROM commandes c
                    JOIN users u ON c.user_id = u.id
                    WHERE c.id = :id";
            $result = $this->db->query($sql, ['id' => $id]);
            return $result ? $result[0] : null;
        } catch (Exception $e) {
            error_log('Erreur Commande::getById - ' . $e->getMessage());
            return null;
        }
    }

    public function getDetails(int $commandeId): array {
        try {
            $sql = "SELECT cd.*, p.nom AS produit_nom, p.reference
                    FROM commande_details cd
                    JOIN produits p ON cd.produit_id = p.id
                    WHERE cd.commande_id = :id";
            return $this->db->query($sql, ['id' => $commandeId]);
        } catch (Exception $e) {
            return [];
        }
    }

    public function getAll(string $search = '', string $statut = ''): array {
        try {
            $where  = "WHERE 1=1";
            $params = [];

            if (!empty($search)) {
                $where .= " AND (c.numero_commande LIKE :search OR u.nom LIKE :search OR u.prenom LIKE :search)";
                $params['search'] = "%$search%";
            }
            if (!empty($statut)) {
                $where .= " AND c.statut = :statut";
                $params['statut'] = $statut;
            }

            $sql = "SELECT c.*,
                           u.nom AS user_nom, u.prenom AS user_prenom,
                           (SELECT COUNT(*) FROM commande_details WHERE commande_id = c.id) AS nb_articles
                    FROM commandes c
                    JOIN users u ON c.user_id = u.id
                    $where
                    ORDER BY c.created_at DESC";
            return $this->db->query($sql, $params);
        } catch (Exception $e) {
            error_log('Erreur Commande::getAll - ' . $e->getMessage());
            return [];
        }
    }

    public function getByUserId(int $userId): array {
        try {
            $sql = "SELECT c.*,
                           u.nom AS user_nom, u.prenom AS user_prenom,
                           (SELECT COUNT(*) FROM commande_details WHERE commande_id = c.id) AS nb_articles
                    FROM commandes c
                    JOIN users u ON c.user_id = u.id
                    WHERE c.user_id = :user_id
                    ORDER BY c.created_at DESC";
            return $this->db->query($sql, ['user_id' => $userId]);
        } catch (Exception $e) {
            error_log('Erreur Commande::getByUserId - ' . $e->getMessage());
            return [];
        }
    }

    public function updateStatut(int $id, string $statut): bool {
        try {
            return $this->db->execute(
                "UPDATE commandes SET statut = :statut, updated_at = NOW() WHERE id = :id",
                ['statut' => $statut, 'id' => $id]
            );
        } catch (Exception $e) {
            error_log('Erreur Commande::updateStatut - ' . $e->getMessage());
            return false;
        }
    }

    public function update(int $id, array $data): bool {
        try {
            $sql = "UPDATE commandes SET
                        adresse_livraison = :adresse_livraison,
                        ville             = :ville,
                        code_postal       = :code_postal,
                        telephone         = :telephone,
                        mode_paiement     = :mode_paiement,
                        statut            = :statut,
                        notes             = :notes,
                        updated_at        = NOW()
                    WHERE id = :id";
            $data['id'] = $id;
            return $this->db->execute($sql, $data);
        } catch (Exception $e) {
            error_log('Erreur Commande::update - ' . $e->getMessage());
            return false;
        }
    }

    public function delete(int $id): bool {
        try {
            // Les details sont supprimés en CASCADE
            return $this->db->execute("DELETE FROM commandes WHERE id = :id", ['id' => $id]);
        } catch (Exception $e) {
            error_log('Erreur Commande::delete - ' . $e->getMessage());
            return false;
        }
    }

    public function getStats(): array {
        try {
            return [
                'total'         => (int)$this->db->queryScalar("SELECT COUNT(*) FROM commandes"),
                'en_attente'    => (int)$this->db->queryScalar("SELECT COUNT(*) FROM commandes WHERE statut='en_attente'"),
                'confirmees'    => (int)$this->db->queryScalar("SELECT COUNT(*) FROM commandes WHERE statut='confirmee'"),
                'livrees'       => (int)$this->db->queryScalar("SELECT COUNT(*) FROM commandes WHERE statut='livree'"),
                'annulees'      => (int)$this->db->queryScalar("SELECT COUNT(*) FROM commandes WHERE statut='annulee'"),
                'ca_total'      => (float)$this->db->queryScalar("SELECT COALESCE(SUM(total_ttc),0) FROM commandes WHERE statut != 'annulee'"),
            ];
        } catch (Exception $e) {
            return ['total'=>0,'en_attente'=>0,'confirmees'=>0,'livrees'=>0,'annulees'=>0,'ca_total'=>0];
        }
    }

    public function generateNumero(): string {
        return 'CMD-' . date('Y') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
    }
}
