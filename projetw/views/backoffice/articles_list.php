<?php
// views/backoffice/articles_list.php
$current_page = 'articles_admin';

// Récupérer les commentaires récents et notifications
require_once __DIR__ . '/../../config/database.php';
$db = Database::getInstance()->getConnection();

// Notifications
$notifications = [];
$notifCount = 0;
try {
    $notifications = $db->query("SELECT * FROM admin_notifications ORDER BY created_at DESC LIMIT 10")->fetchAll(PDO::FETCH_ASSOC);
    $notifCount = (int)$db->query("SELECT COUNT(*) FROM admin_notifications WHERE is_read=0")->fetchColumn();
} catch(Exception $e) {}

// Commentaires récents avec jointure
$lastReplies = [];
try {
    $lastReplies = $db->query("
        SELECT r.id, r.replay, r.moderation_status, r.created_at,
               r.image AS photo,
               CONCAT(u.prenom,' ',u.nom) AS auteur,
               a.titre AS article_titre, a.id AS article_id
        FROM replies r
        LEFT JOIN users u ON u.id = r.user_id
        LEFT JOIN articles a ON a.id = r.article_id
        ORDER BY r.created_at DESC LIMIT 5
    ")->fetchAll(PDO::FETCH_ASSOC);
} catch(Exception $e) {}

// Nombre de commentaires par article
$replyCounts = [];
try {
    $stmt = $db->query("SELECT article_id, COUNT(*) as nb FROM replies GROUP BY article_id");
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $replyCounts[$row['article_id']] = $row['nb'];
    }
} catch(Exception $e) {}

// Likes/Dislikes par article
$articleLikes = [];
try {
    $stmt = $db->query("SELECT article_id, type, COUNT(*) as nb FROM article_likes GROUP BY article_id, type");
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $articleLikes[$row['article_id']][$row['type']] = $row['nb'];
    }
} catch(Exception $e) {}

