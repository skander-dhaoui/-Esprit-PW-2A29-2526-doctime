<?php
// views/backoffice/produit_form.php
$current_page = 'produits_admin';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nouveau Produit - DocTime Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        :root {
            --green:     #4CAF50;
            --navy:      #1a2035;
            --teal:      #0fa99b;
            --teal-dark: #0d8a7d;
            --red:       #ef4444;
            --gray-50:   #f8fafc;
            --gray-200:  #e2e8f0;
            --gray-700:  #334155;
            --gray-900:  #0f172a;
            --shadow:    0 1px 6px rgba(0,0,0,.07);
            --radius:    12px;
        }
        body {
            font-family: 'Segoe UI', system-ui, sans-serif;
            background: var(--gray-50);
            color: var(--gray-900);
            min-height: 100vh;
            display: flex;
        }
        .main-content { margin-left: 260px; flex: 1; display: flex; flex-direction: column; }
        .page-header {
            padding: 25px 30px 10px;
            display: flex; justify-content: space-between; align-items: center;
        }
        .page-header h4 {
            font-size: 22px; font-weight: 700; color: var(--navy);
            display: flex; align-items: center; gap: 10px; margin: 0;
        }
        .page-header h4 i { color: var(--teal); }

        .content-card {
            background: #fff; border-radius: var(--radius); box-shadow: var(--shadow);
            padding: 30px; margin: 20px 30px; max-width: 900px;
        }

        .form-section { margin-bottom: 30px; }
        .form-section h5 {
            font-size: 15px; font-weight: 700; color: var(--navy);
            margin-bottom: 20px; border-bottom: 2px solid var(--gray-200);
            padding-bottom: 8px; display: flex; align-items: center; gap: 8px;
        }
        .form-section h5 i { color: var(--teal); }

        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px; }
        .form-row.three { grid-template-columns: 1fr 1fr 1fr; }
        .form-row.full  { grid-template-columns: 1fr; }

        .form-group label {
            display: block; font-weight: 600; margin-bottom: 7px; font-size: 12px;
            color: var(--gray-700); text-transform: uppercase; letter-spacing: .5px;
        }
        .form-group label .req { color: var(--red); }

        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%; padding: 11px 14px; border: 1.5px solid var(--gray-200);
            border-radius: 8px; font-size: 14px; transition: all .2s;
            background: white; color: var(--gray-900);
        }
        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            border-color: var(--teal); outline: none;
            box-shadow: 0 0 0 3px rgba(15,169,155,.1);
        }
        .form-group textarea { resize: vertical; min-height: 110px; }

        .is-invalid { border-color: var(--red) !important; box-shadow: 0 0 0 3px rgba(239,68,68,.1) !important; }
        .error-text { color: var(--red); font-size: 12px; margin-top: 5px; display: none; font-weight: 500; }

        .action-buttons {
            display: flex; gap: 12px; margin-top: 30px;
            padding-top: 20px; border-top: 1px solid var(--gray-200);
        }
        .btn {
            display: inline-flex; align-items: center; gap: 8px;
            padding: 12px 24px; border-radius: 8px; font-size: 14px;
            font-weight: 600; cursor: pointer; border: none; text-decoration: none; transition: all .2s;
        }
        .btn-teal    { background: var(--teal); color: white; }
        .btn-teal:hover { background: var(--teal-dark); transform: translateY(-1px); color: white; }
        .btn-secondary { background: var(--gray-200); color: var(--gray-700); }
        .btn-secondary:hover { background: #cbd5e1; }

        .flash-box { border-radius: 8px; padding: 14px 16px; margin: 0 30px 20px; font-size: 14px; display: flex; align-items: center; gap: 10px; }
        .flash-error   { background: #fdecea; color: #c62828; border: 1px solid #f9a9a3; }
        .flash-success { background: #e8f5e9; color: #2e7d32; border: 1px solid #a5d6a7; }

        .hint { font-size: 11px; color: var(--gray-700); margin-top: 4px; font-style: italic; }
        .char-counter { font-size: 11px; color: var(--gray-700); text-align: right; margin-top: 4px; }
        .counter-warn { color: #f59e0b; }
        .counter-over { color: var(--red); }
    </style>
</head>
<body>

<?php include __DIR__ . '/sidebar.php'; ?>

<div class="main-content">
    <div class="page-header">
        <h4><i class="fas fa-plus-circle"></i> Nouveau Produit</h4>
        <a href="index.php?page=produits_admin" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Retour à la liste
        </a>
    </div>

    <?php if (!empty($flash)): ?>
        <div class="flash-box flash-<?= $flash['type'] ?>">
            <i class="fas fa-<?= $flash['type'] === 'success' ? 'check-circle' : 'exclamation-circle' ?>"></i>
            <?= $flash['message'] ?>
        </div>
    <?php endif; ?>

    <div class="content-card">
        <form method="POST" id="produitForm"
              action="index.php?page=produits_admin&action=create"
              novalidate enctype="multipart/form-data">

            <input type="hidden" name="csrf_token"
                   value="<?= htmlspecialchars($csrfToken ?? $_SESSION['csrf_token'] ?? '') ?>">

            <!-- Section: Informations générales -->
            <div class="form-section">
                <h5><i class="fas fa-info-circle"></i> Informations Générales</h5>
                <div class="form-row">
                    <div class="form-group">
                        <label>Nom du produit <span class="req">*</span></label>
                        <input type="text" id="nom" name="nom" maxlength="255"
                               value="<?= htmlspecialchars($old['nom'] ?? '') ?>"
                               placeholder="Ex: Doliprane 1000mg">
                        <div class="error-text" id="nom-error"></div>
                    </div>
                    <div class="form-group">
                        <label>Catégorie <span class="req">*</span></label>
                        <select id="categorie_id" name="categorie_id">
                            <option value="">— Sélectionner une catégorie —</option>
                            <?php foreach ($categories ?? [] as $cat): ?>
                                <option value="<?= $cat['id'] ?>"
                                    <?= (int)($old['categorie_id'] ?? 0) === (int)$cat['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($cat['nom']) ?>
                                    <?php if (($cat['nombre_produits'] ?? 0) > 0): ?>
                                        (<?= $cat['nombre_produits'] ?> produit<?= $cat['nombre_produits'] > 1 ? 's' : '' ?>)
                                    <?php endif; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="error-text" id="categorie-error"></div>
                    </div>
                </div>

                <div class="form-row full">
                    <div class="form-group">
                        <label>Description <span class="req">*</span></label>
                        <textarea id="description" name="description"
                                  placeholder="Description détaillée du produit (min. 10 caractères)…"
                                  maxlength="2000"><?= htmlspecialchars($old['description'] ?? '') ?></textarea>
                        <div class="char-counter" id="desc-counter">0 / 2000</div>
                        <div class="error-text" id="description-error"></div>
                    </div>
                </div>
            </div>

            <!-- Section: Prix et stock -->
            <div class="form-section">
                <h5><i class="fas fa-tag"></i> Prix & Stock</h5>
                <div class="form-row three">
                    <div class="form-group">
                        <label>Prix d'achat (TND)</label>
                        <input type="number" id="prix_achat" name="prix_achat" step="0.01" min="0"
                               value="<?= htmlspecialchars($old['prix_achat'] ?? '0') ?>"
                               placeholder="0.00">
                        <div class="error-text" id="prix_achat-error"></div>
                    </div>
                    <div class="form-group">
                        <label>Prix de vente (TND) <span class="req">*</span></label>
                        <input type="number" id="prix_vente" name="prix_vente" step="0.01" min="0.01"
                               value="<?= htmlspecialchars($old['prix_vente'] ?? '') ?>"
                               placeholder="0.00">
                        <div class="error-text" id="prix_vente-error"></div>
                    </div>
                    <div class="form-group">
                        <label>Stock initial <span class="req">*</span></label>
                        <input type="number" id="stock" name="stock" min="0" step="1"
                               value="<?= htmlspecialchars($old['stock'] ?? '0') ?>"
                               placeholder="0">
                        <div class="error-text" id="stock-error"></div>
                    </div>
                </div>
            </div>

            <!-- Section: Options -->
            <div class="form-section">
                <h5><i class="fas fa-sliders-h"></i> Options</h5>
                <div class="form-row">
                    <div class="form-group">
                        <label>Statut <span class="req">*</span></label>
                        <select id="statut" name="statut">
                            <?php $statut = $old['statut'] ?? 'inactif'; ?>
                            <option value="actif"   <?= $statut === 'actif'   ? 'selected' : '' ?>>Actif</option>
                            <option value="inactif" <?= $statut === 'inactif' ? 'selected' : '' ?>>Inactif</option>
                            <option value="rupture" <?= $statut === 'rupture' ? 'selected' : '' ?>>Rupture de stock</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Sur ordonnance</label>
                        <select id="prescription" name="prescription">
                            <option value="0" <?= empty($old['prescription']) ? 'selected' : '' ?>>Non</option>
                            <option value="1" <?= !empty($old['prescription']) ? 'selected' : '' ?>>Oui</option>
                        </select>
                        <div class="hint">Cocher si le produit nécessite une ordonnance médicale.</div>
                    </div>
                </div>
                <div class="form-row full">
                    <div class="form-group">
                        <label>Image (URL)</label>
                        <input type="text" id="image" name="image"
                               value="<?= htmlspecialchars($old['image'] ?? '') ?>"
                               placeholder="https://exemple.com/image.jpg">
                        <div class="hint">Entrez l'URL directe de l'image du produit.</div>
                    </div>
                </div>
            </div>

            <div class="action-buttons">
                <button type="submit" class="btn btn-teal" id="submitBtn">
                    <i class="fas fa-save"></i> Créer le produit
                </button>
                <a href="index.php?page=produits_admin" class="btn btn-secondary">
                    <i class="fas fa-times"></i> Annuler
                </a>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    // Character counter for description
    const desc = document.getElementById('description');
    const counter = document.getElementById('desc-counter');
    function updateCounter() {
        const len = desc.value.length;
        counter.textContent = len + ' / 2000';
        counter.className = 'char-counter' + (len > 1900 ? ' counter-over' : len > 1500 ? ' counter-warn' : '');
    }
    desc.addEventListener('input', updateCounter);
    updateCounter();

    // Form validation
    const form = document.getElementById('produitForm');
    form.addEventListener('submit', function (e) {
        let valid = true;

        document.querySelectorAll('.error-text').forEach(el => { el.textContent = ''; el.style.display = 'none'; });
        document.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));

        function showError(id, inputId, msg) {
            const inp = document.getElementById(inputId);
            const err = document.getElementById(id);
            if (inp) inp.classList.add('is-invalid');
            if (err) { err.textContent = msg; err.style.display = 'block'; }
            valid = false;
        }

        const nom = document.getElementById('nom').value.trim();
        if (!nom || nom.length < 3) showError('nom-error', 'nom', 'Le nom doit contenir au moins 3 caractères.');
        else if (nom.length > 255) showError('nom-error', 'nom', 'Le nom ne peut pas dépasser 255 caractères.');

        const catId = document.getElementById('categorie_id').value;
        if (!catId) showError('categorie-error', 'categorie_id', 'Veuillez sélectionner une catégorie.');

        const descVal = document.getElementById('description').value.trim();
        if (!descVal || descVal.length < 10) showError('description-error', 'description', 'La description doit contenir au moins 10 caractères.');

        const prixAchat = parseFloat(document.getElementById('prix_achat').value) || 0;
        const prixVente = parseFloat(document.getElementById('prix_vente').value);
        if (!prixVente || prixVente <= 0) showError('prix_vente-error', 'prix_vente', 'Le prix de vente doit être supérieur à 0.');
        else if (prixVente < prixAchat) showError('prix_vente-error', 'prix_vente', 'Le prix de vente doit être ≥ au prix d\'achat.');

        const stock = parseInt(document.getElementById('stock').value);
        if (isNaN(stock) || stock < 0) showError('stock-error', 'stock', 'Le stock doit être un nombre ≥ 0.');

        if (!valid) {
            e.preventDefault();
            window.scrollTo({ top: 0, behavior: 'smooth' });
        } else {
            document.getElementById('submitBtn').innerHTML = '<i class="fas fa-spinner fa-spin"></i> Création…';
            document.getElementById('submitBtn').disabled = true;
        }
    });
});
</script>

</body>
</html>
