<?php
declare(strict_types=1);

class CertificateGenerator
{
    public static function generateHtml(
        string $userName,
        string $rewardTitle,
        string $rewardDescription,
        string $rewardIcon,
        int    $totalPoints,
        array  $levelInfo,
        string $date
    ): string {
        $badge     = htmlspecialchars($levelInfo['badge'] ?? '⭐');
        $levelName = htmlspecialchars($levelInfo['name']  ?? 'Actif');
        $color     = htmlspecialchars($levelInfo['color'] ?? '#ffd700');
        $certId    = 'CERT-' . strtoupper(substr(md5($userName . $date . $totalPoints), 0, 8));

        return '<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>Certificat - ' . htmlspecialchars($rewardTitle) . '</title>
<style>
*{margin:0;padding:0;box-sizing:border-box}
body{font-family:\'Segoe UI\',Arial,sans-serif;background:#0a0a14;color:#fff;padding:20px}
.wrap{max-width:680px;margin:0 auto}
.header{background:linear-gradient(135deg,#12122a,#1a1a3e,#0d2a4a);border-radius:16px 16px 0 0;padding:36px 30px 28px;text-align:center;border-bottom:3px solid ' . $color . '}
.brand{font-size:12px;color:#888;letter-spacing:4px;text-transform:uppercase;margin-bottom:14px}
.cert{background:linear-gradient(145deg,#111128,#14142e);border:2px solid ' . $color . ';border-radius:12px;margin:20px 0;padding:36px 32px;position:relative;overflow:hidden}
.cert::before{content:\'\';position:absolute;top:-50px;right:-50px;width:180px;height:180px;background:radial-gradient(circle,' . $color . '18,transparent 70%);border-radius:50%}
.cert::after{content:\'\';position:absolute;bottom:-50px;left:-50px;width:180px;height:180px;background:radial-gradient(circle,' . $color . '18,transparent 70%);border-radius:50%}
.corner{position:absolute;width:18px;height:18px;border-color:' . $color . ';border-style:solid;opacity:.5}
.tl{top:10px;left:10px;border-width:2px 0 0 2px}.tr{top:10px;right:10px;border-width:2px 2px 0 0}
.bl{bottom:10px;left:10px;border-width:0 0 2px 2px}.br{bottom:10px;right:10px;border-width:0 2px 2px 0}
.cert-top{text-align:center;border-bottom:1px solid ' . $color . '44;padding-bottom:22px;margin-bottom:22px}
.cert-label{font-size:11px;letter-spacing:4px;text-transform:uppercase;color:' . $color . ';margin-bottom:8px}
.cert-title{font-size:20px;font-weight:700}
.icon{font-size:68px;text-align:center;margin:18px 0;line-height:1}
.presented{font-size:11px;letter-spacing:3px;text-transform:uppercase;color:#666;text-align:center;margin-bottom:6px}
.username{font-size:30px;font-weight:700;text-align:center;background:linear-gradient(135deg,' . $color . ',#fff);-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text;margin-bottom:14px}
.desc{font-size:14px;color:#bbb;text-align:center;line-height:1.6;max-width:400px;margin:0 auto 22px}
.stats{display:flex;justify-content:center;gap:28px;background:rgba(255,255,255,0.04);border-radius:12px;padding:18px;margin:20px 0}
.stat .val{font-size:26px;font-weight:700;color:' . $color . ';text-align:center}
.stat .lbl{font-size:10px;color:#666;text-transform:uppercase;letter-spacing:2px;text-align:center;margin-top:3px}
.level-badge{display:table;margin:10px auto;background:' . $color . '18;border:1px solid ' . $color . '55;border-radius:50px;padding:8px 20px}
.cert-foot{border-top:1px solid ' . $color . '44;padding-top:18px;margin-top:20px;display:flex;justify-content:space-between;flex-wrap:wrap;gap:10px}
.cert-foot .date{font-size:11px;color:#555}
.cert-foot .cid{font-size:10px;color:#333;font-family:monospace}
.footer{background:#0d0d0d;border-radius:0 0 16px 16px;padding:22px 28px;text-align:center;border-top:1px solid #1a1a1a}
.footer p{font-size:12px;color:#444;line-height:1.8}
</style>
</head>
<body>
<div class="wrap">
<div class="header">
  <div class="brand">🏆 DocTime — Système de Récompenses</div>
  <h1 style="font-size:24px;font-weight:700;margin-bottom:6px">Félicitations !</h1>
  <p style="font-size:14px;color:#999">Vous venez de débloquer une nouvelle récompense</p>
</div>
<div class="cert">
  <div class="corner tl"></div><div class="corner tr"></div>
  <div class="corner bl"></div><div class="corner br"></div>
  <div class="cert-top">
    <div class="cert-label">✦ Certificat d\'Excellence ✦</div>
    <div class="cert-title">' . htmlspecialchars($rewardTitle) . '</div>
  </div>
  <div class="icon">' . htmlspecialchars($rewardIcon) . '</div>
  <div class="presented">Décerné à</div>
  <div class="username">' . htmlspecialchars($userName) . '</div>
  <div class="desc">' . htmlspecialchars($rewardDescription) . ' Votre engagement et votre participation active font de vous un membre exemplaire de notre communauté.</div>
  <div class="stats">
    <div class="stat"><div class="val">' . $totalPoints . '</div><div class="lbl">Points</div></div>
    <div class="stat"><div class="val">' . $badge . '</div><div class="lbl">Badge</div></div>
    <div class="stat"><div class="val">' . $levelName . '</div><div class="lbl">Niveau</div></div>
  </div>
  <div class="level-badge" style="display:flex;align-items:center;gap:8px;">
    <span style="font-size:22px">' . $badge . '</span>
    <span style="font-size:14px;font-weight:600;color:' . $color . '">Niveau ' . $levelName . '</span>
  </div>
  <div class="cert-foot">
    <span class="date">📅 Délivré le ' . htmlspecialchars($date) . '</span>
    <span class="cid">' . $certId . '</span>
  </div>
</div>
<div class="footer">
  <p>Continuez à publier des articles et des commentaires pour gagner encore plus de points !<br>
  Cet email a été généré automatiquement. Merci pour votre participation active.</p>
</div>
</div>
</body>
</html>';
    }
}
?>