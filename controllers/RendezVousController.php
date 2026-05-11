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
    /** @var Ordonnance|null */
    private $ordModel = null;

    public function __construct() {
        $this->rdvModel     = new RendezVous();
        $this->medecinModel = new Medecin();
        $this->patientModel = new Patient();
        $this->dispoModel   = new Disponibilite();
        $this->auth         = new AuthController();
        $this->db           = Database::getInstance()->getConnection();
        if (is_file(__DIR__ . '/../models/Ordonnance.php')) {
            require_once __DIR__ . '/../models/Ordonnance.php';
            $this->ordModel = new Ordonnance();
        }
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

        $mailSent = $this->sendConfirmationEmail($rdv);

        $_SESSION['flash'] = [
            'type' => 'success',
            'message' => $mailSent
                ? 'Rendez-vous confirmé. Un e-mail de confirmation a été envoyé au patient.'
                : 'Rendez-vous confirmé. L\'e-mail n\'a pas pu être envoyé (vérifiez MAIL_HOST / MAIL_USERNAME / MAIL_PASSWORD dans .env).',
        ];
        header('Location: index.php?page=mes_rendez_vous');
        exit;
    }

    /**
     * E-mail au patient après confirmation du RDV (SMTP via config/mail.php / .env).
     */
    private function sendConfirmationEmail(array $rdv): bool {
        if (!class_exists('MailConfig')) {
            require_once __DIR__ . '/../config/mail.php';
        }

        $patientEmail = trim((string)($rdv['patient_email'] ?? ''));
        if ($patientEmail === '' || !filter_var($patientEmail, FILTER_VALIDATE_EMAIL)) {
            error_log('sendConfirmationEmail: adresse patient invalide ou absente.');
            return false;
        }

        if (!MailConfig::isConfigured()) {
            error_log('sendConfirmationEmail: Mail non configuré (.env MAIL_*).');
            return false;
        }

        $patientNom = trim((string)(($rdv['patient_prenom'] ?? '') . ' ' . ($rdv['patient_nom'] ?? '')));
        if ($patientNom === '') {
            $patientNom = 'Patient';
        }

        $medecinNom = trim((string)(($rdv['medecin_prenom'] ?? '') . ' ' . ($rdv['medecin_nom'] ?? '')));
        if ($medecinNom !== '') {
            $medecinNom = 'Dr. ' . $medecinNom;
        } else {
            $medecinNom = 'Votre médecin';
        }

        $specialite = htmlspecialchars((string)($rdv['specialite'] ?? ''), ENT_QUOTES, 'UTF-8');
        $dateStr    = htmlspecialchars(date('d/m/Y', strtotime((string)($rdv['date_rendezvous'] ?? 'now'))), ENT_QUOTES, 'UTF-8');
        $heure      = htmlspecialchars((string)($rdv['heure_rendezvous'] ?? ''), ENT_QUOTES, 'UTF-8');
        $motif      = htmlspecialchars(trim((string)($rdv['motif'] ?? '')), ENT_QUOTES, 'UTF-8');
        if ($motif === '') {
            $motif = 'Non précisé';
        }
        $adresse = htmlspecialchars(trim((string)($rdv['cabinet_adresse'] ?? '')), ENT_QUOTES, 'UTF-8');
        if ($adresse === '') {
            $adresse = 'Voir les informations sur votre espace Valorys.';
        }

        $subject = 'Confirmation de votre rendez-vous — Valorys';
        $displayName = htmlspecialchars($patientNom, ENT_QUOTES, 'UTF-8');

        $body = '<!DOCTYPE html><html lang="fr"><head><meta charset="UTF-8"></head><body style="margin:0;background:#f5f7fb;font-family:Segoe UI,Arial,sans-serif;">'
            . '<div style="max-width:600px;margin:24px auto;background:#fff;border-radius:12px;overflow:hidden;box-shadow:0 4px 24px rgba(0,0,0,.08);">'
            . '<div style="background:linear-gradient(135deg,#2A7FAA,#4CAF50);padding:22px;text-align:center;">'
            . '<h1 style="color:#fff;margin:0;font-size:20px;">Rendez-vous confirmé</h1></div>'
            . '<div style="padding:28px;color:#333;font-size:15px;line-height:1.55;">'
            . '<p>Bonjour ' . $displayName . ',</p>'
            . '<p>Votre médecin a <strong>confirmé</strong> le rendez-vous suivant :</p>'
            . '<table style="width:100%;border-collapse:collapse;background:#f8fafc;border-radius:8px;margin:16px 0;">'
            . '<tr><td style="padding:12px 16px;border-bottom:1px solid #e9ecef;"><strong>Médecin</strong></td><td style="padding:12px 16px;border-bottom:1px solid #e9ecef;">'
            . htmlspecialchars($medecinNom, ENT_QUOTES, 'UTF-8') . ($specialite !== '' ? ' — ' . $specialite : '') . '</td></tr>'
            . '<tr><td style="padding:12px 16px;border-bottom:1px solid #e9ecef;"><strong>Date</strong></td><td style="padding:12px 16px;border-bottom:1px solid #e9ecef;">' . $dateStr . '</td></tr>'
            . '<tr><td style="padding:12px 16px;border-bottom:1px solid #e9ecef;"><strong>Heure</strong></td><td style="padding:12px 16px;border-bottom:1px solid #e9ecef;">' . $heure . '</td></tr>'
            . '<tr><td style="padding:12px 16px;border-bottom:1px solid #e9ecef;"><strong>Motif</strong></td><td style="padding:12px 16px;border-bottom:1px solid #e9ecef;">' . $motif . '</td></tr>'
            . '<tr><td style="padding:12px 16px;"><strong>Cabinet</strong></td><td style="padding:12px 16px;">' . $adresse . '</td></tr>'
            . '</table>'
            . '<p style="font-size:14px;color:#6c757d;">Connectez-vous à Valorys pour plus de détails ou pour modifier votre rendez-vous selon les règles du cabinet.</p>'
            . '<p>Cordialement,<br><strong>L’équipe Valorys</strong></p>'
            . '</div><div style="background:#1a2035;color:#adb5bd;padding:14px;text-align:center;font-size:12px;">Message automatique — ne pas répondre.</div>'
            . '</div></body></html>';

        $alt = strip_tags(str_replace(['<br>', '<br/>', '<br />'], "\n", $body));

        try {
            return MailConfig::send($patientEmail, $patientNom, $subject, $body, $alt);
        } catch (Throwable $e) {
            error_log('sendConfirmationEmail: ' . $e->getMessage());
            return false;
        }
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

        $wasBlocking = !in_array((string)($rdv['statut'] ?? ''), ['annulé', 'terminé'], true);
        $slotMid     = (int)$rdv['medecin_id'];
        $slotDate    = (string)$rdv['date_rendezvous'];
        $slotHeure   = RendezVous::normalizeHeureRendezvous((string)($rdv['heure_rendezvous'] ?? ''));

        $this->rdvModel->updateStatus($id, 'annulé');

        if ($wasBlocking) {
            $this->rdvModel->fillWaitlistForFreedSlot($slotMid, $slotDate, $slotHeure);
        }

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

        $wasBlocking = !in_array((string)($rdv['statut'] ?? ''), ['annulé', 'terminé'], true);
        $slotMid     = (int)$rdv['medecin_id'];
        $slotDate    = (string)$rdv['date_rendezvous'];
        $slotHeure   = RendezVous::normalizeHeureRendezvous((string)($rdv['heure_rendezvous'] ?? ''));

        $this->rdvModel->updateStatus($id, 'annulé');

        if ($wasBlocking) {
            $this->rdvModel->fillWaitlistForFreedSlot($slotMid, $slotDate, $slotHeure);
        }

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

    public function apiRendezVousChatbot(): void {
        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        header('Content-Type: application/json; charset=utf-8');

        if (empty($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode(['success' => false, 'reply' => 'Veuillez vous connecter pour utiliser le chatbot.']);
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'reply' => 'Methode non autorisee.']);
            exit;
        }

        $payload = json_decode(file_get_contents('php://input'), true) ?? [];
        $message = trim((string)($payload['message'] ?? ''));

        if ($message === '') {
            echo json_encode(['success' => false, 'reply' => 'Posez-moi une question sur les rendez-vous, les ordonnances ou les disponibilités.']);
            exit;
        }

        if (strlen($message) > 600) {
            echo json_encode(['success' => false, 'reply' => 'Votre question est trop longue. Merci de la raccourcir.']);
            exit;
        }

        $context = $this->buildRendezVousChatContext();
        $action = $this->detectRendezVousChatAction($message, $context);
        $reply = $this->askFreeLocalAi($message, $context);

        if ($reply === null) {
            $reply = $this->fallbackRendezVousReply($message, $context);
        }

        echo json_encode(['success' => true, 'reply' => $reply, 'action' => $action], JSON_UNESCAPED_UNICODE);
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

    private function buildRendezVousChatContext(): array {
        $role = $_SESSION['user_role'] ?? 'patient';
        $userId = (int)($_SESSION['user_id'] ?? 0);
        $context = [
            'role' => $role,
            'today' => date('Y-m-d'),
            'summary' => [],
            'actions' => [
                'patient' => 'RDV (Mes RDV, prendre RDV), ordonnances (Mes ordonnances), liste d attente selon le flux du formulaire',
                'medecin' => 'RDV, ordonnances délivrées, créneaux / disponibilités cabinet',
                'admin' => 'filtrer, créer, modifier ou supprimer les rendez-vous et suivre les statuts',
            ],
        ];

        if ($role === 'patient') {
            $upcoming = $this->rdvModel->getUpcomingByPatient($userId);
            $context['summary'][] = 'Prochains rendez-vous patient: ' . count($upcoming);
            foreach (array_slice($upcoming, 0, 3) as $rdv) {
                $context['summary'][] = sprintf(
                    '%s a %s avec Dr. %s %s, statut %s',
                    $rdv['date_rendezvous'] ?? '-',
                    $rdv['heure_rendezvous'] ?? '-',
                    $rdv['medecin_prenom'] ?? '',
                    $rdv['medecin_nom'] ?? '',
                    $rdv['statut'] ?? '-'
                );
            }
            if ($this->ordModel) {
                $ords = $this->ordModel->getAllByPatient($userId);
                $context['summary'][] = 'Ordonnances patient (total): ' . count($ords);
                foreach (array_slice($ords, 0, 3) as $o) {
                    $context['summary'][] = sprintf(
                        'Ordonnance %s du %s — Dr. %s — statut %s',
                        $o['numero_ordonnance'] ?? ('#' . ($o['id'] ?? '')),
                        $o['date_ordonnance'] ?? '-',
                        $o['medecin_nom'] ?? '-',
                        $o['status'] ?? '-'
                    );
                }
            }
        } elseif ($role === 'medecin') {
            $today = $this->rdvModel->getTodayByMedecin($userId);
            $upcoming = $this->rdvModel->getUpcomingByMedecin($userId);
            $context['summary'][] = 'Rendez-vous aujourd hui: ' . count($today);
            $context['summary'][] = 'Rendez-vous a venir: ' . count($upcoming);
            foreach (array_slice($today, 0, 3) as $rdv) {
                $context['summary'][] = sprintf(
                    'Aujourd hui %s avec %s %s, statut %s',
                    $rdv['heure_rendezvous'] ?? '-',
                    $rdv['patient_prenom'] ?? '',
                    $rdv['patient_nom'] ?? '',
                    $rdv['statut'] ?? '-'
                );
            }
            if ($this->ordModel) {
                $ords = $this->ordModel->getAllByMedecin($userId);
                $context['summary'][] = 'Ordonnances rédigées (total): ' . count($ords);
                foreach (array_slice($ords, 0, 3) as $o) {
                    $context['summary'][] = sprintf(
                        'Ordonnance %s patient %s date %s',
                        $o['numero_ordonnance'] ?? ('#' . ($o['id'] ?? '')),
                        $o['patient_nom'] ?? '-',
                        $o['date_ordonnance'] ?? '-'
                    );
                }
            }
            $dispos = $this->dispoModel->getByMedecin($userId);
            $context['summary'][] = 'Disponibilités actives (créneaux): ' . count($dispos);
            foreach (array_slice($dispos, 0, 8) as $d) {
                $context['summary'][] = sprintf(
                    'Créneau %s %s-%s',
                    $d['jour_semaine'] ?? '-',
                    $d['heure_debut'] ?? '',
                    $d['heure_fin'] ?? ''
                );
            }
        } elseif ($role === 'admin') {
            $context['summary'][] = 'Total rendez-vous: ' . $this->rdvModel->countAll();
            $context['summary'][] = 'En attente: ' . $this->countRendezVousByStatusForChat('en_attente');
            $context['summary'][] = 'Confirmes: ' . $this->countRendezVousByStatusForChat('confirmÃ©');
            $context['summary'][] = 'Termines: ' . $this->countRendezVousByStatusForChat('terminÃ©');
        }

        return $context;
    }

    private function countRendezVousByStatusForChat(string $status): int {
        if (method_exists($this->rdvModel, 'countByStatus')) {
            return (int)$this->rdvModel->countByStatus($status);
        }

        if (method_exists($this->rdvModel, 'countByStatut')) {
            return (int)$this->rdvModel->countByStatut($status);
        }

        return 0;
    }

    private function askFreeLocalAi(string $message, array $context): ?string {
        if (!function_exists('curl_init')) {
            return null;
        }

        $model = getenv('OLLAMA_MODEL') ?: 'llama3.2';
        $prompt = "Tu es l assistant gratuit Valorys pour rendez-vous, ordonnances (liste, PDF, validité) et disponibilités médecin (créneaux). "
            . "Reponds en francais, en 2 a 5 phrases, sans diagnostic medical ni posologie. "
            . "Pour urgence medicale, conseille de contacter les urgences. "
            . "Contexte: " . implode(' | ', $context['summary'])
            . " | Role: " . ($context['role'] ?? 'utilisateur')
            . " | Action disponible: " . ($context['actions'][$context['role']] ?? 'gestion rendez-vous')
            . "\nQuestion: " . $message;

        $ch = curl_init('http://127.0.0.1:11434/api/generate');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            CURLOPT_POSTFIELDS => json_encode([
                'model' => $model,
                'prompt' => $prompt,
                'stream' => false,
                'options' => ['temperature' => 0.3],
            ]),
            CURLOPT_CONNECTTIMEOUT => 2,
            CURLOPT_TIMEOUT => 8,
        ]);

        $response = curl_exec($ch);
        $status = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($response === false || $status < 200 || $status >= 300) {
            return null;
        }

        $data = json_decode($response, true);
        $reply = trim((string)($data['response'] ?? ''));

        return $reply !== '' ? $reply : null;
    }

    private function detectRendezVousChatAction(string $message, array $context): ?array {
        $text = strtolower($message);
        $role = $context['role'] ?? 'patient';
        preg_match('/\b(\d+)\b/', $text, $matches);
        $rdvId = isset($matches[1]) ? (int)$matches[1] : 0;

        $wantOrdonnance = str_contains($text, 'ordonnan') || str_contains($text, 'prescript')
            || str_contains($text, 'médicament') || str_contains($text, 'medicament')
            || str_contains($text, 'pdf ordonnance');
        if ($wantOrdonnance) {
            if ($role === 'patient') {
                return [
                    'type' => 'redirect',
                    'url' => 'index.php?page=mes_ordonnances',
                    'label' => 'Ouvrir Mes ordonnances',
                ];
            }
            if ($role === 'medecin') {
                return [
                    'type' => 'redirect',
                    'url' => 'index.php?page=ordonnances',
                    'label' => 'Ouvrir la liste des ordonnances',
                ];
            }
        }

        if ($role === 'medecin' && (str_contains($text, 'disponibi') || str_contains($text, 'créneau')
                || str_contains($text, 'creneau') || str_contains($text, 'horaire cabinet'))) {
            return [
                'type' => 'redirect',
                'url' => 'index.php?page=disponibilites',
                'label' => 'Gérer mes disponibilités',
            ];
        }

        if ($role === 'patient' && (str_contains($text, 'prendre') || str_contains($text, 'nouveau') || str_contains($text, 'reserver'))) {
            return [
                'type' => 'redirect',
                'url' => 'index.php?page=prendre_rendez_vous',
                'label' => 'J ouvre la page pour prendre rendez-vous.',
            ];
        }

        if (str_contains($text, 'mes rdv') || str_contains($text, 'mes rendez')
            || (str_contains($text, 'liste') && str_contains($text, 'rendez'))) {
            return [
                'type' => 'redirect',
                'url' => 'index.php?page=mes_rendez_vous',
                'label' => 'Ouvrir Mes rendez-vous',
            ];
        }

        if ($role === 'patient' && (str_contains($text, 'annul') || str_contains($text, 'supprim')) && $rdvId > 0) {
            return [
                'type' => 'confirm_redirect',
                'url' => 'index.php?page=annuler_rendez_vous&id=' . $rdvId,
                'label' => 'Confirmer l annulation du RDV #' . $rdvId,
            ];
        }

        if ((str_contains($text, 'detail') || str_contains($text, 'voir') || str_contains($text, 'modifier') || str_contains($text, 'deplacer')) && $rdvId > 0) {
            return [
                'type' => 'redirect',
                'url' => 'index.php?page=detail_rendez_vous&id=' . $rdvId,
                'label' => 'J ouvre le rendez-vous #' . $rdvId . '.',
            ];
        }

        if ($role === 'medecin' && str_contains($text, 'confirmer') && $rdvId > 0) {
            return [
                'type' => 'confirm_redirect',
                'url' => 'index.php?page=confirmer_rendez_vous&id=' . $rdvId,
                'label' => 'Confirmer le RDV #' . $rdvId,
            ];
        }

        if ($role === 'medecin' && str_contains($text, 'terminer') && $rdvId > 0) {
            return [
                'type' => 'confirm_redirect',
                'url' => 'index.php?page=terminer_rendez_vous&id=' . $rdvId,
                'label' => 'Marquer le RDV #' . $rdvId . ' comme termine',
            ];
        }

        return null;
    }

    private function fallbackRendezVousReply(string $message, array $context): string {
        $text = strtolower($message);
        $role = $context['role'] ?? 'patient';
        $summary = !empty($context['summary']) ? "\n\nResume actuel: " . implode(' | ', $context['summary']) : '';

        if (str_contains($text, 'annul')) {
            return "Pour annuler, ouvrez le rendez-vous concerne puis utilisez le bouton Annuler. Verifiez bien la date et le patient avant de confirmer." . $summary;
        }

        if (str_contains($text, 'modif') || str_contains($text, 'changer') || str_contains($text, 'deplacer')) {
            return "Pour modifier un rendez-vous, utilisez l action Modifier, choisissez une nouvelle date et un creneau disponible, puis enregistrez." . $summary;
        }

        if (str_contains($text, 'dispo') || str_contains($text, 'creneau') || str_contains($text, 'heure')) {
            $extra = ($role === 'medecin')
                ? ' En tant que médecin, vos créneaux récurrents se gèrent dans Disponibilités (menu Médecin). Les patients voient les horaires libres après choix du médecin et de la date.'
                : '';
            return "Les créneaux côté patient se proposent après le choix du médecin et de la date. Si un créneau est pris, vous pouvez être mis en liste d’attente selon le message affiché." . $extra . $summary;
        }

        if (str_contains($text, 'ordonnan') || str_contains($text, 'prescript') || str_contains($text, 'médicament') || str_contains($text, 'medicament')) {
            if ($role === 'patient') {
                return "Vos ordonnances sont dans « Mes ordonnances » : vous pouvez consulter le détail et télécharger le PDF. Pour toute question médicale, contactez votre médecin." . $summary;
            }
            if ($role === 'medecin') {
                return "Les ordonnances se créent depuis le détail d’un rendez-vous terminé ou via la liste ordonnances. Vérifiez diagnostic, médicaments et date de validité avant enregistrement." . $summary;
            }
        }

        if (str_contains($text, 'statut') || str_contains($text, 'confirm')) {
            return "Les statuts principaux sont en attente, confirme, termine et annule. Un medecin peut confirmer ou terminer une consultation, et l admin peut suivre l ensemble des statuts." . $summary;
        }

        if ($role === 'admin') {
            return "Je peux vous aider a analyser la liste, filtrer par statut, retrouver un rendez-vous ou preparer une modification. Dites-moi ce que vous cherchez exactement." . $summary;
        }

        if ($role === 'medecin') {
            return "Je peux vous aider sur la journée (RDV), les ordonnances délivrées et vos créneaux de disponibilité. Utilisez les liens proposés ou précisez votre situation." . $summary;
        }

        return "Je peux vous guider sur les rendez-vous, vos ordonnances et la prise de créneaux. Pour une urgence médicale, contactez les services d’urgence." . $summary;
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
