<?php
require_once __DIR__ . '/../models/RendezVous.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/Medecin.php';
require_once __DIR__ . '/../models/Patient.php';
require_once __DIR__ . '/../models/Disponibilite.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/AuthController.php';

class RendezVousController {
    private RendezVous $rdvModel;
    private Medecin $medecinModel;
    private Patient $patientModel;
    private Disponibilite $dispoModel;
    private AuthController $auth;
    private PDO $db;

    public function __construct() {
        $this->rdvModel     = new RendezVous();
        $this->medecinModel = new Medecin();
        $this->patientModel = new Patient();
        $this->dispoModel   = new Disponibilite();
        $this->auth         = new AuthController();
        $this->db           = Database::getInstance()->getConnection();
    }

    // ═══════════════════════════════════════════════════════════
    //  HELPER PRIVÉ : récupérer les utilisateurs par rôle
    //  User::getByRole() n'existe pas dans le modèle —
    //  on interroge directement la BDD.
    // ═══════════════════════════════════════════════════════════

    private function getUsersByRole(string $role): array {
        $stmt = $this->db->prepare(
            "SELECT id, nom, prenom, email, telephone
             FROM users
             WHERE role = :role AND statut = 'actif'
             ORDER BY nom, prenom"
        );
        $stmt->execute([':role' => $role]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // ═══════════════════════════════════════════════════════════
    //  FRONTOFFICE - MÉDECIN (MES RENDEZ-VOUS)
    // ═══════════════════════════════════════════════════════════

    public function medecinMesRendezVous(): void {
        $this->auth->requireRole('medecin');

        $medecinId   = (int)$_SESSION['user_id'];
        $todayRdv    = $this->rdvModel->getTodayByMedecin($medecinId);
        $upcomingRdv = $this->rdvModel->getUpcomingByMedecin($medecinId);
        $historyRdv  = $this->rdvModel->getHistoryByMedecin($medecinId);

        // ✅ Utilise le helper privé au lieu de User::getByRole()
        $patients = $this->getUsersByRole('patient');

        $stats = [
            'total'     => $this->rdvModel->countByMedecin($medecinId),
            'today'     => count($todayRdv),
            'upcoming'  => count($upcomingRdv),
            'completed' => $this->rdvModel->countByMedecinAndStatus($medecinId, 'terminé'),
        ];

        $flash = $_SESSION['flash'] ?? null;
        unset($_SESSION['flash']);

        require_once __DIR__ . '/../views/frontoffice/medecin/mes_rendezvous.php';
    }

    public function medecinCreate(): void {
        $this->auth->requireRole('medecin');

        $patients = $this->getUsersByRole('patient');

        $flash = $_SESSION['flash'] ?? null;
        unset($_SESSION['flash']);

        require_once __DIR__ . '/../views/frontoffice/medecin/creer_rendezvous.php';
    }

    public function medecinStore(): void {
        $this->auth->requireRole('medecin');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: index.php?page=medecin_rendezvous');
            exit;
        }

        $data = [
            'patient_id'       => (int)($_POST['patient_id'] ?? 0),
            'medecin_id'       => (int)$_SESSION['user_id'],
            'date_rendezvous'  => $_POST['date_rendezvous']  ?? '',
            'heure_rendezvous' => $_POST['heure_rendezvous'] ?? '',
            'motif'            => trim($_POST['motif'] ?? ''),
            'statut'           => $_POST['statut'] ?? 'en_attente',
        ];

        $errors = $this->validate($data);

        if (!empty($errors)) {
            $_SESSION['flash'] = ['type' => 'error', 'message' => implode('<br>', $errors)];
            header('Location: index.php?page=medecin_rendezvous&action=create');
            exit;
        }

        $this->rdvModel->create($data);
        $_SESSION['flash'] = ['type' => 'success', 'message' => 'Rendez-vous créé avec succès.'];
        header('Location: index.php?page=medecin_rendezvous');
        exit;
    }

    public function medecinEdit(int $id): void {
        $this->auth->requireRole('medecin');

        $rdv       = $this->rdvModel->getById($id);
        $medecinId = (int)$_SESSION['user_id'];

        if (!$rdv || $rdv['medecin_id'] != $medecinId) {
            $_SESSION['flash'] = ['type' => 'error', 'message' => 'Rendez-vous introuvable.'];
            header('Location: index.php?page=medecin_rendezvous');
            exit;
        }

        $patients = $this->getUsersByRole('patient');

        $flash = $_SESSION['flash'] ?? null;
        unset($_SESSION['flash']);

        require_once __DIR__ . '/../views/frontoffice/medecin/modifier_rendezvous.php';
    }

    public function medecinUpdate(int $id): void {
        $this->auth->requireRole('medecin');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: index.php?page=medecin_rendezvous&action=edit&id=$id");
            exit;
        }

        $rdv       = $this->rdvModel->getById($id);
        $medecinId = (int)$_SESSION['user_id'];

        if (!$rdv || $rdv['medecin_id'] != $medecinId) {
            $_SESSION['flash'] = ['type' => 'error', 'message' => 'Rendez-vous introuvable.'];
            header('Location: index.php?page=medecin_rendezvous');
            exit;
        }

        $data = [
            'patient_id'       => (int)($_POST['patient_id'] ?? 0),
            'date_rendezvous'  => $_POST['date_rendezvous']  ?? '',
            'heure_rendezvous' => $_POST['heure_rendezvous'] ?? '',
            'motif'            => trim($_POST['motif'] ?? ''),
            'statut'           => $_POST['statut'] ?? 'en_attente',
        ];

        $this->rdvModel->update($id, $data);
        $_SESSION['flash'] = ['type' => 'success', 'message' => 'Rendez-vous mis à jour.'];
        header('Location: index.php?page=medecin_rendezvous');
        exit;
    }

