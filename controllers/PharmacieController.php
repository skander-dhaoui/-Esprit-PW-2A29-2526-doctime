<?php
// controllers/PharmacieController.php

require_once __DIR__ . '/../models/Produit.php';
require_once __DIR__ . '/../models/Categorie.php';
require_once __DIR__ . '/../models/Commande.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/AuthController.php';

use App\Models\Produit;
use App\Models\Categorie;
use App\Models\Commande;

class PharmacieController {

    private Produit    $produitModel;
    private Categorie  $categorieModel;
    private Commande   $commandeModel;
    private AuthController $auth;

    public function __construct() {
        $this->produitModel   = new Produit();
        $this->categorieModel = new Categorie();
        $this->commandeModel  = new Commande();
        $this->auth           = new AuthController();
    }

    // ══════════════════════════════════════════════════════════
    //  PRODUITS — BACKOFFICE
    // ══════════════════════════════════════════════════════════

    public function listProduits(): void {
        $this->auth->requireRole('admin');
        $search     = trim($_GET['search'] ?? '');
        $categorieId= (int)($_GET['categorie'] ?? 0);
        $statut     = $_GET['statut'] ?? '';

        $produits   = $this->produitModel->getAll($search, $categorieId, $statut);
        $categories = $this->categorieModel->getActives();
        $stats      = $this->produitModel->getStats();
        $flash      = $_SESSION['flash'] ?? null;
        unset($_SESSION['flash']);

        require __DIR__ . '/../views/backoffice/pharmacie/produits_list.php';
    }

    public function showCreateProduit(): void {
        $this->auth->requireRole('admin');
        $categories = $this->categorieModel->getActives();
        $flash      = $_SESSION['flash'] ?? null;
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

        $slug = isset($_POST['slug']) ? trim($_POST['slug']) : $this->generateSlug(trim($_POST['nom']));
        if ($this->produitModel->referenceExists($slug)) {
            $_SESSION['flash'] = ['type' => 'error', 'message' => "Le produit '$slug' existe déjà."];
            header('Location: index.php?page=produits_admin&action=create');
            exit;
        }

        $data = [
            'nom'          => htmlspecialchars(trim($_POST['nom'])),
            'slug'         => htmlspecialchars($slug),
            'description'  => htmlspecialchars(trim($_POST['description'] ?? '')),
            'categorie_id' => (int)$_POST['categorie_id'] ?: null,
            'prix'         => (float)str_replace(',', '.', $_POST['prix_vente'] ?? $_POST['prix'] ?? 0),
            'stock'        => (int)$_POST['stock'],
            'image'        => htmlspecialchars(trim($_POST['image'] ?? '')),
            'prescription' => isset($_POST['prescription']) ? 1 : 0,
            'status'       => isset($_POST['actif']) ? 'actif' : 'inactif',
        ];

        $id = $this->produitModel->create($data);
        if ($id) {
            $_SESSION['flash'] = ['type' => 'success', 'message' => 'Produit créé avec succès.'];
            header("Location: index.php?page=produits_admin&action=show&id=$id");
        } else {
            $_SESSION['flash'] = ['type' => 'error', 'message' => 'Erreur lors de la création.'];
            header('Location: index.php?page=produits_admin&action=create');
        }
        exit;
    }
    
    private function generateSlug(string $text): string {
        $text = strtolower(trim($text));
        $text = preg_replace('/[^a-z0-9]+/', '-', $text);
        return trim($text, '-');
    }

    public function showProduit(int $id): void {
        $this->auth->requireRole('admin');
        $produit = $this->produitModel->getById($id);
        if (!$produit) { $this->notFound(); return; }
        $flash = $_SESSION['flash'] ?? null;
        unset($_SESSION['flash']);
        require __DIR__ . '/../views/backoffice/pharmacie/produit_show.php';
    }

