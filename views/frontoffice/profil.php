<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mon profil - MediConnect</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #2A7FAA;
            --primary-dark: #1e5f80;
            --secondary: #4CAF50;
            --secondary-dark: #3d8b40;
            --gray-bg: #f5f7fb;
            --card-shadow: 0 10px 40px rgba(0,0,0,0.08);
            --transition: all 0.3s ease;
        }

        body {
            background: var(--gray-bg);
            font-family: 'Inter', 'Segoe UI', sans-serif;
            margin: 0;
            padding: 0;
        }

        /* Navbar */
        .navbar {
            background: white;
            box-shadow: 0 2px 20px rgba(0,0,0,0.05);
            padding: 12px 0;
            position: sticky;
            top: 0;
            z-index: 1000;
        }
        .navbar-brand {
            font-weight: 700;
            font-size: 1.5rem;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent !important;
        }
        .navbar-brand i {
            background: none;
            -webkit-background-clip: unset;
            background-clip: unset;
            color: var(--primary);
        }
        .nav-link {
            color: #4a5568 !important;
            font-weight: 500;
            transition: var(--transition);
            margin: 0 5px;
            border-radius: 10px;
            padding: 8px 16px;
        }
        .nav-link:hover, .nav-link.active {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white !important;
            transform: translateY(-2px);
        }
        .nav-link i {
            margin-right: 8px;
        }

        /* Profile Header */
        .profile-header {
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            padding: 50px 0;
            position: relative;
            overflow: hidden;
        }
        .profile-header::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -20%;
            width: 60%;
            height: 200%;
            background: rgba(255,255,255,0.05);
            transform: rotate(35deg);
            pointer-events: none;
        }
        .profile-avatar-wrapper {
            position: relative;
            width: 130px;
            height: 130px;
            margin: 0 auto 20px;
        }
        .profile-avatar {
            width: 130px;
            height: 130px;
            background: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 52px;
            color: var(--primary);
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            cursor: pointer;
            transition: var(--transition);
            object-fit: cover;
            overflow: hidden;
            border: 4px solid rgba(255,255,255,0.3);
        }
        .profile-avatar:hover {
            transform: scale(1.05);
            border-color: white;
        }
        .profile-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .avatar-overlay {
            position: absolute;
            bottom: 5px;
            right: 5px;
            background: var(--secondary);
            border-radius: 50%;
            width: 38px;
            height: 38px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: var(--transition);
            border: 3px solid white;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .avatar-overlay:hover {
            background: var(--primary);
            transform: scale(1.1);
        }
        .avatar-overlay i {
            color: white;
            font-size: 16px;
        }
        .profile-name {
            font-size: 28px;
            font-weight: 700;
            color: white;
            margin-bottom: 8px;
        }
        .profile-role {
            font-size: 14px;
            color: rgba(255,255,255,0.9);
            background: rgba(255,255,255,0.2);
            display: inline-block;
            padding: 5px 15px;
            border-radius: 50px;
            backdrop-filter: blur(10px);
        }

        /* Main Container */
        .main-container {
            max-width: 1000px;
            margin: -30px auto 0;
            padding: 0 20px 40px;
            position: relative;
            z-index: 2;
        }

        /* Cards */
        .card {
            border: none;
            border-radius: 20px;
            box-shadow: var(--card-shadow);
            margin-bottom: 25px;
            overflow: hidden;
            transition: var(--transition);
        }
        .card:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 50px rgba(0,0,0,0.12);
        }
        .card-header {
            background: white;
            border-bottom: 1px solid #e9ecef;
            padding: 18px 24px;
            font-weight: 600;
            font-size: 18px;
        }
        .card-header i {
            color: var(--primary);
            margin-right: 10px;
        }
        .card-body {
            padding: 24px;
        }

        /* Form Elements */
        .form-label {
            font-weight: 500;
            color: #2d3748;
            margin-bottom: 8px;
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .form-control, .form-select {
            border-radius: 12px;
            padding: 12px 16px;
            border: 1px solid #e2e8f0;
            transition: var(--transition);
            font-size: 14px;
        }
        .form-control:focus, .form-select:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(42, 127, 170, 0.1);
            outline: none;
        }
        textarea.form-control {
            resize: vertical;
        }

        /* Buttons */
        .btn-save {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
            border-radius: 12px;
            padding: 12px 28px;
            border: none;
            font-weight: 600;
            transition: var(--transition);
        }
        .btn-save:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(76, 175, 80, 0.3);
            color: white;
        }
        .btn-cancel {
            background: #f1f3f5;
            color: #6c757d;
            border-radius: 12px;
            padding: 12px 28px;
            border: none;
            font-weight: 600;
            transition: var(--transition);
        }
        .btn-cancel:hover {
            background: #e9ecef;
            color: #495057;
            transform: translateY(-2px);
        }

        /* Alert Messages */
        .alert-success-custom {
            background: linear-gradient(135deg, #d4edda, #c3e6cb);
            color: #155724;
            border-radius: 12px;
            padding: 14px 18px;
            border: none;
            border-left: 4px solid #28a745;
            font-weight: 500;
        }
        .alert-error-custom {
            background: linear-gradient(135deg, #f8d7da, #f5c6cb);
            color: #721c24;
            border-radius: 12px;
            padding: 14px 18px;
            border: none;
            border-left: 4px solid #dc3545;
            font-weight: 500;
        }

        /* Stats Cards */
        .stats-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-top: 10px;
        }
        .stat-card {
            background: white;
            border-radius: 20px;
            padding: 24px 20px;
            text-align: center;
            box-shadow: var(--card-shadow);
            transition: var(--transition);
        }
        .stat-card:hover {
            transform: translateY(-5px);
        }
        .stat-card i {
            font-size: 42px;
            margin-bottom: 12px;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
        }
        .stat-card h3 {
            margin: 0;
            font-size: 32px;
            font-weight: 700;
            color: #2d3748;
        }
        .stat-card p {
            margin: 8px 0 0;
            color: #718096;
            font-size: 13px;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        /* Password Requirements */
        .password-requirements {
            font-size: 12px;
            margin-top: 8px;
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
        }
        .requirement-valid {
            color: #28a745;
        }
        .requirement-invalid {
            color: #dc3545;
        }
        .requirement-valid i, .requirement-invalid i {
            font-size: 10px;
            margin-right: 4px;
        }

        /* Field Error */
        .field-error {
            font-size: 11px;
            margin-top: 5px;
            color: #dc3545;
            font-weight: 500;
        }

        /* Toast Notification */
        .toast-message {
            position: fixed;
            bottom: 20px;
            right: 20px;
            min-width: 320px;
            background: white;
            border-radius: 12px;
            padding: 14px 18px;
            display: flex;
            align-items: center;
            gap: 12px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.15);
            z-index: 9999;
            animation: slideInRight 0.3s ease;
            border-left: 4px solid;
        }
        .toast-error { border-left-color: #dc3545; }
        .toast-error i:first-child { color: #dc3545; }
        .toast-success { border-left-color: #28a745; }
        .toast-success i:first-child { color: #28a745; }
        .toast-content { flex: 1; }
        .toast-title { font-weight: 700; margin-bottom: 3px; font-size: 14px; }
        .toast-text { font-size: 12px; opacity: 0.85; }
        .toast-close { cursor: pointer; opacity: 0.5; transition: var(--transition); }
        .toast-close:hover { opacity: 1; }

        @keyframes slideInRight {
            from { opacity: 0; transform: translateX(100px); }
            to { opacity: 1; transform: translateX(0); }
        }

        /* Responsive */
        @media (max-width: 768px) {
            .stats-row { grid-template-columns: 1fr; }
            .main-container { padding: 0 15px 30px; }
            .profile-name { font-size: 22px; }
            .profile-avatar-wrapper { width: 100px; height: 100px; }
            .profile-avatar { width: 100px; height: 100px; font-size: 40px; }
        }

        /* Face Capture Section */
        #faceVideo {
            border-radius: 16px;
            border: 2px solid var(--primary);
            background: #000;
            max-width: 100%;
            height: auto;
        }
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

    <!-- Profile Header -->
    <div class="profile-header">
        <div class="container text-center">
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
                        <span style="font-size: 52px;"><?= $initiales ?: '👤' ?></span>
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
                <i class="fas fa-user-check me-1"></i>
                <?= ucfirst($userRole ?? $_SESSION['user_role'] ?? 'patient') ?>
                · Membre depuis <?= isset($user['created_at']) ? date('F Y', strtotime($user['created_at'])) : date('F Y') ?>
            </div>
        </div>
    </div>

    <div class="main-container">
        <!-- Alert Messages -->
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
                <i class="fas fa-user-circle"></i> Informations personnelles
            </div>
            <div class="card-body">
                <form method="POST" action="index.php?page=profil" id="profileForm" novalidate>
                    <input type="hidden" name="action" value="update_profile">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Nom <span class="text-danger">*</span></label>
                            <input type="text" name="nom" id="nom" class="form-control" value="<?= htmlspecialchars($user['nom'] ?? '') ?>" placeholder="Votre nom">
                            <div class="error-container" id="nom-error"></div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Prénom <span class="text-danger">*</span></label>
                            <input type="text" name="prenom" id="prenom" class="form-control" value="<?= htmlspecialchars($user['prenom'] ?? '') ?>" placeholder="Votre prénom">
                            <div class="error-container" id="prenom-error"></div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Email <span class="text-danger">*</span></label>
                            <input type="email" name="email" id="email" class="form-control" value="<?= htmlspecialchars($user['email'] ?? $_SESSION['user_email'] ?? '') ?>" placeholder="exemple@email.com">
                            <div class="error-container" id="email-error"></div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Téléphone</label>
                            <input type="tel" name="telephone" id="telephone" class="form-control" value="<?= htmlspecialchars($user['telephone'] ?? '') ?>" placeholder="+33 X XX XX XX XX">
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
                                <option value="">Sélectionner</option>
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
                        <textarea name="adresse" id="adresse" class="form-control" rows="2" placeholder="Votre adresse complète"><?= htmlspecialchars($user['adresse'] ?? '') ?></textarea>
                        <div class="error-container" id="adresse-error"></div>
                    </div>
                    <div class="d-flex gap-3 mt-3">
                        <button type="submit" class="btn-save">
                            <i class="fas fa-save me-2"></i> Enregistrer
                        </button>
                        <a href="index.php?page=profil" class="btn-cancel">
                            <i class="fas fa-undo me-2"></i> Annuler
                        </a>
                    </div>
                </form>
            </div>
        </div>

        <!-- SECTION 2 : Changer le mot de passe -->
        <div class="card">
            <div class="card-header">
                <i class="fas fa-lock"></i> Sécurité
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
                            <span id="reqLength" class="requirement-invalid"><i class="fas fa-circle"></i> 8 caractères</span>
                            <span id="reqUpper" class="requirement-invalid"><i class="fas fa-circle"></i> 1 majuscule</span>
                            <span id="reqNumber" class="requirement-invalid"><i class="fas fa-circle"></i> 1 chiffre</span>
                        </div>
                        <div class="error-container" id="newPassword-error"></div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Confirmer le mot de passe <span class="text-danger">*</span></label>
                        <input type="password" id="confirmPassword" name="confirm_password" class="form-control" placeholder="Confirmez votre nouveau mot de passe">
                        <div class="error-container" id="confirmPassword-error"></div>
                    </div>
                    <div class="d-flex gap-3 mt-3">
                        <button type="submit" class="btn-save">
                            <i class="fas fa-key me-2"></i> Changer le mot de passe
                        </button>
                        <button type="button" class="btn-cancel" onclick="cancelPassword()">
                            <i class="fas fa-times me-2"></i> Annuler
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- SECTION 3 : Reconnaissance faciale -->
        <div class="card">
            <div class="card-header">
                <i class="fas fa-camera"></i> Reconnaissance faciale
            </div>
            <div class="card-body">
                <?php if (!empty($user['face_encoding'])): ?>
                    <div class="alert-success-custom mb-3" style="background: #d4edda; border-left-color: #28a745;">
                        <i class="fas fa-check-circle me-2"></i> ✅ Visage enregistré
                    </div>
                <?php endif; ?>
                
                <div id="faceCaptureSection" style="<?= !empty($user['face_encoding']) ? 'display:none;' : '' ?>">
                    <div class="text-center">
                        <div id="faceVideoContainer">
                            <video id="faceVideo" width="450" height="340" autoplay playsinline style="border-radius: 16px; border: 2px solid #2A7FAA; background: #000; max-width: 100%;"></video>
                            <canvas id="faceCanvas" style="display: none;"></canvas>
                        </div>
                        <div class="mt-4">
                            <button type="button" class="btn-save" onclick="captureFace()" id="captureFaceBtn">
                                <i class="fas fa-camera me-2"></i> Enregistrer mon visage
                            </button>
                            <button type="button" class="btn-cancel" onclick="stopFaceCamera()" id="stopCameraBtn" style="display:none;">
                                <i class="fas fa-stop me-2"></i> Arrêter
                            </button>
                        </div>
                        <div id="faceStatus" class="mt-3"></div>
                        <div class="alert alert-info mt-3" style="background: #e7f3ff; border: none; border-radius: 12px; font-size: 12px;">
                            <i class="fas fa-info-circle me-2"></i>
                            Placez votre visage face à la caméra dans un endroit bien éclairé
                        </div>
                    </div>
                </div>
                
                <?php if (!empty($user['face_encoding'])): ?>
                    <div class="text-center">
                        <button type="button" class="btn-cancel" onclick="deleteFace()">
                            <i class="fas fa-trash-alt me-2"></i> Supprimer mon visage
                        </button>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- SECTION 4 : Statistiques -->
        <div class="stats-row">
            <div class="stat-card">
                <i class="fas fa-calendar-check"></i>
                <h3><?= $stats['total_rdv'] ?? 0 ?></h3>
                <p>Rendez-vous</p>
            </div>
            <div class="stat-card">
                <i class="fas fa-clock"></i>
                <h3><?= $stats['rdv_avenir'] ?? 0 ?></h3>
                <p>À venir</p>
            </div>
            <div class="stat-card">
                <i class="fas fa-star"></i>
                <h3><?= $stats['note_moyenne'] ?? '—' ?></h3>
                <p>Note moyenne</p>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // ═══ FUNCTIONS ═══
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

        // Avatar Upload
        function uploadAvatarOnly() {
            const input = document.getElementById('avatarInput');
            if (input.files && input.files[0]) {
                const file = input.files[0];
                const allowedTypes = ['image/jpeg', 'image/png', 'image/jpg', 'image/gif', 'image/webp'];
                
                if (!allowedTypes.includes(file.type)) {
                    showToast('Format non supporté. Utilisez JPG, PNG, GIF ou WEBP.', 'error');
                    input.value = '';
                    return;
                }
                
                if (file.size > 2 * 1024 * 1024) {
                    showToast('L\'image ne doit pas dépasser 2 Mo.', 'error');
                    input.value = '';
                    return;
                }
                
                document.getElementById('avatarForm').submit();
            }
        }

        // Profile Validation
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
                showFieldError('email', 'Email invalide.');
                isValid = false;
            }
            
            return isValid;
        }

        // Password Validation
        function validatePasswordForm() {
            let isValid = true;
            
            clearFieldError('currentPassword');
            clearFieldError('newPassword');
            clearFieldError('confirmPassword');
            
            const currentPassword = document.getElementById('currentPassword').value;
            const newPassword = document.getElementById('newPassword').value;
            const confirmPassword = document.getElementById('confirmPassword').value;
            
            if (!currentPassword) {
                showFieldError('currentPassword', 'Mot de passe actuel requis.');
                isValid = false;
            }
            
            if (!newPassword) {
                showFieldError('newPassword', 'Nouveau mot de passe requis.');
                isValid = false;
            } else if (newPassword.length < 8) {
                showFieldError('newPassword', '8 caractères minimum.');
                isValid = false;
            } else if (!/[A-Z]/.test(newPassword)) {
                showFieldError('newPassword', 'Une majuscule requise.');
                isValid = false;
            } else if (!/[0-9]/.test(newPassword)) {
                showFieldError('newPassword', 'Un chiffre requis.');
                isValid = false;
            }
            
            if (!confirmPassword) {
                showFieldError('confirmPassword', 'Confirmation requise.');
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
                Length: '8 caractères',
                Upper: '1 majuscule',
                Number: '1 chiffre'
            };
            Object.keys(checks).forEach(k => {
                const el = document.getElementById('req' + k);
                const ok = checks[k];
                el.className = ok ? 'requirement-valid' : 'requirement-invalid';
                el.innerHTML = '<i class="fas fa-' + (ok ? 'check-circle' : 'circle') + '"></i> ' + labels[k];
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

        // Face Recognition
        let faceStream = null;
        let faceVideo = null;

        function startFaceCamera() {
            faceVideo = document.getElementById('faceVideo');
            if (navigator.mediaDevices && navigator.mediaDevices.getUserMedia) {
                navigator.mediaDevices.getUserMedia({ video: true })
                    .then(function(stream) {
                        faceStream = stream;
                        faceVideo.srcObject = stream;
                        faceVideo.play();
                        document.getElementById('captureFaceBtn').style.display = 'inline-block';
                        document.getElementById('stopCameraBtn').style.display = 'inline-block';
                    })
                    .catch(function(err) {
                        document.getElementById('faceStatus').innerHTML = '<div class="alert alert-danger">Erreur caméra: ' + err.message + '</div>';
                    });
            }
        }

        function stopFaceCamera() {
            if (faceStream) {
                faceStream.getTracks().forEach(track => track.stop());
                faceStream = null;
            }
            if (faceVideo) {
                faceVideo.srcObject = null;
            }
            document.getElementById('captureFaceBtn').style.display = 'none';
            document.getElementById('stopCameraBtn').style.display = 'none';
        }

        function captureFace() {
            let video = document.getElementById('faceVideo');
            let canvas = document.getElementById('faceCanvas');
            let context = canvas.getContext('2d');
            
            canvas.width = video.videoWidth;
            canvas.height = video.videoHeight;
            
            context.drawImage(video, 0, 0, canvas.width, canvas.height);
            
            let imageData = canvas.toDataURL('image/jpeg', 0.8);
            
            document.getElementById('faceStatus').innerHTML = '<div class="alert alert-info"><i class="fas fa-spinner fa-spin me-2"></i>Envoi...</div>';
            
            fetch('index.php?page=profil', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ image: imageData, action: 'register_face' })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    document.getElementById('faceStatus').innerHTML = '<div class="alert alert-success"><i class="fas fa-check-circle me-2"></i>' + data.message + '</div>';
                    stopFaceCamera();
                    setTimeout(() => {
                        location.reload();
                    }, 2000);
                } else {
                    document.getElementById('faceStatus').innerHTML = '<div class="alert alert-danger"><i class="fas fa-exclamation-circle me-2"></i>' + data.message + '</div>';
                }
            })
            .catch(error => {
                document.getElementById('faceStatus').innerHTML = '<div class="alert alert-danger">Erreur: ' + error + '</div>';
            });
        }

        function deleteFace() {
            if (confirm('Êtes-vous sûr de vouloir supprimer votre visage enregistré ?')) {
                fetch('index.php?page=api', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ action: 'delete_face' })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        showToast(data.message || 'Erreur lors de la suppression', 'error');
                    }
                })
                .catch(error => {
                    showToast('Erreur réseau', 'error');
                });
            }
        }

        // Event Listeners
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

        if (document.getElementById('newPassword')) {
            document.getElementById('newPassword').addEventListener('input', updatePasswordRequirements);
        }

        // Auto-hide alerts
        setTimeout(() => {
            document.querySelectorAll('.alert-success-custom, .alert-error-custom').forEach(el => {
                if (el !== document.getElementById('faceStatus')?.querySelector('.alert')) {
                    el.style.opacity = '0';
                    setTimeout(() => { if(el.parentNode) el.style.display = 'none'; }, 300);
                }
            });
        }, 5000);

        // Start face camera if needed
        document.addEventListener('DOMContentLoaded', function() {
            <?php if (empty($user['face_encoding'])): ?>
            startFaceCamera();
            <?php endif; ?>
        });
    </script>
</body>
</html>// update
