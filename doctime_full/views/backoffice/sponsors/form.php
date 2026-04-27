<?php
$current_page = 'sponsors';
$isEdit = false;
$sponsor = [];

if (isset($_GET['action']) && $_GET['action'] === 'edit') {
    $isEdit = true;
    if (isset($GLOBALS['sponsor'])) {
        $sponsor = $GLOBALS['sponsor'];
    } elseif (isset($sponsor)) {
        // already provided
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $isEdit ? 'Modifier' : 'Ajouter' ?> un Sponsor - DocTime</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        :root {
            --green:         #4CAF50;
            --navy:          #1a2035;
            --teal:          #0fa99b;
            --teal-dark:     #0d8a7d;
            --red:           #ef4444;
            --gray-50:       #f8fafc;
            --gray-200:      #e2e8f0;
            --gray-700:      #334155;
            --gray-900:      #0f172a;
            --shadow:        0 1px 6px rgba(0,0,0,.07);
            --radius:        12px;
        }
        body {
            font-family: 'Segoe UI', system-ui, sans-serif;
            background: var(--gray-50);
            color: var(--gray-900);
            min-height: 100vh;
            display: flex;
        }

        .main-content { margin-left: 260px; flex: 1; display: flex; flex-direction: column; }
        .page-header { padding: 30px 30px 10px; display: flex; justify-content: space-between; align-items: center; }
        .page-header h4 { font-size: 22px; font-weight: 600; color: var(--navy); display: flex; align-items: center; gap: 10px; margin: 0; }
        .page-header h4 i { color: var(--teal); }

        .content-card {
            background: #fff; border-radius: var(--radius); box-shadow: var(--shadow);
            padding: 30px; margin: 20px 30px; max-width: 900px;
        }

        .form-section { margin-bottom: 30px; }
        .form-section h5 { font-size: 16px; font-weight: 600; color: var(--navy); margin-bottom: 20px; border-bottom: 2px solid var(--gray-200); padding-bottom: 8px; }

        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px; }
        
        .form-group label { display: block; font-weight: 600; margin-bottom: 8px; font-size: 13px; color: var(--gray-700); text-transform: uppercase; letter-spacing: 0.5px; }
        
        .form-group input, .form-group select, .form-group textarea {
            width: 100%; padding: 12px 14px; border: 1.5px solid var(--gray-200); border-radius: 8px; font-size: 14px; transition: all 0.2s;
        }
        .form-group input:focus, .form-group select:focus, .form-group textarea:focus {
            border-color: var(--teal); outline: none; box-shadow: 0 0 0 3px rgba(15, 169, 155, 0.1);
        }

        .is-invalid { border-color: var(--red) !important; box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.1) !important; }
        .error-text { color: var(--red); font-size: 12px; margin-top: 5px; display: none; font-weight: 500; }

        .action-buttons { display: flex; gap: 12px; margin-top: 30px; padding-top: 20px; border-top: 1px solid var(--gray-200); }
        
        .btn { display: inline-flex; align-items: center; gap: 8px; padding: 12px 24px; border-radius: 8px; font-size: 14px; font-weight: 600; cursor: pointer; border: none; text-decoration: none; transition: all 0.2s; }
        .btn-primary { background: var(--teal); color: white; }
        .btn-primary:hover { background: var(--teal-dark); transform: translateY(-1px); }
        .btn-secondary { background: var(--gray-200); color: var(--gray-700); }
        .btn-secondary:hover { background: #cbd5e1; }

        .flash-box { border-radius: 8px; padding: 16px; margin: 0 30px 20px; font-size: 14px; }
        .flash-error { background: #fdecea; color: #c62828; border: 1px solid #f9a9a3; }
    </style>
</head>
<body>

<?php include __DIR__ . '/../sidebar.php'; ?>

<div class="main-content">
    <div class="page-header">
        <h4>
            <i class="fas fa-<?= $isEdit ? 'pen' : 'plus' ?>"></i> 
            <?= $isEdit ? 'Modifier le Sponsor' : 'Nouveau Sponsor' ?>
        </h4>
        <a href="index.php?page=sponsors" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Retour</a>
    </div>

    <?php if (isset($_SESSION['flash']) && $_SESSION['flash']['type'] === 'error'): ?>
        <div class="flash-box flash-error">
            <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($_SESSION['flash']['message']) ?>
        </div>
    <?php endif; ?>

    <div class="content-card">
        <form method="POST" id="sponsorForm" action="index.php?page=sponsors&action=<?= $isEdit ? 'edit&id='.$sponsor['id'] : 'create' ?>" novalidate>
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">

            <div class="form-section">
                <h5>Informations Générales</h5>
                <div class="form-row">
                    <div class="form-group">
                        <label>Nom du Sponsor *</label>
                        <input type="text" name="nom" id="nom" value="<?= htmlspecialchars($old['nom'] ?? $sponsor['nom'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label>Email Contact *</label>
                        <input type="email" name="email" id="email" value="<?= htmlspecialchars($old['email'] ?? $sponsor['email'] ?? '') ?>">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Téléphone *</label>
                        <input type="text" id="telephone" name="telephone" value="<?= htmlspecialchars($old['telephone'] ?? $sponsor['telephone'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label>Site Web</label>
                        <input type="url" id="site_web" name="site_web" placeholder="https://" value="<?= htmlspecialchars($old['site_web'] ?? $sponsor['site_web'] ?? '') ?>">
                    </div>
                </div>
            </div>

            <div class="form-section">
                <h5>Parrainage</h5>
                <div class="form-row">
                    <div class="form-group">
                        <label>Niveau de Sponsoring *</label>
                        <select name="niveau" id="niveau">
                            <?php $niv = strtolower($old['niveau'] ?? $sponsor['niveau'] ?? 'argent'); ?>
                            <option value="Bronze" <?= $niv === 'bronze' ? 'selected' : '' ?>>Bronze</option>
                            <option value="Argent" <?= $niv === 'argent' ? 'selected' : '' ?>>Argent</option>
                            <option value="Or" <?= $niv === 'or' ? 'selected' : '' ?>>Or</option>
                            <option value="Platine" <?= $niv === 'platine' ? 'selected' : '' ?>>Platine</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Montant / Budget (TND) *</label>
                        <input type="number" id="budget" step="0.01" name="budget" value="<?= htmlspecialchars($old['budget'] ?? $sponsor['budget'] ?? '0') ?>">
                    </div>
                </div>
                
                <div class="form-row" style="grid-template-columns: auto;">
                    <div class="form-group">
                        <label>Description *</label>
                        <textarea name="description" id="description" rows="3"><?= htmlspecialchars($old['description'] ?? $sponsor['description'] ?? '') ?></textarea>
                    </div>
                </div>
            </div>

            <div class="action-buttons">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> <?= $isEdit ? 'Enregistrer les modifications' : 'Créer le sponsor' ?>
                </button>
                <a href="index.php?page=sponsors" class="btn btn-secondary">Annuler</a>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('sponsorForm');

    form.addEventListener('submit', function(e) {
        let valid = true;
        
        document.querySelectorAll('.error-text').forEach(el => el.remove());
        document.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));

        function showError(inputId, message) {
            const input = document.getElementById(inputId);
            if (!input) return;
            input.classList.add('is-invalid');
            const errorDiv = document.createElement('div');
            errorDiv.className = 'error-text';
            errorDiv.innerHTML = '<i class="fas fa-exclamation-circle"></i> ' + message;
            errorDiv.style.display = 'block';
            input.parentNode.appendChild(errorDiv);
            valid = false;
        }

        const nom = document.getElementById('nom').value.trim();
        if (!nom) { showError('nom', 'Le nom est obligatoire.'); } 
        else if (nom.length < 2) { showError('nom', 'Le nom doit contenir au moins 2 caractères.'); }

        const email = document.getElementById('email').value.trim();
        if (!email || !email.includes('@')) { showError('email', 'Un email valide est obligatoire.'); }

        const telephone = document.getElementById('telephone').value.trim();
        if (!telephone) { showError('telephone', 'Le téléphone est obligatoire.'); }
        else if (telephone.length < 8) { showError('telephone', 'Le téléphone doit contenir au moins 8 chiffres.'); }

        const niveau = document.getElementById('niveau').value;
        if (!niveau) { showError('niveau', 'Le niveau est obligatoire.'); }

        const budget = document.getElementById('budget').value;
        if (budget === '' || parseFloat(budget) <= 0) { showError('budget', 'Le montant/budget doit être un nombre positif.'); }

        const siteWeb = document.getElementById('site_web').value.trim();
        if (siteWeb && !siteWeb.startsWith('http')) { showError('site_web', 'Le site web doit commencer par http:// ou https://'); }

        const description = document.getElementById('description').value.trim();
        if (!description) { showError('description', 'La description est obligatoire.'); }

        if (!valid) e.preventDefault();
    });
});
</script>

</body>
</html>
