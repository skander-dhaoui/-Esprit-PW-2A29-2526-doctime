<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';

class Gamification
{
    private PDO $db;

    public const LEVELS = [
        1 => ['name' => 'Débutant',     'min' => 0,   'badge' => '🌱', 'color' => '#6c757d'],
        2 => ['name' => 'Apprenti',     'min' => 10,  'badge' => '⭐', 'color' => '#17a2b8'],
        3 => ['name' => 'Contributeur', 'min' => 30,  'badge' => '🥉', 'color' => '#cd7f32'],
        4 => ['name' => 'Actif',        'min' => 60,  'badge' => '🥈', 'color' => '#c0c0c0'],
        5 => ['name' => 'Expert',       'min' => 100, 'badge' => '🥇', 'color' => '#ffd700'],
        6 => ['name' => 'Maître',       'min' => 150, 'badge' => '💎', 'color' => '#b9f2ff'],
        7 => ['name' => 'Légende',      'min' => 250, 'badge' => '🏆', 'color' => '#ff6b35'],
    ];

    public const REWARDS = [
        10  => ['title' => 'Premiers secours (الإسعافات الأولية)', 'description' => 'Formation: Apprenez les gestes de base des premiers secours', 'icon' => '🩹', 'formation_key' => 'premiers_secours'],
        20  => ['title' => 'Comment aider une personne blessée', 'description' => 'Formation: Techniques pour assister une personne blessée', 'icon' => '🫂', 'formation_key' => 'aider_blesse'],
        30  => ['title' => 'Arrêter un saignement', 'description' => 'Formation: Maîtriser les techniques pour stopper un saignement', 'icon' => '🩸', 'formation_key' => 'saignement'],
        40  => ['title' => 'Massage cardiaque (RCP)', 'description' => 'Formation: Apprendre la technique du massage cardiaque', 'icon' => '❤️', 'formation_key' => 'massage_cardiaque'],
        50  => ['title' => 'Réagir en cas d\'étouffement', 'description' => 'Formation: Manœuvre de Heimlich pour sauver une vie', 'icon' => '🫁', 'formation_key' => 'etouffement'],
        60  => ['title' => 'Formation secourisme complet', 'description' => 'Formation: Gestes de secourisme avancés', 'icon' => '🚑', 'formation_key' => 'secourisme'],
        70  => ['title' => 'Appeler les urgences', 'description' => 'Formation: Savoir contacter les services d\'urgence', 'icon' => '📞', 'formation_key' => 'appeler_urgences'],
        80  => ['title' => 'Protéger la victime', 'description' => 'Formation: Position latérale de sécurité (PLS)', 'icon' => '🛡️', 'formation_key' => 'proteger_victime'],
        90  => ['title' => 'Gestes de base en urgence', 'description' => 'Formation: Techniques fondamentales d\'urgence médicale', 'icon' => '⚡', 'formation_key' => 'gestes_urgence'],
        100 => ['title' => 'Formation hygiène et santé', 'description' => 'Formation: Règles d\'hygiène médicale', 'icon' => '🧼', 'formation_key' => 'hygiene_sante'],
        120 => ['title' => 'Lavage des mains médical', 'description' => 'Formation: Technique professionnelle de lavage des mains', 'icon' => '🧴', 'formation_key' => 'lavage_mains'],
        140 => ['title' => 'Prévention des maladies', 'description' => 'Formation: Bases de la prévention des maladies', 'icon' => '🛡️', 'formation_key' => 'prevention_maladies'],
        160 => ['title' => 'Désinfection des blessures', 'description' => 'Formation: Protocole de désinfection des plaies', 'icon' => '🧴', 'formation_key' => 'desinfection'],
        180 => ['title' => 'Formation soins de base avancés', 'description' => 'Formation: Soins médicaux de base', 'icon' => '🏥', 'formation_key' => 'soins_base'],
        200 => ['title' => 'Mesurer la tension artérielle', 'description' => 'Formation: Utiliser un tensiomètre', 'icon' => '💓', 'formation_key' => 'tension_arterielle'],
        250 => ['title' => 'Mesurer la glycémie', 'description' => 'Formation: Technique de mesure du taux de sucre', 'icon' => '🩸', 'formation_key' => 'glycemie'],
        300 => ['title' => 'Administration des médicaments', 'description' => 'Formation: Donner les médicaments correctement', 'icon' => '💊', 'formation_key' => 'medicaments'],
        350 => ['title' => 'Formation urgence domestique', 'description' => 'Formation: Gérer les accidents à la maison', 'icon' => '🏠', 'formation_key' => 'urgence_domestique'],
        400 => ['title' => 'Brûlures - Traitement', 'description' => 'Formation: Savoir traiter une brûlure', 'icon' => '🔥', 'formation_key' => 'brulures'],
        450 => ['title' => 'Gestion des chutes', 'description' => 'Formation: Protocole pour une personne tombée', 'icon' => '⚠️', 'formation_key' => 'chutes'],
        500 => ['title' => 'Intoxication - Symptômes et réaction', 'description' => 'Formation: Reconnaître une intoxication', 'icon' => '🧪', 'formation_key' => 'intoxication'],
        600 => ['title' => 'Malaise - Diagnostic rapide', 'description' => 'Formation: Identifier les causes d\'un malaise', 'icon' => '😰', 'formation_key' => 'malaise'],
        750 => ['title' => 'Formation SST (Sauveteur Secouriste)', 'description' => 'Certification reconnue en secourisme', 'icon' => '🏅', 'formation_key' => 'sst'],
        1000 => ['title' => 'Certificat de Formateur Secourisme', 'description' => 'Devenir formateur en gestes de premiers secours', 'icon' => '🎓', 'formation_key' => 'formateur'],
    ];

