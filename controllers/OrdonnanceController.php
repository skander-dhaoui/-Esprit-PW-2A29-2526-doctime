<?php
require_once __DIR__ . '/../models/Ordonnance.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/RendezVous.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/AuthController.php';

class OrdonnanceController {
    private Ordonnance $ordModel;
    private User $userModel;
    private RendezVous $rdvModel;
    private AuthController $auth;
    private PDO $db;

    public function __construct() {
        $this->ordModel  = new Ordonnance();
        $this->userModel = new User();
        $this->rdvModel  = new RendezVous();
        $this->auth      = new AuthController();
        $this->db        = Database::getInstance()->getConnection();
    }

    // ═══════════════════════════════════════════════════════════
    //  HELPER PRIVÉ : récupérer les utilisateurs par rôle
    //  User::getByRole() n'existe pas — on interroge directement
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

    // ─────────────────────────────────────────
    //  BACKOFFICE - Admin
    // ─────────────────────────────────────────

    public function adminIndex(): void {
        $this->auth->requireRole('admin');

        $ordonnances = $this->ordModel->getAll();
        $stats = [
            'total'    => $this->ordModel->countAll(),
            'actives'  => 0,
            'expirees' => 0,
        ];

        $flash = $_SESSION['flash'] ?? null;
        unset($_SESSION['flash']);

        require_once __DIR__ . '/../views/backoffice/ordonnance/list.php';
    }

    public function adminCreate(): void {
        $this->auth->requireRole('admin');

        // ✅ Remplace User::getByRole()
        $patients = $this->getUsersByRole('patient');
        $medecins = $this->getUsersByRole('medecin');

        $flash = $_SESSION['flash'] ?? null;
        unset($_SESSION['flash']);

        require_once __DIR__ . '/../views/backoffice/ordonnance/form.php';
    }

    public function adminStore(): void {
        $this->auth->requireRole('admin');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: index.php?page=admin_ordonnance');
            exit;
        }

        $data = [
            'patient_id' => (int)($_POST['patient_id'] ?? 0),
            'medecin_id' => (int)($_POST['medecin_id'] ?? 0),
            'diagnostic' => trim($_POST['diagnostic'] ?? ''),
            'contenu'    => trim($_POST['contenu']    ?? ''),
        ];

        $errors = $this->validate($data);

        if (!empty($errors)) {
            $_SESSION['flash'] = ['type' => 'error', 'message' => implode('<br>', $errors)];
            header('Location: index.php?page=admin_ordonnance&action=create');
            exit;
        }

