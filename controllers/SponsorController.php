<?php
require_once __DIR__ . '/../models/Sponsor.php';
require_once __DIR__ . '/../config/Validator.php';

class SponsorController {
    private Sponsor $model;

    public function __construct() {
        $this->model = new Sponsor();
    }

    // ─── BackOffice ────────────────────────────────────────────────────

    /** Liste tous les sponsors (backoffice) */
    public function index(): void {
        $sponsors = $this->model->findAll();
        require __DIR__ . '/../views/backoffice/sponsor/index.php';
    }

    /** Affiche le formulaire de création */
    public function create(): void {
        $errors = $_SESSION['errors'] ?? [];
        $old    = $_SESSION['old']    ?? [];
        unset($_SESSION['errors'], $_SESSION['old']);
        require __DIR__ . '/../views/backoffice/sponsor/create.php';
    }

    /** Traite la soumission du formulaire de création */
    public function store(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: index.php?page=sponsors_admin&action=create');
            exit;
        }

        $data = [
            'nom'       => trim($_POST['nom']       ?? ''),
            'email'     => trim($_POST['email']     ?? ''),
            'telephone' => trim($_POST['telephone'] ?? ''),
            'site_web'  => trim($_POST['site_web']  ?? ''),
            'niveau'    => trim($_POST['niveau']    ?? ''),
            'montant'   => trim($_POST['montant']   ?? ''),
        ];

        // ========== VALIDATIONS SERVEUR ==========
        $validator = new Validator();
        $validator->required('nom', $data['nom'], 'Nom')
                  ->minLength('nom', $data['nom'], 2, 'Nom')
                  ->maxLength('nom', $data['nom'], 100, 'Nom')
                  ->required('email', $data['email'], 'Email')
                  ->email('email', $data['email'], 'Email')
                  ->required('telephone', $data['telephone'], 'Téléphone')
                  ->numeric('telephone', $data['telephone'], 'Téléphone')
                  ->minLength('telephone', $data['telephone'], 10, 'Téléphone')
                  ->required('niveau', $data['niveau'], 'Niveau')
                  ->inArray('niveau', $data['niveau'], ['bronze', 'argent', 'or', 'platine'], 'Niveau')
                  ->required('montant', $data['montant'], 'Montant')
                  ->positiveNumber('montant', $data['montant'], 'Montant');

        // Site web optionnel mais valide s'il est fourni
        if (!empty($data['site_web'])) {
            $validator->url('site_web', $data['site_web'], 'Site web');
        }

        $errors = $validator->getErrors();

        // ========== VÉRIFICATION UNICITÉ EMAIL ==========
        if (empty($errors['email']) && $this->model->emailExists($data['email'])) {
            $errors['email'] = "Cet email est déjà utilisé par un autre sponsor.";
        }

        // ========== STOCKAGE ERREURS ET REDIRECTION ==========
        if (!empty($errors)) {
            $_SESSION['errors'] = $errors;
            $_SESSION['old']    = $data;
            header('Location: index.php?page=sponsors_admin&action=create');
            exit;
        }

        // ========== CRÉATION SPONSOR ==========
        $this->model->create($data);
        $_SESSION['success'] = "Sponsor créé avec succès.";
        header('Location: index.php?page=sponsors_admin');
        exit;
    }

    /** Affiche le formulaire d'édition */
    public function edit(): void {
        $id      = (int)($_GET['id'] ?? 0);
        $sponsor = $this->model->findById($id);
        if (!$sponsor) {
            $this->notFound();
            return;
        }
        $errors = $_SESSION['errors'] ?? [];
        $old    = $_SESSION['old']    ?? $sponsor;
        unset($_SESSION['errors'], $_SESSION['old']);
        require __DIR__ . '/../views/backoffice/sponsor/edit.php';
    }

    /** Traite la soumission du formulaire d'édition */
    public function update(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: index.php?page=sponsors_admin');
            exit;
        }

        $id      = (int)($_POST['id'] ?? 0);
        $sponsor = $this->model->findById($id);
        if (!$sponsor) {
            $this->notFound();
            return;
        }

        $data = [
            'nom'       => trim($_POST['nom']       ?? ''),
            'email'     => trim($_POST['email']     ?? ''),
            'telephone' => trim($_POST['telephone'] ?? ''),
            'site_web'  => trim($_POST['site_web']  ?? ''),
            'niveau'    => trim($_POST['niveau']    ?? ''),
            'montant'   => trim($_POST['montant']   ?? ''),
        ];

        // ========== VALIDATIONS SERVEUR ==========
        $validator = new Validator();
        $validator->required('nom', $data['nom'], 'Nom')
                  ->minLength('nom', $data['nom'], 2, 'Nom')
                  ->maxLength('nom', $data['nom'], 100, 'Nom')
                  ->required('email', $data['email'], 'Email')
                  ->email('email', $data['email'], 'Email')
                  ->required('telephone', $data['telephone'], 'Téléphone')
                  ->numeric('telephone', $data['telephone'], 'Téléphone')
                  ->minLength('telephone', $data['telephone'], 10, 'Téléphone')
                  ->required('niveau', $data['niveau'], 'Niveau')
                  ->inArray('niveau', $data['niveau'], ['bronze', 'argent', 'or', 'platine'], 'Niveau')
                  ->required('montant', $data['montant'], 'Montant')
                  ->positiveNumber('montant', $data['montant'], 'Montant');

        // Site web optionnel mais valide s'il est fourni
        if (!empty($data['site_web'])) {
            $validator->url('site_web', $data['site_web'], 'Site web');
        }

        $errors = $validator->getErrors();

        // ========== VÉRIFICATION UNICITÉ EMAIL ==========
        if (empty($errors['email']) && $this->model->emailExists($data['email'], $id)) {
            $errors['email'] = "Cet email est déjà utilisé par un autre sponsor.";
        }

        // ========== STOCKAGE ERREURS ET REDIRECTION ==========
        if (!empty($errors)) {
            $_SESSION['errors'] = $errors;
            $_SESSION['old']    = array_merge($sponsor, $data, ['id' => $id]);
            header('Location: index.php?page=sponsors_admin&action=edit&id=' . $id);
            exit;
        }

        // ========== MISE À JOUR SPONSOR ==========
        $this->model->update($id, $data);
        $_SESSION['success'] = "Sponsor mis à jour avec succès.";
        header('Location: index.php?page=sponsors_admin');
        exit;
    }

    /** Supprime un sponsor */
    public function delete(): void {
        $id = (int)($_GET['id'] ?? 0);
        if ($this->model->findById($id)) {
            // Vérifier s'il a des événements liés
            if ($this->model->countEvenements($id) > 0) {
                $_SESSION['error'] = "Ce sponsor a des événements liés. Impossible de le supprimer.";
                header('Location: index.php?page=sponsors_admin');
                exit;
            }
            $this->model->delete($id);
            $_SESSION['success'] = "Sponsor supprimé avec succès.";
        }
        header('Location: index.php?page=sponsors_admin');
        exit;
    }

    // ─── FrontOffice ───────────────────────────────────────────────────

    /** Liste des sponsors (frontoffice) */
    public function list(): void {
        $sponsors = $this->model->findAll();
        require __DIR__ . '/../views/frontoffice/sponsors.php';
    }

    private function notFound(): void {
        http_response_code(404);
        echo "<h1>404 – Sponsor introuvable</h1>";
    }
}
