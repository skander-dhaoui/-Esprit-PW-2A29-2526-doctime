<?php
// views/backoffice/patients_list.php
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ../../index.php?page=login');
    exit;
}

$page_title = 'Gestion des patients';
$current_page = 'patients';
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

        .page-header {
            background: white;
            border-radius: 12px;
            padding: 18px 25px;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            box-shadow: 0 1px 6px rgba(0,0,0,0.06);
        }

        .page-header h4 {
            font-size: 18px;
            font-weight: 700;
            color: #1a2035;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .page-header h4 i {
            color: #4CAF50;
        }

        .admin-avatar {
            width: 40px;
            height: 40px;
            background: #4CAF50;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 16px;
            font-weight: bold;
            text-decoration: none;
        }

        .content-card {
            background: white;
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 1px 6px rgba(0,0,0,0.06);
        }

        .card-title-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 20px;
        }

        .card-title-row h5 {
            font-size: 16px;
            font-weight: 600;
            color: #1a2035;
            margin: 0;
        }

        .badge-actif { background: #d4edda; color: #155724; padding: 4px 12px; border-radius: 20px; font-size: 12px; }
        .badge-inactif { background: #f8d7da; color: #721c24; padding: 4px 12px; border-radius: 20px; font-size: 12px; }

        .table thead th {
            background: #1a2035;
            color: white;
            font-weight: 600;
            font-size: 13px;
            padding: 12px 14px;
            border: none;
        }
    </style>
</head>
<body class="bo-shell-body">
<?php require_once __DIR__ . '/sidebar.php'; ?>

<div class="main-content">
    <div class="page-header">
        <h4><i class="fas fa-user-injured"></i> Gestion des patients</h4>
        <a href="index.php?page=mon_profil" class="admin-avatar">
            <?= strtoupper(substr($_SESSION['user_name'] ?? 'A', 0, 1)) ?>
        </a>
    </div>

    <div class="content-card">
        <div class="card-title-row">
            <h5><i class="fas fa-list"></i> Liste des patients (<?= count($patients ?? []) ?>)</h5>
            <a href="index.php?page=users&action=create" class="btn btn-success btn-sm">
                <i class="fas fa-plus me-1"></i> Ajouter un patient
            </a>
        </div>

        <div class="table-responsive">
            <table id="patientsTable" class="table table-hover align-middle">
                <thead>
                    <tr>
                        <th>Nom complet</th><th>Email</th>
                        <th>Téléphone</th><th>Groupe sanguin</th><th>Statut</th><th>Inscrit le</th><th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (!empty($patients)): ?>
                    <?php foreach ($patients as $p): ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($p['prenom'] . ' ' . $p['nom']) ?></strong></td>
                        <td><?= htmlspecialchars($p['email']) ?></td>
                        <td><?= htmlspecialchars($p['telephone'] ?? '—') ?></td>
                        <td><?= htmlspecialchars($p['groupe_sanguin'] ?? '—') ?></td>
                        <td>
                            <span class="badge-actif"><?= $p['statut'] === 'actif' ? 'Actif' : 'Inactif' ?></span>
                        </td>
                        <td><?= date('d/m/Y', strtotime($p['created_at'])) ?></td>
                        <td>
                            <a href="index.php?page=patients&action=edit&id=<?= $p['id'] ?>" class="btn btn-sm btn-primary"><i class="fas fa-edit"></i></a>
                            <a href="index.php?page=patients&action=delete&id=<?= $p['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Supprimer ?')"><i class="fas fa-trash"></i></a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="7" class="text-center">Aucun patient trouvé</td></tr>
                <?php endif; ?>
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
    <?php if (!empty($patients)): ?>
    $('#patientsTable').DataTable({
        language: { url: '//cdn.datatables.net/plug-ins/1.13.4/i18n/fr-FR.json' },
        pageLength: 10,
        order: [[5, 'desc']]
    });
    <?php endif; ?>
});
</script>
</body>
</html>
