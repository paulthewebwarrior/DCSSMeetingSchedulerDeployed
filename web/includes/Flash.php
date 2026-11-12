<?php
require_once __DIR__ . '/Auth.php';
class Flash {
    public static function set(string $type, string $message): void {
        Auth::start();
        $_SESSION['flash'] = [
            'type'    => $type,
            'message' => $message
        ];
    }

    public static function get(): ?array {
        Auth::start();
        if (isset($_SESSION['flash'])) {
            $flash = $_SESSION['flash'];
            unset($_SESSION['flash']);
            return $flash;
        }
        return null;
    }

    public static function render(): string {
        $flash = self::get();
        if (!$flash) {
            return '';
        }
        $type = htmlspecialchars($flash['type']);
        $msg  = htmlspecialchars($flash['message']);
        $bg = ($type === 'error') ? '#f8d7da' : (($type === 'warning') ? '#fff3cd' : '#d4edda');
        $color = ($type === 'error') ? '#721c24' : (($type === 'warning') ? '#856404' : '#155724');
        $border = ($type === 'error') ? '#f5c6cb' : (($type === 'warning') ? '#ffeeba' : '#c3e6cb');
        return "<div class='alert-banner' role='alert' tabindex='-1' style='background:{$bg}; color:{$color}; border:1px solid {$border}; padding:12px 18px; border-radius:6px; margin-bottom:18px; font-weight:500; font-size:14px; display:flex; align-items:center; justify-content:space-between;'><span>{$msg}</span><button type='button' aria-label='Dismiss notification' onclick='this.parentElement.remove()' style='background:none; border:none; color:inherit; font-size:18px; cursor:pointer;'>&times;</button></div>";
    }
}

