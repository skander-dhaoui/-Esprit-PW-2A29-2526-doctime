<?php
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'medecin') {
    header('Location: index.php?page=login');
    exit;
}

// Initialisation des variables avec valeurs par défaut
$disponibilites = $disponibilites ?? [];
$errors = $_SESSION['errors'] ?? [];
$old = $_SESSION['old'] ?? [];
$flash = $_SESSION['flash'] ?? null;

// Nettoyer les sessions après récupération
unset($_SESSION['errors'], $_SESSION['old']);
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
        .navbar { background: linear-gradient(135deg, #2A7FAA 0%, #4CAF50 100%); box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .navbar-brand { color: white !important; font-weight: bold; }
        .nav-link { color: rgba(255,255,255,0.9) !important; }
        .nav-link:hover { color: white !important; }
        .nav-link.active { background: rgba(255,255,255,0.2); border-radius: 8px; }
        .card { border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); margin-bottom: 20px; border: none; transition: transform 0.2s; }
        .card:hover { transform: translateY(-3px); box-shadow: 0 5px 20px rgba(0,0,0,0.1); }
        .badge-actif { background: #d4edda; color: #155724; padding: 5px 12px; border-radius: 20px; font-size: 12px; font-weight: normal; }
        .badge-inactif { background: #f8d7da; color: #721c24; padding: 5px 12px; border-radius: 20px; font-size: 12px; font-weight: normal; }
        .field-error { font-size: 12px; margin-top: 5px; color: #dc3545; font-weight: normal; }
        .field-error i { margin-right: 5px; }
        .form-control.error, .form-select.error { border-color: #dc3545 !important; background-color: #fff8f8; }
        .error-container { min-height: 32px; }
        .btn-toggle { background: #ffc107; color: #000; border: none; padding: 5px 12px; border-radius: 5px; font-size: 12px; text-decoration: none; display: inline-block; margin-right: 5px; }
        .btn-toggle:hover { background: #e0a800; color: #000; }
        .btn-delete { background: #dc3545; color: #fff; border: none; padding: 5px 12px; border-radius: 5px; font-size: 12px; text-decoration: none; display: inline-block; }
        .btn-delete:hover { background: #c82333; color: #fff; }
        .btn-primary { background: linear-gradient(135deg, #2A7FAA 0%, #4CAF50 100%); border: none; }
        .btn-primary:hover { background: linear-gradient(135deg, #1e5f80 0%, #3d8b40 100%); }
        footer { background: #1a2035; color: white; text-align: center; padding: 30px; margin-top: 50px; }
        .empty-state { text-align: center; padding: 60px 20px; background: white; border-radius: 12px; }
        .empty-state i { font-size: 60px; color: #2A7FAA; margin-bottom: 20px; opacity: 0.5; }
        .jour-badge { background: #e8f4f8; padding: 3px 10px; border-radius: 15px; font-size: 11px; color: #2A7FAA; display: inline-block; margin-top: 8px; }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark">
    <div class="container">
        <a class="navbar-brand" href="index.php?page=accueil"><i class="fas fa-stethoscope me-2"></i>Valorys</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item"><a class="nav-link" href="index.php?page=accueil"><i class="fas fa-home me-1"></i>Accueil</a></li>
                <li class="nav-item"><a class="nav-link" href="index.php?page=mes_rendez_vous"><i class="fas fa-calendar-check me-1"></i>Mes RDV</a></li>
                <li class="nav-item"><a class="nav-link active" href="index.php?page=medecin_disponibilites"><i class="fas fa-clock me-1"></i>Disponibilités</a></li>
                <li class="nav-item"><a class="nav-link" href="index.php?page=mes_ordonnances"><i class="fas fa-prescription me-1"></i>Ordonnances</a></li>
                <li class="nav-item"><a class="nav-link" href="index.php?page=mon_profil"><i class="fas fa-user-circle me-1"></i>Profil</a></li>
                <li class="nav-item"><a class="nav-link" href="index.php?page=logout"><i class="fas fa-sign-out-alt me-1"></i>Déconnexion</a></li>
            </ul>
        </div>
    </div>
</nav>

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="fas fa-clock me-2" style="color: #2A7FAA;"></i>Mes disponibilités</h2>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addDispoModal">
            <i class="fas fa-plus me-2"></i>Ajouter un créneau
        </button>
    </div>

    <!-- Message flash -->
    <?php if (isset($flash) && is_array($flash)): ?>
        <div class="alert alert-<?= $flash['type'] === 'error' ? 'danger' : 'success' ?> alert-dismissible fade show">
            <i class="fas <?= $flash['type'] === 'error' ? 'fa-exclamation-circle' : 'fa-check-circle' ?> me-2"></i>
            <?= htmlspecialchars($flash['message']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['flash']); ?>
    <?php endif; ?>

    <!-- Liste des disponibilités -->
    <div class="row">
        <?php if (empty($disponibilites)): ?>
            <div class="col-12">
                <div class="empty-state">
                    <i class="fas fa-calendar-times"></i>
                    <h4>Aucune disponibilité définie</h4>
                    <p class="text-muted">Ajoutez vos créneaux horaires pour que les patients puissent prendre rendez-vous.</p>
                    <button class="btn btn-primary mt-3" data-bs-toggle="modal" data-bs-target="#addDispoModal">
                        <i class="fas fa-plus me-2"></i>Ajouter mes premiers créneaux
                    </button>
                </div>
            </div>
        <?php else: ?>
            <?php foreach ($disponibilites as $dispo): ?>
            <div class="col-md-6 col-lg-4">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <div>
                                <h5 class="card-title text-primary mb-1"><?= htmlspecialchars($dispo['jour_semaine']) ?></h5>
                                <span class="jour-badge"><i class="fas fa-calendar-alt me-1"></i>Hebdomadaire</span>
                            </div>
                            <span class="<?= $dispo['actif'] ? 'badge-actif' : 'badge-inactif' ?>">
                                <i class="fas <?= $dispo['actif'] ? 'fa-check-circle' : 'fa-ban' ?> me-1"></i>
                                <?= $dispo['actif'] ? 'Actif' : 'Inactif' ?>
                            </span>
                        </div>
                        <p class="card-text mt-3">
                            <i class="fas fa-clock me-2 text-success"></i>
                            <strong><?= date('H:i', strtotime($dispo['heure_debut'])) ?></strong> - <strong><?= date('H:i', strtotime($dispo['heure_fin'])) ?></strong>
                        </p>
                        <div class="mt-3">
                            <a href="index.php?page=medecin_disponibilites&action=toggle&id=<?= $dispo['id'] ?>" class="btn-toggle" onclick="return confirm('<?= $dispo['actif'] ? 'Désactiver' : 'Activer' ?> ce créneau ?')">
                                <i class="fas <?= $dispo['actif'] ? 'fa-ban' : 'fa-check' ?> me-1"></i>
                                <?= $dispo['actif'] ? 'Désactiver' : 'Activer' ?>
                            </a>
                            <a href="index.php?page=medecin_disponibilites&action=delete&id=<?= $dispo['id'] ?>" class="btn-delete" onclick="return confirm('Supprimer définitivement ce créneau ?')">
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
                    <!-- Jour -->
                    <div class="mb-3">
                        <label class="form-label">Jour <span class="text-danger">*</span></label>
                        <select name="jour_semaine" id="jour_semaine" class="form-select <?= isset($errors['jour_semaine']) ? 'error' : '' ?>">
                            <option value="">-- Sélectionner un jour --</option>
                            <?php
                            $jours = ['Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi', 'Dimanche'];
                            foreach ($jours as $jour):
                                $selected = (isset($old['jour_semaine']) && $old['jour_semaine'] == $jour) ? 'selected' : '';
                            ?>
                                <option value="<?= $jour ?>" <?= $selected ?>><?= $jour ?></option>
                            <?php endforeach; ?>
                        </select>
                        <div class="error-container" id="jour_semaine-error">
                            <?php if (isset($errors['jour_semaine'])): ?>
                                <div class="field-error"><i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($errors['jour_semaine']) ?></div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="row">
                        <!-- Heure début -->
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Heure début <span class="text-danger">*</span></label>
                            <input type="time" name="heure_debut" id="heure_debut" 
                                   class="form-control <?= isset($errors['heure_debut']) ? 'error' : '' ?>" 
                                   value="<?= htmlspecialchars($old['heure_debut'] ?? '09:00') ?>">
                            <div class="error-container" id="heure_debut-error">
                                <?php if (isset($errors['heure_debut'])): ?>
                                    <div class="field-error"><i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($errors['heure_debut']) ?></div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Heure fin -->
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Heure fin <span class="text-danger">*</span></label>
                            <input type="time" name="heure_fin" id="heure_fin" 
                                   class="form-control <?= isset($errors['heure_fin']) ? 'error' : '' ?>" 
                                   value="<?= htmlspecialchars($old['heure_fin'] ?? '17:00') ?>">
                            <div class="error-container" id="heure_fin-error">
                                <?php if (isset($errors['heure_fin'])): ?>
                                    <div class="field-error"><i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($errors['heure_fin']) ?></div>
                                <?php endif; ?>
                            </div>
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

<footer>
    <div class="container">
        <p>&copy; 2024 Valorys - Tous droits réservés</p>
        <small>Plateforme médicale en ligne</small>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Nettoyer les erreurs quand l'utilisateur corrige
document.querySelectorAll('#jour_semaine, #heure_debut, #heure_fin').forEach(field => {
    if (field) {
        field.addEventListener('change', function() {
            this.classList.remove('error');
            const errorContainer = document.getElementById(this.id + '-error');
            if (errorContainer) errorContainer.innerHTML = '';
        });
        field.addEventListener('input', function() {
            this.classList.remove('error');
            const errorContainer = document.getElementById(this.id + '-error');
            if (errorContainer) errorContainer.innerHTML = '';
        });
    }
});

// Validation du formulaire avant soumission
document.querySelector('form').addEventListener('submit', function(e) {
    let isValid = true;
    const jour = document.getElementById('jour_semaine');
    const heureDebut = document.getElementById('heure_debut');
    const heureFin = document.getElementById('heure_fin');
    
    // Nettoyer les erreurs existantes
    document.querySelectorAll('.error-container').forEach(container => container.innerHTML = '');
    document.querySelectorAll('.form-control, .form-select').forEach(field => field.classList.remove('error'));
    
    // Validation du jour
    if (!jour.value) {
        document.getElementById('jour_semaine-error').innerHTML = '<div class="field-error"><i class="fas fa-exclamation-circle"></i> Veuillez sélectionner un jour</div>';
        jour.classList.add('error');
        isValid = false;
    }
    
    // Validation heure début
    if (!heureDebut.value) {
        document.getElementById('heure_debut-error').innerHTML = '<div class="field-error"><i class="fas fa-exclamation-circle"></i> Veuillez entrer une heure de début</div>';
        heureDebut.classList.add('error');
        isValid = false;
    }
    
    // Validation heure fin
    if (!heureFin.value) {
        document.getElementById('heure_fin-error').innerHTML = '<div class="field-error"><i class="fas fa-exclamation-circle"></i> Veuillez entrer une heure de fin</div>';
        heureFin.classList.add('error');
        isValid = false;
    }
    
    // Vérifier que heure début < heure fin
    if (heureDebut.value && heureFin.value && heureDebut.value >= heureFin.value) {
        document.getElementById('heure_fin-error').innerHTML = '<div class="field-error"><i class="fas fa-exclamation-circle"></i> L\'heure de fin doit être après l\'heure de début</div>';
        heureFin.classList.add('error');
        isValid = false;
    }
    
    if (!isValid) e.preventDefault();
});
</script>
</body>
</html>