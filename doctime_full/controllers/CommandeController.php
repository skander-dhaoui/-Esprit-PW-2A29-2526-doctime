<?php

require_once __DIR__ . '/../models/Commande.php';
require_once __DIR__ . '/../models/CommandeLigne.php';
require_once __DIR__ . '/../models/Produit.php';
require_once __DIR__ . '/../models/Client.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/AuthController.php';

class CommandeController {

    private Commande $commandeModel;
    private CommandeLigne $commandeLigneModel;
    private Produit $produitModel;
    private Client $clientModel;
    private AuthController $auth;
    private Database $db;

    public function __construct() {
        $this->commandeModel = new Commande();
        $this->commandeLigneModel = new CommandeLigne();
        $this->produitModel = new Produit();
        $this->clientModel = new Client();
        $this->auth = new AuthController();
        $this->db = Database::getInstance();
    }

    // ─────────────────────────────────────────
    //  Lister toutes les commandes (admin)
    // ─────────────────────────────────────────
    public function index(): void {
        $this->auth->requireRole('admin');

        try {
            $page = (int)($_GET['page'] ?? 1);
            $perPage = 20;
            $offset = ($page - 1) * $perPage;

            $filter = $_GET['filter'] ?? 'all'; // all, en attente, confirmée, expédiée, livrée, annulée
            $search = $_GET['search'] ?? '';
            $dateDebut = $_GET['date_debut'] ?? '';
            $dateFin = $_GET['date_fin'] ?? '';

            $commandes = $this->commandeModel->getAll($filter, $offset, $perPage, $search, $dateDebut, $dateFin);
            $total = $this->commandeModel->countAll($filter, $search, $dateDebut, $dateFin);
            $totalPages = ceil($total / $perPage);

            $stats = [
                'total_commandes' => $this->commandeModel->countAll('all'),
                'commandes_en_attente' => $this->commandeModel->countByStatus('en attente'),
                'commandes_confirmees' => $this->commandeModel->countByStatus('confirmée'),
                'total_montant' => $this->commandeModel->getTotalMontant(),
                'montant_mois' => $this->commandeModel->getTotalMontantMois(),
            ];

            $flash = $_SESSION['flash'] ?? null;
            unset($_SESSION['flash']);

            require_once __DIR__ . '/../views/backoffice/commande_list.php';
        } catch (Exception $e) {
            error_log('Erreur CommandeController::index - ' . $e->getMessage());
            $this->setFlash('error', 'Erreur lors du chargement des commandes.');
            header('Location: /admin/dashboard');
            exit;
        }
    }

    // ─────────────────────────────────────────
    //  Afficher une commande
    // ─────────────────────────────────────────
    public function show(int $id): void {
        $this->auth->requireRole(['admin', 'client']);

        try {
            $commande = $this->commandeModel->getById($id);

            if (!$commande) {
                http_response_code(404);
                die('Commande introuvable.');
            }

            // Vérifier les permissions
            $userId = (int)$_SESSION['user_id'];
            $userRole = $_SESSION['user_role'];

            if ($userRole === 'client' && (int)$commande['client_id'] !== $userId) {
                http_response_code(403);
                die('Accès refusé.');
            }

            $lignes = $this->commandeLigneModel->getByCommande($id);
            $client = $this->clientModel->findById($commande['client_id']);
            $historique = $this->commandeModel->getHistorique($id);
            $flash = $_SESSION['flash'] ?? null;
            unset($_SESSION['flash']);

            require_once __DIR__ . '/../views/commande_show.php';
        } catch (Exception $e) {
            error_log('Erreur CommandeController::show - ' . $e->getMessage());
            http_response_code(500);
            die('Erreur lors du chargement.');
        }
    }

