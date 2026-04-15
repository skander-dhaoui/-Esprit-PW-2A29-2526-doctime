<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mon profil - MediConnect</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background: #f5f7fb; font-family: 'Segoe UI', sans-serif; margin: 0; padding: 0; }
        .navbar { background: #2A7FAA; box-shadow: 0 2px 10px rgba(0,0,0,0.1); padding: 12px 0; }
        .navbar-brand { color: white !important; font-weight: bold; font-size: 1.5rem; }
        .navbar-brand i { margin-right: 8px; }
        .nav-link { color: rgba(255,255,255,0.9) !important; font-weight: 500; transition: all 0.3s; margin: 0 5px; }
        .nav-link:hover, .nav-link.active { color: white !important; background: rgba(255,255,255,0.2); border-radius: 8px; }
        .nav-link i { margin-right: 5px; }
        .profile-header { background: linear-gradient(135deg, #2A7FAA 0%, #4CAF50 100%); color: white; padding: 40px 0; text-align: center; }
        .profile-avatar-wrapper { position: relative; width: 150px; height: 150px; margin: 0 auto 15px; }
        .profile-avatar { width: 150px; height: 150px; background: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 60px; color: #2A7FAA; box-shadow: 0 5px 15px rgba(0,0,0,0.2); object-fit: cover; cursor: pointer; transition: all 0.3s; overflow: hidden; }
        .profile-avatar:hover { opacity: 0.8; }
        .profile-avatar img { width: 100%; height: 100%; object-fit: cover; }
        .avatar-overlay { position: absolute; bottom: 5px; right: 5px; background: #4CAF50; border-radius: 50%; width: 40px; height: 40px; display: flex; align-items: center; justify-content: center; cursor: pointer; transition: all 0.3s; border: 3px solid white; }
        .avatar-overlay:hover { background: #2A7FAA; transform: scale(1.1); }
        .avatar-overlay i { color: white; font-size: 18px; }
        .profile-name { font-size: 24px; font-weight: bold; margin-bottom: 5px; }
        .profile-role { font-size: 14px; opacity: 0.9; }
        .card { border: none; border-radius: 15px; box-shadow: 0 5px 15px rgba(0,0,0,0.05); margin-bottom: 25px; }
        .card-header { background: white; border-bottom: 2px solid #f0f0f0; padding: 15px 20px; font-weight: bold; border-radius: 15px 15px 0 0; }
        .form-control, .form-select { border-radius: 10px; padding: 10px 15px; border: 1px solid #ddd; }
        .btn-save { background: #4CAF50; color: white; border-radius: 25px; padding: 10px 25px; border: none; transition: all 0.3s; }
        .btn-save:hover { background: #2A7FAA; transform: translateY(-2px); }
        .btn-cancel { background: #f0f0f0; color: #666; border-radius: 25px; padding: 10px 25px; border: none; transition: all 0.3s; }
        .btn-cancel:hover { background: #e0e0e0; }
        .alert-success-custom { background: #d4edda; color: #155724; border-radius: 10px; padding: 12px 15px; border: none; border-left: 4px solid #28a745; }
        .alert-error-custom { background: #f8d7da; color: #721c24; border-radius: 10px; padding: 12px 15px; border: none; border-left: 4px solid #dc3545; }
        .password-requirements { font-size: 12px; color: #999; margin-top: 5px; }
        .requirement-valid { color: #4CAF50; }
        .requirement-invalid { color: #dc3545; }
        .main-container { max-width: 800px; margin: 0 auto; padding: 30px 20px; }
        .stats-row { display: flex; gap: 20px; margin-top: 20px; }
        .stat-card { flex: 1; background: white; border-radius: 15px; padding: 20px; text-align: center; box-shadow: 0 5px 15px rgba(0,0,0,0.05); }
        .stat-card i { font-size: 40px; margin-bottom: 10px; }
        .stat-card h3 { margin: 0; font-size: 28px; font-weight: bold; }
        .stat-card p { margin: 5px 0 0; color: #666; font-size: 14px; }
        
        .field-error {
            font-size: 12px;
            margin-top: 5px;
            color: #c00;
            font-weight: normal;
        }
        .error-container { min-height: 20px; }
        
        /* Toast notification */
        .toast-message {
            position: fixed;
            bottom: 20px;
            right: 20px;
            min-width: 300px;
            background: white;
            border-radius: 12px;
            padding: 14px 16px;
            display: flex;
            align-items: center;
            gap: 12px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.15);
            z-index: 9999;
            animation: slideInRight 0.3s ease;
            border-left: 4px solid;
        }
        .toast-error { border-left-color: #dc3545; }
        .toast-error i:first-child { color: #dc3545; }
        .toast-success { border-left-color: #28a745; }
        .toast-success i:first-child { color: #28a745; }
        .toast-content { flex: 1; }
        .toast-title { font-weight: bold; margin-bottom: 3px; }
        .toast-text { font-size: 13px; opacity: 0.85; }
        .toast-close { cursor: pointer; opacity: 0.5; padding: 0 5px; }
        .toast-close:hover { opacity: 1; }
        @keyframes slideInRight {
            from { opacity: 0; transform: translateX(100px); }
            to { opacity: 1; transform: translateX(0); }
        }
        
        @media (max-width: 768px) { .stats-row { flex-direction: column; } .main-container { padding: 20px; } }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg">
        <div class="container">
            <a class="navbar-brand" href="index.php?page=accueil">
                <i class="fas fa-stethoscope"></i> MediConnect
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php?page=accueil"><i class="fas fa-home"></i> Accueil</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="index.php?page=mes_rendez_vous"><i class="fas fa-calendar"></i> Mes RDV</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="index.php?page=profil"><i class="fas fa-user"></i> Profil</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="index.php?page=logout"><i class="fas fa-sign-out-alt"></i> Déconnexion</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Header profil -->
    <div class="profile-header">
        <div class="profile-avatar-wrapper">
            <?php
                $prenom = $user['prenom'] ?? $_SESSION['user_name'] ?? '';
                $nom    = $user['nom']    ?? '';
                $initiales = strtoupper(substr($prenom, 0, 1) . substr($nom, 0, 1));
                $avatar = $user['avatar'] ?? null;
                $avatarFullPath = $avatar ? '/valorys_Copie/' . $avatar : null;
            ?>
            <div class="profile-avatar" id="avatarPreview">
                <?php if ($avatar && file_exists($_SERVER['DOCUMENT_ROOT'] . $avatarFullPath)): ?>
                    <img src="<?= htmlspecialchars($avatarFullPath) ?>" alt="Avatar" id="avatarImg">
                <?php else: ?>
                    <span style="font-size: 60px;"><?= $initiales ?: '👤' ?></span>
                <?php endif; ?>
            </div>
            <div class="avatar-overlay" onclick="document.getElementById('avatarInput').click()">
                <i class="fas fa-camera"></i>
            </div>
<form id="avatarForm" method="POST" action="index.php?page=profil" enctype="multipart/form-data">
    <input type="hidden" name="action" value="update_avatar">
    <input type="file" name="avatar" id="avatarInput" 
           accept="image/jpeg,image/png,image/jpg,image/gif,image/webp" 
           style="display:none;" 
           onchange="uploadAvatarOnly()">
</form>
        </div>
        <div class="profile-name">
            <?= htmlspecialchars(($user['prenom'] ?? '') . ' ' . ($user['nom'] ?? $_SESSION['user_name'] ?? '')) ?>
        </div>
        <div class="profile-role">
            <i class="fas fa-user"></i>
            <?= ucfirst($userRole ?? $_SESSION['user_role'] ?? 'patient') ?>
            · Membre depuis <?= isset($user['created_at']) ? date('F Y', strtotime($user['created_at'])) : date('F Y') ?>
        </div>
    </div>

    <div class="main-container">

        <?php if (!empty($success)): ?>
            <div class="alert-success-custom mb-3">
                <i class="fas fa-check-circle me-2"></i> <?= htmlspecialchars($success) ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($error)): ?>
            <div class="alert-error-custom mb-3">
                <i class="fas fa-exclamation-circle me-2"></i> <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($successPassword)): ?>
            <div class="alert-success-custom mb-3">
                <i class="fas fa-check-circle me-2"></i> <?= htmlspecialchars($successPassword) ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($errorPassword)): ?>
            <div class="alert-error-custom mb-3">
                <i class="fas fa-exclamation-circle me-2"></i> <?= htmlspecialchars($errorPassword) ?>
            </div>
        <?php endif; ?>

        <!-- SECTION 1 : Informations du compte -->
        <div class="card">
            <div class="card-header">
                <i class="fas fa-info-circle me-2"></i> Informations du compte
            </div>
            <div class="card-body">
                <form method="POST" action="index.php?page=profil" id="profileForm" novalidate>
                    <input type="hidden" name="action" value="update_profile">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Nom <span class="text-danger">*</span></label>
                            <input type="text" name="nom" id="nom" class="form-control" value="<?= htmlspecialchars($user['nom'] ?? '') ?>">
                            <div class="error-container" id="nom-error"></div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Prénom <span class="text-danger">*</span></label>
                            <input type="text" name="prenom" id="prenom" class="form-control" value="<?= htmlspecialchars($user['prenom'] ?? '') ?>">
                            <div class="error-container" id="prenom-error"></div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Email <span class="text-danger">*</span></label>
                            <input type="email" name="email" id="email" class="form-control" value="<?= htmlspecialchars($user['email'] ?? $_SESSION['user_email'] ?? '') ?>">
                            <div class="error-container" id="email-error"></div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Téléphone</label>
                            <input type="tel" name="telephone" id="telephone" class="form-control" value="<?= htmlspecialchars($user['telephone'] ?? '') ?>">
                            <div class="error-container" id="telephone-error"></div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Date de naissance</label>
                            <?php 
                            $dateNaissance = '';
                            if (!empty($user['date_naissance']) && $user['date_naissance'] !== '0000-00-00') {
                                $dateNaissance = $user['date_naissance'];
                            }
                            ?>
                            <input type="date" name="date_naissance" id="date_naissance" class="form-control" value="<?= htmlspecialchars($dateNaissance) ?>">
                            <div class="error-container" id="date_naissance-error"></div>
                        </div>
                        <?php if (($userRole ?? $_SESSION['user_role'] ?? '') === 'patient'): ?>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Groupe sanguin</label>
                            <select name="groupe_sanguin" id="groupe_sanguin" class="form-select">
                                <?php foreach (['A+','A-','B+','B-','AB+','AB-','O+','O-'] as $gs): ?>
                                    <option value="<?= $gs ?>" <?= ($user['groupe_sanguin'] ?? '') == $gs ? 'selected' : '' ?>><?= $gs ?></option>
                                <?php endforeach; ?>
                            </select>
                            <div class="error-container" id="groupe_sanguin-error"></div>
                        </div>
                        <?php endif; ?>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Adresse</label>
                        <textarea name="adresse" id="adresse" class="form-control" rows="2"><?= htmlspecialchars($user['adresse'] ?? '') ?></textarea>
                        <div class="error-container" id="adresse-error"></div>
                    </div>
                    <div class="d-flex gap-2 mt-3">
                        <button type="submit" class="btn-save">
                            <i class="fas fa-save me-1"></i> Enregistrer les modifications
                        </button>
                        <a href="index.php?page=profil" class="btn-cancel">
                            <i class="fas fa-undo me-1"></i> Annuler
                        </a>
                    </div>
                </form>
            </div>
        </div>

        <!-- SECTION 2 : Changer le mot de passe -->
        <div class="card">
            <div class="card-header">
                <i class="fas fa-lock me-2"></i> Changer le mot de passe
            </div>
            <div class="card-body">
                <form method="POST" action="index.php?page=profil" id="passwordForm" novalidate>
                    <input type="hidden" name="action" value="change_password">
                    <div class="mb-3">
                        <label class="form-label">Mot de passe actuel <span class="text-danger">*</span></label>
                        <input type="password" name="current_password" id="currentPassword" class="form-control" placeholder="Entrez votre mot de passe actuel">
                        <div class="error-container" id="currentPassword-error"></div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Nouveau mot de passe <span class="text-danger">*</span></label>
                        <input type="password" id="newPassword" name="new_password" class="form-control" placeholder="Entrez votre nouveau mot de passe">
                        <div class="password-requirements">
                            <span id="reqLength" class="requirement-invalid"><i class="fas fa-circle me-1"></i> Au moins 8 caractères</span><br>
                            <span id="reqUpper" class="requirement-invalid"><i class="fas fa-circle me-1"></i> Au moins une majuscule</span><br>
                            <span id="reqNumber" class="requirement-invalid"><i class="fas fa-circle me-1"></i> Au moins un chiffre</span>
                        </div>
                        <div class="error-container" id="newPassword-error"></div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Confirmer le nouveau mot de passe <span class="text-danger">*</span></label>
                        <input type="password" id="confirmPassword" name="confirm_password" class="form-control" placeholder="Confirmez votre nouveau mot de passe">
                        <div class="error-container" id="confirmPassword-error"></div>
                    </div>
                    <div class="d-flex gap-2 mt-3">
                        <button type="submit" class="btn-save">
                            <i class="fas fa-key me-1"></i> Enregistrer le nouveau mot de passe
                        </button>
                        <button type="button" class="btn-cancel" onclick="cancelPassword()">
                            <i class="fas fa-undo me-1"></i> Annuler
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- SECTION 3 : Statistiques -->
        <div class="stats-row">
            <div class="stat-card">
                <i class="fas fa-calendar-check text-success"></i>
                <h3><?= $stats['total_rdv'] ?? 0 ?></h3>
                <p>Rendez-vous pris</p>
            </div>
            <div class="stat-card">
                <i class="fas fa-clock text-primary"></i>
                <h3><?= $stats['rdv_avenir'] ?? 0 ?></h3>
                <p>Rendez-vous à venir</p>
            </div>
            <div class="stat-card">
                <i class="fas fa-star text-warning"></i>
                <h3><?= $stats['note_moyenne'] ?? '—' ?></h3>
                <p>Note moyenne</p>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // ═══ FONCTIONS D'ERREUR SOUS LES CHAMPS ═══
        function showFieldError(fieldId, message) {
            const container = document.getElementById(fieldId + '-error');
            if (container) {
                container.innerHTML = '<div class="field-error">' + message + '</div>';
            }
        }

        function clearFieldError(fieldId) {
            const container = document.getElementById(fieldId + '-error');
            if (container) {
                container.innerHTML = '';
            }
        }

        // ═══ TOAST NOTIFICATION ═══
        function showToast(message, type = 'error') {
            let toastEl = document.getElementById('customToast');
            
            if (!toastEl) {
                const toastDiv = document.createElement('div');
                toastDiv.id = 'customToast';
                toastDiv.className = 'toast-message ' + (type === 'error' ? 'toast-error' : 'toast-success');
                toastDiv.innerHTML = `
                    <i class="fas ${type === 'error' ? 'fa-exclamation-circle' : 'fa-check-circle'}"></i>
                    <div class="toast-content">
                        <div class="toast-title">${type === 'error' ? 'Erreur' : 'Succès'}</div>
                        <div class="toast-text"></div>
                    </div>
                    <i class="fas fa-times toast-close"></i>
                `;
                document.body.appendChild(toastDiv);
                toastEl = document.getElementById('customToast');
            }
            
            toastEl.className = 'toast-message ' + (type === 'error' ? 'toast-error' : 'toast-success');
            toastEl.querySelector('.toast-title').textContent = type === 'error' ? 'Erreur' : 'Succès';
            toastEl.querySelector('.toast-text').textContent = message;
            toastEl.querySelector('i:first-child').className = `fas ${type === 'error' ? 'fa-exclamation-circle' : 'fa-check-circle'}`;
            toastEl.style.display = 'flex';
            
            if (toastEl.timeoutId) clearTimeout(toastEl.timeoutId);
            toastEl.timeoutId = setTimeout(() => {
                toastEl.style.display = 'none';
            }, 5000);
            
            toastEl.querySelector('.toast-close').onclick = () => {
                toastEl.style.display = 'none';
                if (toastEl.timeoutId) clearTimeout(toastEl.timeoutId);
            };
        }

// ═══ UPLOAD AVATAR UNIQUEMENT (NE DÉCLENCHE PAS LA VALIDATION) ═══
function uploadAvatarOnly() {
    const input = document.getElementById('avatarInput');
    if (input.files && input.files[0]) {
        const file = input.files[0];
        const allowedTypes = ['image/jpeg', 'image/png', 'image/jpg', 'image/gif', 'image/webp'];
        
        if (!allowedTypes.includes(file.type)) {
            showToast('Format d\'image non supporté. Utilisez JPG, PNG, GIF ou WEBP.', 'error');
            input.value = '';
            return;
        }
        
        if (file.size > 2 * 1024 * 1024) {
            showToast('L\'image ne doit pas dépasser 2 Mo.', 'error');
            input.value = '';
            return;
        }
        
        // ✅ CRUCIAL : Désactiver temporairement l'événement submit du formulaire principal
        const profileForm = document.getElementById('profileForm');
        const originalSubmit = profileForm.onsubmit;
        profileForm.onsubmit = null;
        
        // Soumettre UNIQUEMENT le formulaire d'avatar
        document.getElementById('avatarForm').submit();
        
        // Réactiver après soumission
        setTimeout(() => {
            profileForm.onsubmit = originalSubmit;
        }, 100);
    }
}

        // ═══ VALIDATION DU FORMULAIRE DE PROFIL ═══
        function validateProfileForm() {
            let isValid = true;
            
            clearFieldError('nom');
            clearFieldError('prenom');
            clearFieldError('email');
            
            const nom = document.getElementById('nom').value.trim();
            const prenom = document.getElementById('prenom').value.trim();
            const email = document.getElementById('email').value.trim();
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            
            if (!nom) {
                showFieldError('nom', 'Le nom est obligatoire.');
                isValid = false;
            }
            
            if (!prenom) {
                showFieldError('prenom', 'Le prénom est obligatoire.');
                isValid = false;
            }
            
            if (!email) {
                showFieldError('email', 'L\'email est obligatoire.');
                isValid = false;
            } else if (!emailRegex.test(email)) {
                showFieldError('email', 'L\'email n\'est pas valide.');
                isValid = false;
            }
            
            return isValid;
        }

        // ═══ VALIDATION DU FORMULAIRE DE MOT DE PASSE ═══
        function validatePasswordForm() {
            let isValid = true;
            
            clearFieldError('currentPassword');
            clearFieldError('newPassword');
            clearFieldError('confirmPassword');
            
            const currentPassword = document.getElementById('currentPassword').value;
            const newPassword = document.getElementById('newPassword').value;
            const confirmPassword = document.getElementById('confirmPassword').value;
            
            if (!currentPassword) {
                showFieldError('currentPassword', 'Le mot de passe actuel est obligatoire.');
                isValid = false;
            }
            
            if (!newPassword) {
                showFieldError('newPassword', 'Le nouveau mot de passe est obligatoire.');
                isValid = false;
            } else if (newPassword.length < 8) {
                showFieldError('newPassword', 'Le mot de passe doit contenir au moins 8 caractères.');
                isValid = false;
            } else if (!/[A-Z]/.test(newPassword)) {
                showFieldError('newPassword', 'Le mot de passe doit contenir au moins une majuscule.');
                isValid = false;
            } else if (!/[0-9]/.test(newPassword)) {
                showFieldError('newPassword', 'Le mot de passe doit contenir au moins un chiffre.');
                isValid = false;
            }
            
            if (!confirmPassword) {
                showFieldError('confirmPassword', 'La confirmation est obligatoire.');
                isValid = false;
            } else if (newPassword !== confirmPassword) {
                showFieldError('confirmPassword', 'Les mots de passe ne correspondent pas.');
                isValid = false;
            }
            
            return isValid;
        }

        function updatePasswordRequirements() {
            const password = document.getElementById('newPassword').value;
            const checks = {
                Length: password.length >= 8,
                Upper: /[A-Z]/.test(password),
                Number: /[0-9]/.test(password)
            };
            const labels = {
                Length: 'Au moins 8 caractères',
                Upper: 'Au moins une majuscule',
                Number: 'Au moins un chiffre'
            };
            Object.keys(checks).forEach(k => {
                const el = document.getElementById('req' + k);
                const ok = checks[k];
                el.className = ok ? 'requirement-valid' : 'requirement-invalid';
                el.innerHTML = '<i class="fas fa-' + (ok ? 'check-circle' : 'circle') + ' me-1"></i> ' + labels[k];
            });
        }

        function cancelPassword() {
            document.getElementById('currentPassword').value = '';
            document.getElementById('newPassword').value = '';
            document.getElementById('confirmPassword').value = '';
            updatePasswordRequirements();
            clearFieldError('currentPassword');
            clearFieldError('newPassword');
            clearFieldError('confirmPassword');
        }

        // ═══ SOUMISSION DES FORMULAIRES ═══
        const profileForm = document.getElementById('profileForm');
        if (profileForm) {
            profileForm.addEventListener('submit', function(e) {
                if (!validateProfileForm()) {
                    e.preventDefault();
                    const firstError = document.querySelector('.field-error');
                    if (firstError) firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }
            });
        }

        const passwordForm = document.getElementById('passwordForm');
        if (passwordForm) {
            passwordForm.addEventListener('submit', function(e) {
                if (!validatePasswordForm()) {
                    e.preventDefault();
                    const firstError = document.querySelector('.field-error');
                    if (firstError) firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }
            });
        }

        // Auto-hide alerts after 5s
        setTimeout(() => {
            document.querySelectorAll('.alert-success-custom, .alert-error-custom').forEach(el => {
                el.style.display = 'none';
            });
        }, 5000);

        // Initialisation
        if (document.getElementById('newPassword')) {
            document.getElementById('newPassword').addEventListener('input', updatePasswordRequirements);
        }
    </script>
</body>
</html>