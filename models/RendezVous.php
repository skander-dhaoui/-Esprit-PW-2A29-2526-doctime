<?php

require_once __DIR__ . '/../config/database.php';
class RendezVous {

    private Database $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    // ─────────────────────────────────────────
    //  CRUD de base
    // ─────────────────────────────────────────
    public function create(array $data): ?int {
        try {
            $sql = "INSERT INTO rendez_vous (client_id, user_id, titre, description, date_debut, date_fin, lieu, type, statut, notes, rappel, created_at, updated_at)
                    VALUES (:client_id, :user_id, :titre, :description, :date_debut, :date_fin, :lieu, :type, :statut, :notes, :rappel, NOW(), NOW())";

            $result = $this->db->execute($sql, $data);
            return $result ? $this->db->lastInsertId() : null;
        } catch (Exception $e) {
            error_log('Erreur RendezVous::create - ' . $e->getMessage());
            return null;
        }
    }

    public function getById(int $id): ?array {
        try {
            $sql = "SELECT rv.*, 
                           c.nom as client_nom, c.prenom as client_prenom, c.email as client_email, c.telephone as client_telephone,
                           u.nom as user_nom, u.prenom as user_prenom, u.email as user_email, u.avatar
                    FROM rendez_vous rv
                    LEFT JOIN clients c ON rv.client_id = c.id
                    LEFT JOIN users u ON rv.user_id = u.id
                    WHERE rv.id = :id";

            $result = $this->db->query($sql, ['id' => $id]);
            return $result ? $result[0] : null;
        } catch (Exception $e) {
            error_log('Erreur RendezVous::getById - ' . $e->getMessage());
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

            $sql = "UPDATE rendez_vous SET " . implode(', ', $fields) . " WHERE id = :id";
            return $this->db->execute($sql, $values);
        } catch (Exception $e) {
            error_log('Erreur RendezVous::update - ' . $e->getMessage());
            return false;
        }
    }

