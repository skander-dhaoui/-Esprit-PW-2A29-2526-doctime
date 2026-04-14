<?php
require_once __DIR__ . '/../models/Reply.php';
require_once __DIR__ . '/../models/Article.php';

class ReplyController {
    private Reply   $reply;
    private Article $article;

    public function __construct() {
        $this->reply   = new Reply();
        $this->article = new Article();
    }

    // GET ?page=api_reply&article_id=X
    public function index(int $articleId): void {
        if (!$this->article->getById($articleId)) {
            $this->json(['success' => false, 'message' => 'Article introuvable.'], 404);
            return;
        }
        $this->json(['success' => true, 'replies' => $this->reply->getByArticle($articleId)]);
    }

    // POST ?page=api_reply
    public function store(): void {
        $d         = $this->body();
        $articleId = (int)($d['id_article'] ?? 0);
        $type      = trim($d['type_reply']  ?? 'text');
        $auteur    = trim($d['auteur']       ?? '') ?: null;

        $errors = [];
        
        if (!in_array($type, ['text', 'emoji', 'photo'])) {
            $errors['type_reply'] = 'Type invalide.';
        }
        
        if (!$this->article->getById($articleId)) {
            $errors['id_article'] = 'Article introuvable.';
        }

        [$text, $emoji, $photo] = [null, null, null];

        if (empty($errors)) {
            switch ($type) {
                case 'text':
                    $text = trim($d['contenu_text'] ?? '');
                    if (empty($text)) {
                        $errors['contenu_text'] = 'Le texte est obligatoire.';
                    } elseif (strlen($text) < 2) {
                        $errors['contenu_text'] = 'Le commentaire doit contenir au moins 2 caractères.';
                    } elseif (strlen($text) > 1000) {
                        $errors['contenu_text'] = 'Le commentaire ne doit pas dépasser 1000 caractères.';
                    }
                    break;
                case 'emoji':
                    $emoji = trim($d['emoji'] ?? '');
                    if (empty($emoji)) {
                        $errors['emoji'] = "L'emoji est obligatoire.";
                    } elseif (strlen($emoji) > 20) {
                        $errors['emoji'] = "L'emoji est trop long (max 20 caractères).";
                    }
                    break;
                case 'photo':
                    $photo = trim($d['photo'] ?? '');
                    if (empty($photo)) {
                        $errors['photo'] = "L'URL est obligatoire.";
                    } elseif (!filter_var($photo, FILTER_VALIDATE_URL)) {
                        $errors['photo'] = 'URL invalide.';
                    } elseif (!preg_match('/\.(jpg|jpeg|png|gif|webp|svg|bmp)(\?.*)?$/i', $photo)) {
                        $errors['photo'] = "L'URL doit pointer vers une image (jpg, jpeg, png, gif, webp, svg, bmp).";
                    }
                    break;
            }
        }

        if (!empty($errors)) { 
            $this->json(['success' => false, 'errors' => $errors], 422); 
            return; 
        }

        $id = $this->reply->create($articleId, $text, $emoji, $photo, $auteur, $type);
        $this->json(['success' => true, 'message' => 'Commentaire ajouté avec succès.', 'id' => $id], 201);
    }

    // PUT ?page=api_reply&id=X
    public function update(int $id): void {
        $r = $this->reply->getById($id);
        if (!$r) { 
            $this->json(['success' => false, 'message' => 'Commentaire introuvable.'], 404); 
            return; 
        }

        $d         = $this->body();
        $articleId = (int)($d['id_article'] ?? $r['id_article']);
        $type      = trim($d['type_reply']  ?? $r['type_reply']);
        $auteur    = trim($d['auteur']       ?? '') ?: null;

        $errors = [];
        
        if (!in_array($type, ['text', 'emoji', 'photo'])) {
            $errors['type_reply'] = 'Type invalide.';
        }

        [$text, $emoji, $photo] = [null, null, null];

        if (empty($errors)) {
            switch ($type) {
                case 'text':
                    $text = trim($d['contenu_text'] ?? '');
                    if (empty($text)) {
                        $errors['contenu_text'] = 'Le texte est obligatoire.';
                    } elseif (strlen($text) < 2) {
                        $errors['contenu_text'] = 'Le commentaire doit contenir au moins 2 caractères.';
                    } elseif (strlen($text) > 1000) {
                        $errors['contenu_text'] = 'Le commentaire ne doit pas dépasser 1000 caractères.';
                    }
                    break;
                case 'emoji':
                    $emoji = trim($d['emoji'] ?? '');
                    if (empty($emoji)) {
                        $errors['emoji'] = "L'emoji est obligatoire.";
                    }
                    break;
                case 'photo':
                    $photo = trim($d['photo'] ?? '');
                    if (empty($photo)) {
                        $errors['photo'] = "L'URL est obligatoire.";
                    } elseif (!filter_var($photo, FILTER_VALIDATE_URL)) {
                        $errors['photo'] = 'URL invalide.';
                    }
                    break;
            }
        }

        if (!empty($errors)) { 
            $this->json(['success' => false, 'errors' => $errors], 422); 
            return; 
        }

        $this->reply->update($id, $articleId, $text, $emoji, $photo, $auteur, $type);
        $this->json(['success' => true, 'message' => 'Commentaire mis à jour avec succès.']);
    }

    // DELETE ?page=api_reply&id=X
    public function destroy(int $id): void {
        if (!$this->reply->getById($id)) {
            $this->json(['success' => false, 'message' => 'Commentaire introuvable.'], 404);
            return;
        }
        $this->reply->delete($id);
        $this->json(['success' => true, 'message' => 'Commentaire supprimé avec succès.']);
    }

    // ─── Helpers ───────────────────────────

    private function body(): array {
        $raw = file_get_contents('php://input');
        return json_decode($raw, true) ?? $_POST;
    }

    private function json(array $data, int $code = 200): void {
        // Nettoyer les buffers de sortie avant d'envoyer le JSON
        if (ob_get_level() > 0) {
            ob_clean();
        }
        
        http_response_code($code);
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-cache, must-revalidate');
        
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }
}
?>