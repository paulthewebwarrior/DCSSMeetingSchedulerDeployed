<?php
require_once __DIR__ . '/includes/Database.php';
require_once __DIR__ . '/includes/Auth.php';
require_once __DIR__ . '/includes/Flash.php';

$user = Auth::requireRole('Admin');
$db = Database::getConnection();

$totalUsers   = $db->query("SELECT COUNT(*) FROM users")->fetchColumn();
$facultyCount = $db->query("SELECT COUNT(*) FROM users WHERE role = 'Faculty'")->fetchColumn();
$studentCount = $db->query("SELECT COUNT(*) FROM users WHERE role = 'Student'")->fetchColumn();
$totalAppts   = $db->query("SELECT COUNT(*) FROM appointments")->fetchColumn();
$pendingAppts = $db->query("SELECT COUNT(*) FROM appointments WHERE status = 'Pending'")->fetchColumn();

$userSearch = trim($_GET['user_search'] ?? '');
$roleFilter = $_GET['role_filter'] ?? 'All';

$uQuery = "SELECT user_id, ue_email, first_name, last_name, role, id_number, program_or_dept, is_active FROM users WHERE 1=1";
$uParams = [];
if ($roleFilter !== 'All') {
    $uQuery .= " AND role = :role";
    $uParams[':role'] = $roleFilter;
}
if (!empty($userSearch)) {
    $uQuery .= " AND (first_name LIKE :s OR last_name LIKE :s OR ue_email LIKE :s OR id_number LIKE :s)";
    $uParams[':s'] = "%{$userSearch}%";
}
$uQuery .= " ORDER BY role, last_name, first_name";
$stmtUsers = $db->prepare($uQuery);
$stmtUsers->execute($uParams);
$usersList = $stmtUsers->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - DCSS Meeting Scheduler</title>
    <link rel="stylesheet" href="css/app.css">
</head>
<body>
    <header class="win-header-panel">
        <div class="win-brand">
            <img src="assets/dcss.jpg" alt="DCSS Logo" class="win-brand-logo" onerror="this.src='Resources/dcss.jpg'">
            <div>
                <div class="win-title">Admin Dashboard</div>
                <div class="win-subtitle">DCSS Meeting Scheduler System Administration</div>
            </div>
        </div>
        <div class="win-user-info">
            <span class="win-badge"><?= htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) ?> (Admin)</span>
            <a href="logout.php" class="win-btn-logout" onclick="return confirm('Log out from Admin session?');">
                Log Out
            </a>
        </div>
    </header>

    <div class="win-container">
        <div class="win-section-header">
            <h2>Administrative Overview</h2>
            <p>System metrics, user accounts, and appointment tracking</p>
        </div>

        <div class="win-metric-cards">
            <div class="win-card-panel card-total">
                <div class="win-card-label">Total Users</div>
                <div class="win-card-value"><?= (int)$totalUsers ?></div>
            </div>
            <div class="win-card-panel card-approved">
                <div class="win-card-label">Faculty Members</div>
                <div class="win-card-value"><?= (int)$facultyCount ?></div>
            </div>
            <div class="win-card-panel card-total">
                <div class="win-card-label">Registered Students</div>
                <div class="win-card-value"><?= (int)$studentCount ?></div>
            </div>
            <div class="win-card-panel card-pending">
                <div class="win-card-label">Pending Appointments</div>
                <div class="win-card-value"><?= (int)$pendingAppts ?></div>
            </div>
        </div>

        <div class="win-groupbox">
            <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:8px; margin-bottom:12px;">
                <div class="win-groupbox-title" style="margin-bottom:0; border:none; padding:0;">User Management</div>
                <form method="GET" action="admin.php" style="display:flex; gap:6px; flex-wrap:wrap;">
                    <input type="text" name="user_search" class="win-textbox" placeholder="Search name or email..." value="<?= htmlspecialchars($userSearch) ?>" style="width:220px;">
                    <select name="role_filter" class="win-combobox" onchange="this.form.submit()">
                        <option value="All" <?= $roleFilter === 'All' ? 'selected' : '' ?>>All Roles</option>
                        <option value="Admin" <?= $roleFilter === 'Admin' ? 'selected' : '' ?>>Admin</option>
                        <option value="Faculty" <?= $roleFilter === 'Faculty' ? 'selected' : '' ?>>Faculty</option>
                        <option value="Student" <?= $roleFilter === 'Student' ? 'selected' : '' ?>>Student</option>
                    </select>
                    <button type="submit" class="win-btn win-btn-blue">Filter</button>
                    <?php if (!empty($userSearch) || $roleFilter !== 'All'): ?>
                        <a href="admin.php" class="win-btn win-btn-gray">Reset</a>
                    <?php endif; ?>
                </form>
            </div>

            <div class="win-datagrid-wrap">
                <table class="win-datagrid">
                    <thead>
                        <tr>
                            <th>User ID</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>ID Number</th>
                            <th>Dept / Program</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($usersList as $u): ?>
                            <tr>
                                <td>#<?= htmlspecialchars($u['user_id']) ?></td>
                                <td><strong><?= htmlspecialchars($u['first_name'] . ' ' . $u['last_name']) ?></strong></td>
                                <td><?= htmlspecialchars($u['ue_email']) ?></td>
                                <td>
                                    <span class="win-status-badge status-completed">
                                        <?= htmlspecialchars($u['role']) ?>
                                    </span>
                                </td>
                                <td><?= htmlspecialchars($u['id_number'] ?: '-') ?></td>
                                <td><?= htmlspecialchars($u['program_or_dept'] ?: '-') ?></td>
                                <td>
                                    <span class="win-status-badge status-<?= $u['is_active'] ? 'approved' : 'declined' ?>">
                                        <?= $u['is_active'] ? 'Active' : 'Disabled' ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>