    // ─────────────────────────────────────────
    //  Créer une commande (formulaire)
    // ─────────────────────────────────────────
    public function create(): void {
        $this->auth->requireRole('client');

        try {
            $csrfToken = $this->generateCsrfToken();
            $produits = $this->produitModel->getActive();
            $categories = $this->produitModel->getCategories();
            $panier = $_SESSION['panier'] ?? [];
            $old = $_SESSION['old'] ?? null;
            $flash = $_SESSION['flash'] ?? null;
            unset($_SESSION['old'], $_SESSION['flash']);

            require_once __DIR__ . '/../views/commande_form.php';
        } catch (Exception $e) {
            error_log('Erreur CommandeController::create - ' . $e->getMessage());
            $this->setFlash('error', 'Erreur lors du chargement du formulaire.');
            header('Location: /produits');
            exit;
        }
    }

    // ─────────────────────────────────────────
    //  Ajouter un produit au panier
    // ─────────────────────────────────────────
    public function addToCart(int $produitId): void {
        $this->auth->requireRole('client');

        try {
            $produit = $this->produitModel->getById($produitId);

            if (!$produit) {
                echo json_encode(['error' => 'Produit introuvable']);
                exit;
            }

            if ($produit['stock'] <= 0) {
                echo json_encode(['error' => 'Produit en rupture de stock']);
                exit;
            }

            $quantite = (int)($_POST['quantite'] ?? 1);

            if ($quantite < 1 || $quantite > $produit['stock']) {
                echo json_encode(['error' => 'Quantité invalide']);
                exit;
            }

            // Initialiser le panier
            if (!isset($_SESSION['panier'])) {
                $_SESSION['panier'] = [];
            }

            $panierKey = "produit_$produitId";

            if (isset($_SESSION['panier'][$panierKey])) {
                $_SESSION['panier'][$panierKey]['quantite'] += $quantite;
            } else {
                $_SESSION['panier'][$panierKey] = [
                    'produit_id' => $produitId,
                    'quantite' => $quantite,
                    'prix_unitaire' => $produit['prix_vente'],
                    'nom' => $produit['nom'],
                ];
            }

            $total = $this->calculateCartTotal();

            echo json_encode([
                'success' => true,
                'message' => 'Produit ajouté au panier',
                'cart_count' => count($_SESSION['panier']),
                'total' => $total,
            ]);
            exit;
        } catch (Exception $e) {
            error_log('Erreur addToCart - ' . $e->getMessage());
            echo json_encode(['error' => 'Erreur serveur']);
            exit;
        }
    }

    // ─────────────────────────────────────────
    //  Afficher le panier
    // ─────────────────────────────────────────
    public function cart(): void {
        $this->auth->requireRole('client');

        try {
            $panier = $_SESSION['panier'] ?? [];
            $total = $this->calculateCartTotal();
            $flash = $_SESSION['flash'] ?? null;
            unset($_SESSION['flash']);

            require_once __DIR__ . '/../views/cart.php';
        } catch (Exception $e) {
            error_log('Erreur CommandeController::cart - ' . $e->getMessage());
            $this->setFlash('error', 'Erreur lors du chargement.');
            header('Location: /produits');
            exit;
        }
    }

    // ─────────────────────────────────────────
    //  Mettre à jour la quantité du panier
    // ─────────────────────────────────────────
    public function updateCart(): void {
        $this->auth->requireRole('client');

        try {
            $panierKey = $_POST['panier_key'] ?? null;
            $quantite = (int)($_POST['quantite'] ?? 0);

            if (!$panierKey || $quantite < 1) {
                echo json_encode(['error' => 'Données invalides']);
                exit;
            }

            if (!isset($_SESSION['panier'][$panierKey])) {
                echo json_encode(['error' => 'Article non trouvé dans le panier']);
                exit;
            }

            $produitId = $_SESSION['panier'][$panierKey]['produit_id'];
            $produit = $this->produitModel->getById($produitId);

            if ($quantite > $produit['stock']) {
                echo json_encode(['error' => 'Stock insuffisant']);
                exit;
            }

            $_SESSION['panier'][$panierKey]['quantite'] = $quantite;
            $total = $this->calculateCartTotal();

            echo json_encode([
                'success' => true,
                'message' => 'Panier mis à jour',
                'total' => $total,
            ]);
            exit;
        } catch (Exception $e) {
            error_log('Erreur updateCart - ' . $e->getMessage());
            echo json_encode(['error' => 'Erreur serveur']);
            exit;
        }
    }

