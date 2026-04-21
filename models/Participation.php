<?php

require_once __DIR__ . '/../config/database.php';

class Participation {

    private Database $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    // ─────────────────────────────────────────
    //  CRUD de base
    // ─────────────────────────────────────────
    public function create(array $data): ?int {
        try {
            $sql = "INSERT INTO participations (evenement_id, client_id, user_id, statut, nombre_places, prix_total, date_inscription, notes, created_at, updated_at)
                    VALUES (:evenement_id, :client_id, :user_id, :statut, :nombre_places, :prix_total, :date_inscription, :notes, NOW(), NOW())";

            $result = $this->db->execute($sql, $data);
            return $result ? $this->db->lastInsertId() : null;
        } catch (Exception $e) {
            error_log('Erreur Participation::create - ' . $e->getMessage());
            return null;
        }
    }

    public function getById(int $id): ?array {
        try {
            $sql = "SELECT p.*, 
                           e.titre as evenement_titre, e.date_debut as evenement_date, e.prix_unitaire,
                           c.nom as client_nom, c.prenom as client_prenom, c.email as client_email, c.telephone as client_telephone,
                           u.nom as user_nom, u.prenom as user_prenom, u.email as user_email
                    FROM participations p
                    LEFT JOIN evenements e ON p.evenement_id = e.id
                    LEFT JOIN clients c ON p.client_id = c.id
                    LEFT JOIN users u ON p.user_id = u.id
                    WHERE p.id = :id";

            $result = $this->db->query($sql, ['id' => $id]);
            return $result ? $result[0] : null;
        } catch (Exception $e) {
            error_log('Erreur Participation::getById - ' . $e->getMessage());
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

            $sql = "UPDATE participations SET " . implode(', ', $fields) . " WHERE id = :id";
            return $this->db->execute($sql, $values);
        } catch (Exception $e) {
            error_log('Erreur Participation::update - ' . $e->getMessage());
            return false;
        }
    }

    public function delete(int $id): bool {
        try {
            $sql = "DELETE FROM participations WHERE id = :id";
            return $this->db->execute($sql, ['id' => $id]);
        } catch (Exception $e) {
            error_log('Erreur Participation::delete - ' . $e->getMessage());
            return false;
        }
    }

    // ─────────────────────────────────────────
    //  Récupération avec filtres
    // ─────────────────────────────────────────
    public function getAll(int $offset = 0, int $limit = 20, string $filter = 'tous', string $search = '', int $userId = 0): array {
        try {
            $where = "WHERE 1=1";

            if ($filter !== 'tous') {
                $where .= " AND p.statut = :statut";
            }

            if (!empty($search)) {
                $where .= " AND (c.nom LIKE :search OR c.prenom LIKE :search OR e.titre LIKE :search)";
            }

            if ($userId > 0) {
                $where .= " AND p.user_id = :user_id";
            }

            $sql = "SELECT p.*, 
                           e.titre as evenement_titre, e.date_debut as evenement_date,
                           c.nom as client_nom, c.prenom as client_prenom, c.email as client_email,
                           u.nom as user_nom, u.prenom as user_prenom
                    FROM participations p
                    LEFT JOIN evenements e ON p.evenement_id = e.id
                    LEFT JOIN clients c ON p.client_id = c.id
                    LEFT JOIN users u ON p.user_id = u.id
                    $where
                    ORDER BY p.created_at DESC
                    LIMIT :offset, :limit";

            $params = ['offset' => $offset, 'limit' => $limit];
            if ($filter !== 'tous') {
                $params['statut'] = $filter;
            }
            if (!empty($search)) {
                $params['search'] = "%$search%";
            }
            if ($userId > 0) {
                $params['user_id'] = $userId;
            }

            return $this->db->query($sql, $params);
        } catch (Exception $e) {
            error_log('Erreur Participation::getAll - ' . $e->getMessage());
            return [];
        }
    }

    public function countAll(string $filter = 'tous', string $search = '', int $userId = 0): int {
        try {
            $where = "WHERE 1=1";

            if ($filter !== 'tous') {
                $where .= " AND p.statut = :statut";
            }

            if (!empty($search)) {
                $where .= " AND (c.nom LIKE :search OR c.prenom LIKE :search OR e.titre LIKE :search)";
            }

            if ($userId > 0) {
                $where .= " AND p.user_id = :user_id";
            }

            $sql = "SELECT COUNT(*) as count FROM participations p
                    LEFT JOIN evenements e ON p.evenement_id = e.id
                    LEFT JOIN clients c ON p.client_id = c.id
                    $where";

            $params = [];
            if ($filter !== 'tous') {
                $params['statut'] = $filter;
            }
            if (!empty($search)) {
                $params['search'] = "%$search%";
            }
            if ($userId > 0) {
                $params['user_id'] = $userId;
            }

            $result = $this->db->query($sql, $params);
            return $result[0]['count'] ?? 0;
        } catch (Exception $e) {
            error_log('Erreur Participation::countAll - ' . $e->getMessage());
            return 0;
        }
    }

