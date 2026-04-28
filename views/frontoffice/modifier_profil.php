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

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            font-family: 'Inter', 'Segoe UI', sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 40px 20px;
        }

        .edit-profile-container {
            max-width: 550px;
            width: 100%;
            margin: 0 auto;
        }

        /* Card Principal */
        .profile-card {
            background: white;
            border-radius: 28px;
            box-shadow: var(--card-shadow);
            overflow: hidden;
            transition: var(--transition);
        }

        /* Header avec gradient */
        .card-header {
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            padding: 40px 28px;
            text-align: center;
            position: relative;
        }
        .card-header::before {
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
        .avatar-icon {
            width: 90px;
            height: 90px;
            background: rgba(255,255,255,0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            position: relative;
            z-index: 1;
            backdrop-filter: blur(10px);
        }
        .avatar-icon i {
            font-size: 45px;
            color: white;
        }
        .card-header h2 {
            color: white;
            font-size: 28px;
            font-weight: 700;
            margin: 0;
            position: relative;
            z-index: 1;
        }
        .card-header p {
            color: rgba(255,255,255,0.9);
            margin: 8px 0 0;
            font-size: 14px;
            position: relative;
            z-index: 1;
        }

        /* Corps de la carte */
        .card-body {
            padding: 32px 28px;
        }

        /* Groupes de formulaire */
        .form-group {
            margin-bottom: 24px;
        }
        .form-label {
            font-weight: 600;
            color: #2d3748;
            margin-bottom: 8px;
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            display: block;
        }
        .input-icon {
            position: relative;
        }
        .input-icon i {
            position: absolute;
            left: 16px;
            top: 50%;
            transform: translateY(-50%);
            color: #a0aec0;
            font-size: 16px;
            pointer-events: none;
            z-index: 1;
        }
        .form-control {
            width: 100%;
            border-radius: 14px;
            padding: 14px 16px 14px 46px;
            border: 1.5px solid #e2e8f0;
            transition: var(--transition);
            font-size: 15px;
            background: #f8fafc;
        }
        .form-control:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(42, 127, 170, 0.1);
            outline: none;
            background: white;
        }
        .form-control::placeholder {
            color: #cbd5e0;
            font-size: 14px;
        }

        /* Section mot de passe */
        .password-section {
            background: #f8fafc;
            border-radius: 20px;
            padding: 20px;
            margin: 24px 0;
            border: 1px solid #e2e8f0;
        }
        .password-section-title {
            font-size: 16px;
            font-weight: 600;
            color: var(--primary);
            margin-bottom: 16px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .password-section-title i {
            font-size: 18px;
        }
        .password-hint {
            font-size: 12px;
            color: #718096;
            margin-top: 8px;
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
        }
        .password-hint i {
            font-size: 11px;
        }

        /* Alertes */
        .alert-custom {
            border-radius: 14px;
            padding: 14px 18px;
            margin-bottom: 24px;
            display: flex;
            align-items: center;
            gap: 12px;
            border: none;
            font-size: 14px;
        }
        .alert-success-custom {
            background: #d4edda;
            color: #155724;
            border-left: 4px solid #28a745;
        }
        .alert-error-custom {
            background: #f8d7da;
            color: #721c24;
            border-left: 4px solid #dc3545;
        }
        .alert-info-custom {
            background: #e7f3ff;
            color: #004085;
            border-left: 4px solid #17a2b8;
        }

        /* Boutons */
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
        }
        .btn-save:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(76, 175, 80, 0.3);
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

        /* Lien vers profil */
        .back-link {
            text-align: center;
            margin-top: 20px;
        }
        .back-link a {
            color: white;
            text-decoration: none;
            font-size: 14px;
            opacity: 0.9;
            transition: var(--transition);
        }
        .back-link a:hover {
            opacity: 1;
            text-decoration: underline;
        }

        /* Responsive */
        @media (max-width: 480px) {
            .card-body {
                padding: 24px 20px;
            }
            .card-header {
                padding: 30px 20px;
            }
            .card-header h2 {
                font-size: 24px;
            }
        }
    </style>
</head>
<body>
    <div class="edit-profile-container">
        <div class="profile-card">
            <!-- Header -->
            <div class="card-header">
                <div class="avatar-icon">
                    <i class="fas fa-user-edit"></i>
                </div>
                <h2>Modifier mon profil</h2>
                <p>Mettez à jour vos informations personnelles</p>
            </div>

            <!-- Body -->
            <div class="card-body">
                <!-- Messages d'alerte -->
                <?php if (isset($_SESSION['success'])): ?>
                    <div class="alert-custom alert-success-custom">
                        <i class="fas fa-check-circle fa-lg"></i>
                        <span><?= htmlspecialchars($_SESSION['success']) ?></span>
                    </div>
                    <?php unset($_SESSION['success']); ?>
                <?php endif; ?>

                <?php if (isset($_SESSION['error'])): ?>
                    <div class="alert-custom alert-error-custom">
                        <i class="fas fa-exclamation-circle fa-lg"></i>
                        <span><?= htmlspecialchars($_SESSION['error']) ?></span>
                    </div>
                    <?php unset($_SESSION['error']); ?>
                <?php endif; ?>

                <!-- Formulaire modification infos -->
                <form method="POST" action="index.php?page=modifier_profil" id="profileForm">
                    <input type="hidden" name="action" value="update_profile">
                    
                    <div class="form-group">
                        <label class="form-label">
                            <i class="fas fa-user me-2"></i>Nom complet
                        </label>
                        <div class="input-icon">
                            <i class="fas fa-user"></i>
                            <input type="text" name="nom" class="form-control" 
                                   value="<?= htmlspecialchars($user['nom'] ?? '') ?>" 
                                   placeholder="Votre nom" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <div class="input-icon">
                            <i class="fas fa-user"></i>
                            <input type="text" name="prenom" class="form-control" 
                                   value="<?= htmlspecialchars($user['prenom'] ?? '') ?>" 
                                   placeholder="Votre prénom" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">
                            <i class="fas fa-envelope me-2"></i>Adresse email
                        </label>
                        <div class="input-icon">
                            <i class="fas fa-envelope"></i>
                            <input type="email" name="email" class="form-control" 
                                   value="<?= htmlspecialchars($user['email'] ?? '') ?>" 
                                   placeholder="exemple@email.com" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">
                            <i class="fas fa-phone me-2"></i>Numéro de téléphone
                        </label>
                        <div class="input-icon">
                            <i class="fas fa-phone"></i>
                            <input type="tel" name="telephone" class="form-control" 
                                   value="<?= htmlspecialchars($user['telephone'] ?? '') ?>" 
                                   placeholder="+33 X XX XX XX XX">
                        </div>
                    </div>

                    <!-- Section Mot de passe optionnel -->
                    <div class="password-section">
                        <div class="password-section-title">
                            <i class="fas fa-lock"></i>
                            Mot de passe <span style="font-size: 12px; font-weight: normal;">(optionnel)</span>
                        </div>
                        
                        <div class="form-group" style="margin-bottom: 16px;">
                            <div class="input-icon">
                                <i class="fas fa-key"></i>
                                <input type="password" name="password" id="password" class="form-control" 
                                       placeholder="Nouveau mot de passe">
                            </div>
                            <div class="password-hint">
                                <i class="fas fa-info-circle"></i>
                                Laisser vide pour ne pas changer
                            </div>
                        </div>

                        <div class="form-group" style="margin-bottom: 0;">
                            <div class="input-icon">
                                <i class="fas fa-check-circle"></i>
                                <input type="password" name="confirm_password" id="confirmPassword" class="form-control" 
                                       placeholder="Confirmer le mot de passe">
                            </div>
                            <div class="password-hint">
                                <i class="fas fa-shield-alt"></i>
                                Minimum 6 caractères
                            </div>
                        </div>
                    </div>

                    <!-- Message de conseil -->
                    <div class="alert-custom alert-info-custom" style="margin-bottom: 24px;">
                        <i class="fas fa-lightbulb fa-lg"></i>
                        <span>Mettez à jour vos informations pour une meilleure expérience.</span>
                    </div>

                    <!-- Boutons -->
                    <button type="submit" class="btn-save">
                        <i class="fas fa-save me-2"></i>Enregistrer les modifications
                    </button>
                    
                    <a href="index.php?page=profil" class="btn-cancel">
                        <i class="fas fa-times me-2"></i>Annuler
                    </a>
                </form>
            </div>
        </div>

        <!-- Lien retour -->
        <div class="back-link">
            <a href="index.php?page=profil">
                <i class="fas fa-arrow-left me-2"></i>Retour à mon profil
            </a>
        </div>
    </div>

    <script>
        // Validation du formulaire
        document.getElementById('profileForm').addEventListener('submit', function(e) {
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirmPassword').value;
            
            if (password !== confirmPassword) {
                e.preventDefault();
                alert('Les mots de passe ne correspondent pas');
                return false;
            }
            
            if (password && password.length < 6) {
                e.preventDefault();
                alert('Le mot de passe doit contenir au moins 6 caractères');
                return false;
            }
        });

        // Auto-hide des alertes après 5 secondes
        setTimeout(() => {
            document.querySelectorAll('.alert-custom').forEach(alert => {
                alert.style.transition = 'opacity 0.5s';
                alert.style.opacity = '0';
                setTimeout(() => {
                    if (alert.parentNode) alert.remove();
                }, 500);
            });
        }, 5000);
    </script>
</body>
</html>// update
