<?php
require_once __DIR__ . '/../../models/Event.php';
require_once __DIR__ . '/../../models/Sponsor.php';

$eventModel = new Event();
$sponsorModel = new Sponsor();

$id = isset($_GET['id']) ? (int)$_GET['id'] : null;
$event = null;
$title = 'Nouvel événement';
$errors = isset($_SESSION['errors']) ? $_SESSION['errors'] : [];
$formData = isset($_SESSION['form_data']) ? $_SESSION['form_data'] : [];

if ($id) {
    $event = $eventModel->getById($id);
    if (!$event) {
        echo "<h1>Événement non trouvé</h1>";
        exit;
    }
    $title = 'Modifier l\'événement';
}

$sponsors = $sponsorModel->getAll(0, 100, 'all');

// Nettoyer les erreurs et les données après lecture
unset($_SESSION['errors']);
unset($_SESSION['form_data']);

// Fonction pour afficher les classes Bootstrap d'erreur
function getFieldClasses($fieldName, $errors) {
    return isset($errors[$fieldName]) ? 'form-control is-invalid' : 'form-control';
}

function getSelectClasses($fieldName, $errors) {
    return isset($errors[$fieldName]) ? 'form-select is-invalid' : 'form-select';
}

function getErrorMessage($fieldName, $errors) {
    if (isset($errors[$fieldName])) {
        return '<div class="invalid-feedback d-block"><i class="fas fa-exclamation-circle me-1"></i> ' . htmlspecialchars($errors[$fieldName]) . '</div>';
    }
    return '';
}

