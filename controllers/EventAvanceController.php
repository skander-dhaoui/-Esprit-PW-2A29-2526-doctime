<?php
require_once __DIR__ . '/../models/EventAvance.php';

/**
 * EventAvanceController — Gestion avancée des événements (porté depuis DOCTIME_advanced)
 * Routing Valorys : ?page=evenements_avance_admin&action=...
 */
class EventAvanceController {
    private EventAvance $model;

    public function __construct() {
        $this->model = new EventAvance();
    }

    // ─────────────────────────────────────────────────────────────────
    // 1. Vue d'ensemble avancée (tableau de bord événements)
    // ─────────────────────────────────────────────────────────────────
    public function index(): void {
        $data = $this->model->getVueEnsemble();
        require_once __DIR__ . '/../views/backoffice/evenements_avance/index.php';
    }

    // ─────────────────────────────────────────────────────────────────
    // 2. Statistiques détaillées d'un événement
    // ─────────────────────────────────────────────────────────────────
    public function stats(): void {
        $id   = (int)($_GET['id'] ?? 0);
        $data = $this->model->getStatsEvenement($id);
        if (empty($data)) {
            http_response_code(404);
            echo "<h1>404 – Événement introuvable</h1>";
            return;
        }
        require_once __DIR__ . '/../views/backoffice/evenements_avance/stats.php';
    }

    // ─────────────────────────────────────────────────────────────────
    // 3. Recherche / filtrage avancé
    // ─────────────────────────────────────────────────────────────────
    public function recherche(): void {
        $filtres = [
            'q'              => trim($_GET['q']              ?? ''),
            'statut'         => $_GET['statut']              ?? '',
            'date_debut_min' => $_GET['date_debut_min']      ?? '',
            'date_debut_max' => $_GET['date_debut_max']      ?? '',
            'prix_min'       => $_GET['prix_min']            ?? '',
            'prix_max'       => $_GET['prix_max']            ?? '',
            'sponsor_id'     => $_GET['sponsor_id']          ?? '',
            'avec_places'    => !empty($_GET['avec_places']),
            'tri'            => $_GET['tri']                 ?? 'date_debut',
            'ordre'          => $_GET['ordre']               ?? 'ASC',
        ];

        $evenements = $this->model->recherche($filtres);
        $sponsors   = $this->model->getSponsors();
        $statuts    = $this->model->getStatuts();

        require_once __DIR__ . '/../views/backoffice/evenements_avance/recherche.php';
    }

    // ─────────────────────────────────────────────────────────────────
    // 4. Export CSV des participants d'un événement
    // ─────────────────────────────────────────────────────────────────
    public function exportCsv(): void {
        $id     = (int)($_GET['id']    ?? 0);
        $statut = trim($_GET['statut'] ?? '');

        $evenement = $this->model->findBasic($id);
        if (!$evenement) {
            http_response_code(404);
            echo "<h1>404 – Événement introuvable</h1>";
            return;
        }

        $participants = $this->model->getParticipantsForExport($id, $statut);
        $filename     = 'participants_' . $id . '_' . date('Ymd_His') . '.csv';

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Pragma: no-cache');

        $out = fopen('php://output', 'w');

        // BOM UTF-8 pour Excel
        fprintf($out, chr(0xEF) . chr(0xBB) . chr(0xBF));

        // En-tête du fichier
        fputcsv($out, ['Événement', $evenement['titre']], ';');
        fputcsv($out, ['Date début', date('d/m/Y', strtotime($evenement['date_debut']))], ';');
        fputcsv($out, ['Date fin',   date('d/m/Y', strtotime($evenement['date_fin']))], ';');
        fputcsv($out, ['Lieu',       $evenement['lieu']], ';');
        fputcsv($out, ['Exporté le', date('d/m/Y H:i')], ';');
        fputcsv($out, [], ';');

        // En-têtes colonnes
        fputcsv($out, ['Nom', 'Prénom', 'Email', 'Statut', 'Date inscription'], ';');

        foreach ($participants as $p) {
            fputcsv($out, [
                $p['nom'],
                $p['prenom'],
                $p['email'],
                $p['statut'],
                date('d/m/Y H:i', strtotime($p['date_inscription'])),
            ], ';');
        }

        fclose($out);
        exit;
    }

    // ─────────────────────────────────────────────────────────────────
    // 5. Page de prévisualisation avant export
    // ─────────────────────────────────────────────────────────────────
    public function exportPreview(): void {
        $id     = (int)($_GET['id']    ?? 0);
        $statut = trim($_GET['statut'] ?? '');

        $evenement = $this->model->findBasic($id);
        if (!$evenement) {
            http_response_code(404);
            echo "<h1>404 – Événement introuvable</h1>";
            return;
        }

        $participants = $this->model->getParticipantsForExport($id, $statut);
        $statuts      = array_merge([''], $this->model->getStatuts());

        require_once __DIR__ . '/../views/backoffice/evenements_avance/export.php';
    }
}