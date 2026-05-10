<?php
// views/backoffice/categorie_manage.php
$current_page = 'categories_admin';
?>
<?php // Vue déprécée ?>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Catégories - DocTime Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        :root {
            --navy:      #1a2035;
            --teal:      #0fa99b;
            --teal-dark: #0d8a7d;
            --red:       #ef4444;
            --gray-50:   #f8fafc;
            --gray-200:  #e2e8f0;
            --gray-700:  #334155;
            --gray-900:  #0f172a;
            --shadow:    0 1px 6px rgba(0,0,0,.07);
            --radius:    12px;
        }
        body { font-family:'Segoe UI',system-ui,sans-serif; background:var(--gray-50); color:var(--gray-900); min-height:100vh; display:flex; }
        .main-content { margin-left:260px; flex:1; padding:25px; }

        .page-header { background:white; border-radius:var(--radius); padding:18px 25px; margin-bottom:25px; display:flex; align-items:center; justify-content:space-between; box-shadow:var(--shadow); }
        .page-header h4 { font-size:20px; font-weight:700; color:var(--navy); margin:0; display:flex; align-items:center; gap:10px; }
        .page-header h4 i { color:var(--teal); }

        .stats-row { display:grid; grid-template-columns:repeat(3,1fr); gap:16px; margin-bottom:25px; }
        .stat-card { background:white; border-radius:var(--radius); padding:20px; box-shadow:var(--shadow); display:flex; align-items:center; gap:16px; }
        .stat-icon { width:48px; height:48px; border-radius:12px; display:flex; align-items:center; justify-content:center; font-size:20px; }
        .stat-icon.teal   { background:#e0f5f3; color:var(--teal); }
        .stat-icon.green  { background:#e8f5e9; color:#4CAF50; }
        .stat-icon.orange { background:#fff3e0; color:#ff9800; }
        .stat-value { font-size:28px; font-weight:700; color:var(--navy); }
        .stat-label { font-size:12px; color:var(--gray-700); }

        .filters-bar { background:white; border-radius:var(--radius); padding:16px 20px; margin-bottom:20px; display:flex; align-items:center; gap:12px; box-shadow:var(--shadow); flex-wrap:wrap; }
        .filters-bar input[type="text"] { padding:9px 13px; border:1.5px solid var(--gray-200); border-radius:8px; font-size:13px; width:260px; }
        .filters-bar input[type="text"]:focus { border-color:var(--teal); outline:none; }

        .btn { display:inline-flex; align-items:center; gap:7px; padding:9px 18px; border-radius:8px; font-size:13px; font-weight:600; cursor:pointer; border:none; text-decoration:none; transition:all .2s; }
        .btn-teal    { background:var(--teal); color:white; }
        .btn-teal:hover { background:var(--teal-dark); color:white; transform:translateY(-1px); }
        .btn-sm      { padding:6px 12px; font-size:12px; }
        .btn-secondary { background:var(--gray-200); color:var(--gray-700); }
        .btn-secondary:hover { background:#cbd5e1; }
        .btn-outline-warning { background:transparent; border:1.5px solid #f59e0b; color:#b45309; }
        .btn-outline-warning:hover { background:#fffbeb; }
        .btn-outline-danger  { background:transparent; border:1.5px solid var(--red); color:var(--red); }
        .btn-outline-danger:hover  { background:#fef2f2; }

        .content-card { background:white; border-radius:var(--radius); padding:25px; box-shadow:var(--shadow); }
        .card-title-row { display:flex; align-items:center; justify-content:space-between; margin-bottom:20px; }
        .card-title-row h5 { font-size:16px; font-weight:600; color:var(--navy); margin:0; }

        table { width:100%; border-collapse:collapse; font-size:13px; }
        thead th { background:var(--gray-50); padding:11px 14px; text-align:left; font-weight:600; color:var(--gray-700); text-transform:uppercase; font-size:11px; letter-spacing:.5px; border-bottom:2px solid var(--gray-200); }
        tbody tr { border-bottom:1px solid var(--gray-200); transition:background .15s; }
        tbody tr:hover { background:#f0fdfa; }
        tbody td { padding:12px 14px; vertical-align:middle; }

        .badge { display:inline-block; padding:4px 10px; border-radius:20px; font-size:11px; font-weight:600; }
        .badge-actif   { background:#e8f5e9; color:#2e7d32; }
        .badge-inactif { background:#f3f4f6; color:#6b7280; }

        .cat-icon { width:38px; height:38px; border-radius:10px; background:var(--gray-200); display:inline-flex; align-items:center; justify-content:center; color:var(--gray-700); font-size:16px; overflow:hidden; }
        .cat-icon img { width:100%; height:100%; object-fit:cover; }

        .flash-box { border-radius:10px; padding:13px 16px; margin-bottom:20px; font-size:14px; display:flex; align-items:center; gap:10px; }
        .flash-success { background:#e8f5e9; color:#2e7d32; }
        .flash-error   { background:#fdecea; color:#c62828; }

        .empty-state { text-align:center; padding:50px 20px; color:var(--gray-700); }
        .empty-state i { font-size:48px; color:#cbd5e1; margin-bottom:14px; }

        .nb-badge { background:#e0f5f3; color:var(--teal); padding:3px 10px; border-radius:20px; font-size:12px; font-weight:600; }
    </style>
</head>
<body>

<?php include __DIR__ . '/sidebar.php'; ?>

<div class="main-content">

    <div class="page-header">
        <h4><i class="fas fa-tags"></i> Gestion des Catégories</h4>
        <a href="index.php?page=categories_admin&action=create" class="btn btn-teal">
            <i class="fas fa-plus"></i> Nouvelle Catégorie
        </a>
    </div>

    <?php if (!empty($flash)): ?>
    <div class="flash-box flash-<?= $flash['type'] ?>">
        <i class="fas fa-<?= $flash['type'] === 'success' ? 'check-circle' : 'exclamation-circle' ?>"></i>
        <?= htmlspecialchars($flash['message']) ?>
    </div>
    <?php endif; ?>

    <!-- Stats -->
    <div class="stats-row">
        <div class="stat-card">
            <div class="stat-icon teal"><i class="fas fa-tags"></i></div>
            <div>
                <div class="stat-value"><?= $stats['total'] ?? 0 ?></div>
                <div class="stat-label">Total catégories</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon orange"><i class="fas fa-box"></i></div>
            <div>
                <div class="stat-value"><?= array_sum(array_column($categories ?? [], 'nb_produits')) ?></div>
                <div class="stat-label">Produits liés</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon green"><i class="fas fa-sitemap"></i></div>
            <div>
                <div class="stat-value"><?= count(array_filter($categories ?? [], fn($c) => empty($c['parent_id']))) ?></div>
                <div class="stat-label">Catégories racines</div>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <form method="GET" action="index.php">
        <input type="hidden" name="page" value="categories_admin">
        <div class="filters-bar">
            <input type="text" name="search" placeholder="🔍 Rechercher une catégorie…"
                   value="<?= htmlspecialchars($_GET['search'] ?? '') ?>">
            <button type="submit" class="btn btn-teal btn-sm"><i class="fas fa-filter"></i> Filtrer</button>
            <a href="index.php?page=categories_admin" class="btn btn-secondary btn-sm"><i class="fas fa-times"></i> Réinitialiser</a>
            <span style="margin-left:auto;font-size:13px;color:var(--gray-700);">
                <?= count($categories ?? []) ?> catégorie<?= count($categories ?? []) !== 1 ? 's' : '' ?> trouvée<?= count($categories ?? []) !== 1 ? 's' : '' ?>
            </span>
        </div>
    </form>

    <!-- Table -->
    <div class="content-card">
        <div class="card-title-row">
            <h5><i class="fas fa-list" style="color:var(--teal);margin-right:8px;"></i>Liste des Catégories</h5>
        </div>

        <?php if (empty($categories)): ?>
        <div class="empty-state">
            <i class="fas fa-tags"></i>
            <p>Aucune catégorie trouvée.</p>
            <a href="index.php?page=categories_admin&action=create" class="btn btn-teal" style="margin-top:16px;">
                <i class="fas fa-plus"></i> Créer la première catégorie
            </a>
        </div>
        <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Catégorie</th>
                    <th>Slug</th>
                    <th>Parente</th>
                    <th>Produits</th>
                    <th style="text-align:right;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($categories as $cat): ?>
                <tr>
                    <td style="color:var(--gray-700);font-size:12px;">#<?= $cat['id'] ?></td>
                    <td>
                        <div style="display:flex;align-items:center;gap:10px;">
                            <div class="cat-icon">
                                <?php if (!empty($cat['image'])): ?>
                                    <img src="<?= htmlspecialchars($cat['image']) ?>" alt="">
                                <?php else: ?>
                                    <i class="fas fa-tag"></i>
                                <?php endif; ?>
                            </div>
                            <div>
                                <div style="font-weight:600;color:var(--navy);"><?= htmlspecialchars($cat['nom']) ?></div>
                                <?php if (!empty($cat['description'])): ?>
                                    <small style="color:var(--gray-700);"><?= htmlspecialchars(mb_substr($cat['description'], 0, 55)) ?><?= mb_strlen($cat['description']) > 55 ? '…' : '' ?></small>
                                <?php endif; ?>
                            </div>
                        </div>
                    </td>
                    <td><code style="font-size:11px;background:#f1f5f9;padding:2px 7px;border-radius:4px;"><?= htmlspecialchars($cat['slug']) ?></code></td>
                    <td style="color:var(--gray-700);"><?= htmlspecialchars($cat['parent_nom'] ?? '—') ?></td>
                    <td><span class="nb-badge"><?= (int)($cat['nb_produits'] ?? 0) ?></span></td>
                    <td style="text-align:right;">
                        <div style="display:flex;gap:6px;justify-content:flex-end;">
                            <a href="index.php?page=categories_admin&action=edit&id=<?= $cat['id'] ?>"
                               class="btn btn-outline-warning btn-sm" title="Modifier">
                                <i class="fas fa-pen"></i>
                            </a>
                            <button onclick="confirmDelete(<?= $cat['id'] ?>, '<?= addslashes(htmlspecialchars($cat['nom'])) ?>', <?= (int)($cat['nb_produits'] ?? 0) ?>)"
                                    class="btn btn-outline-danger btn-sm" title="Supprimer">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>

</div>

<!-- Delete Modal -->
<div id="deleteModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:9999;align-items:center;justify-content:center;">
    <div style="background:white;border-radius:14px;padding:30px;max-width:400px;width:90%;text-align:center;box-shadow:0 20px 60px rgba(0,0,0,.2);">
        <div style="width:60px;height:60px;background:#fdecea;border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 16px;font-size:24px;color:#ef4444;">
            <i class="fas fa-trash"></i>
        </div>
        <h5 style="font-size:18px;font-weight:700;color:#1a2035;margin-bottom:8px;">Supprimer cette catégorie ?</h5>
        <p id="deleteModalMsg" style="color:#64748b;font-size:14px;margin-bottom:24px;"></p>
        <div style="display:flex;gap:12px;justify-content:center;flex-wrap:wrap;">
            <button onclick="closeModal()" style="padding:10px 24px;border-radius:8px;border:1.5px solid #e2e8f0;background:white;font-size:14px;font-weight:600;cursor:pointer;color:#334155;">Annuler</button>
            <a id="deleteConfirmBtn" href="#" style="padding:10px 24px;border-radius:8px;background:#ef4444;color:white;font-size:14px;font-weight:600;text-decoration:none;display:inline-flex;align-items:center;gap:6px;"><i class="fas fa-trash"></i> Supprimer</a>
            <button id="deleteOnlyClose" onclick="closeModal()" style="display:none;padding:10px 24px;border-radius:8px;background:#0fa99b;color:white;font-size:14px;font-weight:600;cursor:pointer;border:none;">Fermer</button>
        </div>
    </div>
</div>

<script>
function confirmDelete(id, nom, nbProduits) {
    const modal = document.getElementById('deleteModal');
    const msg   = document.getElementById('deleteModalMsg');
    const btn   = document.getElementById('deleteConfirmBtn');

    if (nbProduits > 0) {
        msg.innerHTML =
            '<strong style="color:#ef4444;">' + nbProduits + ' produit(s) sont liés à cette catégorie.</strong><br>' +
            'Vous devez d\'abord réaffecter ou supprimer ces produits avant de pouvoir supprimer <strong>"' + nom + '"</strong>.';
        btn.style.display = 'none';
        document.getElementById('deleteOnlyClose').style.display = 'inline-flex';
    } else {
        msg.innerHTML = 'Voulez-vous vraiment supprimer <strong>"' + nom + '"</strong> ? Cette action est irréversible.';
        btn.style.display = 'inline-flex';
        btn.href = 'index.php?page=categories_admin&action=delete&id=' + id;
        document.getElementById('deleteOnlyClose').style.display = 'none';
    }
    modal.style.display = 'flex';
}
function closeModal() { document.getElementById('deleteModal').style.display = 'none'; }
document.getElementById('deleteModal').addEventListener('click', function(e){ if(e.target===this) closeModal(); });
</script>
</body>
</html>
