<?php

require_once __DIR__ . '/../models/Patient.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/Medecin.php';
require_once __DIR__ . '/../models/RendezVous.php';
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

    public function __destruct() {
        unset($this->patientModel, $this->userModel, $this->medecinModel, $this->auth);
    }

    /**
 * Mettre à jour un rendez-vous (patient)
 */
public function updateRendezVous(): void {
    $this->auth->requireRole('patient');
    
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        header('Location: index.php?page=mes_rendez_vous');
        exit;
    }
    
    $rdv_id = (int)$_POST['rdv_id'];
    $date_rendezvous = $_POST['date_rendezvous'];
    $heure_rendezvous = $_POST['heure_rendezvous'];
    $motif = $_POST['motif'] ?? '';
    
    $errors = [];
    
    if (empty($date_rendezvous)) {
        $errors['date'] = 'Veuillez sélectionner une date.';
    }
    
    if (empty($heure_rendezvous)) {
        $errors['heure'] = 'Veuillez sélectionner une heure.';
    }
    
    if (!empty($errors)) {
        $_SESSION['error'] = implode('<br>', $errors);
        header('Location: index.php?page=mes_rendez_vous');
        exit;
    }
    
    try {
        $db = Database::getInstance()->getConnection();
        $patient_id = (int)$_SESSION['user_id'];
        
        // Vérifier que le rendez-vous appartient au patient
        $stmt = $db->prepare("SELECT * FROM rendez_vous WHERE id = :id AND patient_id = :patient_id");
        $stmt->execute([':id' => $rdv_id, ':patient_id' => $patient_id]);
        $rdv = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$rdv) {
            $_SESSION['error'] = 'Rendez-vous non trouvé.';
            header('Location: index.php?page=mes_rendez_vous');
            exit;
        }
        
        // Vérifier que la date n'est pas passée
        if (strtotime($date_rendezvous) < strtotime(date('Y-m-d'))) {
            $_SESSION['error'] = 'La date ne peut pas être dans le passé.';
            header('Location: index.php?page=mes_rendez_vous');
            exit;
        }
        
        // Vérifier que le créneau est disponible (optionnel)
        $stmt = $db->prepare("SELECT COUNT(*) FROM rendez_vous WHERE medecin_id = :medecin_id AND date_rendezvous = :date AND heure_rendezvous = :heure AND id != :id AND statut NOT IN ('annulé', 'terminé')");
        $stmt->execute([
            ':medecin_id' => $rdv['medecin_id'],
            ':date' => $date_rendezvous,
            ':heure' => $heure_rendezvous,
            ':id' => $rdv_id
        ]);
        $existing = $stmt->fetchColumn();
        
        if ($existing > 0) {
            $_SESSION['error'] = 'Ce créneau horaire est déjà pris. Veuillez choisir un autre horaire.';
            header('Location: index.php?page=mes_rendez_vous');
            exit;
        }
        
        // Mettre à jour le rendez-vous (remettre en attente)
        $stmt = $db->prepare("UPDATE rendez_vous SET date_rendezvous = :date, heure_rendezvous = :heure, motif = :motif, statut = 'en_attente', updated_at = NOW() WHERE id = :id");
        $result = $stmt->execute([
            ':date' => $date_rendezvous,
            ':heure' => $heure_rendezvous,
            ':motif' => $motif,
            ':id' => $rdv_id
        ]);
        
        if ($result) {
            $_SESSION['success'] = 'Rendez-vous modifié avec succès. En attente de nouvelle confirmation.';
        } else {
            throw new Exception('Erreur lors de la modification.');
        }
        
        header('Location: index.php?page=mes_rendez_vous');
        exit;
    } catch (Exception $e) {
        error_log('Erreur updateRendezVous - ' . $e->getMessage());
        $_SESSION['error'] = 'Erreur lors de la modification. Veuillez réessayer.';
        header('Location: index.php?page=mes_rendez_vous');
        exit;
    }
}

/**
 * Supprimer un rendez-vous (patient)
 */
