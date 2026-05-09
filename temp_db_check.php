<?php
$pdo = new PDO('mysql:host=localhost;dbname=doctime_db;charset=utf8mb4', 'root', '');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$tables = ['categories', 'metiers', 'users', 'articles', 'replies'];
foreach ($tables as $table) {
    $count = $pdo->query("SELECT COUNT(*) FROM $table")->fetchColumn();
    echo "$table:$count\n";
}
