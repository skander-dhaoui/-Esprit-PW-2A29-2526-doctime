<?php

require_once __DIR__ . '/../config/database.php';

class Sponsor {

    private Database $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    // ─────────────────────────────────────────
    //  CRUD de base
    // ─────────────────────────────────────────
    public function create(array $data): ?int {
        try {
            $sql = "INSERT INTO sponsors (nom, niveau, email, telephone, secteur, budget, description, logo, site_web, statut, date_debut, date_fin, contact_nom, contact_prenom, contact_email, contact_telephone, notes, created_at, updated_at)
                    VALUES (:nom, :niveau, :email, :telephone, :secteur, :budget, :description, :logo, :site_web, :statut, :date_debut, :date_fin, :contact_nom, :contact_prenom, :contact_email, :contact_telephone, :notes, NOW(), NOW())";

            $result = $this->db->execute($sql, $data);
            return $result ? $this->db->lastInsertId() : null;
        } catch (Exception $e) {
            error_log('Erreur Sponsor::create - ' . $e->getMessage());
            return null;
        }
    }

    public function getById(int $id): ?array {
        try {
            $sql = "SELECT * FROM sponsors WHERE id = :id";
            $result = $this->db->query($sql, ['id' => $id]);
            return $result ? $result[0] : null;
        } catch (Exception $e) {
            error_log('Erreur Sponsor::getById - ' . $e->getMessage());
            return null;
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

            $fields[] = "updated_at = NOW()";

            $sql = "UPDATE sponsors SET " . implode(', ', $fields) . " WHERE id = :id";
            return $this->db->execute($sql, $values);
        } catch (Exception $e) {
            error_log('Erreur Sponsor::update - ' . $e->getMessage());
            return false;
        }
    }

    public function delete(int $id): bool {
        try {
            $sql = "DELETE FROM sponsors WHERE id = :id";
            return $this->db->execute($sql, ['id' => $id]);
        } catch (Exception $e) {
            error_log('Erreur Sponsor::delete - ' . $e->getMessage());
            return false;
        }
    }

    // ─────────────────────────────────────────
    //  Récupération avec filtres
    // ─────────────────────────────────────────
    public function getAll(int $offset = 0, int $limit = 20, string $filter = 'actif', string $search = '', string $secteur = ''): array {
        try {
            $where = "WHERE 1=1";

            if ($filter !== 'all') {
                $where .= " AND s.statut = :statut";
            }

            if (!empty($search)) {
                $where .= " AND (s.nom LIKE :search OR s.email LIKE :search OR s.telephone LIKE :search)";
            }

            if (!empty($secteur)) {
                $where .= " AND s.secteur = :secteur";
            }

            $sql = "SELECT s.* FROM sponsors s
                    $where
                    ORDER BY s.created_at DESC
                    LIMIT :offset, :limit";

            $params = ['offset' => $offset, 'limit' => $limit];
            if ($filter !== 'all') {
                $params['statut'] = $filter;
            }
            if (!empty($search)) {
                $params['search'] = "%$search%";
            }
            if (!empty($secteur)) {
                $params['secteur'] = $secteur;
            }

            return $this->db->query($sql, $params);
        } catch (Exception $e) {
            error_log('Erreur Sponsor::getAll - ' . $e->getMessage());
            return [];
        }
    }

    public function countAll(string $filter = 'actif', string $search = '', string $secteur = ''): int {
        try {
            $where = "WHERE 1=1";

            if ($filter !== 'all') {
                $where .= " AND s.statut = :statut";
            }

            if (!empty($search)) {
                $where .= " AND (s.nom LIKE :search OR s.email LIKE :search)";
            }

            if (!empty($secteur)) {
                $where .= " AND s.secteur = :secteur";
            }

            $sql = "SELECT COUNT(*) as count FROM sponsors s $where";

            $params = [];
            if ($filter !== 'all') {
                $params['statut'] = $filter;
            }
            if (!empty($search)) {
                $params['search'] = "%$search%";
            }
            if (!empty($secteur)) {
                $params['secteur'] = $secteur;
            }

            $result = $this->db->query($sql, $params);
            return $result[0]['count'] ?? 0;
        } catch (Exception $e) {
            error_log('Erreur Sponsor::countAll - ' . $e->getMessage());
            return 0;
        }
    }

