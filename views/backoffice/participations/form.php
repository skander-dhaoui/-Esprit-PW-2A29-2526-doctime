<?php
$current_page = 'participations';
$isEdit = $isEdit ?? false;
$participation = $participation ?? null;
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $isEdit ? 'Modifier' : 'Ajouter' ?> une participation - MediConnect</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        :root {
            --green:         #4CAF50;
            --green-dark:    #388E3C;
            --navy:          #1a2035;
            --teal:          #0fa99b;
            --teal-dark:     #0d8a7d;
            --red:           #ef4444;
            --gray-50:       #f8fafc;
            --gray-100:      #f1f5f9;
            --gray-200:      #e2e8f0;
            --gray-500:      #64748b;
            --gray-700:      #334155;
            --gray-900:      #0f172a;
            --white:         #ffffff;
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

        .page-header h4 i { color: var(--teal); }

        .content-card {
            background: var(--white);
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            padding: 24px;
            margin-top: 25px;
            max-width: 800px;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 20px;
        }

        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            font-weight: 600;
            margin-bottom: 8px;
            font-size: 14px;
            color: var(--navy);
        }

        .form-group select, .form-group input, .form-group textarea {
            width: 100%;
            padding: 10px 14px;
            border: 1.5px solid var(--gray-200);
            border-radius: 8px;
            font-size: 14px;
            font-family: inherit;
            transition: all 0.2s;
        }

        .form-group select:focus, .form-group input:focus, .form-group textarea:focus {
            border-color: var(--teal);
            outline: none;
            box-shadow: 0 0 0 3px rgba(15, 169, 155, 0.1);
        }

        .form-group select:disabled {
            background: var(--gray-50);
            color: var(--gray-500);
            cursor: not-allowed;
        }

        .action-buttons {
            display: flex;
            gap: 12px;
            margin-top: 30px;
        }

        .btn {
            display: inline-flex; align-items: center; gap: 8px;
            padding: 10px 20px; border-radius: 8px;
            font-size: 14px; font-weight: 600;
            cursor: pointer; border: none; text-decoration: none; transition: all 0.2s;
        }

        .btn-primary { background: var(--teal); color: white; }
        .btn-primary:hover { background: var(--teal-dark); transform: translateY(-1px); }

        .btn-secondary { background: var(--gray-200); color: var(--gray-700); }
        .btn-secondary:hover { background: var(--gray-300); }

        .flash-box {
            border-radius: 8px; padding: 12px 16px; margin-bottom: 20px; font-size: 14px;
        }
        .flash-error { background: #fdecea; color: #c62828; }

        .is-invalid {
            border-color: var(--red) !important;
            box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.1) !important;
        }
        .error-text {
            color: var(--red);
            font-size: 12px;
            margin-top: 5px;
            display: none;
            font-weight: 500;
        }
    </style>
</head>
<body>

<?php include __DIR__ . '/../sidebar.php'; ?>

<div class="main-content">
    <div class="page-header">
        <h4>
            <i class="fas fa-<?= $isEdit ? 'pen' : 'plus' ?>"></i> 
            <?= $isEdit ? 'Modifier la' : 'Ajouter une' ?> participation
        </h4>
        <div class="header-right">
            <a href="index.php?page=participations" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Retour
            </a>
        </div>
    </div>

    <?php if (isset($flash['error'])): ?>
        <div class="flash-box flash-error">
            <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($flash['error']) ?>
        </div>
    <?php endif; ?>

    <div class="content-card">
        <form method="POST" id="participationForm" action="index.php?page=participations&action=<?= $isEdit ? 'edit&id='.$participation['id'] : 'create' ?>" novalidate>
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken ?? '') ?>">
            
            <div class="form-row">
                <div class="form-group">
                    <label>Événement *</label>
                    <select name="event_id" id="event_id">
                        <option value="">Sélectionnez un événement</option>
                        <?php foreach($events as $e): ?>
                            <option value="<?= $e['id'] ?>" <?= ($old['event_id'] ?? $participation['event_id'] ?? '') == $e['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($e['titre']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            
            <?php if ($isEdit): ?>
            <div class="form-row">
                <div class="form-group" style="grid-column: span 2;">
                    <label>Participant (Non modifiable)</label>
                    <input type="text" disabled value="<?= htmlspecialchars(($participation['user_prenom'] ?? '') . ' ' . ($participation['user_nom'] ?? '')) ?>">
                </div>
            </div>
            <?php else: ?>
            <div class="form-row">
                <div class="form-group">
                    <label>Prénom *</label>
                    <input type="text" name="prenom" id="prenom" placeholder="Prénom">
                </div>
                <div class="form-group">
                    <label>Nom *</label>
                    <input type="text" name="nom" id="nom" placeholder="Nom">
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Email *</label>
                    <input type="email" name="email" id="email" placeholder="Email">
                </div>
                <div class="form-group">
                    <label>Téléphone</label>
                    <input type="text" name="telephone" id="telephone" placeholder="Téléphone">
                </div>
            </div>
            <?php endif; ?>

            <div class="form-row" style="display: block;">
                <div class="form-group">
                    <label>Statut *</label>
                    <select name="statut" id="statut">
                        <?php $statut = $old['statut'] ?? $participation['statut'] ?? ''; ?>
                        <option value="inscrit" <?= $statut === 'inscrit' ? 'selected' : '' ?>>Inscrit</option>
                        <option value="confirmé" <?= $statut === 'confirmé' ? 'selected' : '' ?>>Confirmé</option>
                        <option value="en attente" <?= $statut === 'en attente' ? 'selected' : '' ?>>En attente</option>
                        <option value="présent" <?= $statut === 'présent' ? 'selected' : '' ?>>Présent</option>
                        <option value="absent" <?= $statut === 'absent' ? 'selected' : '' ?>>Absent</option>
                        <option value="annulé" <?= $statut === 'annulé' ? 'selected' : '' ?>>Annulé</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Notes Supplémentaires</label>
                    <textarea name="notes_supplementaires" id="notes_supplementaires" rows="3"><?= htmlspecialchars($old['notes_supplementaires'] ?? $participation['notes_supplementaires'] ?? '') ?></textarea>
                </div>
            </div>

            <div class="action-buttons">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Enregistrer
                </button>
                <a href="index.php?page=participations" class="btn btn-secondary">
                    Annuler
                </a>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('participationForm');
    
    form.addEventListener('submit', function(e) {
        let isValid = true;

        // Réinitialiser les messages d'erreur
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
            isValid = false;
        }

        const eventEl = document.getElementById('event_id');
        if (eventEl && !eventEl.value.trim()) {
            showError('event_id', 'L\'événement est obligatoire.');
        }

        const statutEl = document.getElementById('statut');
        if (statutEl && !statutEl.value.trim()) {
            showError('statut', 'Le statut est obligatoire.');
        }

        const prenomEl = document.getElementById('prenom');
        if (prenomEl && !prenomEl.value.trim()) {
            showError('prenom', 'Le prénom est obligatoire.');
        }

        const nomEl = document.getElementById('nom');
        if (nomEl && !nomEl.value.trim()) {
            showError('nom', 'Le nom est obligatoire.');
        }

        const emailEl = document.getElementById('email');
        if (emailEl) {
            const emailVal = emailEl.value.trim();
            if (!emailVal || !emailVal.includes('@')) {
                showError('email', 'Un email valide est obligatoire.');
            }
        }

        if (!isValid) {
            e.preventDefault();
        }
    });
});
</script>

</body>
</html>
