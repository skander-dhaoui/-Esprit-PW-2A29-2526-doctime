<?php
require_once __DIR__ . '/../models/Participation.php';
require_once __DIR__ . '/../models/Evenement.php';
require_once __DIR__ . '/../config/Validator.php';

class ParticipationController {
    private Participation $model;
    private Evenement $eventModel;

    public function __construct() {
        $this->model      = new Participation();
        $this->eventModel = new Evenement();
    }

    private const STATUTS = ['en_attente', 'confirme', 'annule'];

    // ─── BackOffice ────────────────────────────────────────────────────

    public function index(): void {
        $participations = $this->model->findAll();
        require __DIR__ . '/../views/backoffice/participation/index.php';
    }

    public function indexAdmin(): void {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->query("
            SELECT
                p.id,
                u.nom,
                u.prenom,
                u.email,
                COALESCE(u.telephone, '') AS telephone,
                '' AS profession,
                e.titre AS evenement_titre,
                CASE
                    WHEN p.statut IN ('présent', 'present', 'inscrit') THEN 'confirme'
                    WHEN p.statut = 'absent' THEN 'annule'
                    ELSE 'en_attente'
                END AS statut,
                p.date_inscription
            FROM participations p
            JOIN users u ON p.user_id = u.id
            JOIN events e ON p.event_id = e.id
            ORDER BY p.date_inscription DESC
        ");
        $participations = $stmt->fetchAll(PDO::FETCH_ASSOC);
        require __DIR__ . '/../views/backoffice/participation/index.php';
    }

    public function create(): void {
        $events = $this->eventModel->findAll();
        $errors = $_SESSION['errors'] ?? [];
        $old    = $_SESSION['old']    ?? [];
        $statuts = self::STATUTS;
        unset($_SESSION['errors'], $_SESSION['old']);
        require __DIR__ . '/../views/backoffice/participation/create.php';
    }

    public function store(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: index.php?page=participations&action=create');
            exit;
        }

        $data = [
            'nom'          => trim($_POST['nom']          ?? ''),
            'prenom'       => trim($_POST['prenom']       ?? ''),
            'email'        => trim($_POST['email']        ?? ''),
            'telephone'    => trim($_POST['telephone']    ?? ''),
            'profession'   => trim($_POST['profession']   ?? ''),
            'evenement_id' => trim($_POST['evenement_id'] ?? ''),
            'statut'       => trim($_POST['statut']       ?? 'en_attente'),
        ];

        // ========== VALIDATIONS SERVEUR ==========
        $errors = $this->validateParticipation($data);

        // Vérifier les places restantes
        if (empty($errors) && !empty($data['evenement_id'])) {
            $places = $this->eventModel->getPlacesRestantes((int)$data['evenement_id']);
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

        // ========== STOCKAGE ERREURS ET REDIRECTION ==========
        if (!empty($errors)) {
            $_SESSION['errors'] = $errors;
            $_SESSION['old']    = $data;
            header('Location: index.php?page=participations&action=create');
            exit;
        }

        // ========== CRÉATION PARTICIPATION ==========
        $this->model->create($data);
        $_SESSION['success'] = "Participation créée avec succès.";
        header('Location: index.php?page=participations');
        exit;
    }

    public function edit(): void {
        $id            = (int)($_GET['id'] ?? 0);
        $participation = $this->model->findById($id);
        if (!$participation) {
            http_response_code(404);
            echo "<h1>404 – Participation introuvable</h1>";
            return;
        }

        $events = $this->eventModel->findAll();
        $errors = $_SESSION['errors'] ?? [];
        $old    = $_SESSION['old']    ?? $participation;
        unset($_SESSION['errors'], $_SESSION['old']);
        $statuts = self::STATUTS;
        require __DIR__ . '/../views/backoffice/participation/edit.php';
    }

    public function update(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: index.php?page=participations');
            exit;
        }

        $id            = (int)($_POST['id'] ?? 0);
        $participation = $this->model->findById($id);
        if (!$participation) {
            http_response_code(404);
            echo "<h1>404 – Participation introuvable</h1>";
            return;
        }

        $data = [
            'nom'          => trim($_POST['nom']          ?? ''),
            'prenom'       => trim($_POST['prenom']       ?? ''),
            'email'        => trim($_POST['email']        ?? ''),
            'telephone'    => trim($_POST['telephone']    ?? ''),
            'profession'   => trim($_POST['profession']   ?? ''),
            'evenement_id' => trim($_POST['evenement_id'] ?? ''),
            'statut'       => trim($_POST['statut']       ?? ''),
        ];

        // ========== VALIDATIONS SERVEUR ==========
        $errors = $this->validateParticipation($data);

        // Vérifier doublon
        if (empty($errors)) {
            if ($this->model->alreadyRegistered($data['email'], (int)$data['evenement_id'], $id)) {
                $errors['email'] = "Cette adresse e-mail est déjà inscrite à cet événement.";
            }
        }

        // ========== STOCKAGE ERREURS ET REDIRECTION ==========
        if (!empty($errors)) {
            $_SESSION['errors'] = $errors;
            $_SESSION['old']    = array_merge($participation, $data, ['id' => $id]);
            header('Location: index.php?page=participations&action=edit&id=' . $id);
            exit;
        }

        // ========== MISE À JOUR PARTICIPATION ==========
        $this->model->update($id, $data);
        $_SESSION['success'] = "Participation mise à jour avec succès.";
        header('Location: index.php?page=participations');
        exit;
    }

