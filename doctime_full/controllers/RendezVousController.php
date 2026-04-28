<?php

require_once __DIR__ . '/../models/Article.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/mail.php';
require_once __DIR__ . '/AuthController.php';

class RendezVousController {

    private RendezVous $rdvModel;
    private Medecin $medecinModel;
    private Patient $patientModel;
    private AuthController $auth;
    private Database $db;

    public function __construct() {
        $this->rdvModel     = new RendezVous();
        $this->medecinModel = new Medecin();
        $this->patientModel = new Patient();
        $this->auth         = new AuthController();
        $this->db           = Database::getInstance();
    }

    public function __destruct() {
        unset($this->rdvModel, $this->medecinModel, $this->patientModel, $this->auth, $this->db);
    }

    public function indexPatient(): void {
        $this->auth->requireRole('patient');
        try {
            $patientId = (int)$_SESSION['user_id'];
            $filter    = $_GET['filter'] ?? 'all';
            $rdvs = match ($filter) {
                'upcoming'  => $this->rdvModel->getUpcomingByPatient($patientId),
                'past'      => $this->rdvModel->getPastByPatient($patientId),
                'cancelled' => $this->rdvModel->getCancelledByPatient($patientId),
                default     => $this->rdvModel->getAllByPatient($patientId),
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

    public function indexMedecin(): void {
        $this->auth->requireRole('medecin');
        try {
            $medecinId = (int)$_SESSION['user_id'];
            $filter    = $_GET['filter'] ?? 'all';
            $rdvs = match ($filter) {
                'today'    => $this->rdvModel->getTodayByMedecin($medecinId),
                'upcoming' => $this->rdvModel->getUpcomingByMedecin($medecinId),
                'past'     => $this->rdvModel->getPastByMedecin($medecinId),
                default    => $this->rdvModel->getAllByMedecin($medecinId),
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

    public function indexAdmin(): void {
        $this->auth->requireRole('admin');
        try {
            $filter  = $_GET['filter']  ?? 'all';
            $medecin = $_GET['medecin'] ?? null;
            $patient = $_GET['patient'] ?? null;
            $rdvs     = $this->rdvModel->getAll($filter, $medecin, $patient);
            $medecins = $this->medecinModel->getAllWithUsers();
            $patients = $this->patientModel->getAll();
            $flash    = $_SESSION['flash'] ?? null;
            unset($_SESSION['flash']);
            require_once __DIR__ . '/../views/backoffice/rdv_list_admin.php';
        } catch (Exception $e) {
            error_log('Erreur RendezVousController::indexAdmin - ' . $e->getMessage());
            $this->setFlash('error', 'Erreur lors du chargement des rendez-vous.');
            header('Location: /admin/dashboard');
            exit;
        }
    }

    public function createPatient(): void {
        $this->auth->requireRole('patient');
        try {
            $csrfToken = $this->generateCsrfToken();
            $medecinId = $_GET['medecin'] ?? null;
            $medecin   = null;
            if ($medecinId) {
                $medecin = $this->medecinModel->findByUserId((int)$medecinId);
            }
            $medecins = $this->medecinModel->getAllWithUsers();
            $old      = $_SESSION['old']   ?? null;
            $flash    = $_SESSION['flash'] ?? null;
            unset($_SESSION['old'], $_SESSION['flash']);
            require_once __DIR__ . '/../views/frontoffice/rdv_create_patient.php';
        } catch (Exception $e) {
            error_log('Erreur RendezVousController::createPatient - ' . $e->getMessage());
            $this->setFlash('error', 'Erreur lors du chargement du formulaire.');
            header('Location: /patient/rdv');
            exit;
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    //  storePatient — envoie un email de confirmation après création du RDV
    // ─────────────────────────────────────────────────────────────────────────
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
                'patient_id'        => $patientId,
                'medecin_id'        => (int)($_POST['medecin_id'] ?? 0),
                'date_rdv'          => $_POST['date_rdv'] ?? '',
                'heure_rdv'         => $_POST['heure_rdv'] ?? '',
                'motif'             => htmlspecialchars(trim($_POST['motif'] ?? ''), ENT_QUOTES, 'UTF-8'),
                'type_consultation' => $_POST['type_consultation'] ?? 'consultation',
                'statut'            => 'confirmé',
            ];

            $errors = $this->validateRendezVous($data);
            if (!empty($errors)) {
                $this->setFlash('error', implode('<br>', $errors));
                $_SESSION['old'] = $data;
                header('Location: /patient/rdv/create');
                exit;
            }

            $medecin = $this->medecinModel->findByUserId($data['medecin_id']);
            if (!$medecin) {
                throw new Exception('Médecin introuvable.');
            }

            if (!$this->rdvModel->isAvailable($data['medecin_id'], $data['date_rdv'], $data['heure_rdv'])) {
                $this->setFlash('error', "Ce créneau n'est pas disponible.");
                $_SESSION['old'] = $data;
                header('Location: /patient/rdv/create');
                exit;
            }

            $rdvId = $this->rdvModel->create($data);
            if (!$rdvId) {
                throw new Exception('Erreur lors de la création du rendez-vous.');
            }

            $this->logAction($_SESSION['user_id'], 'Création RDV', "Rendez-vous #$rdvId créé avec le médecin #" . $data['medecin_id']);

            // ── EMAIL DE CONFIRMATION ─────────────────────────────────────
            $patient     = $this->patientModel->findByUserId($patientId);
            $nomPatient  = trim(($patient['prenom'] ?? '') . ' ' . ($patient['nom'] ?? ''));
            $nomMedecin  = 'Dr ' . trim(($medecin['prenom'] ?? '') . ' ' . ($medecin['nom'] ?? ''));
            $specialite  = $medecin['specialite'] ?? 'Médecin';
            $dateFormate = (new DateTime($data['date_rdv']))->format('d/m/Y');
            $heure       = substr($data['heure_rdv'], 0, 5);
            $adresse     = $medecin['cabinet_adresse'] ?? 'Non précisée';
            $sujet       = "DocTime - Confirmation RDV du {$dateFormate} à {$heure}";
            $corps       = $this->buildEmailHtml($nomPatient, $nomMedecin, $specialite, $dateFormate, $heure, $data['motif'], $adresse, $rdvId);

            // Email au patient
            if (!empty($patient['email'])) {
                MailConfig::send($patient['email'], $nomPatient, $sujet, $corps);
            }

            // Email de suivi à toi (admin)
            MailConfig::send('skanderdhaoui77@gmail.com', 'Skander', $sujet, $corps);
            // ─────────────────────────────────────────────────────────────

            $this->setFlash('success', 'Rendez-vous créé avec succès. Un email de confirmation a été envoyé.');
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

    // ─────────────────────────────────────────────────────────────────────────
    //  Template email HTML
    // ─────────────────────────────────────────────────────────────────────────
    private function buildEmailHtml(
        string $nomPatient,
        string $nomMedecin,
        string $specialite,
        string $date,
        string $heure,
        string $motif,
        string $adresse,
        int    $rdvId
    ): string {
        return <<<HTML
        <!DOCTYPE html>
        <html lang="fr">
        <head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"></head>
        <body style="margin:0;padding:0;background:#f4f4f4;font-family:Arial,sans-serif;">
          <table width="100%" cellpadding="0" cellspacing="0" style="background:#f4f4f4;padding:30px 0;">
            <tr><td align="center">
              <table width="600" cellpadding="0" cellspacing="0" style="background:#ffffff;border-radius:12px;overflow:hidden;max-width:600px;width:100%;">

                <tr>
                  <td style="background:#1D9E75;padding:28px 32px;text-align:center;">
                    <h1 style="color:#ffffff;margin:0;font-size:26px;font-weight:700;letter-spacing:1px;">DocTime</h1>
                    <p style="color:#a8f0d8;margin:6px 0 0;font-size:14px;">Plateforme de gestion médicale</p>
                  </td>
                </tr>

                <tr>
                  <td style="padding:32px 32px 0;">
                    <h2 style="margin:0 0 8px;font-size:20px;color:#1a1a1a;">Rendez-vous confirmé</h2>
                    <p style="margin:0;color:#666;font-size:15px;">Bonjour <strong>{$nomPatient}</strong>, votre rendez-vous a bien été enregistré.</p>
                  </td>
                </tr>

                <tr>
                  <td style="padding:24px 32px;">
                    <table width="100%" cellpadding="0" cellspacing="0" style="background:#f0fbf6;border-radius:10px;border:1px solid #9FE1CB;">
                      <tr><td style="padding:20px 24px;">
                        <table width="100%" cellpadding="0" cellspacing="0">
                          <tr><td style="padding:8px 0;border-bottom:1px solid #d0f0e4;">
                            <span style="font-size:12px;color:#888;display:block;margin-bottom:2px;">Médecin</span>
                            <span style="font-size:15px;font-weight:600;color:#1a1a1a;">{$nomMedecin}</span>
                            <span style="font-size:13px;color:#0F6E56;margin-left:8px;">— {$specialite}</span>
                          </td></tr>
                          <tr><td style="padding:8px 0;border-bottom:1px solid #d0f0e4;">
                            <span style="font-size:12px;color:#888;display:block;margin-bottom:2px;">Date &amp; heure</span>
                            <span style="font-size:15px;font-weight:600;color:#1a1a1a;">{$date} à {$heure}</span>
                          </td></tr>
                          <tr><td style="padding:8px 0;border-bottom:1px solid #d0f0e4;">
                            <span style="font-size:12px;color:#888;display:block;margin-bottom:2px;">Motif</span>
                            <span style="font-size:14px;color:#1a1a1a;">{$motif}</span>
                          </td></tr>
                          <tr><td style="padding:8px 0;">
                            <span style="font-size:12px;color:#888;display:block;margin-bottom:2px;">Adresse du cabinet</span>
                            <span style="font-size:14px;color:#1a1a1a;">{$adresse}</span>
                          </td></tr>
                        </table>
                      </td></tr>
                    </table>
                  </td>
                </tr>

                <tr>
                  <td style="padding:0 32px 24px;text-align:center;">
                    <p style="margin:0;font-size:13px;color:#999;">
                      Référence : <strong style="color:#1D9E75;">#RDV-{$rdvId}</strong>
                    </p>
                  </td>
                </tr>

                <tr>
                  <td style="background:#f9f9f9;padding:20px 32px;text-align:center;border-top:1px solid #eee;">
                    <p style="margin:0;font-size:12px;color:#aaa;">
                      Email automatique — DocTime.<br>
                      Pour annuler, connectez-vous sur la plateforme.
                    </p>
                  </td>
                </tr>

              </table>
            </td></tr>
          </table>
        </body>
        </html>
        HTML;
    }

    public function cancelPatient(int $id): void {
        $this->auth->requireRole('patient');
        try {
            $patientId = (int)$_SESSION['user_id'];
            $rdv       = $this->rdvModel->getById($id);
            if (!$rdv || (int)$rdv['patient_id'] !== $patientId) {
                http_response_code(403);
                die('Accès refusé.');
            }
            $dateRdv = new DateTime($rdv['date_rdv'] . ' ' . $rdv['heure_rdv']);
            $now     = new DateTime();
            if ($dateRdv->diff($now)->h <= 24 && $dateRdv > $now) {
                $this->setFlash('error', 'Vous ne pouvez pas annuler un RDV à moins de 24h.');
                header("Location: /patient/rdv/$id");
                exit;
            }
            $raison = htmlspecialchars(trim($_POST['raison'] ?? ''), ENT_QUOTES, 'UTF-8');
            $this->rdvModel->update($id, ['statut' => 'annulé', 'raison_annulation' => $raison]);
            $this->logAction($_SESSION['user_id'], 'Annulation RDV', "Rendez-vous #$id annulé");
            $this->setFlash('success', 'Rendez-vous annulé.');
            header('Location: /patient/rdv');
            exit;
        } catch (Exception $e) {
            error_log('Erreur RendezVousController::cancelPatient - ' . $e->getMessage());
            $this->setFlash('error', "Erreur lors de l'annulation.");
            header("Location: /patient/rdv/$id");
            exit;
        }
    }

    public function showPatient(int $id): void {
        $this->auth->requireRole('patient');
        try {
            $patientId = (int)$_SESSION['user_id'];
            $rdv       = $this->rdvModel->getById($id);
            if (!$rdv || (int)$rdv['patient_id'] !== $patientId) {
                http_response_code(403);
                die('Accès refusé.');
            }
            $medecin = $this->medecinModel->findByUserId((int)$rdv['medecin_id']);
            $flash   = $_SESSION['flash'] ?? null;
            unset($_SESSION['flash']);
            require_once __DIR__ . '/../views/frontoffice/rdv_show_patient.php';
        } catch (Exception $e) {
            error_log('Erreur RendezVousController::showPatient - ' . $e->getMessage());
            http_response_code(500);
            die('Erreur lors du chargement du rendez-vous.');
        }
    }

    public function showMedecin(int $id): void {
        $this->auth->requireRole('medecin');
        try {
            $medecinId = (int)$_SESSION['user_id'];
            $rdv       = $this->rdvModel->getById($id);
            if (!$rdv || (int)$rdv['medecin_id'] !== $medecinId) {
                http_response_code(403);
                die('Accès refusé.');
            }
            $patient = $this->patientModel->findByUserId((int)$rdv['patient_id']);
            $flash   = $_SESSION['flash'] ?? null;
            unset($_SESSION['flash']);
            require_once __DIR__ . '/../views/backoffice/rdv_show_medecin.php';
        } catch (Exception $e) {
            error_log('Erreur RendezVousController::showMedecin - ' . $e->getMessage());
            http_response_code(500);
            die('Erreur lors du chargement du rendez-vous.');
        }
    }

    public function editPatient(int $id): void {
        $this->auth->requireRole('patient');
        try {
            $patientId = (int)$_SESSION['user_id'];
            $rdv       = $this->rdvModel->getById($id);
            if (!$rdv || (int)$rdv['patient_id'] !== $patientId) {
                http_response_code(403);
                die('Accès refusé.');
            }
            $dateRdv = new DateTime($rdv['date_rdv'] . ' ' . $rdv['heure_rdv']);
            $now     = new DateTime();
            if ($dateRdv->diff($now)->days < 2 && $dateRdv > $now) {
                $this->setFlash('error', 'Vous ne pouvez pas modifier un RDV à moins de 48h.');
                header("Location: /patient/rdv/$id");
                exit;
            }
            $csrfToken = $this->generateCsrfToken();
            $medecins  = $this->medecinModel->getAllWithUsers();
            $old       = $_SESSION['old']   ?? null;
            $flash     = $_SESSION['flash'] ?? null;
            unset($_SESSION['old'], $_SESSION['flash']);
            require_once __DIR__ . '/../views/frontoffice/rdv_edit_patient.php';
        } catch (Exception $e) {
            error_log('Erreur RendezVousController::editPatient - ' . $e->getMessage());
            $this->setFlash('error', 'Erreur lors du chargement du formulaire.');
            header('Location: /patient/rdv');
            exit;
        }
    }

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
            $rdv       = $this->rdvModel->getById($id);
            if (!$rdv || (int)$rdv['patient_id'] !== $patientId) {
                http_response_code(403);
                die('Accès refusé.');
            }
            $data = [
                'date_rdv'          => $_POST['date_rdv']  ?? '',
                'heure_rdv'         => $_POST['heure_rdv'] ?? '',
                'motif'             => htmlspecialchars(trim($_POST['motif'] ?? ''), ENT_QUOTES, 'UTF-8'),
                'type_consultation' => $_POST['type_consultation'] ?? 'consultation',
            ];
            $errors = $this->validateRendezVousUpdate($data);
            if (!empty($errors)) {
                $this->setFlash('error', implode('<br>', $errors));
                $_SESSION['old'] = $data;
                header("Location: /patient/rdv/$id/edit");
                exit;
            }
            if (!$this->rdvModel->isAvailable((int)$rdv['medecin_id'], $data['date_rdv'], $data['heure_rdv'], $id)) {
                $this->setFlash('error', "Ce créneau n'est pas disponible.");
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

    public function confirmPresence(int $id): void {
        $this->auth->requireRole('medecin');
        try {
            $medecinId = (int)$_SESSION['user_id'];
            $rdv       = $this->rdvModel->getById($id);
            if (!$rdv || (int)$rdv['medecin_id'] !== $medecinId) {
                http_response_code(403);
                die('Accès refusé.');
            }
            $this->rdvModel->update($id, ['statut' => 'effectué', 'date_effet' => date('Y-m-d H:i:s')]);
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

    public function addNote(int $id): void {
        $this->auth->requireRole('medecin');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: /medecin/rdv/$id");
            exit;
        }
        try {
            $medecinId = (int)$_SESSION['user_id'];
            $rdv       = $this->rdvModel->getById($id);
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
            $this->setFlash('error', "Erreur lors de l'ajout de la note.");
            header("Location: /medecin/rdv/$id");
            exit;
        }
    }

    public function getAvailabilities(): void {
        header('Content-Type: application/json');
        try {
            $medecinId = (int)($_GET['medecin_id'] ?? 0);
            $date      = $_GET['date'] ?? '';
            if (!$medecinId || !$date) {
                echo json_encode(['error' => 'Paramètres invalides']);
                exit;
            }
            $availabilities = $this->rdvModel->getAvailabilitiesByMedecin($medecinId, $date);
            echo json_encode(['success' => true, 'availabilities' => $availabilities]);
            exit;
        } catch (Exception $e) {
            error_log('Erreur getAvailabilities - ' . $e->getMessage());
            echo json_encode(['error' => 'Erreur serveur']);
            exit;
        }
    }

    // ── Méthodes privées ──────────────────────────────────────────────────────

    private function validateRendezVous(array $data): array {
        $errors = [];
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
        if (empty($data['heure_rdv'])) {
            $errors[] = "L'heure est obligatoire.";
        } elseif (!preg_match('/^([0-1][0-9]|2[0-3]):[0-5][0-9]$/', $data['heure_rdv'])) {
            $errors[] = "Format d'heure invalide.";
        }
        if (empty($data['motif']) || strlen($data['motif']) < 5) {
            $errors[] = 'Le motif doit contenir au moins 5 caractères.';
        }
        if (empty($data['medecin_id']) || $data['medecin_id'] <= 0) {
            $errors[] = 'Médecin invalide.';
        }
        return $errors;
    }

    private function validateRendezVousUpdate(array $data): array {
        return $this->validateRendezVous([
            'date_rdv'   => $data['date_rdv']  ?? '',
            'heure_rdv'  => $data['heure_rdv'] ?? '',
            'motif'      => $data['motif']      ?? '',
            'medecin_id' => 1,
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
                'user_id'     => $userId,
                'action'      => $action,
                'description' => $description,
                'ip'          => $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0',
            ]);
        } catch (Exception $e) {
            error_log('Erreur logAction: ' . $e->getMessage());
        }
    }
}
?>