    public function editProduit(int $id): void {
        $this->auth->requireRole('admin');
        $produit    = $this->produitModel->getById($id);
        if (!$produit) { $this->notFound(); return; }
        $categories = $this->categorieModel->getActives();
        $flash      = $_SESSION['flash'] ?? null;
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

        $slug = isset($_POST['slug']) ? trim($_POST['slug']) : $this->generateSlug(trim($_POST['nom']));
        if ($this->produitModel->referenceExists($slug, $id)) {
            $_SESSION['flash'] = ['type' => 'error', 'message' => "Le produit '$slug' est déjà utilisé."];
            header("Location: index.php?page=produits_admin&action=edit&id=$id");
            exit;
        }

        $data = [
            'nom'          => htmlspecialchars(trim($_POST['nom'])),
            'slug'         => htmlspecialchars($slug),
            'description'  => htmlspecialchars(trim($_POST['description'] ?? '')),
            'categorie_id' => (int)$_POST['categorie_id'] ?: null,
            'prix'         => (float)str_replace(',', '.', $_POST['prix_vente'] ?? $_POST['prix'] ?? 0),
            'stock'        => (int)$_POST['stock'],
            'image'        => htmlspecialchars(trim($_POST['image'] ?? '')),
            'prescription' => isset($_POST['prescription']) ? 1 : 0,
            'status'       => isset($_POST['actif']) ? 'actif' : 'inactif',
        ];

        $ok = $this->produitModel->update($id, $data);
        $_SESSION['flash'] = $ok
            ? ['type' => 'success', 'message' => 'Produit mis à jour.']
            : ['type' => 'error',   'message' => 'Erreur lors de la mise à jour.'];
        header("Location: index.php?page=produits_admin&action=show&id=$id");
        exit;
    }

    public function deleteProduit(int $id): void {
        $this->auth->requireRole('admin');
        $ok = $this->produitModel->delete($id);
        if ($ok) {
            $_SESSION['flash'] = ['type' => 'success', 'message' => 'Produit supprimé.'];
        } else {
            $_SESSION['flash'] = ['type' => 'error', 'message' => 'Impossible de supprimer : ce produit est lié à des commandes.'];
        }
        header('Location: index.php?page=produits_admin');
        exit;
    }

    // ══════════════════════════════════════════════════════════
    //  CATEGORIES — BACKOFFICE
    // ══════════════════════════════════════════════════════════

    public function listCategories(): void {
        $this->auth->requireRole('admin');
        $search     = trim($_GET['search'] ?? '');
        $categories = $this->categorieModel->getAll($search);
        $stats      = $this->categorieModel->getStats();
        $flash      = $_SESSION['flash'] ?? null;
        unset($_SESSION['flash']);
        require __DIR__ . '/../views/backoffice/pharmacie/categories_list.php';
    }

    public function showCreateCategorie(): void {
        $this->auth->requireRole('admin');
        $parents = $this->categorieModel->getActives();
        $flash   = $_SESSION['flash'] ?? null;
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
        if ($this->categorieModel->slugExists($slug)) {
            $slug .= '-' . time();
        }

        $data = [
            'nom'        => htmlspecialchars(trim($_POST['nom'])),
            'slug'       => $slug,
            'description'=> htmlspecialchars(trim($_POST['description'] ?? '')),
            'image'      => htmlspecialchars(trim($_POST['image'] ?? '')),
            'parent_id'  => (int)($_POST['parent_id'] ?? 0) ?: null,
            'statut'     => $_POST['statut'] ?? 'actif',
        ];

        $id = $this->categorieModel->create($data);
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
        $categorie = $this->categorieModel->getById($id);
        if (!$categorie) { $this->notFound(); return; }
        $parents = $this->categorieModel->getActives();
        $flash   = $_SESSION['flash'] ?? null;
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
        if ($this->categorieModel->slugExists($slug, $id)) {
            $slug .= '-' . $id;
        }

        $data = [
            'nom'        => htmlspecialchars(trim($_POST['nom'])),
            'slug'       => $slug,
            'description'=> htmlspecialchars(trim($_POST['description'] ?? '')),
            'image'      => htmlspecialchars(trim($_POST['image'] ?? '')),
            'parent_id'  => (int)($_POST['parent_id'] ?? 0) ?: null,
            'statut'     => $_POST['statut'] ?? 'actif',
        ];

        $ok = $this->categorieModel->update($id, $data);
        $_SESSION['flash'] = $ok
            ? ['type' => 'success', 'message' => 'Catégorie mise à jour.']
            : ['type' => 'error',   'message' => 'Erreur lors de la mise à jour.'];
        header('Location: index.php?page=categories_admin');
        exit;
    }

