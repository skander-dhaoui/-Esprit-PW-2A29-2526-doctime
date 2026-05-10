<?php

require_once __DIR__ . '/../models/Commande.php';
require_once __DIR__ . '/../models/Produit.php';
require_once __DIR__ . '/../models/Client.php';
require_once __DIR__ . '/../repositories/CommandeRepository.php';
require_once __DIR__ . '/../repositories/ProduitRepository.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/AuthController.php';

use App\Repositories\CommandeRepository;
use App\Repositories\ProduitRepository;

class CommandeController {

    private CommandeRepository $commandeRepo;
    private ProduitRepository $produitRepo;
    private \Client           $clientModel;
    private AuthController    $auth;
    private Database          $db;

    public function __construct() {
        $this->commandeRepo = new CommandeRepository();
        $this->produitRepo  = new ProduitRepository();
        $this->clientModel  = new \Client();
        $this->auth         = new AuthController();
        $this->db           = Database::getInstance();
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

            $commandes = $this->commandeRepo->getAll($filter, $offset, $perPage, $search, $dateDebut, $dateFin);
            $total = $this->commandeRepo->countAll($filter, $search, $dateDebut, $dateFin);
            $totalPages = ceil($total / $perPage);

            $stats = [
                'total_commandes' => $this->commandeRepo->countAll('all'),
                'commandes_en_attente' => $this->commandeRepo->countByStatus('en attente'),
                'commandes_confirmees' => $this->commandeRepo->countByStatus('confirmée'),
                'total_montant' => $this->commandeRepo->getTotalMontant(),
                'montant_mois' => $this->commandeRepo->getTotalMontantMois(),
            ];

            $flash = $_SESSION['flash'] ?? null;
            unset($_SESSION['flash']);

            require_once __DIR__ . '/../views/backoffice/pharmacie/commandes_list.php';
        } catch (Exception $e) {
            error_log('Erreur CommandeController::index - ' . $e->getMessage());
            $_SESSION['flash'] = ['type' => 'error', 'message' => 'Erreur lors du chargement des commandes.'];
            header('Location: index.php?page=dashboard');
            exit;
        }
    }

    // ─────────────────────────────────────────
    //  Afficher une commande
    // ─────────────────────────────────────────
    public function show(int $id): void {
        $this->auth->requireRole(['admin', 'client']);

        try {
            $commande = $this->commandeRepo->getById($id);

            if (!$commande) {
                http_response_code(404);
                die('Commande introuvable.');
            }

            $userId = (int)$_SESSION['user_id'];
            $userRole = $_SESSION['user_role'];

            // Vérifier les droits si c'est un client
            if ($userRole === 'client' && (int)$commande['client_id'] !== $userId) {
                http_response_code(403);
                die('Accès refusé.');
            }

            $lignes = $this->commandeRepo->getByCommande($id);
            $client = $this->clientModel->findById($commande['client_id']);
            $historique = $this->commandeRepo->getHistorique($id);
            $flash = $_SESSION['flash'] ?? null;
            unset($_SESSION['flash']);

            require_once __DIR__ . '/../views/backoffice/pharmacie/commande_show.php';
        } catch (Exception $e) {
            error_log('Erreur CommandeController::show - ' . $e->getMessage());
            http_response_code(500);
            die('Erreur lors du chargement de la commande.');
        }
    }

    // ─────────────────────────────────────────
    //  Modification statut (admin)
    // ─────────────────────────────────────────
    public function updateStatut(int $id): void {
        $this->auth->requireRole('admin');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: index.php?page=commandes_admin');
            exit;
        }

        try {
            $nouveauStatut = $_POST['statut'] ?? '';
            $notes = htmlspecialchars(trim($_POST['notes_admin'] ?? ''), ENT_QUOTES, 'UTF-8');

            $valides = ['en attente', 'confirmée', 'expédiée', 'livrée', 'annulée'];
            if (!in_array($nouveauStatut, $valides)) {
                $_SESSION['flash'] = ['type' => 'error', 'message' => 'Statut invalide.'];
                header("Location: index.php?page=commandes_admin&action=show&id=$id");
                exit;
            }

            $commande = $this->commandeRepo->getById($id);

            if (!$commande) {
                http_response_code(404);
                die('Commande introuvable.');
            }

            // Mettre à jour le statut
            $this->commandeRepo->update($id, [
                'statut' => $nouveauStatut,
                'notes_admin' => $notes,
            ]);

            // Enregistrer le changement dans l'historique
            $this->commandeRepo->addHistorique($id, $_SESSION['user_id'], "Changement statut: {$commande['statut']} → $nouveauStatut", $nouveauStatut);

            $this->logAction($_SESSION['user_id'], 'Modification commande', "Commande #$id - Statut: $nouveauStatut");

            $_SESSION['flash'] = ['type' => 'success', 'message' => "Statut mis à jour : $nouveauStatut"];
            header("Location: index.php?page=commandes_admin&action=show&id=$id");
            exit;
        } catch (Exception $e) {
            error_log('Erreur updateStatut - ' . $e->getMessage());
            $_SESSION['flash'] = ['type' => 'error', 'message' => 'Erreur lors de la mise à jour.'];
            header("Location: index.php?page=commandes_admin&action=show&id=$id");
            exit;
        }
    }

    private function generateCsrfToken(): string {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
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