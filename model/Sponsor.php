<?php

/**
 * Classe Sponsor — Entité représentant un sponsor
 * Utilise des propriétés privées avec getters/setters/constructeur/destructeur
 */
class Sponsor {
    // Propriétés privées
    private ?int $id;
    private string $nom;
    private string $email;
    private string $telephone;
    private ?string $siteWeb;
    private string $niveau;
    private float $montant;
    private string $createdAt;

    /**
     * Constructeur
     */
    public function __construct(
        ?int $id = null,
        string $nom = '',
        string $email = '',
        string $telephone = '',
        ?string $siteWeb = null,
        string $niveau = 'bronze',
        float $montant = 0.0,
        string $createdAt = ''
    ) {
        $this->id = $id;
        $this->nom = $nom;
        $this->email = $email;
        $this->telephone = $telephone;
        $this->siteWeb = $siteWeb;
        $this->niveau = $niveau;
        $this->montant = $montant;
        $this->createdAt = $createdAt ?: date('Y-m-d H:i:s');
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

    public function getEmail(): string {
        return $this->email;
    }

    public function getTelephone(): string {
        return $this->telephone;
    }

    public function getSiteWeb(): ?string {
        return $this->siteWeb;
    }

    public function getNiveau(): string {
        return $this->niveau;
    }

    public function getMontant(): float {
        return $this->montant;
    }

    public function getCreatedAt(): string {
        return $this->createdAt;
    }

    /**
     * Retourne le libellé du niveau avec couleur Bootstrap
     */
    public function getNiveauBadge(): string {
        $badges = [
            'bronze'  => 'secondary',
            'argent'  => 'info',
            'or'      => 'warning',
            'platine' => 'danger',
        ];
        return $badges[$this->niveau] ?? 'secondary';
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

    public function setEmail(string $email): self {
        $this->email = trim($email);
        return $this;
    }

    public function setTelephone(string $telephone): self {
        $this->telephone = trim($telephone);
        return $this;
    }

    public function setSiteWeb(?string $siteWeb): self {
        $this->siteWeb = !empty($siteWeb) ? trim($siteWeb) : null;
        return $this;
    }

    public function setNiveau(string $niveau): self {
        $niveaux = ['bronze', 'argent', 'or', 'platine'];
        $this->niveau = in_array($niveau, $niveaux) ? $niveau : 'bronze';
        return $this;
    }

    public function setMontant(float $montant): self {
        $this->montant = max(0, $montant);
        return $this;
    }

    public function setCreatedAt(string $createdAt): self {
        $this->createdAt = $createdAt;
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
            'id'       => $this->id,
            'nom'      => $this->nom,
            'email'    => $this->email,
            'telephone'=> $this->telephone,
            'site_web' => $this->siteWeb,
            'niveau'   => $this->niveau,
            'montant'  => $this->montant,
            'created_at' => $this->createdAt,
        ];
    }

    /**
     * Créer une instance depuis un tableau
     */
    public static function fromArray(array $data): self {
        return new self(
            $data['id'] ?? null,
            $data['nom'] ?? '',
            $data['email'] ?? '',
            $data['telephone'] ?? '',
            $data['site_web'] ?? null,
            $data['niveau'] ?? 'bronze',
            (float)($data['montant'] ?? 0),
            $data['created_at'] ?? ''
        );
    }

    /**
     * Vérifier si c'est un sponsor premium (platine ou or)
     */
    public function isPremium(): bool {
        return in_array($this->niveau, ['or', 'platine']);
    }

    /**
     * Retourne le libellé traduit du niveau
     */
    public function getNiveauLabel(): string {
        $labels = [
            'bronze'  => 'Bronze',
            'argent'  => 'Argent',
            'or'      => 'Or',
            'platine' => 'Platine',
        ];
        return $labels[$this->niveau] ?? $this->niveau;
    }
}
