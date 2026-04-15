<?php
// controllers/PharmacieController.php

require_once __DIR__ . '/../models/Produit.php';
require_once __DIR__ . '/../models/Categorie.php';
require_once __DIR__ . '/../models/Panier.php';
require_once __DIR__ . '/../models/Commande.php';
require_once __DIR__ . '/../models/LigneCommande.php';
require_once __DIR__ . '/../models/User.php';

class PharmacieController {
    
    private Produit $produitModel;
    private Categorie $categorieModel;
    private Panier $panierModel;
    private Commande $commandeModel;
    private LigneCommande $ligneCommandeModel;
    private User $userModel;
    
    public function __construct() {
        $this->produitModel = new Produit();
        $this->categorieModel = new Categorie();
        $this->panierModel = new Panier();
        $this->commandeModel = new Commande();
        $this->ligneCommandeModel = new LigneCommande();
        $this->userModel = new User();
    }
    
    // =============================================
    //  VITRINE PUBLIQUE (FrontOffice)
    // =============================================
    
    /**
     * Page d'accueil de la pharmacie
     */
    public function pharmacieFront(): void {
        $categories = $this->categorieModel->getAllActive();
        $produitsRecents = $this->produitModel->getLatest(8);
        $promotions = $this->produitModel->getPromotions(4);
        
        require_once __DIR__ . '/../views/frontoffice/pharmacie.php';
    }
    
    /**
     * Liste des produits avec filtres
     */
    public function listeProduits(): void {
        $categorieId = $_GET['categorie'] ?? null;
        $search = $_GET['search'] ?? null;
        $page = (int)($_GET['page'] ?? 1);
        $limit = 12;
        
        $produits = $this->produitModel->getAllWithFilters($categorieId, $search, $page, $limit);
        $totalProduits = $this->produitModel->countAllWithFilters($categorieId, $search);
        $totalPages = ceil($totalProduits / $limit);
        
        $categories = $this->categorieModel->getAllActive();
        
        require_once __DIR__ . '/../views/frontoffice/produits_liste.php';
    }
    
    /**
     * Détail d'un produit
     */
    public function produitDetail(int $id): void {
        $produit = $this->produitModel->getById($id);
        
        if (!$produit || !$produit['actif']) {
            $_SESSION['error'] = "Produit non trouvé.";
            header('Location: index.php?page=pharmacie');
            exit;
        }
        
        $produitsSimilaires = $this->produitModel->getSimilaires($produit['categorie_id'], $id, 4);
        
        require_once __DIR__ . '/../views/frontoffice/produit_detail.php';
    }
    
    // =============================================
    //  PANIER (FrontOffice)
    // =============================================
    
    /**
     * Voir le panier
     */
    public function voirPanier(): void {
        $userId = $_SESSION['user_id'] ?? null;
        $sessionId = session_id();
        
        $panier = $this->panierModel->getByUserOrSession($userId, $sessionId);
        $total = 0;
        
        if ($panier) {
            $lignes = $this->panierModel->getLignes($panier['id']);
            foreach ($lignes as $ligne) {
                $total += $ligne['prix_unitaire'] * $ligne['quantite'];
            }
        } else {
            $lignes = [];
        }
        
        require_once __DIR__ . '/../views/frontoffice/panier.php';
    }
    
    /**
     * Ajouter un produit au panier
     */
    public function ajouterAuPanier(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: index.php?page=pharmacie');
            exit;
        }
        
        $produitId = (int)($_POST['produit_id'] ?? 0);
        $quantite = (int)($_POST['quantite'] ?? 1);
        
        $produit = $this->produitModel->getById($produitId);
        if (!$produit || !$produit['actif']) {
            $_SESSION['error'] = "Produit non disponible.";
            header('Location: index.php?page=pharmacie');
            exit;
        }
        
        $userId = $_SESSION['user_id'] ?? null;
        $sessionId = session_id();
        
        // Récupérer ou créer le panier
        $panier = $this->panierModel->getOrCreate($userId, $sessionId);
        
        // Ajouter ou mettre à jour la ligne
        $this->panierModel->ajouterLigne($panier['id'], $produitId, $quantite, $produit['prix_vente']);
        
