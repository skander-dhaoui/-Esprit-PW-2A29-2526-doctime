<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion - DocTime</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { background: linear-gradient(135deg, #2A7FAA 0%, #4CAF50 100%); min-height: 100vh; display: flex; align-items: center; justify-content: center; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; padding: 20px; }

        .login-card { background: white; border-radius: 20px; box-shadow: 0 20px 40px rgba(0,0,0,0.1); overflow: hidden; width: 100%; max-width: 480px; animation: fadeIn 0.5s ease; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(-20px); } to { opacity: 1; transform: translateY(0); } }

        .login-header { background: linear-gradient(135deg, #2A7FAA 0%, #3e8e41 100%); color: white; padding: 30px; text-align: center; }
        .login-header .logo-container { display: flex; justify-content: center; margin-bottom: 10px; }
        .login-header .logo-container img { height: 90px; width: auto; object-fit: contain; filter: drop-shadow(0 2px 8px rgba(0,0,0,0.2)); }
        .login-header p { font-size: 13px; opacity: 0.9; margin-top: 6px; }

        .login-body { padding: 35px; }

        .form-control { border-radius: 10px; padding: 12px 15px; border: 1px solid #ddd; transition: all 0.3s; }
        .form-control:focus { border-color: #4CAF50; box-shadow: 0 0 0 3px rgba(76,175,80,0.1); }
        .form-control.is-invalid { border-color: #dc3545; background-color: #fff5f5; background-image: none; }
        .form-control.is-invalid:focus { border-color: #dc3545; box-shadow: 0 0 0 3px rgba(220,53,69,0.1); }

        .field-error { color: #dc3545; font-size: 12px; margin-top: 5px; font-weight: 500; display: none; }
        .field-error.show { display: flex; align-items: center; gap: 4px; }

        .btn-login { background: #4CAF50; color: white; border-radius: 10px; padding: 12px; width: 100%; font-weight: bold; font-size: 16px; border: none; transition: all 0.3s; }
        .btn-login:hover { background: #2A7FAA; transform: translateY(-2px); }
        .btn-login:disabled { opacity: 0.7; transform: none; cursor: not-allowed; }

        .btn-camera { background: #6c757d; color: white; border-radius: 10px; padding: 12px; width: 100%; font-weight: bold; font-size: 16px; border: none; transition: all 0.3s; margin-bottom: 15px; }
        .btn-camera:hover { background: #5a6268; transform: translateY(-2px); }
        .social-login { display: flex; justify-content: center; gap: 12px; margin-bottom: 14px; flex-wrap: wrap; }
        .btn-social { width: 54px; height: 54px; display: inline-flex; align-items: center; justify-content: center; padding: 0; border-radius: 50%; text-decoration: none; font-weight: 700; border: 1px solid #e5e7eb; transition: all 0.2s; }
        .btn-social:hover { transform: translateY(-2px); box-shadow: 0 10px 18px rgba(0,0,0,0.08); }
        .btn-social span { display: none; }
        .btn-social i { font-size: 20px; }
        .btn-google { background: #ffffff; color: #1f2937; }
        .btn-github { background: #111827; color: #ffffff; border-color: #111827; }
        .btn-facebook { background: #1877f2; color: #ffffff; border-color: #1877f2; }
        .btn-instagram { color: #ffffff; border: none; background: linear-gradient(135deg, #f58529, #dd2a7b, #8134af, #515bd4); }
        .social-hint { font-size: 12px; color: #6b7280; text-align: center; margin-bottom: 18px; }

        .role-selector { display: flex; gap: 15px; margin-bottom: 25px; }
        .role-option { flex: 1; text-align: center; padding: 12px; border: 2px solid #e0e0e0; border-radius: 12px; cursor: pointer; transition: all 0.3s; background: #f9f9f9; }
        .role-option i { font-size: 24px; margin-bottom: 5px; display: block; }
        .role-option.active { border-color: #4CAF50; background: #e8f5e9; color: #4CAF50; }
        .role-option:hover { border-color: #4CAF50; transform: translateY(-2px); }

        /* PHP session alerts */
        .alert-session { border-radius: 10px; padding: 12px 15px; margin-bottom: 20px; display: flex; align-items: center; gap: 8px; font-size: 14px; }
        .alert-session-success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .alert-session-error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }

        .forgot-link, .register-link { color: #2A7FAA; text-decoration: none; font-size: 14px; }
        .forgot-link:hover, .register-link:hover { color: #4CAF50; text-decoration: underline; }
        hr { margin: 20px 0; }

        .captcha-box { background: #f8f9fa; border-radius: 10px; padding: 15px; text-align: center; margin-bottom: 20px; }
        .captcha-code { font-size: 28px; font-weight: bold; letter-spacing: 8px; background: #2A7FAA; color: white; display: inline-block; padding: 10px 20px; border-radius: 10px; font-family: monospace; margin-bottom: 10px; user-select: none; }
        .captcha-refresh { cursor: pointer; color: #2A7FAA; margin-left: 10px; transition: color 0.2s; }
        .captcha-refresh:hover { color: #4CAF50; }

        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            20% { transform: translateX(-6px); }
            40% { transform: translateX(6px); }
            60% { transform: translateX(-4px); }
            80% { transform: translateX(4px); }
        }
        .shake { animation: shake 0.35s ease-in-out; }

        /* Camera modal */
        .modal-camera { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.6); z-index: 999; align-items: center; justify-content: center; padding: 20px; }
        .modal-camera.open { display: flex; }
        .modal-camera-content { background: white; border-radius: 20px; padding: 30px; text-align: center; max-width: 420px; width: 100%; box-shadow: 0 20px 50px rgba(0,0,0,0.3); animation: fadeIn 0.3s ease; }
        .modal-camera-content h4 { margin-bottom: 8px; color: #1a2035; }
        .modal-camera-content video { width: 100%; border-radius: 12px; margin: 15px 0; background: #000; }
        .modal-camera-content canvas { display: none; }
    </style>
    <!-- Face API JS pour la reconnaissance faciale réelle -->
    <script src="https://cdn.jsdelivr.net/npm/face-api.js@0.22.2/dist/face-api.min.js"></script>
</head>
<body>
    <div class="login-card">
        <div class="login-header">
            <div class="logo-container">
                <img src="assets/images/logo_doctime.png" alt="DocTime Logo"
                     onerror="this.style.display='none'; document.getElementById('logoFallback').style.display='block';">
                <i id="logoFallback" class="fas fa-stethoscope" style="display:none; font-size:60px;"></i>
            </div>
            <p>Plateforme intelligente de rendez-vous médical</p>
        </div>

        <div class="login-body">
            <h4 class="text-center mb-4">Connexion</h4>

            <?php if (!empty($_SESSION['success'])): ?>
                <div class="alert-session alert-session-success">
                    <i class="fas fa-check-circle"></i>
                    <?= htmlspecialchars($_SESSION['success']) ?>
                </div>
                <?php unset($_SESSION['success']); ?>
            <?php endif; ?>

            <?php if (!empty($_SESSION['error'])): ?>
                <div class="alert-session alert-session-error">
                    <i class="fas fa-exclamation-circle"></i>
                    <?= htmlspecialchars($_SESSION['error']) ?>
                </div>
                <?php unset($_SESSION['error']); ?>
            <?php endif; ?>

            <?php
            // Afficher $_SESSION['errors'] (tableau — format utilisé par AuthController::login())
            if (!empty($_SESSION['errors']) && is_array($_SESSION['errors'])):
                foreach ($_SESSION['errors'] as $errKey => $errMsg):
            ?>
                <div class="alert-session alert-session-error">
                    <i class="fas fa-exclamation-circle"></i>
                    <?= htmlspecialchars((string)$errMsg) ?>
                </div>
            <?php
                endforeach;
                unset($_SESSION['errors']);
            endif;
            ?>

            <button type="button" class="btn-camera" onclick="openCameraModal()">
                <i class="fas fa-camera me-2"></i> Connexion avec reconnaissance faciale
            </button>

            <div class="text-center mb-3"><span class="text-muted">connectez-vous avec votre compte DocTime</span></div>

            <div class="role-selector">
                <div class="role-option active" data-role="patient">
                    <i class="fas fa-user"></i><div>Patient</div>
                </div>
                <div class="role-option" data-role="medecin">
                    <i class="fas fa-user-md"></i><div>Médecin</div>
                </div>

            </div>

            <form id="loginForm" method="POST" action="index.php?page=login" novalidate>
                <input type="hidden" name="role" id="selectedRole" value="patient">
                <input type="hidden" name="captcha_response" id="captchaResponse" value="">

                <!-- Email -->
                <div class="mb-3">
                    <label class="form-label" for="email">Email</label>
                    <input type="email" name="email" id="email" class="form-control"
                           placeholder="exemple@email.com"
                           value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                           autocomplete="email">
                    <div class="field-error" id="emailError">
                        <i class="fas fa-times-circle"></i>
                        <span id="emailErrorText"></span>
                    </div>
                </div>

                <!-- Mot de passe -->
                <div class="mb-3">
                    <label class="form-label" for="password">Mot de passe</label>
                    <input type="password" name="password" id="password" class="form-control"
                           placeholder="•••••••••"
                           autocomplete="current-password">
                    <div class="field-error" id="passwordError">
                        <i class="fas fa-times-circle"></i>
                        <span id="passwordErrorText"></span>
                    </div>
                </div>

                <!-- CAPTCHA -->
                <div class="captcha-box">
                    <div>
                        <span class="captcha-code" id="captchaCode"></span>
                        <i class="fas fa-sync-alt captcha-refresh" onclick="generateCaptcha()" title="Recharger"></i>
                    </div>
                    <input type="text" id="captchaInput" class="form-control mt-2"
                           placeholder="Saisissez le code ci-dessus"
                           style="text-align:center;"
                           autocomplete="off">
                    <div class="field-error" id="captchaError" style="justify-content:center;">
                        <i class="fas fa-times-circle"></i>
                        <span id="captchaErrorText"></span>
                    </div>
                </div>

                <!-- Remember + Forgot -->
                <div class="mb-3 form-check d-flex justify-content-between align-items-center">
                    <div>
                        <input type="checkbox" class="form-check-input" id="remember" name="remember">
                        <label class="form-check-label" for="remember">Se souvenir de moi</label>
                    </div>
                    <a href="index.php?page=forgot_password" class="forgot-link">Mot de passe oublié ?</a>
                </div>

                <div class="text-center mb-3 mt-3"><span class="text-muted">ou continuer avec</span></div>

                <div class="social-login">
                    <a class="btn-social btn-google" href="index.php?page=social_login&provider=google">
                        <i class="fab fa-google"></i>
                        <span>Continuer avec Google</span>
                    </a>
                    <a class="btn-social btn-github" href="index.php?page=social_login&provider=github">
                        <i class="fab fa-github"></i>
                        <span>Continuer avec GitHub</span>
                    </a>
                    <a class="btn-social btn-facebook" href="index.php?page=social_login&provider=facebook">
                        <i class="fab fa-facebook-f"></i>
                        <span>Continuer avec Facebook</span>
                    </a>
                    <a class="btn-social btn-instagram" href="index.php?page=social_login&provider=instagram">
                        <i class="fab fa-instagram"></i>
                        <span>Continuer avec Instagram</span>
                    </a>
                </div>
                <div class="social-hint">
                    Si les clés OAuth ne sont pas encore configurées, un message d'information sera affiché.
                </div>

                <button type="submit" class="btn-login" id="submitBtn">
                    <i class="fas fa-sign-in-alt me-2"></i> Se connecter
                </button>
            </form>

            <hr>
            <div class="text-center">
                <p class="mb-0">
                    Pas encore de compte ?
                    <a href="index.php?page=register" class="register-link">S'inscrire</a>
                </p>
            </div>
        </div>
    </div>

    <!-- Modal Caméra -->
    <div id="cameraModal" class="modal-camera">
        <div class="modal-camera-content">
            <h4><i class="fas fa-camera me-2"></i>Reconnaissance faciale</h4>
            <p class="text-muted">Placez votre visage devant la caméra</p>
            <video id="video" autoplay playsinline></video>
            <canvas id="canvas"></canvas>
            <div class="d-flex gap-2 mt-3">
                <button class="btn btn-success w-50" onclick="captureFace()">
                    <i class="fas fa-camera"></i> Capturer
                </button>
                <button class="btn btn-secondary w-50" onclick="closeCameraModal()">
                    <i class="fas fa-times"></i> Annuler
                </button>
            </div>
            <div id="cameraMessage" class="mt-3" style="font-size:14px;min-height:24px;"></div>
        </div>
    </div>

    <script>
        // ── CAPTCHA ──────────────────────────────────────────────────────────
        let currentCaptcha = "<?= htmlspecialchars($_SESSION['captcha_code'] ?? '', ENT_QUOTES, 'UTF-8') ?>";
        
        // Si pas de captcha en session, le charger du serveur
        if (!currentCaptcha || currentCaptcha === '') {
            fetch('index.php?action=get_captcha', { method: 'POST' })
                .then(r => r.json())
                .then(data => {
                    if (data.captcha) {
                        currentCaptcha = data.captcha;
                        document.getElementById("captchaCode").innerText = currentCaptcha;
                    } else {
                        console.error('Erreur captcha:', data.error);
                    }
                })
                .catch(err => console.error('Erreur AJAX captcha:', err));
        } else {
            // Afficher le captcha initial depuis la session serveur
            document.getElementById("captchaCode").innerText = currentCaptcha;
        }

        function generateCaptcha() {
            // Au clic sur rafraîchir, on demande un nouveau captcha côté serveur via AJAX
            fetch('index.php?action=generate_captcha', { method: 'POST' })
                .then(r => r.json())
                .then(data => {
                    if (data.captcha) {
                        currentCaptcha = data.captcha;
                        document.getElementById("captchaCode").innerText = currentCaptcha;
                        document.getElementById("captchaInput").value = '';
                        clearFieldError('captchaInput', 'captchaError');
                    }
                })
                .catch(err => console.error('Erreur rafraîchissement captcha:', err));
        }

        // ── RÔLE ─────────────────────────────────────────────────────────────
        document.querySelectorAll('.role-option').forEach(option => {
            option.addEventListener('click', function () {
                document.querySelectorAll('.role-option').forEach(x => x.classList.remove('active'));
                this.classList.add('active');
                document.getElementById('selectedRole').value = this.dataset.role;
            });
        });

        // ── HELPERS ERREUR CHAMP ──────────────────────────────────────────────
        function showFieldError(inputId, errorDivId, errorTextId, message) {
            const input = document.getElementById(inputId);
            const errorDiv = document.getElementById(errorDivId);
            const errorText = document.getElementById(errorTextId);

            if (input) {
                input.classList.add('is-invalid');
                input.classList.add('shake');
                setTimeout(() => input.classList.remove('shake'), 350);
            }
            if (errorText) errorText.innerText = message;
            if (errorDiv) errorDiv.classList.add('show');
        }

        function clearFieldError(inputId, errorDivId) {
            const input = document.getElementById(inputId);
            const errorDiv = document.getElementById(errorDivId);

            if (input) input.classList.remove('is-invalid');
            if (errorDiv) {
                errorDiv.classList.remove('show');
                const span = errorDiv.querySelector('span');
                if (span) span.innerText = '';
            }
        }

        function clearAllErrors() {
            clearFieldError('email', 'emailError');
            clearFieldError('password', 'passwordError');
            clearFieldError('captchaInput', 'captchaError');
        }

        // ── VALIDATION EN TEMPS RÉEL ─────────────────────────────────────────
        document.getElementById('email').addEventListener('input', function () {
            if (this.classList.contains('is-invalid')) {
                clearFieldError('email', 'emailError');
            }
        });

        document.getElementById('password').addEventListener('input', function () {
            if (this.classList.contains('is-invalid')) {
                clearFieldError('password', 'passwordError');
            }
        });

        document.getElementById('captchaInput').addEventListener('input', function () {
            if (this.classList.contains('is-invalid')) {
                clearFieldError('captchaInput', 'captchaError');
            }
        });

        // ── VALIDATION ────────────────────────────────────────────────────────
        function isValidEmail(email) {
            return /^[^\s@]+@([^\s@]+\.)+[^\s@]+$/.test(email);
        }

        function validateForm() {
            clearAllErrors();
            let isValid = true;

            const email = document.getElementById('email').value.trim();
            const password = document.getElementById('password').value;
            const captchaInput = document.getElementById('captchaInput').value.toUpperCase().trim();

            // Email
            if (!email) {
                showFieldError('email', 'emailError', 'emailErrorText', 'L\'adresse email est requise.');
                isValid = false;
            } else if (!isValidEmail(email)) {
                showFieldError('email', 'emailError', 'emailErrorText', 'Veuillez saisir un email valide (ex: nom@domaine.com).');
                isValid = false;
            }

            // Mot de passe
            if (!password) {
                showFieldError('password', 'passwordError', 'passwordErrorText', 'Le mot de passe est requis.');
                isValid = false;
            } else if (password.length < 6) {
                showFieldError('password', 'passwordError', 'passwordErrorText', 'Le mot de passe doit contenir au moins 6 caractères.');
                isValid = false;
            }

            // CAPTCHA
            if (!captchaInput) {
                showFieldError('captchaInput', 'captchaError', 'captchaErrorText', 'Veuillez saisir le code de vérification.');
                isValid = false;
            } else if (captchaInput !== currentCaptcha) {
                showFieldError('captchaInput', 'captchaError', 'captchaErrorText', 'Code incorrect. Un nouveau code a été généré.');
                generateCaptcha();
                document.getElementById('captchaInput').value = '';
                isValid = false;
            }

            return isValid;
        }

        // ── SOUMISSION ────────────────────────────────────────────────────────
        document.getElementById('loginForm').addEventListener('submit', function (e) {
            if (!validateForm()) {
                e.preventDefault();
                return false;
            }
            // Envoyer ce que l'utilisateur a tapé (en majuscules) comme captcha_response
            const captchaInput = document.getElementById('captchaInput').value.toUpperCase().trim();
            document.getElementById('captchaResponse').value = captchaInput;
            // Désactiver le bouton pour éviter la double soumission
            const btn = document.getElementById('submitBtn');
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status"></span>Connexion...';
            return true;
        });

        // ── CAMÉRA — RECONNAISSANCE FACIALE AVEC face-api.js ────────────────
        let stream = null;
        let faceModelsLoaded = false;

        // Charger les modèles face-api.js une seule fois
        async function loadFaceModels() {
            if (faceModelsLoaded) return true;
            try {
                const MODEL_URL = 'https://justadudewhohacks.github.io/face-api.js/models';
                await Promise.all([
                    faceapi.nets.tinyFaceDetector.loadFromUri(MODEL_URL),
                    faceapi.nets.faceLandmark68TinyNet.loadFromUri(MODEL_URL),
                    faceapi.nets.faceRecognitionNet.loadFromUri(MODEL_URL)
                ]);
                faceModelsLoaded = true;
                return true;
            } catch (e) {
                console.error('Erreur chargement modèles:', e);
                return false;
            }
        }

        function getFaceLoginContext() {
            const typedEmail = (document.getElementById('email')?.value || '').trim();
            const savedEmail = (localStorage.getItem('valorys_face_email') || '').trim();
            const savedRole = (localStorage.getItem('valorys_face_role') || '').trim();
            const roleInput = document.getElementById('selectedRole');
            const selectedRole = roleInput ? roleInput.value : 'patient';
            const resolvedEmail = typedEmail || savedEmail;
            const resolvedRole = (resolvedEmail && savedEmail && resolvedEmail.toLowerCase() === savedEmail.toLowerCase() && savedRole)
                ? savedRole
                : selectedRole;

            return {
                email: resolvedEmail,
                role: resolvedRole || savedRole || 'patient'
            };
        }

        function openCameraModal() {
            const context = getFaceLoginContext();

            if (!context.email) {
                showError(
                    'Saisissez d\'abord votre email ou utilisez l\'appareil sur lequel vous avez enregistré votre visage.'
                );
                return;
            }

            document.getElementById('cameraModal').classList.add('open');
            document.getElementById('cameraMessage').innerHTML =
                `<span class="text-muted" style="font-size:12px;"><i class="fas fa-link me-1"></i>Compte lié : <strong>${context.email}</strong></span>`;
            startCamera();

            // Précharger les modèles en arrière-plan
            loadFaceModels();
        }

        function closeCameraModal() {
            if (stream) stream.getTracks().forEach(t => t.stop());
            stream = null;
            document.getElementById('cameraModal').classList.remove('open');
        }

        async function startCamera() {
            try {
                stream = await navigator.mediaDevices.getUserMedia({ video: true });
                document.getElementById('video').srcObject = stream;
            } catch (err) {
                document.getElementById('cameraMessage').innerHTML =
                    '<span class="text-danger"><i class="fas fa-exclamation-circle me-1"></i>Impossible d\'accéder à la caméra.</span>';
            }
        }

        async function captureFace() {
            const video   = document.getElementById('video');
            const canvas  = document.getElementById('canvas');
            const ctx     = canvas.getContext('2d');
            const msgDiv  = document.getElementById('cameraMessage');

            const contextData = getFaceLoginContext();
            const savedEmail = contextData.email;
            const savedRole  = contextData.role;

            if (!savedEmail) {
                msgDiv.innerHTML = '<span class="text-danger"><i class="fas fa-exclamation-circle me-1"></i>Email introuvable. Saisissez votre email puis réessayez.</span>';
                return;
            }

            canvas.width  = video.videoWidth;
            canvas.height = video.videoHeight;
            ctx.drawImage(video, 0, 0, canvas.width, canvas.height);

            msgDiv.innerHTML = '<span class="text-info"><i class="fas fa-spinner fa-spin me-1"></i>Analyse du visage en cours...</span>';

            // Étape 1 : charger les modèles
            const modelsOk = await loadFaceModels();
            if (!modelsOk) {
                msgDiv.innerHTML = '<span class="text-danger"><i class="fas fa-exclamation-circle me-1"></i>Erreur chargement modèles IA.</span>';
                return;
            }

            // Étape 2 : détecter le visage sur la caméra
            const detectionOptions = new faceapi.TinyFaceDetectorOptions({ inputSize: 224, scoreThreshold: 0.5 });
            const liveDetection = await faceapi
                .detectSingleFace(video, detectionOptions)
                .withFaceLandmarks(true)
                .withFaceDescriptor();

            if (!liveDetection) {
                msgDiv.innerHTML = '<span class="text-danger"><i class="fas fa-times-circle me-1"></i>Aucun visage détecté. Placez votre visage devant la caméra.</span>';
                return;
            }

            // Étape 3 : récupérer la photo enregistrée
            msgDiv.innerHTML = '<span class="text-info"><i class="fas fa-spinner fa-spin me-1"></i>Vérification du profil...</span>';
            let registeredPhotoUrl = '';
            try {
                const photoRes = await fetch(`index.php?page=get_face_photo&email=${encodeURIComponent(savedEmail)}`);
                const photoData = await photoRes.json();
                if (!photoData.success) {
                    msgDiv.innerHTML = '<span class="text-danger"><i class="fas fa-times-circle me-1"></i>Aucun visage enregistré pour ce compte.</span>';
                    setTimeout(() => closeCameraModal(), 2500);
                    return;
                }
                registeredPhotoUrl = photoData.photo_url;
            } catch (e) {
                msgDiv.innerHTML = '<span class="text-danger"><i class="fas fa-exclamation-circle me-1"></i>Erreur serveur.</span>';
                return;
            }

            // Étape 4 : charger la photo enregistrée et calculer son descripteur
            const registeredImg = await faceapi.fetchImage(registeredPhotoUrl);
            const registeredDetection = await faceapi
                .detectSingleFace(registeredImg, detectionOptions)
                .withFaceLandmarks(true)
                .withFaceDescriptor();

            if (!registeredDetection) {
                msgDiv.innerHTML = '<span class="text-warning"><i class="fas fa-exclamation-triangle me-1"></i>Impossible d\'analyser la photo enregistrée. Ré-enregistrez votre visage.</span>';
                setTimeout(() => closeCameraModal(), 3000);
                return;
            }

            // Étape 5 : comparer les descripteurs (distance euclidienne)
            const distance = faceapi.euclideanDistance(liveDetection.descriptor, registeredDetection.descriptor);
            console.log('Distance faciale:', distance); // Pour debug

            const THRESHOLD = 0.55; // 0 = même personne, > 0.6 = différente personne
            if (distance >= THRESHOLD) {
                msgDiv.innerHTML = `<span class="text-danger"><i class="fas fa-times-circle me-1"></i>Visage non reconnu (confiance: ${Math.round((1-distance)*100)}%). Réessayez avec un meilleur éclairage.</span>`;
                setTimeout(() => closeCameraModal(), 3000);
                return;
            }

            // Étape 6 : visage validé → envoyer au backend pour connexion
            msgDiv.innerHTML = `<span class="text-success"><i class="fas fa-check-circle me-1"></i>Visage reconnu (confiance: ${Math.round((1-distance)*100)}%) !</span>`;

            const imageData = canvas.toDataURL('image/jpeg');
            const payload = new FormData();
            payload.append('face_image', imageData);
            payload.append('role', savedRole);
            payload.append('email', savedEmail);

            try {
                const response = await fetch('index.php?page=face_login', {
                    method: 'POST',
                    body: payload
                });
                const rawText = await response.text();
                let result = null;

                try {
                    result = JSON.parse(rawText);
                } catch (e) {
                    console.error('Réponse non JSON face_login:', rawText);
                    msgDiv.innerHTML = '<span class="text-danger"><i class="fas fa-exclamation-circle me-1"></i>Erreur du serveur facial. Vérifiez l\'endpoint face_login.</span>';
                    return;
                }

                if (result.success) {
                    localStorage.setItem('valorys_face_email', savedEmail);
                    localStorage.setItem('valorys_face_role', savedRole);
                    msgDiv.innerHTML = `<span class="text-success"><i class="fas fa-check-circle me-1"></i>${result.message}</span>`;
                    setTimeout(() => { closeCameraModal(); window.location.href = result.redirect; }, 1500);
                } else {
                    msgDiv.innerHTML = `<span class="text-danger"><i class="fas fa-times-circle me-1"></i>${result.message}</span>`;
                    setTimeout(() => closeCameraModal(), 2500);
                }
            } catch (error) {
                msgDiv.innerHTML = '<span class="text-danger"><i class="fas fa-exclamation-circle me-1"></i>Erreur de connexion au serveur.</span>';
                setTimeout(() => closeCameraModal(), 2500);
            }
        }

        // Fermer le modal caméra en cliquant en dehors
        document.getElementById('cameraModal').addEventListener('click', function (e) {
            if (e.target === this) closeCameraModal();
        });

        // ── ERREURS PHP (passées via $error) ─────────────────────────────────
        <?php if (!empty($error)): ?>
        (function() {
            const phpError = <?= json_encode($error) ?>;
            // Tenter de mapper l'erreur PHP sur le bon champ
            const lower = phpError.toLowerCase();
            if (lower.includes('email') || lower.includes('identifiant')) {
                showFieldError('email', 'emailError', 'emailErrorText', phpError);
            } else if (lower.includes('mot de passe') || lower.includes('password')) {
                showFieldError('password', 'passwordError', 'passwordErrorText', phpError);
            } else {
                // Erreur générique : affichée sous le champ email par défaut
                showFieldError('email', 'emailError', 'emailErrorText', phpError);
            }
        })();
        <?php endif; ?>
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
