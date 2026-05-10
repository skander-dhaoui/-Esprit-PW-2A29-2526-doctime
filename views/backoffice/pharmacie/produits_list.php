<?php
$pageTitle  = 'Gestion des Produits';
$currentPage = 'produits_admin';
require __DIR__ . '/../layout_header.php';
?>

<?php if ($flash): ?>
<div class="flash-box flash-<?= $flash['type'] === 'success' ? 'success' : 'error' ?>">
    <i class="fas fa-<?= $flash['type'] === 'success' ? 'check-circle' : 'exclamation-triangle' ?> me-2"></i>
    <?= htmlspecialchars($flash['message']) ?>
</div>
<?php endif; ?>

<!-- KPI Cards -->
<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="stat-card">
            <div class="stat-icon" style="background: rgba(76, 175, 80, 0.15); color: #4CAF50;">
                <i class="fas fa-pills"></i>
            </div>
            <div class="stat-value" style="color: #4CAF50;"><?= $stats['total'] ?></div>
            <div class="stat-label">Total produits</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="stat-card">
            <div class="stat-icon" style="background: rgba(33, 150, 243, 0.15); color: #2196F3;">
                <i class="fas fa-check-circle"></i>
            </div>
            <div class="stat-value" style="color: #2196F3;"><?= $stats['actifs'] ?></div>
            <div class="stat-label">Actifs</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="stat-card">
            <div class="stat-icon" style="background: rgba(255, 152, 0, 0.15); color: #FF9800;">
                <i class="fas fa-exclamation-triangle"></i>
            </div>
            <div class="stat-value" style="color: #FF9800;"><?= $stats['alerte'] ?></div>
            <div class="stat-label">Stock alerte</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="stat-card">
            <div class="stat-icon" style="background: rgba(244, 67, 54, 0.15); color: #f44336;">
                <i class="fas fa-times-circle"></i>
            </div>
            <div class="stat-value" style="color: #f44336;"><?= $stats['rupture'] ?></div>
            <div class="stat-label">En rupture</div>
        </div>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-lg-4">
        <div class="content-card h-100">
            <h6 style="font-weight:700;color:#1e2a3e">Répartition visuelle</h6>
            <canvas id="productsChart" height="220"></canvas>
        </div>
    </div>
</div>

