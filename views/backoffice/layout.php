<?php
// views/backoffice/layout.php
// Layout principal pour toutes les pages admin
?><!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle ?? 'Valorys Admin') ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { background: #f4f6f9; font-family: 'Segoe UI', sans-serif; }
        .admin-wrapper { display: flex; min-height: 100vh; }
        .sidebar { width: 260px; background: #1a2035; color: white; padding: 20px 0; position: fixed; height: 100vh; overflow-y: auto; }
        .sidebar-brand { padding: 20px; text-align: center; border-bottom: 1px solid rgba(255,255,255,0.1); margin-bottom: 20px; }
        .sidebar-brand h4 { margin: 0; font-size: 18px; }
        .sidebar-brand small { color: rgba(255,255,255,0.5); font-size: 11px; }
        .sidebar-nav { padding: 0 15px; }
        .sidebar-nav a { display: flex; align-items: center; gap: 12px; padding: 12px 15px; color: rgba(255,255,255,0.7); text-decoration: none; border-radius: 6px; margin-bottom: 5px; transition: all 0.2s; }
        .sidebar-nav a:hover { background: rgba(255,255,255,0.1); color: white; }
        .sidebar-nav a.active { background: #4CAF50; color: white; }
        .sidebar-nav i { width: 20px; text-align: center; }
        .main-content { margin-left: 260px; flex: 1; padding: 25px; }
        .page-header { background: white; border-radius: 8px; padding: 20px; margin-bottom: 25px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); display: flex; justify-content: space-between; align-items: center; }
        .page-header h1 { margin: 0; font-size: 24px; color: #1a2035; }
        .flash-messages { margin-bottom: 20px; }
        .alert { border-radius: 6px; padding: 12px 16px; }
    </style>
</head>
<body>
    <div class="admin-wrapper">
        <!-- Sidebar -->
        <div class="sidebar">
            <div class="sidebar-brand">
                <div style="font-size: 32px; margin-bottom: 10px;"><i class="fas fa-hospital-user"></i></div>
                <h4>Valorys</h4>
                <small>Admin Panel</small>
            </div>
            <nav class="sidebar-nav">
                <a href="index.php?page=dashboard" class="<?= ($currentPage ?? '') === 'dashboard' ? 'active' : '' ?>">
                    <i class="fas fa-tachometer-alt"></i> Dashboard
                </a>
                <a href="index.php?page=users" class="<?= ($currentPage ?? '') === 'users' ? 'active' : '' ?>">
                    <i class="fas fa-users"></i> Utilisateurs
                </a>
                <a href="index.php?page=medecins_admin" class="<?= ($currentPage ?? '') === 'medecins_admin' ? 'active' : '' ?>">
                    <i class="fas fa-user-md"></i> Médecins
                </a>
                <a href="index.php?page=patients" class="<?= ($currentPage ?? '') === 'patients' ? 'active' : '' ?>">
                    <i class="fas fa-user-injured"></i> Patients
                </a>
                <a href="index.php?page=articles_admin" class="<?= ($currentPage ?? '') === 'articles_admin' ? 'active' : '' ?>">
                    <i class="fas fa-newspaper"></i> Articles
                </a>
                <a href="index.php?page=logout">
                    <i class="fas fa-sign-out-alt"></i> Déconnexion
                </a>
            </nav>
        </div>

        <!-- Main Content -->
        <div class="main-content">
            <div class="page-header">
                <h1><?= htmlspecialchars($pageTitle ?? 'Valorys Admin') ?></h1>
                <div>
                    <span style="color: #666; margin-right: 15px;">
                        <i class="fas fa-user-circle"></i> <?= htmlspecialchars($_SESSION['user_name'] ?? 'Admin') ?>
                    </span>
                </div>
            </div>

            <?php if (isset($_SESSION['flash'])): ?>
                <div class="flash-messages">
                    <div class="alert alert-<?= $_SESSION['flash']['type'] === 'success' ? 'success' : 'danger' ?>">
                        <?= htmlspecialchars($_SESSION['flash']['message']) ?>
                    </div>
                </div>
                <?php unset($_SESSION['flash']); ?>
            <?php endif; ?>

            <?php include $contentFile; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
