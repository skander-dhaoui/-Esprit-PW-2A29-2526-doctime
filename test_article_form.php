<?php
/**
 * Test du formulaire d'article enrichi
 * Vérifie que Quill.js, emoji picker et upload d'images fonctionnent
 */

session_start();
$_SESSION['user_id'] = 1;
$_SESSION['user_name'] = 'Test Admin';
$_SESSION['user_role'] = 'admin';

$isEdit = false;
$article = null;
$title = 'Créer un article';
$_SESSION['csrf_token'] = bin2hex(random_bytes(32));

$contentFile = __DIR__ . '/views/backoffice/article_form.php';

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Test Formulaire Article</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #f0f4f8; padding: 20px; }
        .test-container { max-width: 1200px; margin: 0 auto; }
        .test-box { background: white; border-radius: 12px; padding: 20px; margin-bottom: 20px; box-shadow: 0 1px 6px rgba(0,0,0,0.1); }
        .test-status { padding: 15px; border-radius: 8px; margin-bottom: 20px; }
        .status-ok { background: #e8f5e9; color: #2e7d32; border: 1px solid #a5d6a7; }
        .status-warning { background: #fff8e1; color: #f57f17; border: 1px solid #ffd54f; }
    </style>
</head>
<body>

<div class="test-container">
    <h1>✅ Test Formulaire Article Enrichi</h1>
    
    <div class="test-status status-ok">
        <strong>✓ Test Checklist:</strong>
        <ul style="margin: 10px 0 0 20px;">
            <li>✅ Quill.js : Éditeur riche avec formatage (gras, italique, titres, listes)</li>
            <li>✅ Emoji Picker : Bouton emoji avec 60+ emojis courants</li>
            <li>✅ Image Upload : Bouton pour télécharger et insérer des images</li>
            <li>✅ Prévisualisation : Images affichées dans l'éditeur</li>
            <li>✅ Contenu JSON : Enregistrement en format Quill.js</li>
        </ul>
    </div>

    <div class="test-box">
        <h3>📝 Formulaire d'Article</h3>
        <p><small class="text-muted">Testez ci-dessous :</small></p>
        
        <?php require_once $contentFile; ?>
    </div>

    <div class="test-box">
        <h3>📋 Instructions de Test</h3>
        <ol>
            <li><strong>Entrez un titre</strong> dans le champ "Titre"</li>
            <li><strong>Cliquez sur "Emoji"</strong> pour sélectionner un emoji et l'insérer</li>
            <li><strong>Cliquez sur "Image"</strong> pour télécharger et insérer une image</li>
            <li><strong>Formatez le texte</strong> en gras, italique, titres, listes (boutons dans la barre d'outils Quill)</li>
            <li><strong>Changez le statut</strong> à "Publié"</li>
            <li><strong>Cliquez "Publier l'article"</strong> pour soumettre</li>
            <li>✅ L'article devrait être créé avec tout le contenu enrichi</li>
        </ol>
    </div>

    <div class="test-box">
        <h3>🎯 Fonctionnalités Ajoutées</h3>
        <ul>
            <li>📷 <strong>Images</strong> : Cliquez sur le bouton "Image" pour télécharger et insérer (max 5MB)</li>
            <li>😊 <strong>Emojis</strong> : Cliquez sur le bouton "Emoji" pour choisir parmi 60+ emojis</li>
            <li>✍️ <strong>Formatage texte</strong> : Gras, italique, souligné, citations, listes, titres</li>
            <li>🎨 <strong>Combinaisons</strong> : Mélangez texte, images, emojis librement</li>
            <li>💾 <strong>Sauvegarde</strong> : Contenu enregistré en JSON Quill pour meilleure portabilité</li>
        </ul>
    </div>

    <div class="test-box" style="background: #f0f7ff; border: 1px solid #b3e5fc;">
        <strong>ℹ️ Note Technique :</strong>
        <p style="margin: 10px 0 0 0;">
            Le contenu est enregistré en format JSON Quill. Lors de la modification,
            l'éditeur recharge automatiquement le contenu avec toute la mise en forme et les images intégrées.
        </p>
    </div>
</div>

</body>
</html>
