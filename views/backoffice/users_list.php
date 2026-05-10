<?php
// views/backoffice/users_list.php
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ../../index.php?page=login');
    exit;
}

// Vérification que $users existe
if (!isset($users)) {
    die("ERREUR: La variable \$users n'est pas définie");
}

$page_title = 'Gestion des utilisateurs';
$current_page = 'users';
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
        body.bo-shell-body { background: #f0f2f5; font-family: 'Segoe UI', sans-serif; }

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

        .alert-box {
            border-radius: 10px;
            padding: 12px 15px;
            margin-bottom: 20px;
        }
        .alert-success { background: #d4edda; color: #155724; }
        .alert-error   { background: #f8d7da; color: #721c24; }

        .badge-actif     { background: #d4edda; color: #155724; padding: 4px 12px; border-radius: 20px; font-size: 12px; }
        .badge-inactif   { background: #f8d7da; color: #721c24; padding: 4px 12px; border-radius: 20px; font-size: 12px; }
        .badge-validation{ background: #fff3cd; color: #856404; padding: 4px 12px; border-radius: 20px; font-size: 12px; }

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

        .btn-sm { padding: 5px 10px; margin: 2px; }
    </style>
</head>
<body class="bo-shell-body">
<?php require_once __DIR__ . '/sidebar.php'; ?>

<div class="main-content">
    <div class="page-header">
        <h4><i class="fas fa-users"></i> Gestion des utilisateurs</h4>
        <a href="index.php?page=mon_profil" class="admin-avatar" title="Mon profil">
            <?= strtoupper(substr($_SESSION['user_name'] ?? 'A', 0, 1)) ?>
        </a>
    </div>

    <?php if (isset($_SESSION['flash'])): ?>
        <div class="alert-box alert-<?= $_SESSION['flash']['type'] === 'error' ? 'error' : 'success' ?>">
            <i class="fas fa-<?= $_SESSION['flash']['type'] === 'error' ? 'times-circle' : 'check-circle' ?> me-2"></i>
            <?= htmlspecialchars($_SESSION['flash']['message']) ?>
        </div>
        <?php unset($_SESSION['flash']); ?>
    <?php endif; ?>

    <div class="content-card">
        <div class="card-title-row">
            <h5><i class="fas fa-list"></i> Liste des utilisateurs (<?= count($users) ?>)</h5>
            <a href="index.php?page=users&action=create" class="btn btn-success btn-sm">
                <i class="fas fa-plus me-1"></i> Ajouter
            </a>
        </div>

        <div class="table-responsive">
            <table id="usersTable" class="table table-hover align-middle">
                <thead>
                    <tr>
                        <th>Nom complet</th>
                        <th>Email</th>
                        <th>Téléphone</th>
                        <th>Rôle</th>
                        <th>Statut</th>
                        <th>Inscrit le</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (!empty($users)): ?>
                    <?php foreach ($users as $u): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($u['prenom'] . ' ' . $u['nom']) ?></strong></td>
                            <td><?= htmlspecialchars($u['email']) ?></td>
                            <td><?= htmlspecialchars($u['telephone'] ?? '-') ?></td>
                            <td>
                                <?php
                                    $roleColors = ['patient' => 'info', 'medecin' => 'success', 'admin' => 'danger'];
                                    $color = $roleColors[$u['role']] ?? 'secondary';
                                ?>
                                <span class="badge bg-<?= $color ?>"><?= ucfirst($u['role']) ?></span>
                            </td>
                            <td>
                                <?php if ($u['statut'] === 'actif'): ?>
                                    <span class="badge-actif">Actif</span>
                                <?php elseif ($u['statut'] === 'inactif'): ?>
                                    <span class="badge-inactif">Inactif</span>
                                <?php else: ?>
                                    <span class="badge-validation">En attente</span>
                                <?php endif; ?>
                            </td>
                            <td><?= isset($u['created_at']) ? date('d/m/Y', strtotime($u['created_at'])) : '-' ?></td>
                            <td>
                                <a href="index.php?page=users&action=edit&id=<?= $u['id'] ?>" class="btn btn-sm btn-primary" title="Modifier">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <a href="index.php?page=users&action=toggle&id=<?= $u['id'] ?>" class="btn btn-sm <?= $u['statut'] === 'actif' ? 'btn-warning' : 'btn-success' ?>" title="<?= $u['statut'] === 'actif' ? 'Désactiver' : 'Activer' ?>" onclick="return confirm('Modifier le statut ?')">
                                    <i class="fas <?= $u['statut'] === 'actif' ? 'fa-ban' : 'fa-check' ?>"></i>
                                </a>
                                <?php if ($u['id'] != $_SESSION['user_id']): ?>
                                <a href="index.php?page=users&action=delete&id=<?= $u['id'] ?>" class="btn btn-sm btn-danger" title="Supprimer" onclick="return confirm('Supprimer définitivement cet utilisateur ?')">
                                    <i class="fas fa-trash"></i>
                                </a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="7" class="text-center text-muted py-5">
                            <i class="fas fa-users fa-2x mb-2 d-block opacity-25"></i>
                            Aucun utilisateur trouvé
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
        $('#usersTable').DataTable({
            language: { 
                url: '//cdn.datatables.net/plug-ins/1.13.4/i18n/fr-FR.json'
            },
            pageLength: 10,
            order: [[5, 'desc']],
            responsive: true
        });
    });
</script>
</body>
</html>
