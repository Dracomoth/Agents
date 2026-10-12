<?php
namespace App\Repositories;

use App\Core\Database;
use PDO;

class WorkbenchDocumentRepository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    public function getByUserId(int $userId): array
    {
        $stmt = $this->db->prepare("
            SELECT id, user_id, filename, file_type, file_size, content, created_at, updated_at,
                   CHAR_LENGTH(content) as char_count
            FROM workbench_documents
            WHERE user_id = :user_id
            ORDER BY id DESC
        ");
        $stmt->execute(['user_id' => $userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findById(int $id, int $userId): ?array
    {
        $stmt = $this->db->prepare("
            SELECT * FROM workbench_documents
            WHERE id = :id AND user_id = :user_id
            LIMIT 1
        ");
        $stmt->execute(['id' => $id, 'user_id' => $userId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function create(int $userId, string $filename, string $content, string $fileType = 'text', int $fileSize = 0): int
    {
        if ($fileSize <= 0) {
            $fileSize = strlen($content);
        }

        $stmt = $this->db->prepare("
            INSERT INTO workbench_documents (user_id, filename, file_type, file_size, content)
            VALUES (:user_id, :filename, :file_type, :file_size, :content)
        ");
        $stmt->execute([
            'user_id' => $userId,
            'filename' => trim($filename),
            'file_type' => $fileType,
            'file_size' => $fileSize,
            'content' => $content
        ]);
        return (int)$this->db->lastInsertId();
    }

    public function delete(int $id, int $userId): bool
    {
        $stmt = $this->db->prepare("
            DELETE FROM workbench_documents 
            WHERE id = :id AND user_id = :user_id
        ");
        return $stmt->execute(['id' => $id, 'user_id' => $userId]);
    }

    public function deleteAll(int $userId): bool
    {
        $stmt = $this->db->prepare("
            DELETE FROM workbench_documents 
            WHERE user_id = :user_id
        ");
        return $stmt->execute(['user_id' => $userId]);
    }
}
