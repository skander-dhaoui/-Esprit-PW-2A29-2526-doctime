<?php
require_once __DIR__ . '/../models/Article.php';
require_once __DIR__ . '/../models/Reply.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/AuthController.php';

use App\Models\Article;
use App\Models\Reply;

class ArticleController {
    private Article $articleModel;
    private Reply $replyModel;
    private AuthController $auth;

    public function __construct() {
        $this->articleModel = new Article();
        $this->replyModel   = new Reply();
        $this->auth         = new AuthController();
    }

    /**
     * Backoffice - Liste des articles
     */
    public function index(): void {
        $this->auth->requireRole('admin');
        $articles = $this->articleModel->getAll();
        $total    = $this->articleModel->countAll();
        $month    = $this->articleModel->countThisMonth();
        require_once __DIR__ . '/../views/backoffice/blog.php';
    }

    /**
     * API - Liste des articles (JSON)
     * GET index.php?page=api_article&list=1
     */
    public function list(): void {
        header('Content-Type: application/json');
        $articles = $this->articleModel->getAll();
        $total    = $this->articleModel->countAll();
        $month    = $this->articleModel->countThisMonth();

        // Normalise les champs pour que le JS n'ait qu'un seul nom de clé
        $articles = array_map([$this, 'normalizeArticle'], $articles);

        echo json_encode([
            'success'  => true,
            'articles' => $articles,
            'total'    => $total,
            'month'    => $month,
        ]);
    }

    /**
     * API - Afficher un article + ses commentaires
     * GET index.php?page=api_article&id=X
     */
    public function show(int $id): void {
        header('Content-Type: application/json');
        $article = $this->articleModel->getById($id);

        if (!$article) {
            echo json_encode(['success' => false, 'message' => 'Article non trouvé']);
            return;
        }

        $replies = $this->replyModel->getByArticle($id);
        echo json_encode([
            'success' => true,
            'article' => $this->normalizeArticle($article),
            'replies' => $replies,
        ]);
    }

    /**
     * API - Créer un article
     * POST index.php?page=api_article
     */
    public function store(): void {
        header('Content-Type: application/json');

        $data = json_decode(file_get_contents('php://input'), true) ?? [];

        $titre   = trim($data['titre']   ?? '');
        $contenu = trim($data['contenu'] ?? '');

        $errors = [];
        if (empty($titre))       $errors['titre']   = 'Le titre est obligatoire.';
        elseif (mb_strlen($titre) > 255) $errors['titre'] = 'Le titre ne doit pas dépasser 255 caractères.';

        if (empty($contenu))     $errors['contenu'] = 'Le contenu est obligatoire.';
        elseif (mb_strlen($contenu) < 10) $errors['contenu'] = 'Le contenu doit contenir au moins 10 caractères.';

        if (!empty($errors)) {
            echo json_encode(['success' => false, 'errors' => $errors]);
            return;
        }

        $auteur_id = $_SESSION['user_id'] ?? null;
        $id = $this->articleModel->create([
            'titre'     => $titre,
            'contenu'   => $contenu,
            'auteur_id' => $auteur_id,
        ]);

        echo json_encode(['success' => true, 'id' => $id, 'message' => 'Article créé avec succès']);
    }

    /**
     * API - Modifier un article
     * POST index.php?page=api_article&id=X  (avec _method=PUT dans le body)
     */
    public function update(int $id): void {
        header('Content-Type: application/json');

        $article = $this->articleModel->getById($id);
        if (!$article) {
            echo json_encode(['success' => false, 'message' => 'Article non trouvé']);
            return;
        }

        $data    = json_decode(file_get_contents('php://input'), true) ?? [];
        $titre   = trim($data['titre']   ?? '');
        $contenu = trim($data['contenu'] ?? '');

        $errors = [];
        if (empty($titre))       $errors['titre']   = 'Le titre est obligatoire.';
        elseif (mb_strlen($titre) > 255) $errors['titre'] = 'Le titre ne doit pas dépasser 255 caractères.';

        if (empty($contenu))     $errors['contenu'] = 'Le contenu est obligatoire.';
        elseif (mb_strlen($contenu) < 10) $errors['contenu'] = 'Le contenu doit contenir au moins 10 caractères.';

        if (!empty($errors)) {
            echo json_encode(['success' => false, 'errors' => $errors]);
            return;
        }

        $auteur_id = (int)($_SESSION['user_id'] ?? $article['auteur_id'] ?? 0) ?: null;
        $this->articleModel->update($id, $titre, $contenu, $auteur_id);

        echo json_encode(['success' => true, 'message' => 'Article modifié avec succès']);
    }

