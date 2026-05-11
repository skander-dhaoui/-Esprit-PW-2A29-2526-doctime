<?php
/**
 * Smoke test CLI : modèle Gamification + API HTTP si Apache répond.
 * Usage : php tools/gamification_smoke_test.php
 */
declare(strict_types=1);

$root = dirname(__DIR__);
chdir($root);

require_once $root . '/config/database.php';
require_once $root . '/models/gamification.php';

echo "=== Gamification modèle (DB) ===\n";

try {
    $g  = new Gamification();
    $lb = $g->getLeaderboard(5);
    echo 'leaderboard: ' . count($lb) . " ligne(s)\n";
    foreach ($lb as $i => $row) {
        echo sprintf(
            "  #%d user_id=%s pts=%s niveau=%s\n",
            $i + 1,
            (string) ($row['user_id'] ?? ''),
            (string) ($row['total_points'] ?? ''),
            (string) (($row['level']['name'] ?? '') ?: '')
        );
    }

    $stmt = Database::getInstance()->getConnection()->query('SELECT id FROM users ORDER BY id ASC LIMIT 1');
    $uid  = (int) $stmt->fetchColumn();
    if ($uid > 0) {
        $stats = $g->getUserStats($uid);
        echo "\nuser_id={$uid} total_points={$stats['total_points']} niveau={$stats['level']['name']}\n";
        echo 'récompenses enregistrées: ' . count($stats['rewards']) . "\n";
    } else {
        echo "Aucun utilisateur en base — skip getUserStats.\n";
    }

    echo "\n=== Test requête HTTP API (leaderboard) ===\n";
    $url = 'http://localhost/valorys_Copie/index.php?page=api_gamification&action=leaderboard';
    $ctx = stream_context_create(['http' => ['timeout' => 3, 'ignore_errors' => true]]);
    $raw = @file_get_contents($url, false, $ctx);
    if ($raw === false) {
        echo "ÉCHEC: impossible de joindre $url (Apache éteint ou autre chemin).\n";
        exit(0);
    }
    $j = json_decode($raw, true);
    if (!is_array($j) || empty($j['success'])) {
        echo "Réponse inattendue: " . substr($raw, 0, 500) . "\n";
        exit(1);
    }
    echo "OK api_gamification leaderboard: " . count($j['leaderboard'] ?? []) . " entrées JSON.\n";
} catch (Throwable $e) {
    fwrite(STDERR, 'ERREUR: ' . $e->getMessage() . "\n");
    exit(1);
}

echo "\nTous les tests smoke ont réussi.\n";
