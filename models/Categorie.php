<?php
declare(strict_types=1);

final class Categorie
{
    private int     $id;
    private string  $nom;
    private ?string $slug;
    private ?string $description;
    private ?string $image;
    private ?int    $parentId;

    public function __construct(array $data = [])
    {
        $this->id          = (int)    ($data['id']          ?? 0);
        $this->nom         = (string) ($data['nom']         ?? '');
        $this->slug        =          ($data['slug']        ?? null);
        $this->description =          ($data['description'] ?? null);
        $this->image       =          ($data['image']       ?? null);
        $this->parentId    =          ($data['parent_id'] !== null ? (int)$data['parent_id'] : null);
    }

    public function __destruct() {}

    // ── Getters ──────────────────────────────────────────────────
    public function getId(): int              { return $this->id; }
    public function getNom(): string          { return $this->nom; }
    public function getSlug(): ?string        { return $this->slug; }
    public function getDescription(): ?string { return $this->description; }
    public function getImage(): ?string       { return $this->image; }
    public function getParentId(): ?int       { return $this->parentId; }

    // ── Setters ──────────────────────────────────────────────────
    public function setId(int $v): void            { $this->id          = $v; }
    public function setNom(string $v): void         { $this->nom         = $v; }
    public function setSlug(?string $v): void       { $this->slug        = $v; }
    public function setDescription(?string $v): void { $this->description = $v; }
    public function setImage(?string $v): void      { $this->image       = $v; }
    public function setParentId(?int $v): void      { $this->parentId    = $v; }
}