    public const POINTS = [
        'article_created' => 10,
        'comment_created' => 5,
    ];

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
        $this->createTablesIfNeeded();
    }

    public function createTablesIfNeeded(): void
    {
        $this->db->exec("
            CREATE TABLE IF NOT EXISTS user_points (
                id           INT AUTO_INCREMENT PRIMARY KEY,
                user_id      INT NOT NULL,
                points       INT NOT NULL DEFAULT 1,
                action_type  VARCHAR(50) NOT NULL,
                reference_id INT DEFAULT NULL,
                created_at   DATETIME NOT NULL DEFAULT NOW(),
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                INDEX idx_user_id (user_id),
                INDEX idx_action  (action_type)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");
        $this->db->exec("
            CREATE TABLE IF NOT EXISTS user_rewards (
                id           INT AUTO_INCREMENT PRIMARY KEY,
                user_id      INT NOT NULL,
                reward_key   INT NOT NULL,
                reward_title VARCHAR(100),
                awarded_at   DATETIME NOT NULL DEFAULT NOW(),
                email_sent   TINYINT(1) NOT NULL DEFAULT 0,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                UNIQUE KEY uniq_user_reward (user_id, reward_key)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");
    }

    public function addPoints(int $userId, string $actionType, ?int $referenceId = null): array
    {
        $points = self::POINTS[$actionType] ?? 1;
        
        $totalBefore = $this->getTotalPoints($userId);
        
        $stmt = $this->db->prepare("
            INSERT INTO user_points (user_id, points, action_type, reference_id, created_at)
            VALUES (?, ?, ?, ?, NOW())
        ");
        $stmt->execute([$userId, $points, $actionType, $referenceId]);

        $totalAfter = $this->getTotalPoints($userId);
        $newRewards = $this->checkAndGrantRewards($userId, $totalBefore, $totalAfter);

        return [
            'success' => true,
            'points_added' => $points,
            'total_points' => $totalAfter,
            'level'        => $this->getLevel($totalAfter),
            'new_rewards'  => $newRewards,
        ];
    }

    public function getTotalPoints(int $userId): int
    {
        $stmt = $this->db->prepare("SELECT COALESCE(SUM(points),0) FROM user_points WHERE user_id=?");
        $stmt->execute([$userId]);
        return (int) $stmt->fetchColumn();
    }

    public function getHistory(int $userId, int $limit = 20): array
    {
        $stmt = $this->db->prepare("
            SELECT *,
                CASE action_type
                    WHEN 'article_created' THEN '📝 Article publié'
                    WHEN 'comment_created' THEN '💬 Commentaire ajouté'
                    ELSE action_type
                END AS action_label
            FROM user_points
            WHERE user_id = ?
            ORDER BY created_at DESC
            LIMIT ?
        ");
        $stmt->bindValue(1, $userId, PDO::PARAM_INT);
        $stmt->bindValue(2, $limit,  PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getLevel(int $points): array
    {
        $current = self::LEVELS[1];
        $num     = 1;
        foreach (self::LEVELS as $n => $level) {
            if ($points >= $level['min']) { $current = $level; $num = $n; }
        }
        $next     = self::LEVELS[$num + 1] ?? null;
        $progress = 100;
        if ($next) {
            $range    = $next['min'] - $current['min'];
            $earned   = $points - $current['min'];
            $progress = $range > 0 ? min(100, (int)(($earned / $range) * 100)) : 100;
        }
        return [
            'number'     => $num,
            'name'       => $current['name'],
            'badge'      => $current['badge'],
            'color'      => $current['color'],
            'min_points' => $current['min'],
            'next_level' => $next,
            'progress'   => $progress,
        ];
    }

    public function checkAndGrantRewards(int $userId, int $before, int $after): array
    {
        $new = [];
        foreach (self::REWARDS as $threshold => $reward) {
            if ($before < $threshold && $after >= $threshold) {
                try {
                    $stmt = $this->db->prepare("
                        INSERT IGNORE INTO user_rewards (user_id, reward_key, reward_title, awarded_at, email_sent)
                        VALUES (?, ?, ?, NOW(), 0)
                    ");
                    $stmt->execute([$userId, $threshold, $reward['title']]);
                    if ($this->db->lastInsertId() > 0) {
                        $new[] = array_merge($reward, ['threshold' => $threshold, 'reward_key' => $threshold]);
                    }
                } catch (Exception $e) {
                    error_log('Gamification::checkAndGrantRewards - ' . $e->getMessage());
                }
            }
        }
        return $new;
    }

    public function getUserRewards(int $userId): array
    {
        $stmt = $this->db->prepare("SELECT * FROM user_rewards WHERE user_id=? ORDER BY reward_key ASC");
        $stmt->execute([$userId]);
        return array_map(function ($r) {
            $reward = self::REWARDS[$r['reward_key']] ?? ['title' => $r['reward_title'], 'description' => '', 'icon' => '🏅'];
            return array_merge($r, $reward);
        }, $stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    public function getUserStats(int $userId): array
    {
        $total = $this->getTotalPoints($userId);
        return [
            'total_points' => $total,
            'level'        => $this->getLevel($total),
            'rewards'      => $this->getUserRewards($userId),
            'next_reward'  => $this->getNextReward($total),
        ];
    }

    public function getNextReward(int $points): ?array
    {
        foreach (self::REWARDS as $threshold => $reward) {
            if ($points < $threshold) {
                return array_merge($reward, [
                    'threshold'     => $threshold,
                    'points_needed' => $threshold - $points,
                ]);
            }
        }
        return null;
    }

    public function getLeaderboard(int $limit = 10): array
    {
        $stmt = $this->db->prepare("
            SELECT u.id AS user_id, CONCAT(COALESCE(u.prenom,''),' ',COALESCE(u.nom,'')) AS name,
                   COALESCE(SUM(up.points),0) AS total_points
            FROM users u
            LEFT JOIN user_points up ON up.user_id = u.id
            GROUP BY u.id, u.prenom, u.nom
            ORDER BY total_points DESC
            LIMIT ?
        ");
        $stmt->bindValue(1, $limit, PDO::PARAM_INT);
        $stmt->execute();
        return array_map(function ($r) {
            return array_merge($r, ['level' => $this->getLevel((int)$r['total_points'])]);
        }, $stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    public function getPendingEmailRewards(int $userId): array
    {
        $stmt = $this->db->prepare("SELECT * FROM user_rewards WHERE user_id=? AND email_sent=0");
        $stmt->execute([$userId]);
        return array_map(function ($r) {
            return array_merge($r, self::REWARDS[$r['reward_key']] ?? []);
        }, $stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    public function markEmailSent(int $userId, int $rewardKey): void
    {
        $this->db->prepare("UPDATE user_rewards SET email_sent=1 WHERE user_id=? AND reward_key=?")
                 ->execute([$userId, $rewardKey]);
    }
}
?>