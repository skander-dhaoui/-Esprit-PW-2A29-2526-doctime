<?php
// ═══════════════════════════════════════════════
// FICHIER : services/TranslationService.php
// ═══════════════════════════════════════════════

require_once __DIR__ . '/../config/database.php';

class TranslationService
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
        $this->createTable();
    }

    private function createTable(): void
    {
        $this->db->exec("
            CREATE TABLE IF NOT EXISTS translations (
                id           INT AUTO_INCREMENT PRIMARY KEY,
                source_type  ENUM('article','reply') NOT NULL,
                source_id    INT NOT NULL,
                source_field VARCHAR(50) NOT NULL DEFAULT 'content',
                lang_to      VARCHAR(10) NOT NULL,
                translated   LONGTEXT NOT NULL,
                created_at   DATETIME NOT NULL DEFAULT NOW(),
                UNIQUE KEY uniq_trans (source_type, source_id, source_field, lang_to),
                INDEX idx_source (source_type, source_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    }

    public function getCached(string $type, int $id, string $field, string $lang): ?string
    {
        $stmt = $this->db->prepare("SELECT translated FROM translations WHERE source_type=? AND source_id=? AND source_field=? AND lang_to=?");
        $stmt->execute([$type, $id, $field, $lang]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? $row['translated'] : null;
    }

    public function saveCache(string $type, int $id, string $field, string $lang, string $text): void
    {
        $this->db->prepare("
            INSERT INTO translations (source_type, source_id, source_field, lang_to, translated)
            VALUES (?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE translated=VALUES(translated), created_at=NOW()
        ")->execute([$type, $id, $field, $lang, $text]);
    }

    public function getOriginalText(string $type, int $id, string $field = 'content'): ?string
    {
        if ($type === 'article') {
            $col  = ($field === 'titre') ? 'titre' : 'contenu';
            $stmt = $this->db->prepare("SELECT {$col} FROM articles WHERE id=?");
            $stmt->execute([$id]);
            $row  = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$row) return null;
            $text = $row[$col] ?? '';
            // Quill JSON -> texte brut
            $decoded = json_decode($text, true);
            if (json_last_error() === JSON_ERROR_NONE && isset($decoded['ops'])) {
                $plain = '';
                foreach ($decoded['ops'] as $op) {
                    if (isset($op['insert']) && is_string($op['insert'])) {
                        $plain .= $op['insert'];
                    }
                }
                $text = trim($plain);
            }
            return $text ?: null;
        }

        if ($type === 'reply') {
            $stmt = $this->db->prepare("SELECT replay FROM replies WHERE id=?");
            $stmt->execute([$id]);
            $row  = $stmt->fetch(PDO::FETCH_ASSOC);
            return $row ? ($row['replay'] ?? null) : null;
        }

        return null;
    }
}
?>