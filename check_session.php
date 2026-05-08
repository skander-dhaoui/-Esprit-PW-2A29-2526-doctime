<?php
// Check for 2FA verification code in the session or database
session_start();

echo "<h3>Session data:</h3>";
echo "<pre>";
print_r($_SESSION);
echo "</pre>";

// Try to find a backup 2FA code
require_once __DIR__ . '/config/database.php';
$pdo = Database::getInstance()->getConnection();

// Check if there's a table for 2FA codes
$tables = $pdo->query("SHOW TABLES")->fetchAll();
echo "<h3>Databases tables:</h3>";
foreach ($tables as $t) {
    echo implode($t) . "<br>";
}

// Try to find the 2FA code  
if (isset($_SESSION['user_id'])) {
    echo "<h3>User ID: " . $_SESSION['user_id'] . "</h3>";
    echo "Try to access the 2FA verification page with the code shown on the page.";
}
?>
