<?php
namespace App\Repositories;

use App\Core\Database;
use PDO;

class SystemInfoRepository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    public function getVersions(): array
    {
        try {
            $stmt = $this->db->query("SELECT item_key, version FROM system_info");
            $map = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
            return [
                'system' => $map['system'] ?? '1.0.0',
                'database' => $map['database'] ?? '1.0.0'
            ];
        } catch (\Exception $e) {
            return [
                'system' => '1.0.0',
                'database' => '1.0.0'
            ];
        }
    }
}
