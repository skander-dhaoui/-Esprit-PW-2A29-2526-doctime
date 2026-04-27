<?php

require_once __DIR__ . '/../models/Article.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/AuthController.php';
class RendezVousController {

    private RendezVous $rdvModel;
    private Medecin $medecinModel;
    private Patient $patientModel;
    private AuthController $auth;
    private Database $db;

    public function __construct() {
        $this->rdvModel = new RendezVous();
        $this->medecinModel = new Medecin();
        $this->patientModel = new Patient();
        $this->auth = new AuthController();
        $this->db = Database::getInstance();
    }

    // ─────────────────────────────────────────
    //  Liste des rendez-vous (patient)
    // ─────────────────────────────────────────
    public function indexPatient(): void {
        $this->auth->requireRole('patient');

        try {
            $patientId = (int)$_SESSION['user_id'];
            $filter = $_GET['filter'] ?? 'all'; // all, upcoming, past, cancelled

            $rdvs = match ($filter) {
                'upcoming' => $this->rdvModel->getUpcomingByPatient($patientId),
                'past' => $this->rdvModel->getPastByPatient($patientId),
                'cancelled' => $this->rdvModel->getCancelledByPatient($patientId),
                default => $this->rdvModel->getAllByPatient($patientId),
            };

            $flash = $_SESSION['flash'] ?? null;
            unset($_SESSION['flash']);

            require_once __DIR__ . '/../views/frontoffice/rdv_list_patient.php';
        } catch (Exception $e) {
            error_log('Erreur RendezVousController::indexPatient - ' . $e->getMessage());
            $this->setFlash('error', 'Erreur lors du chargement des rendez-vous.');
            header('Location: /patient/dashboard');
            exit;
        }
    }

    // ─────────────────────────────────────────
    //  Liste des rendez-vous (médecin)
    // ─────────────────────────────────────────
    public function indexMedecin(): void {
        $this->auth->requireRole('medecin');

        try {
            $medecinId = (int)$_SESSION['user_id'];
            $filter = $_GET['filter'] ?? 'all'; // all, today, upcoming, past

            $rdvs = match ($filter) {
                'today' => $this->rdvModel->getTodayByMedecin($medecinId),
                'upcoming' => $this->rdvModel->getUpcomingByMedecin($medecinId),
                'past' => $this->rdvModel->getPastByMedecin($medecinId),
                default => $this->rdvModel->getAllByMedecin($medecinId),
            };

            $flash = $_SESSION['flash'] ?? null;
            unset($_SESSION['flash']);

            require_once __DIR__ . '/../views/backoffice/rdv_list_medecin.php';
        } catch (Exception $e) {
            error_log('Erreur RendezVousController::indexMedecin - ' . $e->getMessage());
            $this->setFlash('error', 'Erreur lors du chargement des rendez-vous.');
            header('Location: /medecin/dashboard');
            exit;
        }
    }

    // ─────────────────────────────────────────
    //  Liste des rendez-vous (admin)
    // ─────────────────────────────────────────
    public function indexAdmin(): void {
        $this->auth->requireRole('admin');

        try {
            $filter = $_GET['filter'] ?? 'all';
            $medecin = $_GET['medecin'] ?? null;
            $patient = $_GET['patient'] ?? null;

            $rdvs = $this->rdvModel->getAll($filter, $medecin, $patient);

            $medecins = $this->medecinModel->getAllWithUsers();
            $patients = $this->patientModel->getAll();
            $flash = $_SESSION['flash'] ?? null;
            unset($_SESSION['flash']);

            require_once __DIR__ . '/../views/backoffice/rdv_list_admin.php';
        } catch (Exception $e) {
            error_log('Erreur RendezVousController::indexAdmin - ' . $e->getMessage());
            $this->setFlash('error', 'Erreur lors du chargement des rendez-vous.');
            header('Location: /admin/dashboard');
            exit;
        }
    }

