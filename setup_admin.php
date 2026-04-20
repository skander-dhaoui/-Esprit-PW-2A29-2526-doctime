<?php
/**
 * SETUP ADMIN - Script de création/réinitialisation d'un compte admin
 * À exécuter une seule fois: http://localhost/valorys_Final/setup_admin.php
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/config/database.php';

try {
    $db = Database::getInstance()->getConnection();
    
    echo "<!DOCTYPE html>
    <html>
    <head>
        <meta charset='UTF-8'>
        <title>Setup Admin</title>
        <style>
            body { font-family: Arial; padding: 20px; background: #f5f5f5; }
            .container { background: white; padding: 20px; border-radius: 5px; max-width: 600px; margin: 0 auto; }
            .success { color: green; }
            .error { color: red; }
            .info { color: blue; }
            table { border-collapse: collapse; width: 100%; margin-top: 20px; }
            table, th, td { border: 1px solid #ddd; padding: 10px; text-align: left; }
        </style>
    </head>
    <body>
    <div class='container'>
        <h1>⚙️ Setup Admin User</h1>";
    
    // Delete existing admin users (optional - comment out if you want to keep them)
    // $db->exec("DELETE FROM users WHERE role='admin' AND email='admin@doctime.com'");
    
    // Check existing admins
    $stmt = $db->query("SELECT id, email, statut FROM users WHERE role='admin'");
    $admins = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h3>Comptes Admin Existants:</h3>";
    if (empty($admins)) {
        echo "<p class='error'>❌ Aucun compte admin trouvé</p>";
    } else {
        echo "<table>";
        echo "<tr><th>Email</th><th>Statut</th><th>ID</th></tr>";
        foreach ($admins as $admin) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($admin['email']) . "</td>";
            echo "<td>" . htmlspecialchars($admin['statut']) . "</td>";
            echo "<td>" . htmlspecialchars($admin['id']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    echo "<hr><h3>Créer/Réinitialiser Compte Admin:</h3>";
    echo "<form method='POST'>";
    echo "<p>";
    echo "  <label>Email: <input type='email' name='admin_email' value='admin@doctime.com' required></label>";
    echo "</p>";
    echo "<p>";
    echo "  <label>Mot de passe: <input type='password' name='admin_pwd' value='Admin123!' required></label>";
    echo "</p>";
    echo "<p>";
    echo "  <label>Nom: <input type='text' name='admin_nom' value='Admin' required></label>";
    echo "</p>";
    echo "<p>";
    echo "  <label>Prénom: <input type='text' name='admin_prenom' value='System' required></label>";
    echo "</p>";
    echo "<p>";
    echo "  <button type='submit' name='create_admin'>Créer/Mettre à jour</button>";
    echo "</p>";
    echo "</form>";
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_admin'])) {
        $email = trim($_POST['admin_email']);
        $pwd = trim($_POST['admin_pwd']);
        $nom = trim($_POST['admin_nom']);
        $prenom = trim($_POST['admin_prenom']);
        
        // Hash password
        $hashedPwd = password_hash($pwd, PASSWORD_DEFAULT);
        
        // Check if exists
        $check = $db->prepare("SELECT id FROM users WHERE email = ?");
        $check->execute([$email]);
        $existingId = $check->fetchColumn();
        
        if ($existingId) {
            // Update existing
            $update = $db->prepare("UPDATE users SET password = ?, nom = ?, prenom = ?, role = 'admin', statut = 'actif' WHERE id = ?");
            $result = $update->execute([$hashedPwd, $nom, $prenom, $existingId]);
            
            if ($result) {
                echo "<p class='success'>✅ Compte admin MISE À JOUR</p>";
                echo "<p class='info'>Email: <strong>$email</strong></p>";
                echo "<p class='info'>Mot de passe: <strong>$pwd</strong></p>";
                echo "<p class='info'>Statut: <strong>actif</strong></p>";
            } else {
                echo "<p class='error'>❌ Erreur lors de la mise à jour</p>";
            }
        } else {
            // Create new
            $insert = $db->prepare("INSERT INTO users (nom, prenom, email, password, role, statut, telephone) VALUES (?, ?, ?, ?, 'admin', 'actif', '0600000000')");
            $result = $insert->execute([$nom, $prenom, $email, $hashedPwd]);
            
            if ($result) {
                echo "<p class='success'>✅ Compte admin CRÉÉ</p>";
                echo "<p class='info'>Email: <strong>$email</strong></p>";
                echo "<p class='info'>Mot de passe: <strong>$pwd</strong></p>";
                echo "<p class='info'>Statut: <strong>actif</strong></p>";
            } else {
                echo "<p class='error'>❌ Erreur lors de la création</p>";
            }
        }
        
        echo "<hr>";
        echo "<p><a href='index.php?page=login'>→ Aller au login</a></p>";
    }
    
    // Verify the admin account can login
    echo "<hr><h3>Vérification du Login:</h3>";
    $stmt = $db->prepare("SELECT id, email, role, statut, password FROM users WHERE email = 'admin@doctime.com' LIMIT 1");
    $stmt->execute();
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($admin) {
        echo "<p class='success'>✅ Compte admin trouvé</p>";
        echo "<p>Email: " . htmlspecialchars($admin['email']) . "</p>";
        echo "<p>Role: " . htmlspecialchars($admin['role']) . "</p>";
        echo "<p>Statut: " . htmlspecialchars($admin['statut']) . "</p>";
        
        if (password_verify('Admin123!', $admin['password'])) {
            echo "<p class='success'>✅ Mot de passe 'Admin123!' CORRECT</p>";
        } else {
            echo "<p class='error'>❌ Mot de passe 'Admin123!' incorrect</p>";
        }
        
        if ($admin['role'] === 'admin' && $admin['statut'] === 'actif') {
            echo "<p class='success'><strong>✅ LE LOGIN ADMIN DEVRAIT FONCTIONNER!</strong></p>";
        } else {
            echo "<p class='error'>❌ Role ou statut incorrect</p>";
        }
    } else {
        echo "<p class='error'>❌ Aucun compte admin@doctime.com trouvé</p>";
    }
    
    echo "
    </div>
    </body>
    </html>";
    
} catch (Exception $e) {
    die("<h2 style='color:red;'>❌ Erreur: " . htmlspecialchars($e->getMessage()) . "</h2>");
}
?>
