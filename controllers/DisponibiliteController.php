<?php
require_once __DIR__ . '/../models/Disponibilite.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/AuthController.php';

class DisponibiliteController {
    private Disponibilite $dispoModel;
    private User $userModel;
    private AuthController $auth;

    public function __construct() {
        $this->dispoModel = new Disponibilite();
        $this->userModel = new User();
        $this->auth = new AuthController();
    }

    // ─────────────────────────────────────────
    //  BACKOFFICE - Gestion des disponibilités
    // ─────────────────────────────────────────

    public function adminIndex(): void {
        $this->auth->requireRole('admin');
        $dispos = $this->dispoModel->getAll();
        require_once __DIR__ . '/../views/backoffice/disponibilite/list.php';
    }

    public function adminCreate(): void {
        $this->auth->requireRole('admin');
        $medecins = $this->userModel->getByRole('medecin');
        require_once __DIR__ . '/../views/backoffice/disponibilite/form.php';
    }

    public function adminStore(): void {
        $this->auth->requireRole('admin');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: index.php?page=admin_disponibilite');
            exit;
        }
        
        $data = [
            'medecin_id' => (int)$_POST['medecin_id'],
            'jour_semaine' => $_POST['jour_semaine'],
            'heure_debut' => $_POST['heure_debut'],
            'heure_fin' => $_POST['heure_fin'],
            'actif' => isset($_POST['actif']) ? (int)$_POST['actif'] : 1
        ];
        
        $this->dispoModel->create($data);
        $_SESSION['flash'] = ['type' => 'success', 'message' => 'Disponibilité ajoutée avec succès.'];
        header('Location: index.php?page=admin_disponibilite');
        exit;
    }

    public function adminEdit(int $id): void {
        $this->auth->requireRole('admin');
        $dispo = $this->dispoModel->getById($id);
        if (!$dispo) $this->notFound();
        $medecins = $this->userModel->getByRole('medecin');
        require_once __DIR__ . '/../views/backoffice/disponibilite/form.php';
    }

    public function adminUpdate(int $id): void {
        $this->auth->requireRole('admin');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: index.php?page=admin_disponibilite&action=edit&id=$id");
            exit;
        }
        
        $data = [
            'medecin_id' => (int)$_POST['medecin_id'],
            'jour_semaine' => $_POST['jour_semaine'],
            'heure_debut' => $_POST['heure_debut'],
            'heure_fin' => $_POST['heure_fin'],
            'actif' => isset($_POST['actif']) ? (int)$_POST['actif'] : 1
        ];
        
        $this->dispoModel->update($id, $data);
        $_SESSION['flash'] = ['type' => 'success', 'message' => 'Disponibilité mise à jour.'];
        header('Location: index.php?page=admin_disponibilite');
        exit;
    }

    public function adminToggle(int $id): void {
        $this->auth->requireRole('admin');
        $this->dispoModel->toggleActive($id);
        $_SESSION['flash'] = ['type' => 'success', 'message' => 'Statut modifié.'];
        header('Location: index.php?page=admin_disponibilite');
        exit;
    }

    public function adminDelete(int $id): void {
        $this->auth->requireRole('admin');
        $this->dispoModel->delete($id);
        $_SESSION['flash'] = ['type' => 'success', 'message' => 'Disponibilité supprimée.'];
        header('Location: index.php?page=admin_disponibilite');
        exit;
    }

    // ─────────────────────────────────────────
    //  FRONTOFFICE - Médecin
    // ─────────────────────────────────────────

    public function medecinMesDisponibilites(): void {
        $this->auth->requireRole('medecin');
        $userId = (int)$_SESSION['user_id'];
        $dispos = $this->dispoModel->getByMedecin($userId);
        require_once __DIR__ . '/../views/frontoffice/medecin/mes_disponibilites.php';
    }

    public function medecinStore(): void {
        $this->auth->requireRole('medecin');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: index.php?page=medecin_disponibilites');
            exit;
        }
        
        $data = [
            'medecin_id' => (int)$_SESSION['user_id'],
            'jour_semaine' => $_POST['jour_semaine'],
            'heure_debut' => $_POST['heure_debut'],
            'heure_fin' => $_POST['heure_fin'],
            'actif' => 1
        ];
        
        $this->dispoModel->create($data);
        $_SESSION['flash'] = ['type' => 'success', 'message' => 'Disponibilité ajoutée.'];
        header('Location: index.php?page=medecin_disponibilites');
        exit;
    }

    public function medecinToggle(int $id): void {
        $this->auth->requireRole('medecin');
        $dispo = $this->dispoModel->getById($id);
        if (!$dispo || $dispo['medecin_id'] != $_SESSION['user_id']) {
            $_SESSION['flash'] = ['type' => 'error', 'message' => 'Disponibilité introuvable.'];
            header('Location: index.php?page=medecin_disponibilites');
            exit;
        }
        $this->dispoModel->toggleActive($id);
        $_SESSION['flash'] = ['type' => 'success', 'message' => 'Statut modifié.'];
        header('Location: index.php?page=medecin_disponibilites');
        exit;
    }

    public function medecinDelete(int $id): void {
        $this->auth->requireRole('medecin');
        $dispo = $this->dispoModel->getById($id);
        if (!$dispo || $dispo['medecin_id'] != $_SESSION['user_id']) {
            $_SESSION['flash'] = ['type' => 'error', 'message' => 'Disponibilité introuvable.'];
            header('Location: index.php?page=medecin_disponibilites');
            exit;
        }
        $this->dispoModel->delete($id);
        $_SESSION['flash'] = ['type' => 'success', 'message' => 'Disponibilité supprimée.'];
        header('Location: index.php?page=medecin_disponibilites');
        exit;
    }

    // ─────────────────────────────────────────
    //  ANCIENNES ROUTES (compatibilité)
    // ─────────────────────────────────────────

    public function indexMedecin(): void {
        $this->medecinMesDisponibilites();
    }

    public function indexAdmin(): void {
        $this->adminIndex();
    }

    public function createMedecin(): void {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->medecinStore();
        } else {
            $this->medecinMesDisponibilites();
        }
    }

    // ─────────────────────────────────────────
    //  API
    // ─────────────────────────────────────────

    public function apiGetByMedecin(): void {
        header('Content-Type: application/json');
        $medecinId = (int)($_GET['medecin_id'] ?? 0);
        if (!$medecinId) {
            echo json_encode(['error' => 'Médecin non spécifié']);
            exit;
        }
        $dispos = $this->dispoModel->getByMedecin($medecinId);
        echo json_encode(['success' => true, 'disponibilites' => $dispos]);
        exit;
    }

    // ─────────────────────────────────────────
    //  HELPERS
    // ─────────────────────────────────────────

    private function notFound(): void {
        http_response_code(404);
        die('Disponibilité introuvable.');
    }
}
?>