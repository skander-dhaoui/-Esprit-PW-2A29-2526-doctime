<?php
require_once __DIR__ . '/../config/Database.php';

class RendezVous {
    private PDO $db;

    public function __construct() {
        $this->db = Database::getInstance()->getPDO();
    }

    // ═══════════════════════════════════════════════════════════
    //  CRUD - CREATE
    // ═══════════════════════════════════════════════════════════
    public function create(array $data): int {
        $stmt = $this->db->prepare("
            INSERT INTO rendez_vous (patient_id, medecin_id, date_rendezvous, heure_rendezvous, motif, statut, created_at)
            VALUES (:patient_id, :medecin_id, :date_rendezvous, :heure_rendezvous, :motif, :statut, NOW())
        ");
        $stmt->execute([
            ':patient_id' => $data['patient_id'],
            ':medecin_id' => $data['medecin_id'],
            ':date_rendezvous' => $data['date_rendezvous'],
            ':heure_rendezvous' => $data['heure_rendezvous'],
            ':motif' => $data['motif'] ?? 'Consultation',
            ':statut' => $data['statut'] ?? 'en_attente'
        ]);
        return (int)$this->db->lastInsertId();
    }

    // ═══════════════════════════════════════════════════════════
    //  CRUD - READ
    // ═══════════════════════════════════════════════════════════
    public function getAll(): array {
        $stmt = $this->db->query("
            SELECT rv.*, 
                   up.nom as patient_nom, up.prenom as patient_prenom,
                   um.nom as medecin_nom, um.prenom as medecin_prenom,
                   m.specialite
            FROM rendez_vous rv
            LEFT JOIN users up ON rv.patient_id = up.id
            LEFT JOIN users um ON rv.medecin_id = um.id
            LEFT JOIN medecins m ON rv.medecin_id = m.user_id
            ORDER BY rv.date_rendezvous DESC, rv.heure_rendezvous DESC
        ");
        return $stmt->fetchAll();
    }

    public function getById(int $id): array|false {
        $stmt = $this->db->prepare("
            SELECT rv.*, 
                   up.nom as patient_nom, up.prenom as patient_prenom,
                   up.email as patient_email, up.telephone as patient_telephone,
                   um.nom as medecin_nom, um.prenom as medecin_prenom,
                   um.email as medecin_email, um.telephone as medecin_telephone,
                   m.specialite, m.cabinet_adresse
            FROM rendez_vous rv
            LEFT JOIN users up ON rv.patient_id = up.id
            LEFT JOIN users um ON rv.medecin_id = um.id
            LEFT JOIN medecins m ON rv.medecin_id = m.user_id
            WHERE rv.id = :id
        ");
        $stmt->execute([':id' => $id]);
        return $stmt->fetch();
    }

    // Pour le patient
    public function getByPatient(int $patientId): array {
        $stmt = $this->db->prepare("
            SELECT rv.*, 
                   um.nom as medecin_nom, um.prenom as medecin_prenom,
                   m.specialite
            FROM rendez_vous rv
            LEFT JOIN users um ON rv.medecin_id = um.id
            LEFT JOIN medecins m ON rv.medecin_id = m.user_id
            WHERE rv.patient_id = :patient_id
            ORDER BY rv.date_rendezvous DESC, rv.heure_rendezvous DESC
        ");
        $stmt->execute([':patient_id' => $patientId]);
        return $stmt->fetchAll();
    }

    public function getUpcomingByPatient(int $patientId): array {
        $stmt = $this->db->prepare("
            SELECT rv.*, 
                   um.nom as medecin_nom, um.prenom as medecin_prenom,
                   m.specialite
            FROM rendez_vous rv
            LEFT JOIN users um ON rv.medecin_id = um.id
            LEFT JOIN medecins m ON rv.medecin_id = m.user_id
            WHERE rv.patient_id = :patient_id 
              AND rv.date_rendezvous >= CURDATE()
              AND rv.statut IN ('en_attente', 'confirmé')
            ORDER BY rv.date_rendezvous ASC, rv.heure_rendezvous ASC
        ");
        $stmt->execute([':patient_id' => $patientId]);
        return $stmt->fetchAll();
    }

    // Pour le médecin
    public function getByMedecin(int $medecinId): array {
        $stmt = $this->db->prepare("
            SELECT rv.*, 
                   up.nom as patient_nom, up.prenom as patient_prenom,
                   up.email as patient_email, up.telephone as patient_telephone
            FROM rendez_vous rv
            LEFT JOIN users up ON rv.patient_id = up.id
            WHERE rv.medecin_id = :medecin_id
            ORDER BY rv.date_rendezvous DESC, rv.heure_rendezvous DESC
        ");
        $stmt->execute([':medecin_id' => $medecinId]);
        return $stmt->fetchAll();
    }

    public function getTodayByMedecin(int $medecinId): array {
        $stmt = $this->db->prepare("
            SELECT rv.*, 
                   up.nom as patient_nom, up.prenom as patient_prenom,
                   up.email as patient_email, up.telephone as patient_telephone
            FROM rendez_vous rv
            LEFT JOIN users up ON rv.patient_id = up.id
            WHERE rv.medecin_id = :medecin_id AND rv.date_rendezvous = CURDATE()
            ORDER BY rv.heure_rendezvous ASC
        ");
        $stmt->execute([':medecin_id' => $medecinId]);
        return $stmt->fetchAll();
    }

    public function getUpcomingByMedecin(int $medecinId): array {
        $stmt = $this->db->prepare("
            SELECT rv.*, 
                   up.nom as patient_nom, up.prenom as patient_prenom,
                   up.email as patient_email, up.telephone as patient_telephone
            FROM rendez_vous rv
            LEFT JOIN users up ON rv.patient_id = up.id
            WHERE rv.medecin_id = :medecin_id 
              AND rv.date_rendezvous > CURDATE()
              AND rv.statut IN ('en_attente', 'confirmé')
            ORDER BY rv.date_rendezvous ASC, rv.heure_rendezvous ASC
        ");
        $stmt->execute([':medecin_id' => $medecinId]);
        return $stmt->fetchAll();
    }

    public function getHistoryByMedecin(int $medecinId): array {
        $stmt = $this->db->prepare("
            SELECT rv.*, 
                   up.nom as patient_nom, up.prenom as patient_prenom,
                   up.email as patient_email, up.telephone as patient_telephone
            FROM rendez_vous rv
            LEFT JOIN users up ON rv.patient_id = up.id
            WHERE rv.medecin_id = :medecin_id 
              AND (rv.date_rendezvous < CURDATE() OR rv.statut = 'terminé')
            ORDER BY rv.date_rendezvous DESC, rv.heure_rendezvous DESC
        ");
        $stmt->execute([':medecin_id' => $medecinId]);
        return $stmt->fetchAll();
    }

    // ═══════════════════════════════════════════════════════════
    //  COMPTEURS
    // ═══════════════════════════════════════════════════════════
    public function countAll(): int {
        return (int)$this->db->query("SELECT COUNT(*) FROM rendez_vous")->fetchColumn();
    }

    public function countByStatus(string $status): int {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM rendez_vous WHERE statut = :statut");
        $stmt->execute([':statut' => $status]);
        return (int)$stmt->fetchColumn();
    }

    public function countByMedecin(int $medecinId): int {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM rendez_vous WHERE medecin_id = :medecin_id");
        $stmt->execute([':medecin_id' => $medecinId]);
        return (int)$stmt->fetchColumn();
    }

    public function countByMedecinAndStatus(int $medecinId, string $status): int {
        $stmt = $this->db->prepare("
            SELECT COUNT(*) FROM rendez_vous 
            WHERE medecin_id = :medecin_id AND statut = :statut
        ");
        $stmt->execute([':medecin_id' => $medecinId, ':statut' => $status]);
        return (int)$stmt->fetchColumn();
    }

    // ═══════════════════════════════════════════════════════════
    //  CRUD - UPDATE
    // ═══════════════════════════════════════════════════════════
    public function update(int $id, array $data): bool {
        $fields = [];
        $params = [':id' => $id];
        
        $allowed = ['date_rendezvous', 'heure_rendezvous', 'motif', 'statut', 'notes_medecin'];
        foreach ($data as $key => $value) {
            if (in_array($key, $allowed)) {
                $fields[] = "$key = :$key";
                $params[":$key"] = $value;
            }
        }
        
        if (empty($fields)) return false;
        
        $stmt = $this->db->prepare("
            UPDATE rendez_vous SET " . implode(', ', $fields) . " WHERE id = :id
        ");
        return $stmt->execute($params);
    }

    public function updateStatus(int $id, string $status): bool {
        $stmt = $this->db->prepare("
            UPDATE rendez_vous SET statut = :statut WHERE id = :id
        ");
        return $stmt->execute([':statut' => $status, ':id' => $id]);
    }

    // ═══════════════════════════════════════════════════════════
    //  CRUD - DELETE
    // ═══════════════════════════════════════════════════════════
    public function delete(int $id): bool {
        $stmt = $this->db->prepare("DELETE FROM rendez_vous WHERE id = :id");
        return $stmt->execute([':id' => $id]);
    }
}
?>