    public function getByEvenement(int $evenementId, int $offset = 0, int $limit = 50, string $filter = 'tous'): array {
        try {
            $where = "WHERE p.evenement_id = :evenement_id";

            if ($filter !== 'tous') {
                $where .= " AND p.statut = :statut";
            }

            $sql = "SELECT p.*, 
                           c.nom as client_nom, c.prenom as client_prenom, c.email as client_email, c.telephone as client_telephone,
                           u.nom as user_nom, u.prenom as user_prenom
                    FROM participations p
                    LEFT JOIN clients c ON p.client_id = c.id
                    LEFT JOIN users u ON p.user_id = u.id
                    $where
                    ORDER BY p.date_inscription DESC
                    LIMIT :offset, :limit";

            $params = ['evenement_id' => $evenementId, 'offset' => $offset, 'limit' => $limit];
            if ($filter !== 'tous') {
                $params['statut'] = $filter;
            }

            return $this->db->query($sql, $params);
        } catch (Exception $e) {
            error_log('Erreur Participation::getByEvenement - ' . $e->getMessage());
            return [];
        }
    }

    public function getByClient(int $clientId, int $offset = 0, int $limit = 20, string $filter = 'tous'): array {
        try {
            $where = "WHERE p.client_id = :client_id";

            if ($filter !== 'tous') {
                $where .= " AND p.statut = :statut";
            }

            $sql = "SELECT p.*, 
                           e.titre as evenement_titre, e.date_debut as evenement_date, e.lieu as evenement_lieu,
                           u.nom as user_nom, u.prenom as user_prenom
                    FROM participations p
                    LEFT JOIN evenements e ON p.evenement_id = e.id
                    LEFT JOIN users u ON p.user_id = u.id
                    $where
                    ORDER BY e.date_debut DESC
                    LIMIT :offset, :limit";

            $params = ['client_id' => $clientId, 'offset' => $offset, 'limit' => $limit];
            if ($filter !== 'tous') {
                $params['statut'] = $filter;
            }

            return $this->db->query($sql, $params);
        } catch (Exception $e) {
            error_log('Erreur Participation::getByClient - ' . $e->getMessage());
            return [];
        }
    }

    public function getByUser(int $userId, int $offset = 0, int $limit = 20, string $filter = 'tous'): array {
        try {
            $where = "WHERE p.user_id = :user_id";

            if ($filter !== 'tous') {
                $where .= " AND p.statut = :statut";
            }

            $sql = "SELECT p.*, 
                           e.titre as evenement_titre, e.date_debut as evenement_date,
                           c.nom as client_nom, c.prenom as client_prenom, c.email as client_email
                    FROM participations p
                    LEFT JOIN evenements e ON p.evenement_id = e.id
                    LEFT JOIN clients c ON p.client_id = c.id
                    $where
                    ORDER BY e.date_debut DESC
                    LIMIT :offset, :limit";

            $params = ['user_id' => $userId, 'offset' => $offset, 'limit' => $limit];
            if ($filter !== 'tous') {
                $params['statut'] = $filter;
            }

            return $this->db->query($sql, $params);
        } catch (Exception $e) {
            error_log('Erreur Participation::getByUser - ' . $e->getMessage());
            return [];
        }
    }

    public function getRecent(int $limit = 10): array {
        try {
            $sql = "SELECT p.*, 
                           e.titre as evenement_titre, e.date_debut as evenement_date,
                           c.nom as client_nom, c.prenom as client_prenom,
                           u.nom as user_nom, u.prenom as user_prenom
                    FROM participations p
                    LEFT JOIN evenements e ON p.evenement_id = e.id
                    LEFT JOIN clients c ON p.client_id = c.id
                    LEFT JOIN users u ON p.user_id = u.id
                    ORDER BY p.created_at DESC
                    LIMIT :limit";

            return $this->db->query($sql, ['limit' => $limit]);
        } catch (Exception $e) {
            error_log('Erreur Participation::getRecent - ' . $e->getMessage());
            return [];
        }
    }

