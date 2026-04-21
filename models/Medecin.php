<?php

require_once __DIR__ . '/../config/database.php';

class Medecin {

    private PDO $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    // ─────────────────────────────────────────
    //  Profil médecin
    // ─────────────────────────────────────────

public function findByUserId(int $userId): array|false {
    try {
        $stmt = $this->db->prepare(
            "SELECT m.*, u.nom, u.prenom, u.email, u.telephone,
                    u.adresse, u.date_naissance, u.statut, u.created_at
             FROM medecins m
             JOIN users u ON m.user_id = u.id
             WHERE m.user_id = :uid
             LIMIT 1"
        );
        $stmt->execute([':uid' => $userId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Retourner un tableau vide si aucun résultat, pas false
        if ($result === false) {
            return [];
        }
        return $result;
    } catch (Exception $e) {
        error_log('Erreur Medecin::findByUserId - ' . $e->getMessage());
        return [];
    }
}

    public function getById(int $id): array|false {
        try {
            $stmt = $this->db->prepare("SELECT * FROM medecins WHERE id = :id LIMIT 1");
            $stmt->execute([':id' => $id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log('Erreur Medecin::getById - ' . $e->getMessage());
            return false;
        }
    }


    

    public function update(int $userId, array $data): bool {
        try {
            $set    = [];
            $params = [':user_id' => $userId];
            foreach ($data as $key => $value) {
                $set[]         = "$key = :$key";
                $params[":$key"] = $value;
            }
            if (empty($set)) return false;
            $stmt = $this->db->prepare(
                "UPDATE medecins SET " . implode(', ', $set) . " WHERE user_id = :user_id"
            );
            return $stmt->execute($params);
        } catch (Exception $e) {
            error_log('Erreur Medecin::update - ' . $e->getMessage());
            return false;
        }
    }

    public function validate(int $userId, string $statutValidation, string $commentaire = ''): bool {
        try {
            $stmt = $this->db->prepare(
                "UPDATE medecins
                 SET statut_validation = :statut, commentaire_validation = :commentaire
                 WHERE user_id = :user_id"
            );
            return $stmt->execute([
                ':statut'      => $statutValidation,
                ':commentaire' => $commentaire,
                ':user_id'     => $userId,
            ]);
        } catch (Exception $e) {
            error_log('Erreur Medecin::validate - ' . $e->getMessage());
            return false;
        }
    }

    // ─────────────────────────────────────────
    //  Rendez-vous
    // ─────────────────────────────────────────

    public function getTodayAppointments(int $medecinId): array {
        try {
            $stmt = $this->db->prepare(
                "SELECT rv.*, u.nom, u.prenom, u.email, u.telephone
                 FROM rendez_vous rv
                 JOIN users u ON rv.patient_id = u.id
                 WHERE rv.medecin_id = :mid
                   AND DATE(rv.date) = CURDATE()
                 ORDER BY rv.heure ASC"
            );
            $stmt->execute([':mid' => $medecinId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log('Erreur Medecin::getTodayAppointments - ' . $e->getMessage());
            return [];
        }
    }

    public function getUpcomingAppointments(int $medecinId): array {
        try {
            $stmt = $this->db->prepare(
                "SELECT rv.*, u.nom, u.prenom
                 FROM rendez_vous rv
                 JOIN users u ON rv.patient_id = u.id
                 WHERE rv.medecin_id = :mid
                   AND rv.date > CURDATE()
                   AND rv.statut IN ('en_attente','confirmé')
                 ORDER BY rv.date ASC, rv.heure ASC
                 LIMIT 10"
            );
            $stmt->execute([':mid' => $medecinId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log('Erreur Medecin::getUpcomingAppointments - ' . $e->getMessage());
            return [];
        }
    }

    public function getAllAppointments(int $medecinId): array {
        try {
            $stmt = $this->db->prepare(
                "SELECT rv.*, u.nom, u.prenom, u.email
                 FROM rendez_vous rv
                 JOIN users u ON rv.patient_id = u.id
                 WHERE rv.medecin_id = :mid
                 ORDER BY rv.date DESC, rv.heure DESC"
            );
            $stmt->execute([':mid' => $medecinId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log('Erreur Medecin::getAllAppointments - ' . $e->getMessage());
            return [];
        }
    }

    public function getAppointmentById(int $id): array|false {
        try {
            $stmt = $this->db->prepare("SELECT * FROM rendez_vous WHERE id = :id LIMIT 1");
            $stmt->execute([':id' => $id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            return false;
        }
    }

    public function updateAppointmentStatus(int $id, string $status): bool {
        try {
            $stmt = $this->db->prepare(
                "UPDATE rendez_vous SET statut = :statut WHERE id = :id"
            );
            return $stmt->execute([':statut' => $status, ':id' => $id]);
        } catch (Exception $e) {
            return false;
        }
    }

    public function completeAppointment(int $id, string $note = ''): bool {
        try {
            $stmt = $this->db->prepare(
                "UPDATE rendez_vous SET statut = 'terminé', note_medecin = :note WHERE id = :id"
            );
            return $stmt->execute([':note' => $note, ':id' => $id]);
        } catch (Exception $e) {
            return false;
        }
    }

    // ─────────────────────────────────────────
    //  Patients
    // ─────────────────────────────────────────

    public function getPatients(int $medecinId): array {
        try {
            $stmt = $this->db->prepare(
                "SELECT DISTINCT u.id, u.nom, u.prenom, u.email, u.telephone,
                        MAX(rv.date) AS derniere_visite
                 FROM rendez_vous rv
                 JOIN users u ON rv.patient_id = u.id
                 WHERE rv.medecin_id = :mid AND rv.statut = 'terminé'
                 GROUP BY u.id
                 ORDER BY u.nom ASC"
            );
            $stmt->execute([':mid' => $medecinId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log('Erreur Medecin::getPatients - ' . $e->getMessage());
            return [];
        }
    }

    public function hasPatient(int $medecinId, int $patientId): bool {
        try {
            $stmt = $this->db->prepare(
                "SELECT COUNT(*) FROM rendez_vous
                 WHERE medecin_id = :mid AND patient_id = :pid"
            );
            $stmt->execute([':mid' => $medecinId, ':pid' => $patientId]);
            return (int) $stmt->fetchColumn() > 0;
        } catch (Exception $e) {
            return false;
        }
    }

    public function getPatientHistory(int $medecinId, int $patientId): array {
        try {
            $stmt = $this->db->prepare(
                "SELECT * FROM rendez_vous
                 WHERE medecin_id = :mid AND patient_id = :pid
                 ORDER BY date DESC"
            );
            $stmt->execute([':mid' => $medecinId, ':pid' => $patientId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            return [];
        }
    }

    // ─────────────────────────────────────────
    //  Disponibilités
    // ─────────────────────────────────────────

    public function getAvailabilities(int $medecinId): array {
        try {
            $stmt = $this->db->prepare(
                "SELECT * FROM disponibilites WHERE medecin_id = :mid ORDER BY jour_semaine, heure_debut"
            );
            $stmt->execute([':mid' => $medecinId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            return [];
        }
    }

    public function createAvailability(array $data): int {
        try {
            $stmt = $this->db->prepare(
                "INSERT INTO disponibilites (medecin_id, jour_semaine, heure_debut, heure_fin, actif)
                 VALUES (:medecin_id, :jour_semaine, :heure_debut, :heure_fin, :actif)"
            );
            $stmt->execute($data);
            return (int) $this->db->lastInsertId();
        } catch (Exception $e) {
            return 0;
        }
    }

    public function deleteAvailability(int $id, int $medecinId): bool {
        try {
            $stmt = $this->db->prepare(
                "DELETE FROM disponibilites WHERE id = :id AND medecin_id = :mid"
            );
            return $stmt->execute([':id' => $id, ':mid' => $medecinId]);
        } catch (Exception $e) {
            return false;
        }
    }

    // ─────────────────────────────────────────
    //  Statistiques
    // ─────────────────────────────────────────

    public function getStats(int $medecinId): array {
        try {
            $total = $this->db->prepare(
                "SELECT COUNT(*) FROM rendez_vous WHERE medecin_id = :mid"
            );
            $total->execute([':mid' => $medecinId]);

            $today = $this->db->prepare(
                "SELECT COUNT(*) FROM rendez_vous
                 WHERE medecin_id = :mid AND DATE(date) = CURDATE()"
            );
            $today->execute([':mid' => $medecinId]);

            $patients = $this->db->prepare(
                "SELECT COUNT(DISTINCT patient_id) FROM rendez_vous WHERE medecin_id = :mid"
            );
            $patients->execute([':mid' => $medecinId]);

            $pending = $this->db->prepare(
                "SELECT COUNT(*) FROM rendez_vous
                 WHERE medecin_id = :mid AND statut = 'en_attente'"
            );
            $pending->execute([':mid' => $medecinId]);

            return [
                'rdv_total'    => (int) $total->fetchColumn(),
                'rdv_today'    => (int) $today->fetchColumn(),
                'patients'     => (int) $patients->fetchColumn(),
                'rdv_pending'  => (int) $pending->fetchColumn(),
            ];
        } catch (Exception $e) {
            return ['rdv_total' => 0, 'rdv_today' => 0, 'patients' => 0, 'rdv_pending' => 0];
        }
    }

    public function getTopSpecialities(): array {
        try {
            $stmt = $this->db->query(
                "SELECT specialite, COUNT(*) AS total
                 FROM medecins
                 GROUP BY specialite
                 ORDER BY total DESC
                 LIMIT 5"
            );
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            return [];
        }
    }

    public function getMonthlyAppointments(): array {
        try {
            $stmt = $this->db->query(
                "SELECT DATE_FORMAT(date, '%Y-%m') AS mois, COUNT(*) AS total
                 FROM rendez_vous
                 WHERE date >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
                 GROUP BY mois
                 ORDER BY mois ASC"
            );
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            return [];
        }
    }


// ─────────────────────────────────────────
//  Récupération des médecins avec leurs utilisateurs (pour l'affichage)
// ─────────────────────────────────────────

/**
 * Récupère tous les médecins avec leurs informations utilisateur
 * Utilisé dans la page publique listeMedecins()
 */
public function getAllMedecinsWithUsers(): array {
    try {
        $stmt = $this->db->prepare(
            "SELECT u.id as user_id, u.nom, u.prenom, u.email, u.telephone, u.adresse, u.statut,
                    m.specialite, m.numero_ordre, m.annee_experience, m.consultation_prix, m.cabinet_adresse
             FROM users u
             INNER JOIN medecins m ON u.id = m.user_id
             WHERE u.role = 'medecin' AND u.statut = 'actif'
             ORDER BY u.nom ASC"
        );
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log('Erreur Medecin::getAllMedecinsWithUsers - ' . $e->getMessage());
        return [];
    }
}

/**
 * Alias pour getAllMedecinsWithUsers() pour compatibilité avec FrontController
 */
public function getAllWithUsers(): array {
    try {
        $stmt = $this->db->prepare(
            "SELECT u.id as user_id, u.nom, u.prenom, u.email, u.telephone, u.adresse, u.statut,
                    COALESCE(m.specialite, 'Généraliste') as specialite,
                    m.numero_ordre, m.annee_experience, m.consultation_prix, m.cabinet_adresse
             FROM users u
             LEFT JOIN medecins m ON u.id = m.user_id
             WHERE u.role = 'medecin' AND u.statut = 'actif'
             ORDER BY u.nom ASC"
        );
        $stmt->execute();
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Debug - loguer le nombre de médecins trouvés
        error_log("Nombre de médecins trouvés: " . count($result));
        
        return $result;
    } catch (Exception $e) {
        error_log('Erreur Medecin::getAllWithUsers - ' . $e->getMessage());
        return [];
    }
}

// ─────────────────────────────────────────
//  Rendez-vous avec détails patient/médecin
// ─────────────────────────────────────────

/**
 * Récupère les rendez-vous d'un médecin avec les infos patient
 */
public function getAppointmentsWithPatients(int $medecinId, ?string $statut = null, ?string $date = null): array {
    try {
        $sql = "SELECT rv.*, 
                       CONCAT(u_patient.prenom, ' ', u_patient.nom) AS patient_nom,
                       u_patient.email AS patient_email,
                       u_patient.telephone AS patient_telephone,
                       u_patient.adresse AS patient_adresse
                FROM rendez_vous rv
                JOIN users u_patient ON rv.patient_id = u_patient.id
                WHERE rv.medecin_id = :medecin_id";
        
        $params = [':medecin_id' => $medecinId];
        
        if (!empty($statut)) {
            $sql .= " AND rv.statut = :statut";
            $params[':statut'] = $statut;
        }
        
        if (!empty($date)) {
            $sql .= " AND DATE(rv.date_rendezvous) = :date";
            $params[':date'] = $date;
        }
        
        $sql .= " ORDER BY rv.date_rendezvous DESC, rv.heure_rendezvous ASC";
        
        $stmt = $this->db->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log('Erreur Medecin::getAppointmentsWithPatients - ' . $e->getMessage());
        return [];
    }
}

// ─────────────────────────────────────────
//  Rendez-vous d'un patient avec le médecin
// ─────────────────────────────────────────

/**
 * Récupère les rendez-vous d'un patient avec les infos médecin
 */
public function getPatientAppointmentsWithMedecin(int $patientId, ?string $statut = null, ?string $date = null): array {
    try {
        $sql = "SELECT rv.*, 
                       CONCAT(u_medecin.prenom, ' ', u_medecin.nom) AS medecin_nom,
                       u_medecin.email AS medecin_email,
                       m.specialite
                FROM rendez_vous rv
                JOIN users u_medecin ON rv.medecin_id = u_medecin.id
                LEFT JOIN medecins m ON rv.medecin_id = m.user_id
                WHERE rv.patient_id = :patient_id";
        
        $params = [':patient_id' => $patientId];
        
        if (!empty($statut)) {
            $sql .= " AND rv.statut = :statut";
            $params[':statut'] = $statut;
        }
        
        if (!empty($date)) {
            $sql .= " AND DATE(rv.date_rendezvous) = :date";
            $params[':date'] = $date;
        }
        
        $sql .= " ORDER BY rv.date_rendezvous DESC, rv.heure_rendezvous ASC";
        
        $stmt = $this->db->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log('Erreur Medecin::getPatientAppointmentsWithMedecin - ' . $e->getMessage());
        return [];
    }
}

// ─────────────────────────────────────────
//  Détail d'un rendez-vous avec toutes les infos
// ─────────────────────────────────────────

/**
 * Récupère les détails complets d'un rendez-vous
 */
public function getRendezVousDetail(int $id): ?array {
    try {
        $sql = "SELECT rv.*, 
                       CONCAT(u_patient.prenom, ' ', u_patient.nom) AS patient_nom,
                       u_patient.email AS patient_email,
                       u_patient.telephone AS patient_telephone,
                       u_patient.adresse AS patient_adresse,
                       CONCAT(u_medecin.prenom, ' ', u_medecin.nom) AS medecin_nom,
                       u_medecin.email AS medecin_email,
                       m.specialite,
                       m.cabinet_adresse
                FROM rendez_vous rv
                JOIN users u_patient ON rv.patient_id = u_patient.id
                JOIN users u_medecin ON rv.medecin_id = u_medecin.id
                LEFT JOIN medecins m ON rv.medecin_id = m.user_id
                WHERE rv.id = :id";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    } catch (Exception $e) {
        error_log('Erreur Medecin::getRendezVousDetail - ' . $e->getMessage());
        return null;
    }
}



}// update
