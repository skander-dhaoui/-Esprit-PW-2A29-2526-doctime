<?php
/**
 * searchProduitsByCategorie.php
 * Vue pour rechercher et afficher les produits par catégorie (JOINTURE)
 * Suit le pattern du workshop avec le formulaire de sélection
 */

// Inclure le contrôleur
require_once __DIR__ . '/../../controllers/CategorieController.php';

// Créer une instance du contrôleur
$categorieC = new CategorieController();

// Variables pour la vue
$categories = [];
$produits = [];
$selectedCategoryId = null;
$selectedCategoryName = '';

// Récupérer toutes les catégories pour le formulaire
$categories = $categorieC->afficherCategories();

// Traitement du formulaire - Valider et récupérer les produits
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validation serveur (pas de HTML5)
    $selectedCategoryId = isset($_POST['categorie']) ? (int)$_POST['categorie'] : null;
    
    // Validation stricte
    if ($selectedCategoryId === null || $selectedCategoryId <= 0) {
        $error = 'Veuillez sélectionner une catégorie valide.';
    } else {
        // Vérifier que la catégorie existe
        $categorie = $categorieC->afficherCategorie($selectedCategoryId);
        if (!$categorie) {
            $error = 'La catégorie sélectionnée n\'existe pas.';
        } else {
            $selectedCategoryName = $categorie['nom'];
            // Récupérer les produits via JOINTURE
            $produits = $categorieC->afficherProduits($selectedCategoryId);
            
            if (empty($produits)) {
                $warning = "Aucun produit trouvé pour la catégorie '{$selectedCategoryName}'.";
            }
        }
    }
}

// Renvoyer les données à la vue HTML
return [
    'categories' => $categories,
    'produits' => $produits,
    'selectedCategoryId' => $selectedCategoryId,
    'selectedCategoryName' => $selectedCategoryName,
    'error' => $error ?? null,
    'warning' => $warning ?? null
];
?>
