<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modifier mon profil - MediConnect</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #2A7FAA;
            --primary-dark: #1e5f80;
            --secondary: #4CAF50;
            --secondary-dark: #3d8b40;
            --card-shadow: 0 20px 60px rgba(0,0,0,0.15);
            --transition: all 0.3s ease;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            font-family: 'Segoe UI', sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 40px 20px;
        }

        .edit-profile-container {
            max-width: 580px;
            width: 100%;
            margin: 0 auto;
        }

        .profile-card {
            background: white;
            border-radius: 28px;
            box-shadow: var(--card-shadow);
            overflow: hidden;
        }

        .card-header-custom {
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            padding: 40px 28px 32px;
            text-align: center;
            position: relative;
        }

        .card-header-custom::before {
            content: '';
            position: absolute;
            top: -50%; right: -20%;
            width: 60%; height: 200%;
            background: rgba(255,255,255,0.05);
            transform: rotate(35deg);
            pointer-events: none;
        }

        .avatar-wrapper {
            position: relative;
            width: 110px;
            height: 110px;
            margin: 0 auto 20px;
            z-index: 1;
        }

        .avatar-circle {
            width: 110px;
            height: 110px;
            border-radius: 50%;
            border: 4px solid rgba(255,255,255,0.7);
            overflow: hidden;
            background: rgba(255,255,255,0.2);
            display: flex;
            align-items: center;
            justify-content: center;
            backdrop-filter: blur(10px);
        }

        .avatar-circle img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .avatar-circle .avatar-placeholder {
            font-size: 48px;
            color: white;
        }

        .avatar-upload-btn {
            position: absolute;
            bottom: 2px; right: 2px;
            width: 34px; height: 34px;
            background: white;
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            cursor: pointer;
            box-shadow: 0 2px 8px rgba(0,0,0,0.2);
            transition: var(--transition);
            border: 2px solid var(--primary);
        }

        .avatar-upload-btn:hover {
            transform: scale(1.1);
            background: var(--primary);
        }

        .avatar-upload-btn:hover i { color: white; }

        .avatar-upload-btn i {
            font-size: 14px;
            color: var(--primary);
            transition: var(--transition);
        }

        #photoInput { display: none; }

        .card-header-custom h2 {
            color: white;
            font-size: 26px;
            font-weight: 700;
            margin: 0;
            position: relative; z-index: 1;
        }

        .card-header-custom p {
            color: rgba(255,255,255,0.9);
            margin: 8px 0 0;
            font-size: 14px;
            position: relative; z-index: 1;
        }

        .card-body-custom { padding: 32px 28px; }

        .form-group { margin-bottom: 20px; }

        .form-label {
            font-weight: 600;
            color: #2d3748;
            margin-bottom: 8px;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            display: block;
        }

        .input-icon { position: relative; }

        .input-icon i.field-icon {
            position: absolute;
            left: 16px; top: 50%;
            transform: translateY(-50%);
            color: #a0aec0;
            font-size: 15px;
            pointer-events: none;
            z-index: 1;
        }

        .form-control {
            width: 100%;
            border-radius: 14px;
            padding: 13px 16px 13px 44px;
            border: 1.5px solid #e2e8f0;
            transition: var(--transition);
            font-size: 14px;
            background: #f8fafc;
        }

        .form-control:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(42,127,170,0.1);
            outline: none;
            background: white;
        }

        .form-control::placeholder { color: #cbd5e0; }

        .photo-filename {
            font-size: 12px;
            color: #718096;
            margin-top: 6px;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .photo-filename.selected { color: var(--secondary-dark); font-weight: 600; }

        .password-section {
            background: #f8fafc;
            border-radius: 20px;
            padding: 20px;
            margin: 20px 0;
            border: 1px solid #e2e8f0;
        }

        .password-section-title {
            font-size: 15px;
            font-weight: 600;
            color: var(--primary);
            margin-bottom: 16px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .password-hint {
            font-size: 12px;
            color: #718096;
            margin-top: 6px;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .alert-custom {
            border-radius: 14px;
            padding: 13px 16px;
            margin-bottom: 20px;
            display: flex;
            align-items: flex-start;
            gap: 12px;
            border: none;
            font-size: 14px;
            line-height: 1.5;
        }

        .alert-success-custom { background:#d4edda; color:#155724; border-left:4px solid #28a745; }
        .alert-error-custom   { background:#f8d7da; color:#721c24; border-left:4px solid #dc3545; }
        .alert-info-custom    { background:#e7f3ff; color:#004085; border-left:4px solid #17a2b8; }

        .alert-custom .alert-icon { flex-shrink: 0; margin-top: 2px; }

        .btn-save {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
            border-radius: 14px;
            padding: 14px 28px;
            border: none;
            font-weight: 600;
            font-size: 15px;
            transition: var(--transition);
            width: 100%;
            cursor: pointer;
        }

        .btn-save:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(76,175,80,0.3);
            color: white;
        }

        .btn-cancel {
            background: #f1f3f5;
            color: #6c757d;
            border-radius: 14px;
            padding: 14px 28px;
            border: none;
            font-weight: 600;
            font-size: 15px;
            transition: var(--transition);
            width: 100%;
            margin-top: 12px;
            text-decoration: none;
            display: inline-block;
            text-align: center;
        }

        .btn-cancel:hover {
            background: #e9ecef;
            color: #495057;
            transform: translateY(-2px);
        }

        .back-link { text-align: center; margin-top: 20px; }

        .back-link a {
            color: white;
            text-decoration: none;
            font-size: 14px;
            opacity: 0.9;
            transition: var(--transition);
        }

        .back-link a:hover { opacity: 1; text-decoration: underline; }

        @media (max-width: 480px) {
            .card-body-custom { padding: 24px 18px; }
            .card-header-custom { padding: 28px 18px 24px; }
        }
    </style>
</head>
<body>

<?php
// Ce fichier est une VUE - Il ne contient PAS la logique métier
// Les données sont fournies par UserController::editProfilForm()

// Définir les constantes si non définies
if (!defined('UPLOAD_URL')) {
    define('UPLOAD_URL', 'uploads/photos/');
}

// Récupérer les données depuis la session (fournies par le contrôleur)
$user = $_SESSION['user'] ?? [
    'nom'       => '',
    'prenom'    => '',
    'email'     => '',
    'telephone' => '',
    'photo'     => null,
];

$success = $_SESSION['success'] ?? null;
$error = $_SESSION['error'] ?? null;
$userRole = $_SESSION['user_role'] ?? 'patient';

// Nettoyer les messages flash après affichage
unset($_SESSION['success'], $_SESSION['error']);
?>

<div class="edit-profile-container">
    <div class="profile-card">

        <div class="card-header-custom">
            <div class="avatar-wrapper">
                <div class="avatar-circle" id="avatarCircle">
                    <?php if (!empty($user['photo'])): ?>
                        <img id="avatarImg"
                             src="<?= UPLOAD_URL . htmlspecialchars($user['photo']) ?>"
                             alt="Photo de profil">
                    <?php else: ?>
                        <i class="fas fa-user avatar-placeholder" id="avatarIcon"></i>
                    <?php endif; ?>
                </div>
                <label class="avatar-upload-btn" for="photoInput" title="Changer la photo">
                    <i class="fas fa-camera"></i>
                </label>
            </div>
            <h2>Modifier mon profil</h2>
            <p>Mettez à jour vos informations personnelles</p>
        </div>

        <div class="card-body-custom">

            <?php if ($success): ?>
                <div class="alert-custom alert-success-custom">
                    <i class="fas fa-check-circle fa-lg alert-icon"></i>
                    <span><?= htmlspecialchars($success) ?></span>
                </div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="alert-custom alert-error-custom">
                    <i class="fas fa-exclamation-circle fa-lg alert-icon"></i>
                    <span><?= $error ?></span>
                </div>
            <?php endif; ?>

            <!-- 
                CRUCIAL: enctype="multipart/form-data" est OBLIGATOIRE
                pour que PHP reçoive correctement $_POST quand un fichier est uploadé
            -->
            <form method="POST"
                  action="index.php?page=modifier_profil"
                  id="profileForm"
                  enctype="multipart/form-data">

                <input type="file"
                       id="photoInput"
                       name="photo"
                       accept="image/jpeg,image/png,image/gif,image/webp">

                <div class="photo-filename" id="photoFilename">
                    <i class="fas fa-image"></i>
                    <span id="photoFilenameText">
                        <?= !empty($user['photo'])
                            ? htmlspecialchars($user['photo'])
                            : 'Aucune photo sélectionnée' ?>
                    </span>
                </div>

                <div class="form-group" style="margin-top:20px;">
                    <label class="form-label" for="nom">
                        <i class="fas fa-user me-1"></i>Nom
                    </label>
                    <div class="input-icon">
                        <i class="fas fa-user field-icon"></i>
                        <input type="text"
                               id="nom"
                               name="nom"
                               class="form-control"
                               value="<?= htmlspecialchars($user['nom'] ?? '') ?>"
                               placeholder="Votre nom"
                               required>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label" for="prenom">
                        <i class="fas fa-user me-1"></i>Prénom
                    </label>
                    <div class="input-icon">
                        <i class="fas fa-user field-icon"></i>
                        <input type="text"
                               id="prenom"
                               name="prenom"
                               class="form-control"
                               value="<?= htmlspecialchars($user['prenom'] ?? '') ?>"
                               placeholder="Votre prénom"
                               required>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label" for="email">
                        <i class="fas fa-envelope me-1"></i>Adresse email
                    </label>
                    <div class="input-icon">
                        <i class="fas fa-envelope field-icon"></i>
                        <input type="email"
                               id="email"
                               name="email"
                               class="form-control"
                               value="<?= htmlspecialchars($user['email'] ?? '') ?>"
                               placeholder="exemple@email.com"
                               required>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label" for="telephone">
                        <i class="fas fa-phone me-1"></i>Numéro de téléphone
                    </label>
                    <div class="input-icon">
                        <i class="fas fa-phone field-icon"></i>
                        <input type="tel"
                               id="telephone"
                               name="telephone"
                               class="form-control"
                               value="<?= htmlspecialchars($user['telephone'] ?? '') ?>"
                               placeholder="+216 XX XXX XXX">
                    </div>
                </div>

                <?php if ($userRole === 'patient'): ?>
                <div class="form-group">
                    <label class="form-label" for="date_naissance">
                        <i class="fas fa-calendar me-1"></i>Date de naissance
                    </label>
                    <div class="input-icon">
                        <i class="fas fa-calendar field-icon"></i>
                        <input type="date"
                               id="date_naissance"
                               name="date_naissance"
                               class="form-control"
                               value="<?= htmlspecialchars($user['date_naissance'] ?? '') ?>">
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label" for="adresse">
                        <i class="fas fa-map-marker-alt me-1"></i>Adresse
                    </label>
                    <div class="input-icon">
                        <i class="fas fa-map-marker-alt field-icon"></i>
                        <input type="text"
                               id="adresse"
                               name="adresse"
                               class="form-control"
                               value="<?= htmlspecialchars($user['adresse'] ?? '') ?>"
                               placeholder="Votre adresse complète">
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label" for="groupe_sanguin">
                        <i class="fas fa-tint me-1"></i>Groupe sanguin
                    </label>
                    <div class="input-icon">
                        <i class="fas fa-tint field-icon"></i>
                        <select id="groupe_sanguin" name="groupe_sanguin" class="form-control">
                            <option value="">Non spécifié</option>
                            <option value="A+" <?= ($user['groupe_sanguin'] ?? '') == 'A+' ? 'selected' : '' ?>>A+</option>
                            <option value="A-" <?= ($user['groupe_sanguin'] ?? '') == 'A-' ? 'selected' : '' ?>>A-</option>
                            <option value="B+" <?= ($user['groupe_sanguin'] ?? '') == 'B+' ? 'selected' : '' ?>>B+</option>
                            <option value="B-" <?= ($user['groupe_sanguin'] ?? '') == 'B-' ? 'selected' : '' ?>>B-</option>
                            <option value="AB+" <?= ($user['groupe_sanguin'] ?? '') == 'AB+' ? 'selected' : '' ?>>AB+</option>
                            <option value="AB-" <?= ($user['groupe_sanguin'] ?? '') == 'AB-' ? 'selected' : '' ?>>AB-</option>
                            <option value="O+" <?= ($user['groupe_sanguin'] ?? '') == 'O+' ? 'selected' : '' ?>>O+</option>
                            <option value="O-" <?= ($user['groupe_sanguin'] ?? '') == 'O-' ? 'selected' : '' ?>>O-</option>
                        </select>
                    </div>
                </div>
                <?php endif; ?>

                <?php if ($userRole === 'medecin'): ?>
                <div class="form-group">
                    <label class="form-label" for="specialite">
                        <i class="fas fa-stethoscope me-1"></i>Spécialité
                    </label>
                    <div class="input-icon">
                        <i class="fas fa-stethoscope field-icon"></i>
                        <input type="text"
                               id="specialite"
                               name="specialite"
                               class="form-control"
                               value="<?= htmlspecialchars($user['specialite'] ?? '') ?>"
                               placeholder="Votre spécialité">
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label" for="tarif">
                        <i class="fas fa-euro-sign me-1"></i>Tarif (€)
                    </label>
                    <div class="input-icon">
                        <i class="fas fa-euro-sign field-icon"></i>
                        <input type="number"
                               id="tarif"
                               name="tarif"
                               class="form-control"
                               value="<?= htmlspecialchars($user['tarif'] ?? '') ?>"
                               placeholder="Tarif de consultation">
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label" for="adresse_cabinet">
                        <i class="fas fa-building me-1"></i>Adresse du cabinet
                    </label>
                    <div class="input-icon">
                        <i class="fas fa-building field-icon"></i>
                        <input type="text"
                               id="adresse_cabinet"
                               name="adresse_cabinet"
                               class="form-control"
                               value="<?= htmlspecialchars($user['adresse_cabinet'] ?? '') ?>"
                               placeholder="Adresse de votre cabinet">
                    </div>
                </div>
                <?php endif; ?>

                <div class="password-section">
                    <div class="password-section-title">
                        <i class="fas fa-lock"></i>
                        Changer le mot de passe
                        <span style="font-size:12px; font-weight:normal;">(optionnel)</span>
                    </div>

                    <div class="form-group" style="margin-bottom:16px;">
                        <div class="input-icon">
                            <i class="fas fa-key field-icon"></i>
                            <input type="password"
                                   name="password"
                                   id="password"
                                   class="form-control"
                                   placeholder="Nouveau mot de passe">
                        </div>
                        <div class="password-hint">
                            <i class="fas fa-info-circle"></i>
                            Laisser vide pour ne pas changer
                        </div>
                    </div>

                    <div class="form-group" style="margin-bottom:0;">
                        <div class="input-icon">
                            <i class="fas fa-check-circle field-icon"></i>
                            <input type="password"
                                   name="confirm_password"
                                   id="confirmPassword"
                                   class="form-control"
                                   placeholder="Confirmer le mot de passe">
                        </div>
                        <div class="password-hint">
                            <i class="fas fa-shield-alt"></i>
                            Minimum 6 caractères
                        </div>
                    </div>
                </div>

                <div class="alert-custom alert-info-custom" style="margin-bottom:24px;">
                    <i class="fas fa-lightbulb fa-lg alert-icon"></i>
                    <span>Photos acceptées : JPG, PNG, GIF, WEBP — 2 Mo max.</span>
                </div>

                <button type="submit" class="btn-save">
                    <i class="fas fa-save me-2"></i>Enregistrer les modifications
                </button>

                <a href="index.php?page=profil" class="btn-cancel">
                    <i class="fas fa-times me-2"></i>Annuler
                </a>

            </form>
        </div>
    </div>

    <div class="back-link">
        <a href="index.php?page=profil">
            <i class="fas fa-arrow-left me-2"></i>Retour à mon profil
        </a>
    </div>
</div>

<script>
    const photoInput = document.getElementById('photoInput');
    const avatarCircle = document.getElementById('avatarCircle');
    const filenameEl = document.getElementById('photoFilenameText');
    const filenameBadge = document.getElementById('photoFilename');

    photoInput.addEventListener('change', function() {
        const file = this.files[0];
        if (!file) return;

        const allowed = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        if (!allowed.includes(file.type)) {
            alert('Format non supporté. Utilisez JPG, PNG, GIF ou WEBP.');
            this.value = '';
            return;
        }
        if (file.size > 2 * 1024 * 1024) {
            alert('La photo ne doit pas dépasser 2 Mo.');
            this.value = '';
            return;
        }

        const reader = new FileReader();
        reader.onload = (e) => {
            avatarCircle.innerHTML = `<img id="avatarImg" src="${e.target.result}" alt="Aperçu" style="width:100%;height:100%;object-fit:cover;">`;
        };
        reader.readAsDataURL(file);

        filenameEl.textContent = file.name;
        filenameBadge.classList.add('selected');
    });

    document.getElementById('profileForm').addEventListener('submit', function(e) {
        const nom = document.getElementById('nom').value.trim();
        const prenom = document.getElementById('prenom').value.trim();
        const email = document.getElementById('email').value.trim();
        const pwd = document.getElementById('password').value;
        const confirm = document.getElementById('confirmPassword').value;

        if (!nom || !prenom) {
            e.preventDefault();
            alert('Le nom et le prénom sont obligatoires.');
            return;
        }
        if (!email.includes('@')) {
            e.preventDefault();
            alert('Veuillez entrer un email valide.');
            return;
        }
        if (pwd && pwd !== confirm) {
            e.preventDefault();
            alert('Les mots de passe ne correspondent pas.');
            return;
        }
        if (pwd && pwd.length < 6) {
            e.preventDefault();
            alert('Le mot de passe doit contenir au moins 6 caractères.');
        }
    });

    setTimeout(() => {
        document.querySelectorAll('.alert-custom').forEach(el => {
            el.style.transition = 'opacity 0.5s';
            el.style.opacity = '0';
            setTimeout(() => el.remove(), 500);
        });
    }, 6000);
</script>
</body>
</html>