<?php
// dashboard.php — NE PAS MODIFIER LA STRUCTURE ORIGINALE
// On ajoute seulement les notifications
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: index.php?page=login');
    exit;
}

require_once __DIR__ . '/../../config/database.php';
$db = Database::getInstance()->getConnection();

// Marquer notifications lues
if (isset($_GET['mark_read']) && is_numeric($_GET['mark_read'])) {
    try { $db->prepare("UPDATE admin_notifications SET is_read=1 WHERE id=?")->execute([(int)$_GET['mark_read']]); } catch(Exception $e){}
    header('Location: index.php?page=dashboard'); exit;
}
if (isset($_GET['mark_all_read'])) {
    try { $db->exec("UPDATE admin_notifications SET is_read=1"); } catch(Exception $e){}
    header('Location: index.php?page=dashboard'); exit;
}

// Stats
$totalUsers = 0; $totalMedecins = 0; $totalPatients = 0; $enValidation = 0;
try {
    $totalUsers    = $db->query("SELECT COUNT(*) FROM users")->fetchColumn();
    $totalMedecins = $db->query("SELECT COUNT(*) FROM users WHERE role='medecin'")->fetchColumn();
    $totalPatients = $db->query("SELECT COUNT(*) FROM users WHERE role='patient'")->fetchColumn();
    $enValidation  = $db->query("SELECT COUNT(*) FROM users WHERE statut='en_attente'")->fetchColumn();
} catch(Exception $e) {}

$stats = [
    'total_users'    => $totalUsers,
    'total_medecins' => $totalMedecins,
    'total_patients' => $totalPatients,
    'en_validation'  => $enValidation,
];

