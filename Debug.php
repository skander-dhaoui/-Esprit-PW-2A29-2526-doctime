<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Debug Session</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 30px; background: #f5f5f5; }
        .box { background: white; padding: 20px; border-radius: 8px; margin-bottom: 20px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        h2 { color: #333; border-bottom: 2px solid #4CAF50; padding-bottom: 10px; }
        pre { background: #f0f0f0; padding: 15px; border-radius: 5px; overflow-x: auto; }
        .btn { display: inline-block; padding: 10px 20px; margin: 5px; border-radius: 5px; text-decoration: none; color: white; font-weight: bold; }
        .btn-green  { background: #4CAF50; }
        .btn-red    { background: #f44336; }
        .btn-blue   { background: #2196F3; }
        .btn-orange { background: #FF9800; }
        .ok   { color: #4CAF50; font-weight: bold; }
        .fail { color: #f44336; font-weight: bold; }
    </style>
</head>
<body>

<div class="box">
    <h2>🔍 État de la session</h2>
    <?php if (empty($_SESSION['user_id'])): ?>
        <p class="fail">❌ Pas connecté (session vide)</p>
    <?php else: ?>
        <p class="ok">✅ Connecté !</p>
        <p><strong>user_id :</strong> <?= htmlspecialchars($_SESSION['user_id']) ?></p>
        <p><strong>user_role :</strong> <?= htmlspecialchars($_SESSION['user_role'] ?? 'non défini') ?></p>
        <p><strong>user_name :</strong> <?= htmlspecialchars($_SESSION['user_name'] ?? 'non défini') ?></p>
    <?php endif; ?>
    <pre><?php print_r($_SESSION); ?></pre>
</div>

<div class="box">
    <h2>🗄️ Test connexion base de données</h2>
    <?php
    try {
        require_once __DIR__ . '/../config/database.php';
        $db = Database::getInstance();
        $result = $db->query("SELECT id, nom, prenom, email, role, statut FROM users LIMIT 5");
        echo '<p class="ok">✅ Connexion BDD réussie</p>';
        echo '<p>Utilisateurs trouvés : <strong>' . count($result) . '</strong></p>';
        if (!empty($result)) {
            echo '<table border="1" cellpadding="8" style="border-collapse:collapse;width:100%">';
            echo '<tr style="background:#4CAF50;color:white"><th>ID</th><th>Nom</th><th>Prénom</th><th>Email</th><th>Rôle</th><th>Statut</th></tr>';
            foreach ($result as $u) {
                echo '<tr>';
                echo '<td>' . $u['id'] . '</td>';
                echo '<td>' . htmlspecialchars($u['nom']) . '</td>';
                echo '<td>' . htmlspecialchars($u['prenom']) . '</td>';
                echo '<td>' . htmlspecialchars($u['email']) . '</td>';
                echo '<td>' . htmlspecialchars($u['role']) . '</td>';
                echo '<td>' . htmlspecialchars($u['statut']) . '</td>';
                echo '</tr>';
            }
            echo '</table>';
        }
    } catch (Exception $e) {
        echo '<p class="fail">❌ Erreur BDD : ' . htmlspecialchars($e->getMessage()) . '</p>';
    }
    ?>
</div>

<div class="box">
    <h2>📁 Fichiers requis</h2>
    <?php
    $files = [
        'config/Database.php',
        'models/User.php',
        'models/Medecin.php',
        'models/Patient.php',
        'controllers/AuthController.php',
        'controllers/AdminController.php',
        'controllers/FrontController.php',
        'views/backoffice/dashboard.html',
        'views/backoffice/medecins_list.html',
        'views/backoffice/logs.html',
        'views/backoffice/stats.html',
    ];
    foreach ($files as $f) {
        $path = __DIR__ . '/' . $f;
        if (file_exists($path)) {
            echo '<p class="ok">✅ ' . $f . '</p>';
        } else {
            echo '<p class="fail">❌ MANQUANT : ' . $f . '</p>';
        }
    }
    ?>
</div>

<div class="box">
    <h2>🔐 Connexion forcée admin (pour test)</h2>
    <?php
    if (isset($_GET['force_login'])) {
        try {
            require_once __DIR__ . '/../config/database.php';
            $db = Database::getInstance();
            $admin = $db->queryOne(
                "SELECT * FROM users WHERE role = 'admin' AND statut = 'actif' LIMIT 1"
            );
            if ($admin) {
                $_SESSION['user_id']    = $admin['id'];
                $_SESSION['user_role']  = $admin['role'];
                $_SESSION['user_name']  = $admin['nom'] . ' ' . $admin['prenom'];
                $_SESSION['user_email'] = $admin['email'];
                echo '<p class="ok">✅ Session admin créée pour : ' . htmlspecialchars($admin['email']) . '</p>';
                echo '<p><a class="btn btn-blue" href="index.php?page=dashboard">→ Aller au Dashboard</a></p>';
            } else {
                echo '<p class="fail">❌ Aucun admin actif trouvé en BDD</p>';
                // Essai avec statut en_attente
                $admin2 = $db->queryOne("SELECT * FROM users WHERE role = 'admin' LIMIT 1");
                if ($admin2) {
                    echo '<p>Admin trouvé mais statut = <strong>' . htmlspecialchars($admin2['statut']) . '</strong></p>';
                    echo '<p>Correction du statut...</p>';
                    $db->execute("UPDATE users SET statut = 'actif' WHERE role = 'admin'");
                    $_SESSION['user_id']    = $admin2['id'];
                    $_SESSION['user_role']  = $admin2['role'];
                    $_SESSION['user_name']  = $admin2['nom'] . ' ' . $admin2['prenom'];
                    $_SESSION['user_email'] = $admin2['email'];
                    echo '<p class="ok">✅ Statut corrigé et session créée !</p>';
                    echo '<p><a class="btn btn-blue" href="index.php?page=dashboard">→ Aller au Dashboard</a></p>';
                }
            }
        } catch (Exception $e) {
            echo '<p class="fail">❌ Erreur : ' . htmlspecialchars($e->getMessage()) . '</p>';
        }
    }
    ?>
    <a class="btn btn-green" href="debug.php?force_login=1">🔑 Forcer connexion admin</a>
    <a class="btn btn-red" href="debug.php?clear=1">🗑️ Vider la session</a>
    <a class="btn btn-blue" href="index.php?page=login">→ Page Login normale</a>
    <a class="btn btn-orange" href="index.php?page=dashboard">→ Dashboard</a>
</div>

<?php
if (isset($_GET['clear'])) {
    session_destroy();
    echo '<script>window.location.href="debug.php";</script>';
}
?>

</body>
</html>
