<?php

require_once __DIR__ . '/../models/Event.php';
require_once __DIR__ . '/../config/database.php';  // ✅require_once __DIR__ . '/AuthController.php';

class EventController {

    private Event $eventModel;
    private AuthController $auth;
    private Database $db;

    public function __construct() {
        $this->eventModel = new Event();
        $this->auth = new AuthController();
        $this->db = Database::getInstance();
    }

    // ─────────────────────────────────────────
    //  Liste des événements (tous les utilisateurs)
    // ─────────────────────────────────────────
    public function index(): void {
        $this->auth->requireAuth();

        try {
            $filter = $_GET['filter'] ?? 'upcoming'; // upcoming, past, all
            $category = $_GET['category'] ?? null;
            $userId = (int)$_SESSION['user_id'];
            $userRole = $_SESSION['user_role'];

            $events = match ($filter) {
                'upcoming' => $this->eventModel->getUpcomingEvents($category),
                'past' => $this->eventModel->getPastEvents($category),
                'all' => $this->eventModel->getAllEvents($category),
                default => $this->eventModel->getUpcomingEvents($category),
            };

            // Filtrer par rôle si nécessaire
            if ($userRole === 'patient') {
                $events = array_filter($events, fn($e) => in_array($e['type'], ['webinaire', 'atelier', 'sensibilisation']));
            }

            $categories = $this->eventModel->getCategories();
            $flash = $_SESSION['flash'] ?? null;
            unset($_SESSION['flash']);

            require_once __DIR__ . '/../views/events_list.php';
        } catch (Exception $e) {
            error_log('Erreur EventController::index - ' . $e->getMessage());
            $this->setFlash('error', 'Erreur lors du chargement des événements.');
            header('Location: /dashboard');
            exit;
        }
    }

    // ─────────────────────────────────────────
    //  Afficher un événement
    // ─────────────────────────────────────────
    public function show(int $id): void {
        $this->auth->requireAuth();

        try {
            $event = $this->eventModel->getById($id);

            if (!$event) {
                http_response_code(404);
                require_once __DIR__ . '/../views/errors/404.php';
                exit;
            }

            $participants = $this->eventModel->getParticipants($id);
            $isParticipating = $this->eventModel->isUserParticipant($id, (int)$_SESSION['user_id']);
            $participantCount = count($participants);
            $flash = $_SESSION['flash'] ?? null;
            unset($_SESSION['flash']);

            require_once __DIR__ . '/../views/event_show.php';
        } catch (Exception $e) {
            error_log('Erreur EventController::show - ' . $e->getMessage());
            http_response_code(500);
            die('Erreur lors du chargement.');
        }
    }

    // ─────────────────────────────────────────
    //  Créer un événement (admin/médecin)
    // ─────────────────────────────────────────
    public function create(): void {
        $this->auth->requireRole(['admin', 'medecin']);

        try {
            $csrfToken = $this->generateCsrfToken();
            $categories = $this->eventModel->getCategories();
            $old = $_SESSION['old'] ?? null;
            $flash = $_SESSION['flash'] ?? null;
            unset($_SESSION['old'], $_SESSION['flash']);

            require_once __DIR__ . '/../views/backoffice/event_form.php';
        } catch (Exception $e) {
            error_log('Erreur EventController::create - ' . $e->getMessage());
            $this->setFlash('error', 'Erreur lors du chargement du formulaire.');
            header('Location: /events');
            exit;
        }
    }

    // ─────────────────────────────────────────
    //  Enregistrer un événement (admin/médecin)
    // ─────────────────────────────────────────
    public function store(): void {
        $this->auth->requireRole(['admin', 'medecin']);

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /events/create');
            exit;
        }

        if (!$this->verifyCsrfToken($_POST['csrf_token'] ?? '')) {
            $this->setFlash('error', 'Erreur de sécurité. Veuillez réessayer.');
            header('Location: /events/create');
            exit;
        }

