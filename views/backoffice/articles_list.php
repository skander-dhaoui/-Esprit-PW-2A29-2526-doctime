<?php
// views/backoffice/articles_list.php
$current_page = 'articles_admin';
?>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $page_title ?? 'Gestion des Articles' ?> - DocTime</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { background: #f0f2f5; font-family: 'Segoe UI', sans-serif; display: flex; min-height: 100vh; }
        .main-content { margin-left: 260px; flex: 1; padding: 25px; min-height: 100vh; }
        .page-header {
            background: white; border-radius: 12px; padding: 18px 25px; margin-bottom: 25px;
            display: flex; align-items: center; justify-content: space-between;
            box-shadow: 0 1px 6px rgba(0,0,0,0.06);
        }
        .page-header h4 { font-size: 18px; font-weight: 700; color: #1a2035; margin: 0; display: flex; align-items: center; gap: 10px; }
        .page-header h4 i { color: #4CAF50; }
        .admin-avatar {
            width: 40px; height: 40px; background: #4CAF50; border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            color: white; font-size: 16px; font-weight: bold; text-decoration: none;
        }
        .content-card { background: white; border-radius: 12px; padding: 25px; box-shadow: 0 1px 6px rgba(0,0,0,0.06); }
        .card-title-row { display: flex; align-items: center; justify-content: space-between; margin-bottom: 20px; }
        .card-title-row h5 { font-size: 16px; font-weight: 600; color: #1a2035; margin: 0; }

        .flash-box { border-radius: 10px; padding: 12px 16px; margin-bottom: 20px; font-size: 14px; }
        .flash-success { background: #e8f5e9; color: #2e7d32; }
        .flash-error { background: #fdecea; color: #c62828; }

        .table thead th { background: #1a2035; color: white; font-weight: 600; font-size: 13px; padding: 12px 14px; border: none; }
        .table tbody td { vertical-align: middle; font-size: 14px; padding: 13px 14px; color: #333; }
        .table tbody tr:hover { background: #f8f9ff; }

        .badge-publie   { background: #e8f5e9; color: #2e7d32; padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: 600; }
        .badge-brouillon{ background: #fff8e1; color: #f57f17; padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: 600; }
        .badge-archive  { background: #e2e8f0; color: #475569; padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: 600; }

        .btn-action {
            width: 32px; height: 32px; border-radius: 8px; border: none;
            display: inline-flex; align-items: center; justify-content: center;
            font-size: 13px; cursor: pointer; transition: opacity 0.2s;
            text-decoration: none; margin: 0 2px;
        }
        .btn-action:hover { opacity: 0.8; }
        .btn-edit   { background: #e3f0ff; color: #1565c0; }
        .btn-delete { background: #fdecea; color: #c62828; }
        .btn-view   { background: #f3e5f5; color: #6a1b9a; }
    </style>
</head>
<body>

<?php include __DIR__ . '/sidebar.php'; ?>

<div class="main-content">
    <div class="page-header">
        <h4><i class="fas fa-blog"></i> Gestion des Articles</h4>
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
            <h5><i class="fas fa-list"></i> Liste des articles (<?= count($articles ?? []) ?>)</h5>
            <div class="d-flex gap-2">
                <a href="index.php?page=articles_admin&action=advanced" class="btn btn-outline-primary btn-sm">
                    <i class="fas fa-chart-bar me-1"></i> Vue avancée
                </a>
                <a href="index.php?page=articles_admin&action=create" class="btn btn-success btn-sm">
                    <i class="fas fa-plus me-1"></i> Nouvel Article
                </a>
            </div>
        </div>

        <div class="table-responsive">
            <table id="articlesTable" class="table table-hover align-middle">
                <thead>
                    <tr>
                        <th>Titre</th>
                        <th>Auteur</th>
                        <th>Vues</th>
                        <th>Statut</th>
                        <th>Créé le</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (!empty($articles)): ?>
                    <?php foreach ($articles as $a): ?>
                    <tr>
                        <td><strong><?= htmlspecialchars(mb_substr($a['titre'] ?? '', 0, 60)) ?></strong></td>
                        <td><?= htmlspecialchars(($a['prenom'] ?? '') . ' ' . ($a['nom'] ?? '')) ?></td>
                        <td><span class="badge bg-info"><?= (int)($a['vues'] ?? 0) ?></span></td>
                        <td>
                            <?php
                            $status = $a['status'] ?? 'brouillon';
                            $badgeClass = match($status) {
                                'publié'  => 'badge-publie',
                                'archive' => 'badge-archive',
                                default   => 'badge-brouillon',
                            };
                            ?>
                            <span class="<?= $badgeClass ?>"><?= htmlspecialchars(ucfirst($status)) ?></span>
                        </td>
                        <td><?= date('d/m/Y', strtotime($a['created_at'] ?? 'now')) ?></td>
                        <td>
                            <div class="d-flex gap-1">
                                <a href="index.php?page=articles_admin&action=view&id=<?= $a['id'] ?>" class="btn-action btn-view" title="Voir les détails" data-bs-toggle="tooltip">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <a href="index.php?page=articles_admin&action=edit&id=<?= $a['id'] ?>" class="btn-action btn-edit" title="Modifier" data-bs-toggle="tooltip">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <a href="index.php?page=articles_admin&action=delete&id=<?= $a['id'] ?>" class="btn-action btn-delete" title="Supprimer" onclick="return confirm('Supprimer cet article ?')" data-bs-toggle="tooltip">
                                    <i class="fas fa-trash"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6" class="text-center text-muted py-5">
                            <i class="fas fa-blog fa-2x mb-2 d-block opacity-25"></i>
                            Aucun article trouvé
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
        $('#articlesTable').DataTable({
            language: { url: '//cdn.datatables.net/plug-ins/1.13.4/i18n/fr-FR.json' },
            pageLength: 10,
            order: [[4, 'desc']],
            columnDefs: [{ orderable: false, targets: 5 }]
        });
    });
</script>
</body>
</html>
