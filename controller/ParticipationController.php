<?php
require_once __DIR__ . '/../model/Participation.php';
require_once __DIR__ . '/../model/Evenement.php';
require_once __DIR__ . '/../config/Validator.php';

class ParticipationController {
    private Participation $model;
    private Evenement     $evenementModel;

    public function __construct() {
        $this->model          = new Participation();
        $this->evenementModel = new Evenement();
    }

    private const STATUTS = ['en_attente','confirme','annule'];

    // ─── BackOffice ────────────────────────────────────────────────────

    public function index(): void {
        $participations = $this->model->findAll();
        require __DIR__ . '/../view/backoffice/participation/index.php';
    }

    public function create(): void {
        $evenements = $this->evenementModel->findAll();
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
            $places = $this->evenementModel->getPlacesRestantes((int)$data['evenement_id']);
            if ($places <= 0) {
                $errors['evenement_id'] = "Cet événement est complet, il n'y a plus de places disponibles.";
            }
        }

        // Vérifier doublon
        if (empty($errors)) {
            if ($this->model->alreadyRegistered($data['email'], (int)$data['evenement_id'])) {
                $errors['email'] = "Cette adresse e-mail est déjà inscrite à cet événement.";
            }
        }

        if (!empty($errors)) {
            $old        = $data;
            $evenements = $this->evenementModel->findAll();
            $statuts    = self::STATUTS;
            require __DIR__ . '/../view/backoffice/participation/create.php';
            return;
        }

        $this->model->create($data);
        header('Location: index.php?controller=participation&action=index&success=create');
        exit;
    }

    public function edit(): void {
        $id            = (int)($_GET['id'] ?? 0);
        $participation = $this->model->findById($id);
        if (!$participation) { $this->notFound(); return; }

        $evenements = $this->evenementModel->findAll();
        $errors     = [];
        $old        = $participation;
        $statuts    = self::STATUTS;
        require __DIR__ . '/../view/backoffice/participation/edit.php';
    }

    public function update(): void {
        $id            = (int)($_POST['id'] ?? 0);
        $participation = $this->model->findById($id);
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
            if ($this->model->alreadyRegistered($data['email'], (int)$data['evenement_id'], $id)) {
                $errors['email'] = "Cette adresse e-mail est déjà inscrite à cet événement.";
            }
        }

        if (!empty($errors)) {
            $old        = array_merge($participation, $data, ['id' => $id]);
            $evenements = $this->evenementModel->findAll();
            $statuts    = self::STATUTS;
            require __DIR__ . '/../view/backoffice/participation/edit.php';
            return;
        }

        $this->model->update($id, $data);
        header('Location: index.php?controller=participation&action=index&success=update');
        exit;
    }

    public function delete(): void {
        $id = (int)($_GET['id'] ?? 0);
        if ($this->model->findById($id)) {
            $this->model->delete($id);
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

        if ($email !== '') {
            $searched       = true;
            $participations = $this->model->findByEmail($email);
        }

        require __DIR__ . '/../view/frontoffice/mes_inscriptions.php';
    }

    /** Formulaire de modification d'une inscription (frontoffice) */
    public function frontEdit(): void {
        $id    = (int)($_GET['id'] ?? 0);
        $email = trim($_GET['email'] ?? '');

        $participation = $this->model->findById($id);
        if (!$participation || strtolower($participation['email']) !== strtolower($email)) {
            http_response_code(403);
            echo "<h1>Accès refusé – email incorrect ou inscription introuvable</h1>";
            return;
        }

        $evenements = $this->evenementModel->findAll();
        $errors     = [];
        $old        = $participation;
        require __DIR__ . '/../view/frontoffice/inscription_edit.php';
    }

    /** Traitement de la modification (frontoffice) */
    public function frontUpdate(): void {
        $id    = (int)($_POST['id'] ?? 0);
        $email = trim($_POST['email_original'] ?? '');

        $participation = $this->model->findById($id);
        if (!$participation || strtolower($participation['email']) !== strtolower($email)) {
            http_response_code(403);
            echo "<h1>Accès refusé</h1>";
            return;
        }

        $data = [
            'nom'          => $_POST['nom']          ?? '',
            'prenom'       => $_POST['prenom']       ?? '',
            'email'        => $participation['email'],   // email non modifiable
            'telephone'    => $_POST['telephone']    ?? '',
            'profession'   => $_POST['profession']   ?? '',
            'evenement_id' => $participation['evenement_id'], // événement non modifiable
            'statut'       => $participation['statut'],       // statut non modifiable
        ];

        $errors = $this->validateParticipation($data);

        if (!empty($errors)) {
            $evenements = $this->evenementModel->findAll();
            $old        = array_merge($participation, $data, ['id' => $id]);
            require __DIR__ . '/../view/frontoffice/inscription_edit.php';
            return;
        }

        $this->model->update($id, $data);
        header('Location: index.php?controller=mesinscriptions&action=search&email=' . urlencode($email) . '&success=update');
        exit;
    }

    /** Suppression d'une inscription (frontoffice) */
    public function frontDelete(): void {
        $id    = (int)($_GET['id'] ?? 0);
        $email = trim($_GET['email'] ?? '');

        $participation = $this->model->findById($id);
        if ($participation && strtolower($participation['email']) === strtolower($email)) {
            $this->model->delete($id);
        }

        header('Location: index.php?controller=mesinscriptions&action=search&email=' . urlencode($email) . '&success=delete');
        exit;
    }

    // ─── FrontOffice : inscription publique ────────────────────────────

    public function inscrire(): void {
        $evenementId = (int)($_GET['evenement_id'] ?? 0);
        $evenement   = $this->evenementModel->findById($evenementId);
        if (!$evenement) { $this->notFound(); return; }

        $errors = [];
        $old    = ['evenement_id' => $evenementId];
        require __DIR__ . '/../view/frontoffice/inscrire.php';
    }

    public function inscrireStore(): void {
        $evenementId = (int)($_POST['evenement_id'] ?? 0);
        $evenement   = $this->evenementModel->findById($evenementId);
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
            $places = $this->evenementModel->getPlacesRestantes($evenementId);
            if ($places <= 0) {
                $errors['evenement_id'] = "Désolé, cet événement est complet.";
            }
        }

        if (empty($errors)) {
            if ($this->model->alreadyRegistered($data['email'], $evenementId)) {
                $errors['email'] = "Vous êtes déjà inscrit(e) à cet événement avec cette adresse e-mail.";
            }
        }

        if (!empty($errors)) {
            $old = $data;
            require __DIR__ . '/../view/frontoffice/inscrire.php';
            return;
        }

        $this->model->create($data);
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
