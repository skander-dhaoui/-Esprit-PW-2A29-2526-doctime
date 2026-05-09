<?php
// views/frontoffice/reset_password.php
$error = $_SESSION['error'] ?? null;
$validToken = $_SESSION['valid_token'] ?? false;
$token = $_GET['token'] ?? null;
unset($_SESSION['error'], $_SESSION['valid_token']);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Réinitialisation - DocTime</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { background: linear-gradient(135deg, #2A7FAA 0%, #4CAF50 100%); min-height: 100vh; display: flex; align-items: center; justify-content: center; font-family: 'Segoe UI', sans-serif; padding: 20px; }
        .card { background: white; border-radius: 20px; box-shadow: 0 20px 40px rgba(0,0,0,0.1); overflow: hidden; width: 100%; max-width: 480px; }
        .card-header { background: linear-gradient(135deg, #2A7FAA 0%, #3e8e41 100%); color: white; padding: 30px; text-align: center; }
        .card-body { padding: 35px; }
        .form-control { border-radius: 10px; padding: 12px 15px; border: 1px solid #ddd; }
        .form-control:focus { border-color: #4CAF50; box-shadow: 0 0 0 3px rgba(76,175,80,0.1); }
        .btn-submit { background: #4CAF50; color: white; border-radius: 10px; padding: 12px; width: 100%; font-weight: bold; font-size: 16px; border: none; transition: all 0.3s; }
        .btn-submit:hover { background: #2A7FAA; transform: translateY(-2px); }
        .alert-error-custom { background: #f8d7da; color: #721c24; border-radius: 10px; padding: 12px 15px; margin-bottom: 20px; border-left: 4px solid #dc3545; }
        .password-requirements { font-size: 12px; color: #999; margin-top: 5px; }
        .requirement-valid { color: #4CAF50; }
        .requirement-invalid { color: #dc3545; }
    </style>
</head>
<body>
    <div class="card">
        <div class="card-header">
            <h4>Nouveau mot de passe</h4>
            <p class="mb-0 mt-2 opacity-75">Créez un mot de passe sécurisé</p>
        </div>
        <div class="card-body">
            <?php if ($error): ?>
                <div class="alert-error-custom">
                    <i class="fas fa-exclamation-circle me-2"></i> <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="index.php?page=reset_password">
                <div class="mb-3">
                    <label class="form-label">Nouveau mot de passe</label>
                    <input type="password" name="password" id="password" class="form-control">
                    <div class="password-requirements">
                        <span id="reqLength" class="requirement-invalid"><i class="fas fa-circle me-1"></i> Au moins 8 caractères</span><br>
                        <span id="reqUpper" class="requirement-invalid"><i class="fas fa-circle me-1"></i> Au moins une majuscule</span><br>
                        <span id="reqNumber" class="requirement-invalid"><i class="fas fa-circle me-1"></i> Au moins un chiffre</span>
                    </div>
                </div>
                <div class="mb-4">
                    <label class="form-label">Confirmer le mot de passe</label>
                    <input type="password" name="confirm_password" id="confirm_password" class="form-control">
                </div>
                <button type="submit" class="btn-submit">
                    <i class="fas fa-key me-2"></i> Réinitialiser
                </button>
            </form>
        </div>
    </div>

    <script>
        function updatePasswordRequirements() {
            const p = document.getElementById('password').value;
            const lengthOk = p.length >= 8;
            const upperOk = /[A-Z]/.test(p);
            const numberOk = /[0-9]/.test(p);
            
            document.getElementById('reqLength').className = lengthOk ? 'requirement-valid' : 'requirement-invalid';
            document.getElementById('reqLength').innerHTML = '<i class="fas fa-' + (lengthOk ? 'check-circle' : 'circle') + ' me-1"></i> Au moins 8 caractères';
            
            document.getElementById('reqUpper').className = upperOk ? 'requirement-valid' : 'requirement-invalid';
            document.getElementById('reqUpper').innerHTML = '<i class="fas fa-' + (upperOk ? 'check-circle' : 'circle') + ' me-1"></i> Au moins une majuscule';
            
            document.getElementById('reqNumber').className = numberOk ? 'requirement-valid' : 'requirement-invalid';
            document.getElementById('reqNumber').innerHTML = '<i class="fas fa-' + (numberOk ? 'check-circle' : 'circle') + ' me-1"></i> Au moins un chiffre';
        }
        
        document.getElementById('password').addEventListener('input', updatePasswordRequirements);
        
        document.getElementById('confirm_password').addEventListener('input', function() {
            const pwd = document.getElementById('password').value;
            if (this.value && pwd !== this.value) {
                this.style.borderColor = '#dc3545';
            } else {
                this.style.borderColor = '#ddd';
            }
        });
    </script>
</body>
</html>