    public function getByStatut(string $statut, int $offset = 0, int $limit = 20): array {
        try {
            $sql = "SELECT p.*, 
                           e.titre as evenement_titre, e.date_debut as evenement_date,
                           c.nom as client_nom, c.prenom as client_prenom,
                           u.nom as user_nom, u.prenom as user_prenom
                    FROM participations p
                    LEFT JOIN evenements e ON p.evenement_id = e.id
                    LEFT JOIN clients c ON p.client_id = c.id
                    LEFT JOIN users u ON p.user_id = u.id
                    WHERE p.statut = :statut
                    ORDER BY p.created_at DESC
                    LIMIT :offset, :limit";

            return $this->db->query($sql, ['statut' => $statut, 'offset' => $offset, 'limit' => $limit]);
        } catch (Exception $e) {
            error_log('Erreur Participation::getByStatut - ' . $e->getMessage());
            return [];
        }
    }

    public function getConfirmed(int $offset = 0, int $limit = 20): array {
        try {
            $sql = "SELECT p.*, 
                           e.titre as evenement_titre, e.date_debut as evenement_date,
                           c.nom as client_nom, c.prenom as client_prenom, c.email as client_email, c.telephone as client_telephone
                    FROM participations p
                    LEFT JOIN evenements e ON p.evenement_id = e.id
                    LEFT JOIN clients c ON p.client_id = c.id
                    WHERE p.statut = 'confirmée'
                    ORDER BY e.date_debut DESC
                    LIMIT :offset, :limit";

            return $this->db->query($sql, ['offset' => $offset, 'limit' => $limit]);
        } catch (Exception $e) {
            error_log('Erreur Participation::getConfirmed - ' . $e->getMessage());
            return [];
        }
    }

    public function getPending(int $offset = 0, int $limit = 20): array {
        try {
            $sql = "SELECT p.*, 
                           e.titre as evenement_titre, e.date_debut as evenement_date,
                           c.nom as client_nom, c.prenom as client_prenom, c.email as client_email
                    FROM participations p
                    LEFT JOIN evenements e ON p.evenement_id = e.id
                    LEFT JOIN clients c ON p.client_id = c.id
                    WHERE p.statut = 'en attente'
                    ORDER BY p.created_at ASC
                    LIMIT :offset, :limit";

            return $this->db->query($sql, ['offset' => $offset, 'limit' => $limit]);
        } catch (Exception $e) {
            error_log('Erreur Participation::getPending - ' . $e->getMessage());
            return [];
        }
    }

    public function getCancelled(int $offset = 0, int $limit = 20): array {
        try {
            $sql = "SELECT p.*, 
                           e.titre as evenement_titre, e.date_debut as evenement_date,
                           c.nom as client_nom, c.prenom as client_prenom
                    FROM participations p
                    LEFT JOIN evenements e ON p.evenement_id = e.id
                    LEFT JOIN clients c ON p.client_id = c.id
                    WHERE p.statut = 'annulée'
                    ORDER BY p.updated_at DESC
                    LIMIT :offset, :limit";

            return $this->db->query($sql, ['offset' => $offset, 'limit' => $limit]);
        } catch (Exception $e) {
            error_log('Erreur Participation::getCancelled - ' . $e->getMessage());
            return [];
        }
    }

    // ─────────────────────────────────────────
    //  Vérification et conditions
    // ─────────────────────────────────────────
    public function isClientAlreadyParticipating(int $evenementId, int $clientId): bool {
        try {
            $sql = "SELECT COUNT(*) as count FROM participations 
                    WHERE evenement_id = :evenement_id AND client_id = :client_id AND statut != 'annulée'";

            $result = $this->db->query($sql, ['evenement_id' => $evenementId, 'client_id' => $clientId]);
            return ($result[0]['count'] ?? 0) > 0;
        } catch (Exception $e) {
            error_log('Erreur Participation::isClientAlreadyParticipating - ' . $e->getMessage());
            return false;
        }
    }

    public function hasPlacesAvailable(int $evenementId, int $nombrePlacesRequises = 1): bool {
        try {
            $sql = "SELECT e.nombre_places_max, COUNT(p.id) as total_participants, SUM(p.nombre_places) as places_reserver
                    FROM evenements e
                    LEFT JOIN participations p ON e.id = p.evenement_id AND p.statut != 'annulée'
                    WHERE e.id = :evenement_id
                    GROUP BY e.id";

            $result = $this->db->query($sql, ['evenement_id' => $evenementId]);
            if (!$result) {
                return false;
            }

            $data = $result[0];
            $placesReservees = (int)($data['places_reserver'] ?? 0);
            $maxPlaces = (int)($data['nombre_places_max'] ?? 0);

            return ($placesReservees + $nombrePlacesRequises) <= $maxPlaces;
        } catch (Exception $e) {
            error_log('Erreur Participation::hasPlacesAvailable - ' . $e->getMessage());
            return false;
        }
    }

