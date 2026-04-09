<?php

require_once __DIR__ . '/../config/database.php'';  // ✅
class Commande {

    private Database $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    public function create(array $data): ?int {
        try {
            $sql = "INSERT INTO commandes (client_id, numero, date_commande, statut, montant_total, montant_ht, tva, adresse_livraison, code_postal, ville, telephone, notes, mode_paiement, created_at)
                    VALUES (:client_id, :numero, :date_commande, :statut, :montant_total, :montant_ht, :tva, :adresse_livraison, :code_postal, :ville, :telephone, :notes, :mode_paiement, NOW())";

            $result = $this->db->execute($sql, $data);
            return $result ? $this->db->lastInsertId() : null;
        } catch (Exception $e) {
            error_log('Erreur Commande::create - ' . $e->getMessage());
            return null;
        }
    }

    public function getById(int $id): ?array {
        try {
            $sql = "SELECT c.*, cl.nom, cl.prenom, cl.email
                    FROM commandes c
                    LEFT JOIN clients cl ON c.client_id = cl.id
                    WHERE c.id = :id";

            $result = $this->db->query($sql, ['id' => $id]);
            return $result ? $result[0] : null;
        } catch (Exception $e) {
            error_log('Erreur Commande::getById - ' . $e->getMessage());
            return null;
        }
    }

    public function getAll(string $filter = 'all', int $offset = 0, int $limit = 20, string $search = '', string $dateDebut = '', string $dateFin = ''): array {
        try {
            $where = "WHERE 1=1";

            if ($filter !== 'all') {
                $where .= " AND c.statut = :statut";
            }

            if (!empty($search)) {
                $where .= " AND (c.numero LIKE :search OR cl.nom LIKE :search OR cl.prenom LIKE :search)";
            }

            if (!empty($dateDebut)) {
                $where .= " AND c.date_commande >= :date_debut";
            }

            if (!empty($dateFin)) {
                $where .= " AND c.date_commande <= :date_fin";
            }

            $sql = "SELECT c.*, cl.nom, cl.prenom, cl.email,
                           COUNT(DISTINCT cl.id) as client_id
                    FROM commandes c
                    LEFT JOIN clients cl ON c.client_id = cl.id
                    $where
                    GROUP BY c.id
                    ORDER BY c.date_commande DESC
                    LIMIT :offset, :limit";

            $params = ['offset' => $offset, 'limit' => $limit];
            if ($filter !== 'all') {
                $params['statut'] = $filter;
            }
            if (!empty($search)) {
                $params['search'] = "%$search%";
            }
            if (!empty($dateDebut)) {
                $params['date_debut'] = $dateDebut . ' 00:00:00';
            }
            if (!empty($dateFin)) {
                $params['date_fin'] = $dateFin . ' 23:59:59';
            }

            return $this->db->query($sql, $params);
        } catch (Exception $e) {
            error_log('Erreur Commande::getAll - ' . $e->getMessage());
            return [];
        }
    }

    public function countAll(string $filter = 'all', string $search = '', string $dateDebut = '', string $dateFin = ''): int {
        try {
            $where = "WHERE 1=1";

            if ($filter !== 'all') {
                $where .= " AND c.statut = :statut";
            }

            if (!empty($search)) {
                $where .= " AND (c.numero LIKE :search OR cl.nom LIKE :search OR cl.prenom LIKE :search)";
            }

            if (!empty($dateDebut)) {
                $where .= " AND c.date_commande >= :date_debut";
            }

            if (!empty($dateFin)) {
                $where .= " AND c.date_commande <= :date_fin";
            }

            $sql = "SELECT COUNT(*) as count FROM commandes c LEFT JOIN clients cl ON c.client_id = cl.id $where";

            $params = [];
            if ($filter !== 'all') {
                $params['statut'] = $filter;
            }
            if (!empty($search)) {
                $params['search'] = "%$search%";
            }
            if (!empty($dateDebut)) {
                $params['date_debut'] = $dateDebut . ' 00:00:00';
            }
            if (!empty($dateFin)) {
                $params['date_fin'] = $dateFin . ' 23:59:59';
            }

            $result = $this->db->query($sql, $params);
            return $result[0]['count'] ?? 0;
        } catch (Exception $e) {
            error_log('Erreur Commande::countAll - ' . $e->getMessage());
            return 0;
        }
    }

