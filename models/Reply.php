<?php
declare(strict_types=1);

namespace App\Models;

final class Reply
{
    private int $id;
    private int $articleId;
    private ?int $userId;
    private string $typeReply;
    private ?string $contenuText;
    private ?string $emoji;
    private ?string $photo;
    private ?string $auteur;
    private string $dateReply;

    public function __construct(array $data = [])
    {
        $this->id = (int) ($data['id_reply'] ?? $data['id'] ?? 0);
        $this->articleId = (int) ($data['id_article'] ?? $data['article_id'] ?? 0);
        $this->userId = (($data['user_id'] ?? null) !== null ? (int) $data['user_id'] : null);
        $this->typeReply = (string) ($data['type_reply'] ?? 'text');
        $this->contenuText = $data['contenu_text'] ?? null;
        $this->emoji = $data['emoji'] ?? null;
        $this->photo = $data['photo'] ?? null;
        $this->auteur = $data['auteur'] ?? null;
        $this->dateReply = (string) ($data['date_reply'] ?? '');
    }

    public function __destruct()
    {
        // Nettoyage des ressources si nécessaire
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getArticleId(): int
    {
        return $this->articleId;
    }

    public function getUserId(): ?int
    {
        return $this->userId;
    }

    public function getTypeReply(): string
    {
        return $this->typeReply;
    }

    public function getContenuText(): ?string
    {
        return $this->contenuText;
    }

    public function getEmoji(): ?string
    {
        return $this->emoji;
    }

    public function getPhoto(): ?string
    {
        return $this->photo;
    }

    public function getAuteur(): ?string
    {
        return $this->auteur;
    }

    public function getDateReply(): string
    {
        return $this->dateReply;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function setArticleId(int $articleId): void
    {
        $this->articleId = $articleId;
    }

    public function setUserId(?int $userId): void
    {
        $this->userId = $userId;
    }

    public function setTypeReply(string $typeReply): void
    {
        $this->typeReply = $typeReply;
    }

    public function setContenuText(?string $contenuText): void
    {
        $this->contenuText = $contenuText;
    }

    public function setEmoji(?string $emoji): void
    {
        $this->emoji = $emoji;
    }

    public function setPhoto(?string $photo): void
    {
        $this->photo = $photo;
    }

    public function setAuteur(?string $auteur): void
    {
        $this->auteur = $auteur;
    }

    public function setDateReply(string $dateReply): void
    {
        $this->dateReply = $dateReply;
    }
}