    public function getAvailablePlaces(int $evenementId): int {
        try {
            $sql = "SELECT e.nombre_places_max, COALESCE(SUM(p.nombre_places), 0) as places_reserver
                    FROM evenements e
                    LEFT JOIN participations p ON e.id = p.evenement_id AND p.statut != 'annulée'
                    WHERE e.id = :evenement_id
                    GROUP BY e.id";

            $result = $this->db->query($sql, ['evenement_id' => $evenementId]);
            if (!$result) {
                return 0;
            }

            $max = (int)($result[0]['nombre_places_max'] ?? 0);
            $reserved = (int)($result[0]['places_reserver'] ?? 0);

            return max(0, $max - $reserved);
        } catch (Exception $e) {
            error_log('Erreur Participation::getAvailablePlaces - ' . $e->getMessage());
            return 0;
        }
    }

    public function getOccupancyRate(int $evenementId): float {
        try {
            $sql = "SELECT e.nombre_places_max, COALESCE(SUM(p.nombre_places), 0) as places_reserver
                    FROM evenements e
                    LEFT JOIN participations p ON e.id = p.evenement_id AND p.statut != 'annulée'
                    WHERE e.id = :evenement_id
                    GROUP BY e.id";

            $result = $this->db->query($sql, ['evenement_id' => $evenementId]);
            if (!$result || (int)($result[0]['nombre_places_max'] ?? 0) === 0) {
                return 0.0;
            }

            $max = (int)($result[0]['nombre_places_max'] ?? 0);
            $reserved = (int)($result[0]['places_reserver'] ?? 0);

            return ($reserved / $max) * 100;
        } catch (Exception $e) {
            error_log('Erreur Participation::getOccupancyRate - ' . $e->getMessage());
            return 0.0;
        }
    }

    // ─────────────────────────────────────────
    //  Compteurs
    // ─────────────────────────────────────────
    public function countByEvenement(int $evenementId, string $filter = 'tous'): int {
        try {
            $where = "WHERE p.evenement_id = :evenement_id";

            if ($filter !== 'tous') {
                $where .= " AND p.statut = :statut";
            }

            $sql = "SELECT COUNT(*) as count FROM participations p $where";

            $params = ['evenement_id' => $evenementId];
            if ($filter !== 'tous') {
                $params['statut'] = $filter;
            }

            $result = $this->db->query($sql, $params);
            return $result[0]['count'] ?? 0;
        } catch (Exception $e) {
            error_log('Erreur Participation::countByEvenement - ' . $e->getMessage());
            return 0;
        }
    }

    public function countByClient(int $clientId, string $filter = 'tous'): int {
        try {
            $where = "WHERE p.client_id = :client_id";

            if ($filter !== 'tous') {
                $where .= " AND p.statut = :statut";
            }

            $sql = "SELECT COUNT(*) as count FROM participations p $where";

            $params = ['client_id' => $clientId];
            if ($filter !== 'tous') {
                $params['statut'] = $filter;
            }

            $result = $this->db->query($sql, $params);
            return $result[0]['count'] ?? 0;
        } catch (Exception $e) {
            error_log('Erreur Participation::countByClient - ' . $e->getMessage());
            return 0;
        }
    }

    public function countByUser(int $userId, string $filter = 'tous'): int {
        try {
            $where = "WHERE p.user_id = :user_id";

            if ($filter !== 'tous') {
                $where .= " AND p.statut = :statut";
            }

            $sql = "SELECT COUNT(*) as count FROM participations p $where";

            $params = ['user_id' => $userId];
            if ($filter !== 'tous') {
                $params['statut'] = $filter;
            }

            $result = $this->db->query($sql, $params);
            return $result[0]['count'] ?? 0;
        } catch (Exception $e) {
            error_log('Erreur Participation::countByUser - ' . $e->getMessage());
            return 0;
        }
    }

    public function countByStatut(string $statut): int {
        try {
            $sql = "SELECT COUNT(*) as count FROM participations WHERE statut = :statut";
            $result = $this->db->query($sql, ['statut' => $statut]);
            return $result[0]['count'] ?? 0;
        } catch (Exception $e) {
            error_log('Erreur Participation::countByStatut - ' . $e->getMessage());
            return 0;
        }
    }