    public function getActive(int $offset = 0, int $limit = 20, string $search = '', string $secteur = ''): array {
        try {
            $where = "WHERE s.statut = 'actif'";

            if (!empty($search)) {
                $where .= " AND (s.nom LIKE :search OR s.email LIKE :search)";
            }

            if (!empty($secteur)) {
                $where .= " AND s.secteur = :secteur";
            }

            $sql = "SELECT s.*, 
                           COUNT(DISTINCT p.id) as nb_produits,
                           COUNT(DISTINCT c.id) as nb_campagnes
                    FROM sponsors s
                    LEFT JOIN sponsor_produits p ON s.id = p.sponsor_id
                    LEFT JOIN sponsor_campagnes c ON s.id = c.sponsor_id
                    $where
                    GROUP BY s.id
                    ORDER BY s.nom ASC
                    LIMIT :offset, :limit";

            $params = ['offset' => $offset, 'limit' => $limit];
            if (!empty($search)) {
                $params['search'] = "%$search%";
            }
            if (!empty($secteur)) {
                $params['secteur'] = $secteur;
            }

            return $this->db->query($sql, $params);
        } catch (Exception $e) {
            error_log('Erreur Sponsor::getActive - ' . $e->getMessage());
            return [];
        }
    }

    public function countActive(string $search = '', string $secteur = ''): int {
        try {
            $where = "WHERE s.statut = 'actif'";

            if (!empty($search)) {
                $where .= " AND (s.nom LIKE :search OR s.email LIKE :search)";
            }

            if (!empty($secteur)) {
                $where .= " AND s.secteur = :secteur";
            }

            $sql = "SELECT COUNT(*) as count FROM sponsors s $where";

            $params = [];
            if (!empty($search)) {
                $params['search'] = "%$search%";
            }
            if (!empty($secteur)) {
                $params['secteur'] = $secteur;
            }

            $result = $this->db->query($sql, $params);
            return $result[0]['count'] ?? 0;
        } catch (Exception $e) {
            error_log('Erreur Sponsor::countActive - ' . $e->getMessage());
            return 0;
        }
    }

    public function getRecent(int $limit = 10): array {
        try {
            $sql = "SELECT s.* FROM sponsors s
                    WHERE s.statut = 'actif'
                    ORDER BY s.created_at DESC
                    LIMIT :limit";

            return $this->db->query($sql, ['limit' => $limit]);
        } catch (Exception $e) {
            error_log('Erreur Sponsor::getRecent - ' . $e->getMessage());
            return [];
        }
    }

    public function getExpired(): array {
        try {
            $sql = "SELECT s.* FROM sponsors s
                    WHERE s.statut = 'actif' AND s.date_fin < NOW()
                    ORDER BY s.date_fin DESC";

            return $this->db->query($sql);
        } catch (Exception $e) {
            error_log('Erreur Sponsor::getExpired - ' . $e->getMessage());
            return [];
        }
    }

    public function getExpiringSoon(int $jours = 30): array {
        try {
            $sql = "SELECT s.*, DATEDIFF(s.date_fin, NOW()) as jours_restants
                    FROM sponsors s
                    WHERE s.statut = 'actif' 
                    AND DATE(s.date_fin) BETWEEN DATE(NOW()) AND DATE(DATE_ADD(NOW(), INTERVAL :jours DAY))
                    ORDER BY s.date_fin ASC";

            return $this->db->query($sql, ['jours' => $jours]);
        } catch (Exception $e) {
            error_log('Erreur Sponsor::getExpiringsoon - ' . $e->getMessage());
            return [];
        }
    }

