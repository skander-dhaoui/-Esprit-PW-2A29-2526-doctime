<?php

require_once __DIR__ . '/../models/Sponsor.php';
require_once __DIR__ . '/../models/Event.php';
require_once __DIR__ . '/../config/database.php'';
require_once __DIR__ . '/AuthController.php';

class SponsorController {

    private Sponsor $sponsorModel;
    private Event $eventModel;
    private AuthController $auth;
    private Database $db;

    public function __construct() {
        $this->sponsorModel = new Sponsor();
        $this->eventModel = new Event();
        $this->auth = new AuthController();
        $this->db = Database::getInstance();
    }

    // ─────────────────────────────────────────
    //  Liste des sponsors (admin)
    // ─────────────────────────────────────────
    public function index(): void {
        $this->auth->requireRole('admin');

        try {
            $filter = $_GET['filter'] ?? 'all'; // all, actif, inactif, archive
            $search = $_GET['search'] ?? '';

            $sponsors = $this->sponsorModel->getAll($filter, $search);

            $flash = $_SESSION['flash'] ?? null;
            unset($_SESSION['flash']);

            require_once __DIR__ . '/../views/backoffice/sponsor_list.php';
        } catch (Exception $e) {
            error_log('Erreur SponsorController::index - ' . $e->getMessage());
            $this->setFlash('error', 'Erreur lors du chargement des sponsors.');
            header('Location: /admin/dashboard');
            exit;
        }
    }

    // ─────────────────────────────────────────
    //  Afficher un sponsor
    // ─────────────────────────────────────────
    public function show(int $id): void {
        $this->auth->requireRole('admin');

        try {
            $sponsor = $this->sponsorModel->getById($id);

            if (!$sponsor) {
                http_response_code(404);
                die('Sponsor introuvable.');
            }

            $events = $this->sponsorModel->getEventsBySponsor($id);
            $totalInvested = $this->sponsorModel->calculateTotalInvested($id);
            $flash = $_SESSION['flash'] ?? null;
            unset($_SESSION['flash']);

            require_once __DIR__ . '/../views/backoffice/sponsor_show.php';
        } catch (Exception $e) {
            error_log('Erreur SponsorController::show - ' . $e->getMessage());
            http_response_code(500);
            die('Erreur lors du chargement.');
        }
    }

    // ─────────────────────────────────────────
    //  Créer un sponsor (admin)
    // ─────────────────────────────────────────
    public function create(): void {
        $this->auth->requireRole('admin');

        try {
            $csrfToken = $this->generateCsrfToken();
            $categorieSponsor = $this->sponsorModel->getCategories();
            $old = $_SESSION['old'] ?? null;
            $flash = $_SESSION['flash'] ?? null;
            unset($_SESSION['old'], $_SESSION['flash']);

            require_once __DIR__ . '/../views/backoffice/sponsor_form.php';
        } catch (Exception $e) {
            error_log('Erreur SponsorController::create - ' . $e->getMessage());
            $this->setFlash('error', 'Erreur lors du chargement du formulaire.');
            header('Location: /admin/sponsors');
            exit;
        }
    }

    // ─────────────────────────────────────────
    //  Enregistrer un sponsor (admin)
    // ─────────────────────────────────────────
    public function store(): void {
        $this->auth->requireRole('admin');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /admin/sponsors/create');
            exit;
        }

        if (!$this->verifyCsrfToken($_POST['csrf_token'] ?? '')) {
            $this->setFlash('error', 'Erreur de sécurité. Veuillez réessayer.');
            header('Location: /admin/sponsors/create');
            exit;
        }

