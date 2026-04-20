<?php

require_once __DIR__ . '/../models/Produit.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/AuthController.php';
class ProduitController {

    private Produit $produitModel;
    private AuthController $auth;
    private Database $db;

    public function __construct() {
        $this->produitModel = new Produit();
        $this->auth = new AuthController();
        $this->db = Database::getInstance();
    }

    // ─────────────────────────────────────────
    //  Lister tous les produits
    // ─────────────────────────────────────────
    public function index(): void {
        try {
            $page = (int)($_GET['page'] ?? 1);
            $perPage = 20;
            $offset = ($page - 1) * $perPage;

            $categorie = $_GET['categorie'] ?? 'all';
            $search = $_GET['search'] ?? '';
            $sort = $_GET['sort'] ?? 'recent';

            if ($categorie !== 'all') {
                $produits = $this->produitModel->getByCategorie($categorie, $offset, $perPage, $search);
                $total = $this->produitModel->countByCategorie($categorie, $search);
            } else {
                $produits = match ($sort) {
                    'prix_asc' => $this->produitModel->getByPrice('ASC', $offset, $perPage, $search),
                    'prix_desc' => $this->produitModel->getByPrice('DESC', $offset, $perPage, $search),
                    'populaire' => $this->produitModel->getPopular($offset, $perPage, $search),
                    'note' => $this->produitModel->getByRating($offset, $perPage, $search),
                    default => $this->produitModel->getAll($offset, $perPage, $search),
                };
                $total = $this->produitModel->countAll($search);
            }

            $totalPages = ceil($total / $perPage);
            $categories = $this->produitModel->getCategories();
            $flash = $_SESSION['flash'] ?? null;
            unset($_SESSION['flash']);

            require_once __DIR__ . '/../views/produits_index.php';
        } catch (Exception $e) {
            error_log('Erreur ProduitController::index - ' . $e->getMessage());
            $_SESSION['flash'] = ['type' => 'error', 'message' => 'Erreur lors du chargement.'];
            header('Location: /');
            exit;
        }
    }

    // ─────────────────────────────────────────
    //  Détails d'un produit
    // ─────────────────────────────────────────
    public function show(int $id): void {
        try {
            $produit = $this->produitModel->getById($id);

            if (!$produit) {
                http_response_code(404);
                require_once __DIR__ . '/../views/404.php';
                exit;
            }

            $produits_similaires = $this->produitModel->getSimilaires($id, $produit['categorie_id'], 4);
            $avis = $this->produitModel->getAvis($id);
            $moyenne_avis = $this->produitModel->getAvisMoyenne($id);
            $flash = $_SESSION['flash'] ?? null;
            unset($_SESSION['flash']);

            require_once __DIR__ . '/../views/produit_show.php';
        } catch (Exception $e) {
            error_log('Erreur ProduitController::show - ' . $e->getMessage());
            http_response_code(500);
            die('Erreur lors du chargement.');
        }
    }

    // ─────────────────────────────────────────
    //  Recherche produits (API JSON)
    // ─────────────────────────────────────────
    public function search(): void {
        header('Content-Type: application/json');

        try {
            $search = trim($_GET['q'] ?? '');
            $limit = (int)($_GET['limit'] ?? 10);

            if (strlen($search) < 2) {
                echo json_encode(['error' => 'Requête trop courte']);
                exit;
            }

            $produits = $this->produitModel->search($search, $limit);

            echo json_encode([
                'success' => true,
                'results' => $produits
            ]);
            exit;
        } catch (Exception $e) {
            error_log('Erreur search - ' . $e->getMessage());
            echo json_encode(['error' => 'Erreur serveur']);
            exit;
        }
    }

    // ─────────────────────────────────────────
    //  Gérer les produits (admin)
    // ─────────────────────────────────────────
    public function manage(): void {
        $this->auth->requireRole('admin');

        try {
            $page = (int)($_GET['page'] ?? 1);
            $perPage = 20;
            $offset = ($page - 1) * $perPage;

            $filter = $_GET['filter'] ?? 'all';
            $search = $_GET['search'] ?? '';

            if ($filter !== 'all') {
                $produits = $this->produitModel->getByStatus($filter, $offset, $perPage, $search);
                $total = $this->produitModel->countByStatus($filter, $search);
            } else {
                $produits = $this->produitModel->getAll($offset, $perPage, $search);
                $total = $this->produitModel->countAll($search);
            }

            $totalPages = ceil($total / $perPage);
            $categories = $this->produitModel->getCategories();
            $flash = $_SESSION['flash'] ?? null;
            unset($_SESSION['flash']);

            require_once __DIR__ . '/../views/backoffice/produit_manage.php';
        } catch (Exception $e) {
            error_log('Erreur ProduitController::manage - ' . $e->getMessage());
            $_SESSION['flash'] = ['type' => 'error', 'message' => 'Erreur lors du chargement.'];
            header('Location: /admin/dashboard');
            exit;
        }
    }