function getFieldValue($fieldName, $formData, $event) {
    if (!empty($formData[$fieldName])) {
        return htmlspecialchars($formData[$fieldName]);
    }
    if ($event && isset($event[$fieldName])) {
        return htmlspecialchars($event[$fieldName]);
    }
    return '';
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { background: #f4f6f9; font-family: 'Segoe UI', sans-serif; }
        .main-content { margin-left: 260px; padding: 25px; }
        .form-container { background: white; border-radius: 15px; box-shadow: 0 2px 10px rgba(0,0,0,.05); padding: 30px; }
        .form-section { margin-bottom: 30px; }
        .form-section-title { font-size: 1.1rem; font-weight: 600; color: #1e2a3e; margin-bottom: 20px; padding-bottom: 10px; border-bottom: 2px solid #f0f0f0; }
        .form-label { font-weight: 600; color: #495057; margin-bottom: 8px; }
        .form-control, .form-select { border-radius: 8px; border: 1px solid #ddd; padding: 10px 15px; transition: all 0.3s; }
        .form-control:focus, .form-select:focus { border-color: #17a2b8; box-shadow: 0 0 0 3px rgba(23,162,184,0.1); }
        .form-control.is-invalid, .form-select.is-invalid { border-color: #dc3545; background-color: #fff8f8; }
        .form-control.is-invalid:focus, .form-select.is-invalid:focus { border-color: #dc3545; box-shadow: 0 0 0 3px rgba(220,53,69,0.1); }
        .invalid-feedback { color: #dc3545; font-size: 0.8rem; margin-top: 5px; display: block; }
        .invalid-feedback i { margin-right: 4px; }
        .btn-submit { background: linear-gradient(135deg, #17a2b8 0%, #4CAF50 100%); color: white; border: none; padding: 12px 30px; border-radius: 8px; font-weight: 600; cursor: pointer; transition: all 0.3s; }
        .btn-submit:hover { transform: translateY(-2px); box-shadow: 0 5px 15px rgba(23,162,184,0.3); color: white; }
        .btn-cancel { background: #6c757d; color: white; border: none; padding: 12px 30px; border-radius: 8px; font-weight: 600; cursor: pointer; text-decoration: none; display: inline-block; }
        .btn-cancel:hover { background: #5a6268; color: white; }
        .form-row { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; }
        .required-field::after { content: '*'; color: #dc3545; margin-left: 4px; }
        @media(max-width:768px){ .main-content{ margin-left:0; } }
        .image-preview { margin-top: 15px; text-align: center; }
        .image-preview img { max-width: 200px; border-radius: 8px; border: 1px solid #ddd; padding: 5px; }
    </style>
</head>
<body>
<?php require_once __DIR__ . '/sidebar.php'; ?>

<div class="main-content">

    <!-- En-tête -->
    <div class="mb-4">
        <h4 class="mb-2 fw-bold" style="color:#1e2a3e">
            <i class="bi bi-calendar-event me-2 text-primary"></i><?= $title ?>
        </h4>
        <p class="text-muted small mb-0">Remplissez tous les champs obligatoires <span class="text-danger">*</span></p>
    </div>

    <!-- Message d'erreur général -->
    <?php if (isset($errors['__form'])): ?>
        <div class="alert alert-danger mb-4">
            <i class="fas fa-exclamation-circle me-2"></i>
            <?= htmlspecialchars($errors['__form']) ?>
        </div>
    <?php endif; ?>

    <!-- Formulaire -->
    <div class="form-container">
        <form method="POST" action="index.php?page=evenements_admin&action=<?= $id ? 'edit' : 'create' ?><?= $id ? '&id=' . $id : '' ?>" enctype="multipart/form-data" id="eventForm">

            <!-- Informations de base -->
            <div class="form-section">
                <div class="form-section-title">
                    <i class="bi bi-info-circle me-2"></i>Informations de base
                </div>
                <div class="form-row">
                    <div>
                        <label for="titre" class="form-label required-field">Titre de l'événement</label>
                        <input type="text" id="titre" name="titre" class="<?= getFieldClasses('titre', $errors) ?>" 
                               value="<?= getFieldValue('titre', $formData, $event) ?>">
                        <?= getErrorMessage('titre', $errors) ?>
                        <div class="invalid-feedback-client" id="titre-error" style="display:none; color:#dc3545; font-size:0.8rem; margin-top:5px;"></div>
                    </div>
                    <div>
                        <label for="sponsor_id" class="form-label">Sponsor</label>
                        <select id="sponsor_id" name="sponsor_id" class="<?= getSelectClasses('sponsor_id', $errors) ?>">
                            <option value="">-- Aucun sponsor --</option>
                            <?php foreach ($sponsors as $sp): ?>
                                <option value="<?= $sp['id'] ?>" 
                                    <?= (($formData['sponsor_id'] ?? $event['sponsor_id'] ?? '') == $sp['id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($sp['nom']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <?= getErrorMessage('sponsor_id', $errors) ?>
                    </div>
                </div>
                <div style="margin-top: 20px;">
                    <label for="description" class="form-label required-field">Description</label>
                    <textarea id="description" name="description" class="<?= getFieldClasses('description', $errors) ?>" rows="3"><?= getFieldValue('description', $formData, $event) ?></textarea>
                    <?= getErrorMessage('description', $errors) ?>
                    <div class="invalid-feedback-client" id="description-error" style="display:none; color:#dc3545; font-size:0.8rem; margin-top:5px;"></div>
                </div>
            </div>

            <!-- Dates et lieu -->
            <div class="form-section">
                <div class="form-section-title">
                    <i class="bi bi-calendar-event me-2"></i>Dates et lieu
                </div>
                <div class="form-row">
                    <div>
                        <label for="date_debut" class="form-label required-field">Date de début</label>
                        <input type="datetime-local" id="date_debut" name="date_debut" class="<?= getFieldClasses('date_debut', $errors) ?>" 
                               value="<?= $formData['date_debut'] ?? ($event ? date('Y-m-d\TH:i', strtotime($event['date_debut'])) : '') ?>">
                        <?= getErrorMessage('date_debut', $errors) ?>
                        <div class="invalid-feedback-client" id="date_debut-error" style="display:none; color:#dc3545; font-size:0.8rem; margin-top:5px;"></div>
                    </div>
                    <div>
                        <label for="date_fin" class="form-label required-field">Date de fin</label>
                        <input type="datetime-local" id="date_fin" name="date_fin" class="<?= getFieldClasses('date_fin', $errors) ?>" 
                               value="<?= $formData['date_fin'] ?? ($event ? date('Y-m-d\TH:i', strtotime($event['date_fin'])) : '') ?>">
                        <?= getErrorMessage('date_fin', $errors) ?>
                        <div class="invalid-feedback-client" id="date_fin-error" style="display:none; color:#dc3545; font-size:0.8rem; margin-top:5px;"></div>
                    </div>
                </div>
                <div class="form-row" style="margin-top: 20px;">
                    <div>
                        <label for="lieu" class="form-label required-field">Lieu</label>
                        <input type="text" id="lieu" name="lieu" class="<?= getFieldClasses('lieu', $errors) ?>" 
                               value="<?= getFieldValue('lieu', $formData, $event) ?>">
                        <?= getErrorMessage('lieu', $errors) ?>
                        <div class="invalid-feedback-client" id="lieu-error" style="display:none; color:#dc3545; font-size:0.8rem; margin-top:5px;"></div>
                    </div>
                    <div>
                        <label for="adresse" class="form-label">Adresse complète</label>
                        <input type="text" id="adresse" name="adresse" class="<?= getFieldClasses('adresse', $errors) ?>" 
                               value="<?= getFieldValue('adresse', $formData, $event) ?>">
                        <?= getErrorMessage('adresse', $errors) ?>
                    </div>
                </div>
            </div>

            <!-- Détails pratiques -->
            <div class="form-section">
                <div class="form-section-title">
                    <i class="bi bi-gear me-2"></i>Détails pratiques
                </div>
                <div class="form-row">
                    <div>
                        <label for="capacite_max" class="form-label required-field">Capacité maximale</label>
                        <input type="number" id="capacite_max" name="capacite_max" class="<?= getFieldClasses('capacite_max', $errors) ?>" 
                               value="<?= getFieldValue('capacite_max', $formData, $event) ?>">
                        <?= getErrorMessage('capacite_max', $errors) ?>
                        <div class="invalid-feedback-client" id="capacite_max-error" style="display:none; color:#dc3545; font-size:0.8rem; margin-top:5px;"></div>
                    </div>
                    <div>
                        <label for="prix" class="form-label">Prix (TND)</label>
                        <input type="number" id="prix" name="prix" class="<?= getFieldClasses('prix', $errors) ?>" step="0.01" 
                               value="<?= getFieldValue('prix', $formData, $event) ?: '0' ?>">
                        <?= getErrorMessage('prix', $errors) ?>
                        <div class="invalid-feedback-client" id="prix-error" style="display:none; color:#dc3545; font-size:0.8rem; margin-top:5px;"></div>
                    </div>
                    <div>
                        <label for="status" class="form-label required-field">Statut</label>
                        <select id="status" name="status" class="<?= getSelectClasses('status', $errors) ?>">
                            <option value="à venir" <?= (($formData['status'] ?? $event['status'] ?? '') === 'à venir') ? 'selected' : '' ?>>À venir</option>
                            <option value="en_cours" <?= (($formData['status'] ?? $event['status'] ?? '') === 'en_cours') ? 'selected' : '' ?>>En cours</option>
                            <option value="terminé" <?= (($formData['status'] ?? $event['status'] ?? '') === 'terminé') ? 'selected' : '' ?>>Terminé</option>
                            <option value="annulé" <?= (($formData['status'] ?? $event['status'] ?? '') === 'annulé') ? 'selected' : '' ?>>Annulé</option>
                        </select>
                        <?= getErrorMessage('status', $errors) ?>
                    </div>
                </div>
            </div>

            <!-- Image -->
            <div class="form-section">
                <div class="form-section-title">
                    <i class="bi bi-image me-2"></i>Image
                </div>
                <?php if ($event && !empty($event['image'])): ?>
                    <div class="image-preview" id="currentImagePreview">
                        <img src="<?= htmlspecialchars($event['image']) ?>" alt="Image de l'événement">
                        <div class="mt-2">
                            <label class="form-check-label">
                                <input type="checkbox" name="delete_image" value="1" class="form-check-input"> Supprimer l'image actuelle
                            </label>
                        </div>
                    </div>
                <?php endif; ?>
                <div class="image-preview" id="newImagePreview" style="display:none;"></div>
                <div style="margin-top: <?= ($event && !empty($event['image'])) ? '15px' : '0' ?>;">
                    <label for="image" class="form-label">Télécharger une image</label>
                    <input type="file" id="image" name="image" class="<?= getFieldClasses('image', $errors) ?>" accept="image/*" onchange="previewImage(this)">
                    <?= getErrorMessage('image', $errors) ?>
                    <small class="text-muted d-block mt-1">Formats acceptés : JPG, PNG, GIF. Max 2 Mo.</small>
                </div>
            </div>

            <!-- Boutons -->
            <div style="display: flex; gap: 15px; justify-content: flex-end; margin-top: 30px;">
                <a href="index.php?page=evenements_admin" class="btn-cancel">
                    <i class="bi bi-x-circle me-1"></i>Annuler
                </a>
                <button type="submit" class="btn-submit">
                    <i class="bi bi-check-circle me-1"></i><?= $id ? 'Mettre à jour' : 'Créer l\'événement' ?>
                </button>
            </div>

        </form>
    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Aperçu de l'image
function previewImage(input) {
    const preview = document.getElementById('newImagePreview');
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            preview.innerHTML = '<img src="' + e.target.result + '" alt="Aperçu" style="max-width:200px; border-radius:8px; border:1px solid #ddd; padding:5px;">';
            preview.style.display = 'block';
        }
        reader.readAsDataURL(input.files[0]);
    } else {
        preview.style.display = 'none';
    }
}

// Nettoyer les erreurs quand l'utilisateur corrige
function clearFieldError(fieldId) {
    const field = document.getElementById(fieldId);
    if (field) {
        field.classList.remove('is-invalid');
        const serverError = field.parentElement.querySelector('.invalid-feedback');
        if (serverError) serverError.style.display = 'none';
        const clientError = document.getElementById(fieldId + '-error');
        if (clientError) clientError.style.display = 'none';
    }
}

// Ajouter des écouteurs d'événements pour nettoyer les erreurs
document.querySelectorAll('#titre, #description, #date_debut, #date_fin, #lieu, #capacite_max, #prix').forEach(field => {
    if (field) {
        field.addEventListener('input', function() { clearFieldError(this.id); });
        field.addEventListener('change', function() { clearFieldError(this.id); });
    }
});

// Validation client avant soumission
document.getElementById('eventForm').addEventListener('submit', function(e) {
    let isValid = true;
    
    // Nettoyer les anciennes erreurs client
    document.querySelectorAll('.invalid-feedback-client').forEach(el => el.style.display = 'none');
    
    // Validation du titre
    const titre = document.getElementById('titre');
    if (!titre.value.trim()) {
        document.getElementById('titre-error').innerHTML = '<i class="fas fa-exclamation-circle me-1"></i> Le titre est obligatoire.';
        document.getElementById('titre-error').style.display = 'block';
        titre.classList.add('is-invalid');
        isValid = false;
    }
    
    // Validation de la description
    const description = document.getElementById('description');
    if (!description.value.trim()) {
        document.getElementById('description-error').innerHTML = '<i class="fas fa-exclamation-circle me-1"></i> La description est obligatoire.';
        document.getElementById('description-error').style.display = 'block';
        description.classList.add('is-invalid');
        isValid = false;
    }
    
    // Validation de la date de début
    const dateDebut = document.getElementById('date_debut');
    if (!dateDebut.value) {
        document.getElementById('date_debut-error').innerHTML = '<i class="fas fa-exclamation-circle me-1"></i> La date de début est obligatoire.';
        document.getElementById('date_debut-error').style.display = 'block';
        dateDebut.classList.add('is-invalid');
        isValid = false;
    }
    
    // Validation de la date de fin
    const dateFin = document.getElementById('date_fin');
    if (!dateFin.value) {
        document.getElementById('date_fin-error').innerHTML = '<i class="fas fa-exclamation-circle me-1"></i> La date de fin est obligatoire.';
        document.getElementById('date_fin-error').style.display = 'block';
        dateFin.classList.add('is-invalid');
        isValid = false;
    }
    
    // Vérifier que date_fin >= date_debut
    if (dateDebut.value && dateFin.value && dateFin.value < dateDebut.value) {
        document.getElementById('date_fin-error').innerHTML = '<i class="fas fa-exclamation-circle me-1"></i> La date de fin doit être après la date de début.';
        document.getElementById('date_fin-error').style.display = 'block';
        dateFin.classList.add('is-invalid');
        isValid = false;
    }
    
    // Validation du lieu
    const lieu = document.getElementById('lieu');
    if (!lieu.value.trim()) {
        document.getElementById('lieu-error').innerHTML = '<i class="fas fa-exclamation-circle me-1"></i> Le lieu est obligatoire.';
        document.getElementById('lieu-error').style.display = 'block';
        lieu.classList.add('is-invalid');
        isValid = false;
    }
    
    // Validation de la capacité
    const capacite = document.getElementById('capacite_max');
    if (!capacite.value) {
        document.getElementById('capacite_max-error').innerHTML = '<i class="fas fa-exclamation-circle me-1"></i> La capacité maximale est obligatoire.';
        document.getElementById('capacite_max-error').style.display = 'block';
        capacite.classList.add('is-invalid');
        isValid = false;
    } else if (parseInt(capacite.value) <= 0) {
        document.getElementById('capacite_max-error').innerHTML = '<i class="fas fa-exclamation-circle me-1"></i> La capacité doit être supérieure à 0.';
        document.getElementById('capacite_max-error').style.display = 'block';
        capacite.classList.add('is-invalid');
        isValid = false;
    }
    
    // Validation du prix
    const prix = document.getElementById('prix');
    if (prix.value && parseFloat(prix.value) < 0) {
        document.getElementById('prix-error').innerHTML = '<i class="fas fa-exclamation-circle me-1"></i> Le prix ne peut pas être négatif.';
        document.getElementById('prix-error').style.display = 'block';
        prix.classList.add('is-invalid');
        isValid = false;
    }
    
    if (!isValid) {
        e.preventDefault();
        // Scroll vers le premier champ en erreur
        const firstError = document.querySelector('.is-invalid');
        if (firstError) firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }
});
</script>
</body>
</html>