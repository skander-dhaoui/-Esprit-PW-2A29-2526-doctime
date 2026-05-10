<?php
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php?page=login');
    exit;
}
$pageTitle = 'Modifier ma commande - Valorys';
$activePage = 'mes_commandes';
$extraStyles = "
    .edit-wrap { background: white; border-radius: 14px; padding: 24px; box-shadow: 0 4px 15px rgba(0,0,0,.07); }
";
require __DIR__ . '/partials/header.php';
?>

<div class="pharma-header">
    <div class="container">
        <h1><i class="fas fa-edit me-2"></i>Modifier ma commande</h1>
        <p class="mb-0" style="opacity:.9"><?= htmlspecialchars($commande['numero_commande']) ?></p>
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
        <a href="index.php?page=mes_commandes" class="btn btn-outline-secondary btn-sm">
            <i class="fas fa-arrow-left me-1"></i>Retour mes commandes
        </a>
    </div>

    <div class="edit-wrap">
        <h6 style="font-weight:700;color:#1e2a3e">Articles (lecture seule)</h6>
        <div class="table-responsive mb-4">
            <table class="table table-sm align-middle">
                <thead>
                    <tr><th>Produit</th><th>Quantite</th><th>Prix unit.</th><th>Total</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($details as $d): ?>
                    <tr>
                        <td><?= htmlspecialchars($d['produit_nom']) ?></td>
                        <td><?= (int)$d['quantite'] ?></td>
                        <td><?= number_format((float)$d['prix_unitaire'], 2) ?> TND</td>
                        <td><?= number_format((float)$d['total_ligne'], 2) ?> TND</td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="3" class="text-end"><strong>Total TTC</strong></td>
                        <td><strong style="color:#2A7FAA"><?= number_format((float)$commande['total_ttc'], 2) ?> TND</strong></td>
                    </tr>
                </tfoot>
            </table>
        </div>

        <form method="POST" action="index.php?page=mes_commandes&action=edit&id=<?= (int)$commande['id'] ?>" id="formEditFront" novalidate>
            <div class="row g-3">
                <div class="col-12">
                    <label class="form-label">Adresse de livraison *</label>
                    <input type="text" name="adresse_livraison" class="form-control" value="<?= htmlspecialchars($commande['adresse_livraison']) ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Ville *</label>
                    <input type="text" name="ville" class="form-control" value="<?= htmlspecialchars($commande['ville']) ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Code postal *</label>
                    <input type="text" name="code_postal" class="form-control" value="<?= htmlspecialchars($commande['code_postal']) ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Telephone *</label>
                    <input type="text" name="telephone" class="form-control" value="<?= htmlspecialchars($commande['telephone']) ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Mode paiement</label>
                    <select name="mode_paiement" class="form-select">
                        <?php foreach (['carte','virement','especes','cheque'] as $mp): ?>
                        <option value="<?= $mp ?>" <?= ($commande['mode_paiement'] ?? '') === $mp ? 'selected' : '' ?>><?= ucfirst($mp) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Notes</label>
                    <input type="text" name="notes" class="form-control" value="<?= htmlspecialchars($commande['notes'] ?? '') ?>">
                </div>
            </div>

            <div class="mt-4 d-flex gap-2">
                <button type="submit" class="btn btn-warning"><i class="fas fa-save me-1"></i>Enregistrer</button>
                <a href="index.php?page=mes_commandes" class="btn btn-outline-secondary">Annuler</a>
            </div>
        </form>
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

document.getElementById('formEditFront').addEventListener('submit', function(e) {
    e.preventDefault();
    let valid = true;
    this.querySelectorAll('.is-invalid,.is-valid').forEach(el => el.classList.remove('is-invalid','is-valid'));
    this.querySelectorAll('.invalid-feedback').forEach(el => el.remove());

    const adr = this.querySelector('[name="adresse_livraison"]');
    if (!adr.value.trim()) { showErr(adr, 'Adresse obligatoire.'); valid = false; } else markOk(adr);

    const ville = this.querySelector('[name="ville"]');
    if (!ville.value.trim()) { showErr(ville, 'Ville obligatoire.'); valid = false; }
    else if (!/^[a-zA-ZÀ-ÿ\s\-]+$/.test(ville.value.trim())) { showErr(ville, 'Ville invalide.'); valid = false; }
    else markOk(ville);

    const cp = this.querySelector('[name="code_postal"]');
    if (!/^\d{4,5}$/.test(cp.value.trim())) { showErr(cp, 'Code postal invalide (4 ou 5 chiffres).'); valid = false; } else markOk(cp);

    const tel = this.querySelector('[name="telephone"]');
    if (!/^[+\d\s]{8,15}$/.test(tel.value.trim())) { showErr(tel, 'Telephone invalide.'); valid = false; } else markOk(tel);

    if (valid) this.submit();
});
</script>

<?php require __DIR__ . '/partials/footer.php'; ?>
