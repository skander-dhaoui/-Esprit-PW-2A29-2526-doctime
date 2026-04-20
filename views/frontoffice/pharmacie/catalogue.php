<?php
$pageTitle = 'Parapharmacie - Valorys';
$activePage = 'pharmacie';
$extraStyles = "
    .produit-card {
        background: white; border-radius: 16px; overflow: hidden;
        box-shadow: 0 4px 15px rgba(0,0,0,.08); transition: transform .3s;
        height: 100%; display: flex; flex-direction: column;
    }
    .produit-card:hover { transform: translateY(-5px); }
    .produit-img { height: 160px; object-fit: contain; background: #f8f9fa; padding: 16px; }
    .produit-body { padding: 18px; flex: 1; display: flex; flex-direction: column; }
    .produit-nom { font-size: 1rem; font-weight: 700; margin-bottom: 6px; color: #1a2035; }
    .produit-cat { font-size: 12px; color: #4CAF50; font-weight: 600; margin-bottom: 8px; }
    .produit-desc { font-size: 13px; color: #666; flex: 1; }
    .produit-prix { font-size: 20px; font-weight: 700; color: #2A7FAA; margin-top: 12px; }
    .badge-prescription { background: #e3f2fd; color: #1565c0; font-size: 11px; padding: 3px 8px; border-radius: 20px; }
    .badge-rupture { background: #fdecea; color: #c62828; font-size: 11px; padding: 3px 8px; border-radius: 20px; }
    .filter-card { background: white; border-radius: 16px; padding: 22px; box-shadow: 0 4px 15px rgba(0,0,0,.05); margin-bottom: 30px; }
";
require __DIR__ . '/partials/header.php';
?>

<div class="pharma-header">
    <div class="container">
        <h1><i class="fas fa-pills me-3"></i>Notre Parapharmacie</h1>
        <p class="mb-0" style="font-size:18px;opacity:.9">Soins, hygiene, beaute et bien-etre</p>
    </div>
</div>

<div class="container pb-5">

    <?php if ($flash): ?>
    <div class="alert alert-<?= $flash['type'] === 'success' ? 'success' : 'danger' ?> alert-dismissible fade show">
        <?= htmlspecialchars($flash['message']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <!-- Filtres -->
    <div class="filter-card">
        <form method="GET" style="display:flex;gap:12px;flex-wrap:wrap;align-items:flex-end;" novalidate>
            <input type="hidden" name="page" value="pharmacie">
            <div style="flex:1;min-width:200px">
                <label style="font-size:13px;font-weight:600;margin-bottom:4px;display:block">Recherche</label>
                <input type="text" name="search" class="form-control"
                       placeholder="Nom du produit..." value="<?= htmlspecialchars($_GET['search'] ?? '') ?>">
            </div>
            <div>
                <label style="font-size:13px;font-weight:600;margin-bottom:4px;display:block">Catégorie</label>
                <select name="categorie" class="form-select" style="width:190px">
                    <option value="">Toutes les catégories</option>
                    <?php foreach ($categories as $cat): ?>
                    <option value="<?= $cat['id'] ?>" <?= ($_GET['categorie'] ?? 0) == $cat['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($cat['nom']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary"><i class="fas fa-search me-1"></i>Rechercher</button>
                <a href="index.php?page=pharmacie" class="btn btn-outline-secondary">Tout voir</a>
            </div>
        </form>
    </div>

    <!-- Grille produits -->
    <div class="row g-4">
        <?php if (empty($produits)): ?>
        <div class="col-12 text-center py-5">
            <i class="fas fa-pills fa-4x mb-3" style="color:#ccc"></i>
            <p class="text-muted">Aucun produit trouvé.</p>
            <a href="index.php?page=pharmacie" class="btn btn-primary">Voir tous les produits</a>
        </div>
        <?php else: ?>
        <?php foreach ($produits as $p): ?>
        <div class="col-md-4 col-lg-3">
            <div class="produit-card">
                <?php if ($p['image']): ?>
                <img src="<?= htmlspecialchars($p['image']) ?>" class="produit-img w-100"
                     alt="<?= htmlspecialchars($p['nom']) ?>" onerror="this.src='assets/images/pill_default.png'">
                <?php else: ?>
                <div class="produit-img w-100 d-flex align-items-center justify-content-center">
                    <i class="fas fa-pills fa-3x" style="color:#ccc"></i>
                </div>
                <?php endif; ?>
                <div class="produit-body">
                    <div class="produit-cat"><?= htmlspecialchars($p['categorie_nom'] ?? '') ?></div>
                    <div class="produit-nom"><?= htmlspecialchars($p['nom']) ?></div>
                    <div class="produit-desc">
                        <?= htmlspecialchars(substr($p['description'] ?? '', 0, 80)) ?>
                        <?= strlen($p['description'] ?? '') > 80 ? '...' : '' ?>
                    </div>
                    <div class="d-flex gap-2 mt-2 flex-wrap">
                        <?php if ($p['prescription']): ?>
                        <span class="badge-prescription"><i class="fas fa-prescription me-1"></i>Conseil expert</span>
                        <?php endif; ?>
                        <?php if ($p['stock'] == 0): ?>
                        <span class="badge-rupture">Rupture de stock</span>
                        <?php endif; ?>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mt-3">
                        <div class="produit-prix"><?= number_format($p['prix'], 2) ?> TND</div>
                        <a href="index.php?page=produit_detail&id=<?= $p['id'] ?>"
                           class="btn btn-sm" style="background:#2A7FAA;color:white;border-radius:20px">
                            Détails
                        </a>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>
<?php require __DIR__ . '/partials/footer.php'; ?>
