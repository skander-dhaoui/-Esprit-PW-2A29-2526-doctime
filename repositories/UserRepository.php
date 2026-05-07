<?php
declare(strict_types=1);

namespace App\Repositories;

use \PDO;
use \Database;

class UserRepository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    public function findById(int $id): ?array
    {
        return $this->getUserById($id);
    }

    public function getUserById(int $id): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE id = :id LIMIT 1");
        $stmt->execute([':id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function findByEmail(string $email): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE email = :email LIMIT 1");
        $stmt->execute([':email' => $email]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function updateProfile(int $userId, string $nom, string $prenom, string $email, string $telephone, string $date_naissance, string $groupe_sanguin, string $adresse): bool
    {
        $sql = "UPDATE users SET nom = :nom, prenom = :prenom, email = :email, telephone = :telephone, date_naissance = :date_n, adresse = :adresse WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $result = $stmt->execute([
            ':nom' => $nom,
            ':prenom' => $prenom,
            ':email' => $email,
            ':telephone' => $telephone,
            ':date_n' => $date_naissance,
            ':adresse' => $adresse,
            ':id' => $userId
        ]);

        if ($result) {
            // Update patient table for groupe_sanguin
            $stmt2 = $this->db->prepare("INSERT INTO patients (user_id, groupe_sanguin) VALUES (:id, :gs) ON DUPLICATE KEY UPDATE groupe_sanguin = :gs");
            $stmt2->execute([':id' => $userId, ':gs' => $groupe_sanguin]);
        }

        return $result;
    }

    public function changePassword(int $userId, string $currentPassword, string $newPassword): bool
    {
        $user = $this->getUserById($userId);
        if (!$user || !password_verify($currentPassword, $user['password'])) {
            return false;
        }

        $hashed = password_hash($newPassword, PASSWORD_DEFAULT);
        $stmt = $this->db->prepare("UPDATE users SET password = :pwd WHERE id = :id");
        return $stmt->execute([':pwd' => $hashed, ':id' => $userId]);
    }

    public function updateAvatar(int $userId, string $avatarPath): bool
    {
        $stmt = $this->db->prepare("UPDATE users SET avatar = :avatar WHERE id = :id");
        return $stmt->execute([':avatar' => $avatarPath, ':id' => $userId]);
    }

    public function updateFaceEncoding(int $userId, string $facePhotoPath): bool
    {
        $stmt = $this->db->prepare("UPDATE users SET face_photo = :photo WHERE id = :id");
        return $stmt->execute([':photo' => $facePhotoPath, ':id' => $userId]);
    }

    public function update(int $id, array $data): bool
    {
        if (empty($data)) return false;
        $fields = [];
        $params = [':id' => $id];
        foreach ($data as $key => $value) {
            $fields[] = "$key = :$key";
            $params[":$key"] = $value;
        }
        $sql = "UPDATE users SET " . implode(', ', $fields) . " WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }
}
