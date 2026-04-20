<?php
// views/backoffice/pharmacie/_layout_top.php
// Inclure en haut de chaque vue backoffice pharmacie
// Variables attendues : $pageTitle (string), $activePage (string)
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: index.php?page=login');
    exit;
}
$activePage = $activePage ?? '';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle ?? 'Parapharmacie') ?> — Valorys Back Office</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { background: #f4f6f9; font-family: 'Segoe UI', sans-serif; }
        .sidebar { position: fixed; top: 0; left: 0; width: 280px; height: 100%; background: #1e2a3e; color: white; z-index: 100; }
        .sidebar-header { padding: 20px; text-align: center; border-bottom: 1px solid rgba(255,255,255,.1); }
        .sidebar-header img { width: 130px; filter: brightness(0) invert(1); }
        .sidebar-header small { color: rgba(255,255,255,.6); font-size: 12px; }
        .sidebar-menu { padding: 20px 0; }
        .sidebar-menu a { display: block; padding: 12px 25px; color: rgba(255,255,255,.7); text-decoration: none; font-weight: 500; transition: all .3s; }
        .sidebar-menu a:hover, .sidebar-menu a.active { background: rgba(255,255,255,.1); color: #fff; border-left: 4px solid #4CAF50; }
        .sidebar-menu i { width: 22px; margin-right: 10px; }
        .sidebar-section { padding: 8px 25px 4px; font-size: 10px; text-transform: uppercase; letter-spacing: 1px; color: rgba(255,255,255,.35); margin-top: 10px; }
        .main-content { margin-left: 280px; padding: 24px; }
        .navbar-top { background: #fff; border-radius: 12px; padding: 14px 24px; margin-bottom: 24px; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 2px 10px rgba(0,0,0,.05); }
        .stat-card { background: #fff; border-radius: 14px; padding: 20px; box-shadow: 0 2px 10px rgba(0,0,0,.05); border-left: 4px solid; transition: transform .2s; }
        .stat-card:hover { transform: translateY(-3px); }
        .stat-card h3 { font-size: 30px; font-weight: 700; margin: 8px 0 4px; }
        .stat-card p { color: #888; margin: 0; font-size: 14px; }
        .content-card { background: #fff; border-radius: 14px; padding: 24px; box-shadow: 0 2px 10px rgba(0,0,0,.05); }
        .flash-box { border-radius: 10px; padding: 12px 16px; margin-bottom: 18px; font-size: 14px; }
        .flash-success { background: #e8f5e9; color: #2e7d32; border-left: 4px solid #4CAF50; }
        .flash-error   { background: #fdecea; color: #c62828; border-left: 4px solid #e53935; }
        /* Badges statut */
        .badge-en_attente    { background:#fff3cd; color:#856404; }
        .badge-confirmee     { background:#cfe2ff; color:#084298; }
        .badge-en_preparation{ background:#e2d9f3; color:#4a1b8a; }
        .badge-expediee      { background:#d1ecf1; color:#0c5460; }
        .badge-livree        { background:#d4edda; color:#155724; }
        .badge-annulee       { background:#f8d7da; color:#721c24; }
        .badge-actif         { background:#d4edda; color:#155724; }
        .badge-inactif       { background:#f8d7da; color:#721c24; }
        .badge-alerte        { background:#fff3cd; color:#856404; }
        .form-label { font-weight: 600; font-size: 14px; }
        .invalid-feedback { display: block; font-size: 12px; color: #dc3545; margin-top: 4px; }
        .is-invalid { border-color: #dc3545 !important; }
        .is-valid   { border-color: #198754 !important; }
    </style>
</head>
<body>
<div class="sidebar">
    <div class="sidebar-header">
        <img src="assets/images/logo_doctime.png" alt="Valorys" onerror="this.style.display='none'">
        <br><small>Back Office</small>
    </div>
    <div class="sidebar-menu">
        <a href="index.php?page=dashboard"><i class="fas fa-tachometer-alt"></i> Tableau de bord</a>
        <a href="index.php?page=users"><i class="fas fa-users"></i> Utilisateurs</a>
        <a href="index.php?page=medecins_admin"><i class="fas fa-user-md"></i> Médecins</a>
        <a href="index.php?page=admin_rendezvous"><i class="fas fa-calendar-check"></i> Rendez-vous</a>
        <a href="index.php?page=admin_disponibilite"><i class="fas fa-clock"></i> Disponibilités</a>
        <a href="index.php?page=admin_ordonnance"><i class="fas fa-prescription-bottle"></i> Ordonnances</a>
        <a href="index.php?page=admin_events"><i class="fas fa-calendar-day"></i> Événements</a>
        <a href="index.php?page=blog"><i class="fas fa-blog"></i> Blog</a>

        <div class="sidebar-section">Parapharmacie</div>
        <a href="index.php?page=produits_admin" class="<?= $activePage === 'produits' ? 'active' : '' ?>">
            <i class="fas fa-pills"></i> Produits
        </a>
        <a href="index.php?page=categories_admin" class="<?= $activePage === 'categories' ? 'active' : '' ?>">
            <i class="fas fa-tags"></i> Catégories
        </a>
        <a href="index.php?page=commandes_admin" class="<?= $activePage === 'commandes' ? 'active' : '' ?>">
            <i class="fas fa-shopping-cart"></i> Commandes
        </a>

        <div class="sidebar-section">Système</div>
        <a href="index.php?page=stats"><i class="fas fa-chart-line"></i> Statistiques</a>
        <a href="index.php?page=logout"><i class="fas fa-sign-out-alt"></i> Déconnexion</a>
    </div>
</div>

<div class="main-content">
<div class="navbar-top">
    <h5 style="margin:0;color:#1e2a3e;font-weight:700;">
        <i class="fas fa-clinic-medical me-2" style="color:#4CAF50"></i>
        <?= htmlspecialchars($pageTitle ?? 'Parapharmacie') ?>
    </h5>
    <div style="display:flex;align-items:center;gap:12px;">
        <span style="font-size:14px;color:#666"><?= htmlspecialchars($_SESSION['user_prenom'] ?? '') ?> <?= htmlspecialchars($_SESSION['user_nom'] ?? '') ?></span>
        <div style="width:38px;height:38px;background:#4CAF50;border-radius:50%;display:flex;align-items:center;justify-content:center;color:#fff;font-weight:700;">
            <?= strtoupper(substr($_SESSION['user_prenom'] ?? 'A', 0, 1)) ?>
        </div>
    </div>
</div>
