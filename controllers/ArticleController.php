<?php
require_once __DIR__ . '/../models/Article.php';
require_once __DIR__ . '/../models/Reply.php';

class ArticleController {
    private Article $article;
    private Reply   $reply;

    public function __construct() {
        $this->article = new Article();
        $this->reply   = new Reply();
    }

    // Affiche la vue backoffice
    public function index(): void {
        require_once __DIR__ . '/../views/backoffice/blog.php';
    }

    // GET ?page=api_article&list=1 → JSON liste + stats
    public function list(): void {
        $this->json([
            'success'  => true,
            'articles' => $this->article->getAll(),
            'total'    => $this->article->countAll(),
            'month'    => $this->article->countThisMonth(),
        ]);
    }

    // GET ?page=api_article&id=X → JSON article + replies
    public function show(int $id): void {
        $a = $this->article->getById($id);
        if (!$a) {
            $this->json(['success' => false, 'message' => 'Article introuvable.'], 404);
            return;
        }
        $this->json([
            'success' => true,
            'article' => $a,
            'replies' => $this->reply->getByArticle($id),
        ]);
    }

    // POST ?page=api_article → créer
    public function store(): void {
        $d       = $this->body();
        $titre   = trim($d['titre']   ?? '');
        $contenu = trim($d['contenu'] ?? '');
        $auteur  = trim($d['auteur']  ?? '') ?: null;

        $errors = [];
        if (!$titre) {
            $errors['titre'] = 'Le titre est obligatoire.';
        } elseif (strlen($titre) > 255) {
            $errors['titre'] = 'Le titre ne doit pas dépasser 255 caractères.';
        }
        
        if (!$contenu) {
            $errors['contenu'] = 'Le contenu est obligatoire.';
        } elseif (strlen($contenu) < 10) {
            $errors['contenu'] = 'Le contenu doit contenir au moins 10 caractères.';
        }

        if (!empty($errors)) { 
            $this->json(['success' => false, 'errors' => $errors], 422); 
            return; 
        }

        $id = $this->article->create($titre, $contenu, $auteur);
        $this->json(['success' => true, 'message' => 'Article créé avec succès.', 'id' => $id], 201);
    }

    // PUT ?page=api_article&id=X → modifier
    public function update(int $id): void {
        if (!$this->article->getById($id)) {
            $this->json(['success' => false, 'message' => 'Article introuvable.'], 404);
            return;
        }
        $d       = $this->body();
        $titre   = trim($d['titre']   ?? '');
        $contenu = trim($d['contenu'] ?? '');
        $auteur  = trim($d['auteur']  ?? '') ?: null;

        $errors = [];
        if (!$titre) {
            $errors['titre'] = 'Le titre est obligatoire.';
        } elseif (strlen($titre) > 255) {
            $errors['titre'] = 'Le titre ne doit pas dépasser 255 caractères.';
        }
        
        if (!$contenu) {
            $errors['contenu'] = 'Le contenu est obligatoire.';
        } elseif (strlen($contenu) < 10) {
            $errors['contenu'] = 'Le contenu doit contenir au moins 10 caractères.';
        }

        if (!empty($errors)) { 
            $this->json(['success' => false, 'errors' => $errors], 422); 
            return; 
        }

        $this->article->update($id, $titre, $contenu, $auteur);
        $this->json(['success' => true, 'message' => 'Article mis à jour avec succès.']);
    }

    // DELETE ?page=api_article&id=X → supprimer
    public function destroy(int $id): void {
        if (!$this->article->getById($id)) {
            $this->json(['success' => false, 'message' => 'Article introuvable.'], 404);
            return;
        }
        $this->article->delete($id);
        $this->json(['success' => true, 'message' => 'Article supprimé avec succès.']);
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