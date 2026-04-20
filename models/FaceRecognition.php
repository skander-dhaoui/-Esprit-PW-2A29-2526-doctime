<?php
// models/FaceRecognition.php
require_once __DIR__ . '/../config/database.php';

class FaceRecognition {
    private PDO $db;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    /**
     * Sauvegarder la photo du visage pour un utilisateur
     */
    public function saveFacePhoto(int $userId, string $imageData): bool {
        $uploadDir = __DIR__ . '/../uploads/faces/';
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        
        $filename = 'face_' . $userId . '_' . time() . '.jpg';
        $filepath = $uploadDir . $filename;
        $relativePath = 'uploads/faces/' . $filename;
        
        $imageData = str_replace('data:image/jpeg;base64,', '', $imageData);
        $imageData = str_replace(' ', '+', $imageData);
        $imageBinary = base64_decode($imageData);
        
        if (file_put_contents($filepath, $imageBinary)) {
            $stmt = $this->db->prepare("UPDATE users SET face_photo = :photo WHERE id = :id");
            return $stmt->execute([':photo' => $relativePath, ':id' => $userId]);
        }
        return false;
    }
    
    /**
     * Récupérer la photo du visage d'un utilisateur
     */
    public function getFacePhoto(int $userId): ?string {
        $stmt = $this->db->prepare("SELECT face_photo FROM users WHERE id = :id");
        $stmt->execute([':id' => $userId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['face_photo'] ?? null;
    }
    
    /**
     * Vérifier si un utilisateur a une photo de visage enregistrée
     */
    public function hasFaceRegistered(int $userId): bool {
        $stmt = $this->db->prepare("SELECT face_photo FROM users WHERE id = :id AND face_photo IS NOT NULL");
        $stmt->execute([':id' => $userId]);
        return $stmt->fetch() !== false;
    }
    
    /**
     * Vérifier si l'utilisateur existe et récupérer ses infos
     */
    public function findUserByFace($imageData, string $role = 'patient', string $email = ''): array|false {
        // Sauvegarder l'image temporairement
        $tempFile = __DIR__ . '/../uploads/temp_face_' . time() . '.jpg';
        $imageData = str_replace('data:image/jpeg;base64,', '', $imageData);
        $imageData = str_replace(' ', '+', $imageData);
        file_put_contents($tempFile, base64_decode($imageData));
        
        // Récupérer les utilisateurs
        if (empty($email)) {
            return false;
        }
        
        $stmt = $this->db->prepare("SELECT id, nom, prenom, email, role, statut, face_photo FROM users WHERE face_photo IS NOT NULL AND statut = 'actif' AND email = :email AND role = :role");
        $stmt->execute([':email' => $email, ':role' => $role]);
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Pour l'instant, version simplifiée : 
        // En production, utilisez une API de reconnaissance faciale (Face++, etc.)
        // Ici on simule la reconnaissance en prenant le premier utilisateur trouvé de CE ROLE
        if (!empty($users)) {
            return $users[0];
        }
        
        return false;
    }
}
?>