<?php
require_once __DIR__ . '/../models/Event.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/AuthController.php';

class EventController {
    private Event $eventModel;
    private User $userModel;
    private AuthController $auth;

    public function __construct() {
        $this->eventModel = new Event();
        $this->userModel  = new User();
        $this->auth       = new AuthController();
    }

    // ═══════════════════════════════════════════════════════════
    //  FRONTOFFICE — CRUD (tous utilisateurs connectés)
    // ═══════════════════════════════════════════════════════════

    public function frontCreate(): void {
        $this->auth->requireAuth();
        require_once __DIR__ . '/../views/frontoffice/evenement_form.php';
    }

    public function frontStore(): void {
        $this->auth->requireAuth();

        $data = [
            'titre'        => trim($_POST['titre'] ?? ''),
            'description'  => trim($_POST['description'] ?? ''),
            'contenu'      => trim($_POST['contenu'] ?? ''),
            'date_debut'   => $_POST['date_debut'] ?? '',
            'date_fin'     => $_POST['date_fin'] ?? '',
            'lieu'         => trim($_POST['lieu'] ?? ''),
            'adresse'      => trim($_POST['adresse'] ?? ''),
            'capacite_max' => (int)($_POST['capacite_max'] ?? 0),
            'image'        => trim($_POST['image'] ?? ''),
            'prix'         => (float)($_POST['prix'] ?? 0),
            'status'       => $_POST['status'] ?? 'à venir',
        ];

        $errors = $this->validate($data);
        if (!empty($errors)) {
            $_SESSION['flash'] = ['type' => 'error', 'message' => implode('<br>', $errors)];
            header('Location: index.php?page=evenement_create');
            exit;
        }

        $this->eventModel->create($data);
        $_SESSION['flash'] = ['type' => 'success', 'message' => 'Événement créé avec succès !'];
        header('Location: index.php?page=evenements');
        exit;
    }

    public function frontEdit(int $id): void {
        $this->auth->requireAuth();

        $event = $this->eventModel->getById($id);
        if (!$event) {
            $_SESSION['flash'] = ['type' => 'error', 'message' => 'Événement introuvable.'];
            header('Location: index.php?page=evenements');
            exit;
        }

        require_once __DIR__ . '/../views/frontoffice/evenement_form.php';
    }

    public function frontUpdate(int $id): void {
        $this->auth->requireAuth();

        $event = $this->eventModel->getById($id);
        if (!$event) {
            $_SESSION['flash'] = ['type' => 'error', 'message' => 'Événement introuvable.'];
            header('Location: index.php?page=evenements');
            exit;
        }

        $data = [
            'titre'        => trim($_POST['titre'] ?? ''),
            'description'  => trim($_POST['description'] ?? ''),
            'contenu'      => trim($_POST['contenu'] ?? ''),
            'date_debut'   => $_POST['date_debut'] ?? '',
            'date_fin'     => $_POST['date_fin'] ?? '',
            'lieu'         => trim($_POST['lieu'] ?? ''),
            'adresse'      => trim($_POST['adresse'] ?? ''),
            'capacite_max' => (int)($_POST['capacite_max'] ?? 0),
            'image'        => trim($_POST['image'] ?? ''),
            'prix'         => (float)($_POST['prix'] ?? 0),
            'status'       => $_POST['status'] ?? 'à venir',
        ];

        $errors = $this->validate($data);
        if (!empty($errors)) {
            $_SESSION['flash'] = ['type' => 'error', 'message' => implode('<br>', $errors)];
            header("Location: index.php?page=evenement_edit&id=$id");
            exit;
        }

        $this->eventModel->update($id, $data);
        $_SESSION['flash'] = ['type' => 'success', 'message' => 'Événement mis à jour.'];
        header('Location: index.php?page=evenements');
        exit;
    }

    public function frontDelete(int $id): void {
        $this->auth->requireAuth();

        $event = $this->eventModel->getById($id);
        if (!$event) {
            $_SESSION['flash'] = ['type' => 'error', 'message' => 'Événement introuvable.'];
            header('Location: index.php?page=evenements');
            exit;
        }

        $this->eventModel->delete($id);
        $_SESSION['flash'] = ['type' => 'success', 'message' => 'Événement supprimé.'];
        header('Location: index.php?page=evenements');
        exit;
    }

    // ═══════════════════════════════════════════════════════════
    //  BACKOFFICE — ADMIN UNIQUEMENT
    // ═══════════════════════════════════════════════════════════

    public function adminIndex(): void {
        $this->auth->requireRole('admin');

        $events = $this->eventModel->getAll();
        $stats  = [
            'total'    => $this->eventModel->countAll(),
            'a_venir'  => $this->eventModel->countByStatus('à venir'),
            'termines' => $this->eventModel->countByStatus('terminé'),
        ];

        require_once __DIR__ . '/../views/backoffice/evenements/list.php';
    }