    // ─────────────────────────────────────────
    //  Créer un produit (admin)
    // ─────────────────────────────────────────
    public function create(): void {
        $this->auth->requireRole('admin');

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!$this->verifyCsrfToken($_POST['csrf_token'] ?? '')) {
                $_SESSION['flash'] = ['type' => 'error', 'message' => 'Erreur de sécurité.'];
                header('Location: /admin/produits/create');
                exit;
            }

            try {
                $data = [
                    'nom' => htmlspecialchars(trim($_POST['nom'] ?? ''), ENT_QUOTES, 'UTF-8'),
                    'description' => htmlspecialchars(trim($_POST['description'] ?? ''), ENT_QUOTES, 'UTF-8'),
                    'categorie_id' => (int)($_POST['categorie_id'] ?? 0),
                    'prix_achat' => (float)($_POST['prix_achat'] ?? 0),
                    'prix_vente' => (float)($_POST['prix_vente'] ?? 0),
                    'stock' => (int)($_POST['stock'] ?? 0),
                    'statut' => $_POST['statut'] ?? 'inactif',
                ];

                $errors = $this->validateProduit($data);

                if (!empty($errors)) {
                    $_SESSION['flash'] = ['type' => 'error', 'message' => implode('<br>', $errors)];
                    $_SESSION['old'] = $data;
                    header('Location: /admin/produits/create');
                    exit;
                }

                $id = $this->produitModel->create($data);

                if ($id) {
                    $this->logAction($_SESSION['user_id'], 'Création produit', "Produit #$id créé");
                    $_SESSION['flash'] = ['type' => 'success', 'message' => 'Produit créé avec succès.'];
                    header('Location: /admin/produits');
                } else {
                    throw new Exception('Erreur lors de la création.');
                }
                exit;
            } catch (Exception $e) {
                error_log('Erreur create - ' . $e->getMessage());
                $_SESSION['flash'] = ['type' => 'error', 'message' => 'Erreur lors de la création.'];
                header('Location: /admin/produits/create');
                exit;
            }
        }

        try {
            $csrfToken = $this->generateCsrfToken();
            $categories = $this->produitModel->getCategories();
            $old = $_SESSION['old'] ?? null;
            unset($_SESSION['old']);

            require_once __DIR__ . '/../views/backoffice/produit_form.php';
        } catch (Exception $e) {
            error_log('Erreur create form - ' . $e->getMessage());
            $_SESSION['flash'] = ['type' => 'error', 'message' => 'Erreur.'];
            header('Location: /admin/produits');
            exit;
        }
    }

    // ─────────────────────────────────────────
    //  Éditer un produit (admin)
    // ─────────────────────────────────────────
    public function edit(int $id): void {
        $this->auth->requireRole('admin');

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!$this->verifyCsrfToken($_POST['csrf_token'] ?? '')) {
                $_SESSION['flash'] = ['type' => 'error', 'message' => 'Erreur de sécurité.'];
                header("Location: /admin/produits/$id/edit");
                exit;
            }

            try {
                $data = [
                    'nom' => htmlspecialchars(trim($_POST['nom'] ?? ''), ENT_QUOTES, 'UTF-8'),
                    'description' => htmlspecialchars(trim($_POST['description'] ?? ''), ENT_QUOTES, 'UTF-8'),
                    'categorie_id' => (int)($_POST['categorie_id'] ?? 0),
                    'prix_achat' => (float)($_POST['prix_achat'] ?? 0),
                    'prix_vente' => (float)($_POST['prix_vente'] ?? 0),
                    'stock' => (int)($_POST['stock'] ?? 0),
                    'statut' => $_POST['statut'] ?? 'inactif',
                ];

                $errors = $this->validateProduit($data);

                if (!empty($errors)) {
                    $_SESSION['flash'] = ['type' => 'error', 'message' => implode('<br>', $errors)];
                    $_SESSION['old'] = $data;
                    header("Location: /admin/produits/$id/edit");
                    exit;
                }

                if ($this->produitModel->update($id, $data)) {
                    $this->logAction($_SESSION['user_id'], 'Modification produit', "Produit #$id modifié");
                    $_SESSION['flash'] = ['type' => 'success', 'message' => 'Produit mis à jour.'];
                    header("Location: /admin/produits/$id/edit");
                } else {
                    throw new Exception('Erreur lors de la mise à jour.');
                }
                exit;
            } catch (Exception $e) {
                error_log('Erreur edit - ' . $e->getMessage());
                $_SESSION['flash'] = ['type' => 'error', 'message' => 'Erreur lors de la mise à jour.'];
                header("Location: /admin/produits/$id/edit");
                exit;
            }
        }

        try {
            $produit = $this->produitModel->getById($id);

            if (!$produit) {
                http_response_code(404);
                die('Produit introuvable.');
            }

            $csrfToken = $this->generateCsrfToken();
            $categories = $this->produitModel->getCategories();
            $old = $_SESSION['old'] ?? null;
            unset($_SESSION['old']);

            require_once __DIR__ . '/../views/backoffice/produit_form_edit.php';
        } catch (Exception $e) {
            error_log('Erreur edit form - ' . $e->getMessage());
            $_SESSION['flash'] = ['type' => 'error', 'message' => 'Erreur.'];
            header('Location: /admin/produits');
            exit;
        }
    }

    // ─────────────────────────────────────────
    //  Supprimer un produit (admin)
    // ─────────────────────────────────────────
    public function delete(int $id): void {
        $this->auth->requireRole('admin');

        try {
            $produit = $this->produitModel->getById($id);

            if (!$produit) {
                http_response_code(404);
                die('Produit introuvable.');
            }

            if ($this->produitModel->delete($id)) {
                $this->logAction($_SESSION['user_id'], 'Suppression produit', "Produit #$id supprimé");
                $_SESSION['flash'] = ['type' => 'success', 'message' => 'Produit supprimé.'];
                header('Location: /admin/produits');
            } else {
                throw new Exception('Erreur lors de la suppression.');
            }
            exit;
        } catch (Exception $e) {
            error_log('Erreur delete - ' . $e->getMessage());
            $_SESSION['flash'] = ['type' => 'error', 'message' => 'Erreur lors de la suppression.'];
            header('Location: /admin/produits');
            exit;
        }
    }

    // ─────────────────────────────────────────
    //  Ajouter un avis (client)
    // ─────────────────────────────────────────
    public function addAvis(int $produitId): void {
        $this->auth->requireRole('client');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            die('Méthode non autorisée.');
        }

        try {
            $note = (int)($_POST['note'] ?? 0);
            $commentaire = htmlspecialchars(trim($_POST['commentaire'] ?? ''), ENT_QUOTES, 'UTF-8');

            if ($note < 1 || $note > 5) {
                echo json_encode(['error' => 'Note invalide (1-5).']);
                exit;
            }

            if (strlen($commentaire) < 10) {
                echo json_encode(['error' => 'Le commentaire doit contenir au least 10 caractères.']);
                exit;
            }

            $userId = $_SESSION['user_id'];

            if ($this->produitModel->addAvis($produitId, $userId, $note, $commentaire)) {
                echo json_encode(['success' => true, 'message' => 'Avis ajouté.']);
            } else {
                echo json_encode(['error' => 'Erreur lors de l\'ajout.']);
            }
            exit;
        } catch (Exception $e) {
            error_log('Erreur addAvis - ' . $e->getMessage());
            echo json_encode(['error' => 'Erreur serveur']);
            exit;
        }
    }

    // ─────────────────────────────────────────
    //  Helpers privés
    // ─────────────────────────────────────────
    private function validateProduit(array $data): array {
        $errors = [];

        if (empty($data['nom']) || strlen($data['nom']) < 3) {
            $errors[] = 'Le nom doit contenir au moins 3 caractères.';
        }

        if (strlen($data['nom']) > 255) {
            $errors[] = 'Le nom dépasse 255 caractères.';
        }

        if (empty($data['description']) || strlen($data['description']) < 10) {
            $errors[] = 'La description doit contenir au moins 10 caractères.';
        }

        if (empty($data['categorie_id'])) {
            $errors[] = 'Une catégorie doit être sélectionnée.';
        }

        if ($data['prix_achat'] < 0) {
            $errors[] = 'Le prix d\'achat ne peut pas être négatif.';
        }

        if ($data['prix_vente'] <= 0) {
            $errors[] = 'Le prix de vente doit être supérieur à 0.';
        }

        if ($data['prix_vente'] < $data['prix_achat']) {
            $errors[] = 'Le prix de vente doit être supérieur au prix d\'achat.';
        }

        if ($data['stock'] < 0) {
            $errors[] = 'Le stock ne peut pas être négatif.';
        }

        return $errors;
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