<!-- Filtres + Bouton -->
<div class="content-card mb-4">
    <form method="GET" style="display:flex;gap:10px;flex-wrap:wrap;align-items:flex-end;" novalidate>
        <input type="hidden" name="page" value="produits_admin">
        <div>
            <label class="form-label">Recherche</label>
            <input type="text" id="liveSearchInput" name="search" class="form-control"
                   placeholder="Nom, référence..." value="<?= htmlspecialchars($_GET['search'] ?? '') ?>" style="width:220px">
        </div>
        <div>
            <label class="form-label">Catégorie</label>
            <select id="liveCategoryFilter" name="categorie" class="form-select" style="width:180px">
                <option value="0">Toutes</option>
                <?php foreach ($categories as $cat): ?>
                <option value="<?= $cat['id'] ?>" <?= ($_GET['categorie'] ?? 0) == $cat['id'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($cat['nom']) ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label class="form-label">Statut</label>
            <select id="liveStatusFilter" name="statut" class="form-select" style="width:150px">
                <option value="">Tous</option>
                <option value="actif"   <?= ($_GET['statut'] ?? '') === 'actif'   ? 'selected' : '' ?>>Actifs</option>
                <option value="inactif" <?= ($_GET['statut'] ?? '') === 'inactif' ? 'selected' : '' ?>>Inactifs</option>
                <option value="alerte"  <?= ($_GET['statut'] ?? '') === 'alerte'  ? 'selected' : '' ?>>Stock alerte</option>
            </select>
        </div>
        <div>
            <label class="form-label">Tri</label>
            <select id="liveSort" class="form-select" style="width:210px">
                <option value="default">Par défaut</option>
                <option value="nom_asc">Nom A-Z</option>
                <option value="nom_desc">Nom Z-A</option>
                <option value="prix_asc">Prix croissant</option>
                <option value="prix_desc">Prix décroissant</option>
                <option value="stock_asc">Stock croissant</option>
                <option value="stock_desc">Stock décroissant</option>
            </select>
        </div>
        <button type="submit" class="btn btn-primary"><i class="fas fa-search me-1"></i>Filtrer</button>
        <a href="index.php?page=produits_admin" class="btn btn-outline-secondary">Réinitialiser</a>
        <a href="index.php?page=produits_admin&action=create" class="btn btn-success ms-auto">
            <i class="fas fa-plus me-1"></i>Nouveau produit
        </a>
    </form>
</div>

<!-- Table -->
<div class="content-card">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;">
        <h6 id="resultCount" style="margin:0;font-weight:700;color:#1e2a3e">
            <?= count($produits) ?> produit(s) trouvé(s)
        </h6>
        <div class="d-flex gap-2 flex-wrap">
            <button type="button" class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#promoModal">
                <i class="fas fa-ticket-alt me-1"></i>Gérer codes promo
            </button>
            <a href="index.php?page=produits_admin&action=export_csv&<?= http_build_query(array_filter([
                'search' => $_GET['search'] ?? '',
                'categorie' => $_GET['categorie'] ?? '',
                'statut' => $_GET['statut'] ?? '',
            ])) ?>" class="btn btn-outline-success btn-sm">
                <i class="fas fa-file-csv me-1"></i>Exporter CSV
            </a>
            <a href="index.php?page=categories_admin" class="btn btn-outline-secondary btn-sm">
                <i class="fas fa-tags me-1"></i>Gérer les catégories
            </a>
        </div>
    </div>

    <?php if (empty($produits)): ?>
    <div style="text-align:center;padding:50px;color:#999">
        <i class="fas fa-pills fa-3x mb-3" style="opacity:.3"></i>
        <p>Aucun produit trouvé.</p>
        <a href="index.php?page=produits_admin&action=create" class="btn btn-primary">Ajouter le premier produit</a>
    </div>
    <?php else: ?>
    <div class="table-responsive">
        <table class="table table-modern mb-0" id="produitsTable">
            <thead>
                <tr>
                    <th>Référence</th>
                    <th>Produit</th>
                    <th>Catégorie</th>
                    <th>Prix</th>
                    <th>Stock</th>
                    <th>Statut</th>
                    <th style="width: 120px;">Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($produits as $p): ?>
            <?php
                $stockClass = '';
                $stockBadge = '';
                if ($p['stock'] == 0) {
                    $stockClass = 'text-danger fw-bold';
                    $stockBadge = '<span class="badge badge-annulee ms-1">Rupture</span>';
                } elseif ($p['stock'] <= 5) {
                    $stockClass = 'text-warning fw-bold';
                    $stockBadge = '<span class="badge badge-alerte ms-1">Alerte</span>';
                }
            ?>
            <tr>
                <td><code class="bg-light px-2 py-1 rounded"><?= htmlspecialchars($p['reference'] ?? 'REF-'.$p['id']) ?></code></td>
                <td>
                    <div class="d-flex align-items-center">
                        <div class="bg-light rounded p-2 me-3" style="width: 50px; height: 50px; display: flex; align-items: center; justify-content: center;">
                            <i class="fas fa-pills text-muted"></i>
                        </div>
                        <div>
                            <strong class="d-block"><?= htmlspecialchars($p['nom']) ?></strong>
                            <small class="text-muted"><?= htmlspecialchars(substr($p['description'] ?? '', 0, 40)) ?>...</small>
                        </div>
                    </div>
                </td>
                <td><span class="badge bg-light text-dark border"><?= htmlspecialchars($p['categorie_nom'] ?? '—') ?></span></td>
                <td><strong style="color: #0fa99b;"><?= number_format($p['prix'] ?? 0, 2) ?> TND</strong></td>
                <td>
                    <span class="<?= $stockClass ?>"><?= $p['stock'] ?></span>
                    <?= $stockBadge ?>
                </td>
                <td>
                    <span class="badge badge-<?= ($p['status'] ?? 'actif') === 'actif' ? 'actif' : 'inactif' ?>">
                        <?= ($p['status'] ?? 'actif') === 'actif' ? 'Actif' : 'Inactif' ?>
                    </span>
                </td>
                <td>
                    <a href="index.php?page=produits_admin&action=show&id=<?= $p['id'] ?>"
                       class="btn-action btn-action-view" title="Voir"><i class="fas fa-eye"></i></a>
                    <a href="index.php?page=produits_admin&action=edit&id=<?= $p['id'] ?>"
                       class="btn-action btn-action-edit" title="Modifier"><i class="fas fa-edit"></i></a>
                    <a href="index.php?page=produits_admin&action=delete&id=<?= $p['id'] ?>"
                       class="btn btn-sm btn-outline-danger" title="Supprimer"
                       onclick="return confirm('Supprimer ce produit ?')"><i class="fas fa-trash"></i></a>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>
