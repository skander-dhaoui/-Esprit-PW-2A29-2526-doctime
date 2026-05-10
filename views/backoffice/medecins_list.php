<?php
// views/backoffice/medecins_list.php
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ../../index.php?page=login');
    exit;
}

$page_title = 'Gestion des médecins';
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

        .admin-avatar:hover {
            background: #2A7FAA;
            color: white;
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

        .flash-box {
            border-radius: 10px;
            padding: 12px 16px;
            margin-bottom: 20px;
            font-size: 14px;
        }

        .flash-success {
            background: #e8f5e9;
            color: #2e7d32;
        }

        .flash-error {
            background: #fdecea;
            color: #c62828;
        }

        /* Table styles */
        .table thead th {
            background: #1a2035;
            color: white;
            font-weight: 600;
            font-size: 13px;
            padding: 12px 14px;
            border: none;
        }

        .table tbody td {
            vertical-align: middle;
            font-size: 14px;
            padding: 13px 14px;
            color: #333;
        }

        .table tbody tr:hover {
            background: #f8f9ff;
        }

        .badge-actif {
            background: #e8f5e9;
            color: #2e7d32;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }

        .badge-inactif {
            background: #fdecea;
            color: #c62828;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }

        .badge-attente {
            background: #fff8e1;
            color: #f57f17;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }

        .badge-specialite {
            background: #d1f5e0;
            color: #0d6b37;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }

        .btn-action {
            width: 32px;
            height: 32px;
            border-radius: 8px;
            border: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 13px;
            cursor: pointer;
            transition: opacity 0.2s;
            text-decoration: none;
            margin: 0 2px;
        }

        .btn-action:hover { opacity: 0.8; }
        .btn-edit { background: #e3f0ff; color: #1565c0; }
        .btn-delete { background: #fdecea; color: #c62828; }
        .btn-view { background: #f3e5f5; color: #6a1b9a; }
        .btn-validate { background: #e8f5e9; color: #2e7d32; }
    </style>
</head>
<body class="bo-shell-body">
<?php require_once __DIR__ . '/sidebar.php'; ?>

<div class="main-content">
    <div class="page-header">
        <h4><i class="fas fa-user-md"></i> Gestion des médecins</h4>
        <a href="index.php?page=mon_profil" class="admin-avatar" title="Mon profil">
            <?= strtoupper(substr($_SESSION['user_name'] ?? 'A', 0, 1)) ?>
        </a>
    </div>

    <?php if (isset($_SESSION['flash'])): ?>
        <div class="flash-box flash-<?= $_SESSION['flash']['type'] === 'error' ? 'error' : 'success' ?>">
            <i class="fas fa-<?= $_SESSION['flash']['type'] === 'error' ? 'times-circle' : 'check-circle' ?> me-2"></i>
            <?= htmlspecialchars($_SESSION['flash']['message']) ?>
        </div>
        <?php unset($_SESSION['flash']); ?>
    <?php endif; ?>

    <div class="content-card">
        <div class="card-title-row">
            <h5><i class="fas fa-list"></i> Liste des médecins (<?= count($medecins) ?>)</h5>
            <a href="index.php?page=users&action=create" class="btn btn-success btn-sm">
                <i class="fas fa-plus me-1"></i> Ajouter un médecin
            </a>
        </div>

        <div class="table-responsive">
            <table id="medecinsTable" class="table table-hover align-middle">
                <thead>
                    <tr>
                        <th>Nom complet</th>
                        <th>Email</th>
                        <th>Téléphone</th>
                        <th>Spécialité</th>
                        <th>Tarif</th>
                        <th>Statut</th>
                        <th>Inscrit le</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (!empty($medecins)): ?>
                    <?php foreach ($medecins as $m): ?>
                    <tr>
                        <td><strong>Dr. <?= htmlspecialchars($m['prenom'] . ' ' . $m['nom']) ?></strong></td>
                        <td><?= htmlspecialchars($m['email']) ?></td>
                        <td><?= htmlspecialchars($m['telephone'] ?? '—') ?></td>
                        <td>
                            <?php if (!empty($m['specialite'])): ?>
                                <span class="badge-specialite"><?= htmlspecialchars($m['specialite']) ?></span>
                            <?php else: ?>
                                <span class="text-muted">—</span>
                            <?php endif; ?>
                        </td>
                        <td><?= !empty($m['consultation_prix']) ? htmlspecialchars($m['consultation_prix']) . ' €' : '—' ?></td>
                        <td>
                            <?php if ($m['statut'] === 'actif'): ?>
                                <span class="badge-actif">Actif</span>
                            <?php elseif ($m['statut'] === 'inactif'): ?>
                                <span class="badge-inactif">Inactif</span>
                            <?php else: ?>
                                <span class="badge-attente">En attente</span>
                            <?php endif; ?>
                        </td>
                        <td><?= date('d/m/Y', strtotime($m['created_at'])) ?></td>
                        <td>
                            <div class="d-flex gap-1">
                                <a href="index.php?page=medecins_admin&action=show&id=<?= $m['id'] ?>" class="btn-action btn-view" title="Voir">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <a href="index.php?page=medecins_admin&action=edit&id=<?= $m['id'] ?>" class="btn-action btn-edit" title="Modifier">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <a href="index.php?page=medecins_admin&action=delete&id=<?= $m['id'] ?>" class="btn-action btn-delete" title="Supprimer" onclick="return confirm('Supprimer définitivement ce médecin ?')">
                                    <i class="fas fa-trash"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="8" class="text-center text-muted py-5">
                            <i class="fas fa-user-md fa-2x mb-2 d-block opacity-25"></i>
                            Aucun médecin trouvé
                        </td>
                    </tr>
                <?php endif; ?>
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
        <?php if (!empty($medecins)): ?>
        $('#medecinsTable').DataTable({
            language: {
                url: '//cdn.datatables.net/plug-ins/1.13.4/i18n/fr-FR.json'
            },
            pageLength: 10,
            order: [[6, 'desc']],
            responsive: true,
            columnDefs: [
                { orderable: false, targets: 7 }
            ]
        });
        <?php endif; ?>
    });
</script>
</body>
</html>
