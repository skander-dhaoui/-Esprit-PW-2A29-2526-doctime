<?php
$f = $filtres ?? [];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rendez-vous — Vue avancée - DocTime</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        :root {
            --navy: #1a2035; --teal: #0fa99b; --purple: #7c3aed; --purple-light: #ede9fe;
            --blue: #3b82f6; --green: #4CAF50; --gray-50: #f8fafc; --gray-100: #f1f5f9; 
            --gray-200: #e2e8f0; --gray-500: #64748b; --gray-800: #1e293b;
            --shadow: 0 4px 6px -1px rgba(0,0,0,0.05); --radius: 12px;
        }
        body { font-family: 'Segoe UI', system-ui, sans-serif; background: var(--gray-50); color: var(--gray-800); min-height: 100vh; display: flex; }
        .main-content { margin-left: 260px; flex: 1; padding: 25px; }
        .page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; background: white; border-radius: var(--radius); padding: 18px 25px; box-shadow: var(--shadow); }
        .page-header h1 { font-size: 20px; font-weight: 700; color: var(--navy); margin: 0; display: flex; align-items: center; gap: 10px; }
        .page-header h1 i { color: var(--teal); }
        .btn-back { background: var(--gray-200); color: var(--gray-800); padding: 8px 16px; border-radius: 8px; text-decoration: none; font-weight: 600; display: inline-flex; align-items: center; gap: 6px; }
        .btn-back:hover { background: #cbd5e1; }
        
        .kpi-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; margin-bottom: 20px; }
        .kpi-card { background: white; border-radius: var(--radius); padding: 18px 20px; box-shadow: var(--shadow); }
        .kpi-label { font-size: 11px; font-weight: 700; text-transform: uppercase; color: var(--gray-500); margin-bottom: 6px; }
        .kpi-value { font-size: 28px; font-weight: 800; }
        .kpi-rdv .kpi-value { color: var(--teal); }
        .kpi-confirmed .kpi-value { color: var(--green); }
        .kpi-finished .kpi-value { color: var(--blue); }
        .kpi-month .kpi-value { color: var(--purple); }
        
        .search-panel { background: white; border-radius: var(--radius); box-shadow: var(--shadow); margin-bottom: 20px; overflow: hidden; }
        .search-header { padding: 14px 24px; background: linear-gradient(135deg, var(--navy), #2d3a5c); color: white; cursor: pointer; display: flex; align-items: center; justify-content: space-between; }
        .search-header h3 { font-size: 15px; font-weight: 600; margin: 0; display: flex; align-items: center; gap: 8px; }
        .search-body { padding: 20px 24px; display: none; }
        .search-body.show { display: block; }
        .form-label-sm { font-size: 11px; font-weight: 700; color: var(--gray-500); text-transform: uppercase; margin-bottom: 5px; }
        .form-control, .form-select { border-radius: 8px; border: 1.5px solid var(--gray-200); font-size: 13px; padding: 8px 12px; }
        .form-control:focus, .form-select:focus { border-color: var(--purple); box-shadow: 0 0 0 3px rgba(124,58,237,.1); }
        .btn-search { background: linear-gradient(135deg, var(--purple), #6d28d9); color: white; border: none; border-radius: 8px; padding: 10px 24px; font-weight: 600; font-size: 13px; display: inline-flex; align-items: center; gap: 8px; cursor: pointer; }
        .btn-search:hover { opacity: .9; }
        .btn-reset { background: var(--gray-200); color: var(--gray-800); border: none; border-radius: 8px; padding: 10px 20px; font-weight: 600; font-size: 13px; text-decoration: none; display: inline-flex; align-items: center; gap: 6px; }
        
        .results-panel { background: white; border-radius: var(--radius); box-shadow: var(--shadow); margin-bottom: 20px; }
        .results-header { padding: 14px 24px; border-bottom: 1px solid var(--gray-100); display: flex; align-items: center; justify-content: space-between; }
        .results-header h4 { margin: 0; font-size: 15px; font-weight: 600; color: var(--navy); }
        .results-count { background: var(--purple-light); color: var(--purple); padding: 3px 10px; border-radius: 20px; font-size: 12px; font-weight: 700; }
        .table thead th { background: var(--navy); color: white; font-weight: 600; font-size: 12px; padding: 10px 14px; border: none; }
        .table tbody td { vertical-align: middle; font-size: 13px; padding: 12px 14px; }
        .table tbody tr:hover { background: #f0f9f8; }
        
        .badge-confirmed { background: #d1fae5; color: #065f46; padding: 4px 10px; border-radius: 20px; font-size: 11px; font-weight: 600; }
        .badge-pending { background: #fef3c7; color: #92400e; padding: 4px 10px; border-radius: 20px; font-size: 11px; font-weight: 600; }
        .badge-finished { background: #bfdbfe; color: #1e40af; padding: 4px 10px; border-radius: 20px; font-size: 11px; font-weight: 600; }
        .badge-cancelled { background: #fecaca; color: #991b1b; padding: 4px 10px; border-radius: 20px; font-size: 11px; font-weight: 600; }
        
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
        .btn-icon { background: var(--purple-light); color: var(--purple); border: none; width: 28px; height: 28px; border-radius: 6px; display: inline-flex; align-items: center; justify-content: center; text-decoration: none; font-size: 11px; transition: all .2s; }
        .btn-icon:hover { background: var(--purple); color: white; }
        
        @media(max-width: 992px) { .dashboard-grid, .kpi-grid { grid-template-columns: 1fr 1fr; } }
        @media(max-width: 600px) { .kpi-grid { grid-template-columns: 1fr; } }
    </style>
</head>
<body>

<?php include __DIR__ . '/../sidebar.php'; ?>

<div class="main-content">
    <!-- Header -->
    <div class="page-header">
        <h1><i class="fas fa-calendar-check"></i> Rendez-vous — Vue avancée</h1>
        <a href="index.php?page=rendez_vous_admin" class="btn-back"><i class="fas fa-arrow-left"></i> Liste</a>
    </div>

    <!-- KPIs -->
    <div class="kpi-grid">
        <div class="kpi-card kpi-rdv">
            <div class="kpi-label">Total RDV</div>
            <div class="kpi-value"><?= $totalRDV ?? 0 ?></div>
        </div>
        <div class="kpi-card kpi-confirmed">
            <div class="kpi-label">Confirmés</div>
            <div class="kpi-value"><?= $confirmedRDV ?? 0 ?></div>
        </div>
        <div class="kpi-card kpi-finished">
            <div class="kpi-label">Terminés</div>
            <div class="kpi-value"><?= $finishedRDV ?? 0 ?></div>
        </div>
        <div class="kpi-card kpi-month">
            <div class="kpi-label">En attente</div>
            <div class="kpi-value"><?= ($totalRDV ?? 0) - ($confirmedRDV ?? 0) - ($finishedRDV ?? 0) ?></div>
        </div>
    </div>

    <!-- Recherche avancée -->
    <div class="search-panel">
        <div class="search-header" onclick="toggleSearch()">
            <h3><i class="fas fa-search"></i> Recherche avancée</h3>
            <i class="fas fa-chevron-down" id="searchChevron" style="transition:transform 0.3s;<?= ($hasSearch ?? false) ? 'transform:rotate(180deg);' : '' ?>"></i>
        </div>
        <div class="search-body <?= ($hasSearch ?? false) ? 'show' : '' ?>" id="searchBody">
            <form method="GET" action="index.php">
                <input type="hidden" name="page" value="rendez_vous_admin">
                <input type="hidden" name="action" value="advanced">

                <div class="row g-3 mb-3">
                    <div class="col-md-4">
                        <label class="form-label-sm">Mot-clé (patient, médecin, motif)</label>
                        <input type="text" name="keyword" class="form-control" placeholder="Rechercher..."
                               value="<?= htmlspecialchars($f['keyword'] ?? '') ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label-sm">Médecin</label>
                        <select name="medecin_id" class="form-select">
                            <option value="">Tous</option>
                            <?php foreach ($medecins ?? [] as $m): ?>
                                <option value="<?= $m['id'] ?>" <?= ($f['medecin_id'] ?? '') == $m['id'] ? 'selected' : '' ?>>
                                    Dr. <?= htmlspecialchars($m['prenom'] . ' ' . $m['nom']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label-sm">Statut</label>
                        <select name="statut" class="form-select">
                            <option value="">Tous</option>
                            <option value="confirmé" <?= ($f['statut'] ?? '') === 'confirmé' ? 'selected' : '' ?>>Confirmé</option>
                            <option value="en_attente" <?= ($f['statut'] ?? '') === 'en_attente' ? 'selected' : '' ?>>En attente</option>
                            <option value="terminé" <?= ($f['statut'] ?? '') === 'terminé' ? 'selected' : '' ?>>Terminé</option>
                            <option value="annulé" <?= ($f['statut'] ?? '') === 'annulé' ? 'selected' : '' ?>>Annulé</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label-sm">Patient</label>
                        <select name="patient_id" class="form-select">
                            <option value="">Tous</option>
                            <?php foreach ($patients ?? [] as $p): ?>
                                <option value="<?= $p['id'] ?>" <?= ($f['patient_id'] ?? '') == $p['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($p['prenom'] . ' ' . $p['nom']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label-sm">Date min</label>
                        <input type="date" name="date_min" class="form-control" value="<?= htmlspecialchars($f['date_min'] ?? '') ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label-sm">Date max</label>
                        <input type="date" name="date_max" class="form-control" value="<?= htmlspecialchars($f['date_max'] ?? '') ?>">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label-sm">Trier par</label>
                        <select name="tri" class="form-select">
                            <option value="date_rendezvous" <?= ($f['tri'] ?? '') === 'date_rendezvous' ? 'selected' : '' ?>>Date</option>
                            <option value="id" <?= ($f['tri'] ?? '') === 'id' ? 'selected' : '' ?>>ID</option>
                            <option value="statut" <?= ($f['tri'] ?? '') === 'statut' ? 'selected' : '' ?>>Statut</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label-sm">Ordre</label>
                        <select name="ordre" class="form-select">
                            <option value="DESC" <?= ($f['ordre'] ?? '') === 'DESC' ? 'selected' : '' ?>>Décroissant</option>
                            <option value="ASC" <?= ($f['ordre'] ?? '') === 'ASC' ? 'selected' : '' ?>>Croissant</option>
                        </select>
                    </div>
                    <div class="col-md-2 d-flex align-items-end gap-2">
                        <button type="submit" class="btn-search w-100"><i class="fas fa-search"></i> Rechercher</button>
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
        <div style="padding: 20px;">
            <?php if (empty($searchResults)): ?>
                <div class="text-center text-muted py-5">
                    <i class="fas fa-search fa-2x mb-2 d-block opacity-25"></i>
                    Aucun RDV ne correspond aux critères.
                </div>
            <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead><tr>
                        <th>ID</th><th>Patient</th><th>Médecin</th><th>Date</th><th>Heure</th><th>Motif</th><th>Statut</th><th>Actions</th>
                    </tr></thead>
                    <tbody>
                    <?php foreach ($searchResults as $rdv): ?>
                    <?php
                        $badgeClass = match($rdv['statut'] ?? 'en_attente') {
                            'confirmé' => 'badge-confirmed', 'terminé' => 'badge-finished',
                            'annulé' => 'badge-cancelled', default => 'badge-pending',
                        };
                    ?>
                    <tr>
                        <td>#<?= $rdv['id'] ?></td>
                        <td><?= htmlspecialchars($rdv['patient_prenom'] . ' ' . $rdv['patient_nom']) ?></td>
                        <td>Dr. <?= htmlspecialchars($rdv['medecin_prenom'] . ' ' . $rdv['medecin_nom']) ?></td>
                        <td><?= date('d/m/Y', strtotime($rdv['date_rendezvous'])) ?></td>
                        <td><?= $rdv['heure_rendezvous'] ?></td>
                        <td><?= htmlspecialchars(substr($rdv['motif'], 0, 30)) ?><?= strlen($rdv['motif']) > 30 ? '...' : '' ?></td>
                        <td><span class="<?= $badgeClass ?>"><?= ucfirst($rdv['statut']) ?></span></td>
                        <td>
                            <a href="index.php?page=rendez_vous_admin&action=view&id=<?= $rdv['id'] ?>" class="btn-icon" title="Voir"><i class="fas fa-eye"></i></a>
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
        <!-- Top Médecins -->
        <div class="card">
            <div class="card-header-custom"><h4><i class="fas fa-user-md" style="color:var(--teal);"></i> Top Médecins</h4></div>
            <div class="card-body-custom" style="padding:0;">
                <table class="table-sm">
                    <thead><tr><th>Médecin</th><th>RDV</th></tr></thead>
                    <tbody>
                    <?php if (!empty($topMedecins)): foreach ($topMedecins as $m): ?>
                        <tr>
                            <td class="title-cell">Dr. <?= htmlspecialchars($m['prenom'] . ' ' . $m['nom']) ?></td>
                            <td class="text-bold"><?= $m['count'] ?></td>
                        </tr>
                    <?php endforeach; else: ?>
                        <tr><td colspan="2" class="text-center text-muted py-3">Aucune donnée.</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- RDV Récents -->
        <div class="card">
            <div class="card-header-custom"><h4><i class="fas fa-calendar" style="color:var(--blue);"></i> RDV Récents</h4></div>
            <div class="card-body-custom" style="padding:0;">
                <table class="table-sm">
                    <thead><tr><th>Date</th><th>Nombre</th></tr></thead>
                    <tbody>
                    <?php if (!empty($recentRDV)): foreach ($recentRDV as $r): ?>
                        <tr>
                            <td><?= date('d/m/Y', strtotime($r['date'])) ?></td>
                            <td class="text-bold text-success"><?= $r['count'] ?></td>
                        </tr>
                    <?php endforeach; else: ?>
                        <tr><td colspan="2" class="text-center text-muted py-3">Aucune donnée.</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
function toggleSearch() {
    const searchBody = document.getElementById('searchBody');
    const chevron = document.getElementById('searchChevron');
    searchBody.classList.toggle('show');
    chevron.style.transform = searchBody.classList.contains('show') ? 'rotate(180deg)' : 'rotate(0)';
}
</script>
</body>
</html>
