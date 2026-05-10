<?php
declare(strict_types=1);

namespace App\Models;

final class Disponibilite
{
    private int $id;
    private int $medecinId;
    private string $jourSemaine;
    private string $heureDebut;
    private string $heureFin;
    private ?string $pauseDebut;
    private ?string $pauseFin;
    private int $actif;

    public function __construct(array $data = [])
    {
        $this->id = (int) ($data['id'] ?? 0);
        $this->medecinId = (int) ($data['medecin_id'] ?? $data['user_id'] ?? 0);
        $this->jourSemaine = (string) ($data['jour_semaine'] ?? '');
        $this->heureDebut = (string) ($data['heure_debut'] ?? '');
        $this->heureFin = (string) ($data['heure_fin'] ?? '');
        $this->pauseDebut = $data['pause_debut'] ?? null;
        $this->pauseFin = $data['pause_fin'] ?? null;
        $this->actif = (int) ($data['actif'] ?? 1);
    }

    public function __destruct()
    {
        // Nettoyage des ressources si nécessaire
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getMedecinId(): int
    {
        return $this->medecinId;
    }

    public function getJourSemaine(): string
    {
        return $this->jourSemaine;
    }

    public function getHeureDebut(): string
    {
        return $this->heureDebut;
    }

    public function getHeureFin(): string
    {
        return $this->heureFin;
    }

    public function getPauseDebut(): ?string
    {
        return $this->pauseDebut;
    }

    public function getPauseFin(): ?string
    {
        return $this->pauseFin;
    }

    public function getActif(): int
    {
        return $this->actif;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function setMedecinId(int $medecinId): void
    {
        $this->medecinId = $medecinId;
    }

    public function setJourSemaine(string $jourSemaine): void
    {
        $this->jourSemaine = $jourSemaine;
    }

    public function setHeureDebut(string $heureDebut): void
    {
        $this->heureDebut = $heureDebut;
    }

    public function setHeureFin(string $heureFin): void
    {
        $this->heureFin = $heureFin;
    }

    public function setPauseDebut(?string $pauseDebut): void
    {
        $this->pauseDebut = $pauseDebut;
    }

    public function setPauseFin(?string $pauseFin): void
    {
        $this->pauseFin = $pauseFin;
    }

    public function setActif(int $actif): void
    {
        $this->actif = $actif;
    }
}