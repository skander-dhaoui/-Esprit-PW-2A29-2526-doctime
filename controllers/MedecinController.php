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

    /**
     * Marquer un rendez-vous comme terminé
     */
    public function terminerRendezVous(int $id): void {
        $this->auth->requireRole('medecin');
        
        try {
            $db = Database::getInstance()->getConnection();
            
            $medecinId = (int)$_SESSION['user_id'];
            $stmt = $db->prepare("SELECT * FROM rendez_vous WHERE id = :id AND medecin_id = :medecin_id");
            $stmt->execute([':id' => $id, ':medecin_id' => $medecinId]);
            $rdv = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$rdv) {
                $_SESSION['error'] = 'Rendez-vous non trouvé ou accès non autorisé.';
                header('Location: index.php?page=mes_rendez_vous');
                exit;
            }
            
            $stmt = $db->prepare("UPDATE rendez_vous SET statut = 'terminé', updated_at = NOW() WHERE id = :id");
            $result = $stmt->execute([':id' => $id]);
            
            if ($result) {
                $_SESSION['success'] = 'Rendez-vous marqué comme terminé avec succès.';
            } else {
                $_SESSION['error'] = 'Erreur lors de la mise à jour.';
            }
            
            header('Location: index.php?page=mes_rendez_vous');
            exit;
        } catch (Exception $e) {
            error_log('Erreur terminerRendezVous - ' . $e->getMessage());
            $_SESSION['error'] = 'Erreur lors de la mise à jour.';
            header('Location: index.php?page=mes_rendez_vous');
            exit;
        }
    }

    /**
     * Afficher les détails d'un rendez-vous pour le médecin
     */
    public function showRendezVous(int $id): void {
        $this->auth->requireRole('medecin');
        
        try {
            $db = Database::getInstance()->getConnection();
            $medecinId = (int)$_SESSION['user_id'];
            
            $sql = "SELECT rv.*, 
                           CONCAT(u_patient.prenom, ' ', u_patient.nom) AS patient_nom,
                           u_patient.email AS patient_email,
                           u_patient.telephone AS patient_telephone,
                           u_patient.adresse AS patient_adresse,
                           CONCAT(u_medecin.prenom, ' ', u_medecin.nom) AS medecin_nom,
                           u_medecin.email AS medecin_email,
                           m.specialite,
                           m.cabinet_adresse
                    FROM rendez_vous rv
                    JOIN users u_patient ON rv.patient_id = u_patient.id
                    JOIN users u_medecin ON rv.medecin_id = u_medecin.id
                    LEFT JOIN medecins m ON rv.medecin_id = m.user_id
                    WHERE rv.id = :id AND rv.medecin_id = :medecin_id";
            
            $stmt = $db->prepare($sql);
            $stmt->execute([':id' => $id, ':medecin_id' => $medecinId]);
            $rdv = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$rdv) {
                $_SESSION['error'] = 'Rendez-vous non trouvé.';
                header('Location: index.php?page=mes_rendez_vous');
                exit;
            }
            
            $flash = $_SESSION['flash'] ?? null;
            unset($_SESSION['flash']);
            
            $viewPath = __DIR__ . '/../views/frontoffice/medecin/detail_rendez_vous.php';
            if (file_exists($viewPath)) {
                require_once $viewPath;
            } else {
                $this->renderRendezVousDetailFallback($rdv);
            }
        } catch (Exception $e) {
            error_log('Erreur showRendezVous - ' . $e->getMessage());
            $_SESSION['error'] = 'Erreur lors du chargement.';
            header('Location: index.php?page=mes_rendez_vous');
            exit;
        }
    }

    /**
     * Fallback pour l'affichage des détails d'un rendez-vous (quand la vue n'existe pas)
     */
/**
 * Fallback pour l'affichage des détails d'un rendez-vous (quand la vue n'existe pas)
 */
