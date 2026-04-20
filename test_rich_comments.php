<?php
/**
 * Test : Commentaires enrichis (texte + images + emojis)
 */

session_start();
require_once __DIR__ . '/config/database.php';

$_SESSION['user_id'] = 1;
$_SESSION['user_name'] = 'Test Admin';
$_SESSION['user_role'] = 'admin';

try {
    $db = Database::getInstance()->getConnection();
    
    // Get article with rich comments
    $stmt = $db->query("
        SELECT a.id, a.titre, COUNT(r.id) as comment_count
        FROM articles a
        LEFT JOIN replies r ON a.id = r.article_id AND r.status = 'approuvé'
        GROUP BY a.id
        LIMIT 1
    ");
    $article = $stmt->fetch(PDO::FETCH_ASSOC);
    
} catch (Exception $e) {
    $article = null;
}

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Test Commentaires Enrichis</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background: #f0f4f8; padding: 40px 20px; }
        .container { max-width: 900px; }
        .test-box { background: white; border-radius: 12px; padding: 24px; box-shadow: 0 1px 6px rgba(0,0,0,0.1); margin-bottom: 20px; }
        .status-ok { background: #e8f5e9; color: #2e7d32; border: 1px solid #a5d6a7; padding: 15px; border-radius: 8px; margin-bottom: 20px; }
        .feature-list li { margin: 10px 0; }
    </style>
</head>
<body>

<div class="container">
    <h1>✨ Test : Commentaires Enrichis (Texte + Images + Emoji)</h1>

    <div class="status-ok">
        <strong>✓ Nouvelles Fonctionnalités :</strong><br>
        <i class="fas fa-check me-2"></i> Éditeur Quill.js pour les commentaires<br>
        <i class="fas fa-check me-2"></i> Bouton Emoji avec 50+ emojis<br>
        <i class="fas fa-check me-2"></i> Upload d'images (max 2MB)<br>
        <i class="fas fa-check me-2"></i> Formatage (gras, italique, listes)<br>
        <i class="fas fa-check me-2"></i> Affichage enrichi des commentaires
    </div>

    <div class="test-box">
        <h3>🎯 Fonctionnalités du Formulaire</h3>
        <ul class="feature-list">
            <li><strong>Bouton Emoji :</strong> Cliquez pour ouvrir le sélecteur d'emojis</li>
            <li><strong>Bouton Image :</strong> Uploader une image (max 2MB) et l'insérer</li>
            <li><strong>Barre d'outils Quill :</strong> Formatage (gras, italique, listes, citations, liens, images)</li>
            <li><strong>Contenu mixte :</strong> Combinez librement texte, images, emojis</li>
            <li><strong>Validation :</strong> Min 3 caractères requis</li>
            <li><strong>Stockage JSON :</strong> Contenu enregistré en format Quill</li>
        </ul>
    </div>

    <div class="test-box">
        <h3>📝 Exemple de Commentaire Enrichi</h3>
        <p>Vous pouvez maintenant créer des commentaires comme :</p>
        <blockquote style="background:#f0f9f8;border-left:3px solid #0fa99b;padding:12px;border-radius:4px">
            <strong>Exemple :</strong> "Excellent article ! 😊 <br>
            Voici mon avis : <br>
            • Point 1 : texte en gras<br>
            • Point 2 : avec une image<br>
            • Point 3 : et des emojis 🎉"
        </blockquote>
    </div>

    <?php if ($article): ?>
    <div class="test-box">
        <h3>🧪 Tester Maintenant</h3>
        <p><strong>Article :</strong> <?= htmlspecialchars($article['titre']) ?></p>
        <p><strong>Commentaires actuels :</strong> <span class="badge bg-primary"><?= $article['comment_count'] ?></span></p>
        
        <a href="index.php?page=articles_admin&action=view&id=<?= $article['id'] ?>" target="_blank" class="btn btn-primary btn-lg">
            <i class="fas fa-external-link-alt me-2"></i> Ouvrir l'article
        </a>
        <p style="margin-top: 20px; font-size: 12px; color: #666;">
            👉 Allez à la section "Commentaires" pour voir le nouveau formulaire enrichi
        </p>
    </div>
    <?php else: ?>
    <div class="test-box alert alert-warning">
        <i class="fas fa-exclamation-triangle me-2"></i>
        Aucun article trouvé dans la base de données
    </div>
    <?php endif; ?>

    <div class="test-box">
        <h3>🔧 Détails Techniques</h3>
        <ul class="feature-list">
            <li><strong>Éditeur :</strong> Quill.js 1.3.6 (open-source)</li>
            <li><strong>Format :</strong> JSON Delta (standard Quill)</li>
            <li><strong>Images :</strong> Base64 (intégrées dans le JSON)</li>
            <li><strong>Émojis :</strong> Sélecteur natif (50+ emojis courants)</li>
            <li><strong>Stockage :</strong> Table <code>replies</code> colonne <code>replay</code></li>
            <li><strong>Affichage :</strong> Rendu HTML avec styles appropriés</li>
        </ul>
    </div>

    <div class="test-box">
        <h3>✅ Checklist de Test</h3>
        <form>
            <div class="form-check">
                <input class="form-check-input" type="checkbox" id="check1">
                <label class="form-check-label" for="check1">
                    Vérifier que le formulaire de commentaire s'affiche avec Quill
                </label>
            </div>
            <div class="form-check">
                <input class="form-check-input" type="checkbox" id="check2">
                <label class="form-check-label" for="check2">
                    Cliquer sur le bouton "Emoji" et sélectionner un emoji
                </label>
            </div>
            <div class="form-check">
                <input class="form-check-input" type="checkbox" id="check3">
                <label class="form-check-label" for="check3">
                    Cliquer sur le bouton "Image" et uploader une image
                </label>
            </div>
            <div class="form-check">
                <input class="form-check-input" type="checkbox" id="check4">
                <label class="form-check-label" for="check4">
                    Formater du texte (gras, italique, listes)
                </label>
            </div>
            <div class="form-check">
                <input class="form-check-input" type="checkbox" id="check5">
                <label class="form-check-label" for="check5">
                    Publier le commentaire enrichi
                </label>
            </div>
            <div class="form-check">
                <input class="form-check-input" type="checkbox" id="check6">
                <label class="form-check-label" for="check6">
                    Vérifier que le commentaire s'affiche correctement (texte, image, emoji)
                </label>
            </div>
        </form>
    </div>

    <div class="test-box">
        <h3>🎨 Comparaison Avant / Après</h3>
        <table class="table">
            <tr>
                <th>Avant</th>
                <th>Après</th>
            </tr>
            <tr>
                <td><span class="text-muted">Textarea simple</span></td>
                <td><span class="text-success">✅ Quill.js WYSIWYG</span></td>
            </tr>
            <tr>
                <td><span class="text-muted">Texte brut uniquement</span></td>
                <td><span class="text-success">✅ Texte + formatage</span></td>
            </tr>
            <tr>
                <td><span class="text-muted">Pas d'images</span></td>
                <td><span class="text-success">✅ Images intégrées</span></td>
            </tr>
            <tr>
                <td><span class="text-muted">Pas d'emojis</span></td>
                <td><span class="text-success">✅ 50+ emojis</span></td>
            </tr>
            <tr>
                <td><span class="text-muted">Affichage simple</span></td>
                <td><span class="text-success">✅ Rendu enrichi</span></td>
            </tr>
        </table>
    </div>
</div>

</body>
</html>
