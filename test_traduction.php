<?php
// ╔══════════════════════════════════════════════════════════════╗
// ║  FICHIER TEST : test_traduction.php                         ║
// ║  PLACER       : projetw/test_traduction.php                 ║
// ║  OUVRIR       : http://localhost/projetw/test_traduction.php ║
// ║  SUPPRIMER    : après que ça marche                         ║
// ╚══════════════════════════════════════════════════════════════╝

session_start();
require_once __DIR__ . '/config/database.php';

echo '<!DOCTYPE html><html lang="fr"><head><meta charset="UTF-8">
<title>Test Traduction</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head><body class="p-4">';

echo '<h2>🔍 Diagnostic Traduction</h2>';

// ── TEST 1 : Session ─────────────────────────────────────────
echo '<h4>1. Session utilisateur</h4>';
if (!empty($_SESSION['user_id'])) {
    echo '<div class="alert alert-success">✅ Connecté — user_id = ' . $_SESSION['user_id'] . '</div>';
} else {
    echo '<div class="alert alert-danger">❌ NON CONNECTÉ — Le widget ne s\'affiche que si vous êtes connecté.<br>
    <a href="index.php?page=login" class="btn btn-primary mt-2">Se connecter</a></div>';
}

// ── TEST 2 : Fichier widget ───────────────────────────────────
echo '<h4>2. Fichier translation_widget.php</h4>';
$widgetPath = __DIR__ . '/views/translation_widget.php';
if (file_exists($widgetPath)) {
    echo '<div class="alert alert-success">✅ Fichier trouvé : ' . $widgetPath . '</div>';
} else {
    echo '<div class="alert alert-danger">❌ FICHIER MANQUANT : ' . $widgetPath . '<br>
    <strong>Créez ce fichier avec le contenu de T3_translation_widget.php</strong></div>';
}

// ── TEST 3 : Fichier TranslationController ────────────────────
echo '<h4>3. TranslationController.php</h4>';
$ctrlPath = __DIR__ . '/controllers/TranslationController.php';
if (file_exists($ctrlPath)) {
    echo '<div class="alert alert-success">✅ Fichier trouvé</div>';
} else {
    echo '<div class="alert alert-danger">❌ FICHIER MANQUANT : controllers/TranslationController.php</div>';
}

// ── TEST 4 : Fichier TranslationService ──────────────────────
echo '<h4>4. TranslationService.php</h4>';
$svcPath = __DIR__ . '/services/TranslationService.php';
if (file_exists($svcPath)) {
    echo '<div class="alert alert-success">✅ Fichier trouvé</div>';
} else {
    echo '<div class="alert alert-danger">❌ FICHIER MANQUANT : services/TranslationService.php</div>';
}

// ── TEST 5 : Table translations ───────────────────────────────
echo '<h4>5. Table SQL "translations"</h4>';
try {
    $db   = Database::getInstance()->getConnection();
    $stmt = $db->query("SHOW TABLES LIKE 'translations'");
    if ($stmt->rowCount() > 0) {
        echo '<div class="alert alert-success">✅ Table "translations" existe</div>';
    } else {
        echo '<div class="alert alert-danger">❌ Table "translations" MANQUANTE<br>
        Exécutez T4_translations_SQL.sql dans phpMyAdmin</div>';
    }
} catch (Exception $e) {
    echo '<div class="alert alert-danger">❌ Erreur DB : ' . $e->getMessage() . '</div>';
}

// ── TEST 6 : Article dans la DB ───────────────────────────────
echo '<h4>6. Articles disponibles</h4>';
try {
    $db    = Database::getInstance()->getConnection();
    $arts  = $db->query("SELECT id, titre FROM articles LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
    if ($arts) {
        echo '<div class="alert alert-success">✅ Articles trouvés :<ul>';
        foreach ($arts as $a) {
            echo '<li>ID=' . $a['id'] . ' — ' . htmlspecialchars($a['titre']) . '
            <a href="index.php?page=detail_article_public&id=' . $a['id'] . '" target="_blank" class="btn btn-sm btn-outline-primary ms-2">Ouvrir</a></li>';
        }
        echo '</ul></div>';
    } else {
        echo '<div class="alert alert-warning">⚠️ Aucun article dans la base</div>';
    }
} catch (Exception $e) {
    echo '<div class="alert alert-danger">❌ Erreur : ' . $e->getMessage() . '</div>';
}

// ── TEST 7 : Tester l'API translate ──────────────────────────
echo '<h4>7. Test API Traduction</h4>';
if (!empty($_SESSION['user_id'])) {
    $arts2 = $db->query("SELECT id FROM articles LIMIT 1")->fetch(PDO::FETCH_ASSOC);
    $artId = $arts2['id'] ?? 1;
    echo '<div class="alert alert-info">
    Cliquez pour tester l\'API :<br><br>
    <a href="index.php?page=api_translate&action=get_text&type=article&id=' . $artId . '&lang=en" 
       target="_blank" class="btn btn-primary">
       Tester API → index.php?page=api_translate&action=get_text&type=article&id=' . $artId . '&lang=en
    </a><br><br>
    Vous devez voir du JSON avec "success":true
    </div>';
}

// ── TEST 8 : Afficher le widget directement ───────────────────
echo '<h4>8. Affichage direct du widget</h4>';
if (!empty($_SESSION['user_id']) && file_exists($widgetPath)) {
    require_once $widgetPath;
    $arts3 = $db->query("SELECT id FROM articles LIMIT 1")->fetch(PDO::FETCH_ASSOC);
    $artId3 = $arts3['id'] ?? 1;
    echo '<div class="p-3" style="background:#111;border-radius:12px;">';
    echo '<p style="color:#888;font-size:12px;">Widget pour article ID=' . $artId3 . ' :</p>';
    echo renderTranslationWidget('article', $artId3);
    echo '</div>';
    echo '<div class="alert alert-success mt-2">✅ Le widget s\'affiche ci-dessus — cliquez sur 🇬🇧 English pour tester</div>';
} elseif (empty($_SESSION['user_id'])) {
    echo '<div class="alert alert-warning">⚠️ Connectez-vous d\'abord pour voir le widget</div>';
} else {
    echo '<div class="alert alert-danger">❌ Fichier widget manquant</div>';
}

// ── RÉSUMÉ ────────────────────────────────────────────────────
echo '<hr><h4>📋 Résumé — Ce qui manque</h4>';
$problems = [];
if (empty($_SESSION['user_id']))    $problems[] = 'Vous n\'êtes pas connecté';
if (!file_exists($widgetPath))      $problems[] = 'Fichier views/frontoffice/translation_widget.php manquant';
if (!file_exists($ctrlPath))        $problems[] = 'Fichier controllers/TranslationController.php manquant';
if (!file_exists($svcPath))         $problems[] = 'Fichier services/TranslationService.php manquant';

if (empty($problems)) {
    echo '<div class="alert alert-success"><strong>✅ Tout est en ordre !</strong><br>
    Ouvrez un article et le widget de traduction apparaîtra sous le contenu.<br>
    <a href="index.php?page=blog_public" class="btn btn-primary mt-2">Aller au blog</a></div>';
} else {
    echo '<div class="alert alert-danger"><strong>❌ Problèmes trouvés :</strong><ul>';
    foreach ($problems as $p) echo '<li>' . $p . '</li>';
    echo '</ul></div>';
}

echo '</body></html>';