<?php $pageTitle = 'Tableau de bord Événements Avancé'; ?>
<?php require __DIR__ . '/../layout_header.php'; ?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h5 class="mb-0 fw-semibold">Vue d'ensemble avancée des Événements</h5>
        <p class="text-muted small mb-0">Alertes, performances et analyses</p>
    </div>
    <div class="d-flex gap-2">
        <a href="index.php?controller=evenementavance&action=recherche" class="btn btn-outline-primary btn-sm">
            <i class="bi bi-search me-1"></i> Recherche avancée
        </a>
        <a href="index.php?controller=evenement&action=index" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left me-1"></i> Retour
        </a>
    </div>
</div>

<?php
$alertes   = $data['alertes_saturation'];
$topEvts   = $data['top_evenements'];
$revenus   = $data['revenu_estime'];
$parSpec   = $data['par_specialite'];
$prochains = $data['prochains_30j'];
?>

<!-- ── Alertes de saturation ─────────────────────────────────────── -->
<?php if (!empty($alertes)): ?>
<div class="alert alert-warning d-flex align-items-start mb-4">
    <i class="bi bi-exclamation-triangle-fill fs-5 me-2 mt-1"></i>
    <div>
        <strong><?= count($alertes) ?> événement(s) proches de la saturation (&ge; 80%)</strong>
        <div class="mt-2 d-flex flex-wrap gap-2">
            <?php foreach ($alertes as $a): ?>
                <a href="index.php?controller=evenementavance&action=stats&id=<?= $a['id'] ?>"
                   class="badge bg-warning text-dark text-decoration-none">
                    <?= htmlspecialchars($a['titre']) ?> — <?= $a['taux'] ?>%
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- ── Événements dans les 30 prochains jours ────────────────────── -->
<?php if (!empty($prochains)): ?>
<div class="card mb-4">
    <div class="card-header fw-semibold">
        <i class="bi bi-calendar-event me-1 text-primary"></i>
        Événements dans les 30 prochains jours (<?= count($prochains) ?>)
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 small">
                <thead class="table-light">
                    <tr>
                        <th>Titre</th>
                        <th>Date</th>
                        <th>Lieu</th>
                        <th>Inscrits / Capacité</th>
                        <th>Taux</th>
                        <th class="text-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($prochains as $p):
                    $taux = $p['capacite'] > 0 ? round($p['nb_inscrits']/$p['capacite']*100) : 0;
                    $cls  = $taux >= 80 ? 'danger' : ($taux >= 50 ? 'warning' : 'success');
                ?>
                    <tr>
                        <td class="fw-semibold"><?= htmlspecialchars($p['titre']) ?></td>
                        <td><?= date('d/m/Y', strtotime($p['date_debut'])) ?></td>
                        <td class="text-muted"><?= htmlspecialchars($p['lieu']) ?></td>
                        <td><?= $p['nb_inscrits'] ?> / <?= $p['capacite'] ?></td>
                        <td>
                            <div class="progress" style="height:6px;width:80px">
                                <div class="progress-bar bg-<?= $cls ?>" style="width:<?= $taux ?>%"></div>
                            </div>
                            <small class="text-muted"><?= $taux ?>%</small>
                        </td>
                        <td class="text-center">
                            <a href="index.php?controller=evenementavance&action=stats&id=<?= $p['id'] ?>"
                               class="btn btn-sm btn-outline-info" title="Statistiques">
                                <i class="bi bi-bar-chart"></i>
                            </a>
                            <a href="index.php?controller=evenementavance&action=exportPreview&id=<?= $p['id'] ?>"
                               class="btn btn-sm btn-outline-success" title="Exporter">
                                <i class="bi bi-download"></i>
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php endif; ?>

