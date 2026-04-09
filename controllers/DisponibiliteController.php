<?php

require_once __DIR__ . '/../models/Disponibilite.php';
require_once __DIR__ . '/../models/Medecin.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/AuthController.php';

class DisponibiliteController {

    private Disponibilite $disponibiliteModel;
    private Medecin $medecinModel;
    private AuthController $auth;
    private Database $db;

    public function __construct() {
        $this->disponibiliteModel = new Disponibilite();
        $this->medecinModel = new Medecin();
        $this->auth = new AuthController();
        $this->db = Database::getInstance();
    }

    // ─────────────────────────────────────────
    //  Liste des disponibilités (médecin)
    // ─────────────────────────────────────────
    public function indexMedecin(): void {
        $this->auth->requireRole('medecin');

        try {
            $medecinId = (int)$_SESSION['user_id'];
            $disponibilites = $this->disponibiliteModel->getByMedecin($medecinId);

            $flash = $_SESSION['flash'] ?? null;
            unset($_SESSION['flash']);

            require_once __DIR__ . '/../views/backoffice/disponibilite_list_medecin.php';
        } catch (Exception $e) {
            error_log('Erreur DisponibiliteController::indexMedecin - ' . $e->getMessage());
            $_SESSION['error'] = 'Erreur lors du chargement des disponibilités.';
            header('Location: index.php?page=accueil');
            exit;
        }
    }

    // ─────────────────────────────────────────
    //  Liste des disponibilités (admin)
    // ─────────────────────────────────────────
    public function indexAdmin(): void {
        $this->auth->requireRole('admin');

        try {
            $medecinId = $_GET['medecin'] ?? null;
            $filter = $_GET['filter'] ?? 'all';

            if ($medecinId) {
                $disponibilites = $this->disponibiliteModel->getByMedecin((int)$medecinId, $filter);
            } else {
                $disponibilites = $this->disponibiliteModel->getAll($filter);
            }

            $medecins = $this->medecinModel->getAllWithUsers();
            $flash = $_SESSION['flash'] ?? null;
            unset($_SESSION['flash']);

            require_once __DIR__ . '/../views/backoffice/disponibilite_list_admin.php';
        } catch (Exception $e) {
            error_log('Erreur DisponibiliteController::indexAdmin - ' . $e->getMessage());
            $_SESSION['error'] = 'Erreur lors du chargement.';
            header('Location: index.php?page=accueil');
            exit;
        }
    }

    // ─────────────────────────────────────────
    //  Créer une disponibilité (médecin)
    // ─────────────────────────────────────────
    public function createMedecin(): void {
        $this->auth->requireRole('medecin');

        try {
            $csrfToken = $this->generateCsrfToken();
            $old = $_SESSION['old'] ?? null;
            $flash = $_SESSION['flash'] ?? null;
            unset($_SESSION['old'], $_SESSION['flash']);

            require_once __DIR__ . '/../views/backoffice/disponibilite_form_medecin.php';
        } catch (Exception $e) {
            error_log('Erreur DisponibiliteController::createMedecin - ' . $e->getMessage());
            $_SESSION['error'] = 'Erreur lors du chargement du formulaire.';
            header('Location: index.php?page=accueil');
            exit;
        }
    }

    // ─────────────────────────────────────────
    //  Enregistrer une disponibilité (médecin)
    // ─────────────────────────────────────────
    public function storeMedecin(): void {
        $this->auth->requireRole('medecin');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: index.php?page=disponibilites&action=create');
            exit;
        }