public function deleteRendezVous(int $id): void {
    $this->auth->requireRole('patient');
    
    try {
        $db = Database::getInstance()->getConnection();
        $patient_id = (int)$_SESSION['user_id'];
        
        // Vérifier que le rendez-vous appartient au patient
        $stmt = $db->prepare("SELECT * FROM rendez_vous WHERE id = :id AND patient_id = :patient_id");
        $stmt->execute([':id' => $id, ':patient_id' => $patient_id]);
        $rdv = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$rdv) {
            $_SESSION['error'] = 'Rendez-vous non trouvé.';
            header('Location: index.php?page=mes_rendez_vous');
            exit;
        }
        
        // Supprimer le rendez-vous
        $stmt = $db->prepare("DELETE FROM rendez_vous WHERE id = :id");
        $result = $stmt->execute([':id' => $id]);
        
        if ($result) {
            $rdvModel = new RendezVous();
            $rdvModel->fillWaitlistForFreedSlot(
                (int)($rdv['medecin_id'] ?? 0),
                (string)($rdv['date_rendezvous'] ?? ''),
                (string)($rdv['heure_rendezvous'] ?? '')
            );
            $_SESSION['success'] = 'Rendez-vous supprimé avec succès.';
        } else {
            throw new Exception('Erreur lors de la suppression.');
        }
        
        header('Location: index.php?page=mes_rendez_vous');
        exit;
    } catch (Exception $e) {
        error_log('Erreur deleteRendezVous - ' . $e->getMessage());
        $_SESSION['error'] = 'Erreur lors de la suppression.';
        header('Location: index.php?page=mes_rendez_vous');
        exit;
    }
}


/**
 * Afficher les détails d'un rendez-vous pour le patient
 */
public function showRendezVous(int $id): void {
    $this->auth->requireRole('patient');
    
    try {
        $db = Database::getInstance()->getConnection();
        $patientId = (int)$_SESSION['user_id'];
        
        $sql = "SELECT rv.*, 
                       CONCAT(u_patient.prenom, ' ', u_patient.nom) AS patient_nom,
                       CONCAT(u_medecin.prenom, ' ', u_medecin.nom) AS medecin_nom,
                       u_medecin.email AS medecin_email,
                       m.specialite,
                       m.cabinet_adresse
                FROM rendez_vous rv
                JOIN users u_patient ON rv.patient_id = u_patient.id
                JOIN users u_medecin ON rv.medecin_id = u_medecin.id
                LEFT JOIN medecins m ON rv.medecin_id = m.user_id
                WHERE rv.id = :id AND rv.patient_id = :patient_id";
        
        $stmt = $db->prepare($sql);
        $stmt->execute([':id' => $id, ':patient_id' => $patientId]);
        $rdv = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$rdv) {
            $_SESSION['error'] = 'Rendez-vous non trouvé.';
            header('Location: index.php?page=mes_rendez_vous');
            exit;
        }
        
        $flash = $_SESSION['flash'] ?? null;
        unset($_SESSION['flash']);
        
        $viewPath = __DIR__ . '/../views/frontoffice/patient/detail_rendez_vous.php';
        if (file_exists($viewPath)) {
            require_once $viewPath;
        } else {
            $this->renderPatientRendezVousDetailFallback($rdv);
        }
    } catch (Exception $e) {
        error_log('Erreur showRendezVous - ' . $e->getMessage());
        $_SESSION['error'] = 'Erreur lors du chargement.';
        header('Location: index.php?page=mes_rendez_vous');
        exit;
    }
}


/**
 * Fallback pour l'affichage des détails d'un rendez-vous pour patient
 */
