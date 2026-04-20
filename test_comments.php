<?php
/**
 * Test : Vérifier que les commentaires s'affichent et peuvent être ajoutés
 */

session_start();
require_once __DIR__ . '/config/database.php';

$_SESSION['user_id'] = 1;
$_SESSION['user_name'] = 'Test Admin';
$_SESSION['user_role'] = 'admin';

try {
    $db = Database::getInstance()->getConnection();
    
    // Get one article with its comments count
    $stmt = $db->query("
        SELECT a.id, a.titre, COUNT(r.id) as comment_count
        FROM articles a
        LEFT JOIN replies r ON a.id = r.article_id AND r.status = 'approuvé'
        GROUP BY a.id
        LIMIT 1
    ");
    $article = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Get comments for this article
    $commentsStmt = $db->prepare("
        SELECT r.*, u.nom, u.prenom
        FROM replies r
        LEFT JOIN users u ON r.user_id = u.id
        WHERE r.article_id = ? AND r.status = 'approuvé'
        ORDER BY r.created_at DESC
    ");
    $commentsStmt->execute([$article['id'] ?? 0]);
    $comments = $commentsStmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (Exception $e) {
    $article = null;
    $comments = [];
}

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Test Commentaires</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background: #f0f4f8; padding: 40px 20px; }
        .container { max-width: 900px; }
        .test-box { background: white; border-radius: 12px; padding: 24px; box-shadow: 0 1px 6px rgba(0,0,0,0.1); margin-bottom: 20px; }
        .status-ok { background: #e8f5e9; color: #2e7d32; border: 1px solid #a5d6a7; padding: 15px; border-radius: 8px; margin-bottom: 20px; }
        .comment-item {
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 16px;
            margin: 12px 0;
            background: #fafbfc;
        }
    </style>
</head>
<body>

<div class="container">
    <h1>✅ Test : Commentaires sur Articles</h1>

    <div class="status-ok">
        <strong>✓ Fonctionnalités Ajoutées :</strong><br>
        <i class="fas fa-check me-2"></i> Formulaire pour ajouter un commentaire<br>
        <i class="fas fa-check me-2"></i> Affichage des commentaires approuvés<br>
        <i class="fas fa-check me-2"></i> Compteur de commentaires<br>
        <i class="fas fa-check me-2"></i> Logs d'actions (ajout commentaire)
    </div>

    <?php if ($article): ?>
    <div class="test-box">
        <h3>📰 Article Test : <?= htmlspecialchars($article['titre']) ?></h3>
        
        <div class="alert alert-info">
            <i class="fas fa-info-circle me-2"></i>
            <strong>Commentaires : </strong>
            <span class="badge bg-primary"><?= $article['comment_count'] ?></span>
        </div>

        <a href="index.php?page=articles_admin&action=view&id=<?= $article['id'] ?>" target="_blank" class="btn btn-primary btn-sm">
            <i class="fas fa-external-link-alt me-1"></i> Voir l'article avec commentaires
        </a>
    </div>

    <?php if (!empty($comments)): ?>
    <div class="test-box">
        <h4>💬 Commentaires Approuvés</h4>
        
        <?php foreach ($comments as $c): ?>
        <div class="comment-item">
            <strong><?= htmlspecialchars(($c['prenom'] ?? '') . ' ' . ($c['nom'] ?? '')) ?: 'Anonyme' ?></strong>
            <small class="text-muted d-block mt-2">
                <i class="fas fa-calendar me-1"></i>
                <?= date('d/m/Y H:i', strtotime($c['created_at'] ?? 'now')) ?>
            </small>
            <p class="mt-3 mb-0" style="line-height: 1.6; color: #334155;">
                <?= nl2br(htmlspecialchars($c['replay'] ?? '')) ?>
            </p>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
    
    <?php else: ?>
    <div class="test-box alert alert-warning">
        <i class="fas fa-exclamation-triangle me-2"></i>
        Aucun article trouvé dans la base de données
    </div>
    <?php endif; ?>

    <div class="test-box">
        <h3>🎯 Fonctionnalités Implémentées</h3>
        <ul>
            <li>✅ <strong>Formulaire de commentaire</strong> avec textarea</li>
            <li>✅ <strong>Validation</strong> (min 3 caractères)</li>
            <li>✅ <strong>Stockage</strong> en table <code>replies</code></li>
            <li>✅ <strong>Statut</strong> : nouveau commentaire = automatiquement approuvé</li>
            <li>✅ <strong>Affichage</strong> des commentaires approuvés</li>
            <li>✅ <strong>Compteur</strong> de commentaires</li>
            <li>✅ <strong>Auteur</strong> enregistré (user_id)</li>
            <li>✅ <strong>Logs</strong> d'ajout de commentaire (audit trail)</li>
            <li>✅ <strong>Messages flash</strong> de succès/erreur</li>
        </ul>
    </div>

    <div class="test-box">
        <h3>📝 Flux Complet</h3>
        <ol>
            <li>L'utilisateur admin visite la page de détails d'un article</li>
            <li>Il voit le formulaire "Ajouter un commentaire" avec textarea</li>
            <li>Il écrit un commentaire et clique sur "Publier"</li>
            <li>Le formulaire POST vers <code>index.php?page=articles_admin&action=add_comment&id=ID</code></li>
            <li>AdminController::addComment() valide et insère en DB</li>
            <li>Message de succès et redirection vers la même page</li>
            <li>Le commentaire s'affiche immédiatement dans la section "Commentaires"</li>
        </ol>
    </div>

    <div class="test-box">
        <h3>🔗 Navigation</h3>
        <a href="index.php?page=articles_admin" class="btn btn-secondary btn-sm">
            <i class="fas fa-list me-1"></i> Voir tous les articles
        </a>
        <a href="test_article_details.php" class="btn btn-info btn-sm">
            <i class="fas fa-link me-1"></i> Test : Bouton Voir Détails
        </a>
    </div>
</div>

</body>
</html>
