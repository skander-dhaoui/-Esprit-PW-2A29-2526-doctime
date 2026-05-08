<?php
declare(strict_types=1);

namespace App\Models;

final class Medecin
{
    private int $id;
    private int $userId;
    private string $specialite;
    private ?string $numeroOrdre;
    private ?int $anneeExperience;
    private ?float $consultationPrix;
    private ?string $cabinetAdresse;
    private ?string $description;
    private string $statutValidation;
    private ?string $commentaireValidation;

    public function __construct(array $data = [])
    {
        $this->id = (int) ($data['id'] ?? 0);
        $this->userId = (int) ($data['user_id'] ?? 0);
        $this->specialite = (string) ($data['specialite'] ?? 'Généraliste');
        $this->numeroOrdre = $data['numero_ordre'] ?? null;
        $this->anneeExperience = isset($data['annee_experience']) && $data['annee_experience'] !== null ? (int)$data['annee_experience'] : null;
        $this->consultationPrix = isset($data['consultation_prix']) && $data['consultation_prix'] !== null ? (float)$data['consultation_prix'] : null;
        $this->cabinetAdresse = $data['cabinet_adresse'] ?? null;
        $this->description = $data['description'] ?? null;
        $this->statutValidation = (string) ($data['statut_validation'] ?? 'en_attente');
        $this->commentaireValidation = $data['commentaire_validation'] ?? null;
    }

    public function __destruct()
    {
        // Nettoyage des ressources si nécessaire
    }

    // Getters
    public function getId(): int
    {
        return $this->id;
    }

    public function getUserId(): int
    {
        return $this->userId;
    }

    public function getSpecialite(): string
    {
        return $this->specialite;
    }

    public function getNumeroOrdre(): ?string
    {
        return $this->numeroOrdre;
    }

    public function getAnneeExperience(): ?int
    {
        return $this->anneeExperience;
    }

    public function getConsultationPrix(): ?float
    {
        return $this->consultationPrix;
    }

    public function getCabinetAdresse(): ?string
    {
        return $this->cabinetAdresse;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function getStatutValidation(): string
    {
        return $this->statutValidation;
    }

    public function getCommentaireValidation(): ?string
    {
        return $this->commentaireValidation;
    }

    // Setters
    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function setUserId(int $userId): void
    {
        $this->userId = $userId;
    }

    public function setSpecialite(string $specialite): void
    {
        $this->specialite = $specialite;
    }

    public function setNumeroOrdre(?string $numeroOrdre): void
    {
        $this->numeroOrdre = $numeroOrdre;
    }

    public function setAnneeExperience(?int $anneeExperience): void
    {
        $this->anneeExperience = $anneeExperience;
    }

    public function setConsultationPrix(?float $consultationPrix): void
    {
        $this->consultationPrix = $consultationPrix;
    }

    public function setCabinetAdresse(?string $cabinetAdresse): void
    {
        $this->cabinetAdresse = $cabinetAdresse;
    }

    public function setDescription(?string $description): void
    {
        $this->description = $description;
    }

    public function setStatutValidation(string $statutValidation): void
    {
        $this->statutValidation = $statutValidation;
    }

    public function setCommentaireValidation(?string $commentaireValidation): void
    {
        $this->commentaireValidation = $commentaireValidation;
    }
}
