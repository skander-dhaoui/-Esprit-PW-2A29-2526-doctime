<?php declare(strict_types=1); ?>
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
    <link rel="stylesheet" href="assets/css/backoffice-create-forms.css">
    <?php require_once __DIR__ . '/../partials/backoffice_shell_styles.php'; ?>
    <style>
        :root {
            --grad-start: #1a7fa8;
            --grad-end:   #1db88e;
            --primary:    #1a7fa8;
            --primary-dark: #155f80;
        }
        body.bo-shell-body { background: #f0f4f8; font-family: 'Segoe UI', sans-serif; }

        /* ── Main (sidebar = partial commun backoffice_shell_styles) ── */
        .main-content { margin-left: 260px; padding: 2rem; min-height: 100vh; }
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

        /* Assistant vocal back-office (pile micro + clavier, bas gauche) */
        .bo-voice-stack {
            position: fixed; bottom: 1.25rem; left: 1.25rem; z-index: 1040;
            display: flex; flex-direction: column; gap: 8px; align-items: center;
        }
        .bo-voice-fab {
            width: 52px; height: 52px; border-radius: 50% !important;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.2rem; border: none; cursor: pointer;
            background: linear-gradient(135deg, #1a7fa8, #1db88e);
            color: #fff; box-shadow: 0 4px 16px rgba(26, 127, 168, 0.4);
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .bo-voice-fab--kbd {
            background: linear-gradient(135deg, #475569, #64748b);
            box-shadow: 0 4px 12px rgba(71, 85, 105, 0.35);
        }
        .bo-voice-fab--disabled { opacity: 0.45; cursor: not-allowed; }
        .bo-voice-fallback-panel {
            position: fixed; bottom: 9rem; left: 1rem; z-index: 1039;
            width: min(340px, calc(100vw - 2rem));
            background: #fff; border: 1px solid #e2e8f0; border-radius: 12px;
            padding: 12px 14px; box-shadow: 0 10px 30px rgba(0,0,0,.12);
        }
        .bo-voice-fab:hover:not(:disabled) { transform: scale(1.06); color: #fff; }
        .bo-voice-fab.is-listening {
            animation: bo-voice-pulse 1.15s ease-in-out infinite;
        }
        @keyframes bo-voice-pulse {
            0%, 100% { box-shadow: 0 0 0 0 rgba(29, 184, 142, 0.45); }
            50% { box-shadow: 0 0 0 10px rgba(29, 184, 142, 0); }
        }
        .bo-voice-status {
            position: fixed; bottom: 5rem; left: 1rem; max-width: min(320px, calc(100vw - 2rem));
            z-index: 1039; display: none;
            background: rgba(15, 23, 42, 0.92); color: #f1f5f9; font-size: 0.82rem;
            padding: 10px 14px; border-radius: 12px; line-height: 1.45;
            box-shadow: 0 8px 24px rgba(0,0,0,.18);
        }
        .bo-voice-toast {
            position: fixed; bottom: 5rem; left: 50%; transform: translateX(-50%);
            z-index: 1041; max-width: min(420px, 94vw);
            padding: 12px 18px; border-radius: 12px; font-size: 0.85rem; line-height: 1.45;
            box-shadow: 0 8px 28px rgba(0,0,0,.2);
            display: none;
        }
        .bo-voice-toast--info { background: #0f172a; color: #e2e8f0; }
        .bo-voice-toast--warn { background: #7c2d12; color: #fff7ed; }
    </style>
</head>
<?php $__boBodyExtra = (isset($boBodyClass) && $boBodyClass !== '') ? (' ' . htmlspecialchars((string)$boBodyClass, ENT_QUOTES, 'UTF-8')) : ''; ?>
<body class="bo-shell-body<?= $__boBodyExtra ?>">

<?php require_once __DIR__ . '/../partials/backoffice_sidebar.php'; ?>

<!-- ── Contenu principal ── -->
<div class="main-content">
    <div class="topbar">
        <h4><i class="bi bi-activity me-2" style="color:var(--grad-end)"></i><?= $pageTitle ?? 'Administration' ?></h4>
        <div class="topbar-actions d-flex align-items-center flex-wrap gap-1 justify-content-end">
            <?php require_once __DIR__ . '/../partials/backoffice_notifications_bell.php'; ?>
            <span class="badge-time"><i class="bi bi-clock me-1"></i><?= date('d/m/Y H:i') ?></span>
            <a href="index.php?page=logout" class="btn-logout-top">
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

