<?php
// controllers/CategorieController.php

require_once __DIR__ . '/../models/Categorie.php';
require_once __DIR__ . '/../models/Produit.php';
require_once __DIR__ . '/../config/database.php';

use App\Models\Categorie;
use App\Models\Produit;

class CategorieController {

    private Categorie $categorieModel;
    private Produit   $produitModel;

    public function __construct() {
        $this->categorieModel = new Categorie();
        $this->produitModel   = new Produit();
    }

    // ─────────────────────────────────────────
    //  ADMIN — liste
    // ─────────────────────────────────────────
    public function index(): void {
        $this->adminOnly();

        $search     = $_GET['search'] ?? '';
        $categories = $this->categorieModel->getAll($search);
        $stats      = $this->categorieModel->getStats();
        $flash      = $_SESSION['flash'] ?? null;
        unset($_SESSION['flash']);

        require_once __DIR__ . '/../views/backoffice/categorie_manage.php';
    }

    // ─────────────────────────────────────────
    //  ADMIN — créer
    // ─────────────────────────────────────────
    public function create(): void {
        $this->adminOnly();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!$this->verifyCsrf($_POST['csrf_token'] ?? '')) {
                $_SESSION['flash'] = ['type' => 'error', 'message' => 'Erreur de sécurité.'];
                header('Location: index.php?page=categories_admin&action=create');
                exit;
            }

            $nom      = trim($_POST['nom'] ?? '');
            $slug     = $this->makeSlug($nom);
            $desc     = trim($_POST['description'] ?? '');
            $image    = trim($_POST['image'] ?? '');
            $parentId = (int)($_POST['parent_id'] ?? 0) ?: null;
            $statut   = in_array($_POST['statut'] ?? '', ['actif','inactif']) ? $_POST['statut'] : 'actif';

            $errors = $this->validate($nom, $slug);

            if (!empty($errors)) {
                $_SESSION['flash'] = ['type' => 'error', 'message' => implode('<br>', $errors)];
                $_SESSION['old']   = $_POST;
                header('Location: index.php?page=categories_admin&action=create');
                exit;
            }

            $data = [
                'nom'         => htmlspecialchars($nom, ENT_QUOTES, 'UTF-8'),
                'slug'        => $slug,
                'description' => htmlspecialchars($desc, ENT_QUOTES, 'UTF-8'),
                'image'       => $image,
                'parent_id'   => $parentId,
            ];

