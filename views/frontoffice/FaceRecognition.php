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
        // Créer le dossier s'il n'existe pas
        $uploadDir = __DIR__ . '/../uploads/faces/';
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        
        // Sauvegarder l'image
        $filename = 'face_' . $userId . '_' . time() . '.jpg';
        $filepath = $uploadDir . $filename;
        $relativePath = 'uploads/faces/' . $filename;
        
        // Décoder l'image base64
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
}