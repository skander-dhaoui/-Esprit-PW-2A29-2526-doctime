<?php
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: index.php?page=login');
    exit;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($dispo) ? 'Modifier' : 'Ajouter' ?> une disponibilité - Valorys</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background: #f0f2f5; font-family: 'Segoe UI', sans-serif; }
        .sidebar { width: 260px; background: #1a2035; color: white; position: fixed; height: 100%; }
        .main-content { margin-left: 260px; padding: 20px; }
        .card { border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
        .form-label { font-weight: 600; }
    </style>
</head>
<body>

<!-- Sidebar -->
<div class="sidebar p-3">
    <h4 class="text-center mb-4">Valorys Admin</h4>
    <hr class="bg-light">
    <a href="index.php?page=dashboard" class="text-white d-block py-2">📊 Dashboard</a>
    <a href="index.php?page=admin_rendezvous" class="text-white d-block py-2">📅 Rendez-vous</a>
    <a href="index.php?page=admin_disponibilite" class="text-white d-block py-2 bg-primary px-2 rounded">⏰ Disponibilités</a>
    <a href="index.php?page=logout" class="text-white d-block py-2 mt-5">🚪 Déconnexion</a>
</div>

<!-- Main Content -->
<div class="main-content">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="fas fa-<?= isset($dispo) ? 'edit' : 'plus' ?> me-2"></i><?= isset($dispo) ? 'Modifier' : 'Ajouter' ?> une disponibilité</h2>
        <a href="index.php?page=admin_disponibilite" class="btn btn-secondary">
            <i class="fas fa-arrow-left me-2"></i>Retour
        </a>
    </div>

    <div class="card">
        <div class="card-body">
            <form method="POST" action="index.php?page=admin_disponibilite&action=<?= isset($dispo) ? 'update&id=' . $dispo['id'] : 'store' ?>">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Médecin *</label>
                        <select name="medecin_id" class="form-select" required>
                            <option value="">Sélectionner un médecin</option>
                            <?php foreach ($medecins as $medecin): ?>
                                <option value="<?= $medecin['id'] ?>" <?= (isset($dispo) && $dispo['medecin_id'] == $medecin['id']) ? 'selected' : '' ?>>
                                    Dr. <?= htmlspecialchars($medecin['prenom'] . ' ' . $medecin['nom']) ?> - <?= $medecin['specialite'] ?? 'Généraliste' ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Jour *</label>
                        <select name="jour_semaine" class="form-select" required>
                            <option value="">Sélectionner un jour</option>
                            <option value="Lundi" <?= (isset($dispo) && $dispo['jour_semaine'] === 'Lundi') ? 'selected' : '' ?>>Lundi</option>
                            <option value="Mardi" <?= (isset($dispo) && $dispo['jour_semaine'] === 'Mardi') ? 'selected' : '' ?>>Mardi</option>
                            <option value="Mercredi" <?= (isset($dispo) && $dispo['jour_semaine'] === 'Mercredi') ? 'selected' : '' ?>>Mercredi</option>
                            <option value="Jeudi" <?= (isset($dispo) && $dispo['jour_semaine'] === 'Jeudi') ? 'selected' : '' ?>>Jeudi</option>
                            <option value="Vendredi" <?= (isset($dispo) && $dispo['jour_semaine'] === 'Vendredi') ? 'selected' : '' ?>>Vendredi</option>
                            <option value="Samedi" <?= (isset($dispo) && $dispo['jour_semaine'] === 'Samedi') ? 'selected' : '' ?>>Samedi</option>
                            <option value="Dimanche" <?= (isset($dispo) && $dispo['jour_semaine'] === 'Dimanche') ? 'selected' : '' ?>>Dimanche</option>
                        </select>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Heure début *</label>
                        <input type="time" name="heure_debut" class="form-control" value="<?= isset($dispo) ? $dispo['heure_debut'] : '09:00' ?>" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Heure fin *</label>
                        <input type="time" name="heure_fin" class="form-control" value="<?= isset($dispo) ? $dispo['heure_fin'] : '17:00' ?>" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Actif</label>
                        <select name="actif" class="form-select">
                            <option value="1" <?= (isset($dispo) && $dispo['actif']) ? 'selected' : '' ?>>Oui</option>
                            <option value="0" <?= (isset($dispo) && !$dispo['actif']) ? 'selected' : '' ?>>Non</option>
                        </select>
                    </div>
                </div>

                <hr>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-2"></i><?= isset($dispo) ? 'Mettre à jour' : 'Créer' ?>
                    </button>
                    <a href="index.php?page=admin_disponibilite" class="btn btn-secondary">Annuler</a>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>