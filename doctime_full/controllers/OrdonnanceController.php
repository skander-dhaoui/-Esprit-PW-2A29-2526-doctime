<?php

require_once __DIR__ . '/../models/Ordonnance.php';
require_once __DIR__ . '/../models/RendezVous.php';
require_once __DIR__ . '/../models/Medecin.php';
require_once __DIR__ . '/../models/Patient.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/AuthController.php';
class OrdonnanceController {

    private Ordonnance $ordonnanceModel;
    private RendezVous $rdvModel;
    private Medecin $medecinModel;
    private Patient $patientModel;
    private AuthController $auth;
    private Database $db;

    public function __construct() {
        $this->ordonnanceModel = new Ordonnance();
        $this->rdvModel = new RendezVous();
        $this->medecinModel = new Medecin();
        $this->patientModel = new Patient();
        $this->auth = new AuthController();
        $this->db = Database::getInstance();
    }

    public function __destruct() {
        unset(
            $this->ordonnanceModel,
            $this->rdvModel,
            $this->medecinModel,
            $this->patientModel,
            $this->auth,
            $this->db
        );
    }

    // ─────────────────────────────────────────
    //  Liste des ordonnances (patient)
    // ─────────────────────────────────────────
    public function indexPatient(): void {
        $this->auth->requireRole('patient');

        try {
            $patientId = (int)$_SESSION['user_id'];
            $filter = $_GET['filter'] ?? 'all'; // all, active, expired, archived

            $ordonnances = match ($filter) {
                'active' => $this->ordonnanceModel->getActiveByPatient($patientId),
                'expired' => $this->ordonnanceModel->getExpiredByPatient($patientId),
                'archived' => $this->ordonnanceModel->getArchivedByPatient($patientId),
                default => $this->ordonnanceModel->getAllByPatient($patientId),
            };

            $flash = $_SESSION['flash'] ?? null;
            unset($_SESSION['flash']);

            require_once __DIR__ . '/../views/frontoffice/patient/mes_ordonnances.php';
        } catch (Exception $e) {
            error_log('Erreur OrdonnanceController::indexPatient - ' . $e->getMessage());
            $this->setFlash('error', 'Erreur lors du chargement des ordonnances.');
            header('Location: /patient/dashboard');
            exit;
        }
    }

    // ─────────────────────────────────────────
    //  Liste des ordonnances (médecin)
    // ─────────────────────────────────────────
    public function indexMedecin(): void {
        $this->auth->requireRole('medecin');

        try {
            $medecinId = (int)$_SESSION['user_id'];
            $filter = $_GET['filter'] ?? 'all'; // all, today, recent, by_patient
            $patientId = $_GET['patient'] ?? null;

            if ($patientId) {
                $ordonnances = $this->ordonnanceModel->getByMedecinAndPatient($medecinId, (int)$patientId);
            } else {
                $ordonnances = match ($filter) {
                    'today' => $this->ordonnanceModel->getTodayByMedecin($medecinId),
                    'recent' => $this->ordonnanceModel->getRecentByMedecin($medecinId, 30),
                    default => $this->ordonnanceModel->getAllByMedecin($medecinId),
                };
            }

            $flash = $_SESSION['flash'] ?? null;
            unset($_SESSION['flash']);

            require_once __DIR__ . '/../views/frontoffice/medecin/mes_ordonnances.php';
        } catch (Exception $e) {
            error_log('Erreur OrdonnanceController::indexMedecin - ' . $e->getMessage());
            $this->setFlash('error', 'Erreur lors du chargement.');
            header('Location: /medecin/dashboard');
            exit;
        }
    }
// ─────────────────────────────────────────
//  Méthodes pour ADMIN
// ─────────────────────────────────────────

public function createAdmin(): void {
    $this->auth->requireRole('admin');
    
    try {
        $csrfToken = $this->generateCsrfToken();
        $patients = $this->patientModel->getAll();
        $medecins = $this->medecinModel->getAllWithUsers();
        $errors = $_SESSION['errors'] ?? [];
        $old = $_SESSION['old'] ?? [];
        $flash = $_SESSION['flash'] ?? null;
        unset($_SESSION['errors'], $_SESSION['old'], $_SESSION['flash']);
        
        require_once __DIR__ . '/../views/backoffice/ordonnance/form.php';
    } catch (Exception $e) {
        error_log('Erreur createAdmin - ' . $e->getMessage());
        $this->setFlash('error', 'Erreur lors du chargement.');
        header('Location: index.php?page=ordonnances');
        exit;
    }
}


/**
 * Créer une ordonnance depuis un rendez-vous
 */
/**
 * Créer une ordonnance depuis un rendez-vous
 */
/**
 * Créer une ordonnance depuis un rendez-vous
 */
public function storeFromRendezVous(): void {
    $this->auth->requireRole('medecin');
    
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        header('Location: index.php?page=mes_rendez_vous');
        exit;
    }
    
    $rdv_id = (int)$_POST['rdv_id'];
    $patient_id = (int)$_POST['patient_id'];
    $medecin_id = (int)$_POST['medecin_id'];
    $diagnostic = trim($_POST['diagnostic']);
    $contenu = trim($_POST['contenu']);
    $date_expiration = $_POST['date_validite'] ?? date('Y-m-d', strtotime('+1 year'));
    
    error_log("=== storeFromRendezVous ===");
    error_log("rdv_id: " . $rdv_id);
    error_log("patient_id: " . $patient_id);
    error_log("medecin_id: " . $medecin_id);
    
