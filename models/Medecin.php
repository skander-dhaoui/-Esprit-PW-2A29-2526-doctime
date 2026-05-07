<?php
declare(strict_types=1);

final class Medecin
{
    private int     $id;
    private int     $userId;
    private string  $specialite;
    private ?string $numeroOrdre;
    private ?int    $anneeExperience;
    private ?float  $consultationPrix;
    private ?string $cabinetAdresse;
    private ?string $description;
    private string  $statutValidation;
    private ?string $commentaireValidation;
    private ?PDO    $db = null;

    public function __construct(array $data = [])
    {
        $this->id                   = (int)    ($data['id']                      ?? 0);
        $this->userId               = (int)    ($data['user_id']                 ?? 0);
        $this->specialite           = (string) ($data['specialite']              ?? 'Généraliste');
        $this->numeroOrdre          =          ($data['numero_ordre']            ?? null);
        $this->anneeExperience      = isset($data['annee_experience']) && $data['annee_experience'] !== null ? (int)$data['annee_experience'] : null;
        $this->consultationPrix     = isset($data['consultation_prix']) && $data['consultation_prix'] !== null ? (float)$data['consultation_prix'] : null;
        $this->cabinetAdresse       =          ($data['cabinet_adresse']         ?? null);
        $this->description          =          ($data['description']             ?? null);
        $this->statutValidation     = (string) ($data['statut_validation']       ?? 'en_attente');
        $this->commentaireValidation =         ($data['commentaire_validation']  ?? null);
    }

    public function __destruct() {}

    // ── Getters ──────────────────────────────────────────────────
    public function getId(): int                    { return $this->id; }
    public function getUserId(): int                { return $this->userId; }
    public function getSpecialite(): string         { return $this->specialite; }
    public function getNumeroOrdre(): ?string       { return $this->numeroOrdre; }
    public function getAnneeExperience(): ?int      { return $this->anneeExperience; }
    public function getConsultationPrix(): ?float   { return $this->consultationPrix; }
    public function getCabinetAdresse(): ?string    { return $this->cabinetAdresse; }
    public function getDescription(): ?string       { return $this->description; }
    public function getStatutValidation(): string   { return $this->statutValidation; }
    public function getCommentaireValidation(): ?string { return $this->commentaireValidation; }

    // ── Setters ──────────────────────────────────────────────────
    public function setId(int $v): void                    { $this->id                    = $v; }
    public function setUserId(int $v): void                { $this->userId                = $v; }
    public function setSpecialite(string $v): void          { $this->specialite            = $v; }
    public function setNumeroOrdre(?string $v): void        { $this->numeroOrdre           = $v; }
    public function setAnneeExperience(?int $v): void       { $this->anneeExperience       = $v; }
    public function setConsultationPrix(?float $v): void    { $this->consultationPrix      = $v; }
    public function setCabinetAdresse(?string $v): void     { $this->cabinetAdresse        = $v; }
    public function setDescription(?string $v): void        { $this->description           = $v; }
    public function setStatutValidation(string $v): void    { $this->statutValidation      = $v; }
    public function setCommentaireValidation(?string $v): void { $this->commentaireValidation = $v; }

    // ── Database Methods ─────────────────────────────────────────

    /**
     * Get statistics for a medecin
     */
    public function getStats(int $medecinId): array {
        try {
            if (!$this->db) {
                $this->db = Database::getInstance()->getConnection();
            }
            $total = $this->db->prepare(
                "SELECT COUNT(*) FROM rendez_vous WHERE medecin_id = :mid"
            );
            $total->execute([':mid' => $medecinId]);

            $today = $this->db->prepare(
                "SELECT COUNT(*) FROM rendez_vous
                 WHERE medecin_id = :mid AND DATE(date_rendezvous) = CURDATE()"
            );
            $today->execute([':mid' => $medecinId]);

            $patients = $this->db->prepare(
                "SELECT COUNT(DISTINCT patient_id) FROM rendez_vous WHERE medecin_id = :mid"
            );
            $patients->execute([':mid' => $medecinId]);

            $pending = $this->db->prepare(
                "SELECT COUNT(*) FROM rendez_vous
                 WHERE medecin_id = :mid AND statut = 'en_attente'"
            );
            $pending->execute([':mid' => $medecinId]);

            return [
                'rdv_total'    => (int) $total->fetchColumn(),
                'rdv_today'    => (int) $today->fetchColumn(),
                'patients'     => (int) $patients->fetchColumn(),
                'rdv_pending'  => (int) $pending->fetchColumn(),
            ];
        } catch (Exception $e) {
            error_log("Error in getStats: " . $e->getMessage());
            return ['rdv_total' => 0, 'rdv_today' => 0, 'patients' => 0, 'rdv_pending' => 0];
        }
    }

    public function findByUserId(int $userId): ?array
    {
        if (!$this->db) {
            $this->db = Database::getInstance()->getConnection();
        }

        $stmt = $this->db->prepare("SELECT * FROM medecins WHERE user_id = :user_id LIMIT 1");
        $stmt->execute([':user_id' => $userId]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function update(int $userId, array $data): bool
    {
        if ($userId <= 0) {
            return false;
        }

        if (!$this->db) {
            $this->db = Database::getInstance()->getConnection();
        }

        $allowedMap = [
            'specialite' => 'specialite',
            'numero_ordre' => 'numero_ordre',
            'annee_experience' => 'annee_experience',
            'consultation_prix' => 'consultation_prix',
            'cabinet_adresse' => 'cabinet_adresse',
            'adresse_cabinet' => 'cabinet_adresse',
            'description' => 'description',
            'statut_validation' => 'statut_validation',
            'commentaire_validation' => 'commentaire_validation',
            'tarif' => 'consultation_prix',
            'experience' => 'annee_experience',
        ];

        $fields = [];
        $params = [':user_id' => $userId];

        foreach ($data as $key => $value) {
            if (!isset($allowedMap[$key])) {
                continue;
            }

            $column = $allowedMap[$key];
            $placeholder = ':p_' . $column;
            if (isset($params[$placeholder])) {
                $params[$placeholder] = $value;
                continue;
            }

            $fields[] = "$column = $placeholder";
            $params[$placeholder] = $value;
        }

        if (empty($fields)) {
            return false;
        }

        $exists = $this->findByUserId($userId);

        if ($exists) {
            $sql = "UPDATE medecins SET " . implode(', ', $fields) . " WHERE user_id = :user_id";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute($params);
        }

        $defaults = [
            'specialite' => '',
            'numero_ordre' => '',
            'annee_experience' => null,
            'consultation_prix' => null,
            'cabinet_adresse' => null,
            'description' => null,
        ];

        foreach ($data as $key => $value) {
            if (isset($allowedMap[$key])) {
                $defaults[$allowedMap[$key]] = $value;
            }
        }

        $stmt = $this->db->prepare(
            "INSERT INTO medecins (
                user_id, specialite, numero_ordre, annee_experience, consultation_prix, cabinet_adresse, description
            ) VALUES (
                :user_id, :specialite, :numero_ordre, :annee_experience, :consultation_prix, :cabinet_adresse, :description
            )"
        );

        return $stmt->execute([
            ':user_id' => $userId,
            ':specialite' => (string) $defaults['specialite'],
            ':numero_ordre' => (string) $defaults['numero_ordre'],
            ':annee_experience' => $defaults['annee_experience'],
            ':consultation_prix' => $defaults['consultation_prix'],
            ':cabinet_adresse' => $defaults['cabinet_adresse'],
            ':description' => $defaults['description'],
        ]);
    }
}
