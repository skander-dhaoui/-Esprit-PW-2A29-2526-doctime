<?php
/** Usage: php tools/repair_event_status.php — répare ENUM status events (UTF-8) */
declare(strict_types=1);
require __DIR__ . '/../config/database.php';
$pdo = Database::getInstance()->getConnection();
$pdo->exec('ALTER TABLE events MODIFY COLUMN status VARCHAR(32) NOT NULL');
$avenir = 'à venir';
$term   = 'terminé';
$annul  = 'annulé';
$run = static function (PDO $pdo, string $sql, string $val): void {
    $st = $pdo->prepare($sql);
    $st->execute([$val]);
};
$run($pdo, "UPDATE events SET status = ? WHERE status IN ('termin?', 'terminé')", $term);
$run($pdo, "UPDATE events SET status = ? WHERE status IN ('annul?', 'annulé')", $annul);
$run($pdo, "UPDATE events SET status = ? WHERE status IN ('? venir', '?', '') OR status IS NULL OR TRIM(COALESCE(status, '')) = ''", $avenir);
$run($pdo, "UPDATE events SET status = ? WHERE status NOT IN ('à venir','en_cours','terminé','annulé')", $avenir);
$pdo->exec("ALTER TABLE events MODIFY COLUMN status ENUM('à venir','en_cours','terminé','annulé') NOT NULL DEFAULT 'à venir'");
$n = (int) $pdo->query("SELECT COUNT(*) FROM events WHERE date_debut >= NOW() AND status = 'à venir'")->fetchColumn();
echo "Événements à venir (liste publique): {$n}\n";
