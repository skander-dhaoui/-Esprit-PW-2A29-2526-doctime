<?php

require_once __DIR__ . '/../models/Article.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/AuthController.php';
class ArticleController {

    private Article $articleModel;
    private Reply $replyModel;
    private User $userModel;
    private AuthController $auth;
    private Database $db;

    public function __construct() {
        $this->articleModel = new Article();
        $this->replyModel = new Reply();
        $this->userModel = new User();
        $this->auth = new AuthController();
        $this->db = Database::getInstance();
    }

    // ─────────────────────────────────────────
    //  Lister tous les articles
    // ─────────────────────────────────────────
    public function index(): void {
        $this->auth->requireAuth();

        try {
            $page = (int)($_GET['page'] ?? 1);
            $perPage = 20;
            $offset = ($page - 1) * $perPage;

            $sort = $_GET['sort'] ?? 'recent'; // recent, popular, unanswered, resolved, views
            $filter = $_GET['filter'] ?? 'all'; // all, open, resolved, closed
            $search = $_GET['search'] ?? '';
            $tag = $_GET['tag'] ?? '';

            $articles = match ($sort) {
                'popular' => $this->articleModel->getPopular($offset, $perPage, $filter, $search, $tag),
                'unanswered' => $this->articleModel->getUnanswered($offset, $perPage, $search, $tag),
                'resolved' => $this->articleModel->getResolved($offset, $perPage, $search, $tag),
                'views' => $this->articleModel->getByViews($offset, $perPage, $filter, $search, $tag),
                default => $this->articleModel->getRecent($offset, $perPage, $filter, $search, $tag),
            };

            $total = $this->articleModel->countAll($filter, $search, $tag);
            $totalPages = ceil($total / $perPage);
            $tags = $this->articleModel->getPopularTags();
            $flash = $_SESSION['flash'] ?? null;
            unset($_SESSION['flash']);

            require_once __DIR__ . '/../views/articles_index.php';
        } catch (Exception $e) {
            error_log('Erreur ArticleController::index - ' . $e->getMessage());
            $this->setFlash('error', 'Erreur lors du chargement des articles.');
            header('Location: /dashboard');
            exit;
        }
    }

    // ─────────────────────────────────────────
    //  Afficher un article
    // ─────────────────────────────────────────
    public function show(int $id): void {
        $this->auth->requireAuth();

        try {
            $article = $this->articleModel->getById($id);

            if (!$article) {
                http_response_code(404);
                require_once __DIR__ . '/../views/errors/404.php';
                exit;
            }

            // Incrémenter les vues
            $this->articleModel->incrementViews($id);

            $replies = $this->replyModel->getByArticleOrderByRecent($id);
            $author = $this->userModel->findById($article['user_id']);
            $relatedArticles = $this->articleModel->getRelated($article['id'], 5);
            $userVote = $this->articleModel->getUserVote($id, (int)$_SESSION['user_id']);
            $userLiked = $this->articleModel->isLikedByUser($id, (int)$_SESSION['user_id']);
            $votes = $this->articleModel->countVotes($id);
            $flash = $_SESSION['flash'] ?? null;
            unset($_SESSION['flash']);

            require_once __DIR__ . '/../views/article_show.php';
        } catch (Exception $e) {
            error_log('Erreur ArticleController::show - ' . $e->getMessage());
            http_response_code(500);
            die('Erreur lors du chargement.');
        }
    }

    // ─────────────────────────────────────────
    //  Créer un article (formulaire)
    // ─────────────────────────────────────────
    public function create(): void {
        $this->auth->requireAuth();

        try {
            $csrfToken = $this->generateCsrfToken();
            $tags = $this->articleModel->getAllTags();
            $categories = $this->articleModel->getCategories();
            $old = $_SESSION['old'] ?? null;
            $flash = $_SESSION['flash'] ?? null;
            unset($_SESSION['old'], $_SESSION['flash']);

            require_once __DIR__ . '/../views/article_form.php';
        } catch (Exception $e) {
            error_log('Erreur ArticleController::create - ' . $e->getMessage());
            $this->setFlash('error', 'Erreur lors du chargement du formulaire.');
            header('Location: /articles');
            exit;
        }
    }

    // ─────────────────────────────────────────
    //  Enregistrer un article
    // ─────────────────────────────────────────
    public function store(): void {
        $this->auth->requireAuth();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /articles/create');
            exit;
        }

