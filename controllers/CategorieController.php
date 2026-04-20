<?php
/**
 * CategorieController.php
 * Contrôleur pour gérer les catégories et les jointures avec les produits
 * Respecte le pattern MVC et les principes de POO
 */

require_once __DIR__ . '/../models/Categorie.php';
require_once __DIR__ . '/../models/Produit.php';
require_once __DIR__ . '/../config/database.php';

class CategorieController {

    private Categorie $categorieModel;
    private Produit $produitModel;

    /**
     * Constructeur - Initialise les modèles
     */
    public function __construct() {
        $this->categorieModel = new Categorie();
        $this->produitModel = new Produit();
    }

    /**
     * Affiche les produits d'une catégorie spécifique (JOINTURE)
     * Pattern : afficherProduits($idCategorie)
     * 
     * @param int $idCategorie ID de la catégorie
     * @return array Liste des produits avec données jointes
     */
    public function afficherProduits(int $idCategorie): array {
        // Valider l'ID de catégorie
        if ($idCategorie <= 0) {
            error_log("CategorieController::afficherProduits - ID catégorie invalide: $idCategorie");
            return [];
        }

        // Vérifier que la catégorie existe
        $categorie = $this->categorieModel->getById($idCategorie);
        if (!$categorie) {
            error_log("CategorieController::afficherProduits - Catégorie non trouvée: $idCategorie");
            return [];
        }

        // Récupérer les produits via JOINTURE
        $produits = $this->produitModel->getProduitsByCategorie($idCategorie);
        
        return $produits;
    }

    /**
     * Affiche toutes les catégories avec le nombre de produits
     * Utilisé pour le formulaire de sélection
     * 
     * @return array Liste des catégories
     */
    public function afficherCategories(): array {
        try {
            return $this->produitModel->getAllCategories();
        } catch (Exception $e) {
            error_log('CategorieController::afficherCategories - ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Affiche une catégorie spécifique avec ses détails
     * 
     * @param int $id ID de la catégorie
     * @return array|null Données de la catégorie ou null
     */
    public function afficherCategorie(int $id): ?array {
        if ($id <= 0) {
            return null;
        }
        return $this->categorieModel->getById($id);
    }

    /**
     * Crée une nouvelle catégorie
     * Validation serveur (pas de HTML5)
     * 
     * @param array $data Données du formulaire
     * @return int|null ID de la catégorie créée ou null
     */
    public function creerCategorie(array $data): ?int {
        // Validation serveur stricte
        $errors = $this->validerCategorie($data);
        if (!empty($errors)) {
            error_log('CategorieController::creerCategorie - Erreurs: ' . implode(', ', $errors));
            return null;
        }

        return $this->categorieModel->create($data);
    }

    /**
     * Met à jour une catégorie
     * 
     * @param int $id ID de la catégorie
     * @param array $data Nouvelles données
     * @return bool Succès ou non
     */
    public function mettreAJourCategorie(int $id, array $data): bool {
        if ($id <= 0) {
            return false;
        }

        $errors = $this->validerCategorie($data);
        if (!empty($errors)) {
            return false;
        }

        return $this->categorieModel->update($id, $data);
    }

    /**
     * Supprime une catégorie
     * 
     * @param int $id ID de la catégorie
     * @return bool Succès ou non
     */
    public function supprimerCategorie(int $id): bool {
        if ($id <= 0) {
            return false;
        }
        return $this->categorieModel->delete($id);
    }

    /**
     * VALIDATION SERVEUR (pas de HTML5)
     * Valide les données d'une catégorie
     * 
     * @param array $data Données à valider
     * @return array Tableau des erreurs
     */
    private function validerCategorie(array $data): array {
        $errors = [];

        // Validation du nom
        if (empty($data['nom']) || !is_string($data['nom'])) {
            $errors[] = 'Le nom de la catégorie est obligatoire.';
        } elseif (strlen(trim($data['nom'])) < 2) {
            $errors[] = 'Le nom doit contenir au moins 2 caractères.';
        } elseif (strlen(trim($data['nom'])) > 100) {
            $errors[] = 'Le nom ne doit pas dépasser 100 caractères.';
        }

        // Validation du slug
        if (!empty($data['slug'])) {
            if (!preg_match('/^[a-z0-9-]+$/', $data['slug'])) {
                $errors[] = 'Le slug doit contenir uniquement des lettres minuscules, chiffres et tirets.';
            }
        }

        // Validation de la description
        if (!empty($data['description'])) {
            if (strlen($data['description']) > 500) {
                $errors[] = 'La description ne doit pas dépasser 500 caractères.';
            }
        }

        // Validation du parent_id si fourni
        if (!empty($data['parent_id'])) {
            $parentId = (int)$data['parent_id'];
            if ($parentId <= 0) {
                $errors[] = 'ID parent invalide.';
            } elseif ($parentId === ($data['id'] ?? null)) {
                $errors[] = 'Une catégorie ne peut pas être sa propre parente.';
            }
        }

        return $errors;
    }
}
?>