    public function deleteCategorie(int $id): void {
        $this->auth->requireRole('admin');
        $ok = $this->categorieModel->delete($id);
        if ($ok) {
            $_SESSION['flash'] = ['type' => 'success', 'message' => 'Catégorie supprimée.'];
        } else {
            $_SESSION['flash'] = ['type' => 'error', 'message' => 'Impossible : des produits sont liés à cette catégorie.'];
        }
        header('Location: index.php?page=categories_admin');
        exit;
    }

    // ══════════════════════════════════════════════════════════
    //  COMMANDES — BACKOFFICE
    // ══════════════════════════════════════════════════════════

    public function listCommandes(): void {
        $this->auth->requireRole('admin');
        $search   = trim($_GET['search'] ?? '');
        $statut   = $_GET['statut'] ?? '';
        $commandes= $this->commandeModel->getAll($search, $statut);
        $stats    = $this->commandeModel->getStats();
        $flash    = $_SESSION['flash'] ?? null;
        unset($_SESSION['flash']);
        require __DIR__ . '/../views/backoffice/pharmacie/commandes_list.php';
    }

    public function showCommande(int $id): void {
        $this->auth->requireRole('admin');
        $commande = $this->commandeModel->getById($id);
        if (!$commande) { $this->notFound(); return; }
        $details = $this->commandeModel->getDetails($id);
        $flash   = $_SESSION['flash'] ?? null;
        unset($_SESSION['flash']);
        require __DIR__ . '/../views/backoffice/pharmacie/commande_show.php';
    }

    public function showCreateCommande(): void {
        $this->auth->requireRole('admin');
        $produits = $this->produitModel->getActifs();
        $flash    = $_SESSION['flash'] ?? null;
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

        $produitIds  = $_POST['produit_id']  ?? [];
        $quantites   = $_POST['quantite']    ?? [];
        $lignes = [];
        $totalHt = 0;

        foreach ($produitIds as $i => $pid) {
            $pid = (int)$pid;
            $qte = (int)($quantites[$i] ?? 0);
            if ($pid <= 0 || $qte <= 0) continue;
            $produit = $this->produitModel->getById($pid);
            if (!$produit) continue;
            $prixUnitaire = (float)$produit['prix_vente'];
            $totalLigne   = round($prixUnitaire * $qte, 2);
            $totalHt     += $totalLigne;
            $lignes[] = ['produit_id' => $pid, 'quantite' => $qte, 'prix_unitaire' => $prixUnitaire, 'total_ligne' => $totalLigne];
        }

        if (empty($lignes)) {
            $_SESSION['flash'] = ['type' => 'error', 'message' => 'Ajoutez au moins un produit valide.'];
            header('Location: index.php?page=commandes_admin&action=create');
            exit;
        }

        $tva        = round($totalHt * 0.19, 2);
        $totalTtc   = round($totalHt + $tva, 2);

        $data = [
            'numero_commande'  => $this->commandeModel->generateNumero(),
            'user_id'          => (int)$_POST['user_id'],
            'adresse_livraison'=> htmlspecialchars(trim($_POST['adresse_livraison'])),
            'ville'            => htmlspecialchars(trim($_POST['ville'])),
            'code_postal'      => htmlspecialchars(trim($_POST['code_postal'])),
            'telephone'        => htmlspecialchars(trim($_POST['telephone'])),
            'mode_paiement'    => $_POST['mode_paiement'] ?? 'carte',
            'total_ht'         => $totalHt,
            'tva_montant'      => $tva,
            'total_ttc'        => $totalTtc,
            'statut'           => $_POST['statut'] ?? 'en_attente',
            'notes'            => htmlspecialchars(trim($_POST['notes'] ?? '')),
        ];

        $id = $this->commandeModel->create($data);
        if ($id) {
            foreach ($lignes as $ligne) {
                $ligne['commande_id'] = $id;
                $this->commandeModel->addDetail($ligne);
            }
            $_SESSION['flash'] = ['type' => 'success', 'message' => 'Commande créée avec succès.'];
            header("Location: index.php?page=commandes_admin&action=show&id=$id");
        } else {
            $_SESSION['flash'] = ['type' => 'error', 'message' => 'Erreur lors de la création.'];
            header('Location: index.php?page=commandes_admin&action=create');
        }
        exit;
    }

