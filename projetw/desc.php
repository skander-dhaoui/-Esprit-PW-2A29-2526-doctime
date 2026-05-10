<?php
require_once __DIR__ . '/config/database.php';
$db = Database::getInstance();
$r = $db->query('DESCRIBE events');
print_r($r);
