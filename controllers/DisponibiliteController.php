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

    public function __destruct() {
        unset($this->disponibiliteModel, $this->medecinModel, $this->auth, $this->db);
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

            // Chemin corrigé vers la vue médecin
            $viewPath = __DIR__ . '/../views/frontoffice/medecin/mes_disponibilites.php';
            if (file_exists($viewPath)) {
                require_once $viewPath;
            } else {
                // Fallback simple
                echo "<div class='container mt-4'>";
                echo "<h2>Mes disponibilités</h2>";
                echo "<table class='table table-bordered'>";
                echo "<thead><tr><th>Jour</th><th>Heure début</th><th>Heure fin</th><th>Statut</th><th>Actions</th></tr></thead><tbody>";
                foreach ($disponibilites as $d) {
                    echo "<tr>";
                    echo "<td>" . htmlspecialchars($d['jour_semaine']) . "</td>";
                    echo "<td>" . $d['heure_debut'] . "</td>";
                    echo "<td>" . $d['heure_fin'] . "</td>";
                    echo "<td>" . ($d['actif'] ? 'Actif' : 'Inactif') . "</td>";
                    echo "<td><a href='index.php?page=disponibilites&action=delete&id=" . $d['id'] . "' class='btn btn-sm btn-danger' onclick='return confirm(\"Supprimer ?\")'>Supprimer</a></td>";
                    echo "</tr>";
                }
                echo "</tbody></table></div>";
            }
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

            // Chemin corrigé vers la vue admin
            $viewPath = __DIR__ . '/../views/backoffice/disponibilite/list.php';
            if (file_exists($viewPath)) {
                require_once $viewPath;
            } else {
                echo "Vue non trouvée: " . $viewPath;
            }
        } catch (Exception $e) {
            error_log('Erreur DisponibiliteController::indexAdmin - ' . $e->getMessage());
            $_SESSION['error'] = 'Erreur lors du chargement.';
            header('Location: index.php?page=dashboard');
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

            // Chemin corrigé vers la vue formulaire médecin
            $viewPath = __DIR__ . '/../views/frontoffice/medecin/disponibilite_form.php';
            if (file_exists($viewPath)) {
                require_once $viewPath;
            } else {
                // Fallback simple
                echo "<div class='container mt-4'>";
                echo "<h2>Ajouter une disponibilité</h2>";
                echo "<form method='POST'>";
                echo "<select name='jour_semaine'><option>Lundi</option><option>Mardi</option><option>Mercredi</option><option>Jeudi</option><option>Vendredi</option><option>Samedi</option><option>Dimanche</option></select>";
                echo "<input type='time' name='heure_debut'>";
                echo "<input type='time' name='heure_fin'>";
                echo "<button type='submit'>Ajouter</button>";
                echo "</form></div>";
            }
        } catch (Exception $e) {
            error_log('Erreur DisponibiliteController::createMedecin - ' . $e->getMessage());
            $_SESSION['error'] = 'Erreur lors du chargement du formulaire.';
            header('Location: index.php?page=disponibilites');
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
            $jour_semaine = $_POST['jour_semaine'] ?? '';
            $heure_debut = $_POST['heure_debut'] ?? '';
            $heure_fin = $_POST['heure_fin'] ?? '';

            if (empty($jour_semaine) || empty($heure_debut) || empty($heure_fin)) {
                $_SESSION['error'] = 'Tous les champs sont obligatoires.';
                $_SESSION['old'] = $_POST;
                header('Location: index.php?page=disponibilites&action=create');
                exit;
            }

            $disponibiliteId = $this->disponibiliteModel->create([
                'medecin_id' => $medecinId,
                'jour_semaine' => $jour_semaine,
                'heure_debut' => $heure_debut,
                'heure_fin' => $heure_fin,
                'actif' => 1
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

            $viewPath = __DIR__ . '/../views/frontoffice/medecin/disponibilite_edit.php';
            if (file_exists($viewPath)) {
                require_once $viewPath;
            } else {
                echo "Vue non trouvée: " . $viewPath;
            }
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
            header("Location: index.php?page=disponibilites&action=edit&id=$id");
            exit;
        }

        try {
            $jour_semaine = $_POST['jour_semaine'] ?? '';
            $heure_debut = $_POST['heure_debut'] ?? '';
            $heure_fin = $_POST['heure_fin'] ?? '';
            $actif = isset($_POST['actif']) ? 1 : 0;

            if (empty($jour_semaine) || empty($heure_debut) || empty($heure_fin)) {
                $_SESSION['error'] = 'Tous les champs sont obligatoires.';
                header("Location: index.php?page=disponibilites&action=edit&id=$id");
                exit;
            }

            $this->disponibiliteModel->update($id, [
                'jour_semaine' => $jour_semaine,
                'heure_debut' => $heure_debut,
                'heure_fin' => $heure_fin,
                'actif' => $actif
            ]);

            $_SESSION['success'] = 'Disponibilité mise à jour.';
            header('Location: index.php?page=disponibilites');
            exit;
        } catch (Exception $e) {
            error_log('Erreur updateMedecin - ' . $e->getMessage());
            $_SESSION['error'] = 'Erreur lors de la mise à jour.';
            header("Location: index.php?page=disponibilites&action=edit&id=$id");
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
    //  Activer/Désactiver une disponibilité
    // ─────────────────────────────────────────
    public function toggle(int $id): void {
        $this->auth->requireRole('medecin');

        try {
            $dispo = $this->disponibiliteModel->getById($id);
            if ($dispo) {
                $newStatus = $dispo['actif'] ? 0 : 1;
                $this->disponibiliteModel->update($id, ['actif' => $newStatus]);
                $_SESSION['success'] = 'Statut mis à jour.';
            } else {
                $_SESSION['error'] = 'Disponibilité non trouvée.';
            }
            header('Location: index.php?page=disponibilites');
            exit;
        } catch (Exception $e) {
            error_log('Erreur toggle - ' . $e->getMessage());
            $_SESSION['error'] = 'Erreur lors de la modification.';
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