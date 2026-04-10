<?php
// controllers/PharmacieController.php

require_once __DIR__ . '/../models/Produit.php';
require_once __DIR__ . '/../models/Categorie.php';
require_once __DIR__ . '/../models/Commande.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/mail.php';
require_once __DIR__ . '/AuthController.php';

class PharmacieController {

    private AuthController $auth;
    private Database $db;

    public function __construct() {
        $this->auth = new AuthController();
        $this->db = Database::getInstance();
    }

    // ================================
    // PRODUITS - BACKOFFICE
    // ================================

    public function listProduits(): void {
        $this->auth->requireRole('admin');
        $search = trim($_GET['search'] ?? '');
        $categorieId = (int)($_GET['categorie'] ?? 0);
        $statut = $_GET['statut'] ?? '';

        $produits = $this->produitGetAll($search, $categorieId, $statut);
        $categories = $this->categorieGetActives();
        $stats = $this->produitGetStats();
        $promos = $this->loadStoredPromos();
        $flash = $_SESSION['flash'] ?? null;
        unset($_SESSION['flash']);

        require __DIR__ . '/../views/backoffice/pharmacie/produits_list.php';
    }

    public function exportProduitsCsv(): void {
        $this->auth->requireRole('admin');
        $search = trim($_GET['search'] ?? '');
        $categorieId = (int)($_GET['categorie'] ?? 0);
        $statut = $_GET['statut'] ?? '';

        $produits = $this->produitGetAll($search, $categorieId, $statut);

        $filename = 'produits-parapharmacie-' . date('Y-m-d-His') . '.csv';
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');

        $output = fopen('php://output', 'w');
        fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));
        fputcsv($output, ['Référence', 'Produit', 'Catégorie', 'Prix vente', 'Stock', 'Statut', 'Prescription', 'Description'], ';');

        foreach ($produits as $produit) {
            fputcsv($output, [
                $produit['reference'] ?? '',
                $produit['nom'] ?? '',
                $produit['categorie_nom'] ?? '',
                number_format((float)($produit['prix_vente'] ?? 0), 2, '.', ''),
                (string)($produit['stock'] ?? 0),
                !empty($produit['actif']) ? 'Actif' : 'Inactif',
                !empty($produit['prescription']) ? 'Oui' : 'Non',
                $produit['description'] ?? '',
            ], ';');
        }

        fclose($output);
        exit;
    }

    public function exportCategoriesCsv(): void {
        $this->auth->requireRole('admin');
        $search = trim($_GET['search'] ?? '');
        $categories = $this->categorieGetAll($search);

        $filename = 'categories-parapharmacie-' . date('Y-m-d-His') . '.csv';
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');

        $output = fopen('php://output', 'w');
        fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));
        fputcsv($output, ['Nom', 'Slug', 'Parent', 'Nb produits', 'Statut', 'Description'], ';');

        foreach ($categories as $categorie) {
            fputcsv($output, [
                $categorie['nom'] ?? '',
                $categorie['slug'] ?? '',
                $categorie['parent_nom'] ?? '',
                (string)($categorie['nb_produits'] ?? 0),
                $categorie['statut'] ?? '',
                $categorie['description'] ?? '',
            ], ';');
        }

        fclose($output);
        exit;
    }

    public function exportCommandesCsv(): void {
        $this->auth->requireRole('admin');
        $search = trim($_GET['search'] ?? '');
        $statut = $_GET['statut'] ?? '';
        $commandes = $this->commandeGetAll($search, $statut);

        $filename = 'commandes-parapharmacie-' . date('Y-m-d-His') . '.csv';
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');

        $output = fopen('php://output', 'w');
        fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));
        fputcsv($output, ['N° commande', 'Client', 'Articles', 'Total TTC', 'Paiement', 'Statut', 'Date'], ';');

        foreach ($commandes as $commande) {
            fputcsv($output, [
                $commande['numero_commande'] ?? '',
                trim(($commande['user_prenom'] ?? '') . ' ' . ($commande['user_nom'] ?? '')),
                (string)($commande['nb_articles'] ?? 0),
                number_format((float)($commande['total_ttc'] ?? 0), 2, '.', ''),
                $commande['mode_paiement'] ?? '',
                $commande['statut'] ?? '',
                $commande['created_at'] ?? '',
            ], ';');
        }

        fclose($output);
        exit;
    }

    public function showCreateProduit(): void {
        $this->auth->requireRole('admin');
        $categories = $this->categorieGetActives();
        $flash = $_SESSION['flash'] ?? null;
        unset($_SESSION['flash']);
        require __DIR__ . '/../views/backoffice/pharmacie/produit_form.php';
    }

    public function createProduit(): void {
        $this->auth->requireRole('admin');

        $errors = $this->validateProduit($_POST);
        if (!empty($errors)) {
            $_SESSION['flash'] = ['type' => 'error', 'message' => implode(' | ', $errors)];
            header('Location: index.php?page=produits_admin&action=create');
            exit;
        }

        $ref = trim($_POST['reference']);
        if ($this->produitReferenceExists($ref)) {
            $_SESSION['flash'] = ['type' => 'error', 'message' => "La référence '$ref' existe déjà."];
            header('Location: index.php?page=produits_admin&action=create');
            exit;
        }

        $data = [
            'nom' => htmlspecialchars(trim($_POST['nom'])),
            'reference' => htmlspecialchars($ref),
            'description' => htmlspecialchars(trim($_POST['description'] ?? '')),
            'categorie_id' => (int)$_POST['categorie_id'] ?: null,
            'prix_achat' => (float)str_replace(',', '.', $_POST['prix_achat']),
            'prix_vente' => (float)str_replace(',', '.', $_POST['prix_vente']),
            'tva' => (float)str_replace(',', '.', $_POST['tva'] ?: 19),
            'stock' => (int)$_POST['stock'],
            'stock_alerte' => (int)($_POST['stock_alerte'] ?: 5),
            'image' => htmlspecialchars(trim($_POST['image'] ?? '')),
            'prescription' => isset($_POST['prescription']) ? 1 : 0,
            'actif' => isset($_POST['actif']) ? 1 : 0,
        ];

        $id = $this->produitCreate($data);
        if ($id) {
            $_SESSION['flash'] = ['type' => 'success', 'message' => 'Produit créé avec succès.'];
            header("Location: index.php?page=produits_admin&action=show&id=$id");
        } else {
            $_SESSION['flash'] = ['type' => 'error', 'message' => 'Erreur lors de la création.'];
            header('Location: index.php?page=produits_admin&action=create');
        }
        exit;
    }

    public function showProduit(int $id): void {
        $this->auth->requireRole('admin');
        $produit = $this->produitGetById($id);
        if (!$produit) {
            $this->notFound();
            return;
        }
        $flash = $_SESSION['flash'] ?? null;
        unset($_SESSION['flash']);
        require __DIR__ . '/../views/backoffice/pharmacie/produit_show.php';
    }

    public function editProduit(int $id): void {
        $this->auth->requireRole('admin');
        $produit = $this->produitGetById($id);
        if (!$produit) {
            $this->notFound();
            return;
        }
        $categories = $this->categorieGetActives();
        $flash = $_SESSION['flash'] ?? null;
        unset($_SESSION['flash']);
        require __DIR__ . '/../views/backoffice/pharmacie/produit_form.php';
    }

    public function updateProduit(int $id): void {
        $this->auth->requireRole('admin');

        $errors = $this->validateProduit($_POST);
        if (!empty($errors)) {
            $_SESSION['flash'] = ['type' => 'error', 'message' => implode(' | ', $errors)];
            header("Location: index.php?page=produits_admin&action=edit&id=$id");
            exit;
        }

        $ref = trim($_POST['reference']);
        if ($this->produitReferenceExists($ref, $id)) {
            $_SESSION['flash'] = ['type' => 'error', 'message' => "La référence '$ref' est déjà utilisée."];
            header("Location: index.php?page=produits_admin&action=edit&id=$id");
            exit;
        }

        $data = [
            'nom' => htmlspecialchars(trim($_POST['nom'])),
            'reference' => htmlspecialchars($ref),
            'description' => htmlspecialchars(trim($_POST['description'] ?? '')),
            'categorie_id' => (int)$_POST['categorie_id'] ?: null,
            'prix_achat' => (float)str_replace(',', '.', $_POST['prix_achat']),
            'prix_vente' => (float)str_replace(',', '.', $_POST['prix_vente']),
            'tva' => (float)str_replace(',', '.', $_POST['tva'] ?: 19),
            'stock' => (int)$_POST['stock'],
            'stock_alerte' => (int)($_POST['stock_alerte'] ?: 5),
            'image' => htmlspecialchars(trim($_POST['image'] ?? '')),
            'prescription' => isset($_POST['prescription']) ? 1 : 0,
            'actif' => isset($_POST['actif']) ? 1 : 0,
        ];

        $ok = $this->produitUpdate($id, $data);
        $_SESSION['flash'] = $ok
            ? ['type' => 'success', 'message' => 'Produit mis à jour.']
            : ['type' => 'error', 'message' => 'Erreur lors de la mise à jour.'];
        header("Location: index.php?page=produits_admin&action=show&id=$id");
        exit;
    }

    public function deleteProduit(int $id): void {
        $this->auth->requireRole('admin');
        $ok = $this->produitDelete($id);
        if ($ok) {
            $_SESSION['flash'] = ['type' => 'success', 'message' => 'Produit supprimé.'];
        } else {
            $_SESSION['flash'] = ['type' => 'error', 'message' => 'Impossible de supprimer : ce produit est lié à des commandes.'];
        }
        header('Location: index.php?page=produits_admin');
        exit;
    }

    // ================================
    // CATEGORIES - BACKOFFICE
    // ================================

    public function listCategories(): void {
        $this->auth->requireRole('admin');
        $search = trim($_GET['search'] ?? '');
        $categories = $this->categorieGetAll($search);
        $stats = $this->categorieGetStats();
        $flash = $_SESSION['flash'] ?? null;
        unset($_SESSION['flash']);
        require __DIR__ . '/../views/backoffice/pharmacie/categories_list.php';
    }

    public function showCreateCategorie(): void {
        $this->auth->requireRole('admin');
        $parents = $this->categorieGetActives();
        $flash = $_SESSION['flash'] ?? null;
        unset($_SESSION['flash']);
        require __DIR__ . '/../views/backoffice/pharmacie/categorie_form.php';
    }

    public function createCategorie(): void {
        $this->auth->requireRole('admin');
        $errors = $this->validateCategorie($_POST);
        if (!empty($errors)) {
            $_SESSION['flash'] = ['type' => 'error', 'message' => implode(' | ', $errors)];
            header('Location: index.php?page=categories_admin&action=create');
            exit;
        }

        $slug = $this->slugify(trim($_POST['nom']));
        if ($this->categorieSlugExists($slug)) {
            $slug .= '-' . time();
        }

        $data = [
            'nom' => htmlspecialchars(trim($_POST['nom'])),
            'slug' => $slug,
            'description' => htmlspecialchars(trim($_POST['description'] ?? '')),
            'image' => htmlspecialchars(trim($_POST['image'] ?? '')),
            'parent_id' => (int)($_POST['parent_id'] ?? 0) ?: null,
            'statut' => $_POST['statut'] ?? 'actif',
        ];

        $id = $this->categorieCreate($data);
        if ($id) {
            $_SESSION['flash'] = ['type' => 'success', 'message' => 'Catégorie créée.'];
            header('Location: index.php?page=categories_admin');
        } else {
            $_SESSION['flash'] = ['type' => 'error', 'message' => 'Erreur lors de la création.'];
            header('Location: index.php?page=categories_admin&action=create');
        }
        exit;
    }

    public function editCategorie(int $id): void {
        $this->auth->requireRole('admin');
        $categorie = $this->categorieGetById($id);
        if (!$categorie) {
            $this->notFound();
            return;
        }
        $parents = $this->categorieGetActives();
        $flash = $_SESSION['flash'] ?? null;
        unset($_SESSION['flash']);
        require __DIR__ . '/../views/backoffice/pharmacie/categorie_form.php';
    }

    public function updateCategorie(int $id): void {
        $this->auth->requireRole('admin');
        $errors = $this->validateCategorie($_POST);
        if (!empty($errors)) {
            $_SESSION['flash'] = ['type' => 'error', 'message' => implode(' | ', $errors)];
            header("Location: index.php?page=categories_admin&action=edit&id=$id");
            exit;
        }

        $slug = $this->slugify(trim($_POST['nom']));
        if ($this->categorieSlugExists($slug, $id)) {
            $slug .= '-' . $id;
        }

        $data = [
            'nom' => htmlspecialchars(trim($_POST['nom'])),
            'slug' => $slug,
            'description' => htmlspecialchars(trim($_POST['description'] ?? '')),
            'image' => htmlspecialchars(trim($_POST['image'] ?? '')),
            'parent_id' => (int)($_POST['parent_id'] ?? 0) ?: null,
            'statut' => $_POST['statut'] ?? 'actif',
        ];

        $ok = $this->categorieUpdate($id, $data);
        $_SESSION['flash'] = $ok
            ? ['type' => 'success', 'message' => 'Catégorie mise à jour.']
            : ['type' => 'error', 'message' => 'Erreur lors de la mise à jour.'];
        header('Location: index.php?page=categories_admin');
        exit;
    }

    public function deleteCategorie(int $id): void {
        $this->auth->requireRole('admin');
        $ok = $this->categorieDelete($id);
        if ($ok) {
            $_SESSION['flash'] = ['type' => 'success', 'message' => 'Catégorie supprimée.'];
        } else {
            $_SESSION['flash'] = ['type' => 'error', 'message' => 'Impossible : des produits sont liés à cette catégorie.'];
        }
        header('Location: index.php?page=categories_admin');
        exit;
    }

    // ================================
    // COMMANDES - BACKOFFICE
    // ================================

    public function listCommandes(): void {
        $this->auth->requireRole('admin');
        $search = trim($_GET['search'] ?? '');
        $statut = $_GET['statut'] ?? '';
        $commandes = $this->commandeGetAll($search, $statut);
        $stats = $this->commandeGetStats();
        $flash = $_SESSION['flash'] ?? null;
        unset($_SESSION['flash']);
        require __DIR__ . '/../views/backoffice/pharmacie/commandes_list.php';
    }

    public function showCommande(int $id): void {
        $this->auth->requireRole('admin');
        $commande = $this->commandeGetById($id);
        if (!$commande) {
            $this->notFound();
            return;
        }
        $details = $this->commandeGetDetails($id);
        $flash = $_SESSION['flash'] ?? null;
        unset($_SESSION['flash']);
        require __DIR__ . '/../views/backoffice/pharmacie/commande_show.php';
    }

    public function showCreateCommande(): void {
        $this->auth->requireRole('admin');
        $produits = $this->produitGetActifs();
        $flash = $_SESSION['flash'] ?? null;
        unset($_SESSION['flash']);
        require __DIR__ . '/../views/backoffice/pharmacie/commande_form.php';
    }

    public function createCommande(): void {
        $this->auth->requireRole('admin');
        $errors = $this->validateCommande($_POST);
        if (!empty($errors)) {
            $_SESSION['flash'] = ['type' => 'error', 'message' => implode(' | ', $errors)];
            header('Location: index.php?page=commandes_admin&action=create');
            exit;
        }

        $produitIds = $_POST['produit_id'] ?? [];
        $quantites = $_POST['quantite'] ?? [];
        $lignes = [];
        $totalHt = 0.0;
        $stockAlerts = [];

        foreach ($produitIds as $i => $pid) {
            $pid = (int)$pid;
            $qte = (int)($quantites[$i] ?? 0);
            if ($pid <= 0 || $qte <= 0) {
                continue;
            }
            $produit = $this->produitGetById($pid);
            if (!$produit) {
                continue;
            }
            if ($qte > (int)$produit['stock']) {
                $_SESSION['flash'] = ['type' => 'error', 'message' => 'Stock insuffisant pour le produit ' . ($produit['nom'] ?? '') . '.'];
                header('Location: index.php?page=commandes_admin&action=create');
                exit;
            }
            $prixUnitaire = (float)$produit['prix_vente'];
            $totalLigne = round($prixUnitaire * $qte, 2);
            $totalHt += $totalLigne;
            $lignes[] = [
                'produit_id' => $pid,
                'quantite' => $qte,
                'prix_unitaire' => $prixUnitaire,
                    'total' => $totalLigne,
            ];
        }

        if (empty($lignes)) {
            $_SESSION['flash'] = ['type' => 'error', 'message' => 'Ajoutez au moins un produit valide.'];
            header('Location: index.php?page=commandes_admin&action=create');
            exit;
        }

        $tva = round($totalHt * 0.19, 2);
        $totalTtc = round($totalHt + $tva, 2);

        $data = [
            'numero_commande' => $this->commandeGenerateNumero(),
            'user_id' => (int)$_POST['user_id'],
            'adresse_livraison' => htmlspecialchars(trim($_POST['adresse_livraison'])),
            'ville' => htmlspecialchars(trim($_POST['ville'])),
            'code_postal' => htmlspecialchars(trim($_POST['code_postal'])),
            'telephone' => htmlspecialchars(trim($_POST['telephone'])),
            'mode_paiement' => $_POST['mode_paiement'] ?? 'carte',
            'total_ht' => $totalHt,
            'tva_montant' => $tva,
            'total_ttc' => $totalTtc,
            'statut' => $_POST['statut'] ?? 'en_attente',
            'notes' => htmlspecialchars(trim($_POST['notes'] ?? '')),
        ];

        $this->db->beginTransaction();
        $id = $this->commandeCreate($data);
        if ($id) {
            foreach ($lignes as $ligne) {
                $ligne['commande_id'] = $id;
                if (!$this->commandeAddDetail($ligne)) {
                    $this->db->rollback();
                    $_SESSION['flash'] = ['type' => 'error', 'message' => 'Erreur lors de la création des lignes de commande.'];
                    header('Location: index.php?page=commandes_admin&action=create');
                    exit;
                }

                $produit = $this->produitGetById((int)$ligne['produit_id']);
                if ($produit) {
                    $newStock = (int)$produit['stock'] - (int)$ligne['quantite'];
                    if (!$this->db->execute(
                        "UPDATE produits SET stock = :stock, updated_at = NOW() WHERE id = :id",
                        ['stock' => $newStock, 'id' => (int)$ligne['produit_id']]
                    )) {
                        $this->db->rollback();
                        $_SESSION['flash'] = ['type' => 'error', 'message' => 'Erreur lors de la mise à jour du stock.'];
                        header('Location: index.php?page=commandes_admin&action=create');
                        exit;
                    }
                    if ((int)$produit['stock'] > (int)$produit['stock_alerte'] && $newStock <= (int)$produit['stock_alerte']) {
                        $stockAlerts[] = ['produit' => $produit, 'nouveau_stock' => $newStock];
                    }
                }
            }
            $this->db->commit();
            foreach ($stockAlerts as $alert) {
                $this->envoyerAlerteStock($alert['produit'], $alert['nouveau_stock']);
            }
            $_SESSION['flash'] = ['type' => 'success', 'message' => 'Commande créée avec succès.'];
            header("Location: index.php?page=commandes_admin&action=show&id=$id");
        } else {
            $this->db->rollback();
            $_SESSION['flash'] = ['type' => 'error', 'message' => 'Erreur lors de la création.'];
            header('Location: index.php?page=commandes_admin&action=create');
        }
        exit;
    }

    public function editCommande(int $id): void {
        $this->auth->requireRole('admin');
        $commande = $this->commandeGetById($id);
        if (!$commande) {
            $this->notFound();
            return;
        }
        $details = $this->commandeGetDetails($id);
        $flash = $_SESSION['flash'] ?? null;
        unset($_SESSION['flash']);
        require __DIR__ . '/../views/backoffice/pharmacie/commande_edit.php';
    }

    public function updateCommande(int $id): void {
        $this->auth->requireRole('admin');
        $errors = $this->validateCommandeEdit($_POST);
        if (!empty($errors)) {
            $_SESSION['flash'] = ['type' => 'error', 'message' => implode(' | ', $errors)];
            header("Location: index.php?page=commandes_admin&action=edit&id=$id");
            exit;
        }

        $data = [
            'adresse_livraison' => htmlspecialchars(trim($_POST['adresse_livraison'])),
            'ville' => htmlspecialchars(trim($_POST['ville'])),
            'code_postal' => htmlspecialchars(trim($_POST['code_postal'])),
            'telephone' => htmlspecialchars(trim($_POST['telephone'])),
            'mode_paiement' => $_POST['mode_paiement'] ?? 'carte',
            'statut' => $_POST['statut'] ?? 'en_attente',
            'notes' => htmlspecialchars(trim($_POST['notes'] ?? '')),
        ];

        $ok = $this->commandeUpdate($id, $data);
        $_SESSION['flash'] = $ok
            ? ['type' => 'success', 'message' => 'Commande mise à jour.']
            : ['type' => 'error', 'message' => 'Erreur lors de la mise à jour.'];
        header("Location: index.php?page=commandes_admin&action=show&id=$id");
        exit;
    }

    public function deleteCommande(int $id): void {
        $this->auth->requireRole('admin');
        $ok = $this->commandeDelete($id);
        $_SESSION['flash'] = $ok
            ? ['type' => 'success', 'message' => 'Commande supprimée.']
            : ['type' => 'error', 'message' => 'Erreur lors de la suppression.'];
        header('Location: index.php?page=commandes_admin');
        exit;
    }

    public function updateStatutCommande(int $id): void {
        $this->auth->requireRole('admin');
        $allowed = ['en_attente', 'confirmee', 'en_preparation', 'expediee', 'livree', 'annulee'];
        $statut = $_POST['statut'] ?? '';
        if (!in_array($statut, $allowed, true)) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false]);
            exit;
        }
        $ok = $this->commandeUpdateStatut($id, $statut);
        header('Content-Type: application/json');
        echo json_encode(['success' => $ok]);
        exit;
    }

    // ================================
    // FRONTOFFICE
    // ================================

    public function pharmacieFront(): void {
        $search = trim($_GET['search'] ?? '');
        $categorieId = (int)($_GET['categorie'] ?? 0);
        $produits = $this->produitGetAll($search, $categorieId, 'actif');
        if (isset($_GET['ajax'])) {
            header('Content-Type: application/json');
            $out = array_map(function($p){
                return [
                    'id' => (int)$p['id'], 'nom' => $p['nom'] ?? '', 'description' => $p['description'] ?? '',
                    'prix_vente' => (float)$p['prix_vente'], 'categorie_nom' => $p['categorie_nom'] ?? '',
                    'image' => $p['image'] ?? '', 'stock' => (int)$p['stock'], 'prescription' => (int)$p['prescription']
                ];
            }, $produits);
            echo json_encode(['success' => true, 'produits' => $out], JSON_UNESCAPED_UNICODE);
            exit;
        }
        $categories = $this->categorieGetActives();
        $chatbotQuery = trim($_POST['chatbot_query'] ?? '');
        $chatbotAnswer = null;
        $chatbotSuggestions = [];

        if ($chatbotQuery !== '') {
            [$chatbotAnswer, $chatbotSuggestions] = $this->chatbotRecommanderProduits($chatbotQuery);
        }

        $flash = $_SESSION['flash'] ?? null;
        unset($_SESSION['flash']);
        require __DIR__ . '/../views/frontoffice/pharmacie/catalogue.php';
    }

    public function produitDetail(int $id): void {
        $produit = $this->produitGetById($id);
        if (!$produit || !(int)$produit['actif']) {
            $this->notFound();
            return;
        }
        $flash = $_SESSION['flash'] ?? null;
        unset($_SESSION['flash']);
        require __DIR__ . '/../views/frontoffice/pharmacie/produit_detail.php';
    }

    public function createCommandeFront(): void {
        if (!isset($_SESSION['user_id'])) {
            header('Location: index.php?page=login');
            exit;
        }

        $userId = (int)$_SESSION['user_id'];
        $errors = $this->validateCommande(array_merge($_POST, ['user_id' => (string)$userId]));

        $produitId = (int)($_POST['produit_id'] ?? 0);
        $quantite = (int)($_POST['quantite'] ?? 0);
        if ($produitId <= 0 || $quantite <= 0) {
            $errors[] = 'Produit ou quantite invalide.';
        }

        $produit = $this->produitGetById($produitId);
        if (!$produit || !(int)$produit['actif']) {
            $errors[] = 'Produit indisponible.';
        } elseif ($quantite > (int)$produit['stock']) {
            $errors[] = 'Quantite demandee superieure au stock disponible.';
        }

        if (!empty($errors)) {
            $_SESSION['flash'] = ['type' => 'error', 'message' => implode(' | ', $errors)];
            $redirectId = $produitId > 0 ? $produitId : (int)($_GET['produit_id'] ?? 0);
            header('Location: index.php?page=produit_detail&id=' . $redirectId);
            exit;
        }

        $prixUnitaire = (float)$produit['prix_vente'];
        $totalHt = round($prixUnitaire * $quantite, 2);
        $tva = round($totalHt * 0.19, 2);
        $totalTtc = round($totalHt + $tva, 2);

        $data = [
            'numero_commande' => $this->commandeGenerateNumero(),
            'user_id' => $userId,
            'adresse_livraison' => htmlspecialchars(trim($_POST['adresse_livraison'])),
            'ville' => htmlspecialchars(trim($_POST['ville'])),
            'code_postal' => htmlspecialchars(trim($_POST['code_postal'])),
            'telephone' => htmlspecialchars(trim($_POST['telephone'])),
            'mode_paiement' => $_POST['mode_paiement'] ?? 'carte',
            'total_ht' => $totalHt,
            'tva_montant' => $tva,
            'total_ttc' => $totalTtc,
            'statut' => 'en_attente',
            'notes' => htmlspecialchars(trim($_POST['notes'] ?? '')),
        ];

        $stockAlerts = [];
        $this->db->beginTransaction();
        $commandeId = $this->commandeCreate($data);
        if (!$commandeId) {
            $this->db->rollback();
            $_SESSION['flash'] = ['type' => 'error', 'message' => 'Erreur lors de la creation de la commande.'];
            header('Location: index.php?page=produit_detail&id=' . $produitId);
            exit;
        }

        $okDetail = $this->commandeAddDetail([
            'commande_id' => $commandeId,
            'produit_id' => $produitId,
            'quantite' => $quantite,
            'prix_unitaire' => $prixUnitaire,
            'total' => $totalHt,
        ]);

        if ($okDetail) {
            $newStock = (int)$produit['stock'] - $quantite;
            if (!$this->db->execute(
                "UPDATE produits SET stock = :stock, updated_at = NOW() WHERE id = :id",
                ['stock' => $newStock, 'id' => $produitId]
            )) {
                $this->db->rollback();
                $_SESSION['flash'] = ['type' => 'error', 'message' => 'Erreur lors de la mise à jour du stock.'];
                header('Location: index.php?page=produit_detail&id=' . $produitId);
                exit;
            }

            if ((int)$produit['stock'] > (int)$produit['stock_alerte'] && $newStock <= (int)$produit['stock_alerte']) {
                $stockAlerts[] = ['produit' => $produit, 'nouveau_stock' => $newStock];
            }
        }

        if (!$okDetail) {
            $this->db->rollback();
            $_SESSION['flash'] = ['type' => 'error', 'message' => 'Erreur lors de la creation des details de la commande.'];
            header('Location: index.php?page=produit_detail&id=' . $produitId);
            exit;
        }

        $this->db->commit();
        foreach ($stockAlerts as $alert) {
            $this->envoyerAlerteStock($alert['produit'], $alert['nouveau_stock']);
        }
        $_SESSION['flash'] = ['type' => 'success', 'message' => 'Commande creee avec succes.'];
        header('Location: index.php?page=mes_commandes');
        exit;
    }

    public function editCommandeFront(int $id): void {
        if (!isset($_SESSION['user_id'])) {
            header('Location: index.php?page=login');
            exit;
        }

        $userId = (int)$_SESSION['user_id'];
        $commande = $this->commandeGetById($id);
        if (!$commande || (int)$commande['user_id'] !== $userId) {
            $this->notFound();
            return;
        }

        if (!in_array($commande['statut'], ['en_attente', 'confirmee'], true)) {
            $_SESSION['flash'] = ['type' => 'error', 'message' => 'Cette commande ne peut plus etre modifiee.'];
            header('Location: index.php?page=mes_commandes');
            exit;
        }

        $details = $this->commandeGetDetails($id);
        $flash = $_SESSION['flash'] ?? null;
        unset($_SESSION['flash']);
        require __DIR__ . '/../views/frontoffice/pharmacie/commande_edit.php';
    }

    public function updateCommandeFront(int $id): void {
        if (!isset($_SESSION['user_id'])) {
            header('Location: index.php?page=login');
            exit;
        }

        $userId = (int)$_SESSION['user_id'];
        $commande = $this->commandeGetById($id);
        if (!$commande || (int)$commande['user_id'] !== $userId) {
            $this->notFound();
            return;
        }

        if (!in_array($commande['statut'], ['en_attente', 'confirmee'], true)) {
            $_SESSION['flash'] = ['type' => 'error', 'message' => 'Cette commande ne peut plus etre modifiee.'];
            header('Location: index.php?page=mes_commandes');
            exit;
        }

        $errors = $this->validateCommande(array_merge($_POST, ['user_id' => (string)$userId]));
        if (!empty($errors)) {
            $_SESSION['flash'] = ['type' => 'error', 'message' => implode(' | ', $errors)];
            header('Location: index.php?page=mes_commandes&action=edit&id=' . $id);
            exit;
        }

        $data = [
            'adresse_livraison' => htmlspecialchars(trim($_POST['adresse_livraison'])),
            'ville' => htmlspecialchars(trim($_POST['ville'])),
            'code_postal' => htmlspecialchars(trim($_POST['code_postal'])),
            'telephone' => htmlspecialchars(trim($_POST['telephone'])),
            'mode_paiement' => $_POST['mode_paiement'] ?? 'carte',
            'statut' => $commande['statut'],
            'notes' => htmlspecialchars(trim($_POST['notes'] ?? '')),
        ];

        $ok = $this->commandeUpdate($id, $data);
        $_SESSION['flash'] = $ok
            ? ['type' => 'success', 'message' => 'Commande mise a jour.']
            : ['type' => 'error', 'message' => 'Erreur lors de la mise a jour.'];
        header('Location: index.php?page=mes_commandes');
        exit;
    }

    public function cancelCommandeFront(int $id): void {
        if (!isset($_SESSION['user_id'])) {
            header('Location: index.php?page=login');
            exit;
        }

        $userId = (int)$_SESSION['user_id'];
        $commande = $this->commandeGetById($id);
        if (!$commande || (int)$commande['user_id'] !== $userId) {
            $this->notFound();
            return;
        }

        if (!in_array($commande['statut'], ['en_attente', 'confirmee'], true)) {
            $_SESSION['flash'] = ['type' => 'error', 'message' => 'Cette commande ne peut pas etre annulee.'];
            header('Location: index.php?page=mes_commandes');
            exit;
        }

        $ok = $this->commandeUpdateStatut($id, 'annulee');
        $_SESSION['flash'] = $ok
            ? ['type' => 'success', 'message' => 'Commande annulee avec succes.']
            : ['type' => 'error', 'message' => 'Erreur lors de l\'annulation.'];
        header('Location: index.php?page=mes_commandes');
        exit;
    }

    public function mesCommandes(): void {
        if (!isset($_SESSION['user_id'])) {
            header('Location: index.php?page=login');
            exit;
        }
        $userId = (int)$_SESSION['user_id'];
        $commandes = $this->commandeGetByUserId($userId);
        $flash = $_SESSION['flash'] ?? null;
        unset($_SESSION['flash']);
        require __DIR__ . '/../views/frontoffice/pharmacie/mes_commandes.php';
    }

    public function ajouterAuPanier(int $id): void {
        if (!isset($_SESSION['user_id'])) {
            header('Location: index.php?page=login');
            exit;
        }

        $produit = $this->produitGetById($id);
        if (!$produit || !(int)$produit['actif']) {
            $_SESSION['flash'] = ['type' => 'error', 'message' => 'Produit indisponible.'];
            header('Location: index.php?page=parapharmacie');
            exit;
        }

        $quantite = max(1, (int)($_POST['quantite'] ?? 1));
        $panier = $_SESSION['panier'] ?? [];
        $existant = (int)($panier[$id]['quantite'] ?? 0);
        $nouvelleQuantite = min((int)$produit['stock'], $existant + $quantite);

        if ($nouvelleQuantite <= 0) {
            $_SESSION['flash'] = ['type' => 'error', 'message' => 'Stock insuffisant pour ce produit.'];
            header('Location: index.php?page=parapharmacie');
            exit;
        }

        $panier[$id] = [
            'produit_id' => $id,
            'quantite' => $nouvelleQuantite,
        ];
        $_SESSION['panier'] = $panier;
        $_SESSION['flash'] = ['type' => 'success', 'message' => 'Produit ajouté au panier.'];

        header('Location: index.php?page=panier');
        exit;
    }

    public function panier(): void {
        if (!isset($_SESSION['user_id'])) {
            header('Location: index.php?page=login');
            exit;
        }

        $items = $this->getPanierItems();
        $promoCode = $_SESSION['panier_promo'] ?? '';
        $discountRate = $this->getPromoDiscount($promoCode);
        $sousTotal = 0.0;

        foreach ($items as $item) {
            $sousTotal += round((float)$item['prix_vente'] * (int)$item['quantite'], 2);
        }

        $reduction = round($sousTotal * ($discountRate / 100), 2);
        $totalNet = round($sousTotal - $reduction, 2);
        $tva = round($totalNet * 0.19, 2);
        $totalTtc = round($totalNet + $tva, 2);

        $flash = $_SESSION['flash'] ?? null;
        unset($_SESSION['flash']);
        require __DIR__ . '/../views/frontoffice/pharmacie/panier.php';
    }

    public function retirerDuPanier(int $id): void {
        if (!isset($_SESSION['user_id'])) {
            header('Location: index.php?page=login');
            exit;
        }

        $panier = $_SESSION['panier'] ?? [];
        unset($panier[$id]);
        $_SESSION['panier'] = $panier;
        $_SESSION['flash'] = ['type' => 'success', 'message' => 'Produit retiré du panier.'];
        header('Location: index.php?page=panier');
        exit;
    }

    public function viderPanier(): void {
        if (!isset($_SESSION['user_id'])) {
            header('Location: index.php?page=login');
            exit;
        }

        $_SESSION['panier'] = [];
        unset($_SESSION['panier_promo']);
        $_SESSION['flash'] = ['type' => 'success', 'message' => 'Panier vidé.'];
        header('Location: index.php?page=panier');
        exit;
    }

    public function appliquerCodePromoPanier(): void {
        if (!isset($_SESSION['user_id'])) {
            header('Location: index.php?page=login');
            exit;
        }

        $code = strtoupper(trim($_POST['code_promo'] ?? ''));
        if ($code !== '' && $this->getPromoDiscount($code) === 0.0) {
            $_SESSION['flash'] = ['type' => 'error', 'message' => 'Code promo invalide.'];
        } else {
            $_SESSION['panier_promo'] = $code;
            $_SESSION['flash'] = ['type' => 'success', 'message' => $code !== '' ? 'Code promo appliqué.' : 'Code promo retiré.'];
        }

        header('Location: index.php?page=panier');
        exit;
    }

    public function validerPanier(): void {
        if (!isset($_SESSION['user_id'])) {
            header('Location: index.php?page=login');
            exit;
        }

        $items = $this->getPanierItems();
        if (empty($items)) {
            $_SESSION['flash'] = ['type' => 'error', 'message' => 'Votre panier est vide.'];
            header('Location: index.php?page=panier');
            exit;
        }

        $userId = (int)$_SESSION['user_id'];
        $errors = $this->validateCommande(array_merge($_POST, ['user_id' => (string)$userId]));
        if (!empty($errors)) {
            $_SESSION['flash'] = ['type' => 'error', 'message' => implode(' | ', $errors)];
            header('Location: index.php?page=panier');
            exit;
        }

        $promoCode = strtoupper(trim($_POST['code_promo'] ?? ($_SESSION['panier_promo'] ?? '')));
        $discountRate = $this->getPromoDiscount($promoCode);

        $totalHt = 0.0;
        foreach ($items as $item) {
            if ($item['quantite'] > (int)$item['stock']) {
                $_SESSION['flash'] = ['type' => 'error', 'message' => 'Stock insuffisant pour ' . $item['nom'] . '.'];
                header('Location: index.php?page=panier');
                exit;
            }
            $totalHt += round(((float)$item['prix_vente'] * (int)$item['quantite']), 2);
        }

        $reduction = round($totalHt * ($discountRate / 100), 2);
        $totalHtNet = round($totalHt - $reduction, 2);
        $tva = round($totalHtNet * 0.19, 2);
        $totalTtc = round($totalHtNet + $tva, 2);

        $data = [
            'numero_commande' => $this->commandeGenerateNumero(),
            'user_id' => $userId,
            'adresse_livraison' => htmlspecialchars(trim($_POST['adresse_livraison'])),
            'ville' => htmlspecialchars(trim($_POST['ville'])),
            'code_postal' => htmlspecialchars(trim($_POST['code_postal'])),
            'telephone' => htmlspecialchars(trim($_POST['telephone'])),
            'mode_paiement' => $_POST['mode_paiement'] ?? 'carte',
            'total_ht' => $totalHtNet,
            'tva_montant' => $tva,
            'total_ttc' => $totalTtc,
            'statut' => 'en_attente',
            'notes' => htmlspecialchars(trim(($_POST['notes'] ?? '') . ($promoCode !== '' ? ' | Code promo: ' . $promoCode : ''))),
        ];

        $this->db->beginTransaction();
        $commandeId = $this->commandeCreate($data);
        if (!$commandeId) {
            $this->db->rollback();
            $_SESSION['flash'] = ['type' => 'error', 'message' => 'Erreur lors de la création de la commande.'];
            header('Location: index.php?page=panier');
            exit;
        }

        $stockAlerts = [];
        foreach ($items as $item) {
            $prixRemise = round((float)$item['prix_vente'] * (1 - $discountRate / 100), 2);
            $totalLigne = round($prixRemise * (int)$item['quantite'], 2);
            $produitIdLigne = (int)($item['produit_id'] ?? $item['id'] ?? 0);

            if (!$this->commandeAddDetail([
                'commande_id' => $commandeId,
                'produit_id' => $produitIdLigne,
                'quantite' => $item['quantite'],
                'prix_unitaire' => $prixRemise,
                'total' => $totalLigne,
            ])) {
                $this->db->rollback();
                $_SESSION['flash'] = ['type' => 'error', 'message' => 'Erreur lors de l’ajout des lignes de commande.'];
                header('Location: index.php?page=panier');
                exit;
            }

            $newStock = (int)$item['stock'] - (int)$item['quantite'];
            if (!$this->db->execute(
                "UPDATE produits SET stock = :stock, updated_at = NOW() WHERE id = :id",
                ['stock' => $newStock, 'id' => $produitIdLigne]
            )) {
                $this->db->rollback();
                $_SESSION['flash'] = ['type' => 'error', 'message' => 'Erreur lors de la mise à jour du stock.'];
                header('Location: index.php?page=panier');
                exit;
            }

            if ((int)$item['stock'] > (int)$item['stock_alerte'] && $newStock <= (int)$item['stock_alerte']) {
                $stockAlerts[] = $item;
            }
        }

        $this->db->commit();
        $_SESSION['panier'] = [];
        unset($_SESSION['panier_promo']);

        foreach ($stockAlerts as $item) {
            $this->envoyerAlerteStock($item, max(0, (int)$item['stock'] - (int)$item['quantite']));
        }

        $_SESSION['flash'] = ['type' => 'success', 'message' => 'Commande panier validée avec succès.'];
        header('Location: index.php?page=mes_commandes');
        exit;
    }

    // ================================
    // SQL - PRODUITS
    // ================================

    private function produitCreate(array $data): ?int {
        $sql = "INSERT INTO produits
                    (nom, reference, description, categorie_id, prix_achat, prix_vente,
                     tva, stock, stock_alerte, image, prescription, actif, created_at, updated_at)
                VALUES
                    (:nom, :reference, :description, :categorie_id, :prix_achat, :prix_vente,
                     :tva, :stock, :stock_alerte, :image, :prescription, :actif, NOW(), NOW())";
        $ok = $this->db->execute($sql, $data);
        return $ok ? $this->db->lastInsertId() : null;
    }

    private function produitGetById(int $id): ?array {
        $sql = "SELECT p.*, c.nom AS categorie_nom
                FROM produits p
                LEFT JOIN categories c ON p.categorie_id = c.id
                WHERE p.id = :id";
        $result = $this->db->query($sql, ['id' => $id]);
        return $result[0] ?? null;
    }

    private function produitGetAll(string $search = '', int $categorieId = 0, string $statut = ''): array {
        $where = "WHERE 1=1";
        $params = [];

        if ($search !== '') {
            $where .= " AND (p.nom LIKE :search OR p.reference LIKE :search OR p.description LIKE :search)";
            $params['search'] = '%' . $search . '%';
        }
        if ($categorieId > 0) {
            $where .= " AND p.categorie_id = :cat";
            $params['cat'] = $categorieId;
        }
        if ($statut === 'actif') {
            $where .= " AND p.actif = 1";
        } elseif ($statut === 'inactif') {
            $where .= " AND p.actif = 0";
        } elseif ($statut === 'alerte') {
            $where .= " AND p.stock <= p.stock_alerte";
        }

        $sql = "SELECT p.*, c.nom AS categorie_nom
                FROM produits p
                LEFT JOIN categories c ON p.categorie_id = c.id
                $where
                ORDER BY p.created_at DESC";
        return $this->db->query($sql, $params);
    }

    private function produitGetActifs(): array {
        $sql = "SELECT p.*, c.nom AS categorie_nom
                FROM produits p
                LEFT JOIN categories c ON p.categorie_id = c.id
                WHERE p.actif = 1
                ORDER BY p.nom ASC";
        return $this->db->query($sql);
    }

    private function produitUpdate(int $id, array $data): bool {
        $sql = "UPDATE produits SET
                    nom = :nom,
                    reference = :reference,
                    description = :description,
                    categorie_id = :categorie_id,
                    prix_achat = :prix_achat,
                    prix_vente = :prix_vente,
                    tva = :tva,
                    stock = :stock,
                    stock_alerte = :stock_alerte,
                    image = :image,
                    prescription = :prescription,
                    actif = :actif,
                    updated_at = NOW()
                WHERE id = :id";
        $data['id'] = $id;
        return $this->db->execute($sql, $data);
    }

    private function produitDelete(int $id): bool {
        $nb = (int)$this->db->queryScalar(
            "SELECT COUNT(*) FROM commande_details WHERE produit_id = :id",
            ['id' => $id]
        );
        if ($nb > 0) {
            return false;
        }
        return $this->db->execute("DELETE FROM produits WHERE id = :id", ['id' => $id]);
    }

    private function produitReferenceExists(string $ref, int $excludeId = 0): bool {
        $n = (int)$this->db->queryScalar(
            "SELECT COUNT(*) FROM produits WHERE reference = :ref AND id != :id",
            ['ref' => $ref, 'id' => $excludeId]
        );
        return $n > 0;
    }

    private function produitGetStats(): array {
        return [
            'total' => (int)$this->db->queryScalar("SELECT COUNT(*) FROM produits"),
            'actifs' => (int)$this->db->queryScalar("SELECT COUNT(*) FROM produits WHERE actif=1"),
            'rupture' => (int)$this->db->queryScalar("SELECT COUNT(*) FROM produits WHERE stock=0"),
            'alerte' => (int)$this->db->queryScalar("SELECT COUNT(*) FROM produits WHERE stock <= stock_alerte AND stock > 0"),
            'valeur_stock' => (float)$this->db->queryScalar("SELECT COALESCE(SUM(stock * prix_vente),0) FROM produits WHERE actif=1"),
        ];
    }

    private function getPanierItems(): array {
        $panier = $_SESSION['panier'] ?? [];
        $items = [];

        foreach ($panier as $line) {
            $produitId = (int)($line['produit_id'] ?? 0);
            $quantite = (int)($line['quantite'] ?? 0);
            if ($produitId <= 0 || $quantite <= 0) {
                continue;
            }

            $produit = $this->produitGetById($produitId);
            if (!$produit || !(int)$produit['actif']) {
                continue;
            }

            $produit['produit_id'] = (int)$produit['id'];
            $produit['quantite'] = min($quantite, (int)$produit['stock']);
            $items[] = $produit;
        }

        return $items;
    }

    private function getPromoDiscount(string $code): float {
        $promos = $this->loadPromos();
        $key = strtoupper(trim($code));
        return isset($promos[$key]) ? (float)$promos[$key] : 0.0;
    }

    // Promotions simples stockées en JSON pour gestion one-time codes
    private function loadPromos(): array {
        $file = __DIR__ . '/../config/promos.json';
        $defaults = [
            'PROMO10' => 10,
            'PROMO15' => 15,
            'BIENVENUE20' => 20,
        ];

        if (!file_exists($file)) {
            return $defaults;
        }

        $json = file_get_contents($file);
        $data = json_decode($json, true);
        $promos = is_array($data) ? $data : [];

        return $promos + $defaults;
    }

    private function loadStoredPromos(): array {
        $file = __DIR__ . '/../config/promos.json';
        if (!file_exists($file)) {
            return [];
        }

        $json = file_get_contents($file);
        $data = json_decode($json, true);
        return is_array($data) ? $data : [];
    }

    private function savePromos(array $promos): bool {
        $file = __DIR__ . '/../config/promos.json';
        $json = json_encode($promos, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        return (bool)file_put_contents($file, $json);
    }

    public function addPromo(): void {
        $this->auth->requireRole('admin');
        $code = strtoupper(trim($_POST['code'] ?? ''));
        $rate = (float)($_POST['rate'] ?? 0);
        if ($code === '' || $rate <= 0) {
            $_SESSION['flash'] = ['type' => 'error', 'message' => 'Code ou taux invalide.'];
            header('Location: index.php?page=produits_admin'); exit;
        }
        $promos = $this->loadStoredPromos();
        $promos[$code] = $rate;
        $this->savePromos($promos);
        $_SESSION['flash'] = ['type' => 'success', 'message' => 'Code promo ajouté.'];
        header('Location: index.php?page=produits_admin'); exit;
    }

    public function deletePromo(): void {
        $this->auth->requireRole('admin');
        $code = strtoupper(trim($_GET['code'] ?? ''));
        if ($code === '') { header('Location: index.php?page=produits_admin'); exit; }
        $promos = $this->loadStoredPromos();
        if (isset($promos[$code])) unset($promos[$code]);
        $this->savePromos($promos);
        $_SESSION['flash'] = ['type' => 'success', 'message' => 'Code promo supprimé.'];
        header('Location: index.php?page=produits_admin'); exit;
    }

    private function chatbotRecommanderProduits(string $query): array {
        $q = $this->normalizeText($query);
        $produits = $this->produitGetAll('', 0, 'actif');

        $synonyms = [
            'peau' => ['peau', 'derme', 'épiderme'],
            'seche' => ['sèche', 'sec', 'hydrat', 'hydrata', 'creme', 'lait', 'baume', 'nourr'],
            'grasse' => ['grasse', 'purifiant', 'nettoyant', 'matifiant', 'brillance'],
            'cheveux' => ['cheveux', 'capillaire', 'shampoo', 'shampo', 'serum', 'démêlant'],
            'bébé' => ['bébé', 'bebe', 'enfant', 'nouveau-né', 'maternité', 'douce'],
            'acne' => ['acné', 'acne', 'bouton', 'boutons', 'purifiant', 'derm'],
            'stress' => ['stress', 'sommeil', 'relax', 'calme', 'magnésium', 'magnesium'],
            'digestion' => ['digestion', 'ventre', 'ballonnement', 'transit', 'digest'],
            'soleil' => ['soleil', 'uv', 'sunscreen', 'spf', 'protection'],
        ];

        $tokens = array_values(array_filter(preg_split('/\s+/', $q) ?: []));
        $best = [];

        foreach ($produits as $produit) {
            $text = $this->normalizeText(($produit['nom'] ?? '') . ' ' . ($produit['description'] ?? '') . ' ' . ($produit['categorie_nom'] ?? ''));
            $score = 0;

            foreach ($tokens as $token) {
                foreach ($synonyms as $base => $words) {
                    if (in_array($token, array_map([$this, 'normalizeText'], $words), true) || str_contains($text, $token)) {
                        $score += 2;
                    }
                }
            }

            foreach ($synonyms as $words) {
                foreach ($words as $word) {
                    $wordNorm = $this->normalizeText($word);
                    if ($wordNorm !== '' && str_contains($q, $wordNorm) && str_contains($text, $wordNorm)) {
                        $score += 3;
                    }
                }
            }

            if ($score > 0) {
                $best[] = ['score' => $score, 'produit' => $produit];
            }
        }

        usort($best, fn($a, $b) => $b['score'] <=> $a['score']);
        $suggestions = array_map(fn($item) => $item['produit'], array_slice($best, 0, 3));

        if (empty($suggestions)) {
            $fallback = array_slice($produits, 0, 3);
            return ['Je n’ai pas trouvé de correspondance précise. Essaie avec des mots comme “peau sèche”, “cheveux”, “bébé” ou “acné”.', $fallback];
        }

        return ['Voici les produits les plus proches de ton besoin dans le catalogue.', $suggestions];
    }

    private function normalizeText(string $value): string {
        $value = mb_strtolower(trim($value));
        $value = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value) ?: $value;
        $value = preg_replace('/[^a-z0-9\s]/', ' ', $value);
        $value = preg_replace('/\s+/', ' ', $value);
        return trim($value);
    }

    private function envoyerAlerteStock(array $produit, int $nouveauStock): void {
        return;
    }

    // ================================
    // SQL - CATEGORIES
    // ================================

    private function categorieCreate(array $data): ?int {
        $sql = "INSERT INTO categories (nom, slug, description, image, parent_id, statut, created_at, updated_at)
                VALUES (:nom, :slug, :description, :image, :parent_id, :statut, NOW(), NOW())";
        $ok = $this->db->execute($sql, $data);
        return $ok ? $this->db->lastInsertId() : null;
    }

    private function categorieGetById(int $id): ?array {
        $sql = "SELECT c.*, p.nom AS parent_nom
                FROM categories c
                LEFT JOIN categories p ON c.parent_id = p.id
                WHERE c.id = :id";
        $result = $this->db->query($sql, ['id' => $id]);
        return $result[0] ?? null;
    }

    private function categorieGetAll(string $search = ''): array {
        $where = "WHERE 1=1";
        $params = [];
        if ($search !== '') {
            $where .= " AND (c.nom LIKE :search OR c.description LIKE :search)";
            $params['search'] = '%' . $search . '%';
        }

        $sql = "SELECT c.*,
                       p.nom AS parent_nom,
                       (SELECT COUNT(*) FROM produits WHERE categorie_id = c.id) AS nb_produits
                FROM categories c
                LEFT JOIN categories p ON c.parent_id = p.id
                $where
                ORDER BY c.nom ASC";
        return $this->db->query($sql, $params);
    }

    private function categorieGetActives(): array {
        return $this->db->query(
            "SELECT id, nom FROM categories WHERE statut = 'actif' ORDER BY nom ASC"
        );
    }

    private function categorieUpdate(int $id, array $data): bool {
        $sql = "UPDATE categories SET
                    nom = :nom,
                    slug = :slug,
                    description = :description,
                    image = :image,
                    parent_id = :parent_id,
                    statut = :statut,
                    updated_at = NOW()
                WHERE id = :id";
        $data['id'] = $id;
        return $this->db->execute($sql, $data);
    }

    private function categorieDelete(int $id): bool {
        $nb = (int)$this->db->queryScalar(
            "SELECT COUNT(*) FROM produits WHERE categorie_id = :id",
            ['id' => $id]
        );
        if ($nb > 0) {
            return false;
        }
        return $this->db->execute("DELETE FROM categories WHERE id = :id", ['id' => $id]);
    }

    private function categorieSlugExists(string $slug, int $excludeId = 0): bool {
        $n = (int)$this->db->queryScalar(
            "SELECT COUNT(*) FROM categories WHERE slug = :slug AND id != :id",
            ['slug' => $slug, 'id' => $excludeId]
        );
        return $n > 0;
    }

    private function categorieGetStats(): array {
        $total = (int)$this->db->queryScalar("SELECT COUNT(*) FROM categories");
        $actifs = (int)$this->db->queryScalar("SELECT COUNT(*) FROM categories WHERE statut='actif'");
        return [
            'total' => $total,
            'actives' => $actifs,
            'inactives' => $total - $actifs,
        ];
    }

    // ================================
    // SQL - COMMANDES
    // ================================

    private function commandeGenerateNumero(): string {
        return 'CMD-' . date('Y') . '-' . str_pad((string)rand(1, 9999), 4, '0', STR_PAD_LEFT);
    }

    private function commandeCreate(array $data): ?int {
        $sql = "INSERT INTO commandes
                    (numero_commande, user_id, adresse_livraison, ville, code_postal,
                     telephone, mode_paiement, total_ht, tva_montant, total_ttc, statut, notes, created_at, updated_at)
                VALUES
                    (:numero_commande, :user_id, :adresse_livraison, :ville, :code_postal,
                     :telephone, :mode_paiement, :total_ht, :tva_montant, :total_ttc, :statut, :notes, NOW(), NOW())";
        $ok = $this->db->execute($sql, $data);
        return $ok ? $this->db->lastInsertId() : null;
    }

    private function commandeAddDetail(array $data): bool {
        $amount = $data['total'] ?? $data['total_ligne'] ?? 0;
        $sql = "INSERT INTO commande_details (commande_id, produit_id, quantite, prix_unitaire, total_ligne)
                VALUES (:commande_id, :produit_id, :quantite, :prix_unitaire, :total_ligne)";
        return $this->db->execute($sql, [
            'commande_id' => $data['commande_id'],
            'produit_id' => $data['produit_id'],
            'quantite' => $data['quantite'],
            'prix_unitaire' => $data['prix_unitaire'],
            'total_ligne' => $amount,
        ]);
    }

    private function commandeGetById(int $id): ?array {
        $sql = "SELECT c.*,
                       u.nom AS user_nom, u.prenom AS user_prenom, u.email AS user_email
                FROM commandes c
                JOIN users u ON c.user_id = u.id
                WHERE c.id = :id";
        $result = $this->db->query($sql, ['id' => $id]);
        return $result[0] ?? null;
    }

    private function commandeGetDetails(int $commandeId): array {
        $sql = "SELECT cd.*, cd.total_ligne AS montant_ligne, p.nom AS produit_nom, p.reference
            FROM commande_details cd
            JOIN produits p ON cd.produit_id = p.id
            WHERE cd.commande_id = :id";
        return $this->db->query($sql, ['id' => $commandeId]);
    }

    private function commandeGetAll(string $search = '', string $statut = ''): array {
        $where = "WHERE 1=1";
        $params = [];

        if ($search !== '') {
            $where .= " AND (c.numero_commande LIKE :search OR u.nom LIKE :search OR u.prenom LIKE :search)";
            $params['search'] = '%' . $search . '%';
        }
        if ($statut !== '') {
            $where .= " AND c.statut = :statut";
            $params['statut'] = $statut;
        }

        $sql = "SELECT c.*,
                       u.nom AS user_nom, u.prenom AS user_prenom,
                       (SELECT COUNT(*) FROM commande_details WHERE commande_id = c.id) AS nb_articles
                FROM commandes c
                JOIN users u ON c.user_id = u.id
                $where
                ORDER BY c.created_at DESC";
        return $this->db->query($sql, $params);
    }

    private function commandeGetByUserId(int $userId): array {
        $sql = "SELECT c.*,
                       u.nom AS user_nom, u.prenom AS user_prenom,
                       (SELECT COUNT(*) FROM commande_details WHERE commande_id = c.id) AS nb_articles
                FROM commandes c
                JOIN users u ON c.user_id = u.id
                WHERE c.user_id = :user_id
                ORDER BY c.created_at DESC";
        return $this->db->query($sql, ['user_id' => $userId]);
    }

    private function commandeUpdateStatut(int $id, string $statut): bool {
        return $this->db->execute(
            "UPDATE commandes SET statut = :statut, updated_at = NOW() WHERE id = :id",
            ['statut' => $statut, 'id' => $id]
        );
    }

    private function commandeUpdate(int $id, array $data): bool {
        $sql = "UPDATE commandes SET
                    adresse_livraison = :adresse_livraison,
                    ville = :ville,
                    code_postal = :code_postal,
                    telephone = :telephone,
                    mode_paiement = :mode_paiement,
                    statut = :statut,
                    notes = :notes,
                    updated_at = NOW()
                WHERE id = :id";
        $data['id'] = $id;
        return $this->db->execute($sql, $data);
    }

    private function commandeDelete(int $id): bool {
        return $this->db->execute("DELETE FROM commandes WHERE id = :id", ['id' => $id]);
    }

    private function commandeGetStats(): array {
        return [
            'total' => (int)$this->db->queryScalar("SELECT COUNT(*) FROM commandes"),
            'en_attente' => (int)$this->db->queryScalar("SELECT COUNT(*) FROM commandes WHERE statut='en_attente'"),
            'confirmees' => (int)$this->db->queryScalar("SELECT COUNT(*) FROM commandes WHERE statut='confirmee'"),
            'en_preparation' => (int)$this->db->queryScalar("SELECT COUNT(*) FROM commandes WHERE statut='en_preparation'"),
            'expediees' => (int)$this->db->queryScalar("SELECT COUNT(*) FROM commandes WHERE statut='expediee'"),
            'livrees' => (int)$this->db->queryScalar("SELECT COUNT(*) FROM commandes WHERE statut='livree'"),
            'annulees' => (int)$this->db->queryScalar("SELECT COUNT(*) FROM commandes WHERE statut='annulee'"),
            'ca_total' => (float)$this->db->queryScalar("SELECT COALESCE(SUM(total_ttc),0) FROM commandes WHERE statut != 'annulee'"),
        ];
    }

    // ================================
    // VALIDATION
    // ================================

    private function validateProduit(array $post): array {
        $errors = [];
        if (empty(trim($post['nom'] ?? ''))) {
            $errors[] = 'Le nom du produit est obligatoire.';
        } elseif (strlen(trim($post['nom'])) < 2) {
            $errors[] = 'Le nom doit contenir au moins 2 caractères.';
        }

        if (empty(trim($post['reference'] ?? ''))) {
            $errors[] = 'La référence est obligatoire.';
        } elseif (!preg_match('/^[A-Z0-9\-]+$/', trim($post['reference']))) {
            $errors[] = 'Référence invalide (lettres majuscules, chiffres et tirets uniquement).';
        }

        $prixV = str_replace(',', '.', $post['prix_vente'] ?? '');
        if (!is_numeric($prixV) || (float)$prixV <= 0) {
            $errors[] = 'Le prix de vente doit être un nombre positif.';
        }

        $prixA = str_replace(',', '.', $post['prix_achat'] ?? '');
        if (!is_numeric($prixA) || (float)$prixA < 0) {
            $errors[] = 'Le prix d\'achat doit être un nombre positif ou zéro.';
        }

        if (!ctype_digit(strval($post['stock'] ?? '')) || (int)$post['stock'] < 0) {
            $errors[] = 'Le stock doit être un entier positif ou nul.';
        }

        return $errors;
    }

    private function validateCategorie(array $post): array {
        $errors = [];
        if (empty(trim($post['nom'] ?? ''))) {
            $errors[] = 'Le nom de la catégorie est obligatoire.';
        } elseif (strlen(trim($post['nom'])) < 2) {
            $errors[] = 'Le nom doit contenir au moins 2 caractères.';
        }
        return $errors;
    }

    private function validateCommande(array $post): array {
        $errors = [];

        if (!ctype_digit(strval($post['user_id'] ?? '')) || (int)$post['user_id'] <= 0) {
            $errors[] = 'ID utilisateur invalide.';
        }
        if (empty(trim($post['adresse_livraison'] ?? ''))) {
            $errors[] = 'L\'adresse de livraison est obligatoire.';
        }
        if (empty(trim($post['ville'] ?? ''))) {
            $errors[] = 'La ville est obligatoire.';
        } elseif (!preg_match('/^[a-zA-ZÀ-ÿ\s\-]+$/', trim($post['ville']))) {
            $errors[] = 'La ville ne doit contenir que des lettres.';
        }
        if (!preg_match('/^\d{4,5}$/', trim($post['code_postal'] ?? ''))) {
            $errors[] = 'Code postal invalide (4 ou 5 chiffres).';
        }
        if (!preg_match('/^[+\d\s]{8,15}$/', trim($post['telephone'] ?? ''))) {
            $errors[] = 'Numéro de téléphone invalide.';
        }

        return $errors;
    }

    private function validateCommandeEdit(array $post): array {
        return $this->validateCommande(array_merge($post, ['user_id' => '1']));
    }

    // ================================
    // HELPERS
    // ================================

    private function slugify(string $text): string {
        $text = strtolower(trim($text));
        $text = preg_replace('/[àáâãäå]/u', 'a', $text);
        $text = preg_replace('/[èéêë]/u', 'e', $text);
        $text = preg_replace('/[ìíîï]/u', 'i', $text);
        $text = preg_replace('/[òóôõö]/u', 'o', $text);
        $text = preg_replace('/[ùúûü]/u', 'u', $text);
        $text = preg_replace('/[ç]/u', 'c', $text);
        $text = preg_replace('/[^a-z0-9\-]/', '-', $text);
        $text = preg_replace('/-+/', '-', $text);
        return trim($text, '-');
    }

    private function notFound(): void {
        http_response_code(404);
        echo "<div style='text-align:center;padding:50px;font-family:sans-serif'>
              <h2>404 - Page introuvable</h2>
              <a href='index.php'>Retour à l'accueil</a></div>";
    }
}
