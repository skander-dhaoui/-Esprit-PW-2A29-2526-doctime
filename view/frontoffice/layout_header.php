<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DocTime – Événements Médicaux en Tunisie</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <style>
        :root { --green: #1a7fa8; --green-end: #1db88e; --green-light: #e0f4f8; }
        body { font-family: 'Segoe UI', sans-serif; background: #f0f6fa; }
        .navbar { background: linear-gradient(90deg,#1a7fa8,#1db88e) !important; box-shadow: 0 2px 12px rgba(26,127,168,.25); }
        .navbar-brand { font-weight: 800; font-size: 1.2rem; }
        .navbar .nav-link { color: rgba(255,255,255,.88) !important; font-size: .9rem; }
        .navbar .nav-link:hover, .navbar .nav-link.active { color: #fff !important; }
        footer { background: linear-gradient(135deg,#0d4f6b,#0e7a5c); color: rgba(255,255,255,.75); }
        .card { border: none; box-shadow: 0 2px 12px rgba(0,0,0,.08); border-radius: 14px; }
        .badge-specialite { background: var(--green-light); color: var(--green); font-weight: 600; font-size: .75rem; }
        .btn-green { background: linear-gradient(90deg,var(--green),var(--green-end)); color: #fff; border: none; border-radius: 8px; }
        .btn-green:hover { opacity: .88; color: #fff; }
        .invalid-feedback { font-size: .8rem; }
        .is-invalid { border-color: #dc3545 !important; }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark">
    <div class="container">
        <a class="navbar-brand" href="index.php">
            <i class="bi bi-activity me-2"></i>DocTime
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navMenu">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navMenu">
            <ul class="navbar-nav ms-auto gap-1">
                <li class="nav-item">
                    <a class="nav-link <?= ($_GET['controller'] ?? 'home') === 'home' ? 'active fw-semibold' : '' ?>"
                       href="index.php">Accueil</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= ($_GET['controller'] ?? '') === 'evenement' ? 'active fw-semibold' : '' ?>"
                       href="index.php?controller=evenement&action=list">Événements</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= ($_GET['controller'] ?? '') === 'sponsor' ? 'active fw-semibold' : '' ?>"
                       href="index.php?controller=sponsor&action=list">Sponsors</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= ($_GET['controller'] ?? '') === 'mesinscriptions' ? 'active fw-semibold' : '' ?>"
                       href="index.php?controller=mesinscriptions&action=search">
                        <i class="bi bi-calendar-check me-1"></i>Mes Inscriptions
                    </a>
                </li>
                <li class="nav-item ms-2">
                    <a class="nav-link btn btn-outline-light btn-sm px-3"
                       href="index.php?controller=evenement&action=index">
                        <i class="bi bi-gear me-1"></i>Admin
                    </a>
                </li>
            </ul>
        </div>
    </div>
</nav>
