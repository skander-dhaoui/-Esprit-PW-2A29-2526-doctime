<?php
require __DIR__ . '/config/database.php';
$pdo = Database::getInstance()->getConnection();
$stmt = $pdo->query('SELECT id, titre, specialite FROM evenement');
$events = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo json_encode($events, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
