<?php

require_once __DIR__ . '/../models/Patient.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/Medecin.php';
require_once __DIR__ . '/AuthController.php';

class PatientController {

    private Patient        $patientModel;
    private User           $userModel;
    private Medecin        $medecinModel;
    private AuthController $auth;

    public function __construct() {
        $this->patientModel = new Patient();
        $this->userModel    = new User();
        $this->medecinModel = new Medecin();
        $this->auth         = new AuthController();
    }

    // ─────────────────────────────────────────
    //  Dashboard patient
    // ─────────────────────────────────────────
    public function dashboard(): void {
        $this->auth->requireRole('patient');

        $userId          = (int)$_SESSION['user_id'];
        $patient         = $this->patientModel->findByUserId($userId);
        $appointments    = $this->patientModel->getAppointments($userId);
        $nextAppointment = $this->patientModel->getNextAppointment($userId);
        $claims          = $this->patientModel->getClaims($userId);
        $stats           = $this->patientModel->getStats($userId);

        $viewPath = __DIR__ . '/../views/frontoffice/dashboard.html';
        file_exists($viewPath) ? require_once $viewPath : http_response_code(200);
    }

    // ─────────────────────────────────────────
    //  Rendez-vous
    // ─────────────────────────────────────────
    public function appointments(): void {
        $this->auth->requireRole('patient');

        $userId       = (int)$_SESSION['user_id'];
        $appointments = $this->patientModel->getAppointments($userId);

        $viewPath = __DIR__ . '/../views/frontoffice/patient_appointments.html';
        file_exists($viewPath) ? require_once $viewPath : http_response_code(200);
    }

    public function createAppointment(): void {
        $this->auth->requireRole('patient');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: index.php?page=prendre_rendez_vous');
            exit;
        }

        $userId = (int)$_SESSION['user_id'];
        $data   = [
            'patient_id' => $userId,
            'medecin_id' => (int)($_POST['medecin_id'] ?? 0),
            'date'       => $_POST['date']  ?? '',
            'heure'      => $_POST['heure'] ?? '',
            'motif'      => trim($_POST['motif'] ?? 'Consultation'),
            'statut'     => 'en_attente',
        ];

        if (!$data['medecin_id'] || !$data['date'] || !$data['heure']) {
            $_SESSION['error'] = 'Veuillez remplir tous les champs obligatoires.';
            header('Location: index.php?page=prendre_rendez_vous');
            exit;
        }

        $this->patientModel->createAppointment($data);
        $_SESSION['success'] = 'Rendez-vous demandé avec succès. En attente de confirmation.';
        header('Location: index.php?page=mes_rendez_vous');
        exit;
    }

    public function cancelAppointment(int $id): void {
        $this->auth->requireRole('patient');

        $userId = (int)$_SESSION['user_id'];
        $appt   = $this->patientModel->getAppointmentById($id);

        if (!$appt || (int)$appt['patient_id'] !== $userId) {
            $_SESSION['error'] = 'Rendez-vous introuvable ou accès refusé.';
            header('Location: index.php?page=mes_rendez_vous');
            exit;
        }

        $this->patientModel->updateAppointmentStatus($id, 'annulé');
        $_SESSION['success'] = 'Rendez-vous annulé.';
        header('Location: index.php?page=mes_rendez_vous');
        exit;
    }

    // ─────────────────────────────────────────
    //  Réclamations
    // ─────────────────────────────────────────
    public function claims(): void {
        $this->auth->requireRole('patient');

        $userId = (int)$_SESSION['user_id'];
        $claims = $this->patientModel->getClaims($userId);

        $viewPath = __DIR__ . '/../views/frontoffice/patient_claims.html';
        file_exists($viewPath) ? require_once $viewPath : http_response_code(200);
    }

    public function createClaim(): void {
        $this->auth->requireRole('patient');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: index.php?page=mes_rendez_vous');
            exit;
        }

        $userId = (int)$_SESSION['user_id'];
        $data   = [
            'patient_id'  => $userId,
            'sujet'       => trim($_POST['sujet']       ?? ''),
            'description' => trim($_POST['description'] ?? ''),
            'priorite'    => $_POST['priorite']         ?? 'moyenne',
            'statut'      => 'en_cours',
        ];

        if (empty($data['sujet']) || empty($data['description'])) {
            $_SESSION['error'] = 'Veuillez remplir tous les champs.';
            header('Location: index.php?page=mes_rendez_vous');
            exit;
        }

        $this->patientModel->createClaim($data);
        $_SESSION['success'] = 'Réclamation envoyée avec succès.';
        header('Location: index.php?page=mes_rendez_vous');
        exit;
    }

    // ─────────────────────────────────────────
    //  Profil patient
    // ─────────────────────────────────────────
    public function profile(): void {
        $this->auth->requireRole('patient');

        $userId  = (int)$_SESSION['user_id'];
        $user    = $this->userModel->findById($userId);
        $patient = $this->patientModel->findByUserId($userId);
        $stats   = $this->patientModel->getStats($userId);

        $viewPath = __DIR__ . '/../views/frontoffice/patient_profil.html';
        file_exists($viewPath) ? require_once $viewPath : http_response_code(200);
    }

    // ─────────────────────────────────────────
    //  Helper JSON
    // ─────────────────────────────────────────
    private function jsonResponse(bool $success, string $message, array $data = []): void {
        header('Content-Type: application/json');
        echo json_encode(array_merge(['success' => $success, 'message' => $message], $data));
        exit;
    }
}