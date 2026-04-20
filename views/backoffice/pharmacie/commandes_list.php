<?php
$pageTitle  = 'Gestion des Commandes';
$activePage = 'commandes';
require __DIR__ . '/_layout_top.php';
?>

<?php if ($flash): ?>
<div class="flash-box flash-<?= $flash['type'] === 'success' ? 'success' : 'error' ?>">
    <?= htmlspecialchars($flash['message']) ?>
</div>
<?php endif; ?>

<!-- KPI -->
<div class="row g-3 mb-4">
    <?php $kpis = [
        ['label'=>'Total',       'val'=>$stats['total'],      'color'=>'#607d8b', 'icon'=>'shopping-cart'],
        ['label'=>'En attente',  'val'=>$stats['en_attente'], 'color'=>'#FF9800', 'icon'=>'clock'],
        ['label'=>'Confirmées',  'val'=>$stats['confirmees'], 'color'=>'#2196F3', 'icon'=>'check'],
        ['label'=>'Livrées',     'val'=>$stats['livrees'],    'color'=>'#4CAF50', 'icon'=>'truck'],
        ['label'=>'Annulées',    'val'=>$stats['annulees'],   'color'=>'#f44336', 'icon'=>'times'],
        ['label'=>'CA Total',    'val'=>number_format($stats['ca_total'],2).' TND', 'color'=>'#9C27B0', 'icon'=>'coins'],
    ];
    foreach ($kpis as $k): ?>
    <div class="col-6 col-md-2">
        <div class="stat-card" style="border-color:<?= $k['color'] ?>">
            <i class="fas fa-<?= $k['icon'] ?> fa-xl" style="color:<?= $k['color'] ?>44;float:right"></i>
            <h3 style="color:<?= $k['color'] ?>;font-size:22px"><?= $k['val'] ?></h3>
            <p><?= $k['label'] ?></p>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<!-- Filtres -->
<div class="content-card mb-4">
    <form method="GET" style="display:flex;gap:10px;flex-wrap:wrap;align-items:flex-end;" novalidate>
        <input type="hidden" name="page" value="commandes_admin">
        <div>
            <label class="form-label">Recherche</label>
            <input type="text" name="search" class="form-control"
                   placeholder="N° commande, client..." value="<?= htmlspecialchars($_GET['search'] ?? '') ?>" style="width:230px">
        </div>
        <div>
            <label class="form-label">Statut</label>
            <select name="statut" class="form-select" style="width:170px">
                <option value="">Tous</option>
                <?php foreach (['en_attente'=>'En attente','confirmee'=>'Confirmée','en_preparation'=>'En préparation','expediee'=>'Expédiée','livree'=>'Livrée','annulee'=>'Annulée'] as $v=>$l): ?>
                <option value="<?= $v ?>" <?= ($_GET['statut'] ?? '') === $v ? 'selected' : '' ?>><?= $l ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <button type="submit" class="btn btn-primary"><i class="fas fa-search me-1"></i>Filtrer</button>
        <a href="index.php?page=commandes_admin" class="btn btn-outline-secondary">Réinitialiser</a>
        <a href="index.php?page=commandes_admin&action=create" class="btn btn-success ms-auto">
            <i class="fas fa-plus me-1"></i>Nouvelle commande
        </a>
    </form>
</div>

<!-- Table -->
<div class="content-card">
    <h6 style="font-weight:700;margin-bottom:16px;color:#1e2a3e"><?= count($commandes) ?> commande(s)</h6>
    <?php if (empty($commandes)): ?>
    <div style="text-align:center;padding:50px;color:#999">
        <i class="fas fa-shopping-cart fa-3x mb-3" style="opacity:.3"></i>
        <p>Aucune commande trouvée.</p>
    </div>
    <?php else: ?>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead style="background:#f8f9fa">
                <tr>
                    <th>N° Commande</th>
                    <th>Client</th>
                    <th>Articles</th>
                    <th>Total TTC</th>
                    <th>Paiement</th>
                    <th>Statut</th>
                    <th>Date</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($commandes as $c): ?>
            <tr>
                <td><strong><?= htmlspecialchars($c['numero_commande']) ?></strong></td>
                <td><?= htmlspecialchars($c['user_prenom'] . ' ' . $c['user_nom']) ?></td>
                <td><span class="badge" style="background:#e3f2fd;color:#1565c0"><?= $c['nb_articles'] ?></span></td>
                <td><strong><?= number_format($c['total_ttc'], 2) ?> TND</strong></td>
                <td><small><?= ucfirst($c['mode_paiement']) ?></small></td>
                <td>
                    <span class="badge badge-<?= $c['statut'] ?>">
                        <?= ucfirst(str_replace('_', ' ', $c['statut'])) ?>
                    </span>
                </td>
                <td><small><?= date('d/m/Y', strtotime($c['created_at'])) ?></small></td>
                <td>
                    <a href="index.php?page=commandes_admin&action=show&id=<?= $c['id'] ?>"
                       class="btn btn-sm btn-outline-info"><i class="fas fa-eye"></i></a>
                    <a href="index.php?page=commandes_admin&action=edit&id=<?= $c['id'] ?>"
                       class="btn btn-sm btn-outline-warning"><i class="fas fa-edit"></i></a>
                    <a href="index.php?page=commandes_admin&action=delete&id=<?= $c['id'] ?>"
                       class="btn btn-sm btn-outline-danger"
                       onclick="return confirm('Supprimer cette commande ?')"><i class="fas fa-trash"></i></a>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/valo-backoffice.js"></script>
</div></body></html>
