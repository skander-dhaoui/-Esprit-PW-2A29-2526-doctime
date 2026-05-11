<?php

require_once __DIR__ . '/../models/Participation.php';
require_once __DIR__ . '/../models/Event.php';
require_once __DIR__ . '/../models/Patient.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/AuthController.php';

class ParticipationController {

    private Participation $participationModel;
    private Event $eventModel;
    private Patient $patientModel;
    private User $userModel;
    private AuthController $auth;
    private Database $db;

    public function __construct() {
        $this->participationModel = new Participation();
        $this->eventModel = new Event();
        $this->patientModel = new Patient();
        $this->userModel = new User();
        $this->auth = new AuthController();
        $this->db = Database::getInstance();
    }

    // ─────────────────────────────────────────
    //  Liste des participations (admin)
    // ─────────────────────────────────────────
    public function indexAdmin(): void {
        $this->auth->requireRole('admin');

        try {
            $filter = $_GET['filter'] ?? 'all';
            $eventId = isset($_GET['event']) && $_GET['event'] !== '' ? (int)$_GET['event'] : null;
            $search = $_GET['search'] ?? '';

            $participations = $this->participationModel->listAdminBackoffice($filter, $eventId, $search, 500);

            $events = $this->eventModel->getAll();
            $flash = $_SESSION['flash'] ?? null;
            unset($_SESSION['flash']);

            require_once __DIR__ . '/../views/backoffice/participation/index.php';
        } catch (Throwable $e) {
            error_log('Erreur ParticipationController::indexAdmin - ' . $e->getMessage());
            $this->setFlash('error', 'Erreur lors du chargement des participations.');
            header('Location: index.php?page=dashboard');
            exit;
        }
    }

    // ─────────────────────────────────────────
    //  Nouvelle participation (admin)
    // ─────────────────────────────────────────
    public function create(): void {
        $this->auth->requireRole('admin');

        try {
            $csrfToken = $this->generateCsrfToken();
            $old = $_SESSION['old'] ?? [];
            $errors = $_SESSION['participation_errors'] ?? [];
            unset($_SESSION['old'], $_SESSION['participation_errors']);

            $evenements = $this->eventModel->getAll();
            $statuts = ['inscrit', 'présent', 'absent'];

            require_once __DIR__ . '/../views/backoffice/participation/create.php';
        } catch (Throwable $e) {
            error_log('Erreur ParticipationController::create - ' . $e->getMessage());
            $this->setFlash('error', 'Erreur lors du chargement du formulaire.');
            header('Location: index.php?page=participations');
            exit;
        }
    }

    public function store(): void {
        $this->auth->requireRole('admin');

        $redirectCreate = 'Location: index.php?page=participations&action=create';

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header($redirectCreate);
            exit;
        }

        if (!$this->verifyCsrfToken($_POST['csrf_token'] ?? '')) {
            $this->setFlash('error', 'Erreur de sécurité. Veuillez réessayer.');
            header($redirectCreate);
            exit;
        }

        $payload = [
            'nom' => trim($_POST['nom'] ?? ''),
            'prenom' => trim($_POST['prenom'] ?? ''),
            'email' => strtolower(trim($_POST['email'] ?? '')),
            'telephone' => trim($_POST['telephone'] ?? ''),
            'profession' => trim($_POST['profession'] ?? ''),
            'evenement_id' => (int)($_POST['evenement_id'] ?? 0),
            'statut' => trim($_POST['statut'] ?? ''),
        ];

        $errors = $this->validateParticipationCreate($payload);

        if (!empty($errors)) {
            $_SESSION['old'] = $payload;
            $_SESSION['participation_errors'] = $errors;
            header($redirectCreate);
            exit;
        }

        $event = $this->eventModel->getById($payload['evenement_id']);
        if (!$event) {
            $this->setFlash('error', 'Événement introuvable.');
            $_SESSION['old'] = $payload;
            header($redirectCreate);
            exit;
        }

