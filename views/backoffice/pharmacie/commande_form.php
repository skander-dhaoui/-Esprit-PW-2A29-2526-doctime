<?php
$pageTitle  = 'Nouvelle commande';
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
        <i class="fas fa-plus-circle me-2" style="color:#4CAF50"></i>Nouvelle commande
    </h5>
    <a href="index.php?page=commandes_admin" class="btn btn-outline-secondary btn-sm">
        <i class="fas fa-arrow-left me-1"></i>Retour
    </a>
</div>

<form method="POST" action="index.php?page=commandes_admin&action=create" id="formCommande" novalidate>
<div class="row g-4">

    <!-- Client + Livraison -->
    <div class="col-md-5">
        <div class="content-card">
            <h6 style="font-weight:700;margin-bottom:16px;color:#1e2a3e">Informations client</h6>
            <div class="mb-3">
                <label class="form-label">ID Utilisateur *</label>
                <input type="text" name="user_id" class="form-control" placeholder="Ex : 1">
                <small class="text-muted">Entier positif correspondant à un utilisateur</small>
            </div>
            <hr>
            <h6 style="font-weight:700;margin-bottom:16px;color:#1e2a3e">Livraison</h6>
            <div class="mb-3">
                <label class="form-label">Adresse *</label>
                <input type="text" name="adresse_livraison" class="form-control" placeholder="12 Rue de la République">
            </div>
            <div class="row g-2 mb-3">
                <div class="col-7">
                    <label class="form-label">Ville *</label>
                    <input type="text" name="ville" class="form-control" placeholder="Tunis">
                </div>
                <div class="col-5">
                    <label class="form-label">Code postal *</label>
                    <input type="text" name="code_postal" class="form-control" placeholder="1000" maxlength="5">
                </div>
            </div>
            <div class="mb-3">
                <label class="form-label">Téléphone *</label>
                <input type="text" name="telephone" class="form-control" placeholder="+216 22 345 678">
            </div>
            <div class="row g-2 mb-3">
                <div class="col-6">
                    <label class="form-label">Mode paiement</label>
                    <select name="mode_paiement" class="form-select">
                        <option value="carte">Carte</option>
                        <option value="virement">Virement</option>
                        <option value="especes">Espèces</option>
                        <option value="cheque">Chèque</option>
                    </select>
                </div>
                <div class="col-6">
                    <label class="form-label">Statut</label>
                    <select name="statut" class="form-select">
                        <option value="en_attente">En attente</option>
                        <option value="confirmee">Confirmée</option>
                        <option value="en_preparation">En préparation</option>
                        <option value="expediee">Expédiée</option>
                        <option value="livree">Livrée</option>
                        <option value="annulee">Annulée</option>
                    </select>
                </div>
            </div>
            <div>
                <label class="form-label">Notes</label>
                <textarea name="notes" class="form-control" rows="2" placeholder="Instructions..."></textarea>
            </div>
        </div>
    </div>

    <!-- Produits -->
    <div class="col-md-7">
        <div class="content-card">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px">
                <h6 style="margin:0;font-weight:700;color:#1e2a3e">Articles</h6>
                <button type="button" class="btn btn-outline-primary btn-sm" id="btnAddLigne">
                    <i class="fas fa-plus me-1"></i>Ajouter un produit
                </button>
            </div>

            <div id="produit-error"
                 style="display:none;align-items:center;gap:10px;padding:12px 16px;
                        background:#fdecea;border-left:4px solid #f44336;
                        border-radius:10px;margin-bottom:10px;
                        color:#c62828;font-weight:600;">
                <i class="fas fa-exclamation-circle"></i>
                Veuillez sélectionner au moins un produit.
            </div>
            <div id="lignesContainer">
                <div class="row g-2 mb-2 ligne-row align-items-end">
                    <div class="col-7">
                        <label class="form-label small">Produit *</label>
                        <select name="produit_id[]" class="form-select">
                            <option value="">— Sélectionner —</option>
                            <?php foreach ($produits as $p): ?>
                            <option value="<?= $p['id'] ?>" data-prix="<?= $p['prix_vente'] ?>">
                                <?= htmlspecialchars($p['nom']) ?> (<?= number_format($p['prix_vente'],2) ?> TND)
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-3">
                        <label class="form-label small">Quantité *</label>
                        <input type="text" name="quantite[]" class="form-control quantite-input"
                               placeholder="1" value="1">
                    </div>
                    <div class="col-2">
                        <button type="button" class="btn btn-outline-danger btn-sm w-100 btn-remove-ligne">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>
            </div>

            <hr>
            <!-- Récap total -->
            <div style="background:#f8f9fa;border-radius:10px;padding:16px">
                <div style="display:flex;justify-content:space-between;margin-bottom:6px">
                    <span>Total HT (estimé)</span>
                    <strong id="totalHt">0.00 TND</strong>
                </div>
                <div style="display:flex;justify-content:space-between;margin-bottom:6px;color:#666">
                    <span>TVA 19%</span>
                    <span id="totalTva">0.00 TND</span>
                </div>
                <div style="display:flex;justify-content:space-between;font-size:18px;font-weight:700;color:#4CAF50">
                    <span>Total TTC</span>
                    <span id="totalTtc">0.00 TND</span>
                </div>
            </div>
        </div>

        <div class="mt-3 d-flex gap-2">
            <button type="submit" class="btn btn-success">
                <i class="fas fa-save me-1"></i>Créer la commande
            </button>
            <a href="index.php?page=commandes_admin" class="btn btn-outline-secondary">Annuler</a>
        </div>
    </div>
