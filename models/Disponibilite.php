<?php

require_once __DIR__ . '/../config/database.php';  // ✅
class Disponibilite {

    private Database $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    // ─────────────────────────────────────────
    //  CRUD de base
    // ─────────────────────────────────────────
public function create(array $data): ?int {
    try {
        $sql = "INSERT INTO disponibilites (medecin_id, jour_semaine, heure_debut, heure_fin, actif, created_at, updated_at) 
                VALUES (:medecin_id, :jour_semaine, :heure_debut, :heure_fin, :actif, NOW(), NOW())";
        
        $params = [
            ':medecin_id' => $data['medecin_id'],
            ':jour_semaine' => $data['jour_semaine'],
            ':heure_debut' => $data['heure_debut'],
            ':heure_fin' => $data['heure_fin'],
            ':actif' => $data['actif'] ?? 1
        ];
        
        $result = $this->db->execute($sql, $params);
        return $result ? $this->db->lastInsertId() : null;
    } catch (Exception $e) {
        error_log('Erreur Disponibilite::create - ' . $e->getMessage());
        return null;
    }
}

public function getById(int $id): ?array {
    try {
        $sql = "SELECT * FROM disponibilites WHERE id = :id";
        $result = $this->db->query($sql, ['id' => $id]);
        return ($result && count($result) > 0) ? $result[0] : null;
    } catch (Exception $e) {
        error_log('Erreur Disponibilite::getById - ' . $e->getMessage());
        return null;
    }
}

public function delete(int $id): bool {
    try {
        $sql = "DELETE FROM disponibilites WHERE id = :id";
        return $this->db->execute($sql, ['id' => $id]);
    } catch (Exception $e) {
        error_log('Erreur Disponibilite::delete - ' . $e->getMessage());
        return false;
    }
}



    // ─────────────────────────────────────────
    //  Récupération avec filtres
    // ─────────────────────────────────────────
    public function getAll(int $offset = 0, int $limit = 20): array {
        try {
            $sql = "SELECT d.*, u.nom, u.prenom FROM disponibilites d
                    LEFT JOIN users u ON d.user_id = u.id
                    ORDER BY d.jour_semaine ASC, d.heure_debut ASC
                    LIMIT :offset, :limit";

            return $this->db->query($sql, ['offset' => $offset, 'limit' => $limit]);
        } catch (Exception $e) {
            error_log('Erreur Disponibilite::getAll - ' . $e->getMessage());
            return [];
        }
    }

    public function countAll(): int {
        try {
            $sql = "SELECT COUNT(*) as count FROM disponibilites";
            $result = $this->db->query($sql);
            return $result[0]['count'] ?? 0;
        } catch (Exception $e) {
            error_log('Erreur Disponibilite::countAll - ' . $e->getMessage());
            return 0;
        }
    }

    public function getByUser(int $userId, int $offset = 0, int $limit = 20): array {
        try {
            $sql = "SELECT d.*, u.nom, u.prenom FROM disponibilites d
                    LEFT JOIN users u ON d.user_id = u.id
                    WHERE d.user_id = :user_id
                    ORDER BY d.jour_semaine ASC, d.heure_debut ASC
                    LIMIT :offset, :limit";

            return $this->db->query($sql, ['user_id' => $userId, 'offset' => $offset, 'limit' => $limit]);
        } catch (Exception $e) {
            error_log('Erreur Disponibilite::getByUser - ' . $e->getMessage());
            return [];
        }
    }

    public function countByUser(int $userId): int {
        try {
            $sql = "SELECT COUNT(*) as count FROM disponibilites WHERE user_id = :user_id";
            $result = $this->db->query($sql, ['user_id' => $userId]);
            return $result[0]['count'] ?? 0;
        } catch (Exception $e) {
            error_log('Erreur Disponibilite::countByUser - ' . $e->getMessage());
            return 0;
        }
    }

    public function getByDayOfWeek(int $userId, int $dayOfWeek): array {
        try {
            $sql = "SELECT d.* FROM disponibilites d
                    WHERE d.user_id = :user_id AND d.jour_semaine = :jour_semaine
                    ORDER BY d.heure_debut ASC";

            return $this->db->query($sql, ['user_id' => $userId, 'jour_semaine' => $dayOfWeek]);
        } catch (Exception $e) {
            error_log('Erreur Disponibilite::getByDayOfWeek - ' . $e->getMessage());
            return [];
        }
    }

