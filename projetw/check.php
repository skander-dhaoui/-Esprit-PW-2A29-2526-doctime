<?php
require_once __DIR__ . '/config/database.php';
$db = Database::getInstance();
$r = $db->query('SELECT COUNT(*) as c FROM events');
$p = $db->query('SELECT COUNT(*) as c FROM participations');
print_r(['events' => $r[0]['c'], 'participations' => $p[0]['c']]);
