<?php
// views/backoffice/evenements/list.php
// Variables disponibles : $events (array)
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Événements</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        :root {
            --teal:       #0d9488;
            --teal-dark:  #0f766e;
            --teal-light: #ccfbf1;
            --red:        #ef4444;
            --red-light:  #fee2e2;
            --blue:       #3b82f6;
            --blue-light: #dbeafe;
            --green:      #22c55e;
            --green-light:#dcfce7;
            --orange:     #f97316;
            --orange-light:#ffedd5;
            --gray-50:    #f8fafc;
            --gray-100:   #f1f5f9;
            --gray-200:   #e2e8f0;
            --gray-300:   #cbd5e1;
            --gray-500:   #64748b;
            --gray-700:   #334155;
            --gray-900:   #0f172a;
            --white:      #ffffff;
            --shadow-sm:  0 1px 3px rgba(0,0,0,.08);
            --shadow-md:  0 4px 16px rgba(0,0,0,.10);
            --radius:     10px;
        }

        body {
            font-family: 'Segoe UI', system-ui, sans-serif;
            background: var(--gray-50);
            color: var(--gray-900);
            min-height: 100vh;
            display: flex;
        }

        /* ── Sidebar ── */
        .sidebar {
            width: 250px;
            background: #1e2a3e;
            color: var(--white);
            flex-shrink: 0;
            display: flex;
            flex-direction: column;
            position: fixed;
            height: 100vh;
            overflow-y: auto;
            z-index: 50;
        }
        .sidebar-header {
            padding: 30px 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-bottom: 1px solid rgba(255,255,255,0.05);
            margin-bottom: 10px;
        }
        .sidebar-header .brand-subtitle {
            font-size: 0.95rem;
            color: rgba(255,255,255,0.7);
        }
        .sidebar-menu {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        .sidebar-menu li a {
            display: flex;
            align-items: center;
            gap: 16px;
            padding: 14px 24px;
            color: rgba(255,255,255,0.85);
            text-decoration: none;
            font-size: 0.95rem;
            font-weight: 500;
            transition: all 0.2s;
        }
        .sidebar-menu li a:hover {
            background: rgba(255,255,255,0.05);
            color: var(--white);
        }
        .sidebar-menu li.active a {
            background: rgba(255,255,255,0.1);
            color: var(--white);
        }
        .sidebar-menu li a i {
            width: 20px;
            text-align: center;
            font-size: 1.1rem;
        }

        /* ── Main wrapper ── */
        .main-wrapper {
            flex: 1;
            margin-left: 250px;
            display: flex;
            flex-direction: column;
            min-width: 0; /* allows child content to truncate properly if needed */
        }

        /* ── Page header ── */
        .page-header {
            background: var(--white);
            border-bottom: 1px solid var(--gray-200);
            padding: 18px 32px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: sticky;
            top: 0;
            z-index: 10;
        }
        .page-header h1 {
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--gray-900);
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .page-header h1 i { color: var(--teal); }
        .clock {
            background: var(--teal);
            color: var(--white);
            padding: 6px 14px;
            border-radius: 20px;
            font-size: .82rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        /* ── Main content ── */
        .content { padding: 28px 32px; max-width: 1400px; }

        /* ── Toolbar ── */
        .toolbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 22px;
            flex-wrap: wrap;
            gap: 12px;
        }
        .toolbar-left h2 {
            font-size: 1.4rem;
            font-weight: 700;
        }
        .toolbar-left .subtitle {
            font-size: .85rem;
            color: var(--gray-500);
            margin-top: 2px;
        }
        .toolbar-right {
            display: flex;
            gap: 10px;
            align-items: center;
            flex-wrap: wrap;
        }

        /* ── Buttons ── */
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            padding: 9px 18px;
            border-radius: 8px;
            font-size: .875rem;
            font-weight: 600;
            cursor: pointer;
            border: none;
            text-decoration: none;
            transition: all .18s;
        }
        .btn-primary {
            background: var(--teal);
            color: var(--white);
        }
        .btn-primary:hover { background: var(--teal-dark); transform: translateY(-1px); box-shadow: 0 4px 12px rgba(13,148,136,.3); }
        .btn-outline {
            background: var(--white);
            color: var(--gray-700);
            border: 1.5px solid var(--gray-300);
        }
        .btn-outline:hover { border-color: var(--teal); color: var(--teal); }

        /* ── Search bar ── */
        .search-box {
            position: relative;
        }
        .search-box input {
            padding: 9px 14px 9px 38px;
            border: 1.5px solid var(--gray-200);
            border-radius: 8px;
            font-size: .875rem;
            width: 220px;
            background: var(--white);
            outline: none;
            transition: border .2s;
        }
        .search-box input:focus { border-color: var(--teal); }
        .search-box i {
            position: absolute;
            left: 12px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--gray-500);
            font-size: .85rem;
        }

        /* ── Stats cards ── */
        .stats-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
            gap: 14px;
            margin-bottom: 24px;
        }
        .stat-card {
            background: var(--white);
            border-radius: var(--radius);
            padding: 16px 20px;
            border: 1px solid var(--gray-200);
            box-shadow: var(--shadow-sm);
        }
        .stat-card .label { font-size: .78rem; color: var(--gray-500); margin-bottom: 6px; font-weight: 600; text-transform: uppercase; letter-spacing: .04em; }
        .stat-card .value { font-size: 1.6rem; font-weight: 800; }
        .stat-card.total  .value { color: var(--teal); }
        .stat-card.planif .value { color: var(--blue); }
        .stat-card.cours  .value { color: var(--green); }
        .stat-card.annule .value { color: var(--red); }

        /* ── Table card ── */
        .table-card {
            background: var(--white);
            border-radius: var(--radius);
            border: 1px solid var(--gray-200);
            box-shadow: var(--shadow-sm);
            overflow: hidden;
        }

        table { width: 100%; border-collapse: collapse; }
        thead tr { background: var(--gray-50); border-bottom: 2px solid var(--gray-200); }
        thead th {
            padding: 12px 16px;
            text-align: left;
            font-size: .75rem;
            font-weight: 700;
            color: var(--gray-500);
            text-transform: uppercase;
            letter-spacing: .06em;
            white-space: nowrap;
        }
        tbody tr {
            border-bottom: 1px solid var(--gray-100);
            transition: background .15s;
        }
        tbody tr:last-child { border-bottom: none; }
        tbody tr:hover { background: var(--gray-50); }
        td { padding: 14px 16px; font-size: .875rem; vertical-align: middle; }

        .col-num {
            color: var(--gray-500);
            font-size: .8rem;
            font-weight: 600;
            width: 36px;
            text-align: center;
        }
        .event-title {
            font-weight: 700;
            color: var(--gray-900);
            margin-bottom: 2px;
        }

        /* ── Badge spécialité ── */
        .badge {
            display: inline-block;
            padding: 3px 10px;
            border-radius: 20px;
            font-size: .75rem;
            font-weight: 600;
        }
        .badge-spec { background: var(--gray-100); color: var(--gray-700); }

        /* ── Statuts ── */
        .badge-status {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: .78rem;
            font-weight: 700;
            white-space: nowrap;
        }
        .status-planifie   { background: var(--blue-light);   color: #1d4ed8; }
        .status-encours    { background: var(--green-light);  color: #15803d; }
        .status-termine    { background: var(--gray-100);     color: var(--gray-500); }
        .status-annule     { background: var(--red-light);    color: #b91c1c; }
        .status-avenir     { background: var(--orange-light); color: #c2410c; }

        /* ── Dates ── */
        .date-block { font-size: .82rem; color: var(--gray-700); line-height: 1.6; }
        .date-block .arrow { color: var(--gray-400); }

        /* ── Lieu ── */
        .lieu-text { font-size: .875rem; color: var(--gray-700); }

        /* ── Capacité ── */
        .capacite { font-weight: 700; color: var(--gray-800); }

        /* ── Sponsor ── */
        .sponsor-name { font-size: .82rem; color: var(--gray-600); }
        .no-sponsor   { color: var(--gray-400); }

        /* ── Actions ── */
        .actions { display: flex; gap: 6px; flex-wrap: wrap; }
        .btn-icon {
            width: 32px; height: 32px;
            border-radius: 7px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border: 1.5px solid;
            cursor: pointer;
            text-decoration: none;
            font-size: .8rem;
            transition: all .15s;
        }
        .btn-icon:hover { transform: translateY(-1px); box-shadow: 0 3px 8px rgba(0,0,0,.12); }
        .btn-edit   { color: var(--teal); border-color: var(--teal);  background: var(--teal-light); }
        .btn-delete { color: var(--red);  border-color: var(--red);   background: var(--red-light); }
        .btn-stats  { color: var(--blue); border-color: var(--blue);  background: var(--blue-light); }
        .btn-dl     { color: var(--gray-500); border-color: var(--gray-300); background: var(--gray-100); }

        /* ── Empty state ── */
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: var(--gray-500);
        }
        .empty-state i { font-size: 3rem; margin-bottom: 16px; color: var(--gray-300); }
        .empty-state p { font-size: 1rem; margin-bottom: 20px; }

        /* ── Alert flash ── */
        .alert {
            padding: 12px 18px;
            border-radius: 8px;
            margin-bottom: 18px;
            font-size: .875rem;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .alert-success { background: var(--green-light); color: #15803d; border: 1px solid #86efac; }
        .alert-danger  { background: var(--red-light);   color: #b91c1c; border: 1px solid #fca5a5; }

        /* ── Responsive ── */
        @media (max-width: 900px) {
            .sidebar { width: 70px; }
            .sidebar-header .brand-text, .sidebar-section, .sidebar-menu li a span { display: none; }
            .sidebar-menu li.active a { border-right: none; margin-right: 0; border-radius: 0; }
            .main-wrapper { margin-left: 70px; }
            .content { padding: 16px; }
            .page-header { padding: 14px 16px; }
            table { font-size: .8rem; }
            td, th { padding: 10px 10px; }
        }
    </style>
</head>
<body>

<!-- ── Sidebar ── -->
<aside class="sidebar">
    <div class="sidebar-header">
        <div class="brand-subtitle">Back Office</div>
    </div>

    <ul class="sidebar-menu">
        <li>
            <a href="index.php?page=dashboard">
                <i class="fas fa-tachometer-alt"></i> <span>Tableau de bord</span>
            </a>
        </li>
        <li>
            <a href="index.php?page=users">
                <i class="fas fa-users"></i> <span>Utilisateurs</span>
            </a>
        </li>
        <li>
            <a href="index.php?page=medecins_admin">
                <i class="fas fa-user-md"></i> <span>Médecins</span>
            </a>
        </li>
        <li>
            <a href="index.php?page=rendez_vous_admin">
                <i class="fas fa-calendar-check"></i> <span>Rendez vous</span>
            </a>
        </li>
        <li>
            <a href="index.php?page=ordonnances">
                <i class="fas fa-prescription-bottle"></i> <span>Ordonnances</span>
            </a>
        </li>
        <li>
            <a href="index.php?page=produits_admin">
                <i class="fas fa-box"></i> <span>Produits</span>
            </a>
        </li>
        <li>
            <a href="index.php?page=articles_admin">
                <i class="fas fa-blog"></i> <span>Blog</span>
            </a>
        </li>
        <li class="active">
            <a href="index.php?page=evenements_admin">
                <i class="fas fa-calendar-day"></i> <span>Événements</span>
            </a>
        </li>
        <li>
            <a href="index.php?page=participations">
                <i class="fas fa-handshake"></i> <span>Participations</span>
            </a>
        </li>
        <li>
            <a href="index.php?page=stats">
                <i class="fas fa-chart-line"></i> <span>Statistiques</span>
            </a>
        </li>
        <li>
            <a href="index.php?page=logs">
                <i class="fas fa-history"></i> <span>Historique</span>
            </a>
        </li>
        <li>
            <a href="index.php?page=settings">
                <i class="fas fa-cog"></i> <span>Paramètres</span>
            </a>
        </li>
        <li>
            <a href="index.php?page=logout">
                <i class="fas fa-sign-out-alt"></i> <span>Déconnexion</span>
            </a>
        </li>
    </ul>
</aside>

<!-- ── Main Content Wrapper ── -->
<div class="main-wrapper">

<!-- ── Page header ── -->
<div class="page-header">
    <h1><i class="fas fa-calendar-alt"></i> Gestion des Événements</h1>
    <div class="clock" id="clock">
        <i class="fas fa-clock"></i>
        <span id="clockTime"><?= date('d/m/Y H:i') ?></span>
    </div>
</div>

<div class="content">

    <?php if (isset($_SESSION['flash'])): ?>
        <?php $f = $_SESSION['flash']; unset($_SESSION['flash']); ?>
        <div class="alert alert-<?= $f['type'] === 'success' ? 'success' : 'danger' ?>">
            <i class="fas fa-<?= $f['type'] === 'success' ? 'check-circle' : 'exclamation-circle' ?>"></i>
            <?= htmlspecialchars($f['message']) ?>
        </div>
    <?php endif; ?>

    <?php
    // Calcul des stats
    $total    = count($events ?? []);
    $planifie = 0; $encours = 0; $annule = 0; $termine = 0;
    foreach (($events ?? []) as $e) {
        $s = strtolower($e['statut'] ?? $e['status'] ?? '');
        if (str_contains($s, 'planif') || str_contains($s, 'venir')) $planifie++;
        elseif (str_contains($s, 'cours'))  $encours++;
        elseif (str_contains($s, 'annul'))  $annule++;
        elseif (str_contains($s, 'termin')) $termine++;
    }
    ?>

    <!-- ── Stats ── -->
    <div class="stats-row">
        <div class="stat-card total">
            <div class="label">Total</div>
            <div class="value"><?= $total ?></div>
        </div>
        <div class="stat-card planif">
            <div class="label">Planifiés</div>
            <div class="value"><?= $planifie ?></div>
        </div>
        <div class="stat-card cours">
            <div class="label">En cours</div>
            <div class="value"><?= $encours ?></div>
        </div>
        <div class="stat-card annule">
            <div class="label">Annulés</div>
            <div class="value"><?= $annule ?></div>
        </div>
    </div>

    <!-- ── Toolbar ── -->
    <div class="toolbar">
        <div class="toolbar-left">
            <h2>Liste des Événements</h2>
            <div class="subtitle"><?= $total ?> événement<?= $total > 1 ? 's' : '' ?></div>
        </div>
        <div class="toolbar-right">
            <div class="search-box">
                <i class="fas fa-search"></i>
                <input type="text" id="searchInput" placeholder="Recherche rapide…">
            </div>
            <a href="index.php?page=evenements_admin&action=advanced" class="btn btn-outline">
                <i class="fas fa-chart-bar"></i> Vue avancée
            </a>
            <a href="index.php?page=evenements_admin&action=create" class="btn btn-primary">
                <i class="fas fa-plus"></i> Nouvel événement
            </a>
        </div>
    </div>

    <!-- ── Table ── -->
    <div class="table-card">
        <?php if (empty($events)): ?>
            <div class="empty-state">
                <i class="fas fa-calendar-times"></i>
                <p>Aucun événement trouvé.</p>
                <a href="index.php?page=evenements_admin&action=create" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Créer un événement
                </a>
            </div>
        <?php else: ?>
        <table id="eventsTable">
            <thead>
                <tr>
                    <th>#</th>
                    <th>TITRE</th>
                    <th>SPÉCIALITÉ</th>
                    <th>LIEU</th>
                    <th>DATES</th>
                    <th>CAPACITÉ</th>
                    <th>STATUT</th>
                    <th>SPONSOR</th>
                    <th>ACTIONS</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($events as $i => $event): ?>
                <?php
                    $statut = strtolower($event['statut'] ?? $event['status'] ?? '');
                    $statusClass = 'status-avenir';
                    $statusLabel = ucfirst($event['statut'] ?? $event['status'] ?? 'N/A');
                    if (str_contains($statut, 'planif'))      { $statusClass = 'status-planifie'; $statusLabel = 'Planifié'; }
                    elseif (str_contains($statut, 'cours'))   { $statusClass = 'status-encours';  $statusLabel = 'En cours'; }
                    elseif (str_contains($statut, 'termin'))  { $statusClass = 'status-termine';  $statusLabel = 'Terminé'; }
                    elseif (str_contains($statut, 'annul'))   { $statusClass = 'status-annule';   $statusLabel = 'Annulé'; }
                    elseif (str_contains($statut, 'venir'))   { $statusClass = 'status-avenir';   $statusLabel = 'À venir'; }

                    $spec    = $event['specialite'] ?? $event['type'] ?? $event['categorie'] ?? '—';
                    $lieu    = $event['lieu'] ?? '—';
                    $adresse = $event['adresse'] ?? '';
                    $lieu_full = $lieu . ($adresse && $adresse !== $lieu ? ', ' . $adresse : '');

                    $dateDebut = !empty($event['date_debut']) ? date('d/m/Y', strtotime($event['date_debut'])) : '—';
                    $dateFin   = !empty($event['date_fin'])   ? date('d/m/Y', strtotime($event['date_fin']))   : '—';
                    $capacite  = $event['capacite_max'] ?? $event['nombre_places_max'] ?? '—';
                    $sponsor   = $event['sponsor_nom'] ?? null;
                    $id        = $event['id'];
                ?>
                <tr>
                    <td class="col-num"><?= $id ?></td>
                    <td>
                        <div class="event-title"><?= htmlspecialchars($event['titre'] ?? '') ?></div>
                    </td>
                    <td>
                        <?php if ($spec && $spec !== '—'): ?>
                            <span class="badge badge-spec"><?= htmlspecialchars($spec) ?></span>
                        <?php else: ?>
                            <span class="no-sponsor">—</span>
                        <?php endif; ?>
                    </td>
                    <td class="lieu-text"><?= htmlspecialchars($lieu_full) ?></td>
                    <td>
                        <div class="date-block">
                            <?= $dateDebut ?><br>
                            <span class="arrow">→</span><br>
                            <?= $dateFin ?>
                        </div>
                    </td>
                    <td class="capacite"><?= $capacite ?></td>
                    <td>
                        <span class="badge-status <?= $statusClass ?>"><?= $statusLabel ?></span>
                    </td>
                    <td class="sponsor-name">
                        <?= $sponsor ? htmlspecialchars($sponsor) : '<span class="no-sponsor">—</span>' ?>
                    </td>
                    <td>
                        <div class="actions">
                            <a href="index.php?page=evenements_admin&action=edit&id=<?= $id ?>"
                               class="btn-icon btn-edit" title="Modifier">
                                <i class="fas fa-pen"></i>
                            </a>
                            <a href="index.php?page=evenements_admin&action=delete&id=<?= $id ?>"
                               class="btn-icon btn-delete" title="Supprimer"
                               onclick="confirmDelete(event, this.href)">
                                <i class="fas fa-trash"></i>
                            </a>
                            <a href="index.php?page=evenements_admin&action=show&id=<?= $id ?>"
                               class="btn-icon btn-stats" title="Statistiques">
                                <i class="fas fa-chart-bar"></i>
                            </a>
                            <a href="index.php?page=evenements_admin&action=show&id=<?= $id ?>"
                               class="btn-icon btn-dl" title="Télécharger">
                                <i class="fas fa-download"></i>
                            </a>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>

</div><!-- /content -->
</div><!-- /main-wrapper -->

<script>
    // Horloge en temps réel
    function updateClock() {
        const now = new Date();
        const pad = n => String(n).padStart(2, '0');
        document.getElementById('clockTime').textContent =
            `${pad(now.getDate())}/${pad(now.getMonth()+1)}/${now.getFullYear()} ${pad(now.getHours())}:${pad(now.getMinutes())}`;
    }
    setInterval(updateClock, 1000);

    // Recherche en temps réel
    document.getElementById('searchInput').addEventListener('input', function () {
        const q = this.value.toLowerCase();
        document.querySelectorAll('#eventsTable tbody tr').forEach(row => {
            row.style.display = row.textContent.toLowerCase().includes(q) ? '' : 'none';
        });
    });

    // Custom SweetAlert Delete Confirmation
    function confirmDelete(event, url) {
        event.preventDefault();
        Swal.fire({
            title: 'Êtes-vous sûr ?',
            text: "Cette action est irréversible et supprimera cet événement ! \nSi cet evenmement a des relation il ne sera pas suprime !",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: '<i class="fas fa-trash"></i> Oui, supprimer !',
            cancelButtonText: '<i class="fas fa-ban"></i> Annuler'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = url;
            }
        });
    }
</script>

</body>
</html>