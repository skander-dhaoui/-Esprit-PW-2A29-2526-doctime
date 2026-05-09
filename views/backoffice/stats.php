<?php
if (!isset($_SESSION['user_id']) || ($_SESSION['user_role'] ?? '') !== 'admin') {
    header('Location: index.php?page=login');
    exit;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Statistiques Rendez-vous - Backoffice</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body { background: #f4f6f9; font-family: 'Segoe UI', sans-serif; }
        .main-content { padding: 22px; }
        .kpi { background: #fff; border-radius: 12px; padding: 18px; box-shadow: 0 2px 8px rgba(0,0,0,.05); height: 100%; border-left: 4px solid #2a7faa; }
        .kpi h6 { color: #6c757d; font-size: 12px; text-transform: uppercase; margin-bottom: 8px; }
        .kpi .value { font-size: 28px; font-weight: 700; color: #1e2a3e; }
        .card-clean { background: #fff; border: 0; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,.05); }
        .table thead th { background: #1e2a3e; color: white; font-size: 12px; }
        .risk-badge { display:inline-block; padding:4px 10px; border-radius:999px; font-size:12px; font-weight:700; }
        .risk-green  { background:#d4edda; color:#155724; }
        .risk-orange { background:#fff3cd; color:#856404; }
        .risk-red    { background:#f8d7da; color:#721c24; }
    </style>
</head>
<body>
<div class="main-content">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3 class="mb-0"><i class="fas fa-chart-line me-2"></i>Statistiques rendez-vous</h3>
        <a href="index.php?page=rendez_vous_admin" class="btn btn-outline-primary btn-sm">
            <i class="fas fa-calendar-check me-1"></i>Voir les rendez-vous
        </a>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-md-3"><div class="kpi"><h6>Total RDV</h6><div class="value"><?= (int)$totalRdv ?></div></div></div>
        <div class="col-md-3"><div class="kpi"><h6>Aujourd'hui</h6><div class="value"><?= (int)$todayRdv ?></div></div></div>
        <div class="col-md-3"><div class="kpi"><h6>Prochains 7 jours</h6><div class="value"><?= (int)$next7DaysRdv ?></div></div></div>
        <div class="col-md-3"><div class="kpi"><h6>Taux confirmation</h6><div class="value"><?= $totalRdv > 0 ? round(($confirmedRdv / $totalRdv) * 100, 1) : 0 ?>%</div></div></div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-md-3"><div class="kpi"><h6>RDV en attente</h6><div class="value"><?= (int)$pendingRdv ?></div></div></div>
        <div class="col-md-3"><div class="kpi"><h6>RDV annulés</h6><div class="value"><?= (int)$cancelledRdv ?></div></div></div>
        <div class="col-md-3"><div class="kpi"><h6>RDV terminés</h6><div class="value"><?= (int)$finishedRdv ?></div></div></div>
        <div class="col-md-3"><div class="kpi"><h6>Liste d'attente</h6><div class="value"><?= (int)($waitlistPending ?? 0) ?></div></div></div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-md-6"><div class="kpi"><h6>Score risque moyen</h6><div class="value"><?= (float)($riskScoreAvg ?? 0) ?>/100</div></div></div>
        <div class="col-md-6"><div class="kpi"><h6>Score risque max</h6><div class="value"><?= (float)($riskScoreMax ?? 0) ?>/100</div></div></div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-md-8">
            <div class="card card-clean">
                <div class="card-body">
                    <h5 class="card-title mb-3">Evolution mensuelle des rendez-vous</h5>
                    <canvas id="monthlyChart" height="110"></canvas>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card card-clean">
                <div class="card-body">
                    <h5 class="card-title mb-3">Répartition par statut</h5>
                    <canvas id="statusChart" height="180"></canvas>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-md-6">
            <div class="card card-clean">
                <div class="card-body">
                    <h5 class="card-title mb-3">Top médecins (volume RDV)</h5>
                    <div class="table-responsive">
                        <table class="table table-sm align-middle">
                            <thead><tr><th>Médecin</th><th class="text-end">RDV</th></tr></thead>
                            <tbody>
                            <?php if (!empty($topDoctors)): foreach ($topDoctors as $d): ?>
                                <tr>
                                    <td><?= htmlspecialchars($d['medecin_nom']) ?></td>
                                    <td class="text-end fw-bold"><?= (int)$d['total'] ?></td>
                                </tr>
                            <?php endforeach; else: ?>
                                <tr><td colspan="2" class="text-center text-muted">Aucune donnée</td></tr>
                            <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card card-clean">
                <div class="card-body">
                    <h5 class="card-title mb-3">Top patients (volume RDV)</h5>
                    <div class="table-responsive">
                        <table class="table table-sm align-middle">
                            <thead><tr><th>Patient</th><th class="text-end">RDV</th></tr></thead>
                            <tbody>
                            <?php if (!empty($topPatients)): foreach ($topPatients as $p): ?>
                                <tr>
                                    <td><?= htmlspecialchars($p['patient_nom']) ?></td>
                                    <td class="text-end fw-bold"><?= (int)$p['total'] ?></td>
                                </tr>
                            <?php endforeach; else: ?>
                                <tr><td colspan="2" class="text-center text-muted">Aucune donnée</td></tr>
                            <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mt-1">
        <div class="col-12">
            <div class="card card-clean">
                <div class="card-body">
                    <h5 class="card-title mb-1">Top patients à risque no-show (score fiabilité)</h5>
                    <p class="text-muted small mb-3">Le score est sur 100 (0 = fiable, 100 = très risqué).</p>
                    <div class="table-responsive">
                        <table class="table table-sm align-middle">
                            <thead>
                                <tr>
                                    <th>Patient</th>
                                    <th class="text-end">Total RDV</th>
                                    <th class="text-end">Annulations</th>
                                    <th class="text-end">Absences</th>
                                    <th class="text-end">Score risque</th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php if (!empty($riskyPatients)): foreach ($riskyPatients as $p): ?>
                                <?php
                                    $score = (float)($p['risk_score'] ?? 0);
                                    if ($score <= 20) {
                                        $badgeClass = 'risk-badge risk-green';
                                        $badgeLabel = 'Fiable';
                                    } elseif ($score <= 50) {
                                        $badgeClass = 'risk-badge risk-orange';
                                        $badgeLabel = 'Moyen';
                                    } else {
                                        $badgeClass = 'risk-badge risk-red';
                                        $badgeLabel = 'Risque élevé';
                                    }
                                ?>
                                <tr>
                                    <td><?= htmlspecialchars($p['patient_nom']) ?></td>
                                    <td class="text-end"><?= (int)$p['total_rdv'] ?></td>
                                    <td class="text-end"><?= (int)$p['cancelled_count'] ?></td>
                                    <td class="text-end"><?= (int)$p['absent_count'] ?></td>
                                    <td class="text-end">
                                        <span class="<?= $badgeClass ?>"><?= $badgeLabel ?></span>
                                        <span class="ms-2 fw-bold"><?= $score ?></span>
                                    </td>
                                </tr>
                            <?php endforeach; else: ?>
                                <tr><td colspan="5" class="text-center text-muted">Pas assez d'historique pour calculer le score.</td></tr>
                            <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
const monthlyLabels = <?= json_encode(array_column($monthlyTrend ?? [], 'mois')) ?>;
const monthlyValues = <?= json_encode(array_map('intval', array_column($monthlyTrend ?? [], 'total'))) ?>;
const statusLabels = <?= json_encode(array_column($statusDistribution ?? [], 'statut')) ?>;
const statusValues = <?= json_encode(array_map('intval', array_column($statusDistribution ?? [], 'total'))) ?>;

new Chart(document.getElementById('monthlyChart'), {
    type: 'line',
    data: {
        labels: monthlyLabels,
        datasets: [{
            label: 'Rendez-vous',
            data: monthlyValues,
            borderColor: '#2a7faa',
            backgroundColor: 'rgba(42,127,170,0.15)',
            tension: 0.3,
            fill: true
        }]
    },
    options: { responsive: true, plugins: { legend: { display: false } } }
});

new Chart(document.getElementById('statusChart'), {
    type: 'doughnut',
    data: {
        labels: statusLabels,
        datasets: [{
            data: statusValues,
            backgroundColor: ['#ffc107', '#28a745', '#17a2b8', '#dc3545', '#6c757d']
        }]
    },
    options: { responsive: true, plugins: { legend: { position: 'bottom' } } }
});
</script>
</body>
</html>