        $this->ordModel->create($data);
        $_SESSION['flash'] = ['type' => 'success', 'message' => 'Ordonnance créée avec succès.'];
        header('Location: index.php?page=admin_ordonnance');
        exit;
    }

    public function adminShow(int $id): void {
        $this->auth->requireRole('admin');

        $ord = $this->ordModel->getById($id);
        if (!$ord) $this->notFound();

        require_once __DIR__ . '/../views/backoffice/ordonnance/show.php';
    }

    public function adminDelete(int $id): void {
        $this->auth->requireRole('admin');

        $this->ordModel->delete($id);
        $_SESSION['flash'] = ['type' => 'success', 'message' => 'Ordonnance supprimée.'];
        header('Location: index.php?page=admin_ordonnance');
        exit;
    }

    // ─────────────────────────────────────────
    //  FRONTOFFICE - Patient
    // ─────────────────────────────────────────

    public function patientMesOrdonnances(): void {
        $this->auth->requireRole('patient');

        $userId      = (int)$_SESSION['user_id'];
        $ordonnances = $this->ordModel->getByPatient($userId);

        $flash = $_SESSION['flash'] ?? null;
        unset($_SESSION['flash']);

        require_once __DIR__ . '/../views/frontoffice/patient/mes_ordonnances.php';
    }

    // ─────────────────────────────────────────
    //  FRONTOFFICE - Médecin
    // ─────────────────────────────────────────

    public function medecinMesOrdonnances(): void {
        $this->auth->requireRole('medecin');

        $userId      = (int)$_SESSION['user_id'];
        $ordonnances = $this->ordModel->getByMedecin($userId);

        // ✅ Remplace User::getByRole()
        $patients = $this->getUsersByRole('patient');

        $flash = $_SESSION['flash'] ?? null;
        unset($_SESSION['flash']);

        require_once __DIR__ . '/../views/frontoffice/medecin/mes_ordonnances.php';
    }

    public function medecinCreate(): void {
        $this->auth->requireRole('medecin');

        $rdvId = (int)($_GET['rdv_id'] ?? 0);
        $rdv   = null;

        if ($rdvId) {
            $rdv = $this->rdvModel->getById($rdvId);
        }

        // ✅ Remplace User::getByRole()
        $patients = $this->getUsersByRole('patient');

        $flash = $_SESSION['flash'] ?? null;
        unset($_SESSION['flash']);

        require_once __DIR__ . '/../views/frontoffice/medecin/ordonnance_form.php';
    }

    public function medecinStore(): void {
        $this->auth->requireRole('medecin');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: index.php?page=medecin_ordonnances');
            exit;
        }

        $data = [
            'patient_id' => (int)($_POST['patient_id'] ?? 0),
            'medecin_id' => (int)$_SESSION['user_id'],
            'diagnostic' => trim($_POST['diagnostic'] ?? ''),
            'contenu'    => trim($_POST['contenu']    ?? ''),
        ];

        $errors = $this->validate($data);

        if (!empty($errors)) {
            $_SESSION['flash'] = ['type' => 'error', 'message' => implode('<br>', $errors)];
            header('Location: index.php?page=medecin_ordonnances&action=create');
            exit;
        }

        $this->ordModel->create($data);
        $_SESSION['flash'] = ['type' => 'success', 'message' => 'Ordonnance créée avec succès.'];
        header('Location: index.php?page=medecin_ordonnances');
        exit;
    }

    // ─────────────────────────────────────────
    //  API
    // ─────────────────────────────────────────

    public function apiGetOrdonnance(): void {
        header('Content-Type: application/json');

        $id  = (int)($_GET['id'] ?? 0);

        if (!$id) {
            echo json_encode(['error' => 'ID non spécifié']);
            exit;
        }

        $ord = $this->ordModel->getById($id);

        if (!$ord) {
            echo json_encode(['error' => 'Ordonnance introuvable']);
            exit;
        }

        echo json_encode(['success' => true, 'ordonnance' => $ord]);
        exit;
    }

    public function apiDownloadPDF(): void {
        $this->auth->requireAuth();

        $id  = (int)($_GET['id'] ?? 0);
        $ord = $this->ordModel->getById($id);

        if (!$ord) {
            die('Ordonnance introuvable.');
        }

        $userId   = (int)$_SESSION['user_id'];
        $userRole = $_SESSION['user_role'];

        if ($userRole !== 'admin' && $ord['patient_id'] != $userId && $ord['medecin_id'] != $userId) {
            die('Accès non autorisé.');
        }

        header('Content-Type: text/html');
        header('Content-Disposition: inline; filename="ordonnance_' . $ord['numero_ordonnance'] . '.html"');

        echo $this->generatePDFContent($ord);
        exit;
    }

    // ─────────────────────────────────────────
    //  HELPERS PRIVÉS
    // ─────────────────────────────────────────

    private function validate(array $data): array {
        $errors = [];

        if (empty($data['patient_id'])) $errors[] = 'Le patient est obligatoire.';
        if (empty($data['medecin_id'])) $errors[] = 'Le médecin est obligatoire.';
        if (empty($data['diagnostic'])) $errors[] = 'Le diagnostic est obligatoire.';
        if (empty($data['contenu']))    $errors[] = 'La prescription est obligatoire.';

        return $errors;
    }

    private function generatePDFContent(array $ord): string {
        $patient = $this->userModel->findById($ord['patient_id']);
        $medecin = $this->userModel->findById($ord['medecin_id']);

        return '<!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <title>Ordonnance ' . htmlspecialchars($ord['numero_ordonnance']) . '</title>
            <style>
                body { font-family: Arial, sans-serif; margin: 40px; }
                .header { text-align: center; margin-bottom: 30px; border-bottom: 2px solid #2A7FAA; padding-bottom: 10px; }
                .title { color: #2A7FAA; font-size: 24px; }
                .content { margin: 20px 0; }
                .info { margin: 10px 0; }
                .medicament { margin: 15px 0; padding: 10px; background: #f5f5f5; border-radius: 5px; }
                .footer { margin-top: 50px; text-align: center; font-size: 12px; color: #999; border-top: 1px solid #ccc; padding-top: 10px; }
                .signature { margin-top: 30px; text-align: right; font-style: italic; }
            </style>
        </head>
        <body>
            <div class="header">
                <div class="title">Valorys - Ordonnance Médicale</div>
                <div>N° ' . htmlspecialchars($ord['numero_ordonnance']) . '</div>
            </div>
            <div class="content">
                <div class="info"><strong>Patient :</strong> ' . htmlspecialchars(($patient['prenom'] ?? '') . ' ' . ($patient['nom'] ?? '')) . '</div>
                <div class="info"><strong>Date :</strong> ' . date('d/m/Y', strtotime($ord['date_ordonnance'])) . '</div>
                <div class="info"><strong>Médecin :</strong> Dr. ' . htmlspecialchars(($medecin['prenom'] ?? '') . ' ' . ($medecin['nom'] ?? '')) . '</div>
            </div>
            <hr>
            <div class="medicament">
                <strong>Diagnostic :</strong><br>
                ' . nl2br(htmlspecialchars($ord['diagnostic'])) . '
            </div>
            <div class="medicament">
                <strong>Prescription :</strong><br>
                ' . nl2br(htmlspecialchars($ord['contenu'])) . '
            </div>
            <div class="signature">
                Dr. ' . htmlspecialchars(($medecin['prenom'] ?? '') . ' ' . ($medecin['nom'] ?? '')) . '<br>
                ' . date('d/m/Y') . '
            </div>
            <div class="footer">
                Valorys - Plateforme médicale en ligne<br>
                Cette ordonnance est valable 3 mois
            </div>
        </body>
        </html>';
    }

    private function notFound(): void {
        http_response_code(404);
        die('Ordonnance introuvable.');
    }
}
?>