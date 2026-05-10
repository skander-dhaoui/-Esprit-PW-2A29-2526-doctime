<?php
declare(strict_types=1);

require_once __DIR__ . '/../models/Gamification.php';
require_once __DIR__ . '/../services/RewardEmailService.php';

class GamificationController
{
    public static function grantPoints(int $userId, string $action, ?int $refId = null): array
    {
        try {
            $g      = new Gamification();
            $result = $g->addPoints($userId, $action, $refId);

            if (!empty($result['new_rewards'])) {
                try {
                    $db   = Database::getInstance()->getConnection();
                    $stmt = $db->prepare("SELECT email, CONCAT(COALESCE(prenom,''),' ',COALESCE(nom,'')) AS name FROM users WHERE id=?");
                    $stmt->execute([$userId]);
                    $user = $stmt->fetch(PDO::FETCH_ASSOC);
                    if ($user && !empty($user['email'])) {
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
            return ['success' => false, 'points_added' => 0, 'total_points' => 0, 'level' => [], 'new_rewards' => []];
        }
    }

    public function stats(): void
    {
        header('Content-Type: application/json');
        $userId = (int)($_GET['user_id'] ?? $_SESSION['user_id'] ?? 0);
        if (!$userId) { echo json_encode(['success'=>false,'message'=>'Non identifié']); return; }
        $g = new Gamification();
        echo json_encode(['success'=>true,'stats'=>$g->getUserStats($userId)]);
    }

    public function leaderboard(): void
    {
        header('Content-Type: application/json');
        $g = new Gamification();
        echo json_encode(['success'=>true,'leaderboard'=>$g->getLeaderboard(10)]);
    }

    public function history(): void
    {
        header('Content-Type: application/json');
        $userId = (int)($_GET['user_id'] ?? $_SESSION['user_id'] ?? 0);
        $g      = new Gamification();
        echo json_encode(['success'=>true,'history'=>$g->getHistory($userId)]);
    }
}
?>