    public function getByStatut(string $statut, int $offset = 0, int $limit = 20): array {
        try {
            $sql = "SELECT s.* FROM sponsors s
                    WHERE s.statut = :statut
                    ORDER BY s.created_at DESC
                    LIMIT :offset, :limit";

            return $this->db->query($sql, ['statut' => $statut, 'offset' => $offset, 'limit' => $limit]);
        } catch (Exception $e) {
            error_log('Erreur Sponsor::getByStatut - ' . $e->getMessage());
            return [];
        }
    }

    public function getBySecteur(string $secteur, int $offset = 0, int $limit = 20): array {
        try {
            $sql = "SELECT s.*, COUNT(DISTINCT p.id) as nb_produits
                    FROM sponsors s
                    LEFT JOIN sponsor_produits p ON s.id = p.sponsor_id
                    WHERE s.secteur = :secteur AND s.statut = 'actif'
                    GROUP BY s.id
                    ORDER BY s.nom ASC
                    LIMIT :offset, :limit";

            return $this->db->query($sql, ['secteur' => $secteur, 'offset' => $offset, 'limit' => $limit]);
        } catch (Exception $e) {
            error_log('Erreur Sponsor::getBySecteur - ' . $e->getMessage());
            return [];
        }
    }

    public function getByBudgetRange(float $minBudget, float $maxBudget, int $offset = 0, int $limit = 20): array {
        try {
            $sql = "SELECT s.* FROM sponsors s
                    WHERE s.budget >= :min AND s.budget <= :max AND s.statut = 'actif'
                    ORDER BY s.budget DESC
                    LIMIT :offset, :limit";

            return $this->db->query($sql, [
                'min' => $minBudget,
                'max' => $maxBudget,
                'offset' => $offset,
                'limit' => $limit,
            ]);
        } catch (Exception $e) {
            error_log('Erreur Sponsor::getByBudgetRange - ' . $e->getMessage());
            return [];
        }
    }

    public function getTopByBudget(int $limit = 10): array {
        try {
            $sql = "SELECT s.*, COUNT(DISTINCT p.id) as nb_produits, COUNT(DISTINCT c.id) as nb_campagnes
                    FROM sponsors s
                    LEFT JOIN sponsor_produits p ON s.id = p.sponsor_id
                    LEFT JOIN sponsor_campagnes c ON s.id = c.sponsor_id
                    WHERE s.statut = 'actif'
                    GROUP BY s.id
                    ORDER BY s.budget DESC
                    LIMIT :limit";

            return $this->db->query($sql, ['limit' => $limit]);
        } catch (Exception $e) {
            error_log('Erreur Sponsor::getTopByBudget - ' . $e->getMessage());
            return [];
        }
    }

    public function getTopByActivity(int $limit = 10): array {
        try {
            $sql = "SELECT s.*, 
                           COUNT(DISTINCT p.id) as nb_produits,
                           COUNT(DISTINCT ev.id) as nb_evenements,
                           COUNT(DISTINCT c.id) as nb_campagnes,
                           (COUNT(DISTINCT p.id) + COUNT(DISTINCT ev.id) + COUNT(DISTINCT c.id)) as activite_total
                    FROM sponsors s
                    LEFT JOIN sponsor_produits p ON s.id = p.sponsor_id
                    LEFT JOIN sponsor_evenements ev ON s.id = ev.sponsor_id
                    LEFT JOIN sponsor_campagnes c ON s.id = c.sponsor_id
                    WHERE s.statut = 'actif'
                    GROUP BY s.id
                    ORDER BY activite_total DESC
                    LIMIT :limit";

            return $this->db->query($sql, ['limit' => $limit]);
        } catch (Exception $e) {
            error_log('Erreur Sponsor::getTopByActivity - ' . $e->getMessage());
            return [];
        }
    }

