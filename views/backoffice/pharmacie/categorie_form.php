<?php
$isEdit     = isset($categorie);
$pageTitle  = $isEdit ? 'Modifier la catégorie' : 'Nouvelle catégorie';
$activePage = 'categories';
require __DIR__ . '/_layout_top.php';
?>

<?php if ($flash): ?>
<div class="flash-box flash-<?= $flash['type'] === 'success' ? 'success' : 'error' ?>">
    <?= htmlspecialchars($flash['message']) ?>
</div>
<?php endif; ?>

<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px">
    <h5 style="margin:0;font-weight:700;color:#1e2a3e">
        <i class="fas fa-tags me-2" style="color:#4CAF50"></i><?= $pageTitle ?>
    </h5>
    <a href="index.php?page=categories_admin" class="btn btn-outline-secondary btn-sm">
        <i class="fas fa-arrow-left me-1"></i>Retour
    </a>
</div>

<div class="content-card" style="max-width:680px">
    <form method="POST"
          action="index.php?page=categories_admin&action=<?= $isEdit ? 'edit&id='.$categorie['id'] : 'create' ?>"
          id="formCategorie" novalidate>

        <div class="row g-3">
            <div class="col-md-8">
                <label class="form-label">Nom de la catégorie *</label>
                <input type="text" name="nom" class="form-control"
                       value="<?= htmlspecialchars($categorie['nom'] ?? '') ?>"
                       placeholder="Ex : Soins dermocosmetiques">
            </div>
            <div class="col-md-4">
                <label class="form-label">Statut</label>
                <select name="statut" class="form-select">
                    <option value="actif"   <?= ($categorie['statut'] ?? 'actif') === 'actif'   ? 'selected' : '' ?>>Actif</option>
                    <option value="inactif" <?= ($categorie['statut'] ?? '') === 'inactif' ? 'selected' : '' ?>>Inactif</option>
                </select>
            </div>

            <div class="col-12">
                <label class="form-label">Description</label>
                <textarea name="description" class="form-control" rows="3"
                          placeholder="Description de la catégorie..."><?= htmlspecialchars($categorie['description'] ?? '') ?></textarea>
            </div>

            <div class="col-md-6">
                <label class="form-label">Catégorie parente</label>
                <select name="parent_id" class="form-select">
                    <option value="">— Aucune (catégorie racine) —</option>
                    <?php foreach ($parents as $p): ?>
                    <?php if ($isEdit && $p['id'] == $categorie['id']) continue; // éviter auto-référence ?>
                    <option value="<?= $p['id'] ?>"
                        <?= ($categorie['parent_id'] ?? 0) == $p['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($p['nom']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-md-6">
                <label class="form-label">URL Image</label>
                <input type="text" name="image" class="form-control"
                       value="<?= htmlspecialchars($categorie['image'] ?? '') ?>"
                       placeholder="https://... ou chemin/image.jpg">
            </div>
        </div>

        <hr class="my-4">
        <div class="d-flex gap-2">
            <button type="submit" class="btn btn-success">
                <i class="fas fa-save me-1"></i><?= $isEdit ? 'Mettre à jour' : 'Créer la catégorie' ?>
            </button>
            <a href="index.php?page=categories_admin" class="btn btn-outline-secondary">Annuler</a>
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

document.getElementById('formCategorie').addEventListener('submit', function(e) {
    e.preventDefault();
    let valid = true;
    this.querySelectorAll('.is-invalid,.is-valid').forEach(el => el.classList.remove('is-invalid','is-valid'));
    this.querySelectorAll('.invalid-feedback').forEach(el => el.remove());

    const nom = this.querySelector('[name="nom"]');
    const statut = this.querySelector('[name="statut"]');
    const image = this.querySelector('[name="image"]');

    if (!nom.value.trim() || nom.value.trim().length < 2) {
        showErr(nom, 'Le nom est obligatoire (minimum 2 caractères).'); valid = false;
    } else markOk(nom);

    if (!statut.value || !['actif', 'inactif'].includes(statut.value)) {
        showErr(statut, 'Statut invalide.'); valid = false;
    } else markOk(statut);

    const imageVal = image.value.trim();
    if (imageVal.length > 0 && /^https?:\/\//i.test(imageVal)) {
        try {
            new URL(imageVal);
            markOk(image);
        } catch (err) {
            showErr(image, 'URL image invalide.'); valid = false;
        }
    } else if (imageVal.length > 0 && imageVal.length < 3) {
        showErr(image, 'Chemin image invalide.'); valid = false;
    } else if (imageVal.length > 0) {
        markOk(image);
    }

    if (valid) this.submit();
});
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</div></body></html>
// update
