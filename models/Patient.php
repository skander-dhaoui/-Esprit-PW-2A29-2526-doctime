<?php
declare(strict_types=1);

final class Patient
{
    private int     $id;
    private int     $userId;
    private ?string $groupeSanguin;
    private ?string $allergie;
    private ?string $antecedents;
    private ?PDO    $db = null;

    public function __construct(array $data = [])
    {
        $this->id            = (int)    ($data['id']             ?? 0);
        $this->userId        = (int)    ($data['user_id']        ?? 0);
        $this->groupeSanguin =          ($data['groupe_sanguin'] ?? null);
        $this->allergie      =          ($data['allergie']       ?? null);
        $this->antecedents   =          ($data['antecedents']    ?? null);
    }

    public function __destruct() {}

    // ── Getters ──────────────────────────────────────────────────
    public function getId(): int               { return $this->id; }
    public function getUserId(): int           { return $this->userId; }
    public function getGroupeSanguin(): ?string { return $this->groupeSanguin; }
    public function getAllergie(): ?string      { return $this->allergie; }
    public function getAntecedents(): ?string  { return $this->antecedents; }

    // ── Setters ──────────────────────────────────────────────────
    public function setId(int $v): void               { $this->id            = $v; }
    public function setUserId(int $v): void           { $this->userId        = $v; }
    public function setGroupeSanguin(?string $v): void { $this->groupeSanguin = $v; }
    public function setAllergie(?string $v): void     { $this->allergie      = $v; }
    public function setAntecedents(?string $v): void  { $this->antecedents   = $v; }

    // ── Database Methods ─────────────────────────────────────────

    /**
     * Get statistics for a patient
     */
    public function getStats(int $userId): array {
        try {
            if (!$this->db) {
                $this->db = Database::getInstance()->getConnection();
            }
            $total = $this->db->prepare(
                "SELECT COUNT(*) FROM rendez_vous WHERE patient_id = :uid"
            );
            $total->execute([':uid' => $userId]);

            $upcoming = $this->db->prepare(
                "SELECT COUNT(*) FROM rendez_vous
                 WHERE patient_id = :uid
                   AND statut IN ('en_attente','confirmé')"
            );
            $upcoming->execute([':uid' => $userId]);

            $claims = $this->db->prepare(
                "SELECT COUNT(*) FROM reclamations WHERE patient_id = :uid"
            );
            $claims->execute([':uid' => $userId]);

            return [
                'rdv_total'    => (int) $total->fetchColumn(),
                'rdv_a_venir'  => (int) $upcoming->fetchColumn(),
                'reclamations' => (int) $claims->fetchColumn(),
            ];
        } catch (Exception $e) {
            error_log("Error in getStats: " . $e->getMessage());
            return ['rdv_total' => 0, 'rdv_a_venir' => 0, 'reclamations' => 0];
        }
    }
}