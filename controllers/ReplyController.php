<?php

require_once __DIR__ . '/GamificationController.php';
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
    /**
     * @param array<string,mixed>|null $parsedJson JSON déjà décodé par index.php (api_reply).
     */
    public function store(?array $parsedJson = null): void {
        $this->auth->requireAuth();
        header('Content-Type: application/json');
        
        // Vérifier si c'est un upload de fichier (multipart/form-data)
        $isFileUpload = !empty($_FILES) && isset($_FILES['photo_file']);
        
        if ($isFileUpload) {
            // Traitement upload fichier
            $articleId = (int)($_POST['id_article'] ?? 0);
            $type = $_POST['type_reply'] ?? 'photo';
            $auteur = $_POST['auteur'] ?? $_SESSION['user_name'] ?? null;
            $parentReplyId = (int)($_POST['parent_reply_id'] ?? 0);
            
            $article = $this->articleModel->getById($articleId);
            
            if (!$article) {
                echo json_encode(['success' => false, 'message' => 'Article non trouvé']);
                return;
            }
            if ($parentReplyId > 0) {
                $parentRow = $this->replyModel->getById($parentReplyId);
                if (!$parentRow || (int)$parentRow['id_article'] !== $articleId) {
                    echo json_encode(['success' => false, 'message' => 'Commentaire parent invalide.']);
                    return;
                }
            } else {
                $parentReplyId = 0;
            }
            
            // Upload de l'image
            $photo = $this->uploadPhoto($_FILES['photo_file']);
            
            if (!$photo) {
                echo json_encode(['success' => false, 'message' => 'Erreur lors de l\'upload de la photo']);
                return;
            }
            
            $id = $this->replyModel->create($articleId, null, null, $photo, $auteur, $type, null, $parentReplyId > 0 ? $parentReplyId : null);
            
            $uid = (int) ($_SESSION['user_id'] ?? 0);
            $gamification = null;
            if ($id > 0 && $uid > 0 && class_exists('GamificationController')) {
                $gRes = GamificationController::grantPoints($uid, 'comment_created', $id);
                $gamification = GamificationController::formatGrantForClient($gRes);
            }
            if ($id > 0) {
                $this->notifyAdminPatientComment($articleId);
            }
            echo json_encode([
                'success'        => true,
                'id'             => $id,
                'message'        => 'Commentaire ajouté avec succès !',
                'gamification'   => $gamification,
            ], JSON_UNESCAPED_UNICODE);
        } else {
            // Traitement JSON classique
            $data = is_array($parsedJson) ? $parsedJson : (json_decode(file_get_contents('php://input'), true) ?? []);
            
            $articleId = (int)($data['id_article'] ?? 0);
            $type = $data['type_reply'] ?? 'text';
            $contenuText = $data['contenu_text'] ?? null;
            $emoji = $data['emoji'] ?? null;
            $photo = $data['photo'] ?? null;
            $auteur = $data['auteur'] ?? $_SESSION['user_name'] ?? null;
            $parentReplyId = (int)($data['parent_reply_id'] ?? 0);
            
            $article = $this->articleModel->getById($articleId);
            
            if (!$article) {
                echo json_encode(['success' => false, 'message' => 'Article non trouvé']);
                return;
            }
            if ($parentReplyId > 0) {
                $parentRow = $this->replyModel->getById($parentReplyId);
                if (!$parentRow || (int)$parentRow['id_article'] !== $articleId) {
                    echo json_encode(['success' => false, 'message' => 'Commentaire parent invalide.']);
                    return;
                }
            } else {
                $parentReplyId = 0;
            }
            
            $errors = [];
            
            if (!in_array($type, ['text', 'emoji', 'photo', 'mixte'], true)) {
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
            
            $id = $this->replyModel->create($articleId, $contenuText, $emoji, $photo, $auteur, $type, null, $parentReplyId > 0 ? $parentReplyId : null);
            
            $uid = (int) ($_SESSION['user_id'] ?? 0);
            $gamification = null;
            if ($id > 0 && $uid > 0 && class_exists('GamificationController')) {
                $gRes = GamificationController::grantPoints($uid, 'comment_created', $id);
                $gamification = GamificationController::formatGrantForClient($gRes);
            }
            if ($id > 0) {
                $this->notifyAdminPatientComment($articleId);
            }
            echo json_encode([
                'success'        => true,
                'id'             => $id,
                'message'        => 'Commentaire ajouté avec succès !',
                'gamification'   => $gamification,
            ], JSON_UNESCAPED_UNICODE);
        }
    }

    // ─────────────────────────────────────────
    //  API - Modifier un commentaire
    // ─────────────────────────────────────────
    public function update(int $id, ?array $parsedJson = null): void {
        $this->auth->requireAuth();
        header('Content-Type: application/json');
        
        $reply = $this->replyModel->getById($id);
        
        if (!$reply) {
            echo json_encode(['success' => false, 'message' => 'Commentaire non trouvé']);
            return;
        }
        
        $data = is_array($parsedJson) ? $parsedJson : (json_decode(file_get_contents('php://input'), true) ?? []);
        
        $type = $data['type_reply'] ?? $reply['type_reply'];
        $contenuText = $data['contenu_text'] ?? null;
        $emoji = $data['emoji'] ?? null;
        $photo = $data['photo'] ?? null;
        $auteur = $data['auteur'] ?? $reply['auteur'];
        
        $errors = [];
        
        if (!in_array($type, ['text', 'emoji', 'photo', 'mixte'], true)) {
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
    
    /** Notif admin lorsqu'un patient poste un commentaire (API / formulaire blog). */
    private function notifyAdminPatientComment(int $articleId): void {
        if (($_SESSION['user_role'] ?? '') !== 'patient') {
            return;
        }
        if (!is_file(__DIR__ . '/../models/AdminNotification.php')) {
            return;
        }
        require_once __DIR__ . '/../models/AdminNotification.php';
        $art = $this->articleModel->getById($articleId);
        $title = is_array($art) ? (string) ($art['titre'] ?? 'Article') : 'Article';
        $nm = trim((string) ($_SESSION['user_name'] ?? '')) ?: 'Patient';
        AdminNotification::notifyPatientComment($articleId, $title, $nm);
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