    if (empty($diagnostic) || empty($contenu)) {
        $_SESSION['error'] = 'Le diagnostic et la prescription sont obligatoires.';
        header("Location: index.php?page=detail_rendez_vous&id=$rdv_id");
        exit;
    }
    
    // Générer un numéro d'ordonnance unique
    $numero = 'ORD-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -6));
    
    $data = [
        'numero_ordonnance' => $numero,
        'patient_id' => $patient_id,
        'medecin_id' => $medecin_id,
        'rdv_id' => $rdv_id,  // ASSUREZ-VOUS QUE CECI EST PRÉSENT
        'date_ordonnance' => date('Y-m-d'),
        'date_expiration' => $date_expiration,
        'diagnostic' => $diagnostic,
        'contenu' => $contenu,
        'status' => 'active'
    ];
    
    error_log("Données à insérer: " . print_r($data, true));
    
    $ordonnanceId = $this->ordonnanceModel->create($data);
    
    error_log("Résultat insertion - ID: " . ($ordonnanceId ? $ordonnanceId : 'NULL'));
    
    if ($ordonnanceId) {
        // Vérifier que l'ordonnance a bien été créée avec le rdv_id
        $check = $this->ordonnanceModel->getById($ordonnanceId);
        error_log("Ordonnance créée - rdv_id: " . ($check['rdv_id'] ?? 'NULL'));
        
        $_SESSION['success'] = 'Ordonnance créée avec succès.';
    } else {
        $_SESSION['error'] = 'Erreur lors de la création de l\'ordonnance.';
    }
    
    header("Location: index.php?page=detail_rendez_vous&id=$rdv_id");
    exit;
}

/**
 * Modifier une ordonnance depuis un rendez-vous
 */
public function updateFromRendezVous(): void {
    $this->auth->requireRole('medecin');
    
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        header('Location: index.php?page=mes_rendez_vous');
        exit;
    }
    
    $ordonnance_id = (int)$_POST['ordonnance_id'];
    $rdv_id = (int)$_POST['rdv_id'];
    $diagnostic = trim($_POST['diagnostic']);
    $contenu = trim($_POST['contenu']);
    $date_validite = $_POST['date_validite'] ?? date('Y-m-d', strtotime('+1 year'));
    
    if (empty($diagnostic) || empty($contenu)) {
        $_SESSION['error'] = 'Le diagnostic et la prescription sont obligatoires.';
        header("Location: index.php?page=detail_rendez_vous&id=$rdv_id");
        exit;
    }
    
    $result = $this->ordonnanceModel->update($ordonnance_id, [
        'diagnostic' => $diagnostic,
        'contenu' => $contenu,
        'date_validite' => $date_validite
    ]);
    
    if ($result) {
        $_SESSION['success'] = 'Ordonnance modifiée avec succès.';
    } else {
        $_SESSION['error'] = 'Erreur lors de la modification.';
    }
    
    header("Location: index.php?page=detail_rendez_vous&id=$rdv_id");
    exit;
}

/**
 * Supprimer une ordonnance depuis un rendez-vous
 */
public function deleteFromRendezVous(int $ordonnanceId, int $rdvId): void {
    $this->auth->requireRole('medecin');
    
    $result = $this->ordonnanceModel->delete($ordonnanceId);
    
    if ($result) {
        $_SESSION['success'] = 'Ordonnance supprimée avec succès.';
    } else {
        $_SESSION['error'] = 'Erreur lors de la suppression.';
    }
    
    header("Location: index.php?page=detail_rendez_vous&id=$rdvId");
    exit;
}

/**
 * API - Récupérer une ordonnance
 */
public function apiGet(int $id): void {
    header('Content-Type: application/json');
    
    $ordonnance = $this->ordonnanceModel->getById($id);
    
    if ($ordonnance) {
        echo json_encode(['success' => true, 'ordonnance' => $ordonnance]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Ordonnance non trouvée']);
    }
    exit;
}




public function storeAdmin(): void {
    $this->auth->requireRole('admin');
    
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        header('Location: index.php?page=ordonnances&action=create');
        exit;
    }
    
    $errors = [];
    $old = $_POST;
    
    // Validation
    if (empty($_POST['patient_id'])) {
        $errors['patient_id'] = 'Veuillez sélectionner un patient.';
    }
    
    if (empty($_POST['medecin_id'])) {
        $errors['medecin_id'] = 'Veuillez sélectionner un médecin.';
    }
    
    if (empty($_POST['diagnostic'])) {
        $errors['diagnostic'] = 'Le diagnostic est obligatoire.';
    } elseif (strlen(trim($_POST['diagnostic'])) < 5) {
        $errors['diagnostic'] = 'Le diagnostic doit contenir au moins 5 caractères.';
    }
    
    if (empty($_POST['contenu'])) {
        $errors['contenu'] = 'Le contenu / médicaments est obligatoire.';
    }
    
    // S'il y a des erreurs, retourner au formulaire
    if (!empty($errors)) {
        $_SESSION['errors'] = $errors;
        $_SESSION['old'] = $old;
        header('Location: index.php?page=ordonnances&action=create');
        exit;
    }
    
    try {
        $data = [
            'patient_id' => (int)$_POST['patient_id'],
            'medecin_id' => (int)$_POST['medecin_id'],
            'date_ordonnance' => $_POST['date_ordonnance'] ?? date('Y-m-d'),
            'date_expiration' => $_POST['date_expiration'] ?? null,
            'diagnostic' => trim($_POST['diagnostic']),
            'contenu' => trim($_POST['contenu']),
            'status' => $_POST['status'] ?? 'active'
        ];
        
        $ordonnanceId = $this->ordonnanceModel->create($data);
        
        if ($ordonnanceId) {
            $_SESSION['flash'] = ['type' => 'success', 'message' => 'Ordonnance créée avec succès.'];
            unset($_SESSION['errors'], $_SESSION['old']);
            header('Location: index.php?page=ordonnances');
            exit;
        } else {
            throw new Exception('Erreur lors de la création.');
        }
    } catch (Exception $e) {
        error_log('Erreur storeAdmin - ' . $e->getMessage());
        $_SESSION['flash'] = ['type' => 'error', 'message' => $e->getMessage()];
        $_SESSION['old'] = $old;
        header('Location: index.php?page=ordonnances&action=create');
        exit;
    }
}

