<?php
$pageTitle  = 'Gestion des Produits';
$activePage = 'produits';
require __DIR__ . '/_layout_top.php';
?>

<?php if ($flash): ?>
<div class="flash-box flash-<?= $flash['type'] === 'success' ? 'success' : 'error' ?>">
    <i class="fas fa-<?= $flash['type'] === 'success' ? 'check-circle' : 'exclamation-triangle' ?> me-2"></i>
    <?= htmlspecialchars($flash['message']) ?>
</div>
<?php endif; ?>

<!-- KPI -->
<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="stat-card" style="border-color:#4CAF50">
            <i class="fas fa-pills fa-2x" style="color:#4CAF5055"></i>
            <h3 style="color:#4CAF50"><?= $stats['total'] ?></h3>
            <p>Total produits</p>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="stat-card" style="border-color:#2196F3">
            <i class="fas fa-check-circle fa-2x" style="color:#2196F355"></i>
            <h3 style="color:#2196F3"><?= $stats['actifs'] ?></h3>
            <p>Actifs</p>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="stat-card" style="border-color:#FF9800">
            <i class="fas fa-exclamation-triangle fa-2x" style="color:#FF980055"></i>
            <h3 style="color:#FF9800"><?= $stats['alerte'] ?></h3>
            <p>Stock alerte</p>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="stat-card" style="border-color:#f44336">
            <i class="fas fa-times-circle fa-2x" style="color:#f4433655"></i>
            <h3 style="color:#f44336"><?= $stats['rupture'] ?></h3>
            <p>En rupture</p>
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
        <table class="table table-hover align-middle mb-0" id="produitsTable">
            <thead style="background:#f8f9fa">
                <tr>
                    <th>Référence</th>
                    <th>Produit</th>
                    <th>Catégorie</th>
                    <th>Prix vente</th>
                    <th>Stock</th>
                    <th>Conseil expert</th>
                    <th>Statut</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($produits as $p): ?>
            <?php
                $stockClass = '';
                if ($p['stock'] == 0) $stockClass = 'text-danger fw-bold';
                elseif ($p['stock'] <= $p['stock_alerte']) $stockClass = 'text-warning fw-bold';
            ?>
            <tr
                data-ref="<?= strtolower(htmlspecialchars((string)$p['reference'])) ?>"
                data-nom="<?= strtolower(htmlspecialchars((string)$p['nom'])) ?>"
                data-cat="<?= (int)($p['categorie_id'] ?? 0) ?>"
                data-cat-name="<?= strtolower(htmlspecialchars((string)($p['categorie_nom'] ?? ''))) ?>"
                data-prix="<?= (float)$p['prix_vente'] ?>"
                data-stock="<?= (int)$p['stock'] ?>"
                data-statut="<?= $p['actif'] ? 'actif' : 'inactif' ?>"
                data-alerte="<?= (int)$p['stock_alerte'] ?>"
            >
                <td><code><?= htmlspecialchars($p['reference']) ?></code></td>
                <td>
                    <strong><?= htmlspecialchars($p['nom']) ?></strong>
                    <?php if ($p['description']): ?>
                    <br><small class="text-muted"><?= htmlspecialchars(substr($p['description'], 0, 50)) ?>...</small>
                    <?php endif; ?>
                </td>
                <td><?= htmlspecialchars($p['categorie_nom'] ?? '—') ?></td>
                <td><strong><?= number_format($p['prix_vente'], 2) ?> TND</strong></td>
                <td>
                    <span class="<?= $stockClass ?>">
                        <?= $p['stock'] ?>
                        <?php if ($p['stock'] == 0): ?>
                            <span class="badge badge-annulee ms-1">Rupture</span>
                        <?php elseif ($p['stock'] <= $p['stock_alerte']): ?>
                            <span class="badge badge-en_attente ms-1">Alerte</span>
                        <?php endif; ?>
                    </span>
                </td>
                <td>
                    <?php if ($p['prescription']): ?>
                    <span class="badge" style="background:#e3f2fd;color:#1565c0"><i class="fas fa-prescription me-1"></i>Recommande</span>
                    <?php else: ?>
                    <span class="badge" style="background:#f3f3f3;color:#666">Optionnel</span>
                    <?php endif; ?>
                </td>
                <td>
                    <span class="badge badge-<?= $p['actif'] ? 'actif' : 'inactif' ?>">
                        <?= $p['actif'] ? 'Actif' : 'Inactif' ?>
                    </span>
                </td>
                <td>
                    <a href="index.php?page=produits_admin&action=show&id=<?= $p['id'] ?>"
                       class="btn btn-sm btn-outline-info" title="Voir"><i class="fas fa-eye"></i></a>
                    <a href="index.php?page=produits_admin&action=edit&id=<?= $p['id'] ?>"
                       class="btn btn-sm btn-outline-warning" title="Modifier"><i class="fas fa-edit"></i></a>
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
</div></body></html>
