<?php
require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/Mailer.php';

final class EmailVerification {
    public static function send(PDO $db, string $email): void {
        Auth::start();
        if (($_SESSION['verification_send_after'] ?? 0) > time()) {
            throw new ValidationException('Please wait one minute before requesting another code.');
        }
        $_SESSION['verification_send_after'] = time() + 60;
        $code = (string)random_int(100000, 999999);
        $db->beginTransaction();
        try {
            $stmt = $db->prepare('SELECT * FROM users WHERE ue_email = ? FOR UPDATE');
            $stmt->execute([$email]);
            $user = $stmt->fetch();
            if (!$user || !$user['is_active']) {
                throw new ValidationException('Unable to send a code for this account. Check the email address or contact the administrator.');
            }
            if ($user['email_verified_at'] !== null) {
                throw new ValidationException('This email address is already verified. You can log in.');
            }
            if ($user['verification_sent_at'] && strtotime($user['verification_sent_at']) > time() - 60) {
                throw new ValidationException('Please wait one minute before requesting another code.');
            }
            $stmt = $db->prepare('UPDATE users SET verification_code_hash = ?, verification_expires_at = ?, verification_attempts = 0, verification_sent_at = ? WHERE user_id = ?');
            $stmt->execute([hash('sha256', $code), date('Y-m-d H:i:s', time() + 600), date('Y-m-d H:i:s'), $user['user_id']]);
            $db->commit();
        } catch (Throwable $error) {
            if ($db->inTransaction()) { $db->rollBack(); }
            throw $error;
        }
        Mailer::sendVerification($email, $code);
    }

    public static function verify(PDO $db, string $email, string $code): void {
        if (!preg_match('/^\d{6}$/D', $code)) {
            throw new ValidationException('Enter the six-digit code from your verification email.');
        }
        $db->beginTransaction();
        try {
            $stmt = $db->prepare('SELECT * FROM users WHERE ue_email = ? FOR UPDATE');
            $stmt->execute([$email]);
            $user = $stmt->fetch();
            if (!$user || !$user['is_active']) {
                throw new ValidationException('This verification request is unavailable. Check the email address or contact the administrator.');
            }
            if ($user['email_verified_at'] !== null) {
                throw new ValidationException('This email address is already verified. You can log in.');
            }
            if (!$user['verification_code_hash'] || !$user['verification_expires_at'] || strtotime($user['verification_expires_at']) <= time()) {
                throw new ValidationException('Your code has expired or has not been sent. Please request a new code.');
            }
            if ((int)$user['verification_attempts'] >= 5) {
                throw new ValidationException('Too many incorrect codes. Request a new code before trying again.');
            }
            if (!hash_equals($user['verification_code_hash'], hash('sha256', $code))) {
                $stmt = $db->prepare('UPDATE users SET verification_attempts = verification_attempts + 1 WHERE user_id = ?');
                $stmt->execute([$user['user_id']]);
                $db->commit();
                $remaining = 4 - (int)$user['verification_attempts'];
                $attemptsLabel = $remaining === 1 ? 'attempt' : 'attempts';
                throw new ValidationException($remaining > 0 ? "Incorrect verification code. You have {$remaining} {$attemptsLabel} remaining." : 'Too many incorrect codes. Please request a new code.');
            }
            $stmt = $db->prepare('UPDATE users SET email_verified_at = NOW(), verification_code_hash = NULL, verification_expires_at = NULL, verification_attempts = 0 WHERE user_id = ?');
            $stmt->execute([$user['user_id']]);
            $db->commit();
        } catch (Throwable $error) {
            if ($db->inTransaction()) { $db->rollBack(); }
            throw $error;
        }
    }
}
