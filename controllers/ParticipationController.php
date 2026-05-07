<?php
declare(strict_types=1);

require_once __DIR__ . '/../repositories/ParticipationRepository.php';
require_once __DIR__ . '/../repositories/EventRepository.php';

use App\Repositories\ParticipationRepository;
use App\Repositories\EventRepository;

class ParticipationController {
    private ParticipationRepository $participationRepo;
    private EventRepository $eventRepo;

    public function __construct() {
        $this->participationRepo = new ParticipationRepository();
        $this->eventRepo = new EventRepository();
    }

    public function index(): void {
        // We'll use indexAdmin as it's called in index.php
        $this->indexAdmin();
    }

    public function indexAdmin(): void {
        $participations = $this->participationRepo->getAll();
        require_once __DIR__ . '/../views/backoffice/participation/index.php';
    }

    public function delete(int $id): void {
        $this->participationRepo->delete($id);
        header('Location: index.php?page=participations&success=delete');
        exit;
    }

    public function create(): void {
        $events = $this->eventRepo->getAll();
        require_once __DIR__ . '/../views/backoffice/participation/create.php';
    }

    public function store(): void {
        $data = [
            'event_id' => $_POST['event_id'] ?? 0,
            'user_id'  => $_POST['user_id']  ?? 0,
            'statut'   => $_POST['statut']   ?? 'inscrit',
        ];
        $this->participationRepo->create($data);
        header('Location: index.php?page=participations&success=create');
        exit;
    }

    public function edit(int $id): void {
        // Similar logic for edit...
    }

    public function update(int $id): void {
        // Similar logic for update...
    }
}