        if (!$this->verifyCsrfToken($_POST['csrf_token'] ?? '')) {
            $this->setFlash('error', 'Erreur de sécurité. Veuillez réessayer.');
            header('Location: /articles/create');
            exit;
        }

        try {
            $userId = (int)$_SESSION['user_id'];

            // Vérifier le spam - pas plus d'un article par 5 minutes
            $lastArticle = $this->articleModel->getLastArticleByUser($userId);
            if ($lastArticle) {
                $timeDiff = strtotime('now') - strtotime($lastArticle['created_at']);
                if ($timeDiff < 300) {
                    $this->setFlash('error', 'Veuillez attendre 5 minutes avant de poster un autre article.');
                    header('Location: /articles/create');
                    exit;
                }
            }

            $data = [
                'titre' => htmlspecialchars(trim($_POST['titre'] ?? ''), ENT_QUOTES, 'UTF-8'),
                'description' => htmlspecialchars(trim($_POST['description'] ?? ''), ENT_QUOTES, 'UTF-8'),
                'contenu' => htmlspecialchars(trim($_POST['contenu'] ?? ''), ENT_QUOTES, 'UTF-8'),
                'categorie' => $_POST['categorie'] ?? 'autre',
                'user_id' => $userId,
                'code' => !empty($_POST['code']) ? htmlspecialchars(trim($_POST['code']), ENT_QUOTES, 'UTF-8') : null,
                'langue_code' => $_POST['langue_code'] ?? 'php',
            ];

            $errors = $this->validateArticle($data);

            if (!empty($errors)) {
                $this->setFlash('error', implode('<br>', $errors));
                $_SESSION['old'] = $data;
                header('Location: /articles/create');
                exit;
            }

            $articleId = $this->articleModel->create($data);

            if (!$articleId) {
                throw new Exception('Erreur lors de la création.');
            }

            // Ajouter les tags
            $tags = $_POST['tags'] ?? [];
            if (!empty($tags)) {
                foreach ((array)$tags as $tagName) {
                    if (!empty($tagName)) {
                        $tagName = htmlspecialchars(trim($tagName), ENT_QUOTES, 'UTF-8');
                        $tag = $this->articleModel->findOrCreateTag($tagName);
                        if ($tag) {
                            $this->articleModel->attachTag($articleId, $tag['id']);
                        }
                    }
                }
            }

            $this->logAction($userId, 'Création article', "Article #$articleId créé - {$data['titre']}");

            $this->setFlash('success', 'Article créé avec succès.');
            header('Location: /articles/' . $articleId);
            exit;
        } catch (Exception $e) {
            error_log('Erreur ArticleController::store - ' . $e->getMessage());
            $this->setFlash('error', 'Erreur lors de la création.');
            $_SESSION['old'] = $data ?? [];
            header('Location: /articles/create');
            exit;
        }
    }

    // ─────────────────────────────────────────
    //  Éditer un article
    // ─────────────────────────────────────────
    public function edit(int $id): void {
        $this->auth->requireAuth();

        try {
            $article = $this->articleModel->getById($id);

            if (!$article) {
                http_response_code(404);
                die('Article introuvable.');
            }

            $userId = (int)$_SESSION['user_id'];
            $userRole = $_SESSION['user_role'];

            // Vérifier les permissions
            if ($userRole !== 'admin' && (int)$article['user_id'] !== $userId) {
                http_response_code(403);
                die('Accès refusé.');
            }

            $csrfToken = $this->generateCsrfToken();
            $tags = $this->articleModel->getAllTags();
            $categories = $this->articleModel->getCategories();
            $articleTags = $this->articleModel->getArticleTags($id);
            $old = $_SESSION['old'] ?? null;
            $flash = $_SESSION['flash'] ?? null;
            unset($_SESSION['old'], $_SESSION['flash']);

            require_once __DIR__ . '/../views/article_form_edit.php';
        } catch (Exception $e) {
            error_log('Erreur ArticleController::edit - ' . $e->getMessage());
            $this->setFlash('error', 'Erreur lors du chargement.');
            header('Location: /articles');
            exit;
        }
    }

    // ─────────────────────────────────────────
    //  Mettre à jour un article
    // ─────────────────────────────────────────
    public function update(int $id): void {
        $this->auth->requireAuth();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: /articles/$id/edit");
            exit;
        }

        if (!$this->verifyCsrfToken($_POST['csrf_token'] ?? '')) {
            $this->setFlash('error', 'Erreur de sécurité.');
            header("Location: /articles/$id/edit");
            exit;
        }

        try {
            $article = $this->articleModel->getById($id);

            if (!$article) {
                http_response_code(404);
                die('Article introuvable.');
            }

            $userId = (int)$_SESSION['user_id'];
            $userRole = $_SESSION['user_role'];

            if ($userRole !== 'admin' && (int)$article['user_id'] !== $userId) {
                http_response_code(403);
                die('Accès refusé.');
            }

            $data = [
                'titre' => htmlspecialchars(trim($_POST['titre'] ?? ''), ENT_QUOTES, 'UTF-8'),
                'description' => htmlspecialchars(trim($_POST['description'] ?? ''), ENT_QUOTES, 'UTF-8'),
                'contenu' => htmlspecialchars(trim($_POST['contenu'] ?? ''), ENT_QUOTES, 'UTF-8'),
                'categorie' => $_POST['categorie'] ?? 'autre',
                'code' => !empty($_POST['code']) ? htmlspecialchars(trim($_POST['code']), ENT_QUOTES, 'UTF-8') : null,
                'langue_code' => $_POST['langue_code'] ?? 'php',
            ];

            $errors = $this->validateArticleUpdate($data);

            if (!empty($errors)) {
                $this->setFlash('error', implode('<br>', $errors));
                $_SESSION['old'] = $data;
                header("Location: /articles/$id/edit");
                exit;
            }

            $this->articleModel->update($id, $data);

            // Mettre à jour les tags
            $this->articleModel->detachAllTags($id);
            $tags = $_POST['tags'] ?? [];
            if (!empty($tags)) {
                foreach ((array)$tags as $tagName) {
                    if (!empty($tagName)) {
                        $tagName = htmlspecialchars(trim($tagName), ENT_QUOTES, 'UTF-8');
                        $tag = $this->articleModel->findOrCreateTag($tagName);
                        if ($tag) {
                            $this->articleModel->attachTag($id, $tag['id']);
                        }
                    }
                }
            }

            $this->logAction($userId, 'Modification article', "Article #$id modifié");

            $this->setFlash('success', 'Article mis à jour.');
            header('Location: /articles/' . $id);
            exit;
        } catch (Exception $e) {
            error_log('Erreur ArticleController::update - ' . $e->getMessage());
            $this->setFlash('error', 'Erreur lors de la mise à jour.');
            header("Location: /articles/$id/edit");
            exit;
        }
    }

    // ─────────────────────────────────────────
    //  Voter pour un article
    // ─────────────────────────────────────────
    public function vote(int $id): void {
        $this->auth->requireAuth();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            die('Méthode non autorisée.');
        }

        try {
            $article = $this->articleModel->getById($id);

            if (!$article) {
                echo json_encode(['error' => 'Article introuvable']);
                exit;
            }

            $userId = (int)$_SESSION['user_id'];
            $type = $_POST['type'] ?? 'upvote'; // upvote ou downvote

            if (!in_array($type, ['upvote', 'downvote'])) {
                echo json_encode(['error' => 'Type de vote invalide']);
                exit;
            }

            // Vérifier si l'utilisateur a déjà voté
            $existingVote = $this->articleModel->getUserVote($id, $userId);

            if ($existingVote) {
                // Si même vote, supprimer
                if ($existingVote['type'] === $type) {
                    $this->articleModel->removeVote($id, $userId);
                    echo json_encode([
                        'success' => true,
                        'message' => 'Vote retiré',
                        'votes' => $this->articleModel->countVotes($id),
                    ]);
                    exit;
                } else {
                    // Sinon, changer le vote
                    $this->articleModel->updateVote($id, $userId, $type);
                }
            } else {
                // Ajouter le vote
                $this->articleModel->addVote($id, $userId, $type);
            }

            $votes = $this->articleModel->countVotes($id);

            $this->logAction($userId, "Vote $type article", "Vote $type pour article #$id");

            echo json_encode([
                'success' => true,
                'message' => 'Vote enregistré',
                'votes' => $votes,
            ]);
            exit;
        } catch (Exception $e) {
            error_log('Erreur vote - ' . $e->getMessage());
            echo json_encode(['error' => 'Erreur serveur']);
            exit;
        }
    }

    // ─────────────────────────────────────────
    //  Aimer un article (bookmark)
    // ─────────────────────────────────────────
    public function like(int $id): void {
        $this->auth->requireAuth();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            die('Méthode non autorisée.');
        }

        try {
            $article = $this->articleModel->getById($id);

            if (!$article) {
                echo json_encode(['error' => 'Article introuvable']);
                exit;
            }

            $userId = (int)$_SESSION['user_id'];

            // Vérifier si déjà aimé
            $isLiked = $this->articleModel->isLikedByUser($id, $userId);

            if ($isLiked) {
                $this->articleModel->removeLike($id, $userId);
                echo json_encode([
                    'success' => true,
                    'message' => 'Favori retiré',
                    'liked' => false,
                ]);
            } else {
                $this->articleModel->addLike($id, $userId);
                echo json_encode([
                    'success' => true,
                    'message' => 'Ajouté aux favoris',
                    'liked' => true,
                ]);
            }

            $this->logAction($userId, 'Like article', "Like article #$id");
            exit;
        } catch (Exception $e) {
            error_log('Erreur like - ' . $e->getMessage());
            echo json_encode(['error' => 'Erreur serveur']);
            exit;
        }
    }

    // ─────────────────────────────────────────
    //  Signaler un article (modération)
    // ─────────────────────────────────────────
    public function report(int $id): void {
        $this->auth->requireAuth();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            die('Méthode non autorisée.');
        }

        try {
            $article = $this->articleModel->getById($id);

            if (!$article) {
                echo json_encode(['error' => 'Article introuvable']);
                exit;
            }

            $userId = (int)$_SESSION['user_id'];
            $raison = htmlspecialchars(trim($_POST['raison'] ?? ''), ENT_QUOTES, 'UTF-8');

            if (empty($raison) || strlen($raison) < 10) {
                echo json_encode(['error' => 'La raison du signalement doit contenir au moins 10 caractères']);
                exit;
            }

            // Vérifier si déjà signalé par cet utilisateur
            if ($this->articleModel->isReportedByUser($id, $userId)) {
                echo json_encode(['error' => 'Vous avez déjà signalé cet article']);
                exit;
            }

            $this->articleModel->addReport($id, $userId, $raison);

            $this->logAction($userId, 'Signalement article', "Article #$id signalé - Raison: $raison");

            echo json_encode([
                'success' => true,
                'message' => 'Merci pour votre signalement. L\'équipe de modération a été notifiée.',
            ]);
            exit;
        } catch (Exception $e) {
            error_log('Erreur report - ' . $e->getMessage());
            echo json_encode(['error' => 'Erreur serveur']);
            exit;
        }
    }

    // ─────────────────────────────────────────
    //  Fermer un article
    // ─────────────────────────────────────────
    public function close(int $id): void {
        $this->auth->requireAuth();

        try {
            $article = $this->articleModel->getById($id);

            if (!$article) {
                http_response_code(404);
                die('Article introuvable.');
            }

            $userId = (int)$_SESSION['user_id'];
            $userRole = $_SESSION['user_role'];

            if ($userRole !== 'admin' && (int)$article['user_id'] !== $userId) {
                http_response_code(403);
                die('Accès refusé.');
            }

            $raison = htmlspecialchars(trim($_POST['raison'] ?? ''), ENT_QUOTES, 'UTF-8');

            if (empty($raison)) {
                $this->setFlash('error', 'Une raison de fermeture est obligatoire.');
                header("Location: /articles/$id");
                exit;
            }

            $this->articleModel->update($id, [
                'fermé' => 1,
                'raison_fermeture' => $raison,
                'date_fermeture' => date('Y-m-d H:i:s'),
            ]);

            $this->logAction($userId, 'Fermeture article', "Article #$id fermé - Raison: $raison");

            $this->setFlash('success', 'Article fermé.');
            header("Location: /articles/$id");
            exit;
        } catch (Exception $e) {
            error_log('Erreur close - ' . $e->getMessage());
            $this->setFlash('error', 'Erreur lors de la fermeture.');
            header("Location: /articles/$id");
            exit;
        }
    }

    // ─────────────────────────────────────────
    //  Rouvrir un article (admin)
    // ─────────────────────────────────────────
    public function reopen(int $id): void {
        $this->auth->requireRole('admin');

        try {
            $article = $this->articleModel->getById($id);

            if (!$article) {
                http_response_code(404);
                die('Article introuvable.');
            }

            $this->articleModel->update($id, [
                'fermé' => 0,
                'raison_fermeture' => null,
                'date_fermeture' => null,
            ]);

            $this->logAction($_SESSION['user_id'], 'Réouverture article', "Article #$id réouvert");

            $this->setFlash('success', 'Article réouvert.');
            header("Location: /articles/$id");
            exit;
        } catch (Exception $e) {
            error_log('Erreur reopen - ' . $e->getMessage());
            $this->setFlash('error', 'Erreur lors de la réouverture.');
            header("Location: /articles/$id");
            exit;
        }
    }

    // ─────────────────────────────────────────
    //  Supprimer un article
    // ─────────────────────────────────────────
    public function delete(int $id): void {
        $this->auth->requireAuth();

        try {
            $article = $this->articleModel->getById($id);

            if (!$article) {
                http_response_code(404);
                die('Article introuvable.');
            }

            $userId = (int)$_SESSION['user_id'];
            $userRole = $_SESSION['user_role'];

            if ($userRole !== 'admin' && (int)$article['user_id'] !== $userId) {
                http_response_code(403);
                die('Accès refusé.');
            }

            // Supprimer les tags associés
            $this->articleModel->detachAllTags($id);

            // Supprimer les votes
            $this->articleModel->deleteVotes($id);

            // Supprimer les favoris
            $this->articleModel->deleteLikes($id);

            // Supprimer les signalements
            $this->articleModel->deleteReports($id);

            // Supprimer les réponses
            $replies = $this->replyModel->getByArticle($id);
            foreach ($replies as $reply) {
                $this->replyModel->delete($reply['id']);
            }

            $this->articleModel->delete($id);

            $this->logAction($userId, 'Suppression article', "Article #$id supprimé");

            $this->setFlash('success', 'Article supprimé.');
            header('Location: /articles');
            exit;
        } catch (Exception $e) {
            error_log('Erreur ArticleController::delete - ' . $e->getMessage());
            $this->setFlash('error', 'Erreur lors de la suppression.');
            header('Location: /articles');
            exit;
        }
    }

    // ─────────────────────────────────────────
    //  Mes articles (utilisateur)
    // ─────────────────────────────────────────
    public function myArticles(): void {
        $this->auth->requireAuth();

        try {
            $userId = (int)$_SESSION['user_id'];
            $filter = $_GET['filter'] ?? 'all'; // all, open, resolved, closed
            $sort = $_GET['sort'] ?? 'recent';

            $articles = match ($sort) {
                'popular' => $this->articleModel->getByUserPopular($userId, $filter),
                'views' => $this->articleModel->getByUserViews($userId, $filter),
                default => $this->articleModel->getByUserRecent($userId, $filter),
            };

            $flash = $_SESSION['flash'] ?? null;
            unset($_SESSION['flash']);

            require_once __DIR__ . '/../views/my_articles.php';
        } catch (Exception $e) {
            error_log('Erreur ArticleController::myArticles - ' . $e->getMessage());
            $this->setFlash('error', 'Erreur lors du chargement.');
            header('Location: /dashboard');
            exit;
        }
    }

    // ─────────────────────────────────────────
    //  Articles d'un utilisateur (profil)
    // ─────────────────────────────────────────
    public function userArticles(int $userId): void {
        $this->auth->requireAuth();

        try {
            $user = $this->userModel->findById($userId);

            if (!$user) {
                http_response_code(404);
                die('Utilisateur introuvable.');
            }

            $articles = $this->articleModel->getByUser($userId);
            $flash = $_SESSION['flash'] ?? null;
            unset($_SESSION['flash']);

            require_once __DIR__ . '/../views/user_articles.php';
        } catch (Exception $e) {
            error_log('Erreur ArticleController::userArticles - ' . $e->getMessage());
            http_response_code(500);
            die('Erreur lors du chargement.');
        }
    }

    // ─────────────────────────────────────────
    //  Rechercher par tag
    // ─────────────────────────────────────────
    public function byTag(string $tagName): void {
        $this->auth->requireAuth();

        try {
            $tagName = htmlspecialchars($tagName, ENT_QUOTES, 'UTF-8');
            $tag = $this->articleModel->findTagByName($tagName);

            if (!$tag) {
                http_response_code(404);
                die('Tag introuvable.');
            }

            $page = (int)($_GET['page'] ?? 1);
            $perPage = 20;
            $offset = ($page - 1) * $perPage;

            $articles = $this->articleModel->getByTag($tag['id'], $offset, $perPage);
            $total = $this->articleModel->countByTag($tag['id']);
            $totalPages = ceil($total / $perPage);
            $flash = $_SESSION['flash'] ?? null;
            unset($_SESSION['flash']);

            require_once __DIR__ . '/../views/articles_by_tag.php';
        } catch (Exception $e) {
            error_log('Erreur ArticleController::byTag - ' . $e->getMessage());
            http_response_code(500);
            die('Erreur lors du chargement.');
        }
    }

    // ─────────────────────────────────────────
    //  API - Articles récents
    // ─────────────────────────────────────────
    public function apiRecent(): void {
        header('Content-Type: application/json');
        $this->auth->requireAuth();

        try {
            $limit = (int)($_GET['limit'] ?? 10);
            $articles = $this->articleModel->getRecentApi($limit);

            echo json_encode([
                'success' => true,
                'articles' => $articles,
            ]);
            exit;
        } catch (Exception $e) {
            error_log('Erreur apiRecent - ' . $e->getMessage());
            echo json_encode(['error' => 'Erreur serveur']);
            exit;
        }
    }

    // ─────────────────────────────────────────
    //  API - Articles populaires
    // ─────────────────────────────────────────
    public function apiPopular(): void {
        header('Content-Type: application/json');
        $this->auth->requireAuth();

        try {
            $limit = (int)($_GET['limit'] ?? 10);
            $articles = $this->articleModel->getPopularApi($limit);

            echo json_encode([
                'success' => true,
                'articles' => $articles,
            ]);
            exit;
        } catch (Exception $e) {
            error_log('Erreur apiPopular - ' . $e->getMessage());
            echo json_encode(['error' => 'Erreur serveur']);
            exit;
        }
    }

    // ─────────────────────────────────────────
    //  API - Articles sans réponse
    // ─────────────────────────────────────────
    public function apiUnanswered(): void {
        header('Content-Type: application/json');
        $this->auth->requireAuth();

        try {
            $limit = (int)($_GET['limit'] ?? 10);
            $articles = $this->articleModel->getUnansweredApi($limit);

            echo json_encode([
                'success' => true,
                'articles' => $articles,
            ]);
            exit;
        } catch (Exception $e) {
            error_log('Erreur apiUnanswered - ' . $e->getMessage());
            echo json_encode(['error' => 'Erreur serveur']);
            exit;
        }
    }

    // ─────────────────────────────────────────
    //  Helpers privés
    // ─────────────────────────────────────────
    private function validateArticle(array $data): array {
        $errors = [];

        if (empty($data['titre']) || strlen($data['titre']) < 10) {
            $errors[] = 'Le titre doit contenir au moins 10 caractères.';
        }

        if (strlen($data['titre']) > 255) {
            $errors[] = 'Le titre dépasse 255 caractères.';
        }

        if (empty($data['description']) || strlen($data['description']) < 20) {
            $errors[] = 'La description doit contenir au moins 20 caractères.';
        }

        if (strlen($data['description']) > 500) {
            $errors[] = 'La description dépasse 500 caractères.';
        }

        if (empty($data['contenu']) || strlen($data['contenu']) < 30) {
            $errors[] = 'Le contenu doit contenir au moins 30 caractères.';
        }

        if (strlen($data['contenu']) > 10000) {
            $errors[] = 'Le contenu dépasse 10000 caractères.';
        }

        if (!empty($data['code']) && strlen($data['code']) < 3) {
            $errors[] = 'Le code doit contenir au moins 3 caractères.';
        }

        if (!in_array($data['langue_code'], ['php', 'javascript', 'python', 'sql', 'html', 'css', 'java', 'cpp'])) {
            $errors[] = 'Langage de code invalide.';
        }

        return $errors;
    }

    private function validateArticleUpdate(array $data): array {
        return $this->validateArticle($data);
    }

    private function generateCsrfToken(): string {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    private function verifyCsrfToken(string $token): bool {
        return !empty($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
    }

    private function setFlash(string $type, string $message): void {
        $_SESSION['flash'] = ['type' => $type, 'message' => $message];
    }

    private function logAction(int $userId, string $action, string $description): void {
        try {
            $sql = "INSERT INTO logs (user_id, action, description, ip_address, created_at)
                    VALUES (:user_id, :action, :description, :ip, NOW())";
            $this->db->execute($sql, [
                'user_id' => $userId,
                'action' => $action,
                'description' => $description,
                'ip' => $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0',
            ]);
        } catch (Exception $e) {
            error_log('Erreur logAction: ' . $e->getMessage());
        }
    }
}
?>