        try {
            $existingUser = $this->userModel->findByEmail($payload['email']);

            if ($existingUser) {
                $userId = (int)$existingUser['id'];
                $this->userModel->update($userId, [
                    'nom' => $payload['nom'],
                    'prenom' => $payload['prenom'],
                    'telephone' => $payload['telephone'] !== '' ? $payload['telephone'] : null,
                ]);
            } else {
                $userId = $this->userModel->create([
                    'nom' => $payload['nom'],
                    'prenom' => $payload['prenom'],
                    'email' => $payload['email'],
                    'telephone' => $payload['telephone'],
                    'password' => password_hash(bin2hex(random_bytes(16)), PASSWORD_DEFAULT),
                    'role' => 'patient',
                    'statut' => 'actif',
                ]);
                if ($userId <= 0) {
                    throw new RuntimeException('Création utilisateur refusée.');
                }
                try {
                    $this->userModel->upsertPatient($userId, []);
                } catch (Throwable $e) {
                    error_log('ParticipationController::store upsertPatient - ' . $e->getMessage());
                }
            }

            if ($this->participationModel->findByEventAndUserId($payload['evenement_id'], $userId)) {
                $this->setFlash('error', 'Ce participant est déjà inscrit à cet événement.');
                $_SESSION['old'] = $payload;
                header($redirectCreate);
                exit;
            }

            $pid = $this->participationModel->insertBackoffice(
                $payload['evenement_id'],
                $userId,
                $payload['statut']
            );

            if (!$pid) {
                throw new RuntimeException('Insertion participation refusée.');
            }

            if (!empty($_SESSION['user_id'])) {
                $this->logAction((int)$_SESSION['user_id'], 'Création participation', 'Participation #' . $pid);
            }

            $this->setFlash('success', 'Participation enregistrée.');
            header('Location: index.php?page=participations');
            exit;
        } catch (Throwable $e) {
            error_log('Erreur ParticipationController::store - ' . $e->getMessage());
            $this->setFlash('error', 'Impossible d\'enregistrer la participation.');
            $_SESSION['old'] = $payload;
            header($redirectCreate);
            exit;
        }
    }

    // ─────────────────────────────────────────
    //  Liste des participations (médecin)
    // ─────────────────────────────────────────
    public function indexMedecin(): void {
        $this->auth->requireRole('medecin');

        try {
            $medecinId = (int)$_SESSION['user_id'];
            $filter = $_GET['filter'] ?? 'all';
            $eventId = $_GET['event'] ?? null;

            // Récupérer les événements du médecin
            $medecinEvents = $this->eventModel->getEventsByCreator($medecinId, 'medecin');
            $eventIds = array_column($medecinEvents, 'id');

            if (empty($eventIds)) {
                $participations = [];
            } else {
                $participations = $this->participationModel->getByEvents($eventIds, $filter);
            }

            $flash = $_SESSION['flash'] ?? null;
            unset($_SESSION['flash']);

            require_once __DIR__ . '/../views/backoffice/participation_list_medecin.php';
        } catch (Exception $e) {
            error_log('Erreur ParticipationController::indexMedecin - ' . $e->getMessage());
            $this->setFlash('error', 'Erreur lors du chargement.');
            header('Location: /medecin/dashboard');
            exit;
        }
    }

    // ─────────────────────────────────────────
    //  Créer une participation (patient s'inscrit)
    // ─────────────────────────────────────────
    public function register(int $eventId): void {
        $this->auth->requireRole('patient');

        try {
            $event = $this->eventModel->getById($eventId);

            if (!$event) {
                $this->setFlash('error', 'Événement introuvable.');
                header('Location: /events');
                exit;
            }

            $patientId = (int)$_SESSION['user_id'];

            // Vérifier si déjà inscrit
            $existing = $this->participationModel->getByEventAndPatient($eventId, $patientId);
            if ($existing) {
                $this->setFlash('error', 'Vous êtes déjà inscrit à cet événement.');
                header("Location: /events/$eventId");
                exit;
            }

            // Vérifier les places disponibles
            $participants = $this->participationModel->countByEvent($eventId);
            if ($event['nombre_places_max'] > 0 && $participants >= $event['nombre_places_max']) {
                $this->setFlash('error', 'Cet événement est complet.');
                header("Location: /events/$eventId");
                exit;
            }

            // Vérifier que l'événement n'est pas passé
            $eventDateTime = new DateTime($event['date_debut'] . ' ' . $event['heure_debut']);
            if ($eventDateTime < new DateTime()) {
                $this->setFlash('error', 'Cet événement est déjà passé.');
                header("Location: /events/$eventId");
                exit;
            }

            // Récupérer les informations patient
            $patient = $this->patientModel->findByUserId($patientId);

            $data = [
                'event_id' => $eventId,
                'patient_id' => $patientId,
                'date_inscription' => date('Y-m-d H:i:s'),
                'statut' => 'confirmé',
                'email' => $patient['email'] ?? '',
                'telephone' => $patient['telephone'] ?? '',
                'notes' => '',
            ];

            $participationId = $this->participationModel->create($data);

            if (!$participationId) {
                throw new Exception('Erreur lors de l\'inscription.');
            }

            $this->logAction($patientId, 'Inscription événement', "Inscription à l'événement #$eventId - {$event['titre']}");

            // Envoyer email de confirmation
            $this->sendConfirmationEmail($patient, $event);

            $this->setFlash('success', 'Vous êtes inscrit à l\'événement. Un email de confirmation a été envoyé.');
            header("Location: /events/$eventId");
            exit;
        } catch (Exception $e) {
            error_log('Erreur ParticipationController::register - ' . $e->getMessage());
            $this->setFlash('error', 'Erreur lors de l\'inscription.');
            header("Location: /events/$eventId");
            exit;
        }
    }

    // ─────────────────────────────────────────
    //  Afficher les détails d'une participation
    // ─────────────────────────────────────────
    public function show(int $id): void {
        $this->auth->requireAuth();

        try {
            $participation = $this->participationModel->getById($id);

            if (!$participation) {
                http_response_code(404);
                die('Participation introuvable.');
            }

            $userId = (int)$_SESSION['user_id'];
            $userRole = $_SESSION['user_role'];

            // Vérifier les permissions
            if (!$this->canViewParticipation($participation, $userId, $userRole)) {
                http_response_code(403);
                die('Accès refusé.');
            }

            $event = $this->eventModel->getById($participation['event_id']);
            $patient = $this->patientModel->findByUserId($participation['patient_id']);
            $flash = $_SESSION['flash'] ?? null;
            unset($_SESSION['flash']);

            require_once __DIR__ . '/../views/participation_show.php';
        } catch (Exception $e) {
            error_log('Erreur ParticipationController::show - ' . $e->getMessage());
            http_response_code(500);
            die('Erreur lors du chargement.');
        }
    }

    // ─────────────────────────────────────────
    //  Modifier une participation (notes)
    // ─────────────────────────────────────────
    public function edit(int $id): void {
        $this->auth->requireRole(['admin', 'medecin']);

        try {
            $participation = $this->participationModel->getByIdForBackoffice($id);

            if (!$participation) {
                http_response_code(404);
                die('Participation introuvable.');
            }

            $csrfToken = $this->generateCsrfToken();
            $evenements = $this->eventModel->getAll();
            $statuts = ['inscrit', 'présent', 'absent'];

            $sessionOld = $_SESSION['old'] ?? [];
            $errors = $_SESSION['participation_errors'] ?? [];
            unset($_SESSION['old'], $_SESSION['participation_errors']);

            $defaults = [
                'id' => (int)$participation['id'],
                'nom' => $participation['nom'] ?? '',
                'prenom' => $participation['prenom'] ?? '',
                'email' => $participation['email'] ?? '',
                'telephone' => $participation['telephone'] ?? '',
                'profession' => '',
                'evenement_id' => (int)$participation['event_id'],
                'statut' => $participation['statut'] ?? 'inscrit',
            ];
            $old = array_merge($defaults, $sessionOld);
            if (isset($sessionOld['id'])) {
                $old['id'] = (int)$sessionOld['id'];
            }

            require_once __DIR__ . '/../views/backoffice/participation/edit.php';
        } catch (Throwable $e) {
            error_log('Erreur ParticipationController::edit - ' . $e->getMessage());
            $this->setFlash('error', 'Erreur lors du chargement.');
            header('Location: index.php?page=participations');
            exit;
        }
    }

    // ─────────────────────────────────────────
    //  Mettre à jour une participation
    // ─────────────────────────────────────────
    public function update(int $id): void {
        $this->auth->requireRole(['admin', 'medecin']);

        $redirectEdit = 'Location: index.php?page=participations&action=edit&id=' . $id;

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header($redirectEdit);
            exit;
        }

        if (!$this->verifyCsrfToken($_POST['csrf_token'] ?? '')) {
            $this->setFlash('error', 'Erreur de sécurité.');
            header($redirectEdit);
            exit;
        }

        try {
            $participation = $this->participationModel->getByIdForBackoffice($id);

            if (!$participation) {
                http_response_code(404);
                die('Participation introuvable.');
            }

            $nom = trim($_POST['nom'] ?? '');
            $prenom = trim($_POST['prenom'] ?? '');
            $email = strtolower(trim($_POST['email'] ?? ''));
            $telephone = trim($_POST['telephone'] ?? '');
            $profession = trim($_POST['profession'] ?? '');
            $eventId = (int)($_POST['evenement_id'] ?? 0);
            $statut = trim($_POST['statut'] ?? '');

            $errors = $this->validateParticipationBackoffice([
                'nom' => $nom,
                'prenom' => $prenom,
                'email' => $email,
                'telephone' => $telephone,
                'evenement_id' => $eventId,
                'statut' => $statut,
            ]);
            if ($profession === '' || mb_strlen($profession) < 2) {
                $errors['profession'] = 'Profession requise (2 caractères minimum).';
            }

            if (!empty($errors)) {
                $_SESSION['old'] = [
                    'id' => $id,
                    'nom' => $nom,
                    'prenom' => $prenom,
                    'email' => $email,
                    'telephone' => $telephone,
                    'profession' => $profession,
                    'evenement_id' => $eventId,
                    'statut' => $statut,
                ];
                $_SESSION['participation_errors'] = $errors;
                header($redirectEdit);
                exit;
            }

            $eventRow = $this->eventModel->getById($eventId);
            if (!$eventRow) {
                $this->setFlash('error', 'Événement introuvable.');
                $_SESSION['old'] = [
                    'id' => $id,
                    'nom' => $nom,
                    'prenom' => $prenom,
                    'email' => $email,
                    'telephone' => $telephone,
                    'profession' => $profession,
                    'evenement_id' => $eventId,
                    'statut' => $statut,
                ];
                header($redirectEdit);
                exit;
            }

            $dup = $this->participationModel->findOtherByEventAndUser(
                $eventId,
                (int)$participation['user_id'],
                $id
            );
            if ($dup) {
                $this->setFlash('error', 'Ce participant est déjà inscrit à cet événement.');
                $_SESSION['old'] = [
                    'id' => $id,
                    'nom' => $nom,
                    'prenom' => $prenom,
                    'email' => $email,
                    'telephone' => $telephone,
                    'profession' => $profession,
                    'evenement_id' => $eventId,
                    'statut' => $statut,
                ];
                header($redirectEdit);
                exit;
            }

            $otherUser = $this->userModel->findByEmail($email);
            if ($otherUser && (int)$otherUser['id'] !== (int)$participation['user_id']) {
                $this->setFlash('error', 'Cet email est déjà utilisé par un autre compte.');
                $_SESSION['old'] = [
                    'id' => $id,
                    'nom' => $nom,
                    'prenom' => $prenom,
                    'email' => $email,
                    'telephone' => $telephone,
                    'profession' => $profession,
                    'evenement_id' => $eventId,
                    'statut' => $statut,
                ];
                header($redirectEdit);
                exit;
            }

            $uid = (int)$participation['user_id'];
            $userOk = $this->userModel->update($uid, [
                'nom' => $nom,
                'prenom' => $prenom,
                'email' => $email,
                'telephone' => $telephone !== '' ? $telephone : null,
            ]);

            if (!$userOk) {
                throw new RuntimeException('Mise à jour utilisateur refusée.');
            }

            $partOk = $this->participationModel->updateParticipationBackoffice($id, $eventId, $statut);

            if (!$partOk) {
                throw new RuntimeException('Mise à jour participation refusée.');
            }

            if (!empty($_SESSION['user_id'])) {
                $this->logAction((int)$_SESSION['user_id'], 'Modification participation', 'Participation #' . $id . ' modifiée');
            }

            $this->setFlash('success', 'Participation mise à jour.');
            header('Location: index.php?page=participations');
            exit;
        } catch (Throwable $e) {
            error_log('Erreur ParticipationController::update - ' . $e->getMessage());
            $this->setFlash('error', 'Erreur lors de la mise à jour.');
            header($redirectEdit);
            exit;
        }
    }

    // ─────────────────────────────────────────
    //  Annuler une participation
    // ─────────────────────────────────────────
    public function cancel(int $id): void {
        $this->auth->requireAuth();

        try {
            $participation = $this->participationModel->getById($id);

            if (!$participation) {
                http_response_code(404);
                die('Participation introuvable.');
            }

            $userId = (int)$_SESSION['user_id'];
            $userRole = $_SESSION['user_role'];

            // Vérifier les permissions
            if ($userRole === 'patient' && $participation['patient_id'] !== $userId) {
                http_response_code(403);
                die('Accès refusé.');
            }

            if ($participation['statut'] === 'annulé') {
                $this->setFlash('error', 'Cette participation est déjà annulée.');
                header("Location: /participations/$id");
                exit;
            }

            $event = $this->eventModel->getById($participation['event_id']);

            // Vérifier le délai d'annulation (24h avant)
            $eventDateTime = new DateTime($event['date_debut'] . ' ' . $event['heure_debut']);
            $now = new DateTime();
            $diff = $eventDateTime->diff($now);

            if ($userRole === 'patient' && $diff->h <= 24 && $eventDateTime > $now) {
                $this->setFlash('error', 'Annulation impossible : moins de 24h avant l\'événement.');
                header("Location: /participations/$id");
                exit;
            }

            $raison = htmlspecialchars(trim($_POST['raison'] ?? ''), ENT_QUOTES, 'UTF-8');

            $this->participationModel->update($id, [
                'statut' => 'annulé',
                'raison_annulation' => $raison,
                'date_annulation' => date('Y-m-d H:i:s'),
            ]);

            $this->logAction($userId, 'Annulation participation', "Participation #$id annulée - Raison: $raison");

            // Envoyer email d'annulation
            $patient = $this->patientModel->findByUserId($participation['patient_id']);
            $this->sendCancellationEmail($patient, $event);

            $this->setFlash('success', 'Votre inscription a été annulée.');
            header("Location: /events/$participation[event_id]");
            exit;
        } catch (Exception $e) {
            error_log('Erreur ParticipationController::cancel - ' . $e->getMessage());
            $this->setFlash('error', 'Erreur lors de l\'annulation.');
            header("Location: /participations/$id");
            exit;
        }
    }

    // ─────────────────────────────────────────
    //  Marquer présent/absent (médecin)
    // ─────────────────────────────────────────
    public function markAttendance(int $id): void {
        $this->auth->requireRole(['admin', 'medecin']);

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            die('Méthode non autorisée.');
        }

        try {
            $participation = $this->participationModel->getById($id);

            if (!$participation) {
                echo json_encode(['error' => 'Participation introuvable']);
                exit;
            }

            $presence = $_POST['presence'] ?? null; // 'présent' ou 'absent'

            if (!in_array($presence, ['présent', 'absent'])) {
                echo json_encode(['error' => 'Valeur invalide']);
                exit;
            }

            $this->participationModel->update($id, [
                'presence' => $presence,
                'date_presence' => date('Y-m-d H:i:s'),
            ]);

            $this->logAction($_SESSION['user_id'], 'Marque de présence', "Participation #$id - $presence");

            echo json_encode([
                'success' => true,
                'message' => 'Présence enregistrée',
                'presence' => $presence,
            ]);
            exit;
        } catch (Exception $e) {
            error_log('Erreur markAttendance - ' . $e->getMessage());
            echo json_encode(['error' => 'Erreur serveur']);
            exit;
        }
    }

    // ─────────────────────────────────────────
    //  Supprimer une participation (admin)
    // ─────────────────────────────────────────
    public function delete(int $id): void {
        $this->auth->requireRole('admin');

        try {
            $participation = $this->participationModel->getById($id);

            if (!$participation) {
                http_response_code(404);
                die('Participation introuvable.');
            }

            $this->participationModel->delete($id);

            $this->logAction($_SESSION['user_id'], 'Suppression participation', "Participation #$id supprimée");

            $this->setFlash('success', 'Participation supprimée.');
            header('Location: index.php?page=participations');
            exit;
        } catch (Exception $e) {
            error_log('Erreur ParticipationController::delete - ' . $e->getMessage());
            $this->setFlash('error', 'Erreur lors de la suppression.');
            header('Location: index.php?page=participations');
            exit;
        }
    }

    // ─────────────────────────────────────────
    //  Liste des participants (événement)
    // ─────────────────────────────────────────
    public function eventParticipants(int $eventId): void {
        $this->auth->requireAuth();

        try {
            $event = $this->eventModel->getById($eventId);

            if (!$event) {
                http_response_code(404);
                die('Événement introuvable.');
            }

            $userId = (int)$_SESSION['user_id'];
            $userRole = $_SESSION['user_role'];

            // Vérifier les permissions
            if ($userRole !== 'admin' && (int)$event['createur_id'] !== $userId) {
                http_response_code(403);
                die('Accès refusé.');
            }

            $filter = $_GET['filter'] ?? 'confirmé'; // confirmé, en attente, annulé, présent, absent
            $participants = $this->participationModel->getByEvent($eventId, $filter);

            $flash = $_SESSION['flash'] ?? null;
            unset($_SESSION['flash']);

            require_once __DIR__ . '/../views/event_participants.php';
        } catch (Exception $e) {
            error_log('Erreur ParticipationController::eventParticipants - ' . $e->getMessage());
            http_response_code(500);
            die('Erreur lors du chargement.');
        }
    }

    // ─────────────────────────────────────────
    //  Exporter les participants (CSV)
    // ─────────────────────────────────────────
    public function exportParticipants(int $eventId): void {
        $this->auth->requireAuth();

        try {
            $event = $this->eventModel->getById($eventId);

            if (!$event) {
                http_response_code(404);
                die('Événement introuvable.');
            }

            $userId = (int)$_SESSION['user_id'];
            $userRole = $_SESSION['user_role'];

            if ($userRole !== 'admin' && (int)$event['createur_id'] !== $userId) {
                http_response_code(403);
                die('Accès refusé.');
            }

            $participants = $this->participationModel->getByEvent($eventId, 'confirmé');

            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename="participants_' . $eventId . '_' . date('Y-m-d') . '.csv"');

            $output = fopen('php://output', 'w');
            
            // En-têtes
            fputcsv($output, [
                'ID',
                'Nom',
                'Prénom',
                'Email',
                'Téléphone',
                'Date inscription',
                'Statut',
                'Présence',
            ], ';');

            // Données
            foreach ($participants as $p) {
                $patient = $this->patientModel->findByUserId($p['patient_id']);
                fputcsv($output, [
                    $p['id'],
                    $patient['nom'] ?? '',
                    $patient['prenom'] ?? '',
                    $p['email'],
                    $p['telephone'],
                    $p['date_inscription'],
                    ucfirst($p['statut']),
                    ucfirst($p['presence'] ?? 'Non marqué'),
                ], ';');
            }

            fclose($output);

            $this->logAction($_SESSION['user_id'], 'Export participants', "Export événement #$eventId");
            exit;
        } catch (Exception $e) {
            error_log('Erreur exportParticipants - ' . $e->getMessage());
            http_response_code(500);
            die('Erreur lors de l\'export.');
        }
    }

    // ─────────────────────────────────────────
    //  API - Statistiques de participation
    // ─────────────────────────────────────────
    public function apiStats(int $eventId): void {
        header('Content-Type: application/json');
        $this->auth->requireAuth();

        try {
            $event = $this->eventModel->getById($eventId);

            if (!$event) {
                echo json_encode(['error' => 'Événement introuvable']);
                exit;
            }

            $stats = $this->participationModel->getStats($eventId);

            echo json_encode([
                'success' => true,
                'stats' => $stats,
                'event_capacity' => $event['nombre_places_max'],
            ]);
            exit;
        } catch (Exception $e) {
            error_log('Erreur apiStats - ' . $e->getMessage());
            echo json_encode(['error' => 'Erreur serveur']);
            exit;
        }
    }

    // ─────────────────────────────────────────
    //  Bulk update - Marquer présents
    // ─────────────────────────────────────────
    public function bulkMarkAttendance(): void {
        $this->auth->requireRole(['admin', 'medecin']);

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            die('Méthode non autorisée.');
        }

        try {
            $ids = $_POST['ids'] ?? [];
            $presence = $_POST['presence'] ?? 'présent'; // présent ou absent

            if (empty($ids) || !is_array($ids)) {
                echo json_encode(['error' => 'Aucune participation sélectionnée']);
                exit;
            }

            if (!in_array($presence, ['présent', 'absent'])) {
                echo json_encode(['error' => 'Valeur invalide']);
                exit;
            }

            $count = 0;
            foreach ($ids as $id) {
                $participation = $this->participationModel->getById((int)$id);
                if ($participation) {
                    $this->participationModel->update((int)$id, [
                        'presence' => $presence,
                        'date_presence' => date('Y-m-d H:i:s'),
                    ]);
                    $count++;
                }
            }

            $this->logAction($_SESSION['user_id'], 'Bulk marque présence', "$count participation(s) marquées $presence");

            echo json_encode([
                'success' => true,
                'message' => "$count participation(s) mise(s) à jour",
                'count' => $count,
            ]);
            exit;
        } catch (Exception $e) {
            error_log('Erreur bulkMarkAttendance - ' . $e->getMessage());
            echo json_encode(['error' => 'Erreur serveur']);
            exit;
        }
    }

    // ─────────────────────────────────────────
    //  Mes événements (patient)
    // ─────────────────────────────────────────
    public function myEvents(): void {
        $this->auth->requireRole('patient');

        try {
            $patientId = (int)$_SESSION['user_id'];
            $filter = $_GET['filter'] ?? 'upcoming'; // upcoming, past, all

            $participations = match ($filter) {
                'upcoming' => $this->participationModel->getUpcomingByPatient($patientId),
                'past' => $this->participationModel->getPastByPatient($patientId),
                'all' => $this->participationModel->getAllByPatient($patientId),
                default => $this->participationModel->getUpcomingByPatient($patientId),
            };

            $flash = $_SESSION['flash'] ?? null;
            unset($_SESSION['flash']);

            require_once __DIR__ . '/../views/frontoffice/my_participations.php';
        } catch (Exception $e) {
            error_log('Erreur ParticipationController::myEvents - ' . $e->getMessage());
            $this->setFlash('error', 'Erreur lors du chargement.');
            header('Location: /patient/dashboard');
            exit;
        }
    }

    // ─────────────────────────────────────────
    //  Helpers privés
    // ─────────────────────────────────────────
    private function validateParticipation(array $data): array {
        $errors = [];

        if (!in_array($data['statut'], ['confirmé', 'en attente', 'annulé'])) {
            $errors[] = 'Statut invalide.';
        }

        if (!empty($data['presence']) && !in_array($data['presence'], ['présent', 'absent'])) {
            $errors[] = 'Valeur de présence invalide.';
        }

        return $errors;
    }

    /** @return array<string, string> */
    private function validateParticipationBackoffice(array $data): array {
        $errors = [];
        $allowedStatuts = ['inscrit', 'présent', 'absent'];

        if ($data['nom'] === '' || mb_strlen($data['nom']) < 2) {
            $errors['nom'] = 'Nom requis (2 caractères minimum).';
        }
        if ($data['prenom'] === '' || mb_strlen($data['prenom']) < 2) {
            $errors['prenom'] = 'Prénom requis (2 caractères minimum).';
        }
        if ($data['email'] === '' || !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Email invalide.';
        }
        if ($data['telephone'] === '' || mb_strlen($data['telephone']) < 8) {
            $errors['telephone'] = 'Téléphone requis (8 caractères minimum).';
        }
        if ((int)$data['evenement_id'] <= 0) {
            $errors['evenement_id'] = 'Événement requis.';
        }
        if (!in_array($data['statut'], $allowedStatuts, true)) {
            $errors['statut'] = 'Statut invalide.';
        }

        return $errors;
    }

    /** @return array<string, string> */
    private function validateParticipationCreate(array $data): array {
        $errors = $this->validateParticipationBackoffice($data);
        $prof = trim($data['profession'] ?? '');
        if ($prof === '' || mb_strlen($prof) < 2) {
            $errors['profession'] = 'Profession requise (2 caractères minimum).';
        }
        return $errors;
    }

    private function canViewParticipation($participation, $userId, $userRole): bool {
        if ($userRole === 'admin') {
            return true;
        }

        if ($userRole === 'patient') {
            return $participation['patient_id'] === $userId;
        }

        if ($userRole === 'medecin') {
            $event = $this->eventModel->getById($participation['event_id']);
            return (int)$event['createur_id'] === $userId;
        }

        return false;
    }

    private function sendConfirmationEmail($patient, $event): void {
        try {
            $to = $patient['email'] ?? '';
            $subject = "Confirmation d'inscription - " . $event['titre'];
            
            $message = <<<HTML
<h2>Confirmation d'inscription</h2>
<p>Bonjour {$patient['prenom']} {$patient['nom']},</p>
<p>Vous êtes maintenant inscrit à l'événement <strong>{$event['titre']}</strong></p>

<h3>Détails de l'événement :</h3>
<ul>
    <li><strong>Date :</strong> {$event['date_debut']} à {$event['heure_debut']}</li>
    <li><strong>Lieu :</strong> {$event['lieu']}</li>
    <li><strong>Type :</strong> {$event['type']}</li>
</ul>

<p>{$event['description']}</p>

<p>Cordialement,<br>L'équipe Valorys</p>
HTML;

            // Configuration email (à adapter selon votre système)
            $headers = "MIME-Version: 1.0\r\n";
            $headers .= "Content-type: text/html; charset=UTF-8\r\n";
            
            // mail($to, $subject, $message, $headers);
        } catch (Exception $e) {
            error_log('Erreur sendConfirmationEmail: ' . $e->getMessage());
        }
    }

    private function sendCancellationEmail($patient, $event): void {
        try {
            $to = $patient['email'] ?? '';
            $subject = "Annulation d'inscription - " . $event['titre'];
            
            $message = <<<HTML
<h2>Annulation d'inscription</h2>
<p>Bonjour {$patient['prenom']} {$patient['nom']},</p>
<p>Votre inscription à l'événement <strong>{$event['titre']}</strong> a été annulée.</p>

<p>Si vous avez des questions, n'hésitez pas à nous contacter.</p>

<p>Cordialement,<br>L'équipe Valorys</p>
HTML;

            $headers = "MIME-Version: 1.0\r\n";
            $headers .= "Content-type: text/html; charset=UTF-8\r\n";
            
            // mail($to, $subject, $message, $headers);
        } catch (Exception $e) {
            error_log('Erreur sendCancellationEmail: ' . $e->getMessage());
        }
    }

    private function generateCsrfToken(): string {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    private function verifyCsrfToken(string $token): bool {
        return !empty($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
    }

    private function setFlash(string $type, string $message): void {
        $_SESSION['flash'] = ['type' => $type, 'message' => $message];
    }

    private function logAction(int $userId, string $action, string $description): void {
        try {
            $sql = "INSERT INTO logs (user_id, action, description, ip_address, created_at)
                    VALUES (:user_id, :action, :description, :ip, NOW())";
            $this->db->execute($sql, [
                'user_id' => $userId,
                'action' => $action,
                'description' => $description,
                'ip' => $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0',
            ]);
        } catch (Exception $e) {
            error_log('Erreur logAction: ' . $e->getMessage());
        }
    }
}
?>
