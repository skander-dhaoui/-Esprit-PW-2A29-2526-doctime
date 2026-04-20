<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/models/Event.php';
$em = new Event();
print_r($em->getTopEventsByParticipants(5));
print_r($em->getRevenueEvents());
print_r($em->getSpecialtyDistribution());
