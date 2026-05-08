<?php
require_once __DIR__ . '/../config/database.php';

/**
 * MapController — Gère la carte interactive Tunisie + l'assistant IA métiers
 */
class MapController {
    private PDO $pdo;

    // Coordonnées des gouvernorats tunisiens
    private array $gouvernorats = [
        'Tunis'         => ['lat' => 36.8065, 'lng' => 10.1815],
        'Ariana'        => ['lat' => 36.8625, 'lng' => 10.1956],
        'Ben Arous'     => ['lat' => 36.7531, 'lng' => 10.2282],
        'Manouba'       => ['lat' => 36.8089, 'lng' => 10.0956],
        'Nabeul'        => ['lat' => 36.4561, 'lng' => 10.7376],
        'Zaghouan'      => ['lat' => 36.4021, 'lng' => 10.1426],
        'Bizerte'       => ['lat' => 37.2744, 'lng' => 9.8739],
        'Béja'          => ['lat' => 36.7333, 'lng' => 9.1833],
        'Jendouba'      => ['lat' => 36.5011, 'lng' => 8.7803],
        'Le Kef'        => ['lat' => 36.1675, 'lng' => 8.7049],
        'Siliana'       => ['lat' => 36.0844, 'lng' => 9.3704],
        'Sousse'        => ['lat' => 35.8256, 'lng' => 10.6369],
        'Monastir'      => ['lat' => 35.7643, 'lng' => 10.8113],
        'Mahdia'        => ['lat' => 35.5047, 'lng' => 11.0622],
        'Sfax'          => ['lat' => 34.7400, 'lng' => 10.7600],
        'Kairouan'      => ['lat' => 35.6712, 'lng' => 10.1007],
        'Kasserine'     => ['lat' => 35.1676, 'lng' => 8.8365],
        'Sidi Bouzid'   => ['lat' => 35.0382, 'lng' => 9.4858],
        'Gabès'         => ['lat' => 33.8833, 'lng' => 10.0982],
        'Médenine'      => ['lat' => 33.3549, 'lng' => 10.5055],
        'Tataouine'     => ['lat' => 32.9211, 'lng' => 10.4511],
        'Gafsa'         => ['lat' => 34.4250, 'lng' => 8.7842],
        'Tozeur'        => ['lat' => 33.9197, 'lng' => 8.1335],
        'Kébili'        => ['lat' => 33.7046, 'lng' => 8.9688],
    ];

    public function __construct() {
        $this->pdo = Database::getInstance()->getConnection();
    }

    /**
     * Page carte interactive
     */
    public function carte(): void {
        $evenements = $this->pdo->query("
            SELECT e.*,
                   COUNT(p.id) as nb_participants,
                   SUM(p.nombre_participants) as total_part_declarees,
                   s.nom as sponsor_nom,
                   s.secteur as specialite
            FROM events e
            LEFT JOIN participations p ON p.event_id = e.id
            LEFT JOIN sponsors s ON s.id = e.sponsor_id
            GROUP BY e.id
            ORDER BY e.date_debut ASC
        ")->fetchAll(PDO::FETCH_ASSOC);

        // Enrichir avec coordonnées GPS
        $evenementsGeo = [];
        foreach ($evenements as $ev) {
            $coords = $this->resolveCoords($ev['lieu']);
            $evenementsGeo[] = array_merge($ev, $coords);
        }

        // Statistiques par gouvernorat
        $statsGouvernorat = $this->getStatsGouvernorat($evenementsGeo);

        // Stats globales
        $statsGlobales = [
            'total'     => count($evenements),
            'planifie'  => count(array_filter($evenements, fn($e) => $e['status'] === 'à venir')),
            'en_cours'  => count(array_filter($evenements, fn($e) => $e['status'] === 'en_cours')),
            'termine'   => count(array_filter($evenements, fn($e) => $e['status'] === 'terminé')),
        ];

        $pageTitle = 'Carte Interactive – Événements en Tunisie';
        require __DIR__ . '/../views/backoffice/map/carte.php';
    }

    /**
     * Page assistant IA métiers
     */
    public function metiers(): void {
        // Afficher la page métiers créatifs (backoffice avec navbar)
        $pageTitle = 'Métiers Créatifs – Assistant IA';

        // Récupérer les spécialités depuis les sponsors
        try {
            $specialites = $this->pdo->query("
                SELECT s.secteur AS specialite, COUNT(e.id) AS total
                FROM sponsors s
                LEFT JOIN events e ON e.sponsor_id = s.id
                WHERE s.secteur IS NOT NULL AND s.secteur != ''
                GROUP BY s.secteur
                ORDER BY total DESC
            ")->fetchAll(PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            $specialites = [];
        }

        // Récupérer les professions depuis les participations
        try {
            $professions = $this->pdo->query("
                SELECT p.profession, COUNT(*) AS total
                FROM participations p
                WHERE p.profession IS NOT NULL AND p.profession != ''
                GROUP BY p.profession
                ORDER BY total DESC
                LIMIT 20
            ")->fetchAll(PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            $professions = [];
        }

        require __DIR__ . '/../views/backoffice/map/metiers.php';
    }

    /**
     * API JSON : données carte (appelée en AJAX depuis la vue)
     */
    public function apiCarte(): void {
        header('Content-Type: application/json; charset=utf-8');

        $evenements = $this->pdo->query("
            SELECT e.*, 
                   COUNT(p.id) as nb_participants,
                   SUM(p.nombre_participants) as total_part,
                   s.nom as sponsor_nom
            FROM events e
            LEFT JOIN participations p ON p.event_id = e.id
            LEFT JOIN sponsors s ON s.id = e.sponsor_id
            GROUP BY e.id
        ")->fetchAll(PDO::FETCH_ASSOC);

        $result = [];
        foreach ($evenements as $ev) {
            $coords = $this->resolveCoords($ev['lieu']);
            $result[] = array_merge($ev, $coords);
        }

        echo json_encode($result, JSON_UNESCAPED_UNICODE);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Méthodes privées
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Résout les coordonnées GPS d'un lieu
     * Cherche le gouvernorat correspondant dans la liste
     */
    private function resolveCoords(string $lieu): array {
        foreach ($this->gouvernorats as $nom => $coords) {
            if (stripos($lieu, $nom) !== false) {
                // Légère variation pour éviter superposition
                return [
                    'lat'          => $coords['lat'] + (mt_rand(-50, 50) / 10000),
                    'lng'          => $coords['lng'] + (mt_rand(-50, 50) / 10000),
                    'gouvernorat'  => $nom,
                ];
            }
        }
        // Défaut : Tunis
        return [
            'lat'          => 36.8065 + (mt_rand(-200, 200) / 10000),
            'lng'          => 10.1815 + (mt_rand(-200, 200) / 10000),
            'gouvernorat'  => 'Tunis',
        ];
    }

    /**
     * Agrège les stats par gouvernorat
     */
    private function getStatsGouvernorat(array $evenements): array {
        $stats = [];
        foreach ($evenements as $ev) {
            $gov = $ev['gouvernorat'];
            if (!isset($stats[$gov])) {
                $stats[$gov] = ['total' => 0, 'participants' => 0, 'specialites' => []];
            }
            $stats[$gov]['total']++;
            $stats[$gov]['participants'] += (int)($ev['nb_participants'] ?? 0);
            $specialite = $ev['specialite'] ?? $ev['type'] ?? 'Événement';
            if (!in_array($specialite, $stats[$gov]['specialites'])) {
                $stats[$gov]['specialites'][] = $specialite;
            }
        }
        arsort($stats);
        return $stats;
    }
}
