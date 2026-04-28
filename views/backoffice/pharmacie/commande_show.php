<?php
$pageTitle  = 'Commande — ' . htmlspecialchars($commande['numero_commande']);
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
        <i class="fas fa-shopping-cart me-2" style="color:#4CAF50"></i>
        <?= htmlspecialchars($commande['numero_commande']) ?>
    </h5>
    <div class="d-flex gap-2">
        <a href="index.php?page=commandes_admin&action=edit&id=<?= $commande['id'] ?>"
           class="btn btn-warning btn-sm"><i class="fas fa-edit me-1"></i>Modifier</a>
        <a href="index.php?page=commandes_admin&action=delete&id=<?= $commande['id'] ?>"
           class="btn btn-danger btn-sm"
           onclick="return confirm('Supprimer cette commande ?')"><i class="fas fa-trash me-1"></i>Supprimer</a>
        <a href="index.php?page=commandes_admin" class="btn btn-outline-secondary btn-sm">
            <i class="fas fa-arrow-left me-1"></i>Retour
        </a>
    </div>
</div>

<div class="row g-4">
    <!-- Infos client + livraison -->
    <div class="col-md-5">
        <div class="content-card mb-4">
            <h6 style="font-weight:700;margin-bottom:14px;color:#1e2a3e"><i class="fas fa-user me-2" style="color:#4CAF50"></i>Client</h6>
            <table class="table table-borderless mb-0">
                <tr><th width="40%">Nom</th><td><?= htmlspecialchars($commande['user_prenom'] . ' ' . $commande['user_nom']) ?></td></tr>
                <tr><th>Email</th><td><?= htmlspecialchars($commande['user_email']) ?></td></tr>
            </table>
        </div>
        <div class="content-card">
            <h6 style="font-weight:700;margin-bottom:14px;color:#1e2a3e"><i class="fas fa-map-marker-alt me-2" style="color:#4CAF50"></i>Livraison</h6>
            <table class="table table-borderless mb-0">
                <tr><th width="40%">Adresse</th><td><?= htmlspecialchars($commande['adresse_livraison']) ?></td></tr>
                <tr><th>Ville</th><td><?= htmlspecialchars($commande['ville']) ?> — <?= htmlspecialchars($commande['code_postal']) ?></td></tr>
                <tr><th>Téléphone</th><td><?= htmlspecialchars($commande['telephone']) ?></td></tr>
                <tr><th>Paiement</th><td><?= ucfirst($commande['mode_paiement']) ?></td></tr>
                <tr><th>Date</th><td><?= date('d/m/Y H:i', strtotime($commande['created_at'])) ?></td></tr>
                <tr><th>Statut</th><td>
                    <span class="badge badge-<?= $commande['statut'] ?>" style="font-size:13px">
                        <?= ucfirst(str_replace('_',' ',$commande['statut'])) ?>
                    </span>
                </td></tr>
                <?php if ($commande['notes']): ?>
                <tr><th>Notes</th><td><?= nl2br(htmlspecialchars($commande['notes'])) ?></td></tr>
                <?php endif; ?>
            </table>
        </div>
    </div>

    <!-- Articles + totaux -->
    <div class="col-md-7">
        <div class="content-card mb-4">
            <h6 style="font-weight:700;margin-bottom:14px;color:#1e2a3e"><i class="fas fa-pills me-2" style="color:#4CAF50"></i>Articles commandés</h6>
            <table class="table align-middle mb-0">
                <thead style="background:#f8f9fa">
                    <tr><th>Produit</th><th>Réf.</th><th>Qté</th><th>Prix unit.</th><th>Total</th></tr>
                </thead>
                <tbody>
                <?php foreach ($details as $d): ?>
                <tr>
                    <td><?= htmlspecialchars($d['produit_nom']) ?></td>
                    <td><code><?= htmlspecialchars($d['reference']) ?></code></td>
                    <td><?= $d['quantite'] ?></td>
                    <td><?= number_format($d['prix_unitaire'], 2) ?> TND</td>
                    <td><strong><?= number_format($d['total_ligne'], 2) ?> TND</strong></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
                <tfoot style="background:#f8f9fa">
                    <tr>
                        <td colspan="4" style="text-align:right">Total HT</td>
                        <td><strong><?= number_format($commande['total_ht'], 2) ?> TND</strong></td>
                    </tr>
                    <tr>
                        <td colspan="4" style="text-align:right">TVA</td>
                        <td><?= number_format($commande['tva_montant'], 2) ?> TND</td>
                    </tr>
                    <tr>
                        <td colspan="4" style="text-align:right;font-weight:700">Total TTC</td>
                        <td style="font-size:18px;font-weight:700;color:#4CAF50"><?= number_format($commande['total_ttc'], 2) ?> TND</td>
                    </tr>
                </tfoot>
            </table>
        </div>

        <!-- Changement statut rapide -->
        <div class="content-card">
            <h6 style="font-weight:700;margin-bottom:14px;color:#1e2a3e"><i class="fas fa-exchange-alt me-2" style="color:#4CAF50"></i>Changer le statut</h6>
            <div class="d-flex gap-2 flex-wrap">
            <?php foreach (['en_attente'=>'En attente','confirmee'=>'Confirmée','en_preparation'=>'En préparation','expediee'=>'Expédiée','livree'=>'Livrée','annulee'=>'Annulée'] as $v=>$l): ?>
            <button class="btn btn-sm badge-<?= $v ?>"
                    style="border:none"
                    onclick="changeStatut(<?= $commande['id'] ?>, '<?= $v ?>')">
                <?= $l ?>
            </button>
            <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<script>
function changeStatut(id, statut) {
    Valo.confirm('Changer le statut en "' + statut.replace(/_/g,' ') + '" ?', {
        title: 'Changement de statut', okLabel: 'Confirmer', okBg: '#FF9800',
        icon: 'fa-exchange-alt', iconBg: '#fff3e0', iconColor: '#FF9800'
    }).then(ok => {
        if (!ok) return;
        fetch('index.php?page=commandes_admin&action=update_statut&id=' + id, {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: 'statut=' + statut
        }).then(r => r.json()).then(d => {
            if (d.success) location.reload();
            else Valo.error('Erreur lors de la mise à jour.');
        });
    });
}
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/valo-backoffice.js"></script>
</div></body></html>
// update