    public function getSecteurs(): array {
        try {
            $sql = "SELECT DISTINCT secteur FROM sponsors WHERE secteur IS NOT NULL AND statut = 'actif' ORDER BY secteur ASC";
            return $this->db->query($sql);
        } catch (Exception $e) {
            error_log('Erreur Sponsor::getSecteurs - ' . $e->getMessage());
            return [];
        }
    }

    // ─────────────────────────────────────────
    //  Statistiques
    // ─────────────────────────────────────────
    public function getTotalBudget(): float {
        try {
            $sql = "SELECT SUM(budget) as total FROM sponsors WHERE statut = 'actif'";
            $result = $this->db->query($sql);
            return (float)($result[0]['total'] ?? 0);
        } catch (Exception $e) {
            error_log('Erreur Sponsor::getTotalBudget - ' . $e->getMessage());
            return 0.0;
        }
    }

    public function getAverageBudget(): float {
        try {
            $sql = "SELECT AVG(budget) as avg FROM sponsors WHERE statut = 'actif'";
            $result = $this->db->query($sql);
            return (float)($result[0]['avg'] ?? 0);
        } catch (Exception $e) {
            error_log('Erreur Sponsor::getAverageBudget - ' . $e->getMessage());
            return 0.0;
        }
    }

    public function getMaxBudget(): float {
        try {
            $sql = "SELECT MAX(budget) as max FROM sponsors WHERE statut = 'actif'";
            $result = $this->db->query($sql);
            return (float)($result[0]['max'] ?? 0);
        } catch (Exception $e) {
            error_log('Erreur Sponsor::getMaxBudget - ' . $e->getMessage());
            return 0.0;
        }
    }

    public function getMinBudget(): float {
        try {
            $sql = "SELECT MIN(budget) as min FROM sponsors WHERE statut = 'actif'";
            $result = $this->db->query($sql);
            return (float)($result[0]['min'] ?? 0);
        } catch (Exception $e) {
            error_log('Erreur Sponsor::getMinBudget - ' . $e->getMessage());
            return 0.0;
        }
    }

    public function countByStatut(string $statut): int {
        try {
            $sql = "SELECT COUNT(*) as count FROM sponsors WHERE statut = :statut";
            $result = $this->db->query($sql, ['statut' => $statut]);
            return $result[0]['count'] ?? 0;
        } catch (Exception $e) {
            error_log('Erreur Sponsor::countByStatut - ' . $e->getMessage());
            return 0;
        }
    }

    public function countBySecteur(string $secteur): int {
        try {
            $sql = "SELECT COUNT(*) as count FROM sponsors WHERE secteur = :secteur AND statut = 'actif'";
            $result = $this->db->query($sql, ['secteur' => $secteur]);
            return $result[0]['count'] ?? 0;
        } catch (Exception $e) {
            error_log('Erreur Sponsor::countBySecteur - ' . $e->getMessage());
            return 0;
        }
    }

    public function countExpired(): int {
        try {
            $sql = "SELECT COUNT(*) as count FROM sponsors WHERE statut = 'actif' AND date_fin < NOW()";
            $result = $this->db->query($sql);
            return $result[0]['count'] ?? 0;
        } catch (Exception $e) {
            error_log('Erreur Sponsor::countExpired - ' . $e->getMessage());
            return 0;
        }
    }

    public function countExpiringoon(int $jours = 30): int {
        try {
            $sql = "SELECT COUNT(*) as count FROM sponsors 
                    WHERE statut = 'actif' 
                    AND DATE(date_fin) BETWEEN DATE(NOW()) AND DATE(DATE_ADD(NOW(), INTERVAL :jours DAY))";
            $result = $this->db->query($sql, ['jours' => $jours]);
            return $result[0]['count'] ?? 0;
        } catch (Exception $e) {
            error_log('Erreur Sponsor::countExpiringoon - ' . $e->getMessage());
            return 0;
        }
    }

