<?php
class Auth {
    public static function start(): void {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    public static function check(): bool {
        self::start();
        return isset($_SESSION['user']) && is_array($_SESSION['user']);
    }

    public static function user(): ?array {
        self::start();
        return $_SESSION['user'] ?? null;
    }

    public static function login(array $user): void {
        self::start();
        session_regenerate_id(true);
        $_SESSION['user'] = $user;
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

        $user = self::user();
        if (!in_array($user['role'], $roles, true)) {
            http_response_code(403);
            die("Access Denied: You do not have permission to view this page.");
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

