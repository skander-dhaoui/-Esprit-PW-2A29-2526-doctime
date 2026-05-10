<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Historique - MediConnect</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <?php require_once __DIR__ . '/../partials/backoffice_shell_styles.php'; ?>
    <style>
        body.bo-shell-body { background: #f4f6f9; font-family: 'Segoe UI', sans-serif; }
        .navbar-top { background: white; border-radius: 12px; padding: 15px 25px; margin-bottom: 25px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); display: flex; justify-content: space-between; align-items: center; }
        .content-card { background: white; border-radius: 15px; padding: 25px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
    </style>
</head>
<body class="bo-shell-body">
<?php require_once __DIR__ . '/sidebar.php'; ?>
<div class="main-content">
    <div class="navbar-top">
        <h4><i class="fas fa-history me-2"></i> Historique des actions</h4>
        <span>👤 <?= htmlspecialchars($_SESSION['user_prenom'] . ' ' . $_SESSION['user_nom']) ?></span>
    </div>
    <div class="content-card">
        <h5 class="mb-3"><i class="fas fa-list me-2"></i> Journal des actions</h5>
        <div class="table-responsive">
            <table id="logsTable" class="table table-hover">
                <thead class="table-dark">
                    <tr>
                        <th>Utilisateur</th><th>Action</th>
                        <th>Description</th><th>IP</th><th>Date</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($logs as $log): ?>
                    <tr>
                        <td>
                            <?php if (!empty($log['prenom'])): ?>
                                <?= htmlspecialchars($log['prenom'] . ' ' . $log['nom']) ?>
                                <span class="badge bg-secondary"><?= $log['role'] ?></span>
                            <?php else: ?>
                                <span class="text-muted">Système</span>
                            <?php endif; ?>
                        </td>
                        <td><span class="badge bg-primary"><?= htmlspecialchars($log['action']) ?></span></td>
                        <td><?= htmlspecialchars($log['description']) ?></td>
                        <td><?= htmlspecialchars($log['ip_address'] ?? '-') ?></td>
                        <td><?= date('d/m/Y H:i', strtotime($log['created_at'])) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>
<script>
    $(document).ready(function() {
        $('#logsTable').DataTable({
            language: { url: '//cdn.datatables.net/plug-ins/1.13.4/i18n/fr-FR.json' },
            pageLength: 15, order: [[4, 'desc']]
        });
    });
</script>
</body>
</html>
