<?php
class ValidationException extends RuntimeException {}

final class Validation {
    public static function text(array $input, string $key, string $label, int $max, bool $required = true): string {
        $value = $input[$key] ?? '';
        if (!is_string($value)) {
            throw new ValidationException("{$label} must be a single value.");
        }
        if (!mb_check_encoding($value, 'UTF-8') || preg_match('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', $value)) {
            throw new ValidationException("{$label} contains invalid characters.");
        }
        $value = trim($value);
        if ($required && $value === '') {
            throw new ValidationException("{$label} is required.");
        }
        if (mb_strlen($value, 'UTF-8') > $max) {
            throw new ValidationException("{$label} must be {$max} characters or fewer.");
        }
        return $value;
    }

    public static function email(array $input): string {
        $email = self::text($input, 'email', 'UE email address', 100);
        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || !preg_match('/@ue\.edu\.ph$/i', $email)) {
            throw new ValidationException('Enter a valid UE email address ending in @ue.edu.ph.');
        }
        return strtolower($email);
    }

    public static function choice(array $input, string $key, array $allowed, string $default = ''): string {
        $value = self::text($input, $key, ucfirst(str_replace('_', ' ', $key)), 100, false);
        $value = $value === '' ? $default : $value;
        if (!in_array($value, $allowed, true)) {
            throw new ValidationException('Please select a valid ' . str_replace('_', ' ', $key) . '.');
        }
        return $value;
    }

    public static function id(array $input, string $key): int {
        $value = $input[$key] ?? '';
        if (!is_string($value) || !ctype_digit($value) || filter_var($value, FILTER_VALIDATE_INT) === false || (int)$value < 1) {
            throw new ValidationException('Please select a valid record. Refresh the page and try again.');
        }
        return (int)$value;
    }

    public static function password(array $input, string $key, string $label, bool $new = false): string {
        $value = $input[$key] ?? '';
        if (!is_string($value) || str_contains($value, "\0") || !mb_check_encoding($value, 'UTF-8')) {
            throw new ValidationException("{$label} is invalid.");
        }
        if ($value === '' || ($new && (mb_strlen($value, 'UTF-8') < 6 || trim($value) === ''))) {
            throw new ValidationException($new ? "{$label} must contain at least 6 characters and cannot be blank." : "{$label} is required.");
        }
        if (mb_strlen($value, 'UTF-8') > 64) {
            throw new ValidationException("{$label} must be 64 characters or fewer.");
        }
        return $value;
    }

    public static function time(array $input, string $key, string $label): string {
        $value = self::text($input, $key, $label, 5);
        if (!preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/D', $value)) {
            throw new ValidationException("{$label} must be a valid time in HH:MM format.");
        }
        return $value;
    }

    public static function meeting(array $input): array {
        $date = self::text($input, 'meeting_date', 'Meeting date', 10);
        $parsed = DateTimeImmutable::createFromFormat('!Y-m-d', $date);
        if (!$parsed || $parsed->format('Y-m-d') !== $date) {
            throw new ValidationException('Select a valid meeting date.');
        }
        $start = self::time($input, 'start_time', 'Start time');
        $end = self::time($input, 'end_time', 'End time');
        if ($end <= $start) {
            throw new ValidationException('End time must be later than start time on the same day.');
        }
        $startDate = new DateTimeImmutable("{$date} {$start}:00");
        if ($startDate <= new DateTimeImmutable()) {
            throw new ValidationException('Select a future meeting date and time.');
        }
        return [$startDate->format('Y-m-d H:i:s'), "{$date} {$end}:00", $parsed->format('l')];
    }
}
