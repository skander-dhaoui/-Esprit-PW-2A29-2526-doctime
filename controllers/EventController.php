<?php

require_once __DIR__ . '/../models/Event.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/AuthController.php';

use App\Models\Event;

class EventController {

    private Event $eventModel;
    private AuthController $auth;
    private Database $db;

    public function __construct() {
        $this->eventModel = new Event();
        $this->auth = new AuthController();
        $this->db = Database::getInstance();
    }

    public function index(): void {
        $this->auth->requireAuth();
        try {
            $filter   = $_GET['filter']   ?? 'upcoming';
            $category = $_GET['category'] ?? null;
            $userRole = $_SESSION['user_role'];

            $events = match ($filter) {
                'upcoming' => $this->eventModel->getUpcomingEvents($category),
                'past'     => $this->eventModel->getPastEvents($category),
                'all'      => $this->eventModel->getAllEvents($category),
                default    => $this->eventModel->getUpcomingEvents($category),
            };

            if ($userRole === 'patient') {
                $events = array_filter($events, fn($e) => in_array($e['type'], ['webinaire', 'atelier', 'sensibilisation']));
            }

            $categories = $this->eventModel->getCategories();
            $flash = $_SESSION['flash'] ?? null;
            unset($_SESSION['flash']);

            require_once __DIR__ . '/../views/frontoffice/events.php';
        } catch (Exception $e) {
            error_log('Erreur EventController::index - ' . $e->getMessage());
            $this->setFlash('error', 'Erreur lors du chargement des evenements.');
            header('Location: index.php?page=accueil');
            exit;
        }
    }

    public function show(int $id): void {
        $this->auth->requireAuth();
        try {
            $event = $this->eventModel->getById($id);
            if (!$event) {
                http_response_code(404);
                require_once __DIR__ . '/../views/errors/404.php';
                exit;
            }

            $participants     = $this->eventModel->getParticipants($id);
            $isParticipating  = $this->eventModel->isUserParticipant($id, (int)$_SESSION['user_id']);
            $participantCount = count($participants);
            $flash = $_SESSION['flash'] ?? null;
            unset($_SESSION['flash']);

            require_once __DIR__ . '/../views/frontoffice/event_detail.php';
        } catch (Exception $e) {
            error_log('Erreur EventController::show - ' . $e->getMessage());
            http_response_code(500);
            die('Erreur lors du chargement.');
        }
    }

    public function create(): void {
        $this->auth->requireRole(['admin', 'medecin']);
        try {
            $csrfToken  = $this->generateCsrfToken();
            $categories = $this->eventModel->getCategories();
            $old   = $_SESSION['old']   ?? null;
            $flash = $_SESSION['flash'] ?? null;
            unset($_SESSION['old'], $_SESSION['flash']);

            require_once __DIR__ . '/../views/backoffice/evenements/form.php';
        } catch (Exception $e) {
            error_log('Erreur EventController::create - ' . $e->getMessage());
            $this->setFlash('error', 'Erreur lors du chargement du formulaire.');
            header('Location: index.php?page=evenements_admin');
            exit;
        }
    }

    public function store(): void {
        $this->auth->requireRole(['admin', 'medecin']);

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: index.php?page=evenements_admin&action=create');
            exit;
        }

        if (!$this->verifyCsrfToken($_POST['csrf_token'] ?? '')) {
            $this->setFlash('error', 'Erreur de securite. Veuillez reessayer.');
            header('Location: index.php?page=evenements_admin&action=create');
            exit;
        }

