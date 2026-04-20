<?php
// views/backoffice/evenements/advanced.php
$current_page = 'evenements_admin';
$f = $filtres ?? [];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Événements Avancés - DocTime</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        :root {
            --navy: #1a2035; --teal: #0fa99b; --teal-light: #e6f7f5;
            --green: #4CAF50; --gray-50: #f8fafc; --gray-100: #f1f5f9;
            --gray-200: #e2e8f0; --gray-500: #64748b; --gray-800: #1e293b;
            --shadow: 0 4px 6px -1px rgba(0,0,0,0.05); --radius: 12px;
        }
        body { font-family: 'Segoe UI', system-ui, sans-serif; background: var(--gray-50); color: var(--gray-800); min-height: 100vh; display: flex; }
        .main-content { margin-left: 260px; flex: 1; padding: 25px; }
        .page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; background: white; border-radius: var(--radius); padding: 18px 25px; box-shadow: var(--shadow); }
        .page-header h1 { font-size: 20px; font-weight: 700; color: var(--navy); margin: 0; display: flex; align-items: center; gap: 10px; }
        .page-header h1 i { color: var(--teal); }
        .header-actions { display: flex; gap: 10px; }
        .header-actions a { padding: 8px 16px; border-radius: 8px; font-size: 13px; font-weight: 600; text-decoration: none; display: inline-flex; align-items: center; gap: 6px; transition: all 0.2s; }
        .btn-back { background: var(--gray-200); color: var(--gray-800); }
        .btn-back:hover { background: #cbd5e1; color: var(--gray-800); }

        /* Search Panel */
        .search-panel { background: white; border-radius: var(--radius); box-shadow: var(--shadow); margin-bottom: 20px; overflow: hidden; }
        .search-header { padding: 16px 24px; background: linear-gradient(135deg, var(--navy), #2d3a5c); color: white; cursor: pointer; display: flex; align-items: center; justify-content: space-between; }
        .search-header h3 { font-size: 15px; font-weight: 600; margin: 0; display: flex; align-items: center; gap: 8px; }
        .search-header .chevron { transition: transform 0.3s; }
        .search-header .chevron.open { transform: rotate(180deg); }
        .search-body { padding: 20px 24px; display: none; }
        .search-body.show { display: block; }
        .form-label-sm { font-size: 12px; font-weight: 600; color: var(--gray-500); text-transform: uppercase; letter-spacing: .5px; margin-bottom: 5px; }
        .form-control, .form-select { border-radius: 8px; border: 1.5px solid var(--gray-200); font-size: 13px; padding: 8px 12px; transition: border-color .2s; }
        .form-control:focus, .form-select:focus { border-color: var(--teal); box-shadow: 0 0 0 3px rgba(15,169,155,.1); }
        .btn-search { background: linear-gradient(135deg, var(--teal), #0d8a7d); color: white; border: none; border-radius: 8px; padding: 10px 24px; font-weight: 600; font-size: 13px; display: inline-flex; align-items: center; gap: 8px; cursor: pointer; }
        .btn-search:hover { opacity: .9; color: white; }
        .btn-reset { background: var(--gray-200); color: var(--gray-800); border: none; border-radius: 8px; padding: 10px 20px; font-weight: 600; font-size: 13px; text-decoration: none; display: inline-flex; align-items: center; gap: 6px; }
        .btn-reset:hover { background: #cbd5e1; color: var(--gray-800); }
        .form-check-input:checked { background-color: var(--teal); border-color: var(--teal); }

        /* Results */
        .results-panel { background: white; border-radius: var(--radius); box-shadow: var(--shadow); margin-bottom: 20px; }
        .results-header { padding: 16px 24px; border-bottom: 1px solid var(--gray-100); display: flex; align-items: center; justify-content: space-between; }
        .results-header h4 { margin: 0; font-size: 15px; font-weight: 600; color: var(--navy); display: flex; align-items: center; gap: 8px; }
        .results-count { background: var(--teal-light); color: var(--teal); padding: 3px 10px; border-radius: 20px; font-size: 12px; font-weight: 700; }
        .table thead th { background: var(--navy); color: white; font-weight: 600; font-size: 12px; padding: 10px 14px; border: none; text-transform: uppercase; letter-spacing: .5px; }
        .table tbody td { vertical-align: middle; font-size: 13px; padding: 12px 14px; color: #333; }
        .table tbody tr:hover { background: #f0fdfa; }

        /* Badges */
        .badge-status { padding: 4px 10px; border-radius: 20px; font-size: 11px; font-weight: 600; }
        .badge-avenir   { background: #dbeafe; color: #1e40af; }
        .badge-encours  { background: #fef3c7; color: #92400e; }
        .badge-termine  { background: #d1fae5; color: #065f46; }
        .badge-annule   { background: #fee2e2; color: #991b1b; }

        /* Dashboard Grid */
        .dashboard-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px; }
        .card { background: white; border-radius: var(--radius); box-shadow: var(--shadow); border: none; overflow: hidden; }
        .card-header-custom { padding: 16px 20px; background: white; border-bottom: 1px solid var(--gray-100); display: flex; align-items: center; gap: 8px; }
        .card-header-custom h4 { margin: 0; font-size: 14px; font-weight: 600; color: var(--navy); display: flex; align-items: center; gap: 8px; }
        .card-body-custom { padding: 20px; }
        .table-custom { width: 100%; border-collapse: collapse; }
        .table-custom th { text-align: left; padding: 10px 14px; font-size: 11px; font-weight: 600; color: var(--gray-500); text-transform: uppercase; letter-spacing: .5px; border-bottom: 1px solid var(--gray-200); }
        .table-custom td { padding: 12px 14px; font-size: 13px; border-bottom: 1px solid var(--gray-100); vertical-align: middle; }
        .table-custom tr:last-child td { border-bottom: none; }
        .event-title-cell { font-weight: 600; color: var(--navy); max-width: 200px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .badge-specialty { background: var(--gray-100); color: var(--gray-800); padding: 3px 10px; border-radius: 20px; font-size: 11px; font-weight: 500; border: 1px solid var(--gray-200); }
        .badge-rate { background: #e8f5e9; color: #2e7d32; padding: 3px 8px; border-radius: 4px; font-size: 12px; font-weight: 700; }
        .text-bold { font-weight: 600; }
        .text-green { color: var(--green); }
        .total-row { font-weight: 700; background: var(--gray-50); }
        .total-row td { border-top: 2px solid var(--gray-200); }
        .btn-icon { background: var(--teal-light); color: var(--teal); border: none; width: 30px; height: 30px; border-radius: 6px; display: inline-flex; align-items: center; justify-content: center; cursor: pointer; text-decoration: none; transition: all .2s; font-size: 12px; }
        .btn-icon:hover { background: var(--teal); color: white; }

        /* Alert cards */
        .alert-card { background: linear-gradient(135deg, #fef3c7, #fde68a); border-radius: var(--radius); padding: 16px 20px; margin-bottom: 20px; display: flex; align-items: center; gap: 12px; }
        .alert-card i { font-size: 24px; color: #d97706; }
        .alert-card .alert-text { flex: 1; }
        .alert-card .alert-text strong { color: #92400e; }
        .alert-card .alert-text p { margin: 0; font-size: 13px; color: #78350f; }

        .chart-container { position: relative; height: 280px; width: 100%; }

        @media(max-width: 992px) { .dashboard-grid { grid-template-columns: 1fr; } }
    </style>
</head>
<body>

<?php include __DIR__ . '/../sidebar.php'; ?>

<div class="main-content">
    <!-- Header -->
    <div class="page-header">
        <h1><i class="fas fa-chart-bar"></i> Événements — Vue avancée</h1>
        <div class="header-actions">
            <a href="index.php?page=evenements_admin" class="btn-back"><i class="fas fa-arrow-left"></i> Liste</a>
        </div>
    </div>

    <?php if (isset($_SESSION['flash'])): ?>
        <div class="alert alert-<?= $_SESSION['flash']['type'] === 'error' ? 'danger' : 'success' ?> alert-dismissible fade show">
            <?= htmlspecialchars($_SESSION['flash']['message']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['flash']); ?>
    <?php endif; ?>

    <!-- Alertes saturation -->
    <?php if (!empty($vueEnsemble['alertes_saturation'])): ?>
        <div class="alert-card">
            <i class="fas fa-exclamation-triangle"></i>
            <div class="alert-text">
                <strong><?= count($vueEnsemble['alertes_saturation']) ?> événement(s) proche(s) de la saturation (&ge;80%)</strong>
                <p>
                    <?php foreach (array_slice($vueEnsemble['alertes_saturation'], 0, 3) as $a): ?>
                        <?= htmlspecialchars($a['titre']) ?> (<?= $a['taux'] ?>%)<?= $a !== end($vueEnsemble['alertes_saturation']) ? ' — ' : '' ?>
                    <?php endforeach; ?>
                </p>
            </div>
        </div>
    <?php endif; ?>

    <!-- Recherche avancée -->
    <div class="search-panel">
        <div class="search-header" onclick="toggleSearch()">
            <h3><i class="fas fa-search"></i> Recherche avancée</h3>
            <i class="fas fa-chevron-down chevron <?= ($hasSearch ?? false) ? 'open' : '' ?>" id="searchChevron"></i>
        </div>
        <div class="search-body <?= ($hasSearch ?? false) ? 'show' : '' ?>" id="searchBody">
            <form method="GET" action="index.php" id="searchForm">
                <input type="hidden" name="page" value="evenements_admin">
                <input type="hidden" name="action" value="advanced">

                <div class="row g-3 mb-3">
                    <div class="col-md-4">
                        <label class="form-label-sm">Mot-clé (titre, description, lieu)</label>
                        <input type="text" name="q" class="form-control" placeholder="Rechercher..."
                               value="<?= htmlspecialchars($f['q'] ?? '') ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label-sm">Statut</label>
                        <select name="statut" class="form-select">
                            <option value="">Tous les statuts</option>
                            <?php foreach ($statuts ?? [] as $s): ?>
                                <option value="<?= $s ?>" <?= ($f['statut'] ?? '') === $s ? 'selected' : '' ?>>
                                    <?= htmlspecialchars(ucfirst($s)) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label-sm">Sponsor</label>
                        <select name="sponsor_id" class="form-select">
                            <option value="">Tous les sponsors</option>
                            <?php foreach ($sponsors ?? [] as $sp): ?>
                                <option value="<?= $sp['id'] ?>" <?= ($f['sponsor_id'] ?? '') == $sp['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($sp['nom']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-md-3">
                        <label class="form-label-sm">Date début (min)</label>
                        <input type="date" name="date_debut_min" class="form-control"
                               value="<?= htmlspecialchars($f['date_debut_min'] ?? '') ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label-sm">Date début (max)</label>
                        <input type="date" name="date_debut_max" class="form-control"
                               value="<?= htmlspecialchars($f['date_debut_max'] ?? '') ?>">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label-sm">Prix min (TND)</label>
                        <input type="number" name="prix_min" class="form-control" step="1" min="0"
                               value="<?= htmlspecialchars($f['prix_min'] ?? '') ?>" placeholder="0">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label-sm">Prix max (TND)</label>
                        <input type="number" name="prix_max" class="form-control" step="1" min="0"
                               value="<?= htmlspecialchars($f['prix_max'] ?? '') ?>" placeholder="∞">
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="avec_places" value="1"
                                   id="avecPlaces" <?= !empty($f['avec_places']) ? 'checked' : '' ?>>
                            <label class="form-check-label" for="avecPlaces" style="font-size:13px;">Places dispo</label>
                        </div>
                    </div>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-md-3">
                        <label class="form-label-sm">Trier par</label>
                        <select name="tri" class="form-select">
                            <option value="date_debut" <?= ($f['tri'] ?? '') === 'date_debut' ? 'selected' : '' ?>>Date début</option>
                            <option value="titre" <?= ($f['tri'] ?? '') === 'titre' ? 'selected' : '' ?>>Titre</option>
                            <option value="prix" <?= ($f['tri'] ?? '') === 'prix' ? 'selected' : '' ?>>Prix</option>
                            <option value="capacite_max" <?= ($f['tri'] ?? '') === 'capacite_max' ? 'selected' : '' ?>>Capacité</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label-sm">Ordre</label>
                        <select name="ordre" class="form-select">
                            <option value="DESC" <?= ($f['ordre'] ?? '') === 'DESC' ? 'selected' : '' ?>>Décroissant</option>
                            <option value="ASC" <?= ($f['ordre'] ?? '') === 'ASC' ? 'selected' : '' ?>>Croissant</option>
                        </select>
                    </div>
                    <div class="col-md-6 d-flex align-items-end gap-2">
                        <button type="submit" class="btn-search"><i class="fas fa-search"></i> Rechercher</button>
                        <a href="index.php?page=evenements_admin&action=advanced" class="btn-reset"><i class="fas fa-undo"></i> Réinitialiser</a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Résultats de recherche -->
    <?php if ($hasSearch ?? false): ?>
    <div class="results-panel">
        <div class="results-header">
            <h4><i class="fas fa-list-ul"></i> Résultats de recherche <span class="results-count"><?= count($searchResults) ?></span></h4>
        </div>
        <div style="padding: 0 20px 20px;">
            <?php if (empty($searchResults)): ?>
                <div class="text-center text-muted py-5">
                    <i class="fas fa-search fa-2x mb-2 d-block opacity-25"></i>
                    Aucun événement ne correspond aux critères.
                </div>
            <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" id="searchResultsTable">
                    <thead>
                        <tr>
                            <th>Titre</th>
                            <th>Date</th>
                            <th>Lieu</th>
                            <th>Prix</th>
                            <th>Inscrits</th>
                            <th>Statut</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($searchResults as $ev): ?>
                        <?php
                            $statusClass = match($ev['status'] ?? '') {
                                'à venir'  => 'badge-avenir',
                                'en_cours' => 'badge-encours',
                                'terminé'  => 'badge-termine',
                                'annulé'   => 'badge-annule',
                                default    => 'badge-avenir',
                            };
                        ?>
                        <tr>
                            <td><strong><?= htmlspecialchars(mb_substr($ev['titre'], 0, 50)) ?></strong></td>
                            <td><?= date('d/m/Y H:i', strtotime($ev['date_debut'])) ?></td>
                            <td><?= htmlspecialchars($ev['lieu'] ?? 'En ligne') ?></td>
                            <td><?= $ev['prix'] > 0 ? number_format($ev['prix'], 2) . ' TND' : '<span style="color:#0fa99b;font-weight:600;">Gratuit</span>' ?></td>
                            <td>
                                <span class="text-bold"><?= (int)($ev['nb_inscrits'] ?? 0) ?></span>
                                <?php if ($ev['capacite_max'] > 0): ?>
                                    / <?= $ev['capacite_max'] ?>
                                <?php endif; ?>
                            </td>
                            <td><span class="badge-status <?= $statusClass ?>"><?= htmlspecialchars(ucfirst($ev['status'] ?? 'à venir')) ?></span></td>
                            <td>
                                <div class="d-flex gap-1">
                                    <a href="index.php?page=evenements_admin&action=show&id=<?= $ev['id'] ?>" class="btn-icon" title="Voir"><i class="fas fa-eye"></i></a>
                                    <a href="index.php?page=evenements_admin&action=edit&id=<?= $ev['id'] ?>" class="btn-icon" title="Modifier" style="background:#e3f0ff;color:#1565c0;"><i class="fas fa-edit"></i></a>
                                    <a href="index.php?page=evenements_admin&action=advanced&export=csv&event_id=<?= $ev['id'] ?>" class="btn-icon" title="Exporter CSV" style="background:#fef3c7;color:#d97706;"><i class="fas fa-download"></i></a>
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

    <!-- Prochains 30 jours -->
    <?php if (!empty($vueEnsemble['prochains_30j'])): ?>
    <div class="card mb-4">
        <div class="card-header-custom">
            <h4><i class="fas fa-calendar-week" style="color:#3b82f6;"></i> Événements dans les 30 prochains jours (<?= count($vueEnsemble['prochains_30j']) ?>)</h4>
        </div>
        <div class="card-body-custom" style="padding:0;">
            <table class="table-custom">
                <thead><tr><th>Titre</th><th>Date</th><th>Lieu</th><th>Inscrits</th><th>Statut</th><th></th></tr></thead>
                <tbody>
                <?php foreach ($vueEnsemble['prochains_30j'] as $p): ?>
                    <tr>
                        <td class="event-title-cell"><?= htmlspecialchars($p['titre']) ?></td>
                        <td><?= date('d/m/Y H:i', strtotime($p['date_debut'])) ?></td>
                        <td><?= htmlspecialchars($p['lieu'] ?? 'En ligne') ?></td>
                        <td><span class="text-bold"><?= (int)$p['nb_inscrits'] ?></span><?= $p['capacite_max'] > 0 ? ' / '.$p['capacite_max'] : '' ?></td>
                        <td><?php
                            $sc = match($p['status'] ?? '') { 'à venir'=>'badge-avenir','en_cours'=>'badge-encours','terminé'=>'badge-termine','annulé'=>'badge-annule', default=>'badge-avenir' };
                        ?><span class="badge-status <?= $sc ?>"><?= ucfirst($p['status']) ?></span></td>
                        <td><a href="index.php?page=evenements_admin&action=show&id=<?= $p['id'] ?>" class="btn-icon"><i class="fas fa-eye"></i></a></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>

    <!-- Dashboard Grid -->
    <div class="dashboard-grid">
        <!-- Top 5 Événements -->
        <div class="card">
            <div class="card-header-custom">
                <h4><i class="fas fa-trophy" style="color:#f59e0b;"></i> Top 5 par inscrits</h4>
            </div>
            <div class="card-body-custom" style="padding:0;">
                <table class="table-custom">
                    <thead><tr><th>Titre</th><th>Spécialité</th><th>Inscrits</th><th>Taux</th><th></th></tr></thead>
                    <tbody>
                    <?php if(!empty($topEvents)): foreach($topEvents as $event):
                        $ins = (int)$event['inscrits']; $cap = (int)$event['capacite_max'];
                        $taux = $cap > 0 ? min(100, round(($ins / $cap) * 100)) : 0;
                    ?>
                        <tr>
                            <td class="event-title-cell"><?= htmlspecialchars($event['titre']) ?></td>
                            <td><span class="badge-specialty"><?= htmlspecialchars(ucfirst($event['specialite'] ?? 'Autre')) ?></span></td>
                            <td><span class="text-bold"><?= $ins ?></span> / <?= $cap > 0 ? $cap : '∞' ?></td>
                            <td><span class="badge-rate"><?= $taux ?>%</span></td>
                            <td><a href="index.php?page=evenements_admin&action=show&id=<?= $event['id'] ?>" class="btn-icon"><i class="fas fa-chart-line"></i></a></td>
                        </tr>
                    <?php endforeach; else: ?>
                        <tr><td colspan="5" class="text-center text-muted py-3">Aucun événement.</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Revenus -->
        <div class="card">
            <div class="card-header-custom">
                <h4><i class="fas fa-hand-holding-usd" style="color:#10b981;"></i> Revenus estimés</h4>
            </div>
            <div class="card-body-custom" style="padding:0;">
                <table class="table-custom">
                    <thead><tr><th>Événement</th><th>Prix</th><th>Confirmés</th><th>Revenu</th></tr></thead>
                    <tbody>
                    <?php $totalRevenu = 0;
                    if(!empty($revenueEvents)): foreach($revenueEvents as $ev):
                        $rev = $ev['prix'] * $ev['confirmes']; $totalRevenu += $rev;
                    ?>
                        <tr>
                            <td class="event-title-cell"><?= htmlspecialchars($ev['titre']) ?></td>
                            <td><?= number_format($ev['prix'], 2) ?> TND</td>
                            <td class="text-bold"><?= $ev['confirmes'] ?></td>
                            <td class="text-green text-bold"><?= number_format($rev, 2) ?> TND</td>
                        </tr>
                    <?php endforeach; else: ?>
                        <tr><td colspan="4" class="text-center text-muted py-3">Pas de revenus.</td></tr>
                    <?php endif; ?>
                    <tr class="total-row"><td colspan="3">Total estimé</td><td class="text-green"><?= number_format($totalRevenu, 2) ?> TND</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Répartition par statut + spécialité -->
    <div class="dashboard-grid">
        <!-- Par statut -->
        <div class="card">
            <div class="card-header-custom">
                <h4><i class="fas fa-signal" style="color:#8b5cf6;"></i> Répartition par statut</h4>
            </div>
            <div class="card-body-custom">
                <div class="chart-container"><canvas id="statusChart"></canvas></div>
            </div>
        </div>

        <!-- Par spécialité -->
        <div class="card">
            <div class="card-header-custom">
                <h4><i class="fas fa-chart-pie" style="color:#3b82f6;"></i> Par spécialité / type</h4>
            </div>
            <div class="card-body-custom">
                <div class="chart-container"><canvas id="specialtyChart"></canvas></div>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>
<script>
// Toggle search panel
function toggleSearch() {
    const body = document.getElementById('searchBody');
    const chev = document.getElementById('searchChevron');
    body.classList.toggle('show');
    chev.classList.toggle('open');
}

$(document).ready(function() {
    if ($('#searchResultsTable').length) {
        $('#searchResultsTable').DataTable({
            language: { url: '//cdn.datatables.net/plug-ins/1.13.4/i18n/fr-FR.json' },
            pageLength: 10, order: [[1, 'desc']],
            columnDefs: [{ orderable: false, targets: 6 }]
        });
    }
});

// Charts
document.addEventListener('DOMContentLoaded', function() {
    // Status chart
    <?php
    $statusLabels = []; $statusData = [];
    foreach ($vueEnsemble['par_statut'] ?? [] as $ps) {
        $statusLabels[] = ucfirst($ps['status']);
        $statusData[]   = (int)$ps['total'];
    }
    if (empty($statusLabels)) { $statusLabels = ['À venir','Terminé','Annulé']; $statusData = [0,0,0]; }
    ?>
    new Chart(document.getElementById('statusChart'), {
        type: 'doughnut',
        data: {
            labels: <?= json_encode($statusLabels) ?>,
            datasets: [{
                data: <?= json_encode($statusData) ?>,
                backgroundColor: ['#3b82f6','#f59e0b','#10b981','#ef4444','#8b5cf6','#64748b'],
                borderWidth: 0
            }]
        },
        options: {
            responsive: true, maintainAspectRatio: false,
            plugins: {
                legend: { position: 'bottom', labels: { padding: 16, usePointStyle: true, font: { size: 12 } } }
            }
        }
    });

    // Specialty chart
    <?php
    $specLabels = []; $specData = [];
    if (!empty($specialtiesDistribution)) {
        foreach ($specialtiesDistribution as $sd) { $specLabels[] = ucfirst($sd['specialite']); $specData[] = (int)$sd['count']; }
    } else { $specLabels = ['Aucune donnée']; $specData = [0]; }
    ?>
    new Chart(document.getElementById('specialtyChart'), {
        type: 'bar',
        data: {
            labels: <?= json_encode($specLabels) ?>,
            datasets: [{
                label: 'Événements',
                data: <?= json_encode($specData) ?>,
                backgroundColor: ['#3b82f6','#10b981','#f59e0b','#ef4444','#06b6d4','#8b5cf6','#64748b'],
                borderWidth: 0, borderRadius: 4, barPercentage: 0.6
            }]
        },
        options: {
            responsive: true, maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                y: { beginAtZero: true, grid: { color: '#f1f5f9' }, ticks: { stepSize: 1, color: '#64748b' } },
                x: { grid: { display: false }, ticks: { color: '#475569', font: { weight: '500' } } }
            }
        }
    });
});
</script>
</body>
</html>