    public function getTotalParticipants(int $evenementId): int {
        try {
            $sql = "SELECT COALESCE(SUM(nombre_places), 0) as total FROM participations 
                    WHERE evenement_id = :evenement_id AND statut != 'annulée'";

            $result = $this->db->query($sql, ['evenement_id' => $evenementId]);
            return (int)($result[0]['total'] ?? 0);
        } catch (Exception $e) {
            error_log('Erreur Participation::getTotalParticipants - ' . $e->getMessage());
            return 0;
        }
    }

    public function getTotalParticipantsConfirmed(int $evenementId): int {
        try {
            $sql = "SELECT COALESCE(SUM(nombre_places), 0) as total FROM participations 
                    WHERE evenement_id = :evenement_id AND statut = 'confirmée'";

            $result = $this->db->query($sql, ['evenement_id' => $evenementId]);
            return (int)($result[0]['total'] ?? 0);
        } catch (Exception $e) {
            error_log('Erreur Participation::getTotalParticipantsConfirmed - ' . $e->getMessage());
            return 0;
        }
    }

    // ─────────────────────────────────────────
    //  Paiement et facturation
    // ─────────────────────────────────────────
    public function addPayment(int $id, array $data): ?int {
        try {
            $sql = "INSERT INTO participation_paiements (participation_id, montant, date_paiement, type_paiement, reference, statut, created_at)
                    VALUES (:participation_id, :montant, :date_paiement, :type_paiement, :reference, :statut, NOW())";

            $data['participation_id'] = $id;
            $result = $this->db->execute($sql, $data);
            return $result ? $this->db->lastInsertId() : null;
        } catch (Exception $e) {
            error_log('Erreur Participation::addPayment - ' . $e->getMessage());
            return null;
        }
    }

    public function getPayments(int $id): array {
        try {
            $sql = "SELECT * FROM participation_paiements WHERE participation_id = :participation_id ORDER BY date_paiement DESC";
            return $this->db->query($sql, ['participation_id' => $id]);
        } catch (Exception $e) {
            error_log('Erreur Participation::getPayments - ' . $e->getMessage());
            return [];
        }
    }

    public function getTotalPaid(int $id): float {
        try {
            $sql = "SELECT COALESCE(SUM(montant), 0) as total FROM participation_paiements 
                    WHERE participation_id = :participation_id AND statut = 'complété'";

            $result = $this->db->query($sql, ['participation_id' => $id]);
            return (float)($result[0]['total'] ?? 0);
        } catch (Exception $e) {
            error_log('Erreur Participation::getTotalPaid - ' . $e->getMessage());
            return 0.0;
        }
    }

    public function getRemainingBalance(int $id): float {
        try {
            $participation = $this->getById($id);
            if (!$participation) {
                return 0.0;
            }

            $totalPaid = $this->getTotalPaid($id);
            $totalDue = (float)($participation['prix_total'] ?? 0);

            return max(0, $totalDue - $totalPaid);
        } catch (Exception $e) {
            error_log('Erreur Participation::getRemainingBalance - ' . $e->getMessage());
            return 0.0;
        }
    }

    public function getPaymentStatus(int $id): string {
        try {
            $balance = $this->getRemainingBalance($id);

            if ($balance === 0.0) {
                return 'payée';
            } elseif ($balance > 0) {
                return 'partielle';
            }

            return 'impayée';
        } catch (Exception $e) {
            error_log('Erreur Participation::getPaymentStatus - ' . $e->getMessage());
            return 'impayée';
        }
    }

    public function getTotalEarnings(int $evenementId): float {
        try {
            $sql = "SELECT COALESCE(SUM(pp.montant), 0) as total FROM participation_paiements pp
                    LEFT JOIN participations p ON pp.participation_id = p.id
                    WHERE p.evenement_id = :evenement_id AND pp.statut = 'complété'";

            $result = $this->db->query($sql, ['evenement_id' => $evenementId]);
            return (float)($result[0]['total'] ?? 0);
        } catch (Exception $e) {
            error_log('Erreur Participation::getTotalEarnings - ' . $e->getMessage());
            return 0.0;
        }
    }

    public function getUnpaidTotal(int $evenementId): float {
        try {
            $sql = "SELECT COALESCE(SUM(p.prix_total), 0) as total FROM participations p
                    WHERE p.evenement_id = :evenement_id AND p.statut = 'confirmée'";

            $result = $this->db->query($sql, ['evenement_id' => $evenementId]);
            $total = (float)($result[0]['total'] ?? 0);

            $earnings = $this->getTotalEarnings($evenementId);
            return max(0, $total - $earnings);
        } catch (Exception $e) {
            error_log('Erreur Participation::getUnpaidTotal - ' . $e->getMessage());
            return 0.0;
        }
    }

