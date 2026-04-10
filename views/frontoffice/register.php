<?php
// $errors (tableau champ => message), $old : AuthController::showRegister()
$errors = $errors ?? [];
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
        .btn-register { background: #4CAF50; color: white; border-radius: 10px; padding: 12px; width: 100%; font-weight: bold; font-size: 16px; border: none; transition: all 0.3s; }
        .btn-register:hover { background: #2A7FAA; transform: translateY(-2px); }
        .role-selector { display: flex; gap: 15px; margin-bottom: 25px; }
        .role-option { flex: 1; text-align: center; padding: 12px; border: 2px solid #e0e0e0; border-radius: 12px; cursor: pointer; transition: all 0.3s; background: #f9f9f9; }
        .role-option i { font-size: 24px; margin-bottom: 5px; display: block; }
        .role-option.active { border-color: #4CAF50; background: #e8f5e9; color: #4CAF50; }
        .role-option:hover { border-color: #4CAF50; transform: translateY(-2px); }
        .section-title { font-size: 14px; font-weight: bold; margin: 20px 0 15px 0; color: #2A7FAA; border-left: 4px solid #4CAF50; padding-left: 10px; }
        .password-requirements { font-size: 12px; color: #999; margin-top: 5px; }
        .requirement-valid { color: #4CAF50; }
        .requirement-invalid { color: #dc3545; }
        .alert-php { border-radius: 10px; padding: 12px 15px; margin-bottom: 20px; }
        .alert-success-js { background: #d4edda; color: #155724; border-radius: 10px; padding: 12px 15px; margin-bottom: 20px; display: none; }
        .field-error { font-size: 12px; margin-top: 5px; color: #dc3545; font-weight: normal; }
        .field-error i { margin-right: 5px; }
        .form-control.error, .form-select.error { border-color: #dc3545 !important; }
        .form-check.error-wrap { outline: 1px solid #dc3545; border-radius: 8px; padding: 8px; }
        .login-link { color: #2A7FAA; text-decoration: none; }
        .login-link:hover { color: #4CAF50; text-decoration: underline; }
        hr { margin: 20px 0; }
        .medecin-fields { display: none; animation: fadeIn 0.3s ease; }
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

            <?php if (!empty($errors['__form'])): ?>
                <div class="alert alert-danger alert-php">
                    <i class="fas fa-exclamation-circle me-2"></i>
                    <?= htmlspecialchars($errors['__form']) ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($_SESSION['success'])): ?>
                <div class="alert alert-success alert-php">
                    <i class="fas fa-check-circle me-2"></i>
                    <?= htmlspecialchars($_SESSION['success']) ?>
                </div>
                <?php unset($_SESSION['success']); ?>
            <?php endif; ?>

            <div id="successMessage" class="alert-success-js">
                <i class="fas fa-check-circle me-2"></i>
                <span id="successText"></span>
            </div>

            <div class="role-selector">
                <div class="role-option active" data-role="patient">
                    <i class="fas fa-user"></i><div>Patient</div>
                </div>
                <div class="role-option" data-role="medecin">
                    <i class="fas fa-user-md"></i><div>Médecin</div>
                </div>
            </div>

            <form id="registerForm" method="POST" action="index.php?page=register">
                <input type="hidden" name="role" id="selectedRole" value="patient">

                <div class="section-title"><i class="fas fa-user-circle me-1"></i> Informations personnelles</div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Nom <span class="text-danger">*</span></label>
                        <input type="text" name="nom" id="nom" class="form-control <?= !empty($errors['nom']) ? 'error' : '' ?>"
                               placeholder="Votre nom"
                               value="<?= htmlspecialchars($old['nom'] ?? '') ?>" required>
                        <div class="error-container" id="nom-errors">
                            <?php if (!empty($errors['nom'])): ?>
                                <div class="field-error"><i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($errors['nom']) ?></div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Prénom <span class="text-danger">*</span></label>
                        <input type="text" name="prenom" id="prenom" class="form-control <?= !empty($errors['prenom']) ? 'error' : '' ?>"
                               placeholder="Votre prénom"
                               value="<?= htmlspecialchars($old['prenom'] ?? '') ?>" required>
                        <div class="error-container" id="prenom-errors">
                            <?php if (!empty($errors['prenom'])): ?>
                                <div class="field-error"><i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($errors['prenom']) ?></div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Email <span class="text-danger">*</span></label>
                        <input type="email" name="email" id="email" class="form-control <?= !empty($errors['email']) ? 'error' : '' ?>"
                               placeholder="exemple@email.com"
                               value="<?= htmlspecialchars($old['email'] ?? '') ?>" required>
                        <div class="error-container" id="email-errors">
                            <?php if (!empty($errors['email'])): ?>
                                <div class="field-error"><i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($errors['email']) ?></div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Téléphone <span class="text-danger">*</span></label>
                        <input type="tel" name="telephone" id="telephone" class="form-control <?= !empty($errors['telephone']) ? 'error' : '' ?>"
                               placeholder="+216 XX XXX XXX"
                               value="<?= htmlspecialchars($old['telephone'] ?? '') ?>" required>
                        <div class="error-container" id="telephone-errors">
                            <?php if (!empty($errors['telephone'])): ?>
                                <div class="field-error"><i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($errors['telephone']) ?></div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Mot de passe <span class="text-danger">*</span></label>
                        <input type="password" name="password" id="password" class="form-control <?= !empty($errors['password']) ? 'error' : '' ?>"
                               placeholder="••••••••" required>
                        <div class="error-container" id="password-errors">
                            <?php if (!empty($errors['password'])): ?>
                                <div class="field-error"><i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($errors['password']) ?></div>
                            <?php endif; ?>
                        </div>
                        <div class="password-requirements">
                            <span id="reqLength" class="requirement-invalid"><i class="fas fa-circle me-1"></i> Au moins 8 caractères</span><br>
                            <span id="reqUpper" class="requirement-invalid"><i class="fas fa-circle me-1"></i> Au moins une majuscule</span><br>
                            <span id="reqNumber" class="requirement-invalid"><i class="fas fa-circle me-1"></i> Au moins un chiffre</span>
                        </div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Confirmer mot de passe <span class="text-danger">*</span></label>
                        <input type="password" name="password_confirm" id="passwordConfirm" class="form-control <?= !empty($errors['password_confirm']) ? 'error' : '' ?>"
                               placeholder="••••••••" required>
                        <div class="error-container" id="password_confirm-errors">
                            <?php if (!empty($errors['password_confirm'])): ?>
                                <div class="field-error"><i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($errors['password_confirm']) ?></div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Champs médecin -->
                <div id="medecinFields" class="medecin-fields">
                    <div class="section-title"><i class="fas fa-stethoscope me-1"></i> Informations professionnelles</div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Spécialité <span class="text-danger">*</span></label>
                            <select name="specialite" id="specialite" class="form-select <?= !empty($errors['specialite']) ? 'error' : '' ?>">
                                <option value="">Sélectionner une spécialité</option>
                                <option>Cardiologue</option>
                                <option>Dermatologue</option>
                                <option>Gynécologue</option>
                                <option>Pédiatre</option>
                                <option>Généraliste</option>
                                <option>Ophtalmologue</option>
                                <option>Orthopédiste</option>
                                <option>Neurologue</option>
                                <option>Psychiatre</option>
                                <option>Dentiste</option>
                            </select>
                            <div class="error-container" id="specialite-errors">
                                <?php if (!empty($errors['specialite'])): ?>
                                    <div class="field-error"><i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($errors['specialite']) ?></div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Numéro d'ordre <span class="text-danger">*</span></label>
                            <input type="text" name="numero_ordre" id="numeroOrdre" class="form-control <?= !empty($errors['numero_ordre']) ? 'error' : '' ?>"
                                   placeholder="Ex: 12345"
                                   value="<?= htmlspecialchars($old['numero_ordre'] ?? '') ?>">
                            <div class="error-container" id="numero_ordre-errors">
                                <?php if (!empty($errors['numero_ordre'])): ?>
                                    <div class="field-error"><i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($errors['numero_ordre']) ?></div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Tarif de consultation (DT)</label>
                            <input type="number" name="tarif" id="tarif" class="form-control"
                                   placeholder="50" step="5"
                                   value="<?= htmlspecialchars($old['tarif'] ?? '') ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Années d'expérience</label>
                            <input type="number" name="experience" id="experience" class="form-control"
                                   placeholder="5"
                                   value="<?= htmlspecialchars($old['experience'] ?? '') ?>">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Adresse du cabinet</label>
                        <textarea name="adresse_cabinet" id="adresseCabinet" class="form-control" rows="2"
                                  placeholder="Adresse complète du cabinet"><?= htmlspecialchars($old['adresse_cabinet'] ?? '') ?></textarea>
                    </div>
                </div>

                <div class="form-check mb-3 <?= !empty($errors['terms']) ? 'error-wrap' : '' ?>">
                    <input type="checkbox" class="form-check-input" name="terms" value="1" id="terms" required
                        <?= !empty($old['terms']) ? 'checked' : '' ?>>
                    <label class="form-check-label" for="terms">
                        J'accepte les <a href="#" class="login-link">conditions d'utilisation</a>
                        et la <a href="#" class="login-link">politique de confidentialité</a>
                    </label>
                </div>
                <div class="error-container mb-2" id="terms-errors">
                    <?php if (!empty($errors['terms'])): ?>
                        <div class="field-error"><i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($errors['terms']) ?></div>
                    <?php endif; ?>
                </div>

                <button type="submit" class="btn-register">
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
        // ── RÔLE ─────────────────────────────────────
        function setMedecinSectionVisible(isMedecin) {
            const mf = document.getElementById('medecinFields');
            if (isMedecin) {
                mf.style.display = 'block';
                document.getElementById('specialite').required = true;
                document.getElementById('numeroOrdre').required = true;
            } else {
                mf.style.display = 'none';
                document.getElementById('specialite').required = false;
                document.getElementById('numeroOrdre').required = false;
            }
        }
        document.querySelectorAll('.role-option').forEach(o => {
            o.addEventListener('click', function () {
                document.querySelectorAll('.role-option').forEach(x => x.classList.remove('active'));
                this.classList.add('active');
                const r = this.dataset.role;
                document.getElementById('selectedRole').value = r;
                setMedecinSectionVisible(r === 'medecin');
            });
        });
        (function restoreRoleFromServer() {
            const role = <?= json_encode($old['role'] ?? 'patient', JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
            if (role === 'medecin') {
                const opt = document.querySelector('.role-option[data-role="medecin"]');
                if (opt) opt.click();
            }
        })();
        (function restoreSpecialite() {
            const s = <?= json_encode($old['specialite'] ?? '', JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
            const sel = document.getElementById('specialite');
            if (s && sel) sel.value = s;
        })();

        // ── MOT DE PASSE ─────────────────────────────
        function validatePassword(p) {
            return { length: p.length >= 8, upper: /[A-Z]/.test(p), number: /[0-9]/.test(p) };
        }
        function updatePasswordRequirements() {
            const p = document.getElementById('password').value, v = validatePassword(p);
            const checks = { Length: v.length, Upper: v.upper, Number: v.number };
            const labels = { Length: 'Au moins 8 caractères', Upper: 'Au moins une majuscule', Number: 'Au moins un chiffre' };
            Object.keys(checks).forEach(k => {
                const el = document.getElementById('req' + k), ok = checks[k];
                el.className = ok ? 'requirement-valid' : 'requirement-invalid';
                el.innerHTML = `<i class="fas fa-${ok ? 'check-' : ''}circle me-1"></i> ${labels[k]}`;
            });
        }
        document.getElementById('password').addEventListener('input', updatePasswordRequirements);
        document.getElementById('passwordConfirm').addEventListener('input', function () {
            const p = document.getElementById('password').value;
            this.style.borderColor = p && this.value && p !== this.value ? '#dc3545' : '#ddd';
        });

        function escapeHtml(text) {
            const d = document.createElement('div');
            d.textContent = text;
            return d.innerHTML;
        }
        function clearClientFieldErrors() {
            document.querySelectorAll('#registerForm .error-container').forEach(c => { c.innerHTML = ''; });
            document.querySelectorAll('#registerForm .form-control, #registerForm .form-select').forEach(el => el.classList.remove('error'));
            document.querySelectorAll('#registerForm .form-check').forEach(fc => fc.classList.remove('error-wrap'));
        }
        function showFieldError(fieldKey, message) {
            const box = document.getElementById(fieldKey + '-errors');
            if (!box) return;
            box.innerHTML = '<div class="field-error"><i class="fas fa-exclamation-circle"></i> ' + escapeHtml(message) + '</div>';
            const idMap = { nom: 'nom', prenom: 'prenom', email: 'email', telephone: 'telephone', password: 'password', password_confirm: 'passwordConfirm', specialite: 'specialite', numero_ordre: 'numeroOrdre', terms: 'terms' };
            const iid = idMap[fieldKey] || fieldKey;
            const inp = document.getElementById(iid) || document.querySelector('#registerForm [name="' + fieldKey + '"]');
            if (inp && fieldKey !== 'terms') inp.classList.add('error');
            if (fieldKey === 'terms') {
                const wrap = document.getElementById('terms') && document.getElementById('terms').closest('.form-check');
                if (wrap) wrap.classList.add('error-wrap');
            }
        }

        // ── SOUMISSION → PHP ──────────────────────────
        document.getElementById('registerForm').addEventListener('submit', function (e) {
            clearClientFieldErrors();
            const nom      = document.getElementById('nom').value.trim();
            const prenom   = document.getElementById('prenom').value.trim();
            const email    = document.getElementById('email').value.trim();
            const tel      = document.getElementById('telephone').value.trim();
            const password = document.getElementById('password').value;
            const confirm  = document.getElementById('passwordConfirm').value;
            const role     = document.getElementById('selectedRole').value;
            const terms    = document.getElementById('terms').checked;

            if (!nom) { e.preventDefault(); showFieldError('nom', 'Le nom est requis.'); return; }
            if (!prenom) { e.preventDefault(); showFieldError('prenom', 'Le prénom est requis.'); return; }
            if (!email) { e.preventDefault(); showFieldError('email', 'L\'email est requis.'); return; }
            if (!tel) { e.preventDefault(); showFieldError('telephone', 'Le téléphone est requis.'); return; }
            if (!password) { e.preventDefault(); showFieldError('password', 'Le mot de passe est requis.'); return; }
            if (!confirm) { e.preventDefault(); showFieldError('password_confirm', 'Veuillez confirmer le mot de passe.'); return; }

            const pv = validatePassword(password);
            if (!pv.length || !pv.upper || !pv.number) {
                e.preventDefault(); showFieldError('password', 'Au moins 8 caractères, une majuscule et un chiffre.'); return;
            }
            if (password !== confirm) {
                e.preventDefault(); showFieldError('password_confirm', 'Les mots de passe ne correspondent pas.'); return;
            }
            if (!terms) {
                e.preventDefault(); showFieldError('terms', "Vous devez accepter les conditions d'utilisation."); return;
            }
            if (role === 'medecin') {
                const s = document.getElementById('specialite').value;
                const n = document.getElementById('numeroOrdre').value.trim();
                if (!s) { e.preventDefault(); showFieldError('specialite', 'Veuillez sélectionner votre spécialité.'); return; }
                if (!n) { e.preventDefault(); showFieldError('numero_ordre', "Veuillez saisir votre numéro d'ordre."); return; }
            }
        });
        function showSuccess(msg) {
            const d = document.getElementById('successMessage');
            document.getElementById('successText').innerText = msg;
            d.style.display = 'block';
        }
    </script>
</body>
</html>