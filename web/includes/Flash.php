<?php
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
        
        switch ($type) {
            case 'error':
                $bg = '#fee2e2';
                $color = '#991b1b';
                $border = '#fca5a5';
                break;
            case 'warning':
                $bg = '#fef3c7';
                $color = '#92400e';
                $border = '#fde68a';
                break;
            case 'info':
                $bg = '#e0f2fe';
                $color = '#0369a1';
                $border = '#bae6fd';
                break;
            default: // success
                $bg = '#d1fae5';
                $color = '#065f46';
                $border = '#a7f3d0';
                break;
        }

        return "<div class='alert-banner' style='background:{$bg}; color:{$color}; border:1px solid {$border}; padding:12px 18px; border-radius:6px; margin-bottom:18px; font-weight:500; font-size:14px; display:flex; align-items:center; justify-content:space-between;'><span>{$msg}</span><button onclick='this.parentElement.remove()' style='background:none; border:none; color:inherit; font-size:18px; cursor:pointer;'>&times;</button></div>";
    }
}