            $id = $this->categorieModel->create($data);
            if ($id) {
                $_SESSION['flash'] = ['type' => 'success', 'message' => 'Catégorie créée avec succès.'];
                header('Location: index.php?page=categories_admin');
            } else {
                $_SESSION['flash'] = ['type' => 'error', 'message' => 'Erreur lors de la création.'];
                header('Location: index.php?page=categories_admin&action=create');
            }
            exit;
        }

        $isEdit     = false;
        $categorie  = [];
        $csrfToken  = $this->makeCsrf();
        $categories = $this->categorieModel->getActives();
        $old        = $_SESSION['old'] ?? [];
        $flash      = $_SESSION['flash'] ?? null;
        unset($_SESSION['old'], $_SESSION['flash']);

        require_once __DIR__ . '/../views/backoffice/categorie_form.php';
    }

    // ─────────────────────────────────────────
    //  ADMIN — éditer
    // ─────────────────────────────────────────
    public function edit(int $id): void {
        $this->adminOnly();

        $categorie = $this->categorieModel->getById($id);
        if (!$categorie) {
            $_SESSION['flash'] = ['type' => 'error', 'message' => 'Catégorie introuvable.'];
            header('Location: index.php?page=categories_admin');
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!$this->verifyCsrf($_POST['csrf_token'] ?? '')) {
                $_SESSION['flash'] = ['type' => 'error', 'message' => 'Erreur de sécurité.'];
                header("Location: index.php?page=categories_admin&action=edit&id=$id");
                exit;
            }

            $nom      = trim($_POST['nom'] ?? '');
            $slug     = $this->makeSlug($nom);
            $desc     = trim($_POST['description'] ?? '');
            $image    = trim($_POST['image'] ?? '');
            $parentId = (int)($_POST['parent_id'] ?? 0) ?: null;
            $statut   = in_array($_POST['statut'] ?? '', ['actif','inactif']) ? $_POST['statut'] : 'actif';

            if ($parentId === $id) $parentId = null; // pas d'auto-parent

            $errors = $this->validate($nom, $slug, $id);

            if (!empty($errors)) {
                $_SESSION['flash'] = ['type' => 'error', 'message' => implode('<br>', $errors)];
                $_SESSION['old']   = $_POST;
                header("Location: index.php?page=categories_admin&action=edit&id=$id");
                exit;
            }

            $data = [
                'nom'         => htmlspecialchars($nom, ENT_QUOTES, 'UTF-8'),
                'slug'        => $slug,
                'description' => htmlspecialchars($desc, ENT_QUOTES, 'UTF-8'),
                'image'       => $image,
                'parent_id'   => $parentId,
            ];

            if ($this->categorieModel->update($id, $data)) {
                $_SESSION['flash'] = ['type' => 'success', 'message' => 'Catégorie mise à jour.'];
            } else {
                $_SESSION['flash'] = ['type' => 'error', 'message' => 'Erreur lors de la mise à jour.'];
            }
            header("Location: index.php?page=categories_admin&action=edit&id=$id");
            exit;
        }

        $isEdit     = true;
        $csrfToken  = $this->makeCsrf();
        $categories = array_filter(
            $this->categorieModel->getActives(),
            fn($c) => (int)$c['id'] !== $id   // exclure la catégorie elle-même
        );
        $old   = $_SESSION['old'] ?? [];
        $flash = $_SESSION['flash'] ?? null;
        unset($_SESSION['old'], $_SESSION['flash']);

        require_once __DIR__ . '/../views/backoffice/categorie_form.php';
    }

    // ─────────────────────────────────────────
    //  ADMIN — supprimer
    // ─────────────────────────────────────────
    public function delete(int $id): void {
        $this->adminOnly();

        if ($this->categorieModel->delete($id)) {
            $_SESSION['flash'] = ['type' => 'success', 'message' => 'Catégorie supprimée.'];
        } else {
            $_SESSION['flash'] = ['type' => 'error', 'message' => 'Impossible de supprimer : des produits sont liés à cette catégorie.'];
        }
        header('Location: index.php?page=categories_admin');
        exit;
    }

    // ─────────────────────────────────────────
    //  Méthodes de service (existantes)
    // ─────────────────────────────────────────
    public function afficherProduits(int $idCategorie): array {
        if ($idCategorie <= 0) return [];
        $categorie = $this->categorieModel->getById($idCategorie);
        if (!$categorie) return [];
        return $this->produitModel->getProduitsByCategorie($idCategorie);
    }

    public function afficherCategories(): array {
        try {
            return $this->produitModel->getAllCategories();
        } catch (Exception $e) {
            error_log('CategorieController::afficherCategories - ' . $e->getMessage());
            return [];
        }
    }

    public function afficherCategorie(int $id): ?array {
        return $id > 0 ? $this->categorieModel->getById($id) : null;
    }

    // ─────────────────────────────────────────
    //  Helpers privés
    // ─────────────────────────────────────────
    private function validate(string $nom, string $slug, int $excludeId = 0): array {
        $errors = [];
        if (empty($nom) || strlen($nom) < 2)
            $errors[] = 'Le nom doit contenir au moins 2 caractères.';
        if (strlen($nom) > 100)
            $errors[] = 'Le nom ne peut pas dépasser 100 caractères.';
        if ($this->categorieModel->slugExists($slug, $excludeId))
            $errors[] = "Une catégorie avec ce nom (slug: «$slug») existe déjà.";
        return $errors;
    }

    private function makeSlug(string $text): string {
        $text = strtolower(trim($text));
        $map  = ['é'=>'e','è'=>'e','ê'=>'e','ë'=>'e','à'=>'a','â'=>'a','ù'=>'u','û'=>'u',
                  'î'=>'i','ï'=>'i','ô'=>'o','œ'=>'oe','æ'=>'ae','ç'=>'c'];
        $text = strtr($text, $map);
        $text = preg_replace('/[^a-z0-9]+/', '-', $text);
        return trim($text, '-');
    }

    private function makeCsrf(): string {
        if (empty($_SESSION['csrf_token']))
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        return $_SESSION['csrf_token'];
    }

    private function verifyCsrf(string $token): bool {
        return !empty($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
    }

    private function adminOnly(): void {
        if (($_SESSION['user_role'] ?? '') !== 'admin') {
            header('Location: index.php?page=login');
            exit;
        }
    }
}
?>