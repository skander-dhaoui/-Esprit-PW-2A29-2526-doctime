<?php
// views/backoffice/articles_advanced.php
$current_page = 'articles_admin';
$f = $filtres ?? [];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Articles — Vue avancée - DocTime</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        :root {
            --navy: #1a2035; --teal: #0fa99b; --teal-light: #e6f7f5;
            --purple: #7c3aed; --purple-light: #ede9fe;
            --blue: #3b82f6; --green: #4CAF50;
            --gray-50: #f8fafc; --gray-100: #f1f5f9; --gray-200: #e2e8f0;
            --gray-500: #64748b; --gray-800: #1e293b;
            --shadow: 0 4px 6px -1px rgba(0,0,0,0.05); --radius: 12px;
        }
        body { font-family: 'Segoe UI', system-ui, sans-serif; background: var(--gray-50); color: var(--gray-800); min-height: 100vh; display: flex; }
        .main-content { margin-left: 260px; flex: 1; padding: 25px; }

        /* Header */
        .page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; background: white; border-radius: var(--radius); padding: 18px 25px; box-shadow: var(--shadow); }
        .page-header h1 { font-size: 20px; font-weight: 700; color: var(--navy); margin: 0; display: flex; align-items: center; gap: 10px; }
        .page-header h1 i { color: var(--purple); }
        .header-actions { display: flex; gap: 10px; }
        .header-actions a { padding: 8px 16px; border-radius: 8px; font-size: 13px; font-weight: 600; text-decoration: none; display: inline-flex; align-items: center; gap: 6px; transition: all 0.2s; }
        .btn-back { background: var(--gray-200); color: var(--gray-800); }
        .btn-back:hover { background: #cbd5e1; color: var(--gray-800); }

        /* KPI Cards */
        .kpi-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; margin-bottom: 20px; }
        .kpi-card { background: white; border-radius: var(--radius); padding: 18px 20px; box-shadow: var(--shadow); }
        .kpi-card .kpi-label { font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: .5px; color: var(--gray-500); margin-bottom: 6px; }
        .kpi-card .kpi-value { font-size: 28px; font-weight: 800; }
        .kpi-card .kpi-sub { font-size: 12px; color: var(--gray-500); margin-top: 2px; }
        .kpi-articles .kpi-value { color: var(--purple); }
        .kpi-views .kpi-value { color: var(--blue); }
        .kpi-likes .kpi-value { color: #ef4444; }
        .kpi-month .kpi-value { color: var(--teal); }

        /* Search Panel */
        .search-panel { background: white; border-radius: var(--radius); box-shadow: var(--shadow); margin-bottom: 20px; overflow: hidden; }
        .search-header { padding: 14px 24px; background: linear-gradient(135deg, var(--navy), #2d3a5c); color: white; cursor: pointer; display: flex; align-items: center; justify-content: space-between; }
        .search-header h3 { font-size: 15px; font-weight: 600; margin: 0; display: flex; align-items: center; gap: 8px; }
        .search-header .chevron { transition: transform 0.3s; }
        .search-header .chevron.open { transform: rotate(180deg); }
        .search-body { padding: 20px 24px; display: none; }
        .search-body.show { display: block; }
        .form-label-sm { font-size: 11px; font-weight: 700; color: var(--gray-500); text-transform: uppercase; letter-spacing: .5px; margin-bottom: 5px; }
        .form-control, .form-select { border-radius: 8px; border: 1.5px solid var(--gray-200); font-size: 13px; padding: 8px 12px; }
        .form-control:focus, .form-select:focus { border-color: var(--purple); box-shadow: 0 0 0 3px rgba(124,58,237,.1); }
        .btn-search { background: linear-gradient(135deg, var(--purple), #6d28d9); color: white; border: none; border-radius: 8px; padding: 10px 24px; font-weight: 600; font-size: 13px; display: inline-flex; align-items: center; gap: 8px; cursor: pointer; }
        .btn-search:hover { opacity: .9; color: white; }
        .btn-reset { background: var(--gray-200); color: var(--gray-800); border: none; border-radius: 8px; padding: 10px 20px; font-weight: 600; font-size: 13px; text-decoration: none; display: inline-flex; align-items: center; gap: 6px; }
        .btn-reset:hover { background: #cbd5e1; color: var(--gray-800); }

        /* Results */
        .results-panel { background: white; border-radius: var(--radius); box-shadow: var(--shadow); margin-bottom: 20px; }
        .results-header { padding: 14px 24px; border-bottom: 1px solid var(--gray-100); display: flex; align-items: center; justify-content: space-between; }
        .results-header h4 { margin: 0; font-size: 15px; font-weight: 600; color: var(--navy); display: flex; align-items: center; gap: 8px; }
        .results-count { background: var(--purple-light); color: var(--purple); padding: 3px 10px; border-radius: 20px; font-size: 12px; font-weight: 700; }
        .table thead th { background: var(--navy); color: white; font-weight: 600; font-size: 12px; padding: 10px 14px; border: none; text-transform: uppercase; letter-spacing: .5px; }
        .table tbody td { vertical-align: middle; font-size: 13px; padding: 12px 14px; color: #333; }
        .table tbody tr:hover { background: #faf5ff; }

        /* Badges */
        .badge-status { padding: 4px 10px; border-radius: 20px; font-size: 11px; font-weight: 600; }
        .badge-publie    { background: #d1fae5; color: #065f46; }
        .badge-brouillon { background: #fef3c7; color: #92400e; }
        .badge-archive   { background: #e2e8f0; color: #475569; }

        /* Dashboard */
        .dashboard-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px; }
        .card { background: white; border-radius: var(--radius); box-shadow: var(--shadow); border: none; overflow: hidden; }
        .card-header-custom { padding: 14px 20px; background: white; border-bottom: 1px solid var(--gray-100); }
        .card-header-custom h4 { margin: 0; font-size: 14px; font-weight: 600; color: var(--navy); display: flex; align-items: center; gap: 8px; }
        .card-body-custom { padding: 20px; }
        .table-sm { width: 100%; border-collapse: collapse; }
        .table-sm th { text-align: left; font-size: 11px; font-weight: 600; color: var(--gray-500); text-transform: uppercase; padding: 8px 12px; border-bottom: 1px solid var(--gray-200); }
        .table-sm td { padding: 10px 12px; font-size: 13px; border-bottom: 1px solid var(--gray-100); }
        .table-sm tr:last-child td { border-bottom: none; }
        .title-cell { font-weight: 600; color: var(--navy); max-width: 200px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .text-bold { font-weight: 600; }
        .text-purple { color: var(--purple); }
        .btn-icon { background: var(--purple-light); color: var(--purple); border: none; width: 28px; height: 28px; border-radius: 6px; display: inline-flex; align-items: center; justify-content: center; text-decoration: none; font-size: 11px; transition: all .2s; }
        .btn-icon:hover { background: var(--purple); color: white; }
        .chart-container { position: relative; height: 260px; width: 100%; }

        @media(max-width: 992px) { .dashboard-grid, .kpi-grid { grid-template-columns: 1fr 1fr; } }
        @media(max-width: 600px) { .kpi-grid { grid-template-columns: 1fr; } }
    </style>
</head>
<body>

<?php include __DIR__ . '/sidebar.php'; ?>

<div class="main-content">
    <!-- Header -->
    <div class="page-header">
        <h1><i class="fas fa-blog"></i> Articles — Vue avancée</h1>
        <div class="header-actions">
            <a href="index.php?page=articles_admin" class="btn-back"><i class="fas fa-arrow-left"></i> Liste</a>
        </div>
    </div>

    <!-- KPIs -->
    <div class="kpi-grid">
        <div class="kpi-card kpi-articles">
            <div class="kpi-label">Total articles</div>
            <div class="kpi-value"><?= $totalArticles ?? 0 ?></div>
        </div>
        <div class="kpi-card kpi-views">
            <div class="kpi-label">Vues totales</div>
            <div class="kpi-value"><?= number_format($totalViews ?? 0) ?></div>
        </div>
        <div class="kpi-card kpi-likes">
            <div class="kpi-label">Likes totaux</div>
            <div class="kpi-value"><?= number_format($totalLikes ?? 0) ?></div>
        </div>
        <div class="kpi-card kpi-month">
            <div class="kpi-label">Ce mois</div>
            <div class="kpi-value"><?= $thisMonth ?? 0 ?></div>
            <div class="kpi-sub">articles publiés</div>
        </div>
    </div>

    <!-- Recherche avancée -->
    <div class="search-panel">
        <div class="search-header" onclick="toggleSearch()">
            <h3><i class="fas fa-search"></i> Recherche avancée</h3>
            <i class="fas fa-chevron-down chevron <?= ($hasSearch ?? false) ? 'open' : '' ?>" id="searchChevron"></i>
        </div>
        <div class="search-body <?= ($hasSearch ?? false) ? 'show' : '' ?>" id="searchBody">
            <form method="GET" action="index.php">
                <input type="hidden" name="page" value="articles_admin">
                <input type="hidden" name="action" value="advanced">

                <div class="row g-3 mb-3">
                    <div class="col-md-4">
                        <label class="form-label-sm">Mot-clé (titre, contenu, tags)</label>
                        <input type="text" name="keyword" class="form-control" placeholder="Rechercher..."
                               value="<?= htmlspecialchars($f['keyword'] ?? '') ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label-sm">Catégorie</label>
                        <select name="categorie" class="form-select">
                            <option value="">Toutes</option>
                            <?php foreach ($categories ?? [] as $c): ?>
                                <option value="<?= htmlspecialchars($c['categorie']) ?>" <?= ($f['categorie'] ?? '') === $c['categorie'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars(ucfirst($c['categorie'])) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label-sm">Statut</label>
                        <select name="status" class="form-select">
                            <option value="">Tous</option>
                            <option value="publié" <?= ($f['status'] ?? '') === 'publié' ? 'selected' : '' ?>>Publié</option>
                            <option value="brouillon" <?= ($f['status'] ?? '') === 'brouillon' ? 'selected' : '' ?>>Brouillon</option>
                            <option value="archive" <?= ($f['status'] ?? '') === 'archive' ? 'selected' : '' ?>>Archivé</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label-sm">Auteur</label>
                        <select name="auteur_id" class="form-select">
                            <option value="">Tous</option>
                            <?php foreach ($auteurs ?? [] as $a): ?>
                                <option value="<?= $a['id'] ?>" <?= ($f['auteur_id'] ?? '') == $a['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($a['nom_complet']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-md-3">
                        <label class="form-label-sm">Date min</label>
                        <input type="date" name="date_min" class="form-control" value="<?= htmlspecialchars($f['date_min'] ?? '') ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label-sm">Date max</label>
                        <input type="date" name="date_max" class="form-control" value="<?= htmlspecialchars($f['date_max'] ?? '') ?>">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label-sm">Tag</label>
                        <input type="text" name="tag" class="form-control" placeholder="Ex: santé" value="<?= htmlspecialchars($f['tag'] ?? '') ?>">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label-sm">Vues min</label>
                        <input type="number" name="vues_min" class="form-control" min="0" value="<?= htmlspecialchars($f['vues_min'] ?? '') ?>" placeholder="0">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label-sm">Trier par</label>
                        <select name="tri" class="form-select">
                            <option value="created_at" <?= ($f['tri'] ?? '') === 'created_at' ? 'selected' : '' ?>>Date</option>
                            <option value="titre" <?= ($f['tri'] ?? '') === 'titre' ? 'selected' : '' ?>>Titre</option>
                            <option value="vues" <?= ($f['tri'] ?? '') === 'vues' ? 'selected' : '' ?>>Vues</option>
                            <option value="likes" <?= ($f['tri'] ?? '') === 'likes' ? 'selected' : '' ?>>Likes</option>
                        </select>
                    </div>
                </div>

                <div class="row g-3">
                    <div class="col-md-2">
                        <label class="form-label-sm">Ordre</label>
                        <select name="ordre" class="form-select">
                            <option value="DESC" <?= ($f['ordre'] ?? '') === 'DESC' ? 'selected' : '' ?>>Décroissant</option>
                            <option value="ASC" <?= ($f['ordre'] ?? '') === 'ASC' ? 'selected' : '' ?>>Croissant</option>
                        </select>
                    </div>
                    <div class="col-md-10 d-flex align-items-end gap-2">
                        <button type="submit" class="btn-search"><i class="fas fa-search"></i> Rechercher</button>
                        <a href="index.php?page=articles_admin&action=advanced" class="btn-reset"><i class="fas fa-undo"></i> Réinitialiser</a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Résultats -->
    <?php if ($hasSearch ?? false): ?>
    <div class="results-panel">
        <div class="results-header">
            <h4><i class="fas fa-list-ul"></i> Résultats <span class="results-count"><?= count($searchResults) ?></span></h4>
        </div>
        <div style="padding: 0 20px 20px;">
            <?php if (empty($searchResults)): ?>
                <div class="text-center text-muted py-5">
                    <i class="fas fa-search fa-2x mb-2 d-block opacity-25"></i>
                    Aucun article ne correspond aux critères.
                </div>
            <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" id="searchResultsTable">
                    <thead><tr>
                        <th>Titre</th><th>Auteur</th><th>Catégorie</th><th>Vues</th><th>Likes</th><th>Commentaires</th><th>Statut</th><th>Créé le</th><th>Actions</th>
                    </tr></thead>
                    <tbody>
                    <?php foreach ($searchResults as $art): ?>
                    <?php
                        $badgeClass = match($art['status'] ?? 'brouillon') {
                            'publié' => 'badge-publie', 'archive' => 'badge-archive', default => 'badge-brouillon',
                        };
                    ?>
                    <tr>
                        <td><strong><?= htmlspecialchars(mb_substr($art['titre'], 0, 50)) ?></strong></td>
                        <td><?= htmlspecialchars(($art['auteur_prenom'] ?? '') . ' ' . ($art['auteur_name'] ?? '')) ?></td>
                        <td><?= htmlspecialchars(ucfirst($art['categorie'] ?? '—')) ?></td>
                        <td><span class="text-bold text-purple"><?= (int)$art['vues'] ?></span></td>
                        <td><span style="color:#ef4444;font-weight:600;"><?= (int)($art['likes'] ?? 0) ?></span></td>
                        <td><span class="badge bg-info"><?= (int)$art['nb_replies'] ?></span></td>
                        <td><span class="badge-status <?= $badgeClass ?>"><?= ucfirst($art['status'] ?? 'brouillon') ?></span></td>
                        <td><?= date('d/m/Y', strtotime($art['created_at'])) ?></td>
                        <td>
                            <div class="d-flex gap-1">
                                <a href="index.php?page=articles_admin&action=view&id=<?= $art['id'] ?>" class="btn-icon" title="Voir"><i class="fas fa-eye"></i></a>
                                <a href="index.php?page=articles_admin&action=edit&id=<?= $art['id'] ?>" class="btn-icon" title="Modifier" style="background:#e3f0ff;color:#1565c0;"><i class="fas fa-edit"></i></a>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Dashboard -->
    <div class="dashboard-grid">
        <!-- Top 5 par vues -->
        <div class="card">
            <div class="card-header-custom"><h4><i class="fas fa-eye" style="color:var(--blue);"></i> Top 5 par vues</h4></div>
            <div class="card-body-custom" style="padding:0;">
                <table class="table-sm">
                    <thead><tr><th>Titre</th><th>Auteur</th><th>Vues</th><th>Likes</th><th></th></tr></thead>
                    <tbody>
                    <?php if (!empty($topByViews)): foreach ($topByViews as $t): ?>
                        <tr>
                            <td class="title-cell"><?= htmlspecialchars($t['titre']) ?></td>
                            <td><?= htmlspecialchars($t['auteur_name'] ?? '—') ?></td>
                            <td class="text-bold text-purple"><?= number_format($t['vues']) ?></td>
                            <td style="color:#ef4444;font-weight:600;"><?= (int)($t['likes'] ?? 0) ?></td>
                            <td><a href="index.php?page=articles_admin&action=view&id=<?= $t['id'] ?>" class="btn-icon"><i class="fas fa-eye"></i></a></td>
                        </tr>
                    <?php endforeach; else: ?>
                        <tr><td colspan="5" class="text-center text-muted py-3">Aucun article.</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Top 5 par commentaires -->
        <div class="card">
            <div class="card-header-custom"><h4><i class="fas fa-comments" style="color:#f59e0b;"></i> Top 5 par commentaires</h4></div>
            <div class="card-body-custom" style="padding:0;">
                <table class="table-sm">
                    <thead><tr><th>Titre</th><th>Auteur</th><th>Commentaires</th><th>Vues</th><th></th></tr></thead>
                    <tbody>
                    <?php if (!empty($topByComments)): foreach ($topByComments as $t): ?>
                        <tr>
                            <td class="title-cell"><?= htmlspecialchars($t['titre']) ?></td>
                            <td><?= htmlspecialchars($t['auteur_name'] ?? '—') ?></td>
                            <td class="text-bold" style="color:#f59e0b;"><?= (int)$t['nb_replies'] ?></td>
                            <td><?= (int)$t['vues'] ?></td>
                            <td><a href="index.php?page=articles_admin&action=view&id=<?= $t['id'] ?>" class="btn-icon"><i class="fas fa-eye"></i></a></td>
                        </tr>
                    <?php endforeach; else: ?>
                        <tr><td colspan="5" class="text-center text-muted py-3">Aucun article.</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Charts -->
    <div class="dashboard-grid">
        <!-- Répartition par statut -->
        <div class="card">
            <div class="card-header-custom"><h4><i class="fas fa-chart-pie" style="color:var(--purple);"></i> Par statut</h4></div>
            <div class="card-body-custom">
                <div class="chart-container"><canvas id="statusChart"></canvas></div>
            </div>
        </div>

        <!-- Tendance mensuelle -->
        <div class="card">
            <div class="card-header-custom"><h4><i class="fas fa-chart-line" style="color:var(--teal);"></i> Publication mensuelle (6 mois)</h4></div>
            <div class="card-body-custom">
                <div class="chart-container"><canvas id="trendChart"></canvas></div>
            </div>
        </div>
    </div>

    <!-- Catégories chart -->
    <div class="card mb-4">
        <div class="card-header-custom"><h4><i class="fas fa-tags" style="color:var(--blue);"></i> Répartition par catégorie</h4></div>
        <div class="card-body-custom">
            <div class="chart-container"><canvas id="categoryChart"></canvas></div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>
<script>
function toggleSearch() {
    document.getElementById('searchBody').classList.toggle('show');
    document.getElementById('searchChevron').classList.toggle('open');
}

$(document).ready(function() {
    if ($('#searchResultsTable').length) {
        $('#searchResultsTable').DataTable({
            language: { url: '//cdn.datatables.net/plug-ins/1.13.4/i18n/fr-FR.json' },
            pageLength: 10, order: [[7, 'desc']],
            columnDefs: [{ orderable: false, targets: 8 }]
        });
    }
});

document.addEventListener('DOMContentLoaded', function() {
    // Status Chart
    <?php
    $sLabels = []; $sData = [];
    foreach ($statusDistrib ?? [] as $sd) { $sLabels[] = ucfirst($sd['status']); $sData[] = (int)$sd['total']; }
    if (empty($sLabels)) { $sLabels = ['Aucun']; $sData = [0]; }
    ?>
    new Chart(document.getElementById('statusChart'), {
        type: 'doughnut',
        data: {
            labels: <?= json_encode($sLabels) ?>,
            datasets: [{ data: <?= json_encode($sData) ?>, backgroundColor: ['#10b981','#f59e0b','#64748b','#ef4444'], borderWidth: 0 }]
        },
        options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom', labels: { padding: 14, usePointStyle: true, font: { size: 12 } } } } }
    });

    // Trend Chart
    <?php
    $tLabels = []; $tData = [];
    foreach ($monthlyTrend ?? [] as $mt) { $tLabels[] = $mt['mois']; $tData[] = (int)$mt['total']; }
    if (empty($tLabels)) { $tLabels = [date('Y-m')]; $tData = [0]; }
    ?>
    new Chart(document.getElementById('trendChart'), {
        type: 'line',
        data: {
            labels: <?= json_encode($tLabels) ?>,
            datasets: [{
                label: 'Articles', data: <?= json_encode($tData) ?>,
                borderColor: '#0fa99b', backgroundColor: 'rgba(15,169,155,0.1)',
                fill: true, tension: 0.4, borderWidth: 2, pointRadius: 4, pointBackgroundColor: '#0fa99b'
            }]
        },
        options: {
            responsive: true, maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                y: { beginAtZero: true, ticks: { stepSize: 1, color: '#64748b' }, grid: { color: '#f1f5f9' } },
                x: { grid: { display: false }, ticks: { color: '#475569' } }
            }
        }
    });

    // Category Chart
    <?php
    $cLabels = []; $cData = [];
    foreach ($categoryDistrib ?? [] as $cd) { $cLabels[] = ucfirst($cd['categorie']); $cData[] = (int)$cd['total']; }
    if (empty($cLabels)) { $cLabels = ['Aucune']; $cData = [0]; }
    ?>
    new Chart(document.getElementById('categoryChart'), {
        type: 'bar',
        data: {
            labels: <?= json_encode($cLabels) ?>,
            datasets: [{
                label: 'Articles', data: <?= json_encode($cData) ?>,
                backgroundColor: ['#7c3aed','#3b82f6','#10b981','#f59e0b','#ef4444','#06b6d4','#8b5cf6','#64748b'],
                borderWidth: 0, borderRadius: 6, barPercentage: 0.5
            }]
        },
        options: {
            responsive: true, maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                y: { beginAtZero: true, ticks: { stepSize: 1, color: '#64748b' }, grid: { color: '#f1f5f9' } },
                x: { grid: { display: false }, ticks: { color: '#475569', font: { weight: '500' } } }
            }
        }
    });
});
</script>
</body>
</html>
