<?php
declare(strict_types=1);

final class Disponibilite
{
    private int     $id;
    private int     $medecinId;
    private string  $jourSemaine;
    private string  $heureDebut;
    private string  $heureFin;
    private ?string $pauseDebut;
    private ?string $pauseFin;
    private int     $actif;

    public function __construct(array $data = [])
    {
        $this->id          = (int)    ($data['id']           ?? 0);
        $this->medecinId   = (int)    ($data['medecin_id']   ?? $data['user_id'] ?? 0);
        $this->jourSemaine = (string) ($data['jour_semaine'] ?? '');
        $this->heureDebut  = (string) ($data['heure_debut']  ?? '');
        $this->heureFin    = (string) ($data['heure_fin']    ?? '');
        $this->pauseDebut  =          ($data['pause_debut']  ?? null);
        $this->pauseFin    =          ($data['pause_fin']    ?? null);
        $this->actif       = (int)    ($data['actif']        ?? 1);
    }

    public function __destruct() {}

    // ── Getters ──────────────────────────────────────────────────
    public function getId(): int            { return $this->id; }
    public function getMedecinId(): int     { return $this->medecinId; }
    public function getJourSemaine(): string { return $this->jourSemaine; }
    public function getHeureDebut(): string  { return $this->heureDebut; }
    public function getHeureFin(): string    { return $this->heureFin; }
    public function getPauseDebut(): ?string { return $this->pauseDebut; }
    public function getPauseFin(): ?string   { return $this->pauseFin; }
    public function getActif(): int          { return $this->actif; }

    // ── Setters ──────────────────────────────────────────────────
    public function setId(int $v): void             { $this->id          = $v; }
    public function setMedecinId(int $v): void      { $this->medecinId   = $v; }
    public function setJourSemaine(string $v): void  { $this->jourSemaine = $v; }
    public function setHeureDebut(string $v): void   { $this->heureDebut  = $v; }
    public function setHeureFin(string $v): void     { $this->heureFin    = $v; }
    public function setPauseDebut(?string $v): void  { $this->pauseDebut  = $v; }
    public function setPauseFin(?string $v): void    { $this->pauseFin    = $v; }
    public function setActif(int $v): void           { $this->actif       = $v; }
}