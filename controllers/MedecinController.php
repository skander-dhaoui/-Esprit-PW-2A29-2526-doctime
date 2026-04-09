<?php

require_once __DIR__ . '/../models/Medecin.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/AuthController.php';

class MedecinController {

    private Medecin     $medecinModel;
    private User        $userModel;
    private AuthController $auth;

    public function __construct() {
        $this->medecinModel = new Medecin();
        $this->userModel    = new User();
        $this->auth         = new AuthController();
    }

    // ─────────────────────────────────────────
    //  Dashboard médecin
    // ─────────────────────────────────────────
    public function dashboard(): void {
        $this->auth->requireRole('medecin');

        $medecinId    = $_SESSION['user_id'];
        $appointments = $this->medecinModel->getTodayAppointments($medecinId);
        $upcoming     = $this->medecinModel->getUpcomingAppointments($medecinId);
        $stats        = $this->medecinModel->getStats($medecinId);

        require_once __DIR__ . '/../views/frontoffice/medecin_dashboard.html';
    }

    // ─────────────────────────────────────────
    //  Liste rendez-vous du médecin
    // ─────────────────────────────────────────
    public function appointments(): void {
        $this->auth->requireRole('medecin');

        $medecinId    = $_SESSION['user_id'];
        $appointments = $this->medecinModel->getAllAppointments($medecinId);

        require_once __DIR__ . '/../views/frontoffice/medecin_appointments.html';
    }

    // ─────────────────────────────────────────
    //  Confirmer un rendez-vous
    // ─────────────────────────────────────────
    public function confirmAppointment(int $id): void {
        $this->auth->requireRole('medecin');

        $appt = $this->medecinModel->getAppointmentById($id);

        if (!$appt || (int)$appt['medecin_id'] !== (int)$_SESSION['user_id']) {
            $this->jsonResponse(false, 'Rendez-vous introuvable ou accès refusé.');
            return;
        }

        $this->medecinModel->updateAppointmentStatus($id, 'confirmé');
        $this->jsonResponse(true, 'Rendez-vous confirmé.');
    }

    // ─────────────────────────────────────────
    //  Refuser un rendez-vous
    // ─────────────────────────────────────────
    public function refuseAppointment(int $id): void {
        $this->auth->requireRole('medecin');

        $appt = $this->medecinModel->getAppointmentById($id);

        if (!$appt || (int)$appt['medecin_id'] !== (int)$_SESSION['user_id']) {
            $this->jsonResponse(false, 'Rendez-vous introuvable ou accès refusé.');
            return;
        }

        $this->medecinModel->updateAppointmentStatus($id, 'refusé');
        $this->jsonResponse(true, 'Rendez-vous refusé.');
    }

    // ─────────────────────────────────────────
    //  Terminer un rendez-vous
    // ─────────────────────────────────────────
    public function completeAppointment(int $id): void {
        $this->auth->requireRole('medecin');

        $appt = $this->medecinModel->getAppointmentById($id);

        if (!$appt || (int)$appt['medecin_id'] !== (int)$_SESSION['user_id']) {
            $this->jsonResponse(false, 'Accès refusé.');
            return;
        }

        $note = trim($_POST['note'] ?? '');
        $this->medecinModel->completeAppointment($id, $note);
        $this->jsonResponse(true, 'Consultation terminée.');
    }

    // ─────────────────────────────────────────
    //  Liste de ses patients
    // ─────────────────────────────────────────
    public function patients(): void {
        $this->auth->requireRole('medecin');

        $medecinId = $_SESSION['user_id'];
        $patients  = $this->medecinModel->getPatients($medecinId);

        require_once __DIR__ . '/../views/frontoffice/medecin_patients.html';
    }

    // ─────────────────────────────────────────
    //  Fiche d'un patient
    // ─────────────────────────────────────────
    public function patientDetail(int $patientId): void {
        $this->auth->requireRole('medecin');

        $medecinId = $_SESSION['user_id'];

        // Vérifier que ce patient a bien consulté ce médecin
        if (!$this->medecinModel->hasPatient($medecinId, $patientId)) {
            http_response_code(403);
            die('Accès interdit.');
        }

        $patient  = $this->userModel->findById($patientId);
        $history  = $this->medecinModel->getPatientHistory($medecinId, $patientId);

        require_once __DIR__ . '/../views/frontoffice/medecin_patient_detail.html';
    }

    // ─────────────────────────────────────────
    //  Profil et disponibilités du médecin
    // ─────────────────────────────────────────
    public function profile(): void {
        $this->auth->requireRole('medecin');

        $userId  = $_SESSION['user_id'];
        $user    = $this->userModel->findById($userId);
        $medecin = $this->medecinModel->findByUserId($userId);

        require_once __DIR__ . '/../views/frontoffice/medecin_profil.html';
    }

    public function updateProfile(): void {
        $this->auth->requireRole('medecin');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /medecin/profil');
            exit;
        }

        $userId = $_SESSION['user_id'];

        $userData = [
            'nom'       => trim($_POST['nom']       ?? ''),
            'prenom'    => trim($_POST['prenom']    ?? ''),
            'telephone' => trim($_POST['telephone'] ?? ''),
            'adresse'   => trim($_POST['adresse']   ?? ''),
        ];

        $medecinData = [
            'specialite'       => trim($_POST['specialite']       ?? ''),
            'tarif'            => (float)($_POST['tarif']          ?? 0),
            'experience'       => (int)($_POST['experience']       ?? 0),
            'adresse_cabinet'  => trim($_POST['adresse_cabinet']  ?? ''),
            'bio'              => trim($_POST['bio']              ?? ''),
        ];

        $this->userModel->update($userId, $userData);
        $this->medecinModel->update($userId, $medecinData);

        $_SESSION['user_nom']    = $userData['nom'];
        $_SESSION['user_prenom'] = $userData['prenom'];

        $this->jsonResponse(true, 'Profil mis à jour.');
    }

    // ─────────────────────────────────────────
    //  Gestion des disponibilités
    // ─────────────────────────────────────────
    public function availabilities(): void {
        $this->auth->requireRole('medecin');

        $medecinId     = $_SESSION['user_id'];
        $availabilities = $this->medecinModel->getAvailabilities($medecinId);

        require_once __DIR__ . '/../views/frontoffice/medecin_disponibilites.html';
    }

    public function storeAvailability(): void {
        $this->auth->requireRole('medecin');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /medecin/disponibilites');
            exit;
        }

        $data = [
            'medecin_id'   => $_SESSION['user_id'],
            'jour_semaine' => (int)($_POST['jour_semaine'] ?? 1),
            'heure_debut'  => $_POST['heure_debut']  ?? '',
            'heure_fin'    => $_POST['heure_fin']    ?? '',
            'actif'        => 1,
        ];

        $id = $this->medecinModel->createAvailability($data);
        $this->jsonResponse(true, 'Disponibilité ajoutée.', ['id' => $id]);
    }

    public function deleteAvailability(int $id): void {
        $this->auth->requireRole('medecin');

        $this->medecinModel->deleteAvailability($id, $_SESSION['user_id']);
        $this->jsonResponse(true, 'Disponibilité supprimée.');
    }

    // ─────────────────────────────────────────
    //  Statistiques du médecin
    // ─────────────────────────────────────────
    public function stats(): void {
        $this->auth->requireRole('medecin');

        $medecinId = $_SESSION['user_id'];
        $stats     = $this->medecinModel->getStats($medecinId);

        header('Content-Type: application/json');
        echo json_encode($stats);
        exit;
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


