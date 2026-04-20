<?php

require_once __DIR__ . '/../config/database.php';

class Ordonnance {

    private Database $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    // ─────────────────────────────────────────
    //  CRUD de base - adapté à votre structure
    // ─────────────────────────────────────────
public function create(array $data): ?int {
    try {
        $sql = "INSERT INTO ordonnances (numero_ordonnance, patient_id, medecin_id, rdv_id, date_ordonnance, date_expiration, contenu, diagnostic, status, created_at, updated_at) 
                VALUES (:numero_ordonnance, :patient_id, :medecin_id, :rdv_id, :date_ordonnance, :date_expiration, :contenu, :diagnostic, :status, NOW(), NOW())";
        
        $params = [
            ':numero_ordonnance' => $data['numero_ordonnance'] ?? '',
            ':patient_id' => $data['patient_id'] ?? 0,
            ':medecin_id' => $data['medecin_id'] ?? 0,
            ':rdv_id' => $data['rdv_id'] ?? null,
            ':date_ordonnance' => $data['date_ordonnance'] ?? date('Y-m-d'),
            ':date_expiration' => $data['date_expiration'] ?? $data['date_validite'] ?? null,
            ':contenu' => $data['contenu'] ?? '',
            ':diagnostic' => $data['diagnostic'] ?? '',
            ':status' => $data['status'] ?? 'active'
        ];
        
        error_log("Insertion ordonnance - rdv_id: " . ($params[':rdv_id'] ?? 'null'));
        error_log("SQL: " . $sql);
        error_log("Params: " . print_r($params, true));
        
        $result = $this->db->execute($sql, $params);
        return $result ? $this->db->lastInsertId() : null;
    } catch (Exception $e) {
        error_log('Erreur Ordonnance::create - ' . $e->getMessage());
        return null;
    }
}

    public function getById(int $id): ?array {
        try {
            $sql = "SELECT o.*, 
                           CONCAT(u_patient.prenom, ' ', u_patient.nom) AS patient_nom,
                           u_patient.email AS patient_email,
                           u_patient.telephone AS patient_telephone,
                           CONCAT(u_medecin.prenom, ' ', u_medecin.nom) AS medecin_nom,
                           u_medecin.email AS medecin_email,
                           m.specialite
                    FROM ordonnances o
                    LEFT JOIN users u_patient ON o.patient_id = u_patient.id
                    LEFT JOIN users u_medecin ON o.medecin_id = u_medecin.id
                    LEFT JOIN medecins m ON o.medecin_id = m.user_id
                    WHERE o.id = :id";

            $result = $this->db->query($sql, ['id' => $id]);
            return $result ? $result[0] : null;
        } catch (Exception $e) {
            error_log('Erreur Ordonnance::getById - ' . $e->getMessage());
            return null;
        }
    }

    public function getByNumero(string $numero): ?array {
        try {
            $sql = "SELECT o.*, 
                           CONCAT(u_patient.prenom, ' ', u_patient.nom) AS patient_nom,
                           CONCAT(u_medecin.prenom, ' ', u_medecin.nom) AS medecin_nom
                    FROM ordonnances o
                    LEFT JOIN users u_patient ON o.patient_id = u_patient.id
                    LEFT JOIN users u_medecin ON o.medecin_id = u_medecin.id
                    WHERE o.numero_ordonnance = :numero";

            $result = $this->db->query($sql, ['numero' => $numero]);
            return $result ? $result[0] : null;
        } catch (Exception $e) {
            error_log('Erreur Ordonnance::getByNumero - ' . $e->getMessage());
            return null;
        }
    }

    public function update(int $id, array $data): bool {
        try {
            $fields = [];
            $values = ['id' => $id];

            $allowedFields = ['contenu', 'diagnostic', 'status', 'date_expiration'];
            foreach ($data as $key => $value) {
                if (in_array($key, $allowedFields)) {
                    $fields[] = "$key = :$key";
                    $values[$key] = $value;
                }
            }

            if (empty($fields)) {
                return false;
            }

            $fields[] = "updated_at = NOW()";
            $sql = "UPDATE ordonnances SET " . implode(', ', $fields) . " WHERE id = :id";
            return $this->db->execute($sql, $values);
        } catch (Exception $e) {
            error_log('Erreur Ordonnance::update - ' . $e->getMessage());
            return false;
        }
    }

