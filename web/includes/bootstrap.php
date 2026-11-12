<?php
require_once __DIR__ . '/Validation.php';

date_default_timezone_set('Asia/Manila');
ini_set('display_errors', '0');
ini_set('log_errors', '1');

set_exception_handler(function (Throwable $error): void {
    $validation = $error instanceof ValidationException;
    $status = $validation ? 422 : ($error instanceof PDOException ? 503 : 500);
    http_response_code($status);
    if (!$validation) {
        error_log(get_class($error) . ': ' . $error->getMessage());
    }
    $message = $validation ? $error->getMessage() : 'We could not complete your request. Please try again shortly. If the problem continues, contact the administrator.';
    $message = htmlspecialchars($message, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    echo '<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Request could not be completed</title><link rel="stylesheet" href="css/app.css"></head><body class="auth-page"><main class="auth-card"><h1>Request could not be completed</h1><p role="alert">' . $message . '</p><p><a href="index.php">Return to the portal</a></p></main></body></html>';
});

final class App {
    public static function token(): string {
        Auth::start();
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    public static function csrfField(): string {
        return '<input type="hidden" name="csrf_token" value="' . self::token() . '">';
    }

    public static function verifyPost(): void {
        $token = $_POST['csrf_token'] ?? null;
        if (!is_string($token) || !hash_equals(self::token(), $token)) {
            throw new ValidationException('Your form has expired. Refresh the page and try again.');
        }
    }

    public static function rememberInput(): void {
        $safe = [];
        foreach ($_POST as $key => $value) {
            if (is_string($value) && !str_contains($key, 'password') && $key !== 'csrf_token' && strlen($value) <= 2048) {
                $safe[$key] = $value;
            }
        }
        $_SESSION['old_input'] = $safe;
    }

    public static function formState(): string {
        Auth::start();
        $old = $_SESSION['old_input'] ?? [];
        unset($_SESSION['old_input']);
        return '<script type="application/json" id="form-state">' . json_encode($old, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_INVALID_UTF8_SUBSTITUTE) . '</script>';
    }

    public static function verifyPassword(string $password, string $stored): bool {
        return hash_equals($stored, $password);
    }

    public static function databaseError(PDOException $error): string {
        error_log('Database request failed: ' . $error->getMessage());
        if (($error->errorInfo[1] ?? null) === 1062) {
            return 'An account with this email address already exists.';
        }
        return 'We could not complete your request. Please try again shortly. If the problem continues, contact the administrator.';
    }
}