    // ─────────────────────────────────────────
    //  Documents et certificats
    // ─────────────────────────────────────────
    public function addDocument(int $id, array $data): ?int {
        try {
            $sql = "INSERT INTO participation_documents (participation_id, nom, fichier, type, created_at)
                    VALUES (:participation_id, :nom, :fichier, :type, NOW())";

            $data['participation_id'] = $id;
            $result = $this->db->execute($sql, $data);
            return $result ? $this->db->lastInsertId() : null;
        } catch (Exception $e) {
            error_log('Erreur Participation::addDocument - ' . $e->getMessage());
            return null;
        }
    }

    public function removeDocument(int $documentId): bool {
        try {
            $sql = "DELETE FROM participation_documents WHERE id = :id";
            return $this->db->execute($sql, ['id' => $documentId]);
        } catch (Exception $e) {
            error_log('Erreur Participation::removeDocument - ' . $e->getMessage());
            return false;
        }
    }

    public function getDocuments(int $id): array {
        try {
            $sql = "SELECT * FROM participation_documents WHERE participation_id = :participation_id ORDER BY created_at DESC";
            return $this->db->query($sql, ['participation_id' => $id]);
        } catch (Exception $e) {
            error_log('Erreur Participation::getDocuments - ' . $e->getMessage());
            return [];
        }
    }

    public function addCertificate(int $id, array $data): ?int {
        try {
            $sql = "INSERT INTO participation_certificats (participation_id, numero_certificat, date_emission, fichier, statut, created_at)
                    VALUES (:participation_id, :numero_certificat, :date_emission, :fichier, :statut, NOW())";

            $data['participation_id'] = $id;
            $result = $this->db->execute($sql, $data);
            return $result ? $this->db->lastInsertId() : null;
        } catch (Exception $e) {
            error_log('Erreur Participation::addCertificate - ' . $e->getMessage());
            return null;
        }
    }

    public function getCertificate(int $id): ?array {
        try {
            $sql = "SELECT * FROM participation_certificats WHERE participation_id = :participation_id";
            $result = $this->db->query($sql, ['participation_id' => $id]);
            return $result ? $result[0] : null;
        } catch (Exception $e) {
            error_log('Erreur Participation::getCertificate - ' . $e->getMessage());
            return null;
        }
    }

    public function hasCertificate(int $id): bool {
        try {
            $certificate = $this->getCertificate($id);
            return $certificate !== null && $certificate['statut'] === 'émis';
        } catch (Exception $e) {
            error_log('Erreur Participation::hasCertificate - ' . $e->getMessage());
            return false;
        }
    }

    // ─────────────────────────────────────────
    //  Historique et communication
    // ─────────────────────────────────────────
    public function addHistorique(int $id, array $data): bool {
        try {
            $sql = "INSERT INTO participation_historiques (participation_id, action, description, user_id, created_at)
                    VALUES (:participation_id, :action, :description, :user_id, NOW())";

            $data['participation_id'] = $id;
            return $this->db->execute($sql, $data);
        } catch (Exception $e) {
            error_log('Erreur Participation::addHistorique - ' . $e->getMessage());
            return false;
        }
    }

    public function getHistorique(int $id): array {
        try {
            $sql = "SELECT h.*, u.nom, u.prenom FROM participation_historiques h
                    LEFT JOIN users u ON h.user_id = u.id
                    WHERE h.participation_id = :participation_id
                    ORDER BY h.created_at DESC";

            return $this->db->query($sql, ['participation_id' => $id]);
        } catch (Exception $e) {
            error_log('Erreur Participation::getHistorique - ' . $e->getMessage());
            return [];
        }
    }

    public function addCommunication(int $id, array $data): ?int {
        try {
            $sql = "INSERT INTO participation_communications (participation_id, type, sujet, contenu, date_envoi, created_at)
                    VALUES (:participation_id, :type, :sujet, :contenu, :date_envoi, NOW())";

            $data['participation_id'] = $id;
            $result = $this->db->execute($sql, $data);
            return $result ? $this->db->lastInsertId() : null;
        } catch (Exception $e) {
            error_log('Erreur Participation::addCommunication - ' . $e->getMessage());
            return null;
        }
    }

    public function getCommunications(int $id): array {
        try {
            $sql = "SELECT * FROM participation_communications WHERE participation_id = :participation_id ORDER BY date_envoi DESC";
            return $this->db->query($sql, ['participation_id' => $id]);
        } catch (Exception $e) {
            error_log('Erreur Participation::getCommunications - ' . $e->getMessage());
            return [];
        }
    }

