<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/models/User.php';

try {
    $db = Database::getInstance()->getConnection();
    
    echo "<h2 style='color:blue;'>🔍 DIAGNOSTIC LOGIN ADMIN</h2>\n";
    
    // 1. Check table structure
    echo "<h3>1️⃣ Structure de la table 'users'</h3>\n";
    $stmt = $db->query("DESCRIBE users");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<table border='1' cellpadding='5'><tr><th>Column</th><th>Type</th><th>Null</th><th>Key</th></tr>";
    foreach ($columns as $col) {
        echo "<tr><td>{$col['Field']}</td><td>{$col['Type']}</td><td>{$col['Null']}</td><td>{$col['Key']}</td></tr>";
    }
    echo "</table>\n";
    
    // 2. Check for admin users
    echo "<h3>2️⃣ Utilisateurs ADMIN</h3>\n";
    $stmt = $db->query("SELECT id, nom, prenom, email, role, statut FROM users WHERE role='admin'");
    $admins = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($admins)) {
        echo "<p style='color:red;'>❌ AUCUN ADMIN TROUVÉ !</p>\n";
        echo "<p>Création d'un admin de test...</p>\n";
        
        $email = 'admin@doctime.test';
        $pwd = password_hash('Admin123!', PASSWORD_DEFAULT);
        
        $stmt = $db->prepare("INSERT INTO users (nom, prenom, email, password, role, statut, telephone) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $result = $stmt->execute(['Admin', 'Test', $email, $pwd, 'admin', 'actif', '0600000000']);
        
        if ($result) {
            echo "<p style='color:green;'>✅ Admin créé avec succès !</p>\n";
            echo "<p><b>Email:</b> $email</p>\n";
            echo "<p><b>Mot de passe:</b> Admin123!</p>\n";
        }
    } else {
        echo "<p style='color:green;'>✅ " . count($admins) . " admin(s) trouvé(s)</p>\n";
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>ID</th><th>Email</th><th>Role</th><th>Statut</th><th>Test mdp</th></tr>";
        
        foreach ($admins as $admin) {
            // Test common passwords
            $testPwd = $db->prepare("SELECT password FROM users WHERE id = ?");
            $testPwd->execute([$admin['id']]);
            $hashedPwd = $testPwd->fetchColumn();
            
            $pwdTest = password_verify('Admin123!', $hashedPwd) ? '✅ Admin123!' : '❌';
            
            echo "<tr>";
            echo "<td>{$admin['id']}</td>";
            echo "<td>{$admin['email']}</td>";
            echo "<td>{$admin['role']}</td>";
            echo "<td>{$admin['statut']}</td>";
            echo "<td>$pwdTest</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    // 3. Check all users
    echo "<h3>3️⃣ Tous les utilisateurs</h3>\n";
    $stmt = $db->query("SELECT id, email, role, statut FROM users LIMIT 10");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<p>Total: " . $stmt->rowCount() . " utilisateurs (affichage limité à 10)</p>";
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>ID</th><th>Email</th><th>Role</th><th>Statut</th></tr>";
    foreach ($users as $u) {
        echo "<tr><td>{$u['id']}</td><td>{$u['email']}</td><td>{$u['role']}</td><td>{$u['statut']}</td></tr>";
    }
    echo "</table>";
    
    // 4. Test login logic
    echo "<h3>4️⃣ Simulation du login</h3>\n";
    echo "<form method='POST'>";
    echo "<p><label>Email: <input type='email' name='test_email' value='admin@doctime.test'></label></p>";
    echo "<p><label>Password: <input type='password' name='test_pwd' value='Admin123!'></label></p>";
    echo "<p><button type='submit' name='test_login'>Tester le login</button></p>";
    echo "</form>";
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['test_login'])) {
        $testEmail = $_POST['test_email'];
        $testPwd = $_POST['test_pwd'];
        
        echo "<hr><h4>Résultat du test:</h4>";
        
        $user = $db->prepare("SELECT * FROM users WHERE email = ?");
        $user->execute([$testEmail]);
        $userData = $user->fetch(PDO::FETCH_ASSOC);
        
        if (!$userData) {
            echo "<p style='color:red;'>❌ User not found</p>";
        } else {
            echo "<p style='color:green;'>✅ User found</p>";
            echo "<p>Role: " . $userData['role'] . "</p>";
            echo "<p>Statut: " . $userData['statut'] . "</p>";
            
            if (password_verify($testPwd, $userData['password'])) {
                echo "<p style='color:green;'>✅ Password CORRECT</p>";
                if ($userData['statut'] === 'actif') {
                    echo "<p style='color:green;'>✅ Account ACTIVE</p>";
                    if ($userData['role'] === 'admin') {
                        echo "<p style='color:green;'>✅ User is ADMIN</p>";
                        echo "<p style='color:green;'><b>✅ LOGIN SHOULD SUCCEED!</b></p>";
                    } else {
                        echo "<p style='color:red;'>❌ User is not admin</p>";
                    }
                } else {
                    echo "<p style='color:red;'>❌ Account is " . $userData['statut'] . "</p>";
                }
            } else {
                echo "<p style='color:red;'>❌ Password INCORRECT</p>";
            }
        }
    }
    
} catch (Exception $e) {
    echo "<h2 style='color:red;'>❌ ERROR</h2>";
    echo "<pre>" . $e->getMessage() . "</pre>";
}
?>
