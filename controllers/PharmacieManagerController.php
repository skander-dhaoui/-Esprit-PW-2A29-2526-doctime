<?php
// controllers/PharmacieController.php - Fusion User+Event+Pharmacie

require_once __DIR__ . '/../models/Pharmacie.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/Evenement.php';
require_once __DIR__ . '/../config/database.php';

class PharmacieController {
    private Pharmacie $pharmacieModel;
    private Evenement $evenementModel;
    private PDO $pdo;

    public function __construct() {
        $this->pharmacieModel = new Pharmacie();
        $this->evenementModel = new Evenement();
        $this->pdo = Database::getInstance()->getConnection();
    }

    /**
     * Page d'accueil - Liste des pharmacies avec fusion user+event
     */
    public function dashboard(): void {
        $pharmacies = $this->pharmacieModel->findAll();
        
        // Ajouter les données fusionnées pour chaque pharmacie
        foreach ($pharmacies as &$pharmacie) {
            $pharmacie['utilisateurs'] = $this->pharmacieModel->getUtilisateurs($pharmacie['id']);
            $pharmacie['evenements'] = $this->pharmacieModel->getEvenements($pharmacie['id']);
            $pharmacie['stats'] = $this->pharmacieModel->getStats($pharmacie['id']);
        }

        $flash = $_SESSION['flash'] ?? null;
        unset($_SESSION['flash']);

        require __DIR__ . '/../views/backoffice/pharmacies/dashboard.php';
    }

    /**
     * Liste toutes les pharmacies avec filtres
     */
    public function list(): void {
        $search = trim($_GET['search'] ?? '');
        $ville = $_GET['ville'] ?? '';
        $statut = $_GET['statut'] ?? '';

        $filters = [];
        if (!empty($search)) $filters['search'] = $search;
        if (!empty($ville)) $filters['ville'] = $ville;
        if (!empty($statut)) $filters['statut'] = $statut;

        $pharmacies = $this->pharmacieModel->findAll($filters);
        
        // Ajouter fusion user+event pour chaque pharmacie
        foreach ($pharmacies as &$pharmacie) {
            $pharmacie['utilisateurs'] = $this->pharmacieModel->getUtilisateurs($pharmacie['id']);
            $pharmacie['evenements'] = $this->pharmacieModel->getEvenements($pharmacie['id']);
        }

        $villes = $this->getVillesUniques();
        $flash = $_SESSION['flash'] ?? null;
        unset($_SESSION['flash']);

        require __DIR__ . '/../views/backoffice/pharmacies/list.php';
    }

    /**
     * Affiche les détails d'une pharmacie avec ses utilisateurs et événements
     */
    public function show(): void {
        $id = (int)($_GET['id'] ?? 0);
        if ($id <= 0) {
            http_response_code(404);
            die('Pharmacie non trouvée');
        }

        $pharmacie = $this->pharmacieModel->findById($id);
        if (!$pharmacie) {
            http_response_code(404);
            die('Pharmacie non trouvée');
        }

        // Fusion complète : utilisateurs + événements + produits
        $pharmacie['utilisateurs'] = $this->pharmacieModel->getUtilisateurs($id);
        $pharmacie['evenements'] = $this->pharmacieModel->getEvenements($id);
        $pharmacie['produits'] = $this->pharmacieModel->getProduits($id);
        $pharmacie['stats'] = $this->pharmacieModel->getStats($id);

        $flash = $_SESSION['flash'] ?? null;
        unset($_SESSION['flash']);

        require __DIR__ . '/../views/backoffice/pharmacies/show.php';
    }

    /**
     * Affiche le formulaire de création de pharmacie
     */
    public function create(): void {
        $utilisateurs = $this->getUtilisateurs();
        $flash = $_SESSION['flash'] ?? null;
        unset($_SESSION['flash']);

        require __DIR__ . '/../views/backoffice/pharmacies/form.php';
    }

    /**
     * Crée une nouvelle pharmacie
     */
    public function store(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: index.php?page=pharmacies_admin');
            exit;
        }

        $errors = $this->validatePharmacie($_POST);
        if (!empty($errors)) {
            $_SESSION['flash'] = ['type' => 'error', 'message' => implode(' | ', $errors)];
            header('Location: index.php?page=pharmacies_admin&action=create');
            exit;
        }

