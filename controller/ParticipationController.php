<?php
require_once __DIR__ . '/../model/Participation.php';
require_once __DIR__ . '/../model/ParticipationRepository.php';
require_once __DIR__ . '/../model/Evenement.php';
require_once __DIR__ . '/../model/EvenementRepository.php';
require_once __DIR__ . '/../config/Validator.php';

class ParticipationController {
    private ParticipationRepository $participationRepo;
    private EvenementRepository     $evenementRepo;

    public function __construct() {
        $this->participationRepo = new ParticipationRepository();
        $this->evenementRepo     = new EvenementRepository();
    }

    private const STATUTS = ['en_attente','confirme','annule'];

    // ─── BackOffice ────────────────────────────────────────────────────

    public function index(): void {
        $participations = $this->participationRepo->findAll();
        
        $evenements = $this->evenementRepo->findAll();
        $titres = [];
        foreach ($evenements as $e) {
            $titres[$e->getId()] = $e->getTitre();
        }

        $participations = array_map(function($p) use ($titres) {
            $arr = $p->toArray();
            $arr['evenement_titre'] = $titres[$p->getEvenementId()] ?? 'Événement #' . $p->getEvenementId();
            return $arr;
        }, $participations);

        require __DIR__ . '/../view/backoffice/participation/index.php';
    }

    public function create(): void {
        $evenements = $this->evenementRepo->findAll();
        $evenements = array_map(fn($e) => $e->toArray(), $evenements);
        $errors     = [];
        $old        = [];
        $statuts    = self::STATUTS;
        require __DIR__ . '/../view/backoffice/participation/create.php';
    }

    public function store(): void {
        $data = [
            'nom'          => $_POST['nom']          ?? '',
            'prenom'       => $_POST['prenom']       ?? '',
            'email'        => $_POST['email']        ?? '',
            'telephone'    => $_POST['telephone']    ?? '',
            'profession'   => $_POST['profession']   ?? '',
            'evenement_id' => $_POST['evenement_id'] ?? '',
            'statut'       => $_POST['statut']       ?? 'en_attente',
        ];

        $errors = $this->validateParticipation($data);

        // Vérifier les places restantes
        if (empty($errors) && !empty($data['evenement_id'])) {
            $places = $this->evenementRepo->getPlacesRestantes((int)$data['evenement_id']);
            if ($places <= 0) {
                $errors['evenement_id'] = "Cet événement est complet, il n'y a plus de places disponibles.";
            }
        }

        // Vérifier doublon
        if (empty($errors)) {
            if ($this->participationRepo->isAlreadyRegistered($data['email'], (int)$data['evenement_id'])) {
                $errors['email'] = "Cette adresse e-mail est déjà inscrite à cet événement.";
            }
        }

        if (!empty($errors)) {
            $old        = $data;
            $evenements = $this->evenementRepo->findAll();
            $evenements = array_map(fn($e) => $e->toArray(), $evenements);
            $statuts    = self::STATUTS;
            require __DIR__ . '/../view/backoffice/participation/create.php';
            return;
        }

        $participation = Participation::fromArray($data);
        $this->participationRepo->create($participation);
        header('Location: index.php?controller=participation&action=index&success=create');
        exit;
    }

    public function edit(): void {
        $id            = (int)($_GET['id'] ?? 0);
        $participation = $this->participationRepo->findById($id);
        if (!$participation) { $this->notFound(); return; }

        $evenements = $this->evenementRepo->findAll();
        $evenements = array_map(fn($e) => $e->toArray(), $evenements);
        $errors     = [];
        $old        = $participation->toArray();
        $statuts    = self::STATUTS;
        require __DIR__ . '/../view/backoffice/participation/edit.php';
    }

    public function update(): void {
        $id            = (int)($_POST['id'] ?? 0);
        $participation = $this->participationRepo->findById($id);
        if (!$participation) { $this->notFound(); return; }

        $data = [
            'nom'          => $_POST['nom']          ?? '',
            'prenom'       => $_POST['prenom']       ?? '',
            'email'        => $_POST['email']        ?? '',
            'telephone'    => $_POST['telephone']    ?? '',
            'profession'   => $_POST['profession']   ?? '',
            'evenement_id' => $_POST['evenement_id'] ?? '',
            'statut'       => $_POST['statut']       ?? '',
        ];

        $errors = $this->validateParticipation($data);

        if (empty($errors)) {
            if ($this->participationRepo->isAlreadyRegistered($data['email'], (int)$data['evenement_id'], $id)) {
                $errors['email'] = "Cette adresse e-mail est déjà inscrite à cet événement.";
            }
        }

        if (!empty($errors)) {
            $old        = array_merge($participation->toArray(), $data, ['id' => $id]);
            $evenements = $this->evenementRepo->findAll();
            $evenements = array_map(fn($e) => $e->toArray(), $evenements);
            $statuts    = self::STATUTS;
            require __DIR__ . '/../view/backoffice/participation/edit.php';
            return;
        }

        $participation->setNom($data['nom'])
                     ->setPrenom($data['prenom'])
                     ->setEmail($data['email'])
                     ->setTelephone($data['telephone'])
                     ->setProfession($data['profession'])
                     ->setEvenementId((int)$data['evenement_id'])
                     ->setStatut($data['statut']);
        $this->participationRepo->update($participation);
        header('Location: index.php?controller=participation&action=index&success=update');
        exit;
    }

