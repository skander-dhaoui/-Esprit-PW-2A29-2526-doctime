<?php
require_once __DIR__ . '/../config/database.php';

/**
 * EventAvance — Gestion avancée des événements (porté depuis DOCTIME_advanced)
 * Adapté à la structure Valorys : table `events`, `participations`, `sponsors`
 */
class EventAvance {
    private PDO $pdo;

    public function __construct() {
        $this->pdo = Database::getInstance()->getConnection();
    }

    // ═══════════════════════════════════════════════════════════════════
    // 1. STATISTIQUES DÉTAILLÉES PAR ÉVÉNEMENT
    // ═══════════════════════════════════════════════════════════════════

    public function getStatsEvenement(int $id): array {
        // Infos de base de l'événement
        $stmt = $this->pdo->prepare("
            SELECT e.*,
                   s.nom AS sponsor_nom, s.niveau AS sponsor_niveau
            FROM events e
            LEFT JOIN sponsors s ON e.sponsor_id = s.id
            WHERE e.id = :id
        ");
        $stmt->execute([':id' => $id]);
        $evenement = $stmt->fetch();
        if (!$evenement) return [];

        $capacite = (int)($evenement['capacite_max'] ?? 0);

        // Nombre total de participations
        $stmt = $this->pdo->prepare("
            SELECT COUNT(*) FROM participations
            WHERE event_id = :id AND statut != 'absent'
        ");
        $stmt->execute([':id' => $id]);
        $nbInscrits = (int)$stmt->fetchColumn();

        // Taux de remplissage
        $tauxRemplissage = $capacite > 0
            ? round(($nbInscrits / $capacite) * 100, 1)
            : 0;

        // Répartition par statut de participation
        $stmt = $this->pdo->prepare("
            SELECT statut, COUNT(*) as total
            FROM participations
            WHERE event_id = :id
            GROUP BY statut
        ");
        $stmt->execute([':id' => $id]);
        $repartitionStatut = $stmt->fetchAll();

        // Évolution des inscriptions par jour
        $stmt = $this->pdo->prepare("
            SELECT DATE(date_inscription) AS jour, COUNT(*) AS total
            FROM participations
            WHERE event_id = :id
            GROUP BY jour
            ORDER BY jour ASC
        ");
        $stmt->execute([':id' => $id]);
        $evolutionInscriptions = $stmt->fetchAll();

        // Liste complète des participants avec infos utilisateur
        $stmt = $this->pdo->prepare("
            SELECT p.id, p.statut, p.date_inscription,
                   u.nom, u.prenom, u.email
            FROM participations p
            JOIN users u ON p.user_id = u.id
            WHERE p.event_id = :id
            ORDER BY p.date_inscription DESC
        ");
        $stmt->execute([':id' => $id]);
        $participants = $stmt->fetchAll();

        return [
            'evenement'              => $evenement,
            'nb_inscrits'            => $nbInscrits,
            'places_restantes'       => max(0, $capacite - $nbInscrits),
            'taux_remplissage'       => $tauxRemplissage,
            'repartition_statut'     => $repartitionStatut,
            'evolution_inscriptions' => $evolutionInscriptions,
            'participants'           => $participants,
        ];
    }

    // ═══════════════════════════════════════════════════════════════════
    // 2. RECHERCHE ET FILTRAGE AVANCÉ
    // ═══════════════════════════════════════════════════════════════════

    public function recherche(array $filtres): array {
        $conditions = ['1=1'];
        $params     = [];

        if (!empty($filtres['q'])) {
            $conditions[] = "(e.titre LIKE :q OR e.description LIKE :q OR e.lieu LIKE :q)";
            $params[':q'] = '%' . $filtres['q'] . '%';
        }

        if (!empty($filtres['statut'])) {
            $conditions[] = "e.status = :statut";
            $params[':statut'] = $filtres['statut'];
        }

        if (!empty($filtres['date_debut_min'])) {
            $conditions[] = "e.date_debut >= :date_debut_min";
            $params[':date_debut_min'] = $filtres['date_debut_min'];
        }

        if (!empty($filtres['date_debut_max'])) {
            $conditions[] = "e.date_debut <= :date_debut_max";
            $params[':date_debut_max'] = $filtres['date_debut_max'];
        }

        if (!empty($filtres['prix_min'])) {
            $conditions[] = "e.prix >= :prix_min";
            $params[':prix_min'] = (float)$filtres['prix_min'];
        }

        if (!empty($filtres['prix_max'])) {
            $conditions[] = "e.prix <= :prix_max";
            $params[':prix_max'] = (float)$filtres['prix_max'];
        }

        if (isset($filtres['avec_places']) && $filtres['avec_places']) {
            $conditions[] = "e.places_restantes > 0";
        }

        if (!empty($filtres['sponsor_id'])) {
            $conditions[] = "e.sponsor_id = :sponsor_id";
            $params[':sponsor_id'] = (int)$filtres['sponsor_id'];
        }

        $tri   = in_array($filtres['tri'] ?? '', ['date_debut', 'titre', 'prix', 'capacite_max'])
               ? $filtres['tri'] : 'date_debut';
        $ordre = ($filtres['ordre'] ?? 'ASC') === 'DESC' ? 'DESC' : 'ASC';

        $sql = "
            SELECT e.*,
                   s.nom AS sponsor_nom,
                   (SELECT COUNT(*) FROM participations p
                    WHERE p.event_id = e.id AND p.statut != 'absent') AS nb_inscrits
            FROM events e
            LEFT JOIN sponsors s ON e.sponsor_id = s.id
            WHERE " . implode(' AND ', $conditions) . "
            ORDER BY e.$tri $ordre
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    // ═══════════════════════════════════════════════════════════════════
    // 3. EXPORT CSV DES PARTICIPANTS D'UN ÉVÉNEMENT
    // ═══════════════════════════════════════════════════════════════════

    public function getParticipantsForExport(int $eventId, string $statut = ''): array {
        $params = [':eid' => $eventId];
        $statutCondition = '';
        if (!empty($statut)) {
            $statutCondition = "AND p.statut = :statut";
            $params[':statut'] = $statut;
        }

        $stmt = $this->pdo->prepare("
            SELECT u.nom, u.prenom, u.email,
                   p.statut, p.date_inscription,
                   e.titre AS evenement_titre, e.date_debut, e.date_fin, e.lieu
            FROM participations p
            JOIN users u ON p.user_id = u.id
            JOIN events e ON p.event_id = e.id
            WHERE p.event_id = :eid $statutCondition
            ORDER BY u.nom, u.prenom
        ");
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function findBasic(int $id): array|false {
        $stmt = $this->pdo->prepare("SELECT id, titre, date_debut, date_fin, lieu FROM events WHERE id = :id");
        $stmt->execute([':id' => $id]);
        return $stmt->fetch();
    }

    // ═══════════════════════════════════════════════════════════════════
    // 4. TABLEAU DE BORD GLOBAL AVANCÉ
    // ═══════════════════════════════════════════════════════════════════

    public function getVueEnsemble(): array {
        // Événements proches de la saturation (>= 80%)
        $alertes = $this->pdo->query("
            SELECT e.id, e.titre, e.date_debut, e.capacite_max,
                   COUNT(p.id) AS nb_inscrits,
                   ROUND(COUNT(p.id) / NULLIF(e.capacite_max, 0) * 100, 1) AS taux
            FROM events e
            LEFT JOIN participations p ON p.event_id = e.id AND p.statut != 'absent'
            WHERE e.status = 'à venir'
              AND e.capacite_max > 0
            GROUP BY e.id
            HAVING taux >= 80
            ORDER BY taux DESC
        ")->fetchAll();

        // Top 5 événements par nombre d'inscrits
        $topEvenements = $this->pdo->query("
            SELECT e.id, e.titre, e.status,
                   COUNT(p.id) AS nb_inscrits, e.capacite_max,
                   ROUND(COUNT(p.id) / NULLIF(e.capacite_max, 0) * 100, 1) AS taux
            FROM events e
            LEFT JOIN participations p ON p.event_id = e.id AND p.statut != 'absent'
            GROUP BY e.id
            ORDER BY nb_inscrits DESC
            LIMIT 5
        ")->fetchAll();

        // Résumé financier : revenus estimés (prix × inscrits)
        $revenuEstime = $this->pdo->query("
            SELECT e.id, e.titre, e.prix,
                   COUNT(p.id) AS nb_confirmes,
                   ROUND(e.prix * COUNT(p.id), 2) AS revenu
            FROM events e
            JOIN participations p ON p.event_id = e.id AND p.statut = 'inscrit'
            GROUP BY e.id
            ORDER BY revenu DESC
            LIMIT 8
        ")->fetchAll();

        // Répartition des événements par statut
        $parStatut = $this->pdo->query("
            SELECT status, COUNT(*) AS total
            FROM events
            GROUP BY status
            ORDER BY total DESC
        ")->fetchAll();

        // Événements à venir dans les 30 jours
        $prochains = $this->pdo->query("
            SELECT e.id, e.titre, e.date_debut, e.lieu, e.status,
                   COUNT(p.id) AS nb_inscrits, e.capacite_max
            FROM events e
            LEFT JOIN participations p ON p.event_id = e.id AND p.statut != 'absent'
            WHERE e.date_debut BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 30 DAY)
            GROUP BY e.id
            ORDER BY e.date_debut ASC
        ")->fetchAll();

        return [
            'alertes_saturation' => $alertes,
            'top_evenements'     => $topEvenements,
            'revenu_estime'      => $revenuEstime,
            'par_statut'         => $parStatut,
            'prochains_30j'      => $prochains,
        ];
    }

    public function getSponsors(): array {
        return $this->pdo->query("SELECT id, nom FROM sponsors WHERE actif = 1 ORDER BY nom")->fetchAll();
    }

    public function getStatuts(): array {
        return ['à venir', 'en_cours', 'terminé', 'annulé'];
    }
}