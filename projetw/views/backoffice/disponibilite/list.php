<?php
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: index.php?page=login');
    exit;
}

// Récupérer les variables passées par le contrôleur
$disponibilites = $disponibilites ?? [];
$medecins = $medecins ?? [];
$stats = $stats ?? ['total' => 0, 'actives' => 0, 'inactives' => 0];

// Jours de la semaine en français
$jours = [
    'Lundi' => 'Lundi',
    'Mardi' => 'Mardi',
    'Mercredi' => 'Mercredi',
    'Jeudi' => 'Jeudi',
    'Vendredi' => 'Vendredi',
    'Samedi' => 'Samedi',
    'Dimanche' => 'Dimanche'
];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des disponibilités - Valorys Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { background: #f4f6f9; font-family: 'Segoe UI', sans-serif; }
        .sidebar { position: fixed; top: 0; left: 0; width: 280px; height: 100%; background: #1e2a3e; color: white; transition: all 0.3s; z-index: 100; }
        .sidebar-header { padding: 20px; text-align: center; border-bottom: 1px solid rgba(255,255,255,0.1); }
        .sidebar-header .logo-img { width: 130px; height: auto; margin-bottom: 6px; filter: brightness(0) invert(1); }
        .sidebar-header small { color: rgba(255,255,255,0.6); font-size: 12px; }
        .sidebar-menu { padding: 20px 0; }
        .sidebar-menu a { display: block; padding: 12px 25px; color: rgba(255,255,255,0.7); text-decoration: none; transition: all 0.3s; font-weight: 500; }
        .sidebar-menu a:hover, .sidebar-menu a.active { background: rgba(255,255,255,0.1); color: white; border-left: 4px solid #4CAF50; }
        .sidebar-menu i { width: 25px; margin-right: 12px; }
        .main-content { margin-left: 280px; padding: 20px; }
        .navbar-top { background: white; border-radius: 12px; padding: 15px 25px; margin-bottom: 25px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px; }
        .admin-info { display: flex; align-items: center; gap: 15px; }
        .admin-avatar { width: 45px; height: 45px; background: #4CAF50; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-weight: bold; font-size: 18px; cursor: pointer; }
        .stat-card { background: white; border-radius: 15px; padding: 20px; margin-bottom: 20px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); border-left: 4px solid; }
        .stat-card h3 { font-size: 32px; margin: 10px 0 5px; font-weight: bold; }
        .stat-icon { font-size: 45px; opacity: 0.3; float: right; }
        .badge-actif { background: #d4edda; color: #155724; padding: 5px 12px; border-radius: 20px; font-size: 12px; }
        .badge-inactif { background: #f8d7da; color: #721c24; padding: 5px 12px; border-radius: 20px; font-size: 12px; }
        @media (max-width: 992px) {
            .sidebar { width: 80px; }
            .sidebar-menu a span { display: none; }
            .sidebar-menu a { text-align: center; padding: 15px; }
            .sidebar-menu i { margin-right: 0; font-size: 20px; }
            .main-content { margin-left: 80px; }
        }
    </style>
</head>
<body>

<!-- Sidebar -->
<div class="sidebar">
    <div class="sidebar-header">
        <img src="assets/images/logo_doctime.png" alt="Valorys Logo" class="logo-img" onerror="this.style.display='none'">
        <br><small>Back Office</small>
    </div>
    <div class="sidebar-menu">
        <a href="index.php?page=dashboard"><i class="fas fa-tachometer-alt"></i> <span>Dashboard</span></a>
        <a href="index.php?page=users"><i class="fas fa-users"></i> <span>Utilisateurs</span></a>
        <a href="index.php?page=medecins_admin"><i class="fas fa-user-md"></i> <span>Médecins</span></a>
        <a href="index.php?page=disponibilites_admin" class="active"><i class="fas fa-clock"></i> <span>Disponibilités</span></a>
        <a href="index.php?page=rendez_vous_admin"><i class="fas fa-calendar-check"></i> <span>Rendez-vous</span></a>
        <a href="index.php?page=ordonnances"><i class="fas fa-prescription-bottle"></i> <span>Ordonnances</span></a>
        <a href="index.php?page=produits_admin"><i class="fas fa-box"></i> <span>Produits</span></a>
        <a href="index.php?page=articles_admin"><i class="fas fa-blog"></i> <span>Blog</span></a>
        <a href="index.php?page=evenements_admin"><i class="fas fa-calendar-day"></i> <span>Événements</span></a>
        <a href="index.php?page=stats"><i class="fas fa-chart-line"></i> <span>Statistiques</span></a>
        <a href="index.php?page=logout"><i class="fas fa-sign-out-alt"></i> <span>Déconnexion</span></a>
    </div>
</div>

<!-- Main Content -->
<div class="main-content">
    <div class="navbar-top">
        <h4 class="mb-0"><i class="fas fa-clock me-2"></i>Gestion des disponibilités</h4>
        <div class="admin-info">
            <div class="admin-avatar"><?= strtoupper(substr($_SESSION['user_name'] ?? 'A', 0, 1)) ?></div>
            <span><?= htmlspecialchars($_SESSION['user_name'] ?? 'Admin') ?></span>
        </div>
    </div>

    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="fas fa-clock me-2"></i>Disponibilités des médecins</h2>
        <a href="index.php?page=disponibilites_admin&action=create" class="btn btn-success">
            <i class="fas fa-plus me-2"></i>Nouvelle disponibilité
        </a>
    </div>

    <!-- Statistiques -->
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="stat-card" style="border-left-color: #4CAF50;">
                <i class="fas fa-calendar-week stat-icon"></i>
                <p>Total disponibilités</p>
                <h3><?= $stats['total'] ?? 0 ?></h3>
            </div>
        </div>
        <div class="col-md-4">
            <div class="stat-card" style="border-left-color: #2A7FAA;">
                <i class="fas fa-check-circle stat-icon"></i>
                <p>Actives</p>
                <h3><?= $stats['actives'] ?? 0 ?></h3>
            </div>
        </div>
        <div class="col-md-4">
            <div class="stat-card" style="border-left-color: #6c757d;">
                <i class="fas fa-ban stat-icon"></i>
                <p>Inactives</p>
                <h3><?= $stats['inactives'] ?? 0 ?></h3>
            </div>
        </div>
    </div>

    <!-- Filtres -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <input type="hidden" name="page" value="disponibilites_admin">
                <div class="col-md-3">
                    <select name="medecin_id" class="form-select">
                        <option value="">Tous les médecins</option>
                        <?php foreach ($medecins as $medecin): ?>
                            <option value="<?= $medecin['id'] ?>" <?= ($_GET['medecin_id'] ?? '') == $medecin['id'] ? 'selected' : '' ?>>
                                Dr. <?= htmlspecialchars($medecin['prenom'] . ' ' . $medecin['nom']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <select name="jour" class="form-select">
                        <option value="">Tous les jours</option>
                        <option value="Lundi" <?= ($_GET['jour'] ?? '') === 'Lundi' ? 'selected' : '' ?>>Lundi</option>
                        <option value="Mardi" <?= ($_GET['jour'] ?? '') === 'Mardi' ? 'selected' : '' ?>>Mardi</option>
                        <option value="Mercredi" <?= ($_GET['jour'] ?? '') === 'Mercredi' ? 'selected' : '' ?>>Mercredi</option>
                        <option value="Jeudi" <?= ($_GET['jour'] ?? '') === 'Jeudi' ? 'selected' : '' ?>>Jeudi</option>
                        <option value="Vendredi" <?= ($_GET['jour'] ?? '') === 'Vendredi' ? 'selected' : '' ?>>Vendredi</option>
                        <option value="Samedi" <?= ($_GET['jour'] ?? '') === 'Samedi' ? 'selected' : '' ?>>Samedi</option>
                        <option value="Dimanche" <?= ($_GET['jour'] ?? '') === 'Dimanche' ? 'selected' : '' ?>>Dimanche</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <input type="text" name="search" class="form-control" placeholder="Rechercher..." value="<?= htmlspecialchars($_GET['search'] ?? '') ?>">
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100">Filtrer</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Tableau -->
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead class="table-primary">
                        <tr>
                            <th>Médecin</th>
                            <th>Spécialité</th>
                            <th>Jour</th>
                            <th>Heure début</th>
                            <th>Heure fin</th>
                            <th>Pause début</th>
                            <th>Pause fin</th>
                            <th>Statut</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($disponibilites)): ?>
                            <tr><td colspan="9" class="text-center text-muted py-4">Aucune disponibilité trouvée</td></tr>
                        <?php else: ?>
                            <?php foreach ($disponibilites as $dispo): ?>
                            <tr>
                                <td>Dr. <?= htmlspecialchars($dispo['medecin_nom'] ?? 'N/A') ?></td>
                                <td><?= htmlspecialchars($dispo['specialite'] ?? '-') ?></td>
                                <td><?= htmlspecialchars($dispo['jour_semaine'] ?? '-') ?></td>
                                <td><?= date('H:i', strtotime($dispo['heure_debut'])) ?></td>
                                <td><?= date('H:i', strtotime($dispo['heure_fin'])) ?></td>
                                <td><?= !empty($dispo['pause_debut']) ? date('H:i', strtotime($dispo['pause_debut'])) : '-' ?></td>
                                <td><?= !empty($dispo['pause_fin']) ? date('H:i', strtotime($dispo['pause_fin'])) : '-' ?></td>
                                <td>
                                    <?php if ($dispo['actif'] == 1): ?>
                                        <span class="badge badge-actif">Actif</span>
                                    <?php else: ?>
                                        <span class="badge badge-inactif">Inactif</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <a href="index.php?page=disponibilites_admin&action=edit&id=<?= $dispo['id'] ?>" class="btn btn-sm btn-warning" title="Modifier"><i class="fas fa-edit"></i></a>
                                    <a href="index.php?page=disponibilites_admin&action=delete&id=<?= $dispo['id'] ?>" class="btn btn-sm btn-danger" title="Supprimer" onclick="return confirm('Supprimer cette disponibilité ?')"><i class="fas fa-trash"></i></a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>