        try {
            $medecinId = (int)$_SESSION['user_id'];
            $dateDebut = $_POST['date_debut'] ?? '';
            $dateFin = $_POST['date_fin'] ?? '';
            $heureDebut = $_POST['heure_debut'] ?? '';
            $heureFin = $_POST['heure_fin'] ?? '';

            if (empty($dateDebut) || empty($dateFin) || empty($heureDebut) || empty($heureFin)) {
                $_SESSION['error'] = 'Tous les champs sont obligatoires.';
                $_SESSION['old'] = $_POST;
                header('Location: index.php?page=disponibilites&action=create');
                exit;
            }

            $disponibiliteId = $this->disponibiliteModel->create([
                'medecin_id' => $medecinId,
                'date_debut' => $dateDebut,
                'date_fin' => $dateFin,
                'heure_debut' => $heureDebut,
                'heure_fin' => $heureFin,
            ]);

            if (!$disponibiliteId) {
                throw new Exception('Erreur lors de la création.');
            }

            $_SESSION['success'] = 'Disponibilité créée avec succès.';
            header('Location: index.php?page=disponibilites');
            exit;
        } catch (Exception $e) {
            error_log('Erreur storeMedecin - ' . $e->getMessage());
            $_SESSION['error'] = 'Erreur lors de l\'enregistrement.';
            header('Location: index.php?page=disponibilites&action=create');
            exit;
        }
    }

    // ─────────────────────────────────────────
    //  Éditer une disponibilité (médecin)
    // ─────────────────────────────────────────
    public function editMedecin(int $id): void {
        $this->auth->requireRole('medecin');

        try {
            $disponibilite = $this->disponibiliteModel->getById($id);

            if (!$disponibilite) {
                http_response_code(404);
                die('Disponibilité introuvable.');
            }

            $csrfToken = $this->generateCsrfToken();
            $flash = $_SESSION['flash'] ?? null;
            unset($_SESSION['flash']);

            require_once __DIR__ . '/../views/backoffice/disponibilite_form_medecin_edit.php';
        } catch (Exception $e) {
            error_log('Erreur editMedecin - ' . $e->getMessage());
            $_SESSION['error'] = 'Erreur lors du chargement.';
            header('Location: index.php?page=disponibilites');
            exit;
        }
    }

    // ─────────────────────────────────────────
    //  Mettre à jour une disponibilité
    // ─────────────────────────────────────────
    public function updateMedecin(int $id): void {
        $this->auth->requireRole('medecin');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: index.php?page=disponibilites&id=$id&action=edit");
            exit;
        }

        try {
            $dateDebut = $_POST['date_debut'] ?? '';
            $dateFin = $_POST['date_fin'] ?? '';
            $heureDebut = $_POST['heure_debut'] ?? '';
            $heureFin = $_POST['heure_fin'] ?? '';

            if (empty($dateDebut) || empty($dateFin) || empty($heureDebut) || empty($heureFin)) {
                $_SESSION['error'] = 'Tous les champs sont obligatoires.';
                header("Location: index.php?page=disponibilites&id=$id&action=edit");
                exit;
            }

            $this->disponibiliteModel->update($id, [
                'date_debut' => $dateDebut,
                'date_fin' => $dateFin,
                'heure_debut' => $heureDebut,
                'heure_fin' => $heureFin,
            ]);

            $_SESSION['success'] = 'Disponibilité mise à jour.';
            header('Location: index.php?page=disponibilites');
            exit;
        } catch (Exception $e) {
            error_log('Erreur updateMedecin - ' . $e->getMessage());
            $_SESSION['error'] = 'Erreur lors de la mise à jour.';
            header("Location: index.php?page=disponibilites&id=$id&action=edit");
            exit;
        }
    }

    // ─────────────────────────────────────────
    //  Supprimer une disponibilité
    // ─────────────────────────────────────────
    public function delete(int $id): void {
        $this->auth->requireRole('medecin');

        try {
            $this->disponibiliteModel->delete($id);
            $_SESSION['success'] = 'Disponibilité supprimée.';
            header('Location: index.php?page=disponibilites');
            exit;
        } catch (Exception $e) {
            error_log('Erreur delete - ' . $e->getMessage());
            $_SESSION['error'] = 'Erreur lors de la suppression.';
            header('Location: index.php?page=disponibilites');
            exit;
        }
    }

    // ─────────────────────────────────────────
    //  Helpers privés
    // ─────────────────────────────────────────
    private function generateCsrfToken(): string {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    private function verifyCsrfToken(string $token): bool {
        return !empty($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
    }
}
?>