    public function editCommande(int $id): void {
        $this->auth->requireRole('admin');
        $commande = $this->commandeModel->getById($id);
        if (!$commande) { $this->notFound(); return; }
        $details  = $this->commandeModel->getDetails($id);
        $flash    = $_SESSION['flash'] ?? null;
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
            'adresse_livraison'=> htmlspecialchars(trim($_POST['adresse_livraison'])),
            'ville'            => htmlspecialchars(trim($_POST['ville'])),
            'code_postal'      => htmlspecialchars(trim($_POST['code_postal'])),
            'telephone'        => htmlspecialchars(trim($_POST['telephone'])),
            'mode_paiement'    => $_POST['mode_paiement'] ?? 'carte',
            'statut'           => $_POST['statut'] ?? 'en_attente',
            'notes'            => htmlspecialchars(trim($_POST['notes'] ?? '')),
        ];

        $ok = $this->commandeModel->update($id, $data);
        $_SESSION['flash'] = $ok
            ? ['type' => 'success', 'message' => 'Commande mise à jour.']
            : ['type' => 'error',   'message' => 'Erreur lors de la mise à jour.'];
        header("Location: index.php?page=commandes_admin&action=show&id=$id");
        exit;
    }

    public function deleteCommande(int $id): void {
        $this->auth->requireRole('admin');
        $ok = $this->commandeModel->delete($id);
        $_SESSION['flash'] = $ok
            ? ['type' => 'success', 'message' => 'Commande supprimée.']
            : ['type' => 'error',   'message' => 'Erreur lors de la suppression.'];
        header('Location: index.php?page=commandes_admin');
        exit;
    }

    public function updateStatutCommande(int $id): void {
        $this->auth->requireRole('admin');
        $allowed = ['en_attente','confirmee','en_preparation','expediee','livree','annulee'];
        $statut  = $_POST['statut'] ?? '';
        if (!in_array($statut, $allowed)) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false]);
            exit;
        }
        $ok = $this->commandeModel->updateStatut($id, $statut);
        header('Content-Type: application/json');
        echo json_encode(['success' => $ok]);
        exit;
    }

    // ══════════════════════════════════════════════════════════
    //  FRONTOFFICE
    // ══════════════════════════════════════════════════════════

    public function pharmacieFront(): void {
        $search      = trim($_GET['search'] ?? '');
        $categorieId = (int)($_GET['categorie'] ?? 0);
        $produits    = $this->produitModel->getAll($search, $categorieId, 'actif');
        $categories  = $this->categorieModel->getActives();
        $flash       = $_SESSION['flash'] ?? null;
        unset($_SESSION['flash']);
        require __DIR__ . '/../views/frontoffice/pharmacie/catalogue.php';
    }

    public function produitDetail(int $id): void {
        $produit = $this->produitModel->getById($id);
        if (!$produit || ($produit['status'] ?? 'actif') !== 'actif') { $this->notFound(); return; }
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
        // Meme regles que le backoffice.
        $errors = $this->validateCommande(array_merge($_POST, ['user_id' => (string)$userId]));

        $produitId = (int)($_POST['produit_id'] ?? 0);
        $quantite  = (int)($_POST['quantite'] ?? 0);
        if ($produitId <= 0 || $quantite <= 0) {
            $errors[] = 'Produit ou quantite invalide.';
        }

        $produit = $this->produitModel->getById($produitId);
        if (!$produit || ($produit['status'] ?? 'actif') !== 'actif') {
            $errors[] = 'Produit indisponible.';
        } elseif ($quantite > (int)$produit['stock']) {
            $errors[] = 'Quantite demandee superieure au stock disponible.';
        }

        if (!empty($errors)) {
            // On renvoie l'utilisateur au produit pour corriger.
            $_SESSION['flash'] = ['type' => 'error', 'message' => implode(' | ', $errors)];
            $redirectId = $produitId > 0 ? $produitId : (int)($_GET['produit_id'] ?? 0);
            header('Location: index.php?page=produit_detail&id=' . $redirectId);
            exit;
        }

        $prixUnitaire = (float)$produit['prix_vente'];
        $totalHt      = round($prixUnitaire * $quantite, 2);
        $tva          = round($totalHt * 0.19, 2);
        $totalTtc     = round($totalHt + $tva, 2);

        $data = [
            'numero_commande'   => $this->commandeModel->generateNumero(),
            'user_id'           => $userId,
            'adresse_livraison' => htmlspecialchars(trim($_POST['adresse_livraison'])),
            'ville'             => htmlspecialchars(trim($_POST['ville'])),
            'code_postal'       => htmlspecialchars(trim($_POST['code_postal'])),
            'telephone'         => htmlspecialchars(trim($_POST['telephone'])),
            'mode_paiement'     => $_POST['mode_paiement'] ?? 'carte',
            'total_ht'          => $totalHt,
            'tva_montant'       => $tva,
            'total_ttc'         => $totalTtc,
            'statut'            => 'en_attente',
            'notes'             => htmlspecialchars(trim($_POST['notes'] ?? '')),
        ];

        $commandeId = $this->commandeModel->create($data);
        if (!$commandeId) {
            $_SESSION['flash'] = ['type' => 'error', 'message' => 'Erreur lors de la creation de la commande.'];
            header('Location: index.php?page=produit_detail&id=' . $produitId);
            exit;
        }

        $this->commandeModel->addDetail([
            'commande_id'   => $commandeId,
            'produit_id'    => $produitId,
            'quantite'      => $quantite,
            'prix_unitaire' => $prixUnitaire,
            'total_ligne'   => $totalHt,
        ]);

        $_SESSION['flash'] = ['type' => 'success', 'message' => 'Commande creee avec succes.'];
        header('Location: index.php?page=mes_commandes');
        exit;
    }

    public function editCommandeFront(int $id): void {
        if (!isset($_SESSION['user_id'])) {
            header('Location: index.php?page=login');
            exit;
        }

        $userId   = (int)$_SESSION['user_id'];
        $commande = $this->commandeModel->getById($id);
        if (!$commande || (int)$commande['user_id'] !== $userId) {
            $this->notFound();
            return;
        }

        if (!in_array($commande['statut'], ['en_attente', 'confirmee'], true)) {
            $_SESSION['flash'] = ['type' => 'error', 'message' => 'Cette commande ne peut plus etre modifiee.'];
            header('Location: index.php?page=mes_commandes');
            exit;
        }

        $details = $this->commandeModel->getDetails($id);
        $flash   = $_SESSION['flash'] ?? null;
        unset($_SESSION['flash']);
        require __DIR__ . '/../views/frontoffice/pharmacie/commande_edit.php';
    }

    public function updateCommandeFront(int $id): void {
        if (!isset($_SESSION['user_id'])) {
            header('Location: index.php?page=login');
            exit;
        }

        $userId   = (int)$_SESSION['user_id'];
        $commande = $this->commandeModel->getById($id);
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
            'ville'             => htmlspecialchars(trim($_POST['ville'])),
            'code_postal'       => htmlspecialchars(trim($_POST['code_postal'])),
            'telephone'         => htmlspecialchars(trim($_POST['telephone'])),
            'mode_paiement'     => $_POST['mode_paiement'] ?? 'carte',
            'statut'            => $commande['statut'],
            'notes'             => htmlspecialchars(trim($_POST['notes'] ?? '')),
        ];

        $ok = $this->commandeModel->update($id, $data);
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

        $userId   = (int)$_SESSION['user_id'];
        $commande = $this->commandeModel->getById($id);
        if (!$commande || (int)$commande['user_id'] !== $userId) {
            $this->notFound();
            return;
        }

        if (!in_array($commande['statut'], ['en_attente', 'confirmee'], true)) {
            // Trop tard pour annuler si deja en traitement.
            $_SESSION['flash'] = ['type' => 'error', 'message' => 'Cette commande ne peut pas etre annulee.'];
            header('Location: index.php?page=mes_commandes');
            exit;
        }

        $ok = $this->commandeModel->updateStatut($id, 'annulee');
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
        $userId   = (int)$_SESSION['user_id'];
        $commandes= $this->commandeModel->getByUserId($userId);
        $flash     = $_SESSION['flash'] ?? null;
        unset($_SESSION['flash']);
        require __DIR__ . '/../views/frontoffice/pharmacie/mes_commandes.php';
    }
    //  Validation avec javascript

    private function validateProduit(array $post): array {
        $errors = [];
        if (empty(trim($post['nom'] ?? '')))
            $errors[] = 'Le nom du produit est obligatoire.';
        elseif (strlen(trim($post['nom'])) < 2)
            $errors[] = 'Le nom doit contenir au moins 2 caractères.';

        // Accept either prix_vente/prix_achat or just prix
        $prix = str_replace(',', '.', $post['prix_vente'] ?? $post['prix'] ?? '');
        if (!is_numeric($prix) || (float)$prix <= 0)
            $errors[] = 'Le prix doit être un nombre positif.';

        if (!ctype_digit(strval($post['stock'] ?? '')) || (int)$post['stock'] < 0)
            $errors[] = 'Le stock doit être un entier positif ou nul.';

        return $errors;
    }

    private function validateCategorie(array $post): array {
        $errors = [];
        if (empty(trim($post['nom'] ?? '')))
            $errors[] = 'Le nom de la catégorie est obligatoire.';
        elseif (strlen(trim($post['nom'])) < 2)
            $errors[] = 'Le nom doit contenir au moins 2 caractères.';
        return $errors;
    }

    private function validateCommande(array $post): array {
        $errors = [];
        // Validation simple des infos client/livraison.
        if (!ctype_digit(strval($post['user_id'] ?? '')) || (int)$post['user_id'] <= 0)
            $errors[] = 'ID utilisateur invalide.';
        if (empty(trim($post['adresse_livraison'] ?? '')))
            $errors[] = 'L\'adresse de livraison est obligatoire.';
        if (empty(trim($post['ville'] ?? '')))
            $errors[] = 'La ville est obligatoire.';
        elseif (!preg_match('/^[a-zA-ZÀ-ÿ\s\-]+$/', trim($post['ville'])))
            $errors[] = 'La ville ne doit contenir que des lettres.';
        if (!preg_match('/^\d{4,5}$/', trim($post['code_postal'] ?? '')))
            $errors[] = 'Code postal invalide (4 ou 5 chiffres).';
        if (!preg_match('/^[+\d\s]{8,15}$/', trim($post['telephone'] ?? '')))
            $errors[] = 'Numéro de téléphone invalide.';
        return $errors;
    }

    private function validateCommandeEdit(array $post): array {
        return $this->validateCommande(array_merge($post, ['user_id' => '1']));
    }

    // ── Helpers ───────────────────────────────────────────────────────────
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
              <h2>404 — Page introuvable</h2>
              <a href='index.php'>Retour à l'accueil</a></div>";
    }
}