    public function medecinDelete(int $id): void {
        $this->auth->requireRole('medecin');

        $rdv       = $this->rdvModel->getById($id);
        $medecinId = (int)$_SESSION['user_id'];

        if (!$rdv || $rdv['medecin_id'] != $medecinId) {
            $_SESSION['flash'] = ['type' => 'error', 'message' => 'Rendez-vous introuvable.'];
            header('Location: index.php?page=medecin_rendezvous');
            exit;
        }

        $this->rdvModel->delete($id);
        $_SESSION['flash'] = ['type' => 'success', 'message' => 'Rendez-vous supprimé.'];
        header('Location: index.php?page=medecin_rendezvous');
        exit;
    }

    public function medecinConfirmerRendezVous(int $id): void {
        $this->auth->requireRole('medecin');

        $rdv       = $this->rdvModel->getById($id);
        $medecinId = (int)$_SESSION['user_id'];

        if (!$rdv || $rdv['medecin_id'] != $medecinId) {
            $_SESSION['flash'] = ['type' => 'error', 'message' => 'Rendez-vous introuvable.'];
            header('Location: index.php?page=medecin_rendezvous');
            exit;
        }

        $this->rdvModel->updateStatus($id, 'confirmé');
        $_SESSION['flash'] = ['type' => 'success', 'message' => 'Rendez-vous confirmé.'];
        header('Location: index.php?page=medecin_rendezvous');
        exit;
    }

    public function medecinTerminerRendezVous(int $id): void {
        $this->auth->requireRole('medecin');

        $rdv       = $this->rdvModel->getById($id);
        $medecinId = (int)$_SESSION['user_id'];

        if (!$rdv || $rdv['medecin_id'] != $medecinId) {
            $_SESSION['flash'] = ['type' => 'error', 'message' => 'Rendez-vous introuvable.'];
            header('Location: index.php?page=medecin_rendezvous');
            exit;
        }

        $this->rdvModel->updateStatus($id, 'terminé');

        if (!empty($_POST['notes'])) {
            $this->rdvModel->update($id, ['notes_medecin' => trim($_POST['notes'])]);
        }

        $_SESSION['flash'] = ['type' => 'success', 'message' => 'Consultation terminée.'];
        header('Location: index.php?page=medecin_rendezvous');
        exit;
    }