    public function countByStatus(string $statut): int {
        try {
            $sql = "SELECT COUNT(*) as count FROM commandes WHERE statut = :statut";
            $result = $this->db->query($sql, ['statut' => $statut]);
            return $result[0]['count'] ?? 0;
        } catch (Exception $e) {
            error_log('Erreur Commande::countByStatus - ' . $e->getMessage());
            return 0;
        }
    }

    public function getByClientOrderByRecent(int $clientId, string $filter = 'all'): array {
        try {
            $where = "WHERE c.client_id = :client_id";

            if ($filter !== 'all') {
                $where .= " AND c.statut = :statut";
            }

            $sql = "SELECT c.*, COUNT(DISTINCT cl.id) FROM commandes c
                    LEFT JOIN clients cl ON c.client_id = cl.id
                    $where
                    GROUP BY c.id
                    ORDER BY c.date_commande DESC";

            $params = ['client_id' => $clientId];
            if ($filter !== 'all') {
                $params['statut'] = $filter;
            }

            return $this->db->query($sql, $params);
        } catch (Exception $e) {
            error_log('Erreur Commande::getByClientOrderByRecent - ' . $e->getMessage());
            return [];
        }
    }

    public function getByClientOrderByOldest(int $clientId, string $filter = 'all'): array {
        try {
            $where = "WHERE c.client_id = :client_id";

            if ($filter !== 'all') {
                $where .= " AND c.statut = :statut";
            }

            $sql = "SELECT c.* FROM commandes c $where ORDER BY c.date_commande ASC";

            $params = ['client_id' => $clientId];
            if ($filter !== 'all') {
                $params['statut'] = $filter;
            }

            return $this->db->query($sql, $params);
        } catch (Exception $e) {
            error_log('Erreur Commande::getByClientOrderByOldest - ' . $e->getMessage());
            return [];
        }
    }

    public function getByClientOrderByMontant(int $clientId, string $filter = 'all', string $order = 'DESC'): array {
        try {
            $where = "WHERE c.client_id = :client_id";

            if ($filter !== 'all') {
                $where .= " AND c.statut = :statut";
            }

            $sql = "SELECT c.* FROM commandes c $where ORDER BY c.montant_total $order";

            $params = ['client_id' => $clientId];
            if ($filter !== 'all') {
                $params['statut'] = $filter;
            }

            return $this->db->query($sql, $params);
        } catch (Exception $e) {
            error_log('Erreur Commande::getByClientOrderByMontant - ' . $e->getMessage());
            return [];
        }
    }

    public function countByClient(int $clientId): int {
        try {
            $sql = "SELECT COUNT(*) as count FROM commandes WHERE client_id = :client_id";
            $result = $this->db->query($sql, ['client_id' => $clientId]);
            return $result[0]['count'] ?? 0;
        } catch (Exception $e) {
            error_log('Erreur Commande::countByClient - ' . $e->getMessage());
            return 0;
        }
    }

    public function getTotalClientMontant(int $clientId): float {
        try {
            $sql = "SELECT SUM(montant_total) as total FROM commandes WHERE client_id = :client_id";
            $result = $this->db->query($sql, ['client_id' => $clientId]);
            return (float)($result[0]['total'] ?? 0);
        } catch (Exception $e) {
            error_log('Erreur Commande::getTotalClientMontant - ' . $e->getMessage());
            return 0.0;
        }
    }

    public function getLastOrderDate(int $clientId): ?string {
        try {
            $sql = "SELECT MAX(date_commande) as last_date FROM commandes WHERE client_id = :client_id";
            $result = $this->db->query($sql, ['client_id' => $clientId]);
            return $result[0]['last_date'] ?? null;
        } catch (Exception $e) {
            error_log('Erreur Commande::getLastOrderDate - ' . $e->getMessage());
            return null;
        }
    }

