<?php
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'medecin') {
    header('Location: index.php?page=login');
    exit;
}

$disponibilites = $disponibilites ?? [];
$errors = $_SESSION['errors'] ?? [];
$old = $_SESSION['old'] ?? [];
$flash = $_SESSION['flash'] ?? null;

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
        .navbar { background: linear-gradient(135deg, #2A7FAA 0%, #4CAF50 100%); }
        .card { border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); margin-bottom: 20px; border: none; }
        .card-header { background: white; border-bottom: 1px solid #eee; font-weight: bold; border-radius: 12px 12px 0 0; }
        .badge-actif { background: #d4edda; color: #155724; padding: 5px 12px; border-radius: 20px; font-size: 12px; }
        .badge-inactif { background: #f8d7da; color: #721c24; padding: 5px 12px; border-radius: 20px; font-size: 12px; }
        .field-error { font-size: 12px; margin-top: 5px; color: #dc3545; font-weight: normal; }
        .field-error i { margin-right: 5px; }
        .form-control.is-invalid, .form-select.is-invalid { border-color: #dc3545 !important; }
        .error-container { min-height: auto; margin-top: 6px; }
        .error-container .alert { margin-bottom: 0; padding: 8px 12px; font-size: 13px; }
        .btn-toggle { background: #ffc107; color: #000; border: none; padding: 5px 12px; border-radius: 5px; font-size: 12px; }
        .btn-delete { background: #dc3545; color: #fff; border: none; padding: 5px 12px; border-radius: 5px; font-size: 12px; }
        footer { background: #1a2035; color: white; text-align: center; padding: 30px; margin-top: 50px; }
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
                <li class="nav-item"><a class="nav-link" href="index.php?page=accueil">Accueil</a></li>
                <li class="nav-item"><a class="nav-link" href="index.php?page=mes_rendez_vous">Mes RDV</a></li>
                <li class="nav-item"><a class="nav-link active" href="index.php?page=disponibilites">Disponibilités</a></li>
                <li class="nav-item"><a class="nav-link" href="index.php?page=ordonnances">Ordonnances</a></li>
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

    <!-- Message flash -->
    <?php if (isset($flash)): ?>
        <div class="alert alert-<?= $flash['type'] === 'error' ? 'danger' : 'success' ?> alert-dismissible fade show">
            <?= htmlspecialchars($flash['message']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- Liste des disponibilités -->
    <div class="row">
        <?php if (empty($disponibilites)): ?>
            <div class="col-12">
                <div class="alert alert-info text-center py-4">
                    <i class="fas fa-info-circle fa-3x mb-3 d-block"></i>
                    <p>Aucune disponibilité définie.</p>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addDispoModal">
                        Ajouter vos créneaux
                    </button>
                </div>
            </div>
        <?php else: ?>
            <?php foreach ($disponibilites as $dispo): ?>
            <div class="col-md-6 col-lg-4">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <h5 class="card-title text-primary mb-0"><?= htmlspecialchars($dispo['jour_semaine']) ?></h5>
                            <span class="badge <?= $dispo['actif'] ? 'badge-actif' : 'badge-inactif' ?>">
                                <?= $dispo['actif'] ? 'Actif' : 'Inactif' ?>
                            </span>
                        </div>
                        <p class="card-text">
                            <i class="fas fa-clock me-2 text-success"></i>
                            <?= date('H:i', strtotime($dispo['heure_debut'])) ?> - <?= date('H:i', strtotime($dispo['heure_fin'])) ?>
                        </p>
                        <div class="mt-3">
                            <a href="index.php?page=disponibilites&action=toggle&id=<?= $dispo['id'] ?>" class="btn-toggle">
                                <i class="fas <?= $dispo['actif'] ? 'fa-ban' : 'fa-check' ?> me-1"></i>
                                <?= $dispo['actif'] ? 'Désactiver' : 'Activer' ?>
                            </a>
                            <a href="index.php?page=disponibilites&action=delete&id=<?= $dispo['id'] ?>" class="btn-delete" onclick="confirmDelete(event, this.href)">
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
            <form method="POST" action="index.php?page=disponibilites&action=store">
                <input type="hidden" name="actif" value="1">
                <div class="modal-body">
                    <!-- Jour -->
                    <div class="mb-3">
                        <label class="form-label"><i class="fas fa-calendar me-2"></i>Jour <span class="text-danger">*</span></label>
                        <select name="jour_semaine" id="jour_semaine" class="form-select <?= isset($errors['jour_semaine']) ? 'is-invalid' : '' ?>">
                            <option value="">Sélectionner un jour</option>
                            <option value="Lundi" <?= (isset($old['jour_semaine']) && $old['jour_semaine'] == 'Lundi') ? 'selected' : '' ?>>Lundi</option>
                            <option value="Mardi" <?= (isset($old['jour_semaine']) && $old['jour_semaine'] == 'Mardi') ? 'selected' : '' ?>>Mardi</option>
                            <option value="Mercredi" <?= (isset($old['jour_semaine']) && $old['jour_semaine'] == 'Mercredi') ? 'selected' : '' ?>>Mercredi</option>
                            <option value="Jeudi" <?= (isset($old['jour_semaine']) && $old['jour_semaine'] == 'Jeudi') ? 'selected' : '' ?>>Jeudi</option>
                            <option value="Vendredi" <?= (isset($old['jour_semaine']) && $old['jour_semaine'] == 'Vendredi') ? 'selected' : '' ?>>Vendredi</option>
                            <option value="Samedi" <?= (isset($old['jour_semaine']) && $old['jour_semaine'] == 'Samedi') ? 'selected' : '' ?>>Samedi</option>
                            <option value="Dimanche" <?= (isset($old['jour_semaine']) && $old['jour_semaine'] == 'Dimanche') ? 'selected' : '' ?>>Dimanche</option>
                        </select>
                        <div class="error-container" id="jour_semaine-error">
                            <?php if (isset($errors['jour_semaine'])): ?>
                                <div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i><?= htmlspecialchars($errors['jour_semaine']) ?></div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="row">
                        <!-- Heure début -->
                        <div class="col-md-6 mb-3">
                            <label class="form-label"><i class="fas fa-clock me-2"></i>Heure début <span class="text-danger">*</span></label>
                            <input type="time" name="heure_debut" id="heure_debut" class="form-control <?= isset($errors['heure_debut']) ? 'is-invalid' : '' ?>" 
                                   value="<?= htmlspecialchars($old['heure_debut'] ?? '09:00') ?>">
                            <div class="error-container" id="heure_debut-error">
                                <?php if (isset($errors['heure_debut'])): ?>
                                    <div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i><?= htmlspecialchars($errors['heure_debut']) ?></div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Heure fin -->
                        <div class="col-md-6 mb-3">
                            <label class="form-label"><i class="fas fa-clock me-2"></i>Heure fin <span class="text-danger">*</span></label>
                            <input type="time" name="heure_fin" id="heure_fin" class="form-control <?= isset($errors['heure_fin']) ? 'is-invalid' : '' ?>" 
                                   value="<?= htmlspecialchars($old['heure_fin'] ?? '17:00') ?>">
                            <div class="error-container" id="heure_fin-error">
                                <?php if (isset($errors['heure_fin'])): ?>
                                    <div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i><?= htmlspecialchars($errors['heure_fin']) ?></div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save me-2"></i>Ajouter</button>
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
// Validation du formulaire de disponibilité
const disponibiliteForm = document.querySelector('#addDispoModal form');
const joursemaine = document.getElementById('jour_semaine');
const heureDebut = document.getElementById('heure_debut');
const heureFin = document.getElementById('heure_fin');

function validateForm() {
    let isValid = true;
    const errors = {};

    // Nettoyer tous les messages d'erreur précédents
    document.querySelectorAll('.error-container').forEach(container => {
        container.innerHTML = '';
    });

    // Valider jour
    if (!joursemaine.value) {
        isValid = false;
        errors['jour_semaine'] = 'Veuillez sélectionner un jour';
    }

    // Valider heure début
    if (!heureDebut.value) {
        isValid = false;
        errors['heure_debut'] = 'L\'heure de début est obligatoire';
    }

    // Valider heure fin
    if (!heureFin.value) {
        isValid = false;
        errors['heure_fin'] = 'L\'heure de fin est obligatoire';
    }

    // Valider que heure_fin > heure_debut
    if (heureDebut.value && heureFin.value && heureFin.value <= heureDebut.value) {
        isValid = false;
        errors['heure_fin'] = 'L\'heure de fin doit être après l\'heure de début';
    }

    // Afficher les erreurs dans la modale
    if (!isValid) {
        Object.keys(errors).forEach(fieldName => {
            const errorContainer = document.getElementById(fieldName + '-error');
            if (errorContainer) {
                const errorHTML = `<div class="alert alert-danger py-2 mb-0"><i class="fas fa-times-circle me-2"></i>${errors[fieldName]}</div>`;
                errorContainer.innerHTML = errorHTML;
            }

            // Ajouter la classe d'erreur au champ
            const field = document.getElementById(fieldName);
            if (field) {
                field.classList.add('is-invalid');
            }
        });
    }

    return isValid;
}

// Intercepter la soumission du formulaire
if (disponibiliteForm) {
    disponibiliteForm.addEventListener('submit', function(e) {
        e.preventDefault();

        if (validateForm()) {
            // Validation réussie, soumettre le formulaire
            this.submit();
        }
        // Si la validation échoue, le formulaire reste ouvert avec les erreurs affichées
    });
}

// Nettoyer les erreurs quand l'utilisateur corrige
document.querySelectorAll('#jour_semaine, #heure_debut, #heure_fin').forEach(field => {
    if (field) {
        field.addEventListener('change', function() {
            this.classList.remove('is-invalid');
            const errorContainer = document.getElementById(this.id + '-error');
            if (errorContainer) {
                errorContainer.innerHTML = '';
            }
        });
        field.addEventListener('input', function() {
            this.classList.remove('is-invalid');
            const errorContainer = document.getElementById(this.id + '-error');
            if (errorContainer) {
                errorContainer.innerHTML = '';
            }
        });
    }
});
</script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
function confirmDelete(event, url) {
    event.preventDefault();
    Swal.fire({
        title: 'Supprimer ce créneau ?',
        text: "Cette action est irréversible.",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        cancelButtonColor: '#6c757d',
        confirmButtonText: '<i class="fas fa-trash me-2"></i>Oui, supprimer',
        cancelButtonText: 'Annuler',
        borderRadius: '12px'
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = url;
        }
    });
}
</script>
</body>
</html>