    public function medecinAnnulerRendezVous(int $id): void {
        $this->auth->requireRole('medecin');

        $rdv       = $this->rdvModel->getById($id);
        $medecinId = (int)$_SESSION['user_id'];

        if (!$rdv || $rdv['medecin_id'] != $medecinId) {
            $_SESSION['flash'] = ['type' => 'error', 'message' => 'Rendez-vous introuvable.'];
            header('Location: index.php?page=medecin_rendezvous');
            exit;
        }

        $this->rdvModel->updateStatus($id, 'annulé');
        $_SESSION['flash'] = ['type' => 'success', 'message' => 'Rendez-vous annulé.'];
        header('Location: index.php?page=medecin_rendezvous');
        exit;
    }

    public function medecinAjouterNote(int $id): void {
        $this->auth->requireRole('medecin');

        $rdv       = $this->rdvModel->getById($id);
        $medecinId = (int)$_SESSION['user_id'];

        if (!$rdv || $rdv['medecin_id'] != $medecinId) {
            $_SESSION['flash'] = ['type' => 'error', 'message' => 'Rendez-vous introuvable.'];
            header('Location: index.php?page=medecin_rendezvous');
            exit;
        }

        $flash = $_SESSION['flash'] ?? null;
        unset($_SESSION['flash']);

        require_once __DIR__ . '/../views/frontoffice/medecin/ajouter_note.php';
    }

    public function medecinSaveNote(int $id): void {
        $this->auth->requireRole('medecin');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: index.php?page=medecin_rendezvous");
            exit;
        }

        $rdv       = $this->rdvModel->getById($id);
        $medecinId = (int)$_SESSION['user_id'];

        if (!$rdv || $rdv['medecin_id'] != $medecinId) {
            $_SESSION['flash'] = ['type' => 'error', 'message' => 'Rendez-vous introuvable.'];
            header('Location: index.php?page=medecin_rendezvous');
            exit;
        }