    public function getTotalMontant(): float {
        try {
            $sql = "SELECT SUM(montant_total) as total FROM commandes WHERE statut != 'annulée'";
            $result = $this->db->query($sql);
            return (float)($result[0]['total'] ?? 0);
        } catch (Exception $e) {
            error_log('Erreur Commande::getTotalMontant - ' . $e->getMessage());
            return 0.0;
        }
    }

    public function getTotalMontantMois(): float {
        try {
            $sql = "SELECT SUM(montant_total) as total FROM commandes 
                    WHERE MONTH(date_commande) = MONTH(NOW()) 
                    AND YEAR(date_commande) = YEAR(NOW())
                    AND statut != 'annulée'";
            $result = $this->db->query($sql);
            return (float)($result[0]['total'] ?? 0);
        } catch (Exception $e) {
            error_log('Erreur Commande::getTotalMontantMois - ' . $e->getMessage());
            return 0.0;
        }
    }

    public function getTotalMontantJour(): float {
        try {
            $sql = "SELECT SUM(montant_total) as total FROM commandes 
                    WHERE DATE(date_commande) = DATE(NOW())
                    AND statut != 'annulée'";
            $result = $this->db->query($sql);
            return (float)($result[0]['total'] ?? 0);
        } catch (Exception $e) {
            error_log('Erreur Commande::getTotalMontantJour - ' . $e->getMessage());
            return 0.0;
        }
    }

    public function getAverageCommande(): float {
        try {
            $sql = "SELECT AVG(montant_total) as avg FROM commandes WHERE statut != 'annulée'";
            $result = $this->db->query($sql);
            return (float)($result[0]['avg'] ?? 0);
        } catch (Exception $e) {
            error_log('Erreur Commande::getAverageCommande - ' . $e->getMessage());
            return 0.0;
        }
    }

    public function update(int $id, array $data): bool {
        try {
            $fields = [];
            $values = ['id' => $id];

            foreach ($data as $key => $value) {
                $fields[] = "$key = :$key";
                $values[$key] = $value;
            }

            $sql = "UPDATE commandes SET " . implode(', ', $fields) . " WHERE id = :id";
            return $this->db->execute($sql, $values);
        } catch (Exception $e) {
            error_log('Erreur Commande::update - ' . $e->getMessage());
            return false;
        }
    }

    public function addHistorique(int $commandeId, int $userId, string $description, string $statut): bool {
        try {
            $sql = "INSERT INTO historique_commandes (commande_id, user_id, description, statut, created_at)
                    VALUES (:commande_id, :user_id, :description, :statut, NOW())";

            return $this->db->execute($sql, [
                'commande_id' => $commandeId,
                'user_id' => $userId,
                'description' => $description,
                'statut' => $statut,
            ]);
        } catch (Exception $e) {
            error_log('Erreur Commande::addHistorique - ' . $e->getMessage());
            return false;
        }
    }

    public function getHistorique(int $commandeId): array {
        try {
            $sql = "SELECT h.*, u.nom, u.prenom FROM historique_commandes h
                    LEFT JOIN users u ON h.user_id = u.id
                    WHERE h.commande_id = :commande_id
                    ORDER BY h.created_at DESC";

            return $this->db->query($sql, ['commande_id' => $commandeId]);
        } catch (Exception $e) {
            error_log('Erreur Commande::getHistorique - ' . $e->getMessage());
            return [];
        }
    }

    public function getStatsParMois(int $mois, int $annee): array {
        try {
            $sql = "SELECT 
                           COUNT(*) as total_commandes,
                           SUM(CASE WHEN statut = 'livrée' THEN 1 ELSE 0 END) as commandes_livrees,
                           SUM(CASE WHEN statut = 'annulée' THEN 1 ELSE 0 END) as commandes_annulees,
                           SUM(montant_total) as montant_total,
                           AVG(montant_total) as montant_moyen
                    FROM commandes
                    WHERE MONTH(date_commande) = :mois
                    AND YEAR(date_commande) = :annee";

            $result = $this->db->query($sql, ['mois' => $mois, 'annee' => $annee]);
            return $result ? $result[0] : [];
        } catch (Exception $e) {
            error_log('Erreur Commande::getStatsParMois - ' . $e->getMessage());
            return [];
        }
    }
}
?>
