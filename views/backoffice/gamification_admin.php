<?php

// ╔══════════════════════════════════════════════════════════════╗
// ║  FICHIER 6 : views/backoffice/gamification_admin.php        ║
// ║  PLACER    : projetw/views/backoffice/gamification_admin.php ║
// ║  ACTION    : CRÉER CE FICHIER (nouveau)                      ║
// ║                                                              ║
// ║  UTILISATION dans votre dashboard admin :                    ║
// ║  require_once __DIR__ . '/gamification_admin.php';           ║
// ║  echo renderGamificationAdmin();                             ║
// ╚══════════════════════════════════════════════════════════════╝

function renderGamificationAdmin(): string
{
    try {
        require_once __DIR__ . '/../../models/Gamification.php';
        require_once __DIR__ . '/../../config/database.php';
        $g  = new Gamification();
        $db = Database::getInstance()->getConnection();

        $board         = $g->getLeaderboard(10);
        $totalPoints   = (int)$db->query("SELECT COALESCE(SUM(points),0) FROM user_points")->fetchColumn();
        $totalActions  = (int)$db->query("SELECT COUNT(*) FROM user_points")->fetchColumn();
        $totalArticles = (int)$db->query("SELECT COUNT(*) FROM user_points WHERE action_type='article_created'")->fetchColumn();
        $totalComments = (int)$db->query("SELECT COUNT(*) FROM user_points WHERE action_type='comment_created'")->fetchColumn();
        $totalRewarded = (int)$db->query("SELECT COUNT(DISTINCT user_id) FROM user_rewards")->fetchColumn();
        $totalRewards  = (int)$db->query("SELECT COUNT(*) FROM user_rewards")->fetchColumn();
        $emailsSent    = (int)$db->query("SELECT COUNT(*) FROM user_rewards WHERE email_sent=1")->fetchColumn();

        // 7 derniers jours
        $stmt      = $db->query("SELECT DATE(created_at) AS jour, SUM(points) AS pts FROM user_points WHERE created_at >= DATE_SUB(NOW(),INTERVAL 7 DAY) GROUP BY DATE(created_at) ORDER BY jour ASC");
        $dailyData = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $dailyMap  = [];
        foreach ($dailyData as $row) $dailyMap[$row['jour']] = (int)$row['pts'];

        // Répartition par action
        $stmt2     = $db->query("SELECT action_type, COUNT(*) AS cnt, SUM(points) AS pts FROM user_points GROUP BY action_type");
        $byAction  = $stmt2->fetchAll(PDO::FETCH_ASSOC);

    } catch (Exception $e) {
        return '<div class="alert alert-warning"><i class="fas fa-exclamation-triangle"></i> Gamification : ' . htmlspecialchars($e->getMessage()) . '</div>';
    }

    // ── Classement HTML ──────────────────────────────────────
    $boardHtml = '';
    $medals    = ['🥇','🥈','🥉'];
    $maxPts    = (int)(($board[0]['total_points'] ?? 1) ?: 1);
    foreach ($board as $i => $u) {
        $medal  = $medals[$i] ?? ('#'.($i+1));
        $color  = $u['level']['color']  ?? '#888';
        $badge  = $u['level']['badge']  ?? '⭐';
        $name   = htmlspecialchars($u['name']);
        $pts    = (int)$u['total_points'];
        $lvl    = htmlspecialchars($u['level']['name']);
        $pct    = $maxPts > 0 ? min(100,(int)(($pts/$maxPts)*100)) : 0;
        $boardHtml .= '
        <tr style="border-bottom:1px solid #f0f0f0;">
            <td style="padding:12px 14px;font-size:'.($i<3?'20':'13').'px;text-align:center;">'.$medal.'</td>
            <td style="padding:12px 14px;">
                <div style="display:flex;align-items:center;gap:10px;">
                    <div style="width:36px;height:36px;border-radius:50%;background:'.$color.'22;
                                border:2px solid '.$color.';display:flex;align-items:center;
                                justify-content:center;font-size:18px;flex-shrink:0;">'.$badge.'</div>
                    <div>
                        <div style="font-weight:600;font-size:14px;color:#1c1e21;">'.$name.'</div>
                        <div style="font-size:11px;color:#6c757d;">'.$lvl.'</div>
                    </div>
                </div>
            </td>
            <td style="padding:12px 14px;">
                <div style="display:flex;align-items:center;gap:10px;">
                    <div style="flex:1;height:6px;background:#f0f0f0;border-radius:50px;overflow:hidden;">
                        <div style="height:100%;width:'.$pct.'%;background:'.$color.';border-radius:50px;"></div>
                    </div>
                    <span style="font-weight:700;color:'.$color.';font-size:14px;min-width:55px;text-align:right;">'.$pts.' pts</span>
                </div>
            </td>
        </tr>';
    }

    // ── Graphique 7 jours ────────────────────────────────────
    $days   = [];
    for ($d=6;$d>=0;$d--) $days[] = date('Y-m-d',strtotime("-{$d} days"));
    $maxDay = max(array_values($dailyMap) ?: [1]);
    $graph  = '';
    foreach ($days as $day) {
        $v   = $dailyMap[$day] ?? 0;
        $h   = $maxDay>0 ? max(4,(int)(($v/$maxDay)*80)) : 4;
        $lbl = date('D',strtotime($day));
        $graph .= '
        <div style="display:flex;flex-direction:column;align-items:center;gap:5px;flex:1;">
            <div style="font-size:11px;font-weight:600;color:#2A7FAA;min-height:16px;">'.($v>0?$v:'').'</div>
            <div style="width:100%;height:80px;display:flex;align-items:flex-end;">
                <div style="width:100%;height:'.$h.'px;background:'.($v>0?'linear-gradient(180deg,#2A7FAA,#4CAF50)':'#f0f0f0').';border-radius:4px 4px 0 0;"></div>
            </div>
            <div style="font-size:11px;color:#6c757d;">'.$lbl.'</div>
        </div>';
    }

    // ── Répartition actions ──────────────────────────────────
    $actionMap = [
        'article_created' => ['label'=>'Articles publiés','icon'=>'📝','color'=>'#2A7FAA'],
        'comment_created' => ['label'=>'Commentaires',    'icon'=>'💬','color'=>'#4CAF50'],
    ];
    $actHtml = '';
    foreach ($byAction as $a) {
        $cfg      = $actionMap[$a['action_type']] ?? ['label'=>$a['action_type'],'icon'=>'⚡','color'=>'#888'];
        $actHtml .= '
        <div style="display:flex;align-items:center;gap:12px;padding:12px 16px;
                    background:#f8f9fa;border-radius:10px;margin-bottom:8px;">
            <div style="font-size:24px;">'.$cfg['icon'].'</div>
            <div style="flex:1;">
                <div style="font-weight:600;font-size:13px;color:#1c1e21;">'.$cfg['label'].'</div>
                <div style="font-size:11px;color:#6c757d;">'.(int)$a['cnt'].' actions</div>
            </div>
            <div style="font-weight:800;font-size:18px;color:'.$cfg['color'].'">'.(int)$a['pts'].' pts</div>
        </div>';
    }

    $stats = [
        ['val'=>$totalPoints,   'lbl'=>'Points distribués',       'color'=>'#2A7FAA'],
        ['val'=>$totalActions,  'lbl'=>'Actions totales',          'color'=>'#4CAF50'],
        ['val'=>$totalArticles, 'lbl'=>'📝 Articles',             'color'=>'#17a2b8'],
        ['val'=>$totalComments, 'lbl'=>'💬 Commentaires',         'color'=>'#6f42c1'],
        ['val'=>$totalRewarded, 'lbl'=>'Utilisateurs récompensés','color'=>'#f0a500'],
        ['val'=>$totalRewards,  'lbl'=>'Récompenses obtenues',    'color'=>'#dc3545'],
        ['val'=>$emailsSent,    'lbl'=>'✉️ Certificats envoyés',  'color'=>'#28a745'],
    ];
    $statsHtml = '';
    foreach ($stats as $s) {
        $statsHtml .= '
        <div style="background:white;border-radius:14px;padding:20px;box-shadow:0 2px 8px rgba(0,0,0,0.06);
                    border-left:4px solid '.$s['color'].';text-align:center;">
            <div style="font-size:30px;font-weight:900;color:'.$s['color'].'">'.$s['val'].'</div>
            <div style="font-size:12px;color:#6c757d;margin-top:4px;">'.$s['lbl'].'</div>
        </div>';
    }

    return '
<div style="font-family:\'Segoe UI\',sans-serif;">

    <!-- Titre -->
    <div style="display:flex;align-items:center;gap:12px;margin-bottom:24px;">
        <div style="width:44px;height:44px;border-radius:12px;
                    background:linear-gradient(135deg,#2A7FAA,#4CAF50);
                    display:flex;align-items:center;justify-content:center;font-size:22px;">🏆</div>
        <div>
            <h3 style="margin:0;font-size:18px;font-weight:700;color:#1c1e21;">Système de Gamification</h3>
            <div style="font-size:13px;color:#6c757d;">Points, niveaux et récompenses des utilisateurs</div>
        </div>
    </div>

    <!-- Stats globales -->
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(140px,1fr));gap:14px;margin-bottom:26px;">
        '.$statsHtml.'
    </div>

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:22px;">

        <!-- Graphique 7 jours -->
        <div style="background:white;border-radius:14px;padding:20px;box-shadow:0 2px 8px rgba(0,0,0,0.06);">
            <h5 style="margin:0 0 14px;font-size:14px;font-weight:700;color:#1c1e21;">
                📈 Points distribués — 7 derniers jours
            </h5>
            <div style="display:flex;align-items:flex-end;gap:8px;height:110px;">
                '.$graph.'
            </div>
        </div>

        <!-- Répartition par action -->
        <div style="background:white;border-radius:14px;padding:20px;box-shadow:0 2px 8px rgba(0,0,0,0.06);">
            <h5 style="margin:0 0 14px;font-size:14px;font-weight:700;color:#1c1e21;">
                ⚡ Répartition par type d\'action
            </h5>
            '.($actHtml ?: '<p style="color:#6c757d;font-size:13px;">Aucune action enregistrée.</p>').'
        </div>
    </div>

    <!-- Classement -->
    <div style="background:white;border-radius:14px;padding:20px;box-shadow:0 2px 8px rgba(0,0,0,0.06);">
        <h5 style="margin:0 0 16px;font-size:14px;font-weight:700;color:#1c1e21;">🏆 Classement des utilisateurs</h5>
        <div style="overflow-x:auto;">
            <table style="width:100%;border-collapse:collapse;">
                <thead>
                    <tr style="border-bottom:2px solid #f0f0f0;">
                        <th style="padding:10px 14px;text-align:left;font-size:11px;color:#6c757d;font-weight:600;text-transform:uppercase;letter-spacing:1px;width:50px;">#</th>
                        <th style="padding:10px 14px;text-align:left;font-size:11px;color:#6c757d;font-weight:600;text-transform:uppercase;letter-spacing:1px;">Utilisateur</th>
                        <th style="padding:10px 14px;text-align:left;font-size:11px;color:#6c757d;font-weight:600;text-transform:uppercase;letter-spacing:1px;">Score</th>
                    </tr>
                </thead>
                <tbody>
                    '.($boardHtml ?: '<tr><td colspan="3" style="padding:20px;text-align:center;color:#6c757d;font-size:13px;">Aucun utilisateur avec des points pour l\'instant.</td></tr>').'
                </tbody>
            </table>
        </div>
    </div>

</div>
';
}