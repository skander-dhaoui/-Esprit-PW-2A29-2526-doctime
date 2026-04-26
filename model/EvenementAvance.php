<?php
require_once __DIR__ . '/../config/database.php';

/**
 * EvenementAvance — Fonctionnalités avancées sur la gestion des événements
 * Ce modèle étend les fonctionnalités sans modifier Evenement.php
 */
class EvenementAvance {
    private PDO $pdo;

    // ═══════════════════════════════════════════════════════════════════
    // CONSTRUCTEUR / DESTRUCTEUR
    // ═══════════════════════════════════════════════════════════════════

    public function __construct() {
        $this->pdo = Database::getInstance()->getConnection();
    }

    public function __destruct() {
        // Nettoyage des ressources si nécessaire
    }

    // ═══════════════════════════════════════════════════════════════════
    // GETTER INTERNE
    // ═══════════════════════════════════════════════════════════════════

    private function getPdo(): PDO {
        return $this->pdo;
    }

    // ═══════════════════════════════════════════════════════════════════
    // 1. STATISTIQUES DÉTAILLÉES PAR ÉVÉNEMENT
    // ═══════════════════════════════════════════════════════════════════

    /**
     * Retourne les statistiques complètes d'un événement :
     * taux de remplissage, répartition statuts/professions, évolution inscriptions
     */
    public function getStatsEvenement(int $id): array {
        $evenement = $this->fetchEvenementBase($id);
        if (!$evenement) {
            return [];
        }

        $nbInscrits          = $this->fetchNbInscrits($id);
        $tauxRemplissage     = $this->calculTaux($evenement['capacite'], $nbInscrits);
        $repartitionStatut   = $this->fetchRepartitionStatut($id);
        $repartitionProfession = $this->fetchRepartitionProfession($id);
        $evolutionInscriptions = $this->fetchEvolutionInscriptions($id);
        $participants        = $this->fetchParticipants($id);

        return [
            'evenement'              => $evenement,
            'nb_inscrits'            => $nbInscrits,
            'places_restantes'       => max(0, $evenement['capacite'] - $nbInscrits),
            'taux_remplissage'       => $tauxRemplissage,
            'repartition_statut'     => $repartitionStatut,
            'repartition_profession' => $repartitionProfession,
            'evolution_inscriptions' => $evolutionInscriptions,
            'participants'           => $participants,
        ];
    }

    // ═══════════════════════════════════════════════════════════════════
    // 2. RECHERCHE ET FILTRAGE AVANCÉ
    // ═══════════════════════════════════════════════════════════════════

