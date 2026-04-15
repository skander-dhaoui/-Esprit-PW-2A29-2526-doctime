<?php

require_once __DIR__ . '/../models/Reply.php';
require_once __DIR__ . '/../models/Article.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../config/database.php'';
require_once __DIR__ . '/AuthController.php';

class ReplyController {

    private Reply $replyModel;
    private Article $articleModel;
    private User $userModel;
    private AuthController $auth;
    private Database $db;

    public function __construct() {
        $this->replyModel = new Reply();
        $this->articleModel = new Article();
        $this->userModel = new User();
        $this->auth = new AuthController();
        $this->db = Database::getInstance();
    }

    // ─────────────────────────────────────────
    //  Lister les réponses d'un article
    // ─────────────────────────────────────────
    public function index(int $articleId): void {
        $this->auth->requireAuth();

        try {
            $article = $this->articleModel->getById($articleId);

            if (!$article) {
                http_response_code(404);
                die('Article introuvable.');
            }

            $sort = $_GET['sort'] ?? 'recent'; // recent, popular, oldest, marked
            $replies = match ($sort) {
                'popular' => $this->replyModel->getByArticleOrderByVotes($articleId),
                'oldest' => $this->replyModel->getByArticleOrderByOldest($articleId),
                'marked' => $this->replyModel->getMarkedByArticle($articleId),
                default => $this->replyModel->getByArticleOrderByRecent($articleId),
            };

            $flash = $_SESSION['flash'] ?? null;
            unset($_SESSION['flash']);

            require_once __DIR__ . '/../views/replies_list.php';
        } catch (Exception $e) {
            error_log('Erreur ReplyController::index - ' . $e->getMessage());
            http_response_code(500);
            die('Erreur lors du chargement.');
        }
    }

    // ─────────────────────────────────────────
    //  Afficher une réponse
    // ─────────────────────────────────────────
    public function show(int $id): void {
        $this->auth->requireAuth();

        try {
            $reply = $this->replyModel->getById($id);

            if (!$reply) {
                http_response_code(404);
                die('Réponse introuvable.');
            }

            $article = $this->articleModel->getById($reply['article_id']);
            $author = $this->userModel->findById($reply['user_id']);
            $votes = $this->replyModel->getVotes($id);
            $userVote = $this->replyModel->getUserVote($id, (int)$_SESSION['user_id']);
            $flash = $_SESSION['flash'] ?? null;
            unset($_SESSION['flash']);

            require_once __DIR__ . '/../views/reply_show.php';
        } catch (Exception $e) {
            error_log('Erreur ReplyController::show - ' . $e->getMessage());
            http_response_code(500);
            die('Erreur lors du chargement.');
        }
    }

    // ─────────────────────────────────────────
    //  Créer une réponse
    // ─────────────────────────────────────────
    public function create(int $articleId): void {
        $this->auth->requireAuth();

        try {
            $article = $this->articleModel->getById($articleId);

            if (!$article) {
                http_response_code(404);
                die('Article introuvable.');
            }

            // Vérifier que l'article n'est pas fermé
            if ($article['fermé'] === 1) {
                $this->setFlash('error', 'Cet article est fermé. Vous ne pouvez pas répondre.');
                header("Location: /articles/$articleId");
                exit;
            }

            $csrfToken = $this->generateCsrfToken();
            $old = $_SESSION['old'] ?? null;
            $flash = $_SESSION['flash'] ?? null;
            unset($_SESSION['old'], $_SESSION['flash']);

            require_once __DIR__ . '/../views/reply_form.php';
        } catch (Exception $e) {
            error_log('Erreur ReplyController::create - ' . $e->getMessage());
            $this->setFlash('error', 'Erreur lors du chargement du formulaire.');
            header("Location: /articles/$articleId");
            exit;
        }
    }

    // ─────────────────────────────────────────
    //  Enregistrer une réponse
    // ─────────────────────────────────────────
    public function store(int $articleId): void {
        $this->auth->requireAuth();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: /articles/$articleId/replies/create");
            exit;
        }