    // ─────────────────────────────────────────
    //  Produits sponsorisés
    // ─────────────────────────────────────────
    public function addProduct(int $sponsorId, int $productId, int $priorite = 0, string $description = ''): bool {
        try {
            $sql = "INSERT INTO sponsor_produits (sponsor_id, produit_id, priorite, description, created_at)
                    VALUES (:sponsor_id, :product_id, :priorite, :description, NOW())
                    ON DUPLICATE KEY UPDATE priorite = :priorite, description = :description";

            return $this->db->execute($sql, [
                'sponsor_id' => $sponsorId,
                'product_id' => $productId,
                'priorite' => $priorite,
                'description' => $description,
            ]);
        } catch (Exception $e) {
            error_log('Erreur Sponsor::addProduct - ' . $e->getMessage());
            return false;
        }
    }

    public function removeProduct(int $sponsorId, int $productId): bool {
        try {
            $sql = "DELETE FROM sponsor_produits WHERE sponsor_id = :sponsor_id AND produit_id = :product_id";
            return $this->db->execute($sql, ['sponsor_id' => $sponsorId, 'product_id' => $productId]);
        } catch (Exception $e) {
            error_log('Erreur Sponsor::removeProduct - ' . $e->getMessage());
            return false;
        }
    }

    public function getProducts(int $sponsorId): array {
        try {
            $sql = "SELECT p.*, sp.priorite, sp.description FROM produits p
                    LEFT JOIN sponsor_produits sp ON p.id = sp.produit_id
                    WHERE sp.sponsor_id = :sponsor_id
                    ORDER BY sp.priorite DESC, p.nom ASC";

            return $this->db->query($sql, ['sponsor_id' => $sponsorId]);
        } catch (Exception $e) {
            error_log('Erreur Sponsor::getProducts - ' . $e->getMessage());
            return [];
        }
    }

    public function countProducts(int $sponsorId): int {
        try {
            $sql = "SELECT COUNT(*) as count FROM sponsor_produits WHERE sponsor_id = :sponsor_id";
            $result = $this->db->query($sql, ['sponsor_id' => $sponsorId]);
            return $result[0]['count'] ?? 0;
        } catch (Exception $e) {
            error_log('Erreur Sponsor::countProducts - ' . $e->getMessage());
            return 0;
        }
    }

    // ─────────────────────────────────────────
    //  Événements sponsorisés
    // ─────────────────────────────────────────
    public function addEvent(int $sponsorId, array $data): ?int {
        try {
            $sql = "INSERT INTO sponsor_evenements (sponsor_id, nom, date_debut, date_fin, lieu, budget, description, created_at)
                    VALUES (:sponsor_id, :nom, :date_debut, :date_fin, :lieu, :budget, :description, NOW())";

            $data['sponsor_id'] = $sponsorId;
            $result = $this->db->execute($sql, $data);
            return $result ? $this->db->lastInsertId() : null;
        } catch (Exception $e) {
            error_log('Erreur Sponsor::addEvent - ' . $e->getMessage());
            return null;
        }
    }

    public function removeEvent(int $eventId): bool {
        try {
            $sql = "DELETE FROM sponsor_evenements WHERE id = :id";
            return $this->db->execute($sql, ['id' => $eventId]);
        } catch (Exception $e) {
            error_log('Erreur Sponsor::removeEvent - ' . $e->getMessage());
            return false;
        }
    }

    public function getEvents(int $sponsorId): array {
        try {
            $sql = "SELECT * FROM sponsor_evenements WHERE sponsor_id = :sponsor_id ORDER BY date_debut DESC";
            return $this->db->query($sql, ['sponsor_id' => $sponsorId]);
        } catch (Exception $e) {
            error_log('Erreur Sponsor::getEvents - ' . $e->getMessage());
            return [];
        }
    }

