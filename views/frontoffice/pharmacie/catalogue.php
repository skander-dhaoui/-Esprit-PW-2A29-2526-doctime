<?php
$pageTitle = 'Parapharmacie - Valorys';
$activePage = 'parapharmacie';
$extraStyles = "
    .patient-parapharma-wrap .pharma-patient-product {
        border-radius: 12px;
        transition: transform 0.2s, box-shadow 0.2s;
    }
    .patient-parapharma-wrap .pharma-patient-product:hover {
        transform: translateY(-4px);
        box-shadow: 0 8px 24px rgba(42, 127, 170, 0.18) !important;
    }
    .patient-parapharma-wrap .pharma-product-img {
        height: 140px;
        object-fit: contain;
        background: #f8fafc;
        padding: 14px;
    }
    .patient-parapharma-wrap .badge-pharma-stock {
        background: #cfe2ff;
        color: #084298;
        font-size: 11px;
        padding: 4px 10px;
        border-radius: 20px;
        font-weight: 600;
    }
    .patient-parapharma-wrap .badge-pharma-rupture {
        background: #fdecea;
        color: #c62828;
        font-size: 11px;
        padding: 4px 10px;
        border-radius: 20px;
    }
    .pharma-chat-fab {
        position: fixed;
        bottom: 1.5rem;
        right: 1.5rem;
        width: 56px;
        height: 56px;
        z-index: 1030;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.35rem;
        border-radius: 50% !important;
    }
";
require __DIR__ . '/partials/header.php';
?>