private function renderRendezVousDetailFallback(array $rdv): void {
    // Récupérer l'ordonnance existante pour ce rendez-vous
    $ordonnance = null;
    try {
        $dbOrd = Database::getInstance()->getConnection();
        $stmtOrd = $dbOrd->prepare("SELECT * FROM ordonnances WHERE rdv_id = :rdv_id");
        $stmtOrd->execute([':rdv_id' => $rdv['id']]);
        $ordonnance = $stmtOrd->fetch(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log('Erreur récupération ordonnance: ' . $e->getMessage());
    }
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
            .btn-confirmer { background: #28a745; color: white; }
            .btn-annuler { background: #dc3545; color: white; }
            .btn-terminer { background: #17a2b8; color: white; }
            .btn-retour { background: #6c757d; color: white; }
            .btn-voir-ordo { background: #17a2b8; color: white; }
            .btn-modifier-ordo { background: #ffc107; color: #000; }
            .btn-supprimer-ordo { background: #dc3545; color: white; }
            .btn-ajouter-ordo { background: #28a745; color: white; }
            .ordonnance-card { background: #f8f9fa; border-radius: 12px; padding: 20px; margin-top: 15px; border-left: 4px solid #2A7FAA; }
            .ordonnance-content { margin: 15px 0; }
            .modal { display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.5); align-items: center; justify-content: center; }
            .modal.show { display: flex; }
            .modal-content { background: white; border-radius: 15px; width: 90%; max-width: 600px; max-height: 90vh; overflow-y: auto; animation: slideDown 0.3s ease; }
            .modal-header { background: linear-gradient(135deg, #2A7FAA 0%, #4CAF50 100%); color: white; padding: 15px 20px; border-radius: 15px 15px 0 0; display: flex; justify-content: space-between; align-items: center; }
            .modal-header .close { background: none; border: none; color: white; font-size: 24px; cursor: pointer; }
            .modal-body { padding: 20px; }
            .modal-footer { padding: 15px 20px; border-top: 1px solid #eee; display: flex; justify-content: flex-end; gap: 10px; }
            @keyframes slideDown {
                from { opacity: 0; transform: translateY(-50px); }
                to { opacity: 1; transform: translateY(0); }
            }
            footer { background: #1a2035; color: white; text-align: center; padding: 30px; margin-top: 50px; }
            .field-error { font-size: 12px; margin-top: 5px; color: #dc3545; }
            .form-control.error { border-color: #dc3545 !important; }
        </style>
    </head>
    <body>
        <nav class="navbar navbar-expand-lg navbar-dark navbar-custom">
            <div class="container">
                <a class="navbar-brand fw-bold" href="index.php?page=accueil"><i class="fas fa-hospital-user"></i> Valorys</a>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="collapse navbar-collapse" id="navbarNav">
                    <ul class="navbar-nav ms-auto">
                        <li class="nav-item"><a class="nav-link" href="index.php?page=accueil">Accueil</a></li>
                        <li class="nav-item"><a class="nav-link" href="index.php?page=mes_rendez_vous">Mes RDV</a></li>
                        <li class="nav-item"><a class="nav-link" href="index.php?page=disponibilites">Disponibilités</a></li>
                        <li class="nav-item"><a class="nav-link" href="index.php?page=mon_profil">Profil</a></li>
                        <li class="nav-item"><a class="nav-link" href="index.php?page=logout">Déconnexion</a></li>
                    </ul>
                </div>
            </div>
        </nav>
        
        <div class="container">
            <!-- Messages flash -->
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
            
            <!-- Détail du rendez-vous -->
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
                        <span class="badge-statut <?= $badgeClass ?>"><i class="fas fa-circle me-1"></i><?= ucfirst($rdv['statut']) ?></span>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="info-section">
                                <h5><i class="fas fa-user me-2"></i>Informations patient</h5>
                                <div class="info-row"><span class="info-label">Nom complet :</span><span class="info-value"><?= htmlspecialchars($rdv['patient_nom'] ?? 'Non renseigné') ?></span></div>
                                <div class="info-row"><span class="info-label">Email :</span><span class="info-value"><?= htmlspecialchars($rdv['patient_email'] ?? 'Non renseigné') ?></span></div>
                                <div class="info-row"><span class="info-label">Téléphone :</span><span class="info-value"><?= htmlspecialchars($rdv['patient_telephone'] ?? 'Non renseigné') ?></span></div>
                                <div class="info-row"><span class="info-label">Adresse :</span><span class="info-value"><?= htmlspecialchars($rdv['patient_adresse'] ?? 'Non renseignée') ?></span></div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="info-section">
                                <h5><i class="fas fa-stethoscope me-2"></i>Informations consultation</h5>
                                <div class="info-row"><span class="info-label">Date :</span><span class="info-value"><?= date('d/m/Y', strtotime($rdv['date_rendezvous'])) ?></span></div>
                                <div class="info-row"><span class="info-label">Heure :</span><span class="info-value"><?= $rdv['heure_rendezvous'] ?></span></div>
                                <div class="info-row"><span class="info-label">Spécialité :</span><span class="info-value"><?= htmlspecialchars($rdv['specialite'] ?? 'Généraliste') ?></span></div>
                                <div class="info-row"><span class="info-label">Cabinet :</span><span class="info-value"><?= htmlspecialchars($rdv['cabinet_adresse'] ?? 'Non renseigné') ?></span></div>
                                <?php if (!empty($rdv['motif'])): ?>
                                <div class="info-row"><span class="info-label">Motif :</span><span class="info-value"><?= nl2br(htmlspecialchars($rdv['motif'])) ?></span></div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    
                    <hr>
                    
                    <div class="d-flex justify-content-end gap-2">
                        <a href="index.php?page=mes_rendez_vous" class="btn-action btn-retour"><i class="fas fa-arrow-left me-2"></i>Retour</a>
                        <?php if ($rdv['statut'] === 'en_attente'): ?>
                            <a href="index.php?page=confirmer_rendez_vous&id=<?= $rdv['id'] ?>" class="btn-action btn-confirmer" onclick="return confirm('Confirmer ce rendez-vous ?')"><i class="fas fa-check me-2"></i>Confirmer</a>
                        <?php endif; ?>
                        <?php if ($rdv['statut'] === 'confirmé'): ?>
                            <a href="index.php?page=terminer_rendez_vous&id=<?= $rdv['id'] ?>" class="btn-action btn-terminer" onclick="return confirm('Marquer comme terminé ?')"><i class="fas fa-check-double me-2"></i>Terminer</a>
                        <?php endif; ?>
                        <?php if ($rdv['statut'] !== 'annulé' && $rdv['statut'] !== 'terminé'): ?>
                            <a href="index.php?page=annuler_rendez_vous&id=<?= $rdv['id'] ?>" class="btn-action btn-annuler" onclick="return confirm('Annuler ce rendez-vous ?')"><i class="fas fa-times me-2"></i>Annuler</a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- SECTION ORDONNANCE -->
            <div class="card mt-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="fas fa-prescription-bottle me-2"></i>Ordonnance</h5>
                </div>
                <div class="card-body">
                    <?php if ($ordonnance): ?>
                        <!-- Affichage de l'ordonnance existante -->
                        <div class="ordonnance-card">
                            <div class="d-flex justify-content-between align-items-center">
                                <h6 class="mb-0"><i class="fas fa-file-prescription me-2"></i>Ordonnance n° <?= htmlspecialchars($ordonnance['numero_ordonnance'] ?? 'N/A') ?></h6>
                                <div class="btn-group">
                                    <button class="btn btn-sm btn-voir-ordo" onclick="showOrdonnance(<?= $ordonnance['id'] ?>)">
                                        <i class="fas fa-eye me-1"></i>Voir
                                    </button>
                                    <button class="btn btn-sm btn-modifier-ordo" onclick="editOrdonnance(<?= $ordonnance['id'] ?>)">
                                        <i class="fas fa-edit me-1"></i>Modifier
                                    </button>
                                    <button class="btn btn-sm btn-supprimer-ordo" onclick="deleteOrdonnance(<?= $ordonnance['id'] ?>, <?= $rdv['id'] ?>)">
                                        <i class="fas fa-trash me-1"></i>Supprimer
                                    </button>
                                </div>
                            </div>
                            <div class="ordonnance-content">
                                <p><strong>Diagnostic :</strong> <?= nl2br(htmlspecialchars(substr($ordonnance['diagnostic'] ?? '', 0, 100))) ?>...</p>
                                <p><strong>Date :</strong> <?= date('d/m/Y', strtotime($ordonnance['date_ordonnance'] ?? 'now')) ?></p>
                                <p><strong>Validité :</strong> <?= date('d/m/Y', strtotime($ordonnance['date_validite'] ?? '+1 year')) ?></p>
                            </div>
                        </div>
                    <?php else: ?>
                        <!-- Formulaire de création d'ordonnance -->
                        <form id="ordonnanceForm" method="POST" action="index.php?page=creer_ordonnance_rdv">
                            <input type="hidden" name="rdv_id" value="<?= $rdv['id'] ?>">
                            <input type="hidden" name="patient_id" value="<?= $rdv['patient_id'] ?>">
                            <input type="hidden" name="medecin_id" value="<?= $_SESSION['user_id'] ?>">
                            
                            <div class="mb-3">
                                <label class="form-label">Diagnostic <span class="text-danger">*</span></label>
                                <textarea name="diagnostic" class="form-control" rows="3" required placeholder="Description du diagnostic..."></textarea>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Prescription / Médicaments <span class="text-danger">*</span></label>
                                <textarea name="contenu" class="form-control" rows="5" required placeholder="Liste des médicaments..."></textarea>
                                <small class="text-muted">Séparez chaque médicament par une ligne</small>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Date de validité</label>
                                    <input type="date" name="date_validite" class="form-control" value="<?= date('Y-m-d', strtotime('+1 year')) ?>">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Numéro d'ordonnance</label>
                                    <input type="text" name="numero_ordonnance" class="form-control" value="ORD-<?= date('Ymd') ?>-<?= strtoupper(substr(uniqid(), -4)) ?>" readonly>
                                </div>
                            </div>
                            
                            <div class="d-flex justify-content-end">
                                <button type="submit" class="btn btn-ajouter-ordo"><i class="fas fa-save me-2"></i>Créer l'ordonnance</button>
                            </div>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Modal Voir Ordonnance -->
        <div id="viewOrdonnanceModal" class="modal">
            <div class="modal-content">
                <div class="modal-header">
                    <h5><i class="fas fa-file-prescription me-2"></i>Détail de l'ordonnance</h5>
                    <button class="close" onclick="closeModal('viewOrdonnanceModal')">&times;</button>
                </div>
                <div class="modal-body" id="viewOrdonnanceContent"></div>
                <div class="modal-footer">
                    <button class="btn btn-secondary" onclick="closeModal('viewOrdonnanceModal')">Fermer</button>
                    <button class="btn btn-warning" id="editFromViewBtn">Modifier</button>
                </div>
            </div>
        </div>
        
        <!-- Modal Modifier Ordonnance -->
        <div id="editOrdonnanceModal" class="modal">
            <div class="modal-content">
                <div class="modal-header">
                    <h5><i class="fas fa-edit me-2"></i>Modifier l'ordonnance</h5>
                    <button class="close" onclick="closeModal('editOrdonnanceModal')">&times;</button>
                </div>
                <form id="editOrdonnanceForm" method="POST" action="index.php?page=modifier_ordonnance_rdv">
                    <div class="modal-body">
                        <input type="hidden" name="ordonnance_id" id="edit_ordonnance_id">
                        <input type="hidden" name="rdv_id" value="<?= $rdv['id'] ?>">
                        
                        <div class="mb-3">
                            <label class="form-label">Diagnostic <span class="text-danger">*</span></label>
                            <textarea name="diagnostic" id="edit_diagnostic" class="form-control" rows="3" required></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Prescription / Médicaments <span class="text-danger">*</span></label>
                            <textarea name="contenu" id="edit_contenu" class="form-control" rows="5" required></textarea>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Date de validité</label>
                                <input type="date" name="date_validite" id="edit_date_validite" class="form-control">
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" onclick="closeModal('editOrdonnanceModal')">Annuler</button>
                        <button type="submit" class="btn btn-primary">Enregistrer</button>
                    </div>
                </form>
            </div>
        </div>
        
        <footer>
            <div class="container"><p>&copy; 2024 Valorys - Tous droits réservés</p><small>Plateforme médicale en ligne</small></div>
        </footer>
        
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
        <script>
        function showOrdonnance(id) {
            fetch('index.php?page=api_ordonnance&id=' + id)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        document.getElementById('viewOrdonnanceContent').innerHTML = `
                            <div class="mb-3"><strong>Numéro :</strong> ${data.ordonnance.numero_ordonnance || 'N/A'}</div>
                            <div class="mb-3"><strong>Diagnostic :</strong><br>${data.ordonnance.diagnostic || ''}</div>
                            <div class="mb-3"><strong>Prescription :</strong><br>${(data.ordonnance.contenu || '').replace(/\n/g, '<br>')}</div>
                            <div class="mb-3"><strong>Date de prescription :</strong> ${new Date(data.ordonnance.date_ordonnance).toLocaleDateString('fr-FR')}</div>
                            <div class="mb-3"><strong>Date de validité :</strong> ${data.ordonnance.date_validite ? new Date(data.ordonnance.date_validite).toLocaleDateString('fr-FR') : 'Non définie'}</div>
                        `;
                        document.getElementById('viewOrdonnanceModal').style.display = 'flex';
                        document.getElementById('editFromViewBtn').onclick = function() {
                            closeModal('viewOrdonnanceModal');
                            editOrdonnance(id);
                        };
                    } else {
                        alert('Erreur: ' + data.message);
                    }
                })
                .catch(error => alert('Erreur de chargement'));
        }
        
        function editOrdonnance(id) {
            fetch('index.php?page=api_ordonnance&id=' + id)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        document.getElementById('edit_ordonnance_id').value = data.ordonnance.id;
                        document.getElementById('edit_diagnostic').value = data.ordonnance.diagnostic || '';
                        document.getElementById('edit_contenu').value = data.ordonnance.contenu || '';
                        document.getElementById('edit_date_validite').value = data.ordonnance.date_validite || '';
                        document.getElementById('editOrdonnanceModal').style.display = 'flex';
                    } else {
                        alert('Erreur: ' + data.message);
                    }
                });
        }
        
        function deleteOrdonnance(ordonnanceId, rdvId) {
            if (confirm('Êtes-vous sûr de vouloir supprimer cette ordonnance ?\n\n⚠️ Cette action est irréversible.')) {
                window.location.href = 'index.php?page=supprimer_ordonnance_rdv&id=' + ordonnanceId + '&rdv_id=' + rdvId;
            }
        }
        
        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
        }
        
        window.onclick = function(event) {
            if (event.target.classList.contains('modal')) {
                event.target.style.display = 'none';
            }
        };
        </script>
    </body>
    </html>
    <?php
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

    // ... le reste de vos méthodes ...


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