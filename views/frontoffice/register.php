<?php
$errors = $errors ?? [];
$old = $old ?? [];
$userType = $userType ?? 'patient';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inscription - Valorys</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { background: linear-gradient(135deg, #2A7FAA 0%, #4CAF50 100%); min-height: 100vh; display: flex; align-items: center; justify-content: center; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; padding: 20px; }
        .register-card { background: white; border-radius: 20px; box-shadow: 0 20px 40px rgba(0,0,0,0.1); overflow: hidden; width: 100%; max-width: 650px; animation: fadeIn 0.5s ease; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(-20px); } to { opacity: 1; transform: translateY(0); } }
        .register-header { background: linear-gradient(135deg, #2A7FAA 0%, #4CAF50 100%); color: white; padding: 25px; text-align: center; }
        .register-header h2 { margin: 0; font-size: 1.8rem; }
        .register-header p { margin: 5px 0 0; opacity: 0.9; }
        .register-body { padding: 30px; }
        .form-control, .form-select { border-radius: 10px; padding: 10px 15px; border: 1px solid #ddd; transition: all 0.3s; }
        .form-control:focus, .form-select:focus { border-color: #4CAF50; box-shadow: 0 0 0 3px rgba(76,175,80,0.1); outline: none; }
        .form-control.error, .form-select.error { border-color: #dc3545 !important; background-color: #fff8f8; }
        .btn-register { background: linear-gradient(135deg, #2A7FAA 0%, #4CAF50 100%); color: white; border-radius: 10px; padding: 12px; width: 100%; font-weight: bold; font-size: 16px; border: none; transition: all 0.3s; }
        .btn-register:hover { transform: translateY(-2px); box-shadow: 0 5px 15px rgba(42,127,170,0.3); }
        .role-selector { display: flex; gap: 15px; margin-bottom: 25px; }
        .role-option { flex: 1; text-align: center; padding: 12px; border: 2px solid #e0e0e0; border-radius: 12px; cursor: pointer; transition: all 0.3s; background: #f9f9f9; }
        .role-option i { font-size: 24px; margin-bottom: 5px; display: block; }
        .role-option.active { border-color: #4CAF50; background: #e8f5e9; color: #4CAF50; }
        .section-title { font-size: 14px; font-weight: bold; margin: 20px 0 15px 0; color: #2A7FAA; border-left: 4px solid #4CAF50; padding-left: 10px; }
        .password-requirements { font-size: 11px; margin-top: 5px; }
        .requirement-valid { color: #4CAF50; }
        .requirement-invalid { color: #dc3545; }
        .field-error { font-size: 11px; margin-top: 5px; color: #dc3545; }
        .field-error i { margin-right: 4px; }
        .medecin-fields { display: none; }
        .login-link { color: #2A7FAA; text-decoration: none; }
        .login-link:hover { text-decoration: underline; }
        hr { margin: 20px 0; }
        .alert-custom { border-radius: 10px; padding: 12px 15px; margin-bottom: 20px; }
        .error-wrap { border: 1px solid #dc3545; border-radius: 8px; padding: 8px; background: #fff8f8; }
    </style>
</head>
<body>
    <div class="register-card">
        <div class="register-header">
            <h2><i class="fas fa-user-plus me-2"></i>Inscription</h2>
            <p>Rejoignez Valorys et facilitez votre accès aux soins</p>
        </div>

        <div class="register-body">
            <!-- Affichage des erreurs générales -->
            <?php if (!empty($errors['__form'])): ?>
                <div class="alert alert-danger alert-custom">
                    <i class="fas fa-exclamation-circle me-2"></i><?= htmlspecialchars($errors['__form']) ?>
                </div>
            <?php endif; ?>

            <div class="role-selector">
                <div class="role-option <?= ($userType === 'patient') ? 'active' : '' ?>" data-role="patient">
                    <i class="fas fa-user"></i>
                    <div>Patient</div>
                </div>
                <div class="role-option <?= ($userType === 'medecin') ? 'active' : '' ?>" data-role="medecin">
                    <i class="fas fa-user-md"></i>
                    <div>Médecin</div>
                </div>
            </div>

            <form id="registerForm" method="POST" action="index.php?page=register">
                <input type="hidden" name="role" id="selectedRole" value="<?= htmlspecialchars($userType) ?>">

                <div class="section-title"><i class="fas fa-user-circle me-1"></i> Informations personnelles</div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Nom <span class="text-danger">*</span></label>
                        <input type="text" name="nom" id="nom" class="form-control <?= isset($errors['nom']) ? 'error' : '' ?>"
                               value="<?= htmlspecialchars($old['nom'] ?? '') ?>" placeholder="Votre nom">
                        <?php if (isset($errors['nom'])): ?>
                            <div class="field-error"><i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($errors['nom']) ?></div>
                        <?php endif; ?>
                        <div class="field-error" id="nom-error-js" style="display:none"></div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Prénom <span class="text-danger">*</span></label>
                        <input type="text" name="prenom" id="prenom" class="form-control <?= isset($errors['prenom']) ? 'error' : '' ?>"
                               value="<?= htmlspecialchars($old['prenom'] ?? '') ?>" placeholder="Votre prénom">
                        <?php if (isset($errors['prenom'])): ?>
                            <div class="field-error"><i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($errors['prenom']) ?></div>
                        <?php endif; ?>
                        <div class="field-error" id="prenom-error-js" style="display:none"></div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Email <span class="text-danger">*</span></label>
                        <input type="email" name="email" id="email" class="form-control <?= isset($errors['email']) ? 'error' : '' ?>"
                               value="<?= htmlspecialchars($old['email'] ?? '') ?>" placeholder="exemple@email.com">
                        <?php if (isset($errors['email'])): ?>
                            <div class="field-error"><i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($errors['email']) ?></div>
                        <?php endif; ?>
                        <div class="field-error" id="email-error-js" style="display:none"></div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Téléphone <span class="text-danger">*</span></label>
                        <input type="tel" name="telephone" id="telephone" class="form-control <?= isset($errors['telephone']) ? 'error' : '' ?>"
                               value="<?= htmlspecialchars($old['telephone'] ?? '') ?>" placeholder="+216 XX XXX XXX">
                        <?php if (isset($errors['telephone'])): ?>
                            <div class="field-error"><i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($errors['telephone']) ?></div>
                        <?php endif; ?>
                        <div class="field-error" id="telephone-error-js" style="display:none"></div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Mot de passe <span class="text-danger">*</span></label>
                        <input type="password" name="password" id="password" class="form-control <?= isset($errors['password']) ? 'error' : '' ?>"
                               placeholder="••••••••">
                        <?php if (isset($errors['password'])): ?>
                            <div class="field-error"><i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($errors['password']) ?></div>
                        <?php endif; ?>
                        <div class="field-error" id="password-error-js" style="display:none"></div>
                        <div class="password-requirements">
                            <span id="reqLength" class="requirement-invalid"><i class="fas fa-circle me-1"></i> Au moins 8 caractères</span><br>
                            <span id="reqUpper" class="requirement-invalid"><i class="fas fa-circle me-1"></i> Au moins une majuscule</span><br>
                            <span id="reqNumber" class="requirement-invalid"><i class="fas fa-circle me-1"></i> Au moins un chiffre</span>
                        </div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Confirmer le mot de passe <span class="text-danger">*</span></label>
                        <input type="password" name="password_confirm" id="password_confirm" class="form-control <?= isset($errors['password_confirm']) ? 'error' : '' ?>"
                               placeholder="••••••••">
                        <?php if (isset($errors['password_confirm'])): ?>
                            <div class="field-error"><i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($errors['password_confirm']) ?></div>
                        <?php endif; ?>
                        <div class="field-error" id="password_confirm-error-js" style="display:none"></div>
                    </div>
                </div>

                <!-- Champs médecin -->
                <div id="medecinFields" class="medecin-fields">
                    <div class="section-title"><i class="fas fa-stethoscope me-1"></i> Informations professionnelles</div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Spécialité <span class="text-danger">*</span></label>
                            <select name="specialite" id="specialite" class="form-select <?= isset($errors['specialite']) ? 'error' : '' ?>">
                                <option value="">Sélectionner une spécialité</option>
                                <option value="Cardiologue" <?= ($old['specialite'] ?? '') == 'Cardiologue' ? 'selected' : '' ?>>Cardiologue</option>
                                <option value="Dermatologue" <?= ($old['specialite'] ?? '') == 'Dermatologue' ? 'selected' : '' ?>>Dermatologue</option>
                                <option value="Gynécologue" <?= ($old['specialite'] ?? '') == 'Gynécologue' ? 'selected' : '' ?>>Gynécologue</option>
                                <option value="Pédiatre" <?= ($old['specialite'] ?? '') == 'Pédiatre' ? 'selected' : '' ?>>Pédiatre</option>
                                <option value="Généraliste" <?= ($old['specialite'] ?? '') == 'Généraliste' ? 'selected' : '' ?>>Généraliste</option>
                                <option value="Ophtalmologue" <?= ($old['specialite'] ?? '') == 'Ophtalmologue' ? 'selected' : '' ?>>Ophtalmologue</option>
                                <option value="Orthopédiste" <?= ($old['specialite'] ?? '') == 'Orthopédiste' ? 'selected' : '' ?>>Orthopédiste</option>
                                <option value="Neurologue" <?= ($old['specialite'] ?? '') == 'Neurologue' ? 'selected' : '' ?>>Neurologue</option>
                                <option value="Psychiatre" <?= ($old['specialite'] ?? '') == 'Psychiatre' ? 'selected' : '' ?>>Psychiatre</option>
                                <option value="Dentiste" <?= ($old['specialite'] ?? '') == 'Dentiste' ? 'selected' : '' ?>>Dentiste</option>
                            </select>
                            <?php if (isset($errors['specialite'])): ?>
                                <div class="field-error"><i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($errors['specialite']) ?></div>
                            <?php endif; ?>
                            <div class="field-error" id="specialite-error-js" style="display:none"></div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Numéro d'ordre <span class="text-danger">*</span></label>
                            <input type="text" name="numero_ordre" id="numero_ordre" class="form-control <?= isset($errors['numero_ordre']) ? 'error' : '' ?>"
                                   value="<?= htmlspecialchars($old['numero_ordre'] ?? '') ?>" placeholder="Ex: 12345">
                            <?php if (isset($errors['numero_ordre'])): ?>
                                <div class="field-error"><i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($errors['numero_ordre']) ?></div>
                            <?php endif; ?>
                            <div class="field-error" id="numero_ordre-error-js" style="display:none"></div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Tarif de consultation (DT)</label>
                            <input type="number" name="tarif" class="form-control" value="<?= htmlspecialchars($old['tarif'] ?? '50') ?>" step="5">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Années d'expérience</label>
                            <input type="number" name="experience" class="form-control" value="<?= htmlspecialchars($old['experience'] ?? '') ?>">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Adresse du cabinet</label>
                        <textarea name="adresse_cabinet" class="form-control" rows="2" placeholder="Adresse complète du cabinet"><?= htmlspecialchars($old['adresse_cabinet'] ?? '') ?></textarea>
                    </div>
                </div>

                <div class="form-check mb-3 <?= isset($errors['terms']) ? 'error-wrap' : '' ?>">
                    <input type="checkbox" class="form-check-input" name="terms" value="1" id="terms"
                           <?= isset($old['terms']) && $old['terms'] ? 'checked' : '' ?>>
                    <label class="form-check-label" for="terms">
                        J'accepte les <a href="#" class="login-link">conditions d'utilisation</a>
                        et la <a href="#" class="login-link">politique de confidentialité</a>
                    </label>
                </div>
                <?php if (isset($errors['terms'])): ?>
                    <div class="field-error"><i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($errors['terms']) ?></div>
                <?php endif; ?>
                <div class="field-error" id="terms-error-js" style="display:none"></div>

                <button type="submit" class="btn-register mt-3">
                    <i class="fas fa-user-plus me-2"></i> S'inscrire
                </button>
            </form>

            <hr>
            <div class="text-center">
                <p class="mb-0">Déjà un compte ? <a href="index.php?page=login" class="login-link">Se connecter</a></p>
            </div>
        </div>
    </div>

    <script>
        // Gestion du rôle (Patient/Médecin)
        const roleOptions = document.querySelectorAll('.role-option');
        const medecinFields = document.getElementById('medecinFields');
        const selectedRole = document.getElementById('selectedRole');

        roleOptions.forEach(option => {
            option.addEventListener('click', function() {
                roleOptions.forEach(opt => opt.classList.remove('active'));
                this.classList.add('active');
                const role = this.dataset.role;
                selectedRole.value = role;
                medecinFields.style.display = role === 'medecin' ? 'block' : 'none';
            });
        });

        // Validation mot de passe en temps réel
        const passwordInput = document.getElementById('password');
        const reqLength = document.getElementById('reqLength');
        const reqUpper = document.getElementById('reqUpper');
        const reqNumber = document.getElementById('reqNumber');

        function validatePassword(password) {
            return {
                length: password.length >= 8,
                upper: /[A-Z]/.test(password),
                number: /[0-9]/.test(password)
            };
        }

        function updatePasswordUI() {
            const password = passwordInput.value;
            const validation = validatePassword(password);
            reqLength.className = validation.length ? 'requirement-valid' : 'requirement-invalid';
            reqUpper.className = validation.upper ? 'requirement-valid' : 'requirement-invalid';
            reqNumber.className = validation.number ? 'requirement-valid' : 'requirement-invalid';
            reqLength.innerHTML = `<i class="fas fa-${validation.length ? 'check-' : ''}circle me-1"></i> Au moins 8 caractères`;
            reqUpper.innerHTML = `<i class="fas fa-${validation.upper ? 'check-' : ''}circle me-1"></i> Au moins une majuscule`;
            reqNumber.innerHTML = `<i class="fas fa-${validation.number ? 'check-' : ''}circle me-1"></i> Au moins un chiffre`;
        }

        passwordInput.addEventListener('input', updatePasswordUI);
        updatePasswordUI();

        // Validation avant soumission
        const form = document.getElementById('registerForm');

        function showError(fieldId, message) {
            const errorDiv = document.getElementById(fieldId + '-error-js');
            if (errorDiv) {
                errorDiv.textContent = message;
                errorDiv.style.display = 'block';
            }
            const input = document.getElementById(fieldId);
            if (input) input.classList.add('error');
        }

        function clearErrors() {
            document.querySelectorAll('.field-error').forEach(el => el.style.display = 'none');
            document.querySelectorAll('.form-control, .form-select').forEach(el => el.classList.remove('error'));
        }

        form.addEventListener('submit', function(e) {
            clearErrors();

            let isValid = true;
            const nom = document.getElementById('nom').value.trim();
            const prenom = document.getElementById('prenom').value.trim();
            const email = document.getElementById('email').value.trim();
            const telephone = document.getElementById('telephone').value.trim();
            const password = document.getElementById('password').value;
            const passwordConfirm = document.getElementById('password_confirm').value;
            const terms = document.getElementById('terms').checked;
            const role = selectedRole.value;

            if (!nom) { showError('nom', 'Le nom est obligatoire'); isValid = false; }
            if (!prenom) { showError('prenom', 'Le prénom est obligatoire'); isValid = false; }
            if (!email) { showError('email', 'L\'email est obligatoire'); isValid = false; }
            if (!telephone) { showError('telephone', 'Le téléphone est obligatoire'); isValid = false; }
            if (!password) { showError('password', 'Le mot de passe est obligatoire'); isValid = false; }
            if (password !== passwordConfirm) { showError('password_confirm', 'Les mots de passe ne correspondent pas'); isValid = false; }

            const pwdValid = validatePassword(password);
            if (password && (!pwdValid.length || !pwdValid.upper || !pwdValid.number)) {
                showError('password', '8 caractères, une majuscule et un chiffre minimum');
                isValid = false;
            }

            if (!terms) { showError('terms', 'Vous devez accepter les conditions'); isValid = false; }

            if (role === 'medecin') {
                const specialite = document.getElementById('specialite').value;
                const numeroOrdre = document.getElementById('numero_ordre').value.trim();
                if (!specialite) { showError('specialite', 'Veuillez sélectionner votre spécialité'); isValid = false; }
                if (!numeroOrdre) { showError('numero_ordre', 'Le numéro d\'ordre est obligatoire'); isValid = false; }
            }

            if (!isValid) {
                e.preventDefault();
            }
        });
    </script>
</body>
</html>