        $this->rdvModel->update($id, ['notes_medecin' => trim($_POST['notes'] ?? '')]);
        $_SESSION['flash'] = ['type' => 'success', 'message' => 'Note ajoutée avec succès.'];
        header('Location: index.php?page=medecin_rendezvous');
        exit;
    }

    // ═══════════════════════════════════════════════════════════
    //  FRONTOFFICE - PATIENT
    // ═══════════════════════════════════════════════════════════

    public function patientMesRendezVous(): void {
        $this->auth->requireRole('patient');

        $patientId = (int)$_SESSION['user_id'];
        $rdvs      = $this->rdvModel->getByPatient($patientId);
        $upcoming  = $this->rdvModel->getUpcomingByPatient($patientId);

        $flash = $_SESSION['flash'] ?? null;
        unset($_SESSION['flash']);

        require_once __DIR__ . '/../views/frontoffice/patient/mes_rendezvous.php';
    }

    public function patientPrendreRendezVous(): void {
        $this->auth->requireRole('patient');

        $medecins  = $this->getUsersByRole('medecin');
        $date      = $_GET['date'] ?? date('Y-m-d');
        $medecinId = (int)($_GET['medecin_id'] ?? 0);

        $slots = [];
        if ($medecinId > 0) {
            $slots = $this->dispoModel->getAvailableSlots($medecinId, $date);
        }

        $flash = $_SESSION['flash'] ?? null;
        unset($_SESSION['flash']);

        require_once __DIR__ . '/../views/frontoffice/patient/prendre_rendezvous.php';
    }

    public function patientStoreRendezVous(): void {
        $this->auth->requireRole('patient');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: index.php?page=prendre_rendezvous');
            exit;
        }

        $data = [
            'patient_id'       => (int)$_SESSION['user_id'],
            'medecin_id'       => (int)($_POST['medecin_id'] ?? 0),
            'date_rendezvous'  => $_POST['date_rendezvous']  ?? '',
            'heure_rendezvous' => $_POST['heure_rendezvous'] ?? '',
            'motif'            => trim($_POST['motif'] ?? ''),
            'statut'           => 'en_attente',
        ];

        $errors = $this->validate($data);

        if (!empty($errors)) {
            $_SESSION['flash'] = ['type' => 'error', 'message' => implode('<br>', $errors)];
            header('Location: index.php?page=prendre_rendezvous&medecin_id=' . $data['medecin_id']);
            exit;
        }

        $this->rdvModel->create($data);
        $_SESSION['flash'] = ['type' => 'success', 'message' => 'Rendez-vous demandé avec succès.'];
        header('Location: index.php?page=mes_rendezvous');
        exit;
    }

    public function patientAnnulerRendezVous(int $id): void {
        $this->auth->requireRole('patient');

        $rdv = $this->rdvModel->getById($id);

        if (!$rdv || $rdv['patient_id'] != $_SESSION['user_id']) {
            $_SESSION['flash'] = ['type' => 'error', 'message' => 'Rendez-vous introuvable.'];
            header('Location: index.php?page=mes_rendezvous');
            exit;
        }

        $this->rdvModel->updateStatus($id, 'annulé');
        $_SESSION['flash'] = ['type' => 'success', 'message' => 'Rendez-vous annulé.'];
        header('Location: index.php?page=mes_rendezvous');
        exit;
    }

    // ═══════════════════════════════════════════════════════════
    //  BACKOFFICE - ADMIN
    // ═══════════════════════════════════════════════════════════

    public function adminIndex(): void {
        $this->auth->requireRole('admin');

        $filter  = $_GET['filter']  ?? 'all';
        $medecin = $_GET['medecin'] ?? null;
        $patient = $_GET['patient'] ?? null;

        $rdvs     = $this->rdvModel->getAll($filter, $medecin, $patient);
        $medecins = $this->getUsersByRole('medecin');
        $patients = $this->getUsersByRole('patient');

        $stats = [
            'total'      => $this->rdvModel->countAll(),
            'en_attente' => $this->rdvModel->countByStatus('en_attente'),
            'confirmes'  => $this->rdvModel->countByStatus('confirmé'),
            'termines'   => $this->rdvModel->countByStatus('terminé'),
        ];

        $flash = $_SESSION['flash'] ?? null;
        unset($_SESSION['flash']);

        require_once __DIR__ . '/../views/backoffice/rendezvous/list.php';
    }

    public function adminCreate(): void {
        $this->auth->requireRole('admin');

        $medecins = $this->getUsersByRole('medecin');
        $patients = $this->getUsersByRole('patient');

        $flash = $_SESSION['flash'] ?? null;
        unset($_SESSION['flash']);

        require_once __DIR__ . '/../views/backoffice/rendezvous/form.php';
    }

    public function adminStore(): void {
        $this->auth->requireRole('admin');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: index.php?page=admin_rendezvous');
            exit;
        }

        $data = [
            'patient_id'       => (int)($_POST['patient_id'] ?? 0),
            'medecin_id'       => (int)($_POST['medecin_id'] ?? 0),
            'date_rendezvous'  => $_POST['date_rendezvous']  ?? '',
            'heure_rendezvous' => $_POST['heure_rendezvous'] ?? '',
            'motif'            => trim($_POST['motif'] ?? ''),
            'statut'           => $_POST['statut'] ?? 'en_attente',
        ];

        $errors = $this->validate($data);

        if (!empty($errors)) {
            $_SESSION['flash'] = ['type' => 'error', 'message' => implode('<br>', $errors)];
            header('Location: index.php?page=admin_rendezvous&action=create');
            exit;
        }

        $this->rdvModel->create($data);
        $_SESSION['flash'] = ['type' => 'success', 'message' => 'Rendez-vous créé.'];
        header('Location: index.php?page=admin_rendezvous');
        exit;
    }

    public function adminEdit(int $id): void {
        $this->auth->requireRole('admin');

        $rdv = $this->rdvModel->getById($id);
        if (!$rdv) $this->notFound();

        $medecins = $this->getUsersByRole('medecin');
        $patients = $this->getUsersByRole('patient');

        $flash = $_SESSION['flash'] ?? null;
        unset($_SESSION['flash']);

        require_once __DIR__ . '/../views/backoffice/rendezvous/form.php';
    }

    public function adminUpdate(int $id): void {
        $this->auth->requireRole('admin');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: index.php?page=admin_rendezvous&action=edit&id=$id");
            exit;
        }

        $data = [
            'date_rendezvous'  => $_POST['date_rendezvous']  ?? '',
            'heure_rendezvous' => $_POST['heure_rendezvous'] ?? '',
            'motif'            => trim($_POST['motif'] ?? ''),
            'statut'           => $_POST['statut'] ?? 'en_attente',
        ];

        $this->rdvModel->update($id, $data);
        $_SESSION['flash'] = ['type' => 'success', 'message' => 'Rendez-vous mis à jour.'];
        header('Location: index.php?page=admin_rendezvous');
        exit;
    }

    public function adminDelete(int $id): void {
        $this->auth->requireRole('admin');

        $this->rdvModel->delete($id);
        $_SESSION['flash'] = ['type' => 'success', 'message' => 'Rendez-vous supprimé.'];
        header('Location: index.php?page=admin_rendezvous');
        exit;
    }

    public function adminShow(int $id): void {
        $this->auth->requireRole('admin');

        $rdv = $this->rdvModel->getById($id);
        if (!$rdv) $this->notFound();

        require_once __DIR__ . '/../views/backoffice/rendezvous/show.php';
    }

    // ═══════════════════════════════════════════════════════════
    //  API
    // ═══════════════════════════════════════════════════════════

    public function apiGetSlots(): void {
        header('Content-Type: application/json');

        $medecinId = (int)($_GET['medecin_id'] ?? 0);
        $date      = $_GET['date'] ?? date('Y-m-d');

        if (!$medecinId) {
            echo json_encode(['error' => 'Médecin non spécifié']);
            exit;
        }

        $slots = $this->dispoModel->getAvailableSlots($medecinId, $date);
        echo json_encode(['success' => true, 'slots' => $slots]);
        exit;
    }

    // ═══════════════════════════════════════════════════════════
    //  HELPERS PRIVÉS
    // ═══════════════════════════════════════════════════════════

    private function validate(array $data): array {
        $errors = [];

        if (empty($data['patient_id']))       $errors[] = 'Le patient est obligatoire.';
        if (empty($data['medecin_id']))        $errors[] = 'Le médecin est obligatoire.';
        if (empty($data['date_rendezvous']))   $errors[] = 'La date est obligatoire.';
        if (empty($data['heure_rendezvous']))  $errors[] = "L'heure est obligatoire.";

        if (!empty($data['date_rendezvous']) && $data['date_rendezvous'] < date('Y-m-d')) {
            $errors[] = 'La date ne peut pas être dans le passé.';
        }

        return $errors;
    }

    private function notFound(): void {
        http_response_code(404);
        die('Rendez-vous introuvable.');
    }
        // ═══════════════════════════════════════════════════════════
    //  FRONTOFFICE - PATIENT (MODIFIER UN RENDEZ-VOUS)
    // ═══════════════════════════════════════════════════════════

    public function patientModifierRendezVous(int $id): void {
        $this->auth->requireRole('patient');
        
        $rdv = $this->rdvModel->getById($id);
        
        // Vérifier que le rendez-vous appartient bien au patient connecté
        if (!$rdv || $rdv['patient_id'] != $_SESSION['user_id']) {
            $_SESSION['flash'] = ['type' => 'error', 'message' => 'Rendez-vous introuvable.'];
            header('Location: index.php?page=mes_rendez_vous');
            exit;
        }
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = [
                'date_rendezvous' => $_POST['date_rendezvous'],
                'heure_rendezvous' => $_POST['heure_rendezvous'],
                'motif' => trim($_POST['motif'] ?? '')
            ];
            
            $errors = $this->validate($data);
            
            if (!empty($errors)) {
                $_SESSION['flash'] = ['type' => 'error', 'message' => implode('<br>', $errors)];
                header("Location: index.php?page=mes_rendez_vous");
                exit;
            }
            
            $this->rdvModel->update($id, $data);
            $_SESSION['flash'] = ['type' => 'success', 'message' => 'Rendez-vous modifié avec succès.'];
            header('Location: index.php?page=mes_rendez_vous');
            exit;
        }
    }
}
?>