</div>
</form>

<script>
// ── Ligne template ──────────────────────────────────────────────────────
const produitOptions = `<?php foreach ($produits as $p): ?><option value="<?= $p['id'] ?>" data-prix="<?= $p['prix_vente'] ?>"><?= htmlspecialchars($p['nom']) ?> (<?= number_format($p['prix_vente'],2) ?> TND)</option><?php endforeach; ?>`;

const container = document.getElementById('lignesContainer');

document.getElementById('btnAddLigne').addEventListener('click', () => {
    const row = document.createElement('div');
    row.className = 'row g-2 mb-2 ligne-row align-items-end';
    row.innerHTML = `
        <div class="col-7">
            <label class="form-label small">Produit *</label>
            <select name="produit_id[]" class="form-select">
                <option value="">— Sélectionner —</option>
                ${produitOptions}
            </select>
        </div>
        <div class="col-3">
            <label class="form-label small">Quantité *</label>
            <input type="text" name="quantite[]" class="form-control quantite-input" placeholder="1" value="1">
        </div>
        <div class="col-2">
            <button type="button" class="btn btn-outline-danger btn-sm w-100 btn-remove-ligne">
                <i class="fas fa-times"></i>
            </button>
        </div>`;
    container.appendChild(row);
    attachListeners(row);
    recalcTotal();
});

container.addEventListener('click', e => {
    if (e.target.closest('.btn-remove-ligne')) {
        if (container.querySelectorAll('.ligne-row').length > 1)
            e.target.closest('.ligne-row').remove();
        recalcTotal();
    }
});
container.addEventListener('change', e => { if (e.target.matches('select, input')) recalcTotal(); });
container.addEventListener('input',  e => { if (e.target.matches('input'))          recalcTotal(); });

function recalcTotal() {
    let ht = 0;
    container.querySelectorAll('.ligne-row').forEach(row => {
        const sel = row.querySelector('select');
        const qte = parseInt(row.querySelector('input').value) || 0;
        const opt = sel.options[sel.selectedIndex];
        const prix = parseFloat(opt?.dataset?.prix || 0);
        ht += prix * qte;
    });
    const tva = ht * 0.19;
    document.getElementById('totalHt').textContent  = ht.toFixed(2) + ' TND';
    document.getElementById('totalTva').textContent = tva.toFixed(2) + ' TND';
    document.getElementById('totalTtc').textContent = (ht + tva).toFixed(2) + ' TND';
}

// ── Validation JS (sans HTML5) ──────────────────────────────────────────
function showErr(el, msg) {
    el.classList.add('is-invalid');
    let fb = el.parentNode.querySelector('.invalid-feedback');
    if (!fb) { fb = document.createElement('div'); fb.className = 'invalid-feedback'; el.parentNode.appendChild(fb); }
    fb.textContent = msg;
}
function markOk(el) { el.classList.remove('is-invalid'); el.classList.add('is-valid'); }

document.getElementById('formCommande').addEventListener('submit', function(e) {
    e.preventDefault();
    let valid = true;
    this.querySelectorAll('.is-invalid, .is-valid').forEach(el => el.classList.remove('is-invalid','is-valid'));
    this.querySelectorAll('.invalid-feedback').forEach(el => el.remove());

    const userId = this.querySelector('[name="user_id"]');
    if (!/^\d+$/.test(userId.value.trim()) || parseInt(userId.value) <= 0) {
        showErr(userId, 'ID utilisateur invalide (entier positif).'); valid = false;
    } else markOk(userId);

    const adr = this.querySelector('[name="adresse_livraison"]');
    if (!adr.value.trim()) { showErr(adr, 'Adresse obligatoire.'); valid = false; } else markOk(adr);

    const ville = this.querySelector('[name="ville"]');
    if (!ville.value.trim()) { showErr(ville, 'Ville obligatoire.'); valid = false; }
    else if (!/^[a-zA-ZÀ-ÿ\s\-]+$/.test(ville.value.trim())) { showErr(ville, 'Ville invalide (lettres uniquement).'); valid = false; }
    else markOk(ville);

    const cp = this.querySelector('[name="code_postal"]');
    if (!/^\d{4,5}$/.test(cp.value.trim())) { showErr(cp, 'Code postal invalide (4 ou 5 chiffres).'); valid = false; } else markOk(cp);

    const tel = this.querySelector('[name="telephone"]');
    if (!/^[+\d\s]{8,15}$/.test(tel.value.trim())) { showErr(tel, 'Téléphone invalide.'); valid = false; } else markOk(tel);

    // Vérifier au moins un produit sélectionné
    const selects = container.querySelectorAll('select');
    let auMoinsUn = false;
    selects.forEach(s => { if (s.value) auMoinsUn = true; });
    const prodErr = document.getElementById('produit-error');
    if (!auMoinsUn) {
        prodErr.style.display = 'flex';
        prodErr.scrollIntoView({ behavior: 'smooth', block: 'center' });
        valid = false;
    } else {
        prodErr.style.display = 'none';
    }

    if (valid) this.submit();
});

recalcTotal();
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/valo-backoffice.js"></script>
</div></body></html>
// update