// Utilisateurs récents
$users = [];
try {
    $stmt = $db->prepare("SELECT id,nom,prenom,email,role,statut,created_at FROM users ORDER BY created_at DESC LIMIT 5");
    $stmt->execute();
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(Exception $e) {}

// Notifications
$notifications = [];
$notifCount = 0;
try {
    $notifications = $db->query("SELECT * FROM admin_notifications ORDER BY created_at DESC LIMIT 10")->fetchAll(PDO::FETCH_ASSOC);
    $notifCount    = (int)$db->query("SELECT COUNT(*) FROM admin_notifications WHERE is_read=0")->fetchColumn();
} catch(Exception $e) {}

// Articles récents
$lastArticles = [];
try {
    $lastArticles = $db->query("
        SELECT a.id,a.titre,a.moderation_status,a.created_at,CONCAT(u.prenom,' ',u.nom) AS auteur
        FROM articles a LEFT JOIN users u ON u.id=a.auteur_id
        ORDER BY a.created_at DESC LIMIT 5
    ")->fetchAll(PDO::FETCH_ASSOC);
} catch(Exception $e) {}

// Commentaires récents
$lastReplies = [];
try {
    $lastReplies = $db->query("
        SELECT r.id,r.replay,r.moderation_status,r.created_at,
               CONCAT(u.prenom,' ',u.nom) AS auteur, a.titre AS article_titre
        FROM replies r
        LEFT JOIN users u ON u.id=r.user_id
        LEFT JOIN articles a ON a.id=r.article_id
        ORDER BY r.created_at DESC LIMIT 5
    ")->fetchAll(PDO::FETCH_ASSOC);
} catch(Exception $e) {}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Dashboard - DOCtime</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <nav class="navbar navbar-dark bg-dark">
        <div class="container-fluid">
            <a class="navbar-brand" href="#"><i class="fas fa-hospital-user me-2"></i>DOCtime</a>
            <div class="d-flex align-items-center gap-3">

                <!-- CLOCHE NOTIFICATIONS -->
                <div class="dropdown">
                    <button class="btn btn-outline-light btn-sm position-relative" data-bs-toggle="dropdown">
                        <i class="fas fa-bell"></i>
                        <?php if ($notifCount > 0): ?>
                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="font-size:10px;"><?= $notifCount ?></span>
                        <?php endif; ?>
                    </button>
                    <div class="dropdown-menu dropdown-menu-end p-0" style="width:350px;max-height:400px;overflow-y:auto;">
                        <div class="p-3 border-bottom d-flex justify-content-between align-items-center">
                            <strong>Notifications <?= $notifCount > 0 ? "($notifCount)" : "" ?></strong>
                            <?php if($notifCount > 0): ?>
                            <a href="index.php?page=dashboard&mark_all_read=1" class="text-decoration-none small text-primary">Tout lire</a>
                            <?php endif; ?>
                        </div>
                        <?php if(empty($notifications)): ?>
                        <div class="p-4 text-center text-muted"><i class="fas fa-bell-slash fa-2x mb-2 d-block opacity-25"></i>Aucune notification</div>
                        <?php else: ?>
                        <?php foreach($notifications as $n):
                            $icons = ['new_article'=>'📝','new_reply'=>'💬','moderation_rejected'=>'🚫','new_user'=>'👤'];
                            $icon  = $icons[$n['type']] ?? '🔔';
                        ?>
                        <div class="p-3 border-bottom <?= !$n['is_read'] ? 'bg-light border-start border-primary border-3' : '' ?>">
                            <div class="d-flex justify-content-between">
                                <strong style="font-size:13px;"><?= $icon ?> <?= htmlspecialchars($n['title']) ?></strong>
                                <small class="text-muted"><?= date('d/m H:i', strtotime($n['created_at'])) ?></small>
                            </div>
                            <div class="text-muted small"><?= htmlspecialchars(substr($n['message'], 0, 80)) ?>...</div>
                            <div class="mt-1">
                                <?php if(!$n['is_read']): ?>
                                <a href="index.php?page=dashboard&mark_read=<?= $n['id'] ?>" class="small text-primary text-decoration-none me-2">Marquer lu</a>
                                <?php endif; ?>
                                <?php if($n['reference_type']==='article' && $n['reference_id']): ?>
                                <a href="index.php?page=articles_admin&action=view&id=<?= $n['reference_id'] ?>" class="small text-primary text-decoration-none">Voir l'article →</a>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <span class="text-white">Bienvenue, <?= htmlspecialchars($_SESSION['user_name'] ?? 'Admin') ?> (<?= $_SESSION['user_role'] ?>)</span>
                <a href="index.php?page=logout" class="btn btn-danger btn-sm">Déconnexion</a>
            </div>
        </div>
    </nav>

    <div class="container mt-4">

        <!-- STATS -->
        <div class="row g-3 mb-4">
            <div class="col-md-3">
                <div class="card text-center p-3 border-top border-primary border-3">
                    <div class="fs-1 fw-bold text-primary"><?= $stats['total_users'] ?></div>
                    <div class="text-muted"><i class="fas fa-users"></i> Utilisateurs</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-center p-3 border-top border-success border-3">
                    <div class="fs-1 fw-bold text-success"><?= $stats['total_medecins'] ?></div>
                    <div class="text-muted"><i class="fas fa-user-md"></i> Médecins</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-center p-3 border-top border-info border-3">
                    <div class="fs-1 fw-bold text-info"><?= $stats['total_patients'] ?></div>
                    <div class="text-muted"><i class="fas fa-user-injured"></i> Patients</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-center p-3 border-top border-warning border-3">
                    <div class="fs-1 fw-bold text-warning"><?= $stats['en_validation'] ?></div>
                    <div class="text-muted"><i class="fas fa-clock"></i> En validation</div>
                </div>
            </div>
        </div>

        <!-- LIENS RAPIDES -->
        <div class="mb-4 d-flex gap-2 flex-wrap">
            <a href="index.php?page=users" class="btn btn-outline-primary btn-sm"><i class="fas fa-users"></i> Utilisateurs</a>
            <a href="index.php?page=medecins_admin" class="btn btn-outline-success btn-sm"><i class="fas fa-user-md"></i> Médecins</a>
            <a href="index.php?page=patients" class="btn btn-outline-info btn-sm"><i class="fas fa-user-injured"></i> Patients</a>
            <a href="index.php?page=rendez_vous_admin" class="btn btn-outline-warning btn-sm"><i class="fas fa-calendar"></i> Rendez-vous</a>
            <a href="index.php?page=articles_admin" class="btn btn-outline-danger btn-sm"><i class="fas fa-newspaper"></i> Articles</a>
            <a href="index.php?page=evenements_admin" class="btn btn-outline-secondary btn-sm"><i class="fas fa-calendar-alt"></i> Événements</a>
            <a href="index.php?page=metiers" class="btn btn-outline-dark btn-sm"><i class="fas fa-briefcase-medical"></i> Métiers</a>
            <a href="index.php?page=blog_public" class="btn btn-dark btn-sm"><i class="fas fa-eye"></i> Voir le site</a>
        </div>

        <div class="row g-3">

            <!-- NOTIFICATIONS PANEL -->
            <div class="col-md-4">
                <div class="card h-100">
                    <div class="card-header bg-warning text-dark d-flex justify-content-between align-items-center">
                        <h6 class="mb-0"><i class="fas fa-bell"></i> Notifications
                            <?php if($notifCount>0): ?>
                            <span class="badge bg-danger"><?= $notifCount ?></span>
                            <?php endif; ?>
                        </h6>
                        <?php if($notifCount>0): ?>
                        <a href="index.php?page=dashboard&mark_all_read=1" class="btn btn-sm btn-dark">Tout lire</a>
                        <?php endif; ?>
                    </div>
                    <div class="card-body p-0" style="max-height:400px;overflow-y:auto;">
                        <?php if(empty($notifications)): ?>
                        <div class="p-4 text-center text-muted"><i class="fas fa-bell-slash fa-2x mb-2 d-block opacity-25"></i>Aucune notification</div>
                        <?php else: ?>
                        <?php foreach($notifications as $n):
                            $icons = ['new_article'=>['emoji'=>'📝','class'=>'text-success'],'new_reply'=>['emoji'=>'💬','class'=>'text-primary'],'moderation_rejected'=>['emoji'=>'🚫','class'=>'text-danger'],'new_user'=>['emoji'=>'👤','class'=>'text-warning']];
                            $ic = $icons[$n['type']] ?? ['emoji'=>'🔔','class'=>'text-secondary'];
                        ?>
                        <div class="p-3 border-bottom <?= !$n['is_read'] ? 'bg-light' : '' ?>">
                            <div class="d-flex gap-2">
                                <span style="font-size:18px;"><?= $ic['emoji'] ?></span>
                                <div style="flex:1;min-width:0;">
                                    <div style="font-weight:<?= !$n['is_read']?'700':'500' ?>;font-size:13px;"><?= htmlspecialchars($n['title']) ?></div>
                                    <div class="text-muted" style="font-size:12px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"><?= htmlspecialchars(substr($n['message'],0,70)) ?></div>
                                    <div style="font-size:11px;color:#999;"><?= date('d/m/Y H:i',strtotime($n['created_at'])) ?>
                                        <?php if(!$n['is_read']): ?>
                                        · <a href="index.php?page=dashboard&mark_read=<?= $n['id'] ?>" class="text-primary text-decoration-none">Lu</a>
                                        <?php endif; ?>
                                        <?php if($n['reference_type']==='article' && $n['reference_id']): ?>
                                        · <a href="index.php?page=articles_admin&action=view&id=<?= $n['reference_id'] ?>" class="text-primary text-decoration-none">Voir</a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- DERNIERS ARTICLES -->
            <div class="col-md-4">
                <div class="card mb-3">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h6 class="mb-0"><i class="fas fa-newspaper text-success"></i> Derniers articles</h6>
                        <a href="index.php?page=articles_admin" class="btn btn-sm btn-outline-success">Voir tout</a>
                    </div>
                    <div class="card-body p-0">
                        <?php if(empty($lastArticles)): ?>
                        <p class="p-3 text-muted mb-0">Aucun article.</p>
                        <?php else: ?>
                        <?php foreach($lastArticles as $art):
                            $ms = $art['moderation_status'] ?? 'approved';
                            $badges = ['approved'=>'<span class="badge bg-success">✅ Approuvé</span>','rejected'=>'<span class="badge bg-danger">🚫 Rejeté</span>','pending'=>'<span class="badge bg-warning text-dark">⏳ En attente</span>'];
                            $badge = $badges[$ms] ?? $badges['approved'];
                        ?>
                        <div class="p-3 border-bottom">
                            <div class="d-flex justify-content-between align-items-start">
                                <div style="flex:1;min-width:0;">
                                    <div style="font-size:13px;font-weight:600;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"><?= htmlspecialchars($art['titre']) ?></div>
                                    <small class="text-muted"><?= htmlspecialchars($art['auteur']??'') ?> · <?= date('d/m/Y',strtotime($art['created_at'])) ?></small>
                                </div>
                                <div class="ms-2 d-flex gap-1 align-items-center flex-shrink-0">
                                    <?= $badge ?>
                                    <a href="index.php?page=articles_admin&action=view&id=<?= $art['id'] ?>" class="btn btn-xs btn-outline-primary btn-sm" style="font-size:11px;padding:1px 6px;">Voir</a>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- DERNIERS COMMENTAIRES -->
                <div class="card">
                    <div class="card-header">
                        <h6 class="mb-0"><i class="fas fa-comments text-primary"></i> Derniers commentaires</h6>
                    </div>
                    <div class="card-body p-0">
                        <?php if(empty($lastReplies)): ?>
                        <p class="p-3 text-muted mb-0">Aucun commentaire.</p>
                        <?php else: ?>
                        <?php foreach($lastReplies as $r):
                            $ms = $r['moderation_status'] ?? 'approved';
                            $emojis = ['approved'=>'✅','rejected'=>'🚫','pending'=>'⏳'];
                        ?>
                        <div class="p-3 border-bottom">
                            <div class="d-flex justify-content-between">
                                <div style="flex:1;min-width:0;">
                                    <div style="font-size:12px;"><strong><?= htmlspecialchars($r['auteur']??'') ?></strong> sur <em><?= htmlspecialchars(substr($r['article_titre']??'',0,25)) ?>...</em></div>
                                    <div class="text-muted" style="font-size:12px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">"<?= htmlspecialchars(substr($r['replay']??'',0,50)) ?>"</div>
                                    <small class="text-muted"><?= date('d/m/Y H:i',strtotime($r['created_at'])) ?></small>
                                </div>
                                <span style="font-size:16px;margin-left:8px;"><?= $emojis[$ms]??'✅' ?></span>
                            </div>
                        </div>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- UTILISATEURS RÉCENTS + INFO -->
            <div class="col-md-4">
                <div class="card mb-3">
                    <div class="card-header bg-success text-white d-flex justify-content-between">
                        <h6 class="mb-0">Tableau de bord</h6>
                        <span class="badge bg-light text-dark"><?= date('d/m/Y H:i') ?></span>
                    </div>
                    <div class="card-body">
                        <p>Connecté en tant que <strong><?= htmlspecialchars($_SESSION['user_name']??'Admin') ?></strong></p>
                        <p>Rôle : <span class="badge bg-danger"><?= $_SESSION['user_role'] ?></span></p>
                        <p>ID : <?= $_SESSION['user_id'] ?></p>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header d-flex justify-content-between">
                        <h6 class="mb-0"><i class="fas fa-users text-primary"></i> Utilisateurs récents</h6>
                        <a href="index.php?page=users" class="btn btn-sm btn-outline-primary">Voir tout</a>
                    </div>
                    <div class="card-body p-0">
                        <?php foreach($users as $u): ?>
                        <div class="p-3 border-bottom">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <div style="font-size:13px;font-weight:600;"><?= htmlspecialchars($u['prenom'].' '.$u['nom']) ?></div>
                                    <small class="text-muted"><?= htmlspecialchars($u['email']) ?></small>
                                </div>
                                <span class="badge bg-<?= $u['role']==='admin'?'danger':($u['role']==='medecin'?'success':'info') ?>"><?= $u['role'] ?></span>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>