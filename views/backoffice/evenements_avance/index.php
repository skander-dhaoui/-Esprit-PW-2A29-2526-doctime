<?php
// views/backoffice/evenements_avance/index.php
// Vue d'ensemble avancée des événements — porté depuis DOCTIME_advanced

$alertes   = $data['alertes_saturation'];
$topEvts   = $data['top_evenements'];
$revenus   = $data['revenu_estime'];
$parStatut = $data['par_statut'];
$prochains = $data['prochains_30j'];

$current_page = 'evenements_avance_admin';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Événements Avancé — Back Office</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { background: #f4f6f9; font-family: 'Segoe UI', sans-serif; }
        .main-content { margin-left: 260px; padding: 25px; }
        @media(max-width:768px){ .main-content{ margin-left:0; } }
    </style>
</head>
<body>
<?php require_once __DIR__ . '/../sidebar.php'; ?>

<div class="main-content">

    <!-- En-tête -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-0 fw-bold" style="color:#1e2a3e">
                <i class="bi bi-calendar-event me-2 text-primary"></i>Vue d'ensemble Événements
            </h4>
            <p class="text-muted small mb-0">Alertes, performances et analyses avancées</p>
        </div>
        <div class="d-flex gap-2">
            <a href="index.php?page=evenements_avance_admin&action=recherche" class="btn btn-outline-primary btn-sm">
                <i class="bi bi-search me-1"></i> Recherche avancée
            </a>
            <a href="index.php?page=evenements_admin" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left me-1"></i> Gestion événements
            </a>
        </div>
    </div>

    <!-- Alertes de saturation -->
    <?php if (!empty($alertes)): ?>
    <div class="alert alert-warning d-flex align-items-start mb-4">
        <i class="bi bi-exclamation-triangle-fill fs-5 me-2 mt-1"></i>
        <div>
            <strong><?= count($alertes) ?> événement(s) proche(s) de la saturation (&ge; 80%)</strong>
            <div class="mt-2 d-flex flex-wrap gap-2">
                <?php foreach ($alertes as $a): ?>
                    <a href="index.php?page=evenements_avance_admin&action=stats&id=<?= $a['id'] ?>"
                       class="badge bg-warning text-dark text-decoration-none">
                        <?= htmlspecialchars($a['titre']) ?> — <?= $a['taux'] ?>%
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Événements dans les 30 prochains jours -->
    <?php if (!empty($prochains)): ?>
    <div class="card mb-4" style="border-radius:15px;box-shadow:0 2px 10px rgba(0,0,0,.05)">
        <div class="card-header fw-semibold" style="border-radius:15px 15px 0 0">
            <i class="bi bi-calendar-week me-1 text-primary"></i>
            Événements dans les 30 prochains jours (<?= count($prochains) ?>)
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 small">
                    <thead class="table-light">
                        <tr>
                            <th>Titre</th><th>Date</th><th>Lieu</th>
                            <th>Inscrits / Capacité</th><th>Taux</th>
                            <th class="text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($prochains as $p):
                        $cap  = (int)($p['capacite_max'] ?? 0);
                        $taux = $cap > 0 ? round($p['nb_inscrits'] / $cap * 100) : 0;
                        $cls  = $taux >= 80 ? 'danger' : ($taux >= 50 ? 'warning' : 'success');
                    ?>
                        <tr>
                            <td class="fw-semibold"><?= htmlspecialchars($p['titre']) ?></td>
                            <td><?= date('d/m/Y', strtotime($p['date_debut'])) ?></td>
                            <td class="text-muted"><?= htmlspecialchars($p['lieu']) ?></td>
                            <td><?= $p['nb_inscrits'] ?> / <?= $cap ?></td>
                            <td>
                                <div class="progress" style="height:6px;width:80px">
                                    <div class="progress-bar bg-<?= $cls ?>" style="width:<?= $taux ?>%"></div>
                                </div>
                                <small class="text-muted"><?= $taux ?>%</small>
                            </td>
                            <td class="text-center">
                                <a href="index.php?page=evenements_avance_admin&action=stats&id=<?= $p['id'] ?>"
                                   class="btn btn-sm btn-outline-info" title="Statistiques">
                                    <i class="bi bi-bar-chart"></i>
                                </a>
                                <a href="index.php?page=evenements_avance_admin&action=exportPreview&id=<?= $p['id'] ?>"
                                   class="btn btn-sm btn-outline-success" title="Exporter CSV">
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
        <!-- Top 5 événements -->
        <div class="col-lg-6">
            <div class="card h-100" style="border-radius:15px;box-shadow:0 2px 10px rgba(0,0,0,.05)">
                <div class="card-header fw-semibold" style="border-radius:15px 15px 0 0">
                    <i class="bi bi-trophy me-1 text-warning"></i> Top 5 événements par inscrits
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0 small">
                            <thead class="table-light">
                                <tr><th>Titre</th><th>Inscrits</th><th>Taux</th><th></th></tr>
                            </thead>
                            <tbody>
                            <?php foreach ($topEvts as $t):
                                $cap  = (int)($t['capacite_max'] ?? 0);
                                $taux = $cap > 0 ? round($t['nb_inscrits'] / $cap * 100) : 0;
                                $cls  = $taux >= 80 ? 'danger' : ($taux >= 50 ? 'warning' : 'success');
                            ?>
                                <tr>
                                    <td class="fw-semibold" style="max-width:160px">
                                        <?= htmlspecialchars($t['titre']) ?>
                                    </td>
                                    <td><?= $t['nb_inscrits'] ?> / <?= $cap ?></td>
                                    <td><span class="badge bg-<?= $cls ?>"><?= $taux ?>%</span></td>
                                    <td>
                                        <a href="index.php?page=evenements_avance_admin&action=stats&id=<?= $t['id'] ?>"
                                           class="btn btn-sm btn-outline-info"><i class="bi bi-bar-chart"></i></a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Revenus estimés -->
        <div class="col-lg-6">
            <div class="card h-100" style="border-radius:15px;box-shadow:0 2px 10px rgba(0,0,0,.05)">
                <div class="card-header fw-semibold" style="border-radius:15px 15px 0 0">
                    <i class="bi bi-cash-coin me-1 text-success"></i> Revenus estimés
                </div>
                <div class="card-body p-0">
                    <?php if (empty($revenus)): ?>
                        <p class="text-muted text-center py-4">Aucune participation inscrite pour l'instant.</p>
                    <?php else:
                        $totalRevenu = array_sum(array_column($revenus, 'revenu'));
                    ?>
                    <div class="table-responsive">
                        <table class="table align-middle mb-0 small">
                            <thead class="table-light">
                                <tr><th>Événement</th><th>Prix</th><th>Inscrits</th><th>Revenu</th></tr>
                            </thead>
                            <tbody>
                            <?php foreach ($revenus as $r): ?>
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

        <!-- Répartition par statut -->
        <div class="col-12">
            <div class="card" style="border-radius:15px;box-shadow:0 2px 10px rgba(0,0,0,.05)">
                <div class="card-header fw-semibold" style="border-radius:15px 15px 0 0">
                    <i class="bi bi-pie-chart me-1 text-primary"></i> Répartition des événements par statut
                </div>
                <div class="card-body">
                    <div class="d-flex flex-wrap gap-3 align-items-end">
                    <?php
                    $colors  = ['primary','success','warning','danger','info','secondary'];
                    $statLabels = ['à venir'=>'À venir','en_cours'=>'En cours','terminé'=>'Terminé','annulé'=>'Annulé'];
                    $maxSt = max(array_column($parStatut ?: [['total'=>1]], 'total'));
                    foreach ($parStatut as $i => $s):
                        $pct = $maxSt > 0 ? round($s['total'] / $maxSt * 100) : 0;
                        $col = $colors[$i % count($colors)];
                    ?>
                        <div class="text-center" style="min-width:90px">
                            <div class="mb-1" style="height:100px;display:flex;align-items:flex-end;justify-content:center">
                                <div class="bg-<?= $col ?> rounded-top"
                                     style="width:44px;height:<?= max(10,$pct) ?>%;opacity:.85"
                                     title="<?= $s['total'] ?> événement(s)"></div>
                            </div>
                            <div class="badge bg-<?= $col ?> mb-1"><?= $s['total'] ?></div>
                            <div class="text-muted" style="font-size:.72rem">
                                <?= htmlspecialchars($statLabels[$s['status']] ?? $s['status']) ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div><!-- /row -->
</div><!-- /main-content -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
