<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/models/Disponibilite.php';

$model = new Disponibilite();
$res = $model->create([
    'medecin_id' => 9, // Assuming user 9 is a medecin or user at all
    'jour_semaine' => 'Lundi',
    'heure_debut' => '08:00',
    'heure_fin' => '12:00',
    'actif' => 1
]);
var_dump($res);
