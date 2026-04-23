<?php
// Script pour réinitialiser le mot de passe d'un utilisateur
$host   = 'localhost';
$dbname = 'doctime_db';
$user   = 'root';
$pass   = '';

echo "<style>body{font-family:monospace;padding:20px;} .ok{color:green;font-weight:bold;} .ko{color:red;font-weight:bold;}</style>";
echo "<h2>🔧 Réinitialisation de mot de passe</h2>";

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Si formulaire soumis
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['email']) && !empty($_POST['new_password'])) {
        $email    = trim($_POST['email']);
        $newPass  = trim($_POST['new_password']);
        $newHash  = password_hash($newPass, PASSWORD_DEFAULT);

        $stmt = $pdo->prepare("UPDATE users SET password = :password WHERE email = :email");
        $stmt->execute([':password' => $newHash, ':email' => $email]);

        if ($stmt->rowCount() > 0) {
            echo "<p class='ok'>✅ Mot de passe mis à jour pour <strong>" . htmlspecialchars($email) . "</strong></p>";
            echo "<p>Nouveau hash: <code>" . $newHash . "</code></p>";
            // Vérification immédiate
            $verify = password_verify($newPass, $newHash);
            echo "<p class='ok'>✅ Vérification: " . ($verify ? 'hash valide' : '❌ ERREUR hash') . "</p>";
        } else {
            echo "<p class='ko'>❌ Aucun utilisateur trouvé avec cet email.</p>";
        }
    }

    // Afficher tous les utilisateurs avec leur statut de vérif
    echo "<h3>👥 Utilisateurs</h3><table border='1' cellpadding='6'>";
    echo "<tr><th>Email</th><th>Role</th><th>Statut</th><th>Hash type</th></tr>";
    $stmt = $pdo->query("SELECT email, role, statut, password FROM users ORDER BY id");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $hashInfo = '';
        if (strpos($row['password'], '$2y$') === 0 || strpos($row['password'], '$2a$') === 0) {
            $hashInfo = '<span style="color:green">bcrypt ✅</span>';
        } elseif (strlen($row['password']) === 32) {
            $hashInfo = '<span style="color:red">MD5 ❌</span>';
        } elseif (strlen($row['password']) === 40) {
            $hashInfo = '<span style="color:red">SHA1 ❌</span>';
        } else {
            $hashInfo = '<span style="color:orange">Inconnu (?)</span>';
        }
        echo "<tr><td>{$row['email']}</td><td>{$row['role']}</td><td>{$row['statut']}</td><td>{$hashInfo}</td></tr>";
    }
    echo "</table>";

} catch (PDOException $e) {
    echo "<p class='ko'>❌ Erreur: " . $e->getMessage() . "</p>";
}
?>
<hr>
<h3>🔑 Changer le mot de passe</h3>
<form method="POST">
    <p>Email: <input name="email" style="width:300px" required></p>
    <p>Nouveau mot de passe: <input name="new_password" style="width:200px" required placeholder="Ex: Afnen123"></p>
    <button type="submit" style="background:green;color:white;padding:8px 16px;border:none;cursor:pointer">✅ Mettre à jour</button>
</form>
