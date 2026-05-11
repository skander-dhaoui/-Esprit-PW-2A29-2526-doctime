<?php
require_once __DIR__ . '/../config/database.php';

/**
 * MapController — Carte interactive des événements + vue Métiers (données événements / sponsors)
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
                   COUNT(p.id) AS nb_participants,
                   s.nom AS sponsor_nom,
                   COALESCE(NULLIF(TRIM(e.categorie), ''), NULLIF(TRIM(s.nom), ''), 'Événement') AS specialite
            FROM events e
            LEFT JOIN participations p ON p.event_id = e.id
            LEFT JOIN sponsors s ON s.id = e.sponsor_id
            GROUP BY e.id
            ORDER BY e.date_debut ASC
        ")->fetchAll(PDO::FETCH_ASSOC);

        // Enrichir avec coordonnées GPS (lieu + adresse ; événements distincts pour « En ligne »)
        $evenementsGeo = [];
        foreach ($evenements as $ev) {
            $coords = $this->resolveCoords(
                (string) ($ev['lieu'] ?? ''),
                isset($ev['adresse']) ? (string) $ev['adresse'] : null,
                (int) ($ev['id'] ?? 0)
            );
            $evenementsGeo[] = array_merge($ev, $coords);
        }

        // Statistiques par gouvernorat
        $statsGouvernorat = $this->getStatsGouvernorat($evenementsGeo);

        // Stats globales
        $statsGlobales = [
            'total'     => count($evenements),
            'planifie'  => count(array_filter($evenements, fn($e) => ($e['status'] ?? '') === 'à venir')),
            'en_cours'  => count(array_filter($evenements, fn($e) => ($e['status'] ?? '') === 'en_cours')),
            'termine'   => count(array_filter($evenements, fn($e) => ($e['status'] ?? '') === 'terminé')),
            'annule'    => count(array_filter($evenements, fn($e) => ($e['status'] ?? '') === 'annulé')),
        ];

        $payloadCarte = [];
        foreach ($evenementsGeo as $ev) {
            $lat = isset($ev['lat']) ? (float) $ev['lat'] : null;
            $lng = isset($ev['lng']) ? (float) $ev['lng'] : null;
            $gov = (string) ($ev['gouvernorat'] ?? '');
            if ($lat === null || $lng === null || !is_finite($lat) || !is_finite($lng)) {
                $fixed = $this->resolveCoords(
                    (string) ($ev['lieu'] ?? ''),
                    isset($ev['adresse']) ? (string) $ev['adresse'] : null,
                    (int) ($ev['id'] ?? 0)
                );
                $lat = (float) $fixed['lat'];
                $lng = (float) $fixed['lng'];
                $gov = (string) ($fixed['gouvernorat'] ?? $gov);
            }
            $payloadCarte[] = [
                'id'              => (int) ($ev['id'] ?? 0),
                'titre'           => (string) ($ev['titre'] ?? ''),
                'lieu'            => (string) ($ev['lieu'] ?? ''),
                'status'          => (string) ($ev['status'] ?? 'à venir'),
                'lat'             => round($lat, 6),
                'lng'             => round($lng, 6),
                'date_debut'      => (string) ($ev['date_debut'] ?? ''),
                'specialite'      => (string) ($ev['specialite'] ?? ''),
                'gouvernorat'     => $gov,
                'nb_participants' => (int) ($ev['nb_participants'] ?? 0),
            ];
        }

        $jsonFlags = JSON_UNESCAPED_UNICODE;
        if (defined('JSON_INVALID_UTF8_SUBSTITUTE')) {
            $jsonFlags |= JSON_INVALID_UTF8_SUBSTITUTE;
        }
        $evenementsGeoJson = json_encode($payloadCarte, $jsonFlags);
        if ($evenementsGeoJson === false) {
            error_log('MapController::carte json_encode: ' . json_last_error_msg());
            foreach ($payloadCarte as $i => $row) {
                foreach ($row as $k => $v) {
                    if (is_string($v)) {
                        $payloadCarte[$i][$k] = @iconv('UTF-8', 'UTF-8//IGNORE', $v) ?: '';
                    }
                }
            }
            $evenementsGeoJson = json_encode($payloadCarte, $jsonFlags);
        }
        if (!is_string($evenementsGeoJson)) {
            $evenementsGeoJson = '[]';
        }

        $pageTitle = 'Carte Interactive – Tunisie';
        require __DIR__ . '/../views/backoffice/map/carte.php';
    }

    /**
     * Page IA métiers : assistant + panneaux (spécialités événements, profils participants).
     */
    public function metiers(): void {
        $specialites = $this->pdo->query("
            SELECT categorie AS specialite, COUNT(*) AS total
            FROM events
            WHERE categorie IS NOT NULL AND TRIM(categorie) != ''
            GROUP BY categorie
            ORDER BY total DESC
        ")->fetchAll(PDO::FETCH_ASSOC);

        $participantProfessions = [];
        try {
            $participantProfessions = $this->pdo->query("
                SELECT prof_label AS profession, COUNT(*) AS total
                FROM (
                    SELECT
                        CASE
                            WHEN u.role = 'medecin' THEN COALESCE(NULLIF(TRIM(m.specialite), ''), 'Médecin')
                            WHEN u.role = 'patient' THEN 'Participant'
                            WHEN u.role = 'admin' THEN 'Administrateur'
                            ELSE CONCAT('Rôle : ', u.role)
                        END AS prof_label
                    FROM participations p
                    INNER JOIN users u ON u.id = p.user_id
                    LEFT JOIN medecins m ON m.user_id = u.id AND u.role = 'medecin'
                ) AS t
                GROUP BY prof_label
                ORDER BY total DESC
                LIMIT 40
            ")->fetchAll(PDO::FETCH_ASSOC);
        } catch (Throwable $e) {
            error_log('MapController::metiers participantProfessions: ' . $e->getMessage());
        }

        $recentEventsForAssistant = [];
        try {
            $recentEventsForAssistant = $this->pdo->query("
                SELECT titre,
                       COALESCE(NULLIF(TRIM(categorie), ''), 'Non classé') AS categorie
                FROM events
                ORDER BY date_debut DESC
                LIMIT 8
            ")->fetchAll(PDO::FETCH_ASSOC);
        } catch (Throwable $e) {
            error_log('MapController::metiers recentEvents: ' . $e->getMessage());
        }

        $pageTitle = 'IA Métiers créatifs';
        require __DIR__ . '/../views/backoffice/map/metiers.php';
    }

    /**
     * API JSON : données carte (appelée en AJAX depuis la vue)
     */
    public function apiCarte(): void {
        header('Content-Type: application/json; charset=utf-8');

        $evenements = $this->pdo->query("
            SELECT e.*,
                   COUNT(p.id) AS nb_participants,
                   COUNT(p.id) AS total_part,
                   s.nom AS sponsor_nom
            FROM events e
            LEFT JOIN participations p ON p.event_id = e.id
            LEFT JOIN sponsors s ON s.id = e.sponsor_id
            GROUP BY e.id
        ")->fetchAll(PDO::FETCH_ASSOC);

        $result = [];
        foreach ($evenements as $ev) {
            $coords = $this->resolveCoords(
                (string) ($ev['lieu'] ?? ''),
                isset($ev['adresse']) ? (string) $ev['adresse'] : null,
                (int) ($ev['id'] ?? 0)
            );
            $result[] = array_merge($ev, $coords);
        }

        echo json_encode($result, JSON_UNESCAPED_UNICODE);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Méthodes privées
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Résout lat/lng : lieux physiques (nom du gouvernorat dans lieu/adresse),
     * événements « En ligne » répartis sur la carte (évite tout empiler sur Tunis).
     */
    private function resolveCoords(string $lieu, ?string $adresse, int $eventId): array {
        $haystack = mb_strtolower(trim($lieu . ' ' . (string) $adresse), 'UTF-8');

        if ($haystack !== '' && preg_match('/\b(en ligne|online|webinaire|webinar|visio|vidéo conférence|video conference|zoom|teams|distanciel|virtuel|live stream)\b/u', $haystack)) {
            return $this->coordsVirtualSpread($eventId);
        }

        // Noms les plus longs en premier (ex. « Sidi Bouzid » avant « Sidi » si jamais ajouté)
        $sorted = $this->gouvernorats;
        uksort($sorted, static function (string $a, string $b): int {
            return mb_strlen($b, 'UTF-8') <=> mb_strlen($a, 'UTF-8');
        });

        foreach ($sorted as $nom => $coords) {
            $needle = mb_strtolower($nom, 'UTF-8');
            if ($needle !== '' && mb_stripos($haystack, $needle, 0, 'UTF-8') !== false) {
                return $this->coordsWithDeterministicJitter($coords, $nom, $eventId);
            }
        }

        return $this->coordsWithDeterministicJitter(
            ['lat' => 36.8065, 'lng' => 10.1815],
            'Tunis (approx.)',
            $eventId
        );
    }

    /** Événements sans lieu physique reconnu : centre Tunis avec dispersion stable par id */
    private function coordsVirtualSpread(int $eventId): array {
        $baseLat = 35.35;
        $baseLng = 10.15;
        $h       = crc32('virtual|' . $eventId);
        $dlat    = (($h % 4096) / 4096.0) * 1.1 - 0.55;
        $dlng    = ((($h >> 11) % 4096) / 4096.0) * 1.4 - 0.7;

        return [
            'lat'         => $baseLat + $dlat,
            'lng'         => $baseLng + $dlng,
            'gouvernorat' => 'En ligne',
        ];
    }

    private function coordsWithDeterministicJitter(array $coords, string $gouvernorat, int $eventId): array {
        $h = crc32((string) $eventId . '|' . $gouvernorat);
        // ~ ±4 km autour du point de référence du gouvernorat
        $dlat = (($h % 4096) / 4096.0) * 0.06 - 0.03;
        $dlng = ((($h >> 12) % 4096) / 4096.0) * 0.06 - 0.03;

        return [
            'lat'         => $coords['lat'] + $dlat,
            'lng'         => $coords['lng'] + $dlng,
            'gouvernorat' => $gouvernorat,
        ];
    }

    /**
     * Agrège les stats par gouvernorat
     */
    private function getStatsGouvernorat(array $evenements): array {
        $stats = [];
        foreach ($evenements as $ev) {
            $gov = $ev['gouvernorat'] ?? '—';
            if (!isset($stats[$gov])) {
                $stats[$gov] = ['total' => 0, 'participants' => 0, 'specialites' => []];
            }
            $stats[$gov]['total']++;
            $stats[$gov]['participants'] += (int) ($ev['nb_participants'] ?? 0);
            $specialite = $ev['specialite'] ?? $ev['type'] ?? 'Événement';
            if (!in_array($specialite, $stats[$gov]['specialites'], true)) {
                $stats[$gov]['specialites'][] = $specialite;
            }
        }
        uasort($stats, static function (array $a, array $b): int {
            return ($b['total'] ?? 0) <=> ($a['total'] ?? 0);
        });

        return $stats;
    }
}
