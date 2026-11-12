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

$userSearch = Validation::text($_GET, 'user_search', 'User search', 100, false);
$roleFilter = Validation::choice($_GET, 'role_filter', ['All', 'Admin', 'Faculty', 'Student'], 'All');

$uQuery = "SELECT user_id, ue_email, first_name, last_name, role, id_number, program_or_dept, is_active FROM users WHERE 1=1";
$uParams = [];
if ($roleFilter !== 'All') {
    $uQuery .= " AND role = :role";
    $uParams[':role'] = $roleFilter;
}
if (!empty($userSearch)) {
    $uQuery .= " AND (first_name LIKE :s1 OR last_name LIKE :s2 OR ue_email LIKE :s3 OR id_number LIKE :s4)";
    foreach (range(1, 4) as $i) { $uParams[':s' . $i] = "%{$userSearch}%"; }
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
    <title>DCSS - Admin Portal</title>
    <link rel="stylesheet" href="css/app.css">
</head>
<body>
    <header class="app-header">
        <div class="header-brand">
            <img src="assets/dcss.jpg" alt="DCSS" class="brand-logo" onerror="this.style.display='none'">
            <div>
                <h1 class="brand-title">Admin Dashboard</h1>
                <p class="brand-subtitle">DCSS Meeting Scheduler System Administration</p>
            </div>
        </div>
        <div class="header-user">
            <div class="user-badge">
                <span><?= htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) ?></span>
                <span class="role-tag"><?= htmlspecialchars($user['role']) ?></span>
            </div>
            <form method="POST" action="logout.php" onsubmit="return confirm('Log out from Admin session?');" style="margin:0;">
                <?= App::csrfField() ?>
                <button type="submit" class="btn-logout">Log Out</button>
            </form>
        </div>
    </header>

    <main class="main-wrapper">
        <?= Flash::render() ?>
        <div class="section-banner">
            <h2>Administrative Overview</h2>
            <p>System metrics, user accounts, and appointment tracking</p>
        </div>

        <div class="kpi-grid">
            <div class="kpi-card kpi-total">
                <div class="kpi-title">Total Users</div>
                <div class="kpi-value"><?= (int)$totalUsers ?></div>
            </div>
            <div class="kpi-card kpi-approved">
                <div class="kpi-title">Faculty Members</div>
                <div class="kpi-value"><?= (int)$facultyCount ?></div>
            </div>
            <div class="kpi-card kpi-upcoming">
                <div class="kpi-title">Registered Students</div>
                <div class="kpi-value"><?= (int)$studentCount ?></div>
            </div>
            <div class="kpi-card kpi-today">
                <div class="kpi-title">Total Appointments</div>
                <div class="kpi-value"><?= (int)$totalAppts ?></div>
            </div>
            <div class="kpi-card kpi-pending">
                <div class="kpi-title">Pending Approvals</div>
                <div class="kpi-value"><?= (int)$pendingAppts ?></div>
            </div>
        </div>

        <div class="panel-card">
            <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px; margin-bottom:14px;">
                <h3 class="panel-card-title" style="margin:0; border:none; padding:0;">User Management</h3>
                <form method="GET" action="admin.php" style="display:flex; gap:8px; flex-wrap:wrap;">
                    <input maxlength="100" type="text" name="user_search" class="input-search" placeholder="Search name or email..." value="<?= htmlspecialchars($userSearch) ?>">
                    <select name="role_filter" class="select-filter" onchange="this.form.submit()">
                        <option value="All" <?= $roleFilter === 'All' ? 'selected' : '' ?>>All Roles</option>
                        <option value="Admin" <?= $roleFilter === 'Admin' ? 'selected' : '' ?>>Admin</option>
                        <option value="Faculty" <?= $roleFilter === 'Faculty' ? 'selected' : '' ?>>Faculty</option>
                        <option value="Student" <?= $roleFilter === 'Student' ? 'selected' : '' ?>>Student</option>
                    </select>
                    <button type="submit" class="btn btn-primary">Filter</button>
                    <?php if (!empty($userSearch) || $roleFilter !== 'All'): ?>
                        <a href="admin.php" class="btn btn-secondary">Reset</a>
                    <?php endif; ?>
                </form>
            </div>

            <div class="table-responsive">
                <table class="data-table">
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
                        <?php if (!$usersList): ?>
                            <tr><td colspan="7" style="text-align:center; padding:24px;">No users match your search. Try another name, email, or role.</td></tr>
                        <?php endif; ?>
                        <?php foreach ($usersList as $u): ?>
                            <tr>
                                <td><strong>#<?= htmlspecialchars($u['user_id']) ?></strong></td>
                                <td><?= htmlspecialchars($u['first_name'] . ' ' . $u['last_name']) ?></td>
                                <td><?= htmlspecialchars($u['ue_email']) ?></td>
                                <td>
                                    <span class="badge" style="background:#e0f2fe; color:#0369a1;">
                                        <?= htmlspecialchars($u['role']) ?>
                                    </span>
                                </td>
                                <td><?= htmlspecialchars($u['id_number'] ?: '-') ?></td>
                                <td><?= htmlspecialchars($u['program_or_dept'] ?: '-') ?></td>
                                <td>
                                    <span class="badge badge-<?= $u['is_active'] ? 'approved' : 'declined' ?>">
                                        <?= $u['is_active'] ? 'Active' : 'Disabled' ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
    <?= App::formState() ?>
    <script src="assets/validation.js" defer></script>
</body>
</html>

