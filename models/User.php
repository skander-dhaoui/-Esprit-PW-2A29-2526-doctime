<?php
declare(strict_types=1);

class User
{
    private int     $id;
    private string  $nom;
    private string  $prenom;
    private string  $email;
    private string  $telephone;
    private string  $password;
    private string  $role;
    private string  $statut;
    private ?string $adresse;
    private ?string $dateNaissance;
    private ?string $avatar;
    private ?string $facePhoto;
    private ?string $faceEncoding;
    private ?string $faceDescriptor;
    private string  $createdAt;
    private string  $derniereConnexion;

    public function __construct(array $data = [])
    {
        $this->id                = (int)    ($data['id']                 ?? 0);
        $this->nom               = (string) ($data['nom']                ?? '');
        $this->prenom            = (string) ($data['prenom']             ?? '');
        $this->email             = (string) ($data['email']              ?? '');
        $this->telephone         = (string) ($data['telephone']          ?? '');
        $this->password          = (string) ($data['password']           ?? '');
        $this->role              = (string) ($data['role']               ?? 'patient');
        $this->statut            = (string) ($data['statut']             ?? 'actif');
        $this->adresse           =          ($data['adresse']            ?? null);
        $this->dateNaissance     =          ($data['date_naissance']     ?? null);
        $this->avatar            =          ($data['avatar']             ?? null);
        $this->facePhoto         =          ($data['face_photo']         ?? null);
        $this->faceEncoding      =          ($data['face_encoding']      ?? null);
        $this->faceDescriptor    =          ($data['face_descriptor']    ?? null);
        $this->createdAt         = (string) ($data['created_at']         ?? '');
        $this->derniereConnexion = (string) ($data['derniere_connexion'] ?? '');
    }

    public function __destruct() {}

    public function getId(): int                   { return $this->id; }
    public function getNom(): string               { return $this->nom; }
    public function getPrenom(): string            { return $this->prenom; }
    public function getEmail(): string             { return $this->email; }
    public function getTelephone(): string         { return $this->telephone; }
    public function getPassword(): string          { return $this->password; }
    public function getRole(): string              { return $this->role; }
    public function getStatut(): string            { return $this->statut; }
    public function getAdresse(): ?string          { return $this->adresse; }
    public function getDateNaissance(): ?string    { return $this->dateNaissance; }
    public function getAvatar(): ?string           { return $this->avatar; }
    public function getFacePhoto(): ?string        { return $this->facePhoto; }
    public function getFaceEncoding(): ?string     { return $this->faceEncoding; }
    public function getFaceDescriptor(): ?string   { return $this->faceDescriptor; }
    public function getCreatedAt(): string         { return $this->createdAt; }
    public function getDerniereConnexion(): string { return $this->derniereConnexion; }
    public function getNomComplet(): string        { return trim($this->prenom . ' ' . $this->nom); }

    public function setId(int $v): void                  { $this->id                = $v; }
    public function setNom(string $v): void              { $this->nom               = $v; }
    public function setPrenom(string $v): void           { $this->prenom            = $v; }
    public function setEmail(string $v): void            { $this->email             = $v; }
    public function setTelephone(string $v): void        { $this->telephone         = $v; }
    public function setPassword(string $v): void         { $this->password          = $v; }
    public function setRole(string $v): void             { $this->role              = $v; }
    public function setStatut(string $v): void           { $this->statut            = $v; }
    public function setAdresse(?string $v): void         { $this->adresse           = $v; }
    public function setDateNaissance(?string $v): void   { $this->dateNaissance     = $v; }
    public function setAvatar(?string $v): void          { $this->avatar            = $v; }
    public function setFacePhoto(?string $v): void       { $this->facePhoto         = $v; }
    public function setFaceEncoding(?string $v): void    { $this->faceEncoding      = $v; }
    public function setFaceDescriptor(?string $v): void  { $this->faceDescriptor    = $v; }
    public function setCreatedAt(string $v): void        { $this->createdAt         = $v; }
    public function setDerniereConnexion(string $v): void { $this->derniereConnexion = $v; }

    // ─── Requêtes DB ───────────────────────────────────────────────────────