        try {
            $data = [
                'nom' => htmlspecialchars(trim($_POST['nom'] ?? ''), ENT_QUOTES, 'UTF-8'),
                'description' => htmlspecialchars(trim($_POST['description'] ?? ''), ENT_QUOTES, 'UTF-8'),
                'categorie' => $_POST['categorie'] ?? 'autre',
                'email' => strtolower(trim($_POST['email'] ?? '')),
                'telephone' => trim($_POST['telephone'] ?? ''),
                'adresse' => htmlspecialchars(trim($_POST['adresse'] ?? ''), ENT_QUOTES, 'UTF-8'),
                'site_web' => filter_var($_POST['site_web'] ?? '', FILTER_VALIDATE_URL) ?: null,
                'budget_annuel' => (float)($_POST['budget_annuel'] ?? 0),
                'statut' => $_POST['statut'] ?? 'actif',
                'logo' => null,
            ];

            $errors = $this->validateSponsor($data);

            if (!empty($errors)) {
                $this->setFlash('error', implode('<br>', $errors));
                $_SESSION['old'] = $data;
                header('Location: /admin/sponsors/create');
                exit;
            }

            // Vérifier que l'email n'existe pas
            if ($this->sponsorModel->findByEmail($data['email'])) {
                $this->setFlash('error', 'Cet email est déjà utilisé.');
                $_SESSION['old'] = $data;
                header('Location: /admin/sponsors/create');
                exit;
            }

            // Traiter l'upload du logo
            if (!empty($_FILES['logo']['name'])) {
                $logoData = $this->handleLogoUpload($_FILES['logo']);
                if (is_array($logoData) && isset($logoData['error'])) {
                    $this->setFlash('error', $logoData['error']);
                    $_SESSION['old'] = $data;
                    header('Location: /admin/sponsors/create');
                    exit;
                }
                $data['logo'] = $logoData;
            }

            $sponsorId = $this->sponsorModel->create($data);

            if (!$sponsorId) {
                throw new Exception('Erreur lors de la création.');
            }

            $this->logAction($_SESSION['user_id'], 'Création sponsor', "Sponsor #$sponsorId créé - {$data['nom']}");

            $this->setFlash('success', 'Sponsor créé avec succès.');
            header('Location: /admin/sponsors/' . $sponsorId);
            exit;
        } catch (Exception $e) {
            error_log('Erreur SponsorController::store - ' . $e->getMessage());
            $this->setFlash('error', 'Erreur lors de la création.');
            $_SESSION['old'] = $data ?? [];
            header('Location: /admin/sponsors/create');
            exit;
        }
    }

    // ─────────────────────────────────────────
    //  Éditer un sponsor (admin)
    // ─────────────────────────────────────────
    public function edit(int $id): void {
        $this->auth->requireRole('admin');

        try {
            $sponsor = $this->sponsorModel->getById($id);

            if (!$sponsor) {
                http_response_code(404);
                die('Sponsor introuvable.');
            }

            $csrfToken = $this->generateCsrfToken();
            $categorieSponsor = $this->sponsorModel->getCategories();
            $old = $_SESSION['old'] ?? null;
            $flash = $_SESSION['flash'] ?? null;
            unset($_SESSION['old'], $_SESSION['flash']);

            require_once __DIR__ . '/../views/backoffice/sponsor_form_edit.php';
        } catch (Exception $e) {
            error_log('Erreur SponsorController::edit - ' . $e->getMessage());
            $this->setFlash('error', 'Erreur lors du chargement.');
            header('Location: /admin/sponsors');
            exit;
        }
    }

    // ─────────────────────────────────────────
    //  Mettre à jour un sponsor (admin)
    // ─────────────────────────────────────────
    public function update(int $id): void {
        $this->auth->requireRole('admin');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: /admin/sponsors/$id/edit");
            exit;
        }

        if (!$this->verifyCsrfToken($_POST['csrf_token'] ?? '')) {
            $this->setFlash('error', 'Erreur de sécurité.');
            header("Location: /admin/sponsors/$id/edit");
            exit;
        }

        try {
            $sponsor = $this->sponsorModel->getById($id);

            if (!$sponsor) {
                http_response_code(404);
                die('Sponsor introuvable.');
            }

            $data = [
                'nom' => htmlspecialchars(trim($_POST['nom'] ?? ''), ENT_QUOTES, 'UTF-8'),
                'description' => htmlspecialchars(trim($_POST['description'] ?? ''), ENT_QUOTES, 'UTF-8'),
                'categorie' => $_POST['categorie'] ?? 'autre',
                'email' => strtolower(trim($_POST['email'] ?? '')),
                'telephone' => trim($_POST['telephone'] ?? ''),
                'adresse' => htmlspecialchars(trim($_POST['adresse'] ?? ''), ENT_QUOTES, 'UTF-8'),
                'site_web' => filter_var($_POST['site_web'] ?? '', FILTER_VALIDATE_URL) ?: null,
                'budget_annuel' => (float)($_POST['budget_annuel'] ?? 0),
                'statut' => $_POST['statut'] ?? 'actif',
            ];

            $errors = $this->validateSponsorUpdate($data);

            if (!empty($errors)) {
                $this->setFlash('error', implode('<br>', $errors));
                $_SESSION['old'] = $data;
                header("Location: /admin/sponsors/$id/edit");
                exit;
            }

            // Vérifier que l'email n'existe pas ailleurs
            $existing = $this->sponsorModel->findByEmail($data['email']);
            if ($existing && (int)$existing['id'] !== $id) {
                $this->setFlash('error', 'Cet email est déjà utilisé.');
                $_SESSION['old'] = $data;
                header("Location: /admin/sponsors/$id/edit");
                exit;
            }

            // Traiter l'upload du logo
            if (!empty($_FILES['logo']['name'])) {
                $logoData = $this->handleLogoUpload($_FILES['logo']);
                if (is_array($logoData) && isset($logoData['error'])) {
                    $this->setFlash('error', $logoData['error']);
                    $_SESSION['old'] = $data;
                    header("Location: /admin/sponsors/$id/edit");
                    exit;
                }
                $data['logo'] = $logoData;

                // Supprimer l'ancien logo
                if (!empty($sponsor['logo'])) {
                    @unlink(__DIR__ . '/../../public/uploads/sponsors/' . $sponsor['logo']);
                }
            }

            $this->sponsorModel->update($id, $data);

            $this->logAction($_SESSION['user_id'], 'Modification sponsor', "Sponsor #$id modifié");

            $this->setFlash('success', 'Sponsor mis à jour.');
            header('Location: /admin/sponsors/' . $id);
            exit;
        } catch (Exception $e) {
            error_log('Erreur SponsorController::update - ' . $e->getMessage());
            $this->setFlash('error', 'Erreur lors de la mise à jour.');
            header("Location: /admin/sponsors/$id/edit");
            exit;
        }
    }

    // ─────────────────────────────────────────
    //  Supprimer un sponsor (admin)
    // ─────────────────────────────────────────
    public function delete(int $id): void {
        $this->auth->requireRole('admin');

        try {
            $sponsor = $this->sponsorModel->getById($id);

            if (!$sponsor) {
                http_response_code(404);
                die('Sponsor introuvable.');
            }

            // Vérifier s'il y a des événements associés
            $eventCount = $this->sponsorModel->countEventsAssociated($id);
            if ($eventCount > 0) {
                $this->setFlash('error', "Impossible de supprimer : $eventCount événement(s) associé(s).");
                header('Location: /admin/sponsors');
                exit;
            }

            // Supprimer le logo
            if (!empty($sponsor['logo'])) {
                @unlink(__DIR__ . '/../../public/uploads/sponsors/' . $sponsor['logo']);
            }

            $this->sponsorModel->delete($id);

            $this->logAction($_SESSION['user_id'], 'Suppression sponsor', "Sponsor #$id supprimé");

            $this->setFlash('success', 'Sponsor supprimé.');
            header('Location: /admin/sponsors');
            exit;
        } catch (Exception $e) {
            error_log('Erreur SponsorController::delete - ' . $e->getMessage());
            $this->setFlash('error', 'Erreur lors de la suppression.');
            header('Location: /admin/sponsors');
            exit;
        }
    }

    // ─────────────────────────────────────────
    //  Activer/Désactiver un sponsor (admin)
    // ─────────────────────────────────────────
    public function toggleStatus(int $id): void {
        $this->auth->requireRole('admin');

        try {
            $sponsor = $this->sponsorModel->getById($id);

            if (!$sponsor) {
                http_response_code(404);
                die('Sponsor introuvable.');
            }

            $newStatus = ($sponsor['statut'] === 'actif') ? 'inactif' : 'actif';
            $this->sponsorModel->update($id, ['statut' => $newStatus]);

            $this->logAction($_SESSION['user_id'], 'Changement statut sponsor', "Sponsor #$id - Statut: $newStatus");

            $this->setFlash('success', "Sponsor $newStatus.");
            header('Location: /admin/sponsors');
            exit;
        } catch (Exception $e) {
            error_log('Erreur toggleStatus - ' . $e->getMessage());
            $this->setFlash('error', 'Erreur lors du changement de statut.');
            header('Location: /admin/sponsors');
            exit;
        }
    }

    // ─────────────────────────────────────────
    //  Ajouter un sponsor à un événement
    // ─────────────────────────────────────────
    public function addToEvent(int $eventId): void {
        $this->auth->requireRole(['admin', 'medecin']);

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            die('Méthode non autorisée.');
        }

        if (!$this->verifyCsrfToken($_POST['csrf_token'] ?? '')) {
            echo json_encode(['error' => 'Erreur de sécurité']);
            exit;
        }

        try {
            $event = $this->eventModel->getById($eventId);

            if (!$event) {
                echo json_encode(['error' => 'Événement introuvable']);
                exit;
            }

            $sponsorId = (int)($_POST['sponsor_id'] ?? 0);
            $montant = (float)($_POST['montant'] ?? 0);

            if (!$sponsorId || $montant <= 0) {
                echo json_encode(['error' => 'Données invalides']);
                exit;
            }

            $sponsor = $this->sponsorModel->getById($sponsorId);

            if (!$sponsor) {
                echo json_encode(['error' => 'Sponsor introuvable']);
                exit;
            }

            // Vérifier que le sponsor n'est pas déjà associé
            if ($this->sponsorModel->isEventSponsored($eventId, $sponsorId)) {
                echo json_encode(['error' => 'Ce sponsor est déjà associé à cet événement']);
                exit;
            }

            // Vérifier le budget
            $totalEvents = $this->sponsorModel->calculateTotalInvested($sponsorId);
            if ($totalEvents + $montant > $sponsor['budget_annuel']) {
                echo json_encode(['error' => 'Budget insuffisant pour ce parrainage']);
                exit;
            }

            $eventSponsorId = $this->sponsorModel->addToEvent($eventId, $sponsorId, $montant);

            if (!$eventSponsorId) {
                throw new Exception('Erreur lors de l\'ajout.');
            }

            $this->logAction($_SESSION['user_id'], 'Ajout sponsor à événement', "Sponsor #$sponsorId ajouté à événement #$eventId pour {$montant}€");

            echo json_encode([
                'success' => true,
                'message' => 'Sponsor ajouté avec succès',
                'event_sponsor_id' => $eventSponsorId,
            ]);
            exit;
        } catch (Exception $e) {
            error_log('Erreur addToEvent - ' . $e->getMessage());
            echo json_encode(['error' => 'Erreur serveur']);
            exit;
        }
    }

    // ─────────────────────────────────────────
    //  Retirer un sponsor d'un événement
    // ─────────────────────────────────────────
    public function removeFromEvent(int $eventSponsorId): void {
        $this->auth->requireRole(['admin', 'medecin']);

        try {
            $eventSponsor = $this->sponsorModel->getEventSponsor($eventSponsorId);

            if (!$eventSponsor) {
                http_response_code(404);
                die('Association introuvable.');
            }

            $this->sponsorModel->removeFromEvent($eventSponsorId);

            $this->logAction($_SESSION['user_id'], 'Retrait sponsor d\'événement', "Association #$eventSponsorId supprimée");

            $this->setFlash('success', 'Sponsor retiré de l\'événement.');
            header('Location: /events/' . $eventSponsor['event_id']);
            exit;
        } catch (Exception $e) {
            error_log('Erreur removeFromEvent - ' . $e->getMessage());
            $this->setFlash('error', 'Erreur lors du retrait.');
            header('Location: /events');
            exit;
        }
    }

    // ─────────────────────────────────────────
    //  Mettre à jour le montant d'un parrainage
    // ─────────────────────────────────────────
    public function updateAmount(int $eventSponsorId): void {
        $this->auth->requireRole('admin');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            die('Méthode non autorisée.');
        }

        try {
            $eventSponsor = $this->sponsorModel->getEventSponsor($eventSponsorId);

            if (!$eventSponsor) {
                echo json_encode(['error' => 'Association introuvable']);
                exit;
            }

            $montant = (float)($_POST['montant'] ?? 0);

            if ($montant <= 0) {
                echo json_encode(['error' => 'Montant invalide']);
                exit;
            }

            $sponsor = $this->sponsorModel->getById($eventSponsor['sponsor_id']);

            // Calculer le total sans l'ancienne association
            $total = $this->sponsorModel->calculateTotalInvested($sponsor['id'], $eventSponsorId);

            if ($total + $montant > $sponsor['budget_annuel']) {
                echo json_encode(['error' => 'Budget insuffisant']);
                exit;
            }

            $this->sponsorModel->updateEventAmount($eventSponsorId, $montant);

            $this->logAction($_SESSION['user_id'], 'Modification montant parrainage', "Association #$eventSponsorId - Nouveau montant: {$montant}€");

            echo json_encode([
                'success' => true,
                'message' => 'Montant mis à jour',
                'montant' => $montant,
            ]);
            exit;
        } catch (Exception $e) {
            error_log('Erreur updateAmount - ' . $e->getMessage());
            echo json_encode(['error' => 'Erreur serveur']);
            exit;
        }
    }

    // ─────────────────────────────────────────
    //  API - Liste sponsors pour événements
    // ─────────────────────────────────────────
    public function apiList(): void {
        header('Content-Type: application/json');
        $this->auth->requireRole(['admin', 'medecin']);

        try {
            $statut = $_GET['statut'] ?? 'actif';
            $eventId = (int)($_GET['event_id'] ?? 0);

            $sponsors = $this->sponsorModel->getByStatus($statut);

            // Filtrer les sponsors déjà associés
            if ($eventId) {
                $sponsors = array_filter($sponsors, function($s) use ($eventId) {
                    return !$this->sponsorModel->isEventSponsored($eventId, $s['id']);
                });
            }

            echo json_encode([
                'success' => true,
                'sponsors' => array_values($sponsors),
            ]);
            exit;
        } catch (Exception $e) {
            error_log('Erreur apiList - ' . $e->getMessage());
            echo json_encode(['error' => 'Erreur serveur']);
            exit;
        }
    }

    // ─────────────────────────────────────────
    //  API - Sponsors d'un événement
    // ─────────────────────────────────────────
    public function apiEventSponsors(int $eventId): void {
        header('Content-Type: application/json');
        $this->auth->requireAuth();

        try {
            $sponsors = $this->sponsorModel->getEventSponsors($eventId);
            $total = $this->sponsorModel->getTotalEventSponsorship($eventId);

            echo json_encode([
                'success' => true,
                'sponsors' => $sponsors,
                'total' => $total,
                'count' => count($sponsors),
            ]);
            exit;
        } catch (Exception $e) {
            error_log('Erreur apiEventSponsors - ' . $e->getMessage());
            echo json_encode(['error' => 'Erreur serveur']);
            exit;
        }
    }

    // ─────────────────────────────────────────
    //  Rapport de parrainage (admin)
    // ─────────────────────────────────────────
    public function report(): void {
        $this->auth->requireRole('admin');

        try {
            $annee = (int)($_GET['annee'] ?? date('Y'));
            $stats = $this->sponsorModel->getAnnualStats($annee);
            $sponsors = $this->sponsorModel->getAll();

            $flash = $_SESSION['flash'] ?? null;
            unset($_SESSION['flash']);

            require_once __DIR__ . '/../views/backoffice/sponsor_report.php';
        } catch (Exception $e) {
            error_log('Erreur SponsorController::report - ' . $e->getMessage());
            $this->setFlash('error', 'Erreur lors du chargement.');
            header('Location: /admin/sponsors');
            exit;
        }
    }

    // ─────────────────────────────────────────
    //  Helpers privés
    // ─────────────────────────────────────────
    private function validateSponsor(array $data): array {
        $errors = [];

        if (empty($data['nom']) || strlen($data['nom']) < 2) {
            $errors[] = 'Le nom doit contenir au moins 2 caractères.';
        }

        if (empty($data['description']) || strlen($data['description']) < 10) {
            $errors[] = 'La description doit contenir au moins 10 caractères.';
        }

        if (empty($data['email']) || !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Email invalide.';
        }

        if (empty($data['telephone']) || strlen($data['telephone']) < 10) {
            $errors[] = 'Numéro de téléphone invalide.';
        }

        if ($data['budget_annuel'] <= 0) {
            $errors[] = 'Le budget annuel doit être positif.';
        }

        if (!in_array($data['categorie'], ['technologie', 'santé', 'education', 'finance', 'autre'])) {
            $errors[] = 'Catégorie invalide.';
        }

        return $errors;
    }

    private function validateSponsorUpdate(array $data): array {
        return $this->validateSponsor($data);
    }

    private function handleLogoUpload($file): ?string {
        $maxSize = 2 * 1024 * 1024; // 2MB
        $allowedTypes = ['image/jpeg', 'image/png', 'image/svg+xml', 'image/webp'];
        $uploadDir = __DIR__ . '/../../public/uploads/sponsors/';

        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        if ($file['size'] > $maxSize) {
            return ['error' => 'Logo trop grand (max 2MB).'];
        }

        if (!in_array($file['type'], $allowedTypes)) {
            return ['error' => 'Format de logo non autorisé.'];
        }

        $ext = match ($file['type']) {
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/svg+xml' => 'svg',
            'image/webp' => 'webp',
        };

        $filename = 'sponsor_' . time() . '_' . uniqid() . '.' . $ext;
        $filepath = $uploadDir . $filename;

        if (!move_uploaded_file($file['tmp_name'], $filepath)) {
            return ['error' => 'Erreur lors du téléchargement.'];
        }

        return $filename;
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