<!-- Promo Modal -->
<div class="modal fade" id="promoModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-md modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Gestion des codes promo</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
            </div>
            <div class="modal-body">
                <form method="POST" action="index.php?page=produits_admin&action=add_promo" class="row g-2 align-items-end">
                    <div class="col-7"><label class="form-label">Code</label><input name="code" class="form-control" required></div>
                    <div class="col-3"><label class="form-label">Taux %</label><input name="rate" type="number" step="0.1" class="form-control" required></div>
                    <div class="col-2"><button class="btn btn-primary w-100" type="submit">Ajouter</button></div>
                </form>

                <hr>
                <h6>Codes existants</h6>
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead><tr><th>Code</th><th>Taux</th><th></th></tr></thead>
                        <tbody>
                        <?php $promos = $promos ?? []; foreach ($promos as $c=>$r): ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($c) ?></strong></td>
                                <td><?= number_format((float)$r,2) ?> %</td>
                                <td><a class="btn btn-sm btn-outline-danger" href="index.php?page=produits_admin&action=delete_promo&code=<?= urlencode($c) ?>">Supprimer</a></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button></div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="assets/js/valo-backoffice.js"></script>
<script>
(function() {
    const searchInput = document.getElementById('liveSearchInput');
    const categoryFilter = document.getElementById('liveCategoryFilter');
    const statusFilter = document.getElementById('liveStatusFilter');
    const sortSelect = document.getElementById('liveSort');
    const table = document.getElementById('produitsTable');
    const countBox = document.getElementById('resultCount');

    if (!table) return;

    const tbody = table.querySelector('tbody');
    const originalRows = Array.from(tbody.querySelectorAll('tr'));

    function rowMatches(row) {
        const q = (searchInput?.value || '').trim().toLowerCase();
        const cat = categoryFilter?.value || '0';
        const statut = statusFilter?.value || '';

        const rowText = (row.dataset.nom + ' ' + row.dataset.ref + ' ' + row.dataset.catName).toLowerCase();
        if (q && !rowText.includes(q)) return false;

        if (cat !== '0' && String(row.dataset.cat) !== String(cat)) return false;

        if (statut === 'actif' && row.dataset.statut !== 'actif') return false;
        if (statut === 'inactif' && row.dataset.statut !== 'inactif') return false;
        if (statut === 'alerte' && !((Number(row.dataset.stock) > 0) && (Number(row.dataset.stock) <= Number(row.dataset.alerte)))) return false;

        return true;
    }

    function sortRows(rows) {
        const mode = sortSelect?.value || 'default';
        const sorted = [...rows];

        if (mode === 'nom_asc') sorted.sort((a, b) => a.dataset.nom.localeCompare(b.dataset.nom));
        else if (mode === 'nom_desc') sorted.sort((a, b) => b.dataset.nom.localeCompare(a.dataset.nom));
        else if (mode === 'prix_asc') sorted.sort((a, b) => Number(a.dataset.prix) - Number(b.dataset.prix));
        else if (mode === 'prix_desc') sorted.sort((a, b) => Number(b.dataset.prix) - Number(a.dataset.prix));
        else if (mode === 'stock_asc') sorted.sort((a, b) => Number(a.dataset.stock) - Number(b.dataset.stock));
        else if (mode === 'stock_desc') sorted.sort((a, b) => Number(b.dataset.stock) - Number(a.dataset.stock));

        return sorted;
    }

    function applyFilters() {
        const visible = originalRows.filter(rowMatches);
        const ordered = sortRows(visible);

        tbody.innerHTML = '';
        ordered.forEach(row => tbody.appendChild(row));

        if (countBox) {
            countBox.textContent = visible.length + ' produit(s) trouvé(s)';
        }
    }

    [searchInput, categoryFilter, statusFilter, sortSelect].forEach(el => {
        if (!el) return;
        const evt = el === searchInput ? 'input' : 'change';
        el.addEventListener(evt, applyFilters);
    });

    applyFilters();

    const productsChart = document.getElementById('productsChart');
    if (productsChart) {
        new Chart(productsChart, {
            type: 'doughnut',
            data: {
                labels: ['Actifs', 'Stock alerte', 'Rupture'],
                datasets: [{
                    data: [<?= (int)$stats['actifs'] ?>, <?= (int)$stats['alerte'] ?>, <?= (int)$stats['rupture'] ?>],
                    backgroundColor: ['#4CAF50', '#FF9800', '#f44336'],
                    borderWidth: 0
                }]
            },
            options: { plugins: { legend: { position: 'bottom' } } }
        });
    }
})();
</script>
<?php require __DIR__ . '/_layout_bottom.php'; ?>
