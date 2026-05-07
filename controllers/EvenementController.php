<?php
require_once __DIR__ . '/../models/Evenement.php';
require_once __DIR__ . '/../models/Sponsor.php';
require_once __DIR__ . '/../config/Validator.php';

class EvenementController {
    private Evenement $model;
    private Sponsor   $sponsorModel;

    public function __construct() {
        $this->model        = new Evenement();
        $this->sponsorModel = new Sponsor();
    }

    private const STATUTS    = ['planifie','en_cours','termine','annule'];
    private const SPECIALITES = [
        'Cardiologie','Dermatologie','Oncologie','Neurologie',
        'Pédiatrie','Chirurgie','Radiologie','Psychiatrie',
        'Gynécologie','Médecine générale','Autre',
    ];

    // ─── BackOffice ────────────────────────────────────────────────────

    public function index(): void {
        $evenements = $this->model->findAll();
        require __DIR__ . '/../view/backoffice/evenement/index.php';
    }

    public function create(): void {
        $sponsors = $this->sponsorModel->findAll();
        $errors   = [];
        $old      = [];
        $statuts  = self::STATUTS;
        $specialites = self::SPECIALITES;
        require __DIR__ . '/../view/backoffice/evenement/create.php';
    }

    public function store(): void {
        $data = [
            'titre'       => $_POST['titre']       ?? '',
            'description' => $_POST['description'] ?? '',
            'specialite'  => $_POST['specialite']  ?? '',
            'lieu'        => $_POST['lieu']        ?? '',
            'date_debut'  => $_POST['date_debut']  ?? '',
            'date_fin'    => $_POST['date_fin']    ?? '',
            'capacite'    => $_POST['capacite']    ?? '',
            'prix'        => $_POST['prix']        ?? '0',
            'statut'      => $_POST['statut']      ?? '',
            'sponsor_id'  => $_POST['sponsor_id']  ?? '',
        ];

        $errors = $this->validateEvenement($data);

        if (!empty($errors)) {
            $old         = $data;
            $sponsors    = $this->sponsorModel->findAll();
            $statuts     = self::STATUTS;
            $specialites = self::SPECIALITES;
            require __DIR__ . '/../view/backoffice/evenement/create.php';
            return;
        }

        $this->model->create($data);
        header('Location: index.php?controller=evenement&action=index&success=create');
        exit;
    }

    public function edit(): void {
        $id       = (int)($_GET['id'] ?? 0);
        $evenement = $this->model->findById($id);
        if (!$evenement) { $this->notFound(); return; }

        $sponsors    = $this->sponsorModel->findAll();
        $errors      = [];
        $old         = $evenement;
        $statuts     = self::STATUTS;
        $specialites = self::SPECIALITES;
        require __DIR__ . '/../view/backoffice/evenement/edit.php';
    }

    public function update(): void {
        $id        = (int)($_POST['id'] ?? 0);
        $evenement = $this->model->findById($id);
        if (!$evenement) { $this->notFound(); return; }

        $data = [
            'titre'       => $_POST['titre']       ?? '',
            'description' => $_POST['description'] ?? '',
            'specialite'  => $_POST['specialite']  ?? '',
            'lieu'        => $_POST['lieu']        ?? '',
            'date_debut'  => $_POST['date_debut']  ?? '',
            'date_fin'    => $_POST['date_fin']    ?? '',
            'capacite'    => $_POST['capacite']    ?? '',
            'prix'        => $_POST['prix']        ?? '0',
            'statut'      => $_POST['statut']      ?? '',
            'sponsor_id'  => $_POST['sponsor_id']  ?? '',
        ];

        $errors = $this->validateEvenement($data);

        if (!empty($errors)) {
            $old         = array_merge($evenement, $data, ['id' => $id]);
            $sponsors    = $this->sponsorModel->findAll();
            $statuts     = self::STATUTS;
            $specialites = self::SPECIALITES;
            require __DIR__ . '/../view/backoffice/evenement/edit.php';
            return;
        }

        $this->model->update($id, $data);
        header('Location: index.php?controller=evenement&action=index&success=update');
        exit;
    }

    public function delete(): void {
        $id = (int)($_GET['id'] ?? 0);
        if ($this->model->findById($id)) {
            $this->model->delete($id);
        }
        header('Location: index.php?controller=evenement&action=index&success=delete');
        exit;
    }

    // ─── FrontOffice ───────────────────────────────────────────────────

    public function list(): void {
        $evenements = $this->model->findUpcoming();
        require __DIR__ . '/../view/frontoffice/evenements.php';
    }

    public function detail(): void {
        $id        = (int)($_GET['id'] ?? 0);
        $evenement = $this->model->findById($id);
        if (!$evenement) { $this->notFound(); return; }

        $placesRestantes = $this->model->getPlacesRestantes($id);
        require __DIR__ . '/../view/frontoffice/evenement_detail.php';
    }

    // ─── Validation interne ────────────────────────────────────────────

    private function validateEvenement(array $data): array {
        $v = new Validator();
        $v->required('titre', $data['titre'], 'Titre')
          ->minLength('titre', $data['titre'], 3, 'Titre')
          ->maxLength('titre', $data['titre'], 200, 'Titre')
          ->required('description', $data['description'], 'Description')
          ->minLength('description', $data['description'], 10, 'Description')
          ->required('specialite', $data['specialite'], 'Spécialité')
          ->inArray('specialite', $data['specialite'], self::SPECIALITES, 'Spécialité')
          ->required('lieu', $data['lieu'], 'Lieu')
          ->required('date_debut', $data['date_debut'], 'Date de début')
          ->date('date_debut', $data['date_debut'], 'Date de début')
          ->required('date_fin', $data['date_fin'], 'Date de fin')
          ->date('date_fin', $data['date_fin'], 'Date de fin')
          ->dateAfter('date_fin', $data['date_fin'], $data['date_debut'], 'Date de fin', 'Date de début')
          ->required('capacite', $data['capacite'], 'Capacité')
          ->integer('capacite', $data['capacite'], 'Capacité')
          ->required('statut', $data['statut'], 'Statut')
          ->inArray('statut', $data['statut'], self::STATUTS, 'Statut');

        // Prix : obligatoire, >= 0
        if (trim($data['prix']) === '') {
            // default to 0, no error
        } else {
            $v->numeric('prix', $data['prix'], 'Prix');
        }

        return $v->getErrors();
    }

    private function notFound(): void {
        http_response_code(404);
        echo "<h1>404 – Événement introuvable</h1>";
    }
}
