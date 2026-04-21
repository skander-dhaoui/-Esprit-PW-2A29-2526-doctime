<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';

class FaceRecognition
{
    private int $userId;
    private ?string $facePhoto;
    private ?string $faceEncoding;
    private ?string $faceDescriptor;

    public function __construct(array $data = [])
    {
        $this->userId = (int) ($data['user_id'] ?? 0);
        $this->facePhoto = $data['face_photo'] ?? null;
        $this->faceEncoding = $data['face_encoding'] ?? ($data['face_descriptors'] ?? null);
        $this->faceDescriptor = $data['face_descriptor'] ?? ($data['face_descriptors'] ?? null);
    }

    public function getUserId(): int { return $this->userId; }
    public function getFacePhoto(): ?string { return $this->facePhoto; }
    public function getFaceEncoding(): ?string { return $this->faceEncoding; }
    public function getFaceDescriptor(): ?string { return $this->faceDescriptor; }

    public function setUserId(int $value): void { $this->userId = $value; }
    public function setFacePhoto(?string $value): void { $this->facePhoto = $value; }
    public function setFaceEncoding(?string $value): void { $this->faceEncoding = $value; }
    public function setFaceDescriptor(?string $value): void { $this->faceDescriptor = $value; }

    public function saveFacePhoto(int $userId, string $imageData): bool
    {
        if ($userId <= 0) {
            return false;
        }

        if (!preg_match('/^data:image\/(\w+);base64,/', $imageData, $matches)) {
            error_log('FaceRecognition::saveFacePhoto invalid image payload');
            return false;
        }

        $extension = strtolower($matches[1]);
        if (!in_array($extension, ['jpg', 'jpeg', 'png'], true)) {
            $extension = 'jpg';
        }

        $rawData = substr($imageData, strpos($imageData, ',') + 1);
        $binary = base64_decode(str_replace(' ', '+', $rawData), true);
        if ($binary === false) {
            error_log('FaceRecognition::saveFacePhoto base64 decode failed');
            return false;
        }

        $uploadDir = dirname(__DIR__) . '/uploads/faces';
        if (!is_dir($uploadDir) && !mkdir($uploadDir, 0775, true) && !is_dir($uploadDir)) {
            error_log('FaceRecognition::saveFacePhoto cannot create upload dir');
            return false;
        }

        $filename = sprintf('face_%d_%d.%s', $userId, time(), $extension);
        $fullPath = $uploadDir . '/' . $filename;
        $relativePath = 'uploads/faces/' . $filename;

        if (file_put_contents($fullPath, $binary) === false) {
            error_log('FaceRecognition::saveFacePhoto cannot write file');
            return false;
        }

        $db = Database::getInstance()->getConnection();

        $oldStmt = $db->prepare("SELECT face_photo FROM users WHERE id = :id LIMIT 1");
        $oldStmt->execute([':id' => $userId]);
        $oldPhoto = $oldStmt->fetchColumn();
        if (is_string($oldPhoto) && $oldPhoto !== '') {
            $oldFullPath = dirname(__DIR__) . '/' . ltrim(str_replace('\\', '/', $oldPhoto), '/');
            if (is_file($oldFullPath)) {
                @unlink($oldFullPath);
            }
        }

        $stmt = $db->prepare(
            "UPDATE users
             SET face_photo = :face_photo,
                 face_descriptors = NULL
             WHERE id = :id"
        );

        return $stmt->execute([
            ':face_photo' => $relativePath,
            ':id' => $userId,
        ]);
    }

    public function findUserByFace(string $imageData, string $role = 'patient', string $email = ''): ?array
    {
        if ($imageData === '') {
            return null;
        }

        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return null;
        }

        $db = Database::getInstance()->getConnection();
        $allowedRoles = ['patient', 'medecin', 'admin'];
        $role = in_array($role, $allowedRoles, true) ? $role : '';

        if ($role !== '') {
            $stmt = $db->prepare(
                "SELECT id, nom, prenom, email, role, statut, face_photo, face_descriptors AS face_descriptor
                 FROM users
                 WHERE email = :email
                   AND role = :role
                   AND (face_photo IS NOT NULL OR face_descriptors IS NOT NULL)
                 LIMIT 1"
            );
            $stmt->execute([
                ':email' => $email,
                ':role' => $role,
            ]);

            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($user) {
                return $user;
            }
        }

        $fallbackStmt = $db->prepare(
            "SELECT id, nom, prenom, email, role, statut, face_photo, face_descriptors AS face_descriptor
             FROM users
             WHERE email = :email
               AND (face_photo IS NOT NULL OR face_descriptors IS NOT NULL)
             LIMIT 1"
        );
        $fallbackStmt->execute([':email' => $email]);

        $user = $fallbackStmt->fetch(PDO::FETCH_ASSOC);
        return $user ?: null;
    }
}