public function editAdmin(int $id): void {
    $this->auth->requireRole('admin');
    
    try {
        $ordonnance = $this->ordonnanceModel->getById($id);
        if (!$ordonnance) {
            $this->setFlash('error', 'Ordonnance non trouvée.');
            header('Location: index.php?page=ordonnances');
            exit;
        }
        
        $csrfToken = $this->generateCsrfToken();
        $patients = $this->patientModel->getAll();
        $medecins = $this->medecinModel->getAllWithUsers();
        $errors = $_SESSION['errors'] ?? [];
        $old = $_SESSION['old'] ?? [];
        $flash = $_SESSION['flash'] ?? null;
        unset($_SESSION['errors'], $_SESSION['old'], $_SESSION['flash']);
        
        if (empty($old) && isset($ordonnance)) {
            $old = $ordonnance;
        }
        
        require_once __DIR__ . '/../views/backoffice/ordonnance/form.php';
    } catch (Exception $e) {
        error_log('Erreur editAdmin - ' . $e->getMessage());
        $this->setFlash('error', 'Erreur lors du chargement.');
        header('Location: index.php?page=ordonnances');
        exit;
    }
}

public function updateAdmin(int $id): void {
    $this->auth->requireRole('admin');
    
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        header("Location: index.php?page=ordonnances&action=edit&id=$id");
        exit;
    }
    
    $errors = [];
    $old = $_POST;
    
    $diag = trim($_POST['diagnostic'] ?? '');
    if ($diag === '') {
        $errors['diagnostic'] = 'Le diagnostic est obligatoire.';
    } elseif (strlen($diag) < 5) {
        $errors['diagnostic'] = 'Le diagnostic doit contenir au moins 5 caractères.';
    }
    
    if (empty(trim($_POST['contenu'] ?? ''))) {
        $errors['contenu'] = 'Le contenu / médicaments est obligatoire.';
    }
    
    if (!empty($errors)) {
        $_SESSION['errors'] = $errors;
        $_SESSION['old'] = $old;
        header("Location: index.php?page=ordonnances&action=edit&id=$id");
        exit;
    }
    
    try {
        $data = [
            'diagnostic' => trim($_POST['diagnostic'] ?? ''),
            'contenu' => trim($_POST['contenu'] ?? ''),
            'date_expiration' => $_POST['date_expiration'] ?? null,
            'status' => $_POST['status'] ?? 'active'
        ];
        
        $result = $this->ordonnanceModel->update($id, $data);
        
        if ($result) {
            $this->setFlash('success', 'Ordonnance mise à jour avec succès.');
        } else {
            throw new Exception('Erreur lors de la mise à jour.');
        }
        
        header('Location: index.php?page=ordonnances');
        exit;
    } catch (Exception $e) {
        error_log('Erreur updateAdmin - ' . $e->getMessage());
        $_SESSION['flash'] = ['type' => 'error', 'message' => $e->getMessage()];
        $_SESSION['old'] = $old;
        header("Location: index.php?page=ordonnances&action=edit&id=$id");
        exit;
    }
}

public function deleteAdmin(int $id): void {
    $this->auth->requireRole('admin');
    
    try {
        $result = $this->ordonnanceModel->delete($id);
        
        if ($result) {
            $this->setFlash('success', 'Ordonnance supprimée avec succès.');
        } else {
            throw new Exception('Erreur lors de la suppression.');
        }
        
        header('Location: index.php?page=ordonnances');
        exit;
    } catch (Exception $e) {
        error_log('Erreur deleteAdmin - ' . $e->getMessage());
        $this->setFlash('error', $e->getMessage());
        header('Location: index.php?page=ordonnances');
        exit;
    }
}

public function showAdmin(int $id): void {
    $this->auth->requireRole('admin');
    
    try {
        $ordonnance = $this->ordonnanceModel->getById($id);
        if (!$ordonnance) {
            $this->setFlash('error', 'Ordonnance non trouvée.');
            header('Location: index.php?page=ordonnances');
            exit;
        }
        
        $medicaments = $this->ordonnanceModel->getMedicaments($id);
        $flash = $_SESSION['flash'] ?? null;
        unset($_SESSION['flash']);
        
        require_once __DIR__ . '/../views/backoffice/ordonnance/show.php';
    } catch (Exception $e) {
        error_log('Erreur showAdmin - ' . $e->getMessage());
        $this->setFlash('error', 'Erreur lors du chargement.');
        header('Location: index.php?page=ordonnances');
        exit;
    }
}
    // ─────────────────────────────────────────
    //  Liste des ordonnances (admin)
    // ─────────────────────────────────────────
