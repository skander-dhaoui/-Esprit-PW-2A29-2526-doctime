<?php
declare(strict_types=1);
require __DIR__ . '/config/database.php';

$fixes = [
    2 => [
        'titre'   => '5 gestes pour le cœur',
        'contenu' => '<p>Activité physique, alimentation équilibrée, arrêt tabac.</p>',
    ],
    4 => [
        'titre'   => 'Sommeil et récupération',
        'contenu' => '<p>Hygiène de sommeil pour adultes actifs.</p>',
    ],
    5 => [
        'contenu' => '<p>Conseils d\'achat en ligne sécurisé.</p>',
    ],
    6 => [
        'titre' => 'Événements santé à Tunis',
    ],
];

$pdo = Database::getInstance()->getConnection();
foreach ($fixes as $id => $cols) {
    $sets = [];
    $params = [];
    foreach ($cols as $k => $v) {
        $sets[] = "$k = ?";
        $params[] = $v;
    }
    $params[] = $id;
    $sql = 'UPDATE articles SET ' . implode(', ', $sets) . ' WHERE id = ?';
    $st = $pdo->prepare($sql);
    $st->execute($params);
    echo "Updated article $id\n";
}