        try {
            $data = [
                'titre' => htmlspecialchars(trim($_POST['titre'] ?? ''), ENT_QUOTES, 'UTF-8'),
                'description' => htmlspecialchars(trim($_POST['description'] ?? ''), ENT_QUOTES, 'UTF-8'),
                'type' => $_POST['type'] ?? 'webinaire', // webinaire, atelier, conférence, sensibilisation
                'date_debut' => $_POST['date_debut'] ?? '',
                'heure_debut' => $_POST['heure_debut'] ?? '',
                'date_fin' => $_POST['date_fin'] ?? '',
                'heure_fin' => $_POST['heure_fin'] ?? '',
                'lieu' => htmlspecialchars(trim($_POST['lieu'] ?? ''), ENT_QUOTES, 'UTF-8'),
                'lien_visioconference' => filter_var($_POST['lien_visioconference'] ?? '', FILTER_VALIDATE_URL) ?: null,
                'categorie' => $_POST['categorie'] ?? '',
                'nombre_places_max' => (int)($_POST['nombre_places_max'] ?? 0),
                'createur_id' => (int)$_SESSION['user_id'],
                'createur_type' => $_SESSION['user_role'],
                'image' => null,
            ];

            $errors = $this->validateEvent($data);

            if (!empty($errors)) {
                $this->setFlash('error', implode('<br>', $errors));
                $_SESSION['old'] = $data;
                header('Location: /events/create');
                exit;
            }

            // Traiter l'upload d'image
            if (!empty($_FILES['image']['name'])) {
                $imageData = $this->handleImageUpload($_FILES['image']);
                if (is_array($imageData) && isset($imageData['error'])) {
                    $this->setFlash('error', $imageData['error']);
                    $_SESSION['old'] = $data;
                    header('Location: /events/create');
                    exit;
                }
                $data['image'] = $imageData;
            }

            $eventId = $this->eventModel->create($data);

            if (!$eventId) {
                throw new Exception('Erreur lors de la création.');
            }

            $this->logAction($_SESSION['user_id'], 'Création événement', "Événement #$eventId créé - {$data['titre']}");

            $this->setFlash('success', 'Événement créé avec succès.');
            header('Location: /events/' . $eventId);
            exit;
        } catch (Exception $e) {
            error_log('Erreur EventController::store - ' . $e->getMessage());
            $this->setFlash('error', 'Erreur lors de la création.');
            $_SESSION['old'] = $data ?? [];
            header('Location: /events/create');
            exit;
        }
    }

    // ─────────────────────────────────────────
    //  Éditer un événement (créateur/admin)
    // ─────────────────────────────────────────
    public function edit(int $id): void {
        $this->auth->requireAuth();

        try {
            $event = $this->eventModel->getById($id);

            if (!$event) {
                http_response_code(404);
                die('Événement introuvable.');
            }

            // Vérifier les permissions
            if (!$this->canEditEvent($event)) {
                http_response_code(403);
                die('Accès refusé.');
            }

            $csrfToken = $this->generateCsrfToken();
            $categories = $this->eventModel->getCategories();
            $old = $_SESSION['old'] ?? null;
            $flash = $_SESSION['flash'] ?? null;
            unset($_SESSION['old'], $_SESSION['flash']);

            require_once __DIR__ . '/../views/backoffice/event_form_edit.php';
        } catch (Exception $e) {
            error_log('Erreur EventController::edit - ' . $e->getMessage());
            $this->setFlash('error', 'Erreur lors du chargement.');
            header('Location: /events');
            exit;
        }
    }

    // ─────────────────────────────────────────
    //  Mettre à jour un événement
    // ─────────────────────────────────────────
    public function update(int $id): void {
        $this->auth->requireAuth();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: /events/$id/edit");
            exit;
        }

        if (!$this->verifyCsrfToken($_POST['csrf_token'] ?? '')) {
            $this->setFlash('error', 'Erreur de sécurité.');
            header("Location: /events/$id/edit");
            exit;
        }

        try {
            $event = $this->eventModel->getById($id);

            if (!$event) {
                http_response_code(404);
                die('Événement introuvable.');
            }

            if (!$this->canEditEvent($event)) {
                http_response_code(403);
                die('Accès refusé.');
            }

            $data = [
                'titre' => htmlspecialchars(trim($_POST['titre'] ?? ''), ENT_QUOTES, 'UTF-8'),
                'description' => htmlspecialchars(trim($_POST['description'] ?? ''), ENT_QUOTES, 'UTF-8'),
                'type' => $_POST['type'] ?? 'webinaire',
                'date_debut' => $_POST['date_debut'] ?? '',
                'heure_debut' => $_POST['heure_debut'] ?? '',
                'date_fin' => $_POST['date_fin'] ?? '',
                'heure_fin' => $_POST['heure_fin'] ?? '',
                'lieu' => htmlspecialchars(trim($_POST['lieu'] ?? ''), ENT_QUOTES, 'UTF-8'),
                'lien_visioconference' => filter_var($_POST['lien_visioconference'] ?? '', FILTER_VALIDATE_URL) ?: null,
                'categorie' => $_POST['categorie'] ?? '',
                'nombre_places_max' => (int)($_POST['nombre_places_max'] ?? 0),
            ];

            $errors = $this->validateEventUpdate($data);

            if (!empty($errors)) {
                $this->setFlash('error', implode('<br>', $errors));
                $_SESSION['old'] = $data;
                header("Location: /events/$id/edit");
                exit;
            }

            // Traiter l'upload d'image
            if (!empty($_FILES['image']['name'])) {
                $imageData = $this->handleImageUpload($_FILES['image']);
                if (is_array($imageData) && isset($imageData['error'])) {
                    $this->setFlash('error', $imageData['error']);
                    $_SESSION['old'] = $data;
                    header("Location: /events/$id/edit");
                    exit;
                }
                $data['image'] = $imageData;

                // Supprimer l'ancienne image
                if (!empty($event['image'])) {
                    @unlink(__DIR__ . '/../../public/uploads/events/' . $event['image']);
                }
            }

            $this->eventModel->update($id, $data);

            $this->logAction($_SESSION['user_id'], 'Modification événement', "Événement #$id modifié");

            $this->setFlash('success', 'Événement mis à jour.');
            header('Location: /events/' . $id);
            exit;
        } catch (Exception $e) {
            error_log('Erreur EventController::update - ' . $e->getMessage());
            $this->setFlash('error', 'Erreur lors de la mise à jour.');
            header("Location: /events/$id/edit");
            exit;
        }
    }

    // ─────────────────────────────────────────
    //  Supprimer un événement (créateur/admin)
    // ─────────────────────────────────────────
    public function delete(int $id): void {
        $this->auth->requireAuth();

        try {
            $event = $this->eventModel->getById($id);

            if (!$event) {
                http_response_code(404);
                die('Événement introuvable.');
            }

            if (!$this->canDeleteEvent($event)) {
                http_response_code(403);
                die('Accès refusé.');
            }

            // Supprimer l'image
            if (!empty($event['image'])) {
                @unlink(__DIR__ . '/../../public/uploads/events/' . $event['image']);
            }

            $this->eventModel->delete($id);

            $this->logAction($_SESSION['user_id'], 'Suppression événement', "Événement #$id supprimé");

            $this->setFlash('success', 'Événement supprimé.');
            header('Location: /events');
            exit;
        } catch (Exception $e) {
            error_log('Erreur EventController::delete - ' . $e->getMessage());
            $this->setFlash('error', 'Erreur lors de la suppression.');
            header('Location: /events');
            exit;
        }
    }

    // ─────────────────────────────────────────
    //  S'inscrire à un événement (patient)
    // ─────────────────────────────────────────
    public function register(int $id): void {
        $this->auth->requireRole('patient');

        try {
            $event = $this->eventModel->getById($id);

            if (!$event) {
                http_response_code(404);
                die('Événement introuvable.');
            }

            $userId = (int)$_SESSION['user_id'];

            // Vérifier déjà inscrit
            if ($this->eventModel->isUserParticipant($id, $userId)) {
                $this->setFlash('error', 'Vous êtes déjà inscrit à cet événement.');
                header("Location: /events/$id");
                exit;
            }

            // Vérifier les places disponibles
            $participants = $this->eventModel->getParticipants($id);
            if ($event['nombre_places_max'] > 0 && count($participants) >= $event['nombre_places_max']) {
                $this->setFlash('error', 'Cet événement est complet.');
                header("Location: /events/$id");
                exit;
            }

            // Vérifier que l'événement n'est pas passé
            $eventDateTime = new DateTime($event['date_debut'] . ' ' . $event['heure_debut']);
            if ($eventDateTime < new DateTime()) {
                $this->setFlash('error', 'Cet événement est déjà passé.');
                header("Location: /events/$id");
                exit;
            }

            $this->eventModel->registerParticipant($id, $userId);

            $this->logAction($userId, 'Inscription événement', "Inscription à l'événement #$id - {$event['titre']}");

            $this->setFlash('success', 'Vous êtes inscrit à l\'événement.');
            header("Location: /events/$id");
            exit;
        } catch (Exception $e) {
            error_log('Erreur EventController::register - ' . $e->getMessage());
            $this->setFlash('error', 'Erreur lors de l\'inscription.');
            header("Location: /events/$id");
            exit;
        }
    }

    // ─────────────────────────────────────────
    //  Se désinscrire d'un événement (patient)
    // ─────────────────────────────────────────
    public function unregister(int $id): void {
        $this->auth->requireRole('patient');

        try {
            $event = $this->eventModel->getById($id);

            if (!$event) {
                http_response_code(404);
                die('Événement introuvable.');
            }

            $userId = (int)$_SESSION['user_id'];

            // Vérifier inscrit
            if (!$this->eventModel->isUserParticipant($id, $userId)) {
                $this->setFlash('error', 'Vous n\'êtes pas inscrit à cet événement.');
                header("Location: /events/$id");
                exit;
            }

            // Vérifier délai d'annulation (24h avant)
            $eventDateTime = new DateTime($event['date_debut'] . ' ' . $event['heure_debut']);
            $now = new DateTime();
            $diff = $eventDateTime->diff($now);

            if ($diff->h <= 24 && $eventDateTime > $now) {
                $this->setFlash('error', 'Annulation impossible : moins de 24h avant l\'événement.');
                header("Location: /events/$id");
                exit;
            }

            $this->eventModel->unregisterParticipant($id, $userId);

            $this->logAction($userId, 'Désinscription événement', "Désinscription de l'événement #$id");

            $this->setFlash('success', 'Vous êtes désinscrit.');
            header("Location: /events/$id");
            exit;
        } catch (Exception $e) {
            error_log('Erreur EventController::unregister - ' . $e->getMessage());
            $this->setFlash('error', 'Erreur lors de la désinscription.');
            header("Location: /events/$id");
            exit;
        }
    }

    // ─────────────────────────────────────────
    //  Publier un événement (créateur/admin)
    // ─────────────────────────────────────────
    public function publish(int $id): void {
        $this->auth->requireAuth();

        try {
            $event = $this->eventModel->getById($id);

            if (!$event) {
                http_response_code(404);
                die('Événement introuvable.');
            }

            if (!$this->canEditEvent($event)) {
                http_response_code(403);
                die('Accès refusé.');
            }

            if ($event['statut'] === 'publié') {
                $this->setFlash('error', 'Cet événement est déjà publié.');
                header("Location: /events/$id");
                exit;
            }

            $this->eventModel->update($id, [
                'statut' => 'publié',
                'date_publication' => date('Y-m-d H:i:s'),
            ]);

            $this->logAction($_SESSION['user_id'], 'Publication événement', "Événement #$id publié");

            $this->setFlash('success', 'Événement publié.');
            header("Location: /events/$id");
            exit;
        } catch (Exception $e) {
            error_log('Erreur EventController::publish - ' . $e->getMessage());
            $this->setFlash('error', 'Erreur lors de la publication.');
            header("Location: /events/$id");
            exit;
        }
    }

    // ─────────────────────────────────────────
    //  Annuler un événement (créateur/admin)
    // ─────────────────────────────────────────
    public function cancel(int $id): void {
        $this->auth->requireAuth();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            die('Méthode non autorisée.');
        }

        try {
            $event = $this->eventModel->getById($id);

            if (!$event) {
                http_response_code(404);
                die('Événement introuvable.');
            }

            if (!$this->canEditEvent($event)) {
                http_response_code(403);
                die('Accès refusé.');
            }

            if ($event['statut'] === 'annulé') {
                $this->setFlash('error', 'Cet événement est déjà annulé.');
                header("Location: /events/$id");
                exit;
            }

            $raison = htmlspecialchars(trim($_POST['raison'] ?? ''), ENT_QUOTES, 'UTF-8');

            if (empty($raison)) {
                $this->setFlash('error', 'Une raison d\'annulation est obligatoire.');
                header("Location: /events/$id");
                exit;
            }

            $this->eventModel->update($id, [
                'statut' => 'annulé',
                'raison_annulation' => $raison,
                'date_annulation' => date('Y-m-d H:i:s'),
            ]);

            $this->logAction($_SESSION['user_id'], 'Annulation événement', "Événement #$id annulé - Raison: $raison");

            $this->setFlash('success', 'Événement annulé.');
            header("Location: /events/$id");
            exit;
        } catch (Exception $e) {
            error_log('Erreur EventController::cancel - ' . $e->getMessage());
            $this->setFlash('error', 'Erreur lors de l\'annulation.');
            header("Location: /events/$id");
            exit;
        }
    }

    // ─────────────────────────────────────────
    //  Mes inscriptions (patient)
    // ─────────────────────────────────────────
    public function myRegistrations(): void {
        $this->auth->requireRole('patient');

        try {
            $userId = (int)$_SESSION['user_id'];
            $filter = $_GET['filter'] ?? 'upcoming'; // upcoming, past, all

            $events = match ($filter) {
                'upcoming' => $this->eventModel->getUpcomingEventsByParticipant($userId),
                'past' => $this->eventModel->getPastEventsByParticipant($userId),
                'all' => $this->eventModel->getAllEventsByParticipant($userId),
                default => $this->eventModel->getUpcomingEventsByParticipant($userId),
            };

            $flash = $_SESSION['flash'] ?? null;
            unset($_SESSION['flash']);

            require_once __DIR__ . '/../views/frontoffice/my_events.php';
        } catch (Exception $e) {
            error_log('Erreur EventController::myRegistrations - ' . $e->getMessage());
            $this->setFlash('error', 'Erreur lors du chargement.');
            header('Location: /patient/dashboard');
            exit;
        }
    }

    // ─────────────────────────────────────────
    //  Mes événements (créateur)
    // ─────────────────────────────────────────
    public function myEvents(): void {
        $this->auth->requireRole(['admin', 'medecin']);

        try {
            $userId = (int)$_SESSION['user_id'];
            $userRole = $_SESSION['user_role'];
            $filter = $_GET['filter'] ?? 'all'; // all, publié, brouillon, annulé

            $events = match ($filter) {
                'publié' => $this->eventModel->getEventsByCreator($userId, $userRole, 'publié'),
                'brouillon' => $this->eventModel->getEventsByCreator($userId, $userRole, 'brouillon'),
                'annulé' => $this->eventModel->getEventsByCreator($userId, $userRole, 'annulé'),
                default => $this->eventModel->getEventsByCreator($userId, $userRole),
            };

            $flash = $_SESSION['flash'] ?? null;
            unset($_SESSION['flash']);

            require_once __DIR__ . '/../views/backoffice/my_events.php';
        } catch (Exception $e) {
            error_log('Erreur EventController::myEvents - ' . $e->getMessage());
            $this->setFlash('error', 'Erreur lors du chargement.');
            header('Location: /dashboard');
            exit;
        }
    }

    // ─────────────────────────────────────────
    //  API - Événements à venir (Widget)
    // ─────────────────────────────────────────
    public function apiUpcoming(): void {
        header('Content-Type: application/json');
        $this->auth->requireAuth();

        try {
            $limit = (int)($_GET['limit'] ?? 5);
            $events = $this->eventModel->getUpcomingEvents(null, $limit);

            echo json_encode([
                'success' => true,
                'events' => $events,
            ]);
            exit;
        } catch (Exception $e) {
            error_log('Erreur apiUpcoming - ' . $e->getMessage());
            echo json_encode(['error' => 'Erreur serveur']);
            exit;
        }
    }

    // ─────────────────────────────────────────
    //  API - Recherche d'événements
    // ─────────────────────────────────────────
    public function apiSearch(): void {
        header('Content-Type: application/json');
        $this->auth->requireAuth();

        try {
            $query = htmlspecialchars($_GET['q'] ?? '', ENT_QUOTES, 'UTF-8');
            $category = $_GET['category'] ?? null;

            if (empty($query) || strlen($query) < 2) {
                echo json_encode(['error' => 'Recherche trop courte']);
                exit;
            }

            $events = $this->eventModel->search($query, $category);

            echo json_encode([
                'success' => true,
                'events' => $events,
            ]);
            exit;
        } catch (Exception $e) {
            error_log('Erreur apiSearch - ' . $e->getMessage());
            echo json_encode(['error' => 'Erreur serveur']);
            exit;
        }
    }

    // ─────────────────────────────────────────
    //  Helpers privés
    // ─────────────────────────────────────────
    private function validateEvent(array $data): array {
        $errors = [];

        if (empty($data['titre']) || strlen($data['titre']) < 3) {
            $errors[] = 'Le titre doit contenir au moins 3 caractères.';
        }

        if (empty($data['description']) || strlen($data['description']) < 10) {
            $errors[] = 'La description doit contenir au moins 10 caractères.';
        }

        if (!in_array($data['type'], ['webinaire', 'atelier', 'conférence', 'sensibilisation'])) {
            $errors[] = 'Type d\'événement invalide.';
        }

        if (empty($data['date_debut'])) {
            $errors[] = 'La date de début est obligatoire.';
        } else {
            $date = DateTime::createFromFormat('Y-m-d', $data['date_debut']);
            if (!$date || $date->format('Y-m-d') !== $data['date_debut']) {
                $errors[] = 'Format de date invalide.';
            } elseif ($date < new DateTime('today')) {
                $errors[] = 'La date doit être dans le futur.';
            }
        }

        if (empty($data['heure_debut']) || !preg_match('/^([0-1][0-9]|2[0-3]):[0-5][0-9]$/', $data['heure_debut'])) {
            $errors[] = 'Heure de début invalide.';
        }

        if (empty($data['date_fin'])) {
            $errors[] = 'La date de fin est obligatoire.';
        } else {
            $date = DateTime::createFromFormat('Y-m-d', $data['date_fin']);
            if (!$date || $date->format('Y-m-d') !== $data['date_fin']) {
                $errors[] = 'Format de date fin invalide.';
            }
        }

        if (empty($data['heure_fin']) || !preg_match('/^([0-1][0-9]|2[0-3]):[0-5][0-9]$/', $data['heure_fin'])) {
            $errors[] = 'Heure de fin invalide.';
        }

        if (!empty($data['date_debut']) && !empty($data['date_fin'])) {
            $dateDebut = DateTime::createFromFormat('Y-m-d H:i', $data['date_debut'] . ' ' . $data['heure_debut']);
            $dateFin = DateTime::createFromFormat('Y-m-d H:i', $data['date_fin'] . ' ' . $data['heure_fin']);

            if ($dateDebut && $dateFin && $dateDebut >= $dateFin) {
                $errors[] = 'La date de fin doit être après la date de début.';
            }
        }

        if (!in_array($data['type'], ['conférence']) && empty($data['lieu']) && empty($data['lien_visioconference'])) {
            $errors[] = 'Le lieu ou un lien de visioconférence est obligatoire.';
        }

        if ($data['nombre_places_max'] < 0) {
            $errors[] = 'Le nombre de places doit être positif.';
        }

        return $errors;
    }

    private function validateEventUpdate(array $data): array {
        return $this->validateEvent($data);
    }

    private function handleImageUpload($file): ?string {
        $maxSize = 5 * 1024 * 1024; // 5MB
        $allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];
        $uploadDir = __DIR__ . '/../../public/uploads/events/';

        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        if ($file['size'] > $maxSize) {
            return ['error' => 'Image trop grande (max 5MB).'];
        }

        if (!in_array($file['type'], $allowedTypes)) {
            return ['error' => 'Format d\'image non autorisé.'];
        }

        $ext = match ($file['type']) {
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
        };

        $filename = 'event_' . time() . '_' . uniqid() . '.' . $ext;
        $filepath = $uploadDir . $filename;

        if (!move_uploaded_file($file['tmp_name'], $filepath)) {
            return ['error' => 'Erreur lors du téléchargement.'];
        }

        return $filename;
    }

    private function canEditEvent($event): bool {
        $userId = (int)$_SESSION['user_id'];
        $userRole = $_SESSION['user_role'];

        return $userRole === 'admin' || ((int)$event['createur_id'] === $userId);
    }

    private function canDeleteEvent($event): bool {
        return $this->canEditEvent($event) && $event['statut'] !== 'publié';
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



// update
