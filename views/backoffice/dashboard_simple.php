<?php $pageTitle = 'Tableau de bord'; ?>
<?php require __DIR__ . '/layout_header.php'; ?>

<?php
// Récupérer les données directement dans la vue pour éviter les problèmes de portée
require_once __DIR__ . '/../../config/database.php';
$db = Database::getInstance()->getConnection();

$totalSponsors = $db->query("SELECT COUNT(*) FROM sponsors")->fetchColumn() ?? 0;
$totalMontant = $db->query("SELECT COALESCE(SUM(montant), 0) FROM sponsors")->fetchColumn() ?? 0;
$totalEvenements = $db->query("SELECT COUNT(*) FROM events")->fetchColumn() ?? 0;
$totalParticipations = $db->query("SELECT COUNT(*) FROM participations")->fetchColumn() ?? 0;
$sponsorsData = $db->query("SELECT nom, montant FROM sponsors ORDER BY montant DESC LIMIT 10")->fetchAll() ?? [];
?>

<!-- ── Stat cards ── -->
<div class="row g-3 mb-4">
    <div class="col-sm-6 col-xl-3">
        <div class="stat-card" style="background: linear-gradient(135deg,#1a7fa8,#1db88e)">
            <p>Événements</p>
            <h3><?= $totalEvenements ?></h3>
            <i class="bi bi-calendar-event stat-icon"></i>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="stat-card" style="background: linear-gradient(135deg,#7c3aed,#a855f7)">
            <p>Sponsors</p>
            <h3><?= $totalSponsors ?></h3>
            <i class="bi bi-building stat-icon"></i>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="stat-card" style="background: linear-gradient(135deg,#f59e0b,#ef4444)">
            <p>Participations</p>
            <h3><?= $totalParticipations ?></h3>
            <i class="bi bi-people stat-icon"></i>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="stat-card" style="background: linear-gradient(135deg,#0ea5e9,#06b6d4)">
            <p>Total sponsors (TND)</p>
            <h3><?= number_format($totalMontant, 0, ',', ' ') ?></h3>
            <i class="bi bi-cash-stack stat-icon"></i>
        </div>
    </div>
</div>

<!-- ── Liste des sponsors ── -->
<div class="row g-4 mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header py-3 d-flex align-items-center justify-content-between">
                <h6 class="mb-0 fw-bold">
                    <i class="bi bi-building me-2 text-primary"></i>
                    Top Sponsors
                </h6>
                <span class="badge bg-primary rounded-pill"><?= count($sponsorsData) ?></span>
            </div>
            <div class="card-body">
                <?php if (count($sponsorsData) > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Nom</th>
                                    <th>Montant (TND)</th>
                                    <th>Progress</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($sponsorsData as $sponsor): ?>
                                    <tr>
                                        <td><strong><?= htmlspecialchars($sponsor['nom']) ?></strong></td>
                                        <td><?= number_format($sponsor['montant'], 2, ',', ' ') ?></td>
                                        <td>
                                            <div class="progress" style="height: 20px;">
                                                <div class="progress-bar bg-success" style="width: <?= min(100, ($sponsor['montant'] / 15000) * 100) ?>%">
                                                    <?= number_format(($sponsor['montant'] / 15000) * 100, 1) ?>%
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle"></i> Aucun sponsor trouvé.
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require __DIR__ . '/layout_footer.php'; ?>