    public function delete(int $id): bool {
        try {
            $sql = "DELETE FROM rendez_vous WHERE id = :id";
            return $this->db->execute($sql, ['id' => $id]);
        } catch (Exception $e) {
            error_log('Erreur RendezVous::delete - ' . $e->getMessage());
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
                $where .= " AND rv.statut = :statut";
            }

            if (!empty($search)) {
                $where .= " AND (c.nom LIKE :search OR c.prenom LIKE :search OR rv.titre LIKE :search)";
            }

            if ($userId > 0) {
                $where .= " AND rv.user_id = :user_id";
            }

            $sql = "SELECT rv.*, 
                           c.nom as client_nom, c.prenom as client_prenom, c.email as client_email,
                           u.nom as user_nom, u.prenom as user_prenom
                    FROM rendez_vous rv
                    LEFT JOIN clients c ON rv.client_id = c.id
                    LEFT JOIN users u ON rv.user_id = u.id
                    $where
                    ORDER BY rv.date_debut DESC
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
            error_log('Erreur RendezVous::getAll - ' . $e->getMessage());
            return [];
        }
    }

    public function countAll(string $filter = 'tous', string $search = '', int $userId = 0): int {
        try {
            $where = "WHERE 1=1";

            if ($filter !== 'tous') {
                $where .= " AND rv.statut = :statut";
            }

            if (!empty($search)) {
                $where .= " AND (c.nom LIKE :search OR c.prenom LIKE :search OR rv.titre LIKE :search)";
            }

            if ($userId > 0) {
                $where .= " AND rv.user_id = :user_id";
            }

            $sql = "SELECT COUNT(*) as count FROM rendez_vous rv
                    LEFT JOIN clients c ON rv.client_id = c.id
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
            error_log('Erreur RendezVous::countAll - ' . $e->getMessage());
            return 0;
        }
    }

    public function getRecent(int $limit = 10, int $userId = 0): array {
        try {
            $where = "WHERE rv.statut IN ('prévu', 'confirmé')";

            if ($userId > 0) {
                $where .= " AND rv.user_id = :user_id";
            }

            $sql = "SELECT rv.*, 
                           c.nom as client_nom, c.prenom as client_prenom, c.email as client_email,
                           u.nom as user_nom, u.prenom as user_prenom
                    FROM rendez_vous rv
                    LEFT JOIN clients c ON rv.client_id = c.id
                    LEFT JOIN users u ON rv.user_id = u.id
                    $where
                    ORDER BY rv.date_debut ASC
                    LIMIT :limit";

            $params = ['limit' => $limit];
            if ($userId > 0) {
                $params['user_id'] = $userId;
            }

            return $this->db->query($sql, $params);
        } catch (Exception $e) {
            error_log('Erreur RendezVous::getRecent - ' . $e->getMessage());
            return [];
        }
    }

    public function getByUser(int $userId, int $offset = 0, int $limit = 20, string $filter = 'tous'): array {
        try {
            $where = "WHERE rv.user_id = :user_id";

            if ($filter !== 'tous') {
                $where .= " AND rv.statut = :statut";
            }

            $sql = "SELECT rv.*, 
                           c.nom as client_nom, c.prenom as client_prenom, c.email as client_email
                    FROM rendez_vous rv
                    LEFT JOIN clients c ON rv.client_id = c.id
                    $where
                    ORDER BY rv.date_debut DESC
                    LIMIT :offset, :limit";

            $params = ['user_id' => $userId, 'offset' => $offset, 'limit' => $limit];
            if ($filter !== 'tous') {
                $params['statut'] = $filter;
            }

            return $this->db->query($sql, $params);
        } catch (Exception $e) {
            error_log('Erreur RendezVous::getByUser - ' . $e->getMessage());
            return [];
        }
    }

    public function getByClient(int $clientId, int $offset = 0, int $limit = 20, string $filter = 'tous'): array {
        try {
            $where = "WHERE rv.client_id = :client_id";

            if ($filter !== 'tous') {
                $where .= " AND rv.statut = :statut";
            }

            $sql = "SELECT rv.*, 
                           u.nom as user_nom, u.prenom as user_prenom, u.email as user_email, u.avatar
                    FROM rendez_vous rv
                    LEFT JOIN users u ON rv.user_id = u.id
                    $where
                    ORDER BY rv.date_debut DESC
                    LIMIT :offset, :limit";

            $params = ['client_id' => $clientId, 'offset' => $offset, 'limit' => $limit];
            if ($filter !== 'tous') {
                $params['statut'] = $filter;
            }

            return $this->db->query($sql, $params);
        } catch (Exception $e) {
            error_log('Erreur RendezVous::getByClient - ' . $e->getMessage());
            return [];
        }
    }

    public function getByDate(string $date, int $userId = 0, string $filter = 'tous'): array {
        try {
            $where = "WHERE DATE(rv.date_debut) = :date";

            if ($userId > 0) {
                $where .= " AND rv.user_id = :user_id";
            }

            if ($filter !== 'tous') {
                $where .= " AND rv.statut = :statut";
            }

            $sql = "SELECT rv.*, 
                           c.nom as client_nom, c.prenom as client_prenom,
                           u.nom as user_nom, u.prenom as user_prenom
                    FROM rendez_vous rv
                    LEFT JOIN clients c ON rv.client_id = c.id
                    LEFT JOIN users u ON rv.user_id = u.id
                    $where
                    ORDER BY rv.date_debut ASC";

            $params = ['date' => $date];
            if ($userId > 0) {
                $params['user_id'] = $userId;
            }
            if ($filter !== 'tous') {
                $params['statut'] = $filter;
            }

            return $this->db->query($sql, $params);
        } catch (Exception $e) {
            error_log('Erreur RendezVous::getByDate - ' . $e->getMessage());
            return [];
        }
    }

    public function getByDateRange(string $dateDebut, string $dateFin, int $userId = 0, string $filter = 'tous'): array {
        try {
            $where = "WHERE rv.date_debut BETWEEN :date_debut AND :date_fin";

            if ($userId > 0) {
                $where .= " AND rv.user_id = :user_id";
            }

            if ($filter !== 'tous') {
                $where .= " AND rv.statut = :statut";
            }

            $sql = "SELECT rv.*, 
                           c.nom as client_nom, c.prenom as client_prenom,
                           u.nom as user_nom, u.prenom as user_prenom
                    FROM rendez_vous rv
                    LEFT JOIN clients c ON rv.client_id = c.id
                    LEFT JOIN users u ON rv.user_id = u.id
                    $where
                    ORDER BY rv.date_debut ASC";

            $params = [
                'date_debut' => $dateDebut . ' 00:00:00',
                'date_fin' => $dateFin . ' 23:59:59',
            ];
            if ($userId > 0) {
                $params['user_id'] = $userId;
            }
            if ($filter !== 'tous') {
                $params['statut'] = $filter;
            }

            return $this->db->query($sql, $params);
        } catch (Exception $e) {
            error_log('Erreur RendezVous::getByDateRange - ' . $e->getMessage());
            return [];
        }
    }

    public function getByType(string $type, int $offset = 0, int $limit = 20): array {
        try {
            $sql = "SELECT rv.*, 
                           c.nom as client_nom, c.prenom as client_prenom,
                           u.nom as user_nom, u.prenom as user_prenom
                    FROM rendez_vous rv
                    LEFT JOIN clients c ON rv.client_id = c.id
                    LEFT JOIN users u ON rv.user_id = u.id
                    WHERE rv.type = :type AND rv.statut != 'annulé'
                    ORDER BY rv.date_debut DESC
                    LIMIT :offset, :limit";

            return $this->db->query($sql, ['type' => $type, 'offset' => $offset, 'limit' => $limit]);
        } catch (Exception $e) {
            error_log('Erreur RendezVous::getByType - ' . $e->getMessage());
            return [];
        }
    }

    public function getByStatut(string $statut, int $offset = 0, int $limit = 20): array {
        try {
            $sql = "SELECT rv.*, 
                           c.nom as client_nom, c.prenom as client_prenom,
                           u.nom as user_nom, u.prenom as user_prenom
                    FROM rendez_vous rv
                    LEFT JOIN clients c ON rv.client_id = c.id
                    LEFT JOIN users u ON rv.user_id = u.id
                    WHERE rv.statut = :statut
                    ORDER BY rv.date_debut DESC
                    LIMIT :offset, :limit";

            return $this->db->query($sql, ['statut' => $statut, 'offset' => $offset, 'limit' => $limit]);
        } catch (Exception $e) {
            error_log('Erreur RendezVous::getByStatut - ' . $e->getMessage());
            return [];
        }
    }

    public function getUpcoming(int $limit = 10, int $userId = 0): array {
        try {
            $where = "WHERE rv.date_debut > NOW() AND rv.statut IN ('prévu', 'confirmé')";

            if ($userId > 0) {
                $where .= " AND rv.user_id = :user_id";
            }

            $sql = "SELECT rv.*, 
                           c.nom as client_nom, c.prenom as client_prenom, c.email as client_email,
                           u.nom as user_nom, u.prenom as user_prenom
                    FROM rendez_vous rv
                    LEFT JOIN clients c ON rv.client_id = c.id
                    LEFT JOIN users u ON rv.user_id = u.id
                    $where
                    ORDER BY rv.date_debut ASC
                    LIMIT :limit";

            $params = ['limit' => $limit];
            if ($userId > 0) {
                $params['user_id'] = $userId;
            }

            return $this->db->query($sql, $params);
        } catch (Exception $e) {
            error_log('Erreur RendezVous::getUpcoming - ' . $e->getMessage());
            return [];
        }
    }

    public function getPast(int $limit = 10, int $userId = 0): array {
        try {
            $where = "WHERE rv.date_debut <= NOW()";

            if ($userId > 0) {
                $where .= " AND rv.user_id = :user_id";
            }

            $sql = "SELECT rv.*, 
                           c.nom as client_nom, c.prenom as client_prenom,
                           u.nom as user_nom, u.prenom as user_prenom
                    FROM rendez_vous rv
                    LEFT JOIN clients c ON rv.client_id = c.id
                    LEFT JOIN users u ON rv.user_id = u.id
                    $where
                    ORDER BY rv.date_debut DESC
                    LIMIT :limit";

            $params = ['limit' => $limit];
            if ($userId > 0) {
                $params['user_id'] = $userId;
            }

            return $this->db->query($sql, $params);
        } catch (Exception $e) {
            error_log('Erreur RendezVous::getPast - ' . $e->getMessage());
            return [];
        }
    }

    public function getTodaysRendezVous(int $userId = 0): array {
        try {
            $where = "WHERE DATE(rv.date_debut) = DATE(NOW()) AND rv.statut IN ('prévu', 'confirmé')";

            if ($userId > 0) {
                $where .= " AND rv.user_id = :user_id";
            }

            $sql = "SELECT rv.*, 
                           c.nom as client_nom, c.prenom as client_prenom, c.email as client_email,
                           u.nom as user_nom, u.prenom as user_prenom
                    FROM rendez_vous rv
                    LEFT JOIN clients c ON rv.client_id = c.id
                    LEFT JOIN users u ON rv.user_id = u.id
                    $where
                    ORDER BY rv.date_debut ASC";

            $params = [];
            if ($userId > 0) {
                $params['user_id'] = $userId;
            }

            return $this->db->query($sql, $params);
        } catch (Exception $e) {
            error_log('Erreur RendezVous::getTodaysRendezVous - ' . $e->getMessage());
            return [];
        }
    }

    public function getReminderNeeded(int $minutesBefore = 15, int $userId = 0): array {
        try {
            $where = "WHERE rv.rappel = 1 
                     AND rv.statut IN ('prévu', 'confirmé')
                     AND rv.date_debut > NOW() 
                     AND rv.date_debut <= DATE_ADD(NOW(), INTERVAL :minutes MINUTE)
                     AND (rv.rappel_envoye IS NULL OR rv.rappel_envoye = 0)";

            if ($userId > 0) {
                $where .= " AND rv.user_id = :user_id";
            }

            $sql = "SELECT rv.*, 
                           c.nom as client_nom, c.prenom as client_prenom, c.email as client_email,
                           u.nom as user_nom, u.prenom as user_prenom, u.email as user_email
                    FROM rendez_vous rv
                    LEFT JOIN clients c ON rv.client_id = c.id
                    LEFT JOIN users u ON rv.user_id = u.id
                    $where
                    ORDER BY rv.date_debut ASC";

            $params = ['minutes' => $minutesBefore];
            if ($userId > 0) {
                $params['user_id'] = $userId;
            }

            return $this->db->query($sql, $params);
        } catch (Exception $e) {
            error_log('Erreur RendezVous::getReminderNeeded - ' . $e->getMessage());
            return [];
        }
    }

    // ─────────────────────────────────────────
    //  Vérification de disponibilité
    // ─────────────────────────────────────────
    public function isSlotAvailable(int $userId, string $dateDebut, string $dateFin, int $excludeId = 0): bool {
        try {
            $where = "WHERE rv.user_id = :user_id 
                     AND rv.statut IN ('prévu', 'confirmé')
                     AND (
                        (rv.date_debut < :date_fin AND rv.date_fin > :date_debut)
                     )";

            if ($excludeId > 0) {
                $where .= " AND rv.id != :exclude_id";
            }

            $sql = "SELECT COUNT(*) as count FROM rendez_vous rv $where";

            $params = [
                'user_id' => $userId,
                'date_debut' => $dateDebut,
                'date_fin' => $dateFin,
            ];
            if ($excludeId > 0) {
                $params['exclude_id'] = $excludeId;
            }

            $result = $this->db->query($sql, $params);
            return ($result[0]['count'] ?? 0) === 0;
        } catch (Exception $e) {
            error_log('Erreur RendezVous::isSlotAvailable - ' . $e->getMessage());
            return false;
        }
    }

    public function getConflicts(int $userId, string $dateDebut, string $dateFin, int $excludeId = 0): array {
        try {
            $where = "WHERE rv.user_id = :user_id 
                     AND rv.statut IN ('prévu', 'confirmé')
                     AND (
                        (rv.date_debut < :date_fin AND rv.date_fin > :date_debut)
                     )";

            if ($excludeId > 0) {
                $where .= " AND rv.id != :exclude_id";
            }

            $sql = "SELECT rv.* FROM rendez_vous rv $where ORDER BY rv.date_debut ASC";

            $params = [
                'user_id' => $userId,
                'date_debut' => $dateDebut,
                'date_fin' => $dateFin,
            ];
            if ($excludeId > 0) {
                $params['exclude_id'] = $excludeId;
            }

            return $this->db->query($sql, $params);
        } catch (Exception $e) {
            error_log('Erreur RendezVous::getConflicts - ' . $e->getMessage());
            return [];
        }
    }

    // ─────────────────────────────────────────
    //  Compteurs
    // ─────────────────────────────────────────
    public function countByStatut(string $statut): int {
        try {
            $sql = "SELECT COUNT(*) as count FROM rendez_vous WHERE statut = :statut";
            $result = $this->db->query($sql, ['statut' => $statut]);
            return $result[0]['count'] ?? 0;
        } catch (Exception $e) {
            error_log('Erreur RendezVous::countByStatut - ' . $e->getMessage());
            return 0;
        }
    }

    public function countByUser(int $userId, string $filter = 'tous'): int {
        try {
            $where = "WHERE user_id = :user_id";

            if ($filter !== 'tous') {
                $where .= " AND statut = :statut";
            }

            $sql = "SELECT COUNT(*) as count FROM rendez_vous $where";

            $params = ['user_id' => $userId];
            if ($filter !== 'tous') {
                $params['statut'] = $filter;
            }

            $result = $this->db->query($sql, $params);
            return $result[0]['count'] ?? 0;
        } catch (Exception $e) {
            error_log('Erreur RendezVous::countByUser - ' . $e->getMessage());
            return 0;
        }
    }

    public function countByClient(int $clientId, string $filter = 'tous'): int {
        try {
            $where = "WHERE client_id = :client_id";

            if ($filter !== 'tous') {
                $where .= " AND statut = :statut";
            }

            $sql = "SELECT COUNT(*) as count FROM rendez_vous $where";

            $params = ['client_id' => $clientId];
            if ($filter !== 'tous') {
                $params['statut'] = $filter;
            }

            $result = $this->db->query($sql, $params);
            return $result[0]['count'] ?? 0;
        } catch (Exception $e) {
            error_log('Erreur RendezVous::countByClient - ' . $e->getMessage());
            return 0;
        }
    }

    public function countUpcoming(): int {
        try {
            $sql = "SELECT COUNT(*) as count FROM rendez_vous WHERE date_debut > NOW() AND statut IN ('prévu', 'confirmé')";
            $result = $this->db->query($sql);
            return $result[0]['count'] ?? 0;
        } catch (Exception $e) {
            error_log('Erreur RendezVous::countUpcoming - ' . $e->getMessage());
            return 0;
        }
    }

    public function countToday(int $userId = 0): int {
        try {
            $where = "WHERE DATE(date_debut) = DATE(NOW()) AND statut IN ('prévu', 'confirmé')";

            if ($userId > 0) {
                $where .= " AND user_id = :user_id";
            }

            $sql = "SELECT COUNT(*) as count FROM rendez_vous $where";

            $params = [];
            if ($userId > 0) {
                $params['user_id'] = $userId;
            }

            $result = $this->db->query($sql, $params);
            return $result[0]['count'] ?? 0;
        } catch (Exception $e) {
            error_log('Erreur RendezVous::countToday - ' . $e->getMessage());
            return 0;
        }
    }

    // ─────────────────────────────────────────
    //  Rappels et notifications
    // ─────────────────────────────────────────
    public function markReminderSent(int $id): bool {
        try {
            $sql = "UPDATE rendez_vous SET rappel_envoye = 1, date_rappel_envoye = NOW() WHERE id = :id";
            return $this->db->execute($sql, ['id' => $id]);
        } catch (Exception $e) {
            error_log('Erreur RendezVous::markReminderSent - ' . $e->getMessage());
            return false;
        }
    }

    public function addReminder(int $id, array $data): ?int {
        try {
            $sql = "INSERT INTO rendez_vous_rappels (rendez_vous_id, type, delai, message, statut, created_at)
                    VALUES (:rendez_vous_id, :type, :delai, :message, :statut, NOW())";

            $data['rendez_vous_id'] = $id;
            $result = $this->db->execute($sql, $data);
            return $result ? $this->db->lastInsertId() : null;
        } catch (Exception $e) {
            error_log('Erreur RendezVous::addReminder - ' . $e->getMessage());
            return null;
        }
    }

    public function getReminders(int $id): array {
        try {
            $sql = "SELECT * FROM rendez_vous_rappels WHERE rendez_vous_id = :rendez_vous_id ORDER BY created_at DESC";
            return $this->db->query($sql, ['rendez_vous_id' => $id]);
        } catch (Exception $e) {
            error_log('Erreur RendezVous::getReminders - ' . $e->getMessage());
            return [];
        }
    }

    // ─────────────────────────────────────────
    //  Ressources et participants
    // ─────────────────────────────────────────
    public function addParticipant(int $id, int $userId): bool {
        try {
            $sql = "INSERT INTO rendez_vous_participants (rendez_vous_id, user_id, created_at)
                    VALUES (:rendez_vous_id, :user_id, NOW())
                    ON DUPLICATE KEY UPDATE created_at = NOW()";

            return $this->db->execute($sql, ['rendez_vous_id' => $id, 'user_id' => $userId]);
        } catch (Exception $e) {
            error_log('Erreur RendezVous::addParticipant - ' . $e->getMessage());
            return false;
        }
    }

    public function removeParticipant(int $id, int $userId): bool {
        try {
            $sql = "DELETE FROM rendez_vous_participants WHERE rendez_vous_id = :rendez_vous_id AND user_id = :user_id";
            return $this->db->execute($sql, ['rendez_vous_id' => $id, 'user_id' => $userId]);
        } catch (Exception $e) {
            error_log('Erreur RendezVous::removeParticipant - ' . $e->getMessage());
            return false;
        }
    }

    public function getParticipants(int $id): array {
        try {
            $sql = "SELECT u.* FROM users u
                    LEFT JOIN rendez_vous_participants p ON u.id = p.user_id
                    WHERE p.rendez_vous_id = :rendez_vous_id
                    ORDER BY u.nom ASC";

            return $this->db->query($sql, ['rendez_vous_id' => $id]);
        } catch (Exception $e) {
            error_log('Erreur RendezVous::getParticipants - ' . $e->getMessage());
            return [];
        }
    }

    public function addResource(int $id, string $resource): bool {
        try {
            $sql = "INSERT INTO rendez_vous_ressources (rendez_vous_id, ressource, created_at)
                    VALUES (:rendez_vous_id, :ressource, NOW())
                    ON DUPLICATE KEY UPDATE created_at = NOW()";

            return $this->db->execute($sql, ['rendez_vous_id' => $id, 'ressource' => $resource]);
        } catch (Exception $e) {
            error_log('Erreur RendezVous::addResource - ' . $e->getMessage());
            return false;
        }
    }

    public function removeResource(int $id, string $resource): bool {
        try {
            $sql = "DELETE FROM rendez_vous_ressources WHERE rendez_vous_id = :rendez_vous_id AND ressource = :ressource";
            return $this->db->execute($sql, ['rendez_vous_id' => $id, 'ressource' => $resource]);
        } catch (Exception $e) {
            error_log('Erreur RendezVous::removeResource - ' . $e->getMessage());
            return false;
        }
    }

    public function getResources(int $id): array {
        try {
            $sql = "SELECT * FROM rendez_vous_ressources WHERE rendez_vous_id = :rendez_vous_id ORDER BY created_at ASC";
            return $this->db->query($sql, ['rendez_vous_id' => $id]);
        } catch (Exception $e) {
            error_log('Erreur RendezVous::getResources - ' . $e->getMessage());
            return [];
        }
    }

    // ─────────────────────────────────────────
    //  Documents et notes
    // ─────────────────────────────────────────
    public function addDocument(int $id, array $data): ?int {
        try {
            $sql = "INSERT INTO rendez_vous_documents (rendez_vous_id, nom, fichier, type, created_at)
                    VALUES (:rendez_vous_id, :nom, :fichier, :type, NOW())";

            $data['rendez_vous_id'] = $id;
            $result = $this->db->execute($sql, $data);
            return $result ? $this->db->lastInsertId() : null;
        } catch (Exception $e) {
            error_log('Erreur RendezVous::addDocument - ' . $e->getMessage());
            return null;
        }
    }

    public function removeDocument(int $documentId): bool {
        try {
            $sql = "DELETE FROM rendez_vous_documents WHERE id = :id";
            return $this->db->execute($sql, ['id' => $documentId]);
        } catch (Exception $e) {
            error_log('Erreur RendezVous::removeDocument - ' . $e->getMessage());
            return false;
        }
    }

    public function getDocuments(int $id): array {
        try {
            $sql = "SELECT * FROM rendez_vous_documents WHERE rendez_vous_id = :rendez_vous_id ORDER BY created_at DESC";
            return $this->db->query($sql, ['rendez_vous_id' => $id]);
        } catch (Exception $e) {
            error_log('Erreur RendezVous::getDocuments - ' . $e->getMessage());
            return [];
        }
    }

    public function addNote(int $id, array $data): ?int {
        try {
            $sql = "INSERT INTO rendez_vous_notes (rendez_vous_id, user_id, contenu, created_at, updated_at)
                    VALUES (:rendez_vous_id, :user_id, :contenu, NOW(), NOW())";

            $data['rendez_vous_id'] = $id;
            $result = $this->db->execute($sql, $data);
            return $result ? $this->db->lastInsertId() : null;
        } catch (Exception $e) {
            error_log('Erreur RendezVous::addNote - ' . $e->getMessage());
            return null;
        }
    }

    public function updateNote(int $noteId, string $contenu): bool {
        try {
            $sql = "UPDATE rendez_vous_notes SET contenu = :contenu, updated_at = NOW() WHERE id = :id";
            return $this->db->execute($sql, ['id' => $noteId, 'contenu' => $contenu]);
        } catch (Exception $e) {
            error_log('Erreur RendezVous::updateNote - ' . $e->getMessage());
            return false;
        }
    }

    public function removeNote(int $noteId): bool {
        try {
            $sql = "DELETE FROM rendez_vous_notes WHERE id = :id";
            return $this->db->execute($sql, ['id' => $noteId]);
        } catch (Exception $e) {
            error_log('Erreur RendezVous::removeNote - ' . $e->getMessage());
            return false;
        }
    }

    public function getNotes(int $id): array {
        try {
            $sql = "SELECT rn.*, u.nom, u.prenom, u.avatar FROM rendez_vous_notes rn
                    LEFT JOIN users u ON rn.user_id = u.id
                    WHERE rn.rendez_vous_id = :rendez_vous_id
                    ORDER BY rn.created_at DESC";

            return $this->db->query($sql, ['rendez_vous_id' => $id]);
        } catch (Exception $e) {
            error_log('Erreur RendezVous::getNotes - ' . $e->getMessage());
            return [];
        }
    }

    // ─────────────────────────────────────────
    //  Historique et suivi
    // ─────────────────────────────────────────
    public function addHistorique(int $id, array $data): bool {
        try {
            $sql = "INSERT INTO rendez_vous_historiques (rendez_vous_id, action, description, user_id, created_at)
                    VALUES (:rendez_vous_id, :action, :description, :user_id, NOW())";

            $data['rendez_vous_id'] = $id;
            return $this->db->execute($sql, $data);
        } catch (Exception $e) {
            error_log('Erreur RendezVous::addHistorique - ' . $e->getMessage());
            return false;
        }
    }

    public function getHistorique(int $id): array {
        try {
            $sql = "SELECT h.*, u.nom, u.prenom, u.avatar FROM rendez_vous_historiques h
                    LEFT JOIN users u ON h.user_id = u.id
                    WHERE h.rendez_vous_id = :rendez_vous_id
                    ORDER BY h.created_at DESC";

            return $this->db->query($sql, ['rendez_vous_id' => $id]);
        } catch (Exception $e) {
            error_log('Erreur RendezVous::getHistorique - ' . $e->getMessage());
            return [];
        }
    }

    // ─────────────────────────────────────────
    //  Rapports et statistiques
    // ─────────────────────────────────────────
    public function getStats(string $dateDebut = '', string $dateFin = '', int $userId = 0): array {
        try {
            $where = "WHERE 1=1";

            if (!empty($dateDebut)) {
                $where .= " AND DATE(date_debut) >= :date_debut";
            }

            if (!empty($dateFin)) {
                $where .= " AND DATE(date_debut) <= :date_fin";
            }

            if ($userId > 0) {
                $where .= " AND user_id = :user_id";
            }

            $sql = "SELECT 
                           COUNT(*) as total,
                           SUM(CASE WHEN statut = 'prévu' THEN 1 ELSE 0 END) as prevu,
                           SUM(CASE WHEN statut = 'confirmé' THEN 1 ELSE 0 END) as confirme,
                           SUM(CASE WHEN statut = 'complété' THEN 1 ELSE 0 END) as complete,
                           SUM(CASE WHEN statut = 'annulé' THEN 1 ELSE 0 END) as annule,
                           SUM(CASE WHEN statut = 'reporté' THEN 1 ELSE 0 END) as reporte,
                           COUNT(DISTINCT client_id) as nb_clients,
                           COUNT(DISTINCT user_id) as nb_utilisateurs
                    FROM rendez_vous
                    $where";

            $params = [];
            if (!empty($dateDebut)) {
                $params['date_debut'] = $dateDebut;
            }
            if (!empty($dateFin)) {
                $params['date_fin'] = $dateFin;
            }
            if ($userId > 0) {
                $params['user_id'] = $userId;
            }

            $result = $this->db->query($sql, $params);
            return $result ? $result[0] : [];
        } catch (Exception $e) {
            error_log('Erreur RendezVous::getStats - ' . $e->getMessage());
            return [];
        }
    }

    public function getStatsByUser(int $userId, string $dateDebut = '', string $dateFin = ''): array {
        try {
            $where = "WHERE user_id = :user_id";

            if (!empty($dateDebut)) {
                $where .= " AND DATE(date_debut) >= :date_debut";
            }

            if (!empty($dateFin)) {
                $where .= " AND DATE(date_debut) <= :date_fin";
            }

            $sql = "SELECT 
                           COUNT(*) as total,
                           SUM(CASE WHEN statut = 'prévu' THEN 1 ELSE 0 END) as prevu,
                           SUM(CASE WHEN statut = 'confirmé' THEN 1 ELSE 0 END) as confirme,
                           SUM(CASE WHEN statut = 'complété' THEN 1 ELSE 0 END) as complete,
                           SUM(CASE WHEN statut = 'annulé' THEN 1 ELSE 0 END) as annule,
                           AVG(DATEDIFF(MINUTE, date_debut, date_fin)) as duree_moyenne
                    FROM rendez_vous
                    $where";

            $params = ['user_id' => $userId];
            if (!empty($dateDebut)) {
                $params['date_debut'] = $dateDebut;
            }
            if (!empty($dateFin)) {
                $params['date_fin'] = $dateFin;
            }

            $result = $this->db->query($sql, $params);
            return $result ? $result[0] : [];
        } catch (Exception $e) {
            error_log('Erreur RendezVous::getStatsByUser - ' . $e->getMessage());
            return [];
        }
    }

    public function getAverageLength(): float {
        try {
            $sql = "SELECT AVG(TIMESTAMPDIFF(MINUTE, date_debut, date_fin)) as avg FROM rendez_vous WHERE statut IN ('complété', 'confirmé')";
            $result = $this->db->query($sql);
            return (float)($result[0]['avg'] ?? 0);
        } catch (Exception $e) {
            error_log('Erreur RendezVous::getAverageLength - ' . $e->getMessage());
            return 0.0;
        }
    }

    // ─────────────────────────────────────────
    //  API et export
    // ─────────────────────────────────────────
    public function getApiListing(int $offset, int $limit): array {
        try {
            $sql = "SELECT rv.id, rv.titre, rv.date_debut, rv.date_fin, rv.type, rv.statut,
                           c.nom as client_nom, c.prenom as client_prenom,
                           u.nom as user_nom, u.prenom as user_prenom
                    FROM rendez_vous rv
                    LEFT JOIN clients c ON rv.client_id = c.id
                    LEFT JOIN users u ON rv.user_id = u.id
                    WHERE rv.statut IN ('prévu', 'confirmé', 'complété')
                    ORDER BY rv.date_debut DESC
                    LIMIT :offset, :limit";

            return $this->db->query($sql, ['offset' => $offset, 'limit' => $limit]);
        } catch (Exception $e) {
            error_log('Erreur RendezVous::getApiListing - ' . $e->getMessage());
            return [];
        }
    }

    public function getCalendarView(string $dateDebut, string $dateFin, int $userId = 0): array {
        try {
            $where = "WHERE rv.date_debut BETWEEN :date_debut AND :date_fin AND rv.statut != 'annulé'";

            if ($userId > 0) {
                $where .= " AND rv.user_id = :user_id";
            }

            $sql = "SELECT rv.id, rv.titre, rv.date_debut, rv.date_fin, rv.type, rv.statut, rv.lieu,
                           c.nom as client_nom, c.prenom as client_prenom
                    FROM rendez_vous rv
                    LEFT JOIN clients c ON rv.client_id = c.id
                    $where
                    ORDER BY rv.date_debut ASC";

            $params = [
                'date_debut' => $dateDebut . ' 00:00:00',
                'date_fin' => $dateFin . ' 23:59:59',
            ];
            if ($userId > 0) {
                $params['user_id'] = $userId;
            }

            return $this->db->query($sql, $params);
        } catch (Exception $e) {
            error_log('Erreur RendezVous::getCalendarView - ' . $e->getMessage());
            return [];
        }
    }

    public function exportList(string $dateDebut = '', string $dateFin = '', int $userId = 0): array {
        try {
            $where = "WHERE 1=1";

            if (!empty($dateDebut)) {
                $where .= " AND DATE(date_debut) >= :date_debut";
            }

            if (!empty($dateFin)) {
                $where .= " AND DATE(date_debut) <= :date_fin";
            }

            if ($userId > 0) {
                $where .= " AND user_id = :user_id";
            }

            $sql = "SELECT rv.*, c.nom as client_nom, c.email as client_email, u.nom as user_nom FROM rendez_vous rv
                    LEFT JOIN clients c ON rv.client_id = c.id
                    LEFT JOIN users u ON rv.user_id = u.id
                    $where
                    ORDER BY rv.date_debut DESC";

            $params = [];
            if (!empty($dateDebut)) {
                $params['date_debut'] = $dateDebut;
            }
            if (!empty($dateFin)) {
                $params['date_fin'] = $dateFin;
            }
            if ($userId > 0) {
                $params['user_id'] = $userId;
            }

            return $this->db->query($sql, $params);
        } catch (Exception $e) {
            error_log('Erreur RendezVous::exportList - ' . $e->getMessage());
            return [];
        }
    }

    public function getLastRendezVous(int $clientId): ?array {
        try {
            $sql = "SELECT rv.* FROM rendez_vous rv
                    WHERE rv.client_id = :client_id
                    ORDER BY rv.date_debut DESC
                    LIMIT 1";

            $result = $this->db->query($sql, ['client_id' => $clientId]);
            return $result ? $result[0] : null;
        } catch (Exception $e) {
            error_log('Erreur RendezVous::getLastRendezVous - ' . $e->getMessage());
            return null;
        }
    }

    public function getNextRendezVous(int $clientId): ?array {
        try {
            $sql = "SELECT rv.* FROM rendez_vous rv
                    WHERE rv.client_id = :client_id AND rv.date_debut > NOW()
                    ORDER BY rv.date_debut ASC
                    LIMIT 1";

            $result = $this->db->query($sql, ['client_id' => $clientId]);
            return $result ? $result[0] : null;
        } catch (Exception $e) {
            error_log('Erreur RendezVous::getNextRendezVous - ' . $e->getMessage());
            return null;
        }
    }
}
?>
