<?php

require_once __DIR__ . '/../config/database.php';

class User {

    private PDO $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    // ─────────────────────────────────────────
    //  Lecture
    // ─────────────────────────────────────────

    public function getAll(): array {
        $stmt = $this->db->query(
            "SELECT id, nom, prenom, email, telephone, role, statut, created_at
             FROM users
             ORDER BY created_at DESC"
        );
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getRecent(int $limit = 5): array {
        $stmt = $this->db->prepare(
            "SELECT id, nom, prenom, email, role, statut, created_at
             FROM users
             ORDER BY created_at DESC
             LIMIT :limit"
        );
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findById(int $id): array|false {
        $stmt = $this->db->prepare(
            "SELECT * FROM users WHERE id = :id LIMIT 1"
        );
        $stmt->execute([':id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function findByEmail(string $email): array|false {
        $stmt = $this->db->prepare(
            "SELECT * FROM users WHERE email = :email LIMIT 1"
        );
        $stmt->execute([':email' => $email]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getExtras(int $userId, string $role): array {
        return match ($role) {
            'patient' => $this->getPatientExtras($userId),
            'medecin' => $this->getMedecinExtras($userId),
            default   => [],
        };
    }

    private function getPatientExtras(int $userId): array {
        $stmt = $this->db->prepare(
            "SELECT groupe_sanguin FROM patients WHERE user_id = :uid LIMIT 1"
        );
        $stmt->execute([':uid' => $userId]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
    }

    private function getMedecinExtras(int $userId): array {
        $stmt = $this->db->prepare(
            "SELECT specialite, numero_ordre, cabinet_adresse, description, statut_validation
             FROM medecins WHERE user_id = :uid LIMIT 1"
        );
        $stmt->execute([':uid' => $userId]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
    }

    // ─────────────────────────────────────────
    //  Compteurs
    // ─────────────────────────────────────────

    public function count(): int {
        return (int) $this->db->query("SELECT COUNT(*) FROM users")->fetchColumn();
    }

    public function countByRole(string $role): int {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM users WHERE role = :role");
        $stmt->execute([':role' => $role]);
        return (int) $stmt->fetchColumn();
    }

    public function countByStatus(string $status): int {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM users WHERE statut = :statut");
        $stmt->execute([':statut' => $status]);
        return (int) $stmt->fetchColumn();
    }

    // ─────────────────────────────────────────
    //  Statistiques
    // ─────────────────────────────────────────

    public function getMonthlyRegistrations(): array {
        $stmt = $this->db->query(
            "SELECT DATE_FORMAT(created_at, '%Y-%m') AS mois,
                    COUNT(*) AS total
             FROM users
             WHERE created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
             GROUP BY mois
             ORDER BY mois ASC"
        );
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getRepartitionByRole(): array {
        $stmt = $this->db->query(
            "SELECT role, COUNT(*) AS total
             FROM users
             GROUP BY role"
        );
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // ─────────────────────────────────────────
    //  Écriture
    // ─────────────────────────────────────────

    public function create(array $data): int {
        $sql = "INSERT INTO users
                    (nom, prenom, email, telephone, password, role, statut, adresse, date_naissance, created_at)
                VALUES
                    (:nom, :prenom, :email, :telephone, :password, :role, :statut, :adresse, :date_naissance, NOW())";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':nom'            => $data['nom'],
            ':prenom'         => $data['prenom'],
            ':email'          => $data['email'],
            ':telephone'      => $data['telephone'] ?? '',
            ':password'       => $data['password'],
            ':role'           => $data['role']   ?? 'patient',
            ':statut'         => $data['statut'] ?? 'actif',
            ':adresse'        => $data['adresse']        ?? null,
            ':date_naissance' => $data['date_naissance'] ?? null,
        ]);

        return (int) $this->db->lastInsertId();
    }

    public function update(int $id, array $data): bool {
        if (empty($data)) return false;

        $allowed = [
            'nom', 'prenom', 'email', 'telephone', 'adresse',
            'date_naissance', 'role', 'statut', 'password', 'derniere_connexion',
        ];

        $fields = [];
        $params = [':id' => $id];

        foreach ($data as $key => $value) {
            if (in_array($key, $allowed, true)) {
                $fields[]    = "$key = :$key";
                $params[":$key"] = $value;
            }
        }

        if (empty($fields)) return false;

        $stmt = $this->db->prepare(
            "UPDATE users SET " . implode(', ', $fields) . " WHERE id = :id"
        );
        return $stmt->execute($params);
    }

    public function delete(int $id): bool {
        $stmt = $this->db->prepare("DELETE FROM users WHERE id = :id");
        return $stmt->execute([':id' => $id]);
    }

    // ─────────────────────────────────────────
    //  Données liées patient / médecin
    // ─────────────────────────────────────────

    public function createPatient(array $data): int {
        $stmt = $this->db->prepare(
            "INSERT INTO patients (user_id, groupe_sanguin)
             VALUES (:user_id, :groupe_sanguin)"
        );
        $stmt->execute([
            ':user_id'        => $data['user_id'],
            ':groupe_sanguin' => $data['groupe_sanguin'] ?? null,
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function upsertPatient(int $userId, array $data): void {
        $stmt = $this->db->prepare(
            "INSERT INTO patients (user_id, groupe_sanguin)
             VALUES (:user_id, :groupe_sanguin)
             ON DUPLICATE KEY UPDATE groupe_sanguin = VALUES(groupe_sanguin)"
        );
        $stmt->execute([
            ':user_id'        => $userId,
            ':groupe_sanguin' => $data['groupe_sanguin'] ?? null,
        ]);
    }

    public function createMedecin(array $data): int {
        $stmt = $this->db->prepare(
            "INSERT INTO medecins
                (user_id, specialite, numero_ordre, cabinet_adresse, statut_validation)
             VALUES
                (:user_id, :specialite, :numero_ordre, :cabinet_adresse, 'en_attente')"
        );
        $stmt->execute([
            ':user_id'          => $data['user_id'],
            ':specialite'       => $data['specialite']      ?? '',
            ':numero_ordre'     => $data['numero_ordre']    ?? '',
            ':cabinet_adresse'  => $data['adresse_cabinet'] ?? '',
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function upsertMedecin(int $userId, array $data): void {
        $stmt = $this->db->prepare(
            "INSERT INTO medecins
                (user_id, specialite, numero_ordre, cabinet_adresse)
             VALUES
                (:user_id, :specialite, :numero_ordre, :cabinet_adresse)
             ON DUPLICATE KEY UPDATE
                specialite      = VALUES(specialite),
                numero_ordre    = VALUES(numero_ordre),
                cabinet_adresse = VALUES(cabinet_adresse)"
        );
        $stmt->execute([
            ':user_id'          => $userId,
            ':specialite'       => $data['specialite']      ?? '',
            ':numero_ordre'     => $data['numero_ordre']    ?? '',
            ':cabinet_adresse'  => $data['adresse_cabinet'] ?? '',
        ]);
    }
}