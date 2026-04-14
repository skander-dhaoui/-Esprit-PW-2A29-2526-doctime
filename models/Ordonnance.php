<?php
require_once __DIR__ . '/../config/Database.php';

class Ordonnance {
    private PDO $db;

    public function __construct() {
        $this->db = Database::getInstance()->getPDO();
    }

    // ─────────────────────────────────────────
    //  CRUD - CREATE
    // ─────────────────────────────────────────
    public function create(array $data): int {
        $stmt = $this->db->prepare("
            INSERT INTO ordonnances (numero_ordonnance, patient_id, medecin_id, date_ordonnance, diagnostic, contenu, status, created_at)
            VALUES (:numero_ordonnance, :patient_id, :medecin_id, NOW(), :diagnostic, :contenu, 'active', NOW())
        ");
        $stmt->execute([
            ':numero_ordonnance' => $this->generateNumero(),
            ':patient_id' => $data['patient_id'],
            ':medecin_id' => $data['medecin_id'],
            ':diagnostic' => $data['diagnostic'],
            ':contenu' => $data['contenu']
        ]);
        return (int)$this->db->lastInsertId();
    }

    // ─────────────────────────────────────────
    //  CRUD - READ
    // ─────────────────────────────────────────
    public function getAll(): array {
        $stmt = $this->db->query("
            SELECT o.*, 
                   up.nom as patient_nom, up.prenom as patient_prenom,
                   um.nom as medecin_nom, um.prenom as medecin_prenom
            FROM ordonnances o
            LEFT JOIN users up ON o.patient_id = up.id
            LEFT JOIN users um ON o.medecin_id = um.id
            ORDER BY o.date_ordonnance DESC
        ");
        return $stmt->fetchAll();
    }

    public function getById(int $id): array|false {
        $stmt = $this->db->prepare("
            SELECT o.*, 
                   up.nom as patient_nom, up.prenom as patient_prenom,
                   um.nom as medecin_nom, um.prenom as medecin_prenom
            FROM ordonnances o
            LEFT JOIN users up ON o.patient_id = up.id
            LEFT JOIN users um ON o.medecin_id = um.id
            WHERE o.id = :id
        ");
        $stmt->execute([':id' => $id]);
        return $stmt->fetch();
    }

    public function getByPatient(int $patientId): array {
        $stmt = $this->db->prepare("
            SELECT o.*, um.nom as medecin_nom, um.prenom as medecin_prenom
            FROM ordonnances o
            LEFT JOIN users um ON o.medecin_id = um.id
            WHERE o.patient_id = :patient_id
            ORDER BY o.date_ordonnance DESC
        ");
        $stmt->execute([':patient_id' => $patientId]);
        return $stmt->fetchAll();
    }

    public function getByMedecin(int $medecinId): array {
        $stmt = $this->db->prepare("
            SELECT o.*, up.nom as patient_nom, up.prenom as patient_prenom
            FROM ordonnances o
            LEFT JOIN users up ON o.patient_id = up.id
            WHERE o.medecin_id = :medecin_id
            ORDER BY o.date_ordonnance DESC
        ");
        $stmt->execute([':medecin_id' => $medecinId]);
        return $stmt->fetchAll();
    }

    public function getByNumero(string $numero): array|false {
        $stmt = $this->db->prepare("SELECT * FROM ordonnances WHERE numero_ordonnance = :numero");
        $stmt->execute([':numero' => $numero]);
        return $stmt->fetch();
    }

    // ─────────────────────────────────────────
    //  CRUD - UPDATE
    // ─────────────────────────────────────────
    public function update(int $id, array $data): bool {
        $fields = [];
        $params = [':id' => $id];
        
        $allowed = ['diagnostic', 'contenu', 'status'];
        foreach ($data as $key => $value) {
            if (in_array($key, $allowed)) {
                $fields[] = "$key = :$key";
                $params[":$key"] = $value;
            }
        }
        
        if (empty($fields)) return false;
        
        $stmt = $this->db->prepare("
            UPDATE ordonnances SET " . implode(', ', $fields) . " WHERE id = :id
        ");
        return $stmt->execute($params);
    }

    // ─────────────────────────────────────────
    //  CRUD - DELETE
    // ─────────────────────────────────────────
    public function delete(int $id): bool {
        $stmt = $this->db->prepare("DELETE FROM ordonnances WHERE id = :id");
        return $stmt->execute([':id' => $id]);
    }

    // ─────────────────────────────────────────
    //  STATISTIQUES
    // ─────────────────────────────────────────
    public function countAll(): int {
        return (int)$this->db->query("SELECT COUNT(*) FROM ordonnances")->fetchColumn();
    }

    public function countByPatient(int $patientId): int {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM ordonnances WHERE patient_id = :patient_id");
        $stmt->execute([':patient_id' => $patientId]);
        return (int)$stmt->fetchColumn();
    }

    // ─────────────────────────────────────────
    //  HELPERS
    // ─────────────────────────────────────────
    private function generateNumero(): string {
        $prefix = 'ORD';
        $year = date('Y');
        $month = date('m');
        $random = strtoupper(substr(uniqid(), -6));
        return $prefix . $year . $month . '_' . $random;
    }
}
?>