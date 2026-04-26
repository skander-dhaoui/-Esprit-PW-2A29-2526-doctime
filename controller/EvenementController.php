<?php
require_once __DIR__ . '/../model/Evenement.php';
require_once __DIR__ . '/../model/EvenementRepository.php';
require_once __DIR__ . '/../model/Sponsor.php';
require_once __DIR__ . '/../model/SponsorRepository.php';
require_once __DIR__ . '/../config/Validator.php';

class EvenementController {
    private EvenementRepository $evenementRepo;
    private SponsorRepository   $sponsorRepo;

    public function __construct() {
        $this->evenementRepo = new EvenementRepository();
        $this->sponsorRepo   = new SponsorRepository();
    }

    private const STATUTS    = ['planifie','en_cours','termine','annule'];
    private const SPECIALITES = [
        'Cardiologie','Dermatologie','Oncologie','Neurologie',
        'Pédiatrie','Chirurgie','Radiologie','Psychiatrie',
        'Gynécologie','Médecine générale','Autre',
    ];

    // ─── BackOffice ────────────────────────────────────────────────────

    public function index(): void {
        $evenements = $this->evenementRepo->findAll();
        $evenements = array_map(function($e) {
            $arr = $e->toArray();
            $sponsors = $e->getSponsors();
            $noms = array_map(fn($s) => is_array($s) ? ($s['nom'] ?? '') : '', $sponsors);
            $arr['sponsors_nom'] = implode(', ', array_filter($noms));
            return $arr;
        }, $evenements);
        require __DIR__ . '/../view/backoffice/evenement/index.php';
    }

    public function create(): void {
        $sponsors = $this->sponsorRepo->findAll();
        $sponsors = array_map(fn($s) => $s->toArray(), $sponsors);
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
            'sponsors'    => $_POST['sponsor_ids'] ?? [],
        ];

        $errors = $this->validateEvenement($data, true);

        if (!empty($errors)) {
            $old         = $data;
            $sponsors    = $this->sponsorRepo->findAll();
            $sponsors    = array_map(fn($s) => $s->toArray(), $sponsors);
            $statuts     = self::STATUTS;
            $specialites = self::SPECIALITES;
            require __DIR__ . '/../view/backoffice/evenement/create.php';
            return;
        }

        $evenement = Evenement::fromArray($data);
        $this->evenementRepo->create($evenement);
        header('Location: index.php?controller=evenement&action=index&success=create');
        exit;
    }

    public function edit(): void {
        $id       = (int)($_GET['id'] ?? 0);
        $evenement = $this->evenementRepo->findById($id);
        if (!$evenement) { $this->notFound(); return; }

        $sponsors    = $this->sponsorRepo->findAll();
        $sponsors    = array_map(fn($s) => $s->toArray(), $sponsors);
        $errors      = [];
        $old         = $evenement->toArray();
        $old['sponsor_ids'] = array_column($evenement->getSponsors(), 'id');
        $statuts     = self::STATUTS;
        $specialites = self::SPECIALITES;
        require __DIR__ . '/../view/backoffice/evenement/edit.php';
    }

    public function update(): void {
        $id        = (int)($_POST['id'] ?? 0);
        $evenement = $this->evenementRepo->findById($id);
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
            'sponsors'    => $_POST['sponsor_ids'] ?? [],
        ];

        $errors = $this->validateEvenement($data);

        if (!empty($errors)) {
            $old         = array_merge($evenement->toArray(), $data, ['id' => $id]);
            $sponsors    = $this->sponsorRepo->findAll();
            $sponsors    = array_map(fn($s) => $s->toArray(), $sponsors);
            $statuts     = self::STATUTS;
            $specialites = self::SPECIALITES;
            require __DIR__ . '/../view/backoffice/evenement/edit.php';
            return;
        }

        $evenement->setTitre($data['titre'])
                  ->setDescription($data['description'])
                  ->setSpecialite($data['specialite'])
                  ->setLieu($data['lieu'])
                  ->setDateDebut($data['date_debut'])
                  ->setDateFin($data['date_fin'])
                  ->setCapacite((int)$data['capacite'])
                  ->setPrix((float)$data['prix'])
                  ->setStatut($data['statut'])
                  ->setSponsors($data['sponsors']);
        $this->evenementRepo->update($evenement);
        header('Location: index.php?controller=evenement&action=index&success=update');
        exit;
    }

    public function delete(): void {
        $id = (int)($_GET['id'] ?? 0);
        if ($this->evenementRepo->findById($id)) {
            $this->evenementRepo->delete($id);
        }
        header('Location: index.php?controller=evenement&action=index&success=delete');
        exit;
    }

    // ─── FrontOffice ───────────────────────────────────────────────────

    public function list(): void {
        $evenements = $this->evenementRepo->findUpcoming();
        $evenements = array_map(function($e) {
            $arr = $e->toArray();
            $sponsors = $e->getSponsors();
            $noms = array_map(fn($s) => is_array($s) ? ($s['nom'] ?? '') : '', $sponsors);
            $arr['sponsors_nom'] = implode(', ', array_filter($noms));
            return $arr;
        }, $evenements);
        require __DIR__ . '/../view/frontoffice/evenements.php';
    }

    public function detail(): void {
        $id        = (int)($_GET['id'] ?? 0);
        $evenementObj = $this->evenementRepo->findById($id);
        if (!$evenementObj) { $this->notFound(); return; }

        $evenement = $evenementObj->toArray();
        $sponsors = $evenementObj->getSponsors();
        $noms = array_map(fn($s) => is_array($s) ? ($s['nom'] ?? '') : '', $sponsors);
        $evenement['sponsors_nom'] = implode(', ', array_filter($noms));

        $placesRestantes = $this->evenementRepo->getPlacesRestantes($id);
        require __DIR__ . '/../view/frontoffice/evenement_detail.php';
    }

    // ─── Validation interne ────────────────────────────────────────────

    private function validateEvenement(array $data, bool $nouvelEvenement = false): array {
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
          ->date('date_debut', $data['date_debut'], 'Date de début');
        if ($nouvelEvenement) {
            $v->dateNotPast('date_debut', $data['date_debut'], 'Date de début');
        }
        $v->required('date_fin', $data['date_fin'], 'Date de fin')
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
