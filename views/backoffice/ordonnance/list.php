<?php
// views/backoffice/ordonnance/list.php
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: index.php?page=login');
    exit;
}

// Calculer les statistiques
$totalOrdonnances = count($ordonnances);
$enAttente = count(array_filter($ordonnances, fn($o) => ($o['status'] ?? '') === 'en_attente'));
$delivrees = count(array_filter($ordonnances, fn($o) => ($o['status'] ?? '') === 'delivree'));
$expirees = count(array_filter($ordonnances, fn($o) => 
    !empty($o['date_expiration']) && strtotime($o['date_expiration']) < time()
));
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des ordonnances - Valorys Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <?php require_once __DIR__ . '/../../partials/backoffice_shell_styles.php'; ?>
    <style>
        body.bo-shell-body { background: #f4f6f9; font-family: 'Segoe UI', sans-serif; }
        .navbar-top { background: white; border-radius: 12px; padding: 15px 25px; margin-bottom: 25px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); display: flex; justify-content: space-between; align-items: center; }
        .stat-card { background: white; border-radius: 15px; padding: 20px; margin-bottom: 20px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); border-left: 4px solid; }
        .stat-card h3 { font-size: 32px; margin: 10px 0 5px; font-weight: bold; }
        .stat-icon { font-size: 45px; opacity: 0.3; float: right; }
        .badge-active { background: #d4edda; color: #155724; padding: 5px 12px; border-radius: 20px; font-size: 12px; }
        .badge-expired { background: #f8d7da; color: #721c24; padding: 5px 12px; border-radius: 20px; font-size: 12px; }
        .badge-warning { background: #fff3cd; color: #856404; padding: 5px 12px; border-radius: 20px; font-size: 12px; }
    </style>
</head>
<body class="bo-shell-body">
<?php require_once __DIR__ . '/../sidebar.php'; ?>

<div class="main-content">
    <div class="navbar-top">
        <h4 class="mb-0"><i class="fas fa-prescription-bottle me-2"></i>Gestion des ordonnances</h4>
        <div>
            <span><?= htmlspecialchars($_SESSION['user_name'] ?? 'Admin') ?></span>
        </div>
    </div>

    <!-- Statistiques -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="stat-card" style="border-left-color: #4CAF50;">
                <i class="fas fa-prescription-bottle stat-icon"></i>
                <p>Total ordonnances</p>
                <h3><?= $totalOrdonnances ?></h3>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card" style="border-left-color: #ffc107;">
                <i class="fas fa-clock stat-icon"></i>
                <p>En attente</p>
                <h3><?= $enAttente ?></h3>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card" style="border-left-color: #17a2b8;">
                <i class="fas fa-check-circle stat-icon"></i>
                <p>Délivrées</p>
                <h3><?= $delivrees ?></h3>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card" style="border-left-color: #dc3545;">
                <i class="fas fa-exclamation-circle stat-icon"></i>
                <p>Expirées</p>
                <h3><?= $expirees ?></h3>
            </div>
        </div>
    </div>

    <!-- Actions -->
    <div class="card mb-4">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                <a href="index.php?page=ordonnances&action=create" class="btn btn-success">
                    <i class="fas fa-plus me-2"></i>Nouvelle ordonnance
                </a>
                <form method="GET" class="d-flex gap-2">
                    <input type="hidden" name="page" value="ordonnances">
                    <input type="text" name="search" class="form-control" placeholder="Rechercher..." value="<?= htmlspecialchars($_GET['search'] ?? '') ?>" style="width: 250px;">
                    <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i></button>
                </form>
            </div>
        </div>
    </div>

    <!-- Tableau -->
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead class="table-primary">
                        <tr>
                            <th>N° Ordonnance</th>
                            <th>Patient</th>
                            <th>Médecin</th>
                            <th>Date</th>
                            <th>Expiration</th>
                            <th>Diagnostic</th>
                            <th>Statut</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($ordonnances)): ?>
                            <tr><td colspan="8" class="text-center text-muted py-4">Aucune ordonnance trouvée</td></tr>
                        <?php else: ?>
                            <?php foreach ($ordonnances as $ordo): ?>
                                <?php
                                // Déterminer le statut et la badge
                                $isExpired = !empty($ordo['date_expiration']) && strtotime($ordo['date_expiration']) < time();
                                $badgeClass = 'badge-active';
                                $statusLabel = $ordo['status'] ?? 'active';
                                if ($isExpired) {
                                    $badgeClass = 'badge-expired';
                                    $statusLabel = 'Expirée';
                                } elseif ($statusLabel === 'en_attente') {
                                    $badgeClass = 'badge-warning';
                                }
                                ?>
                            <tr>
                                <td><?= htmlspecialchars($ordo['numero_ordonnance'] ?? 'N/A') ?></td>
                                <td><?= htmlspecialchars($ordo['patient_nom'] ?? 'Patient #' . ($ordo['patient_id'] ?? '?')) ?></td>
                                <td><?= htmlspecialchars($ordo['medecin_nom'] ?? 'Médecin #' . ($ordo['medecin_id'] ?? '?')) ?></td>
                                <td><?= date('d/m/Y', strtotime($ordo['date_ordonnance'] ?? 'now')) ?></td>
                                <td><?= !empty($ordo['date_expiration']) ? date('d/m/Y', strtotime($ordo['date_expiration'])) : 'N/A' ?></td>
                                <td><?= htmlspecialchars(substr($ordo['diagnostic'] ?? '', 0, 30)) ?>...</div>
                                <td>
                                    <span class="badge <?= $badgeClass ?> px-3 py-2"><?= $statusLabel ?></span>
                                 </div>
                                <td>
                                    <div class="btn-group" role="group">
                                        <a href="index.php?page=ordonnances&action=show&id=<?= $ordo['id'] ?>" class="btn btn-sm btn-info" title="Voir">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="index.php?page=ordonnances&action=edit&id=<?= $ordo['id'] ?>" class="btn btn-sm btn-warning" title="Modifier">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="index.php?page=ordonnances&action=delete&id=<?= $ordo['id'] ?>" class="btn btn-sm btn-danger" title="Supprimer" onclick="return confirm('Supprimer cette ordonnance ?')">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                        <a href="index.php?page=ordonnances&action=pdf&id=<?= $ordo['id'] ?>" class="btn btn-sm btn-secondary" title="PDF">
                                            <i class="fas fa-file-pdf"></i>
                                        </a>
                                    </div>
                                 </div>
                             </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
