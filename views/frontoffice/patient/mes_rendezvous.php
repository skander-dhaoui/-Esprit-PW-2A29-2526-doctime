<?php
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'patient') {
    header('Location: index.php?page=login');
    exit;
}

// Les variables $rdvs et $upcoming sont déjà définies par le contrôleur
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mes rendez-vous - Valorys</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background: #f5f7fb; font-family: 'Segoe UI', sans-serif; }
        .navbar { background: #1a2035; }
        .navbar-brand { color: white !important; }
        .nav-link { color: rgba(255,255,255,0.8) !important; }
        .nav-link:hover { color: white !important; }
        .card { border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); margin-bottom: 20px; }
        .card-header { background: linear-gradient(135deg, #2A7FAA, #4CAF50); color: white; border-radius: 12px 12px 0 0 !important; }
        .badge-confirme { background: #d4edda; color: #155724; padding: 5px 12px; border-radius: 20px; font-size: 12px; }
        .badge-attente { background: #fff3cd; color: #856404; padding: 5px 12px; border-radius: 20px; font-size: 12px; }
        .badge-termine { background: #cfe2ff; color: #084298; padding: 5px 12px; border-radius: 20px; font-size: 12px; }
        .badge-annule { background: #f8d7da; color: #721c24; padding: 5px 12px; border-radius: 20px; font-size: 12px; }
        .btn-cancel { background: #dc3545; color: white; border: none; padding: 5px 15px; border-radius: 20px; font-size: 12px; }
        .btn-cancel:hover { background: #c82333; }
        .btn-edit { background: #ffc107; color: #333; border: none; padding: 5px 15px; border-radius: 20px; font-size: 12px; margin-right: 5px; }
        .btn-edit:hover { background: #e0a800; }
        .btn-primary-custom { background: linear-gradient(135deg, #2A7FAA, #4CAF50); color: white; border: none; padding: 8px 20px; border-radius: 25px; }
        .btn-primary-custom:hover { opacity: 0.9; }
        .modal-header { background: linear-gradient(135deg, #2A7FAA, #4CAF50); color: white; }
        .btn-save { background: #4CAF50; color: white; border: none; padding: 8px 20px; border-radius: 20px; }
        .btn-save:hover { background: #45a049; }
        footer { background: #1a2035; color: white; text-align: center; padding: 30px; margin-top: 50px; }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark">
    <div class="container">
        <a class="navbar-brand" href="index.php?page=accueil"><i class="fas fa-stethoscope me-2"></i>Valorys</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item"><a class="nav-link" href="index.php?page=accueil">Accueil</a></li>
                <li class="nav-item"><a class="nav-link" href="index.php?page=medecins">Médecins</a></li>
                <li class="nav-item"><a class="nav-link active" href="index.php?page=mes_rendezvous">Mes RDV</a></li>
                <li class="nav-item"><a class="nav-link" href="index.php?page=mes_ordonnances">Ordonnances</a></li>
                <li class="nav-item"><a class="nav-link" href="index.php?page=mon_profil">Profil</a></li>
                <li class="nav-item"><a class="nav-link" href="index.php?page=logout">Déconnexion</a></li>
            </ul>
        </div>
    </div>
</nav>

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="fas fa-calendar-check me-2"></i>Mes rendez-vous</h2>
        <a href="index.php?page=prendre_rendezvous" class="btn-primary-custom">
            <i class="fas fa-plus me-2"></i>Prendre un rendez-vous
        </a>
    </div>

    <?php if (isset($_SESSION['flash'])): ?>
        <div class="alert alert-<?= $_SESSION['flash']['type'] === 'error' ? 'danger' : 'success' ?> alert-dismissible fade show">
            <?= $_SESSION['flash']['message'] ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['flash']); ?>
    <?php endif; ?>

    <!-- Prochains rendez-vous -->
    <?php if (!empty($upcoming)): ?>
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0"><i class="fas fa-clock me-2"></i>Prochains rendez-vous</h5>
        </div>
        <div class="card-body">
            <?php foreach ($upcoming as $rdv): ?>
            <div class="border-bottom pb-3 mb-3">
                <div class="row align-items-center">
                    <div class="col-md-4">
                        <strong>Dr. <?= htmlspecialchars($rdv['medecin_prenom'] . ' ' . $rdv['medecin_nom']) ?></strong><br>
                        <small class="text-muted"><?= htmlspecialchars($rdv['specialite'] ?? 'Généraliste') ?></small>
                    </div>
                    <div class="col-md-3">
                        <i class="fas fa-calendar me-2"></i><?= date('d/m/Y', strtotime($rdv['date_rendezvous'])) ?><br>
                        <i class="fas fa-clock me-2"></i><?= $rdv['heure_rendezvous'] ?>
                    </div>
                    <div class="col-md-3">
                        <span class="badge-<?= $rdv['statut'] === 'confirmé' ? 'confirme' : 'attente' ?>">
                            <?= $rdv['statut'] === 'confirmé' ? 'Confirmé' : 'En attente' ?>
                        </span>
                    </div>
                    <div class="col-md-2 text-end">
                        <div class="d-flex gap-2 justify-content-end">
                            <button class="btn-edit" onclick="openEditModal(<?= $rdv['id'] ?>, '<?= $rdv['date_rendezvous'] ?>', '<?= $rdv['heure_rendezvous'] ?>', '<?= addslashes($rdv['motif'] ?? '') ?>', <?= $rdv['medecin_id'] ?>)">
                                <i class="fas fa-edit me-1"></i>Modifier
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Historique -->
    <div class="card">
        <div class="card-header bg-secondary text-white">
            <h5 class="mb-0"><i class="fas fa-history me-2"></i>Historique des rendez-vous</h5>
        </div>
        <div class="card-body">
            <?php if (empty($rdvs)): ?>
                <p class="text-muted text-center py-3">Aucun rendez-vous trouvé.</p>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead class="table-light">
                            <tr>
                                <th>Date</th>
                                <th>Heure</th>
                                <th>Médecin</th>
                                <th>Spécialité</th>
                                <th>Motif</th>
                                <th>Statut</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($rdvs as $rdv): ?>
                            <tr>
                                <td><?= date('d/m/Y', strtotime($rdv['date_rendezvous'])) ?></td>
                                <td><?= $rdv['heure_rendezvous'] ?></td>
                                <td>Dr. <?= htmlspecialchars($rdv['medecin_prenom'] . ' ' . $rdv['medecin_nom']) ?></td>
                                <td><?= htmlspecialchars($rdv['specialite'] ?? '-') ?></td>
                                <td><?= htmlspecialchars(substr($rdv['motif'] ?? '', 0, 30)) ?>...</div>
                                <td>
                                    <?php
                                    $badgeClass = match($rdv['statut']) {
                                        'confirmé' => 'badge-confirme',
                                        'terminé' => 'badge-termine',
                                        'annulé' => 'badge-annule',
                                        default => 'badge-attente'
                                    };
                                    ?>
                                    <span class="<?= $badgeClass ?>"><?= $rdv['statut'] ?></span>
                                 </div>
                             </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- MODAL MODIFIER RENDEZ-VOUS -->
<div class="modal fade" id="editRdvModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-edit me-2"></i>Modifier le rendez-vous</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="" id="editRdvForm">
                <div class="modal-body">
                    <input type="hidden" name="rdv_id" id="edit_rdv_id">
                    <div class="mb-3">
                        <label class="form-label">Date</label>
                        <input type="date" name="date_rendezvous" id="edit_date" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Heure</label>
                        <select name="heure_rendezvous" id="edit_heure" class="form-select" required>
                            <option value="">Sélectionner une heure</option>
                            <option value="08:00">08:00</option>
                            <option value="09:00">09:00</option>
                            <option value="10:00">10:00</option>
                            <option value="11:00">11:00</option>
                            <option value="14:00">14:00</option>
                            <option value="15:00">15:00</option>
                            <option value="16:00">16:00</option>
                            <option value="17:00">17:00</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Motif</label>
                        <textarea name="motif" id="edit_motif" class="form-control" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn-save">Enregistrer</button>
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
function openEditModal(id, date, heure, motif, medecinId) {
    document.getElementById('edit_rdv_id').value = id;
    document.getElementById('edit_date').value = date;
    document.getElementById('edit_motif').value = motif;
    
    // Sélectionner l'heure dans le select
    const heureSelect = document.getElementById('edit_heure');
    for (let i = 0; i < heureSelect.options.length; i++) {
        if (heureSelect.options[i].value === heure) {
            heureSelect.options[i].selected = true;
            break;
        }
    }
    
    // Définir l'action du formulaire
    document.getElementById('editRdvForm').action = 'index.php?page=modifier_rendezvous&id=' + id;
    
    // Ouvrir la modale
    new bootstrap.Modal(document.getElementById('editRdvModal')).show();
}
</script>
</body>
</html>