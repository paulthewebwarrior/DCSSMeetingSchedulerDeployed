<?php
require_once __DIR__ . '/includes/Auth.php';
require_once __DIR__ . '/includes/Flash.php';
require_once __DIR__ . '/includes/EmailVerification.php';

Auth::redirectIfLoggedIn();
$email = $_SESSION['verification_email'] ?? '';
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        App::verifyPost();
        $email = Validation::email($_POST);
        $_SESSION['verification_email'] = $email;
        $action = Validation::choice($_POST, 'action', ['verify_email', 'resend_verification']);
        $db = Database::getConnection();
        if ($action === 'resend_verification') {
            EmailVerification::send($db, $email);
            Flash::set('success', 'A new verification code has been sent. Check your inbox and spam folder. Only the latest code will work.');
            header('Location: verify-email.php', true, 303);
            exit;
        }
        $code = Validation::text($_POST, 'code', 'Verification code', 6);
        EmailVerification::verify($db, $email, $code);
        unset($_SESSION['verification_email'], $_SESSION['old_input']);
        Flash::set('success', 'Your email has been verified! You can now log in.');
        header('Location: login.php', true, 303);
        exit;
    } catch (ValidationException | MailDeliveryException | PDOException $exception) {
        $error = $exception instanceof PDOException ? App::databaseError($exception) : $exception->getMessage();
    }
}
$escapedEmail = htmlspecialchars($email, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="referrer" content="no-referrer">
    <title>DCSS - Verify Email</title>
    <link rel="stylesheet" href="css/app.css">
</head>
<body class="auth-page">
    <main class="auth-card" style="max-width:500px;">
        <div class="auth-header">
            <h1 class="auth-title">Verify Your Email</h1>
            <p class="auth-subtitle">Enter the six-digit code sent to your UE email.</p>
        </div>
        <?= Flash::render() ?>
        <?php if ($error !== ''): ?>
            <div role="alert" tabindex="-1" class="alert-banner" style="background:#fee2e2; border:1px solid #fca5a5; color:#991b1b; padding:12px; border-radius:6px; margin-bottom:16px;">
                <?= htmlspecialchars($error, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>
            </div>
        <?php endif; ?>
        <form method="POST" action="verify-email.php">
            <?= App::csrfField() ?>
            <input type="hidden" name="action" value="verify_email">
            <div class="form-group">
                <label class="form-label" for="verify-email">UE Email Address</label>
                <input id="verify-email" type="email" name="email" class="form-control" maxlength="100" autocomplete="email" value="<?= $escapedEmail ?>" required>
            </div>
            <div class="form-group">
                <label class="form-label" for="code">Verification Code</label>
                <input id="code" type="text" name="code" class="form-control" inputmode="numeric" autocomplete="one-time-code" pattern="[0-9]{6}" minlength="6" maxlength="6" aria-describedby="code-hint" required autofocus>
                <p id="code-hint" class="form-hint">Codes expire after 10 minutes. You have up to five attempts per code.</p>
            </div>
            <button type="submit" class="btn btn-success" style="width:100%;">Verify Email</button>
        </form>
        <div style="border-top:1px solid var(--border-light); margin-top:24px; padding-top:20px;">
            <h2 style="font-size:16px; margin-bottom:8px;">Didn't receive a code?</h2>
            <p class="form-hint">Check your spam folder, or request a new code. Please wait one minute between requests.</p>
            <form method="POST" action="verify-email.php">
                <?= App::csrfField() ?>
                <input type="hidden" name="action" value="resend_verification">
                <div class="form-group">
                    <label class="form-label" for="resend-email">UE Email Address</label>
                    <input id="resend-email" type="email" name="email" class="form-control" maxlength="100" autocomplete="email" value="<?= $escapedEmail ?>" required>
                </div>
                <button type="submit" class="btn btn-secondary" style="width:100%;">Resend Code</button>
            </form>
        </div>
        <p style="text-align:center; margin-top:20px;"><a href="login.php">Back to login</a> &middot; <a href="signup.php">Create an account</a></p>
    </main>
    <script src="assets/validation.js" defer></script>
</body>
</html>
