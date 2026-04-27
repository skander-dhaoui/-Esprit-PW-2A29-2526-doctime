<?php

require_once __DIR__ . '/../config/database.php';

class Patient {

    private ?PDO $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    public function __destruct() {
        $this->db = null;
    }

    // ─────────────────────────────────────────
    //  Profil patient
    // ─────────────────────────────────────────

    public function findByUserId(int $userId): array|false {
        $stmt = $this->db->prepare(
            "SELECT p.*, u.nom, u.prenom, u.email, u.telephone, u.adresse,
                    u.date_naissance, u.statut, u.created_at
             FROM patients p
             JOIN users u ON p.user_id = u.id
             WHERE p.user_id = :uid
             LIMIT 1"
        );
        $stmt->execute([':uid' => $userId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
/**
 * Récupère tous les patients
 */
public function getAll(): array {
    try {
        $stmt = $this->db->prepare("
            SELECT u.*, p.groupe_sanguin
            FROM users u
            JOIN patients p ON u.id = p.user_id
            WHERE u.role = 'patient'
            ORDER BY u.created_at DESC
        ");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log('Erreur Patient::getAll: ' . $e->getMessage());
        return [];
    }
}
    public function update(int $userId, array $data): bool {
        $allowed = ['groupe_sanguin', 'allergie', 'antecedents'];
        $fields  = [];
        $params  = [':uid' => $userId];

        foreach ($data as $key => $value) {
            if (in_array($key, $allowed, true)) {
                $fields[]        = "$key = :$key";
                $params[":$key"] = $value;
            }
        }

        if (empty($fields)) return false;

        $stmt = $this->db->prepare(
            "UPDATE patients SET " . implode(', ', $fields) . " WHERE user_id = :uid"
        );
        return $stmt->execute($params);
    }

    // ─────────────────────────────────────────
    //  Rendez-vous
    // ─────────────────────────────────────────

    public function getAppointments(int $userId): array {
        $stmt = $this->db->prepare(
            "SELECT r.*,
                    u.nom      AS medecin_nom,
                    u.prenom   AS medecin_prenom,
                    m.specialite
             FROM rendez_vous r
             JOIN users   u ON r.medecin_id = u.id
             JOIN medecins m ON r.medecin_id = m.user_id
             WHERE r.patient_id = :uid
             ORDER BY r.date DESC, r.heure DESC"
        );
        $stmt->execute([':uid' => $userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getNextAppointment(int $userId): array|false {
        $stmt = $this->db->prepare(
            "SELECT r.*,
                    u.nom    AS medecin_nom,
                    u.prenom AS medecin_prenom,
                    m.specialite
             FROM rendez_vous r
             JOIN users   u ON r.medecin_id = u.id
             JOIN medecins m ON r.medecin_id = m.user_id
             WHERE r.patient_id = :uid
               AND r.date >= CURDATE()
               AND r.statut IN ('en_attente','confirmé')
             ORDER BY r.date ASC, r.heure ASC
             LIMIT 1"
        );
        $stmt->execute([':uid' => $userId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getAppointmentById(int $id): array|false {
        $stmt = $this->db->prepare(
            "SELECT * FROM rendez_vous WHERE id = :id LIMIT 1"
        );
        $stmt->execute([':id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function createAppointment(array $data): int {
        $stmt = $this->db->prepare(
            "INSERT INTO rendez_vous
                (patient_id, medecin_id, date, heure, motif, statut, created_at)
             VALUES
                (:patient_id, :medecin_id, :date, :heure, :motif, :statut, NOW())"
        );
        $stmt->execute([
            ':patient_id' => $data['patient_id'],
            ':medecin_id' => $data['medecin_id'],
            ':date'       => $data['date'],
            ':heure'      => $data['heure'],
            ':motif'      => $data['motif']  ?? 'Consultation',
            ':statut'     => $data['statut'] ?? 'en_attente',
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function updateAppointmentStatus(int $id, string $status): bool {
        $stmt = $this->db->prepare(
            "UPDATE rendez_vous SET statut = :statut WHERE id = :id"
        );
        return $stmt->execute([':statut' => $status, ':id' => $id]);
    }

    // ─────────────────────────────────────────
    //  Réclamations
    // ─────────────────────────────────────────

    public function getClaims(int $userId): array {
        $stmt = $this->db->prepare(
            "SELECT * FROM reclamations
             WHERE patient_id = :uid
             ORDER BY created_at DESC"
        );
        $stmt->execute([':uid' => $userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getClaimById(int $id): array|false {
        $stmt = $this->db->prepare(
            "SELECT * FROM reclamations WHERE id = :id LIMIT 1"
        );
        $stmt->execute([':id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function createClaim(array $data): int {
        $stmt = $this->db->prepare(
            "INSERT INTO reclamations
                (patient_id, sujet, description, priorite, statut, created_at)
             VALUES
                (:patient_id, :sujet, :description, :priorite, :statut, NOW())"
        );
        $stmt->execute([
            ':patient_id'  => $data['patient_id'],
            ':sujet'       => $data['sujet'],
            ':description' => $data['description'],
            ':priorite'    => $data['priorite'] ?? 'moyenne',
            ':statut'      => $data['statut']   ?? 'en_cours',
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function updateClaim(int $id, array $data): bool {
        $allowed = ['statut', 'reponse'];
        $fields  = [];
        $params  = [':id' => $id];

        foreach ($data as $key => $value) {
            if (in_array($key, $allowed, true)) {
                $fields[]        = "$key = :$key";
                $params[":$key"] = $value;
            }
        }

        if (empty($fields)) return false;

        $stmt = $this->db->prepare(
            "UPDATE reclamations SET " . implode(', ', $fields) . " WHERE id = :id"
        );
        return $stmt->execute($params);
    }

    // ─────────────────────────────────────────
    //  Statistiques patient
    // ─────────────────────────────────────────

    public function getStats(int $userId): array {
        $total = $this->db->prepare(
            "SELECT COUNT(*) FROM rendez_vous WHERE patient_id = :uid"
        );
        $total->execute([':uid' => $userId]);

$upcoming = $this->db->prepare(
    "SELECT COUNT(*) FROM rendez_vous
     WHERE patient_id = :uid
       AND statut IN ('en_attente','confirmé')"
);
        $upcoming->execute([':uid' => $userId]);

        $claims = $this->db->prepare(
            "SELECT COUNT(*) FROM reclamations WHERE patient_id = :uid"
        );
        $claims->execute([':uid' => $userId]);

        return [
            'rdv_total'    => (int) $total->fetchColumn(),
            'rdv_a_venir'  => (int) $upcoming->fetchColumn(),
            'reclamations' => (int) $claims->fetchColumn(),
        ];
    }
}