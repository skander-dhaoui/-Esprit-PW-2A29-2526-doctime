<?php
require_once __DIR__ . '/../config/Database.php';

class Disponibilite {
    private PDO $db;

    public function __construct() {
        $this->db = Database::getInstance()->getPDO();
    }

    // ─────────────────────────────────────────
    //  CRUD - CREATE
    // ─────────────────────────────────────────
    public function create(array $data): int {
        $stmt = $this->db->prepare("
            INSERT INTO disponibilites (medecin_id, jour_semaine, heure_debut, heure_fin, actif, created_at)
            VALUES (:medecin_id, :jour_semaine, :heure_debut, :heure_fin, :actif, NOW())
        ");
        $stmt->execute([
            ':medecin_id' => $data['medecin_id'],
            ':jour_semaine' => $data['jour_semaine'],
            ':heure_debut' => $data['heure_debut'],
            ':heure_fin' => $data['heure_fin'],
            ':actif' => $data['actif'] ?? 1
        ]);
        return (int)$this->db->lastInsertId();
    }

    // ─────────────────────────────────────────
    //  CRUD - READ
    // ─────────────────────────────────────────
    public function getAll(): array {
        $stmt = $this->db->query("
            SELECT d.*, u.nom, u.prenom
            FROM disponibilites d
            LEFT JOIN users u ON d.medecin_id = u.id
            ORDER BY d.jour_semaine, d.heure_debut
        ");
        return $stmt->fetchAll();
    }

    public function getById(int $id): array|false {
        $stmt = $this->db->prepare("SELECT * FROM disponibilites WHERE id = :id");
        $stmt->execute([':id' => $id]);
        return $stmt->fetch();
    }

    public function getByMedecin(int $medecinId): array {
        $stmt = $this->db->prepare("
            SELECT * FROM disponibilites 
            WHERE medecin_id = :medecin_id AND actif = 1
            ORDER BY FIELD(jour_semaine, 'Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi', 'Dimanche'), heure_debut
        ");
        $stmt->execute([':medecin_id' => $medecinId]);
        return $stmt->fetchAll();
    }

    public function getByDay(string $jour): array {
        $stmt = $this->db->prepare("
            SELECT d.*, u.nom, u.prenom, m.specialite
            FROM disponibilites d
            LEFT JOIN users u ON d.medecin_id = u.id
            LEFT JOIN medecins m ON d.medecin_id = m.user_id
            WHERE d.jour_semaine = :jour AND d.actif = 1
            ORDER BY d.heure_debut
        ");
        $stmt->execute([':jour' => $jour]);
        return $stmt->fetchAll();
    }

    public function getAvailableSlots(int $medecinId, string $date): array {
        // Récupérer le jour de la semaine
        $jour = date('l', strtotime($date));
        $jourFr = $this->convertDayToFrench($jour);
        
        $stmt = $this->db->prepare("
            SELECT * FROM disponibilites 
            WHERE medecin_id = :medecin_id AND jour_semaine = :jour AND actif = 1
        ");
        $stmt->execute([':medecin_id' => $medecinId, ':jour' => $jourFr]);
        $dispos = $stmt->fetchAll();
        
        // Récupérer les créneaux déjà pris
        $stmt2 = $this->db->prepare("
            SELECT heure_rendezvous FROM rendez_vous 
            WHERE medecin_id = :medecin_id AND date_rendezvous = :date AND statut NOT IN ('annulé')
        ");
        $stmt2->execute([':medecin_id' => $medecinId, ':date' => $date]);
        $pris = $stmt2->fetchAll(PDO::FETCH_COLUMN);
        
        // Générer les créneaux disponibles
        $slots = [];
        foreach ($dispos as $dispo) {
            $debut = strtotime($dispo['heure_debut']);
            $fin = strtotime($dispo['heure_fin']);
            $interval = 30 * 60; // 30 minutes
            
            for ($time = $debut; $time < $fin; $time += $interval) {
                $heure = date('H:i', $time);
                if (!in_array($heure, $pris)) {
                    $slots[] = $heure;
                }
            }
        }
        return $slots;
    }

    // ─────────────────────────────────────────
    //  CRUD - UPDATE
    // ─────────────────────────────────────────
    public function update(int $id, array $data): bool {
        $fields = [];
        $params = [':id' => $id];
        
        $allowed = ['jour_semaine', 'heure_debut', 'heure_fin', 'actif'];
        foreach ($data as $key => $value) {
            if (in_array($key, $allowed)) {
                $fields[] = "$key = :$key";
                $params[":$key"] = $value;
            }
        }
        
        if (empty($fields)) return false;
        
        $stmt = $this->db->prepare("
            UPDATE disponibilites SET " . implode(', ', $fields) . " WHERE id = :id
        ");
        return $stmt->execute($params);
    }

    public function toggleActive(int $id): bool {
        $stmt = $this->db->prepare("
            UPDATE disponibilites SET actif = NOT actif WHERE id = :id
        ");
        return $stmt->execute([':id' => $id]);
    }

    // ─────────────────────────────────────────
    //  CRUD - DELETE
    // ─────────────────────────────────────────
    public function delete(int $id): bool {
        $stmt = $this->db->prepare("DELETE FROM disponibilites WHERE id = :id");
        return $stmt->execute([':id' => $id]);
    }

    // ─────────────────────────────────────────
    //  HELPERS
    // ─────────────────────────────────────────
    private function convertDayToFrench(string $day): string {
        $days = [
            'Monday' => 'Lundi',
            'Tuesday' => 'Mardi',
            'Wednesday' => 'Mercredi',
            'Thursday' => 'Jeudi',
            'Friday' => 'Vendredi',
            'Saturday' => 'Samedi',
            'Sunday' => 'Dimanche'
        ];
        return $days[$day] ?? $day;
    }
}
?>