    public function delete(): void {
        $id = (int)($_GET['id'] ?? 0);
        if ($this->model->findById($id)) {
            $this->model->delete($id);
            $_SESSION['success'] = "Participation supprimée avec succès.";
        }
        header('Location: index.php?page=participations');
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

        require __DIR__ . '/../views/frontoffice/mes_inscriptions.php';
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

        $events = $this->eventModel->findAll();
        $errors = [];
        $old    = $participation;
        require __DIR__ . '/../views/frontoffice/inscription_edit.php';
    }

    /** Traitement de la modification (frontoffice) */
    public function frontUpdate(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: index.php?page=mes_inscriptions');
            exit;
        }

        $id    = (int)($_POST['id'] ?? 0);
        $email = trim($_POST['email_original'] ?? '');

        $participation = $this->model->findById($id);
        if (!$participation || strtolower($participation['email']) !== strtolower($email)) {
            http_response_code(403);
            echo "<h1>Accès refusé</h1>";
            return;
        }

        $data = [
            'nom'          => trim($_POST['nom']       ?? ''),
            'prenom'       => trim($_POST['prenom']    ?? ''),
            'email'        => $participation['email'],   // email non modifiable
            'telephone'    => trim($_POST['telephone'] ?? ''),
            'profession'   => trim($_POST['profession'] ?? ''),
            'evenement_id' => $participation['evenement_id'], // événement non modifiable
            'statut'       => $participation['statut'],       // statut non modifiable
        ];

        // ========== VALIDATIONS SERVEUR ==========
        $errors = $this->validateParticipation($data);

        // ========== STOCKAGE ERREURS ET REDIRECTION ==========
        if (!empty($errors)) {
            $_SESSION['errors'] = $errors;
            $_SESSION['old']    = array_merge($participation, $data, ['id' => $id]);
            header('Location: index.php?page=inscription_edit&id=' . $id . '&email=' . urlencode($email));
            exit;
        }

