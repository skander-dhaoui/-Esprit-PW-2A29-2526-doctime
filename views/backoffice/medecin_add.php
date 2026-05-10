<?php
// views/backoffice/medecins_list.php
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ../../index.php?page=login');
    exit;
}

$page_title = 'Gestion des médecins';
$current_page = 'medecins_admin';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $page_title ?> - Valorys</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <?php require_once __DIR__ . '/../partials/backoffice_shell_styles.php'; ?>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body.bo-shell-body { background: #f0f2f5; font-family: 'Segoe UI', sans-serif; min-height: 100vh; }
        .page-header { background: white; border-radius: 12px; padding: 18px 25px; margin-bottom: 25px; display: flex; align-items: center; justify-content: space-between; box-shadow: 0 1px 6px rgba(0,0,0,0.06); }
        .page-header h4 { font-size: 18px; font-weight: 700; color: #1a2035; margin: 0; display: flex; align-items: center; gap: 10px; }
        .page-header h4 i { color: #4CAF50; }
        .admin-avatar { width: 40px; height: 40px; background: #4CAF50; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-size: 16px; font-weight: bold; text-decoration: none; }
        .content-card { background: white; border-radius: 12px; padding: 25px; box-shadow: 0 1px 6px rgba(0,0,0,0.06); }
        .card-title-row { display: flex; align-items: center; justify-content: space-between; margin-bottom: 20px; }
        .btn-action { width: 32px; height: 32px; border-radius: 8px; display: inline-flex; align-items: center; justify-content: center; margin: 0 2px; text-decoration: none; }
        .btn-view { background: #e3f0ff; color: #1565c0; }
        .btn-edit { background: #fff3e0; color: #e65100; }
        .btn-delete { background: #fdecea; color: #c62828; }
        .badge-actif { background: #d4edda; color: #155724; padding: 4px 12px; border-radius: 20px; font-size: 12px; }
        .badge-inactif { background: #f8d7da; color: #721c24; padding: 4px 12px; border-radius: 20px; font-size: 12px; }
        .table thead th { background: #1a2035; color: white; font-weight: 600; font-size: 13px; padding: 12px 14px; border: none; }
    </style>
</head>
<body class="bo-shell-body">
<?php require_once __DIR__ . '/sidebar.php'; ?>

<div class="main-content">
    <div class="page-header">
        <h4><i class="fas fa-user-md"></i> Gestion des médecins</h4>
        <a href="index.php?page=mon_profil" class="admin-avatar"><?= strtoupper(substr($_SESSION['user_name'] ?? 'A', 0, 1)) ?></a>
    </div>

    <?php if (isset($_SESSION['flash'])): ?>
        <div class="alert alert-<?= $_SESSION['flash']['type'] === 'error' ? 'danger' : 'success' ?> alert-dismissible fade show">
            <?= htmlspecialchars($_SESSION['flash']['message']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['flash']); ?>
    <?php endif; ?>

    <div class="content-card">
        <div class="card-title-row">
            <h5><i class="fas fa-list"></i> Liste des médecins (<?= count($medecins) ?>)</h5>
            <a href="index.php?page=medecins_admin&action=add" class="btn btn-success btn-sm">
                <i class="fas fa-plus"></i> Ajouter un médecin
            </a>
        </div>
        <div class="table-responsive">
            <table id="medecinsTable" class="table table-hover align-middle">
                <thead>
                    <tr><th>Nom complet</th><th>Email</th><th>Spécialité</th><th>Téléphone</th><th>Statut</th><th>Actions</th></tr>
                </thead>
                <tbody>
                <?php foreach ($medecins as $m): ?>
                <tr>
                    <td><strong>Dr. <?= htmlspecialchars($m['prenom'] . ' ' . $m['nom']) ?></strong></td>
                    <td><?= htmlspecialchars($m['email']) ?></td>
                    <td><?= htmlspecialchars($m['specialite'] ?? '—') ?></td>
                    <td><?= htmlspecialchars($m['telephone'] ?? '—') ?></td>
                    <td><span class="badge-actif"><?= $m['statut'] === 'actif' ? 'Actif' : 'Inactif' ?></span></td>
                    <td>
                        <a href="index.php?page=medecins_admin&action=show&id=<?= $m['id'] ?>" class="btn-action btn-view"><i class="fas fa-eye"></i></a>
                        <a href="index.php?page=medecins_admin&action=edit&id=<?= $m['id'] ?>" class="btn-action btn-edit"><i class="fas fa-edit"></i></a>
                        <a href="index.php?page=medecins_admin&action=delete&id=<?= $m['id'] ?>" class="btn-action btn-delete" onclick="return confirm('Supprimer ce médecin ?')"><i class="fas fa-trash"></i></a>
                    </td>
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
<script>
$(document).ready(function() {
    $('#medecinsTable').DataTable({ language: { url: '//cdn.datatables.net/plug-ins/1.13.4/i18n/fr-FR.json' }, pageLength: 10 });
});
</script>
</body>
</html>
