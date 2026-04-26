<?php
declare(strict_types=1);

namespace App\Models;

final class FaceRecognition
{
    private int $userId;
    private ?string $facePhoto;
    private ?string $faceEncoding;
    private ?string $faceDescriptor;

    public function __construct(array $data = [])
    {
        $this->userId = (int) ($data['user_id'] ?? 0);
        $this->facePhoto = $data['face_photo'] ?? null;
        $this->faceEncoding = $data['face_encoding'] ?? ($data['face_descriptors'] ?? null);
        $this->faceDescriptor = $data['face_descriptor'] ?? ($data['face_descriptors'] ?? null);
    }

    public function __destruct()
    {
        // Nettoyage des ressources si nécessaire
    }

    public function getUserId(): int
    {
        return $this->userId;
    }

    public function getFacePhoto(): ?string
    {
        return $this->facePhoto;
    }

    public function getFaceEncoding(): ?string
    {
        return $this->faceEncoding;
    }

    public function getFaceDescriptor(): ?string
    {
        return $this->faceDescriptor;
    }

    public function setUserId(int $userId): void
    {
        $this->userId = $userId;
    }

    public function setFacePhoto(?string $facePhoto): void
    {
        $this->facePhoto = $facePhoto;
    }

    public function setFaceEncoding(?string $faceEncoding): void
    {
        $this->faceEncoding = $faceEncoding;
    }

    public function setFaceDescriptor(?string $faceDescriptor): void
    {
        $this->faceDescriptor = $faceDescriptor;
    }
}
