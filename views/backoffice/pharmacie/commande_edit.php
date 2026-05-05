<?php
$pageTitle  = 'Modifier commande — ' . htmlspecialchars($commande['numero_commande']);
$activePage = 'commandes';
require __DIR__ . '/_layout_top.php';
?>

<?php if ($flash): ?>
<div class="flash-box flash-<?= $flash['type'] === 'success' ? 'success' : 'error' ?>">
    <?= htmlspecialchars($flash['message']) ?>
</div>
<?php endif; ?>

<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px">
    <h5 style="margin:0;font-weight:700;color:#1e2a3e">
        <i class="fas fa-edit me-2" style="color:#FF9800"></i>
        Modifier — <?= htmlspecialchars($commande['numero_commande']) ?>
    </h5>
    <a href="index.php?page=commandes_admin&action=show&id=<?= $commande['id'] ?>" class="btn btn-outline-secondary btn-sm">
        <i class="fas fa-arrow-left me-1"></i>Retour
    </a>
</div>

<div class="content-card" style="max-width:700px">
    <form method="POST" action="index.php?page=commandes_admin&action=edit&id=<?= $commande['id'] ?>"
          id="formEdit" novalidate>

        <!-- Articles (lecture seule) -->
        <h6 style="font-weight:700;margin-bottom:12px;color:#1e2a3e">Articles (non modifiables)</h6>
        <div class="table-responsive mb-4">
            <table class="table table-sm mb-0" style="background:#f8f9fa;border-radius:8px">
                <thead><tr><th>Produit</th><th>Qté</th><th>Prix unit.</th><th>Total</th></tr></thead>
                <tbody>
                <?php foreach ($details as $d): ?>
                <tr>
                    <td><?= htmlspecialchars($d['produit_nom']) ?></td>
                    <td><?= $d['quantite'] ?></td>
                    <td><?= number_format($d['prix_unitaire'],2) ?> TND</td>
                    <td><?= number_format($d['total_ligne'],2) ?> TND</td>
                </tr>
                <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr><td colspan="3" style="text-align:right;font-weight:700">Total TTC</td>
                        <td style="font-weight:700;color:#4CAF50"><?= number_format($commande['total_ttc'],2) ?> TND</td></tr>
                </tfoot>
            </table>
        </div>

        <hr>
        <h6 style="font-weight:700;margin-bottom:16px;color:#1e2a3e">Informations modifiables</h6>

        <div class="row g-3">
            <div class="col-12">
                <label class="form-label">Adresse de livraison *</label>
                <input type="text" name="adresse_livraison" class="form-control"
                       value="<?= htmlspecialchars($commande['adresse_livraison']) ?>">
            </div>
            <div class="col-md-6">
                <label class="form-label">Ville *</label>
                <input type="text" name="ville" class="form-control"
                       value="<?= htmlspecialchars($commande['ville']) ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label">Code postal *</label>
                <input type="text" name="code_postal" class="form-control"
                       value="<?= htmlspecialchars($commande['code_postal']) ?>" maxlength="5">
            </div>
            <div class="col-md-3">
                <label class="form-label">Téléphone *</label>
                <input type="text" name="telephone" class="form-control"
                       value="<?= htmlspecialchars($commande['telephone']) ?>">
            </div>
            <div class="col-md-6">
                <label class="form-label">Mode paiement</label>
                <select name="mode_paiement" class="form-select">
                    <?php foreach (['carte','virement','especes','cheque'] as $mp): ?>
                    <option value="<?= $mp ?>" <?= $commande['mode_paiement'] === $mp ? 'selected' : '' ?>>
                        <?= ucfirst($mp) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-6">
                <label class="form-label">Statut</label>
                <select name="statut" class="form-select">
                    <?php foreach (['en_attente'=>'En attente','confirmee'=>'Confirmée','en_preparation'=>'En préparation','expediee'=>'Expédiée','livree'=>'Livrée','annulee'=>'Annulée'] as $v=>$l): ?>
                    <option value="<?= $v ?>" <?= $commande['statut'] === $v ? 'selected' : '' ?>><?= $l ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-12">
                <label class="form-label">Notes</label>
                <textarea name="notes" class="form-control" rows="2"><?= htmlspecialchars($commande['notes'] ?? '') ?></textarea>
            </div>
        </div>

        <hr class="my-4">
        <div class="d-flex gap-2">
            <button type="submit" class="btn btn-warning"><i class="fas fa-save me-1"></i>Mettre à jour</button>
            <a href="index.php?page=commandes_admin&action=show&id=<?= $commande['id'] ?>" class="btn btn-outline-secondary">Annuler</a>
        </div>
    </form>
</div>

<script>
function showErr(el, msg) {
    el.classList.add('is-invalid');
    let fb = el.parentNode.querySelector('.invalid-feedback');
    if (!fb) { fb = document.createElement('div'); fb.className = 'invalid-feedback'; el.parentNode.appendChild(fb); }
    fb.textContent = msg;
}
function markOk(el) { el.classList.remove('is-invalid'); el.classList.add('is-valid'); }

document.getElementById('formEdit').addEventListener('submit', function(e) {
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
    if (!/^\d{4,5}$/.test(cp.value.trim())) { showErr(cp, 'Code postal invalide.'); valid = false; } else markOk(cp);

    const tel = this.querySelector('[name="telephone"]');
    if (!/^[+\d\s]{8,15}$/.test(tel.value.trim())) { showErr(tel, 'Téléphone invalide.'); valid = false; } else markOk(tel);

    if (valid) this.submit();
});
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</div></body></html>
