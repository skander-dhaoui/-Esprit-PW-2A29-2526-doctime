<?php

require_once __DIR__ . '/../config/database.php';

/**
 * Client e-commerce : utilisateurs avec rôle client (table users).
 */
class Client {

    private Database $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    public function findById(int $id): ?array {
        try {
            $sql = "SELECT * FROM users WHERE id = :id AND role = 'client' LIMIT 1";
            $r = $this->db->query($sql, ['id' => $id]);
            return $r[0] ?? null;
        } catch (Exception $e) {
            error_log('Erreur Client::findById - ' . $e->getMessage());
            return null;
        }
    }

    public function findByUserId(int $userId): ?array {
        return $this->findById($userId);
    }

    /**
     * @return array<int, array<string,mixed>>
     */
    public function getTopClients(int $limit = 10): array {
        try {
            $lim = max(1, min(50, $limit));
            $sql = "SELECT u.id, u.nom, u.prenom, u.email, COUNT(c.id) AS nb_commandes, COALESCE(SUM(c.total_ttc),0) AS montant
                    FROM users u
                    INNER JOIN commandes c ON c.user_id = u.id
                    WHERE u.role = 'client'
                    GROUP BY u.id, u.nom, u.prenom, u.email
                    ORDER BY montant DESC
                    LIMIT $lim";
            return $this->db->query($sql);
        } catch (Exception $e) {
            error_log('Erreur Client::getTopClients - ' . $e->getMessage());
            return [];
        }
    }
}