    /**
     * Compte le nombre total d'utilisateurs
     * @return int
     */
    public static function count(): int
    {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->query("SELECT COUNT(*) as total FROM users");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int) ($result['total'] ?? 0);
    }

    /**
     * Compte les utilisateurs par rôle
     * @param string $role
     * @return int
     */
    public static function countByRole(string $role): int
    {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("SELECT COUNT(*) as total FROM users WHERE role = :role");
        $stmt->execute([':role' => $role]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int) ($result['total'] ?? 0);
    }

    /**
     * Compte les utilisateurs par statut
     * @param string $statut
     * @return int
     */
    public static function countByStatut(string $statut): int
    {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("SELECT COUNT(*) as total FROM users WHERE statut = :statut");
        $stmt->execute([':statut' => $statut]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int) ($result['total'] ?? 0);
    }

    public function findByEmail(string $email): ?array
    {
        $db   = Database::getInstance()->getConnection();
        $stmt = $db->prepare("SELECT * FROM users WHERE email = :email LIMIT 1");
        $stmt->execute([':email' => $email]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function findById(int $id): ?array
    {
        $db   = Database::getInstance()->getConnection();
        $stmt = $db->prepare("SELECT * FROM users WHERE id = :id LIMIT 1");
        $stmt->execute([':id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    /**
     * Récupère tous les utilisateurs
     * @param int $limit
     * @param int $offset
     * @return array
     */
    public static function findAll(int $limit = 100, int $offset = 0): array
    {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("SELECT * FROM users LIMIT :limit OFFSET :offset");
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getAll(int $offset = 0, int $limit = 100): array
    {
        return self::findAll($limit, $offset);
    }

    public function delete(int $id): bool
    {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("DELETE FROM users WHERE id = :id");
        return $stmt->execute([':id' => $id]);
    }

    public function update(int $id, array $data): bool
    {
        if ($id <= 0 || empty($data)) {
            return false;
        }

        $allowed = [
            'nom',
            'prenom',
            'email',
            'telephone',
            'password',
            'role',
            'statut',
            'adresse',
            'date_naissance',
            'avatar',
            'face_photo',
            'face_encoding',
            'face_descriptor',
            'derniere_connexion',
        ];

        $fields = [];
        $params = [':id' => $id];

        foreach ($data as $key => $value) {
            if (!in_array($key, $allowed, true)) {
                continue;
            }
            $fields[] = "$key = :$key";
            $params[":$key"] = $value;
        }

        if (empty($fields)) {
            return false;
        }

        $db = Database::getInstance()->getConnection();
        $sql = "UPDATE users SET " . implode(', ', $fields) . " WHERE id = :id";
        $stmt = $db->prepare($sql);
        return $stmt->execute($params);
    }

    public function getExtras(int $userId, string $role): array
    {
        $db = Database::getInstance()->getConnection();

        if ($role === 'medecin') {
            $stmt = $db->prepare(
                "SELECT specialite, numero_ordre, annee_experience, consultation_prix,
                        cabinet_adresse, description, statut_validation
                 FROM medecins
                 WHERE user_id = :user_id
                 LIMIT 1"
            );
            $stmt->execute([':user_id' => $userId]);
            return $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
        }

        if ($role === 'patient') {
            $stmt = $db->prepare("SELECT * FROM patients WHERE user_id = :user_id LIMIT 1");
            $stmt->execute([':user_id' => $userId]);
            return $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
        }

        return [];
    }

    public function toArray(): array
    {
        return [
            'id'                 => $this->id,
            'nom'                => $this->nom,
            'prenom'             => $this->prenom,
            'email'              => $this->email,
            'telephone'          => $this->telephone,
            'role'               => $this->role,
            'statut'             => $this->statut,
            'adresse'            => $this->adresse,
            'date_naissance'     => $this->dateNaissance,
            'avatar'             => $this->avatar,
            'face_photo'         => $this->facePhoto,
            'created_at'         => $this->createdAt,
            'derniere_connexion'  => $this->derniereConnexion,
        ];
    }

// Dans User.php, ajoutez cette méthode après les autres méthodes :

/**
 * Récupère les derniers utilisateurs inscrits
 * @param int $limit Nombre d'utilisateurs à récupérer
 * @return array
 */
public function getRecent(int $limit = 5): array
{
    $db = Database::getInstance()->getConnection();
    $stmt = $db->prepare("
        SELECT id, nom, prenom, email, role, statut, created_at 
        FROM users 
        ORDER BY created_at DESC 
        LIMIT :limit
    ");
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}    }
