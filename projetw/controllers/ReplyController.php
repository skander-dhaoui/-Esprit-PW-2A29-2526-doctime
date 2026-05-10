<?php

require_once __DIR__ . '/../models/Reply.php';
require_once __DIR__ . '/../models/Article.php';
require_once __DIR__ . '/AuthController.php';

class ReplyController {

    private Reply $replyModel;
    private Article $articleModel;
    private AuthController $auth;

    public function __construct() {
        $this->replyModel = new Reply();
        $this->articleModel = new Article();
        $this->auth = new AuthController();
    }

    // ─────────────────────────────────────────
    //  API - Commentaires d'un article
    // ─────────────────────────────────────────
    public function index(int $articleId): void {
        header('Content-Type: application/json');
        
        $article = $this->articleModel->getById($articleId);
        
        if (!$article) {
            echo json_encode(['success' => false, 'message' => 'Article non trouvé']);
            return;
        }
        
        $replies = $this->replyModel->getByArticle($articleId);
        
        echo json_encode(['success' => true, 'replies' => $replies]);
    }

    // ─────────────────────────────────────────
    //  API - Tous les commentaires
    // ─────────────────────────────────────────
    public function all(): void {
        $this->auth->requireAuth();
        header('Content-Type: application/json');
        
        $replies = $this->replyModel->getAll();
        $total = $this->replyModel->countAll();
        
        echo json_encode(['success' => true, 'replies' => $replies, 'total' => $total]);
    }

    // ─────────────────────────────────────────
    //  API - Afficher un commentaire
    // ─────────────────────────────────────────
/**
 * API - Afficher un commentaire spécifique (pour modification)
 */
public function show(int $id): void {
    header('Content-Type: application/json');
    
    $reply = $this->replyModel->getById($id);
    
    if (!$reply) {
        echo json_encode(['success' => false, 'message' => 'Commentaire non trouvé']);
        return;
    }
    
    echo json_encode(['success' => true, 'reply' => $reply]);
}

    // ─────────────────────────────────────────
    //  API - Créer un commentaire (avec upload photo)
    // ─────────────────────────────────────────
    public function store(): void {
        $this->auth->requireAuth();
        header('Content-Type: application/json');
        
        // Vérifier si c'est un upload de fichier (multipart/form-data)
        $isFileUpload = !empty($_FILES) && isset($_FILES['photo_file']);
        
        if ($isFileUpload) {
            // Traitement upload fichier
            $articleId = (int)($_POST['id_article'] ?? 0);
            $type = $_POST['type_reply'] ?? 'photo';
            $auteur = $_POST['auteur'] ?? $_SESSION['user_name'] ?? null;
            
            $article = $this->articleModel->getById($articleId);
            
            if (!$article) {
                echo json_encode(['success' => false, 'message' => 'Article non trouvé']);
                return;
            }
            
            // Upload de l'image
            $photo = $this->uploadPhoto($_FILES['photo_file']);
            
            if (!$photo) {
                echo json_encode(['success' => false, 'message' => 'Erreur lors de l\'upload de la photo']);
                return;
            }
            
            $id = $this->replyModel->create($articleId, null, null, $photo, $auteur, $type);
            
            echo json_encode(['success' => true, 'id' => $id, 'message' => 'Commentaire ajouté avec succès']);
        } else {
            // Traitement JSON classique
            $data = json_decode(file_get_contents('php://input'), true);
            
            $articleId = (int)($data['id_article'] ?? 0);
            $type = $data['type_reply'] ?? 'text';
            $contenuText = $data['contenu_text'] ?? null;
            $emoji = $data['emoji'] ?? null;
            $photo = $data['photo'] ?? null;
            $auteur = $data['auteur'] ?? $_SESSION['user_name'] ?? null;
            
            $article = $this->articleModel->getById($articleId);
            
            if (!$article) {
                echo json_encode(['success' => false, 'message' => 'Article non trouvé']);
                return;
            }
            
            $errors = [];
            
            if (!in_array($type, ['text', 'emoji', 'photo'])) {
                $errors['type_reply'] = 'Type de commentaire invalide.';
            }
            
            if ($type === 'text' && empty($contenuText)) {
                $errors['contenu_text'] = 'Le texte est obligatoire.';
            }
            
            if ($type === 'emoji' && empty($emoji)) {
                $errors['emoji'] = "L'emoji est obligatoire.";
            }
            
            if ($type === 'photo' && empty($photo)) {
                $errors['photo'] = "L'URL de la photo est obligatoire.";
            }
            
            if (!empty($errors)) {
                echo json_encode(['success' => false, 'errors' => $errors]);
                return;
            }
            
            $id = $this->replyModel->create($articleId, $contenuText, $emoji, $photo, $auteur, $type);
            
            echo json_encode(['success' => true, 'id' => $id, 'message' => 'Commentaire ajouté avec succès']);
        }
    }

