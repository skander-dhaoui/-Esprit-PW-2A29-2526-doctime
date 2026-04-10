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

    <?php if (isset($_SESSION['user_id'])): ?>
    <div class="d-flex justify-content-end gap-2 mb-3 flex-wrap">
        <a href="index.php?page=mes_commandes" class="btn btn-outline-secondary">
            <i class="fas fa-shopping-bag me-1"></i>Mes commandes
        </a>
        <a href="index.php?page=panier" class="btn btn-primary">
            <i class="fas fa-cart-shopping me-1"></i>Voir le panier
        </a>
    </div>
    <?php endif; ?>

    <!-- Filtres -->
    <div class="filter-card">
        <form method="GET" style="display:flex;gap:12px;flex-wrap:wrap;align-items:flex-end;" novalidate>
            <input type="hidden" name="page" value="parapharmacie">
            <div style="flex:1;min-width:200px">
                <label style="font-size:13px;font-weight:600;margin-bottom:4px;display:block">Recherche</label>
                  <input id="frontSearchInput" type="text" name="search" class="form-control"
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
                <a id="frontReset" href="index.php?page=parapharmacie" class="btn btn-outline-secondary">Tout voir</a>
            </div>
        </form>
    </div>

    <!-- Chatbot conseiller -->
    <div class="filter-card">
        <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-3">
            <div>
                <h5 style="font-weight:700;color:#1a2035;margin-bottom:4px">Chatbot Conseiller Produits</h5>
                <p class="text-muted mb-0">Décris ton besoin, par exemple “j’ai la peau sèche” ou “je cherche quelque chose pour les cheveux”.</p>
            </div>
            <span class="badge" style="background:#e3f2fd;color:#1565c0">IA produit</span>
        </div>
        <form method="POST" action="index.php?page=pharmacie" class="d-flex gap-2 flex-wrap" novalidate>
            <textarea name="chatbot_query" class="form-control" rows="2" placeholder="Décris ton besoin..." style="min-width:260px;flex:1"><?= htmlspecialchars($chatbotQuery ?? '') ?></textarea>
            <button type="submit" class="btn btn-primary align-self-end"><i class="fas fa-robot me-1"></i>Conseiller</button>
        </form>

        <?php if (!empty($chatbotAnswer)): ?>
        <div class="alert alert-info mt-3 mb-0">
            <?= htmlspecialchars($chatbotAnswer) ?>
        </div>
        <div class="row g-3 mt-1">
            <?php foreach ($chatbotSuggestions as $suggestion): ?>
            <div class="col-md-4 col-lg-3">
                <div class="border rounded-3 p-3 h-100 bg-white">
                    <div class="fw-semibold"><?= htmlspecialchars($suggestion['nom']) ?></div>
                    <small class="text-muted d-block mb-2"><?= htmlspecialchars($suggestion['categorie_nom'] ?? '') ?></small>
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="fw-bold"><?= number_format((float)$suggestion['prix_vente'], 2) ?> TND</span>
                        <a href="index.php?page=produit_detail&id=<?= (int)$suggestion['id'] ?>" class="btn btn-sm btn-outline-primary">Voir</a>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>

    <!-- Grille produits -->
    <div class="row g-4">
        <?php if (empty($produits)): ?>
        <div class="col-12 text-center py-5">
            <i class="fas fa-pills fa-4x mb-3" style="color:#ccc"></i>
            <p class="text-muted">Aucun produit trouvé.</p>
            <a href="index.php?page=parapharmacie" class="btn btn-primary">Voir tous les produits</a>
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
                        <div class="produit-prix"><?= number_format($p['prix_vente'], 2) ?> TND</div>
                        <a href="index.php?page=produit_detail&id=<?= $p['id'] ?>"
                           class="btn btn-sm" style="background:#2A7FAA;color:white;border-radius:20px">
                            Détails
                        </a>
                    </div>
                    <?php if (isset($_SESSION['user_id'])): ?>
                    <form method="POST" action="index.php?page=panier&action=add&id=<?= (int)$p['id'] ?>" class="mt-2">
                        <input type="hidden" name="quantite" value="1">
                        <button type="submit" class="btn btn-outline-primary btn-sm w-100">
                            <i class="fas fa-cart-plus me-1"></i>Ajouter au panier
                        </button>
                    </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>
<?php require __DIR__ . '/partials/footer.php'; ?>
<script>
document.addEventListener('DOMContentLoaded', function(){
    const input = document.getElementById('frontSearchInput');
    const cat = document.querySelector('select[name="categorie"]');
    const container = document.querySelector('.container .row.g-4');
    const reset = document.getElementById('frontReset');

    async function fetchProducts() {
        const q = encodeURIComponent(input.value || '');
        const c = encodeURIComponent(cat?.value || '');
        const url = `index.php?page=parapharmacie&ajax=1&search=${q}&categorie=${c}`;
        try {
            const r = await fetch(url);
            const data = await r.json();
            if (!data.success) return;
            const html = data.produits.map(p => `
                <div class="col-md-4 col-lg-3">
                    <div class="produit-card">
                        ${p.image?`<img src="${p.image}" class="produit-img w-100" alt="${p.nom}" onerror="this.src='assets/images/pill_default.png'">`:`<div class="produit-img w-100 d-flex align-items-center justify-content-center"><i class="fas fa-pills fa-3x" style="color:#ccc"></i></div>`}
                        <div class="produit-body">
                            <div class="produit-cat">${p.categorie_nom || ''}</div>
                            <div class="produit-nom">${p.nom}</div>
                            <div class="produit-desc">${(p.description||'').substring(0,80)}${(p.description||'').length>80?'...':''}</div>
                            <div class="d-flex gap-2 mt-2 flex-wrap">${p.prescription?'<span class="badge-prescription"><i class="fas fa-prescription me-1"></i>Conseil expert</span>':''}${p.stock==0?'<span class="badge-rupture">Rupture de stock</span>':''}</div>
                            <div class="d-flex justify-content-between align-items-center mt-3"><div class="produit-prix">${Number(p.prix_vente).toFixed(2)} TND</div><a href="index.php?page=produit_detail&id=${p.id}" class="btn btn-sm" style="background:#2A7FAA;color:white;border-radius:20px">Détails</a></div>
                        </div>
                    </div>
                </div>
            `).join('');
            container.innerHTML = html || '<div class="col-12 text-center py-5"><i class="fas fa-pills fa-4x mb-3" style="color:#ccc"></i><p class="text-muted">Aucun produit trouvé.</p></div>';
        } catch(e) { console.error(e); }
    }

    let timer;
    if (input) input.addEventListener('input', ()=>{ clearTimeout(timer); timer=setTimeout(fetchProducts, 300); });
    if (cat) cat.addEventListener('change', fetchProducts);
});
</script>
