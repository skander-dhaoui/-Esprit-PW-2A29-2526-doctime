<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../models/Sponsor.php';

// Récupérer le mode et les données depuis SESSION
$formMode = $_SESSION['sponsor_form_mode'] ?? 'create';
$sponsor = $_SESSION['sponsor_form_data'] ?? null;
$sponsorId = $_SESSION['sponsor_form_id'] ?? null;

// Si pas de données en SESSION mais on a un ID en GET, charger directement
if (empty($sponsor) && isset($_GET['id'])) {
    $sponsorModel = new Sponsor();
    $sponsor = $sponsorModel->getById((int)$_GET['id']);
    if ($sponsor) {
        $formMode = 'edit';
        $sponsorId = (int)$_GET['id'];
    }
}

$isEdit = ($formMode === 'edit' && !empty($sponsor));

// Titre
$title = $isEdit ? "Modifier Sponsor" : "Nouveau Sponsor";

// Erreurs et anciennes données
$errors = $_SESSION['errors'] ?? [];
$formData = $_SESSION['form_data'] ?? [];

// Générer le CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrfToken = $_SESSION['csrf_token'];

// Fonctions utilitaires
function getFieldValue($fieldName, $sponsor = null, $formData = []) {
    if (!empty($formData) && isset($formData[$fieldName])) {
        return htmlspecialchars($formData[$fieldName]);
    }
    if ($sponsor && isset($sponsor[$fieldName])) {
        return htmlspecialchars($sponsor[$fieldName]);
    }
    return '';
}