    public function delete(int $id): bool {
        try {
            $sql = "DELETE FROM ordonnances WHERE id = :id";
            return $this->db->execute($sql, ['id' => $id]);
        } catch (Exception $e) {
            error_log('Erreur Ordonnance::delete - ' . $e->getMessage());
            return false;
        }
    }

    // ─────────────────────────────────────────
    //  Récupération avec filtres
    // ─────────────────────────────────────────
    public function getAll(): array {
        try {
            $sql = "
                SELECT o.*, 
                       CONCAT(u_patient.prenom, ' ', u_patient.nom) AS patient_nom,
                       u_patient.email AS patient_email,
                       u_patient.telephone AS patient_telephone,
                       CONCAT(u_medecin.prenom, ' ', u_medecin.nom) AS medecin_nom,
                       u_medecin.email AS medecin_email,
                       m.specialite
                FROM ordonnances o
                LEFT JOIN users u_patient ON o.patient_id = u_patient.id
                LEFT JOIN users u_medecin ON o.medecin_id = u_medecin.id
                LEFT JOIN medecins m ON o.medecin_id = m.user_id
                ORDER BY o.id DESC
            ";
            
            return $this->db->query($sql);
        } catch (Exception $e) {
            error_log('Erreur Ordonnance::getAll: ' . $e->getMessage());
            return [];
        }
    }

    public function getAllByPatient(int $patientId): array {
        try {
            $sql = "SELECT o.*, 
                           CONCAT(u_medecin.prenom, ' ', u_medecin.nom) AS medecin_nom,
                           m.specialite
                    FROM ordonnances o
                    LEFT JOIN users u_medecin ON o.medecin_id = u_medecin.id
                    LEFT JOIN medecins m ON o.medecin_id = m.user_id
                    WHERE o.patient_id = :patient_id
                    ORDER BY o.date_ordonnance DESC";

            return $this->db->query($sql, ['patient_id' => $patientId]);
        } catch (Exception $e) {
            error_log('Erreur Ordonnance::getAllByPatient - ' . $e->getMessage());
            return [];
        }
    }

    public function getAllByMedecin(int $medecinId): array {
        try {
            $sql = "SELECT o.*, 
                           CONCAT(u_patient.prenom, ' ', u_patient.nom) AS patient_nom,
                           u_patient.email AS patient_email
                    FROM ordonnances o
                    LEFT JOIN users u_patient ON o.patient_id = u_patient.id
                    WHERE o.medecin_id = :medecin_id
                    ORDER BY o.date_ordonnance DESC";

            return $this->db->query($sql, ['medecin_id' => $medecinId]);
        } catch (Exception $e) {
            error_log('Erreur Ordonnance::getAllByMedecin - ' . $e->getMessage());
            return [];
        }
    }

    public function search(string $query): array {
        try {
            $sql = "SELECT o.*, 
                           CONCAT(u_patient.prenom, ' ', u_patient.nom) AS patient_nom,
                           CONCAT(u_medecin.prenom, ' ', u_medecin.nom) AS medecin_nom
                    FROM ordonnances o
                    LEFT JOIN users u_patient ON o.patient_id = u_patient.id
                    LEFT JOIN users u_medecin ON o.medecin_id = u_medecin.id
                    WHERE (o.numero_ordonnance LIKE :query 
                           OR u_patient.nom LIKE :query 
                           OR u_patient.prenom LIKE :query
                           OR u_medecin.nom LIKE :query)
                    ORDER BY o.id DESC";

            return $this->db->query($sql, ['query' => "%$query%"]);
        } catch (Exception $e) {
            error_log('Erreur Ordonnance::search - ' . $e->getMessage());
            return [];
        }
    }

