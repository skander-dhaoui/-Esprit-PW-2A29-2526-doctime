<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($article['titre'] ?? 'Article') ?> - Valorys</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background: #f5f7fb; font-family: 'Segoe UI', sans-serif; }
        .article-header {
            background: linear-gradient(135deg, #2A7FAA 0%, #4CAF50 100%);
            color: white;
            padding: 60px 0;
            margin-bottom: 40px;
        }
        .article-content {
            background: white;
            border-radius: 15px;
            padding: 35px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            margin-bottom: 30px;
        }
        .article-title { font-size: 2rem; font-weight: 700; margin-bottom: 20px; }
        .article-meta {
            font-size: 14px;
            color: #6c757d;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 1px solid #eee;
        }
        .article-body { line-height: 1.8; color: #333; }
        .comments-section {
            background: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
        }
        .comment-item {
            border-bottom: 1px solid #eee;
            padding: 15px 0;
        }
        .comment-avatar {
            width: 45px;
            height: 45px;
            border-radius: 50%;
            background: #2A7FAA;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
        }
        .btn-back { background: #6c757d; color: white; border-radius: 25px; padding: 8px 20px; }
        .btn-back:hover { background: #5a6268; color: white; }
        footer {
            background: #1a2035;
            color: white;
            text-align: center;
            padding: 30px;
            margin-top: 50px;
        }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container">
        <a class="navbar-brand" href="index.php?page=accueil"><i class="fas fa-stethoscope"></i> Valorys</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item"><a class="nav-link" href="index.php?page=accueil">Accueil</a></li>
                <li class="nav-item"><a class="nav-link" href="index.php?page=medecins">Médecins</a></li>
                <li class="nav-item"><a class="nav-link active" href="index.php?page=blog_public">Blog</a></li>
                <li class="nav-item"><a class="nav-link" href="index.php?page=contact">Contact</a></li>
                <?php if (isset($_SESSION['user_id'])): ?>
                    <li class="nav-item"><a class="nav-link" href="index.php?page=dashboard">Dashboard</a></li>
                    <li class="nav-item"><a class="nav-link" href="index.php?page=logout">Déconnexion</a></li>
                <?php else: ?>
                    <li class="nav-item"><a class="nav-link" href="index.php?page=login">Connexion</a></li>
                    <li class="nav-item"><a class="nav-link" href="index.php?page=register">Inscription</a></li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>

<div class="article-header">
    <div class="container">
        <a href="index.php?page=blog_public" class="btn-back mb-3 d-inline-block">
            <i class="fas fa-arrow-left me-2"></i> Retour au blog
        </a>
        <h1 class="article-title"><?= htmlspecialchars($article['titre'] ?? 'Article') ?></h1>
        <div class="article-meta">
            <span><i class="fas fa-user me-1"></i> <?= htmlspecialchars($article['auteur'] ?? 'Valorys') ?></span>
            <span class="ms-3"><i class="fas fa-calendar me-1"></i> <?= date('d/m/Y', strtotime($article['date_creation'] ?? 'now')) ?></span>
            <span class="ms-3"><i class="fas fa-comment me-1"></i> <?= count($replies ?? []) ?> commentaire(s)</span>
        </div>
    </div>
</div>

<div class="container mb-5">
    <div class="row">
        <div class="col-lg-8 mx-auto">
            <div class="article-content">
                <div class="article-body">
                    <?= nl2br(htmlspecialchars($article['contenu'] ?? 'Contenu non disponible')) ?>
                </div>
            </div>

            <div class="comments-section">
                <h4><i class="fas fa-comments me-2"></i> Commentaires (<?= count($replies ?? []) ?>)</h4>
                <?php if (empty($replies)): ?>
                    <p class="text-muted text-center py-4">Aucun commentaire pour le moment. Soyez le premier à réagir !</p>
                <?php else: ?>
                    <?php foreach ($replies as $reply): ?>
                    <div class="comment-item d-flex gap-3">
                        <div class="comment-avatar flex-shrink-0">
                            <?= strtoupper(substr($reply['auteur'] ?? 'A', 0, 1)) ?>
                        </div>
                        <div>
                            <div class="fw-bold"><?= htmlspecialchars($reply['auteur'] ?? 'Anonyme') ?></div>
                            <small class="text-muted"><?= date('d/m/Y H:i', strtotime($reply['date_reply'] ?? 'now')) ?></small>
                            <div class="mt-2">
                                <?php if ($reply['type_reply'] === 'emoji'): ?>
                                    <span style="font-size: 2rem;"><?= htmlspecialchars($reply['emoji']) ?></span>
                                <?php elseif ($reply['type_reply'] === 'photo'): ?>
                                    <img src="<?= htmlspecialchars($reply['photo']) ?>" style="max-width: 100%; border-radius: 10px;">
                                <?php else: ?>
                                    <p class="mb-0"><?= nl2br(htmlspecialchars($reply['contenu_text'] ?? '')) ?></p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<footer>
    <div class="container">
        <p>&copy; 2024 Valorys - Tous droits réservés</p>
        <small>Plateforme médicale en ligne</small>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>// update
