<?php
declare(strict_types=1);

// ╔══════════════════════════════════════════════════════════════╗
// ║  FICHIER 4 : controllers/GamificationController.php         ║
// ║  PLACER    : projetw/controllers/GamificationController.php  ║
// ║  ACTION    : CRÉER CE FICHIER (nouveau)                      ║
// ╚══════════════════════════════════════════════════════════════╝

require_once __DIR__ . '/../models/Gamification.php';
require_once __DIR__ . '/../services/RewardEmailService.php';

class GamificationController
{
    // ── Méthode statique appelée depuis FrontController ──────────
    // Usage : GamificationController::grantPoints($userId, 'article_created', $id)
    // Usage : GamificationController::grantPoints($userId, 'comment_created', $id)
    public static function grantPoints(int $userId, string $action, ?int $refId = null): array
    {
        try {
            $g      = new Gamification();
            $result = $g->addPoints($userId, $action, $refId);

            // Envoyer emails si nouvelles récompenses débloquées
            if (!empty($result['new_rewards'])) {
                try {
                    $db   = Database::getInstance()->getConnection();
                    $stmt = $db->prepare("SELECT email, CONCAT(prenom,' ',nom) AS name FROM users WHERE id=?");
                    $stmt->execute([$userId]);
                    $user = $stmt->fetch(PDO::FETCH_ASSOC);
                    if ($user) {
                        $svc = new RewardEmailService();
                        $svc->processPendingRewards($userId, $user['email'], $user['name']);
                    }
                } catch (Exception $e) {
                    error_log('GamificationController email: ' . $e->getMessage());
                }
            }
            return $result;
        } catch (Exception $e) {
            error_log('GamificationController::grantPoints - ' . $e->getMessage());
            return ['points_added' => 0, 'total_points' => 0, 'level' => [], 'new_rewards' => []];
        }
    }

    /** Données pour le front (toast / JSON) à partir du retour de grantPoints. */
    public static function formatGrantForClient(array $gRes): array
    {
        return [
            'points_added' => (int) ($gRes['points_added'] ?? 0),
            'total_points' => (int) ($gRes['total_points'] ?? 0),
            'level'        => $gRes['level'] ?? null,
            'new_rewards'  => array_values(array_map(static function (array $rw): array {
                return [
                    'title'       => (string) ($rw['title'] ?? ''),
                    'icon'        => (string) ($rw['icon'] ?? '🏅'),
                    'description' => (string) ($rw['description'] ?? ''),
                ];
            }, $gRes['new_rewards'] ?? [])),
        ];
    }

    // ── API JSON : GET index.php?page=api_gamification&action=stats&user_id=X
    public function stats(): void
    {
        header('Content-Type: application/json');
        $userId = (int)($_GET['user_id'] ?? $_SESSION['user_id'] ?? 0);
        if (!$userId) { echo json_encode(['success'=>false,'message'=>'Non identifié']); return; }
        $g = new Gamification();
        echo json_encode(['success'=>true,'stats'=>$g->getUserStats($userId)]);
    }

    // ── API JSON : GET index.php?page=api_gamification&action=leaderboard
    public function leaderboard(): void
    {
        header('Content-Type: application/json');
        $g = new Gamification();
        echo json_encode(['success'=>true,'leaderboard'=>$g->getLeaderboard(10)]);
    }

    // ── API JSON : GET index.php?page=api_gamification&action=history&user_id=X
    public function history(): void
    {
        header('Content-Type: application/json');
        $userId = (int)($_GET['user_id'] ?? $_SESSION['user_id'] ?? 0);
        $g      = new Gamification();
        echo json_encode(['success'=>true,'history'=>$g->getHistory($userId)]);
    }
}