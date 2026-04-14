<?php
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'medecin') {
    header('Location: index.php?page=login');
    exit;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mes disponibilités - Espace Médecin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background: #f5f7fb; font-family: 'Segoe UI', sans-serif; }
        .navbar { background: #1a2035; }
        .card { border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); margin-bottom: 20px; }
        .badge-actif { background: #d4edda; color: #155724; }
        .badge-inactif { background: #f8d7da; color: #721c24; }
        .dispo-card { border-left: 4px solid #4CAF50; transition: transform 0.2s; }
        .dispo-card:hover { transform: translateY(-3px); }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark">
    <div class="container">
        <a class="navbar-brand" href="index.php?page=accueil"><i class="fas fa-stethoscope me-2"></i>Valorys</a>
        <div class="collapse navbar-collapse">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item"><a class="nav-link" href="index.php?page=accueil">Accueil</a></li>
                <li class="nav-item"><a class="nav-link" href="index.php?page=medecin_rendezvous">Mes RDV</a></li>
                <li class="nav-item"><a class="nav-link active" href="index.php?page=medecin_disponibilites">Disponibilités</a></li>
                <li class="nav-item"><a class="nav-link" href="index.php?page=medecin_ordonnances">Ordonnances</a></li>
                <li class="nav-item"><a class="nav-link" href="index.php?page=mon_profil">Profil</a></li>
                <li class="nav-item"><a class="nav-link" href="index.php?page=logout">Déconnexion</a></li>
            </ul>
        </div>
    </div>
</nav>

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="fas fa-clock me-2"></i>Mes disponibilités</h2>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addDispoModal">
            <i class="fas fa-plus me-2"></i>Ajouter un créneau
        </button>
    </div>

    <?php if (isset($_SESSION['flash'])): ?>
        <div class="alert alert-<?= $_SESSION['flash']['type'] === 'error' ? 'danger' : 'success' ?> alert-dismissible fade show">
            <?= $_SESSION['flash']['message'] ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['flash']); ?>
    <?php endif; ?>

    <div class="row">
        <?php if (empty($dispos)): ?>
            <div class="col-12">
                <div class="alert alert-info text-center">
                    <i class="fas fa-info-circle fa-2x mb-2 d-block"></i>
                    <p>Aucune disponibilité définie.</p>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addDispoModal">
                        Ajouter vos créneaux
                    </button>
                </div>
            </div>
        <?php else: ?>
            <?php foreach ($dispos as $dispo): ?>
            <div class="col-md-6 col-lg-4">
                <div class="card dispo-card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start">
                            <h5 class="card-title text-primary"><?= $dispo['jour_semaine'] ?></h5>
                            <span class="badge <?= $dispo['actif'] ? 'badge-actif' : 'badge-inactif' ?>">
                                <?= $dispo['actif'] ? 'Actif' : 'Inactif' ?>
                            </span>
                        </div>
                        <p class="card-text">
                            <i class="fas fa-clock me-2"></i><?= $dispo['heure_debut'] ?> - <?= $dispo['heure_fin'] ?>
                        </p>
                        <div class="mt-3">
                            <a href="index.php?page=medecin_disponibilites&action=toggle&id=<?= $dispo['id'] ?>" class="btn btn-sm <?= $dispo['actif'] ? 'btn-warning' : 'btn-success' ?>">
                                <i class="fas <?= $dispo['actif'] ? 'fa-ban' : 'fa-check' ?> me-1"></i><?= $dispo['actif'] ? 'Désactiver' : 'Activer' ?>
                            </a>
                            <a href="index.php?page=medecin_disponibilites&action=delete&id=<?= $dispo['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Supprimer ce créneau ?')">
                                <i class="fas fa-trash me-1"></i>Supprimer
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<!-- Modal Ajout Disponibilité -->
<div class="modal fade" id="addDispoModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title"><i class="fas fa-plus me-2"></i>Ajouter une disponibilité</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="index.php?page=medecin_disponibilites&action=store">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Jour *</label>
                        <select name="jour_semaine" class="form-select" required>
                            <option value="">Sélectionner un jour</option>
                            <option value="Lundi">Lundi</option>
                            <option value="Mardi">Mardi</option>
                            <option value="Mercredi">Mercredi</option>
                            <option value="Jeudi">Jeudi</option>
                            <option value="Vendredi">Vendredi</option>
                            <option value="Samedi">Samedi</option>
                            <option value="Dimanche">Dimanche</option>
                        </select>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Heure début *</label>
                            <input type="time" name="heure_debut" class="form-control" value="09:00" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Heure fin *</label>
                            <input type="time" name="heure_fin" class="form-control" value="17:00" required>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary">Ajouter</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>