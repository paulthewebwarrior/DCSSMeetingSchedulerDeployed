<?php
require_once __DIR__ . '/includes/Database.php';
require_once __DIR__ . '/includes/Auth.php';
require_once __DIR__ . '/includes/Flash.php';

Auth::redirectIfLoggedIn();

$error = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL) || !preg_match('/^[a-zA-Z0-9._%+-]+@ue\.edu\.ph$/i', $email)) {
        $error = "Please enter a valid UE email.";
    } elseif (strlen($password) < 6) {
        $error = "Please enter a password of at least 6 characters.";
    } else {
        try {
            $db = Database::getConnection();
            $stmt = $db->prepare("SELECT * FROM users WHERE ue_email = :email LIMIT 1");
            $stmt->execute([':email' => $email]);
            $user = $stmt->fetch();

            if ($user && $user['user_password'] === $password) {
                if (!$user['is_active']) {
                    if ($user['role'] === 'Faculty') {
                        $error = "Your faculty account is pending administrator approval. Please wait for an administrator to verify and approve your registration.";
                    } else {
                        $error = "Your account is currently disabled. Please contact the administrator.";
                    }
                } else {
                    Auth::login($user);
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
                            $error = "Unsupported user role.";
                    }
                }
            } else {
                $error = "Invalid email or password. Please try again.";
            }
        } catch (PDOException $e) {
            $error = "Database connection error. Please ensure database server is running.";
        }
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
            <div style="background:#fee2e2; border:1px solid #fca5a5; color:#991b1b; padding:10px 14px; border-radius:6px; font-size:13px; font-weight:600; margin-bottom:16px;">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="login.php">
            <div class="form-group">
                <label class="form-label" for="email">UE Email Address</label>
                <input type="email" id="email" name="email" class="form-control" 
                       placeholder="e.g. name@ue.edu.ph" 
                       pattern="^[a-zA-Z0-9._%+-]+@ue\.edu\.ph$"
                       title="Please enter a valid UE email address ending with @ue.edu.ph"
                       value="<?= htmlspecialchars($email) ?>" required autofocus>
            </div>

            <div class="form-group">
                <label class="form-label" for="password">Password</label>
                <input type="password" id="password" name="password" class="form-control" 
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
</body>
</html>
