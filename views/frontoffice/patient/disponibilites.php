<?php
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'patient') {
    header('Location: index.php?page=login');
    exit;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Disponibilités des médecins - Valorys</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background: #f5f7fb; font-family: 'Segoe UI', sans-serif; }
        .navbar { background: linear-gradient(135deg, #2A7FAA 0%, #4CAF50 100%); }
        .card { border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); transition: transform 0.2s; }
        .card:hover { transform: translateY(-3px); }
        .filter-card { background: white; border-radius: 12px; padding: 20px; margin-bottom: 25px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
        .dispo-card { border-left: 4px solid #4CAF50; margin-bottom: 20px; }
        .dispo-card .card-title { color: #2A7FAA; font-weight: bold; }
        .badge-dispo { background: #d4edda; color: #155724; padding: 5px 12px; border-radius: 20px; font-size: 12px; }
        .btn-rdv { background: linear-gradient(135deg, #2A7FAA, #4CAF50); color: white; border: none; padding: 6px 15px; border-radius: 20px; font-size: 13px; }
        .btn-rdv:hover { opacity: 0.9; color: white; }
        footer { background: #1a2035; color: white; text-align: center; padding: 30px; margin-top: 50px; }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark">
    <div class="container">
        <a class="navbar-brand" href="index.php?page=accueil"><i class="fas fa-stethoscope me-2"></i>Valorys</a>
        <div class="collapse navbar-collapse">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item"><a class="nav-link" href="index.php?page=accueil">Accueil</a></li>
                <li class="nav-item"><a class="nav-link" href="index.php?page=medecins">Médecins</a></li>
                <li class="nav-item"><a class="nav-link active" href="index.php?page=patient_disponibilites">Disponibilités</a></li>
                <li class="nav-item"><a class="nav-link" href="index.php?page=blog_public">Blog</a></li>
                <li class="nav-item"><a class="nav-link" href="index.php?page=mon_profil">Profil</a></li>
                <li class="nav-item"><a class="nav-link" href="index.php?page=logout">Déconnexion</a></li>
            </ul>
        </div>
    </div>
</nav>

<div class="container mt-4">
    <h2 class="mb-4"><i class="fas fa-clock me-2"></i>Disponibilités des médecins</h2>

    <!-- Filtres -->
    <div class="filter-card">
        <h5><i class="fas fa-filter me-2"></i>Filtrer</h5>
        <form method="GET" class="row g-3">
            <input type="hidden" name="page" value="patient_disponibilites">
            <div class="col-md-4">
                <select name="medecin_id" class="form-select">
                    <option value="">Tous les médecins</option>
                    <?php foreach ($medecins as $medecin): ?>
                        <option value="<?= $medecin['user_id'] ?>" <?= (isset($_GET['medecin_id']) && $_GET['medecin_id'] == $medecin['user_id']) ? 'selected' : '' ?>>
                            Dr. <?= htmlspecialchars($medecin['prenom'] . ' ' . $medecin['nom']) ?> - <?= htmlspecialchars($medecin['specialite'] ?? 'Généraliste') ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <select name="jour" class="form-select">
                    <option value="">Tous les jours</option>
                    <option value="Lundi" <?= (isset($_GET['jour']) && $_GET['jour'] == 'Lundi') ? 'selected' : '' ?>>Lundi</option>
                    <option value="Mardi" <?= (isset($_GET['jour']) && $_GET['jour'] == 'Mardi') ? 'selected' : '' ?>>Mardi</option>
                    <option value="Mercredi" <?= (isset($_GET['jour']) && $_GET['jour'] == 'Mercredi') ? 'selected' : '' ?>>Mercredi</option>
                    <option value="Jeudi" <?= (isset($_GET['jour']) && $_GET['jour'] == 'Jeudi') ? 'selected' : '' ?>>Jeudi</option>
                    <option value="Vendredi" <?= (isset($_GET['jour']) && $_GET['jour'] == 'Vendredi') ? 'selected' : '' ?>>Vendredi</option>
                    <option value="Samedi" <?= (isset($_GET['jour']) && $_GET['jour'] == 'Samedi') ? 'selected' : '' ?>>Samedi</option>
                    <option value="Dimanche" <?= (isset($_GET['jour']) && $_GET['jour'] == 'Dimanche') ? 'selected' : '' ?>>Dimanche</option>
                </select>
            </div>
            <div class="col-md-3">
                <button type="submit" class="btn btn-primary w-100">Filtrer</button>
            </div>
            <div class="col-md-2">
                <a href="index.php?page=patient_disponibilites" class="btn btn-secondary w-100">Réinitialiser</a>
            </div>
        </form>
    </div>

    <!-- Liste des disponibilités -->
    <div class="row">
        <?php if (empty($disponibilites)): ?>
            <div class="col-12">
                <div class="alert alert-info text-center py-4">
                    <i class="fas fa-info-circle fa-3x mb-3 d-block"></i>
                    <p>Aucune disponibilité trouvée pour le moment.</p>
                </div>
            </div>
        <?php else: ?>
            <?php foreach ($disponibilites as $dispo): ?>
            <div class="col-md-6 col-lg-4">
                <div class="card dispo-card h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start">
                            <h5 class="card-title">Dr. <?= htmlspecialchars($dispo['medecin_nom']) ?></h5>
                            <span class="badge-dispo">Disponible</span>
                        </div>
                        <p class="card-text text-muted small">
                            <i class="fas fa-stethoscope me-1"></i> <?= htmlspecialchars($dispo['specialite'] ?? 'Généraliste') ?>
                        </p>
                        <hr>
                        <div class="mb-2">
                            <i class="fas fa-calendar me-2 text-success"></i> <?= $dispo['jour_semaine'] ?>
                        </div>
                        <div class="mb-3">
                            <i class="fas fa-clock me-2 text-success"></i> <?= date('H:i', strtotime($dispo['heure_debut'])) ?> - <?= date('H:i', strtotime($dispo['heure_fin'])) ?>
                        </div>
                        <a href="index.php?page=prendre_rendez_vous&id=<?= $dispo['medecin_id'] ?>" class="btn-rdv">
                            <i class="fas fa-calendar-check me-1"></i> Prendre RDV
                        </a>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<footer>
    <div class="container">
        <p>&copy; 2024 Valorys - Tous droits réservés</p>
        <small>Plateforme médicale en ligne</small>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>// update
