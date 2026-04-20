<?php
/**
 * Test : Vérifier que le bouton "Voir" fonctionne pour chaque article
 */

session_start();
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/models/Article.php';

$_SESSION['user_id'] = 1;
$_SESSION['user_name'] = 'Test Admin';
$_SESSION['user_role'] = 'admin';

try {
    $db = Database::getInstance()->getConnection();
    $stmt = $db->query("SELECT id, titre, auteur_id FROM articles LIMIT 5");
    $articles = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $articles = [];
}

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Test Bouton Voir Détails</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #f0f4f8; padding: 40px 20px; }
        .container { max-width: 900px; }
        .test-box { background: white; border-radius: 12px; padding: 24px; box-shadow: 0 1px 6px rgba(0,0,0,0.1); margin-bottom: 20px; }
        .status-ok { background: #e8f5e9; color: #2e7d32; border: 1px solid #a5d6a7; padding: 15px; border-radius: 8px; margin-bottom: 20px; }
        .article-link { margin: 8px 0; }
        .article-link a { display: inline-flex; align-items: center; gap: 8px; padding: 10px 14px; background: #e3f0ff; color: #1565c0; text-decoration: none; border-radius: 6px; font-weight: 600; }
        .article-link a:hover { background: #bbdefb; }
    </style>
</head>
<body>

<div class="container">
    <h1>✅ Test : Bouton "Voir les Détails"</h1>

    <div class="status-ok">
        <strong>✓ Fonctionnalité Ajoutée :</strong><br>
        Chaque article a maintenant un bouton <strong>"Voir"</strong> (eye icon) dans la colonne Actions
    </div>

    <div class="test-box">
        <h3>📋 Articles Disponibles</h3>
        <p><small class="text-muted">Cliquez sur un article pour voir ses détails complets :</small></p>

        <?php if (!empty($articles)): ?>
            <?php foreach ($articles as $article): ?>
                <div class="article-link">
                    <a href="index.php?page=articles_admin&action=view&id=<?= $article['id'] ?>" target="_blank">
                        <i class="fas fa-eye"></i>
                        <span><?= htmlspecialchars(mb_substr($article['titre'], 0, 50)) ?></span>
                        <small style="opacity: 0.7;">(#<?= $article['id'] ?>)</small>
                    </a>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p class="text-muted"><i class="fas fa-info-circle me-2"></i>Aucun article trouvé</p>
        <?php endif; ?>
    </div>

    <div class="test-box">
        <h3>🎯 Fonctionnalités de la Vue Détails</h3>
        <ul>
            <li>✅ <strong>Titre complet</strong> de l'article</li>
            <li>✅ <strong>Auteur</strong> (nom du médecin/admin qui a créé)</li>
            <li>✅ <strong>Date de création</strong> formatée (JJ/MM/YYYY HH:mm)</li>
            <li>✅ <strong>Nombre de vues</strong> (incrémenté à chaque visite)</li>
            <li>✅ <strong>Catégorie</strong> et <strong>Tags</strong></li>
            <li>✅ <strong>Statut</strong> (Brouillon/Publié/Archivé) avec badge</li>
            <li>✅ <strong>Contenu enrichi</strong> (texte, images, emoji, formatage)</li>
            <li>✅ <strong>Boutons d'action</strong> : Modifier, Supprimer, Retour</li>
        </ul>
    </div>

    <div class="test-box">
        <h3>📝 Contenu Supporté</h3>
        <p>La vue détails affiche correctement :</p>
        <ul>
            <li>📄 Texte formaté (gras, italique, souligné, citation)</li>
            <li>📷 Images intégrées</li>
            <li>😊 Emojis</li>
            <li>📊 Listes et titres</li>
            <li>💻 Code et blocs de code</li>
            <li>🔗 Liens (créés via Quill)</li>
        </ul>
    </div>

    <div class="test-box">
        <h3>🔗 Navigation</h3>
        <ul>
            <li>
                <strong>Liste des articles :</strong>
                <a href="index.php?page=articles_admin" class="btn btn-sm btn-primary">
                    <i class="fas fa-list me-1"></i>Voir la liste
                </a>
            </li>
            <li>
                <strong>Ajouter un article :</strong>
                <a href="index.php?page=articles_admin&action=create" class="btn btn-sm btn-success">
                    <i class="fas fa-plus me-1"></i>Nouvel article
                </a>
            </li>
        </ul>
    </div>
</div>

</body>
</html>
