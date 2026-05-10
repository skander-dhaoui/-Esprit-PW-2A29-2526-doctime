<?php
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: index.php?page=login');
    exit;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des rendez-vous - Valorys</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <?php require_once __DIR__ . '/../../partials/backoffice_shell_styles.php'; ?>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body.bo-shell-body { background: #f4f6f9; font-family: 'Segoe UI', sans-serif; }
        .navbar-top { background: white; border-radius: 12px; padding: 15px 25px; margin-bottom: 25px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px; }
        .admin-info { display: flex; align-items: center; gap: 15px; }
        .admin-avatar { width: 45px; height: 45px; background: #4CAF50; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-weight: bold; font-size: 18px; cursor: pointer; }
        .stat-card { background: white; border-radius: 15px; padding: 20px; margin-bottom: 20px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); transition: transform 0.3s; border-left: 4px solid; }
        .stat-card:hover { transform: translateY(-5px); }
        .stat-card h3 { font-size: 32px; margin: 10px 0 5px; font-weight: bold; }
        .stat-card p { color: #666; margin: 0; }
        .stat-icon { font-size: 45px; opacity: 0.3; float: right; }
        .recent-table { background: white; border-radius: 15px; padding: 20px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
        .recent-table h5 { margin-bottom: 20px; color: #1e2a3e; }
        .badge-confirme { background: #d4edda; color: #155724; padding: 5px 12px; border-radius: 20px; font-size: 12px; }
        .badge-attente { background: #fff3cd; color: #856404; padding: 5px 12px; border-radius: 20px; font-size: 12px; }
        .badge-termine { background: #cfe2ff; color: #084298; padding: 5px 12px; border-radius: 20px; font-size: 12px; }
        .badge-annule { background: #f8d7da; color: #721c24; padding: 5px 12px; border-radius: 20px; font-size: 12px; }
        .btn-action { padding: 5px 10px; margin: 2px; border-radius: 5px; }
        .chart-container { background: white; border-radius: 15px; padding: 20px; margin-bottom: 20px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
    </style>
</head>
<body class="bo-shell-body">
<?php require_once __DIR__ . '/../sidebar.php'; ?>

<div class="main-content">
    <!-- Navbar top -->
    <div class="navbar-top">
        <div style="display:flex;align-items:center;gap:10px;">
            <strong style="color:#1e2a3e;font-size:1.05rem;"><i class="fas fa-calendar-check me-2" style="color:#4CAF50"></i> Rendez-vous</strong>
        </div>
        <div class="admin-info">
            <a href="index.php?page=mes_notifications" style="color:#1e2a3e;">
                <i class="fas fa-bell"></i>
            </a>
            <a href="index.php?page=profil" style="text-decoration:none;">
                <div class="admin-avatar" title="Mon profil">
                    <?= strtoupper(substr($_SESSION['user_name'] ?? 'A', 0, 1)) ?>
                </div>
            </a>
            <span><?= htmlspecialchars($_SESSION['user_name'] ?? 'Admin') ?></span>
        </div>
    </div>

    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="fas fa-calendar-check me-2"></i>Gestion des rendez-vous</h2>
        <a href="index.php?page=admin_rendezvous&action=create" class="btn btn-success">
            <i class="fas fa-plus me-2"></i>Nouveau rendez-vous
        </a>
    </div>

    <!-- Statistiques -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="stat-card" style="border-left-color: #4CAF50;">
                <i class="fas fa-calendar-check stat-icon"></i>
                <p>Total RDV</p>
                <h3><?= $stats['total'] ?? 0 ?></h3>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card" style="border-left-color: #ffc107;">
                <i class="fas fa-clock stat-icon"></i>
                <p>En attente</p>
                <h3><?= $stats['en_attente'] ?? 0 ?></h3>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card" style="border-left-color: #2A7FAA;">
                <i class="fas fa-check-circle stat-icon"></i>
                <p>Confirmés</p>
                <h3><?= $stats['confirmes'] ?? 0 ?></h3>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card" style="border-left-color: #6c757d;">
                <i class="fas fa-check-double stat-icon"></i>
                <p>Terminés</p>
                <h3><?= $stats['termines'] ?? 0 ?></h3>
            </div>
        </div>
    </div>

    <!-- Filtres -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <input type="hidden" name="page" value="admin_rendezvous">
                <div class="col-md-3">
                    <input type="date" name="date" class="form-control" value="<?= $_GET['date'] ?? '' ?>" placeholder="Date">
                </div>
                <div class="col-md-3">
                    <select name="statut" class="form-select">
                        <option value="">Tous les statuts</option>
                        <option value="en_attente" <?= ($_GET['statut'] ?? '') === 'en_attente' ? 'selected' : '' ?>>En attente</option>
                        <option value="confirmé" <?= ($_GET['statut'] ?? '') === 'confirmé' ? 'selected' : '' ?>>Confirmé</option>
                        <option value="terminé" <?= ($_GET['statut'] ?? '') === 'terminé' ? 'selected' : '' ?>>Terminé</option>
                        <option value="annulé" <?= ($_GET['statut'] ?? '') === 'annulé' ? 'selected' : '' ?>>Annulé</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <input type="text" name="search" class="form-control" placeholder="Rechercher..." value="<?= htmlspecialchars($_GET['search'] ?? '') ?>">
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100">Filtrer</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Tableau -->
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table id="rdvTable" class="table table-hover">
                    <thead>
                        <tr>
                            <th>Patient</th>
                            <th>Médecin</th>
                            <th>Spécialité</th>
                            <th>Date</th>
                            <th>Heure</th>
                            <th>Motif</th>
                            <th>Statut</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($rdvs)): ?>
                            <?php foreach ($rdvs as $rdv): ?>
                            <tr>
                                <td><?= htmlspecialchars($rdv['patient_prenom'] . ' ' . $rdv['patient_nom']) ?></td>
                                <td>Dr. <?= htmlspecialchars($rdv['medecin_prenom'] . ' ' . $rdv['medecin_nom']) ?></td>
                                <td><?= htmlspecialchars($rdv['specialite'] ?? '-') ?></td>
                                <td><?= date('d/m/Y', strtotime($rdv['date_rendezvous'])) ?></td>
                                <td><?= $rdv['heure_rendezvous'] ?></td>
                                <td><?= htmlspecialchars(substr($rdv['motif'] ?? '', 0, 30)) ?><?= strlen($rdv['motif'] ?? '') > 30 ? '…' : '' ?></td>
                                <td>
                                    <?php
                                    $badgeClass = match($rdv['statut']) {
                                        'confirmé' => 'badge-confirme',
                                        'en_attente' => 'badge-attente',
                                        'terminé' => 'badge-termine',
                                        'annulé' => 'badge-annule',
                                        default => 'badge-secondary'
                                    };
                                    ?>
                                    <span class="badge <?= $badgeClass ?> px-3 py-2"><?= htmlspecialchars($rdv['statut']) ?></span>
                                </td>
                                <td>
                                    <a href="index.php?page=admin_rendezvous&action=show&id=<?= $rdv['id'] ?>" class="btn btn-sm btn-info" title="Voir"><i class="fas fa-eye"></i></a>
                                    <a href="index.php?page=admin_rendezvous&action=edit&id=<?= $rdv['id'] ?>" class="btn btn-sm btn-warning" title="Modifier"><i class="fas fa-edit"></i></a>
                                    <a href="index.php?page=admin_rendezvous&action=delete&id=<?= $rdv['id'] ?>" class="btn btn-sm btn-danger" title="Supprimer" onclick="return confirm('Supprimer ce rendez-vous ?')"><i class="fas fa-trash"></i></a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="8" class="text-center text-muted py-4">Aucun rendez-vous trouvé</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
<script>
$(document).ready(function() {
    <?php if (!empty($rdvs)): ?>
    $('#rdvTable').DataTable({
        language: { url: '//cdn.datatables.net/plug-ins/1.13.4/i18n/fr-FR.json' },
        pageLength: 10,
        order: [[3, 'desc']],
        searching: false,
        paging: true
    });
    <?php endif; ?>
});
</script>
</body>
</html>