        $data = [
            'nom'                => htmlspecialchars(trim($_POST['nom'])),
            'slug'               => trim($_POST['slug'] ?? $this->generateSlug($_POST['nom'])),
            'description'        => htmlspecialchars(trim($_POST['description'] ?? '')),
            'adresse'            => htmlspecialchars(trim($_POST['adresse'])),
            'ville'              => htmlspecialchars(trim($_POST['ville'])),
            'code_postal'        => htmlspecialchars(trim($_POST['code_postal'] ?? '')),
            'telephone'          => htmlspecialchars(trim($_POST['telephone'])),
            'email'              => filter_var($_POST['email'], FILTER_VALIDATE_EMAIL),
            'site_web'           => htmlspecialchars(trim($_POST['site_web'] ?? '')),
            'responsable_id'     => !empty($_POST['responsable_id']) ? (int)$_POST['responsable_id'] : null,
            'horaires_ouverture' => $_POST['horaires_ouverture'] ?? null,
            'gerant_nom'         => htmlspecialchars(trim($_POST['gerant_nom'] ?? '')),
            'gerant_prenom'      => htmlspecialchars(trim($_POST['gerant_prenom'] ?? '')),
            'gerant_telephone'   => htmlspecialchars(trim($_POST['gerant_telephone'] ?? '')),
            'latitude'           => !empty($_POST['latitude']) ? (float)$_POST['latitude'] : null,
            'longitude'          => !empty($_POST['longitude']) ? (float)$_POST['longitude'] : null,
            'image'              => $_POST['image'] ?? null,
            'statut'             => $_POST['statut'] ?? 'actif'
        ];

