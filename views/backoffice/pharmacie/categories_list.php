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

<!-- Filtres -->
<div class="content-card mb-4">
    <form method="GET" style="display:flex;gap:10px;align-items:flex-end;" novalidate>
        <input type="hidden" name="page" value="categories_admin">
        <div>
            <label class="form-label">Recherche</label>
            <input type="text" name="search" class="form-control"
                   placeholder="Nom de catégorie..." value="<?= htmlspecialchars($_GET['search'] ?? '') ?>" style="width:260px">
        </div>
        <button type="submit" class="btn btn-primary"><i class="fas fa-search me-1"></i>Filtrer</button>
        <a href="index.php?page=categories_admin" class="btn btn-outline-secondary">Réinitialiser</a>
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
        <table class="table table-hover align-middle mb-0">
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
            <tr>
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
<script src="assets/js/valo-backoffice.js"></script>
</div></body></html>
