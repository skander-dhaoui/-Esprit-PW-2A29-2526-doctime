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
    <title><?= isset($ordonnance) ? 'Modifier' : 'Créer' ?> une ordonnance - Valorys Admin</title>
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
            min-height: 30px;
        }
    </style>
</head>
<body>
<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="fas fa-prescription-bottle"></i> <?= isset($ordonnance) ? 'Modifier' : 'Créer' ?> une ordonnance</h2>
        <a href="index.php?page=ordonnances" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Retour
        </a>
    </div>

    <?php if (isset($flash) && isset($flash['type']) && $flash['type'] !== 'field'): ?>
        <div class="alert alert-<?= $flash['type'] === 'error' ? 'danger' : $flash['type'] ?> alert-dismissible fade show">
            <?= htmlspecialchars($flash['message']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="card">
        <div class="card-body">
            <form method="POST" action="index.php?page=ordonnances&action=<?= isset($ordonnance) ? 'edit&id=' . $ordonnance['id'] : 'create' ?>" id="ordonnanceForm">
                <div class="row">
                    <!-- Patient -->
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Patient <span class="text-danger">*</span></label>
                        <select name="patient_id" id="patient_id" class="form-select <?= isset($errors['patient_id']) ? 'error' : '' ?>">
                            <option value="">-- Sélectionner un patient --</option>
                            <?php foreach ($patients as $patient): ?>
                                <option value="<?= $patient['id'] ?>" <?= (isset($old['patient_id']) && $old['patient_id'] == $patient['id']) || (isset($ordonnance) && $ordonnance['patient_id'] == $patient['id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($patient['prenom'] . ' ' . $patient['nom']) ?> - <?= htmlspecialchars($patient['email']) ?>
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
                                <option value="<?= $medecin['user_id'] ?>" <?= (isset($old['medecin_id']) && $old['medecin_id'] == $medecin['user_id']) || (isset($ordonnance) && $ordonnance['medecin_id'] == $medecin['user_id']) ? 'selected' : '' ?>>
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
                    <!-- Date prescription -->
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Date de prescription</label>
                        <input type="date" name="date_ordonnance" id="date_ordonnance" class="form-control <?= isset($errors['date_ordonnance']) ? 'error' : '' ?>" 
                               value="<?= htmlspecialchars($old['date_ordonnance'] ?? ($ordonnance['date_ordonnance'] ?? date('Y-m-d'))) ?>">
                        <div class="error-container" id="date_ordonnance-error">
                            <?php if (isset($errors['date_ordonnance'])): ?>
                                <div class="field-error"><i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($errors['date_ordonnance']) ?></div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Date expiration -->
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Date d'expiration</label>
                        <input type="date" name="date_expiration" id="date_expiration" class="form-control <?= isset($errors['date_expiration']) ? 'error' : '' ?>" 
                               value="<?= htmlspecialchars($old['date_expiration'] ?? ($ordonnance['date_expiration'] ?? '')) ?>">
                        <div class="error-container" id="date_expiration-error">
                            <?php if (isset($errors['date_expiration'])): ?>
                                <div class="field-error"><i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($errors['date_expiration']) ?></div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Diagnostic -->
                <div class="mb-3">
                    <label class="form-label">Diagnostic <span class="text-danger">*</span></label>
                    <textarea name="diagnostic" id="diagnostic" class="form-control <?= isset($errors['diagnostic']) ? 'error' : '' ?>" rows="3" 
                              placeholder="Description du diagnostic..."><?= htmlspecialchars($old['diagnostic'] ?? ($ordonnance['diagnostic'] ?? '')) ?></textarea>
                    <div class="error-container" id="diagnostic-error">
                        <?php if (isset($errors['diagnostic'])): ?>
                            <div class="field-error"><i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($errors['diagnostic']) ?></div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Contenu / Médicaments -->
                <div class="mb-3">
                    <label class="form-label">Contenu / Médicaments <span class="text-danger">*</span></label>
                    <textarea name="contenu" id="contenu" class="form-control <?= isset($errors['contenu']) ? 'error' : '' ?>" rows="5" 
                              placeholder="Exemple:&#10;- Paracétamol 500mg, 3x/jour pendant 5 jours&#10;- Amoxicilline 1g, 2x/jour pendant 7 jours"><?= htmlspecialchars($old['contenu'] ?? ($ordonnance['contenu'] ?? '')) ?></textarea>
                    <small class="text-muted">Séparez chaque médicament par une ligne</small>
                    <div class="error-container" id="contenu-error">
                        <?php if (isset($errors['contenu'])): ?>
                            <div class="field-error"><i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($errors['contenu']) ?></div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Statut -->
                <div class="mb-3">
                    <label class="form-label">Statut</label>
                    <select name="status" id="status" class="form-select <?= isset($errors['status']) ? 'error' : '' ?>">
                        <option value="active" <?= (isset($old['status']) && $old['status'] === 'active') || (isset($ordonnance) && $ordonnance['status'] === 'active') ? 'selected' : '' ?>>Active</option>
                        <option value="en_attente" <?= (isset($old['status']) && $old['status'] === 'en_attente') || (isset($ordonnance) && $ordonnance['status'] === 'en_attente') ? 'selected' : '' ?>>En attente</option>
                        <option value="expired" <?= (isset($old['status']) && $old['status'] === 'expired') || (isset($ordonnance) && $ordonnance['status'] === 'expired') ? 'selected' : '' ?>>Expirée</option>
                    </select>
                    <div class="error-container" id="status-error">
                        <?php if (isset($errors['status'])): ?>
                            <div class="field-error"><i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($errors['status']) ?></div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="d-flex justify-content-end gap-2">
                    <a href="index.php?page=ordonnances" class="btn btn-secondary">Annuler</a>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> <?= isset($ordonnance) ? 'Mettre à jour' : 'Créer' ?>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Validation en temps réel
document.getElementById('ordonnanceForm').addEventListener('submit', function(e) {
    let isValid = true;
    
    // Nettoyer les erreurs précédentes
    document.querySelectorAll('.field-error').forEach(el => el.remove());
    document.querySelectorAll('.form-control, .form-select').forEach(el => el.classList.remove('error'));
    
    // Valider patient
    const patient = document.getElementById('patient_id');
    if (!patient.value) {
        showError('patient_id', 'Veuillez sélectionner un patient.');
        isValid = false;
    }
    
    // Valider médecin
    const medecin = document.getElementById('medecin_id');
    if (!medecin.value) {
        showError('medecin_id', 'Veuillez sélectionner un médecin.');
        isValid = false;
    }
    
    // Valider diagnostic
    const diagnostic = document.getElementById('diagnostic');
    if (!diagnostic.value.trim()) {
        showError('diagnostic', 'Le diagnostic est obligatoire.');
        isValid = false;
    } else if (diagnostic.value.trim().length < 5) {
        showError('diagnostic', 'Le diagnostic doit contenir au moins 5 caractères.');
        isValid = false;
    }
    
    // Valider contenu
    const contenu = document.getElementById('contenu');
    if (!contenu.value.trim()) {
        showError('contenu', 'Le contenu / médicaments est obligatoire.');
        isValid = false;
    }
    
    if (!isValid) {
        e.preventDefault();
        // Scroll vers la première erreur
        const firstError = document.querySelector('.field-error');
        if (firstError) firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }
});

function showError(fieldId, message) {
    const field = document.getElementById(fieldId);
    field.classList.add('error');
    
    const errorContainer = document.getElementById(fieldId + '-error');
    const errorDiv = document.createElement('div');
    errorDiv.className = 'field-error';
    errorDiv.innerHTML = '<i class="fas fa-exclamation-circle"></i> ' + message;
    errorContainer.appendChild(errorDiv);
}

// Effacer l'erreur quand l'utilisateur corrige
document.querySelectorAll('#ordonnanceForm select, #ordonnanceForm input, #ordonnanceForm textarea').forEach(field => {
    field.addEventListener('change', function() {
        this.classList.remove('error');
        const errorContainer = document.getElementById(this.id + '-error');
        if (errorContainer) {
            errorContainer.innerHTML = '';
        }
    });
    field.addEventListener('input', function() {
        this.classList.remove('error');
        const errorContainer = document.getElementById(this.id + '-error');
        if (errorContainer) {
            errorContainer.innerHTML = '';
        }
    });
});
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>// update
