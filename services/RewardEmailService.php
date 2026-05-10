<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/mail.php';
require_once __DIR__ . '/../models/Gamification.php';
require_once __DIR__ . '/CertificateGenerator.php';

class RewardEmailService
{
    public function sendRewardEmail(
        string $toEmail,
        string $toName,
        array  $reward,
        int    $totalPoints,
        array  $levelInfo
    ): bool {
        $date    = date('d/m/Y à H:i');
        $subject = '🏆 Félicitations ' . $toName . ' ! Récompense : ' . $reward['title'];

        $body = CertificateGenerator::generateHtml(
            userName:          $toName,
            rewardTitle:       $reward['title'],
            rewardDescription: $reward['description'] ?? '',
            rewardIcon:        $reward['icon']        ?? '🏅',
            totalPoints:       $totalPoints,
            levelInfo:         $levelInfo,
            date:              $date
        );

        $alt  = "Félicitations {$toName} !\n\n";
        $alt .= "Récompense : {$reward['icon']} {$reward['title']}\n";
        $alt .= ($reward['description'] ?? '') . "\n\n";
        $alt .= "Score : {$totalPoints} pts — Niveau : {$levelInfo['badge']} {$levelInfo['name']}\n";
        $alt .= "Date  : {$date}";

        $sent = MailConfig::send($toEmail, $toName, $subject, $body, $alt);
        error_log($sent
            ? "RewardEmail: ✅ envoyé à {$toEmail} — {$reward['title']}"
            : "RewardEmail: ❌ échec à {$toEmail} — {$reward['title']}"
        );
        return $sent;
    }

    public function processPendingRewards(int $userId, string $email, string $name): int
    {
        $g       = new Gamification();
        $pending = $g->getPendingEmailRewards($userId);
        $total   = $g->getTotalPoints($userId);
        $level   = $g->getLevel($total);
        $sent    = 0;

        foreach ($pending as $reward) {
            if ($this->sendRewardEmail($email, $name, $reward, $total, $level)) {
                $g->markEmailSent($userId, (int)$reward['reward_key']);
                $sent++;
            }
            if (count($pending) > 1) usleep(400_000);
        }
        return $sent;
    }
}
?>