    // ─────────────────────────────────────────
    //  Retirer du panier
    // ─────────────────────────────────────────
    public function removeFromCart(): void {
        $this->auth->requireRole('client');

        try {
            $panierKey = $_POST['panier_key'] ?? null;

            if (!$panierKey || !isset($_SESSION['panier'][$panierKey])) {
                echo json_encode(['error' => 'Article non trouvé']);
                exit;
            }

            unset($_SESSION['panier'][$panierKey]);
            $total = $this->calculateCartTotal();

            echo json_encode([
                'success' => true,
                'message' => 'Article retiré du panier',
                'total' => $total,
                'cart_count' => count($_SESSION['panier']),
            ]);
            exit;
        } catch (Exception $e) {
            error_log('Erreur removeFromCart - ' . $e->getMessage());
            echo json_encode(['error' => 'Erreur serveur']);
            exit;
        }
    }

    // ─────────────────────────────────────────
    //  Vider le panier
    // ─────────────────────────────────────────
    public function clearCart(): void {
        $this->auth->requireRole('client');

        try {
            $_SESSION['panier'] = [];

            $this->setFlash('success', 'Panier vidé.');
            header('Location: /panier');
            exit;
        } catch (Exception $e) {
            error_log('Erreur clearCart - ' . $e->getMessage());
            $this->setFlash('error', 'Erreur lors du vidage.');
            header('Location: /panier');
            exit;
        }
    }

    // ─────────────────────────────────────────
    //  Enregistrer une commande
    // ─────────────────────────────────────────
    public function store(): void {
        $this->auth->requireRole('client');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /panier');
            exit;
        }

        if (!$this->verifyCsrfToken($_POST['csrf_token'] ?? '')) {
            $this->setFlash('error', 'Erreur de sécurité.');
            header('Location: /commandes/create');
            exit;
        }

