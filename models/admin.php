<?php

require_once __DIR__ . '/../config/database.php';

class Admin {

    private PDO $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    // ─────────────────────────────────────────
    //  Logs / Historique
    // ─────────────────────────────────────────

    public function getLogs(int $limit = 200): array {
        $stmt = $this->db->prepare(
            "SELECT l.*, u.nom, u.prenom, u.role
             FROM logs l
             LEFT JOIN users u ON l.user_id = u.id
             ORDER BY l.created_at DESC
             LIMIT :limit"
        );
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function addLog(int $userId, string $action, string $description, string $ip = ''): int {
        $stmt = $this->db->prepare(
            "INSERT INTO logs (user_id, action, description, ip_address, created_at)
             VALUES (:uid, :action, :desc, :ip, NOW())"
        );
        $stmt->execute([
            ':uid'    => $userId,
            ':action' => $action,
            ':desc'   => $description,
            ':ip'     => $ip ?: ($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0'),
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function clearOldLogs(int $daysOld = 90): int {
        $stmt = $this->db->prepare(
            "DELETE FROM logs WHERE created_at < DATE_SUB(NOW(), INTERVAL :days DAY)"
        );
        $stmt->bindValue(':days', $daysOld, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->rowCount();
    }

    // ─────────────────────────────────────────
    //  Réclamations (vue admin)
    // ─────────────────────────────────────────

    public function getAllClaims(): array {
        $stmt = $this->db->query(
            "SELECT r.*,
                    u.nom    AS patient_nom,
                    u.prenom AS patient_prenom,
                    u.email  AS patient_email
             FROM reclamations r
             JOIN users u ON r.patient_id = u.id
             ORDER BY
                FIELD(r.priorite, 'haute', 'moyenne', 'basse'),
                r.created_at DESC"
        );
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function updateClaim(int $id, string $statut, string $reponse = ''): bool {
        $stmt = $this->db->prepare(
            "UPDATE reclamations
             SET statut = :statut, reponse = :reponse, updated_at = NOW()
             WHERE id = :id"
        );
        return $stmt->execute([
            ':statut'  => $statut,
            ':reponse' => $reponse,
            ':id'      => $id,
        ]);
    }

    // ─────────────────────────────────────────
    //  Tableau de bord — KPIs globaux
    // ─────────────────────────────────────────

    public function getGlobalStats(): array {
        $rdvTotal = $this->db->query(
            "SELECT COUNT(*) FROM rendez_vous"
        )->fetchColumn();

        $rdvMois = $this->db->query(
            "SELECT COUNT(*) FROM rendez_vous
             WHERE MONTH(date) = MONTH(NOW())
               AND YEAR(date) = YEAR(NOW())"
        )->fetchColumn();

        $claimsEnCours = $this->db->query(
            "SELECT COUNT(*) FROM reclamations WHERE statut = 'en_cours'"
        )->fetchColumn();

        $tauxRemplissage = $this->db->query(
            "SELECT ROUND(
                COUNT(CASE WHEN statut = 'terminé' THEN 1 END) * 100.0 / NULLIF(COUNT(*),0)
             , 1) FROM rendez_vous"
        )->fetchColumn();

        return [
            'rdv_total'        => (int) $rdvTotal,
            'rdv_ce_mois'      => (int) $rdvMois,
            'reclamations'     => (int) $claimsEnCours,
            'taux_remplissage' => (float) ($tauxRemplissage ?? 0),
        ];
    }

    // ─────────────────────────────────────────
    //  Paramètres applicatifs
    // ─────────────────────────────────────────

    public function getSetting(string $key): ?string {
        $stmt = $this->db->prepare(
            "SELECT value FROM settings WHERE `key` = :key LIMIT 1"
        );
        $stmt->execute([':key' => $key]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? $row['value'] : null;
    }

    public function setSetting(string $key, string $value): void {
        $stmt = $this->db->prepare(
            "INSERT INTO settings (`key`, value)
             VALUES (:key, :value)
             ON DUPLICATE KEY UPDATE value = :value"
        );
        $stmt->execute([':key' => $key, ':value' => $value]);
    }

    public function getAllSettings(): array {
        $stmt = $this->db->query("SELECT `key`, value FROM settings");
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $out  = [];
        foreach ($rows as $row) {
            $out[$row['key']] = $row['value'];
        }
        return $out;
    }
}// update