    public function adminCreate(): void {
        $this->auth->requireRole('admin');
        require_once __DIR__ . '/../views/backoffice/evenements/form.php';
    }

    public function adminStore(): void {
        $this->auth->requireRole('admin');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: index.php?page=admin_evenements');
            exit;
        }

        $data = [
            'titre'        => trim($_POST['titre']),
            'description'  => trim($_POST['description']),
            'contenu'      => trim($_POST['contenu']),
            'date_debut'   => $_POST['date_debut'],
            'date_fin'     => $_POST['date_fin'],
            'lieu'         => trim($_POST['lieu']),
            'adresse'      => trim($_POST['adresse']),
            'capacite_max' => (int)($_POST['capacite_max'] ?? 0),
            'image'        => trim($_POST['image'] ?? ''),
            'prix'         => (float)($_POST['prix'] ?? 0),
            'status'       => $_POST['status'] ?? 'à venir',
        ];

        $errors = $this->validate($data);
        if (!empty($errors)) {
            $_SESSION['flash'] = ['type' => 'error', 'message' => implode('<br>', $errors)];
            header('Location: index.php?page=admin_evenements&action=create');
            exit;
        }

        $eventId = $this->eventModel->create($data);

        if (!empty($_POST['sponsors']) && $eventId) {
            foreach ($_POST['sponsors'] as $sponsor) {
                if (!empty($sponsor['sponsor_name'])) {
                    $this->eventModel->addSponsor($eventId, [
                        'sponsor_name'      => $sponsor['sponsor_name'],
                        'sponsor_logo'      => $sponsor['sponsor_logo'] ?? '',
                        'sponsor_website'   => $sponsor['sponsor_website'] ?? '',
                        'amount'            => (float)($sponsor['amount'] ?? 0),
                        'contribution_type' => $sponsor['contribution_type'] ?? 'financier',
                    ]);
                }
            }
        }

