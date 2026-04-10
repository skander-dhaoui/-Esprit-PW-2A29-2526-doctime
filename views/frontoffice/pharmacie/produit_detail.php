<?php
$pageTitle = 'Detail Produit Parapharmacie - Valorys';
$activePage = 'pharmacie';
$extraStyles = "
    .detail-wrap { background: white; border-radius: 16px; box-shadow: 0 6px 20px rgba(0,0,0,.08); overflow: hidden; }
    .detail-image { background: #f8f9fa; min-height: 320px; display: flex; align-items: center; justify-content: center; }
    .detail-image img { max-width: 100%; max-height: 320px; object-fit: contain; }
    .detail-content { padding: 26px; }
    .detail-title { font-size: 1.7rem; font-weight: 700; color: #1a2035; }
    .detail-cat { color: #4CAF50; font-weight: 600; margin-bottom: 10px; }
    .detail-price { font-size: 2rem; font-weight: 700; color: #2A7FAA; }
    .pill-badge { display:inline-block; padding:5px 12px; border-radius:20px; font-size:12px; margin-right:6px; }
    .badge-prescription { background:#e3f2fd; color:#1565c0; }
    .badge-rupture { background:#fdecea; color:#c62828; }
    .badge-stock { background:#e8f5e9; color:#2e7d32; }
    .smart-box { background:#f8fbff; border:1px solid #d9e8f8; border-radius:14px; padding:16px; }
";
require __DIR__ . '/partials/header.php';
?>

<div class="pharma-header">
    <div class="container">
        <h1><i class="fas fa-capsules me-2"></i>Detail Produit Parapharmacie</h1>
        <p class="mb-0" style="opacity:.9">Informations completes, conseils et disponibilite</p>
    </div>
</div>

<div class="container pb-5">
    <?php if (!empty($flash)): ?>
    <div class="alert alert-<?= $flash['type'] === 'success' ? 'success' : 'danger' ?> alert-dismissible fade show">
        <?= htmlspecialchars($flash['message']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <div class="mb-3">
        <a href="index.php?page=pharmacie" class="btn btn-outline-secondary btn-sm">
            <i class="fas fa-arrow-left me-1"></i>Retour au catalogue
        </a>
        <?php if (isset($_SESSION['user_id'])): ?>
        <a href="index.php?page=panier" class="btn btn-primary btn-sm ms-2">
            <i class="fas fa-cart-shopping me-1"></i>Voir le panier
        </a>
        <a href="index.php?page=mes_commandes" class="btn btn-outline-primary btn-sm ms-2">
            <i class="fas fa-shopping-bag me-1"></i>Mes commandes
        </a>
        <?php endif; ?>
    </div>

    <div class="detail-wrap">
        <div class="row g-0">
            <div class="col-lg-5">
                <div class="detail-image">
                    <?php if (!empty($produit['image'])): ?>
                    <img src="<?= htmlspecialchars($produit['image']) ?>" alt="<?= htmlspecialchars($produit['nom']) ?>" onerror="this.src='assets/images/pill_default.png'">
                    <?php else: ?>
                    <i class="fas fa-pills fa-5x" style="color:#c5c5c5"></i>
                    <?php endif; ?>
                </div>
            </div>
            <div class="col-lg-7">
                <div class="detail-content">
                    <div class="detail-cat"><?= htmlspecialchars($produit['categorie_nom'] ?? 'Sans categorie') ?></div>
                    <div class="detail-title mb-2"><?= htmlspecialchars($produit['nom']) ?></div>

                    <div class="mb-3">
                        <?php if (!empty($produit['prescription'])): ?>
                        <span class="pill-badge badge-prescription"><i class="fas fa-prescription me-1"></i>Conseil expert recommande</span>
                        <?php endif; ?>
                        <?php if ((int)($produit['stock'] ?? 0) <= 0): ?>
                        <span class="pill-badge badge-rupture">Rupture de stock</span>
                        <?php else: ?>
                        <span class="pill-badge badge-stock">Stock disponible: <?= (int)$produit['stock'] ?></span>
                        <?php endif; ?>
                    </div>

                    <div class="detail-price mb-3"><?= number_format((float)($produit['prix_vente'] ?? 0), 2) ?> TND</div>

                    <div class="text-muted mb-3" style="line-height:1.7">
                        <?= nl2br(htmlspecialchars($produit['description'] ?? 'Aucune description disponible pour ce produit.')) ?>
                    </div>

                    <?php if (!empty($produit['reference'])): ?>
                    <div class="small text-secondary">Reference: <strong><?= htmlspecialchars($produit['reference']) ?></strong></div>
                    <?php endif; ?>

                    <hr>
                    <?php if (!isset($_SESSION['user_id'])): ?>
                    <a href="index.php?page=login" class="btn btn-primary">
                        <i class="fas fa-sign-in-alt me-1"></i>Se connecter pour commander
                    </a>
                    <?php elseif ((int)($produit['stock'] ?? 0) > 0): ?>
                    <form method="POST" action="index.php?page=panier&action=add&id=<?= (int)$produit['id'] ?>" class="mb-3" novalidate>
                        <div class="row g-2 align-items-end">
                            <div class="col-md-4">
                                <label class="form-label">Quantite *</label>
                                <input type="text" name="quantite" class="form-control" value="1" placeholder="1">
                            </div>
                            <div class="col-md-8 d-flex gap-2 flex-wrap">
                                <button type="submit" class="btn btn-outline-primary">
                                    <i class="fas fa-cart-plus me-1"></i>Ajouter au panier
                                </button>
                                <a href="index.php?page=panier" class="btn btn-primary">
                                    <i class="fas fa-cart-shopping me-1"></i>Voir le panier
                                </a>
                            </div>
                        </div>
                    </form>

                    <form method="POST" action="index.php?page=mes_commandes&action=create" id="formCommandeFront" novalidate>
                        <input type="hidden" name="produit_id" value="<?= (int)$produit['id'] ?>">

                        <div class="row g-2">
                            <div class="col-md-4">
                                <label class="form-label">Quantite *</label>
                                <input type="text" name="quantite" class="form-control" value="1" placeholder="1">
                            </div>
                            <div class="col-md-8">
                                <label class="form-label">Adresse de livraison *</label>
                                <input type="text" name="adresse_livraison" class="form-control" placeholder="12 Rue de la Republique">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Ville *</label>
                                <input type="text" name="ville" class="form-control" placeholder="Tunis">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Code postal *</label>
                                <input type="text" name="code_postal" class="form-control" placeholder="1000">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Telephone *</label>
                                <input type="text" name="telephone" class="form-control" placeholder="+216 22 345 678">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Mode paiement</label>
                                <select name="mode_paiement" class="form-select">
                                    <option value="carte">Carte</option>
                                    <option value="virement">Virement</option>
                                    <option value="especes">Especes</option>
                                    <option value="cheque">Cheque</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Notes</label>
                                <input type="text" name="notes" class="form-control" placeholder="Instructions de livraison...">
                            </div>
                        </div>

                        <div class="mt-3 d-flex justify-content-between align-items-center">
                            <small class="text-muted">Total estime: <strong><?= number_format((float)$produit['prix_vente'], 2) ?> TND</strong> (hors TVA, quantite 1)</small>
                            <button type="submit" class="btn btn-success">
                                <i class="fas fa-shopping-cart me-1"></i>Commander maintenant
                            </button>
                        </div>
                    </form>
                    <?php else: ?>
                    <button class="btn btn-secondary" disabled>
                        <i class="fas fa-ban me-1"></i>Indisponible
                    </button>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function showErr(el, msg) {
    el.classList.add('is-invalid');
    let fb = el.parentNode.querySelector('.invalid-feedback');
    if (!fb) { fb = document.createElement('div'); fb.className = 'invalid-feedback'; el.parentNode.appendChild(fb); }
    fb.textContent = msg;
}
function markOk(el) { el.classList.remove('is-invalid'); el.classList.add('is-valid'); }

const form = document.getElementById('formCommandeFront');
if (form) {
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        let valid = true;
        // On nettoie les anciennes erreurs.
        this.querySelectorAll('.is-invalid,.is-valid').forEach(el => el.classList.remove('is-invalid','is-valid'));
        this.querySelectorAll('.invalid-feedback').forEach(el => el.remove());

        const qte = this.querySelector('[name="quantite"]');
        if (!/^\d+$/.test(qte.value.trim()) || parseInt(qte.value.trim(), 10) <= 0) {
            showErr(qte, 'Quantite invalide (entier positif).'); valid = false;
        } else markOk(qte);

        const adr = this.querySelector('[name="adresse_livraison"]');
        if (!adr.value.trim()) { showErr(adr, 'Adresse obligatoire.'); valid = false; } else markOk(adr);

        const ville = this.querySelector('[name="ville"]');
        if (!ville.value.trim()) { showErr(ville, 'Ville obligatoire.'); valid = false; }
        else if (!/^[a-zA-ZÀ-ÿ\s\-]+$/.test(ville.value.trim())) { showErr(ville, 'Ville invalide.'); valid = false; }
        else markOk(ville);

        const cp = this.querySelector('[name="code_postal"]');
        if (!/^\d{4,5}$/.test(cp.value.trim())) { showErr(cp, 'Code postal invalide.'); valid = false; } else markOk(cp);

        const tel = this.querySelector('[name="telephone"]');
        if (!/^[+\d\s]{8,15}$/.test(tel.value.trim())) { showErr(tel, 'Telephone invalide.'); valid = false; } else markOk(tel);

        // Si tout est bon, on envoie.
        if (valid) this.submit();
    });
}
</script>

<?php require __DIR__ . '/partials/footer.php'; ?>
