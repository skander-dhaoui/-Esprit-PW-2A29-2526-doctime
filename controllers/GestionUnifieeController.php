<?php
/**
 * Contrôleur Gestion Unifiée
 * Gère l'intégration entre Utilisateurs, Événements et Pharmacie
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/Evenement.php';
require_once __DIR__ . '/../models/Pharmacie.php';
require_once __DIR__ . '/../models/Participation.php';
require_once __DIR__ . '/../models/Produit.php';

class GestionUnifieeController {
    private $pdo;
    private $user;
    private $evenement;
    private $pharmacie;
    private $participation;
    private $produit;
    
    public function __construct() {
        $this->pdo = Database::getInstance()->getConnection();
        $this->user = new User();
        $this->evenement = new Evenement();
        $this->pharmacie = new Pharmacie();
        $this->participation = new Participation();
        $this->produit = new Produit();
    }

    /**
     * Tableau de bord unifié
     * Affiche l'état global des utilisateurs, événements et pharmacie
     */
    public function tableauBordUnifie() {
        try {
            $data = [
                'utilisateurs' => $this->obtenirStatistiquesUtilisateurs(),
                'evenements' => $this->obtenirStatistiquesEvenements(),
                'pharmacie' => $this->obtenirStatistiquesPharmacie(),
                'participations' => $this->obtenirParticipationsActuelles(),
                'dernieresMiseAJour' => $this->obtenirDernieresMiseAJour()
            ];
            
            return $this->succes('Tableau de bord chargé', $data);
        } catch (Exception $e) {
            return $this->erreur('Erreur : ' . $e->getMessage());
        }
    }

    /**
     * Créer une participation avec produits pharmacie
     */
    public function creerParticipationAvecPharmacie($userId, $eventId, $produits = []) {
        try {
            $this->pdo->beginTransaction();
            
            // Vérifier l'utilisateur
            $stmt = $this->pdo->prepare("SELECT id FROM users WHERE id = :id");
            $stmt->execute([':id' => $userId]);
            if (!$stmt->fetch()) {
                throw new Exception("Utilisateur non trouvé");
            }
            
            // Vérifier l'événement
            $stmt = $this->pdo->prepare("SELECT id FROM events WHERE id = :id");
            $stmt->execute([':id' => $eventId]);
            if (!$stmt->fetch()) {
                throw new Exception("Événement non trouvé");
            }
            
            // Créer la participation
            $stmt = $this->pdo->prepare("
                INSERT INTO participations (user_id, event_id, date_participation, statut, createdAt)
                VALUES (:user_id, :event_id, NOW(), 'confirmee', NOW())
            ");
            $stmt->execute([
                ':user_id' => $userId,
                ':event_id' => $eventId
            ]);
            
            $participationId = $this->pdo->lastInsertId();
            
            // Ajouter les produits pharmacie associés
            foreach ($produits as $produitId) {
                $stmt = $this->pdo->prepare("
                    INSERT INTO participation_produits (participation_id, produit_id, createdAt)
                    VALUES (:participation_id, :produit_id, NOW())
                ");
                $stmt->execute([
                    ':participation_id' => $participationId,
                    ':produit_id' => $produitId
                ]);
            }
            
            $this->pdo->commit();
            
            return $this->succes(
                'Participation créée avec produits pharmacie',
                ['participation_id' => $participationId, 'produits_ajoutes' => count($produits)]
            );
        } catch (Exception $e) {
            $this->pdo->rollBack();
            return $this->erreur('Erreur lors de la création : ' . $e->getMessage());
        }
    }

    /**
     * Récupérer les participations d'un utilisateur avec produits
     */
    public function obtenirParticipationsUtilisateur($userId) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT 
                    p.id,
                    p.user_id,
                    p.event_id,
                    e.titre AS event_titre,
                    e.date_debut AS event_date,
                    e.lieu AS event_lieu,
                    p.statut,
                    p.date_participation,
                    COUNT(pr.id) AS nombre_produits
                FROM participations p
                LEFT JOIN events e ON p.event_id = e.id
                LEFT JOIN participation_produits pp ON p.id = pp.participation_id
                LEFT JOIN produits pr ON pp.produit_id = pr.id
                WHERE p.user_id = :user_id
                GROUP BY p.id
                ORDER BY p.date_participation DESC
            ");
            
            $stmt->execute([':user_id' => $userId]);
            $participations = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Enrichir avec les détails des produits
            foreach ($participations as &$p) {
                $p['produits'] = $this->obtenirProduitsParticipation($p['id']);
            }
            
            return $this->succes('Participations récupérées', $participations);
        } catch (Exception $e) {
            return $this->erreur('Erreur : ' . $e->getMessage());
        }
    }

    /**
     * Lister tous les événements avec participations
     */
    public function listerEvenementsAvecParticipations($filtres = []) {
        try {
            $query = "
                SELECT 
                    e.id,
                    e.titre,
                    e.date_debut,
                    e.date_fin,
                    e.lieu,
                    e.statut,
                    COUNT(DISTINCT p.id) AS nombre_participants,
                    COUNT(DISTINCT pr.id) AS nombre_produits_distribues
                FROM events e
                LEFT JOIN participations p ON e.id = p.event_id
                LEFT JOIN participation_produits pp ON p.id = pp.participation_id
                LEFT JOIN produits pr ON pp.produit_id = pr.id
                WHERE 1=1
            ";
            
            $params = [];
            
            if (!empty($filtres['statut'])) {
                $query .= " AND e.statut = :statut";
                $params[':statut'] = $filtres['statut'];
            }
            
            if (!empty($filtres['date_debut'])) {
                $query .= " AND e.date_debut >= :date_debut";
                $params[':date_debut'] = $filtres['date_debut'];
            }
            
            if (!empty($filtres['date_fin'])) {
                $query .= " AND e.date_fin <= :date_fin";
                $params[':date_fin'] = $filtres['date_fin'];
            }
            
            $query .= " GROUP BY e.id ORDER BY e.date_debut DESC";
            
            $stmt = $this->pdo->prepare($query);
            $stmt->execute($params);
            
            return $this->succes('Événements récupérés', $stmt->fetchAll(PDO::FETCH_ASSOC));
        } catch (Exception $e) {
            return $this->erreur('Erreur : ' . $e->getMessage());
        }
    }

    /**
     * Gérer les produits pharmacie pour une participation
     */
    public function gererProduitsParticipation($participationId, $action = 'lister', $produitId = null) {
        try {
            if ($action === 'lister') {
                $stmt = $this->pdo->prepare("
                    SELECT 
                        pr.id,
                        pr.nom,
                        pr.description,
                        pr.prix,
                        pr.stock,
                        p.nom AS pharmacie_nom
                    FROM participation_produits pp
                    LEFT JOIN produits pr ON pp.produit_id = pr.id
                    LEFT JOIN pharmacies p ON pr.pharmacie_id = p.id
                    WHERE pp.participation_id = :participation_id
                ");
                $stmt->execute([':participation_id' => $participationId]);
                
                return $this->succes('Produits récupérés', $stmt->fetchAll(PDO::FETCH_ASSOC));
            } 
            elseif ($action === 'ajouter') {
                if (!$produitId) {
                    throw new Exception("ID produit requis");
                }
                
                $stmt = $this->pdo->prepare("
                    INSERT INTO participation_produits (participation_id, produit_id, createdAt)
                    VALUES (:participation_id, :produit_id, NOW())
                ");
                $stmt->execute([
                    ':participation_id' => $participationId,
                    ':produit_id' => $produitId
                ]);
                
                return $this->succes('Produit ajouté à la participation');
            }
            elseif ($action === 'retirer') {
                if (!$produitId) {
                    throw new Exception("ID produit requis");
                }
                
                $stmt = $this->pdo->prepare("
                    DELETE FROM participation_produits 
                    WHERE participation_id = :participation_id AND produit_id = :produit_id
                ");
                $stmt->execute([
                    ':participation_id' => $participationId,
                    ':produit_id' => $produitId
                ]);
                
                return $this->succes('Produit retiré de la participation');
            }
        } catch (Exception $e) {
            return $this->erreur('Erreur : ' . $e->getMessage());
        }
    }

    /**
     * Rapport intégré
     */
    public function genererRapportIntegre($dateDebut, $dateFin) {
        try {
            $rapport = [
                'periode' => [
                    'debut' => $dateDebut,
                    'fin' => $dateFin
                ],
                'utilisateurs' => $this->obtenirStatistiquesUtilisateurs(),
                'evenements' => $this->obtenirStatistiquesEvenements(),
                'pharmacie' => $this->obtenirStatistiquesPharmacie(),
                'participations_detail' => $this->obtenirDetailParticipations($dateDebut, $dateFin),
                'distribution_produits' => $this->obtenirDistributionProduits($dateDebut, $dateFin)
            ];
            
            return $this->succes('Rapport généré', $rapport);
        } catch (Exception $e) {
            return $this->erreur('Erreur : ' . $e->getMessage());
        }
    }

    /**
     * Méthodes privées d'aide
     */
    
    private function obtenirStatistiquesUtilisateurs() {
        try {
            $stmt = $this->pdo->query("
                SELECT 
                    COUNT(*) AS total,
                    SUM(CASE WHEN statut = 'actif' THEN 1 ELSE 0 END) AS actifs,
                    SUM(CASE WHEN statut = 'inactif' THEN 1 ELSE 0 END) AS inactifs,
                    SUM(CASE WHEN role = 'admin' THEN 1 ELSE 0 END) AS admins,
                    SUM(CASE WHEN role = 'patient' THEN 1 ELSE 0 END) AS patients
                FROM users
            ");
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            return [];
        }
    }

    private function obtenirStatistiquesEvenements() {
        try {
            $stmt = $this->pdo->query("
                SELECT 
                    COUNT(*) AS total,
                    SUM(CASE WHEN statut = 'planifie' THEN 1 ELSE 0 END) AS planifies,
                    SUM(CASE WHEN statut = 'en_cours' THEN 1 ELSE 0 END) AS en_cours,
                    SUM(CASE WHEN statut = 'termine' THEN 1 ELSE 0 END) AS termines,
                    SUM(CASE WHEN date_debut >= CURDATE() THEN 1 ELSE 0 END) AS a_venir
                FROM events
            ");
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            return [];
        }
    }

    private function obtenirStatistiquesPharmacie() {
        try {
            $stmt = $this->pdo->query("
                SELECT 
                    COUNT(DISTINCT p.id) AS nombre_pharmacies,
                    COUNT(DISTINCT pr.id) AS nombre_produits,
                    SUM(pr.stock) AS stock_total,
                    SUM(pr.prix * pr.stock) AS valeur_stock
                FROM pharmacies p
                LEFT JOIN produits pr ON p.id = pr.pharmacie_id
            ");
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            return [];
        }
    }

    private function obtenirParticipationsActuelles() {
        try {
            $stmt = $this->pdo->query("
                SELECT 
                    COUNT(*) AS total,
                    SUM(CASE WHEN statut = 'confirmee' THEN 1 ELSE 0 END) AS confirmees,
                    SUM(CASE WHEN statut = 'en_attente' THEN 1 ELSE 0 END) AS en_attente,
                    SUM(CASE WHEN statut = 'annulee' THEN 1 ELSE 0 END) AS annulees
                FROM participations
            ");
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            return [];
        }
    }

    private function obtenirDernieresMiseAJour() {
        return [
            'utilisateurs' => date('Y-m-d H:i:s'),
            'evenements' => date('Y-m-d H:i:s'),
            'pharmacie' => date('Y-m-d H:i:s')
        ];
    }

    private function obtenirProduitsParticipation($participationId) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT pr.id, pr.nom, pr.description, pr.prix
                FROM participation_produits pp
                LEFT JOIN produits pr ON pp.produit_id = pr.id
                WHERE pp.participation_id = :participation_id
            ");
            $stmt->execute([':participation_id' => $participationId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            return [];
        }
    }

    private function obtenirDetailParticipations($dateDebut, $dateFin) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT 
                    COUNT(*) AS nombre_participations,
                    AVG(DATE_DIFF(CURDATE(), p.date_participation)) AS jours_moyen,
                    COUNT(DISTINCT p.user_id) AS utilisateurs_uniques,
                    COUNT(DISTINCT p.event_id) AS evenements
                FROM participations p
                WHERE p.date_participation BETWEEN :debut AND :fin
            ");
            $stmt->execute([
                ':debut' => $dateDebut,
                ':fin' => $dateFin
            ]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            return [];
        }
    }

    private function obtenirDistributionProduits($dateDebut, $dateFin) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT 
                    pr.nom AS produit,
                    COUNT(*) AS nombre_distributions,
                    SUM(pr.prix) AS total_valeur,
                    p.nom AS pharmacie
                FROM participation_produits pp
                LEFT JOIN participations part ON pp.participation_id = part.id
                LEFT JOIN produits pr ON pp.produit_id = pr.id
                LEFT JOIN pharmacies p ON pr.pharmacie_id = p.id
                WHERE part.date_participation BETWEEN :debut AND :fin
                GROUP BY pr.id, p.id
            ");
            $stmt->execute([
                ':debut' => $dateDebut,
                ':fin' => $dateFin
            ]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            return [];
        }
    }

    /**
     * Format de réponse
     */
    private function succes($message, $data = null) {
        return [
            'statut' => 'succes',
            'message' => $message,
            'data' => $data
        ];
    }

    private function erreur($message) {
        return [
            'statut' => 'erreur',
            'message' => $message
        ];
    }
}
?>