    public function countEvents(int $sponsorId): int {
        try {
            $sql = "SELECT COUNT(*) as count FROM sponsor_evenements WHERE sponsor_id = :sponsor_id";
            $result = $this->db->query($sql, ['sponsor_id' => $sponsorId]);
            return $result[0]['count'] ?? 0;
        } catch (Exception $e) {
            error_log('Erreur Sponsor::countEvents - ' . $e->getMessage());
            return 0;
        }
    }

    // ─────────────────────────────────────────
    //  Campagnes sponsorisées
    // ─────────────────────────────────────────
    public function addCampaign(int $sponsorId, array $data): ?int {
        try {
            $sql = "INSERT INTO sponsor_campagnes (sponsor_id, nom, type, date_debut, date_fin, budget, description, created_at)
                    VALUES (:sponsor_id, :nom, :type, :date_debut, :date_fin, :budget, :description, NOW())";

            $data['sponsor_id'] = $sponsorId;
            $result = $this->db->execute($sql, $data);
            return $result ? $this->db->lastInsertId() : null;
        } catch (Exception $e) {
            error_log('Erreur Sponsor::addCampaign - ' . $e->getMessage());
            return null;
        }
    }

    public function removeCampaign(int $campaignId): bool {
        try {
            $sql = "DELETE FROM sponsor_campagnes WHERE id = :id";
            return $this->db->execute($sql, ['id' => $campaignId]);
        } catch (Exception $e) {
            error_log('Erreur Sponsor::removeCampaign - ' . $e->getMessage());
            return false;
        }
    }

    public function getCampaigns(int $sponsorId): array {
        try {
            $sql = "SELECT * FROM sponsor_campagnes WHERE sponsor_id = :sponsor_id ORDER BY date_debut DESC";
            return $this->db->query($sql, ['sponsor_id' => $sponsorId]);
        } catch (Exception $e) {
            error_log('Erreur Sponsor::getCampaigns - ' . $e->getMessage());
            return [];
        }
    }

    public function countCampaigns(int $sponsorId): int {
        try {
            $sql = "SELECT COUNT(*) as count FROM sponsor_campagnes WHERE sponsor_id = :sponsor_id";
            $result = $this->db->query($sql, ['sponsor_id' => $sponsorId]);
            return $result[0]['count'] ?? 0;
        } catch (Exception $e) {
            error_log('Erreur Sponsor::countCampaigns - ' . $e->getMessage());
            return 0;
        }
    }

    // ─────────────────────────────────────────
    //  Contrats
    // ─────────────────────────────────────────
    public function addContract(int $sponsorId, array $data): ?int {
        try {
            $sql = "INSERT INTO sponsor_contrats (sponsor_id, nom, date_debut, date_fin, montant, conditions, document_url, statut, created_at)
                    VALUES (:sponsor_id, :nom, :date_debut, :date_fin, :montant, :conditions, :document_url, :statut, NOW())";

            $data['sponsor_id'] = $sponsorId;
            $result = $this->db->execute($sql, $data);
            return $result ? $this->db->lastInsertId() : null;
        } catch (Exception $e) {
            error_log('Erreur Sponsor::addContract - ' . $e->getMessage());
            return null;
        }
    }

    public function getContracts(int $sponsorId): array {
        try {
            $sql = "SELECT * FROM sponsor_contrats WHERE sponsor_id = :sponsor_id ORDER BY date_debut DESC";
            return $this->db->query($sql, ['sponsor_id' => $sponsorId]);
        } catch (Exception $e) {
            error_log('Erreur Sponsor::getContracts - ' . $e->getMessage());
            return [];
        }
    }

    public function countContracts(int $sponsorId): int {
        try {
            $sql = "SELECT COUNT(*) as count FROM sponsor_contrats WHERE sponsor_id = :sponsor_id";
            $result = $this->db->query($sql, ['sponsor_id' => $sponsorId]);
            return $result[0]['count'] ?? 0;
        } catch (Exception $e) {
            error_log('Erreur Sponsor::countContracts - ' . $e->getMessage());
            return 0;
        }
    }