        $id = $this->pharmacieModel->create($data);
        if ($id) {
            $_SESSION['flash'] = ['type' => 'success', 'message' => 'Pharmacie créée avec succès.'];
            header("Location: index.php?page=pharmacies_admin&action=show&id=$id");
        } else {
            $_SESSION['flash'] = ['type' => 'error', 'message' => 'Erreur lors de la création.'];
            header('Location: index.php?page=pharmacies_admin&action=create');
        }
        exit;
    }

    /**
     * Affiche le formulaire d'édition
     */
    public function edit(): void {
        $id = (int)($_GET['id'] ?? 0);
        if ($id <= 0) {
            header('Location: index.php?page=pharmacies_admin');
            exit;
        }

        $pharmacie = $this->pharmacieModel->findById($id);
        if (!$pharmacie) {
            http_response_code(404);
            die('Pharmacie non trouvée');
        }

        $utilisateurs = $this->getUtilisateurs();
        $flash = $_SESSION['flash'] ?? null;
        unset($_SESSION['flash']);

        require __DIR__ . '/../views/backoffice/pharmacies/form.php';
    }

    /**
     * Met à jour une pharmacie
     */
    public function update(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: index.php?page=pharmacies_admin');
            exit;
        }

        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) {
            header('Location: index.php?page=pharmacies_admin');
            exit;
        }

        $errors = $this->validatePharmacie($_POST, $id);
        if (!empty($errors)) {
            $_SESSION['flash'] = ['type' => 'error', 'message' => implode(' | ', $errors)];
            header("Location: index.php?page=pharmacies_admin&action=edit&id=$id");
            exit;
        }

        $data = [
            'nom'                => htmlspecialchars(trim($_POST['nom'])),
            'slug'               => trim($_POST['slug']),
            'description'        => htmlspecialchars(trim($_POST['description'] ?? '')),
            'adresse'            => htmlspecialchars(trim($_POST['adresse'])),
            'ville'              => htmlspecialchars(trim($_POST['ville'])),
            'code_postal'        => htmlspecialchars(trim($_POST['code_postal'] ?? '')),
            'telephone'          => htmlspecialchars(trim($_POST['telephone'])),
            'email'              => filter_var($_POST['email'], FILTER_VALIDATE_EMAIL),
            'site_web'           => htmlspecialchars(trim($_POST['site_web'] ?? '')),
            'responsable_id'     => !empty($_POST['responsable_id']) ? (int)$_POST['responsable_id'] : null,
            'horaires_ouverture' => $_POST['horaires_ouverture'] ?? null,
            'gerant_nom'         => htmlspecialchars(trim($_POST['gerant_nom'] ?? '')),
            'gerant_prenom'      => htmlspecialchars(trim($_POST['gerant_prenom'] ?? '')),
            'gerant_telephone'   => htmlspecialchars(trim($_POST['gerant_telephone'] ?? '')),
            'latitude'           => !empty($_POST['latitude']) ? (float)$_POST['latitude'] : null,
            'longitude'          => !empty($_POST['longitude']) ? (float)$_POST['longitude'] : null,
            'image'              => $_POST['image'] ?? null,
            'statut'             => $_POST['statut'] ?? 'actif'
        ];

        if ($this->pharmacieModel->update($id, $data)) {
            $_SESSION['flash'] = ['type' => 'success', 'message' => 'Pharmacie mise à jour avec succès.'];
            header("Location: index.php?page=pharmacies_admin&action=show&id=$id");
        } else {
            $_SESSION['flash'] = ['type' => 'error', 'message' => 'Erreur lors de la mise à jour.'];
            header("Location: index.php?page=pharmacies_admin&action=edit&id=$id");
        }
        exit;
    }

    /**
     * Supprime une pharmacie
     */
    public function delete(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            die('Méthode non autorisée');
        }

        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) {
            $_SESSION['flash'] = ['type' => 'error', 'message' => 'ID invalide.'];
            header('Location: index.php?page=pharmacies_admin');
            exit;
        }

        if ($this->pharmacieModel->delete($id)) {
            $_SESSION['flash'] = ['type' => 'success', 'message' => 'Pharmacie supprimée avec succès.'];
        } else {
            $_SESSION['flash'] = ['type' => 'error', 'message' => 'Erreur lors de la suppression.'];
        }

        header('Location: index.php?page=pharmacies_admin');
        exit;
    }

    /**
     * Ajoute un utilisateur à une pharmacie
     */
    public function addUser(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            die('Méthode non autorisée');
        }

        $pharmacieId = (int)($_POST['pharmacie_id'] ?? 0);
        $userId = (int)($_POST['user_id'] ?? 0);
        $role = trim($_POST['role'] ?? 'employe');

        if ($pharmacieId <= 0 || $userId <= 0) {
            $_SESSION['flash'] = ['type' => 'error', 'message' => 'Données invalides.'];
            header("Location: index.php?page=pharmacies_admin&action=show&id=$pharmacieId");
            exit;
        }

        if ($this->pharmacieModel->addUtilisateur($pharmacieId, $userId, $role)) {
            $_SESSION['flash'] = ['type' => 'success', 'message' => 'Utilisateur ajouté à la pharmacie.'];
        } else {
            $_SESSION['flash'] = ['type' => 'error', 'message' => 'Erreur lors de l\'ajout.'];
        }

        header("Location: index.php?page=pharmacies_admin&action=show&id=$pharmacieId");
        exit;
    }

    /**
     * Retire un utilisateur d'une pharmacie
     */
    public function removeUser(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            die('Méthode non autorisée');
        }

        $pharmacieId = (int)($_POST['pharmacie_id'] ?? 0);
        $userId = (int)($_POST['user_id'] ?? 0);

        if ($pharmacieId <= 0 || $userId <= 0) {
            $_SESSION['flash'] = ['type' => 'error', 'message' => 'Données invalides.'];
            header("Location: index.php?page=pharmacies_admin&action=show&id=$pharmacieId");
            exit;
        }

        if ($this->pharmacieModel->removeUtilisateur($pharmacieId, $userId)) {
            $_SESSION['flash'] = ['type' => 'success', 'message' => 'Utilisateur retiré de la pharmacie.'];
        } else {
            $_SESSION['flash'] = ['type' => 'error', 'message' => 'Erreur lors de la suppression.'];
        }

        header("Location: index.php?page=pharmacies_admin&action=show&id=$pharmacieId");
        exit;
    }

    /**
     * Valide les données d'une pharmacie
     */
    private function validatePharmacie(array $data, ?int $excludeId = null): array {
        $errors = [];

        if (empty(trim($data['nom'] ?? ''))) {
            $errors[] = 'Le nom de la pharmacie est requis.';
        }

        if (empty(trim($data['adresse'] ?? ''))) {
            $errors[] = 'L\'adresse est requise.';
        }

        if (empty(trim($data['ville'] ?? ''))) {
            $errors[] = 'La ville est requise.';
        }

        if (empty(trim($data['telephone'] ?? ''))) {
            $errors[] = 'Le téléphone est requis.';
        } elseif (!preg_match('/^[\d\s\-\+\(\)]+$/', $data['telephone'])) {
            $errors[] = 'Le téléphone n\'est pas valide.';
        }

        if (!empty($data['email'])) {
            if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                $errors[] = 'L\'email n\'est pas valide.';
            } elseif ($this->pharmacieModel->emailExists($data['email'], $excludeId)) {
                $errors[] = 'Cet email est déjà utilisé.';
            }
        }

        $slug = trim($data['slug'] ?? $this->generateSlug($data['nom'] ?? ''));
        if ($this->pharmacieModel->slugExists($slug, $excludeId)) {
            $errors[] = 'Le slug est déjà utilisé.';
        }

        return $errors;
    }

    /**
     * Génère un slug
     */
    private function generateSlug(string $text): string {
        $text = strtolower(trim($text));
        $text = preg_replace('/[^a-z0-9]+/', '-', $text);
        return trim($text, '-');
    }

    /**
     * Récupère tous les utilisateurs pour le formulaire
     */
    private function getUtilisateurs(): array {
        $stmt = $this->pdo->query("SELECT id, nom, prenom, email FROM users ORDER BY nom, prenom");
        return $stmt->fetchAll();
    }

    /**
     * Récupère les villes uniques
     */
    private function getVillesUniques(): array {
        $stmt = $this->pdo->query("SELECT DISTINCT ville FROM pharmacies WHERE ville IS NOT NULL ORDER BY ville");
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }
}
?>
