<?php
// models/CommandeLigne.php

require_once __DIR__ . '/../config/database.php';

class CommandeLigne {

    private Database $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    public function create(array $data): bool {
        try {
            $sql = "INSERT INTO commande_details (commande_id, produit_id, quantite, prix_unitaire, total_ligne)
                    VALUES (:commande_id, :produit_id, :quantite, :prix_unitaire, :total_ligne)";
            return $this->db->execute($sql, $data);
        } catch (Exception $e) {
            error_log('Erreur CommandeLigne::create - ' . $e->getMessage());
            return false;
        }
    }

    public function getByCommande(int $commandeId): array {
        try {
            $sql = "SELECT cd.*, p.nom AS produit_nom, p.reference
                    FROM commande_details cd
                    LEFT JOIN produits p ON p.id = cd.produit_id
                    WHERE cd.commande_id = :commande_id
                    ORDER BY cd.id ASC";
            return $this->db->query($sql, ['commande_id' => $commandeId]);
        } catch (Exception $e) {
            error_log('Erreur CommandeLigne::getByCommande - ' . $e->getMessage());
            return [];
        }
    }
}