    /**
     * Recherche multicritères d'événements — tous les paramètres sont optionnels
     */
    public function recherche(array $filtres): array {
        $conditions = ['1=1'];
        $params     = [];

        $conditions = $this->applyFiltreTexte($filtres, $conditions, $params);
        $conditions = $this->applyFiltreSpecialite($filtres, $conditions, $params);
        $conditions = $this->applyFiltreStatut($filtres, $conditions, $params);
        $conditions = $this->applyFiltreDates($filtres, $conditions, $params);
        $conditions = $this->applyFiltrePrix($filtres, $conditions, $params);
        $conditions = $this->applyFiltreAvecPlaces($filtres, $conditions);
        $conditions = $this->applyFiltreSponsor($filtres, $conditions, $params);

        $tri   = $this->getTri($filtres);
        $ordre = $this->getOrdre($filtres);

        $sql = "
            SELECT e.*,
                   GROUP_CONCAT(s.nom) AS sponsor_nom,
                   (SELECT COUNT(*) FROM participation p
                    WHERE p.evenement_id = e.id AND p.statut != 'annule') AS nb_inscrits,
                   (e.capacite - (SELECT COUNT(*) FROM participation p
                    WHERE p.evenement_id = e.id AND p.statut != 'annule')) AS places_restantes
            FROM evenement e
            LEFT JOIN evenement_sponsor es ON e.id = es.evenement_id
            LEFT JOIN sponsor s ON es.sponsor_id = s.id
            WHERE " . implode(' AND ', $conditions) . "
            GROUP BY e.id
            ORDER BY e.$tri $ordre
        ";

        $stmt = $this->getPdo()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    // ═══════════════════════════════════════════════════════════════════
    // 3. EXPORT CSV DES PARTICIPANTS D'UN ÉVÉNEMENT
    // ═══════════════════════════════════════════════════════════════════

    /**
     * Retourne les données nécessaires pour l'export CSV des participants
     */
    public function getParticipantsForExport(int $evenementId, string $statut = ''): array {
        $params = [':eid' => $evenementId];
        $statutCondition = '';

        if (!empty($statut)) {
            $statutCondition  = "AND p.statut = :statut";
            $params[':statut'] = $statut;
        }

        $stmt = $this->getPdo()->prepare("
            SELECT p.nom, p.prenom, p.email, p.telephone, p.profession,
                   p.statut, p.date_inscription,
                   e.titre AS evenement_titre, e.date_debut, e.date_fin, e.lieu
            FROM participation p
            JOIN evenement e ON p.evenement_id = e.id
            WHERE p.evenement_id = :eid $statutCondition
            ORDER BY p.nom, p.prenom
        ");
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Retourne les informations de base d'un événement pour l'entête CSV
     */
    public function findBasic(int $id): array|false {
        $stmt = $this->getPdo()->prepare(
            "SELECT id, titre, date_debut, date_fin, lieu FROM evenement WHERE id = :id"
        );
        $stmt->execute([':id' => $id]);
        return $stmt->fetch();
    }

    // ═══════════════════════════════════════════════════════════════════
    // 4. TABLEAU DE BORD GLOBAL AVANCÉ
    // ═══════════════════════════════════════════════════════════════════

    /**
     * Vue d'ensemble avancée : alertes capacité, top événements, revenus, prochains 30j
     */
    public function getVueEnsemble(): array {
        return [
            'alertes_saturation' => $this->fetchAlertesSaturation(),
            'top_evenements'     => $this->fetchTopEvenements(),
            'revenu_estime'      => $this->fetchRevenuEstime(),
            'par_specialite'     => $this->fetchParSpecialite(),
            'prochains_30j'      => $this->fetchProchains30j(),
        ];
    }

    /**
     * Retourne toutes les spécialités distinctes présentes en base
     */
    public function getSpecialitesDistinctes(): array {
        return $this->getPdo()
                    ->query("SELECT DISTINCT specialite FROM evenement ORDER BY specialite")
                    ->fetchAll(PDO::FETCH_COLUMN);
    }

    /**
     * Retourne tous les sponsors (pour le filtre de recherche)
     */
    public function getSponsors(): array {
        return $this->getPdo()
                    ->query("SELECT id, nom FROM sponsor ORDER BY nom")
                    ->fetchAll();
    }

    // ═══════════════════════════════════════════════════════════════════
    // MÉTHODES PRIVÉES — STATS ÉVÉNEMENT
    // ═══════════════════════════════════════════════════════════════════

    private function fetchEvenementBase(int $id): array|false {
        $stmt = $this->getPdo()->prepare("SELECT e.* FROM evenement e WHERE e.id = :id");
        $stmt->execute([':id' => $id]);
        return $stmt->fetch();
    }

    private function fetchNbInscrits(int $id): int {
        $stmt = $this->getPdo()->prepare("
            SELECT COUNT(*) FROM participation
            WHERE evenement_id = :id AND statut != 'annule'
        ");
        $stmt->execute([':id' => $id]);
        return (int)$stmt->fetchColumn();
    }

    private function calculTaux(int $capacite, int $nbInscrits): float {
        return $capacite > 0 ? round(($nbInscrits / $capacite) * 100, 1) : 0;
    }

    private function fetchRepartitionStatut(int $id): array {
        $stmt = $this->getPdo()->prepare("
            SELECT statut, COUNT(*) AS total
            FROM participation
            WHERE evenement_id = :id
            GROUP BY statut
        ");
        $stmt->execute([':id' => $id]);
        return $stmt->fetchAll();
    }

    private function fetchRepartitionProfession(int $id): array {
        $stmt = $this->getPdo()->prepare("
            SELECT profession, COUNT(*) AS total
            FROM participation
            WHERE evenement_id = :id AND statut != 'annule'
            GROUP BY profession
            ORDER BY total DESC
            LIMIT 8
        ");
        $stmt->execute([':id' => $id]);
        return $stmt->fetchAll();
    }

    private function fetchEvolutionInscriptions(int $id): array {
        $stmt = $this->getPdo()->prepare("
            SELECT DATE(date_inscription) AS jour, COUNT(*) AS total
            FROM participation
            WHERE evenement_id = :id
            GROUP BY jour
            ORDER BY jour ASC
        ");
        $stmt->execute([':id' => $id]);
        return $stmt->fetchAll();
    }

    private function fetchParticipants(int $id): array {
        $stmt = $this->getPdo()->prepare("
            SELECT id, nom, prenom, email, telephone, profession, statut, date_inscription
            FROM participation
            WHERE evenement_id = :id
            ORDER BY date_inscription DESC
        ");
        $stmt->execute([':id' => $id]);
        return $stmt->fetchAll();
    }

    // ═══════════════════════════════════════════════════════════════════
    // MÉTHODES PRIVÉES — FILTRES DE RECHERCHE
    // ═══════════════════════════════════════════════════════════════════

    private function applyFiltreTexte(array $filtres, array $conditions, array &$params): array {
        if (!empty($filtres['q'])) {
            $conditions[] = "(e.titre LIKE :q OR e.description LIKE :q OR e.lieu LIKE :q)";
            $params[':q'] = '%' . $filtres['q'] . '%';
        }
        return $conditions;
    }

    private function applyFiltreSpecialite(array $filtres, array $conditions, array &$params): array {
        if (!empty($filtres['specialite'])) {
            $conditions[] = "e.specialite = :specialite";
            $params[':specialite'] = $filtres['specialite'];
        }
        return $conditions;
    }

    private function applyFiltreStatut(array $filtres, array $conditions, array &$params): array {
        if (!empty($filtres['statut'])) {
            $conditions[] = "e.statut = :statut";
            $params[':statut'] = $filtres['statut'];
        }
        return $conditions;
    }

    private function applyFiltreDates(array $filtres, array $conditions, array &$params): array {
        if (!empty($filtres['date_debut_min'])) {
            $conditions[] = "e.date_debut >= :date_debut_min";
            $params[':date_debut_min'] = $filtres['date_debut_min'];
        }
        if (!empty($filtres['date_debut_max'])) {
            $conditions[] = "e.date_debut <= :date_debut_max";
            $params[':date_debut_max'] = $filtres['date_debut_max'];
        }
        return $conditions;
    }

    private function applyFiltrePrix(array $filtres, array $conditions, array &$params): array {
        if (!empty($filtres['prix_min'])) {
            $conditions[] = "e.prix >= :prix_min";
            $params[':prix_min'] = (float)$filtres['prix_min'];
        }
        if (!empty($filtres['prix_max'])) {
            $conditions[] = "e.prix <= :prix_max";
            $params[':prix_max'] = (float)$filtres['prix_max'];
        }
        return $conditions;
    }

    private function applyFiltreAvecPlaces(array $filtres, array $conditions): array {
        if (isset($filtres['avec_places']) && $filtres['avec_places']) {
            $conditions[] = "
                e.capacite > (
                    SELECT COUNT(*) FROM participation p
                    WHERE p.evenement_id = e.id AND p.statut != 'annule'
                )
            ";
        }
        return $conditions;
    }

    private function applyFiltreSponsor(array $filtres, array $conditions, array &$params): array {
        if (!empty($filtres['sponsor_id'])) {
            $conditions[] = "es.sponsor_id = :sponsor_id";
            $params[':sponsor_id'] = (int)$filtres['sponsor_id'];
        }
        return $conditions;
    }

    private function getTri(array $filtres): string {
        $trisAutorisés = ['date_debut', 'titre', 'prix', 'capacite'];
        return in_array($filtres['tri'] ?? '', $trisAutorisés) ? $filtres['tri'] : 'date_debut';
    }

    private function getOrdre(array $filtres): string {
        return ($filtres['ordre'] ?? 'ASC') === 'DESC' ? 'DESC' : 'ASC';
    }

    // ═══════════════════════════════════════════════════════════════════
    // MÉTHODES PRIVÉES — TABLEAU DE BORD
    // ═══════════════════════════════════════════════════════════════════

    private function fetchAlertesSaturation(): array {
        return $this->getPdo()->query("
            SELECT e.id, e.titre, e.date_debut, e.capacite,
                   COUNT(p.id) AS nb_inscrits,
                   ROUND(COUNT(p.id) / e.capacite * 100, 1) AS taux
            FROM evenement e
            LEFT JOIN participation p ON p.evenement_id = e.id AND p.statut != 'annule'
            WHERE e.statut = 'planifie'
            GROUP BY e.id
            HAVING taux >= 80
            ORDER BY taux DESC
        ")->fetchAll();
    }

    private function fetchTopEvenements(): array {
        return $this->getPdo()->query("
            SELECT e.id, e.titre, e.specialite, e.statut,
                   COUNT(p.id) AS nb_inscrits, e.capacite,
                   ROUND(COUNT(p.id) / e.capacite * 100, 1) AS taux
            FROM evenement e
            LEFT JOIN participation p ON p.evenement_id = e.id AND p.statut != 'annule'
            GROUP BY e.id
            ORDER BY nb_inscrits DESC
            LIMIT 5
        ")->fetchAll();
    }

    private function fetchRevenuEstime(): array {
        return $this->getPdo()->query("
            SELECT e.id, e.titre, e.prix,
                   COUNT(p.id) AS nb_confirmes,
                   ROUND(e.prix * COUNT(p.id), 2) AS revenu
            FROM evenement e
            JOIN participation p ON p.evenement_id = e.id AND p.statut = 'confirme'
            GROUP BY e.id
            ORDER BY revenu DESC
            LIMIT 8
        ")->fetchAll();
    }

    private function fetchParSpecialite(): array {
        return $this->getPdo()->query("
            SELECT specialite, COUNT(*) AS total
            FROM evenement
            GROUP BY specialite
            ORDER BY total DESC
        ")->fetchAll();
    }

    private function fetchProchains30j(): array {
        return $this->getPdo()->query("
            SELECT e.id, e.titre, e.date_debut, e.lieu, e.statut,
                   COUNT(p.id) AS nb_inscrits, e.capacite
            FROM evenement e
            LEFT JOIN participation p ON p.evenement_id = e.id AND p.statut != 'annule'
            WHERE e.date_debut BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)
            GROUP BY e.id
            ORDER BY e.date_debut ASC
        ")->fetchAll();
    }
}