    /**
     * API - Supprimer un article
     * POST index.php?page=api_article&id=X  (avec _method=DELETE dans le body)
     */
    public function destroy(int $id): void {
        header('Content-Type: application/json');

        $article = $this->articleModel->getById($id);
        if (!$article) {
            echo json_encode(['success' => false, 'message' => 'Article non trouvé']);
            return;
        }

        $this->articleModel->delete($id);
        echo json_encode(['success' => true, 'message' => 'Article supprimé avec succès']);
    }

    // ═══════════════════════════════════════════════════════════
    //  JOINTURES - Relation Articles ↔ Replies
    // ═══════════════════════════════════════════════════════════

    /**
     * Affiche les replies d'un article spécifique (JOINTURE INNER JOIN)
     * Pattern : afficherReplies($idArticle)
     * 
     * @param int $idArticle ID de l'article
     * @return array Liste des replies avec données utilisateur
     */
    public function afficherReplies(int $idArticle): array {
        // Valider l'ID d'article
        if ($idArticle <= 0) {
            error_log("ArticleController::afficherReplies - ID article invalide: $idArticle");
            return [];
        }

        // Récupérer les replies via JOINTURE
        $replies = $this->articleModel->getRepliesByArticle($idArticle);
        
        return $replies;
    }

    /**
     * Affiche tous les articles avec le nombre de replies
     * Utilisé pour le formulaire de sélection (LEFT JOIN)
     * 
     * @return array Liste des articles avec comptage
     */
    public function afficherArticles(): array {
        try {
            return $this->articleModel->getArticlesWithReplyCount();
        } catch (Exception $e) {
            error_log('ArticleController::afficherArticles - ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Affiche un article spécifique avec toutes ses replies
     * 
     * @param int $id ID de l'article
     * @return array|null Données article avec replies ou null
     */
    public function afficherArticleComplet(int $id): ?array {
        if ($id <= 0) {
            return null;
        }
        $article = $this->articleModel->getArticleWithReplies($id);
        return !empty($article) ? $article : null;
    }

    // ─────────────────────────────────────────
    //  Helper : normalise les clés de l'article
    //  pour que le JS reçoive toujours les mêmes noms
    // ─────────────────────────────────────────
    private function normalizeArticle(array $a): array {
        return [
            // Identifiant unique — la DB renvoie "id"
            'id'           => $a['id']         ?? $a['id_article'] ?? 0,
            'id_article'   => $a['id']         ?? $a['id_article'] ?? 0,   // compat legacy

            'titre'        => $a['titre']       ?? '',
            'contenu'      => $a['contenu']     ?? '',
            'auteur'       => $a['auteur_name'] ?? $a['auteur'] ?? 'Valorys',
            'auteur_id'    => $a['auteur_id']   ?? null,
            'categorie'    => $a['categorie']   ?? null,
            'image'        => $a['image']       ?? null,
            'vues'         => (int)($a['vues']  ?? 0),
            'nb_replies'   => (int)($a['nb_replies'] ?? 0),

            // Dates : on normalise sur "created_at" ET "date_creation" (compat)
            'created_at'    => $a['created_at']    ?? $a['date_creation'] ?? null,
            'date_creation' => $a['created_at']    ?? $a['date_creation'] ?? null,
        ];
    }
}
?>