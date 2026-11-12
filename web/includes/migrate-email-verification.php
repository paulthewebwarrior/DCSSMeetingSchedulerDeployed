<?php
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require_once __DIR__ . '/Database.php';
try {
    $db = Database::getConnection();
    $existing = $db->query('SHOW COLUMNS FROM users')->fetchAll(PDO::FETCH_COLUMN);
    $columns = [
        // Existing/imported demo accounts remain verified. Signup explicitly inserts NULL.
        'email_verified_at' => 'DATETIME NULL DEFAULT CURRENT_TIMESTAMP',
        'verification_code_hash' => 'CHAR(64) NULL DEFAULT NULL',
        'verification_expires_at' => 'DATETIME NULL DEFAULT NULL',
        'verification_attempts' => 'TINYINT UNSIGNED NOT NULL DEFAULT 0',
        'verification_sent_at' => 'DATETIME NULL DEFAULT NULL',
    ];
    $changes = [];
    foreach ($columns as $name => $definition) {
        if (!in_array($name, $existing, true)) { $changes[] = "ADD COLUMN `{$name}` {$definition}"; }
    }
    if ($changes) { $db->exec('ALTER TABLE users ' . implode(', ', $changes)); }
    echo "Email verification schema is ready. Existing accounts remain verified.\n";
} catch (Throwable $error) {
    fwrite(STDERR, "Email verification migration failed. Check the database connection and ALTER TABLE permission.\n");
    exit(1);
}
