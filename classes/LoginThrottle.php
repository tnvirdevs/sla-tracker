<?php
/**
 * LoginThrottle
 * Simple IP-based brute-force protection for the login form.
 * Locks an IP address out for LOCKOUT_MINUTES after MAX_ATTEMPTS
 * consecutive failures within WINDOW_MINUTES.
 */
class LoginThrottle
{
    private const MAX_ATTEMPTS   = 5;
    private const WINDOW_MINUTES = 15;
    private const LOCKOUT_MINUTES = 15;

    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Returns the number of seconds remaining before this identifier may
     * try again, or 0 if it is not currently locked out.
     */
    public function secondsUntilUnlocked(string $identifier): int
    {
        $stmt = $this->db->prepare('SELECT attempts, last_attempt FROM login_attempts WHERE identifier = :id');
        $stmt->execute(['id' => $identifier]);
        $row = $stmt->fetch();

        if (!$row) {
            return 0;
        }

        $windowExpired = (time() - strtotime($row['last_attempt'])) > (self::WINDOW_MINUTES * 60);
        if ($windowExpired) {
            return 0;
        }

        if ((int) $row['attempts'] < self::MAX_ATTEMPTS) {
            return 0;
        }

        $unlocksAt = strtotime($row['last_attempt']) + (self::LOCKOUT_MINUTES * 60);
        $remaining = $unlocksAt - time();

        return max(0, $remaining);
    }

    public function isLocked(string $identifier): bool
    {
        return $this->secondsUntilUnlocked($identifier) > 0;
    }

    public function registerFailure(string $identifier): void
    {
        $stmt = $this->db->prepare('SELECT attempts, last_attempt FROM login_attempts WHERE identifier = :id');
        $stmt->execute(['id' => $identifier]);
        $row = $stmt->fetch();

        if (!$row) {
            $insert = $this->db->prepare(
                'INSERT INTO login_attempts (identifier, attempts, first_attempt, last_attempt) VALUES (:id, 1, NOW(), NOW())'
            );
            $insert->execute(['id' => $identifier]);
            return;
        }

        $windowExpired = (time() - strtotime($row['last_attempt'])) > (self::WINDOW_MINUTES * 60);
        $newAttempts = $windowExpired ? 1 : ((int) $row['attempts'] + 1);

        $update = $this->db->prepare(
            'UPDATE login_attempts SET attempts = :attempts, last_attempt = NOW() WHERE identifier = :id'
        );
        $update->execute(['attempts' => $newAttempts, 'id' => $identifier]);
    }

    public function clear(string $identifier): void
    {
        $stmt = $this->db->prepare('DELETE FROM login_attempts WHERE identifier = :id');
        $stmt->execute(['id' => $identifier]);
    }

    /**
     * Best-effort client IP resolution. Only trusts REMOTE_ADDR by default
     * since X-Forwarded-For / X-Real-IP headers are attacker-controlled
     * unless the app is known to sit behind a trusted, correctly configured
     * reverse proxy.
     */
    public static function clientIdentifier(): string
    {
        return $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    }
}