        try {
            $data = [
                'titre'                => htmlspecialchars(trim($_POST['titre']                ?? ''), ENT_QUOTES, 'UTF-8'),
                'description'          => htmlspecialchars(trim($_POST['description']          ?? ''), ENT_QUOTES, 'UTF-8'),
                'type'                 => $_POST['type'] ?? 'webinaire',
                'date_debut'           => $_POST['date_debut']           ?? '',
                'heure_debut'          => $_POST['heure_debut']          ?? '',
                'date_fin'             => $_POST['date_fin']             ?? '',
                'heure_fin'            => $_POST['heure_fin']            ?? '',
                'lieu'                 => htmlspecialchars(trim($_POST['lieu'] ?? ''), ENT_QUOTES, 'UTF-8'),
                'lien_visioconference' => filter_var($_POST['lien_visioconference'] ?? '', FILTER_VALIDATE_URL) ?: null,
                'categorie'            => $_POST['categorie']            ?? '',
                'nombre_places_max'    => (int)($_POST['nombre_places_max'] ?? 0),
                'createur_id'          => (int)$_SESSION['user_id'],
                'createur_type'        => $_SESSION['user_role'],
                'image'                => null,
            ];

            $errors = $this->validateEvent($data);
            if (!empty($errors)) {
                $this->setFlash('error', implode('<br>', $errors));
                $_SESSION['old'] = $data;
                header('Location: index.php?page=evenements_admin&action=create');
                exit;
            }

            if (!empty($_FILES['image']['name'])) {
                $imageData = $this->handleImageUpload($_FILES['image']);
                if (is_array($imageData) && isset($imageData['error'])) {
                    $this->setFlash('error', $imageData['error']);
                    $_SESSION['old'] = $data;
                    header('Location: index.php?page=evenements_admin&action=create');
                    exit;
                }
                $data['image'] = $imageData;
            }

            $eventId = $this->eventModel->create($data);
            if (!$eventId) throw new Exception('Erreur lors de la creation.');

            $this->logAction($_SESSION['user_id'], 'Creation evenement', "Evenement #$eventId cree - {$data['titre']}");
            $this->setFlash('success', 'Evenement cree avec succes.');
            header('Location: index.php?page=evenements_admin&action=show&id=' . $eventId);
            exit;
        } catch (Exception $e) {
            error_log('Erreur EventController::store - ' . $e->getMessage());
            $this->setFlash('error', 'Erreur lors de la creation.');
            $_SESSION['old'] = $data ?? [];
            header('Location: index.php?page=evenements_admin&action=create');
            exit;
        }
    }

    public function edit(int $id): void {
        $this->auth->requireAuth();
        try {
            $event = $this->eventModel->getById($id);
            if (!$event) { http_response_code(404); die('Evenement introuvable.'); }
            if (!$this->canEditEvent($event)) { http_response_code(403); die('Acces refuse.'); }

            $csrfToken  = $this->generateCsrfToken();
            $categories = $this->eventModel->getCategories();
            $old   = $_SESSION['old']   ?? null;
            $flash = $_SESSION['flash'] ?? null;
            unset($_SESSION['old'], $_SESSION['flash']);

            require_once __DIR__ . '/../views/backoffice/evenements/form.php';
        } catch (Exception $e) {
            error_log('Erreur EventController::edit - ' . $e->getMessage());
            $this->setFlash('error', 'Erreur lors du chargement.');
            header('Location: index.php?page=evenements_admin');
            exit;
        }
    }

    public function update(int $id): void {
        $this->auth->requireAuth();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: index.php?page=evenements_admin&action=edit&id=$id");
            exit;
        }

        if (!$this->verifyCsrfToken($_POST['csrf_token'] ?? '')) {
            $this->setFlash('error', 'Erreur de securite.');
            header("Location: index.php?page=evenements_admin&action=edit&id=$id");
            exit;
        }

        try {
            $event = $this->eventModel->getById($id);
            if (!$event) { http_response_code(404); die('Evenement introuvable.'); }
            if (!$this->canEditEvent($event)) { http_response_code(403); die('Acces refuse.'); }

            $data = [
                'titre'                => htmlspecialchars(trim($_POST['titre']       ?? ''), ENT_QUOTES, 'UTF-8'),
                'description'          => htmlspecialchars(trim($_POST['description'] ?? ''), ENT_QUOTES, 'UTF-8'),
                'type'                 => $_POST['type'] ?? 'webinaire',
                'date_debut'           => $_POST['date_debut']  ?? '',
                'heure_debut'          => $_POST['heure_debut'] ?? '',
                'date_fin'             => $_POST['date_fin']    ?? '',
                'heure_fin'            => $_POST['heure_fin']   ?? '',
                'lieu'                 => htmlspecialchars(trim($_POST['lieu'] ?? ''), ENT_QUOTES, 'UTF-8'),
                'lien_visioconference' => filter_var($_POST['lien_visioconference'] ?? '', FILTER_VALIDATE_URL) ?: null,
                'categorie'            => $_POST['categorie'] ?? '',
                'nombre_places_max'    => (int)($_POST['nombre_places_max'] ?? 0),
            ];

            $errors = $this->validateEvent($data);
            if (!empty($errors)) {
                $this->setFlash('error', implode('<br>', $errors));
                $_SESSION['old'] = $data;
                header("Location: index.php?page=evenements_admin&action=edit&id=$id");
                exit;
            }

            if (!empty($_FILES['image']['name'])) {
                $imageData = $this->handleImageUpload($_FILES['image']);
                if (is_array($imageData) && isset($imageData['error'])) {
                    $this->setFlash('error', $imageData['error']);
                    $_SESSION['old'] = $data;
                    header("Location: index.php?page=evenements_admin&action=edit&id=$id");
                    exit;
                }
                $data['image'] = $imageData;
                if (!empty($event['image'])) {
                    @unlink(__DIR__ . '/../../public/uploads/events/' . $event['image']);
                }
            }

            $this->eventModel->update($id, $data);
            $this->logAction($_SESSION['user_id'], 'Modification evenement', "Evenement #$id modifie");
            $this->setFlash('success', 'Evenement mis a jour.');
            header('Location: index.php?page=evenements_admin&action=show&id=' . $id);
            exit;
        } catch (Exception $e) {
            error_log('Erreur EventController::update - ' . $e->getMessage());
            $this->setFlash('error', 'Erreur lors de la mise a jour.');
            header("Location: index.php?page=evenements_admin&action=edit&id=$id");
            exit;
        }
    }

    public function delete(int $id): void {
        $this->auth->requireAuth();
        try {
            $event = $this->eventModel->getById($id);
            if (!$event) { http_response_code(404); die('Evenement introuvable.'); }
            if (!$this->canDeleteEvent($event)) { http_response_code(403); die('Acces refuse.'); }

            if (!empty($event['image'])) {
                @unlink(__DIR__ . '/../../public/uploads/events/' . $event['image']);
            }

            $this->eventModel->delete($id);
            $this->logAction($_SESSION['user_id'], 'Suppression evenement', "Evenement #$id supprime");
            $this->setFlash('success', 'Evenement supprime.');
            header('Location: index.php?page=evenements_admin');
            exit;
        } catch (Exception $e) {
            error_log('Erreur EventController::delete - ' . $e->getMessage());
            $this->setFlash('error', 'Erreur lors de la suppression.');
            header('Location: index.php?page=evenements_admin');
            exit;
        }
    }

    public function listAdmin(): void {
        $this->auth->requireRole(['admin', 'medecin']);
        try {
            $events = $this->eventModel->getAll();
            $flash  = $_SESSION['flash'] ?? null;
            unset($_SESSION['flash']);
            require_once __DIR__ . '/../views/backoffice/evenements/list.php';
        } catch (Exception $e) {
            error_log('Erreur EventController::listAdmin - ' . $e->getMessage());
            $this->setFlash('error', 'Erreur lors du chargement.');
            header('Location: index.php?page=dashboard');
            exit;
        }
    }

    public function showAdmin(int $id): void {
        $this->auth->requireRole(['admin', 'medecin']);
        try {
            $event = $this->eventModel->getById($id);
            if (!$event) { http_response_code(404); die('Evenement introuvable.'); }
            $participants = $this->eventModel->getParticipants($id);
            $flash = $_SESSION['flash'] ?? null;
            unset($_SESSION['flash']);
            require_once __DIR__ . '/../views/backoffice/evenements/show.php';
        } catch (Exception $e) {
            error_log('Erreur EventController::showAdmin - ' . $e->getMessage());
            header('Location: index.php?page=evenements_admin');
            exit;
        }
    }

    public function myRegistrations(): void {
        $this->auth->requireRole('patient');
        try {
            $userId = (int)$_SESSION['user_id'];
            $filter = $_GET['filter'] ?? 'upcoming';

            $events = match ($filter) {
                'upcoming' => $this->eventModel->getUpcomingEventsByParticipant($userId),
                'past'     => $this->eventModel->getPastEventsByParticipant($userId),
                'all'      => $this->eventModel->getAllEventsByParticipant($userId),
                default    => $this->eventModel->getUpcomingEventsByParticipant($userId),
            };

            $flash = $_SESSION['flash'] ?? null;
            unset($_SESSION['flash']);

            require_once __DIR__ . '/../views/frontoffice/evenement_form.php';
        } catch (Exception $e) {
            error_log('Erreur EventController::myRegistrations - ' . $e->getMessage());
            $this->setFlash('error', 'Erreur lors du chargement.');
            header('Location: index.php?page=accueil');
            exit;
        }
    }

    public function myEvents(): void {
        $this->auth->requireRole(['admin', 'medecin']);
        try {
            $userId   = (int)$_SESSION['user_id'];
            $userRole = $_SESSION['user_role'];
            $filter   = $_GET['filter'] ?? 'all';

            $events = match ($filter) {
                'publie'    => $this->eventModel->getEventsByCreator($userId, $userRole, 'publie'),
                'brouillon' => $this->eventModel->getEventsByCreator($userId, $userRole, 'brouillon'),
                'annule'    => $this->eventModel->getEventsByCreator($userId, $userRole, 'annule'),
                default     => $this->eventModel->getEventsByCreator($userId, $userRole),
            };

            $flash = $_SESSION['flash'] ?? null;
            unset($_SESSION['flash']);

            require_once __DIR__ . '/../views/backoffice/evenements/list.php';
        } catch (Exception $e) {
            error_log('Erreur EventController::myEvents - ' . $e->getMessage());
            $this->setFlash('error', 'Erreur lors du chargement.');
            header('Location: index.php?page=dashboard');
            exit;
        }
    }

    public function advanced(): void {
        $this->auth->requireRole(['admin', 'medecin']);
        try {
            require_once __DIR__ . '/../models/EventAvance.php';
            $eventAvance = new EventAvance();

            // Recherche avancée
            $filtres = [
                'q'              => trim($_GET['q'] ?? ''),
                'statut'         => $_GET['statut'] ?? '',
                'date_debut_min' => $_GET['date_debut_min'] ?? '',
                'date_debut_max' => $_GET['date_debut_max'] ?? '',
                'prix_min'       => $_GET['prix_min'] ?? '',
                'prix_max'       => $_GET['prix_max'] ?? '',
                'avec_places'    => !empty($_GET['avec_places']),
                'sponsor_id'     => $_GET['sponsor_id'] ?? '',
                'tri'            => $_GET['tri'] ?? 'date_debut',
                'ordre'          => $_GET['ordre'] ?? 'DESC',
            ];
            $hasSearch = !empty($filtres['q']) || !empty($filtres['statut'])
                      || !empty($filtres['date_debut_min']) || !empty($filtres['date_debut_max'])
                      || !empty($filtres['prix_min']) || !empty($filtres['prix_max'])
                      || $filtres['avec_places'] || !empty($filtres['sponsor_id']);

            $searchResults = $hasSearch ? $eventAvance->recherche($filtres) : [];

            // Données du tableau de bord
            $vueEnsemble = $eventAvance->getVueEnsemble();
            $sponsors     = $eventAvance->getSponsors();
            $statuts      = $eventAvance->getStatuts();

            // Stats classiques
            $topEvents               = $this->eventModel->getTopEventsByParticipants(5);
            $revenueEvents           = $this->eventModel->getRevenueEvents();
            $specialtiesDistribution = $this->eventModel->getSpecialtyDistribution();

            // Export CSV
            if (isset($_GET['export']) && $_GET['export'] === 'csv' && !empty($_GET['event_id'])) {
                $exportStatut = $_GET['export_statut'] ?? '';
                $rows = $eventAvance->getParticipantsForExport((int)$_GET['event_id'], $exportStatut);
                header('Content-Type: text/csv; charset=utf-8');
                header('Content-Disposition: attachment; filename=participants_event_' . $_GET['event_id'] . '.csv');
                $out = fopen('php://output', 'w');
                fputcsv($out, ['Nom', 'Prénom', 'Email', 'Statut', 'Date inscription', 'Événement', 'Lieu']);
                foreach ($rows as $r) {
                    fputcsv($out, [$r['nom'], $r['prenom'], $r['email'], $r['statut'], $r['date_inscription'], $r['evenement_titre'], $r['lieu']]);
                }
                fclose($out);
                exit;
            }

            require __DIR__ . '/../views/backoffice/evenements/advanced.php';
        } catch (Exception $e) {
            error_log('Erreur EventController::advanced - ' . $e->getMessage());
            $this->setFlash('error', 'Erreur lors du chargement des statistiques avancées.');
            header('Location: index.php?page=evenements_admin');
            exit;
        }
    }

    // ─── Helpers prives ───────────────────────────────────────

    private function validateEvent(array $data): array {
        $errors = [];
        if (empty($data['titre']) || strlen($data['titre']) < 3)
            $errors[] = 'Le titre doit contenir au moins 3 caracteres.';
        if (empty($data['description']) || strlen($data['description']) < 10)
            $errors[] = 'La description doit contenir au moins 10 caracteres.';
        if (empty($data['date_debut']))
            $errors[] = 'La date de debut est obligatoire.';
        if (empty($data['date_fin']))
            $errors[] = 'La date de fin est obligatoire.';
        if (!empty($data['date_debut']) && !empty($data['date_fin'])) {
            $debut = DateTime::createFromFormat('Y-m-d', $data['date_debut']);
            $fin   = DateTime::createFromFormat('Y-m-d', $data['date_fin']);
            if ($debut && $fin && $debut > $fin)
                $errors[] = 'La date de fin doit etre apres la date de debut.';
        }
        if (($data['nombre_places_max'] ?? 0) < 0)
            $errors[] = 'Le nombre de places doit etre positif.';
        return $errors;
    }

    private function handleImageUpload(array $file): string|array {
        $maxSize      = 5 * 1024 * 1024;
        $allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];
        $uploadDir    = __DIR__ . '/../../public/uploads/events/';

        if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
        if ($file['size'] > $maxSize) return ['error' => 'Image trop grande (max 5MB).'];
        if (!in_array($file['type'], $allowedTypes)) return ['error' => 'Format non autorise.'];

        $ext      = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'][$file['type']];
        $filename = 'event_' . time() . '_' . uniqid() . '.' . $ext;

        if (!move_uploaded_file($file['tmp_name'], $uploadDir . $filename))
            return ['error' => 'Erreur lors du telechargement.'];

        return $filename;
    }

    private function canEditEvent(array $event): bool {
        return $_SESSION['user_role'] === 'admin'
            || (int)($event['createur_id'] ?? 0) === (int)$_SESSION['user_id'];
    }

    private function canDeleteEvent(array $event): bool {
        return $this->canEditEvent($event) && ($event['statut'] ?? '') !== 'publie';
    }

    private function generateCsrfToken(): string {
        if (empty($_SESSION['csrf_token']))
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
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
            $this->db->execute(
                "INSERT INTO logs (user_id, action, description, ip_address, created_at) VALUES (:u, :a, :d, :ip, NOW())",
                [':u' => $userId, ':a' => $action, ':d' => $description, ':ip' => $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0']
            );
        } catch (Exception $e) {
            error_log('Erreur logAction: ' . $e->getMessage());
        }
    }
}
?>