<?php $pageTitle = 'Statistiques – ' . htmlspecialchars($data['evenement']['titre']); ?>
<?php require __DIR__ . '/../layout_header.php'; ?>

<?php
$evt      = $data['evenement'];
$nbI      = $data['nb_inscrits'];
$places   = $data['places_restantes'];
$taux     = $data['taux_remplissage'];
$rStatut  = $data['repartition_statut'];
$rProf    = $data['repartition_profession'];
$evol     = $data['evolution_inscriptions'];
$parts    = $data['participants'];

$statutLabel = ['en_attente' => 'En attente', 'confirme' => 'Confirmé', 'annule' => 'Annulé'];
$statutColor = ['en_attente' => 'warning',    'confirme' => 'success',  'annule' => 'danger'];
$cls = $taux >= 80 ? 'danger' : ($taux >= 50 ? 'warning' : 'success');
?>

<!-- Retour + actions -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h5 class="mb-0 fw-semibold"><?= htmlspecialchars($evt['titre']) ?></h5>
        <p class="text-muted small mb-0">
            <i class="bi bi-tag me-1"></i><?= htmlspecialchars($evt['specialite']) ?>
            &nbsp;|&nbsp;<i class="bi bi-geo-alt me-1"></i><?= htmlspecialchars($evt['lieu']) ?>
            &nbsp;|&nbsp;<i class="bi bi-calendar me-1"></i>
            <?= date('d/m/Y', strtotime($evt['date_debut'])) ?> →
            <?= date('d/m/Y', strtotime($evt['date_fin'])) ?>
        </p>
    </div>
    <div class="d-flex gap-2">
        <a href="index.php?controller=evenementavance&action=exportPreview&id=<?= $evt['id'] ?>"
           class="btn btn-success btn-sm">
            <i class="bi bi-download me-1"></i> Exporter CSV
        </a>
        <a href="index.php?controller=evenement&action=edit&id=<?= $evt['id'] ?>"
           class="btn btn-outline-primary btn-sm">
            <i class="bi bi-pencil me-1"></i> Modifier
        </a>
        <a href="index.php?controller=evenementavance&action=index"
           class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left me-1"></i> Retour
        </a>
    </div>
</div>

<!-- KPIs -->
<div class="row g-3 mb-4">
    <div class="col-sm-6 col-lg-3">
        <div class="card text-center p-3">
            <div class="fs-2 fw-bold text-primary"><?= $nbI ?></div>
            <div class="text-muted small">Inscrits actifs</div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-3">
        <div class="card text-center p-3">
            <div class="fs-2 fw-bold text-<?= $places == 0 ? 'danger' : 'success' ?>"><?= $places ?></div>
            <div class="text-muted small">Places restantes</div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-3">
        <div class="card text-center p-3">
            <div class="fs-2 fw-bold text-<?= $cls ?>"><?= $taux ?>%</div>
            <div class="text-muted small">Taux de remplissage</div>
            <div class="progress mt-2" style="height:6px">
                <div class="progress-bar bg-<?= $cls ?>" style="width:<?= $taux ?>%"></div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-3">
        <div class="card text-center p-3">
            <div class="fs-2 fw-bold text-info">
                <?= $evt['prix'] > 0 ? number_format($evt['prix'] * $nbI, 0) . ' TND' : 'Gratuit' ?>
            </div>
            <div class="text-muted small">Revenu estimé</div>
        </div>
    </div>
</div>

<div class="row g-4 mb-4">
    <!-- Répartition statuts -->
    <div class="col-lg-4">
        <div class="card h-100">
            <div class="card-header fw-semibold">
                <i class="bi bi-pie-chart me-1"></i> Répartition par statut
            </div>
            <div class="card-body">
                <?php if (empty($rStatut)): ?>
                    <p class="text-muted text-center">Aucune participation.</p>
                <?php else: ?>
                    <?php foreach ($rStatut as $s): ?>
                    <div class="mb-3">
                        <div class="d-flex justify-content-between mb-1">
                            <span class="badge bg-<?= $statutColor[$s['statut']] ?? 'secondary' ?>">
                                <?= $statutLabel[$s['statut']] ?? $s['statut'] ?>
                            </span>
                            <span class="fw-semibold"><?= $s['total'] ?></span>
                        </div>
                        <?php
                        $total = array_sum(array_column($rStatut, 'total'));
                        $pct   = $total > 0 ? round($s['total']/$total*100) : 0;
                        ?>
                        <div class="progress" style="height:8px">
                            <div class="progress-bar bg-<?= $statutColor[$s['statut']] ?? 'secondary' ?>"
                                 style="width:<?= $pct ?>%"></div>
                        </div>
                        <small class="text-muted"><?= $pct ?>% du total</small>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Top professions -->
    <div class="col-lg-4">
        <div class="card h-100">
            <div class="card-header fw-semibold">
                <i class="bi bi-people me-1"></i> Top professions
            </div>
            <div class="card-body">
                <?php if (empty($rProf)): ?>
                    <p class="text-muted text-center">Aucune donnée.</p>
                <?php else:
                    $maxP = max(array_column($rProf, 'total'));
                    foreach ($rProf as $pr):
                        $pct = $maxP > 0 ? round($pr['total']/$maxP*100) : 0;
                ?>
                    <div class="mb-2">
                        <div class="d-flex justify-content-between small mb-1">
                            <span><?= htmlspecialchars($pr['profession']) ?></span>
                            <span class="fw-semibold"><?= $pr['total'] ?></span>
                        </div>
                        <div class="progress" style="height:6px">
                            <div class="progress-bar bg-primary" style="width:<?= $pct ?>%"></div>
                        </div>
                    </div>
                <?php endforeach; endif; ?>
            </div>
        </div>
    </div>

    <!-- Évolution inscriptions -->
    <div class="col-lg-4">
        <div class="card h-100">
            <div class="card-header fw-semibold">
                <i class="bi bi-graph-up me-1"></i> Évolution des inscriptions
            </div>
            <div class="card-body">
                <?php if (empty($evol)): ?>
                    <p class="text-muted text-center">Aucune inscription.</p>
                <?php else:
                    $maxE = max(array_column($evol, 'total'));
                    foreach ($evol as $e2):
                        $pct = $maxE > 0 ? round($e2['total']/$maxE*100) : 0;
                ?>
                    <div class="mb-2 d-flex align-items-center gap-2 small">
                        <span class="text-muted" style="min-width:75px">
                            <?= date('d/m', strtotime($e2['jour'])) ?>
                        </span>
                        <div class="progress flex-grow-1" style="height:14px">
                            <div class="progress-bar bg-info" style="width:<?= $pct ?>%">
                                <?= $e2['total'] ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Liste des participants -->
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center fw-semibold">
        <span><i class="bi bi-person-lines-fill me-1"></i> Liste des participants (<?= count($parts) ?>)</span>
        <a href="index.php?controller=evenementavance&action=exportPreview&id=<?= $evt['id'] ?>"
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
                        <th>Téléphone</th>
                        <th>Profession</th>
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
                        <td><?= htmlspecialchars($p['telephone']) ?></td>
                        <td><?= htmlspecialchars($p['profession']) ?></td>
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

<?php require __DIR__ . '/../layout_footer.php'; ?>
