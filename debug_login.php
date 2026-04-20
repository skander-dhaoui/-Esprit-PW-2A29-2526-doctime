<?php
// FICHIER DE DIAGNOSTIC - À SUPPRIMER APRÈS USAGE
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/config/database.php';

try {
    $db = Database::getInstance()->getConnection();
    echo "<h2>✅ Connexion base de données OK</h2>";

    // Lister tous les users
    $stmt = $db->query("SELECT id, nom, prenom, email, role, statut, LEFT(password,30) as pwd_debut FROM users LIMIT 10");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "<h3>Utilisateurs dans la base :</h3>";
    echo "<table border='1' cellpadding='8' style='border-collapse:collapse;'>";
    echo "<tr><th>ID</th><th>Nom</th><th>Email</th><th>Role</th><th>Statut</th><th>Password (début)</th><th>Test mdp 'password'</th></tr>";
    
    foreach ($users as $u) {
        // Tester si le mot de passe 'password' correspond
        $testPwd = password_verify('password', $u['pwd_debut'] . '...') ? '✅ OUI' : '❌ NON';
        
        // Re-fetch full password for verify
        $stmt2 = $db->prepare("SELECT password FROM users WHERE id = :id");
        $stmt2->execute([':id' => $u['id']]);
        $fullPwd = $stmt2->fetchColumn();
        
        $testPwd      = password_verify('password', $fullPwd)  ? '✅ password'  : '❌';
        $testAdmin123 = password_verify('Admin123', $fullPwd)  ? '✅ Admin123'  : '❌';
        $testAdmin    = password_verify('admin123', $fullPwd)  ? '✅ admin123'  : '❌';
        $testDoctime  = password_verify('doctime', $fullPwd)   ? '✅ doctime'   : '❌';
        
        echo "<tr>";
        echo "<td>{$u['id']}</td>";
        echo "<td>{$u['nom']} {$u['prenom']}</td>";
        echo "<td>{$u['email']}</td>";
        echo "<td><b>{$u['role']}</b></td>";
        echo "<td>{$u['statut']}</td>";
        echo "<td style='font-size:11px;'>{$u['pwd_debut']}</td>";
        echo "<td>$testPwd | $testAdmin123 | $testAdmin | $testDoctime</td>";
        echo "</tr>";
    }
    echo "</table>";

    // Créer un admin de test avec mot de passe connu
    echo "<hr><h3>🔧 Créer/Reset un admin de test</h3>";
    echo "<form method='POST'>";
    echo "<input type='email' name='email' placeholder='email admin' value='admin@doctime.com' style='padding:8px;width:250px;'> ";
    echo "<input type='password' name='pwd' placeholder='nouveau mot de passe' value='Admin123!' style='padding:8px;width:200px;'> ";
    echo "<button type='submit' name='create_admin' style='padding:8px 15px;background:green;color:white;border:none;cursor:pointer;'>Créer/Mettre à jour l'admin</button>";
    echo "</form>";

    if (isset($_POST['create_admin'])) {
        $email = trim($_POST['email']);
        $pwd   = password_hash(trim($_POST['pwd']), PASSWORD_DEFAULT);
        
        // Check if exists
        $check = $db->prepare("SELECT id FROM users WHERE email = :email");
        $check->execute([':email' => $email]);
        $existing = $check->fetchColumn();
        
        if ($existing) {
            $upd = $db->prepare("UPDATE users SET password = :pwd, role = 'admin', statut = 'actif' WHERE email = :email");
            $upd->execute([':pwd' => $pwd, ':email' => $email]);
            echo "<div style='background:#d4edda;padding:10px;margin-top:10px;border-radius:5px;'>✅ Admin mis à jour ! Email: <b>$email</b> / Mot de passe: <b>{$_POST['pwd']}</b></div>";
        } else {
            $ins = $db->prepare("INSERT INTO users (nom, prenom, email, password, role, statut) VALUES ('Admin','System',:email,:pwd,'admin','actif')");
            $ins->execute([':email' => $email, ':pwd' => $pwd]);
            echo "<div style='background:#d4edda;padding:10px;margin-top:10px;border-radius:5px;'>✅ Admin créé ! Email: <b>$email</b> / Mot de passe: <b>{$_POST['pwd']}</b></div>";
        }
        echo "<p><a href='index.php?page=login'>→ Aller au login</a></p>";
    }

} catch (Exception $e) {
    echo "<h2>❌ Erreur base de données :</h2>";
    echo "<pre style='background:#f8d7da;padding:15px;'>" . $e->getMessage() . "</pre>";
    echo "<p>Vérifiez votre fichier <b>config/database.php</b></p>";
}
?>