    // ─────────────────────────────────────────
    //  Génération numéro d'ordonnance
    // ─────────────────────────────────────────
    public function generateNumeroOrdonnance(): string {
        $prefix = 'ORD';
        $date = date('Ymd');
        $random = strtoupper(substr(uniqid(), -6));
        $numero = $prefix . $date . '_' . $random;
        
        // Vérifier l'unicité
        while ($this->getByNumero($numero)) {
            $random = strtoupper(substr(uniqid(), -6));
            $numero = $prefix . $date . '_' . $random;
        }
        
        return $numero;
    }
/**
 * Récupère les médicaments d'une ordonnance
 */
public function getMedicaments(int $ordonnanceId): array {
    try {
        // Si vous avez une table ordonnance_medicaments
        $sql = "SELECT * FROM ordonnance_medicaments WHERE ordonnance_id = :ordonnance_id ORDER BY id ASC";
        return $this->db->query($sql, ['ordonnance_id' => $ordonnanceId]);
    } catch (Exception $e) {
        error_log('Erreur Ordonnance::getMedicaments - ' . $e->getMessage());
        return [];
    }
}
    // ─────────────────────────────────────────
    //  Statistiques
    // ─────────────────────────────────────────
    public function getStats(): array {
        try {
            $sql = "SELECT 
                           COUNT(*) as total,
                           SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as actives,
                           SUM(CASE WHEN status = 'expired' THEN 1 ELSE 0 END) as expirees,
                           SUM(CASE WHEN status = 'en_attente' THEN 1 ELSE 0 END) as en_attente
                    FROM ordonnances";

            $result = $this->db->query($sql);
            return $result ? $result[0] : ['total' => 0, 'actives' => 0, 'expirees' => 0, 'en_attente' => 0];
        } catch (Exception $e) {
            error_log('Erreur Ordonnance::getStats - ' . $e->getMessage());
            return ['total' => 0, 'actives' => 0, 'expirees' => 0, 'en_attente' => 0];
        }
    }

    // ═══════════════════════════════════════════════════════════
    //  JOINTURES - Relation Ordonnance ↔ RendezVous/Disponibilite
    // ═══════════════════════════════════════════════════════════

    /**
     * Récupère une ordonnance avec son rendez-vous et ses détails complets (INNER JOIN)
     * Pattern: getWithRendezVous($ordonnanceId)
     * 
     * @param int $ordonnanceId ID de l'ordonnance
     * @return array|null Ordonnance avec rendez-vous associé
     */
    public function getWithRendezVous(int $ordonnanceId): ?array {
        try {
            $sql = "SELECT o.*, 
                           rv.id as rv_id, rv.titre, rv.description, rv.date_debut, rv.date_fin, rv.statut as rv_statut,
                           rv.patient_id, rv.medecin_id,
                           CONCAT(u_patient.prenom, ' ', u_patient.nom) AS patient_nom,
                           u_patient.email AS patient_email,
                           CONCAT(u_medecin.prenom, ' ', u_medecin.nom) AS medecin_nom,
                           u_medecin.email AS medecin_email,
                           m.specialite
                    FROM ordonnances o
                    INNER JOIN rendez_vous rv ON o.rdv_id = rv.id
                    LEFT JOIN users u_patient ON rv.patient_id = u_patient.id
                    LEFT JOIN users u_medecin ON rv.medecin_id = u_medecin.id
                    LEFT JOIN medecins m ON o.medecin_id = m.user_id
                    WHERE o.id = :ordonnance_id";

            $result = $this->db->query($sql, ['ordonnance_id' => $ordonnanceId]);
            return $result ? $result[0] : null;
        } catch (Exception $e) {
            error_log('Erreur Ordonnance::getWithRendezVous - ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Récupère les ordonnances d'un rendez-vous avec ses détails complets (INNER JOIN)
     * Pattern: getByRendezVous($rendezvousId)
     * 
     * @param int $rendezvousId ID du rendez-vous
     * @return array Liste des ordonnances pour ce rendez-vous
     */
    public function getByRendezVous(int $rendezvousId): array {
        try {
            $sql = "SELECT o.*, 
                           rv.titre, rv.date_debut, rv.date_fin, rv.statut as rv_statut,
                           CONCAT(u_patient.prenom, ' ', u_patient.nom) AS patient_nom,
                           CONCAT(u_medecin.prenom, ' ', u_medecin.nom) AS medecin_nom,
                           m.specialite
                    FROM ordonnances o
                    INNER JOIN rendez_vous rv ON o.rdv_id = rv.id
                    LEFT JOIN users u_patient ON rv.patient_id = u_patient.id
                    LEFT JOIN users u_medecin ON rv.medecin_id = u_medecin.id
                    LEFT JOIN medecins m ON o.medecin_id = m.user_id
                    WHERE o.rdv_id = :rendezvous_id
                    ORDER BY o.date_ordonnance DESC";

            return $this->db->query($sql, ['rendezvous_id' => $rendezvousId]);
        } catch (Exception $e) {
            error_log('Erreur Ordonnance::getByRendezVous - ' . $e->getMessage());
            return [];
        }
    }
}
?>