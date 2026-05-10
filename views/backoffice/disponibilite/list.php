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
    <?php require_once __DIR__ . '/../../partials/backoffice_shell_styles.php'; ?>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body.bo-shell-body { background: #f4f6f9; font-family: 'Segoe UI', sans-serif; }
        .navbar-top { background: white; border-radius: 12px; padding: 15px 25px; margin-bottom: 25px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px; }
        .admin-info { display: flex; align-items: center; gap: 15px; }
        .admin-avatar { width: 45px; height: 45px; background: #4CAF50; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-weight: bold; font-size: 18px; cursor: pointer; }
        .stat-card { background: white; border-radius: 15px; padding: 20px; margin-bottom: 20px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); border-left: 4px solid; }
        .stat-card h3 { font-size: 32px; margin: 10px 0 5px; font-weight: bold; }
        .stat-icon { font-size: 45px; opacity: 0.3; float: right; }
        .badge-actif { background: #d4edda; color: #155724; padding: 5px 12px; border-radius: 20px; font-size: 12px; }
        .badge-inactif { background: #f8d7da; color: #721c24; padding: 5px 12px; border-radius: 20px; font-size: 12px; }
    </style>
</head>
<body class="bo-shell-body">
<?php require_once __DIR__ . '/../sidebar.php'; ?>

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
                                 </div>
                                <td>
                                    <a href="index.php?page=disponibilites_admin&action=edit&id=<?= $dispo['id'] ?>" class="btn btn-sm btn-warning" title="Modifier"><i class="fas fa-edit"></i></a>
                                    <a href="index.php?page=disponibilites_admin&action=delete&id=<?= $dispo['id'] ?>" class="btn btn-sm btn-danger" title="Supprimer" onclick="return confirm('Supprimer cette disponibilité ?')"><i class="fas fa-trash"></i></a>
                                 </div>
                             </div>
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
