<?php
// views/backoffice/produit_manage.php
$current_page = 'produits_admin';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Produits - DocTime Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        :root {
            --green:     #4CAF50;
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
        body {
            font-family: 'Segoe UI', system-ui, sans-serif;
            background: var(--gray-50);
            color: var(--gray-900);
            min-height: 100vh;
            display: flex;
        }
        .main-content { margin-left: 260px; flex: 1; padding: 25px; }

        /* Header */
        .page-header {
            background: white; border-radius: var(--radius); padding: 18px 25px;
            margin-bottom: 25px; display: flex; align-items: center;
            justify-content: space-between; box-shadow: var(--shadow);
        }
        .page-header h4 {
            font-size: 20px; font-weight: 700; color: var(--navy);
            margin: 0; display: flex; align-items: center; gap: 10px;
        }
        .page-header h4 i { color: var(--teal); }

        /* Stat cards */
        .stats-row { display: grid; grid-template-columns: repeat(4,1fr); gap: 16px; margin-bottom: 25px; }
        .stat-card {
            background: white; border-radius: var(--radius); padding: 20px;
            box-shadow: var(--shadow); display: flex; align-items: center; gap: 16px;
        }
        .stat-icon {
            width: 48px; height: 48px; border-radius: 12px;
            display: flex; align-items: center; justify-content: center; font-size: 20px;
        }
        .stat-icon.green  { background: #e8f5e9; color: var(--green); }
        .stat-icon.teal   { background: #e0f5f3; color: var(--teal); }
        .stat-icon.orange { background: #fff3e0; color: #ff9800; }
        .stat-icon.red    { background: #fdecea; color: var(--red); }
        .stat-value { font-size: 24px; font-weight: 700; color: var(--navy); }
        .stat-label { font-size: 12px; color: var(--gray-700); margin-top: 2px; }

        /* Filters */
        .filters-bar {
            background: white; border-radius: var(--radius); padding: 18px 20px;
            margin-bottom: 20px; display: flex; align-items: center; gap: 12px;
            box-shadow: var(--shadow); flex-wrap: wrap;
        }
        .filters-bar input[type="text"],
        .filters-bar select {
            padding: 9px 13px; border: 1.5px solid var(--gray-200); border-radius: 8px;
            font-size: 13px; color: var(--gray-900); transition: border-color .2s;
        }
        .filters-bar input[type="text"]:focus,
        .filters-bar select:focus { border-color: var(--teal); outline: none; }
        .filters-bar input[type="text"] { width: 240px; }

        /* Buttons */
        .btn {
            display: inline-flex; align-items: center; gap: 7px;
            padding: 9px 18px; border-radius: 8px; font-size: 13px; font-weight: 600;
            cursor: pointer; border: none; text-decoration: none; transition: all .2s;
        }
        .btn-teal    { background: var(--teal); color: white; }
        .btn-teal:hover { background: var(--teal-dark); color: white; transform: translateY(-1px); }
        .btn-sm { padding: 6px 12px; font-size: 12px; }
        .btn-outline-warning { background: transparent; border: 1.5px solid #f59e0b; color: #b45309; }
        .btn-outline-warning:hover { background: #fffbeb; }
        .btn-outline-danger  { background: transparent; border: 1.5px solid var(--red); color: var(--red); }
        .btn-outline-danger:hover  { background: #fef2f2; }
        .btn-secondary { background: var(--gray-200); color: var(--gray-700); }

        /* Table */
        .content-card {
            background: white; border-radius: var(--radius); padding: 25px;
            box-shadow: var(--shadow);
        }
        .card-title-row {
            display: flex; align-items: center; justify-content: space-between;
            margin-bottom: 20px;
        }
        .card-title-row h5 { font-size: 16px; font-weight: 600; color: var(--navy); margin: 0; }
        table { width: 100%; border-collapse: collapse; font-size: 13px; }
        thead th {
            background: var(--gray-50); padding: 11px 14px; text-align: left;
            font-weight: 600; color: var(--gray-700); text-transform: uppercase;
            font-size: 11px; letter-spacing: .5px; border-bottom: 2px solid var(--gray-200);
        }
        tbody tr { border-bottom: 1px solid var(--gray-200); transition: background .15s; }
        tbody tr:hover { background: #f0fdfa; }
        tbody td { padding: 12px 14px; vertical-align: middle; }

        /* Badges */
        .badge {
            display: inline-block; padding: 4px 10px; border-radius: 20px;
            font-size: 11px; font-weight: 600;
        }
        .badge-actif    { background: #e8f5e9; color: #2e7d32; }
        .badge-inactif  { background: #f3f4f6; color: #6b7280; }
        .badge-rupture  { background: #fdecea; color: #c62828; }

        /* Flash */
        .flash-box { border-radius: 10px; padding: 13px 16px; margin-bottom: 20px; font-size: 14px; display: flex; align-items: center; gap: 10px; }
        .flash-success { background: #e8f5e9; color: #2e7d32; }
        .flash-error   { background: #fdecea; color: #c62828; }

        /* Pagination */
        .pagination { display: flex; gap: 6px; align-items: center; margin-top: 20px; justify-content: center; }
        .pagination a, .pagination span {
            padding: 7px 13px; border-radius: 7px; font-size: 13px; font-weight: 500;
            text-decoration: none; border: 1.5px solid var(--gray-200); color: var(--gray-700);
            transition: all .15s;
        }
        .pagination a:hover { border-color: var(--teal); color: var(--teal); }
        .pagination .active { background: var(--teal); color: white; border-color: var(--teal); }

        /* Empty state */
        .empty-state { text-align: center; padding: 50px 20px; color: var(--gray-700); }
        .empty-state i { font-size: 48px; color: #cbd5e1; margin-bottom: 14px; }
        .empty-state p { font-size: 15px; }

        /* Product image placeholder */
        .prod-img {
            width: 40px; height: 40px; border-radius: 8px; background: var(--gray-200);
            display: inline-flex; align-items: center; justify-content: center;
            color: var(--gray-700); font-size: 16px; overflow: hidden;
        }
        .prod-img img { width: 100%; height: 100%; object-fit: cover; }

        .stock-low  { color: var(--red); font-weight: 700; }
        .stock-ok   { color: var(--green); }
    </style>
</head>
<body>

<?php include __DIR__ . '/sidebar.php'; ?>

<div class="main-content">

    <!-- Page Header -->
    <div class="page-header">
        <h4><i class="fas fa-box"></i> Gestion des Produits</h4>
        <a href="index.php?page=produits_admin&action=create" class="btn btn-teal">
            <i class="fas fa-plus"></i> Nouveau Produit
        </a>
    </div>

    <!-- Flash Messages -->
    <?php if (!empty($flash)): ?>
        <div class="flash-box flash-<?= $flash['type'] ?>">
            <i class="fas fa-<?= $flash['type'] === 'success' ? 'check-circle' : 'exclamation-circle' ?>"></i>
            <?= htmlspecialchars($flash['message']) ?>
        </div>
    <?php endif; ?>

    <!-- Stats -->
    <?php
    $totalProduits  = count($produits ?? []);
    $totalAll       = $total ?? 0;
    $actifs         = count(array_filter($produits ?? [], fn($p) => ($p['status'] ?? '') === 'actif'));
    $rupture        = count(array_filter($produits ?? [], fn($p) => ($p['stock'] ?? 1) == 0));
    ?>
    <div class="stats-row">
        <div class="stat-card">
            <div class="stat-icon green"><i class="fas fa-boxes"></i></div>
            <div>
                <div class="stat-value"><?= $totalAll ?></div>
                <div class="stat-label">Total produits</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon teal"><i class="fas fa-check-circle"></i></div>
            <div>
                <div class="stat-value"><?= count(array_filter($produits ?? [], fn($p) => ($p['status'] ?? '') === 'actif')) ?></div>
                <div class="stat-label">Actifs (page courante)</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon orange"><i class="fas fa-exclamation-triangle"></i></div>
            <div>
                <div class="stat-value"><?= count(array_filter($produits ?? [], fn($p) => ($p['stock'] ?? 1) > 0 && ($p['stock'] ?? 1) <= 5)) ?></div>
                <div class="stat-label">Stock faible (≤5)</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon red"><i class="fas fa-times-circle"></i></div>
            <div>
                <div class="stat-value"><?= $rupture ?></div>
                <div class="stat-label">Rupture de stock</div>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <form method="GET" action="index.php">
        <input type="hidden" name="page" value="produits_admin">
        <div class="filters-bar">
            <input type="text" name="search" placeholder="🔍 Rechercher un produit…"
                   value="<?= htmlspecialchars($_GET['search'] ?? '') ?>">
            <select name="filter">
                <option value="all"    <?= ($_GET['filter'] ?? 'all') === 'all'    ? 'selected' : '' ?>>Tous les statuts</option>
                <option value="actif"  <?= ($_GET['filter'] ?? '') === 'actif'     ? 'selected' : '' ?>>Actifs</option>
                <option value="inactif"<?= ($_GET['filter'] ?? '') === 'inactif'   ? 'selected' : '' ?>>Inactifs</option>
                <option value="rupture"<?= ($_GET['filter'] ?? '') === 'rupture'   ? 'selected' : '' ?>>Rupture</option>
            </select>
            <button type="submit" class="btn btn-teal btn-sm"><i class="fas fa-filter"></i> Filtrer</button>
            <a href="index.php?page=produits_admin" class="btn btn-secondary btn-sm"><i class="fas fa-times"></i> Réinitialiser</a>
            <span style="margin-left:auto; font-size:13px; color:var(--gray-700);">
                <?= $totalAll ?> produit<?= $totalAll > 1 ? 's' : '' ?> trouvé<?= $totalAll > 1 ? 's' : '' ?>
            </span>
        </div>
    </form>

    <!-- Products Table -->
    <div class="content-card">
        <div class="card-title-row">
            <h5><i class="fas fa-list" style="color:var(--teal);margin-right:8px;"></i>Liste des Produits</h5>
            <span style="font-size:12px;color:var(--gray-700);">
                Page <?= $page ?? 1 ?> / <?= $totalPages ?? 1 ?>
            </span>
        </div>

        <?php if (empty($produits)): ?>
            <div class="empty-state">
                <i class="fas fa-box-open"></i>
                <p>Aucun produit trouvé.</p>
                <a href="index.php?page=produits_admin&action=create" class="btn btn-teal" style="margin-top:16px;">
                    <i class="fas fa-plus"></i> Créer le premier produit
                </a>
            </div>
        <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Produit</th>
                        <th>Catégorie</th>
                        <th>Prix</th>
                        <th>Stock</th>
                        <th>Statut</th>
                        <th>Créé le</th>
                        <th style="text-align:right;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($produits as $p): ?>
                    <tr>
                        <td style="color:var(--gray-700);font-size:12px;">#<?= $p['id'] ?></td>
                        <td>
                            <div style="display:flex;align-items:center;gap:10px;">
                                <div class="prod-img">
                                    <?php if (!empty($p['image'])): ?>
                                        <img src="<?= htmlspecialchars($p['image']) ?>" alt="">
                                    <?php else: ?>
                                        <i class="fas fa-pills"></i>
                                    <?php endif; ?>
                                </div>
                                <div>
                                    <div style="font-weight:600;color:var(--navy);">
                                        <?= htmlspecialchars($p['nom']) ?>
                                    </div>
                                    <?php if (!empty($p['prescription'])): ?>
                                        <small style="color:#7c3aed;font-size:11px;">
                                            <i class="fas fa-prescription"></i> Sur ordonnance
                                        </small>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </td>
                        <td style="color:var(--gray-700);"><?= htmlspecialchars($p['categorie_nom'] ?? '—') ?></td>
                        <td style="font-weight:600;">
                            <?= number_format((float)($p['prix'] ?? 0), 2, ',', ' ') ?> TND
                        </td>
                        <td>
                            <?php $stock = (int)($p['stock'] ?? 0); ?>
                            <span class="<?= $stock === 0 ? 'stock-low' : ($stock <= 5 ? 'stock-low' : 'stock-ok') ?>">
                                <?= $stock ?>
                                <?php if ($stock <= 5 && $stock > 0): ?>
                                    <i class="fas fa-exclamation-triangle" title="Stock faible" style="font-size:11px;"></i>
                                <?php endif; ?>
                            </span>
                        </td>
                        <td>
                            <?php $st = $p['status'] ?? 'inactif'; ?>
                            <span class="badge badge-<?= htmlspecialchars($st) ?>">
                                <?= ucfirst($st) ?>
                            </span>
                        </td>
                        <td style="color:var(--gray-700);font-size:12px;">
                            <?= !empty($p['created_at']) ? date('d/m/Y', strtotime($p['created_at'])) : '—' ?>
                        </td>
                        <td style="text-align:right;">
                            <div style="display:flex;gap:6px;justify-content:flex-end;">
                                <a href="index.php?page=produits_admin&action=edit&id=<?= $p['id'] ?>"
                                   class="btn btn-outline-warning btn-sm" title="Modifier">
                                    <i class="fas fa-pen"></i>
                                </a>
                                <button onclick="confirmDelete(<?= $p['id'] ?>, '<?= addslashes(htmlspecialchars($p['nom'])) ?>')"
                                        class="btn btn-outline-danger btn-sm" title="Supprimer">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <!-- Pagination -->
            <?php if (($totalPages ?? 1) > 1): ?>
            <div class="pagination">
                <?php
                $currentPage   = $page ?? 1;
                $filter        = htmlspecialchars($_GET['filter'] ?? 'all');
                $search        = htmlspecialchars($_GET['search'] ?? '');
                $baseUrl = "index.php?page=produits_admin&filter=$filter&search=$search&page=";
                ?>
                <?php if ($currentPage > 1): ?>
                    <a href="<?= $baseUrl . ($currentPage - 1) ?>"><i class="fas fa-chevron-left"></i></a>
                <?php endif; ?>
                <?php for ($i = max(1, $currentPage - 2); $i <= min($totalPages, $currentPage + 2); $i++): ?>
                    <?php if ($i === $currentPage): ?>
                        <span class="active"><?= $i ?></span>
                    <?php else: ?>
                        <a href="<?= $baseUrl . $i ?>"><?= $i ?></a>
                    <?php endif; ?>
                <?php endfor; ?>
                <?php if ($currentPage < $totalPages): ?>
                    <a href="<?= $baseUrl . ($currentPage + 1) ?>"><i class="fas fa-chevron-right"></i></a>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>

</div>

<!-- Delete Confirmation Modal -->
<div id="deleteModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:9999;align-items:center;justify-content:center;">
    <div style="background:white;border-radius:14px;padding:30px;max-width:400px;width:90%;text-align:center;box-shadow:0 20px 60px rgba(0,0,0,.2);">
        <div style="width:60px;height:60px;background:#fdecea;border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 16px;font-size:24px;color:#ef4444;">
            <i class="fas fa-trash"></i>
        </div>
        <h5 style="font-size:18px;font-weight:700;color:#1a2035;margin-bottom:8px;">Supprimer ce produit ?</h5>
        <p id="deleteModalMsg" style="color:#64748b;font-size:14px;margin-bottom:24px;"></p>
        <div style="display:flex;gap:12px;justify-content:center;">
            <button onclick="closeDeleteModal()" style="padding:10px 24px;border-radius:8px;border:1.5px solid #e2e8f0;background:white;font-size:14px;font-weight:600;cursor:pointer;color:#334155;">Annuler</button>
            <a id="deleteConfirmBtn" href="#" style="padding:10px 24px;border-radius:8px;background:#ef4444;color:white;font-size:14px;font-weight:600;text-decoration:none;">Supprimer</a>
        </div>
    </div>
</div>

<script>
function confirmDelete(id, nom) {
    document.getElementById('deleteModal').style.display = 'flex';
    document.getElementById('deleteModalMsg').textContent = 'Voulez-vous vraiment supprimer "' + nom + '" ? Cette action est irréversible.';
    document.getElementById('deleteConfirmBtn').href = 'index.php?page=produits_admin&action=delete&id=' + id;
}
function closeDeleteModal() {
    document.getElementById('deleteModal').style.display = 'none';
}
document.getElementById('deleteModal').addEventListener('click', function(e) {
    if (e.target === this) closeDeleteModal();
});
</script>

</body>
</html>