function getFieldClasses($fieldName, $errors) {
    return isset($errors[$fieldName]) ? 'form-control is-invalid' : 'form-control';
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { background: #f0f4f8; font-family: 'Segoe UI', sans-serif; }
        .main-content { margin-left: 260px; padding: 30px; }
        
        .page-header { 
            display: flex; 
            justify-content: space-between; 
            align-items: center; 
            margin-bottom: 30px; 
        }
        .page-title { 
            font-size: 1.8rem; 
            font-weight: 700; 
            color: #2c3e50;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .page-title i { color: #17a2b8; }
        
        .form-container { 
            background: white; 
            border-radius: 8px; 
            box-shadow: 0 2px 10px rgba(0,0,0,0.08); 
            padding: 30px;
            max-width: 800px;
        }
        
        .form-group { margin-bottom: 20px; }
        .form-label { 
            font-weight: 600; 
            color: #2c3e50; 
            margin-bottom: 8px; 
            display: block;
        }
        .form-control, .form-select { 
            border: 1px solid #e0e0e0; 
            border-radius: 6px; 
            padding: 10px 12px;
            font-size: 0.95rem;
        }
        .form-control:focus, .form-select:focus { 
            border-color: #17a2b8; 
            box-shadow: 0 0 0 3px rgba(23,162,184,0.1);
        }
        
        .error-message { 
            color: #d32f2f; 
            font-size: 0.85rem; 
            margin-top: 5px; 
            display: block;
        }
        
        .btn-submit { 
            background: linear-gradient(135deg, #17a2b8, #4CAF50); 
            color: white; 
            border: none; 
            padding: 10px 25px; 
            border-radius: 6px; 
            font-weight: 600; 
            cursor: pointer;
            transition: all 0.3s;
        }
        .btn-submit:hover { 
            transform: translateY(-2px); 
            box-shadow: 0 4px 12px rgba(23,162,184,0.3);
            color: white;
        }
        
        .btn-cancel { 
            background: #e0e0e0; 
            color: #2c3e50; 
            border: none; 
            padding: 10px 25px; 
            border-radius: 6px; 
            font-weight: 600; 
            cursor: pointer;
            text-decoration: none;
            transition: all 0.3s;
        }
        .btn-cancel:hover { 
            background: #bdbdbd; 
            color: #2c3e50;
        }
        
        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        
        @media(max-width:768px){ 
            .main-content{ margin-left:0; } 
            .form-control.is-invalid { 
            border-color: #d32f2f; 
        }
        
        .form-actions { flex-direction: column; }
            .form-row { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
<?php require_once __DIR__ . '/sidebar.php'; ?>

<div class="main-content">
    
    <!-- Header -->
    <div class="page-header">
        <h1 class="page-title">
            <i class="bi bi-handshake"></i><?= $title ?>
        </h1>
    </div>

    <!-- Formulaire -->
    <div class="form-container">
        <form method="POST" action="index.php?page=sponsors_admin&action=<?= $isEdit && $sponsor ? 'update&id=' . $sponsor['id'] : 'create' ?>" novalidate>
            
            <!-- CSRF Token -->
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken ?? '') ?>">
            
            <?php if ($isEdit && $sponsor): ?>
                <input type="hidden" name="id" value="<?= $sponsor['id'] ?>">
            <?php endif; ?>

            <!-- Informations du sponsor -->
            <h5 style="margin-bottom: 20px; color: #2c3e50; font-weight: 600;">Informations générales</h5>

            <!-- Nom -->
            <div class="form-group">
                <label class="form-label" for="nom">Nom du sponsor *</label>
                <input type="text" class="<?= getFieldClasses('nom', $errors) ?>" id="nom" name="nom" value="<?= getFieldValue('nom', $sponsor, $formData) ?>" required>
                <?php if (!empty($errors['nom'])): ?>
                    <span class="error-message"><?= htmlspecialchars($errors['nom']) ?></span>
                <?php endif; ?>
            </div>

            <!-- Secteur et Budget -->
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label" for="secteur">Secteur *</label>
                    <input type="text" class="<?= getFieldClasses('secteur', $errors) ?>" id="secteur" name="secteur" placeholder="Ex: Pharmatech, Biotech..." value="<?= getFieldValue('secteur', $sponsor, $formData) ?>" required>
                    <?php if (!empty($errors['secteur'])): ?>
                        <span class="error-message"><?= htmlspecialchars($errors['secteur']) ?></span>
                    <?php endif; ?>
                </div>
                <div class="form-group">
                    <label class="form-label" for="budget">Budget (€) *</label>
                    <input type="number" class="<?= getFieldClasses('budget', $errors) ?>" id="budget" name="budget" min="0" step="0.01" value="<?= getFieldValue('budget', $sponsor, $formData) ?>" required>
                    <?php if (!empty($errors['budget'])): ?>
                        <span class="error-message"><?= htmlspecialchars($errors['budget']) ?></span>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Email et Téléphone -->
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label" for="email">Email *</label>
                    <input type="email" class="<?= getFieldClasses('email', $errors) ?>" id="email" name="email" value="<?= getFieldValue('email', $sponsor, $formData) ?>" required>
                    <?php if (!empty($errors['email'])): ?>
                        <span class="error-message"><?= htmlspecialchars($errors['email']) ?></span>
                    <?php endif; ?>
                </div>
                <div class="form-group">
                    <label class="form-label" for="telephone">Téléphone *</label>
                    <input type="tel" class="<?= getFieldClasses('telephone', $errors) ?>" id="telephone" name="telephone" value="<?= getFieldValue('telephone', $sponsor, $formData) ?>" required>
                    <?php if (!empty($errors['telephone'])): ?>
                        <span class="error-message"><?= htmlspecialchars($errors['telephone']) ?></span>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Site Web -->
            <div class="form-group">
                <label class="form-label" for="site_web">Site Web</label>
                <input type="url" class="form-control" id="site_web" name="site_web" placeholder="https://..." value="<?= $isEdit && $sponsor ? htmlspecialchars($sponsor['site_web']) : '' ?>">
            </div>

            <!-- Description -->
            <div class="form-group">
                <label class="form-label" for="description">Description</label>
                <textarea class="form-control" id="description" name="description" rows="3"><?= $isEdit && $sponsor ? htmlspecialchars($sponsor['description']) : '' ?></textarea>
            </div>

            <hr style="margin: 30px 0;">

            <!-- Informations de contact -->
            <h5 style="margin-bottom: 20px; color: #2c3e50; font-weight: 600;">Contact principal</h5>

            <!-- Contact Nom et Prénom -->
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label" for="contact_nom">Nom du contact</label>
                    <input type="text" class="form-control" id="contact_nom" name="contact_nom" value="<?= $isEdit && $sponsor ? htmlspecialchars($sponsor['contact_nom']) : '' ?>">
                </div>
                <div class="form-group">
                    <label class="form-label" for="contact_prenom">Prénom du contact</label>
                    <input type="text" class="form-control" id="contact_prenom" name="contact_prenom" value="<?= $isEdit && $sponsor ? htmlspecialchars($sponsor['contact_prenom']) : '' ?>">
                </div>
            </div>

            <!-- Contact Email et Téléphone -->
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label" for="contact_email">Email du contact</label>
                    <input type="email" class="form-control" id="contact_email" name="contact_email" value="<?= $isEdit && $sponsor ? htmlspecialchars($sponsor['contact_email']) : '' ?>">
                </div>
                <div class="form-group">
                    <label class="form-label" for="contact_telephone">Téléphone du contact</label>
                    <input type="tel" class="form-control" id="contact_telephone" name="contact_telephone" value="<?= $isEdit && $sponsor ? htmlspecialchars($sponsor['contact_telephone']) : '' ?>">
                </div>
            </div>

            <hr style="margin: 30px 0;">

            <!-- Statut -->
            <h5 style="margin-bottom: 20px; color: #2c3e50; font-weight: 600;">Statut</h5>

            <div class="form-group">
                <label class="form-label" for="statut">Statut *</label>
                <select class="form-select" id="statut" name="statut" required>
                    <option value="actif" <?= (!$isEdit || ($sponsor && $sponsor['statut'] === 'actif')) ? 'selected' : '' ?>>Actif</option>
                    <option value="inactif" <?= ($isEdit && $sponsor && $sponsor['statut'] === 'inactif') ? 'selected' : '' ?>>Inactif</option>
                </select>
            </div>

            <!-- Notes -->
            <div class="form-group">
                <label class="form-label" for="notes">Notes</label>
                <textarea class="form-control" id="notes" name="notes" rows="2"><?= $isEdit && $sponsor ? htmlspecialchars($sponsor['notes']) : '' ?></textarea>
            </div>

            <!-- Actions -->
            <div class="form-actions">
                <a href="index.php?page=sponsors_admin" class="btn-cancel">
                    <i class="bi bi-x"></i> Annuler
                </a>
                <button type="submit" class="btn-submit">
                    <i class="bi bi-check"></i> <?= ($isEdit && $sponsor) ? 'Modifier' : 'Ajouter' ?>
                </button>
            </div>
            
            <!-- DEBUG INFO -->
            <hr style="margin-top: 40px; border-top: 1px solid #ccc;">
            <div style="background: #f5f5f5; padding: 10px; border-radius: 4px; font-size: 11px; color: #666;">
                <strong>DEBUG:</strong><br>
                formMode: <?= htmlspecialchars($formMode) ?><br>
                isEdit: <?= $isEdit ? 'TRUE' : 'FALSE' ?><br>
                sponsor empty: <?= empty($sponsor) ? 'TRUE' : 'FALSE' ?><br>
                title: <?= htmlspecialchars($title) ?>
            </div>

        </form>
    </div>

</div>

<?php
// Nettoyer les variables de session après affichage
unset($_SESSION['sponsor_form_mode']);
unset($_SESSION['sponsor_form_data']);
unset($_SESSION['sponsor_form_id']);
unset($_SESSION['errors']);
unset($_SESSION['form_data']);
?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?php
// Nettoyer les erreurs et anciennes données après affichage
unset($_SESSION['errors']);
unset($_SESSION['form_data']);
?>