    public function getByType(int $userId, string $type): array {
        try {
            $sql = "SELECT d.* FROM disponibilites d
                    WHERE d.user_id = :user_id AND d.type = :type
                    ORDER BY d.jour_semaine ASC, d.heure_debut ASC";

            return $this->db->query($sql, ['user_id' => $userId, 'type' => $type]);
        } catch (Exception $e) {
            error_log('Erreur Disponibilite::getByType - ' . $e->getMessage());
            return [];
        }
    }

    public function getWeeklySchedule(int $userId): array {
        try {
            $sql = "SELECT d.* FROM disponibilites d
                    WHERE d.user_id = :user_id AND d.type = 'récurrente'
                    ORDER BY d.jour_semaine ASC, d.heure_debut ASC";

            $result = $this->db->query($sql, ['user_id' => $userId]);

            // Organiser par jour de semaine
            $schedule = [];
            $daysNames = ['Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi', 'Dimanche'];

            for ($i = 1; $i <= 7; $i++) {
                $schedule[$daysNames[$i - 1]] = [];
            }

            foreach ($result as $disp) {
                $dayName = $daysNames[$disp['jour_semaine'] - 1] ?? '';
                if ($dayName) {
                    $schedule[$dayName][] = $disp;
                }
            }

            return $schedule;
        } catch (Exception $e) {
            error_log('Erreur Disponibilite::getWeeklySchedule - ' . $e->getMessage());
            return [];
        }
    }

    public function getByDate(int $userId, string $date): array {
        try {
            $dayOfWeek = (int)date('N', strtotime($date)); // 1=Lundi, 7=Dimanche

            $sql = "SELECT d.* FROM disponibilites d
                    WHERE d.user_id = :user_id 
                    AND (
                        (d.type = 'récurrente' AND d.jour_semaine = :jour_semaine)
                        OR (d.type = 'ponctuelle' AND DATE(d.date_debut) = :date)
                    )
                    ORDER BY d.heure_debut ASC";

            return $this->db->query($sql, [
                'user_id' => $userId,
                'jour_semaine' => $dayOfWeek,
                'date' => $date,
            ]);
        } catch (Exception $e) {
            error_log('Erreur Disponibilite::getByDate - ' . $e->getMessage());
            return [];
        }
    }

    public function getByDateRange(int $userId, string $dateDebut, string $dateFin): array {
        try {
            $sql = "SELECT d.* FROM disponibilites d
                    WHERE d.user_id = :user_id
                    AND d.type = 'ponctuelle'
                    AND d.date_debut BETWEEN :date_debut AND :date_fin
                    ORDER BY d.date_debut ASC, d.heure_debut ASC";

            return $this->db->query($sql, [
                'user_id' => $userId,
                'date_debut' => $dateDebut . ' 00:00:00',
                'date_fin' => $dateFin . ' 23:59:59',
            ]);
        } catch (Exception $e) {
            error_log('Erreur Disponibilite::getByDateRange - ' . $e->getMessage());
            return [];
        }
    }

    public function getUnavailable(int $userId): array {
        try {
            $sql = "SELECT d.* FROM disponibilites d
                    WHERE d.user_id = :user_id AND d.type = 'indisponibilité'
                    ORDER BY d.date_debut ASC, d.heure_debut ASC";

            return $this->db->query($sql, ['user_id' => $userId]);
        } catch (Exception $e) {
            error_log('Erreur Disponibilite::getUnavailable - ' . $e->getMessage());
            return [];
        }
    }

