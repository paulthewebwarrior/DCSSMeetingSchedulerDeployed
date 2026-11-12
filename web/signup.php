<?php
require_once __DIR__ . '/includes/Database.php';
require_once __DIR__ . '/includes/Auth.php';
require_once __DIR__ . '/includes/Flash.php';
require_once __DIR__ . '/includes/EmailVerification.php';

Auth::redirectIfLoggedIn();
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        App::verifyPost();
        $email = Validation::email($_POST);
        $firstName = Validation::text($_POST, 'first_name', 'First name', 50);
        $lastName = Validation::text($_POST, 'last_name', 'Last name', 50);
        $idNumber = Validation::text($_POST, 'id_number', 'ID number', 20, false);
        $program = Validation::text($_POST, 'program', 'Program / department', 50, false);
        $role = Validation::choice($_POST, 'role', ['Faculty', 'Student']);
        $password = Validation::password($_POST, 'password', 'Password', true);
        $confirm = Validation::password($_POST, 'confirm_password', 'Confirm password');
        if ($password !== $confirm) {
            throw new ValidationException('Passwords do not match.');
        }
        $db = Database::getConnection();
        $stmt = $db->prepare('SELECT user_id FROM users WHERE ue_email = ? LIMIT 1');
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            throw new ValidationException('An account with this email address already exists.');
        }
        $stmt = $db->prepare('INSERT INTO users (ue_email, user_password, first_name, last_name, role, id_number, program_or_dept, is_active, must_change_password, email_verified_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 0, NULL)');
        $stmt->execute([$email, $password, $firstName, $lastName, $role, $idNumber, $program, $role === 'Faculty' ? 0 : 1]);
        unset($_SESSION['old_input']);
        $_SESSION['verification_email'] = $email;
        try {
            EmailVerification::send($db, $email);
            Flash::set('success', 'Account created! We sent a six-digit verification code to your UE email. Check your inbox and spam folder.');
        } catch (ValidationException | MailDeliveryException | PDOException $deliveryError) {
            $detail = $deliveryError instanceof PDOException ? App::databaseError($deliveryError) : $deliveryError->getMessage();
            Flash::set('warning', 'Your account was created but is not verified yet. ' . $detail . ' Use the resend form to request a code.');
        }
        header('Location: verify-email.php', true, 303);
        exit;
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
    <title>DCSS Meeting Scheduler - Sign Up</title>
    <link rel="stylesheet" href="css/app.css">
</head>
<body class="auth-page">
    <div class="auth-card" style="max-width:540px;">
        <div class="auth-header">
            <h1 class="auth-title">Create an Account</h1>
            <p class="auth-subtitle">Register for the DCSS Consultation Portal</p>
            <p class="form-hint">We will email you a verification code before you can log in.</p>
        </div>

        <?php if (!empty($error)): ?>
            <div role="alert" tabindex="-1" class="alert-banner" style="background:#fee2e2; border:1px solid #fca5a5; color:#991b1b; padding:10px 14px; border-radius:6px; font-size:13px; font-weight:600; margin-bottom:16px;">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="signup.php">
                            <?= App::csrfField() ?>
            <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px;">
                <div class="form-group">
                    <label class="form-label" for="first_name">First Name</label>
                    <input maxlength="50" type="text" id="first_name" name="first_name" class="form-control" required>
                </div>
                <div class="form-group">
                    <label class="form-label" for="last_name">Last Name</label>
                    <input maxlength="50" type="text" id="last_name" name="last_name" class="form-control" required>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label" for="email">UE Email Address</label>
                <input maxlength="100" type="email" id="email" name="email" class="form-control" placeholder="name@ue.edu.ph" required>
            </div>

            <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px;">
                <div class="form-group">
                    <label class="form-label" for="role">Role</label>
                    <select id="role" name="role" class="form-control">
                        <option value="Student" <?= $role === 'Student' ? 'selected' : '' ?>>Student</option>
                        <option value="Faculty" <?= $role === 'Faculty' ? 'selected' : '' ?>>Faculty</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label" for="id_number">ID Number</label>
                    <input maxlength="20" type="text" id="id_number" name="id_number" class="form-control" placeholder="e.g. 2022-0001">
                </div>
            </div>

            <div class="form-group">
                <label class="form-label" for="program">Program / Department</label>
                <input maxlength="50" type="text" id="program" name="program" class="form-control" placeholder="e.g. BSCS or DCSS">
            </div>

            <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px;">
                <div class="form-group">
                    <label class="form-label" for="password">Password</label>
                    <input autocomplete="new-password" maxlength="64" type="password" id="password" name="password" class="form-control" required minlength="6">
                </div>
                <div class="form-group">
                    <label class="form-label" for="confirm_password">Confirm Password</label>
                    <input autocomplete="new-password" maxlength="64" type="password" id="confirm_password" name="confirm_password" class="form-control" required minlength="6">
                </div>
            </div>

            <button type="submit" class="btn btn-success" style="width:100%; padding:12px; font-size:15px; margin-top:8px;">
                Register
            </button>

            <div style="text-align:center; margin-top:16px; font-size:14px;">
                Already have an account? <a href="login.php" style="color:var(--dcss-green); text-decoration:none; font-weight:600;">Log In</a>
            </div>
        </form>
    </div>
    <?= App::formState() ?>
    <script src="assets/validation.js" defer></script>
</body>
</html>

