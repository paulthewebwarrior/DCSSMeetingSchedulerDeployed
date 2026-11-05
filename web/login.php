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

    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
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
                    $error = "Your account is currently disabled. Please contact the administrator.";
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
            $error = "Unable to connect to the database. Please try again.";
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
    <style>
        body {
            background-color: #f1f5f9;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 16px;
        }

        .win-form-dialog {
            width: 404px;
            background-color: #ffffff;
            border: 1px solid #cbd5e1;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08), 0 1px 3px rgba(0, 0, 0, 0.05);
            padding: 24px 35px 28px 35px;
            box-sizing: border-box;
            border-radius: 4px;
        }

        .win-logo-wrap {
            text-align: center;
            margin-bottom: 8px;
        }

        .win-logo {
            width: 120px;
            height: 120px;
            object-fit: contain;
            display: inline-block;
        }

        .win-title {
            font-size: 20px;
            font-weight: 700;
            color: rgb(30, 41, 59);
            text-align: center;
            margin: 6px 0 2px 0;
            line-height: 1.25;
        }

        .win-subtitle {
            font-size: 13px;
            color: rgb(100, 116, 139);
            text-align: center;
            margin-bottom: 20px;
        }

        .win-field {
            margin-bottom: 14px;
        }

        .win-label {
            display: block;
            font-size: 13px;
            font-weight: 600;
            color: rgb(51, 65, 85);
            margin-bottom: 5px;
        }

        .win-input {
            width: 100%;
            height: 32px;
            padding: 4px 10px;
            font-size: 14px;
            border: 1px solid #94a3b8;
            border-radius: 2px;
            box-sizing: border-box;
            color: rgb(30, 41, 59);
            outline: none;
            background: #ffffff;
            font-family: inherit;
        }

        .win-input:focus {
            border-color: rgb(0, 64, 0);
            box-shadow: 0 0 0 1px rgb(0, 64, 0);
        }

        .win-check {
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 12px;
            color: rgb(71, 85, 105);
            margin-top: 4px;
            cursor: pointer;
            user-select: none;
        }

        .win-error-msg {
            color: rgb(220, 38, 38);
            font-size: 12px;
            min-height: 16px;
            margin: 6px 0;
            text-align: left;
        }

        .win-buttons {
            display: flex;
            gap: 10px;
            margin-top: 14px;
        }

        .win-btn-cancel {
            flex: 1;
            height: 38px;
            background-color: rgb(248, 250, 252);
            border: 1px solid rgb(203, 213, 225);
            color: rgb(71, 85, 105);
            font-size: 13.5px;
            font-weight: 500;
            cursor: pointer;
            border-radius: 2px;
            font-family: inherit;
        }

        .win-btn-cancel:hover {
            background-color: #e2e8f0;
        }

        .win-btn-signin {
            flex: 1;
            height: 38px;
            background-color: rgb(0, 64, 0);
            border: none;
            color: #ffffff;
            font-size: 13.5px;
            font-weight: 600;
            cursor: pointer;
            border-radius: 2px;
            font-family: inherit;
        }

        .win-btn-signin:hover {
            background-color: #003300;
        }

        .win-link {
            display: block;
            text-align: center;
            margin-top: 16px;
            font-size: 13px;
            color: rgb(0, 64, 0);
            text-decoration: underline;
            cursor: pointer;
        }

        .win-link:hover {
            color: rgb(85, 121, 69);
        }
    </style>
</head>
<body>
    <div class="win-form-dialog">
        <div class="win-logo-wrap">
            <img src="assets/dcss.jpg" alt="DCSS Logo" class="win-logo" onerror="this.src='Resources/dcss.jpg'">
        </div>

        <div class="win-title">DCSS Meeting Scheduler</div>
        <div class="win-subtitle">Sign in to continue</div>

        <?= Flash::render() ?>

        <?php if (!empty($error)): ?>
            <div class="win-error-msg" style="background:#fee2e2; border:1px solid #fca5a5; padding:6px 10px; border-radius:2px;">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="login.php">
            <div class="win-field">
                <label class="win-label" for="email">UE Email</label>
                <input type="email" id="email" name="email" class="win-input" 
                       placeholder="Enter your UE email" 
                       value="<?= htmlspecialchars($email) ?>" required autofocus>
            </div>

            <div class="win-field">
                <label class="win-label" for="password">Password</label>
                <input type="password" id="password" name="password" class="win-input" 
                       placeholder="Enter your password" required>
                <label class="win-check">
                    <input type="checkbox" id="chkShow" onclick="document.getElementById('password').type = this.checked ? 'text' : 'password';">
                    <span>Show password</span>
                </label>
            </div>

            <div class="win-buttons">
                <button type="reset" class="win-btn-cancel" onclick="document.getElementById('email').value=''; document.getElementById('password').value='';">
                    Cancel
                </button>
                <button type="submit" class="win-btn-signin">
                    Sign In
                </button>
            </div>

            <a href="signup.php" class="win-link">Don't have an account? Sign Up</a>
        </form>
    </div>
</body>
</html>
