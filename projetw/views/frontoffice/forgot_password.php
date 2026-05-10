<?php
// views/frontoffice/forgot_password.php
$error = $_SESSION['error'] ?? null;
$success = $_SESSION['success'] ?? null;
unset($_SESSION['error'], $_SESSION['success']);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mot de passe oublié - DocTime</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { background: linear-gradient(135deg, #2A7FAA 0%, #4CAF50 100%); min-height: 100vh; display: flex; align-items: center; justify-content: center; font-family: 'Segoe UI', sans-serif; padding: 20px; }
        .card { background: white; border-radius: 20px; box-shadow: 0 20px 40px rgba(0,0,0,0.1); overflow: hidden; width: 100%; max-width: 480px; animation: fadeIn 0.5s ease; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(-20px); } to { opacity: 1; transform: translateY(0); } }
        .card-header { background: linear-gradient(135deg, #2A7FAA 0%, #3e8e41 100%); color: white; padding: 30px; text-align: center; }
        .card-header .logo-container { display: flex; justify-content: center; margin-bottom: 10px; }
        .card-header .logo-container img { height: 90px; width: auto; object-fit: contain; }
        .card-body { padding: 35px; }
        .form-control { border-radius: 10px; padding: 12px 15px; border: 1px solid #ddd; }
        .form-control:focus { border-color: #4CAF50; box-shadow: 0 0 0 3px rgba(76,175,80,0.1); }
        .btn-submit { background: #4CAF50; color: white; border-radius: 10px; padding: 12px; width: 100%; font-weight: bold; font-size: 16px; border: none; transition: all 0.3s; }
        .btn-submit:hover { background: #2A7FAA; transform: translateY(-2px); }
        .alert-error-custom { background: #f8d7da; color: #721c24; border-radius: 10px; padding: 12px 15px; margin-bottom: 20px; border-left: 4px solid #dc3545; }
        .alert-success-custom { background: #d4edda; color: #155724; border-radius: 10px; padding: 12px 15px; margin-bottom: 20px; border-left: 4px solid #28a745; }
        .back-link { color: #2A7FAA; text-decoration: none; }
        .back-link:hover { color: #4CAF50; text-decoration: underline; }
    </style>
</head>
<body>
    <div class="card">
        <div class="card-header">
            <div class="logo-container">
                <img src="assets/images/logo_doctime.png" alt="DocTime Logo"
                     onerror="this.style.display='none'; document.getElementById('logoFallback').style.display='block';">
                <i id="logoFallback" class="fas fa-stethoscope" style="display:none; font-size:60px;"></i>
            </div>
            <h4>Mot de passe oublié ?</h4>
            <p class="mb-0 mt-2 opacity-75">Nous vous enverrons un lien pour le réinitialiser</p>
        </div>
        <div class="card-body">
            <?php if ($error): ?>
                <div class="alert-error-custom">
                    <i class="fas fa-exclamation-circle me-2"></i> <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert-success-custom">
                    <i class="fas fa-check-circle me-2"></i> <?= htmlspecialchars($success) ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="index.php?page=forgot_password">
                <div class="mb-4">
                    <label class="form-label">Votre adresse email</label>
                    <input type="email" name="email" class="form-control" placeholder="exemple@email.com" required>
                </div>
                <button type="submit" class="btn-submit">
                    <i class="fas fa-paper-plane me-2"></i> Envoyer le lien
                </button>
            </form>

            <hr>
            <div class="text-center">
                <a href="index.php?page=login" class="back-link">
                    <i class="fas fa-arrow-left me-1"></i> Retour à la connexion
                </a>
            </div>
        </div>
    </div>
</body>
</html>