        try {
            $panier = $_SESSION['panier'] ?? [];

            if (empty($panier)) {
                $this->setFlash('error', 'Votre panier est vide.');
                header('Location: /panier');
                exit;
            }

            $clientId = (int)$_SESSION['user_id'];
            $client = $this->clientModel->findByUserId($clientId);

            if (!$client) {
                throw new Exception('Profil client introuvable.');
            }

            // Vérifier le stock pour chaque produit
            foreach ($panier as $item) {
                $produit = $this->produitModel->getById($item['produit_id']);
                if ($item['quantite'] > $produit['stock']) {
                    $this->setFlash('error', "Stock insuffisant pour {$produit['nom']}");
                    header('Location: /panier');
                    exit;
                }
            }

            $montantTotal = $this->calculateCartTotal();

            $data = [
                'client_id' => $clientId,
                'numero' => $this->generateNumeroCommande(),
                'date_commande' => date('Y-m-d H:i:s'),
                'statut' => 'en attente',
                'montant_total' => $montantTotal,
                'montant_ht' => $montantTotal / 1.20, // Supposant 20% de TVA
                'tva' => $montantTotal - ($montantTotal / 1.20),
                'adresse_livraison' => htmlspecialchars(trim($_POST['adresse_livraison'] ?? ''), ENT_QUOTES, 'UTF-8'),
                'code_postal' => htmlspecialchars(trim($_POST['code_postal'] ?? ''), ENT_QUOTES, 'UTF-8'),
                'ville' => htmlspecialchars(trim($_POST['ville'] ?? ''), ENT_QUOTES, 'UTF-8'),
                'telephone' => htmlspecialchars(trim($_POST['telephone'] ?? ''), ENT_QUOTES, 'UTF-8'),
                'notes' => htmlspecialchars(trim($_POST['notes'] ?? ''), ENT_QUOTES, 'UTF-8'),
                'mode_paiement' => $_POST['mode_paiement'] ?? 'virement',
            ];

            $errors = $this->validateCommande($data);

            if (!empty($errors)) {
                $this->setFlash('error', implode('<br>', $errors));
                $_SESSION['old'] = $data;
                header('Location: /commandes/create');
                exit;
            }

            $commandeId = $this->commandeModel->create($data);

            if (!$commandeId) {
                throw new Exception('Erreur lors de la création de la commande.');
            }

            // Créer les lignes de commande
            foreach ($panier as $item) {
                $produit = $this->produitModel->getById($item['produit_id']);
                
                $this->commandeLigneModel->create([
                    'commande_id' => $commandeId,
                    'produit_id' => $item['produit_id'],
                    'quantite' => $item['quantite'],
                    'prix_unitaire' => $item['prix_unitaire'],
                    'montant_ligne' => $item['quantite'] * $item['prix_unitaire'],
                ]);

                // Mettre à jour le stock
                $this->produitModel->decrementStock($item['produit_id'], $item['quantite']);
            }

            $this->logAction($clientId, 'Création commande', "Commande #$commandeId créée - Montant: {$data['montant_total']}€");

            // Envoyer email de confirmation
            $this->sendConfirmationEmail($client, $commandeId, $data);

            // Vider le panier
            $_SESSION['panier'] = [];

            $this->setFlash('success', 'Commande créée avec succès. Un email de confirmation a été envoyé.');
            header('Location: /commandes/' . $commandeId);
            exit;
        } catch (Exception $e) {
            error_log('Erreur CommandeController::store - ' . $e->getMessage());
            $this->setFlash('error', 'Erreur lors de la création de la commande.');
            header('Location: /panier');
            exit;
        }
    }

    // ─────────────────────────────────────────
    //  Éditer une commande (statut)
    // ─────────────────────────────────────────
    public function edit(int $id): void {
        $this->auth->requireRole('admin');

        try {
            $commande = $this->commandeModel->getById($id);

            if (!$commande) {
                http_response_code(404);
                die('Commande introuvable.');
            }

            $csrfToken = $this->generateCsrfToken();
            $statuts = ['en attente', 'confirmée', 'expédiée', 'livrée', 'annulée'];
            $flash = $_SESSION['flash'] ?? null;
            unset($_SESSION['flash']);

            require_once __DIR__ . '/../views/backoffice/commande_form_edit.php';
        } catch (Exception $e) {
            error_log('Erreur CommandeController::edit - ' . $e->getMessage());
            $this->setFlash('error', 'Erreur lors du chargement.');
            header('Location: /admin/commandes');
            exit;
        }
    }

    // ─────────────────────────────────────────
    //  Mettre à jour une commande (admin)
    // ─────────────────────────────────────────
    public function update(int $id): void {
        $this->auth->requireRole('admin');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: /admin/commandes/$id/edit");
            exit;
        }

        if (!$this->verifyCsrfToken($_POST['csrf_token'] ?? '')) {
            $this->setFlash('error', 'Erreur de sécurité.');
            header("Location: /admin/commandes/$id/edit");
            exit;
        }

        try {
            $commande = $this->commandeModel->getById($id);

            if (!$commande) {
                http_response_code(404);
                die('Commande introuvable.');
            }

            $nouveauStatut = $_POST['statut'] ?? 'en attente';
            $notes = htmlspecialchars(trim($_POST['notes'] ?? ''), ENT_QUOTES, 'UTF-8');

            if (!in_array($nouveauStatut, ['en attente', 'confirmée', 'expédiée', 'livrée', 'annulée'])) {
                $this->setFlash('error', 'Statut invalide.');
                header("Location: /admin/commandes/$id/edit");
                exit;
            }

            $this->commandeModel->update($id, [
                'statut' => $nouveauStatut,
                'notes_admin' => $notes,
            ]);

            // Enregistrer le changement dans l'historique
            $this->commandeModel->addHistorique($id, $_SESSION['user_id'], "Changement statut: {$commande['statut']} → $nouveauStatut", $nouveauStatut);

            $this->logAction($_SESSION['user_id'], 'Modification commande', "Commande #$id - Statut: $nouveauStatut");

            // Envoyer email si livraison
            if ($nouveauStatut === 'expédiée' || $nouveauStatut === 'livrée') {
                $this->sendStatusEmail($commande, $nouveauStatut);
            }

            $this->setFlash('success', 'Commande mise à jour.');
            header("Location: /admin/commandes/$id");
            exit;
        } catch (Exception $e) {
            error_log('Erreur CommandeController::update - ' . $e->getMessage());
            $this->setFlash('error', 'Erreur lors de la mise à jour.');
            header("Location: /admin/commandes/$id/edit");
            exit;
        }
    }

    // ─────────────────────────────────────────
    //  Annuler une commande
    // ─────────────────────────────────────────
    public function cancel(int $id): void {
        $this->auth->requireRole(['admin', 'client']);

        try {
            $commande = $this->commandeModel->getById($id);

            if (!$commande) {
                http_response_code(404);
                die('Commande introuvable.');
            }

            $userId = (int)$_SESSION['user_id'];
            $userRole = $_SESSION['user_role'];

            if ($userRole === 'client' && (int)$commande['client_id'] !== $userId) {
                http_response_code(403);
                die('Accès refusé.');
            }

            if (in_array($commande['statut'], ['livrée', 'annulée'])) {
                $this->setFlash('error', 'Impossible d\'annuler cette commande.');
                header("Location: /commandes/$id");
                exit;
            }

            $raison = htmlspecialchars(trim($_POST['raison'] ?? ''), ENT_QUOTES, 'UTF-8');

            // Restaurer le stock
            $lignes = $this->commandeLigneModel->getByCommande($id);
            foreach ($lignes as $ligne) {
                $this->produitModel->incrementStock($ligne['produit_id'], $ligne['quantite']);
            }

            $this->commandeModel->update($id, [
                'statut' => 'annulée',
                'raison_annulation' => $raison,
                'date_annulation' => date('Y-m-d H:i:s'),
            ]);

            $this->commandeModel->addHistorique($id, $userId, "Annulation: $raison", 'annulée');

            $this->logAction($userId, 'Annulation commande', "Commande #$id annulée - Raison: $raison");

            $this->setFlash('success', 'Commande annulée.');
            header("Location: /commandes/$id");
            exit;
        } catch (Exception $e) {
            error_log('Erreur CommandeController::cancel - ' . $e->getMessage());
            $this->setFlash('error', 'Erreur lors de l\'annulation.');
            header("Location: /commandes/$id");
            exit;
        }
    }

    // ─────────────────────────────────────────
    //  Générer facture PDF
    // ─────────────────────────────────────────
    public function generateInvoice(int $id): void {
        $this->auth->requireRole(['admin', 'client']);

        try {
            $commande = $this->commandeModel->getById($id);

            if (!$commande) {
                http_response_code(404);
                die('Commande introuvable.');
            }

            $userId = (int)$_SESSION['user_id'];
            $userRole = $_SESSION['user_role'];

            if ($userRole === 'client' && (int)$commande['client_id'] !== $userId) {
                http_response_code(403);
                die('Accès refusé.');
            }

            $lignes = $this->commandeLigneModel->getByCommande($id);
            $client = $this->clientModel->findById($commande['client_id']);

            // Inclure la classe PDF (exemple avec TCPDF ou FPDF)
            require_once __DIR__ . '/../../libraries/FPDF/fpdf.php';

            $pdf = new FPDF();
            $pdf->AddPage();
            $pdf->SetFont('Arial', 'B', 16);

            // En-tête
            $pdf->Cell(0, 10, 'FACTURE', 0, 1, 'C');
            $pdf->SetFont('Arial', '', 10);
            $pdf->Ln(10);

            $pdf->SetFont('Arial', 'B', 10);
            $pdf->Cell(100, 10, 'Client:', 0, 0);
            $pdf->SetFont('Arial', '', 10);
            $pdf->Cell(0, 10, $client['nom'] . ' ' . $client['prenom'], 0, 1);

            $pdf->SetFont('Arial', 'B', 10);
            $pdf->Cell(100, 10, 'Numéro Commande:', 0, 0);
            $pdf->SetFont('Arial', '', 10);
            $pdf->Cell(0, 10, $commande['numero'], 0, 1);

            $pdf->SetFont('Arial', 'B', 10);
            $pdf->Cell(100, 10, 'Date:', 0, 0);
            $pdf->SetFont('Arial', '', 10);
            $pdf->Cell(0, 10, date('d/m/Y', strtotime($commande['date_commande'])), 0, 1);

            $pdf->Ln(10);

            // Tableau articles
            $pdf->SetFont('Arial', 'B', 10);
            $pdf->Cell(80, 10, 'Produit', 1, 0, 'L');
            $pdf->Cell(30, 10, 'Quantité', 1, 0, 'C');
            $pdf->Cell(30, 10, 'P.U.', 1, 0, 'R');
            $pdf->Cell(30, 10, 'Total', 1, 1, 'R');

            $pdf->SetFont('Arial', '', 9);
            foreach ($lignes as $ligne) {
                $produit = $this->produitModel->getById($ligne['produit_id']);
                $pdf->Cell(80, 10, substr($produit['nom'], 0, 30), 1, 0, 'L');
                $pdf->Cell(30, 10, $ligne['quantite'], 1, 0, 'C');
                $pdf->Cell(30, 10, number_format($ligne['prix_unitaire'], 2, ',', ' ') . '€', 1, 0, 'R');
                $pdf->Cell(30, 10, number_format($ligne['montant_ligne'], 2, ',', ' ') . '€', 1, 1, 'R');
            }

            // Totaux
            $pdf->SetFont('Arial', 'B', 10);
            $pdf->Cell(140, 10, 'Total HT:', 1, 0, 'R');
            $pdf->Cell(30, 10, number_format($commande['montant_ht'], 2, ',', ' ') . '€', 1, 1, 'R');

            $pdf->Cell(140, 10, 'TVA (20%):', 1, 0, 'R');
            $pdf->Cell(30, 10, number_format($commande['tva'], 2, ',', ' ') . '€', 1, 1, 'R');

            $pdf->SetFont('Arial', 'B', 12);
            $pdf->Cell(140, 12, 'Total TTC:', 1, 0, 'R');
            $pdf->Cell(30, 12, number_format($commande['montant_total'], 2, ',', ' ') . '€', 1, 1, 'R');

            $this->logAction($userId, 'Export facture PDF', "Facture commande #$id");

            $pdf->Output('D', 'Facture_' . $commande['numero'] . '.pdf');
            exit;
        } catch (Exception $e) {
            error_log('Erreur generateInvoice - ' . $e->getMessage());
            $this->setFlash('error', 'Erreur lors de la génération de la facture.');
            header("Location: /commandes/$id");
            exit;
        }
    }

    // ─────────────────────────────────────────
    //  Mes commandes (client)
    // ─────────────────────────────────────────
    public function myCommandes(): void {
        $this->auth->requireRole('client');

        try {
            $clientId = (int)$_SESSION['user_id'];
            $filter = $_GET['filter'] ?? 'all'; // all, en attente, confirmée, expédiée, livrée, annulée
            $sort = $_GET['sort'] ?? 'recent';

            $commandes = match ($sort) {
                'ancien' => $this->commandeModel->getByClientOrderByOldest($clientId, $filter),
                'montant_asc' => $this->commandeModel->getByClientOrderByMontant($clientId, $filter, 'ASC'),
                'montant_desc' => $this->commandeModel->getByClientOrderByMontant($clientId, $filter, 'DESC'),
                default => $this->commandeModel->getByClientOrderByRecent($clientId, $filter),
            };

            $stats = [
                'total_commandes' => $this->commandeModel->countByClient($clientId),
                'montant_total' => $this->commandeModel->getTotalClientMontant($clientId),
                'dernier_achat' => $this->commandeModel->getLastOrderDate($clientId),
            ];

            $flash = $_SESSION['flash'] ?? null;
            unset($_SESSION['flash']);

            require_once __DIR__ . '/../views/my_commandes.php';
        } catch (Exception $e) {
            error_log('Erreur CommandeController::myCommandes - ' . $e->getMessage());
            $this->setFlash('error', 'Erreur lors du chargement.');
            header('Location: /dashboard');
            exit;
        }
    }

    // ─────────────────────────────────────────
    //  Rapport ventes (admin)
    // ─────────────────────────────────────────
    public function report(): void {
        $this->auth->requireRole('admin');

        try {
            $mois = (int)($_GET['mois'] ?? date('m'));
            $annee = (int)($_GET['annee'] ?? date('Y'));

            $stats = $this->commandeModel->getStatsParMois($mois, $annee);
            $topProduits = $this->produitModel->getTopProduits(10);
            $topClients = $this->clientModel->getTopClients(10);

            $flash = $_SESSION['flash'] ?? null;
            unset($_SESSION['flash']);

            require_once __DIR__ . '/../views/backoffice/commande_report.php';
        } catch (Exception $e) {
            error_log('Erreur CommandeController::report - ' . $e->getMessage());
            $this->setFlash('error', 'Erreur lors du chargement.');
            header('Location: /admin/dashboard');
            exit;
        }
    }

    // ─────────────────────────────────────────
    //  API - Statistiques
    // ─────────────────────────────────────────
    public function apiStats(): void {
        header('Content-Type: application/json');
        $this->auth->requireRole('admin');

        try {
            $stats = [
                'total_commandes' => $this->commandeModel->countAll('all'),
                'commandes_en_attente' => $this->commandeModel->countByStatus('en attente'),
                'commandes_confirmees' => $this->commandeModel->countByStatus('confirmée'),
                'commandes_expediees' => $this->commandeModel->countByStatus('expédiée'),
                'commandes_livrees' => $this->commandeModel->countByStatus('livrée'),
                'total_montant' => $this->commandeModel->getTotalMontant(),
                'montant_mois' => $this->commandeModel->getTotalMontantMois(),
                'montant_jour' => $this->commandeModel->getTotalMontantJour(),
                'moyenne_commande' => $this->commandeModel->getAverageCommande(),
            ];

            echo json_encode([
                'success' => true,
                'stats' => $stats,
            ]);
            exit;
        } catch (Exception $e) {
            error_log('Erreur apiStats - ' . $e->getMessage());
            echo json_encode(['error' => 'Erreur serveur']);
            exit;
        }
    }

    // ─────────────────────────────────────────
    //  Helpers privés
    // ─────────────────────────────────────────
    private function validateCommande(array $data): array {
        $errors = [];

        if (empty($data['adresse_livraison']) || strlen($data['adresse_livraison']) < 5) {
            $errors[] = 'L\'adresse de livraison est requise et doit contenir au moins 5 caractères.';
        }

        if (empty($data['code_postal']) || !preg_match('/^\d{5}$/', $data['code_postal'])) {
            $errors[] = 'Le code postal est invalide.';
        }

        if (empty($data['ville']) || strlen($data['ville']) < 2) {
            $errors[] = 'La ville est requise.';
        }

        if (empty($data['telephone']) || !preg_match('/^[0-9\s\-\.]{10,}$/', str_replace([' ', '-', '.'], '', $data['telephone']))) {
            $errors[] = 'Le numéro de téléphone est invalide.';
        }

        return $errors;
    }

    private function calculateCartTotal(): float {
        $total = 0;
        if (isset($_SESSION['panier']) && is_array($_SESSION['panier'])) {
            foreach ($_SESSION['panier'] as $item) {
                $total += $item['quantite'] * $item['prix_unitaire'];
            }
        }
        return round($total, 2);
    }

    private function generateNumeroCommande(): string {
        return 'CMD-' . date('Ymd') . '-' . str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
    }

    private function sendConfirmationEmail($client, $commandeId, $data): void {
        try {
            $to = $client['email'] ?? '';
            $subject = "Confirmation de commande - Parapharmacie Valorys";

            $message = <<<HTML
<h2>Confirmation de votre commande</h2>
<p>Bonjour {$client['prenom']} {$client['nom']},</p>
<p>Votre commande a bien été reçue. Numéro : <strong>{$data['numero']}</strong></p>

<h3>Détails</h3>
<ul>
    <li><strong>Montant :</strong> {$data['montant_total']}€</li>
    <li><strong>Livraison :</strong> {$data['adresse_livraison']}, {$data['code_postal']} {$data['ville']}</li>
    <li><strong>Statut :</strong> En attente de confirmation</li>
</ul>

<p>Vous recevrez un email dès que votre commande sera confirmée.</p>
<p>Cordialement,<br>L'équipe Parapharmacie Valorys</p>
HTML;

            $headers = "MIME-Version: 1.0\r\n";
            $headers .= "Content-type: text/html; charset=UTF-8\r\n";

            // mail($to, $subject, $message, $headers);
        } catch (Exception $e) {
            error_log('Erreur sendConfirmationEmail: ' . $e->getMessage());
        }
    }

    private function sendStatusEmail($commande, $statut): void {
        try {
            $client = $this->clientModel->findById($commande['client_id']);
            $to = $client['email'] ?? '';
            $subject = "Mise à jour de votre commande - Parapharmacie Valorys";

            $message = match ($statut) {
                'expédiée' => <<<HTML
<h2>Votre commande a été expédiée</h2>
<p>Votre commande {$commande['numero']} a quitté notre entrepôt.</p>
<p>Elle devrait vous parvenir dans 2-3 jours ouvrables.</p>
HTML,
                'livrée' => <<<HTML
<h2>Votre commande a été livrée</h2>
<p>Votre commande {$commande['numero']} a été livrée avec succès.</p>
<p>Merci pour votre confiance !</p>
HTML,
                default => '<p>Votre commande a été mise à jour.</p>',
            };

            $headers = "MIME-Version: 1.0\r\n";
            $headers .= "Content-type: text/html; charset=UTF-8\r\n";

            // mail($to, $subject, $message, $headers);
        } catch (Exception $e) {
            error_log('Erreur sendStatusEmail: ' . $e->getMessage());
        }
    }

    private function generateCsrfToken(): string {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    private function verifyCsrfToken(string $token): bool {
        return !empty($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
    }

    private function setFlash(string $type, string $message): void {
        $_SESSION['flash'] = ['type' => $type, 'message' => $message];
    }

    private function logAction(int $userId, string $action, string $description): void {
        try {
            $sql = "INSERT INTO logs (user_id, action, description, ip_address, created_at)
                    VALUES (:user_id, :action, :description, :ip, NOW())";
            $this->db->execute($sql, [
                'user_id' => $userId,
                'action' => $action,
                'description' => $description,
                'ip' => $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0',
            ]);
        } catch (Exception $e) {
            error_log('Erreur logAction: ' . $e->getMessage());
        }
    }
}
?>