    public function getUpcomingUnavailable(int $userId, int $days = 30): array {
        try {
            $sql = "SELECT d.* FROM disponibilites d
                    WHERE d.user_id = :user_id 
                    AND d.type = 'indisponibilité'
                    AND d.date_debut BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL :days DAY)
                    ORDER BY d.date_debut ASC";

            return $this->db->query($sql, ['user_id' => $userId, 'days' => $days]);
        } catch (Exception $e) {
            error_log('Erreur Disponibilite::getUpcomingUnavailable - ' . $e->getMessage());
            return [];
        }
    }

    public function getRecent(int $limit = 10): array {
        try {
            $sql = "SELECT d.*, u.nom, u.prenom FROM disponibilites d
                    LEFT JOIN users u ON d.user_id = u.id
                    ORDER BY d.created_at DESC
                    LIMIT :limit";

            return $this->db->query($sql, ['limit' => $limit]);
        } catch (Exception $e) {
            error_log('Erreur Disponibilite::getRecent - ' . $e->getMessage());
            return [];
        }
    }

    // ─────────────────────────────────────────
    //  Vérification de disponibilité
    // ─────────────────────────────────────────
    public function isAvailable(int $userId, string $dateDebut, string $dateFin): bool {
        try {
            // Vérifier les indisponibilités
            $sql = "SELECT COUNT(*) as count FROM disponibilites
                    WHERE user_id = :user_id
                    AND type = 'indisponibilité'
                    AND (
                        (date_debut <= :date_debut AND date_fin >= :date_fin)
                        OR (date_debut < :date_fin AND date_fin > :date_debut)
                    )";

            $result = $this->db->query($sql, [
                'user_id' => $userId,
                'date_debut' => $dateDebut,
                'date_fin' => $dateFin,
            ]);

            return ($result[0]['count'] ?? 0) === 0;
        } catch (Exception $e) {
            error_log('Erreur Disponibilite::isAvailable - ' . $e->getMessage());
            return false;
        }
    }

    public function isTimeSlotAvailable(int $userId, string $dateDebut, string $dateFin): bool {
        try {
            $dayOfWeek = (int)date('N', strtotime($dateDebut));
            $startTime = date('H:i:s', strtotime($dateDebut));
            $endTime = date('H:i:s', strtotime($dateFin));

            // Vérifier les disponibilités récurrentes
            $sql = "SELECT COUNT(*) as count FROM disponibilites
                    WHERE user_id = :user_id
                    AND type = 'récurrente'
                    AND jour_semaine = :jour_semaine
                    AND heure_debut <= :heure_debut
                    AND heure_fin >= :heure_fin";

            $result = $this->db->query($sql, [
                'user_id' => $userId,
                'jour_semaine' => $dayOfWeek,
                'heure_debut' => $startTime,
                'heure_fin' => $endTime,
            ]);

            if (($result[0]['count'] ?? 0) === 0) {
                return false;
            }

            // Vérifier qu'il n'y a pas d'indisponibilité
            return $this->isAvailable($userId, $dateDebut, $dateFin);
        } catch (Exception $e) {
            error_log('Erreur Disponibilite::isTimeSlotAvailable - ' . $e->getMessage());
            return false;
        }
    }

    public function getConflicts(int $userId, string $dateDebut, string $dateFin): array {
        try {
            $sql = "SELECT d.* FROM disponibilites d
                    WHERE d.user_id = :user_id
                    AND d.type = 'indisponibilité'
                    AND (
                        (d.date_debut <= :date_debut AND d.date_fin >= :date_fin)
                        OR (d.date_debut < :date_fin AND d.date_fin > :date_debut)
                    )
                    ORDER BY d.date_debut ASC";

            return $this->db->query($sql, [
                'user_id' => $userId,
                'date_debut' => $dateDebut,
                'date_fin' => $dateFin,
            ]);
        } catch (Exception $e) {
            error_log('Erreur Disponibilite::getConflicts - ' . $e->getMessage());
            return [];
        }
    }

    // ─────────────────────────────────────────
    //  Créneaux disponibles
    // ─────────────────────────────────────────
    public function getAvailableSlots(int $userId, string $date, int $durationMinutes = 60): array {
        try {
            $dayOfWeek = (int)date('N', strtotime($date));
            $slots = [];

            // Récupérer les disponibilités de la journée
            $sql = "SELECT d.* FROM disponibilites d
                    WHERE d.user_id = :user_id
                    AND d.type = 'récurrente'
                    AND d.jour_semaine = :jour_semaine
                    ORDER BY d.heure_debut ASC";

            $availabilities = $this->db->query($sql, [
                'user_id' => $userId,
                'jour_semaine' => $dayOfWeek,
            ]);

            // Vérifier les indisponibilités
            $unavailableSql = "SELECT d.* FROM disponibilites d
                              WHERE d.user_id = :user_id
                              AND d.type = 'indisponibilité'
                              AND DATE(d.date_debut) = :date
                              ORDER BY d.date_debut ASC";

            $unavailable = $this->db->query($unavailableSql, [
                'user_id' => $userId,
                'date' => $date,
            ]);

            // Générer les créneaux disponibles
            foreach ($availabilities as $avail) {
                $current = strtotime($date . ' ' . $avail['heure_debut']);
                $end = strtotime($date . ' ' . $avail['heure_fin']);

                while ($current + ($durationMinutes * 60) <= $end) {
                    $slotStart = date('Y-m-d H:i:s', $current);
                    $slotEnd = date('Y-m-d H:i:s', $current + ($durationMinutes * 60));

                    // Vérifier qu'il n'y a pas d'indisponibilité
                    $hasConflict = false;
                    foreach ($unavailable as $unav) {
                        if ($slotStart < $unav['date_fin'] && $slotEnd > $unav['date_debut']) {
                            $hasConflict = true;
                            break;
                        }
                    }

                    if (!$hasConflict) {
                        $slots[] = [
                            'debut' => $slotStart,
                            'fin' => $slotEnd,
                        ];
                    }

                    $current += ($durationMinutes * 60);
                }
            }

            return $slots;
        } catch (Exception $e) {
            error_log('Erreur Disponibilite::getAvailableSlots - ' . $e->getMessage());
            return [];
        }
    }

    public function getAvailableSlotsForRange(int $userId, string $dateDebut, string $dateFin, int $durationMinutes = 60, int $maxSlots = 50): array {
        try {
            $slots = [];
            $current = strtotime($dateDebut);
            $end = strtotime($dateFin);
            $count = 0;

            while ($current <= $end && $count < $maxSlots) {
                $date = date('Y-m-d', $current);
                $daySlots = $this->getAvailableSlots($userId, $date, $durationMinutes);

                foreach ($daySlots as $slot) {
                    if ($count >= $maxSlots) {
                        break;
                    }
                    $slots[] = $slot;
                    $count++;
                }

                $current = strtotime('+1 day', $current);
            }

            return $slots;
        } catch (Exception $e) {
            error_log('Erreur Disponibilite::getAvailableSlotsForRange - ' . $e->getMessage());
            return [];
        }
    }

    public function getFirstAvailableSlot(int $userId, int $durationMinutes = 60): ?array {
        try {
            $date = date('Y-m-d');
            $slots = $this->getAvailableSlots($userId, $date, $durationMinutes);

            if (empty($slots)) {
                // Chercher le prochain jour disponible
                for ($i = 1; $i <= 30; $i++) {
                    $checkDate = date('Y-m-d', strtotime("+$i days"));
                    $slots = $this->getAvailableSlots($userId, $checkDate, $durationMinutes);

                    if (!empty($slots)) {
                        return $slots[0];
                    }
                }
                return null;
            }

            return $slots[0];
        } catch (Exception $e) {
            error_log('Erreur Disponibilite::getFirstAvailableSlot - ' . $e->getMessage());
            return null;
        }
    }

    // ─────────────────────────────────────────
    //  Marquer comme indisponible
    // ─────────────────────────────────────────
    public function markUnavailable(int $userId, string $dateDebut, string $dateFin, string $raison = ''): ?int {
        try {
            $sql = "INSERT INTO disponibilites (user_id, type, date_debut, date_fin, notes, created_at, updated_at)
                    VALUES (:user_id, 'indisponibilité', :date_debut, :date_fin, :notes, NOW(), NOW())";

            $result = $this->db->execute($sql, [
                'user_id' => $userId,
                'date_debut' => $dateDebut,
                'date_fin' => $dateFin,
                'notes' => $raison,
            ]);

            return $result ? $this->db->lastInsertId() : null;
        } catch (Exception $e) {
            error_log('Erreur Disponibilite::markUnavailable - ' . $e->getMessage());
            return null;
        }
    }

    public function markAvailable(int $unavailableId): bool {
        try {
            return $this->delete($unavailableId);
        } catch (Exception $e) {
            error_log('Erreur Disponibilite::markAvailable - ' . $e->getMessage());
            return false;
        }
    }

    public function addRecurringSchedule(int $userId, int $dayOfWeek, string $heureDebut, string $heureFin, string $notes = ''): ?int {
        try {
            $sql = "INSERT INTO disponibilites (user_id, jour_semaine, heure_debut, heure_fin, type, repetition, notes, created_at, updated_at)
                    VALUES (:user_id, :jour_semaine, :heure_debut, :heure_fin, 'récurrente', 'hebdomadaire', :notes, NOW(), NOW())";

            $result = $this->db->execute($sql, [
                'user_id' => $userId,
                'jour_semaine' => $dayOfWeek,
                'heure_debut' => $heureDebut,
                'heure_fin' => $heureFin,
                'notes' => $notes,
            ]);

            return $result ? $this->db->lastInsertId() : null;
        } catch (Exception $e) {
            error_log('Erreur Disponibilite::addRecurringSchedule - ' . $e->getMessage());
            return null;
        }
    }

    public function addPunctualAvailability(int $userId, string $dateDebut, string $dateFin, string $notes = ''): ?int {
        try {
            $sql = "INSERT INTO disponibilites (user_id, type, date_debut, date_fin, notes, created_at, updated_at)
                    VALUES (:user_id, 'ponctuelle', :date_debut, :date_fin, :notes, NOW(), NOW())";

            $result = $this->db->execute($sql, [
                'user_id' => $userId,
                'date_debut' => $dateDebut,
                'date_fin' => $dateFin,
                'notes' => $notes,
            ]);

            return $result ? $this->db->lastInsertId() : null;
        } catch (Exception $e) {
            error_log('Erreur Disponibilite::addPunctualAvailability - ' . $e->getMessage());
            return null;
        }
    }

    // ─────────────────────────────────────────
    //  Statistiques
    // ─────────────────────────────────────────
    public function getHoursPerWeek(int $userId): float {
        try {
            $sql = "SELECT SUM(TIMESTAMPDIFF(HOUR, heure_debut, heure_fin)) as total_hours
                    FROM disponibilites
                    WHERE user_id = :user_id AND type = 'récurrente'";

            $result = $this->db->query($sql, ['user_id' => $userId]);
            return (float)($result[0]['total_hours'] ?? 0);
        } catch (Exception $e) {
            error_log('Erreur Disponibilite::getHoursPerWeek - ' . $e->getMessage());
            return 0.0;
        }
    }

    public function getUnavailableHours(int $userId, string $dateDebut, string $dateFin): float {
        try {
            $sql = "SELECT SUM(TIMESTAMPDIFF(HOUR, date_debut, date_fin)) as total_hours
                    FROM disponibilites
                    WHERE user_id = :user_id 
                    AND type = 'indisponibilité'
                    AND date_debut BETWEEN :date_debut AND :date_fin";

            $result = $this->db->query($sql, [
                'user_id' => $userId,
                'date_debut' => $dateDebut . ' 00:00:00',
                'date_fin' => $dateFin . ' 23:59:59',
            ]);

            return (float)($result[0]['total_hours'] ?? 0);
        } catch (Exception $e) {
            error_log('Erreur Disponibilite::getUnavailableHours - ' . $e->getMessage());
            return 0.0;
        }
    }

    public function getAvailabilityStats(int $userId): array {
        try {
            $hoursPerWeek = $this->getHoursPerWeek($userId);

            $sql = "SELECT 
                           COUNT(*) as total_entries,
                           SUM(CASE WHEN type = 'récurrente' THEN 1 ELSE 0 END) as recurring_entries,
                           SUM(CASE WHEN type = 'ponctuelle' THEN 1 ELSE 0 END) as punctual_entries,
                           SUM(CASE WHEN type = 'indisponibilité' THEN 1 ELSE 0 END) as unavailable_entries
                    FROM disponibilites
                    WHERE user_id = :user_id";

            $result = $this->db->query($sql, ['user_id' => $userId]);

            return [
                'total_entries' => $result[0]['total_entries'] ?? 0,
                'recurring_entries' => $result[0]['recurring_entries'] ?? 0,
                'punctual_entries' => $result[0]['punctual_entries'] ?? 0,
                'unavailable_entries' => $result[0]['unavailable_entries'] ?? 0,
                'hours_per_week' => $hoursPerWeek,
            ];
        } catch (Exception $e) {
            error_log('Erreur Disponibilite::getAvailabilityStats - ' . $e->getMessage());
            return [];
        }
    }

    public function getTeamAvailability(array $userIds, string $date): array {
        try {
            $placeholders = implode(',', array_fill(0, count($userIds), '?'));

            $sql = "SELECT d.user_id, u.nom, u.prenom, d.heure_debut, d.heure_fin 
                    FROM disponibilites d
                    LEFT JOIN users u ON d.user_id = u.id
                    WHERE d.user_id IN ($placeholders)
                    AND (
                        (d.type = 'récurrente' AND d.jour_semaine = ?)
                        OR (d.type = 'ponctuelle' AND DATE(d.date_debut) = ?)
                    )
                    ORDER BY d.user_id, d.heure_debut ASC";

            $params = array_merge($userIds, [
                (int)date('N', strtotime($date)),
                $date,
            ]);

            return $this->db->query($sql, $params);
        } catch (Exception $e) {
            error_log('Erreur Disponibilite::getTeamAvailability - ' . $e->getMessage());
            return [];
        }
    }

    public function findCommonAvailability(array $userIds, string $dateDebut, string $dateFin, int $durationMinutes = 60): array {
        try {
            $commonSlots = [];
            $current = strtotime($dateDebut);
            $end = strtotime($dateFin);

            while ($current <= $end) {
                $date = date('Y-m-d', $current);

                // Vérifier la disponibilité pour tous les utilisateurs
                $isCommonlyAvailable = true;

                foreach ($userIds as $userId) {
                    $slots = $this->getAvailableSlots($userId, $date, $durationMinutes);
                    if (empty($slots)) {
                        $isCommonlyAvailable = false;
                        break;
                    }
                }

                if ($isCommonlyAvailable) {
                    $slots = $this->getAvailableSlots($userIds[0], $date, $durationMinutes);
                    foreach ($slots as $slot) {
                        $commonSlots[] = $slot;
                    }
                }

                $current = strtotime('+1 day', $current);
            }

            return $commonSlots;
        } catch (Exception $e) {
            error_log('Erreur Disponibilite::findCommonAvailability - ' . $e->getMessage());
            return [];
        }
    }

    // ─────────────────────────────────────────
    //  Synchronisation calendrier
    // ─────────────────────────────────────────
    public function syncWithCalendar(int $userId, array $events): bool {
        try {
            foreach ($events as $event) {
                // Marquer comme indisponible pour les événements occupés
                if (isset($event['busy']) && $event['busy']) {
                    $this->markUnavailable(
                        $userId,
                        $event['start'],
                        $event['end'],
                        $event['title'] ?? 'Calendrier'
                    );
                }
            }

            return true;
        } catch (Exception $e) {
            error_log('Erreur Disponibilite::syncWithCalendar - ' . $e->getMessage());
            return false;
        }
    }

    public function exportToCalendar(int $userId): array {
        try {
            $events = [];

            // Disponibilités récurrentes
            $recurringDisp = $this->getByType($userId, 'récurrente');
            foreach ($recurringDisp as $disp) {
                $events[] = [
                    'title' => 'Disponible',
                    'start_time' => $disp['heure_debut'],
                    'end_time' => $disp['heure_fin'],
                    'day_of_week' => $disp['jour_semaine'],
                    'type' => 'disponible',
                    'recurring' => true,
                ];
            }

            // Disponibilités ponctuelles
            $punctualDisp = $this->getByType($userId, 'ponctuelle');
            foreach ($punctualDisp as $disp) {
                $events[] = [
                    'title' => 'Disponible',
                    'start' => $disp['date_debut'],
                    'end' => $disp['date_fin'],
                    'type' => 'disponible',
                    'recurring' => false,
                ];
            }

            // Indisponibilités
            $unavailable = $this->getUnavailable($userId);
            foreach ($unavailable as $unav) {
                $events[] = [
                    'title' => 'Indisponible: ' . ($unav['notes'] ?? ''),
                    'start' => $unav['date_debut'],
                    'end' => $unav['date_fin'],
                    'type' => 'unavailable',
                    'busy' => true,
                ];
            }

            return $events;
        } catch (Exception $e) {
            error_log('Erreur Disponibilite::exportToCalendar - ' . $e->getMessage());
            return [];
        }
    }

    // ─────────────────────────────────────────
    //  API et rapports
    // ─────────────────────────────────────────
    public function getApiAvailableSlots(int $userId, string $dateDebut, string $dateFin, int $durationMinutes = 60): array {
        try {
            return $this->getAvailableSlotsForRange($userId, $dateDebut, $dateFin, $durationMinutes);
        } catch (Exception $e) {
            error_log('Erreur Disponibilite::getApiAvailableSlots - ' . $e->getMessage());
            return [];
        }
    }

    public function getCalendarView(int $userId, string $dateDebut, string $dateFin): array {
        try {
            $events = [];

            // Récupérer les disponibilités ponctuelles
            $punctual = $this->getByDateRange($userId, $dateDebut, $dateFin);
            foreach ($punctual as $disp) {
                $events[] = [
                    'id' => $disp['id'],
                    'title' => 'Disponible',
                    'start' => $disp['date_debut'],
                    'end' => $disp['date_fin'],
                    'color' => 'green',
                    'type' => 'availability',
                ];
            }

            // Récupérer les indisponibilités
            $unavailable = $this->getUpcomingUnavailable($userId, 365);
            foreach ($unavailable as $unav) {
                if ($unav['date_debut'] >= $dateDebut . ' 00:00:00' && $unav['date_debut'] <= $dateFin . ' 23:59:59') {
                    $events[] = [
                        'id' => $unav['id'],
                        'title' => 'Indisponible: ' . ($unav['notes'] ?? ''),
                        'start' => $unav['date_debut'],
                        'end' => $unav['date_fin'],
                        'color' => 'red',
                        'type' => 'unavailability',
                    ];
                }
            }

            return ['events' => $events];
        } catch (Exception $e) {
            error_log('Erreur Disponibilite::getCalendarView - ' . $e->getMessage());
            return ['events' => []];
        }
    }
    public function getByMedecin(int $medecinId): array {
        try {
            $sql = "SELECT d.*, 
                           CONCAT(u.prenom, ' ', u.nom) AS medecin_nom,
                           u.email AS medecin_email,
                           m.specialite
                    FROM disponibilites d
                    JOIN users u ON d.medecin_id = u.id
                    LEFT JOIN medecins m ON d.medecin_id = m.user_id
                    WHERE d.medecin_id = :medecin_id AND d.actif = 1
                    ORDER BY FIELD(d.jour_semaine, 'Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi', 'Dimanche'), d.heure_debut ASC";
            
            return $this->db->query($sql, ['medecin_id' => $medecinId]);
        } catch (Exception $e) {
            error_log('Erreur Disponibilite::getByMedecin - ' . $e->getMessage());
            return [];
        }
    }
    public function generateReport(int $userId, string $dateDebut, string $dateFin): array {
        try {
            $unavailableHours = $this->getUnavailableHours($userId, $dateDebut, $dateFin);
            $hoursPerWeek = $this->getHoursPerWeek($userId);
            $stats = $this->getAvailabilityStats($userId);

            return [
                'user_id' => $userId,
                'period' => [
                    'start' => $dateDebut,
                    'end' => $dateFin,
                ],
                'hours_per_week' => $hoursPerWeek,
                'unavailable_hours' => $unavailableHours,
                'total_entries' => $stats['total_entries'] ?? 0,
                'recurring_entries' => $stats['recurring_entries'] ?? 0,
                'punctual_entries' => $stats['punctual_entries'] ?? 0,
                'unavailable_entries' => $stats['unavailable_entries'] ?? 0,
            ];
        } catch (Exception $e) {
            error_log('Erreur Disponibilite::generateReport - ' . $e->getMessage());
            return [];
        }
    }

    public function deleteRecurringSchedule(int $userId, int $dayOfWeek): bool {
        try {
            $sql = "DELETE FROM disponibilites WHERE user_id = :user_id AND jour_semaine = :jour_semaine AND type = 'récurrente'";
            return $this->db->execute($sql, ['user_id' => $userId, 'jour_semaine' => $dayOfWeek]);
        } catch (Exception $e) {
            error_log('Erreur Disponibilite::deleteRecurringSchedule - ' . $e->getMessage());
            return false;
        }
    }

    public function clearUnavailability(int $userId, string $dateDebut, string $dateFin): bool {
        try {
            $sql = "DELETE FROM disponibilites 
                    WHERE user_id = :user_id 
                    AND type = 'indisponibilité'
                    AND date_debut >= :date_debut 
                    AND date_fin <= :date_fin";

            return $this->db->execute($sql, [
                'user_id' => $userId,
                'date_debut' => $dateDebut . ' 00:00:00',
                'date_fin' => $dateFin . ' 23:59:59',
            ]);
        } catch (Exception $e) {
            error_log('Erreur Disponibilite::clearUnavailability - ' . $e->getMessage());
            return false;
        }
    }
}
?>
// update
