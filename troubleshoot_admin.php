<?php
/**
 * ADMIN LOGIN TROUBLESHOOT
 * 诊断脚本 - 诊断并修复管理员登录问题
 * Diagnostic script to identify and fix admin login issues
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Don't start session yet - we'll handle it manually
if (session_status() === PHP_SESSION_NONE) {
    @session_start();
}

require_once __DIR__ . '/config/database.php';

?><!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Admin Login Troubleshoot</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; background: #f5f5f5; padding: 20px; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 30px; border-radius: 10px; }
        h1 { color: #333; margin-bottom: 30px; border-bottom: 3px solid #4CAF50; padding-bottom: 10px; }
        h2 { color: #2A7FAA; margin-top: 30px; margin-bottom: 15px; }
        .section { margin-bottom: 30px; padding: 15px; background: #f9f9f9; border-left: 4px solid #4CAF50; }
        .success { color: #22863a; background: #e8f5e9; padding: 10px; margin: 10px 0; border-radius: 5px; }
        .error { color: #cb2431; background: #ffeef0; padding: 10px; margin: 10px 0; border-radius: 5px; }
        .warning { color: #e36c09; background: #fff8e5; padding: 10px; margin: 10px 0; border-radius: 5px; }
        .info { color: #1f6feb; background: #ddf4ff; padding: 10px; margin: 10px 0; border-radius: 5px; }
        table { width: 100%; border-collapse: collapse; margin: 15px 0; }
        table, th, td { border: 1px solid #ddd; padding: 12px; text-align: left; }
        th { background: #f5f5f5; font-weight: bold; }
        form { margin: 15px 0; padding: 15px; background: white; border: 1px solid #ddd; border-radius: 5px; }
        input, button { padding: 8px 12px; margin: 5px 0; border: 1px solid #ddd; border-radius: 3px; }
        button { background: #4CAF50; color: white; cursor: pointer; border: none; }
        button:hover { background: #45a049; }
        .code { background: #f4f4f4; padding: 2px 5px; border-radius: 3px; font-family: monospace; }
        a { color: #4CAF50; text-decoration: none; }
        a:hover { text-decoration: underline; }
    </style>
</head>
<body>
<div class="container">
    <h1>🔧 Diagnostic Login Admin</h1>
    
    <?php
    try {
        $db = Database::getInstance()->getConnection();
        
        // ===== STEP 1: Vérifier les tables =====
        echo "<h2>📋 Étape 1: Vérification de la base de données</h2>";
        echo "<div class='section'>";
        
        $result = $db->query("SHOW TABLES");
        $tables = $result->fetchAll(PDO::FETCH_COLUMN);
        
        if (in_array('users', $tables)) {
            echo "<div class='success'>✅ Table 'users' existe</div>";
        } else {
            echo "<div class='error'>❌ Table 'users' N'EXISTE PAS!</div>";
        }
        
        // Check users count
        $userCount = $db->query("SELECT COUNT(*) FROM users")->fetchColumn();
        echo "<div class='info'>Total utilisateurs: <strong>$userCount</strong></div>";
        
        echo "</div>";
        
        // ===== STEP 2: Vérifier les admins =====
        echo "<h2>👤 Étape 2: Vérification des comptes admin</h2>";
        echo "<div class='section'>";
        
        $admins = $db->query("SELECT id, email, role, statut FROM users WHERE role='admin'")->fetchAll();
        
        if (empty($admins)) {
            echo "<div class='error'>❌ AUCUN COMPTE ADMIN TROUVÉ!</div>";
            echo "<p>Cela explique pourquoi vous ne pouvez pas vous connecter en tant qu'administrateur.</p>";
        } else {
            echo "<div class='success'>✅ " . count($admins) . " admin(s) trouvé(s)</div>";
            echo "<table>";
            echo "<tr><th>ID</th><th>Email</th><th>Role</th><th>Statut</th></tr>";
            foreach ($admins as $admin) {
                $statusClass = $admin['statut'] === 'actif' ? 'success' : 'warning';
                echo "<tr>";
                echo "<td>{$admin['id']}</td>";
                echo "<td>{$admin['email']}</td>";
                echo "<td>{$admin['role']}</td>";
                echo "<td><span class='$statusClass'>{$admin['statut']}</span></td>";
                echo "</tr>";
            }
            echo "</table>";
        }
        
        echo "</div>";
        
        // ===== STEP 3: Créer un admin de test =====
        echo "<h2>➕ Étape 3: Créer/Réinitialiser un compte admin</h2>";
        echo "<div class='section'>";
        
        echo "<form method='POST'>";
        echo "<h3>Formulaire de création d'admin</h3>";
        echo "<p>";
        echo "  <label>Email:</label><br>";
        echo "  <input type='email' name='email' value='admin@doctime.com' required>";
        echo "</p>";
        echo "<p>";
        echo "  <label>Mot de passe:</label><br>";
        echo "  <input type='password' name='password' value='Admin123!' required>";
        echo "</p>";
        echo "<p>";
        echo "  <label>Nom:</label><br>";
        echo "  <input type='text' name='nom' value='Admin' required>";
        echo "</p>";
        echo "<p>";
        echo "  <label>Prénom:</label><br>";
        echo "  <input type='text' name='prenom' value='System' required>";
        echo "</p>";
        echo "<button type='submit' name='create_admin'>Créer/Réinitialiser Admin</button>";
        echo "</form>";
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_admin'])) {
            $email = trim($_POST['email']);
            $password = trim($_POST['password']);
            $nom = trim($_POST['nom']);
            $prenom = trim($_POST['prenom']);
            
            // Validate
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                echo "<div class='error'>❌ Email invalide</div>";
            } else {
                $hashedPwd = password_hash($password, PASSWORD_DEFAULT);
                
                // Check if exists
                $check = $db->prepare("SELECT id FROM users WHERE email = ?");
                $check->execute([$email]);
                $id = $check->fetchColumn();
                
                if ($id) {
                    // Update
                    $stmt = $db->prepare("UPDATE users SET password=?, nom=?, prenom=?, role='admin', statut='actif' WHERE id=?");
                    $stmt->execute([$hashedPwd, $nom, $prenom, $id]);
                    echo "<div class='success'>✅ Compte admin MIS À JOUR</div>";
                } else {
                    // Create
                    $stmt = $db->prepare("INSERT INTO users (email, password, nom, prenom, role, statut, telephone) VALUES (?, ?, ?, ?, 'admin', 'actif', '0600000000')");
                    $stmt->execute([$email, $hashedPwd, $nom, $prenom]);
                    echo "<div class='success'>✅ Compte admin CRÉÉ</div>";
                }
                
                echo "<div class='info'>";
                echo "  <p><strong>Identifiants:</strong></p>";
                echo "  <p>Email: <code>$email</code></p>";
                echo "  <p>Mot de passe: <code>$password</code></p>";
                echo "  <p>Statut: <strong>actif</strong></p>";
                echo "</div>";
                
                echo "<div class='warning'>";
                echo "  <strong>⚠️ À faire ensuite:</strong>";
                echo "  <ol>";
                echo "    <li><a href='index.php?page=login'>Aller au login</a></li>";
                echo "    <li>Cliquer sur le rôle <strong>Admin</strong></li>";
                echo "    <li>Entrer l'email et le mot de passe ci-dessus</li>";
                echo "    <li>Vous devriez accéder au dashboard</li>";
                echo "  </ol>";
                echo "</div>";
            }
        }
        
        echo "</div>";
        
        // ===== STEP 4: Test le login =====
        echo "<h2">🧪 Étape 4: Tester le login</h2>";
        echo "<div class='section'>";
        
        $stmt = $db->prepare("SELECT * FROM users WHERE email = ? AND role = 'admin' LIMIT 1");
        $stmt->execute(['admin@doctime.com']);
        $adminUser = $stmt->fetch();
        
        if ($adminUser) {
            echo "<div class='success'>✅ Compte admin@doctime.com trouvé</div>";
            
            $checks = [
                'role === admin' => $adminUser['role'] === 'admin',
                'statut === actif' => $adminUser['statut'] === 'actif',
                'password_verify(Admin123!)' => password_verify('Admin123!', $adminUser['password']),
            ];
            
            echo "<table>";
            echo "<tr><th>Vérification</th><th>Résultat</th></tr>";
            foreach ($checks as $check => $result) {
                $status = $result ? 'success' : 'error';
                $icon = $result ? '✅' : '❌';
                echo "<tr><td>$check</td><td><span class='$status'>$icon</span></td></tr>";
            }
            echo "</table>";
            
            if (array_sum(array_values($checks)) === count($checks)) {
                echo "<div class='success'><strong>✅ LE LOGIN ADMIN DEVRAIT FONCTIONNER!</strong></div>";
                echo "<p><a href='index.php?page=login' style='background:#4CAF50;color:white;padding:10px 20px;border-radius:5px;display:inline-block;'>→ Aller au login</a></p>";
            } else {
                echo "<div class='error'><strong>❌ Il y a un problème</strong></div>";
                if ($adminUser['statut'] !== 'actif') {
                    echo "<p>Le statut n'est pas 'actif'. Statut actuel: <strong>" . $adminUser['statut'] . "</strong></p>";
                }
            }
        } else {
            echo "<div class='warning'>⚠️ Aucun compte admin@doctime.com trouvé</div>";
            echo "<p>Utilisez le formulaire ci-dessus pour en créer un.</p>";
        }
        
        echo "</div>";
        
    } catch (Exception $e) {
        echo "<div class='error'><strong>❌ Erreur:</strong> " . htmlspecialchars($e->getMessage()) . "</div>";
    }
    
    ?>
    
</div>
</body>
</html>
