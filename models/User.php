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
            "SELECT id, nom, prenom, email, telephone, role, statut, avatar, created_at
             FROM users
             ORDER BY created_at DESC"
        );
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getRecent(int $limit = 5): array {
        $stmt = $this->db->prepare(
            "SELECT id, nom, prenom, email, role, statut, avatar, created_at
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
            "SELECT specialite, numero_ordre, cabinet_adresse, annee_experience,
                    consultation_prix, description, statut_validation
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

    // ═══════════════════════════════════════════════════════════
    //  ✅ GESTION DE L'AVATAR (PHOTO DE PROFIL)
    // ═══════════════════════════════════════════════════════════

    /**
     * Récupère le chemin de l'avatar d'un utilisateur
     */
    public function getAvatar(int $userId): ?string {
        $stmt = $this->db->prepare("SELECT avatar FROM users WHERE id = :id");
        $stmt->execute([':id' => $userId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['avatar'] ?? null;
    }

    /**
     * Met à jour l'avatar en base de données
     */
    public function updateAvatarInDb(int $userId, string $avatarPath): bool {
        $stmt = $this->db->prepare("UPDATE users SET avatar = :avatar WHERE id = :id");
        return $stmt->execute([':avatar' => $avatarPath, ':id' => $userId]);
    }

    /**
     * Upload de la photo de profil
     * @param array $file Le fichier $_FILES['avatar']
     * @param int $userId ID de l'utilisateur
     * @return string|false Le chemin de l'image ou false en cas d'erreur
     */
    public function uploadAvatar(array $file, int $userId): string|false {
        // Vérifier les erreurs
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return false;
        }

        // Types MIME autorisés
        $allowedTypes = ['image/jpeg', 'image/png', 'image/jpg', 'image/gif', 'image/webp'];
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        if (!in_array($mimeType, $allowedTypes)) {
            return false;
        }

        // Taille maximale : 2 Mo
        if ($file['size'] > 2 * 1024 * 1024) {
            return false;
        }

        // Créer le dossier s'il n'existe pas
        $uploadDir = __DIR__ . '/../uploads/avatars/';
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        // Générer un nom unique
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = 'user_' . $userId . '_' . time() . '.' . $extension;
        $filepath = $uploadDir . $filename;
        $relativePath = 'uploads/avatars/' . $filename;

        // Déplacer le fichier
        if (move_uploaded_file($file['tmp_name'], $filepath)) {
            // Supprimer l'ancien avatar s'il existe
            $oldAvatar = $this->getAvatar($userId);
            if ($oldAvatar && file_exists(__DIR__ . '/../' . $oldAvatar)) {
                unlink(__DIR__ . '/../' . $oldAvatar);
            }
            
            // Mettre à jour la base de données
            $this->updateAvatarInDb($userId, $relativePath);
            
            return $relativePath;
        }

        return false;
    }

    /**
     * Supprime l'avatar d'un utilisateur
     */
    public function deleteAvatar(int $userId): bool {
        $oldAvatar = $this->getAvatar($userId);
        if ($oldAvatar && file_exists(__DIR__ . '/../' . $oldAvatar)) {
            unlink(__DIR__ . '/../' . $oldAvatar);
        }
        
        $stmt = $this->db->prepare("UPDATE users SET avatar = NULL WHERE id = :id");
        return $stmt->execute([':id' => $userId]);
    }

    // ─────────────────────────────────────────
    //  Écriture
    // ─────────────────────────────────────────

    public function create(array $data): int {
        $sql = "INSERT INTO users
                    (nom, prenom, email, telephone, password, role, statut, adresse, date_naissance, avatar, created_at)
                VALUES
                    (:nom, :prenom, :email, :telephone, :password, :role, :statut, :adresse, :date_naissance, :avatar, NOW())";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':nom'            => $data['nom'],
            ':prenom'         => $data['prenom'],
            ':email'          => $data['email'],
            ':telephone'      => $data['telephone']      ?? '',
            ':password'       => $data['password'],
            ':role'           => $data['role']           ?? 'patient',
            ':statut'         => $data['statut']         ?? 'actif',
            ':adresse'        => $data['adresse']        ?? null,
            ':date_naissance' => $data['date_naissance'] ?? null,
            ':avatar'         => $data['avatar']         ?? null,
        ]);

        return (int) $this->db->lastInsertId();
    }

    public function update(int $id, array $data): bool {
        if (empty($data)) return false;

        $allowed = [
            'nom', 'prenom', 'email', 'telephone', 'adresse',
            'date_naissance', 'role', 'statut', 'password', 
            'derniere_connexion', 'avatar'
        ];

        $fields = [];
        $params = [':id' => $id];

        foreach ($data as $key => $value) {
            if (in_array($key, $allowed, true)) {
                $fields[]        = "$key = :$key";
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
        // Supprimer l'avatar avant de supprimer l'utilisateur
        $this->deleteAvatar($id);
        
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
             ON DUPLICATE KEY UPDATE
                groupe_sanguin = VALUES(groupe_sanguin)"
        );
        $stmt->execute([
            ':user_id'        => $userId,
            ':groupe_sanguin' => $data['groupe_sanguin'] ?? null,
        ]);
    }

    public function createMedecin(array $data): int {
        $stmt = $this->db->prepare(
            "INSERT INTO medecins
                (user_id, specialite, numero_ordre, cabinet_adresse,
                 annee_experience, consultation_prix, statut_validation)
             VALUES
                (:user_id, :specialite, :numero_ordre, :cabinet_adresse,
                 :annee_experience, :consultation_prix, 'en_attente')"
        );
        $stmt->execute([
            ':user_id'          => $data['user_id'],
            ':specialite'       => $data['specialite']        ?? '',
            ':numero_ordre'     => $data['numero_ordre']      ?? '',
            ':cabinet_adresse'  => $data['adresse_cabinet']   ?? '',
            ':annee_experience' => $data['experience']        ?? 0,
            ':consultation_prix'=> $data['tarif']             ?? 0,
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function upsertMedecin(int $userId, array $data): void {
        $stmt = $this->db->prepare(
            "INSERT INTO medecins
                (user_id, specialite, numero_ordre, cabinet_adresse,
                 annee_experience, consultation_prix)
             VALUES
                (:user_id, :specialite, :numero_ordre, :cabinet_adresse,
                 :annee_experience, :consultation_prix)
             ON DUPLICATE KEY UPDATE
                specialite        = VALUES(specialite),
                numero_ordre      = VALUES(numero_ordre),
                cabinet_adresse   = VALUES(cabinet_adresse),
                annee_experience  = VALUES(annee_experience),
                consultation_prix = VALUES(consultation_prix)"
        );
        $stmt->execute([
            ':user_id'           => $userId,
            ':specialite'        => $data['specialite']      ?? '',
            ':numero_ordre'      => $data['numero_ordre']    ?? '',
            ':cabinet_adresse'   => $data['adresse_cabinet'] ?? '',
            ':annee_experience'  => $data['experience']      ?? 0,
            ':consultation_prix' => $data['tarif']           ?? 0,
        ]);
    }
}