<?php
/**
 * Settings
 * Reads and updates the single-row settings table.
 */
class Settings
{
    private PDO $db;
    private static ?array $cache = null;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function get(): array
    {
        if (self::$cache === null) {
            $row = $this->db->query('SELECT * FROM settings ORDER BY id ASC LIMIT 1')->fetch();
            self::$cache = $row ?: [
                'id'            => 0,
                'site_name'     => APP_NAME,
                'timezone'      => DEFAULT_TIMEZONE,
                'company_name'  => '',
            ];
        }
        return self::$cache;
    }

    public function update(array $data): bool
    {
        $settings = $this->get();
        if (empty($settings['id'])) {
            $stmt = $this->db->prepare(
                'INSERT INTO settings (site_name, timezone, company_name) VALUES (:site_name, :timezone, :company_name)'
            );
            $ok = $stmt->execute($data);
        } else {
            $stmt = $this->db->prepare(
                'UPDATE settings SET site_name = :site_name, timezone = :timezone, company_name = :company_name WHERE id = :id'
            );
            $ok = $stmt->execute(array_merge($data, ['id' => $settings['id']]));
        }
        self::$cache = null;
        return $ok;
    }

    public function applyTimezone(): void
    {
        $settings = $this->get();
        if (!empty($settings['timezone'])) {
            date_default_timezone_set($settings['timezone']);
        }
    }
}