    // ─────────────────────────────────────────
    //  Paiements
    // ─────────────────────────────────────────
    public function addPayment(int $sponsorId, array $data): ?int {
        try {
            $sql = "INSERT INTO sponsor_paiements (sponsor_id, montant, date_paiement, type_paiement, reference, description, statut, created_at)
                    VALUES (:sponsor_id, :montant, :date_paiement, :type_paiement, :reference, :description, :statut, NOW())";

            $data['sponsor_id'] = $sponsorId;
            $result = $this->db->execute($sql, $data);
            return $result ? $this->db->lastInsertId() : null;
        } catch (Exception $e) {
            error_log('Erreur Sponsor::addPayment - ' . $e->getMessage());
            return null;
        }
    }

    public function getPayments(int $sponsorId): array {
        try {
            $sql = "SELECT * FROM sponsor_paiements WHERE sponsor_id = :sponsor_id ORDER BY date_paiement DESC";
            return $this->db->query($sql, ['sponsor_id' => $sponsorId]);
        } catch (Exception $e) {
            error_log('Erreur Sponsor::getPayments - ' . $e->getMessage());
            return [];
        }
    }

    public function getTotalPaid(int $sponsorId): float {
        try {
            $sql = "SELECT SUM(montant) as total FROM sponsor_paiements WHERE sponsor_id = :sponsor_id AND statut = 'complète'";
            $result = $this->db->query($sql, ['sponsor_id' => $sponsorId]);
            return (float)($result[0]['total'] ?? 0);
        } catch (Exception $e) {
            error_log('Erreur Sponsor::getTotalPaid - ' . $e->getMessage());
            return 0.0;
        }
    }

    public function getRemainingBalance(int $sponsorId): float {
        try {
            $sponsor = $this->getById($sponsorId);
            if (!$sponsor) {
                return 0.0;
            }

            $totalPaid = $this->getTotalPaid($sponsorId);
            return max(0, (float)$sponsor['budget'] - $totalPaid);
        } catch (Exception $e) {
            error_log('Erreur Sponsor::getRemainingBalance - ' . $e->getMessage());
            return 0.0;
        }
    }

    public function countPayments(int $sponsorId): int {
        try {
            $sql = "SELECT COUNT(*) as count FROM sponsor_paiements WHERE sponsor_id = :sponsor_id";
            $result = $this->db->query($sql, ['sponsor_id' => $sponsorId]);
            return $result[0]['count'] ?? 0;
        } catch (Exception $e) {
            error_log('Erreur Sponsor::countPayments - ' . $e->getMessage());
            return 0;
        }
    }

    // ─────────────────────────────────────────
    //  Communications
    // ─────────────────────────────────────────
    public function addCommunication(int $sponsorId, array $data): ?int {
        try {
            $sql = "INSERT INTO sponsor_communications (sponsor_id, type, sujet, contenu, date_envoi, created_at)
                    VALUES (:sponsor_id, :type, :sujet, :contenu, :date_envoi, NOW())";

            $data['sponsor_id'] = $sponsorId;
            $result = $this->db->execute($sql, $data);
            return $result ? $this->db->lastInsertId() : null;
        } catch (Exception $e) {
            error_log('Erreur Sponsor::addCommunication - ' . $e->getMessage());
            return null;
        }
    }

    public function getCommunications(int $sponsorId): array {
        try {
            $sql = "SELECT * FROM sponsor_communications WHERE sponsor_id = :sponsor_id ORDER BY date_envoi DESC";
            return $this->db->query($sql, ['sponsor_id' => $sponsorId]);
        } catch (Exception $e) {
            error_log('Erreur Sponsor::getCommunications - ' . $e->getMessage());
            return [];
        }
    }

