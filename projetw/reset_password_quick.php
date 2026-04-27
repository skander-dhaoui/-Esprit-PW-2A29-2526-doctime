<?php
session_start();
require_once __DIR__ . '/config/database.php';

echo "<!DOCTYPE html>
<html lang='fr'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Réinitialiser Mot de Passe</title>
    <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css' rel='stylesheet'>
    <style>
        body { background: linear-gradient(135deg, #2A7FAA 0%, #4CAF50 100%); min-height: 100vh; display: flex; align-items: center; justify-content: center; }
        .card { border-radius: 20px; box-shadow: 0 20px 40px rgba(0,0,0,0.2); width: 100%; max-width: 500px; }
        .card-header { background: linear-gradient(135deg, #2A7FAA 0%, #3e8e41 100%); color: white; padding: 30px; text-align: center; border-radius: 20px 20px 0 0; }
        .form-control { border-radius: 10px; padding: 12px 15px; border: 1px solid #ddd; }
        .form-control:focus { border-color: #4CAF50; box-shadow: 0 0 0 3px rgba(76,175,80,0.1); }
        .btn-submit { background: #4CAF50; color: white; border-radius: 10px; padding: 12px; width: 100%; font-weight: bold; }
        .btn-submit:hover { background: #2A7FAA; }
        .alert { border-radius: 10px; }
        .requirement { font-size: 13px; margin: 5px 0; }
        .requirement.valid { color: #28a745; }
        .requirement.invalid { color: #dc3545; }
    </style>
</head>
<body>
    <div class='card'>
        <div class='card-header'>
            <h4>Réinitialiser votre mot de passe</h4>
        </div>
        <div class='card-body p-4'>";

$db = Database::getInstance()->getConnection();

// Récupérer l'utilisateur avec token valide
$stmt = $db->prepare("
    SELECT id, email, nom, prenom, reset_token, reset_expires
    FROM users 
    WHERE email = :email AND reset_token IS NOT NULL AND reset_expires > NOW()
");
$stmt->execute([':email' => 'afnengorai@gmail.com']);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    echo "<div class='alert alert-warning'>
        ❌ Aucun token valide trouvé. Veuillez d'abord soumettre le formulaire 'Mot de passe oublié'.
        <br><a href='index.php?page=forgot_password' class='btn btn-primary mt-2'>Aller au formulaire</a>
    </div>";
} else {
    echo "<div class='alert alert-info'>
        ✅ Token valide pour: <strong>" . htmlspecialchars($user['nom'] . ' ' . $user['prenom']) . "</strong>
    </div>";
    
    echo "<form method='POST' action='index.php?page=reset_password'>
        <input type='hidden' name='token' value='" . htmlspecialchars($user['reset_token']) . "'>
        
        <div class='mb-3'>
            <label class='form-label'><strong>Nouveau mot de passe</strong></label>
            <input type='password' name='password' id='password' class='form-control' placeholder='Entrez votre nouveau mot de passe' required>
            <div class='mt-2'>
                <div class='requirement invalid' id='req-length'>
                    <i class='fas fa-circle'></i> Au moins 8 caractères
                </div>
                <div class='requirement invalid' id='req-upper'>
                    <i class='fas fa-circle'></i> Au moins une majuscule (A-Z)
                </div>
                <div class='requirement invalid' id='req-number'>
                    <i class='fas fa-circle'></i> Au moins un chiffre (0-9)
                </div>
            </div>
        </div>
        
        <div class='mb-4'>
            <label class='form-label'><strong>Confirmer le mot de passe</strong></label>
            <input type='password' name='confirm_password' id='confirm_password' class='form-control' placeholder='Confirmez votre mot de passe' required>
            <div class='mt-2' id='confirm-match' style='font-size:13px;'></div>
        </div>
        
        <button type='submit' class='btn btn-submit' id='submit-btn'>
            <i class='fas fa-key'></i> Réinitialiser mon mot de passe
        </button>
    </form>";
}

echo "  </div>
    </div>
    
    <script>
        const pwdInput = document.getElementById('password');
        const confirmInput = document.getElementById('confirm_password');
        const submitBtn = document.getElementById('submit-btn');
        
        if (pwdInput) {
            pwdInput.addEventListener('input', function() {
                const pwd = this.value;
                const length = pwd.length >= 8;
                const upper = /[A-Z]/.test(pwd);
                const number = /[0-9]/.test(pwd);
                
                updateRequirement('req-length', length);
                updateRequirement('req-upper', upper);
                updateRequirement('req-number', number);
                
                checkMatch();
            });
        }
        
        if (confirmInput) {
            confirmInput.addEventListener('input', checkMatch);
        }
        
        function updateRequirement(id, valid) {
            const elem = document.getElementById(id);
            if (valid) {
                elem.classList.remove('invalid');
                elem.classList.add('valid');
                elem.innerHTML = '<i class=\"fas fa-check-circle\"></i> ' + elem.textContent.split(' ').slice(1).join(' ');
            } else {
                elem.classList.remove('valid');
                elem.classList.add('invalid');
                elem.innerHTML = '<i class=\"fas fa-circle\"></i> ' + elem.textContent.split(' ').slice(1).join(' ');
            }
        }
        
        function checkMatch() {
            const pwd = pwdInput.value;
            const confirm = confirmInput.value;
            const matchDiv = document.getElementById('confirm-match');
            
            if (confirm === '') {
                matchDiv.innerHTML = '';
                return;
            }
            
            if (pwd === confirm) {
                matchDiv.innerHTML = '<span class=\"valid\"><i class=\"fas fa-check-circle\"></i> Les mots de passe correspondent</span>';
            } else {
                matchDiv.innerHTML = '<span class=\"invalid\"><i class=\"fas fa-times-circle\"></i> Les mots de passe ne correspondent pas</span>';
            }
        }
    </script>
</body>
</html>";
?>
