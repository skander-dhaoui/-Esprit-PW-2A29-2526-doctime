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

<!-- Filtres + Bouton -->
<div class="content-card mb-4">
    <form method="GET" style="display:flex;gap:10px;flex-wrap:wrap;align-items:flex-end;" novalidate>
        <input type="hidden" name="page" value="produits_admin">
        <div>
            <label class="form-label">Recherche</label>
            <input type="text" name="search" class="form-control"
                   placeholder="Nom, référence..." value="<?= htmlspecialchars($_GET['search'] ?? '') ?>" style="width:220px">
        </div>
        <div>
            <label class="form-label">Catégorie</label>
            <select name="categorie" class="form-select" style="width:180px">
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
            <select name="statut" class="form-select" style="width:150px">
                <option value="">Tous</option>
                <option value="actif"   <?= ($_GET['statut'] ?? '') === 'actif'   ? 'selected' : '' ?>>Actifs</option>
                <option value="inactif" <?= ($_GET['statut'] ?? '') === 'inactif' ? 'selected' : '' ?>>Inactifs</option>
                <option value="alerte"  <?= ($_GET['statut'] ?? '') === 'alerte'  ? 'selected' : '' ?>>Stock alerte</option>
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
        <h6 style="margin:0;font-weight:700;color:#1e2a3e">
            <?= count($produits) ?> produit(s) trouvé(s)
        </h6>
        <a href="index.php?page=categories_admin" class="btn btn-outline-secondary btn-sm">
            <i class="fas fa-tags me-1"></i>Gérer les catégories
        </a>
    </div>

    <?php if (empty($produits)): ?>
    <div style="text-align:center;padding:50px;color:#999">
        <i class="fas fa-pills fa-3x mb-3" style="opacity:.3"></i>
        <p>Aucun produit trouvé.</p>
        <a href="index.php?page=produits_admin&action=create" class="btn btn-primary">Ajouter le premier produit</a>
    </div>
    <?php else: ?>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
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
                // Pas de stock_alerte, donc on utilise une alerte à 10 unités
                elseif ($p['stock'] <= 10) $stockClass = 'text-warning fw-bold';
            ?>
            <tr>
                <td><code><?= htmlspecialchars($p['slug']) ?></code></td>
                <td>
                    <strong><?= htmlspecialchars($p['nom']) ?></strong>
                    <?php if ($p['description']): ?>
                    <br><small class="text-muted"><?= htmlspecialchars(substr($p['description'], 0, 50)) ?>...</small>
                    <?php endif; ?>
                </td>
                <td><?= htmlspecialchars($p['categorie_nom'] ?? '—') ?></td>
                <td><strong><?= number_format($p['prix'], 2) ?> TND</strong></td>
                <td>
                    <span class="<?= $stockClass ?>">
                        <?= $p['stock'] ?>
                        <?php if ($p['stock'] == 0): ?>
                            <span class="badge badge-annulee ms-1">Rupture</span>
                        <?php elseif ($p['stock'] <= 10): ?>
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
                    <span class="badge badge-<?= ($p['status'] ?? 'inactif') === 'actif' ? 'actif' : 'inactif' ?>">
                        <?= ($p['status'] ?? 'inactif') === 'actif' ? 'Actif' : 'Inactif' ?>
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

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/valo-backoffice.js"></script>
</div></body></html>