        // ========== MISE À JOUR PARTICIPATION ==========
        $this->model->update($id, $data);
        $_SESSION['success'] = "Inscription mise à jour avec succès.";
        header('Location: index.php?page=mes_inscriptions&email=' . urlencode($email));
        exit;
    }

    /** Suppression d'une inscription (frontoffice) */
    public function frontDelete(): void {
        $id    = (int)($_GET['id'] ?? 0);
        $email = trim($_GET['email'] ?? '');

        $participation = $this->model->findById($id);
        if ($participation && strtolower($participation['email']) === strtolower($email)) {
            $this->model->delete($id);
            $_SESSION['success'] = "Inscription supprimée avec succès.";
        }

        header('Location: index.php?page=mes_inscriptions&email=' . urlencode($email));
        exit;
    }

    // ─── FrontOffice : inscription publique ────────────────────────────

    public function inscrire(): void {
        $evenementId = (int)($_GET['evenement_id'] ?? 0);
        $evenement   = $this->eventModel->findById($evenementId);
        if (!$evenement) {
            http_response_code(404);
            echo "<h1>404 – Événement introuvable</h1>";
            return;
        }

        $errors = [];
        $old    = ['evenement_id' => $evenementId];
        require __DIR__ . '/../views/frontoffice/inscrire.php';
    }

    public function inscrireStore(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: index.php?page=events');
            exit;
        }

        $evenementId = (int)($_POST['evenement_id'] ?? 0);
        $evenement   = $this->eventModel->findById($evenementId);
        if (!$evenement) {
            http_response_code(404);
            echo "<h1>404 – Événement introuvable</h1>";
            return;
        }

        $data = [
            'nom'          => trim($_POST['nom']        ?? ''),
            'prenom'       => trim($_POST['prenom']     ?? ''),
            'email'        => trim($_POST['email']      ?? ''),
            'telephone'    => trim($_POST['telephone']  ?? ''),
            'profession'   => trim($_POST['profession'] ?? ''),
            'evenement_id' => $evenementId,
            'statut'       => 'en_attente',
        ];

        // ========== VALIDATIONS SERVEUR ==========
        $errors = $this->validateParticipation($data);

        if (empty($errors)) {
            $places = $this->eventModel->getPlacesRestantes($evenementId);
            if ($places <= 0) {
                $errors['evenement_id'] = "Désolé, cet événement est complet.";
            }
        }

        if (empty($errors)) {
            if ($this->model->alreadyRegistered($data['email'], $evenementId)) {
                $errors['email'] = "Vous êtes déjà inscrit(e) à cet événement avec cette adresse e-mail.";
            }
        }

        // ========== STOCKAGE ERREURS ET REDIRECTION ==========
        if (!empty($errors)) {
            $_SESSION['errors'] = $errors;
            $_SESSION['old']    = $data;
            header('Location: index.php?page=inscrire&evenement_id=' . $evenementId);
            exit;
        }

        // ========== CRÉATION PARTICIPATION ==========
        $this->model->create($data);
        $_SESSION['success'] = "Inscription confirmée! Vous recevrez un email de confirmation.";
        header('Location: index.php?page=event&id=' . $evenementId);
        exit;
    }

    // ─── Validation interne ────────────────────────────────────────────

    private function validateParticipation(array $data): array {
        $validator = new Validator();
        $validator->required('nom', $data['nom'], 'Nom')
                  ->minLength('nom', $data['nom'], 2, 'Nom')
                  ->maxLength('nom', $data['nom'], 100, 'Nom')
                  ->required('prenom', $data['prenom'], 'Prénom')
                  ->minLength('prenom', $data['prenom'], 2, 'Prénom')
                  ->maxLength('prenom', $data['prenom'], 100, 'Prénom')
                  ->required('email', $data['email'], 'Email')
                  ->email('email', $data['email'], 'Email')
                  ->required('telephone', $data['telephone'], 'Téléphone')
                  ->numeric('telephone', $data['telephone'], 'Téléphone')
                  ->minLength('telephone', $data['telephone'], 10, 'Téléphone')
                  ->required('profession', $data['profession'], 'Profession')
                  ->minLength('profession', $data['profession'], 2, 'Profession')
                  ->required('evenement_id', $data['evenement_id'], 'Événement')
                  ->integer('evenement_id', $data['evenement_id'], 'Événement');

        if (!empty($data['statut'])) {
            $validator->inArray('statut', $data['statut'], self::STATUTS, 'Statut');
        }

        return $validator->getErrors();
    }
}