    public function delete(): void {
        $id = (int)($_GET['id'] ?? 0);
        if ($this->participationRepo->findById($id)) {
            $this->participationRepo->delete($id);
        }
        header('Location: index.php?controller=participation&action=index&success=delete');
        exit;
    }

    // ─── FrontOffice : Mes Inscriptions (CRUD public) ─────────────────

    /** Étape 1 : formulaire de recherche par email */
    public function search(): void {
        $email          = trim($_GET['email'] ?? $_POST['email'] ?? '');
        $participations = [];
        $searched       = false;
        $errors         = [];

        if ($email !== '') {
            $searched = true;
            $v       = new Validator();
            $v->required('email', $email, 'E-mail')
              ->email('email', $email, 'E-mail');
            $errors = $v->getErrors();
            if (empty($errors)) {
                $participations = $this->participationRepo->findByEmail($email);
                
                $evenements = $this->evenementRepo->findAll();
                $evenementData = [];
                foreach ($evenements as $e) {
                    $evenementData[$e->getId()] = $e->toArray();
                }

                $participations = array_map(function($p) use ($evenementData) {
                    $arr = $p->toArray();
                    $eData = $evenementData[$p->getEvenementId()] ?? [];
                    $arr['evenement_titre'] = $eData['titre'] ?? 'Inconnu';
                    $arr['evenement_statut'] = $eData['statut'] ?? 'inconnu';
                    $arr['specialite'] = $eData['specialite'] ?? '';
                    $arr['lieu'] = $eData['lieu'] ?? '';
                    $arr['date_debut'] = $eData['date_debut'] ?? '';
                    $arr['date_fin'] = $eData['date_fin'] ?? '';
                    return $arr;
                }, $participations);
            }
        }

        require __DIR__ . '/../view/frontoffice/mes_inscriptions.php';
    }

    /** Formulaire de modification d'une inscription (frontoffice) */
    public function frontEdit(): void {
        $id    = (int)($_GET['id'] ?? 0);
        $email = trim($_GET['email'] ?? '');

        $participation = $this->participationRepo->findById($id);
        if (!$participation || strtolower($participation->getEmail()) !== strtolower($email)) {
            http_response_code(403);
            echo "<h1>Accès refusé – email incorrect ou inscription introuvable</h1>";
            return;
        }

        $evenements = $this->evenementRepo->findAll();
        $evenementDict = [];
        foreach ($evenements as $e) {
            $evenementDict[$e->getId()] = $e->getTitre();
        }
        $evenements = array_map(fn($e) => $e->toArray(), $evenements);
        $errors     = [];
        $old        = $participation->toArray();
        
        $partArray  = $participation->toArray();
        $partArray['evenement_titre'] = $evenementDict[$participation->getEvenementId()] ?? 'Inconnu';
        $participation = $partArray;
        
        require __DIR__ . '/../view/frontoffice/inscription_edit.php';
    }

    /** Traitement de la modification (frontoffice) */
    public function frontUpdate(): void {
        $id    = (int)($_POST['id'] ?? 0);
        $email = trim($_POST['email_original'] ?? '');

        $participation = $this->participationRepo->findById($id);
        if (!$participation || strtolower($participation->getEmail()) !== strtolower($email)) {
            http_response_code(403);
            echo "<h1>Accès refusé</h1>";
            return;
        }

        $data = [
            'nom'          => $_POST['nom']          ?? '',
            'prenom'       => $_POST['prenom']       ?? '',
            'email'        => $participation->getEmail(),   // email non modifiable
            'telephone'    => $_POST['telephone']    ?? '',
            'profession'   => $_POST['profession']   ?? '',
            'evenement_id' => $participation->getEvenementId(), // événement non modifiable
            'statut'       => $participation->getStatut(),       // statut non modifiable
        ];

        $errors = $this->validateParticipation($data);

        if (!empty($errors)) {
            $evenements = $this->evenementRepo->findAll();
            $evenements = array_map(fn($e) => $e->toArray(), $evenements);
            $old        = array_merge($participation->toArray(), $data, ['id' => $id]);
            require __DIR__ . '/../view/frontoffice/inscription_edit.php';
            return;
        }

        $participation->setNom($data['nom'])
                     ->setPrenom($data['prenom'])
                     ->setTelephone($data['telephone'])
                     ->setProfession($data['profession']);
        $this->participationRepo->update($participation);
        header('Location: index.php?controller=mesinscriptions&action=search&email=' . urlencode($email) . '&success=update');
        exit;
    }

