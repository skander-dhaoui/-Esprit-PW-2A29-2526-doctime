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
        .btn-login { background: #4CAF50; color: white; border-radius: 10px; padding: 12px; width: 100%; font-weight: bold; font-size: 16px; border: none; transition: all 0.3s; }
        .btn-login:hover { background: #2A7FAA; transform: translateY(-2px); }
        .btn-camera { background: #6c757d; color: white; border-radius: 10px; padding: 12px; width: 100%; font-weight: bold; font-size: 16px; border: none; transition: all 0.3s; margin-bottom: 15px; }
        .btn-camera:hover { background: #5a6268; transform: translateY(-2px); }
        .role-selector { display: flex; gap: 15px; margin-bottom: 25px; }
        .role-option { flex: 1; text-align: center; padding: 12px; border: 2px solid #e0e0e0; border-radius: 12px; cursor: pointer; transition: all 0.3s; background: #f9f9f9; }
        .role-option i { font-size: 24px; margin-bottom: 5px; display: block; }
        .role-option.active { border-color: #4CAF50; background: #e8f5e9; color: #4CAF50; }
        .role-option:hover { border-color: #4CAF50; transform: translateY(-2px); }
        .alert-php { border-radius: 10px; padding: 12px 15px; margin-bottom: 20px; }
        .alert-error-js { background: #f8d7da; color: #721c24; border-radius: 10px; padding: 12px 15px; margin-bottom: 20px; display: none; }
        .alert-success-js { background: #d4edda; color: #155724; border-radius: 10px; padding: 12px 15px; margin-bottom: 20px; display: none; }
        .forgot-link, .register-link { color: #2A7FAA; text-decoration: none; font-size: 14px; }
        .forgot-link:hover, .register-link:hover { color: #4CAF50; text-decoration: underline; }
        hr { margin: 20px 0; }
        .modal-camera { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.8); z-index: 1000; align-items: center; justify-content: center; }
        .modal-camera-content { background: white; border-radius: 20px; width: 90%; max-width: 500px; padding: 20px; text-align: center; }
        .modal-camera-content video { width: 100%; border-radius: 10px; margin-bottom: 15px; }
        .modal-camera-content canvas { display: none; }
        .captcha-box { background: #f8f9fa; border-radius: 10px; padding: 15px; text-align: center; margin-bottom: 20px; }
        .captcha-code { font-size: 28px; font-weight: bold; letter-spacing: 8px; background: #2A7FAA; color: white; display: inline-block; padding: 10px 20px; border-radius: 10px; font-family: monospace; margin-bottom: 10px; }
        .captcha-refresh { cursor: pointer; color: #2A7FAA; margin-left: 10px; }
        .captcha-refresh:hover { color: #4CAF50; }
        
        /* Spinner */
        .spinner-border-sm { width: 1rem; height: 1rem; border-width: 0.2em; }
        .text-info i { margin-right: 5px; }
    </style>
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

            <?php if (!empty($error)): ?>
                <div class="alert alert-danger alert-php">
                    <i class="fas fa-exclamation-circle me-2"></i>
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($_SESSION['success'])): ?>
                <div class="alert alert-success alert-php">
                    <i class="fas fa-check-circle me-2"></i>
                    <?= htmlspecialchars($_SESSION['success']) ?>
                </div>
                <?php unset($_SESSION['success']); ?>
            <?php endif; ?>

            <div id="errorMessage" class="alert-error-js">
                <i class="fas fa-exclamation-circle me-2"></i>
                <span id="errorText"></span>
            </div>
            <div id="successMessage" class="alert-success-js">
                <i class="fas fa-check-circle me-2"></i>
                <span id="successText"></span>
            </div>

            <button type="button" class="btn-camera" onclick="openCameraModal()">
                <i class="fas fa-camera me-2"></i> Connexion avec reconnaissance faciale
            </button>

            <div class="text-center mb-3"><span class="text-muted">ou</span></div>

            <div class="role-selector">
                <div class="role-option active" data-role="patient">
                    <i class="fas fa-user"></i><div>Patient</div>
                </div>
                <div class="role-option" data-role="medecin">
                    <i class="fas fa-user-md"></i><div>Médecin</div>
                </div>
                <div class="role-option" data-role="admin">
                    <i class="fas fa-shield-alt"></i><div>Admin</div>
                </div>
            </div>

            <form id="loginForm" method="POST" action="index.php?page=login">
                <input type="hidden" name="role" id="selectedRole" value="patient">

                <div class="mb-3">
                    <label class="form-label">Email</label>
                    <input type="email" name="email" id="email" class="form-control"
                           placeholder="exemple@email.com"
                           value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">Mot de passe</label>
                    <input type="password" name="password" id="password" class="form-control"
                           placeholder="••••••••" required>
                </div>

                <div class="captcha-box">
                    <div>
                        <span class="captcha-code" id="captchaCode"></span>
                        <i class="fas fa-sync-alt captcha-refresh" onclick="generateCaptcha()" title="Recharger"></i>
                    </div>
                    <input type="text" id="captchaInput" class="form-control mt-2"
                           placeholder="Saisissez le code ci-dessus" style="text-align:center;">
                </div>

                <div class="mb-3 form-check d-flex justify-content-between align-items-center">
                    <div>
                        <input type="checkbox" class="form-check-input" id="remember" name="remember">
                        <label class="form-check-label" for="remember">Se souvenir de moi</label>
                    </div>
                    <a href="index.php?page=forgot_password" class="forgot-link">Mot de passe oublié ?</a>
                </div>

                <button type="submit" class="btn-login">
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
            <h4><i class="fas fa-camera"></i> Reconnaissance faciale</h4>
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
            <div id="cameraMessage" class="mt-2"></div>
        </div>
    </div>

    <script>
        // ── CAPTCHA ──────────────────────────────────
        let currentCaptcha = "";
        function generateCaptcha() {
            const chars = "ABCDEFGHJKLMNPQRSTUVWXYZ0123456789";
            let c = "";
            for (let i = 0; i < 6; i++) c += chars.charAt(Math.floor(Math.random() * chars.length));
            currentCaptcha = c;
            document.getElementById("captchaCode").innerText = c;
        }
        function verifyCaptcha() {
            return document.getElementById("captchaInput").value.toUpperCase() === currentCaptcha;
        }
        generateCaptcha();

        // ── RÔLE ─────────────────────────────────────
        document.querySelectorAll('.role-option').forEach(o => {
            o.addEventListener('click', function () {
                document.querySelectorAll('.role-option').forEach(x => x.classList.remove('active'));
                this.classList.add('active');
                document.getElementById('selectedRole').value = this.dataset.role;
            });
        });

        // ── SOUMISSION FORMULAIRE ────────────────────
        document.getElementById('loginForm').addEventListener('submit', function (e) {
            const email    = document.getElementById('email').value;
            const password = document.getElementById('password').value;

            if (!email || !password) {
                e.preventDefault();
                showError('Veuillez remplir tous les champs.');
                return;
            }
            if (!verifyCaptcha()) {
                e.preventDefault();
                showError('Code CAPTCHA incorrect. Réessayez.');
                generateCaptcha();
                document.getElementById('captchaInput').value = '';
                return;
            }
        });

        // ── MESSAGES JS ──────────────────────────────
        function showError(msg) {
            const d = document.getElementById('errorMessage');
            document.getElementById('errorText').innerText = msg;
            d.style.display = 'block';
            setTimeout(() => d.style.display = 'none', 5000);
        }
        function showSuccess(msg) {
            const d = document.getElementById('successMessage');
            document.getElementById('successText').innerText = msg;
            d.style.display = 'block';
        }

        // ── CAMÉRA AVEC RECONNAISSANCE FACIALE ─────────────────
        let stream = null;
        
        function openCameraModal() {
            document.getElementById('cameraModal').style.display = 'flex';
            startCamera();
        }
        
        function closeCameraModal() {
            if (stream) stream.getTracks().forEach(t => t.stop());
            document.getElementById('cameraModal').style.display = 'none';
            const msgDiv = document.getElementById('cameraMessage');
            if (msgDiv) msgDiv.innerHTML = '';
        }
        
        async function startCamera() {
            try {
                stream = await navigator.mediaDevices.getUserMedia({ video: true });
                document.getElementById('video').srcObject = stream;
            } catch (err) {
                const msgDiv = document.getElementById('cameraMessage');
                if (msgDiv) msgDiv.innerHTML = '<span class="text-danger"><i class="fas fa-exclamation-circle me-1"></i>Impossible d\'accéder à la caméra.</span>';
            }
        }
        
        async function captureFace() {
            const video = document.getElementById('video');
            const canvas = document.getElementById('canvas');
            const ctx = canvas.getContext('2d');
            const msgDiv = document.getElementById('cameraMessage');
            
            canvas.width = video.videoWidth;
            canvas.height = video.videoHeight;
            ctx.drawImage(video, 0, 0, canvas.width, canvas.height);
            
            const imageData = canvas.toDataURL('image/jpeg');
            
            if (msgDiv) {
                msgDiv.innerHTML = '<span class="text-info"><i class="fas fa-spinner fa-spin me-1"></i>Reconnaissance en cours...</span>';
            }
            
            try {
                const response = await fetch('index.php?page=face_login', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: 'face_image=' + encodeURIComponent(imageData)
                });
                
                const result = await response.json();
                
                if (result.success) {
                    if (msgDiv) {
                        msgDiv.innerHTML = '<span class="text-success"><i class="fas fa-check-circle me-1"></i>' + result.message + '</span>';
                    }
                    setTimeout(() => {
                        closeCameraModal();
                        window.location.href = result.redirect;
                    }, 1500);
                } else {
                    if (msgDiv) {
                        msgDiv.innerHTML = '<span class="text-danger"><i class="fas fa-times-circle me-1"></i>' + result.message + '</span>';
                    }
                    setTimeout(() => {
                        closeCameraModal();
                    }, 2000);
                }
            } catch (error) {
                console.error('Erreur:', error);
                if (msgDiv) {
                    msgDiv.innerHTML = '<span class="text-danger"><i class="fas fa-exclamation-circle me-1"></i>Erreur de connexion au serveur</span>';
                }
                setTimeout(() => {
                    closeCameraModal();
                }, 2000);
            }
        }
    </script>
</body>
</html>