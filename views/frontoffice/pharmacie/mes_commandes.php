<?php
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php?page=login');
    exit;
}
$pageTitle = 'Mes Commandes - Valorys';
$activePage = 'mes_commandes';
$extraStyles = "
    .page-header { background: linear-gradient(135deg,#2A7FAA,#4CAF50); color: white; padding: 50px 0; text-align: center; margin-bottom: 40px; }
    .commande-card { background: white; border-radius: 14px; padding: 22px; margin-bottom: 20px; box-shadow: 0 4px 15px rgba(0,0,0,.07); border-left: 5px solid; }
    .badge-en_attente     { background:#fff3cd;color:#856404; }
    .badge-confirmee      { background:#cfe2ff;color:#084298; }
    .badge-en_preparation { background:#e2d9f3;color:#4a1b8a; }
    .badge-expediee       { background:#d1ecf1;color:#0c5460; }
    .badge-livree         { background:#d4edda;color:#155724; }
    .badge-annulee        { background:#f8d7da;color:#721c24; }
";
require __DIR__ . '/partials/header.php';
?>

<div class="page-header">
    <div class="container">
        <h1><i class="fas fa-shopping-bag me-3"></i>Mes Commandes</h1>
        <p style="opacity:.9">Suivez l'état de vos commandes</p>
    </div>
</div>

<div class="container pb-5">

    <?php if ($flash): ?>
    <div class="alert alert-<?= $flash['type'] === 'success' ? 'success' : 'danger' ?> alert-dismissible fade show">
        <?= htmlspecialchars($flash['message']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <?php if (empty($commandes)): ?>
    <div class="text-center py-5">
        <i class="fas fa-shopping-cart fa-4x mb-4" style="color:#ccc"></i>
        <h4 class="text-muted">Aucune commande pour le moment</h4>
        <a href="index.php?page=pharmacie" class="btn btn-primary mt-3">
            <i class="fas fa-pills me-2"></i>Visiter la parapharmacie
        </a>
    </div>
    <?php else: ?>
    <?php
    $borderColors = ['en_attente'=>'#FF9800','confirmee'=>'#2196F3','en_preparation'=>'#9C27B0','expediee'=>'#00BCD4','livree'=>'#4CAF50','annulee'=>'#f44336'];
    ?>
    <?php foreach ($commandes as $c): ?>
    <?php $border = $borderColors[$c['statut']] ?? '#ccc'; ?>
    <div class="commande-card" style="border-left-color:<?= $border ?>">
        <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
            <div>
                <h5 style="font-weight:700;margin-bottom:4px;color:#1a2035">
                    <?= htmlspecialchars($c['numero_commande']) ?>
                </h5>
                <small class="text-muted">Passée le <?= date('d/m/Y à H:i', strtotime($c['created_at'])) ?></small>
            </div>
            <span class="badge badge-<?= $c['statut'] ?>" style="font-size:13px;padding:6px 14px">
                <?= ucfirst(str_replace('_',' ',$c['statut'])) ?>
            </span>
        </div>

        <hr>

        <div class="row g-3">
            <div class="col-md-6">
                <small class="text-muted d-block mb-1"><i class="fas fa-map-marker-alt me-1"></i>Livraison</small>
                <span><?= htmlspecialchars($c['adresse_livraison']) ?>, <?= htmlspecialchars($c['ville']) ?> <?= htmlspecialchars($c['code_postal']) ?></span>
            </div>
            <div class="col-md-3">
                <small class="text-muted d-block mb-1"><i class="fas fa-credit-card me-1"></i>Paiement</small>
                <span><?= ucfirst($c['mode_paiement']) ?></span>
            </div>
            <div class="col-md-3 text-md-end">
                <small class="text-muted d-block mb-1">Total TTC</small>
                <strong style="font-size:20px;color:#2A7FAA"><?= number_format($c['total_ttc'], 2) ?> TND</strong>
            </div>
        </div>

        <?php if (in_array($c['statut'], ['en_attente', 'confirmee'], true)): ?>
        <div class="mt-3 d-flex gap-2">
            <a href="index.php?page=mes_commandes&action=edit&id=<?= $c['id'] ?>" class="btn btn-outline-warning btn-sm">
                <i class="fas fa-edit me-1"></i>Modifier
            </a>
            <form method="POST" action="index.php?page=mes_commandes&action=cancel&id=<?= $c['id'] ?>" onsubmit="return confirm('Annuler cette commande ?');">
                <button type="submit" class="btn btn-outline-danger btn-sm">
                    <i class="fas fa-ban me-1"></i>Annuler
                </button>
            </form>
        </div>
        <?php endif; ?>

        <!-- Barre de progression statut -->
        <?php
        $etapes = ['en_attente','confirmee','en_preparation','expediee','livree'];
        $idx    = array_search($c['statut'], $etapes);
        $annulee= $c['statut'] === 'annulee';
        ?>
        <?php if (!$annulee): ?>
        <div class="mt-3">
            <div class="d-flex justify-content-between" style="font-size:11px;color:#666;margin-bottom:4px">
                <?php foreach (['Commande','Confirmée','Préparation','Expédiée','Livrée'] as $i => $step): ?>
                <span style="color:<?= ($idx !== false && $i <= $idx) ? '#4CAF50' : '#bbb' ?>;font-weight:<?= ($idx !== false && $i === $idx) ? '700' : '400' ?>"><?= $step ?></span>
                <?php endforeach; ?>
            </div>
            <div class="progress" style="height:6px;border-radius:3px">
                <div class="progress-bar" role="progressbar"
                     style="width:<?= $idx !== false ? (($idx/4)*100) : 0 ?>%;background:#4CAF50;transition:width .5s"></div>
            </div>
        </div>
        <?php else: ?>
        <div class="mt-3 p-2" style="background:#fdecea;border-radius:8px;font-size:13px;color:#c62828">
            <i class="fas fa-times-circle me-1"></i>Commande annulée
        </div>
        <?php endif; ?>
    </div>
    <?php endforeach; ?>
    <?php endif; ?>
</div>
<?php require __DIR__ . '/partials/footer.php'; ?>
// update
