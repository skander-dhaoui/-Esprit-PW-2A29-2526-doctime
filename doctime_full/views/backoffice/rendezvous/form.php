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
    <title>Ajouter un rendez-vous - Valorys Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .field-error {
            font-size: 12px;
            margin-top: 5px;
            color: #dc3545;
            font-weight: normal;
        }
        .field-error i {
            margin-right: 5px;
        }
        .form-control.error, .form-select.error {
            border-color: #dc3545 !important;
        }
        .error-container {
            min-height: 32px;
        }
    </style>
</head>
<body>
<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="fas fa-calendar-plus"></i> Ajouter un rendez-vous</h2>
        <a href="index.php?page=rendez_vous_admin" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Retour
        </a>
    </div>

    <?php if (isset($flash) && isset($flash['type'])): ?>
        <div class="alert alert-<?= $flash['type'] === 'error' ? 'danger' : $flash['type'] ?> alert-dismissible fade show">
            <?= htmlspecialchars($flash['message']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="card">
        <div class="card-body">
            <form method="POST" action="index.php?page=rendez_vous_admin&action=create">
                <div class="row">
                    <!-- Patient -->
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Patient <span class="text-danger">*</span></label>
                        <select name="patient_id" id="patient_id" class="form-select <?= isset($errors['patient_id']) ? 'error' : '' ?>">
                            <option value="">-- Sélectionner un patient --</option>
                            <?php foreach ($patients as $patient): ?>
                                <option value="<?= $patient['id'] ?>" <?= (isset($old['patient_id']) && $old['patient_id'] == $patient['id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($patient['prenom'] . ' ' . $patient['nom']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="error-container" id="patient_id-error">
                            <?php if (isset($errors['patient_id'])): ?>
                                <div class="field-error"><i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($errors['patient_id']) ?></div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Médecin -->
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Médecin <span class="text-danger">*</span></label>
                        <select name="medecin_id" id="medecin_id" class="form-select <?= isset($errors['medecin_id']) ? 'error' : '' ?>">
                            <option value="">-- Sélectionner un médecin --</option>
                            <?php foreach ($medecins as $medecin): ?>
                                <option value="<?= $medecin['id'] ?>" <?= (isset($old['medecin_id']) && $old['medecin_id'] == $medecin['id']) ? 'selected' : '' ?>>
                                    Dr. <?= htmlspecialchars($medecin['prenom'] . ' ' . $medecin['nom']) ?> - <?= htmlspecialchars($medecin['specialite'] ?? 'Généraliste') ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="error-container" id="medecin_id-error">
                            <?php if (isset($errors['medecin_id'])): ?>
                                <div class="field-error"><i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($errors['medecin_id']) ?></div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <!-- Date -->
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Date <span class="text-danger">*</span></label>
                        <input type="text" name="date_rendezvous" id="date_rendezvous" class="form-control <?= isset($errors['date_rendezvous']) ? 'error' : '' ?>" 
                               value="<?= htmlspecialchars($old['date_rendezvous'] ?? '') ?>">
                        <div class="error-container" id="date_rendezvous-error">
                            <?php if (isset($errors['date_rendezvous'])): ?>
                                <div class="field-error"><i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($errors['date_rendezvous']) ?></div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Heure -->
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Heure <span class="text-danger">*</span></label>
                        <input type="text" name="heure_rendezvous" id="heure_rendezvous" class="form-control <?= isset($errors['heure_rendezvous']) ? 'error' : '' ?>" 
                               value="<?= htmlspecialchars($old['heure_rendezvous'] ?? '') ?>">
                        <div class="error-container" id="heure_rendezvous-error">
                            <?php if (isset($errors['heure_rendezvous'])): ?>
                                <div class="field-error"><i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($errors['heure_rendezvous']) ?></div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Motif -->
                <div class="mb-3">
                    <label class="form-label">Motif</label>
                    <textarea name="motif" id="motif" class="form-control" rows="3" 
                              placeholder="Motif de la consultation..."><?= htmlspecialchars($old['motif'] ?? '') ?></textarea>
                    <div class="error-container" id="motif-error"></div>
                </div>

                <!-- Statut -->
                <div class="mb-3">
                    <label class="form-label">Statut</label>
                    <select name="statut" id="statut" class="form-select">
                        <option value="en_attente" <?= (isset($old['statut']) && $old['statut'] === 'en_attente') ? 'selected' : '' ?>>En attente</option>
                        <option value="confirmé" <?= (isset($old['statut']) && $old['statut'] === 'confirmé') ? 'selected' : '' ?>>Confirmé</option>
                        <option value="terminé" <?= (isset($old['statut']) && $old['statut'] === 'terminé') ? 'selected' : '' ?>>Terminé</option>
                        <option value="annulé" <?= (isset($old['statut']) && $old['statut'] === 'annulé') ? 'selected' : '' ?>>Annulé</option>
                    </select>
                    <div class="error-container" id="statut-error"></div>
                </div>

                <div class="d-flex justify-content-end gap-2">
                    <a href="index.php?page=rendez_vous_admin" class="btn btn-secondary">Annuler</a>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Créer
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.querySelector('form').addEventListener('submit', function(e) {
    let isValid = true;
    
    document.querySelectorAll('.field-error').forEach(el => el.remove());
    document.querySelectorAll('.form-control, .form-select').forEach(el => el.classList.remove('error'));
    
    const patient = document.getElementById('patient_id');
    if (!patient.value) {
        showError('patient_id', 'Veuillez sélectionner un patient.');
        isValid = false;
    }
    
    const medecin = document.getElementById('medecin_id');
    if (!medecin.value) {
        showError('medecin_id', 'Veuillez sélectionner un médecin.');
        isValid = false;
    }
    
    const date = document.getElementById('date_rendezvous');
    if (!date.value) {
        showError('date_rendezvous', 'Veuillez sélectionner une date.');
        isValid = false;
    }
    
    const heure = document.getElementById('heure_rendezvous');
    if (!heure.value) {
        showError('heure_rendezvous', 'Veuillez sélectionner une heure.');
        isValid = false;
    }
    
    if (!isValid) {
        e.preventDefault();
        const firstError = document.querySelector('.field-error');
        if (firstError) firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }
});

function showError(fieldId, message) {
    const field = document.getElementById(fieldId);
    if (field) field.classList.add('error');
    
    const errorContainer = document.getElementById(fieldId + '-error');
    if (errorContainer) {
        const errorDiv = document.createElement('div');
        errorDiv.className = 'field-error';
        errorDiv.innerHTML = '<i class="fas fa-exclamation-circle"></i> ' + message;
        errorContainer.appendChild(errorDiv);
    }
}

document.querySelectorAll('#patient_id, #medecin_id, #date_rendezvous, #heure_rendezvous').forEach(field => {
    if (field) {
        field.addEventListener('change', function() {
            this.classList.remove('error');
            const errorContainer = document.getElementById(this.id + '-error');
            if (errorContainer) errorContainer.innerHTML = '';
        });
    }
});
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>