<?php
$isEdit     = isset($produit);
$pageTitle  = $isEdit ? 'Modifier le produit' : 'Nouveau produit';
$activePage = 'produits';
require __DIR__ . '/_layout_top.php';
?>

<?php if ($flash): ?>
<div class="flash-box flash-<?= $flash['type'] === 'success' ? 'success' : 'error' ?>">
    <?= htmlspecialchars($flash['message']) ?>
</div>
<?php endif; ?>

<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px">
    <h5 style="margin:0;font-weight:700;color:#1e2a3e">
        <i class="fas fa-<?= $isEdit ? 'edit' : 'plus-circle' ?> me-2" style="color:#4CAF50"></i>
        <?= $pageTitle ?>
    </h5>
    <a href="index.php?page=produits_admin" class="btn btn-outline-secondary btn-sm">
        <i class="fas fa-arrow-left me-1"></i>Retour
    </a>
</div>

<div class="content-card">
    <form method="POST"
          action="index.php?page=produits_admin&action=<?= $isEdit ? 'edit&id='.$produit['id'] : 'create' ?>"
          id="formProduit" novalidate>

        <div class="row g-3">
            <!-- Nom -->
            <div class="col-md-6">
                <label class="form-label">Nom du produit *</label>
                <input type="text" name="nom" class="form-control"
                       value="<?= htmlspecialchars($produit['nom'] ?? '') ?>"
                       placeholder="Ex : Gel douche surgras 500ml">
            </div>
            <!-- Référence -->
            <div class="col-md-3">
                <label class="form-label">Référence * <small class="text-muted fw-normal">(MAJ, chiffres, tirets)</small></label>
                <input type="text" name="reference" class="form-control"
                       value="<?= htmlspecialchars($produit['reference'] ?? '') ?>"
                       placeholder="PARA-001" style="text-transform:uppercase">
            </div>
            <!-- Catégorie -->
            <div class="col-md-3">
                <label class="form-label">Catégorie</label>
                <select name="categorie_id" class="form-select">
                    <option value="">— Sans catégorie —</option>
                    <?php foreach ($categories as $cat): ?>
                    <option value="<?= $cat['id'] ?>"
                        <?= ($produit['categorie_id'] ?? 0) == $cat['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($cat['nom']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Description -->
            <div class="col-12">
                <label class="form-label">Description</label>
                <textarea name="description" class="form-control" rows="3"
                          placeholder="Description du produit..."><?= htmlspecialchars($produit['description'] ?? '') ?></textarea>
            </div>

            <!-- Prix achat -->
            <div class="col-md-3">
                <label class="form-label">Prix d'achat * (TND)</label>
                <input type="text" name="prix_achat" class="form-control"
                       value="<?= $produit['prix_achat'] ?? '' ?>"
                       placeholder="0.00">
                <small class="text-muted">Nombre positif</small>
            </div>
            <!-- Prix vente -->
            <div class="col-md-3">
                <label class="form-label">Prix de vente * (TND)</label>
                <input type="text" name="prix_vente" class="form-control"
                       value="<?= $produit['prix_vente'] ?? '' ?>"
                       placeholder="0.00">
                <small class="text-muted">Nombre positif</small>
            </div>
            <!-- TVA -->
            <div class="col-md-2">
                <label class="form-label">TVA (%)</label>
                <input type="text" name="tva" class="form-control"
                       value="<?= $produit['tva'] ?? '19' ?>"
                       placeholder="19">
            </div>
            <!-- Stock -->
            <div class="col-md-2">
                <label class="form-label">Stock *</label>
                <input type="text" name="stock" class="form-control"
                       value="<?= $produit['stock'] ?? '0' ?>"
                       placeholder="0">
                <small class="text-muted">Entier ≥ 0</small>
            </div>
            <!-- Stock alerte -->
            <div class="col-md-2">
                <label class="form-label">Seuil alerte</label>
                <input type="text" name="stock_alerte" class="form-control"
                       value="<?= $produit['stock_alerte'] ?? '5' ?>"
                       placeholder="5">
            </div>

            <!-- Image -->
            <div class="col-md-6">
                <label class="form-label">URL Image</label>
                <input type="text" name="image" class="form-control"
                       value="<?= htmlspecialchars($produit['image'] ?? '') ?>"
                       placeholder="https://... ou chemin/relatif.jpg">
            </div>

            <!-- Checkboxes -->
            <div class="col-md-3 d-flex align-items-center gap-4 pt-4">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="prescription" id="prescription"
                           <?= ($produit['prescription'] ?? 0) ? 'checked' : '' ?>>
                    <label class="form-check-label" for="prescription">
                        <i class="fas fa-prescription me-1"></i>Conseil expert recommande
                    </label>
                </div>
            </div>
            <div class="col-md-3 d-flex align-items-center pt-4">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="actif" id="actif"
                           <?= ($produit['actif'] ?? 1) ? 'checked' : '' ?>>
                    <label class="form-check-label" for="actif">
                        <i class="fas fa-toggle-on me-1"></i>Produit actif
                    </label>
                </div>
            </div>
        </div>

        <hr class="my-4">
        <div class="d-flex gap-2">
            <button type="submit" class="btn btn-success">
                <i class="fas fa-save me-1"></i><?= $isEdit ? 'Mettre à jour' : 'Enregistrer' ?>
            </button>
            <a href="index.php?page=produits_admin" class="btn btn-outline-secondary">Annuler</a>
        </div>
    </form>
</div>

<!-- Validation JS (sans HTML5) -->
<script>
function showErr(el, msg) {
    el.classList.add('is-invalid');
    let fb = el.parentNode.querySelector('.invalid-feedback');
    if (!fb) { fb = document.createElement('div'); fb.className = 'invalid-feedback'; el.parentNode.appendChild(fb); }
    fb.textContent = msg;
}
function clearErr(el) {
    el.classList.remove('is-invalid', 'is-valid');
    const fb = el.parentNode.querySelector('.invalid-feedback');
    if (fb) fb.remove();
}
function markOk(el) { clearErr(el); el.classList.add('is-valid'); }

document.getElementById('formProduit').addEventListener('submit', function(e) {
    e.preventDefault();
    let valid = true;
    const fields = this.querySelectorAll('input[type="text"], textarea, select');
    fields.forEach(f => clearErr(f));

    const nom = this.querySelector('[name="nom"]');
    if (!nom.value.trim() || nom.value.trim().length < 2) {
        showErr(nom, 'Le nom est obligatoire (min 2 caractères).'); valid = false;
    } else markOk(nom);

    const ref = this.querySelector('[name="reference"]');
    if (!ref.value.trim()) {
        showErr(ref, 'La référence est obligatoire.'); valid = false;
    } else if (!/^[A-Z0-9\-]+$/.test(ref.value.trim().toUpperCase())) {
        showErr(ref, 'Référence invalide (majuscules, chiffres, tirets uniquement).'); valid = false;
    } else {
        ref.value = ref.value.toUpperCase();
        markOk(ref);
    }

    const prixV = this.querySelector('[name="prix_vente"]');
    const pv = parseFloat(prixV.value.replace(',', '.'));
    if (isNaN(pv) || pv <= 0) {
        showErr(prixV, 'Prix de vente invalide (nombre > 0).'); valid = false;
    } else markOk(prixV);

    const prixA = this.querySelector('[name="prix_achat"]');
    const pa = parseFloat(prixA.value.replace(',', '.'));
    if (isNaN(pa) || pa < 0) {
        showErr(prixA, 'Prix d\'achat invalide (nombre ≥ 0).'); valid = false;
    } else markOk(prixA);

    const stock = this.querySelector('[name="stock"]');
    if (!/^\d+$/.test(stock.value.trim()) || parseInt(stock.value) < 0) {
        showErr(stock, 'Stock invalide (entier ≥ 0).'); valid = false;
    } else markOk(stock);

    if (valid) this.submit();
});

// Auto-majuscule référence
document.querySelector('[name="reference"]').addEventListener('input', function() {
    this.value = this.value.toUpperCase().replace(/[^A-Z0-9\-]/g, '');
});
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</div></body></html>
