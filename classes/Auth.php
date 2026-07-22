<?php
/**
 * Auth
 * Handles login, logout, session guarding, and role checks.
 */
class Auth
{
    /**
     * A static, valid-format bcrypt hash with no known matching plaintext.
     * Used to run password_verify() even when no matching account is found,
     * so that login timing does not reveal whether a username/email exists
     * or whether an account is disabled (prevents user-enumeration via
     * timing side-channel).
     */
    private const DUMMY_HASH = '$2y$10$WaI3PbIVjqs/oUaWBeJvWOo5IStLkI/pLGU1c8RtaLmHWuAqtIBru';

    public static function attempt(string $username, string $password): bool
    {
        $db = Database::getInstance();
        $stmt = $db->prepare('SELECT * FROM users WHERE username = :u1 OR email = :u2 LIMIT 1');
        $stmt->execute(['u1' => $username, 'u2' => $username]);
        $user = $stmt->fetch();

        // Always perform a password_verify() call, even when the account
        // doesn't exist, so failed attempts take a consistent amount of
        // time regardless of whether the username is valid.
        $hash = $user['password'] ?? self::DUMMY_HASH;
        $passwordValid = password_verify($password, $hash);

        if (!$user || !$passwordValid || (int) $user['status'] !== 1) {
            return false;
        }

        self::login($user);
        return true;
    }

    public static function login(array $user): void
    {
        session_regenerate_id(true);
        $_SESSION['user_id']    = (int) $user['id'];
        $_SESSION['username']   = $user['username'];
        $_SESSION['full_name']  = $user['full_name'];
        $_SESSION['role']       = $user['role'];
        $_SESSION['last_activity'] = time();
    }

    public static function logout(): void
    {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
        }
        session_destroy();
    }

    public static function check(): bool
    {
        return !empty($_SESSION['user_id']);
    }

    public static function id(): ?int
    {
        return $_SESSION['user_id'] ?? null;
    }

    public static function user(): ?array
    {
        if (!self::check()) {
            return null;
        }
        static $cached = null;
        if ($cached === null) {
            $db = Database::getInstance();
            $stmt = $db->prepare('SELECT id, full_name, username, email, role, status, created_at FROM users WHERE id = :id');
            $stmt->execute(['id' => self::id()]);
            $cached = $stmt->fetch() ?: null;
        }
        return $cached;
    }

    public static function role(): ?string
    {
        return $_SESSION['role'] ?? null;
    }

    public static function isAdmin(): bool
    {
        return self::role() === 'Administrator';
    }

    public static function isManagerOrAdmin(): bool
    {
        return in_array(self::role(), ['Administrator', 'Manager'], true);
    }

    /**
     * Guard a page: require an active session, enforce idle timeout.
     */
    public static function requireLogin(): void
    {
        if (!self::check()) {
            self::redirectToLogin();
        }

        if (!empty($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > SESSION_TIMEOUT) {
            self::logout();
            self::redirectToLogin('timeout=1');
        }

        $_SESSION['last_activity'] = time();
    }

    public static function requireRole(array $roles): void
    {
        self::requireLogin();
        if (!in_array(self::role(), $roles, true)) {
            http_response_code(403);
            die('You do not have permission to access this page.');
        }
    }

    private static function redirectToLogin(string $query = ''): void
    {
        $url = BASE_URL . '/login.php' . ($query ? '?' . $query : '');
        header('Location: ' . $url);
        exit;
    }
}
