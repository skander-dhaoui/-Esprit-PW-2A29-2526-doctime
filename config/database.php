<?php
// Supprimer le deuxième <?php qui était en double

class Database {
    private static ?Database $instance = null;
    private ?PDO $conn = null;
    
    private string $host = "localhost";
    private string $db_name = "doctime_db";
    private string $username = "root";
    private string $password = "";

    /**
     * Constructeur privé - Pattern Singleton
     */
    private function __construct() {
        $this->connect();
    }

    /**
     * Obtenir l'instance unique
     */
    public static function getInstance(): Database {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Établir la connexion
     */
    private function connect(): void {
        try {
            $this->conn = new PDO(
                "mysql:host=" . $this->host . ";dbname=" . $this->db_name . ";charset=utf8mb4",
                $this->username,
                $this->password,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ]
            );
        } catch (PDOException $e) {
            error_log("Erreur de connexion : " . $e->getMessage());
            die("Erreur de connexion à la base de données");
        }
    }

    /**
     * ✅ NOUVEAU : Obtenir la connexion PDO directement
     * Cette méthode est à utiliser dans les modèles
     */
    public function getPDO(): PDO {
        if ($this->conn === null) {
            $this->connect();
        }
        return $this->conn;
    }

    /**
     * Obtenir la connexion PDO (alias de getPDO pour compatibilité)
     */
    public function getConnection(): PDO {
        return $this->getPDO();
    }

    /**
     * Exécuter une requête (INSERT, UPDATE, DELETE)
     */
    public function execute(string $sql, array $params = []): bool {
        try {
            $stmt = $this->conn->prepare($sql);
            return $stmt->execute($params);
        } catch (PDOException $e) {
            error_log('Erreur execute : ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Récupérer les résultats (SELECT)
     */
    public function query(string $sql, array $params = []): array {
        try {
            $stmt = $this->conn->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?? [];
        } catch (PDOException $e) {
            error_log('Erreur query : ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Récupérer une seule ligne
     */
    public function queryOne(string $sql, array $params = []): ?array {
        try {
            $stmt = $this->conn->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
        } catch (PDOException $e) {
            error_log('Erreur queryOne : ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Récupérer une valeur scalaire
     */
    public function queryScalar(string $sql, array $params = []) {
        try {
            $stmt = $this->conn->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchColumn();
        } catch (PDOException $e) {
            error_log('Erreur queryScalar : ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Obtenir le dernier ID inséré
     */
    public function lastInsertId(): int {
        return (int)$this->conn->lastInsertId();
    }

    /**
     * Démarrer une transaction
     */
    public function beginTransaction(): bool {
        try {
            if (!$this->conn->inTransaction()) {
                return $this->conn->beginTransaction();
            }
            return true;
        } catch (PDOException $e) {
            error_log('Erreur beginTransaction : ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Valider une transaction
     */
    public function commit(): bool {
        try {
            if ($this->conn->inTransaction()) {
                return $this->conn->commit();
            }
            return true;
        } catch (PDOException $e) {
            error_log('Erreur commit : ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Annuler une transaction
     */
    public function rollback(): bool {
        try {
            if ($this->conn->inTransaction()) {
                return $this->conn->rollBack();
            }
            return true;
        } catch (PDOException $e) {
            error_log('Erreur rollback : ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Empêcher le clonage
     */
    private function __clone() {}

    /**
     * Empêcher la sérialisation
     */
    public function __serialize(): array {
        throw new Exception('Cannot serialize a singleton');
    }

    /**
     * Empêcher la désérialisation
     */
    public function __unserialize(array $data): void {
        throw new Exception('Cannot unserialize a singleton');
    }
}
?>