    // ─────────────────────────────────────────
    //  API - Modifier un commentaire
    // ─────────────────────────────────────────
    public function update(int $id): void {
        $this->auth->requireAuth();
        header('Content-Type: application/json');
        
        $reply = $this->replyModel->getById($id);
        
        if (!$reply) {
            echo json_encode(['success' => false, 'message' => 'Commentaire non trouvé']);
            return;
        }
        
        $data = json_decode(file_get_contents('php://input'), true);
        
        $type = $data['type_reply'] ?? $reply['type_reply'];
        $contenuText = $data['contenu_text'] ?? null;
        $emoji = $data['emoji'] ?? null;
        $photo = $data['photo'] ?? null;
        $auteur = $data['auteur'] ?? $reply['auteur'];
        
        $errors = [];
        
        if (!in_array($type, ['text', 'emoji', 'photo'])) {
            $errors['type_reply'] = 'Type de commentaire invalide.';
        }
        
        if ($type === 'text' && empty($contenuText)) {
            $errors['contenu_text'] = 'Le texte est obligatoire.';
        }
        
        if ($type === 'emoji' && empty($emoji)) {
            $errors['emoji'] = "L'emoji est obligatoire.";
        }
        
        if ($type === 'photo' && empty($photo)) {
            $errors['photo'] = "L'URL de la photo est obligatoire.";
        }
        
        if (!empty($errors)) {
            echo json_encode(['success' => false, 'errors' => $errors]);
            return;
        }
        
        $this->replyModel->update($id, $reply['id_article'], $contenuText, $emoji, $photo, $auteur, $type);
        
        echo json_encode(['success' => true, 'message' => 'Commentaire modifié avec succès']);
    }

    // ─────────────────────────────────────────
    //  API - Supprimer un commentaire
    // ─────────────────────────────────────────
    public function destroy(int $id): void {
        $this->auth->requireAuth();
        header('Content-Type: application/json');
        
        $reply = $this->replyModel->getById($id);
        
        if (!$reply) {
            echo json_encode(['success' => false, 'message' => 'Commentaire non trouvé']);
            return;
        }
        
        $this->replyModel->delete($id);
        
        echo json_encode(['success' => true, 'message' => 'Commentaire supprimé avec succès']);
    }
    
    // ─────────────────────────────────────────
    //  Upload de photo
    // ─────────────────────────────────────────
    private function uploadPhoto($file): ?string {
        // Vérifier les erreurs
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return null;
        }
        
        // Types MIME autorisés
        $allowedTypes = ['image/jpeg', 'image/png', 'image/jpg', 'image/gif', 'image/webp'];
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        
        if (!in_array($mimeType, $allowedTypes)) {
            return null;
        }
        
        // Taille maximale : 2 Mo
        if ($file['size'] > 2 * 1024 * 1024) {
            return null;
        }
        
        // Créer le dossier s'il n'existe pas
        $uploadDir = __DIR__ . '/../uploads/comments/';
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        
        // Générer un nom unique
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = 'comment_' . time() . '_' . uniqid() . '.' . $extension;
        $filepath = $uploadDir . $filename;
        $relativePath = 'uploads/comments/' . $filename;
        
        // Déplacer le fichier
        if (move_uploaded_file($file['tmp_name'], $filepath)) {
            return $relativePath;
        }
        
        return null;
    }
}
?>