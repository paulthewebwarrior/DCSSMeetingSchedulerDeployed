<?php
require_once __DIR__ . '/includes/Database.php';
require_once __DIR__ . '/includes/Auth.php';
require_once __DIR__ . '/includes/Flash.php';

Auth::redirectIfLoggedIn();

$error = '';
$success = '';
$firstName = '';
$lastName  = '';
$email     = '';
$idNumber  = '';
$program   = 'BSCS';
$role      = 'Student';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email     = trim($_POST['email'] ?? '');
    $password  = $_POST['password'] ?? '';
    $confirm   = $_POST['confirm_password'] ?? '';
    $firstName = trim($_POST['first_name'] ?? '');
    $lastName  = trim($_POST['last_name'] ?? '');
    $idNumber  = trim($_POST['id_number'] ?? '');
    $program   = trim($_POST['program'] ?? 'BSCS');
    $role      = $_POST['role'] ?? 'Student';

    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL) || !preg_match('/^[a-zA-Z0-9._%+-]+@ue\.edu\.ph$/i', $email)) {
        $error = "Please enter a valid UE email address (e.g. name@ue.edu.ph).";
    } elseif (empty($firstName) || empty($lastName)) {
        $error = "First name and last name are required.";
    } elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters long.";
    } elseif ($password !== $confirm) {
        $error = "Passwords do not match.";
    } else {
        try {
            $db = Database::getConnection();
            $stmt = $db->prepare("SELECT user_id FROM users WHERE ue_email = :email LIMIT 1");
            $stmt->execute([':email' => $email]);
            if ($stmt->fetch()) {
                $error = "An account with this email address already exists.";
            } else {
                $targetRole = in_array($role, ['Faculty', 'Student'], true) ? $role : 'Student';
                // Students are immediately active; Faculty requires admin approval before login
                $isActive = ($targetRole === 'Faculty') ? 0 : 1;

                $stmt = $db->prepare("
                    INSERT INTO users (ue_email, user_password, first_name, last_name, role, id_number, program_or_dept, is_active, must_change_password)
                    VALUES (:email, :password, :fname, :lname, :role, :idnum, :dept, :is_active, 0)
                ");
                $stmt->execute([
                    ':email'     => $email,
                    ':password'  => $password,
                    ':fname'     => $firstName,
                    ':lname'     => $lastName,
                    ':role'      => $targetRole,
                    ':idnum'     => $idNumber,
                    ':dept'      => $program,
                    ':is_active' => $isActive
                ]);

                if ($targetRole === 'Faculty') {
                    Flash::set('info', 'Registration submitted! Faculty accounts require administrator approval before logging in. Please wait for an administrator to verify and approve your account.');
                } else {
                    Flash::set('success', 'Registration successful! Your student account is active. You may now log in.');
                }
                header('Location: login.php');
                exit;
            }
        } catch (PDOException $e) {
            $error = "Database error: " . $e->getMessage();
        }
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
        </div>

        <?php if (!empty($error)): ?>
            <div style="background:#fee2e2; border:1px solid #fca5a5; color:#991b1b; padding:10px 14px; border-radius:6px; font-size:13px; font-weight:600; margin-bottom:16px;">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="signup.php">
            <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px;">
                <div class="form-group">
                    <label class="form-label" for="first_name">First Name</label>
                    <input type="text" id="first_name" name="first_name" class="form-control" value="<?= htmlspecialchars($firstName) ?>" required>
                </div>
                <div class="form-group">
                    <label class="form-label" for="last_name">Last Name</label>
                    <input type="text" id="last_name" name="last_name" class="form-control" value="<?= htmlspecialchars($lastName) ?>" required>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label" for="email">UE Email Address</label>
                <input type="email" id="email" name="email" class="form-control" placeholder="name@ue.edu.ph" pattern="^[a-zA-Z0-9._%+-]+@ue\.edu\.ph$" title="Please enter a valid UE email address ending with @ue.edu.ph" value="<?= htmlspecialchars($email) ?>" required>
            </div>

            <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px;">
                <div class="form-group">
                    <label class="form-label" for="role">Role</label>
                    <select id="role" name="role" class="form-control" onchange="updateRoleNotice()">
                        <option value="Student" <?= $role === 'Student' ? 'selected' : '' ?>>Student</option>
                        <option value="Faculty" <?= $role === 'Faculty' ? 'selected' : '' ?>>Faculty</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label" for="id_number">ID Number</label>
                    <input type="text" id="id_number" name="id_number" class="form-control" placeholder="e.g. 2022-0001" value="<?= htmlspecialchars($idNumber) ?>">
                </div>
            </div>

            <div id="role-approval-notice" style="margin-top:-6px; margin-bottom:14px; padding:10px 14px; border-radius:6px; font-size:13px; line-height:1.45; display:flex; align-items:flex-start; gap:10px;">
                <!-- Populated via JavaScript based on selected role -->
            </div>

            <div class="form-group">
                <label class="form-label" for="program">Program / Department</label>
                <input type="text" id="program" name="program" class="form-control" placeholder="e.g. BSCS or DCSS" value="<?= htmlspecialchars($program) ?>">
            </div>

            <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px;">
                <div class="form-group">
                    <label class="form-label" for="password">Password</label>
                    <input type="password" id="password" name="password" class="form-control" required minlength="6">
                </div>
                <div class="form-group">
                    <label class="form-label" for="confirm_password">Confirm Password</label>
                    <input type="password" id="confirm_password" name="confirm_password" class="form-control" required minlength="6">
                </div>
            </div>

            <button type="submit" id="submit-btn" class="btn btn-success" style="width:100%; padding:12px; font-size:15px; margin-top:8px;">
                Register
            </button>

            <div style="text-align:center; margin-top:16px; font-size:14px;">
                Already have an account? <a href="login.php" style="color:var(--dcss-green); text-decoration:none; font-weight:600;">Log In</a>
            </div>
        </form>
    </div>

    <script>
    function updateRoleNotice() {
        const roleSelect = document.getElementById('role');
        const notice = document.getElementById('role-approval-notice');
        const submitBtn = document.getElementById('submit-btn');
        if (!roleSelect || !notice) return;

        if (roleSelect.value === 'Faculty') {
            notice.style.background = '#fef3c7';
            notice.style.border = '1px solid #fde68a';
            notice.style.color = '#92400e';
            notice.innerHTML = '<span style="font-size:16px; line-height:1;">⏳</span> <div><strong>Faculty Account Verification:</strong> Faculty registrations require administrator approval before you can access the system. Your account will be submitted for verification.</div>';
            if (submitBtn) submitBtn.textContent = 'Submit Registration for Approval';
        } else {
            notice.style.background = '#ecfdf5';
            notice.style.border = '1px solid #a7f3d0';
            notice.style.color = '#065f46';
            notice.innerHTML = '<span style="font-size:16px; line-height:1;">✓</span> <div><strong>Instant Student Activation:</strong> Student accounts are activated immediately upon registration without waiting for approval.</div>';
            if (submitBtn) submitBtn.textContent = 'Register Student Account';
        }
    }
    document.addEventListener('DOMContentLoaded', updateRoleNotice);
    updateRoleNotice();
    </script>
</body>
</html>

