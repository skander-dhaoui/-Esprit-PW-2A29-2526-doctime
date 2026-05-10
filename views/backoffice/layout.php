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
    <?php require_once __DIR__ . '/../partials/backoffice_shell_styles.php'; ?>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body.bo-shell-body { background: #f4f6f9; font-family: 'Segoe UI', sans-serif; }
        .admin-wrapper { display: flex; min-height: 100vh; }
        .main-content { margin-left: 260px; flex: 1; padding: 25px; }
        .page-header { background: white; border-radius: 8px; padding: 20px; margin-bottom: 25px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); display: flex; justify-content: space-between; align-items: center; }
        .page-header h1 { margin: 0; font-size: 24px; color: #1a2035; }
        .flash-messages { margin-bottom: 20px; }
        .alert { border-radius: 6px; padding: 12px 16px; }
    </style>
</head>
<body class="bo-shell-body">
    <div class="admin-wrapper">
        <?php require_once __DIR__ . '/sidebar.php'; ?>

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
