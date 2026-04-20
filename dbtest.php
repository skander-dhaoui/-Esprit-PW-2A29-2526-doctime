<?php
$pdo = new PDO('mysql:host=localhost;dbname=doctime_db', 'root', '');
print_r($pdo->query('DESCRIBE disponibilites')->fetchAll(PDO::FETCH_ASSOC));
print_r($pdo->query('SHOW CREATE TABLE disponibilites')->fetchAll(PDO::FETCH_ASSOC));