    // ─────────────────────────────────────────
    //  Créer un rendez-vous (patient)
    // ─────────────────────────────────────────
    public function createPatient(): void {
        $this->auth->requireRole('patient');

        try {
            $csrfToken = $this->generateCsrfToken();
            $medecinId = $_GET['medecin'] ?? null;
            $medecin = null;

            if ($medecinId) {
                $medecin = $this->medecinModel->findByUserId((int)$medecinId);
            }

            $medecins = $this->medecinModel->getAllWithUsers();
            $old = $_SESSION['old'] ?? null;
            $flash = $_SESSION['flash'] ?? null;
            unset($_SESSION['old'], $_SESSION['flash']);

            require_once __DIR__ . '/../views/frontoffice/rdv_create_patient.php';
        } catch (Exception $e) {
            error_log('Erreur RendezVousController::createPatient - ' . $e->getMessage());
            $this->setFlash('error', 'Erreur lors du chargement du formulaire.');
            header('Location: /patient/rdv');
            exit;
        }
    }

    // ─────────────────────────────────────────
    //  Enregistrer un rendez-vous (patient)
    // ─────────────────────────────────────────
    public function storePatient(): void {
        $this->auth->requireRole('patient');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /patient/rdv/create');
            exit;
        }

        if (!$this->verifyCsrfToken($_POST['csrf_token'] ?? '')) {
            $this->setFlash('error', 'Erreur de sécurité. Veuillez réessayer.');
            header('Location: /patient/rdv/create');
            exit;
        }