public function indexAdmin(): void {
    $this->auth->requireRole('admin');

    try {
        $filter = $_GET['filter'] ?? 'all';
        $medecinId = $_GET['medecin'] ?? null;
        $patientId = $_GET['patient'] ?? null;
        $search = $_GET['search'] ?? '';

        // Récupérer les ordonnances
        if (!empty($search)) {
            $ordonnances = $this->ordonnanceModel->search($search);
        } else {
            $ordonnances = $this->ordonnanceModel->getAll();
        }

        // Récupérer les médecins et patients pour les filtres
        $medecins = $this->medecinModel->getAllWithUsers();
        $patients = $this->patientModel->getAll();
        
        $flash = $_SESSION['flash'] ?? null;
        unset($_SESSION['flash']);

        // Utiliser le bon chemin de la vue
        $viewPath = __DIR__ . '/../views/backoffice/ordonnance/list.php';
        if (file_exists($viewPath)) {
            require_once $viewPath;
        } else {
            // Afficher une erreur claire si le fichier n'existe pas
            echo "<div style='background: #f8d7da; color: #721c24; padding: 15px; margin: 20px; border-radius: 5px; border: 1px solid #f5c6cb;'>";
            echo "<strong>Erreur :</strong> Fichier de vue non trouvé.<br>";
            echo "<strong>Chemin recherché :</strong> " . $viewPath . "<br>";
            echo "<strong>Dossier actuel :</strong> " . __DIR__ . "<br>";
            echo "<strong>Solution :</strong> Créez le fichier à l'emplacement indiqué ou vérifiez le chemin.";
            echo "</div>";
            
            // Afficher les données en fallback
            echo "<h2>Liste des ordonnances</h2>";
            echo "<table class='table table-bordered'>";
            echo "<thead><tr><th>ID</th><th>Patient</th><th>Médecin</th><th>Date</th></tr></thead>";
            echo "<tbody>";
            foreach ($ordonnances as $ordo) {
                echo "<tr>";
                echo "<td>" . ($ordo['id'] ?? '') . "</td>";
                echo "<td>" . htmlspecialchars($ordo['patient_nom'] ?? '') . "</td>";
                echo "<td>" . htmlspecialchars($ordo['medecin_nom'] ?? '') . "</td>";
                echo "<td>" . ($ordo['date_creation'] ?? '') . "</td>";
                echo "</tr>";
            }
            echo "</tbody></table>";
        }
        
    } catch (Exception $e) {
        error_log('Erreur OrdonnanceController::indexAdmin - ' . $e->getMessage());
        $this->setFlash('error', 'Erreur lors du chargement : ' . $e->getMessage());
        header('Location: index.php?page=dashboard');
        exit;
    }
}

    // ─────────────────────────────────────────
    //  Créer une ordonnance (médecin)
    // ─────────────────────────────────────────
    public function createMedecin(): void {
        $this->auth->requireRole('medecin');

        try {
            $csrfToken = $this->generateCsrfToken();
            $rdvId = $_GET['rdv'] ?? null;
            $patientId = $_GET['patient'] ?? null;
            $rdv = null;
            $patient = null;

            if ($rdvId) {
                $rdv = $this->rdvModel->getById((int)$rdvId);
                if ($rdv) {
                    $patient = $this->patientModel->findByUserId((int)$rdv['patient_id']);
                }
            } elseif ($patientId) {
                $patient = $this->patientModel->findByUserId((int)$patientId);
            }

            $old = $_SESSION['old'] ?? null;
            $flash = $_SESSION['flash'] ?? null;
            unset($_SESSION['old'], $_SESSION['flash']);

            require_once __DIR__ . '/../views/backoffice/ordonnance/form.php';
        } catch (Exception $e) {
            error_log('Erreur OrdonnanceController::createMedecin - ' . $e->getMessage());
            $this->setFlash('error', 'Erreur lors du chargement du formulaire.');
            header('Location: /medecin/ordonnances');
            exit;
        }
    }

    // ─────────────────────────────────────────
    //  Enregistrer une ordonnance (médecin)
    // ─────────────────────────────────────────
    public function storeMedecin(): void {
        $this->auth->requireRole('medecin');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /medecin/ordonnances/create');
            exit;
        }

        if (!$this->verifyCsrfToken($_POST['csrf_token'] ?? '')) {
            $this->setFlash('error', 'Erreur de sécurité. Veuillez réessayer.');
            header('Location: /medecin/ordonnances/create');
            exit;
        }

        try {
            $medecinId = (int)$_SESSION['user_id'];

            $data = [
                'medecin_id' => $medecinId,
                'patient_id' => (int)($_POST['patient_id'] ?? 0),
                'rdv_id' => !empty($_POST['rdv_id']) ? (int)$_POST['rdv_id'] : null,
                'date_prescription' => date('Y-m-d'),
                'date_debut' => $_POST['date_debut'] ?? date('Y-m-d'),
                'date_fin' => $_POST['date_fin'] ?? '',
                'diagnostic' => htmlspecialchars(trim($_POST['diagnostic'] ?? ''), ENT_QUOTES, 'UTF-8'),
                'notes_medicales' => htmlspecialchars(trim($_POST['notes_medicales'] ?? ''), ENT_QUOTES, 'UTF-8'),
                'medicaments' => $_POST['medicaments'] ?? [],
            ];

            $errors = $this->validateOrdonnance($data);

            if (!empty($errors)) {
                $this->setFlash('error', implode('<br>', $errors));
                $_SESSION['old'] = $data;
                header('Location: /medecin/ordonnances/create');
                exit;
            }

            // Vérifier que le patient existe
            $patient = $this->patientModel->findByUserId($data['patient_id']);
            if (!$patient) {
                throw new Exception('Patient introuvable.');
            }

            $ordonnanceId = $this->ordonnanceModel->create($data);

            if (!$ordonnanceId) {
                throw new Exception('Erreur lors de la création.');
            }

            // Créer les médicaments
            foreach ($data['medicaments'] as $med) {
                if (!empty($med['nom'])) {
                    $this->ordonnanceModel->addMedicament($ordonnanceId, [
                        'nom' => htmlspecialchars(trim($med['nom']), ENT_QUOTES, 'UTF-8'),
                        'dosage' => htmlspecialchars(trim($med['dosage'] ?? ''), ENT_QUOTES, 'UTF-8'),
                        'frequence' => htmlspecialchars(trim($med['frequence'] ?? ''), ENT_QUOTES, 'UTF-8'),
                        'duree_jours' => (int)($med['duree_jours'] ?? 0),
                        'indication' => htmlspecialchars(trim($med['indication'] ?? ''), ENT_QUOTES, 'UTF-8'),
                    ]);
                }
            }

            $this->logAction($_SESSION['user_id'], 'Création ordonnance', "Ordonnance #$ordonnanceId créée pour patient #" . $data['patient_id']);

            $this->setFlash('success', 'Ordonnance créée avec succès.');
            header('Location: /medecin/ordonnances');
            exit;
        } catch (Exception $e) {
            error_log('Erreur OrdonnanceController::storeMedecin - ' . $e->getMessage());
            $this->setFlash('error', 'Erreur lors de la création : ' . $e->getMessage());
            $_SESSION['old'] = $data ?? [];
            header('Location: /medecin/ordonnances/create');
            exit;
        }
    }

    // ─────────────────────────────────────────
    //  Afficher une ordonnance (patient)
    // ─────────────────────────────────────────
    public function showPatient(int $id): void {
        $this->auth->requireRole('patient');

        try {
            $patientId = (int)$_SESSION['user_id'];
            $ordonnance = $this->ordonnanceModel->getById($id);

            if (!$ordonnance || (int)$ordonnance['patient_id'] !== $patientId) {
                http_response_code(403);
                die('Accès refusé.');
            }

            $medicaments = $this->ordonnanceModel->getMedicaments($id);
            $medecin = $this->medecinModel->findByUserId((int)$ordonnance['medecin_id']);
            $flash = $_SESSION['flash'] ?? null;
            unset($_SESSION['flash']);

            require_once __DIR__ . '/../views/backoffice/ordonnance/show.php';
        } catch (Exception $e) {
            error_log('Erreur OrdonnanceController::showPatient - ' . $e->getMessage());
            http_response_code(500);
            die('Erreur lors du chargement.');
        }
    }

    // ─────────────────────────────────────────
    //  Afficher une ordonnance (médecin)
    // ─────────────────────────────────────────
    public function showMedecin(int $id): void {
        $this->auth->requireRole('medecin');

        try {
            $medecinId = (int)$_SESSION['user_id'];
            $ordonnance = $this->ordonnanceModel->getById($id);

            if (!$ordonnance || (int)$ordonnance['medecin_id'] !== $medecinId) {
                http_response_code(403);
                die('Accès refusé.');
            }

            $medicaments = $this->ordonnanceModel->getMedicaments($id);
            $patient = $this->patientModel->findByUserId((int)$ordonnance['patient_id']);
            $flash = $_SESSION['flash'] ?? null;
            unset($_SESSION['flash']);

            require_once __DIR__ . '/../views/backoffice/ordonnance/show.php';
        } catch (Exception $e) {
            error_log('Erreur OrdonnanceController::showMedecin - ' . $e->getMessage());
            http_response_code(500);
            die('Erreur lors du chargement.');
        }
    }

    // ─────────────────────────────────────────
    //  Éditer une ordonnance (médecin)
    // ─────────────────────────────────────────
    public function editMedecin(int $id): void {
        $this->auth->requireRole('medecin');

        try {
            $medecinId = (int)$_SESSION['user_id'];
            $ordonnance = $this->ordonnanceModel->getById($id);

            if (!$ordonnance || (int)$ordonnance['medecin_id'] !== $medecinId) {
                http_response_code(403);
                die('Accès refusé.');
            }

            // Vérifier que l'ordonnance n'est pas validée
            if ($ordonnance['statut'] === 'validée') {
                $this->setFlash('error', 'Impossible de modifier une ordonnance validée.');
                header("Location: /medecin/ordonnances/$id");
                exit;
            }

            $csrfToken = $this->generateCsrfToken();
            $medicaments = $this->ordonnanceModel->getMedicaments($id);
            $patient = $this->patientModel->findByUserId((int)$ordonnance['patient_id']);
            $old = $_SESSION['old'] ?? null;
            $flash = $_SESSION['flash'] ?? null;
            unset($_SESSION['old'], $_SESSION['flash']);

            require_once __DIR__ . '/../views/backoffice/ordonnance/form.php';
        } catch (Exception $e) {
            error_log('Erreur OrdonnanceController::editMedecin - ' . $e->getMessage());
            $this->setFlash('error', 'Erreur lors du chargement.');
            header('Location: /medecin/ordonnances');
            exit;
        }
    }

    // ─────────────────────────────────────────
    //  Mettre à jour une ordonnance (médecin)
    // ─────────────────────────────────────────
    public function updateMedecin(int $id): void {
        $this->auth->requireRole('medecin');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: /medecin/ordonnances/$id/edit");
            exit;
        }

        if (!$this->verifyCsrfToken($_POST['csrf_token'] ?? '')) {
            $this->setFlash('error', 'Erreur de sécurité.');
            header("Location: /medecin/ordonnances/$id/edit");
            exit;
        }

        try {
            $medecinId = (int)$_SESSION['user_id'];
            $ordonnance = $this->ordonnanceModel->getById($id);

            if (!$ordonnance || (int)$ordonnance['medecin_id'] !== $medecinId) {
                http_response_code(403);
                die('Accès refusé.');
            }

            if ($ordonnance['statut'] === 'validée') {
                throw new Exception('Impossible de modifier une ordonnance validée.');
            }

            $data = [
                'date_fin' => $_POST['date_fin'] ?? '',
                'diagnostic' => htmlspecialchars(trim($_POST['diagnostic'] ?? ''), ENT_QUOTES, 'UTF-8'),
                'notes_medicales' => htmlspecialchars(trim($_POST['notes_medicales'] ?? ''), ENT_QUOTES, 'UTF-8'),
                'medicaments' => $_POST['medicaments'] ?? [],
            ];

            $errors = $this->validateOrdonnanceUpdate($data);

            if (!empty($errors)) {
                $this->setFlash('error', implode('<br>', $errors));
                $_SESSION['old'] = $data;
                header("Location: /medecin/ordonnances/$id/edit");
                exit;
            }

            $this->ordonnanceModel->update($id, [
                'date_fin' => $data['date_fin'],
                'diagnostic' => $data['diagnostic'],
                'notes_medicales' => $data['notes_medicales'],
            ]);

            // Supprimer les anciens médicaments
            $this->ordonnanceModel->deleteMedicaments($id);

            // Ajouter les nouveaux
            foreach ($data['medicaments'] as $med) {
                if (!empty($med['nom'])) {
                    $this->ordonnanceModel->addMedicament($id, [
                        'nom' => htmlspecialchars(trim($med['nom']), ENT_QUOTES, 'UTF-8'),
                        'dosage' => htmlspecialchars(trim($med['dosage'] ?? ''), ENT_QUOTES, 'UTF-8'),
                        'frequence' => htmlspecialchars(trim($med['frequence'] ?? ''), ENT_QUOTES, 'UTF-8'),
                        'duree_jours' => (int)($med['duree_jours'] ?? 0),
                        'indication' => htmlspecialchars(trim($med['indication'] ?? ''), ENT_QUOTES, 'UTF-8'),
                    ]);
                }
            }

            $this->logAction($_SESSION['user_id'], 'Modification ordonnance', "Ordonnance #$id modifiée");

            $this->setFlash('success', 'Ordonnance mise à jour.');
            header('Location: /medecin/ordonnances');
            exit;
        } catch (Exception $e) {
            error_log('Erreur OrdonnanceController::updateMedecin - ' . $e->getMessage());
            $this->setFlash('error', 'Erreur : ' . $e->getMessage());
            header("Location: /medecin/ordonnances/$id/edit");
            exit;
        }
    }

    // ─────────────────────────────────────────
    //  Valider une ordonnance (médecin)
    // ─────────────────────────────────────────
    public function validateMedecin(int $id): void {
        $this->auth->requireRole('medecin');

        try {
            $medecinId = (int)$_SESSION['user_id'];
            $ordonnance = $this->ordonnanceModel->getById($id);

            if (!$ordonnance || (int)$ordonnance['medecin_id'] !== $medecinId) {
                http_response_code(403);
                die('Accès refusé.');
            }

            // Vérifier qu'il y a au moins un médicament
            $medicaments = $this->ordonnanceModel->getMedicaments($id);
            if (empty($medicaments)) {
                $this->setFlash('error', 'Impossible de valider : au moins un médicament est requis.');
                header("Location: /medecin/ordonnances/$id");
                exit;
            }

            $this->ordonnanceModel->update($id, [
                'statut' => 'validée',
                'date_validation' => date('Y-m-d H:i:s'),
            ]);

            $this->logAction($_SESSION['user_id'], 'Validation ordonnance', "Ordonnance #$id validée");

            $this->setFlash('success', 'Ordonnance validée.');
            header("Location: /medecin/ordonnances/$id");
            exit;
        } catch (Exception $e) {
            error_log('Erreur validateMedecin - ' . $e->getMessage());
            $this->setFlash('error', 'Erreur lors de la validation.');
            header("Location: /medecin/ordonnances/$id");
            exit;
        }
    }

    // ─────────────────────────────────────────
    //  Télécharger ordonnance PDF (patient)
    // ─────────────────────────────────────────
    public function downloadPatient(int $id): void {
        $this->auth->requireRole('patient');

        try {
            $patientId = (int)$_SESSION['user_id'];
            $ordonnance = $this->ordonnanceModel->getById($id);

            if (!$ordonnance || (int)$ordonnance['patient_id'] !== $patientId) {
                http_response_code(403);
                die('Accès refusé.');
            }

            $medicaments = $this->ordonnanceModel->getMedicaments($id);
            $medecin = $this->medecinModel->findByUserId((int)$ordonnance['medecin_id']);
            $patient = $this->patientModel->findByUserId($patientId);

            $this->generatePDF($ordonnance, $medicaments, $medecin, $patient);
        } catch (Exception $e) {
            error_log('Erreur downloadPatient - ' . $e->getMessage());
            http_response_code(500);
            die('Erreur lors du téléchargement.');
        }
    }

    public function downloadMedecin(int $id): void {
        $this->auth->requireRole('medecin');

        try {
            $medecinUserId = (int)$_SESSION['user_id'];
            $ordonnance = $this->ordonnanceModel->getById($id);

            if (!$ordonnance || (int)$ordonnance['medecin_id'] !== $medecinUserId) {
                http_response_code(403);
                die('Accès refusé.');
            }

            $medicaments = $this->ordonnanceModel->getMedicaments($id);
            $medecin = $this->medecinModel->findByUserId($medecinUserId);
            $patient = $this->patientModel->findByUserId((int)$ordonnance['patient_id']);

            $this->generatePDF($ordonnance, $medicaments, $medecin, $patient);
        } catch (Exception $e) {
            error_log('Erreur downloadMedecin - ' . $e->getMessage());
            http_response_code(500);
            die('Erreur lors du téléchargement.');
        }
    }

    // ─────────────────────────────────────────
    //  Archiver une ordonnance (patient)
    // ─────────────────────────────────────────
    public function archivePatient(int $id): void {
        $this->auth->requireRole('patient');

        try {
            $patientId = (int)$_SESSION['user_id'];
            $ordonnance = $this->ordonnanceModel->getById($id);

            if (!$ordonnance || (int)$ordonnance['patient_id'] !== $patientId) {
                http_response_code(403);
                die('Accès refusé.');
            }

            $this->ordonnanceModel->update($id, ['archivé' => 1]);

            $this->setFlash('success', 'Ordonnance archivée.');
            header('Location: /patient/ordonnances');
            exit;
        } catch (Exception $e) {
            error_log('Erreur archivePatient - ' . $e->getMessage());
            $this->setFlash('error', 'Erreur lors de l\'archivage.');
            header('Location: /patient/ordonnances');
            exit;
        }
    }

    // ─────────────────────────────────────────
    //  Supprimer une ordonnance (admin)
    // ─────────────────────────────────────────


    // ─────────────────────────────────────────
    //  API - Historique patient
    // ─────────────────────────────────────────
    public function apiHistoriquePatient(): void {
        header('Content-Type: application/json');
        $this->auth->requireRole('medecin');

        try {
            $patientId = (int)($_GET['patient_id'] ?? 0);

            if (!$patientId) {
                echo json_encode(['error' => 'Patient invalide']);
                exit;
            }

            $ordonnances = $this->ordonnanceModel->getAllByPatient($patientId);

            echo json_encode([
                'success' => true,
                'ordonnances' => $ordonnances,
            ]);
            exit;
        } catch (Exception $e) {
            error_log('Erreur apiHistoriquePatient - ' . $e->getMessage());
            echo json_encode(['error' => 'Erreur serveur']);
            exit;
        }
    }

    // ─────────────────────────────────────────
    //  Helpers privés
    // ─────────────────────────────────────────
    private function validateOrdonnance(array $data): array {
        $errors = [];

        if (empty($data['patient_id']) || $data['patient_id'] <= 0) {
            $errors[] = 'Patient invalide.';
        }

        if (empty($data['diagnostic']) || strlen($data['diagnostic']) < 5) {
            $errors[] = 'Le diagnostic doit contenir au moins 5 caractères.';
        }

        if (empty($data['date_debut'])) {
            $errors[] = 'Date de début obligatoire.';
        } else {
            $date = DateTime::createFromFormat('Y-m-d', $data['date_debut']);
            if (!$date || $date->format('Y-m-d') !== $data['date_debut']) {
                $errors[] = 'Format de date invalide.';
            }
        }

        if (!empty($data['date_fin'])) {
            $date = DateTime::createFromFormat('Y-m-d', $data['date_fin']);
            if (!$date || $date->format('Y-m-d') !== $data['date_fin']) {
                $errors[] = 'Format de date fin invalide.';
            }
        }

        if (empty($data['medicaments']) || count($data['medicaments']) === 0) {
            $errors[] = 'Au moins un médicament est requis.';
        }

        return $errors;
    }

    private function validateOrdonnanceUpdate(array $data): array {
        $errors = [];

        if (empty($data['diagnostic']) || strlen($data['diagnostic']) < 5) {
            $errors[] = 'Le diagnostic doit contenir au moins 5 caractères.';
        }

        if (!empty($data['date_fin'])) {
            $date = DateTime::createFromFormat('Y-m-d', $data['date_fin']);
            if (!$date || $date->format('Y-m-d') !== $data['date_fin']) {
                $errors[] = 'Format de date fin invalide.';
            }
        }

        if (empty($data['medicaments']) || count(array_filter($data['medicaments'], fn($m) => !empty($m['nom']))) === 0) {
            $errors[] = 'Au moins un médicament est requis.';
        }

        return $errors;
    }

    private function generatePDF($ordonnance, $medicaments, $medecin, $patient): void {
        $date = $ordonnance['date_prescription'] ?? date('Y-m-d');
        $ordId = $ordonnance['id'] ?? '';
        $diagnostic = htmlspecialchars($ordonnance['diagnostic'] ?? '');
        $notes = htmlspecialchars($ordonnance['notes_medicales'] ?? '');
        $nomMedecin = htmlspecialchars('Dr. ' . ($medecin['nom'] ?? '') . ' ' . ($medecin['prenom'] ?? ''));
        $specialite = htmlspecialchars($medecin['specialite'] ?? '');
        $nomPatient = htmlspecialchars(($patient['nom'] ?? '') . ' ' . ($patient['prenom'] ?? ''));
        $telPatient = htmlspecialchars($patient['telephone'] ?? '');

        // Build medicaments rows
        $rows = '';
        foreach ($medicaments as $med) {
            $nom      = htmlspecialchars($med['nom'] ?? '');
            $dosage   = htmlspecialchars($med['dosage'] ?? '');
            $freq     = htmlspecialchars($med['frequence'] ?? '');
            $duree    = htmlspecialchars($med['duree_jours'] ?? '');
            $indic    = htmlspecialchars($med['indication'] ?? '');
            $rows .= "<tr><td>$nom</td><td>$dosage</td><td>$freq</td><td>{$duree} jours</td><td>$indic</td></tr>";
        }

        // Pure PHP PDF using FPDF embedded as base64 — fallback: print-ready HTML with print JS
        $html = <<<HTML
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Ordonnance #{$ordId}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; font-size: 13px; color: #222; padding: 30px; }
        .page { max-width: 750px; margin: auto; border: 2px solid #1a5276; padding: 30px; }
        .header { display: flex; justify-content: space-between; align-items: center; border-bottom: 3px solid #1a5276; padding-bottom: 15px; margin-bottom: 20px; }
        .header h1 { font-size: 22px; color: #1a5276; }
        .header .date { font-size: 13px; color: #555; }
        .section { margin-bottom: 18px; }
        .section h3 { background: #1a5276; color: white; padding: 6px 12px; font-size: 13px; margin-bottom: 8px; }
        .section p { padding: 0 12px; line-height: 1.7; }
        table { width: 100%; border-collapse: collapse; margin-top: 5px; }
        th { background: #1a5276; color: white; padding: 8px; font-size: 12px; text-align: left; }
        td { border: 1px solid #ccc; padding: 7px 8px; font-size: 12px; }
        tr:nth-child(even) td { background: #f4f8fb; }
        .footer { margin-top: 40px; display: flex; justify-content: space-between; font-size: 12px; color: #555; }
        .signature { border-top: 1px solid #333; width: 200px; padding-top: 5px; text-align: center; }
        .no-print { text-align: center; margin-bottom: 20px; }
        .btn-print { background: #1a5276; color: white; border: none; padding: 10px 30px; font-size: 14px; border-radius: 5px; cursor: pointer; }
        @media print { .no-print { display: none; } body { padding: 0; } }
    </style>
</head>
<body>
    <div class="no-print">
        <button class="btn-print" onclick="window.print()">🖨️ Imprimer / Enregistrer en PDF</button>
    </div>
    <div class="page">
        <div class="header">
            <div>
                <h1>ORDONNANCE MÉDICALE</h1>
                <p style="color:#1a5276; font-size:12px;">DocTime — Plateforme Médicale</p>
            </div>
            <div class="date">
                <p><strong>N° :</strong> ORD-{$ordId}</p>
                <p><strong>Date :</strong> {$date}</p>
            </div>
        </div>

        <div style="display:flex; gap:20px; margin-bottom:18px;">
            <div class="section" style="flex:1;">
                <h3>👨‍⚕️ Médecin</h3>
                <p><strong>{$nomMedecin}</strong></p>
                <p>{$specialite}</p>
            </div>
            <div class="section" style="flex:1;">
                <h3>🧑 Patient</h3>
                <p><strong>{$nomPatient}</strong></p>
                <p>Tél : {$telPatient}</p>
            </div>
        </div>

        <div class="section">
            <h3>🩺 Diagnostic</h3>
            <p style="padding:10px 12px;">{$diagnostic}</p>
        </div>

        <div class="section">
            <h3>💊 Médicaments prescrits</h3>
            <table>
                <thead>
                    <tr>
                        <th>Médicament</th>
                        <th>Dosage</th>
                        <th>Fréquence</th>
                        <th>Durée</th>
                        <th>Indication</th>
                    </tr>
                </thead>
                <tbody>{$rows}</tbody>
            </table>
        </div>

        <div class="section">
            <h3>📝 Notes médicales</h3>
            <p style="padding:10px 12px;">{$notes}</p>
        </div>

        <div class="footer">
            <p>Document généré le {$date} via DocTime</p>
            <div class="signature">
                <p>Signature du médecin</p>
                <p style="margin-top:30px;">{$nomMedecin}</p>
            </div>
        </div>
    </div>
    <script>
        // Auto-trigger print dialog for PDF save
        window.onload = function() {
            // Small delay so page renders first
            setTimeout(function(){ window.print(); }, 500);
        };
    </script>
</body>
</html>
HTML;

        // Send as HTML — browser print dialog allows "Save as PDF"
        header('Content-Type: text/html; charset=UTF-8');
        echo $html;
        exit;
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