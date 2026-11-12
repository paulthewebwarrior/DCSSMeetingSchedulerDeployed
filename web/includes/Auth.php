<?php
require_once __DIR__ . '/bootstrap.php';
class Auth {
    public static function start(): void {
        if (session_status() === PHP_SESSION_NONE) {
            ini_set('session.use_strict_mode', '1');
            session_set_cookie_params(['httponly' => true, 'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off', 'samesite' => 'Lax']);
            session_start();
        }
    }

    public static function check(): bool {
        self::start();
        return isset($_SESSION['user']) && is_array($_SESSION['user'])
            && isset($_SESSION['user']['user_id'], $_SESSION['user']['role'])
            && in_array($_SESSION['user']['role'], ['Admin', 'Faculty', 'Student'], true);
    }

    public static function user(): ?array {
        self::start();
        return $_SESSION['user'] ?? null;
    }

    public static function login(array $user): void {
        self::start();
        session_regenerate_id(true);
        $_SESSION['user'] = $user;
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    public static function logout(): void {
        self::start();
        $_SESSION = [];
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        session_destroy();
    }

    public static function requireRole(string ...$roles): array {
        self::start();
        if (!self::check()) {
            header('Location: login.php');
            exit;
        }

        require_once __DIR__ . '/Database.php';
        require_once __DIR__ . '/Flash.php';
        $stmt = Database::getConnection()->prepare('SELECT * FROM users WHERE user_id = :id');
        $stmt->execute([':id' => self::user()['user_id']]);
        $user = $stmt->fetch();
        if (!$user || !$user['is_active']) {
            unset($_SESSION['user']);
            Flash::set('error', 'Your account is unavailable or disabled. Please contact the administrator.');
            header('Location: login.php');
            exit;
        }
        if ($user['email_verified_at'] === null) {
            unset($_SESSION['user']);
            $_SESSION['verification_email'] = $user['ue_email'];
            Flash::set('warning', 'Please verify your UE email before accessing the portal.');
            header('Location: verify-email.php');
            exit;
        }
        $_SESSION['user'] = $user;
        if (!in_array($user['role'], $roles, true)) {
            http_response_code(403);
            echo '<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><title>Access denied</title></head><body><main><h1>Access denied</h1><p role="alert">You do not have permission to view this page.</p><a href="index.php">Return to your portal</a></main></body></html>';
            exit;
        }

        return $user;
    }

    public static function redirectIfLoggedIn(): void {
        if (self::check()) {
            $user = self::user();
            switch ($user['role']) {
                case 'Admin':
                    header('Location: admin.php');
                    exit;
                case 'Faculty':
                    header('Location: faculty.php');
                    exit;
                case 'Student':
                    header('Location: student.php');
                    exit;
                default:
                    header('Location: login.php');
                    exit;
            }
        }
    }
}