private function renderPatientRendezVousDetailFallback(array $rdv): void {
    ?>
    <!DOCTYPE html>
    <html lang="fr">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Détail du rendez-vous - Valorys</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
        <style>
            body { background: #f0f6ff; font-family: 'Segoe UI', sans-serif; }
            .navbar-custom { background: linear-gradient(135deg, #2A7FAA 0%, #4CAF50 100%); padding: 0.8rem 2rem; }
            .container { margin-top: 30px; margin-bottom: 50px; }
            .detail-card { background: white; border-radius: 15px; box-shadow: 0 2px 10px rgba(0,0,0,0.08); overflow: hidden; }
            .detail-header { background: linear-gradient(135deg, #2A7FAA 0%, #4CAF50 100%); color: white; padding: 20px; }
            .detail-body { padding: 25px; }
            .info-section { margin-bottom: 25px; }
            .info-section h5 { color: #2A7FAA; margin-bottom: 15px; padding-bottom: 8px; border-bottom: 2px solid #eee; }
            .info-row { margin-bottom: 12px; display: flex; flex-wrap: wrap; }
            .info-label { font-weight: bold; width: 130px; color: #555; }
            .info-value { color: #333; flex: 1; }
            .badge-statut { padding: 5px 12px; border-radius: 20px; font-size: 13px; display: inline-block; }
            .badge-confirme { background: #d4edda; color: #155724; }
            .badge-attente { background: #fff3cd; color: #856404; }
            .badge-termine { background: #cfe2ff; color: #084298; }
            .badge-annule { background: #f8d7da; color: #721c24; }
            .btn-action { padding: 8px 20px; border-radius: 25px; text-decoration: none; display: inline-block; margin-right: 10px; }
            .btn-annuler { background: #dc3545; color: white; }
            .btn-retour { background: #6c757d; color: white; }
            .btn-modifier { background: #ffc107; color: #000; }
            footer { background: #1a2035; color: white; text-align: center; padding: 30px; margin-top: 50px; }
        </style>
    </head>
    <body>
        <nav class="navbar navbar-expand-lg navbar-dark navbar-custom">
            <div class="container">
                <a class="navbar-brand fw-bold" href="index.php?page=accueil">
                    <i class="fas fa-hospital-user"></i> Valorys
                </a>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="collapse navbar-collapse" id="navbarNav">
                    <ul class="navbar-nav ms-auto">
                        <li class="nav-item"><a class="nav-link" href="index.php?page=accueil">Accueil</a></li>
                        <li class="nav-item"><a class="nav-link" href="index.php?page=mes_rendez_vous">Mes RDV</a></li>
                        <li class="nav-item"><a class="nav-link" href="index.php?page=mon_profil">Profil</a></li>
                        <li class="nav-item"><a class="nav-link" href="index.php?page=logout">Déconnexion</a></li>
                    </ul>
                </div>
            </div>
        </nav>
        
        <div class="container">
            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success alert-dismissible fade show">
                    <i class="fas fa-check-circle me-2"></i><?= htmlspecialchars($_SESSION['success']) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php unset($_SESSION['success']); ?>
            <?php endif; ?>
            
            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger alert-dismissible fade show">
                    <i class="fas fa-exclamation-circle me-2"></i><?= htmlspecialchars($_SESSION['error']) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php unset($_SESSION['error']); ?>
            <?php endif; ?>
            
            <div class="detail-card">
                <div class="detail-header">
                    <h3 class="mb-0"><i class="fas fa-calendar-check me-2"></i>Détail du rendez-vous #<?= $rdv['id'] ?></h3>
                </div>
                <div class="detail-body">
                    <div class="text-end mb-3">
                        <?php
                        $badgeClass = match($rdv['statut']) {
                            'confirmé' => 'badge-confirme',
                            'en_attente' => 'badge-attente',
                            'terminé' => 'badge-termine',
                            'annulé' => 'badge-annule',
                            default => 'badge-attente'
                        };
                        ?>
                        <span class="badge-statut <?= $badgeClass ?>">
                            <i class="fas fa-circle me-1"></i><?= ucfirst($rdv['statut']) ?>
                        </span>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="info-section">
                                <h5><i class="fas fa-user-md me-2"></i>Informations médecin</h5>
                                <div class="info-row">
                                    <span class="info-label">Nom :</span>
                                    <span class="info-value">Dr. <?= htmlspecialchars($rdv['medecin_nom'] ?? 'Non renseigné') ?></span>
                                </div>
                                <div class="info-row">
                                    <span class="info-label">Spécialité :</span>
                                    <span class="info-value"><?= htmlspecialchars($rdv['specialite'] ?? 'Généraliste') ?></span>
                                </div>
                                <div class="info-row">
                                    <span class="info-label">Email :</span>
                                    <span class="info-value"><?= htmlspecialchars($rdv['medecin_email'] ?? 'Non renseigné') ?></span>
                                </div>
                                <div class="info-row">
                                    <span class="info-label">Cabinet :</span>
                                    <span class="info-value"><?= htmlspecialchars($rdv['cabinet_adresse'] ?? 'Non renseigné') ?></span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="info-section">
                                <h5><i class="fas fa-stethoscope me-2"></i>Informations consultation</h5>
                                <div class="info-row">
                                    <span class="info-label">Date :</span>
                                    <span class="info-value"><?= date('d/m/Y', strtotime($rdv['date_rendezvous'])) ?></span>
                                </div>
                                <div class="info-row">
                                    <span class="info-label">Heure :</span>
                                    <span class="info-value"><?= $rdv['heure_rendezvous'] ?></span>
                                </div>
                                <?php if (!empty($rdv['motif'])): ?>
                                <div class="info-row">
                                    <span class="info-label">Motif :</span>
                                    <span class="info-value"><?= nl2br(htmlspecialchars($rdv['motif'])) ?></span>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    
                    <hr>
                    
                    <div class="d-flex justify-content-end gap-2">
                        <a href="index.php?page=mes_rendez_vous" class="btn-action btn-retour">
                            <i class="fas fa-arrow-left me-2"></i>Retour
                        </a>
                        <?php if ($rdv['statut'] !== 'annulé' && $rdv['statut'] !== 'terminé'): ?>
                            <a href="index.php?page=annuler_rendez_vous&id=<?= $rdv['id'] ?>" class="btn-action btn-annuler" onclick="return confirm('Annuler ce rendez-vous ?')">
                                <i class="fas fa-times me-2"></i>Annuler
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        
        <footer>
            <div class="container">
                <p>&copy; 2024 Valorys - Tous droits réservés</p>
                <small>Plateforme médicale en ligne</small>
            </div>
        </footer>
        
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    </body>
    </html>
    <?php
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
    
    $errors = [];
    $old = $_POST;
    
    // Validation
    if (empty($_POST['medecin_id'])) {
        $errors['medecin_id'] = 'Veuillez sélectionner un médecin.';
    }
    
    if (empty($_POST['date_rendezvous'])) {
        $errors['date_rendezvous'] = 'Veuillez sélectionner une date.';
    }
    
    if (empty($_POST['heure_rendezvous'])) {
        $errors['heure_rendezvous'] = 'Veuillez sélectionner une heure.';
    }
    
    if (!empty($errors)) {
        $_SESSION['errors'] = $errors;
        $_SESSION['old'] = $old;
        header('Location: index.php?page=prendre_rendez_vous');
        exit;
    }
    
    try {
        $db = Database::getInstance()->getConnection();
        
        $patient_id = (int)$_SESSION['user_id'];
        $medecin_id = (int)$_POST['medecin_id'];
        $date_rendezvous = $_POST['date_rendezvous'];
        $heure_rendezvous = $_POST['heure_rendezvous'];
        $motif = $_POST['motif'] ?? '';
        
        // Vérifier que la date n'est pas passée
        if (strtotime($date_rendezvous) < strtotime(date('Y-m-d'))) {
            $_SESSION['error'] = 'La date ne peut pas être dans le passé.';
            $_SESSION['old'] = $old;
            header('Location: index.php?page=prendre_rendez_vous');
            exit;
        }
        
        // Vérifier que le créneau est disponible
        $stmt = $db->prepare("SELECT COUNT(*) FROM rendez_vous WHERE medecin_id = :medecin_id AND date_rendezvous = :date AND heure_rendezvous = :heure AND statut NOT IN ('annulé', 'terminé')");
        $stmt->execute([
            ':medecin_id' => $medecin_id,
            ':date' => $date_rendezvous,
            ':heure' => $heure_rendezvous
        ]);
        $existing = $stmt->fetchColumn();
        
        if ($existing > 0) {
            $rdvModel = new RendezVous();
            $addedToWaitlist = $rdvModel->addToWaitlist(
                $patient_id,
                $medecin_id,
                $date_rendezvous,
                $heure_rendezvous,
                $motif
            );
            if ($addedToWaitlist) {
                $_SESSION['success'] = 'Ce créneau est déjà pris. Vous avez été ajouté à la liste d\'attente.';
            } else {
                $_SESSION['error'] = 'Ce créneau horaire est déjà pris. Veuillez choisir un autre horaire.';
            }
            $_SESSION['old'] = $old;
            header('Location: index.php?page=prendre_rendez_vous');
            exit;
        }
        
        $sql = "INSERT INTO rendez_vous (patient_id, medecin_id, date_rendezvous, heure_rendezvous, motif, statut, created_at) 
                VALUES (:patient_id, :medecin_id, :date_rendezvous, :heure_rendezvous, :motif, 'en_attente', NOW())";
        
        $stmt = $db->prepare($sql);
        $result = $stmt->execute([
            ':patient_id' => $patient_id,
            ':medecin_id' => $medecin_id,
            ':date_rendezvous' => $date_rendezvous,
            ':heure_rendezvous' => $heure_rendezvous,
            ':motif' => $motif
        ]);
        
        if ($result) {
            $_SESSION['success'] = 'Votre rendez-vous a été demandé avec succès. En attente de confirmation.';
            header('Location: index.php?page=mes_rendez_vous');
            exit;
        } else {
            throw new Exception('Erreur lors de la création du rendez-vous.');
        }
    } catch (Exception $e) {
        error_log('Erreur createAppointment - ' . $e->getMessage());
        $_SESSION['error'] = 'Erreur lors de la création du rendez-vous. Veuillez réessayer.';
        $_SESSION['old'] = $old;
        header('Location: index.php?page=prendre_rendez_vous');
        exit;
    }
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
        $rdvModel = new RendezVous();
        $rdvModel->fillWaitlistForFreedSlot(
            (int)($appt['medecin_id'] ?? 0),
            (string)($appt['date_rendezvous'] ?? ''),
            (string)($appt['heure_rendezvous'] ?? '')
        );
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