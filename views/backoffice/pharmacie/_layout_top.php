<?php
// views/backoffice/pharmacie/_layout_top.php
// Variables attendues : $pageTitle (string), $activePage (string) — optionnel pour surcharges locales
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
    <?php require_once __DIR__ . '/../../partials/backoffice_shell_styles.php'; ?>
    <style>
        body.bo-shell-body { background: #f4f6f9; font-family: 'Segoe UI', sans-serif; }
        .navbar-top { background: #fff; border-radius: 12px; padding: 14px 24px; margin-bottom: 24px; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 2px 10px rgba(0,0,0,.05); }
        .stat-card { background: #fff; border-radius: 14px; padding: 20px; box-shadow: 0 2px 10px rgba(0,0,0,.05); border-left: 4px solid; transition: transform .2s; }
        .stat-card:hover { transform: translateY(-3px); }
        .stat-card h3 { font-size: 30px; font-weight: 700; margin: 8px 0 4px; }
        .stat-card p { color: #888; margin: 0; font-size: 14px; }
        .content-card { background: #fff; border-radius: 14px; padding: 24px; box-shadow: 0 2px 10px rgba(0,0,0,.05); }
        .flash-box { border-radius: 10px; padding: 12px 16px; margin-bottom: 18px; font-size: 14px; }
        .flash-success { background: #e8f5e9; color: #2e7d32; border-left: 4px solid #4CAF50; }
        .flash-error   { background: #fdecea; color: #c62828; border-left: 4px solid #e53935; }
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
<body class="bo-shell-body">
<?php require_once __DIR__ . '/../sidebar.php'; ?>

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
