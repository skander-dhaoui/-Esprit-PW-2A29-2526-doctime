<?php
$pageTitle  = 'Détail produit — ' . htmlspecialchars($produit['nom']);
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
        <i class="fas fa-pills me-2" style="color:#4CAF50"></i>
        <?= htmlspecialchars($produit['nom']) ?>
    </h5>
    <div class="d-flex gap-2">
        <a href="index.php?page=produits_admin&action=edit&id=<?= $produit['id'] ?>"
           class="btn btn-warning btn-sm"><i class="fas fa-edit me-1"></i>Modifier</a>
        <a href="index.php?page=produits_admin&action=delete&id=<?= $produit['id'] ?>"
           class="btn btn-danger btn-sm"
           onclick="return confirm('Supprimer ce produit définitivement ?')">
            <i class="fas fa-trash me-1"></i>Supprimer
        </a>
        <a href="index.php?page=produits_admin" class="btn btn-outline-secondary btn-sm">
            <i class="fas fa-arrow-left me-1"></i>Retour
        </a>
    </div>
</div>

<div class="row g-4">
    <!-- Infos principales -->
    <div class="col-md-8">
        <div class="content-card">
            <h6 style="font-weight:700;margin-bottom:16px;color:#1e2a3e">Informations du produit</h6>
            <table class="table table-borderless mb-0">
                <tr><th width="35%" style="color:#666">Référence</th><td><code><?= htmlspecialchars($produit['reference']) ?></code></td></tr>
                <tr><th>Catégorie</th><td><?= htmlspecialchars($produit['categorie_nom'] ?? '—') ?></td></tr>
                <tr><th>Description</th><td><?= nl2br(htmlspecialchars($produit['description'] ?? '—')) ?></td></tr>
                <tr><th>Prix d'achat</th><td><?= number_format($produit['prix_achat'], 2) ?> TND</td></tr>
                <tr><th>Prix de vente</th><td><strong><?= number_format($produit['prix_vente'], 2) ?> TND</strong></td></tr>
                <tr><th>TVA</th><td><?= $produit['tva'] ?>%</td></tr>
                <tr><th>Marge</th><td>
                    <?php $marge = $produit['prix_vente'] - $produit['prix_achat']; ?>
                    <span style="color:<?= $marge >= 0 ? '#2e7d32' : '#c62828' ?>">
                        <?= number_format($marge, 2) ?> TND
                        (<?= $produit['prix_achat'] > 0 ? number_format(($marge/$produit['prix_achat'])*100, 1) : 0 ?>%)
                    </span>
                </td></tr>
                <tr><th>Conseil expert</th><td>
                    <?= $produit['prescription'] ? '<span class="badge" style="background:#e3f2fd;color:#1565c0"><i class="fas fa-prescription me-1"></i>Recommande</span>' : '<span class="badge" style="background:#f3f3f3;color:#666">Optionnel</span>' ?>
                </td></tr>
                <tr><th>Statut</th><td>
                    <span class="badge badge-<?= $produit['actif'] ? 'actif' : 'inactif' ?>">
                        <?= $produit['actif'] ? 'Actif' : 'Inactif' ?>
                    </span>
                </td></tr>
                <tr><th>Créé le</th><td><?= date('d/m/Y H:i', strtotime($produit['created_at'])) ?></td></tr>
                <tr><th>Mis à jour</th><td><?= date('d/m/Y H:i', strtotime($produit['updated_at'])) ?></td></tr>
            </table>
        </div>
    </div>

    <!-- Stock -->
    <div class="col-md-4">
        <div class="content-card text-center mb-4">
            <h6 style="font-weight:700;margin-bottom:12px;color:#1e2a3e">Stock actuel</h6>
            <?php
            $stockColor = '#4CAF50';
            $stockLabel = 'Normal';
            if ($produit['stock'] == 0) { $stockColor = '#f44336'; $stockLabel = 'Rupture'; }
            elseif ($produit['stock'] <= $produit['stock_alerte']) { $stockColor = '#FF9800'; $stockLabel = 'Alerte'; }
            ?>
            <div style="font-size:52px;font-weight:700;color:<?= $stockColor ?>">
                <?= $produit['stock'] ?>
            </div>
            <span class="badge" style="background:<?= $stockColor ?>22;color:<?= $stockColor ?>;font-size:14px">
                <?= $stockLabel ?>
            </span>
            <hr>
            <small class="text-muted">Seuil d'alerte : <?= $produit['stock_alerte'] ?> unités</small>
            <br>
            <small class="text-muted">
                Valeur stock : <strong><?= number_format($produit['stock'] * $produit['prix_vente'], 2) ?> TND</strong>
            </small>
        </div>

        <?php if ($produit['image']): ?>
        <div class="content-card text-center">
            <h6 style="font-weight:700;margin-bottom:12px;color:#1e2a3e">Image</h6>
            <img src="<?= htmlspecialchars($produit['image']) ?>"
                 alt="<?= htmlspecialchars($produit['nom']) ?>"
                 style="max-width:100%;border-radius:8px;max-height:150px;object-fit:contain"
                 onerror="this.style.display='none'">
        </div>
        <?php endif; ?>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/valo-backoffice.js"></script>
</div></body></html>