        if (!$this->verifyCsrfToken($_POST['csrf_token'] ?? '')) {
            $this->setFlash('error', 'Erreur de sécurité. Veuillez réessayer.');
            header("Location: /articles/$articleId/replies/create");
            exit;
        }

        try {
            $article = $this->articleModel->getById($articleId);

            if (!$article) {
                http_response_code(404);
                die('Article introuvable.');
            }

            if ($article['fermé'] === 1) {
                $this->setFlash('error', 'Cet article est fermé.');
                header("Location: /articles/$articleId");
                exit;
            }

            $userId = (int)$_SESSION['user_id'];

            // Vérifier le spam - pas plus d'une réponse par minute
            $lastReply = $this->replyModel->getLastReplyByUser($userId);
            if ($lastReply) {
                $timeDiff = strtotime('now') - strtotime($lastReply['created_at']);
                if ($timeDiff < 60) {
                    $this->setFlash('error', 'Veuillez attendre avant de poster une autre réponse.');
                    header("Location: /articles/$articleId/replies/create");
                    exit;
                }
            }

            $data = [
                'article_id' => $articleId,
                'user_id' => $userId,
                'contenu' => htmlspecialchars(trim($_POST['contenu'] ?? ''), ENT_QUOTES, 'UTF-8'),
                'code' => !empty($_POST['code']) ? htmlspecialchars(trim($_POST['code']), ENT_QUOTES, 'UTF-8') : null,
                'langue_code' => $_POST['langue_code'] ?? 'php',
                'reponse_parent_id' => !empty($_POST['reponse_parent_id']) ? (int)$_POST['reponse_parent_id'] : null,
            ];

            $errors = $this->validateReply($data);

            if (!empty($errors)) {
                $this->setFlash('error', implode('<br>', $errors));
                $_SESSION['old'] = $data;
                header("Location: /articles/$articleId/replies/create");
                exit;
            }

            $replyId = $this->replyModel->create($data);

            if (!$replyId) {
                throw new Exception('Erreur lors de la création.');
            }

            // Mettre à jour le nombre de réponses dans l'article
            $this->articleModel->incrementReplies($articleId);

            $this->logAction($userId, 'Création réponse', "Réponse #$replyId créée pour article #$articleId");

            $this->setFlash('success', 'Votre réponse a été publiée.');
            header("Location: /articles/$articleId#reply-$replyId");
            exit;
        } catch (Exception $e) {
            error_log('Erreur ReplyController::store - ' . $e->getMessage());
            $this->setFlash('error', 'Erreur lors de la publication.');
            $_SESSION['old'] = $data ?? [];
            header("Location: /articles/$articleId/replies/create");
            exit;
        }
    }

    // ─────────────────────────────────────────
    //  Éditer une réponse
    // ─────────────────────────────────────────
    public function edit(int $id): void {
        $this->auth->requireAuth();

        try {
            $reply = $this->replyModel->getById($id);

            if (!$reply) {
                http_response_code(404);
                die('Réponse introuvable.');
            }

            $userId = (int)$_SESSION['user_id'];
            $userRole = $_SESSION['user_role'];

            // Vérifier les permissions
            if ($userRole !== 'admin' && (int)$reply['user_id'] !== $userId) {
                http_response_code(403);
                die('Accès refusé.');
            }

            // Vérifier le délai (24h)
            $createdAt = new DateTime($reply['created_at']);
            $now = new DateTime();
            $diff = $now->diff($createdAt);

            if ($diff->d > 0 && $userRole !== 'admin') {
                $this->setFlash('error', 'Vous ne pouvez modifier une réponse que dans les 24 heures.');
                header("Location: /articles/{$reply['article_id']}#reply-$id");
                exit;
            }

            $csrfToken = $this->generateCsrfToken();
            $old = $_SESSION['old'] ?? null;
            $flash = $_SESSION['flash'] ?? null;
            unset($_SESSION['old'], $_SESSION['flash']);

            require_once __DIR__ . '/../views/reply_form_edit.php';
        } catch (Exception $e) {
            error_log('Erreur ReplyController::edit - ' . $e->getMessage());
            $this->setFlash('error', 'Erreur lors du chargement.');
            header('Location: /articles');
            exit;
        }
    }

    // ─────────────────────────────────────────
    //  Mettre à jour une réponse
    // ─────────────────────────────────────────
    public function update(int $id): void {
        $this->auth->requireAuth();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: /replies/$id/edit");
            exit;
        }

        if (!$this->verifyCsrfToken($_POST['csrf_token'] ?? '')) {
            $this->setFlash('error', 'Erreur de sécurité.');
            header("Location: /replies/$id/edit");
            exit;
        }

        try {
            $reply = $this->replyModel->getById($id);

            if (!$reply) {
                http_response_code(404);
                die('Réponse introuvable.');
            }

            $userId = (int)$_SESSION['user_id'];
            $userRole = $_SESSION['user_role'];

            if ($userRole !== 'admin' && (int)$reply['user_id'] !== $userId) {
                http_response_code(403);
                die('Accès refusé.');
            }

            $data = [
                'contenu' => htmlspecialchars(trim($_POST['contenu'] ?? ''), ENT_QUOTES, 'UTF-8'),
                'code' => !empty($_POST['code']) ? htmlspecialchars(trim($_POST['code']), ENT_QUOTES, 'UTF-8') : null,
                'langue_code' => $_POST['langue_code'] ?? 'php',
            ];

            $errors = $this->validateReplyUpdate($data);

            if (!empty($errors)) {
                $this->setFlash('error', implode('<br>', $errors));
                $_SESSION['old'] = $data;
                header("Location: /replies/$id/edit");
                exit;
            }

            $this->replyModel->update($id, $data);

            $this->logAction($userId, 'Modification réponse', "Réponse #$id modifiée");

            $this->setFlash('success', 'Réponse mise à jour.');
            header("Location: /articles/{$reply['article_id']}#reply-$id");
            exit;
        } catch (Exception $e) {
            error_log('Erreur ReplyController::update - ' . $e->getMessage());
            $this->setFlash('error', 'Erreur lors de la mise à jour.');
            header("Location: /replies/$id/edit");
            exit;
        }
    }

    // ─────────────────────────────────────────
    //  Voter pour une réponse (upvote/downvote)
    // ─────────────────────────────────────────
    public function vote(int $id): void {
        $this->auth->requireAuth();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            die('Méthode non autorisée.');
        }

        try {
            $reply = $this->replyModel->getById($id);

            if (!$reply) {
                echo json_encode(['error' => 'Réponse introuvable']);
                exit;
            }

            $userId = (int)$_SESSION['user_id'];
            $type = $_POST['type'] ?? 'upvote'; // upvote ou downvote

            if (!in_array($type, ['upvote', 'downvote'])) {
                echo json_encode(['error' => 'Type de vote invalide']);
                exit;
            }

            // Vérifier si l'utilisateur a déjà voté
            $existingVote = $this->replyModel->getUserVote($id, $userId);

            if ($existingVote) {
                // Si même vote, supprimer
                if ($existingVote['type'] === $type) {
                    $this->replyModel->removeVote($id, $userId);
                    echo json_encode([
                        'success' => true,
                        'message' => 'Vote retiré',
                        'votes' => $this->replyModel->countVotes($id),
                    ]);
                    exit;
                } else {
                    // Sinon, changer le vote
                    $this->replyModel->updateVote($id, $userId, $type);
                }
            } else {
                // Ajouter le vote
                $this->replyModel->addVote($id, $userId, $type);
            }

            $votes = $this->replyModel->countVotes($id);

            $this->logAction($userId, "Vote $type", "Vote $type pour réponse #$id");

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
    //  Marquer comme réponse correcte
    // ─────────────────────────────────────────
    public function markAsCorrect(int $id): void {
        $this->auth->requireAuth();

        try {
            $reply = $this->replyModel->getById($id);

            if (!$reply) {
                echo json_encode(['error' => 'Réponse introuvable']);
                exit;
            }

            $article = $this->articleModel->getById($reply['article_id']);
            $userId = (int)$_SESSION['user_id'];

            // Seul l'auteur de l'article ou un admin peut marquer
            if ((int)$article['user_id'] !== $userId && $_SESSION['user_role'] !== 'admin') {
                echo json_encode(['error' => 'Vous n\'avez pas la permission']);
                exit;
            }

            // S'il y a déjà une réponse marquée, la dédemarquer
            $currentMarked = $this->replyModel->getMarkedByArticle($reply['article_id']);
            if ($currentMarked) {
                foreach ($currentMarked as $marked) {
                    $this->replyModel->update($marked['id'], ['marquée' => 0]);
                }
            }

            // Marquer cette réponse
            $this->replyModel->update($id, ['marquée' => 1]);

            // Mettre à jour le statut de l'article
            $this->articleModel->update($reply['article_id'], ['resolu' => 1]);

            $this->logAction($userId, 'Marque réponse correcte', "Réponse #$id marquée correcte");

            echo json_encode([
                'success' => true,
                'message' => 'Réponse marquée comme correcte',
            ]);
            exit;
        } catch (Exception $e) {
            error_log('Erreur markAsCorrect - ' . $e->getMessage());
            echo json_encode(['error' => 'Erreur serveur']);
            exit;
        }
    }

    // ─────────────────────────────────────────
    //  Aimer une réponse (bookmark)
    // ─────────────────────────────────────────
    public function like(int $id): void {
        $this->auth->requireAuth();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            die('Méthode non autorisée.');
        }

        try {
            $reply = $this->replyModel->getById($id);

            if (!$reply) {
                echo json_encode(['error' => 'Réponse introuvable']);
                exit;
            }

            $userId = (int)$_SESSION['user_id'];

            // Vérifier si déjà aimée
            $isLiked = $this->replyModel->isLikedByUser($id, $userId);

            if ($isLiked) {
                $this->replyModel->removeLike($id, $userId);
                echo json_encode([
                    'success' => true,
                    'message' => 'Favori retiré',
                    'liked' => false,
                ]);
            } else {
                $this->replyModel->addLike($id, $userId);
                echo json_encode([
                    'success' => true,
                    'message' => 'Ajouté aux favoris',
                    'liked' => true,
                ]);
            }

            $this->logAction($userId, 'Like réponse', "Like réponse #$id");
            exit;
        } catch (Exception $e) {
            error_log('Erreur like - ' . $e->getMessage());
            echo json_encode(['error' => 'Erreur serveur']);
            exit;
        }
    }

    // ─────────────────────────────────────────
    //  Signaler une réponse (modération)
    // ─────────────────────────────────────────
    public function report(int $id): void {
        $this->auth->requireAuth();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            die('Méthode non autorisée.');
        }

        try {
            $reply = $this->replyModel->getById($id);

            if (!$reply) {
                echo json_encode(['error' => 'Réponse introuvable']);
                exit;
            }

            $userId = (int)$_SESSION['user_id'];
            $raison = htmlspecialchars(trim($_POST['raison'] ?? ''), ENT_QUOTES, 'UTF-8');

            if (empty($raison) || strlen($raison) < 10) {
                echo json_encode(['error' => 'La raison du signalement doit contenir au moins 10 caractères']);
                exit;
            }

            // Vérifier si déjà signalée par cet utilisateur
            if ($this->replyModel->isReportedByUser($id, $userId)) {
                echo json_encode(['error' => 'Vous avez déjà signalé cette réponse']);
                exit;
            }

            $this->replyModel->addReport($id, $userId, $raison);

            $this->logAction($userId, 'Signalement réponse', "Réponse #$id signalée - Raison: $raison");

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
    //  Supprimer une réponse
    // ─────────────────────────────────────────
    public function delete(int $id): void {
        $this->auth->requireAuth();

        try {
            $reply = $this->replyModel->getById($id);

            if (!$reply) {
                http_response_code(404);
                die('Réponse introuvable.');
            }

            $userId = (int)$_SESSION['user_id'];
            $userRole = $_SESSION['user_role'];

            // Vérifier les permissions
            if ($userRole !== 'admin' && (int)$reply['user_id'] !== $userId) {
                http_response_code(403);
                die('Accès refusé.');
            }

            $articleId = $reply['article_id'];

            $this->replyModel->delete($id);

            // Mettre à jour le nombre de réponses
            $this->articleModel->decrementReplies($articleId);

            $this->logAction($userId, 'Suppression réponse', "Réponse #$id supprimée");

            $this->setFlash('success', 'Réponse supprimée.');
            header("Location: /articles/$articleId");
            exit;
        } catch (Exception $e) {
            error_log('Erreur ReplyController::delete - ' . $e->getMessage());
            $this->setFlash('error', 'Erreur lors de la suppression.');
            header('Location: /articles');
            exit;
        }
    }

    // ─────────────────────────────────────────
    //  Lister les réponses d'un utilisateur
    // ─────────────────────────────────────────
    public function userReplies(int $userId): void {
        $this->auth->requireAuth();

        try {
            $user = $this->userModel->findById($userId);

            if (!$user) {
                http_response_code(404);
                die('Utilisateur introuvable.');
            }

            $sort = $_GET['sort'] ?? 'recent'; // recent, popular, liked
            $replies = match ($sort) {
                'popular' => $this->replyModel->getByUserOrderByVotes($userId),
                'liked' => $this->replyModel->getByUserOrderByLikes($userId),
                default => $this->replyModel->getByUserOrderByRecent($userId),
            };

            $flash = $_SESSION['flash'] ?? null;
            unset($_SESSION['flash']);

            require_once __DIR__ . '/../views/user_replies.php';
        } catch (Exception $e) {
            error_log('Erreur ReplyController::userReplies - ' . $e->getMessage());
            http_response_code(500);
            die('Erreur lors du chargement.');
        }
    }

    // ─────────────────────────────────────────
    //  API - Réponses récentes
    // ─────────────────────────────────────────
    public function apiRecent(): void {
        header('Content-Type: application/json');
        $this->auth->requireAuth();

        try {
            $limit = (int)($_GET['limit'] ?? 10);
            $replies = $this->replyModel->getRecent($limit);

            echo json_encode([
                'success' => true,
                'replies' => $replies,
            ]);
            exit;
        } catch (Exception $e) {
            error_log('Erreur apiRecent - ' . $e->getMessage());
            echo json_encode(['error' => 'Erreur serveur']);
            exit;
        }
    }

    // ─────────────────────────────────────────
    //  API - Réponses populaires
    // ─────────────────────────────────────────
    public function apiPopular(): void {
        header('Content-Type: application/json');
        $this->auth->requireAuth();

        try {
            $limit = (int)($_GET['limit'] ?? 10);
            $replies = $this->replyModel->getPopular($limit);

            echo json_encode([
                'success' => true,
                'replies' => $replies,
            ]);
            exit;
        } catch (Exception $e) {
            error_log('Erreur apiPopular - ' . $e->getMessage());
            echo json_encode(['error' => 'Erreur serveur']);
            exit;
        }
    }

    // ─────────────────────────────────────────
    //  Helpers privés
    // ─────────────────────────────────────────
    private function validateReply(array $data): array {
        $errors = [];

        if (empty($data['contenu']) || strlen($data['contenu']) < 10) {
            $errors[] = 'La réponse doit contenir au moins 10 caractères.';
        }

        if (strlen($data['contenu']) > 5000) {
            $errors[] = 'La réponse dépasse 5000 caractères.';
        }

        if (!empty($data['code']) && strlen($data['code']) < 3) {
            $errors[] = 'Le code doit contenir au moins 3 caractères.';
        }

        if (!in_array($data['langue_code'], ['php', 'javascript', 'python', 'sql', 'html', 'css', 'java', 'cpp'])) {
            $errors[] = 'Langage de code invalide.';
        }

        return $errors;
    }

    private function validateReplyUpdate(array $data): array {
        return $this->validateReply($data);
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



