<?php
require_once __DIR__ . '/../config/database.php';

class CommandeRepository
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    public function getAll(string $filter = 'all', int $offset = 0, int $limit = 20, string $search = '', string $dateDebut = '', string $dateFin = ''): array
    {
        $sql = "SELECT c.*, u.nom, u.prenom 
                FROM commandes c 
                JOIN users u ON c.user_id = u.id 
                WHERE 1=1";
        $params = [];

        if ($filter !== 'all') {
            $sql .= " AND c.status = :status";
            $params[':status'] = $filter;
        }

        if (!empty($search)) {
            $sql .= " AND (c.numero_commande LIKE :search OR u.nom LIKE :search OR u.prenom LIKE :search)";
            $params[':search'] = "%$search%";
        }

        if (!empty($dateDebut)) {
            $sql .= " AND c.date_commande >= :date_debut";
            $params[':date_debut'] = $dateDebut;
        }

        if (!empty($dateFin)) {
            $sql .= " AND c.date_commande <= :date_fin";
            $params[':date_fin'] = $dateFin;
        }

        $sql .= " ORDER BY c.date_commande DESC LIMIT :offset, :limit";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function countAll(string $filter = 'all', string $search = '', string $dateDebut = '', string $dateFin = ''): int
    {
        $sql = "SELECT COUNT(*) FROM commandes c JOIN users u ON c.user_id = u.id WHERE 1=1";
        $params = [];

        if ($filter !== 'all') {
            $sql .= " AND c.status = :status";
            $params[':status'] = $filter;
        }

        if (!empty($search)) {
            $sql .= " AND (c.numero_commande LIKE :search OR u.nom LIKE :search OR u.prenom LIKE :search)";
            $params[':search'] = "%$search%";
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return (int)$stmt->fetchColumn();
    }

    public function countByStatus(string $status): int
    {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM commandes WHERE status = :status");
        $stmt->execute([':status' => $status]);
        return (int)$stmt->fetchColumn();
    }

    public function getTotalMontant(): float
    {
        return (float)$this->db->query("SELECT SUM(total_ttc) FROM commandes WHERE status != 'annulée'")->fetchColumn();
    }

    public function getTotalMontantMois(): float
    {
        return (float)$this->db->query("SELECT SUM(total_ttc) FROM commandes WHERE status != 'annulée' AND MONTH(date_commande) = MONTH(CURRENT_DATE) AND YEAR(date_commande) = YEAR(CURRENT_DATE)")->fetchColumn();
    }

    public function getById(int $id): ?array
    {
        $stmt = $this->db->prepare("SELECT c.*, u.nom, u.prenom, u.email FROM commandes c JOIN users u ON c.user_id = u.id WHERE c.id = :id");
        $stmt->execute([':id' => $id]);
        return $stmt->fetch() ?: null;
    }

    public function getDetails(int $commandeId): array
    {
        $stmt = $this->db->prepare("SELECT cd.*, p.nom as produit_nom, p.image FROM commande_details cd JOIN produits p ON cd.produit_id = p.id WHERE cd.commande_id = :id");
        $stmt->execute([':id' => $commandeId]);
        return $stmt->fetchAll();
    }

    public function getByCommande(int $commandeId): array
    {
        return $this->getDetails($commandeId);
    }

    public function create(array $data): int
    {
        $sql = "INSERT INTO commandes (numero_commande, user_id, date_commande, total_ht, total_ttc, status, adresse_livraison, mode_paiement, notes) 
                VALUES (:numero, :user_id, NOW(), :total_ht, :total_ttc, :status, :adresse, :paiement, :notes)";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':numero'   => $data['numero_commande'] ?? $data['numero'],
            ':user_id'  => $data['user_id'] ?? $data['client_id'],
            ':total_ht' => $data['total_ht'],
            ':total_ttc'=> $data['total_ttc'] ?? $data['montant_total'],
            ':status'   => $data['status'] ?? 'en_attente',
            ':adresse'  => $data['adresse_livraison'],
            ':paiement' => $data['mode_paiement'] ?? 'virement',
            ':notes'    => $data['notes'] ?? ''
        ]);
        return (int)$this->db->lastInsertId();
    }

    public function addDetail(array $data): bool
    {
        $sql = "INSERT INTO commande_details (commande_id, produit_id, quantite, prix_unitaire, total) 
                VALUES (:commande_id, :produit_id, :quantite, :prix_unitaire, :total)";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':commande_id'   => $data['commande_id'],
            ':produit_id'    => $data['produit_id'],
            ':quantite'      => $data['quantite'],
            ':prix_unitaire' => $data['prix_unitaire'],
            ':total'         => $data['total_ligne'] ?? ($data['quantite'] * $data['prix_unitaire'])
        ]);
    }

    public function update(int $id, array $data): bool
    {
        $fields = [];
        $params = [':id' => $id];
        foreach ($data as $key => $value) {
            $fields[] = "$key = :$key";
            $params[":$key"] = $value;
        }
        $sql = "UPDATE commandes SET " . implode(', ', $fields) . " WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }

    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare("DELETE FROM commandes WHERE id = :id");
        return $stmt->execute([':id' => $id]);
    }

    public function getHistorique(int $id): array
    {
        // Assuming a commande_history table exists, or returning empty if not.
        try {
            $stmt = $this->db->prepare("SELECT * FROM commande_historique WHERE commande_id = :id ORDER BY created_at DESC");
            $stmt->execute([':id' => $id]);
            return $stmt->fetchAll();
        } catch (\Exception $e) {
            return [];
        }
    }

    public function addHistorique(int $commandeId, int $userId, string $action, string $statut): bool
    {
        try {
            $sql = "INSERT INTO commande_historique (commande_id, user_id, action, statut, created_at) VALUES (:cid, :uid, :act, :stat, NOW())";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([':cid' => $commandeId, ':uid' => $userId, ':act' => $action, ':stat' => $statut]);
        } catch (\Exception $e) {
            return false;
        }
    }

    public function getStatsParMois(int $mois, int $annee): array
    {
        $sql = "SELECT DATE(date_commande) as date, SUM(total_ttc) as total, COUNT(*) as nb 
                FROM commandes 
                WHERE MONTH(date_commande) = :m AND YEAR(date_commande) = :y 
                GROUP BY DATE(date_commande)";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':m' => $mois, ':y' => $annee]);
        return $stmt->fetchAll();
    }

    public function countByClient(int $clientId): int
    {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM commandes WHERE user_id = :id");
        $stmt->execute([':id' => $clientId]);
        return (int)$stmt->fetchColumn();
    }

    public function getTotalClientMontant(int $clientId): float
    {
        $stmt = $this->db->prepare("SELECT SUM(total_ttc) FROM commandes WHERE user_id = :id AND status != 'annulée'");
        $stmt->execute([':id' => $clientId]);
        return (float)$stmt->fetchColumn();
    }

    public function getLastOrderDate(int $clientId): ?string
    {
        $stmt = $this->db->prepare("SELECT MAX(date_commande) FROM commandes WHERE user_id = :id");
        $stmt->execute([':id' => $clientId]);
        return $stmt->fetchColumn() ?: null;
    }

    public function getAverageCommande(): float
    {
        return (float)$this->db->query("SELECT AVG(total_ttc) FROM commandes WHERE status != 'annulée'")->fetchColumn();
    }

    public function getTotalMontantJour(): float
    {
        return (float)$this->db->query("SELECT SUM(total_ttc) FROM commandes WHERE DATE(date_commande) = CURRENT_DATE AND status != 'annulée'")->fetchColumn();
    }

    public function getByClientOrderByRecent(int $userId, string $filter = 'all'): array { return $this->getAll($filter, 0, 100, '', '', ''); }
    public function getByClientOrderByOldest(int $userId, string $filter = 'all'): array { return []; }
    public function getByClientOrderByMontant(int $userId, string $filter = 'all', string $dir = 'DESC'): array { return []; }

    public function generateNumero(): string
    {
        return 'CMD-' . date('Ymd') . '-' . str_pad((string)mt_rand(1000, 9999), 4, '0', STR_PAD_LEFT);
    }

    public function getByUserId(int $userId): array
    {
        $stmt = $this->db->prepare("SELECT * FROM commandes WHERE user_id = :id ORDER BY date_commande DESC");
        $stmt->execute([':id' => $userId]);
        return $stmt->fetchAll();
    }
}