    // ─────────────────────────────────────────
    //  Statistiques et rapports
    // ─────────────────────────────────────────
    public function getStats(int $evenementId): array {
        try {
            $sql = "SELECT 
                           COUNT(*) as total_participations,
                           SUM(CASE WHEN p.statut = 'confirmée' THEN 1 ELSE 0 END) as confirmees,
                           SUM(CASE WHEN p.statut = 'en attente' THEN 1 ELSE 0 END) as en_attente,
                           SUM(CASE WHEN p.statut = 'annulée' THEN 1 ELSE 0 END) as annulees,
                           COALESCE(SUM(p.nombre_places), 0) as total_places,
                           COALESCE(SUM(p.prix_total), 0) as revenue_attendue,
                           COALESCE((SELECT SUM(montant) FROM participation_paiements WHERE statut = 'complété'), 0) as revenue_realisee
                    FROM participations p
                    WHERE p.evenement_id = :evenement_id";

            $result = $this->db->query($sql, ['evenement_id' => $evenementId]);
            return $result ? $result[0] : [];
        } catch (Exception $e) {
            error_log('Erreur Participation::getStats - ' . $e->getMessage());
            return [];
        }
    }

    public function getStatsByPeriod(string $dateDebut, string $dateFin): array {
        try {
            $sql = "SELECT 
                           e.titre as evenement_titre,
                           COUNT(p.id) as nb_participations,
                           COALESCE(SUM(p.nombre_places), 0) as total_places,
                           COALESCE(SUM(p.prix_total), 0) as revenue_attendue,
                           COALESCE((SELECT SUM(montant) FROM participation_paiements WHERE statut = 'complété'), 0) as revenue_realisee
                    FROM participations p
                    LEFT JOIN evenements e ON p.evenement_id = e.id
                    WHERE e.date_debut BETWEEN :date_debut AND :date_fin
                    GROUP BY e.id, e.titre
                    ORDER BY e.date_debut DESC";

            return $this->db->query($sql, [
                'date_debut' => $dateDebut . ' 00:00:00',
                'date_fin' => $dateFin . ' 23:59:59',
            ]);
        } catch (Exception $e) {
            error_log('Erreur Participation::getStatsByPeriod - ' . $e->getMessage());
            return [];
        }
    }

    public function getParticipantsAgeDistribution(int $evenementId): array {
        try {
            $sql = "SELECT 
                           CASE 
                               WHEN YEAR(NOW()) - YEAR(c.date_naissance) < 25 THEN 'Moins de 25'
                               WHEN YEAR(NOW()) - YEAR(c.date_naissance) BETWEEN 25 AND 35 THEN '25-35'
                               WHEN YEAR(NOW()) - YEAR(c.date_naissance) BETWEEN 36 AND 50 THEN '36-50'
                               ELSE 'Plus de 50'
                           END as tranche_age,
                           COUNT(*) as nb_participants
                    FROM participations p
                    LEFT JOIN clients c ON p.client_id = c.id
                    WHERE p.evenement_id = :evenement_id AND p.statut = 'confirmée'
                    GROUP BY tranche_age
                    ORDER BY tranche_age ASC";

            return $this->db->query($sql, ['evenement_id' => $evenementId]);
        } catch (Exception $e) {
            error_log('Erreur Participation::getParticipantsAgeDistribution - ' . $e->getMessage());
            return [];
        }
    }

    public function getTopClients(int $limit = 10): array {
        try {
            $sql = "SELECT c.id, c.nom, c.prenom, c.email, COUNT(p.id) as nb_participations, SUM(p.prix_total) as total_depense
                    FROM participations p
                    LEFT JOIN clients c ON p.client_id = c.id
                    WHERE p.statut = 'confirmée'
                    GROUP BY c.id, c.nom, c.prenom, c.email
                    ORDER BY nb_participations DESC
                    LIMIT :limit";

            return $this->db->query($sql, ['limit' => $limit]);
        } catch (Exception $e) {
            error_log('Erreur Participation::getTopClients - ' . $e->getMessage());
            return [];
        }
    }

    public function getTopEvents(int $limit = 10): array {
        try {
            $sql = "SELECT e.id, e.titre, e.date_debut, COUNT(p.id) as nb_participations, SUM(p.nombre_places) as total_places
                    FROM participations p
                    LEFT JOIN evenements e ON p.evenement_id = e.id
                    WHERE p.statut = 'confirmée'
                    GROUP BY e.id, e.titre, e.date_debut
                    ORDER BY nb_participations DESC
                    LIMIT :limit";

            return $this->db->query($sql, ['limit' => $limit]);
        } catch (Exception $e) {
            error_log('Erreur Participation::getTopEvents - ' . $e->getMessage());
            return [];
        }
    }

