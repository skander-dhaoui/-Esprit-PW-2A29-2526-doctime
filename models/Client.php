<?php
// models/Client.php

require_once __DIR__ . '/../config/database.php';

class Client {

    private Database $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    public function findById(int $id): ?array {
        try {
            $sql = "SELECT id, nom, prenom, email, telephone, adresse
                    FROM users
                    WHERE id = :id";
            $rows = $this->db->query($sql, ['id' => $id]);
            return $rows ? $rows[0] : null;
        } catch (Exception $e) {
            error_log('Erreur Client::findById - ' . $e->getMessage());
            return null;
        }
    }

    public function findByUserId(int $userId): ?array {
        return $this->findById($userId);
    }

    public function getTopClients(int $limit = 10): array {
        try {
            $limit = max(1, (int)$limit);
            $sql = "SELECT u.id, u.nom, u.prenom, u.email,
                           COUNT(c.id) AS nb_commandes,
                           COALESCE(SUM(c.total_ttc), 0) AS total_depense
                    FROM users u
                    JOIN commandes c ON c.user_id = u.id
                    GROUP BY u.id, u.nom, u.prenom, u.email
                    ORDER BY total_depense DESC
                    LIMIT $limit";
            return $this->db->query($sql);
        } catch (Exception $e) {
            error_log('Erreur Client::getTopClients - ' . $e->getMessage());
            return [];
        }
    }
}
// update
