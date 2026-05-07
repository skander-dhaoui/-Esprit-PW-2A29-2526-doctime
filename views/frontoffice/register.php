<?php
// Les variables $errors et $old sont passées par AuthController::showRegister()
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inscription - DocTime</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { background: linear-gradient(135deg, #2A7FAA 0%, #4CAF50 100%); min-height: 100vh; display: flex; align-items: center; justify-content: center; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; padding: 20px; }

        .register-card { background: white; border-radius: 20px; box-shadow: 0 20px 40px rgba(0,0,0,0.1); overflow: hidden; width: 100%; max-width: 600px; animation: fadeIn 0.5s ease; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(-20px); } to { opacity: 1; transform: translateY(0); } }

        .register-header { background: linear-gradient(135deg, #2A7FAA 0%, #3e8e41 100%); color: white; padding: 25px; text-align: center; }
        .register-header .logo-container { display: flex; justify-content: center; margin-bottom: 8px; }
        .register-header .logo-container img { height: 80px; width: auto; object-fit: contain; filter: drop-shadow(0 2px 8px rgba(0,0,0,0.2)); }
        .register-header p { font-size: 13px; opacity: 0.9; }

        .register-body { padding: 30px; }

        .form-control, .form-select { border-radius: 10px; padding: 10px 15px; border: 1px solid #ddd; transition: all 0.3s; }
        .form-control:focus, .form-select:focus { border-color: #4CAF50; box-shadow: 0 0 0 3px rgba(76,175,80,0.1); }
        .form-control.is-invalid, .form-select.is-invalid { border-color: #dc3545 !important; background-color: #fff5f5; background-image: none; }
        .form-control.is-invalid:focus, .form-select.is-invalid:focus { border-color: #dc3545; box-shadow: 0 0 0 3px rgba(220,53,69,0.1); }

        .field-error { color: #dc3545; font-size: 12px; margin-top: 5px; font-weight: 500; display: none; align-items: center; gap: 4px; }
        .field-error.show { display: flex; }

        .btn-register { background: #4CAF50; color: white; border-radius: 10px; padding: 12px; width: 100%; font-weight: bold; font-size: 16px; border: none; transition: all 0.3s; }
        .btn-register:hover { background: #2A7FAA; transform: translateY(-2px); }
        .btn-register:disabled { opacity: 0.7; transform: none; cursor: not-allowed; }

        .role-selector { display: flex; gap: 15px; margin-bottom: 25px; }
        .role-option { flex: 1; text-align: center; padding: 12px; border: 2px solid #e0e0e0; border-radius: 12px; cursor: pointer; transition: all 0.3s; background: #f9f9f9; }
        .role-option i { font-size: 24px; margin-bottom: 5px; display: block; }
        .role-option.active { border-color: #4CAF50; background: #e8f5e9; color: #4CAF50; }
        .role-option:hover { border-color: #4CAF50; transform: translateY(-2px); }

        .section-title { font-size: 14px; font-weight: bold; margin: 20px 0 15px 0; color: #2A7FAA; border-left: 4px solid #4CAF50; padding-left: 10px; }

        .password-requirements { font-size: 12px; color: #999; margin-top: 5px; }
        .requirement-valid { color: #4CAF50; }
        .requirement-invalid { color: #dc3545; }

        /* Alert session PHP uniquement (succès) */
        .alert-session { border-radius: 10px; padding: 12px 15px; margin-bottom: 20px; display: flex; align-items: center; gap: 8px; font-size: 14px; }
        .alert-session-success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }

        .login-link { color: #2A7FAA; text-decoration: none; }
        .login-link:hover { color: #4CAF50; text-decoration: underline; }
        hr { margin: 20px 0; }

        .medecin-fields { display: none; animation: fadeIn 0.3s ease; }

        /* Checkbox error */
        .terms-error { color: #dc3545; font-size: 12px; margin-top: 5px; font-weight: 500; display: none; align-items: center; gap: 4px; }
        .terms-error.show { display: flex; }

        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            20% { transform: translateX(-6px); }
            40% { transform: translateX(6px); }
            60% { transform: translateX(-4px); }
            80% { transform: translateX(4px); }
        }
        .shake { animation: shake 0.35s ease-in-out; }
    </style>
</head>
<body>
    <div class="register-card">
        <div class="register-header">
            <div class="logo-container">
                <img src="assets/images/logo_doctime.png" alt="DocTime Logo"
                     onerror="this.style.display='none'; document.getElementById('logoFallback').style.display='block';">
                <i id="logoFallback" class="fas fa-stethoscope" style="display:none; font-size:50px;"></i>
            </div>
            <p>Rejoignez DocTime et facilitez votre accès aux soins</p>
        </div>

        <div class="register-body">

            <?php if (!empty($_SESSION['success'])): ?>
                <div class="alert-session alert-session-success">
                    <i class="fas fa-check-circle"></i>
                    <?= htmlspecialchars($_SESSION['success']) ?>
                </div>
                <?php unset($_SESSION['success']); ?>
            <?php endif; ?>

            <div class="role-selector">
                <div class="role-option active" data-role="patient">
                    <i class="fas fa-user"></i><div>Patient</div>
                </div>
                <div class="role-option" data-role="medecin">
                    <i class="fas fa-user-md"></i><div>Médecin</div>
                </div>
            </div>

            <form id="registerForm" method="POST" action="index.php?page=register" novalidate>
                <input type="hidden" name="role" id="selectedRole" value="<?= htmlspecialchars($old['role'] ?? 'patient') ?>">

                <div class="section-title"><i class="fas fa-user-circle me-1"></i> Informations personnelles</div>

                <div class="row">
                    <!-- Nom -->
                    <div class="col-md-6 mb-3">
                        <label class="form-label" for="nom">Nom <span class="text-danger">*</span></label>
                        <input type="text" name="nom" id="nom"
                               class="form-control <?= !empty($errors['nom']) ? 'is-invalid' : '' ?>"
                               placeholder="Votre nom"
                               value="<?= htmlspecialchars($old['nom'] ?? '') ?>"
                               autocomplete="family-name">
                        <div class="field-error <?= !empty($errors['nom']) ? 'show' : '' ?>" id="nomError">
                            <i class="fas fa-times-circle"></i>
                            <span id="nomErrorText"><?= htmlspecialchars($errors['nom'] ?? '') ?></span>
                        </div>
                    </div>
                    <!-- Prénom -->
                    <div class="col-md-6 mb-3">
                        <label class="form-label" for="prenom">Prénom <span class="text-danger">*</span></label>
                        <input type="text" name="prenom" id="prenom"
                               class="form-control <?= !empty($errors['prenom']) ? 'is-invalid' : '' ?>"
                               placeholder="Votre prénom"
                               value="<?= htmlspecialchars($old['prenom'] ?? '') ?>"
                               autocomplete="given-name">
                        <div class="field-error <?= !empty($errors['prenom']) ? 'show' : '' ?>" id="prenomError">
                            <i class="fas fa-times-circle"></i>
                            <span id="prenomErrorText"><?= htmlspecialchars($errors['prenom'] ?? '') ?></span>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <!-- Email -->
                    <div class="col-md-6 mb-3">
                        <label class="form-label" for="email">Email <span class="text-danger">*</span></label>
                        <input type="text" name="email" id="email"
                               class="form-control <?= !empty($errors['email']) ? 'is-invalid' : '' ?>"
                               placeholder="exemple@email.com"
                               value="<?= htmlspecialchars($old['email'] ?? '') ?>"
                               autocomplete="email">
                        <div class="field-error <?= !empty($errors['email']) ? 'show' : '' ?>" id="emailError">
                            <i class="fas fa-times-circle"></i>
                            <span id="emailErrorText"><?= htmlspecialchars($errors['email'] ?? '') ?></span>
                        </div>
                    </div>
                    <!-- Téléphone -->
                    <div class="col-md-6 mb-3">
                        <label class="form-label" for="telephone">Téléphone <span class="text-danger">*</span></label>
                        <input type="text" name="telephone" id="telephone"
                               class="form-control <?= !empty($errors['telephone']) ? 'is-invalid' : '' ?>"
                               placeholder="+216 XX XXX XXX"
                               value="<?= htmlspecialchars($old['telephone'] ?? '') ?>"
                               autocomplete="tel">
                        <div class="field-error <?= !empty($errors['telephone']) ? 'show' : '' ?>" id="telephoneError">
                            <i class="fas fa-times-circle"></i>
                            <span id="telephoneErrorText"><?= htmlspecialchars($errors['telephone'] ?? '') ?></span>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <!-- Mot de passe -->
                    <div class="col-md-6 mb-3">
                        <label class="form-label" for="password">Mot de passe <span class="text-danger">*</span></label>
                        <input type="password" name="password" id="password"
                               class="form-control <?= !empty($errors['password']) ? 'is-invalid' : '' ?>"
                               placeholder="•••••••••"
                               autocomplete="new-password">
                        <div class="field-error <?= !empty($errors['password']) ? 'show' : '' ?>" id="passwordError">
                            <i class="fas fa-times-circle"></i>
                            <span id="passwordErrorText"><?= htmlspecialchars($errors['password'] ?? '') ?></span>
                        </div>
                        <div class="password-requirements" id="passwordRequirements" style="<?= !empty($errors['password']) ? 'display:none' : '' ?>">
                            <span id="reqLength" class="requirement-invalid"><i class="fas fa-circle me-1"></i> Au moins 8 caractères</span><br>
                            <span id="reqUpper"  class="requirement-invalid"><i class="fas fa-circle me-1"></i> Au moins une majuscule</span><br>
                            <span id="reqNumber" class="requirement-invalid"><i class="fas fa-circle me-1"></i> Au moins un chiffre</span>
                        </div>
                    </div>
                    <!-- Confirmation mot de passe -->
                    <div class="col-md-6 mb-3">
                        <label class="form-label" for="passwordConfirm">Confirmer mot de passe <span class="text-danger">*</span></label>
                        <input type="password" name="password_confirm" id="passwordConfirm"
                               class="form-control <?= !empty($errors['password_confirm']) ? 'is-invalid' : '' ?>"
                               placeholder="•••••••••"
                               autocomplete="new-password">
                        <div class="field-error <?= !empty($errors['password_confirm']) ? 'show' : '' ?>" id="passwordConfirmError">
                            <i class="fas fa-times-circle"></i>
                            <span id="passwordConfirmErrorText"><?= htmlspecialchars($errors['password_confirm'] ?? '') ?></span>
                        </div>
                    </div>
                </div>

                <!-- Champs médecin -->
                <div id="medecinFields" class="medecin-fields">
                    <div class="section-title"><i class="fas fa-stethoscope me-1"></i> Informations professionnelles</div>
                    <div class="row">
                        <!-- Spécialité -->
                        <div class="col-md-6 mb-3">
                            <label class="form-label" for="specialite">Spécialité <span class="text-danger">*</span></label>
                            <select name="specialite" id="specialite"
                                    class="form-select <?= !empty($errors['specialite']) ? 'is-invalid' : '' ?>">
                                <option value="">Sélectionner une spécialité</option>
                                <?php
                                $specialites = ['Cardiologue','Dermatologue','Gynécologue','Pédiatre','Généraliste','Ophtalmologue','Orthopédiste','Neurologue','Psychiatre','Dentiste'];
                                foreach ($specialites as $s):
                                    $selected = (($old['specialite'] ?? '') === $s) ? 'selected' : '';
                                ?>
                                    <option <?= $selected ?>><?= $s ?></option>
                                <?php endforeach; ?>
                            </select>
                            <div class="field-error <?= !empty($errors['specialite']) ? 'show' : '' ?>" id="specialiteError">
                                <i class="fas fa-times-circle"></i>
                                <span id="specialiteErrorText"><?= htmlspecialchars($errors['specialite'] ?? '') ?></span>
                            </div>
                        </div>
                        <!-- Numéro d'ordre -->
                        <div class="col-md-6 mb-3">
                            <label class="form-label" for="numeroOrdre">Numéro d'ordre <span class="text-danger">*</span></label>
                            <input type="text" name="numero_ordre" id="numeroOrdre"
                                   class="form-control <?= !empty($errors['numero_ordre']) ? 'is-invalid' : '' ?>"
                                   placeholder="Ex: 12345"
                                   value="<?= htmlspecialchars($old['numero_ordre'] ?? '') ?>">
                            <div class="field-error <?= !empty($errors['numero_ordre']) ? 'show' : '' ?>" id="numeroOrdreError">
                                <i class="fas fa-times-circle"></i>
                                <span id="numeroOrdreErrorText"><?= htmlspecialchars($errors['numero_ordre'] ?? '') ?></span>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label" for="tarif">Tarif de consultation (DT)</label>
                            <input type="number" name="tarif" id="tarif" class="form-control"
                                   placeholder="50" step="5"
                                   value="<?= htmlspecialchars($old['tarif'] ?? '') ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label" for="experience">Années d'expérience</label>
                            <input type="number" name="experience" id="experience" class="form-control"
                                   placeholder="5"
                                   value="<?= htmlspecialchars($old['experience'] ?? '') ?>">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="adresseCabinet">Adresse du cabinet</label>
                        <textarea name="adresse_cabinet" id="adresseCabinet" class="form-control" rows="2"
                                  placeholder="Adresse complète du cabinet"><?= htmlspecialchars($old['adresse_cabinet'] ?? '') ?></textarea>
                    </div>
                </div>

                <!-- CGU -->
                <div class="form-check mb-1">
                    <input type="checkbox" class="form-check-input" id="terms" name="terms" value="1">
                    <label class="form-check-label" for="terms">
                        J'accepte les <a href="#" class="login-link">conditions d'utilisation</a>
                        et la <a href="#" class="login-link">politique de confidentialité</a>
                    </label>
                </div>
                <div class="terms-error" id="termsError">
                    <i class="fas fa-times-circle"></i>
                    <span id="termsErrorText"></span>
                </div>

                <div class="mt-3">
                    <button type="submit" class="btn-register" id="submitBtn">
                        <i class="fas fa-user-plus me-2"></i> S'inscrire
                    </button>
                </div>
            </form>

            <hr>
            <div class="text-center">
                <p class="mb-0">Déjà un compte ? <a href="index.php?page=login" class="login-link">Se connecter</a></p>
            </div>
        </div>
    </div>

    <script>
        // ── HELPERS ERREUR CHAMP ──────────────────────────────────────────────
        function showFieldError(inputId, errorDivId, errorTextId, message) {
            const input    = document.getElementById(inputId);
            const errorDiv = document.getElementById(errorDivId);
            const errorTxt = document.getElementById(errorTextId);

            if (input) {
                input.classList.add('is-invalid');
                input.classList.add('shake');
                setTimeout(() => input.classList.remove('shake'), 350);
            }
            if (errorTxt) errorTxt.innerText = message;
            if (errorDiv) errorDiv.classList.add('show');
        }

        function clearFieldError(inputId, errorDivId) {
            const input    = document.getElementById(inputId);
            const errorDiv = document.getElementById(errorDivId);
            if (input)    input.classList.remove('is-invalid');
            if (errorDiv) {
                errorDiv.classList.remove('show');
                const span = errorDiv.querySelector('span');
                if (span) span.innerText = '';
            }
        }

        function clearAllErrors() {
            const fields = [
                ['nom',             'nomError'],
                ['prenom',          'prenomError'],
                ['email',           'emailError'],
                ['telephone',       'telephoneError'],
                ['password',        'passwordError'],
                ['passwordConfirm', 'passwordConfirmError'],
                ['specialite',      'specialiteError'],
                ['numeroOrdre',     'numeroOrdreError'],
            ];
            fields.forEach(([inp, err]) => clearFieldError(inp, err));
            // Terms
            const tErr = document.getElementById('termsError');
            if (tErr) tErr.classList.remove('show');
        }

        // ── VALIDATION EN TEMPS RÉEL ─────────────────────────────────────────
        const realtimeFields = [
            ['nom',             'nomError'],
            ['prenom',          'prenomError'],
            ['email',           'emailError'],
            ['telephone',       'telephoneError'],
            ['password',        'passwordError'],
            ['passwordConfirm', 'passwordConfirmError'],
            ['specialite',      'specialiteError'],
            ['numeroOrdre',     'numeroOrdreError'],
        ];
        realtimeFields.forEach(([inputId, errorDivId]) => {
            const el = document.getElementById(inputId);
            if (!el) return;
            const evt = el.tagName === 'SELECT' ? 'change' : 'input';
            el.addEventListener(evt, function () {
                if (this.classList.contains('is-invalid')) {
                    clearFieldError(inputId, errorDivId);
                }
            });
        });

        // ── RÔLE ─────────────────────────────────────────────────────────────
        const savedRole = document.getElementById('selectedRole').value;
        if (savedRole === 'medecin') activateMedecinRole();

        document.querySelectorAll('.role-option').forEach(o => {
            o.addEventListener('click', function () {
                document.querySelectorAll('.role-option').forEach(x => x.classList.remove('active'));
                this.classList.add('active');
                document.getElementById('selectedRole').value = this.dataset.role;
                if (this.dataset.role === 'medecin') {
                    activateMedecinRole();
                } else {
                    deactivateMedecinRole();
                }
            });
        });

        function activateMedecinRole() {
            document.getElementById('medecinFields').style.display = 'block';
            // Marquer le bon onglet actif au rechargement PHP
            document.querySelectorAll('.role-option').forEach(o => {
                o.classList.toggle('active', o.dataset.role === 'medecin');
            });
        }

        function deactivateMedecinRole() {
            document.getElementById('medecinFields').style.display = 'none';
            clearFieldError('specialite',  'specialiteError');
            clearFieldError('numeroOrdre', 'numeroOrdreError');
        }

        // ── INDICATEUR MOT DE PASSE ───────────────────────────────────────────
        function getPasswordStrength(p) {
            return { length: p.length >= 8, upper: /[A-Z]/.test(p), number: /[0-9]/.test(p) };
        }

        function updatePasswordRequirements() {
            const p = document.getElementById('password').value;
            const v = getPasswordStrength(p);
            const map = { Length: [v.length, 'Au moins 8 caractères'], Upper: [v.upper, 'Au moins une majuscule'], Number: [v.number, 'Au moins un chiffre'] };
            Object.entries(map).forEach(([k, [ok, label]]) => {
                const el = document.getElementById('req' + k);
                if (!el) return;
                el.className = ok ? 'requirement-valid' : 'requirement-invalid';
                el.innerHTML = `<i class="fas fa-${ok ? 'check-' : ''}circle me-1"></i> ${label}`;
            });
        }

        document.getElementById('password').addEventListener('input', function () {
            // Afficher les requirements si le champ n'est pas en erreur PHP
            if (!this.classList.contains('is-invalid')) {
                document.getElementById('passwordRequirements').style.display = '';
            }
            updatePasswordRequirements();
            // Re-valider la confirmation si déjà saisie
            const confirm = document.getElementById('passwordConfirm');
            if (confirm.value && confirm.classList.contains('is-invalid')) {
                if (this.value === confirm.value) clearFieldError('passwordConfirm', 'passwordConfirmError');
            }
        });

        document.getElementById('passwordConfirm').addEventListener('input', function () {
            if (this.classList.contains('is-invalid')) {
                const p = document.getElementById('password').value;
                if (this.value === p) clearFieldError('passwordConfirm', 'passwordConfirmError');
            }
        });

        // ── VALIDATION FORMULAIRE ─────────────────────────────────────────────
        function isValidEmail(email) {
            return /^[^\s@]+@([^\s@]+\.)+[^\s@]+$/.test(email);
        }

        function isValidPhone(phone) {
            return /^[\d\s\+\-\(\)]{8,}$/.test(phone.trim());
        }

        function validateForm() {
            clearAllErrors();
            let isValid = true;
            const role = document.getElementById('selectedRole').value;

            const nom      = document.getElementById('nom').value.trim();
            const prenom   = document.getElementById('prenom').value.trim();
            const email    = document.getElementById('email').value.trim();
            const tel      = document.getElementById('telephone').value.trim();
            const password = document.getElementById('password').value;
            const confirm  = document.getElementById('passwordConfirm').value;
            const terms    = document.getElementById('terms').checked;

            // Nom
            if (!nom) {
                showFieldError('nom', 'nomError', 'nomErrorText', 'Le nom est obligatoire.');
                isValid = false;
            } else if (nom.length < 2) {
                showFieldError('nom', 'nomError', 'nomErrorText', 'Le nom doit contenir au moins 2 caractères.');
                isValid = false;
            }

            // Prénom
            if (!prenom) {
                showFieldError('prenom', 'prenomError', 'prenomErrorText', 'Le prénom est obligatoire.');
                isValid = false;
            } else if (prenom.length < 2) {
                showFieldError('prenom', 'prenomError', 'prenomErrorText', 'Le prénom doit contenir au moins 2 caractères.');
                isValid = false;
            }

            // Email
            if (!email) {
                showFieldError('email', 'emailError', 'emailErrorText', 'L\'adresse email est obligatoire.');
                isValid = false;
            } else if (!isValidEmail(email)) {
                showFieldError('email', 'emailError', 'emailErrorText', 'Veuillez saisir un email valide (ex: nom@domaine.com).');
                isValid = false;
            }

            // Téléphone
            if (!tel) {
                showFieldError('telephone', 'telephoneError', 'telephoneErrorText', 'Le numéro de téléphone est obligatoire.');
                isValid = false;
            } else if (!isValidPhone(tel)) {
                showFieldError('telephone', 'telephoneError', 'telephoneErrorText', 'Veuillez saisir un numéro de téléphone valide.');
                isValid = false;
            }

            // Mot de passe
            if (!password) {
                showFieldError('password', 'passwordError', 'passwordErrorText', 'Le mot de passe est obligatoire.');
                isValid = false;
            } else {
                const pv = getPasswordStrength(password);
                if (!pv.length) {
                    showFieldError('password', 'passwordError', 'passwordErrorText', 'Le mot de passe doit contenir au moins 8 caractères.');
                    isValid = false;
                } else if (!pv.upper) {
                    showFieldError('password', 'passwordError', 'passwordErrorText', 'Le mot de passe doit contenir au moins une majuscule.');
                    isValid = false;
                } else if (!pv.number) {
                    showFieldError('password', 'passwordError', 'passwordErrorText', 'Le mot de passe doit contenir au moins un chiffre.');
                    isValid = false;
                }
            }

            // Confirmation
            if (!confirm) {
                showFieldError('passwordConfirm', 'passwordConfirmError', 'passwordConfirmErrorText', 'Veuillez confirmer votre mot de passe.');
                isValid = false;
            } else if (password && password !== confirm) {
                showFieldError('passwordConfirm', 'passwordConfirmError', 'passwordConfirmErrorText', 'Les mots de passe ne correspondent pas.');
                isValid = false;
            }

            // Champs médecin
            if (role === 'medecin') {
                const specialite  = document.getElementById('specialite').value;
                const numeroOrdre = document.getElementById('numeroOrdre').value.trim();

                if (!specialite) {
                    showFieldError('specialite', 'specialiteError', 'specialiteErrorText', 'Veuillez sélectionner votre spécialité.');
                    isValid = false;
                }
                if (!numeroOrdre) {
                    showFieldError('numeroOrdre', 'numeroOrdreError', 'numeroOrdreErrorText', 'Le numéro d\'ordre est obligatoire.');
                    isValid = false;
                }
            }

            // CGU
            if (!terms) {
                const tErr = document.getElementById('termsError');
                const tTxt = document.getElementById('termsErrorText');
                if (tTxt) tTxt.innerText = 'Vous devez accepter les conditions d\'utilisation.';
                if (tErr) tErr.classList.add('show');
                isValid = false;
            }

            return isValid;
        }

        document.getElementById('terms').addEventListener('change', function () {
            if (this.checked) {
                const tErr = document.getElementById('termsError');
                if (tErr) tErr.classList.remove('show');
            }
        });

        // ── SOUMISSION ────────────────────────────────────────────────────────
        document.getElementById('registerForm').addEventListener('submit', function (e) {
            if (!validateForm()) {
                e.preventDefault();
                // Scroll vers la première erreur
                const firstError = document.querySelector('.is-invalid, .terms-error.show');
                if (firstError) firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
                return false;
            }
            // Désactiver pour éviter les doubles soumissions
            const btn = document.getElementById('submitBtn');
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status"></span>Inscription en cours...';
            return true;
        });
    </script>
</body>
</html>