<?php
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php?page=login');
    exit;
}
$pageTitle = 'Mon Panier - Valorys';
$activePage = 'panier';
$extraStyles = "
    .cart-header { background: linear-gradient(135deg,#2A7FAA,#4CAF50); color: white; padding: 50px 0; text-align: center; margin-bottom: 40px; }
    .cart-card { background: white; border-radius: 16px; padding: 22px; box-shadow: 0 4px 15px rgba(0,0,0,.07); }
    .summary-box { background: #f8fbff; border: 1px solid #d9e8f8; border-radius: 16px; padding: 20px; }
";
require __DIR__ . '/partials/header.php';
?>

<div class="cart-header">
    <div class="container">
        <h1><i class="fas fa-cart-shopping me-3"></i>Mon Panier</h1>
        <p class="mb-0" style="opacity:.9">Regroupe plusieurs produits avant de valider une seule commande</p>
    </div>
</div>

<div class="container pb-5">
    <?php if ($flash): ?>
    <div class="alert alert-<?= $flash['type'] === 'success' ? 'success' : 'danger' ?> alert-dismissible fade show">
        <?= htmlspecialchars($flash['message']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <?php if (empty($items)): ?>
    <div class="cart-card text-center py-5">
        <i class="fas fa-cart-shopping fa-4x mb-4" style="color:#ccc"></i>
        <h4 class="text-muted">Votre panier est vide</h4>
        <a href="index.php?page=pharmacie" class="btn btn-primary mt-3">
            <i class="fas fa-pills me-2"></i>Retour au catalogue
        </a>
    </div>
    <?php else: ?>
    <div class="row g-4">
        <div class="col-lg-8">
            <div class="cart-card">
                <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                    <h5 class="mb-0" style="font-weight:700;color:#1a2035">Produits sélectionnés</h5>
                    <div class="d-flex gap-2">
                        <a href="index.php?page=panier&action=clear" class="btn btn-outline-danger btn-sm">
                            <i class="fas fa-trash me-1"></i>Vider le panier
                        </a>
                        <a href="index.php?page=pharmacie" class="btn btn-outline-secondary btn-sm">
                            <i class="fas fa-arrow-left me-1"></i>Continuer les achats
                        </a>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table align-middle">
                        <thead style="background:#f8f9fa">
                            <tr>
                                <th>Produit</th>
                                <th>Prix</th>
                                <th>Quantité</th>
                                <th>Total</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($items as $item): ?>
                        <tr>
                            <td>
                                <div class="fw-semibold"><?= htmlspecialchars($item['nom']) ?></div>
                                <small class="text-muted"><?= htmlspecialchars($item['categorie_nom'] ?? '') ?></small>
                            </td>
                            <td><strong><?= number_format((float)$item['prix_vente'], 2) ?> TND</strong></td>
                            <td><span class="badge bg-light text-dark"><?= (int)$item['quantite'] ?></span></td>
                            <td><strong><?= number_format((float)$item['prix_vente'] * (int)$item['quantite'], 2) ?> TND</strong></td>
                            <td>
                                <a href="index.php?page=panier&action=remove&id=<?= (int)$item['id'] ?>" class="btn btn-outline-danger btn-sm">
                                    <i class="fas fa-times"></i>
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="summary-box mb-4">
                <h5 style="font-weight:700;color:#1a2035">Résumé</h5>
                <div class="d-flex justify-content-between py-2 border-bottom"><span>Sous-total</span><strong><?= number_format((float)$sousTotal, 2) ?> TND</strong></div>
                <div class="d-flex justify-content-between py-2 border-bottom"><span>Réduction</span><strong>- <?= number_format((float)$reduction, 2) ?> TND</strong></div>
                <div class="d-flex justify-content-between py-2 border-bottom"><span>TVA</span><strong><?= number_format((float)$tva, 2) ?> TND</strong></div>
                <div class="d-flex justify-content-between py-2"><span>Total TTC</span><strong style="font-size:1.25rem;color:#2A7FAA"><?= number_format((float)$totalTtc, 2) ?> TND</strong></div>
                <?php if (!empty($promoCode)): ?>
                <div class="mt-2"><span class="badge" style="background:#e3f2fd;color:#1565c0">Code promo: <?= htmlspecialchars($promoCode) ?></span></div>
                <?php endif; ?>
            </div>

            <div class="summary-box mb-4">
                <h6 style="font-weight:700;color:#1a2035">Code promo</h6>
                <form method="POST" action="index.php?page=panier&action=promo" class="d-flex gap-2">
                    <input type="text" name="code_promo" class="form-control" value="<?= htmlspecialchars($promoCode) ?>" placeholder="PROMO10">
                    <button type="submit" class="btn btn-outline-primary">Appliquer</button>
                </form>
            </div>

            <div class="summary-box">
                <h6 style="font-weight:700;color:#1a2035">Valider la commande</h6>
                <form method="POST" action="index.php?page=panier&action=checkout" novalidate>
                    <div class="mb-2">
                        <label class="form-label">Adresse de livraison *</label>
                        <input type="text" name="adresse_livraison" class="form-control" placeholder="12 Rue de la République">
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Ville *</label>
                        <input type="text" name="ville" class="form-control" placeholder="Tunis">
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Code postal *</label>
                        <input type="text" name="code_postal" class="form-control" placeholder="1000">
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Téléphone *</label>
                        <input type="text" name="telephone" class="form-control" placeholder="+216 22 345 678">
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Mode paiement</label>
                        <select name="mode_paiement" class="form-select">
                            <option value="carte">Carte</option>
                            <option value="virement">Virement</option>
                            <option value="especes">Espèces</option>
                            <option value="cheque">Chèque</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Notes</label>
                        <input type="text" name="notes" class="form-control" placeholder="Instructions de livraison...">
                    </div>
                    <input type="hidden" name="code_promo" value="<?= htmlspecialchars($promoCode) ?>">
                    <button type="submit" class="btn btn-success w-100">
                        <i class="fas fa-check me-1"></i>Valider la commande
                    </button>
                </form>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php require __DIR__ . '/partials/footer.php'; ?>
// update