        $_SESSION['flash'] = ['type' => 'success', 'message' => 'Événement créé avec succès.'];
        header('Location: index.php?page=admin_evenements');
        exit;
    }

    public function adminEdit(int $id): void {
        $this->auth->requireRole('admin');

        $event    = $this->eventModel->getById($id);
        if (!$event) $this->notFound();

        $sponsors = $this->eventModel->getSponsorsByEvent($id);

        require_once __DIR__ . '/../views/backoffice/evenements/form.php';
    }

    public function adminUpdate(int $id): void {
        $this->auth->requireRole('admin');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: index.php?page=admin_evenements&action=edit&id=$id");
            exit;
        }

        $data = [
            'titre'        => trim($_POST['titre']),
            'description'  => trim($_POST['description']),
            'contenu'      => trim($_POST['contenu']),
            'date_debut'   => $_POST['date_debut'],
            'date_fin'     => $_POST['date_fin'],
            'lieu'         => trim($_POST['lieu']),
            'adresse'      => trim($_POST['adresse']),
            'capacite_max' => (int)($_POST['capacite_max'] ?? 0),
            'image'        => trim($_POST['image'] ?? ''),
            'prix'         => (float)($_POST['prix'] ?? 0),
            'status'       => $_POST['status'] ?? 'à venir',
        ];

        $this->eventModel->update($id, $data);

        if (!empty($_POST['sponsors'])) {
            foreach ($_POST['sponsors'] as $sponsor) {
                if (!empty($sponsor['sponsor_name'])) {
                    if (!empty($sponsor['id'])) {
                        $this->eventModel->updateSponsor((int)$sponsor['id'], $sponsor);
                    } else {
                        $this->eventModel->addSponsor($id, $sponsor);
                    }
                }
            }
        }

        $_SESSION['flash'] = ['type' => 'success', 'message' => 'Événement mis à jour.'];
        header('Location: index.php?page=admin_evenements');
        exit;
    }

    public function adminDelete(int $id): void {
        $this->auth->requireRole('admin');

        $this->eventModel->delete($id);
        $_SESSION['flash'] = ['type' => 'success', 'message' => 'Événement supprimé.'];
        header('Location: index.php?page=admin_evenements');
        exit;
    }

    public function adminShow(int $id): void {
        $this->auth->requireRole('admin');

        $event    = $this->eventModel->getById($id);
        if (!$event) $this->notFound();

        $participants        = $this->eventModel->getParticipants($id);
        $sponsors            = $this->eventModel->getSponsorsByEvent($id);
        $totalSponsorAmount  = $this->eventModel->getTotalSponsorAmount($id);

        require_once __DIR__ . '/../views/backoffice/evenements/show.php';
    }

    public function adminToggleStatus(int $id): void {
        $this->auth->requireRole('admin');

        $event = $this->eventModel->getById($id);
        if (!$event) $this->notFound();

        $newStatus = match($event['status']) {
            'à venir' => 'terminé',
            'terminé' => 'à venir',
            default   => 'à venir',
        };

        $this->eventModel->updateStatus($id, $newStatus);
        $_SESSION['flash'] = ['type' => 'success', 'message' => 'Statut modifié.'];
        header('Location: index.php?page=admin_evenements');
        exit;
    }

    // ═══════════════════════════════════════════════════════════
    //  FRONTOFFICE — LECTURE PUBLIQUE
    // ═══════════════════════════════════════════════════════════

    public function frontList(): void {
        $upcomingEvents = $this->eventModel->getUpcoming();
        $pastEvents     = $this->eventModel->getPast();
        $isLoggedIn     = isset($_SESSION['user_id']);

        require_once __DIR__ . '/../views/frontoffice/events.php';
    }

    public function frontShow(string $slug): void {
        $event = $this->eventModel->getBySlug($slug);
        if (!$event) {
            $front = new FrontController();
            $front->page404();
            return;
        }

        $isParticipant    = false;
        $participantsCount = $event['nb_participants'] ?? 0;

        if (isset($_SESSION['user_id'])) {
            $isParticipant = $this->eventModel->isParticipant($event['id'], (int)$_SESSION['user_id']);
        }

        require_once __DIR__ . '/../views/frontoffice/event_detail.php';
    }

    public function getEventById(int $id): array|false {
        return $this->eventModel->getById($id);
    }

    // ═══════════════════════════════════════════════════════════
    //  INSCRIPTIONS
    // ═══════════════════════════════════════════════════════════

    public function frontRegister(): void {
        $this->auth->requireAuth();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: index.php?page=evenements');
            exit;
        }

        $eventId = (int)($_POST['event_id'] ?? 0);
        $userId  = (int)$_SESSION['user_id'];

        $event = $this->eventModel->getById($eventId);
        if (!$event) {
            $_SESSION['flash'] = ['type' => 'error', 'message' => 'Événement introuvable.'];
            header('Location: index.php?page=evenements');
            exit;
        }

        $nbParticipants = $this->eventModel->countParticipants($eventId);
        if ($event['capacite_max'] > 0 && $nbParticipants >= $event['capacite_max']) {
            $_SESSION['flash'] = ['type' => 'error', 'message' => 'Plus de places disponibles.'];
            header('Location: index.php?page=evenement&slug=' . $event['slug']);
            exit;
        }

        $result = $this->eventModel->addParticipant($eventId, $userId);
        if ($result) {
            $_SESSION['flash'] = ['type' => 'success', 'message' => 'Inscription confirmée !'];
        } else {
            $_SESSION['flash'] = ['type' => 'error', 'message' => 'Vous êtes déjà inscrit à cet événement.'];
        }

        header('Location: index.php?page=evenement&slug=' . $event['slug']);
        exit;
    }

    public function frontUnregister(): void {
        $this->auth->requireAuth();

        $eventId = (int)($_POST['event_id'] ?? 0);
        $userId  = (int)$_SESSION['user_id'];

        $event = $this->eventModel->getById($eventId);
        if (!$event) {
            $_SESSION['flash'] = ['type' => 'error', 'message' => 'Événement introuvable.'];
            header('Location: index.php?page=evenements');
            exit;
        }

        $this->eventModel->removeParticipant($eventId, $userId);
        $_SESSION['flash'] = ['type' => 'success', 'message' => 'Inscription annulée.'];
        header('Location: index.php?page=evenement&slug=' . $event['slug']);
        exit;
    }

    public function mesInscriptions(): void {
        $this->auth->requireAuth();

        $userId = (int)$_SESSION['user_id'];

        $db   = (new ReflectionProperty($this->eventModel, 'db'))->getValue($this->eventModel);
        $stmt = $db->prepare("
            SELECT e.*, p.date_inscription, p.statut
            FROM participations p
            JOIN events e ON p.event_id = e.id
            WHERE p.user_id = :user_id
            ORDER BY e.date_debut ASC
        ");
        $stmt->execute([':user_id' => $userId]);
        $myEvents = $stmt->fetchAll();

        require_once __DIR__ . '/../views/frontoffice/mes_inscriptions.php';
    }

    // ═══════════════════════════════════════════════════════════
    //  HELPERS PRIVÉS
    // ═══════════════════════════════════════════════════════════

    private function validate(array $data): array {
        $errors = [];

        if (empty($data['titre']))      $errors[] = 'Le titre est obligatoire.';
        if (empty($data['date_debut'])) $errors[] = 'La date de début est obligatoire.';
        if (empty($data['date_fin']))   $errors[] = 'La date de fin est obligatoire.';

        if (!empty($data['date_debut']) && !empty($data['date_fin'])
            && $data['date_debut'] > $data['date_fin']) {
            $errors[] = 'La date de début doit être antérieure à la date de fin.';
        }

        return $errors;
    }

    private function notFound(): void {
        http_response_code(404);
        die('Événement introuvable.');
    }
}