// Marquer notification lue
if (isset($_GET['mark_read']) && is_numeric($_GET['mark_read'])) {
    try { $db->prepare("UPDATE admin_notifications SET is_read=1 WHERE id=?")->execute([(int)$_GET['mark_read']]); } catch(Exception $e){}
    header('Location: index.php?page=articles_admin'); exit;
}
if (isset($_GET['mark_all_read'])) {
    try { $db->exec("UPDATE admin_notifications SET is_read=1"); } catch(Exception $e){}
    header('Location: index.php?page=articles_admin'); exit;
}
?>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $page_title ?? 'Gestion des Articles' ?> - DocTime</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { background: #f0f2f5; font-family: 'Segoe UI', sans-serif; display: flex; min-height: 100vh; }
        .main-content { margin-left: 260px; flex: 1; padding: 25px; min-height: 100vh; }
        .page-header {
            background: white; border-radius: 12px; padding: 18px 25px; margin-bottom: 25px;
            display: flex; align-items: center; justify-content: space-between;
            box-shadow: 0 1px 6px rgba(0,0,0,0.06);
        }
        .page-header h4 { font-size: 18px; font-weight: 700; color: #1a2035; margin: 0; display: flex; align-items: center; gap: 10px; }
        .page-header h4 i { color: #4CAF50; }
        .admin-avatar {
            width: 40px; height: 40px; background: #4CAF50; border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            color: white; font-size: 16px; font-weight: bold; text-decoration: none;
        }
        .content-card { background: white; border-radius: 12px; padding: 25px; box-shadow: 0 1px 6px rgba(0,0,0,0.06); margin-bottom: 20px; }
        .card-title-row { display: flex; align-items: center; justify-content: space-between; margin-bottom: 20px; }
        .card-title-row h5 { font-size: 16px; font-weight: 600; color: #1a2035; margin: 0; }
        .flash-box { border-radius: 10px; padding: 12px 16px; margin-bottom: 20px; font-size: 14px; }
        .flash-success { background: #e8f5e9; color: #2e7d32; }
        .flash-error { background: #fdecea; color: #c62828; }
        .table thead th { background: #1a2035; color: white; font-weight: 600; font-size: 13px; padding: 12px 14px; border: none; }
        .table tbody td { vertical-align: middle; font-size: 14px; padding: 13px 14px; color: #333; }
        .table tbody tr:hover { background: #f8f9ff; }
        .badge-publie   { background: #e8f5e9; color: #2e7d32; padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: 600; }
        .badge-brouillon{ background: #fff8e1; color: #f57f17; padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: 600; }
        .badge-archive  { background: #e2e8f0; color: #475569; padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: 600; }
        .btn-action { width: 32px; height: 32px; border-radius: 8px; border: none; display: inline-flex; align-items: center; justify-content: center; font-size: 13px; cursor: pointer; transition: opacity 0.2s; text-decoration: none; margin: 0 2px; }
        .btn-action:hover { opacity: 0.8; }
        .btn-edit   { background: #e3f0ff; color: #1565c0; }
        .btn-delete { background: #fdecea; color: #c62828; }
        .btn-view   { background: #f3e5f5; color: #6a1b9a; }
        .article-card { background:white;border-radius:12px;padding:20px;margin-bottom:15px;box-shadow:0 1px 4px rgba(0,0,0,0.08);border:1px solid #f0f0f0;transition:transform 0.2s; }
        .article-card:hover { transform:translateY(-2px);box-shadow:0 4px 12px rgba(0,0,0,0.12); }
        .notif-item { padding: 10px 15px; border-bottom: 1px solid #f0f0f0; }
        .notif-item.unread { background: #e8f4fd; border-left: 3px solid #1877f2; }
        .reply-item { padding: 12px 15px; border-bottom: 1px solid #f0f0f0; }
        .reply-item:hover { background: #f8f9fa; }
        .img-thumb { width: 40px; height: 40px; object-fit: cover; border-radius: 6px; }
    </style>
</head>
<body>

<?php include __DIR__ . '/sidebar.php'; ?>

<div class="main-content">
    <div class="page-header">
        <h4><i class="fas fa-blog"></i> Gestion des Articles</h4>
        <div class="d-flex align-items-center gap-3">
            <!-- CLOCHE NOTIFICATIONS -->
            <div class="dropdown">
                <button class="btn btn-outline-secondary btn-sm position-relative" data-bs-toggle="dropdown">
                    <i class="fas fa-bell"></i>
                    <?php if ($notifCount > 0): ?>
                    <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="font-size:9px;"><?= $notifCount ?></span>
                    <?php endif; ?>
                </button>
                <div class="dropdown-menu dropdown-menu-end p-0" style="width:320px;max-height:350px;overflow-y:auto;">
                    <div class="p-2 border-bottom d-flex justify-content-between align-items-center">
                        <strong style="font-size:13px;">Notifications</strong>
                        <?php if ($notifCount > 0): ?>
                        <a href="index.php?page=articles_admin&mark_all_read=1" class="text-decoration-none small text-primary">Tout lire</a>
                        <?php endif; ?>
                    </div>
                    <?php if (empty($notifications)): ?>
                    <div class="p-3 text-center text-muted small">Aucune notification</div>
                    <?php else: ?>
                    <?php foreach ($notifications as $n):
                        $icons = ['new_article'=>'📝','new_reply'=>'💬','moderation_rejected'=>'🚫','new_user'=>'👤'];
                        $icon = $icons[$n['type']] ?? '🔔';
                    ?>
                    <div class="notif-item <?= !$n['is_read'] ? 'unread' : '' ?>">
                        <div class="d-flex justify-content-between">
                            <span style="font-size:13px;font-weight:<?= !$n['is_read']?'600':'400' ?>"><?= $icon ?> <?= htmlspecialchars($n['title']) ?></span>
                            <small class="text-muted"><?= date('d/m H:i', strtotime($n['created_at'])) ?></small>
                        </div>
                        <div class="text-muted" style="font-size:11px;"><?= htmlspecialchars(substr($n['message'],0,60)) ?>...</div>
                        <div>
                            <?php if (!$n['is_read']): ?>
                            <a href="index.php?page=articles_admin&mark_read=<?= $n['id'] ?>" class="text-primary text-decoration-none" style="font-size:11px;">Marquer lu</a>
                            <?php endif; ?>
                            <?php if ($n['reference_type']==='article' && $n['reference_id']): ?>
                            · <a href="index.php?page=articles_admin&action=view&id=<?= $n['reference_id'] ?>" class="text-primary text-decoration-none" style="font-size:11px;">Voir →</a>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
            <a href="index.php?page=mon_profil" class="admin-avatar" title="Mon profil">
                <?= strtoupper(substr($_SESSION['user_name'] ?? 'A', 0, 1)) ?>
            </a>
        </div>
    </div>

    <?php if (isset($_SESSION['flash'])): ?>
    <div class="flash-box flash-<?= $_SESSION['flash']['type'] === 'error' ? 'error' : 'success' ?>">
        <i class="fas fa-<?= $_SESSION['flash']['type'] === 'error' ? 'times-circle' : 'check-circle' ?> me-2"></i>
        <?= htmlspecialchars($_SESSION['flash']['message']) ?>
    </div>
    <?php unset($_SESSION['flash']); ?>
    <?php endif; ?>

    <!-- ARTICLES TABLE -->
    <div class="content-card">
        <div class="card-title-row">
            <h5><i class="fas fa-list"></i> Liste des articles (<?= count($articles ?? []) ?>)</h5>
            <div class="d-flex gap-2">
                <a href="index.php?page=articles_admin&action=advanced" class="btn btn-outline-primary btn-sm">
                    <i class="fas fa-chart-bar me-1"></i> Vue avancée
                </a>
                <a href="index.php?page=articles_admin&action=create" class="btn btn-success btn-sm">
                    <i class="fas fa-plus me-1"></i> Nouvel Article
                </a>
            </div>
        </div>

        <!-- RECHERCHE RAPIDE -->
        <div style="background:#f8f9fa;border-radius:10px;padding:15px;margin-bottom:20px;">
            <div style="display:flex;gap:10px;align-items:center;flex-wrap:wrap;">
                <div style="flex:1;min-width:200px;position:relative;">
                    <i class="fas fa-search" style="position:absolute;left:12px;top:50%;transform:translateY(-50%);color:#65676b;"></i>
                    <input type="text" id="searchInput" placeholder="Rechercher par titre, auteur..."
                        style="width:100%;padding:10px 10px 10px 38px;border:2px solid #e4e6eb;border-radius:25px;font-size:14px;outline:none;">
                </div>
                <select id="sortSelect" style="padding:10px 15px;border:2px solid #e4e6eb;border-radius:25px;font-size:14px;outline:none;">
                    <option value="desc">↓ Plus récent</option>
                    <option value="asc">↑ Plus ancien</option>
                </select>
                <select id="statusFilter" style="padding:10px 15px;border:2px solid #e4e6eb;border-radius:25px;font-size:14px;outline:none;">
                    <option value="">Tous les statuts</option>
                    <option value="publié">✅ Publié</option>
                    <option value="brouillon">📝 Brouillon</option>
                    <option value="archive">📦 Archivé</option>
                </select>
            </div>
        </div>

        <div id="articlesContainer">
        <?php if (!empty($articles)): ?>
            <?php foreach ($articles as $a):
                $status = $a['status'] ?? 'brouillon';
                $badgeClass = match($status) {
                    'publié'  => 'badge-publie',
                    'archive' => 'badge-archive',
                    default   => 'badge-brouillon',
                };
                $initial = strtoupper(substr(($a['prenom'] ?? 'V'), 0, 1));
                $nbComments = $replyCounts[$a['id']] ?? 0;
                $nbLikes    = $articleLikes[$a['id']]['like'] ?? 0;
                $nbDislikes = $articleLikes[$a['id']]['dislike'] ?? 0;
            ?>
            <div class="article-card" data-titre="<?= htmlspecialchars(strtolower($a['titre'] ?? '')) ?>"
                 data-auteur="<?= htmlspecialchars(strtolower(($a['prenom']??'').' '.($a['nom']??''))) ?>"
                 data-status="<?= htmlspecialchars($status) ?>"
                 data-date="<?= strtotime($a['created_at'] ?? 'now') ?>">
                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px;">
                    <div style="display:flex;align-items:center;gap:10px;">
                        <div style="width:42px;height:42px;border-radius:50%;background:linear-gradient(135deg,#2A7FAA,#4CAF50);display:flex;align-items:center;justify-content:center;color:white;font-weight:bold;font-size:18px;">
                            <?= $initial ?>
                        </div>
                        <div>
                            <div style="font-weight:700;font-size:15px;color:#1a2035;">
                                <?= htmlspecialchars(($a['prenom']??'').' '.($a['nom']??'')) ?>
                            </div>
                            <div style="font-size:12px;color:#65676b;">
                                <?= date('d/m/Y H:i', strtotime($a['created_at'] ?? 'now')) ?>
                            </div>
                        </div>
                    </div>
                    <div style="display:flex;gap:8px;align-items:center;">
                        <span class="<?= $badgeClass ?>"><?= ucfirst($status) ?></span>
                        <a href="index.php?page=articles_admin&action=view&id=<?= $a['id'] ?>" class="btn-action btn-view" title="Voir"><i class="fas fa-eye"></i></a>
                        <a href="index.php?page=admin_article_edit&id=<?= $a['id'] ?>" class="btn-action btn-edit" title="Modifier"><i class="fas fa-edit"></i></a>
                        <a href="index.php?page=articles_admin&action=delete&id=<?= $a['id'] ?>" class="btn-action btn-delete" title="Supprimer" onclick="return confirm('Supprimer cet article ?')"><i class="fas fa-trash"></i></a>
                    </div>
                </div>
                <div style="font-size:17px;font-weight:700;color:#1a2035;margin-bottom:6px;">
                    <?= htmlspecialchars($a['titre'] ?? '') ?>
                </div>
                <div style="display:flex;gap:15px;font-size:13px;color:#65676b;margin-top:10px;padding-top:10px;border-top:1px solid #f0f0f0;">
                    <span><i class="fas fa-eye text-info"></i> <?= (int)($a['vues']??0) ?> vues</span>
                    <a href="index.php?page=replies_admin&article_id=<?= $a['id'] ?>" style="text-decoration:none;color:#65676b;">
                        <span><i class="fas fa-comments text-primary"></i> <?= $nbComments ?> commentaire(s)</span>
                    </a>
                    <span id="art-likes-<?= $a['id'] ?>" style="color:#1877f2;font-weight:600;">👍 <?= $nbLikes ?></span>
                    <span id="art-dislikes-<?= $a['id'] ?>" style="color:#dc3545;font-weight:600;">👎 <?= $nbDislikes ?></span>
                </div>
                <div style="display:flex;gap:8px;margin-top:8px;">
                    <button onclick="likeArticleAdmin(<?= $a['id'] ?>,'like')" style="background:#e7f3ff;color:#1877f2;border:none;border-radius:20px;padding:5px 14px;font-size:13px;font-weight:600;cursor:pointer;">
                        👍 J'aime
                    </button>
                    <button onclick="likeArticleAdmin(<?= $a['id'] ?>,'dislike')" style="background:#fff0f0;color:#dc3545;border:none;border-radius:20px;padding:5px 14px;font-size:13px;font-weight:600;cursor:pointer;">
                        👎 Je n'aime pas
                    </button>
                </div>
            </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div style="text-align:center;padding:40px;color:#65676b;">
                <i class="fas fa-blog fa-3x mb-3 d-block" style="opacity:0.2;"></i>
                Aucun article trouvé
            </div>
        <?php endif; ?>
        </div>

        <div id="noResults" style="display:none;text-align:center;padding:30px;color:#65676b;">
            <i class="fas fa-search fa-2x mb-2 d-block" style="opacity:0.3;"></i>
            Aucun résultat trouvé
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
// RECHERCHE + TRI en temps réel
function filterArticles() {
    var q = document.getElementById('searchInput').value.toLowerCase();
    var sort = document.getElementById('sortSelect').value;
    var status = document.getElementById('statusFilter').value.toLowerCase();
    var container = document.getElementById('articlesContainer');
    var cards = Array.from(container.querySelectorAll('.article-card'));
    var visible = 0;

    // Filtrer
    cards.forEach(function(card) {
        var titre = card.getAttribute('data-titre') || '';
        var auteur = card.getAttribute('data-auteur') || '';
        var st = card.getAttribute('data-status') || '';
        var matchQ = !q || titre.includes(q) || auteur.includes(q);
        var matchS = !status || st === status;
        card.style.display = (matchQ && matchS) ? '' : 'none';
        if (matchQ && matchS) visible++;
    });

    // Trier
    var sorted = cards.filter(function(c){ return c.style.display !== 'none'; });
    sorted.sort(function(a, b) {
        var da = parseInt(a.getAttribute('data-date'));
        var db = parseInt(b.getAttribute('data-date'));
        return sort === 'asc' ? da - db : db - da;
    });
    sorted.forEach(function(c){ container.appendChild(c); });

    document.getElementById('noResults').style.display = visible === 0 ? 'block' : 'none';
}

document.getElementById('searchInput').addEventListener('input', filterArticles);
document.getElementById('sortSelect').addEventListener('change', filterArticles);
document.getElementById('statusFilter').addEventListener('change', filterArticles);

function likeArticleAdmin(articleId, type) {
    var xhr = new XMLHttpRequest();
    xhr.open('POST', 'index.php?page=api_article_like', true);
    xhr.setRequestHeader('Content-Type', 'application/json');
    xhr.onreadystatechange = function() {
        if (xhr.readyState === 4 && xhr.status === 200) {
            try {
                var d = JSON.parse(xhr.responseText);
                if (d.success) {
                    var lb = document.getElementById('art-likes-' + articleId);
                    var db = document.getElementById('art-dislikes-' + articleId);
                    if (lb) lb.textContent = '👍 ' + d.likes;
                    if (db) db.textContent = '👎 ' + d.dislikes;
                }
            } catch(e) {}
        }
    };
    xhr.send(JSON.stringify({article_id: parseInt(articleId), type: type}));
}
</script>
</body>
</html>