<?php

/**
 * Classe Evenement — Entité représentant un événement
 * Utilise des propriétés privées avec getters/setters/constructeur/destructeur
 */
class Evenement {
    // Propriétés privées
    private ?int $id;
    private string $titre;
    private string $description;
    private string $specialite;
    private string $lieu;
    private string $dateDebut;
    private string $dateFin;
    private int $capacite;
    private float $prix;
    private string $statut;
    private string $createdAt;
    private array $sponsors;

    /**
     * Constructeur
     */
    public function __construct(
        ?int $id = null,
        string $titre = '',
        string $description = '',
        string $specialite = '',
        string $lieu = '',
        string $dateDebut = '',
        string $dateFin = '',
        int $capacite = 0,
        float $prix = 0.0,
        string $statut = 'planifie',
        string $createdAt = '',
        array $sponsors = []
    ) {
        $this->id = $id;
        $this->titre = $titre;
        $this->description = $description;
        $this->specialite = $specialite;
        $this->lieu = $lieu;
        $this->dateDebut = $dateDebut;
        $this->dateFin = $dateFin;
        $this->capacite = $capacite;
        $this->prix = $prix;
        $this->statut = $statut;
        $this->createdAt = $createdAt ?: date('Y-m-d H:i:s');
        $this->sponsors = $sponsors;
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

    public function getTitre(): string {
        return $this->titre;
    }

    public function getDescription(): string {
        return $this->description;
    }

    public function getSpecialite(): string {
        return $this->specialite;
    }

    public function getLieu(): string {
        return $this->lieu;
    }

    public function getDateDebut(): string {
        return $this->dateDebut;
    }

    public function getDateFin(): string {
        return $this->dateFin;
    }

    public function getCapacite(): int {
        return $this->capacite;
    }

    public function getPrix(): float {
        return $this->prix;
    }

    public function getStatut(): string {
        return $this->statut;
    }

    public function getCreatedAt(): string {
        return $this->createdAt;
    }

    public function getSponsors(): array {
        return $this->sponsors;
    }

    // ═══════════════════════════════════════════════════════════════════
    // SETTERS
    // ═══════════════════════════════════════════════════════════════════

    public function setId(?int $id): self {
        $this->id = $id;
        return $this;
    }

    public function setTitre(string $titre): self {
        $this->titre = trim($titre);
        return $this;
    }

    public function setDescription(string $description): self {
        $this->description = trim($description);
        return $this;
    }

    public function setSpecialite(string $specialite): self {
        $this->specialite = trim($specialite);
        return $this;
    }

    public function setLieu(string $lieu): self {
        $this->lieu = trim($lieu);
        return $this;
    }

    public function setDateDebut(string $dateDebut): self {
        $this->dateDebut = $dateDebut;
        return $this;
    }

    public function setDateFin(string $dateFin): self {
        $this->dateFin = $dateFin;
        return $this;
    }

    public function setCapacite(int $capacite): self {
        $this->capacite = max(1, $capacite);
        return $this;
    }

    public function setPrix(float $prix): self {
        $this->prix = max(0, $prix);
        return $this;
    }

    public function setStatut(string $statut): self {
        $statuts = ['planifie', 'en_cours', 'termine', 'annule'];
        $this->statut = in_array($statut, $statuts) ? $statut : 'planifie';
        return $this;
    }

    public function setCreatedAt(string $createdAt): self {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function setSponsors(array $sponsors): self {
        $this->sponsors = $sponsors;
        return $this;
    }

    /**
     * Ajoute un sponsor à l'événement
     */
    public function addSponsor(array $sponsor): self {
        $this->sponsors[] = $sponsor;
        return $this;
    }

    /**
     * Convertir l'objet en tableau pour les opérations BD
     */
    public function toArray(): array {
        return [
            'id'           => $this->id,
            'titre'        => $this->titre,
            'description'  => $this->description,
            'specialite'   => $this->specialite,
            'lieu'         => $this->lieu,
            'date_debut'   => $this->dateDebut,
            'date_fin'     => $this->dateFin,
            'capacite'     => $this->capacite,
            'prix'         => $this->prix,
            'statut'       => $this->statut,
            'created_at'   => $this->createdAt,
            'sponsors'     => $this->sponsors,
        ];
    }

    /**
     * Créer une instance depuis un tableau
     */
    public static function fromArray(array $data): self {
        return new self(
            $data['id'] ?? null,
            $data['titre'] ?? '',
            $data['description'] ?? '',
            $data['specialite'] ?? '',
            $data['lieu'] ?? '',
            $data['date_debut'] ?? '',
            $data['date_fin'] ?? '',
            (int)($data['capacite'] ?? 0),
            (float)($data['prix'] ?? 0),
            $data['statut'] ?? 'planifie',
            $data['created_at'] ?? '',
            $data['sponsors'] ?? []
        );
    }
}