    /** Suppression d'une inscription (frontoffice) */
    public function frontDelete(): void {
        $id    = (int)($_GET['id'] ?? 0);
        $email = trim($_GET['email'] ?? '');

        $participation = $this->participationRepo->findById($id);
        if ($participation && strtolower($participation->getEmail()) === strtolower($email)) {
            $this->participationRepo->delete($id);
        }

        header('Location: index.php?controller=mesinscriptions&action=search&email=' . urlencode($email) . '&success=delete');
        exit;
    }

    // ─── FrontOffice : inscription publique ────────────────────────────

    public function inscrire(): void {
        $evenementId = (int)($_GET['evenement_id'] ?? 0);
        $evenement   = $this->evenementRepo->findById($evenementId);
        if (!$evenement) { $this->notFound(); return; }

        $errors = [];
        $old    = ['evenement_id' => $evenementId];
        require __DIR__ . '/../view/frontoffice/inscrire.php';
    }

    public function inscrireStore(): void {
        $evenementId = (int)($_POST['evenement_id'] ?? 0);
        $evenement   = $this->evenementRepo->findById($evenementId);
        if (!$evenement) { $this->notFound(); return; }

        $data = [
            'nom'          => $_POST['nom']        ?? '',
            'prenom'       => $_POST['prenom']     ?? '',
            'email'        => $_POST['email']      ?? '',
            'telephone'    => $_POST['telephone']  ?? '',
            'profession'   => $_POST['profession'] ?? '',
            'evenement_id' => $evenementId,
            'statut'       => 'en_attente',
        ];

        $errors = $this->validateParticipation($data);

        if (empty($errors)) {
            $places = $this->evenementRepo->getPlacesRestantes($evenementId);
            if ($places <= 0) {
                $errors['evenement_id'] = "Désolé, cet événement est complet.";
            }
        }

        if (empty($errors)) {
            if ($this->participationRepo->isAlreadyRegistered($data['email'], $evenementId)) {
                $errors['email'] = "Vous êtes déjà inscrit(e) à cet événement avec cette adresse e-mail.";
            }
        }

        if (!empty($errors)) {
            $old = $data;
            require __DIR__ . '/../view/frontoffice/inscrire.php';
            return;
        }

        $participation = Participation::fromArray($data);
        $this->participationRepo->create($participation);
        header('Location: index.php?controller=evenement&action=detail&id=' . $evenementId . '&success=inscrit');
        exit;
    }

    // ─── Validation interne ────────────────────────────────────────────

    private function validateParticipation(array $data): array {
        $v = new Validator();
        $v->required('nom', $data['nom'], 'Nom')
          ->minLength('nom', $data['nom'], 2, 'Nom')
          ->maxLength('nom', $data['nom'], 100, 'Nom')
          ->required('prenom', $data['prenom'], 'Prénom')
          ->minLength('prenom', $data['prenom'], 2, 'Prénom')
          ->maxLength('prenom', $data['prenom'], 100, 'Prénom')
          ->required('email', $data['email'], 'Email')
          ->email('email', $data['email'], 'Email')
          ->required('telephone', $data['telephone'], 'Téléphone')
          ->phone('telephone', $data['telephone'], 'Téléphone')
          ->required('profession', $data['profession'], 'Profession')
          ->minLength('profession', $data['profession'], 2, 'Profession')
          ->maxLength('profession', $data['profession'], 100, 'Profession')
          ->required('evenement_id', $data['evenement_id'], 'Événement')
          ->integer('evenement_id', $data['evenement_id'], 'Événement');

        if (isset($data['statut'])) {
            $v->inArray('statut', $data['statut'], self::STATUTS, 'Statut');
        }

        return $v->getErrors();
    }

    private function notFound(): void {
        http_response_code(404);
        echo "<h1>404 – Participation introuvable</h1>";
    }
}
