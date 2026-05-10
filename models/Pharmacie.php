<?php
require_once __DIR__ . '/../config/database.php';

class Pharmacie {
    private PDO $pdo;

    public function __construct() {
        $this->pdo = Database::getInstance()->getConnection();
    }

    /**
     * Récupère toutes les pharmacies
     */
    public function findAll(array $filters = []): array {
        $query = "SELECT * FROM pharmacies WHERE 1=1";
        $params = [];

        if (!empty($filters['ville'])) {
            $query .= " AND ville = :ville";
            $params[':ville'] = $filters['ville'];
        }

        if (!empty($filters['statut'])) {
            $query .= " AND statut = :statut";
            $params[':statut'] = $filters['statut'];
        }

        if (!empty($filters['search'])) {
            $query .= " AND (nom LIKE :search OR adresse LIKE :search OR ville LIKE :search)";
            $params[':search'] = '%' . $filters['search'] . '%';
        }

        $query .= " ORDER BY nom ASC";

        $stmt = $this->pdo->prepare($query);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Récupère une pharmacie par ID
     */
    public function findById(int $id): array|false {
        $stmt = $this->pdo->prepare("SELECT * FROM pharmacies WHERE id = :id");
        $stmt->execute([':id' => $id]);
        return $stmt->fetch();
    }

    /**
     * Récupère une pharmacie par slug
     */
    public function findBySlug(string $slug): array|false {
        $stmt = $this->pdo->prepare("SELECT * FROM pharmacies WHERE slug = :slug");
        $stmt->execute([':slug' => $slug]);
        return $stmt->fetch();
    }

    /**
     * Crée une nouvelle pharmacie
     */
    public function create(array $data): int|false {
        $stmt = $this->pdo->prepare("
            INSERT INTO pharmacies 
            (nom, slug, description, adresse, ville, code_postal, telephone, email, 
             site_web, responsable_id, horaires_ouverture, gerant_nom, gerant_prenom, 
             gerant_telephone, latitude, longitude, image, statut)
            VALUES 
            (:nom, :slug, :description, :adresse, :ville, :code_postal, :telephone, :email,
             :site_web, :responsable_id, :horaires_ouverture, :gerant_nom, :gerant_prenom,
             :gerant_telephone, :latitude, :longitude, :image, :statut)
        ");

        $result = $stmt->execute([
            ':nom'                => trim($data['nom'] ?? ''),
            ':slug'               => $data['slug'] ?? $this->generateSlug($data['nom'] ?? ''),
            ':description'        => trim($data['description'] ?? ''),
            ':adresse'            => trim($data['adresse'] ?? ''),
            ':ville'              => trim($data['ville'] ?? ''),
            ':code_postal'        => trim($data['code_postal'] ?? ''),
            ':telephone'          => trim($data['telephone'] ?? ''),
            ':email'              => trim($data['email'] ?? ''),
            ':site_web'           => trim($data['site_web'] ?? ''),
            ':responsable_id'     => !empty($data['responsable_id']) ? (int)$data['responsable_id'] : null,
            ':horaires_ouverture' => $data['horaires_ouverture'] ?? null,
            ':gerant_nom'         => trim($data['gerant_nom'] ?? ''),
            ':gerant_prenom'      => trim($data['gerant_prenom'] ?? ''),
            ':gerant_telephone'   => trim($data['gerant_telephone'] ?? ''),
            ':latitude'           => !empty($data['latitude']) ? (float)$data['latitude'] : null,
            ':longitude'          => !empty($data['longitude']) ? (float)$data['longitude'] : null,
            ':image'              => $data['image'] ?? null,
            ':statut'             => $data['statut'] ?? 'actif'
        ]);

        return $result ? (int)$this->pdo->lastInsertId() : false;
    }

    /**
     * Mise à jour d'une pharmacie
     */
    public function update(int $id, array $data): bool {
        $allowedFields = [
            'nom', 'slug', 'description', 'adresse', 'ville', 'code_postal',
            'telephone', 'email', 'site_web', 'responsable_id', 'horaires_ouverture',
            'gerant_nom', 'gerant_prenom', 'gerant_telephone', 'latitude', 'longitude',
            'image', 'statut'
        ];

        $updates = [];
        $params = [':id' => $id];

        foreach ($allowedFields as $field) {
            if (array_key_exists($field, $data)) {
                $updates[] = "$field = :$field";
                if ($field === 'responsable_id') {
                    $params[":$field"] = !empty($data[$field]) ? (int)$data[$field] : null;
                } elseif (in_array($field, ['latitude', 'longitude'])) {
                    $params[":$field"] = !empty($data[$field]) ? (float)$data[$field] : null;
                } else {
                    $params[":$field"] = trim($data[$field] ?? '');
                }
            }
        }

        if (empty($updates)) {
            return false;
        }

        $query = "UPDATE pharmacies SET " . implode(', ', $updates) . ", updated_at = NOW() WHERE id = :id";
        $stmt = $this->pdo->prepare($query);
        return $stmt->execute($params);
    }

    /**
     * Supprime une pharmacie
     */
    public function delete(int $id): bool {
        $stmt = $this->pdo->prepare("DELETE FROM pharmacies WHERE id = :id");
        return $stmt->execute([':id' => $id]);
    }

    /**
     * Récupère les événements d'une pharmacie
     */
    public function getEvenements(int $pharmacieId): array {
        $stmt = $this->pdo->prepare("
            SELECT e.* FROM events e 
            WHERE e.pharmacie_id = :pharmacie_id
            ORDER BY e.date_debut DESC
        ");
        $stmt->execute([':pharmacie_id' => $pharmacieId]);
        return $stmt->fetchAll();
    }

    /**
     * Récupère les utilisateurs associés à une pharmacie
     */
    public function getUtilisateurs(int $pharmacieId): array {
        $stmt = $this->pdo->prepare("
            SELECT u.*, up.role, up.date_embauche, up.statut
            FROM utilisateur_pharmacie up
            JOIN users u ON up.user_id = u.id
            WHERE up.pharmacie_id = :pharmacie_id
            ORDER BY u.nom, u.prenom
        ");
        $stmt->execute([':pharmacie_id' => $pharmacieId]);
        return $stmt->fetchAll();
    }

    /**
     * Ajoute un utilisateur à une pharmacie
     */
    public function addUtilisateur(int $pharmacieId, int $userId, string $role = 'employe'): bool {
        $stmt = $this->pdo->prepare("
            INSERT INTO utilisateur_pharmacie (user_id, pharmacie_id, role, date_embauche)
            VALUES (:user_id, :pharmacie_id, :role, CURDATE())
        ");
        return $stmt->execute([
            ':user_id'     => $userId,
            ':pharmacie_id' => $pharmacieId,
            ':role'        => $role
        ]);
    }

    /**
     * Retire un utilisateur d'une pharmacie
     */
    public function removeUtilisateur(int $pharmacieId, int $userId): bool {
        $stmt = $this->pdo->prepare("
            DELETE FROM utilisateur_pharmacie 
            WHERE user_id = :user_id AND pharmacie_id = :pharmacie_id
        ");
        return $stmt->execute([
            ':user_id'     => $userId,
            ':pharmacie_id' => $pharmacieId
        ]);
    }

    /**
     * Récupère les produits d'une pharmacie
     */
    public function getProduits(int $pharmacieId): array {
        // Récupère tous les produits liés à cette pharmacie via les commandes
        $stmt = $this->pdo->prepare("
            SELECT DISTINCT p.* FROM produits p
            WHERE p.categorie_id IN (
                SELECT DISTINCT categorie_id FROM produits
            )
            ORDER BY p.nom ASC
            LIMIT 50
        ");
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Génère un slug à partir d'un texte
     */
    private function generateSlug(string $text): string {
        $text = strtolower(trim($text));
        $text = preg_replace('/[^a-z0-9]+/', '-', $text);
        return trim($text, '-');
    }

    /**
     * Vérifie si un slug existe
     */
    public function slugExists(string $slug, ?int $excludeId = null): bool {
        $query = "SELECT COUNT(*) FROM pharmacies WHERE slug = :slug";
        $params = [':slug' => $slug];

        if ($excludeId !== null) {
            $query .= " AND id != :id";
            $params[':id'] = $excludeId;
        }

        $stmt = $this->pdo->prepare($query);
        $stmt->execute($params);
        return (int)$stmt->fetchColumn() > 0;
    }

    /**
     * Vérifie si une pharmacie existe par email
     */
    public function emailExists(string $email, ?int $excludeId = null): bool {
        $query = "SELECT COUNT(*) FROM pharmacies WHERE email = :email";
        $params = [':email' => $email];

        if ($excludeId !== null) {
            $query .= " AND id != :id";
            $params[':id'] = $excludeId;
        }

        $stmt = $this->pdo->prepare($query);
        $stmt->execute($params);
        return (int)$stmt->fetchColumn() > 0;
    }

    /**
     * Obtient les stats d'une pharmacie
     */
    public function getStats(int $pharmacieId): array {
        $stats = [
            'total_events' => 0,
            'total_utilisateurs' => 0,
            'total_produits' => 0,
        ];

        // Nombre d'événements
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM events WHERE pharmacie_id = :id");
        $stmt->execute([':id' => $pharmacieId]);
        $stats['total_events'] = (int)$stmt->fetchColumn();

        // Nombre d'utilisateurs
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM utilisateur_pharmacie WHERE pharmacie_id = :id");
        $stmt->execute([':id' => $pharmacieId]);
        $stats['total_utilisateurs'] = (int)$stmt->fetchColumn();

        // Nombre de produits
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM produits");
        $stmt->execute();
        $stats['total_produits'] = (int)$stmt->fetchColumn();

        return $stats;
    }
}
?>