    public function getConversionRate(int $evenementId): float {
        try {
            $sql = "SELECT 
                           SUM(CASE WHEN p.statut = 'confirmée' THEN 1 ELSE 0 END) as confirmees,
                           COUNT(*) as total
                    FROM participations p
                    WHERE p.evenement_id = :evenement_id";

            $result = $this->db->query($sql, ['evenement_id' => $evenementId]);
            if (!$result || (int)($result[0]['total'] ?? 0) === 0) {
                return 0.0;
            }

            $confirmed = (int)($result[0]['confirmees'] ?? 0);
            $total = (int)($result[0]['total'] ?? 0);

            return ($confirmed / $total) * 100;
        } catch (Exception $e) {
            error_log('Erreur Participation::getConversionRate - ' . $e->getMessage());
            return 0.0;
        }
    }

    // ─────────────────────────────────────────
    //  Export et API
    // ─────────────────────────────────────────
    public function exportList(int $evenementId): array {
        try {
            $sql = "SELECT p.*, 
                           c.nom as client_nom, c.prenom as client_prenom, c.email as client_email, c.telephone as client_telephone,
                           u.nom as user_nom, u.prenom as user_prenom
                    FROM participations p
                    LEFT JOIN clients c ON p.client_id = c.id
                    LEFT JOIN users u ON p.user_id = u.id
                    WHERE p.evenement_id = :evenement_id
                    ORDER BY c.nom ASC";

            return $this->db->query($sql, ['evenement_id' => $evenementId]);
        } catch (Exception $e) {
            error_log('Erreur Participation::exportList - ' . $e->getMessage());
            return [];
        }
    }

    public function getApiListing(int $evenementId, int $offset, int $limit): array {
        try {
            $sql = "SELECT p.id, p.nombre_places, p.prix_total, p.statut, p.date_inscription,
                           c.nom as client_nom, c.prenom as client_prenom, c.email as client_email
                    FROM participations p
                    LEFT JOIN clients c ON p.client_id = c.id
                    WHERE p.evenement_id = :evenement_id AND p.statut = 'confirmée'
                    ORDER BY p.date_inscription DESC
                    LIMIT :offset, :limit";

            return $this->db->query($sql, ['evenement_id' => $evenementId, 'offset' => $offset, 'limit' => $limit]);
        } catch (Exception $e) {
            error_log('Erreur Participation::getApiListing - ' . $e->getMessage());
            return [];
        }
    }

    public function getClientHistory(int $clientId): array {
        try {
            $sql = "SELECT p.*, e.titre as evenement_titre, e.date_debut as evenement_date
                    FROM participations p
                    LEFT JOIN evenements e ON p.evenement_id = e.id
                    WHERE p.client_id = :client_id
                    ORDER BY e.date_debut DESC";

            return $this->db->query($sql, ['client_id' => $clientId]);
        } catch (Exception $e) {
            error_log('Erreur Participation::getClientHistory - ' . $e->getMessage());
            return [];
        }
    }

    public function getClientAttendance(int $clientId): array {
        try {
            $sql = "SELECT p.*, e.titre as evenement_titre, e.date_debut, e.date_fin
                    FROM participations p
                    LEFT JOIN evenements e ON p.evenement_id = e.id
                    WHERE p.client_id = :client_id AND p.statut = 'confirmée' AND e.date_debut <= NOW()
                    ORDER BY e.date_debut DESC";

            return $this->db->query($sql, ['client_id' => $clientId]);
        } catch (Exception $e) {
            error_log('Erreur Participation::getClientAttendance - ' . $e->getMessage());
            return [];
        }
    }

    public function getNoShowRate(int $evenementId): float {
        try {
            $sql = "SELECT 
                           SUM(CASE WHEN p.statut = 'no-show' THEN 1 ELSE 0 END) as no_shows,
                           COUNT(*) as total
                    FROM participations p
                    WHERE p.evenement_id = :evenement_id";

            $result = $this->db->query($sql, ['evenement_id' => $evenementId]);
            if (!$result || (int)($result[0]['total'] ?? 0) === 0) {
                return 0.0;
            }

            $noShows = (int)($result[0]['no_shows'] ?? 0);
            $total = (int)($result[0]['total'] ?? 0);

            return ($noShows / $total) * 100;
        } catch (Exception $e) {
            error_log('Erreur Participation::getNoShowRate - ' . $e->getMessage());
            return 0.0;
        }
    }
}
?>
// update
