<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DocTime – Administration</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/backoffice-polish.css">
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
        }
        .sidebar .nav-link:hover, .sidebar .nav-link.active {
            background: rgba(255,255,255,.2); color: #fff;
        }
        .sidebar .nav-link i { width: 22px; }
        .sidebar .nav-section {
            color: rgba(255,255,255,.45); font-size: .68rem;
            text-transform: uppercase; letter-spacing: .1em;
            padding: .8rem 1.4rem .2rem; font-weight: 700;
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

        /* ── Main ── */
        .main-content { margin-left: var(--sidebar-width); padding: 2rem; min-height: 100vh; }
        .topbar {
            background: #fff; padding: .85rem 2rem;
            margin: -2rem -2rem 2rem;
            display: flex; align-items: center; justify-content: space-between;
            box-shadow: 0 2px 8px rgba(0,0,0,.06);
        }
        .topbar h4 { margin: 0; font-size: 1.05rem; font-weight: 700; color: #1e293b; }
        .topbar .badge-time {
            background: linear-gradient(90deg, var(--grad-start), var(--grad-end));
            color: #fff; border-radius: 20px; padding: .35rem .85rem; font-size: .78rem;
        }
        .topbar-actions {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .btn-logout-top {
            background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
            color: white !important;
            padding: .35rem 1rem;
            border-radius: 20px;
            font-size: .78rem;
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

        /* ── Cards ── */
        .card { border: none; box-shadow: 0 2px 10px rgba(0,0,0,.07); border-radius: 12px; }
        .card-header { background: #fff; border-bottom: 1px solid #eef1f5; border-radius: 12px 12px 0 0 !important; }
        .table thead th {
            background: #f5f8fb; font-size: .78rem; text-transform: uppercase;
            letter-spacing: .06em; color: #64748b; border-bottom: 2px solid #e4eaf0;
        }

        /* ── Modern Stat Cards (Pharmacie) ── */
        .stat-card { 
            background: #fff; 
            border-radius: 16px; 
            padding: 20px; 
            box-shadow: 0 2px 12px rgba(0,0,0,.06); 
            border: none; 
            transition: all .2s ease;
            position: relative;
            overflow: hidden;
        }
        .stat-card:hover { transform: translateY(-3px); box-shadow: 0 8px 25px rgba(0,0,0,.1); }
        .stat-card .stat-icon {
            position: absolute;
            top: 15px;
            right: 15px;
            width: 45px;
            height: 45px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.3rem;
            opacity: 0.9;
        }
        .stat-card .stat-value {
            font-size: 2rem;
            font-weight: 800;
            margin-top: 10px;
            margin-bottom: 5px;
        }
        .stat-card .stat-label {
            font-size: 0.9rem;
            color: #64748b;
            font-weight: 500;
        }
        
        /* Status badges like screenshots */
        .badge { padding: 6px 14px; border-radius: 20px; font-weight: 600; font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.3px; }
        .badge-en_attente, .badge-attente { background: #fff7ed; color: #c2410c; border: 1px solid #fed7aa; }
        .badge-confirmee, .badge-confirme { background: #f0fdf4; color: #166534; border: 1px solid #bbf7d0; }
        .badge-livree, .badge-termine { background: #f0fdf4; color: #15803d; border: 1px solid #bbf7d0; }
        .badge-annulee { background: #fef2f2; color: #dc2626; border: 1px solid #fecaca; }
        .badge-actif { background: #f0fdf4; color: #166534; border: 1px solid #bbf7d0; }
        .badge-inactif { background: #f1f5f9; color: #475569; border: 1px solid #cbd5e1; }
        .badge-alerte { background: #fffbeb; color: #92400e; border: 1px solid #fcd34d; }
        
        /* Table modern styling */
        .table-modern { border-collapse: separate; border-spacing: 0; }
        .table-modern th { background: #f8fafc; font-weight: 600; color: #475569; font-size: 0.8rem; text-transform: uppercase; letter-spacing: 0.5px; padding: 14px 16px; border-bottom: 2px solid #e2e8f0; }
        .table-modern td { padding: 16px; border-bottom: 1px solid #f1f5f9; vertical-align: middle; }
        .table-modern tbody tr:hover { background: #f8fafc; }
        
        /* Action buttons */
        .btn-action { width: 32px; height: 32px; border-radius: 8px; display: inline-flex; align-items: center; justify-content: center; border: none; transition: all .2s; margin: 0 2px; }
        .btn-action-edit { background: #dbeafe; color: #2563eb; }
        .btn-action-edit:hover { background: #2563eb; color: white; }
        .btn-action-delete { background: #fee2e2; color: #dc2626; }
        .btn-action-delete:hover { background: #dc2626; color: white; }
        .btn-action-view { background: #f0fdf4; color: #16a34a; }
        .btn-action-view:hover { background: #16a34a; color: white; }
        
        .content-card { background: #fff; border-radius: 15px; padding: 25px; box-shadow: 0 4px 15px rgba(0,0,0,.03); border: 1px solid rgba(0,0,0,.02); }
        .flash-box { border-radius: 12px; padding: 15px 20px; margin-bottom: 25px; font-weight: 500; border: none; }
        .flash-success { background: #dcfce7; color: #166534; }
        .flash-error { background: #fee2e2; color: #991b1b; }

        /* ── Buttons ── */
        .btn-primary {
            background: linear-gradient(90deg, var(--grad-start), var(--grad-end));
            border: none;
        }
        .btn-primary:hover { opacity: .88; }

        /* ── Forms ── */
        .form-label { font-weight: 500; font-size: .875rem; color: #374151; }
        .invalid-feedback { font-size: .8rem; }
        .is-invalid { border-color: #dc3545 !important; }
        .alert { border-radius: 10px; font-size: .875rem; }

        /* ── Stat cards ── */
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
$currentController = $_GET['controller'] ?? '';
$currentAction = $_GET['action'] ?? '';
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
            <a class="nav-link <?= ($currentPage === 'dashboard' || (!isset($_GET['page']) && ($currentController === '' || $currentController === 'home'))) ? 'active' : '' ?>"
               href="/valorys_Copie/index.php?page=dashboard">
                <i class="bi bi-speedometer2"></i> Tableau de bord
            </a>
        </li>
        <li class="nav-section">Gestion Globale</li>
        <li class="nav-item">
            <a class="nav-link <?= $currentPage === 'users' ? 'active' : '' ?>"
               href="/valorys_Copie/index.php?page=users">
                <i class="bi bi-people-fill"></i> Utilisateurs
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= $currentPage === 'evenements_admin' ? 'active' : '' ?>"
               href="/valorys_Copie/index.php?page=evenements_admin">
                <i class="bi bi-calendar-event"></i> Événements
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= $currentPage === 'produits_admin' ? 'active' : '' ?>"
               href="/valorys_Copie/index.php?page=produits_admin">
                <i class="bi bi-capsule-pill"></i> Pharmacie - Produits
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= $currentPage === 'categories_admin' ? 'active' : '' ?>"
               href="/valorys_Copie/index.php?page=categories_admin">
                <i class="bi bi-tags"></i> Pharmacie - Catégories
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= $currentPage === 'commandes_admin' ? 'active' : '' ?>"
               href="/valorys_Copie/index.php?page=commandes_admin">
                <i class="bi bi-cart-check"></i> Pharmacie - Commandes
            </a>
        </li>
        <li class="nav-section">Médical</li>
        <li class="nav-item">
            <a class="nav-link <?= $currentPage === 'medecins_admin' ? 'active' : '' ?>"
               href="/valorys_Copie/index.php?page=medecins_admin">
                <i class="bi bi-person-vcard"></i> M&eacute;decins
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= $currentPage === 'patients' ? 'active' : '' ?>"
               href="/valorys_Copie/index.php?page=patients">
                <i class="bi bi-person-heart"></i> Patients
            </a>
        </li>
        <li class="nav-section">Événements & Sponsors</li>
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
        <li class="nav-item">
            <a class="nav-link <?= $currentPage === 'carte' && ($_GET['action'] ?? '') === 'metiers' ? 'active' : '' ?>"
               href="/valorys_Copie/index.php?page=carte&action=metiers">
                <i class="bi bi-brain"></i> IA Métiers Créatifs
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

