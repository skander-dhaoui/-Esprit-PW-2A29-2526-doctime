<?php
// views/backoffice/evenements_avance/stats.php
// Statistiques détaillées d'un événement — porté depuis DOCTIME_advanced

$evt     = $data['evenement'];
$nbI     = $data['nb_inscrits'];
$places  = $data['places_restantes'];
$taux    = $data['taux_remplissage'];
$rStatut = $data['repartition_statut'];
$evol    = $data['evolution_inscriptions'];
$parts   = $data['participants'];

$statutLabel = ['inscrit'=>'Inscrit','présent'=>'Présent','absent'=>'Absent'];
$statutColor = ['inscrit'=>'primary','présent'=>'success','absent'=>'danger'];
$cls = $taux >= 80 ? 'danger' : ($taux >= 50 ? 'warning' : 'success');

$current_page = 'evenements_avance_admin';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Stats — <?= htmlspecialchars($evt['titre']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <style>
        * { margin:0;padding:0;box-sizing:border-box; }
        body { background:#f4f6f9;font-family:'Segoe UI',sans-serif; }
        .main-content { margin-left:260px;padding:25px; }
        @media(max-width:768px){ .main-content{margin-left:0;} }
        .stat-card { background:white;border-radius:15px;padding:20px;text-align:center;box-shadow:0 2px 10px rgba(0,0,0,.05); }
    </style>
</head>
<body>
<?php require_once __DIR__ . '/../sidebar.php'; ?>

<div class="main-content">

    <!-- En-tête -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-0 fw-bold" style="color:#1e2a3e">
                <i class="bi bi-bar-chart me-2 text-primary"></i><?= htmlspecialchars($evt['titre']) ?>
            </h4>
            <p class="text-muted small mb-0">
                <i class="bi bi-geo-alt me-1"></i><?= htmlspecialchars($evt['lieu'] ?? '') ?>
                &nbsp;|&nbsp;<i class="bi bi-calendar me-1"></i>
                <?= date('d/m/Y', strtotime($evt['date_debut'])) ?> →
                <?= date('d/m/Y', strtotime($evt['date_fin'])) ?>
                <?php if (!empty($evt['sponsor_nom'])): ?>
                    &nbsp;|&nbsp;<i class="bi bi-award me-1"></i><?= htmlspecialchars($evt['sponsor_nom']) ?>
                <?php endif; ?>
            </p>
        </div>
        <div class="d-flex gap-2">
            <a href="index.php?page=evenements_avance_admin&action=exportPreview&id=<?= $evt['id'] ?>"
               class="btn btn-success btn-sm">
                <i class="bi bi-download me-1"></i> Exporter CSV
            </a>
            <a href="index.php?page=evenements_avance_admin&action=index"
               class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left me-1"></i> Retour
            </a>
        </div>
    </div>

    <!-- KPIs -->
    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-lg-3">
            <div class="stat-card">
                <div class="fs-2 fw-bold text-primary"><?= $nbI ?></div>
                <div class="text-muted small">Inscrits actifs</div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div class="stat-card">
                <div class="fs-2 fw-bold text-<?= $places == 0 ? 'danger' : 'success' ?>"><?= $places ?></div>
                <div class="text-muted small">Places restantes</div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div class="stat-card">
                <div class="fs-2 fw-bold text-<?= $cls ?>"><?= $taux ?>%</div>
                <div class="text-muted small">Taux de remplissage</div>
                <div class="progress mt-2" style="height:6px">
                    <div class="progress-bar bg-<?= $cls ?>" style="width:<?= $taux ?>%"></div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div class="stat-card">
                <div class="fs-2 fw-bold text-info">
                    <?= ($evt['prix'] ?? 0) > 0
                        ? number_format($evt['prix'] * $nbI, 0) . ' TND'
                        : 'Gratuit' ?>
                </div>
                <div class="text-muted small">Revenu estimé</div>
            </div>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <!-- Répartition statuts -->
        <div class="col-lg-4">
            <div class="card h-100" style="border-radius:15px;box-shadow:0 2px 10px rgba(0,0,0,.05)">
                <div class="card-header fw-semibold">
                    <i class="bi bi-pie-chart me-1"></i> Répartition par statut
                </div>
                <div class="card-body">
                    <?php if (empty($rStatut)): ?>
                        <p class="text-muted text-center">Aucune participation.</p>
                    <?php else:
                        $totalParts = array_sum(array_column($rStatut, 'total'));
                        foreach ($rStatut as $s):
                            $pct = $totalParts > 0 ? round($s['total'] / $totalParts * 100) : 0;
                    ?>
                        <div class="mb-3">
                            <div class="d-flex justify-content-between mb-1">
                                <span class="badge bg-<?= $statutColor[$s['statut']] ?? 'secondary' ?>">
                                    <?= $statutLabel[$s['statut']] ?? $s['statut'] ?>
                                </span>
                                <span class="fw-semibold"><?= $s['total'] ?></span>
                            </div>
                            <div class="progress" style="height:8px">
                                <div class="progress-bar bg-<?= $statutColor[$s['statut']] ?? 'secondary' ?>"
                                     style="width:<?= $pct ?>%"></div>
                            </div>
                            <small class="text-muted"><?= $pct ?>% du total</small>
                        </div>
                    <?php endforeach; endif; ?>
                </div>
            </div>
        </div>

        <!-- Évolution inscriptions -->
        <div class="col-lg-8">
            <div class="card h-100" style="border-radius:15px;box-shadow:0 2px 10px rgba(0,0,0,.05)">
                <div class="card-header fw-semibold">
                    <i class="bi bi-graph-up me-1"></i> Évolution des inscriptions par jour
                </div>
                <div class="card-body">
                    <?php if (empty($evol)): ?>
                        <p class="text-muted text-center py-3">Aucune inscription enregistrée.</p>
                    <?php else:
                        $maxE = max(array_column($evol, 'total'));
                        foreach ($evol as $e2):
                            $pct = $maxE > 0 ? round($e2['total'] / $maxE * 100) : 0;
                    ?>
                        <div class="mb-2 d-flex align-items-center gap-2 small">
                            <span class="text-muted" style="min-width:75px">
                                <?= date('d/m/Y', strtotime($e2['jour'])) ?>
                            </span>
                            <div class="progress flex-grow-1" style="height:18px">
                                <div class="progress-bar bg-info" style="width:<?= $pct ?>%">
                                    &nbsp;<?= $e2['total'] ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Liste des participants -->
    <div class="card" style="border-radius:15px;box-shadow:0 2px 10px rgba(0,0,0,.05)">
        <div class="card-header d-flex justify-content-between align-items-center fw-semibold">
            <span><i class="bi bi-person-lines-fill me-1"></i> Participants (<?= count($parts) ?>)</span>
            <a href="index.php?page=evenements_avance_admin&action=exportPreview&id=<?= $evt['id'] ?>"
               class="btn btn-sm btn-outline-success">
                <i class="bi bi-file-earmark-spreadsheet me-1"></i> Export CSV
            </a>
        </div>
        <div class="card-body p-0">
            <?php if (empty($parts)): ?>
                <p class="text-center text-muted py-4">Aucun participant enregistré.</p>
            <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 small">
                    <thead class="table-light">
                        <tr>
                            <th>Nom Prénom</th>
                            <th>Email</th>
                            <th>Statut</th>
                            <th>Inscription</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($parts as $p): ?>
                        <tr>
                            <td class="fw-semibold">
                                <?= htmlspecialchars($p['nom'] . ' ' . $p['prenom']) ?>
                            </td>
                            <td><?= htmlspecialchars($p['email']) ?></td>
                            <td>
                                <span class="badge bg-<?= $statutColor[$p['statut']] ?? 'secondary' ?>">
                                    <?= $statutLabel[$p['statut']] ?? $p['statut'] ?>
                                </span>
                            </td>
                            <td class="text-muted">
                                <?= date('d/m/Y H:i', strtotime($p['date_inscription'])) ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>
    </div>

</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
