<?php
// controllers/CategorieController.php

require_once __DIR__ . '/../models/Categorie.php';
require_once __DIR__ . '/../models/Produit.php';
require_once __DIR__ . '/../repositories/CategorieRepository.php';
require_once __DIR__ . '/../config/database.php';

use App\Repositories\CategorieRepository;

class CategorieController {

    private CategorieRepository $categorieRepo;
    private Produit   $produitModel;

    public function __construct() {
        $this->categorieRepo = new CategorieRepository();
        $this->produitModel   = new Produit();
    }

    // ─────────────────────────────────────────
    //  ADMIN — liste
    // ─────────────────────────────────────────
    public function index(): void {
        $this->adminOnly();

        $search     = $_GET['search'] ?? '';
        $categories = $this->categorieRepo->getAll($search);
        $stats      = $this->categorieRepo->getStats();
        $flash      = $_SESSION['flash'] ?? null;
        unset($_SESSION['flash']);

        require_once __DIR__ . '/../views/backoffice/pharmacie/categories_list.php';
    }

    // ─────────────────────────────────────────
    //  ADMIN — créer
    // ─────────────────────────────────────────
    public function create(): void {
        $this->adminOnly();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!$this->verifyCsrf($_POST['csrf_token'] ?? '')) {
                $_SESSION['flash'] = ['type' => 'error', 'message' => 'Token CSRF invalide.'];
                header('Location: index.php?page=categories_admin&action=create');
                exit;
            }

            $nom         = trim($_POST['nom'] ?? '');
            $description = trim($_POST['description'] ?? '');
            $parentId    = !empty($_POST['parent_id']) ? (int)$_POST['parent_id'] : null;
            $slug        = $this->slugify($nom);

            $errors = $this->validate($nom, $slug);
            if (!empty($errors)) {
                $_SESSION['flash'] = ['type' => 'error', 'message' => implode('<br>', $errors)];
                $_SESSION['old']   = $_POST;
                header('Location: index.php?page=categories_admin&action=create');
                exit;
            }

            $data = [
                'nom'         => $nom,
                'slug'        => $slug,
                'description' => $description,
                'parent_id'   => $parentId,
            ];

            $id = $this->categorieRepo->create($data);
            if ($id) {
                $_SESSION['flash'] = ['type' => 'success', 'message' => 'Catégorie créée avec succès.'];
                header('Location: index.php?page=categories_admin');
                exit;
            }
        }

        $isEdit     = false;
        $categorie  = [];
        $csrfToken  = $this->makeCsrf();
        $categories = $this->categorieRepo->getActives();
        $old        = $_SESSION['old'] ?? [];
        $flash      = $_SESSION['flash'] ?? null;
        unset($_SESSION['old'], $_SESSION['flash']);

        require_once __DIR__ . '/../views/backoffice/pharmacie/categorie_form.php';
    }

    // ─────────────────────────────────────────
    //  ADMIN — modifier
    // ─────────────────────────────────────────
    public function edit(int $id): void {
        $this->adminOnly();

        $categorie = $this->categorieRepo->getById($id);
        if (!$categorie) {
            $_SESSION['flash'] = ['type' => 'error', 'message' => 'Catégorie introuvable.'];
            header('Location: index.php?page=categories_admin');
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!$this->verifyCsrf($_POST['csrf_token'] ?? '')) {
                $_SESSION['flash'] = ['type' => 'error', 'message' => 'Token CSRF invalide.'];
                header("Location: index.php?page=categories_admin&action=edit&id=$id");
                exit;
            }

            $nom         = trim($_POST['nom'] ?? '');
            $description = trim($_POST['description'] ?? '');
            $parentId    = !empty($_POST['parent_id']) ? (int)$_POST['parent_id'] : null;
            $slug        = $this->slugify($nom);

            $errors = $this->validate($nom, $slug, $id);
            if (!empty($errors)) {
                $_SESSION['flash'] = ['type' => 'error', 'message' => implode('<br>', $errors)];
                header("Location: index.php?page=categories_admin&action=edit&id=$id");
                exit;
            }

            $data = [
                'nom'         => $nom,
                'slug'        => $slug,
                'description' => $description,
                'parent_id'   => $parentId,
            ];

            if ($this->categorieRepo->update($id, $data)) {
                $_SESSION['flash'] = ['type' => 'success', 'message' => 'Catégorie mise à jour.'];
            } else {
                $_SESSION['flash'] = ['type' => 'error', 'message' => 'Erreur lors de la mise à jour.'];
            }
            header('Location: index.php?page=categories_admin');
            exit;
        }

        $isEdit     = true;
        $csrfToken  = $this->makeCsrf();
        $categories = array_filter(
            $this->categorieRepo->getActives(),
            fn($c) => (int)$c['id'] !== $id   // exclure la catégorie elle-même
        );
        $old   = $_SESSION['old'] ?? [];
        $flash = $_SESSION['flash'] ?? null;
        unset($_SESSION['old'], $_SESSION['flash']);

        require_once __DIR__ . '/../views/backoffice/pharmacie/categorie_form.php';
    }

    // ─────────────────────────────────────────
    //  ADMIN — supprimer
    // ─────────────────────────────────────────
    public function delete(int $id): void {
        $this->adminOnly();

        if ($this->categorieRepo->delete($id)) {
            $_SESSION['flash'] = ['type' => 'success', 'message' => 'Catégorie supprimée.'];
        } else {
            $_SESSION['flash'] = ['type' => 'error', 'message' => 'Impossible de supprimer : des produits sont liés à cette catégorie.'];
        }
        header('Location: index.php?page=categories_admin');
        exit;
    }

    // ─────────────────────────────────────────
    //  FRONT — Affichage
    // ─────────────────────────────────────────
    public function afficherProduits(int $idCategorie): array {
        if ($idCategorie <= 0) return [];
        $categorie = $this->categorieRepo->getById($idCategorie);
        if (!$categorie) return [];
        return $this->produitModel->getProduitsByCategorie($idCategorie);
    }

    public function listerActives(): array {
        return $this->categorieRepo->getActives();
    }

    public function listerArborescence(): array {
        return $this->categorieRepo->getTree();
    }

    public function afficherCategorie(int $id): ?array {
        return $id > 0 ? $this->categorieRepo->getById($id) : null;
    }

    // ─────────────────────────────────────────
    //  HELPERS
    // ─────────────────────────────────────────
    private function validate(string $nom, string $slug, ?int $excludeId = null): array {
        $errors = [];
        if (empty($nom) || strlen($nom) < 2)
            $errors[] = 'Le nom doit contenir au moins 2 caractères.';
        if (strlen($nom) > 100)
            $errors[] = 'Le nom ne peut pas dépasser 100 caractères.';
        if ($this->categorieRepo->slugExists($slug, $excludeId))
            $errors[] = "Une catégorie avec ce nom (slug: «$slug») existe déjà.";
        return $errors;
    }

    private function slugify(string $text): string {
        $text = preg_replace('~[^\pL\d]+~u', '-', $text);
        $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);
        $text = preg_replace('~[^-\w]+~', '', $text);
        $text = trim($text, '-');
        $text = preg_replace('~-+~', '-', $text);
        $text = strtolower($text);
        return empty($text) ? 'n-a' : $text;
    }

    private function makeCsrf(): string {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
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