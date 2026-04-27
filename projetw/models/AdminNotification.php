<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';

class AdminNotification
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    public function add(string $type, string $title, string $message, ?int $referenceId = null, ?string $referenceType = null): int
    {
        $stmt = $this->db->prepare("
            INSERT INTO admin_notifications (type, title, message, reference_id, reference_type, created_at)
            VALUES (:type, :title, :message, :ref_id, :ref_type, NOW())
        ");
        $stmt->execute([
            ':type'     => $type,
            ':title'    => $title,
            ':message'  => $message,
            ':ref_id'   => $referenceId,
            ':ref_type' => $referenceType,
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function getAll(int $limit = 50): array
    {
        $stmt = $this->db->prepare("
            SELECT * FROM admin_notifications
            ORDER BY created_at DESC
            LIMIT :limit
        ");
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getUnread(): array
    {
        $stmt = $this->db->query("
            SELECT * FROM admin_notifications
            WHERE is_read = 0
            ORDER BY created_at DESC
        ");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function countUnread(): int
    {
        return (int) $this->db->query("SELECT COUNT(*) FROM admin_notifications WHERE is_read = 0")->fetchColumn();
    }

    public function markAllRead(): void
    {
        $this->db->exec("UPDATE admin_notifications SET is_read = 1");
    }

    public function markRead(int $id): void
    {
        $this->db->prepare("UPDATE admin_notifications SET is_read = 1 WHERE id = ?")->execute([$id]);
    }

    public function delete(int $id): void
    {
        $this->db->prepare("DELETE FROM admin_notifications WHERE id = ?")->execute([$id]);
    }

    public function deleteAll(): void
    {
        $this->db->exec("DELETE FROM admin_notifications");
    }

    public function getByType(string $type): array
    {
        $stmt = $this->db->prepare("
            SELECT * FROM admin_notifications
            WHERE type = :type
            ORDER BY created_at DESC
        ");
        $stmt->execute([':type' => $type]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}