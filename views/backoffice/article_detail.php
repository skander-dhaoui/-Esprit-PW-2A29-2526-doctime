<?php
// views/backoffice/article_detail.php
// Charger le widget de traduction
if (!function_exists('renderTranslationWidget')) {
    $twPath = __DIR__ . '/../translation_widget.php';
    if (file_exists($twPath)) require_once $twPath;
}
$isAdmin = ($_SESSION['user_role'] ?? '') === 'admin';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($article['titre'] ?? 'Article') ?> — Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background:#f0f2f5; font-family:'Segoe UI',sans-serif; }
        .sidebar { background:#2c3e50; min-height:100vh; color:white; }
        .sidebar .nav-link { color:rgba(255,255,255,0.8); padding:12px 20px; border-radius:8px; margin:4px 10px; }
        .sidebar .nav-link:hover, .sidebar .nav-link.active { background:#2A7FAA; color:white; }
        .sidebar .nav-link i { margin-right:10px; width:20px; }
        .sidebar .navbar-brand { padding:20px 15px; font-size:1.3rem; font-weight:bold; border-bottom:1px solid rgba(255,255,255,0.1); margin-bottom:15px; }
        .main-content { padding:25px; }
        .card { border:none; border-radius:12px; box-shadow:0 2px 8px rgba(0,0,0,0.08); }
        .badge-publie { background:#d4edda; color:#155724; padding:5px 12px; border-radius:20px; font-size:12px; }
        .badge-brouillon { background:#fff3cd; color:#856404; padding:5px 12px; border-radius:20px; font-size:12px; }
        .comment-card { background:#f8f9fa; border-radius:10px; padding:15px; margin-bottom:12px; border-left:3px solid #2A7FAA; }
        .comment-avatar { width:38px; height:38px; border-radius:50%; background:linear-gradient(135deg,#2A7FAA,#4CAF50); display:flex; align-items:center; justify-content:center; color:white; font-weight:bold; font-size:16px; flex-shrink:0; }
    </style>
</head>
<body>
<div class="container-fluid">
    <div class="row">
        <!-- SIDEBAR -->
        <div class="col-md-2 px-0 sidebar">
            <div class="navbar-brand text-center"><i class="fas fa-hospital-user me-2"></i>Admin</div>
            <nav class="nav flex-column">
                <a class="nav-link" href="index.php?page=dashboard"><i class="fas fa-tachometer-alt"></i>Dashboard</a>
                <a class="nav-link" href="index.php?page=users"><i class="fas fa-users"></i>Utilisateurs</a>
                <a class="nav-link" href="index.php?page=medecins_admin"><i class="fas fa-user-md"></i>Médecins</a>
                <a class="nav-link" href="index.php?page=rendez_vous_admin"><i class="fas fa-calendar-check"></i>Rendez-vous</a>
                <a class="nav-link active" href="index.php?page=articles_admin"><i class="fas fa-newspaper"></i>Articles</a>
                <a class="nav-link" href="index.php?page=evenements_admin"><i class="fas fa-calendar-alt"></i>Événements</a>
                <hr style="border-color:rgba(255,255,255,0.1);margin:10px;">
                <a class="nav-link" href="index.php?page=blog_public"><i class="fas fa-eye"></i>Voir le site</a>
                <a class="nav-link text-danger" href="index.php?page=logout"><i class="fas fa-sign-out-alt"></i>Déconnexion</a>
            </nav>
        </div>

        <!-- CONTENU PRINCIPAL -->
        <div class="col-md-10 main-content">
            <!-- FLASH MESSAGE -->
            <?php if (!empty($_SESSION['flash'])): $flash = $_SESSION['flash']; unset($_SESSION['flash']); ?>
            <div class="alert alert-<?= $flash['type'] === 'success' ? 'success' : 'danger' ?> alert-dismissible fade show">
                <?= htmlspecialchars($flash['message']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php endif; ?>

            <!-- HEADER -->
            <div style="background:white;border-radius:12px;padding:20px;margin-bottom:20px;display:flex;justify-content:space-between;align-items:center;">
                <div>
                    <a href="index.php?page=articles_admin" style="color:#6c757d;text-decoration:none;font-size:14px;">
                        <i class="fas fa-arrow-left me-2"></i>Retour aux articles
                    </a>
                    <h4 style="margin:8px 0 0;color:#2c3e50;"><?= htmlspecialchars($article['titre'] ?? '') ?></h4>
                </div>
                <div style="display:flex;gap:8px;">
                    <a href="index.php?page=articles_admin&action=edit&id=<?= $article['id'] ?>" class="btn btn-warning btn-sm">
                        <i class="fas fa-edit"></i> Modifier
                    </a>
                    <a href="index.php?page=detail_article_public&id=<?= $article['id'] ?>" target="_blank" class="btn btn-info btn-sm text-white">
                        <i class="fas fa-eye"></i> Vue public
                    </a>
                    <a href="index.php?page=articles_admin&action=delete&id=<?= $article['id'] ?>"
                       onclick="return confirm('Supprimer cet article ?')" class="btn btn-danger btn-sm">
                        <i class="fas fa-trash"></i> Supprimer
                    </a>
                </div>
            </div>

            <div class="row">
                <!-- ARTICLE -->
                <div class="col-md-8">
                    <div class="card p-4 mb-4">
                        <!-- META -->
                        <div style="display:flex;gap:15px;align-items:center;flex-wrap:wrap;margin-bottom:15px;padding-bottom:15px;border-bottom:1px solid #f0f0f0;">
                            <div style="width:42px;height:42px;border-radius:50%;background:linear-gradient(135deg,#2A7FAA,#4CAF50);display:flex;align-items:center;justify-content:center;color:white;font-weight:bold;">
                                <?= strtoupper(substr($article['prenom'] ?? 'A', 0, 1)) ?>
                            </div>
                            <div>
                                <div style="font-weight:600;"><?= htmlspecialchars(($article['prenom'] ?? '') . ' ' . ($article['nom'] ?? '')) ?></div>
                                <div style="font-size:12px;color:#999;"><?= date('d/m/Y H:i', strtotime($article['created_at'] ?? 'now')) ?></div>
                            </div>
                            <span class="badge-<?= $article['status'] ?? 'brouillon' ?>">
                                <?= ucfirst($article['status'] ?? 'brouillon') ?>
                            </span>
                            <span style="margin-left:auto;font-size:13px;color:#999;">
                                <i class="fas fa-eye"></i> <?= (int)($article['vues'] ?? 0) ?> vues
                                &nbsp;·&nbsp;
                                <i class="fas fa-comment"></i> <?= (int)($commentCount ?? 0) ?> commentaire(s)
                            </span>
                        </div>

                        <!-- IMAGE -->
                        <?php if (!empty($article['image'])): ?>
                        <img src="<?= htmlspecialchars($article['image']) ?>" style="width:100%;max-height:350px;object-fit:cover;border-radius:8px;margin-bottom:20px;">
                        <?php endif; ?>

                        <!-- CONTENU -->
                        <div style="line-height:1.8;color:#333;font-size:15px;">
                            <?php
                            $contenu = $article['contenu'] ?? '';
                            $decoded = json_decode($contenu, true);
                            if (json_last_error() === JSON_ERROR_NONE && isset($decoded['ops'])) {
                                $html = '';
                                foreach ($decoded['ops'] as $op) {
                                    if (isset($op['insert']) && is_string($op['insert'])) {
                                        $text = htmlspecialchars($op['insert']);
                                        $attrs = $op['attributes'] ?? [];
                                        if (!empty($attrs['bold']))      $text = '<strong>' . $text . '</strong>';
                                        if (!empty($attrs['italic']))     $text = '<em>' . $text . '</em>';
                                        if (!empty($attrs['underline']))  $text = '<u>' . $text . '</u>';
                                        $text = str_replace("\n", '<br>', $text);
                                        $html .= $text;
                                    }
                                }
                                echo $html;
                            } else {
                                echo nl2br(htmlspecialchars($contenu));
                            }
                            ?>
                        </div>

                        <!-- WIDGET TRADUCTION ARTICLE -->
                        <?php if (function_exists('renderTranslationWidget')): ?>
                            <?= renderTranslationWidget('article', (int)$article['id'], 'fr', false) ?>
                        <?php endif; ?>
                    </div>

                    <!-- COMMENTAIRES -->
                    <div class="card p-4 mb-4">
                        <h5 style="margin-bottom:20px;padding-bottom:10px;border-bottom:2px solid #2A7FAA;">
                            <i class="fas fa-comments text-primary me-2"></i>
                            Commentaires (<?= count($comments ?? []) ?>)
                        </h5>

                        <?php if (empty($comments)): ?>
                        <p style="color:#999;text-align:center;padding:20px;">Aucun commentaire.</p>
                        <?php else: ?>
                        <?php foreach ($comments as $comment): ?>
                        <div class="comment-card">
                            <div style="display:flex;gap:10px;align-items:flex-start;">
                                <div class="comment-avatar">
                                    <?= strtoupper(substr($comment['user_name'] ?? 'A', 0, 1)) ?>
                                </div>
                                <div style="flex:1;">
                                    <div style="font-weight:600;font-size:14px;"><?= htmlspecialchars($comment['user_name'] ?? 'Anonyme') ?></div>
                                    <div style="font-size:11px;color:#999;margin-bottom:8px;"><?= date('d/m/Y H:i', strtotime($comment['created_at'] ?? 'now')) ?></div>
                                    <div style="color:#333;font-size:14px;line-height:1.6;">
                                        <?= nl2br(htmlspecialchars($comment['replay'] ?? '')) ?>
                                    </div>
                                    <!-- WIDGET TRADUCTION COMMENTAIRE -->
                                    <?php if (function_exists('renderTranslationWidget')): ?>
                                        <?= renderTranslationWidget('reply', (int)$comment['id'], 'fr', true) ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                        <?php endif; ?>

                        <!-- FORMULAIRE AJOUTER COMMENTAIRE -->
                        <div style="margin-top:20px;padding-top:20px;border-top:1px solid #f0f0f0;">
                            <h6 style="margin-bottom:12px;color:#2A7FAA;">Ajouter un commentaire</h6>
                            <form method="POST" action="index.php?page=articles_admin&action=add_comment&id=<?= $article['id'] ?>">
                                <textarea name="comment" rows="3" class="form-control mb-2"
                                    placeholder="Écrivez un commentaire..." required minlength="3"></textarea>
                                <button type="submit" class="btn btn-primary btn-sm">
                                    <i class="fas fa-paper-plane me-1"></i>Publier
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- SIDEBAR DROITE -->
                <div class="col-md-4">
                    <!-- STATS -->
                    <div class="card p-4 mb-4">
                        <h6 style="margin-bottom:15px;color:#2A7FAA;font-weight:700;">
                            <i class="fas fa-chart-bar me-2"></i>Statistiques
                        </h6>
                        <div style="display:flex;flex-direction:column;gap:10px;">
                            <div style="display:flex;justify-content:space-between;padding:8px 12px;background:#f8f9fa;border-radius:8px;">
                                <span style="color:#666;font-size:13px;"><i class="fas fa-eye me-2 text-info"></i>Vues</span>
                                <strong><?= (int)($article['vues'] ?? 0) ?></strong>
                            </div>
                            <div style="display:flex;justify-content:space-between;padding:8px 12px;background:#f8f9fa;border-radius:8px;">
                                <span style="color:#666;font-size:13px;"><i class="fas fa-comment me-2 text-primary"></i>Commentaires</span>
                                <strong><?= count($comments ?? []) ?></strong>
                            </div>
                            <?php
                            try {
                                $db = Database::getInstance()->getConnection();
                                $ls = $db->prepare("SELECT COUNT(*) FROM article_likes WHERE article_id=? AND type='like'");
                                $ls->execute([$article['id']]);
                                $likes = (int)$ls->fetchColumn();
                                $ds = $db->prepare("SELECT COUNT(*) FROM article_likes WHERE article_id=? AND type='dislike'");
                                $ds->execute([$article['id']]);
                                $dislikes = (int)$ds->fetchColumn();
                            } catch(Exception $e) { $likes = 0; $dislikes = 0; }
                            ?>
                            <div style="display:flex;justify-content:space-between;padding:8px 12px;background:#f8f9fa;border-radius:8px;">
                                <span style="color:#666;font-size:13px;">👍 J'aime</span>
                                <strong><?= $likes ?></strong>
                            </div>
                            <div style="display:flex;justify-content:space-between;padding:8px 12px;background:#f8f9fa;border-radius:8px;">
                                <span style="color:#666;font-size:13px;">👎 Je n'aime pas</span>
                                <strong><?= $dislikes ?></strong>
                            </div>
                        </div>
                    </div>

                    <!-- INFOS -->
                    <div class="card p-4">
                        <h6 style="margin-bottom:15px;color:#2A7FAA;font-weight:700;">
                            <i class="fas fa-info-circle me-2"></i>Informations
                        </h6>
                        <div style="font-size:13px;color:#666;display:flex;flex-direction:column;gap:8px;">
                            <div><strong>ID :</strong> #<?= $article['id'] ?></div>
                            <div><strong>Auteur :</strong> <?= htmlspecialchars(($article['prenom'] ?? '') . ' ' . ($article['nom'] ?? '')) ?></div>
                            <div><strong>Statut :</strong> <span class="badge-<?= $article['status'] ?? 'brouillon' ?>"><?= ucfirst($article['status'] ?? '') ?></span></div>
                            <div><strong>Créé le :</strong> <?= date('d/m/Y', strtotime($article['created_at'] ?? 'now')) ?></div>
                            <?php if (!empty($article['categorie'])): ?>
                            <div><strong>Catégorie :</strong> <?= htmlspecialchars($article['categorie']) ?></div>
                            <?php endif; ?>
                            <?php if (!empty($article['tags'])): ?>
                            <div><strong>Tags :</strong> <?= htmlspecialchars($article['tags']) ?></div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>