<div class="container mt-4 patient-parapharma-wrap">
    <?php if (!empty($flash)): ?>
    <div class="alert alert-<?= $flash['type'] === 'success' ? 'success' : 'danger' ?> alert-dismissible fade show">
        <i class="fas fa-<?= $flash['type'] === 'success' ? 'check-circle' : 'exclamation-circle' ?> me-2"></i>
        <?= htmlspecialchars($flash['message']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fermer"></button>
    </div>
    <?php endif; ?>

    <div class="row">
        <div class="col-md-10 mx-auto">
            <div class="card shadow">
                <div class="card-header bg-white">
                    <h3 class="mb-0"><i class="fas fa-pills me-2 text-primary"></i>Parapharmacie</h3>
                </div>
                <div class="card-body">
                    <p class="text-muted mb-4">Liste des produits et conseils — même présentation que &laquo; Mes ordonnances &raquo;.</p>

                    <?php if (isset($_SESSION['user_id'])): ?>
                    <div class="d-flex flex-wrap gap-2 mb-4">
                        <a href="index.php?page=mes_commandes" class="btn btn-outline-secondary btn-sm">
                            <i class="fas fa-shopping-bag me-1"></i>Mes commandes
                        </a>
                        <a href="index.php?page=panier" class="btn btn-primary btn-sm">
                            <i class="fas fa-cart-shopping me-1"></i>Voir le panier
                        </a>
                    </div>
                    <?php endif; ?>

            <!-- Filtres (style « Mes rendez-vous ») -->
            <div class="mb-4 pb-4 border-bottom">
                <h6 class="fw-bold mb-3">
                    <i class="fas fa-filter me-2 text-secondary"></i>Filtrer
                </h6>
                <form method="GET" class="row g-3 align-items-end" novalidate>
                    <input type="hidden" name="page" value="parapharmacie">
                    <div class="col-md-4 col-lg">
                        <label class="form-label fw-semibold small">Recherche</label>
                        <input id="frontSearchInput" type="text" name="search" class="form-control"
                            placeholder="Nom, mot-clé…" value="<?= htmlspecialchars($_GET['search'] ?? '') ?>">
                    </div>
                    <div class="col-md-4 col-lg-3">
                        <label class="form-label fw-semibold small">Catégorie</label>
                        <select name="categorie" class="form-select">
                            <option value="">Toutes les catégories</option>
                            <?php foreach ($categories as $cat): ?>
                            <option value="<?= $cat['id'] ?>" <?= ($_GET['categorie'] ?? 0) == $cat['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($cat['nom']) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4 col-lg-auto d-flex flex-wrap gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-search me-1"></i>Rechercher
                        </button>
                        <a id="frontReset" href="index.php?page=parapharmacie" class="btn btn-secondary">
                            Réinitialiser
                        </a>
                    </div>
                </form>
            </div>

            <!-- Chatbot -->
            <div id="chatbot-conseiller" class="mb-4 pb-4 border-bottom">
                <h6 class="fw-bold mb-3">
                    <i class="fas fa-robot me-2 text-primary"></i>Chatbot conseiller produits
                </h6>
                <p class="text-muted small mb-3">
                    Décris ton besoin : le conseiller s’appuie sur les fiches du catalogue (ex. toux, gel hydroalcoolique, vitamines…).
                </p>
                <form method="POST" action="index.php?page=pharmacie" class="row g-2 align-items-end" novalidate>
                    <div class="col-md">
                        <label class="form-label fw-semibold small">Votre question</label>
                        <textarea name="chatbot_query" class="form-control" rows="2" placeholder="Ex. sirop pour la toux, soins pour les cheveux…"><?= htmlspecialchars($chatbotQuery ?? '') ?></textarea>
                    </div>
                    <div class="col-md-auto">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-comment-medical me-1"></i>Conseiller
                        </button>
                    </div>
                </form>

                <?php if (!empty($chatbotAnswer)): ?>
                <div class="alert alert-success border-0 border-start border-5 border-success mt-3 mb-0" role="status">
                    <i class="fas fa-circle-check me-2"></i><?= htmlspecialchars($chatbotAnswer) ?>
                </div>
                <?php if (!empty($chatbotSuggestions)): ?>
                <div class="row g-3 mt-2">
                    <?php foreach ($chatbotSuggestions as $suggestion): ?>
                    <div class="col-md-4 col-lg-3">
                        <div class="card h-100 border-0 shadow-sm border-start border-primary border-4 pharma-product-suggestion">
                            <div class="card-body">
                                <div class="fw-semibold"><?= htmlspecialchars($suggestion['nom']) ?></div>
                                <small class="text-muted d-block mb-2"><?= htmlspecialchars($suggestion['categorie_nom'] ?? '') ?></small>
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="fw-bold text-primary"><?= number_format((float)$suggestion['prix_vente'], 2) ?> TND</span>
                                    <a href="index.php?page=produit_detail&id=<?= (int)$suggestion['id'] ?>" class="btn btn-sm btn-outline-primary">
                                        <i class="fas fa-eye me-1"></i>Voir
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
                <?php endif; ?>
            </div>

            <!-- Grille produits -->
            <h6 class="fw-bold mb-3">
                <i class="fas fa-th-large me-2 text-secondary"></i>Nos produits
            </h6>
            <div class="row g-3" id="pharmaProductGrid">
                <?php if (empty($produits)): ?>
                <div class="col-12 text-center py-5">
                    <i class="fas fa-pills fa-3x mb-3 text-secondary opacity-50"></i>
                    <p class="text-muted mb-3">Aucun produit trouvé.</p>
                    <a href="index.php?page=parapharmacie" class="btn btn-primary rounded-pill">Voir tout le catalogue</a>
                </div>
                <?php else: ?>
                <?php foreach ($produits as $p): ?>
                <div class="col-md-6 col-lg-4">
                    <div class="card h-100 border-0 shadow-sm border-start border-primary border-4 pharma-patient-product overflow-hidden">
                        <?php if (!empty($p['image'])): ?>
                        <img src="<?= htmlspecialchars($p['image']) ?>" class="pharma-product-img w-100"
                             alt="<?= htmlspecialchars($p['nom']) ?>" onerror="this.src='assets/images/pill_default.png'">
                        <?php else: ?>
                        <div class="pharma-product-img w-100 d-flex align-items-center justify-content-center">
                            <i class="fas fa-pills fa-3x text-secondary opacity-40"></i>
                        </div>
                        <?php endif; ?>
                        <div class="card-body d-flex flex-column">
                            <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
                                <div class="fw-bold text-primary flex-grow-1"><?= htmlspecialchars($p['nom']) ?></div>
                                <?php if ((int)($p['stock'] ?? 0) > 0): ?>
                                <span class="badge-pharma-stock">En stock</span>
                                <?php else: ?>
                                <span class="badge-pharma-rupture">Rupture</span>
                                <?php endif; ?>
                            </div>
                            <small class="text-muted d-block mb-2">
                                <i class="fas fa-tag me-1"></i><?= htmlspecialchars($p['categorie_nom'] ?? '') ?>
                            </small>
                            <p class="text-muted small flex-grow-1 mb-3">
                                <?= htmlspecialchars(substr($p['description'] ?? '', 0, 100)) ?><?= strlen($p['description'] ?? '') > 100 ? '…' : '' ?>
                            </p>
                            <?php if (!empty($p['prescription'])): ?>
                            <div class="mb-2 small"><span class="badge bg-light text-primary border"><i class="fas fa-prescription me-1"></i>Conseil expert</span></div>
                            <?php endif; ?>
                            <div class="d-flex align-items-center justify-content-between gap-2 mb-3">
                                <span class="fs-5 fw-bold" style="color:#2A7FAA"><?= number_format((float)$p['prix_vente'], 2) ?> TND</span>
                            </div>
                            <div class="d-flex flex-wrap gap-2 mt-auto">
                                <a href="index.php?page=produit_detail&id=<?= (int)$p['id'] ?>"
                                   class="btn btn-outline-secondary btn-sm flex-grow-1">
                                    <i class="fas fa-eye me-1"></i>Détails
                                </a>
                                <?php if (isset($_SESSION['user_id'])): ?>
                                <form method="POST" action="index.php?page=panier&action=add&id=<?= (int)$p['id'] ?>" class="flex-grow-1">
                                    <input type="hidden" name="quantite" value="1">
                                    <button type="submit" class="btn btn-primary btn-sm w-100">
                                        <i class="fas fa-cart-plus me-1"></i>Panier
                                    </button>
                                </form>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
                <?php endif; ?>
            </div>
                </div><!-- /.card-body -->
            </div><!-- /.card -->
        </div><!-- /.col-md-10 -->
    </div><!-- /.row -->
</div><!-- /.container -->

<a href="#chatbot-conseiller" class="btn btn-primary shadow pharma-chat-fab" title="Conseiller produits" aria-label="Ouvrir le conseiller produits">
    <i class="fas fa-comments"></i>
</a>

<?php require __DIR__ . '/partials/footer.php'; ?>
<script>
document.addEventListener('DOMContentLoaded', function(){
    const input = document.getElementById('frontSearchInput');
    const cat = document.querySelector('form[method="GET"] select[name="categorie"]');
    const container = document.getElementById('pharmaProductGrid');
    if (!container) return;

    async function fetchProducts() {
        const q = encodeURIComponent(input ? input.value || '' : '');
        const c = encodeURIComponent(cat ? cat.value || '' : '');
        const url = 'index.php?page=parapharmacie&ajax=1&search=' + q + '&categorie=' + c;
        try {
            const r = await fetch(url);
            const data = await r.json();
            if (!data.success) return;
            const logged = <?= isset($_SESSION['user_id']) ? 'true' : 'false' ?>;
            const html = (data.produits || []).map(function(p) {
                const img = p.image
                    ? '<img src="'+String(p.image).replace(/"/g,'&quot;')+'" class="pharma-product-img w-100" alt="" onerror="this.src=\'assets/images/pill_default.png\'">'
                    : '<div class="pharma-product-img w-100 d-flex align-items-center justify-content-center"><i class="fas fa-pills fa-3x text-secondary opacity-40"></i></div>';
                const stockBadge = (parseInt(p.stock,10) || 0) > 0
                    ? '<span class="badge-pharma-stock">En stock</span>'
                    : '<span class="badge-pharma-rupture">Rupture</span>';
                const desc = (p.description || '').substring(0,100) + ((p.description || '').length > 100 ? '…' : '');
                const presc = p.prescription ? '<div class="mb-2 small"><span class="badge bg-light text-primary border"><i class="fas fa-prescription me-1"></i>Conseil expert</span></div>' : '';
                const panier = logged ? '<form method="POST" action="index.php?page=panier&action=add&id='+p.id+'" class="flex-grow-1"><input type="hidden" name="quantite" value="1"><button type="submit" class="btn btn-primary btn-sm w-100"><i class="fas fa-cart-plus me-1"></i>Panier</button></form>' : '';
                return '<div class="col-md-6 col-lg-4"><div class="card h-100 border-0 shadow-sm border-start border-primary border-4 pharma-patient-product overflow-hidden">' + img +
                    '<div class="card-body d-flex flex-column">' +
                    '<div class="d-flex justify-content-between align-items-start gap-2 mb-2">' +
                    '<div class="fw-bold text-primary flex-grow-1">' + escapeHtml(p.nom) + '</div>' + stockBadge + '</div>' +
                    '<small class="text-muted d-block mb-2"><i class="fas fa-tag me-1"></i>' + escapeHtml(p.categorie_nom || '') + '</small>' +
                    '<p class="text-muted small flex-grow-1 mb-3">' + escapeHtml(desc) + '</p>' + presc +
                    '<div class="d-flex align-items-center justify-content-between gap-2 mb-3"><span class="fs-5 fw-bold" style="color:#2A7FAA">' + Number(p.prix_vente).toFixed(2) + ' TND</span></div>' +
                    '<div class="d-flex flex-wrap gap-2 mt-auto"><a href="index.php?page=produit_detail&id=' + p.id + '" class="btn btn-outline-secondary btn-sm flex-grow-1"><i class="fas fa-eye me-1"></i>Détails</a>' + panier + '</div>' +
                    '</div></div></div>';
            }).join('');
            container.innerHTML = html || '<div class="col-12 text-center py-5"><i class="fas fa-pills fa-3x mb-3 text-secondary opacity-50"></i><p class="text-muted">Aucun produit trouvé.</p></div>';
        } catch (e) { console.error(e); }
    }
    function escapeHtml(s) {
        const d = document.createElement('div');
        d.textContent = s;
        return d.innerHTML;
    }

    let timer;
    if (input) input.addEventListener('input', function(){ clearTimeout(timer); timer = setTimeout(fetchProducts, 300); });
    if (cat) cat.addEventListener('change', fetchProducts);
});
</script>