        try {
            $patientId = (int)$_SESSION['user_id'];

            $data = [
                'patient_id' => $patientId,
                'medecin_id' => (int)($_POST['medecin_id'] ?? 0),
                'date_rdv' => $_POST['date_rdv'] ?? '',
                'heure_rdv' => $_POST['heure_rdv'] ?? '',
                'motif' => htmlspecialchars(trim($_POST['motif'] ?? ''), ENT_QUOTES, 'UTF-8'),
                'type_consultation' => $_POST['type_consultation'] ?? 'consultation',
                'statut' => 'confirmé',
            ];

            $errors = $this->validateRendezVous($data);

            if (!empty($errors)) {
                $this->setFlash('error', implode('<br>', $errors));
                $_SESSION['old'] = $data;
                header('Location: /patient/rdv/create');
                exit;
            }

            // Vérifier que le médecin existe
            $medecin = $this->medecinModel->findByUserId($data['medecin_id']);
            if (!$medecin) {
                throw new Exception('Médecin introuvable.');
            }

            // Vérifier la disponibilité
            if (!$this->rdvModel->isAvailable($data['medecin_id'], $data['date_rdv'], $data['heure_rdv'])) {
                $this->setFlash('error', 'Ce créneau n\'est pas disponible.');
                $_SESSION['old'] = $data;
                header('Location: /patient/rdv/create');
                exit;
            }

            $rdvId = $this->rdvModel->create($data);

            if (!$rdvId) {
                throw new Exception('Erreur lors de la création du rendez-vous.');
            }

            $this->logAction($_SESSION['user_id'], 'Création RDV', "Rendez-vous #$rdvId créé avec le médecin #" . $data['medecin_id']);

            $this->setFlash('success', 'Rendez-vous créé avec succès.');
            header('Location: /patient/rdv');
            exit;
        } catch (Exception $e) {
            error_log('Erreur RendezVousController::storePatient - ' . $e->getMessage());
            $this->setFlash('error', 'Erreur lors de la création du rendez-vous.');
            $_SESSION['old'] = $data ?? [];
            header('Location: /patient/rdv/create');
            exit;
        }
    }

    // ─────────────────────────────────────────
    //  Afficher les détails d'un RDV (patient)
    // ─────────────────────────────────────────
    public function showPatient(int $id): void {
        $this->auth->requireRole('patient');

        try {
            $patientId = (int)$_SESSION['user_id'];
            $rdv = $this->rdvModel->getById($id);

            if (!$rdv || (int)$rdv['patient_id'] !== $patientId) {
                http_response_code(403);
                die('Accès refusé.');
            }

            $medecin = $this->medecinModel->findByUserId((int)$rdv['medecin_id']);
            $flash = $_SESSION['flash'] ?? null;
            unset($_SESSION['flash']);

            require_once __DIR__ . '/../views/frontoffice/rdv_show_patient.php';
        } catch (Exception $e) {
            error_log('Erreur RendezVousController::showPatient - ' . $e->getMessage());
            http_response_code(500);
            die('Erreur lors du chargement du rendez-vous.');
        }
    }

    // ─────────────────────────────────────────
    //  Afficher les détails d'un RDV (médecin)
    // ─────────────────────────────────────────
    public function showMedecin(int $id): void {
        $this->auth->requireRole('medecin');

        try {
            $medecinId = (int)$_SESSION['user_id'];
            $rdv = $this->rdvModel->getById($id);

            if (!$rdv || (int)$rdv['medecin_id'] !== $medecinId) {
                http_response_code(403);
                die('Accès refusé.');
            }

            $patient = $this->patientModel->findByUserId((int)$rdv['patient_id']);
            $flash = $_SESSION['flash'] ?? null;
            unset($_SESSION['flash']);

            require_once __DIR__ . '/../views/backoffice/rdv_show_medecin.php';
        } catch (Exception $e) {
            error_log('Erreur RendezVousController::showMedecin - ' . $e->getMessage());
            http_response_code(500);
            die('Erreur lors du chargement du rendez-vous.');
        }
    }

    // ─────────────────────────────────────────
    //  Éditer un rendez-vous (patient)
    // ─────────────────────────────────────────
    public function editPatient(int $id): void {
        $this->auth->requireRole('patient');

        try {
            $patientId = (int)$_SESSION['user_id'];
            $rdv = $this->rdvModel->getById($id);

            if (!$rdv || (int)$rdv['patient_id'] !== $patientId) {
                http_response_code(403);
                die('Accès refusé.');
            }

            // Vérifier que le RDV n'est pas dans les 48 heures
            $dateRdv = new DateTime($rdv['date_rdv'] . ' ' . $rdv['heure_rdv']);
            $now = new DateTime();
            if ($dateRdv->diff($now)->days < 2 && $dateRdv > $now) {
                $this->setFlash('error', 'Vous ne pouvez pas modifier un RDV à moins de 48h.');
                header("Location: /patient/rdv/$id");
                exit;
            }

            $csrfToken = $this->generateCsrfToken();
            $medecins = $this->medecinModel->getAllWithUsers();
            $old = $_SESSION['old'] ?? null;
            $flash = $_SESSION['flash'] ?? null;
            unset($_SESSION['old'], $_SESSION['flash']);

            require_once __DIR__ . '/../views/frontoffice/rdv_edit_patient.php';
        } catch (Exception $e) {
            error_log('Erreur RendezVousController::editPatient - ' . $e->getMessage());
            $this->setFlash('error', 'Erreur lors du chargement du formulaire.');
            header('Location: /patient/rdv');
            exit;
        }
    }

    // ─────────────────────────────────────────
    //  Mettre à jour un RDV (patient)
    // ─────────────────────────────────────────
    public function updatePatient(int $id): void {
        $this->auth->requireRole('patient');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: /patient/rdv/$id/edit");
            exit;
        }

        if (!$this->verifyCsrfToken($_POST['csrf_token'] ?? '')) {
            $this->setFlash('error', 'Erreur de sécurité.');
            header("Location: /patient/rdv/$id/edit");
            exit;
        }

        try {
            $patientId = (int)$_SESSION['user_id'];
            $rdv = $this->rdvModel->getById($id);

            if (!$rdv || (int)$rdv['patient_id'] !== $patientId) {
                http_response_code(403);
                die('Accès refusé.');
            }

            $data = [
                'date_rdv' => $_POST['date_rdv'] ?? '',
                'heure_rdv' => $_POST['heure_rdv'] ?? '',
                'motif' => htmlspecialchars(trim($_POST['motif'] ?? ''), ENT_QUOTES, 'UTF-8'),
                'type_consultation' => $_POST['type_consultation'] ?? 'consultation',
            ];

            $errors = $this->validateRendezVousUpdate($data);

            if (!empty($errors)) {
                $this->setFlash('error', implode('<br>', $errors));
                $_SESSION['old'] = $data;
                header("Location: /patient/rdv/$id/edit");
                exit;
            }

            // Vérifier la disponibilité (en excluant ce RDV)
            if (!$this->rdvModel->isAvailable((int)$rdv['medecin_id'], $data['date_rdv'], $data['heure_rdv'], $id)) {
                $this->setFlash('error', 'Ce créneau n\'est pas disponible.');
                $_SESSION['old'] = $data;
                header("Location: /patient/rdv/$id/edit");
                exit;
            }

            $this->rdvModel->update($id, $data);

            $this->logAction($_SESSION['user_id'], 'Modification RDV', "Rendez-vous #$id modifié");

            $this->setFlash('success', 'Rendez-vous mis à jour avec succès.');
            header('Location: /patient/rdv');
            exit;
        } catch (Exception $e) {
            error_log('Erreur RendezVousController::updatePatient - ' . $e->getMessage());
            $this->setFlash('error', 'Erreur lors de la mise à jour.');
            header("Location: /patient/rdv/$id/edit");
            exit;
        }
    }

    // ─────────────────────────────────────────
    //  Annuler un RDV (patient)
    // ─────────────────────────────────────────
    public function cancelPatient(int $id): void {
        $this->auth->requireRole('patient');

        try {
            $patientId = (int)$_SESSION['user_id'];
            $rdv = $this->rdvModel->getById($id);

            if (!$rdv || (int)$rdv['patient_id'] !== $patientId) {
                http_response_code(403);
                die('Accès refusé.');
            }

            // Vérifier que le RDV n'est pas dans les 24 heures
            $dateRdv = new DateTime($rdv['date_rdv'] . ' ' . $rdv['heure_rdv']);
            $now = new DateTime();
            if ($dateRdv->diff($now)->h <= 24 && $dateRdv > $now) {
                $this->setFlash('error', 'Vous ne pouvez pas annuler un RDV à moins de 24h.');
                header("Location: /patient/rdv/$id");
                exit;
            }

            $raison = htmlspecialchars(trim($_POST['raison'] ?? ''), ENT_QUOTES, 'UTF-8');
            $this->rdvModel->update($id, [
                'statut' => 'annulé',
                'raison_annulation' => $raison,
            ]);

            $this->logAction($_SESSION['user_id'], 'Annulation RDV', "Rendez-vous #$id annulé");

            $this->setFlash('success', 'Rendez-vous annulé.');
            header('Location: /patient/rdv');
            exit;
        } catch (Exception $e) {
            error_log('Erreur RendezVousController::cancelPatient - ' . $e->getMessage());
            $this->setFlash('error', 'Erreur lors de l\'annulation.');
            header("Location: /patient/rdv/$id");
            exit;
        }
    }

    // ─────────────────────────────────────────
    //  Confirmer présence (médecin)
    // ─────────────────────────────────────────
    public function confirmPresence(int $id): void {
        $this->auth->requireRole('medecin');

        try {
            $medecinId = (int)$_SESSION['user_id'];
            $rdv = $this->rdvModel->getById($id);

            if (!$rdv || (int)$rdv['medecin_id'] !== $medecinId) {
                http_response_code(403);
                die('Accès refusé.');
            }

            $this->rdvModel->update($id, [
                'statut' => 'effectué',
                'date_effet' => date('Y-m-d H:i:s'),
            ]);

            $this->logAction($_SESSION['user_id'], 'Confirmation présence RDV', "RDV #$id confirmé");

            $this->setFlash('success', 'Présence confirmée.');
            header("Location: /medecin/rdv/$id");
            exit;
        } catch (Exception $e) {
            error_log('Erreur RendezVousController::confirmPresence - ' . $e->getMessage());
            $this->setFlash('error', 'Erreur lors de la confirmation.');
            header("Location: /medecin/rdv/$id");
            exit;
        }
    }

    // ─────────────────────────────────────────
    //  Ajouter une note (médecin)
    // ─────────────────────────────────────────
    public function addNote(int $id): void {
        $this->auth->requireRole('medecin');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: /medecin/rdv/$id");
            exit;
        }

        try {
            $medecinId = (int)$_SESSION['user_id'];
            $rdv = $this->rdvModel->getById($id);

            if (!$rdv || (int)$rdv['medecin_id'] !== $medecinId) {
                http_response_code(403);
                die('Accès refusé.');
            }

            $note = htmlspecialchars(trim($_POST['note'] ?? ''), ENT_QUOTES, 'UTF-8');

            if (empty($note) || strlen($note) < 10) {
                $this->setFlash('error', 'La note doit contenir au moins 10 caractères.');
                header("Location: /medecin/rdv/$id");
                exit;
            }

            $this->rdvModel->addNote($id, $note);

            $this->logAction($_SESSION['user_id'], 'Ajout note RDV', "Note ajoutée au RDV #$id");

            $this->setFlash('success', 'Note ajoutée avec succès.');
            header("Location: /medecin/rdv/$id");
            exit;
        } catch (Exception $e) {
            error_log('Erreur RendezVousController::addNote - ' . $e->getMessage());
            $this->setFlash('error', 'Erreur lors de l\'ajout de la note.');
            header("Location: /medecin/rdv/$id");
            exit;
        }
    }

    // ─────────────────────────────────────────
    //  API - Disponibilités
    // ─────────────────────────────────────────
    public function getAvailabilities(): void {
        header('Content-Type: application/json');

        try {
            $medecinId = (int)($_GET['medecin_id'] ?? 0);
            $date = $_GET['date'] ?? '';

            if (!$medecinId || !$date) {
                echo json_encode(['error' => 'Paramètres invalides']);
                exit;
            }

            $availabilities = $this->rdvModel->getAvailabilitiesByMedecin($medecinId, $date);

            echo json_encode([
                'success' => true,
                'availabilities' => $availabilities,
            ]);
            exit;
        } catch (Exception $e) {
            error_log('Erreur getAvailabilities - ' . $e->getMessage());
            echo json_encode(['error' => 'Erreur serveur']);
            exit;
        }
    }

    // ─────────────────────────────────────────
    //  Helpers privés
    // ─────────────────────────────────────────
    private function validateRendezVous(array $data): array {
        $errors = [];

        // Validation date
        if (empty($data['date_rdv'])) {
            $errors[] = 'La date est obligatoire.';
        } else {
            $date = DateTime::createFromFormat('Y-m-d', $data['date_rdv']);
            if (!$date || $date->format('Y-m-d') !== $data['date_rdv']) {
                $errors[] = 'Format de date invalide.';
            } elseif ($date < new DateTime('today')) {
                $errors[] = 'La date doit être dans le futur.';
            }
        }

        // Validation heure
        if (empty($data['heure_rdv'])) {
            $errors[] = 'L\'heure est obligatoire.';
        } elseif (!preg_match('/^([0-1][0-9]|2[0-3]):[0-5][0-9]$/', $data['heure_rdv'])) {
            $errors[] = 'Format d\'heure invalide.';
        }

        // Validation motif
        if (empty($data['motif']) || strlen($data['motif']) < 5) {
            $errors[] = 'Le motif doit contenir au moins 5 caractères.';
        }

        // Validation médecin
        if (empty($data['medecin_id']) || $data['medecin_id'] <= 0) {
            $errors[] = 'Médecin invalide.';
        }

        return $errors;
    }

    private function validateRendezVousUpdate(array $data): array {
        return $this->validateRendezVous([
            'date_rdv' => $data['date_rdv'] ?? '',
            'heure_rdv' => $data['heure_rdv'] ?? '',
            'motif' => $data['motif'] ?? '',
            'medecin_id' => 1, // Dummy pour la validation
        ]);
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