<?php
require_once __DIR__ . '/../models/Article.php';
require_once __DIR__ . '/../models/Reply.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/AuthController.php';

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
        header('Content-Type: application/json; charset=utf-8');
        $viewerId = !empty($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;
        $articles = $this->articleModel->getAll($viewerId);
        $total    = $this->articleModel->countAll();
        $month    = $this->articleModel->countThisMonth();

        $articles = array_map([$this, 'normalizeArticle'], $articles);

        echo json_encode([
            'success'  => true,
            'articles' => $articles,
            'total'    => $total,
            'month'    => $month,
        ], JSON_UNESCAPED_UNICODE);
    }

    /**
     * API - Afficher un article + ses commentaires
     * GET index.php?page=api_article&id=X
     */
    public function show(int $id): void {
        header('Content-Type: application/json; charset=utf-8');
        $viewerId = !empty($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;
        $article = $this->articleModel->getById($id, $viewerId);

        if (!$article) {
            echo json_encode(['success' => false, 'message' => 'Article non trouvé'], JSON_UNESCAPED_UNICODE);
            return;
        }

        $replies = $this->replyModel->getByArticle($id);
        echo json_encode([
            'success' => true,
            'article' => $this->normalizeArticle($article),
            'replies' => $replies,
        ], JSON_UNESCAPED_UNICODE);
    }

    /**
     * API - Créer un article
     * POST index.php?page=api_article
     *
     * @param array<string,mixed>|null $input Données déjà décodées par index.php (évite 2e lecture de php://input).
     */
    public function store(?array $input = null): void {
        header('Content-Type: application/json; charset=utf-8');

        $data = is_array($input) ? $input : (json_decode(file_get_contents('php://input'), true) ?? []);

        $titre   = trim($data['titre']   ?? '');
        $contenu = trim($data['contenu'] ?? '');

        $errors = [];
        if (empty($titre))       $errors['titre']   = 'Le titre est obligatoire.';
        elseif (mb_strlen($titre) > 255) $errors['titre'] = 'Le titre ne doit pas dépasser 255 caractères.';

        if (empty($contenu))     $errors['contenu'] = 'Le contenu est obligatoire.';
        elseif (mb_strlen($contenu) < 10) $errors['contenu'] = 'Le contenu doit contenir au moins 10 caractères.';

        if (!empty($errors)) {
            echo json_encode(['success' => false, 'errors' => $errors], JSON_UNESCAPED_UNICODE);
            return;
        }

        $auteur_id = $_SESSION['user_id'] ?? null;
        $id = $this->articleModel->create([
            'titre'     => $titre,
            'contenu'   => $contenu,
            'auteur_id' => $auteur_id,
        ]);

        $gamification = null;
        if ($id > 0 && !empty($auteur_id) && class_exists('GamificationController')) {
            $gRes = GamificationController::grantPoints((int) $auteur_id, 'article_created', (int) $id);
            $gamification = GamificationController::formatGrantForClient($gRes);
        }
        if ($id > 0 && ($_SESSION['user_role'] ?? '') === 'patient' && is_file(__DIR__ . '/../models/AdminNotification.php')) {
            require_once __DIR__ . '/../models/AdminNotification.php';
            $nm = trim((string) ($_SESSION['user_name'] ?? '')) ?: 'Patient';
            AdminNotification::notifyPatientPublishedArticle((int) $id, $titre, $nm);
        }

        echo json_encode([
            'success'       => true,
            'id'            => $id,
            'message'       => 'Article publié avec succès !',
            'gamification'  => $gamification,
        ], JSON_UNESCAPED_UNICODE);
    }

    /**
     * API - Modifier un article
     * POST index.php?page=api_article&id=X  (avec _method=PUT dans le body)
     *
     * @param array<string,mixed>|null $input Corps JSON déjà parsé par la route api_article.
     */
    public function update(int $id, ?array $input = null): void {
        header('Content-Type: application/json; charset=utf-8');

        $article = $this->articleModel->getById($id);
        if (!$article) {
            echo json_encode(['success' => false, 'message' => 'Article non trouvé'], JSON_UNESCAPED_UNICODE);
            return;
        }
        if (!$this->canEditArticle($article)) {
            echo json_encode(['success' => false, 'message' => "Vous n'êtes pas autorisé à modifier cet article."], JSON_UNESCAPED_UNICODE);
            return;
        }

        $data = is_array($input) ? $input : (json_decode(file_get_contents('php://input'), true) ?? []);
        $titre   = trim($data['titre']   ?? '');
        $contenu = trim($data['contenu'] ?? '');

        $errors = [];
        if (empty($titre))       $errors['titre']   = 'Le titre est obligatoire.';
        elseif (mb_strlen($titre) > 255) $errors['titre'] = 'Le titre ne doit pas dépasser 255 caractères.';

        if (empty($contenu))     $errors['contenu'] = 'Le contenu est obligatoire.';
        elseif (mb_strlen($contenu) < 10) $errors['contenu'] = 'Le contenu doit contenir au moins 10 caractères.';

        if (!empty($errors)) {
            echo json_encode(['success' => false, 'errors' => $errors], JSON_UNESCAPED_UNICODE);
            return;
        }

        $auteur_id = (int)($_SESSION['user_id'] ?? $article['auteur_id'] ?? 0) ?: null;
        $this->articleModel->update($id, $titre, $contenu, $auteur_id);

        echo json_encode(['success' => true, 'message' => 'Article modifié avec succès'], JSON_UNESCAPED_UNICODE);
    }

    /**
     * API - Supprimer un article
     * POST index.php?page=api_article&id=X  (avec _method=DELETE dans le body)
     */
    public function destroy(int $id): void {
        header('Content-Type: application/json; charset=utf-8');

        $article = $this->articleModel->getById($id);
        if (!$article) {
            echo json_encode(['success' => false, 'message' => 'Article non trouvé'], JSON_UNESCAPED_UNICODE);
            return;
        }
        if (!$this->canEditArticle($article)) {
            echo json_encode(['success' => false, 'message' => "Vous n'êtes pas autorisé à supprimer cet article."], JSON_UNESCAPED_UNICODE);
            return;
        }

        $this->articleModel->delete($id);
        echo json_encode(['success' => true, 'message' => 'Article supprimé avec succès'], JSON_UNESCAPED_UNICODE);
    }

    /**
     * POST JSON { article_id, type: like|dislike }
     * GET  index.php?page=api_article_like (via route dédiée)
     */
    public function toggleLikeArticle(): void {
        header('Content-Type: application/json; charset=utf-8');

        if (empty($_SESSION['user_id'])) {
            echo json_encode(['success' => false, 'message' => 'Connexion requise pour voter.'], JSON_UNESCAPED_UNICODE);
            return;
        }

        $raw = json_decode(file_get_contents('php://input'), true) ?? [];
        $articleId = (int)($raw['article_id'] ?? $raw['id_article'] ?? $_GET['article_id'] ?? 0);
        $type      = (string)($raw['type'] ?? 'like');
        if (!in_array($type, ['like', 'dislike'], true)) {
            $type = 'like';
        }
        if ($articleId < 1) {
            echo json_encode(['success' => false, 'message' => 'Article invalide.'], JSON_UNESCAPED_UNICODE);
            return;
        }

        $article = $this->articleModel->getById($articleId);
        if (!$article) {
            echo json_encode(['success' => false, 'message' => 'Article non trouvé.'], JSON_UNESCAPED_UNICODE);
            return;
        }

        try {
            $result = $this->articleModel->toggleArticleLike($articleId, (int)$_SESSION['user_id'], $type);
            $viewerId = (int)$_SESSION['user_id'];
            $fresh = $this->articleModel->getById($articleId, $viewerId);
            echo json_encode([
                'success'    => true,
                'action'     => $result['action'],
                'likes'      => $result['likes'],
                'dislikes'   => $result['dislikes'],
                'my_vote'    => $fresh['my_vote'] ?? null,
            ], JSON_UNESCAPED_UNICODE);
        } catch (Throwable $e) {
            error_log('toggleLikeArticle: ' . $e->getMessage());
            echo json_encode([
                'success' => false,
                'message' => 'Vote impossible — vérifiez que la table article_likes existe (doctime_full.sql).',
            ], JSON_UNESCAPED_UNICODE);
        }
    }

    private function canEditArticle(array $article): bool {
        $uid  = (int)($_SESSION['user_id'] ?? 0);
        $role = (string)($_SESSION['user_role'] ?? '');
        if ($role === 'admin') {
            return true;
        }
        return $uid > 0 && $uid === (int)($article['auteur_id'] ?? 0);
    }

    // ─────────────────────────────────────────
    //  Helper : normalise les clés de l'article
    //  pour que le JS reçoive toujours les mêmes noms
    // ─────────────────────────────────────────
    private function normalizeArticle(array $a): array {
        $prenom = trim((string)($a['auteur_prenom'] ?? ''));
        $nom    = trim((string)($a['auteur_name'] ?? $a['auteur'] ?? 'Valorys'));
        $display = trim($prenom . ' ' . $nom) ?: $nom;

        return [
            // Identifiant unique — la DB renvoie "id"
            'id'           => $a['id']         ?? $a['id_article'] ?? 0,
            'id_article'   => $a['id']         ?? $a['id_article'] ?? 0,   // compat legacy

            'titre'        => $a['titre']       ?? '',
            'contenu'      => $a['contenu']     ?? '',
            'auteur'       => $nom,
            'auteur_prenom'=> $prenom,
            'auteur_display' => $display,
            'auteur_id'    => isset($a['auteur_id']) ? (int)$a['auteur_id'] : null,
            'categorie'    => $a['categorie']   ?? null,
            'image'        => $a['image']       ?? null,
            'vues'         => (int)($a['vues']  ?? 0),
            'nb_replies'   => (int)($a['nb_replies'] ?? 0),
            'nb_likes'     => (int)($a['nb_likes'] ?? 0),
            'nb_dislikes'  => (int)($a['nb_dislikes'] ?? 0),
            'my_vote'      => $a['my_vote'] ?? null,

            // Dates : on normalise sur "created_at" ET "date_creation" (compat)
            'created_at'    => $a['created_at']    ?? $a['date_creation'] ?? null,
            'date_creation' => $a['created_at']    ?? $a['date_creation'] ?? null,
        ];
    }
}
?>
