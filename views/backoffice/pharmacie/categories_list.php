<?php
$pageTitle  = 'Gestion des Catégories';
$activePage = 'categories';
require __DIR__ . '/_layout_top.php';
?>

<?php if ($flash): ?>
<div class="flash-box flash-<?= $flash['type'] === 'success' ? 'success' : 'error' ?>">
    <?= htmlspecialchars($flash['message']) ?>
</div>
<?php endif; ?>

<!-- KPI -->
<div class="row g-3 mb-4">
    <div class="col-4">
        <div class="stat-card" style="border-color:#4CAF50">
            <h3 style="color:#4CAF50"><?= $stats['total'] ?></h3>
            <p>Total catégories</p>
        </div>
    </div>
    <div class="col-4">
        <div class="stat-card" style="border-color:#2196F3">
            <h3 style="color:#2196F3"><?= $stats['actives'] ?></h3>
            <p>Actives</p>
        </div>
    </div>
    <div class="col-4">
        <div class="stat-card" style="border-color:#f44336">
            <h3 style="color:#f44336"><?= $stats['inactives'] ?></h3>
            <p>Inactives</p>
        </div>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-lg-4">
        <div class="content-card h-100">
            <h6 style="font-weight:700;color:#1e2a3e">Répartition des catégories</h6>
            <canvas id="categoriesChart" height="220"></canvas>
        </div>
    </div>
</div>

<!-- Filtres -->
<div class="content-card mb-4">
    <form method="GET" style="display:flex;gap:10px;align-items:flex-end;" novalidate>
        <input type="hidden" name="page" value="categories_admin">
        <div>
            <label class="form-label">Recherche</label>
            <input type="text" id="categorySearch" name="search" class="form-control"
                   placeholder="Nom de catégorie..." value="<?= htmlspecialchars($_GET['search'] ?? '') ?>" style="width:260px">
        </div>
        <div>
            <label class="form-label">Tri</label>
            <select id="categorySort" class="form-select" style="width:190px">
                <option value="default">Par défaut</option>
                <option value="nom_asc">Nom A-Z</option>
                <option value="nom_desc">Nom Z-A</option>
                <option value="count_desc">Produits décroissant</option>
                <option value="count_asc">Produits croissant</option>
            </select>
        </div>
        <button type="submit" class="btn btn-primary"><i class="fas fa-search me-1"></i>Filtrer</button>
        <a href="index.php?page=categories_admin" class="btn btn-outline-secondary">Réinitialiser</a>
        <a href="index.php?page=categories_admin&action=export_csv&<?= http_build_query(array_filter(['search' => $_GET['search'] ?? ''])) ?>" class="btn btn-outline-success">
            <i class="fas fa-file-csv me-1"></i>Exporter CSV
        </a>
        <a href="index.php?page=categories_admin&action=create" class="btn btn-success ms-auto">
            <i class="fas fa-plus me-1"></i>Nouvelle catégorie
        </a>
    </form>
</div>

<!-- Table -->
<div class="content-card">
    <?php if (empty($categories)): ?>
    <div style="text-align:center;padding:50px;color:#999">
        <i class="fas fa-tags fa-3x mb-3" style="opacity:.3"></i>
        <p>Aucune catégorie trouvée.</p>
        <a href="index.php?page=categories_admin&action=create" class="btn btn-primary">Créer la première catégorie</a>
    </div>
    <?php else: ?>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0" id="categoriesTable">
            <thead style="background:#f8f9fa">
                <tr>
                    <th>Nom</th>
                    <th>Slug</th>
                    <th>Parent</th>
                    <th>Nb produits</th>
                    <th>Statut</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($categories as $cat): ?>
            <tr data-name="<?= strtolower(htmlspecialchars($cat['nom'])) ?>"
                data-slug="<?= strtolower(htmlspecialchars($cat['slug'])) ?>"
                data-parent="<?= strtolower(htmlspecialchars($cat['parent_nom'] ?? '')) ?>"
                data-count="<?= (int)$cat['nb_produits'] ?>">
                <td>
                    <strong><?= htmlspecialchars($cat['nom']) ?></strong>
                    <?php if ($cat['description']): ?>
                    <br><small class="text-muted"><?= htmlspecialchars(substr($cat['description'], 0, 60)) ?></small>
                    <?php endif; ?>
                </td>
                <td><code><?= htmlspecialchars($cat['slug']) ?></code></td>
                <td><?= htmlspecialchars($cat['parent_nom'] ?? '—') ?></td>
                <td>
                    <span class="badge" style="background:#e3f2fd;color:#1565c0">
                        <?= $cat['nb_produits'] ?> produit(s)
                    </span>
                </td>
                <td>
                    <span class="badge badge-<?= $cat['statut'] ?>">
                        <?= ucfirst($cat['statut']) ?>
                    </span>
                </td>
                <td>
                    <a href="index.php?page=categories_admin&action=edit&id=<?= $cat['id'] ?>"
                       class="btn btn-sm btn-outline-warning" title="Modifier"><i class="fas fa-edit"></i></a>
                    <a href="index.php?page=categories_admin&action=delete&id=<?= $cat['id'] ?>"
                       class="btn btn-sm btn-outline-danger" title="Supprimer"
                       onclick="return confirm('Supprimer la catégorie \'<?= addslashes($cat['nom']) ?>\' ?\nImpossible si des produits y sont liés.')">
                        <i class="fas fa-trash"></i>
                    </a>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="assets/js/valo-backoffice.js"></script>
<script>
(function() {
    const searchInput = document.getElementById('categorySearch');
    const sortSelect = document.getElementById('categorySort');
    const table = document.getElementById('categoriesTable');
    if (table) {
        const tbody = table.querySelector('tbody');
        const rows = Array.from(tbody.querySelectorAll('tr'));

        function matches(row) {
            const q = (searchInput?.value || '').trim().toLowerCase();
            if (!q) return true;
            return [row.dataset.name, row.dataset.slug, row.dataset.parent].join(' ').includes(q);
        }

        function sortRows(list) {
            const mode = sortSelect?.value || 'default';
            const sorted = [...list];
            if (mode === 'nom_asc') sorted.sort((a, b) => a.dataset.name.localeCompare(b.dataset.name));
            else if (mode === 'nom_desc') sorted.sort((a, b) => b.dataset.name.localeCompare(a.dataset.name));
            else if (mode === 'count_asc') sorted.sort((a, b) => Number(a.dataset.count) - Number(b.dataset.count));
            else if (mode === 'count_desc') sorted.sort((a, b) => Number(b.dataset.count) - Number(a.dataset.count));
            return sorted;
        }

        function apply() {
            const visible = rows.filter(matches);
            const ordered = sortRows(visible);
            tbody.innerHTML = '';
            ordered.forEach(row => tbody.appendChild(row));
        }

        [searchInput, sortSelect].forEach(el => {
            if (!el) return;
            el.addEventListener(el === searchInput ? 'input' : 'change', apply);
        });

        apply();
    }

    const ctx = document.getElementById('categoriesChart');
    if (ctx) {
        new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: ['Actives', 'Inactives'],
                datasets: [{
                    data: [<?= (int)$stats['actives'] ?>, <?= (int)$stats['inactives'] ?>],
                    backgroundColor: ['#4CAF50', '#f44336'],
                    borderWidth: 0
                }]
            },
            options: { plugins: { legend: { position: 'bottom' } } }
        });
    }
})();
</script>
</div></body></html>
