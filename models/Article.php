<?php
declare(strict_types=1);

class Article
{
    private int     $id;
    private string  $titre;
    private string  $contenu;
    private ?int    $auteurId;
    private ?string $categorie;
    private string  $status;
    private ?string $tags;
    private ?string $image;
    private int     $vues;
    private int     $likes;
    private string  $createdAt;
    private string  $updatedAt;

    public function __construct(array $data = [])
    {
        $this->id        = (int)    ($data['id']         ?? $data['id_article'] ?? 0);
        $this->titre     = (string) ($data['titre']      ?? '');
        $this->contenu   = (string) ($data['contenu']    ?? '');
        $this->auteurId  =          (($data['auteur_id'] ?? null) !== null ? (int)$data['auteur_id'] : null);
        $this->categorie =          ($data['categorie']  ?? null);
        $this->status    = (string) ($data['status']     ?? 'brouillon');
        $this->tags      =          ($data['tags']       ?? null);
        $this->image     =          ($data['image']      ?? null);
        $this->vues      = (int)    ($data['vues']       ?? 0);
        $this->likes     = (int)    ($data['likes']      ?? 0);
        $this->createdAt = (string) ($data['created_at'] ?? '');
        $this->updatedAt = (string) ($data['updated_at'] ?? '');
    }

    public function __destruct() {}

    public function getId(): int            { return $this->id; }
    public function getTitre(): string      { return $this->titre; }
    public function getContenu(): string    { return $this->contenu; }
    public function getAuteurId(): ?int     { return $this->auteurId; }
    public function getCategorie(): ?string { return $this->categorie; }
    public function getStatus(): string     { return $this->status; }
    public function getTags(): ?string      { return $this->tags; }
    public function getImage(): ?string     { return $this->image; }
    public function getVues(): int          { return $this->vues; }
    public function getLikes(): int         { return $this->likes; }
    public function getCreatedAt(): string  { return $this->createdAt; }
    public function getUpdatedAt(): string  { return $this->updatedAt; }

    public function setId(int $v): void            { $this->id        = $v; }
    public function setTitre(string $v): void       { $this->titre     = $v; }
    public function setContenu(string $v): void     { $this->contenu   = $v; }
    public function setAuteurId(?int $v): void      { $this->auteurId  = $v; }
    public function setCategorie(?string $v): void  { $this->categorie = $v; }
    public function setStatus(string $v): void      { $this->status    = $v; }
    public function setTags(?string $v): void       { $this->tags      = $v; }
    public function setImage(?string $v): void      { $this->image     = $v; }
    public function setVues(int $v): void           { $this->vues      = $v; }
    public function setLikes(int $v): void          { $this->likes     = $v; }
    public function setCreatedAt(string $v): void   { $this->createdAt = $v; }
    public function setUpdatedAt(string $v): void   { $this->updatedAt = $v; }
}