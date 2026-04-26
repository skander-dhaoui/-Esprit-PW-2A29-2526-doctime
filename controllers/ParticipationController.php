<?php

require_once __DIR__ . '/../models/Participation.php';
require_once __DIR__ . '/../models/Event.php';
require_once __DIR__ . '/../models/Patient.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/AuthController.php';

class ParticipationController {

    private Participation $participationModel;
    private Event $eventModel;
    private Patient $patientModel;
    private AuthController $auth;
    private Database $db;

    public function __construct() {
        $this->participationModel = new Participation();
        $this->eventModel = new Event();
        $this->patientModel = new Patient();
        $this->auth = new AuthController();
        $this->db = Database::getInstance();
    }

    // ─────────────────────────────────────────
    //  Liste des participations (admin)
    // ─────────────────────────────────────────
    public function indexAdmin(): void {
        $this->auth->requireRole('admin');

        try {
            $filter = $_GET['filter'] ?? 'all'; // all, confirmé, en attente, annulé, présent, absent
            $eventId = $_GET['event'] ?? null;
            $search = $_GET['search'] ?? '';

            $participations = $this->participationModel->getAll($filter, $eventId, $search);

            $events = $this->eventModel->getAll();
            $flash = $_SESSION['flash'] ?? null;
            unset($_SESSION['flash']);

            require_once __DIR__ . '/../views/backoffice/participation_list_admin.php';
        } catch (Exception $e) {
            error_log('Erreur ParticipationController::indexAdmin - ' . $e->getMessage());
            $this->setFlash('error', 'Erreur lors du chargement des participations.');
            header('Location: /admin/dashboard');
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
            $participation = $this->participationModel->getById($id);

            if (!$participation) {
                http_response_code(404);
                die('Participation introuvable.');
            }

            $csrfToken = $this->generateCsrfToken();
            $event = $this->eventModel->getById($participation['event_id']);
            $patient = $this->patientModel->findByUserId($participation['patient_id']);
            $old = $_SESSION['old'] ?? null;
            $flash = $_SESSION['flash'] ?? null;
            unset($_SESSION['old'], $_SESSION['flash']);

            require_once __DIR__ . '/../views/backoffice/participation_form_edit.php';
        } catch (Exception $e) {
            error_log('Erreur ParticipationController::edit - ' . $e->getMessage());
            $this->setFlash('error', 'Erreur lors du chargement.');
            header('Location: /events');
            exit;
        }
    }

    // ─────────────────────────────────────────
    //  Mettre à jour une participation
    // ─────────────────────────────────────────
    public function update(int $id): void {
        $this->auth->requireRole(['admin', 'medecin']);

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: /participations/$id/edit");
            exit;
        }

        if (!$this->verifyCsrfToken($_POST['csrf_token'] ?? '')) {
            $this->setFlash('error', 'Erreur de sécurité.');
            header("Location: /participations/$id/edit");
            exit;
        }

        try {
            $participation = $this->participationModel->getById($id);

            if (!$participation) {
                http_response_code(404);
                die('Participation introuvable.');
            }

            $data = [
                'statut' => $_POST['statut'] ?? 'confirmé',
                'notes' => htmlspecialchars(trim($_POST['notes'] ?? ''), ENT_QUOTES, 'UTF-8'),
                'presence' => $_POST['presence'] ?? null,
            ];

            $errors = $this->validateParticipation($data);

            if (!empty($errors)) {
                $this->setFlash('error', implode('<br>', $errors));
                $_SESSION['old'] = $data;
                header("Location: /participations/$id/edit");
                exit;
            }

            $this->participationModel->update($id, $data);

            $this->logAction($_SESSION['user_id'], 'Modification participation', "Participation #$id modifiée");

            $this->setFlash('success', 'Participation mise à jour.');
            header("Location: /participations/$id");
            exit;
        } catch (Exception $e) {
            error_log('Erreur ParticipationController::update - ' . $e->getMessage());
            $this->setFlash('error', 'Erreur lors de la mise à jour.');
            header("Location: /participations/$id/edit");
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
            header('Location: /admin/participations');
            exit;
        } catch (Exception $e) {
            error_log('Erreur ParticipationController::delete - ' . $e->getMessage());
            $this->setFlash('error', 'Erreur lors de la suppression.');
            header('Location: /admin/participations');
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



// update
