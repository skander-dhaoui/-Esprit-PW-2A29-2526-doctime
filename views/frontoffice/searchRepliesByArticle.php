<?php
/**
 * searchRepliesByArticle.php
 * Vue pour rechercher et afficher les replies d'un article (JOINTURE)
 * Suit le pattern du workshop avec le formulaire de sélection
 */

// Inclure le contrôleur
require_once __DIR__ . '/../../controllers/ArticleController.php';

// Créer une instance du contrôleur
$articleCtrl = new ArticleController();

// Variables pour la vue
$articles = [];
$replies = [];
$selectedArticleId = null;
$selectedArticleTitle = '';
$error = null;
$warning = null;

// Récupérer tous les articles pour le formulaire
$articles = $articleCtrl->afficherArticles();

// Traitement du formulaire - Valider et récupérer les replies
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validation serveur (pas de HTML5)
    $selectedArticleId = isset($_POST['article']) ? (int)$_POST['article'] : null;
    
    // Validation stricte
    if ($selectedArticleId === null || $selectedArticleId <= 0) {
        $error = 'Veuillez sélectionner un article valide.';
    } else {
        // Vérifier que l'article existe
        $article = $articleCtrl->afficherArticleComplet($selectedArticleId);
        if (!$article) {
            $error = 'L\'article sélectionné n\'existe pas ou n\'est pas publié.';
        } else {
            $selectedArticleTitle = $article['titre'];
            // Récupérer les replies via JOINTURE
            $replies = $articleCtrl->afficherReplies($selectedArticleId);
            
            // Message si pas de replies
            if (empty($replies)) {
                $warning = 'Cet article n\'a pas encore de commentaires approuvés.';
            }
        }
    }
}

// Retourner les données pour la vue HTML
return [
    'articles' => $articles,
    'replies' => $replies,
    'selectedArticleId' => $selectedArticleId,
    'selectedArticleTitle' => $selectedArticleTitle,
    'error' => $error,
    'warning' => $warning,
];
