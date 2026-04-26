<?php

/**
 * Classe Participation — Entité représentant une participation/inscription
 * Utilise des propriétés privées avec getters/setters/constructeur/destructeur
 */
class Participation {
    // Propriétés privées
    private ?int $id;
    private string $nom;
    private string $prenom;
    private string $email;
    private string $telephone;
    private string $profession;
    private int $evenementId;
    private string $statut;
    private string $dateInscription;

    /**
     * Constructeur
     */
    public function __construct(
        ?int $id = null,
        string $nom = '',
        string $prenom = '',
        string $email = '',
        string $telephone = '',
        string $profession = '',
        int $evenementId = 0,
        string $statut = 'en_attente',
        string $dateInscription = ''
    ) {
        $this->id = $id;
        $this->nom = $nom;
        $this->prenom = $prenom;
        $this->email = $email;
        $this->telephone = $telephone;
        $this->profession = $profession;
        $this->evenementId = $evenementId;
        $this->statut = $statut;
        $this->dateInscription = $dateInscription ?: date('Y-m-d H:i:s');
    }

    /**
     * Destructeur
     */
    public function __destruct() {
        // Nettoyage des ressources si nécessaire
    }

    // ═══════════════════════════════════════════════════════════════════
    // GETTERS
    // ═══════════════════════════════════════════════════════════════════

    public function getId(): ?int {
        return $this->id;
    }

    public function getNom(): string {
        return $this->nom;
    }

    public function getPrenom(): string {
        return $this->prenom;
    }

    public function getEmail(): string {
        return $this->email;
    }

    public function getTelephone(): string {
        return $this->telephone;
    }

    public function getProfession(): string {
        return $this->profession;
    }

    public function getEvenementId(): int {
        return $this->evenementId;
    }

    public function getStatut(): string {
        return $this->statut;
    }

    public function getDateInscription(): string {
        return $this->dateInscription;
    }

    /**
     * Retourne le nom complet du participant
     */
    public function getNomComplet(): string {
        return trim($this->prenom . ' ' . $this->nom);
    }

    // ═══════════════════════════════════════════════════════════════════
    // SETTERS
    // ═══════════════════════════════════════════════════════════════════

    public function setId(?int $id): self {
        $this->id = $id;
        return $this;
    }

    public function setNom(string $nom): self {
        $this->nom = trim($nom);
        return $this;
    }

    public function setPrenom(string $prenom): self {
        $this->prenom = trim($prenom);
        return $this;
    }

    public function setEmail(string $email): self {
        $this->email = trim($email);
        return $this;
    }

    public function setTelephone(string $telephone): self {
        $this->telephone = trim($telephone);
        return $this;
    }

    public function setProfession(string $profession): self {
        $this->profession = trim($profession);
        return $this;
    }

    public function setEvenementId(int $evenementId): self {
        $this->evenementId = $evenementId;
        return $this;
    }

    public function setStatut(string $statut): self {
        $statuts = ['en_attente', 'confirme', 'annule'];
        $this->statut = in_array($statut, $statuts) ? $statut : 'en_attente';
        return $this;
    }

    public function setDateInscription(string $dateInscription): self {
        $this->dateInscription = $dateInscription;
        return $this;
    }

    // ═══════════════════════════════════════════════════════════════════
    // MÉTHODES UTILITAIRES
    // ═══════════════════════════════════════════════════════════════════

    /**
     * Convertir l'objet en tableau pour les opérations BD
     */
    public function toArray(): array {
        return [
            'id'               => $this->id,
            'nom'              => $this->nom,
            'prenom'           => $this->prenom,
            'email'            => $this->email,
            'telephone'        => $this->telephone,
            'profession'       => $this->profession,
            'evenement_id'     => $this->evenementId,
            'statut'           => $this->statut,
            'date_inscription' => $this->dateInscription,
        ];
    }

    /**
     * Créer une instance depuis un tableau
     */
    public static function fromArray(array $data): self {
        return new self(
            $data['id'] ?? null,
            $data['nom'] ?? '',
            $data['prenom'] ?? '',
            $data['email'] ?? '',
            $data['telephone'] ?? '',
            $data['profession'] ?? '',
            (int)($data['evenement_id'] ?? 0),
            $data['statut'] ?? 'en_attente',
            $data['date_inscription'] ?? ''
        );
    }

    /**
     * Vérifier si le statut est 'confirme'
     */
    public function isConfirmed(): bool {
        return $this->statut === 'confirme';
    }

    /**
     * Vérifier si le statut est 'annule'
     */
    public function isCancelled(): bool {
        return $this->statut === 'annule';
    }

    /**
     * Vérifier si le statut est 'en_attente'
     */
    public function isPending(): bool {
        return $this->statut === 'en_attente';
    }
}
