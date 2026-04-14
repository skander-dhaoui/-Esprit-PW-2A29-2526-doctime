<?php
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'medecin') {
    header('Location: index.php?page=login');
    exit;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mes rendez-vous - Espace Médecin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background: #f5f7fb; font-family: 'Segoe UI', sans-serif; }
        .navbar { background: #1a2035; }
        .navbar-brand, .nav-link { color: white !important; }
        .card { border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); margin-bottom: 20px; }
        .card-header { background: linear-gradient(135deg, #2A7FAA, #4CAF50); color: white; border-radius: 12px 12px 0 0 !important; }
        .badge-confirme { background: #d4edda; color: #155724; padding: 5px 12px; border-radius: 20px; font-size: 12px; }
        .badge-attente  { background: #fff3cd; color: #856404; padding: 5px 12px; border-radius: 20px; font-size: 12px; }
        .badge-termine  { background: #cfe2ff; color: #084298; padding: 5px 12px; border-radius: 20px; font-size: 12px; }
        .badge-annule   { background: #f8d7da; color: #721c24; padding: 5px 12px; border-radius: 20px; font-size: 12px; }
        .btn-sm { margin: 2px; }
        .stat-card { background: white; border-radius: 10px; padding: 15px; text-align: center; box-shadow: 0 2px 8px rgba(0,0,0,0.05); }
        .stat-number { font-size: 28px; font-weight: bold; color: #2A7FAA; }
        .action-buttons { display: flex; gap: 5px; flex-wrap: wrap; }
        footer { background: #1a2035; color: white; text-align: center; padding: 30px; margin-top: 50px; }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark">
    <div class="container">
        <a class="navbar-brand" href="index.php?page=accueil"><i class="fas fa-stethoscope me-2"></i>Valorys</a>
        <div class="collapse navbar-collapse">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item"><a class="nav-link" href="index.php?page=accueil">Accueil</a></li>
                <li class="nav-item"><a class="nav-link active" href="index.php?page=medecin_rendezvous">Mes RDV</a></li>
                <li class="nav-item"><a class="nav-link" href="index.php?page=medecin_disponibilites">Disponibilités</a></li>
                <li class="nav-item"><a class="nav-link" href="index.php?page=medecin_ordonnances">Ordonnances</a></li>
                <li class="nav-item"><a class="nav-link" href="index.php?page=mon_profil">Profil</a></li>
                <li class="nav-item"><a class="nav-link" href="index.php?page=logout">Déconnexion</a></li>
            </ul>
        </div>
    </div>
</nav>

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="fas fa-calendar-check me-2"></i>Mes rendez-vous</h2>
        <!-- ✅ FIX: Bouton appelle correctement la fonction -->
        <button type="button" class="btn btn-primary" onclick="openCreateRdvModal()">
            <i class="fas fa-plus me-2"></i>Nouveau rendez-vous
        </button>
    </div>
    <p class="text-muted mb-4">Gérez vos consultations et rendez-vous patients.</p>

    <!-- Statistiques -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="stat-card">
                <i class="fas fa-calendar-alt fa-2x text-primary mb-2"></i>
                <div class="stat-number"><?= $stats['today'] ?? 0 ?></div>
                <p class="text-muted mb-0">Aujourd'hui</p>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <i class="fas fa-clock fa-2x text-warning mb-2"></i>
                <div class="stat-number"><?= $stats['upcoming'] ?? 0 ?></div>
                <p class="text-muted mb-0">À venir</p>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <i class="fas fa-check-circle fa-2x text-success mb-2"></i>
                <div class="stat-number"><?= $stats['completed'] ?? 0 ?></div>
                <p class="text-muted mb-0">Terminés</p>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <i class="fas fa-chart-line fa-2x text-info mb-2"></i>
                <div class="stat-number"><?= $stats['total'] ?? 0 ?></div>
                <p class="text-muted mb-0">Total</p>
            </div>
        </div>
    </div>

    <?php if (isset($_SESSION['flash'])): ?>
        <div class="alert alert-<?= $_SESSION['flash']['type'] === 'error' ? 'danger' : 'success' ?> alert-dismissible fade show">
            <?= $_SESSION['flash']['message'] ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['flash']); ?>
    <?php endif; ?>

    <!-- Aujourd'hui -->
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0"><i class="fas fa-calendar-day me-2"></i>Aujourd'hui (<?= date('d/m/Y') ?>)</h5>
        </div>
        <div class="card-body">
            <?php if (empty($todayRdv)): ?>
                <p class="text-muted text-center py-3">Aucun rendez-vous aujourd'hui.</p>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead class="table-light">
                            <tr><th>Heure</th><th>Patient</th><th>Contact</th><th>Motif</th><th>Statut</th><th>Actions</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach ($todayRdv as $rdv): ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($rdv['heure_rendezvous']) ?></strong></td>
                                <td><?= htmlspecialchars($rdv['patient_prenom'] . ' ' . $rdv['patient_nom']) ?></td>
                                <td>
                                    <i class="fas fa-phone me-1"></i><?= htmlspecialchars($rdv['patient_telephone'] ?? '-') ?><br>
                                    <i class="fas fa-envelope me-1"></i><?= htmlspecialchars($rdv['patient_email'] ?? '-') ?>
                                </td>
                                <td><?= htmlspecialchars(substr($rdv['motif'] ?? '', 0, 30)) ?><?= strlen($rdv['motif'] ?? '') > 30 ? '...' : '' ?></td>
                                <td>
                                    <?php
                                    $badgeClass = match($rdv['statut']) {
                                        'confirmé'   => 'badge-confirme',
                                        'en_attente' => 'badge-attente',
                                        'terminé'    => 'badge-termine',
                                        'annulé'     => 'badge-annule',
                                        default      => 'badge-attente'
                                    };
                                    ?>
                                    <span class="<?= $badgeClass ?>"><?= htmlspecialchars($rdv['statut']) ?></span>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <a href="index.php?page=medecin_rendezvous&action=edit&id=<?= $rdv['id'] ?>" class="btn btn-sm btn-warning" title="Modifier">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="index.php?page=medecin_rendezvous&action=delete&id=<?= $rdv['id'] ?>" class="btn btn-sm btn-danger" title="Supprimer" onclick="return confirm('Supprimer ce rendez-vous ?')">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                        <?php if ($rdv['statut'] === 'en_attente'): ?>
                                            <a href="index.php?page=medecin_rendezvous&action=confirm&id=<?= $rdv['id'] ?>" class="btn btn-sm btn-success" title="Confirmer" onclick="return confirm('Confirmer ce rendez-vous ?')">
                                                <i class="fas fa-check"></i>
                                            </a>
                                        <?php endif; ?>
                                        <?php if ($rdv['statut'] === 'confirmé'): ?>
                                            <button type="button" class="btn btn-sm btn-info" title="Terminer" data-bs-toggle="modal" data-bs-target="#completeModal<?= $rdv['id'] ?>">
                                                <i class="fas fa-check-double"></i>
                                            </button>
                                        <?php endif; ?>
                                        <?php if (in_array($rdv['statut'], ['en_attente', 'confirmé'])): ?>
                                            <a href="index.php?page=medecin_rendezvous&action=cancel&id=<?= $rdv['id'] ?>" class="btn btn-sm btn-secondary" title="Annuler" onclick="return confirm('Annuler ce rendez-vous ?')">
                                                <i class="fas fa-times"></i>
                                            </a>
                                        <?php endif; ?>
                                        <a href="index.php?page=medecin_ordonnances&action=create&rdv_id=<?= $rdv['id'] ?>" class="btn btn-sm btn-primary" title="Ordonnance">
                                            <i class="fas fa-prescription-bottle"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>

                            <!-- Modal pour terminer le rendez-vous avec note -->
                            <div class="modal fade" id="completeModal<?= $rdv['id'] ?>" tabindex="-1">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <div class="modal-header bg-info text-white">
                                            <h5 class="modal-title">Terminer la consultation</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <form method="POST" action="index.php?page=medecin_rendezvous&action=complete&id=<?= $rdv['id'] ?>">
                                            <div class="modal-body">
                                                <div class="mb-3">
                                                    <label class="form-label">Patient</label>
                                                    <input type="text" class="form-control" value="<?= htmlspecialchars($rdv['patient_prenom'] . ' ' . $rdv['patient_nom']) ?>" disabled>
                                                </div>
                                                <div class="mb-3">
                                                    <label class="form-label">Notes / Observations</label>
                                                    <textarea name="notes" class="form-control" rows="4" placeholder="Ajoutez des notes sur la consultation..."></textarea>
                                                </div>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                                                <button type="submit" class="btn btn-info text-white">Terminer</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Prochains rendez-vous -->
    <div class="card">
        <div class="card-header bg-secondary text-white" style="background: linear-gradient(135deg,#6c757d,#495057) !important;">
            <h5 class="mb-0"><i class="fas fa-clock me-2"></i>Prochains rendez-vous</h5>
        </div>
        <div class="card-body">
            <?php if (empty($upcomingRdv)): ?>
                <p class="text-muted text-center py-3">Aucun prochain rendez-vous.</p>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead class="table-light">
                            <tr><th>Date</th><th>Heure</th><th>Patient</th><th>Motif</th><th>Statut</th><th>Actions</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach ($upcomingRdv as $rdv): ?>
                            <tr>
                                <td><?= date('d/m/Y', strtotime($rdv['date_rendezvous'])) ?></td>
                                <td><?= htmlspecialchars($rdv['heure_rendezvous']) ?></td>
                                <td><?= htmlspecialchars($rdv['patient_prenom'] . ' ' . $rdv['patient_nom']) ?></td>
                                <td><?= htmlspecialchars(substr($rdv['motif'] ?? '', 0, 30)) ?><?= strlen($rdv['motif'] ?? '') > 30 ? '...' : '' ?></td>
                                <td>
                                    <?php
                                    $badgeClass = match($rdv['statut']) {
                                        'confirmé'   => 'badge-confirme',
                                        'en_attente' => 'badge-attente',
                                        'terminé'    => 'badge-termine',
                                        'annulé'     => 'badge-annule',
                                        default      => 'badge-attente'
                                    };
                                    ?>
                                    <span class="<?= $badgeClass ?>"><?= htmlspecialchars($rdv['statut']) ?></span>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <a href="index.php?page=medecin_rendezvous&action=edit&id=<?= $rdv['id'] ?>" class="btn btn-sm btn-warning" title="Modifier">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="index.php?page=medecin_rendezvous&action=delete&id=<?= $rdv['id'] ?>" class="btn btn-sm btn-danger" title="Supprimer" onclick="return confirm('Supprimer ce rendez-vous ?')">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                        <?php if ($rdv['statut'] === 'en_attente'): ?>
                                            <a href="index.php?page=medecin_rendezvous&action=confirm&id=<?= $rdv['id'] ?>" class="btn btn-sm btn-success" title="Confirmer" onclick="return confirm('Confirmer ce rendez-vous ?')">
                                                <i class="fas fa-check"></i>
                                            </a>
                                        <?php endif; ?>
                                        <?php if (in_array($rdv['statut'], ['en_attente', 'confirmé'])): ?>
                                            <a href="index.php?page=medecin_rendezvous&action=cancel&id=<?= $rdv['id'] ?>" class="btn btn-sm btn-secondary" title="Annuler" onclick="return confirm('Annuler ce rendez-vous ?')">
                                                <i class="fas fa-times"></i>
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Historique -->
    <div class="card">
        <div class="card-header bg-dark text-white" style="background: #1a2035 !important; border-radius: 12px 12px 0 0 !important;">
            <h5 class="mb-0"><i class="fas fa-history me-2"></i>Historique des consultations</h5>
        </div>
        <div class="card-body">
            <?php if (empty($historyRdv)): ?>
                <p class="text-muted text-center py-3">Aucun historique.</p>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead class="table-light">
                            <tr><th>Date</th><th>Heure</th><th>Patient</th><th>Motif</th><th>Statut</th><th>Actions</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach ($historyRdv as $rdv): ?>
                            <tr>
                                <td><?= date('d/m/Y', strtotime($rdv['date_rendezvous'])) ?></td>
                                <td><?= htmlspecialchars($rdv['heure_rendezvous']) ?></td>
                                <td><?= htmlspecialchars($rdv['patient_prenom'] . ' ' . $rdv['patient_nom']) ?></td>
                                <td><?= htmlspecialchars(substr($rdv['motif'] ?? '', 0, 30)) ?><?= strlen($rdv['motif'] ?? '') > 30 ? '...' : '' ?></td>
                                <td>
                                    <span class="badge-termine"><?= $rdv['statut'] === 'terminé' ? 'Terminé' : htmlspecialchars($rdv['statut']) ?></span>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <button type="button" class="btn btn-sm btn-info" data-bs-toggle="modal" data-bs-target="#notesModal<?= $rdv['id'] ?>" title="Voir notes">
                                            <i class="fas fa-sticky-note"></i>
                                        </button>
                                        <a href="index.php?page=medecin_rendezvous&action=edit&id=<?= $rdv['id'] ?>" class="btn btn-sm btn-warning" title="Modifier">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="index.php?page=medecin_rendezvous&action=delete&id=<?= $rdv['id'] ?>" class="btn btn-sm btn-danger" title="Supprimer" onclick="return confirm('Supprimer ce rendez-vous ?')">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>

                            <!-- Modal Notes -->
                            <div class="modal fade" id="notesModal<?= $rdv['id'] ?>" tabindex="-1">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <div class="modal-header bg-info text-white">
                                            <h5 class="modal-title">Notes de consultation</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <div class="modal-body">
                                            <p><strong>Patient :</strong> <?= htmlspecialchars($rdv['patient_prenom'] . ' ' . $rdv['patient_nom']) ?></p>
                                            <p><strong>Date :</strong> <?= date('d/m/Y', strtotime($rdv['date_rendezvous'])) ?> à <?= htmlspecialchars($rdv['heure_rendezvous']) ?></p>
                                            <hr>
                                            <h6>Notes du médecin :</h6>
                                            <p><?= nl2br(htmlspecialchars($rdv['notes_medecin'] ?? 'Aucune note')) ?></p>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
                                            <a href="index.php?page=medecin_rendezvous&action=note&id=<?= $rdv['id'] ?>" class="btn btn-primary">Ajouter/Modifier</a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- ✅ Modal CRÉER RENDEZ-VOUS — IDs uniques préfixés "create_" -->
<div class="modal fade" id="createRdvModal" tabindex="-1" aria-labelledby="createRdvModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header" style="background: linear-gradient(135deg,#2A7FAA,#4CAF50); color:white;">
                <h5 class="modal-title" id="createRdvModalLabel"><i class="fas fa-plus me-2"></i>Nouveau rendez-vous</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="index.php?page=medecin_rendezvous&action=store">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Patient *</label>
                        <!-- ✅ FIX: $patients est passé par le contrôleur, pas de require_once ici -->
                        <select name="patient_id" id="create_patient_id" class="form-select" required>
                            <option value="">Sélectionner un patient</option>
                            <?php if (!empty($patients)): ?>
                                <?php foreach ($patients as $patient): ?>
                                    <option value="<?= $patient['id'] ?>">
                                        <?= htmlspecialchars($patient['prenom'] . ' ' . $patient['nom']) ?> (<?= htmlspecialchars($patient['email']) ?>)
                                    </option>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <option disabled>Aucun patient trouvé</option>
                            <?php endif; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Date *</label>
                        <!-- ✅ FIX: ID unique "create_date_rdv" -->
                        <input type="date" name="date_rendezvous" id="create_date_rdv" class="form-control"
                               min="<?= date('Y-m-d') ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Heure *</label>
                        <!-- ✅ FIX: ID unique "create_heure_rdv" -->
                        <select name="heure_rendezvous" id="create_heure_rdv" class="form-select" required>
                            <option value="">Sélectionnez d'abord une date</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Motif</label>
                        <textarea name="motif" class="form-control" rows="3" placeholder="Motif de la consultation..."></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Statut</label>
                        <select name="statut" class="form-select">
                            <option value="en_attente">En attente</option>
                            <option value="confirmé">Confirmé</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary" style="background:linear-gradient(135deg,#2A7FAA,#4CAF50);border:none;">
                        <i class="fas fa-save me-1"></i>Créer
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal MODIFIER RENDEZ-VOUS -->
<div class="modal fade" id="editRdvModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-warning text-dark">
                <h5 class="modal-title"><i class="fas fa-edit me-2"></i>Modifier le rendez-vous</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" id="editRdvForm">
                <div class="modal-body">
                    <input type="hidden" name="rdv_id" id="edit_rdv_id">
                    <div class="mb-3">
                        <label class="form-label">Patient</label>
                        <select name="patient_id" id="edit_patient_id" class="form-select" required>
                            <option value="">Sélectionner un patient</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Date</label>
                        <input type="date" name="date_rendezvous" id="edit_date_rdv" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Heure</label>
                        <select name="heure_rendezvous" id="edit_heure_rdv" class="form-select" required>
                            <option value="">Sélectionner une heure</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Motif</label>
                        <textarea name="motif" id="edit_motif" class="form-control" rows="3"></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Statut</label>
                        <select name="statut" id="edit_statut" class="form-select">
                            <option value="en_attente">En attente</option>
                            <option value="confirmé">Confirmé</option>
                            <option value="terminé">Terminé</option>
                            <option value="annulé">Annulé</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-warning">Enregistrer</button>
                </div>
            </form>
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
<script>
    // ─── Données médecin & patients (injectées depuis PHP) ─────
    const MEDECIN_ID   = <?= (int)$_SESSION['user_id'] ?>;
    const patientsList = <?= json_encode($patients ?? []) ?>;

    // ─── Utilitaire : charger les créneaux via l'API ──────────
    function loadSlots(medecinId, date, selectEl, currentHeure = '') {
        selectEl.innerHTML = '<option value="">Chargement...</option>';
        selectEl.disabled  = true;

        fetch(`index.php?page=api_slots&medecin_id=${medecinId}&date=${date}`)
            .then(res => res.json())
            .then(data => {
                selectEl.innerHTML = '<option value="">Sélectionner une heure</option>';
                selectEl.disabled  = false;

                if (data.slots && data.slots.length > 0) {
                    data.slots.forEach(slot => {
                        const opt      = document.createElement('option');
                        opt.value      = slot;
                        opt.textContent = slot;
                        if (slot === currentHeure) opt.selected = true;
                        selectEl.appendChild(opt);
                    });
                } else {
                    const opt       = document.createElement('option');
                    opt.textContent = 'Aucun créneau disponible';
                    opt.disabled    = true;
                    selectEl.appendChild(opt);
                }
            })
            .catch(() => {
                selectEl.innerHTML = '<option disabled>Erreur de chargement</option>';
                selectEl.disabled  = false;
            });
    }

    // ─── MODAL CRÉER ──────────────────────────────────────────
    // ✅ FIX: Utilise les IDs uniques create_date_rdv / create_heure_rdv
    const createDateInput  = document.getElementById('create_date_rdv');
    const createHeureSelect = document.getElementById('create_heure_rdv');

    createDateInput.addEventListener('change', function () {
        if (this.value) {
            loadSlots(MEDECIN_ID, this.value, createHeureSelect);
        } else {
            createHeureSelect.innerHTML = '<option value="">Sélectionnez d\'abord une date</option>';
        }
    });

    // ✅ FIX: Ouvrir le modal de création (reset propre)
    function openCreateRdvModal() {
        document.getElementById('createRdvModal').querySelector('form').reset();
        createHeureSelect.innerHTML = '<option value="">Sélectionnez d\'abord une date</option>';
        createHeureSelect.disabled  = false;

        const modal = new bootstrap.Modal(document.getElementById('createRdvModal'));
        modal.show();
    }

    // ─── MODAL MODIFIER ───────────────────────────────────────
    // Pré-remplir la liste des patients dans le modal d'édition
    const patientSelectEdit = document.getElementById('edit_patient_id');
    if (patientSelectEdit && patientsList.length > 0) {
        patientsList.forEach(p => {
            const opt       = document.createElement('option');
            opt.value       = p.id;
            opt.textContent = `${p.prenom} ${p.nom} (${p.email})`;
            patientSelectEdit.appendChild(opt);
        });
    }

    function openEditModal(id, patientId, date, heure, motif, statut) {
        document.getElementById('edit_rdv_id').value   = id;
        document.getElementById('edit_date_rdv').value = date;
        document.getElementById('edit_motif').value    = motif;
        document.getElementById('edit_statut').value   = statut;

        // Sélectionner le bon patient
        document.getElementById('edit_patient_id').value = patientId;

        // Charger les créneaux et pré-sélectionner l'heure courante
        loadSlots(MEDECIN_ID, date, document.getElementById('edit_heure_rdv'), heure);

        // Recharger les créneaux si la date change
        document.getElementById('edit_date_rdv').addEventListener('change', function () {
            loadSlots(MEDECIN_ID, this.value, document.getElementById('edit_heure_rdv'));
        }, { once: true });

        document.getElementById('editRdvForm').action =
            `index.php?page=medecin_rendezvous&action=update&id=${id}`;

        new bootstrap.Modal(document.getElementById('editRdvModal')).show();
    }
</script>
</body>
</html>