        $_SESSION['success'] = "Produit ajouté au panier.";
        header('Location: index.php?page=panier');
        exit;
    }
    
    /**
     * Modifier la quantité dans le panier
     */
    public function modifierPanier(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: index.php?page=panier');
            exit;
        }
        
        $ligneId = (int)($_POST['ligne_id'] ?? 0);
        $quantite = (int)($_POST['quantite'] ?? 0);
        
        if ($quantite <= 0) {
            $this->panierModel->supprimerLigne($ligneId);
        } else {
            $this->panierModel->mettreAJourQuantite($ligneId, $quantite);
        }
        
        header('Location: index.php?page=panier');
        exit;
    }
    
    /**
     * Vider le panier
     */
    public function viderPanier(): void {
        $userId = $_SESSION['user_id'] ?? null;
        $sessionId = session_id();
        
        $panier = $this->panierModel->getByUserOrSession($userId, $sessionId);
        if ($panier) {
            $this->panierModel->vider($panier['id']);
        }
        
        $_SESSION['success'] = "Panier vidé.";
        header('Location: index.php?page=panier');
        exit;
    }
    
    // =============================================
    //  COMMANDES (FrontOffice)
    // =============================================
    
    /**
     * Formulaire de commande
     */
    public function showPasserCommande(): void {
        $userId = $_SESSION['user_id'];
        $sessionId = session_id();
        
        $panier = $this->panierModel->getByUserOrSession($userId, $sessionId);
        
        if (!$panier) {
            $_SESSION['error'] = "Votre panier est vide.";
            header('Location: index.php?page=panier');
            exit;
        }
        
        $lignes = $this->panierModel->getLignes($panier['id']);
        $total = 0;
        foreach ($lignes as $ligne) {
            $total += $ligne['prix_unitaire'] * $ligne['quantite'];
        }
        
        $user = $this->userModel->findById($userId);
        
        require_once __DIR__ . '/../views/frontoffice/passer_commande.php';
    }
    
    /**
     * Valider la commande
     */
    public function passerCommande(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: index.php?page=panier');
            exit;
        }
        
        $userId = $_SESSION['user_id'];
        $sessionId = session_id();
        
        $panier = $this->panierModel->getByUserOrSession($userId, $sessionId);
        
        if (!$panier) {
            $_SESSION['error'] = "Votre panier est vide.";
            header('Location: index.php?page=panier');
            exit;
        }
        
        $lignes = $this->panierModel->getLignes($panier['id']);
        
        if (empty($lignes)) {
            $_SESSION['error'] = "Votre panier est vide.";
            header('Location: index.php?page=panier');
            exit;
        }
        
        // Calculer le total
        $total = 0;
        foreach ($lignes as $ligne) {
            $total += $ligne['prix_unitaire'] * $ligne['quantite'];
        }
        
        // Récupérer l'adresse de livraison
        $adresseLivraison = trim($_POST['adresse_livraison'] ?? '');
        if (empty($adresseLivraison)) {
            $user = $this->userModel->findById($userId);
            $adresseLivraison = $user['adresse'] ?? '';
        }
        
        // Créer la commande
        $commandeId = $this->commandeModel->create([
            'user_id' => $userId,
            'numero_commande' => 'CMD-' . date('Ymd') . '-' . uniqid(),
            'date_commande' => date('Y-m-d H:i:s'),
            'statut' => 'en_attente',
            'total' => $total,
            'adresse_livraison' => $adresseLivraison
        ]);
        
        // Créer les lignes de commande
        foreach ($lignes as $ligne) {
            $this->ligneCommandeModel->create([
                'commande_id' => $commandeId,
                'produit_id' => $ligne['produit_id'],
                'quantite' => $ligne['quantite'],
                'prix_unitaire' => $ligne['prix_unitaire']
            ]);
            
            // Mettre à jour le stock
            $this->produitModel->reduireStock($ligne['produit_id'], $ligne['quantite']);
        }
        
        // Vider le panier
        $this->panierModel->vider($panier['id']);
        
        $_SESSION['success'] = "Commande passée avec succès ! Votre numéro de commande : " . $commandeId;
        header('Location: index.php?page=mes_commandes');
        exit;
    }
    
    /**
     * Liste des commandes du client
     */
    public function mesCommandes(): void {
        $userId = $_SESSION['user_id'];
        $commandes = $this->commandeModel->getByUser($userId);
        
        require_once __DIR__ . '/../views/frontoffice/mes_commandes.php';
    }
    
    /**
     * Détail d'une commande
     */
    public function detailCommande(int $id): void {
        $userId = $_SESSION['user_id'];
        $commande = $this->commandeModel->getById($id);
        
        if (!$commande || $commande['user_id'] != $userId) {
            $_SESSION['error'] = "Commande non trouvée.";
            header('Location: index.php?page=mes_commandes');
            exit;
        }
        
        $lignes = $this->ligneCommandeModel->getByCommande($id);
        
        require_once __DIR__ . '/../views/frontoffice/detail_commande.php';
    }
    
    /**
     * Annuler une commande
     */
    public function annulerCommande(int $id): void {
        $userId = $_SESSION['user_id'];
        $commande = $this->commandeModel->getById($id);
        
        if (!$commande || $commande['user_id'] != $userId) {
            $_SESSION['error'] = "Commande non trouvée.";
            header('Location: index.php?page=mes_commandes');
            exit;
        }
        
        if ($commande['statut'] !== 'en_attente') {
            $_SESSION['error'] = "Cette commande ne peut pas être annulée.";
            header('Location: index.php?page=mes_commandes');
            exit;
        }
        
        $this->commandeModel->updateStatut($id, 'annulee');
        
        // Remettre en stock
        $lignes = $this->ligneCommandeModel->getByCommande($id);
        foreach ($lignes as $ligne) {
            $this->produitModel->augmenterStock($ligne['produit_id'], $ligne['quantite']);
        }
        
        $_SESSION['success'] = "Commande annulée.";
        header('Location: index.php?page=mes_commandes');
        exit;
    }
    
    // =============================================
    //  BACKOFFICE - PRODUITS
    // =============================================
    
    /**
     * Liste des produits (admin)
     */
    public function listProduits(): void {
        $page = (int)($_GET['page'] ?? 1);
        $limit = 20;
        $produits = $this->produitModel->getAll($page, $limit);
        $total = $this->produitModel->countAll();
        $totalPages = ceil($total / $limit);
        
        require_once __DIR__ . '/../views/backoffice/produits_list.php';
    }
    
    /**
     * Formulaire de création de produit
     */
    public function showCreateProduit(): void {
        $categories = $this->categorieModel->getAll();
        require_once __DIR__ . '/../views/backoffice/produit_form.php';
    }
    
    /**
     * Créer un produit
     */
    public function createProduit(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: index.php?page=produits_admin');
            exit;
        }
        
        $data = [
            'nom' => trim($_POST['nom'] ?? ''),
            'reference' => trim($_POST['reference'] ?? ''),
            'description' => trim($_POST['description'] ?? ''),
            'prix_achat' => (float)($_POST['prix_achat'] ?? 0),
            'prix_vente' => (float)($_POST['prix_vente'] ?? 0),
            'tva' => (float)($_POST['tva'] ?? 19),
            'stock' => (int)($_POST['stock'] ?? 0),
            'stock_alerte' => (int)($_POST['stock_alerte'] ?? 5),
            'categorie_id' => (int)($_POST['categorie_id'] ?? 0),
            'image' => trim($_POST['image'] ?? ''),
            'prescription' => isset($_POST['prescription']) ? 1 : 0,
            'actif' => isset($_POST['actif']) ? 1 : 0
        ];
        
        // Validation
        $errors = [];
        if (empty($data['nom'])) $errors[] = "Le nom est obligatoire.";
        if (empty($data['reference'])) $errors[] = "La référence est obligatoire.";
        if ($data['prix_achat'] <= 0) $errors[] = "Le prix d'achat doit être positif.";
        if ($data['prix_vente'] <= 0) $errors[] = "Le prix de vente doit être positif.";
        if ($data['stock'] < 0) $errors[] = "Le stock ne peut pas être négatif.";
        
        if (!empty($errors)) {
            $_SESSION['flash'] = ['type' => 'error', 'message' => implode('<br>', $errors)];
            $_SESSION['old'] = $_POST;
            header('Location: index.php?page=produits_admin&action=create');
            exit;
        }
        
        $this->produitModel->create($data);
        $_SESSION['flash'] = ['type' => 'success', 'message' => 'Produit créé avec succès.'];
        header('Location: index.php?page=produits_admin');
        exit;
    }
    
    /**
     * Formulaire d'édition de produit
     */
    public function editProduit(int $id): void {
        $produit = $this->produitModel->getById($id);
        if (!$produit) {
            $_SESSION['flash'] = ['type' => 'error', 'message' => 'Produit non trouvé.'];
            header('Location: index.php?page=produits_admin');
            exit;
        }
        
        $categories = $this->categorieModel->getAll();
        require_once __DIR__ . '/../views/backoffice/produit_form.php';
    }
    
    /**
     * Mettre à jour un produit
     */
    public function updateProduit(int $id): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: index.php?page=produits_admin');
            exit;
        }
        
        $produit = $this->produitModel->getById($id);
        if (!$produit) {
            $_SESSION['flash'] = ['type' => 'error', 'message' => 'Produit non trouvé.'];
            header('Location: index.php?page=produits_admin');
            exit;
        }
        
        $data = [
            'nom' => trim($_POST['nom'] ?? ''),
            'reference' => trim($_POST['reference'] ?? ''),
            'description' => trim($_POST['description'] ?? ''),
            'prix_achat' => (float)($_POST['prix_achat'] ?? 0),
            'prix_vente' => (float)($_POST['prix_vente'] ?? 0),
            'tva' => (float)($_POST['tva'] ?? 19),
            'stock' => (int)($_POST['stock'] ?? 0),
            'stock_alerte' => (int)($_POST['stock_alerte'] ?? 5),
            'categorie_id' => (int)($_POST['categorie_id'] ?? 0),
            'image' => trim($_POST['image'] ?? ''),
            'prescription' => isset($_POST['prescription']) ? 1 : 0,
            'actif' => isset($_POST['actif']) ? 1 : 0
        ];
        
        // Validation
        $errors = [];
        if (empty($data['nom'])) $errors[] = "Le nom est obligatoire.";
        if (empty($data['reference'])) $errors[] = "La référence est obligatoire.";
        if ($data['prix_achat'] <= 0) $errors[] = "Le prix d'achat doit être positif.";
        if ($data['prix_vente'] <= 0) $errors[] = "Le prix de vente doit être positif.";
        if ($data['stock'] < 0) $errors[] = "Le stock ne peut pas être négatif.";
        
        if (!empty($errors)) {
            $_SESSION['flash'] = ['type' => 'error', 'message' => implode('<br>', $errors)];
            $_SESSION['old'] = $_POST;
            header("Location: index.php?page=produits_admin&action=edit&id=$id");
            exit;
        }
        
        $this->produitModel->update($id, $data);
        $_SESSION['flash'] = ['type' => 'success', 'message' => 'Produit mis à jour.'];
        header('Location: index.php?page=produits_admin');
        exit;
    }
    
    /**
     * Supprimer un produit
     */
    public function deleteProduit(int $id): void {
        $this->produitModel->delete($id);
        $_SESSION['flash'] = ['type' => 'success', 'message' => 'Produit supprimé.'];
        header('Location: index.php?page=produits_admin');
        exit;
    }
    
    /**
     * Afficher un produit (admin)
     */
    public function showProduit(int $id): void {
        $produit = $this->produitModel->getById($id);
        if (!$produit) {
            $_SESSION['flash'] = ['type' => 'error', 'message' => 'Produit non trouvé.'];
            header('Location: index.php?page=produits_admin');
            exit;
        }
        
        require_once __DIR__ . '/../views/backoffice/produit_show.php';
    }
    
    /**
     * Activer/désactiver un produit
     */
    public function toggleProduit(int $id): void {
        $produit = $this->produitModel->getById($id);
        if ($produit) {
            $newStatus = $produit['actif'] ? 0 : 1;
            $this->produitModel->update($id, ['actif' => $newStatus]);
            $_SESSION['flash'] = ['type' => 'success', 'message' => 'Statut du produit modifié.'];
        }
        header('Location: index.php?page=produits_admin');
        exit;
    }
    
    // =============================================
    //  BACKOFFICE - CATÉGORIES
    // =============================================
    
    /**
     * Liste des catégories
     */
    public function listCategories(): void {
        $categories = $this->categorieModel->getAll();
        require_once __DIR__ . '/../views/backoffice/categories_list.php';
    }
    
    /**
     * Formulaire de création de catégorie
     */
    public function showCreateCategorie(): void {
        $parents = $this->categorieModel->getAll();
        require_once __DIR__ . '/../views/backoffice/categorie_form.php';
    }
    
    /**
     * Créer une catégorie
     */
    public function createCategorie(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: index.php?page=categories_admin');
            exit;
        }
        
        $data = [
            'nom' => trim($_POST['nom'] ?? ''),
            'description' => trim($_POST['description'] ?? ''),
            'parent_id' => (int)($_POST['parent_id'] ?? 0),
            'image' => trim($_POST['image'] ?? ''),
            'statut' => $_POST['statut'] ?? 'actif'
        ];
        
        if (empty($data['nom'])) {
            $_SESSION['flash'] = ['type' => 'error', 'message' => 'Le nom est obligatoire.'];
            header('Location: index.php?page=categories_admin&action=create');
            exit;
        }
        
        $this->categorieModel->create($data);
        $_SESSION['flash'] = ['type' => 'success', 'message' => 'Catégorie créée.'];
        header('Location: index.php?page=categories_admin');
        exit;
    }
    
    /**
     * Formulaire d'édition de catégorie
     */
    public function editCategorie(int $id): void {
        $categorie = $this->categorieModel->getById($id);
        if (!$categorie) {
            $_SESSION['flash'] = ['type' => 'error', 'message' => 'Catégorie non trouvée.'];
            header('Location: index.php?page=categories_admin');
            exit;
        }
        
        $parents = $this->categorieModel->getAll();
        require_once __DIR__ . '/../views/backoffice/categorie_form.php';
    }
    
    /**
     * Mettre à jour une catégorie
     */
    public function updateCategorie(int $id): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: index.php?page=categories_admin');
            exit;
        }
        
        $data = [
            'nom' => trim($_POST['nom'] ?? ''),
            'description' => trim($_POST['description'] ?? ''),
            'parent_id' => (int)($_POST['parent_id'] ?? 0),
            'image' => trim($_POST['image'] ?? ''),
            'statut' => $_POST['statut'] ?? 'actif'
        ];
        
        if (empty($data['nom'])) {
            $_SESSION['flash'] = ['type' => 'error', 'message' => 'Le nom est obligatoire.'];
            header("Location: index.php?page=categories_admin&action=edit&id=$id");
            exit;
        }
        
        $this->categorieModel->update($id, $data);
        $_SESSION['flash'] = ['type' => 'success', 'message' => 'Catégorie mise à jour.'];
        header('Location: index.php?page=categories_admin');
        exit;
    }
    
    /**
     * Supprimer une catégorie
     */
    public function deleteCategorie(int $id): void {
        $this->categorieModel->delete($id);
        $_SESSION['flash'] = ['type' => 'success', 'message' => 'Catégorie supprimée.'];
        header('Location: index.php?page=categories_admin');
        exit;
    }
    
    // =============================================
    //  BACKOFFICE - COMMANDES
    // =============================================
    
    /**
     * Liste des commandes (admin)
     */
    public function listCommandes(): void {
        $page = (int)($_GET['page'] ?? 1);
        $limit = 20;
        $commandes = $this->commandeModel->getAll($page, $limit);
        $total = $this->commandeModel->countAll();
        $totalPages = ceil($total / $limit);
        
        require_once __DIR__ . '/../views/backoffice/commandes_list.php';
    }
    
    /**
     * Détail d'une commande (admin)
     */
    public function showCommande(int $id): void {
        $commande = $this->commandeModel->getById($id);
        if (!$commande) {
            $_SESSION['flash'] = ['type' => 'error', 'message' => 'Commande non trouvée.'];
            header('Location: index.php?page=commandes_admin');
            exit;
        }
        
        $lignes = $this->ligneCommandeModel->getByCommande($id);
        $user = $this->userModel->findById($commande['user_id']);
        
        require_once __DIR__ . '/../views/backoffice/commande_show.php';
    }
    
    /**
     * Mettre à jour le statut d'une commande
     */
    public function updateStatutCommande(int $id): void {
        $statut = $_POST['statut'] ?? '';
        
        $validStatuts = ['en_attente', 'confirmee', 'expediee', 'livree', 'annulee'];
        if (!in_array($statut, $validStatuts)) {
            $_SESSION['flash'] = ['type' => 'error', 'message' => 'Statut invalide.'];
            header('Location: index.php?page=commandes_admin');
            exit;
        }
        
        $this->commandeModel->updateStatut($id, $statut);
        $_SESSION['flash'] = ['type' => 'success', 'message' => 'Statut mis à jour.'];
        header('Location: index.php?page=commandes_admin');
        exit;
    }
    
    /**
     * Exporter les commandes en CSV
     */
    public function exportCommandes(): void {
        $commandes = $this->commandeModel->getAllForExport();
        
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="commandes_' . date('Y-m-d') . '.csv"');
        
        $output = fopen('php://output', 'w');
        fputcsv($output, ['ID', 'Numéro', 'Client', 'Date', 'Total', 'Statut']);
        
        foreach ($commandes as $commande) {
            fputcsv($output, [
                $commande['id'],
                $commande['numero_commande'],
                $commande['client_nom'],
                $commande['date_commande'],
                $commande['total'],
                $commande['statut']
            ]);
        }
        
        fclose($output);
        exit;
    }
}
?>