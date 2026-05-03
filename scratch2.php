<?php
require __DIR__ . '/config/database.php';

$sourceDir = 'C:\\Users\\Asus\\.gemini\\antigravity\\brain\\11e707bf-fa9d-4148-8001-813b5009b25c\\';
$targetDir = __DIR__ . '/public/uploads/events/';

if (!is_dir($targetDir)) {
    mkdir($targetDir, 0777, true);
}

$updates = [
    1 => 'cardio_event_1777325845995.png',
    2 => 'dermato_event_1777325857619.png',
    3 => 'onco_event_1777325883846.png',
    4 => 'october_rose_1777325894764.png',
    5 => 'urgence_event_1777325908485.png',
    6 => 'esthetic_event_1777325920667.png'
];

$pdo = Database::getInstance()->getConnection();

foreach ($updates as $id => $filename) {
    $sourceFile = $sourceDir . $filename;
    $targetFile = $targetDir . $filename;
    $dbPath = 'public/uploads/events/' . $filename;
    
    if (file_exists($sourceFile)) {
        copy($sourceFile, $targetFile);
        $stmt = $pdo->prepare('UPDATE evenement SET image = :image WHERE id = :id');
        $stmt->execute([':image' => $dbPath, ':id' => $id]);
        echo "Updated event $id with $filename\n";
    } else {
        echo "File not found: $sourceFile\n";
    }
}
echo "Done.\n";
