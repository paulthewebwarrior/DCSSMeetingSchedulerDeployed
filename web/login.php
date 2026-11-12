<?php
require_once __DIR__ . '/includes/Database.php';
require_once __DIR__ . '/includes/Auth.php';
require_once __DIR__ . '/includes/Flash.php';

Auth::redirectIfLoggedIn();
$error = '';
$email = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        App::verifyPost();
        $email = Validation::email($_POST);
        $password = Validation::password($_POST, 'password', 'Password');
        if (($_SESSION['login_blocked_until'] ?? 0) > time()) {
            throw new ValidationException('Too many unsuccessful attempts. Please wait one minute before trying again.');
        }
        $db = Database::getConnection();
        $stmt = $db->prepare('SELECT * FROM users WHERE ue_email = ? LIMIT 1');
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        if (!$user || !App::verifyPassword($password, $user['user_password'])) {
            $_SESSION['login_failures'] = ($_SESSION['login_failures'] ?? 0) + 1;
            if ($_SESSION['login_failures'] >= 5) {
                $_SESSION['login_blocked_until'] = time() + 60;
                $_SESSION['login_failures'] = 0;
            }
            throw new ValidationException('Invalid email or password. Please try again.');
        }
        if (!$user['is_active']) {
            throw new ValidationException('Your account is currently disabled. Please contact the administrator.');
        }
        if ($user['email_verified_at'] === null) {
            $_SESSION['verification_email'] = $email;
            Flash::set('warning', 'Verify your UE email before logging in. Enter the code from your email or request a new one.');
            header('Location: verify-email.php', true, 303);
            exit;
        }
        if (!in_array($user['role'], ['Admin', 'Faculty', 'Student'], true)) {
            throw new ValidationException('Your account role is unavailable. Please contact the administrator.');
        }
        unset($_SESSION['login_failures'], $_SESSION['login_blocked_until'], $_SESSION['old_input']);
        Auth::login($user);
        Auth::redirectIfLoggedIn();
    } catch (ValidationException | PDOException $exception) {
        $error = $exception instanceof ValidationException ? $exception->getMessage() : App::databaseError($exception);
        App::rememberInput();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DCSS Meeting Scheduler - Login</title>
    <link rel="stylesheet" href="css/app.css">
</head>
<body class="auth-page">
    <div class="auth-card">
        <div class="auth-header">
            <img src="assets/dcss.jpg" alt="DCSS Logo" class="auth-logo" onerror="this.style.display='none'">
            <h1 class="auth-title">DCSS Meeting Scheduler</h1>
            <p class="auth-subtitle">Faculty & Student Consultation Portal</p>
        </div>

        <?= Flash::render() ?>

        <?php if (!empty($error)): ?>
            <div role="alert" tabindex="-1" class="alert-banner" style="background:#fee2e2; border:1px solid #fca5a5; color:#991b1b; padding:10px 14px; border-radius:6px; font-size:13px; font-weight:600; margin-bottom:16px;">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="login.php">
                            <?= App::csrfField() ?>
            <div class="form-group">
                <label class="form-label" for="email">UE Email Address</label>
                <input maxlength="100" type="email" id="email" name="email" class="form-control"
                       placeholder="e.g. name@ue.edu.ph"
                       pattern="^[a-zA-Z0-9._%+-]+@ue\.edu\.ph$"
                       title="Please enter a valid UE email address ending with @ue.edu.ph"
                       value="<?= htmlspecialchars($email) ?>" required autofocus>
            </div>

            <div class="form-group">
                <label class="form-label" for="password">Password</label>
                <input autocomplete="current-password" maxlength="64" type="password" id="password" name="password" class="form-control"
                       placeholder="Enter your password" required>
            </div>

            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px; font-size:13px;">
                <label style="display:flex; align-items:center; gap:6px; cursor:pointer;">
                    <input type="checkbox" onclick="let p=document.getElementById('password'); p.type = this.checked ? 'text' : 'password';">
                    Show password
                </label>
                <a href="signup.php" style="color:var(--dcss-green); text-decoration:none; font-weight:600;">Sign up</a>
            </div>

            <button type="submit" class="btn btn-success" style="width:100%; padding:12px; font-size:15px;">
                Log In
            </button>
        </form>
    </div>
    <?= App::formState() ?>
    <script src="assets/validation.js" defer></script>
</body>
</html>
