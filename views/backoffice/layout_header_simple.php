<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DocTime – Administration</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <style>
        :root {
            --sidebar-width: 255px;
            --grad-start: #1a7fa8;
            --grad-end:   #1db88e;
            --primary:    #1a7fa8;
            --primary-dark: #155f80;
        }
        body { background: #f0f4f8; font-family: 'Segoe UI', sans-serif; }

        /* ── Sidebar ── */
        .sidebar {
            width: var(--sidebar-width);
            min-height: 100vh;
            background: linear-gradient(180deg, var(--grad-start) 0%, var(--grad-end) 100%);
            position: fixed; top: 0; left: 0; z-index: 100;
            box-shadow: 3px 0 12px rgba(0,0,0,.15);
        }
        .sidebar .brand {
            padding: 1.6rem 1.2rem 1.2rem;
            border-bottom: 1px solid rgba(255,255,255,.18);
        }
        .sidebar .brand .logo-icon {
            width: 42px; height: 42px; border-radius: 12px;
            background: rgba(255,255,255,.22);
            display: flex; align-items: center; justify-content: center;
            font-size: 1.4rem; color: #fff;
        }
        .sidebar .brand h5 { color: #fff; font-weight: 800; font-size: 1.05rem; margin: 0; letter-spacing: .02em; }
        .sidebar .brand small { color: rgba(255,255,255,.65); font-size: .72rem; }
        .sidebar .nav-link {
            color: rgba(255,255,255,.82); padding: .62rem 1.1rem;
            border-radius: 8px; margin: 2px 10px; font-size: .875rem;
            transition: background .2s, color .2s;
            text-decoration: none !important;
            display: block;
        }
        .sidebar .nav-link:hover, .sidebar .nav-link.active {
            background: rgba(255,255,255,.2); color: #fff;
        }
        .sidebar .nav-link i { width: 22px; }
        .sidebar .nav-section {
            color: rgba(255,255,255,.45); font-size: .68rem;
            text-transform: uppercase; letter-spacing: .08em;
            font-weight: 700; padding: 1.2rem 1.1rem .4rem;
            margin: 0;
        }

        /* ── Bouton Déconnexion ── */
        .nav-logout {
            background: linear-gradient(135deg, #dc3545 0%, #c82333 100%) !important;
            color: white !important;
            margin: 8px 10px !important;
            border-radius: 8px !important;
            font-weight: 600 !important;
            box-shadow: 0 4px 12px rgba(220, 53, 69, 0.3);
            transition: all 0.3s ease !important;
        }
        .nav-logout:hover {
            background: linear-gradient(135deg, #c82333 0%, #bd2130 100%) !important;
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(220, 53, 69, 0.4);
        }
        .nav-logout i { color: white !important; }

        .main-content {
            margin-left: var(--sidebar-width);
            min-height: 100vh;
            background: #f0f4f8;
        }
        .topbar {
            background: #fff; padding: 1rem 2rem;
            border-bottom: 1px solid #e5e7eb;
            display: flex; align-items: center; justify-content: space-between;
        }
        .badge-time {
            background: var(--grad-start); color: #fff;
            padding: .4rem .8rem; border-radius: 20px;
            font-size: .75rem; font-weight: 600;
        }
        .topbar-actions {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .btn-logout-top {
            background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
            color: white !important;
            padding: .4rem 1rem;
            border-radius: 20px;
            font-size: .75rem;
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            box-shadow: 0 2px 8px rgba(220, 53, 69, 0.3);
            transition: all 0.3s ease;
        }
        .btn-logout-top:hover {
            background: linear-gradient(135deg, #c82333 0%, #bd2130 100%);
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(220, 53, 69, 0.4);
            color: white !important;
        }
        .stat-card {
            border-radius: 14px; padding: 1.3rem 1.5rem;
            color: #fff; position: relative; overflow: hidden;
        }
        .stat-card .stat-icon {
            position: absolute; right: 1rem; top: 50%; transform: translateY(-50%);
            font-size: 3rem; opacity: .18;
        }
        .stat-card h3 { font-weight: 800; font-size: 1.9rem; margin: 0; }
        .stat-card p  { margin: 0; font-size: .85rem; opacity: .85; }
    </style>
</head>
<body>

<?php
$currentPage = $_GET['page'] ?? '';
?>

<!-- ── Sidebar ── -->
<nav class="sidebar">
    <div class="brand">
        <div class="d-flex align-items-center gap-2">
            <div class="logo-icon"><i class="bi bi-activity"></i></div>
            <div>
                <h5>DocTime</h5>
                <small>Administration</small>
            </div>
        </div>
    </div>
    <ul class="nav flex-column mt-2 px-0">
        <li class="nav-section">Navigation</li>
        <li class="nav-item">
            <a class="nav-link <?= ($currentPage === 'dashboard' || $currentPage === '') ? 'active' : '' ?>"
               href="/valorys_Copie/index.php?page=dashboard">
                <i class="bi bi-speedometer2"></i> Tableau de bord
            </a>
        </li>
        <li class="nav-section">Gestion</li>
        <li class="nav-item">
            <a class="nav-link <?= $currentPage === 'users' ? 'active' : '' ?>"
               href="/valorys_Copie/index.php?page=users">
                <i class="bi bi-people-fill"></i> Utilisateurs
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= $currentPage === 'medecins_admin' ? 'active' : '' ?>"
               href="/valorys_Copie/index.php?page=medecins_admin">
                <i class="bi bi-person-vcard"></i> Médecins
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= $currentPage === 'patients' ? 'active' : '' ?>"
               href="/valorys_Copie/index.php?page=patients">
                <i class="bi bi-person-heart"></i> Patients
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= $currentPage === 'evenements_admin' ? 'active' : '' ?>"
               href="/valorys_Copie/index.php?page=evenements_admin">
                <i class="bi bi-calendar-event"></i> Événements
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= $currentPage === 'sponsors_admin' ? 'active' : '' ?>"
               href="/valorys_Copie/index.php?page=sponsors_admin">
                <i class="bi bi-building"></i> Sponsors
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= $currentPage === 'participations' ? 'active' : '' ?>"
               href="/valorys_Copie/index.php?page=participations">
                <i class="bi bi-people"></i> Participations
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= $currentPage === 'carte' ? 'active' : '' ?>"
               href="/valorys_Copie/index.php?page=carte">
                <i class="bi bi-map"></i> Carte Tunisie
            </a>
        </li>
        <li class="nav-section">Analytique</li>
        <li class="nav-item">
            <a class="nav-link <?= $currentPage === 'stats' ? 'active' : '' ?>"
               href="/valorys_Copie/index.php?page=stats">
                <i class="bi bi-bar-chart-line"></i> Statistiques
            </a>
        </li>
        <li class="nav-section">Historique</li>
        <li class="nav-item">
            <a class="nav-link <?= $currentPage === 'login_history' ? 'active' : '' ?>"
               href="/valorys_Copie/index.php?page=login_history">
                <i class="bi bi-clock-history"></i> Historique connexions
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= $currentPage === 'logs' ? 'active' : '' ?>"
               href="/valorys_Copie/index.php?page=logs">
                <i class="bi bi-journal-text"></i> Logs
            </a>
        </li>
        <li class="nav-section">Site public</li>
        <li class="nav-item">
            <a class="nav-link" href="/valorys_Copie/index.php?page=accueil" target="_blank">
                <i class="bi bi-globe"></i> Voir le site
            </a>
        </li>
        <li class="nav-section">Session</li>
        <li class="nav-item">
            <a class="nav-link nav-logout" href="/valorys_Copie/index.php?page=logout">
                <i class="bi bi-box-arrow-right"></i> Déconnexion
            </a>
        </li>
    </ul>
</nav>

<!-- ── Contenu principal ── -->
<div class="main-content">
    <div class="topbar">
        <h4><i class="bi bi-activity me-2" style="color:var(--grad-end)"></i><?= $pageTitle ?? 'Administration' ?></h4>
        <div class="topbar-actions">
            <span class="badge-time"><i class="bi bi-clock me-1"></i><?= date('d/m/Y H:i') ?></span>
            <a href="/valorys_Copie/index.php?page=logout" class="btn-logout-top">
                <i class="bi bi-box-arrow-right"></i> Déconnexion
            </a>
        </div>
    </div>

    <!-- Messages flash -->
    <?php if (!empty($_GET['success'])): ?>
        <?php $msgs = ['create'=>'Enregistrement créé avec succès.','update'=>'Enregistrement mis à jour.','delete'=>'Enregistrement supprimé.']; ?>
        <div class="alert alert-success alert-dismissible fade show">
            <i class="bi bi-check-circle me-2"></i>
            <?= $msgs[$_GET['success']] ?? 'Opération réussie.' ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    <?php if (!empty($_GET['error'])): ?>
        <?php $errMsgs = ['has_evenements'=>'Impossible de supprimer ce sponsor : il est lié à des événements.']; ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <i class="bi bi-exclamation-triangle me-2"></i>
            <?= $errMsgs[$_GET['error']] ?? 'Une erreur est survenue.' ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
