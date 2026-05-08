<?php
require_once __DIR__ . '/../model/Sponsor.php';
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
        require __DIR__ . '/../view/backoffice/sponsor/index.php';
    }

    /** Affiche le formulaire de création */
    public function create(): void {
        $errors = [];
        $old    = [];
        require __DIR__ . '/../view/backoffice/sponsor/create.php';
    }

    /** Traite la soumission du formulaire de création */
    public function store(): void {
        $data = [
            'nom'       => $_POST['nom']      ?? '',
            'email'     => $_POST['email']    ?? '',
            'telephone' => $_POST['telephone'] ?? '',
            'site_web'  => $_POST['site_web'] ?? '',
            'niveau'    => $_POST['niveau']   ?? '',
            'montant'   => $_POST['montant']  ?? '',
        ];

        $v = new Validator();
        $v->required('nom', $data['nom'], 'Nom')
          ->minLength('nom', $data['nom'], 2, 'Nom')
          ->maxLength('nom', $data['nom'], 100, 'Nom')
          ->required('email', $data['email'], 'Email')
          ->email('email', $data['email'], 'Email')
          ->required('telephone', $data['telephone'], 'Téléphone')
          ->phone('telephone', $data['telephone'], 'Téléphone')
          ->url('site_web', $data['site_web'], 'Site web')
          ->required('niveau', $data['niveau'], 'Niveau')
          ->inArray('niveau', $data['niveau'], ['bronze','argent','or','platine'], 'Niveau')
          ->required('montant', $data['montant'], 'Montant')
          ->positiveNumber('montant', $data['montant'], 'Montant');

        // Unicité email
        if (!$v->hasErrors() && $this->model->emailExists($data['email'])) {
            $errors = ['email' => "Cet email est déjà utilisé par un autre sponsor."];
        } else {
            $errors = $v->getErrors();
        }

        if (!empty($errors)) {
            $old = $data;
            require __DIR__ . '/../view/backoffice/sponsor/create.php';
            return;
        }

        $this->model->create($data);
        header('Location: index.php?controller=sponsor&action=index&success=create');
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
        $errors = [];
        $old    = $sponsor;
        require __DIR__ . '/../view/backoffice/sponsor/edit.php';
    }

    /** Traite la soumission du formulaire d'édition */
    public function update(): void {
        $id      = (int)($_POST['id'] ?? 0);
        $sponsor = $this->model->findById($id);
        if (!$sponsor) {
            $this->notFound();
            return;
        }

        $data = [
            'nom'       => $_POST['nom']       ?? '',
            'email'     => $_POST['email']     ?? '',
            'telephone' => $_POST['telephone'] ?? '',
            'site_web'  => $_POST['site_web']  ?? '',
            'niveau'    => $_POST['niveau']    ?? '',
            'montant'   => $_POST['montant']   ?? '',
        ];

        $v = new Validator();
        $v->required('nom', $data['nom'], 'Nom')
          ->minLength('nom', $data['nom'], 2, 'Nom')
          ->maxLength('nom', $data['nom'], 100, 'Nom')
          ->required('email', $data['email'], 'Email')
          ->email('email', $data['email'], 'Email')
          ->required('telephone', $data['telephone'], 'Téléphone')
          ->phone('telephone', $data['telephone'], 'Téléphone')
          ->url('site_web', $data['site_web'], 'Site web')
          ->required('niveau', $data['niveau'], 'Niveau')
          ->inArray('niveau', $data['niveau'], ['bronze','argent','or','platine'], 'Niveau')
          ->required('montant', $data['montant'], 'Montant')
          ->positiveNumber('montant', $data['montant'], 'Montant');

        if (!$v->hasErrors() && $this->model->emailExists($data['email'], $id)) {
            $errors = ['email' => "Cet email est déjà utilisé par un autre sponsor."];
        } else {
            $errors = $v->getErrors();
        }

        if (!empty($errors)) {
            $old = array_merge($sponsor, $data, ['id' => $id]);
            require __DIR__ . '/../view/backoffice/sponsor/edit.php';
            return;
        }

        $this->model->update($id, $data);
        header('Location: index.php?controller=sponsor&action=index&success=update');
        exit;
    }

    /** Supprime un sponsor */
    public function delete(): void {
        $id = (int)($_GET['id'] ?? 0);
        if ($this->model->findById($id)) {
            // Vérifier s'il a des événements liés
            if ($this->model->countEvenements($id) > 0) {
                header('Location: index.php?controller=sponsor&action=index&error=has_evenements');
                exit;
            }
            $this->model->delete($id);
        }
        header('Location: index.php?controller=sponsor&action=index&success=delete');
        exit;
    }

    // ─── FrontOffice ───────────────────────────────────────────────────

    /** Liste des sponsors (frontoffice) */
    public function list(): void {
        $sponsors = $this->model->findAll();
        require __DIR__ . '/../view/frontoffice/sponsors.php';
    }

    private function notFound(): void {
        http_response_code(404);
        echo "<h1>404 – Sponsor introuvable</h1>";
    }
}