    // ─────────────────────────────────────────
    //  Historique
    // ─────────────────────────────────────────
    public function addHistorique(int $sponsorId, array $data): bool {
        try {
            $sql = "INSERT INTO sponsor_historiques (sponsor_id, action, description, user_id, created_at)
                    VALUES (:sponsor_id, :action, :description, :user_id, NOW())";

            $data['sponsor_id'] = $sponsorId;
            return $this->db->execute($sql, $data);
        } catch (Exception $e) {
            error_log('Erreur Sponsor::addHistorique - ' . $e->getMessage());
            return false;
        }
    }

    public function getHistorique(int $sponsorId): array {
        try {
            $sql = "SELECT h.*, u.nom, u.prenom FROM sponsor_historiques h
                    LEFT JOIN users u ON h.user_id = u.id
                    WHERE h.sponsor_id = :sponsor_id
                    ORDER BY h.created_at DESC";

            return $this->db->query($sql, ['sponsor_id' => $sponsorId]);
        } catch (Exception $e) {
            error_log('Erreur Sponsor::getHistorique - ' . $e->getMessage());
            return [];
        }
    }

    // ─────────────────────────────────────────
    //  API
    // ─────────────────────────────────────────
    public function getApiListing(int $offset, int $limit): array {
        try {
            $sql = "SELECT s.id, s.nom, s.logo, s.secteur, s.budget, s.site_web FROM sponsors s
                    WHERE s.statut = 'actif'
                    ORDER BY s.nom ASC
                    LIMIT :offset, :limit";

            return $this->db->query($sql, ['offset' => $offset, 'limit' => $limit]);
        } catch (Exception $e) {
            error_log('Erreur Sponsor::getApiListing - ' . $e->getMessage());
            return [];
        }
    }

    public function getApiDetail(int $id): ?array {
        try {
            $sql = "SELECT s.*, 
                           COUNT(DISTINCT p.id) as nb_produits,
                           COUNT(DISTINCT c.id) as nb_campagnes
                    FROM sponsors s
                    LEFT JOIN sponsor_produits p ON s.id = p.sponsor_id
                    LEFT JOIN sponsor_campagnes c ON s.id = c.sponsor_id
                    WHERE s.id = :id AND s.statut = 'actif'
                    GROUP BY s.id";

            $result = $this->db->query($sql, ['id' => $id]);
            return $result ? $result[0] : null;
        } catch (Exception $e) {
            error_log('Erreur Sponsor::getApiDetail - ' . $e->getMessage());
            return null;
        }
    }

    public function searchApi(string $search, int $limit = 10): array {
        try {
            $sql = "SELECT s.id, s.nom, s.logo, s.secteur FROM sponsors s
                    WHERE (s.nom LIKE :search OR s.description LIKE :search) AND s.statut = 'actif'
                    ORDER BY s.nom ASC
                    LIMIT :limit";

            return $this->db->query($sql, ['search' => "%$search%", 'limit' => $limit]);
        } catch (Exception $e) {
            error_log('Erreur Sponsor::searchApi - ' . $e->getMessage());
            return [];
        }
    }

    // ─────────────────────────────────────────
    //  Import/Export
    // ─────────────────────────────────────────
    public function exportList(): array {
        try {
            $sql = "SELECT * FROM sponsors WHERE statut = 'actif' ORDER BY nom ASC";
            return $this->db->query($sql);
        } catch (Exception $e) {
            error_log('Erreur Sponsor::exportList - ' . $e->getMessage());
            return [];
        }
    }

    public function importFromArray(array $data): ?int {
        try {
            $sql = "INSERT INTO sponsors (nom, email, telephone, secteur, budget, description, site_web, statut, created_at)
                    VALUES (:nom, :email, :telephone, :secteur, :budget, :description, :site_web, :statut, NOW())";

            $result = $this->db->execute($sql, $data);
            return $result ? $this->db->lastInsertId() : null;
        } catch (Exception $e) {
            error_log('Erreur Sponsor::importFromArray - ' . $e->getMessage());
            return null;
        }
    }
}
?>