<div class="row g-4">
    <!-- ── Top 5 événements ── -->
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header fw-semibold">
                <i class="bi bi-trophy me-1 text-warning"></i> Top 5 événements par inscrits
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 small">
                        <thead class="table-light">
                            <tr><th>Titre</th><th>Spécialité</th><th>Inscrits</th><th>Taux</th><th></th></tr>
                        </thead>
                        <tbody>
                        <?php foreach ($topEvts as $t):
                            $taux = $t['capacite'] > 0 ? round($t['nb_inscrits']/$t['capacite']*100) : 0;
                            $cls  = $taux >= 80 ? 'danger' : ($taux >= 50 ? 'warning' : 'success');
                        ?>
                            <tr>
                                <td class="fw-semibold" style="max-width:140px">
                                    <?= htmlspecialchars($t['titre']) ?>
                                </td>
                                <td><span class="badge text-bg-light border text-dark"><?= htmlspecialchars($t['specialite']) ?></span></td>
                                <td><?= $t['nb_inscrits'] ?> / <?= $t['capacite'] ?></td>
                                <td>
                                    <span class="badge bg-<?= $cls ?>"><?= $taux ?>%</span>
                                </td>
                                <td>
                                    <a href="index.php?controller=evenementavance&action=stats&id=<?= $t['id'] ?>"
                                       class="btn btn-sm btn-outline-info" title="Détails">
                                        <i class="bi bi-bar-chart"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- ── Revenus estimés ── -->
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header fw-semibold">
                <i class="bi bi-cash-coin me-1 text-success"></i> Revenus estimés (participants confirmés)
            </div>
            <div class="card-body p-0">
                <?php if (empty($revenus)): ?>
                    <p class="text-muted text-center py-4">Aucun participant confirmé pour l'instant.</p>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="table align-middle mb-0 small">
                        <thead class="table-light">
                            <tr><th>Événement</th><th>Prix</th><th>Confirmés</th><th>Revenu</th></tr>
                        </thead>
                        <tbody>
                        <?php
                            $totalRevenu = 0;
                            foreach ($revenus as $r):
                                $totalRevenu += $r['revenu'];
                        ?>
                            <tr>
                                <td style="max-width:150px"><?= htmlspecialchars($r['titre']) ?></td>
                                <td><?= number_format($r['prix'], 2) ?> TND</td>
                                <td><?= $r['nb_confirmes'] ?></td>
                                <td class="fw-semibold text-success"><?= number_format($r['revenu'], 2) ?> TND</td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                        <tfoot class="table-light">
                            <tr>
                                <td colspan="3" class="fw-bold">Total estimé</td>
                                <td class="fw-bold text-success"><?= number_format($totalRevenu, 2) ?> TND</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- ── Répartition par spécialité ── -->
    <div class="col-12">
        <div class="card">
            <div class="card-header fw-semibold">
                <i class="bi bi-pie-chart me-1 text-primary"></i> Répartition des événements par spécialité
            </div>
            <div class="card-body">
                <div class="d-flex flex-wrap gap-3 align-items-end">
                <?php
                    $maxSpec = max(array_column($parSpec, 'total') ?: [1]);
                    $colors  = ['primary','success','warning','danger','info','secondary'];
                    foreach ($parSpec as $i => $s):
                        $pct = round($s['total'] / $maxSpec * 100);
                        $col = $colors[$i % count($colors)];
                ?>
                    <div class="text-center" style="min-width:80px">
                        <div class="mb-1" style="height:120px;display:flex;align-items:flex-end;justify-content:center">
                            <div class="bg-<?= $col ?> rounded-top"
                                 style="width:40px;height:<?= max(10,$pct) ?>%;opacity:.85"
                                 title="<?= $s['total'] ?> événement(s)">
                            </div>
                        </div>
                        <div class="badge bg-<?= $col ?> mb-1"><?= $s['total'] ?></div>
                        <div class="text-muted" style="font-size:.7rem;max-width:80px;word-break:break-word">
                            <?= htmlspecialchars($s['specialite']) ?>
                        </div>
                    </div>
                <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require __DIR__ . '/../layout_footer.php'; ?>
