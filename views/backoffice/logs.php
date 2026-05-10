<?php
if (!isset($_SESSION['user_id']) || ($_SESSION['user_role'] ?? '') !== 'admin') {
    header('Location: index.php?page=login');
    exit;
}
$pageTitle = 'Historique / Logs';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?> — Valorys</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <?php require_once __DIR__ . '/../partials/backoffice_shell_styles.php'; ?>
</head>
<body class="bo-shell-body">
<?php require_once __DIR__ . '/sidebar.php'; ?>

<div class="main-content">
<div class="container-fluid px-0">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="h4 mb-0"><i class="fas fa-history text-primary me-2"></i>Historique des actions (logs)</h2>
        <a href="index.php?page=logs&action=export" class="btn btn-sm btn-primary shadow-sm">
            <i class="fas fa-download fa-sm text-white-50"></i> Exporter
        </a>
    </div>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Journal des événements</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover" width="100%" cellspacing="0">
                    <thead class="table-light">
                        <tr>
                            <th>Date</th>
                            <th>Utilisateur</th>
                            <th>Action</th>
                            <th>Description</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($logs)): ?>
                            <?php 
                            $currentDate = '';
                            foreach ($logs as $log): 
                                $logDate = date('d/m/Y', strtotime($log['created_at']));
                                if ($logDate !== $currentDate):
                                    $currentDate = $logDate;
                            ?>
                                <tr class="table-secondary">
                                    <td colspan="4" class="fw-bold"><i class="fas fa-calendar-day me-2"></i><?= $currentDate ?></td>
                                </tr>
                            <?php endif; ?>
                                <tr>
                                    <td><?= date('H:i:s', strtotime($log['created_at'])) ?></td>
                                    <td>
                                        <?php if ($log['user_id']): ?>
                                            <strong><?= htmlspecialchars($log['prenom'] . ' ' . $log['nom']) ?></strong>
                                        <?php else: ?>
                                            <span class="text-muted">Système</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php
                                        $actionColor = 'bg-secondary';
                                        if (stripos($log['action'], 'connexion') !== false) $actionColor = 'bg-info';
                                        if (stripos($log['action'], 'create') !== false || stripos($log['action'], 'ajout') !== false) $actionColor = 'bg-success';
                                        if (stripos($log['action'], 'delete') !== false || stripos($log['action'], 'suppr') !== false) $actionColor = 'bg-danger';
                                        if (stripos($log['action'], 'update') !== false || stripos($log['action'], 'modif') !== false) $actionColor = 'bg-warning text-dark';
                                        ?>
                                        <span class="badge <?= $actionColor ?>"><?= htmlspecialchars($log['action']) ?></span>
                                    </td>
                                    <td><?= htmlspecialchars($log['description']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="4" class="text-center py-4 text-muted">Aucun log trouvé pour le moment.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <?php if (isset($totalPages) && $totalPages > 1): ?>
                <?php $logPageNum = isset($_GET['p']) ? max(1, (int)$_GET['p']) : 1; ?>
                <nav aria-label="Page navigation" class="mt-4">
                    <ul class="pagination justify-content-center">
                        <li class="page-item <?= ($logPageNum <= 1) ? 'disabled' : '' ?>">
                            <a class="page-link" href="index.php?page=logs&p=<?= $logPageNum - 1 ?>">Précédent</a>
                        </li>
                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                            <li class="page-item <?= ($logPageNum == $i) ? 'active' : '' ?>">
                                <a class="page-link" href="index.php?page=logs&p=<?= $i ?>"><?= $i ?></a>
                            </li>
                        <?php endfor; ?>
                        <li class="page-item <?= ($logPageNum >= $totalPages) ? 'disabled' : '' ?>">
                            <a class="page-link" href="index.php?page=logs&p=<?= $logPageNum + 1 ?>">Suivant</a>
                        </li>
                    </ul>
                </nav>
            <?php endif; ?>
        </div>
    </div>
</div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
