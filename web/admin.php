<?php
require_once __DIR__ . '/includes/Database.php';
require_once __DIR__ . '/includes/Auth.php';
require_once __DIR__ . '/includes/Flash.php';

$user = Auth::requireRole('Admin');
$db = Database::getConnection();

// Handle POST actions for User Approvals and Management
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    $returnTab = $_POST['return_tab'] ?? 'approvals';

    if ($action === 'approve_user') {
        $targetId = (int)($_POST['target_user_id'] ?? 0);
        if ($targetId > 0) {
            $stmt = $db->prepare("UPDATE users SET is_active = 1 WHERE user_id = :id");
            $stmt->execute([':id' => $targetId]);
            Flash::set('success', 'User account approved and activated successfully. The user may now log in.');
        }
        header('Location: admin.php?tab=' . urlencode($returnTab));
        exit;
    }

    if ($action === 'reject_user') {
        $targetId = (int)($_POST['target_user_id'] ?? 0);
        if ($targetId > 0 && $targetId !== (int)$user['user_id']) {
            $stmt = $db->prepare("DELETE FROM users WHERE user_id = :id AND is_active = 0");
            $stmt->execute([':id' => $targetId]);
            Flash::set('success', 'Registration was rejected and the unapproved account was removed.');
        }
        header('Location: admin.php?tab=' . urlencode($returnTab));
        exit;
    }

    if ($action === 'toggle_active') {
        $targetId = (int)($_POST['target_user_id'] ?? 0);
        $newStatus = (int)($_POST['new_status'] ?? 0);
        if ($targetId > 0 && $targetId !== (int)$user['user_id']) {
            $stmt = $db->prepare("UPDATE users SET is_active = :status WHERE user_id = :id");
            $stmt->execute([':status' => $newStatus, ':id' => $targetId]);
            Flash::set('success', $newStatus ? 'User account has been activated.' : 'User account has been deactivated.');
        }
        header('Location: admin.php?tab=' . urlencode($returnTab));
        exit;
    }

    if ($action === 'delete_user') {
        $targetId = (int)($_POST['target_user_id'] ?? 0);
        if ($targetId > 0 && $targetId !== (int)$user['user_id']) {
            $stmt = $db->prepare("DELETE FROM users WHERE user_id = :id");
            $stmt->execute([':id' => $targetId]);
            Flash::set('success', 'User account deleted successfully.');
        }
        header('Location: admin.php?tab=' . urlencode($returnTab));
        exit;
    }
}

// Current Navigation Tab
$currentTab = $_GET['tab'] ?? 'dashboard';
if (!in_array($currentTab, ['dashboard', 'approvals', 'users', 'appointments'], true)) {
    $currentTab = 'dashboard';
}

// Metrics
$totalUsers            = $db->query("SELECT COUNT(*) FROM users")->fetchColumn();
$facultyCount          = $db->query("SELECT COUNT(*) FROM users WHERE role = 'Faculty' AND is_active = 1")->fetchColumn();
$studentCount          = $db->query("SELECT COUNT(*) FROM users WHERE role = 'Student' AND is_active = 1")->fetchColumn();
$pendingApprovalsCount = $db->query("SELECT COUNT(*) FROM users WHERE is_active = 0")->fetchColumn();
$totalAppts            = $db->query("SELECT COUNT(*) FROM appointments")->fetchColumn();
$pendingAppts          = $db->query("SELECT COUNT(*) FROM appointments WHERE status = 'Pending'")->fetchColumn();

// Data for Approvals Tab
$approvalSearch = trim($_GET['approval_search'] ?? '');
$apprQuery = "SELECT user_id, ue_email, first_name, last_name, role, id_number, program_or_dept, is_active, created_at FROM users WHERE is_active = 0";
$apprParams = [];
if (!empty($approvalSearch)) {
    $apprQuery .= " AND (first_name LIKE :as OR last_name LIKE :as OR ue_email LIKE :as OR id_number LIKE :as)";
    $apprParams[':as'] = "%{$approvalSearch}%";
}
$apprQuery .= " ORDER BY created_at DESC, last_name, first_name";
$stmtPending = $db->prepare($apprQuery);
$stmtPending->execute($apprParams);
$pendingUsersList = $stmtPending->fetchAll();

// Data for All Users Tab
$userSearch = trim($_GET['user_search'] ?? '');
$roleFilter = $_GET['role_filter'] ?? 'All';
$statusFilter = $_GET['status_filter'] ?? 'All';

$uQuery = "SELECT user_id, ue_email, first_name, last_name, role, id_number, program_or_dept, is_active, created_at FROM users WHERE 1=1";
$uParams = [];
if ($roleFilter !== 'All') {
    $uQuery .= " AND role = :role";
    $uParams[':role'] = $roleFilter;
}
if ($statusFilter === 'Active') {
    $uQuery .= " AND is_active = 1";
} elseif ($statusFilter === 'Pending') {
    $uQuery .= " AND is_active = 0";
}
if (!empty($userSearch)) {
    $uQuery .= " AND (first_name LIKE :s OR last_name LIKE :s OR ue_email LIKE :s OR id_number LIKE :s)";
    $uParams[':s'] = "%{$userSearch}%";
}
$uQuery .= " ORDER BY role, last_name, first_name";
$stmtUsers = $db->prepare($uQuery);
$stmtUsers->execute($uParams);
$usersList = $stmtUsers->fetchAll();

// Data for Appointments Tab
$apptSearch = trim($_GET['appt_search'] ?? '');
$apptStatus = $_GET['appt_status'] ?? 'All';

$aQuery = "
    SELECT a.appointment_id,
           CONCAT(s.first_name, ' ', s.last_name) AS student_name,
           s.ue_email AS student_email,
           CONCAT(f.first_name, ' ', f.last_name) AS faculty_name,
           f.ue_email AS faculty_email,
           DATE_FORMAT(a.meeting_datetime, '%b %d, %Y %h:%i %p') AS meeting_time_fmt,
           a.purpose,
           a.status,
           IFNULL(a.faculty_remarks, '') AS faculty_remarks,
           DATE_FORMAT(a.created_at, '%b %d, %Y') AS created_fmt
    FROM appointments a
    INNER JOIN users s ON a.student_id = s.user_id
    INNER JOIN users f ON a.faculty_id = f.user_id
    WHERE 1=1
";
$aParams = [];
if ($apptStatus !== 'All') {
    $aQuery .= " AND a.status = :astatus";
    $aParams[':astatus'] = $apptStatus;
}
if (!empty($apptSearch)) {
    $aQuery .= " AND (s.first_name LIKE :aps OR s.last_name LIKE :aps OR f.first_name LIKE :aps OR f.last_name LIKE :aps OR a.purpose LIKE :aps)";
    $aParams[':aps'] = "%{$apptSearch}%";
}
$aQuery .= " ORDER BY a.meeting_datetime DESC";
$stmtAppts = $db->prepare($aQuery);
$stmtAppts->execute($aParams);
$appointmentsList = $stmtAppts->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DCSS - Admin Portal</title>
    <link rel="stylesheet" href="css/app.css">
    <style>
        .badge-faculty { background: #e0f2fe; color: #0369a1; }
        .badge-student { background: #ede9fe; color: #6d28d9; }
        .badge-admin   { background: #fef3c7; color: #92400e; }
        .btn-sm { padding: 6px 12px; font-size: 13px; font-weight: 600; border-radius: 4px; border: none; cursor: pointer; text-decoration: none; display: inline-flex; align-items: center; gap: 4px; }
        .btn-approve { background: #16a34a; color: #ffffff; }
        .btn-approve:hover { background: #15803d; }
        .btn-reject { background: #dc2626; color: #ffffff; }
        .btn-reject:hover { background: #b91c1c; }
        .btn-action-outline { background: #ffffff; border: 1px solid #d1d5db; color: #374151; }
        .btn-action-outline:hover { background: #f3f4f6; }
        .empty-placeholder { text-align: center; padding: 48px 16px; color: var(--text-secondary); }
        .empty-placeholder-icon { font-size: 44px; margin-bottom: 12px; }
        .notice-card { border-radius: 8px; padding: 14px 18px; margin-bottom: 20px; display: flex; align-items: center; justify-content: space-between; gap: 12px; }
    </style>
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
            <a href="logout.php" class="btn-logout" onclick="return confirm('Log out from Admin session?');">
                Log Out
            </a>
        </div>
    </header>

    <main class="main-wrapper">
        <?= Flash::render() ?>

        <nav class="tab-navigation">
            <a href="?tab=dashboard" class="tab-btn <?= $currentTab === 'dashboard' ? 'active' : '' ?>">Overview</a>
            <a href="?tab=approvals" class="tab-btn <?= $currentTab === 'approvals' ? 'active' : '' ?>">
                User Approvals
                <?php if ($pendingApprovalsCount > 0): ?>
                    <span class="badge badge-pending" style="margin-left:6px; font-size:11px; padding:2px 8px; border-radius:10px;">
                        <?= (int)$pendingApprovalsCount ?>
                    </span>
                <?php endif; ?>
            </a>
            <a href="?tab=users" class="tab-btn <?= $currentTab === 'users' ? 'active' : '' ?>">All Users</a>
            <a href="?tab=appointments" class="tab-btn <?= $currentTab === 'appointments' ? 'active' : '' ?>">Appointments</a>
        </nav>

        <div class="tab-content">
            <?php if ($currentTab === 'dashboard'): ?>
                <!-- ==================== TAB: DASHBOARD ==================== -->
                <div class="section-banner">
                    <h2>Administrative Overview</h2>
                    <p>System metrics, user registrations, and appointment tracking</p>
                </div>

                <?php if ($pendingApprovalsCount > 0): ?>
                    <div class="notice-card" style="background:#fffbeb; border:1px solid #fde68a; color:#92400e;">
                        <div style="display:flex; align-items:center; gap:12px;">
                            <span style="font-size:24px;">⏳</span>
                            <div>
                                <strong style="font-size:15px;">Pending User Approvals (<?= (int)$pendingApprovalsCount ?>)</strong>
                                <div style="font-size:13px; margin-top:2px;">There are faculty accounts waiting for administrator verification before they can access the portal.</div>
                            </div>
                        </div>
                        <a href="?tab=approvals" class="btn btn-primary" style="white-space:nowrap; padding:8px 16px; font-size:13px;">
                            Review Approvals &rarr;
                        </a>
                    </div>
                <?php endif; ?>

                <div class="kpi-grid">
                    <a href="?tab=users" class="kpi-card kpi-total" style="text-decoration:none; color:inherit;">
                        <div class="kpi-title">Total Users</div>
                        <div class="kpi-value"><?= (int)$totalUsers ?></div>
                    </a>
                    <a href="?tab=approvals" class="kpi-card kpi-pending" style="text-decoration:none; color:inherit; <?= $pendingApprovalsCount > 0 ? 'box-shadow: 0 0 0 2px #f59e0b;' : '' ?>">
                        <div class="kpi-title">Pending Approvals</div>
                        <div class="kpi-value"><?= (int)$pendingApprovalsCount ?></div>
                    </a>
                    <a href="?tab=users&role_filter=Faculty" class="kpi-card kpi-approved" style="text-decoration:none; color:inherit;">
                        <div class="kpi-title">Active Faculty</div>
                        <div class="kpi-value"><?= (int)$facultyCount ?></div>
                    </a>
                    <a href="?tab=users&role_filter=Student" class="kpi-card kpi-upcoming" style="text-decoration:none; color:inherit;">
                        <div class="kpi-title">Active Students</div>
                        <div class="kpi-value"><?= (int)$studentCount ?></div>
                    </a>
                    <a href="?tab=appointments" class="kpi-card kpi-today" style="text-decoration:none; color:inherit;">
                        <div class="kpi-title">Total Appointments</div>
                        <div class="kpi-value"><?= (int)$totalAppts ?></div>
                    </a>
                    <a href="?tab=appointments&appt_status=Pending" class="kpi-card kpi-pending" style="text-decoration:none; color:inherit;">
                        <div class="kpi-title">Pending Appointments</div>
                        <div class="kpi-value"><?= (int)$pendingAppts ?></div>
                    </a>
                </div>

                <!-- Recent Registrations Awaiting Verification -->
                <div class="panel-card" style="margin-top:24px;">
                    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:14px;">
                        <h3 class="panel-card-title" style="margin:0; border:none; padding:0;">
                            Registrations Awaiting Verification
                        </h3>
                        <?php if ($pendingApprovalsCount > 0): ?>
                            <a href="?tab=approvals" class="btn btn-secondary" style="font-size:12px; padding:6px 12px;">View All (<?= (int)$pendingApprovalsCount ?>)</a>
                        <?php endif; ?>
                    </div>

                    <?php if (empty($pendingUsersList)): ?>
                        <div class="empty-placeholder">
                            <div class="empty-placeholder-icon">✓</div>
                            <h4 style="margin-bottom:4px; color:var(--dcss-forest);">All User Registrations Are Up to Date</h4>
                            <p style="font-size:13px; margin:0;">There are no unapproved faculty or student accounts at this moment.</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Email</th>
                                        <th>Role</th>
                                        <th>ID Number</th>
                                        <th>Dept / Program</th>
                                        <th>Submitted</th>
                                        <th style="text-align:right;">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach (array_slice($pendingUsersList, 0, 5) as $pu): ?>
                                        <tr>
                                            <td><strong><?= htmlspecialchars($pu['first_name'] . ' ' . $pu['last_name']) ?></strong></td>
                                            <td><?= htmlspecialchars($pu['ue_email']) ?></td>
                                            <td>
                                                <span class="badge badge-<?= strtolower($pu['role']) ?>">
                                                    <?= htmlspecialchars($pu['role']) ?>
                                                </span>
                                            </td>
                                            <td><?= htmlspecialchars($pu['id_number'] ?: '—') ?></td>
                                            <td><?= htmlspecialchars($pu['program_or_dept'] ?: '—') ?></td>
                                            <td><?= !empty($pu['created_at']) ? date('M d, Y h:i A', strtotime($pu['created_at'])) : '—' ?></td>
                                            <td style="text-align:right;">
                                                <form method="POST" action="admin.php" style="display:inline;" onsubmit="return confirm('Approve account for <?= htmlspecialchars(addslashes($pu['first_name'] . ' ' . $pu['last_name'])) ?>?');">
                                                    <input type="hidden" name="action" value="approve_user">
                                                    <input type="hidden" name="target_user_id" value="<?= $pu['user_id'] ?>">
                                                    <input type="hidden" name="return_tab" value="dashboard">
                                                    <button type="submit" class="btn-sm btn-approve" title="Approve registration">
                                                        ✓ Approve
                                                    </button>
                                                </form>
                                                <form method="POST" action="admin.php" style="display:inline; margin-left:4px;" onsubmit="return confirm('Reject registration for <?= htmlspecialchars(addslashes($pu['first_name'] . ' ' . $pu['last_name'])) ?>?');">
                                                    <input type="hidden" name="action" value="reject_user">
                                                    <input type="hidden" name="target_user_id" value="<?= $pu['user_id'] ?>">
                                                    <input type="hidden" name="return_tab" value="dashboard">
                                                    <button type="submit" class="btn-sm btn-reject" title="Reject registration">
                                                        ✕ Reject
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>

            <?php elseif ($currentTab === 'approvals'): ?>
                <!-- ==================== TAB: USER APPROVALS ==================== -->
                <div class="section-banner">
                    <h2>User Registration Approvals</h2>
                    <p>Review and verify faculty registrations. Students are activated immediately, while faculty require administrative verification before logging in.</p>
                </div>

                <div class="panel-card">
                    <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px; margin-bottom:16px;">
                        <div>
                            <h3 class="panel-card-title" style="margin:0; border:none; padding:0;">
                                Pending Registrations (<?= count($pendingUsersList) ?>)
                            </h3>
                            <p style="font-size:13px; color:var(--text-secondary); margin:4px 0 0 0;">
                                Accounts listed here cannot log in until approved by an administrator.
                            </p>
                        </div>
                        <form method="GET" action="admin.php" style="display:flex; gap:8px;">
                            <input type="hidden" name="tab" value="approvals">
                            <input type="text" name="approval_search" class="input-search" placeholder="Search by name, email, or ID..." value="<?= htmlspecialchars($approvalSearch) ?>">
                            <button type="submit" class="btn btn-primary">Search</button>
                            <?php if (!empty($approvalSearch)): ?>
                                <a href="admin.php?tab=approvals" class="btn btn-secondary">Clear</a>
                            <?php endif; ?>
                        </form>
                    </div>

                    <?php if (empty($pendingUsersList)): ?>
                        <div class="empty-placeholder">
                            <div class="empty-placeholder-icon">✅</div>
                            <h4 style="margin-bottom:6px; color:var(--dcss-forest);">No Pending Approvals</h4>
                            <p style="font-size:14px; max-width:500px; margin:0 auto;">
                                There are no accounts waiting for approval. When a faculty member signs up with their <span style="font-weight:600;">@ue.edu.ph</span> email, their registration will appear here for verification.
                            </p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>User ID</th>
                                        <th>Full Name</th>
                                        <th>UE Email</th>
                                        <th>Role</th>
                                        <th>ID Number</th>
                                        <th>Dept / Program</th>
                                        <th>Date Submitted</th>
                                        <th>Status</th>
                                        <th style="text-align:right;">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($pendingUsersList as $pu): ?>
                                        <tr>
                                            <td><strong>#<?= htmlspecialchars($pu['user_id']) ?></strong></td>
                                            <td>
                                                <div style="font-weight:600; color:var(--dcss-forest);">
                                                    <?= htmlspecialchars($pu['first_name'] . ' ' . $pu['last_name']) ?>
                                                </div>
                                            </td>
                                            <td><?= htmlspecialchars($pu['ue_email']) ?></td>
                                            <td>
                                                <span class="badge badge-<?= strtolower($pu['role']) ?>">
                                                    <?= htmlspecialchars($pu['role']) ?>
                                                </span>
                                            </td>
                                            <td><?= htmlspecialchars($pu['id_number'] ?: '—') ?></td>
                                            <td><?= htmlspecialchars($pu['program_or_dept'] ?: '—') ?></td>
                                            <td>
                                                <?= !empty($pu['created_at']) ? date('M d, Y h:i A', strtotime($pu['created_at'])) : '—' ?>
                                            </td>
                                            <td>
                                                <span class="badge badge-pending">Waiting Approval</span>
                                            </td>
                                            <td style="text-align:right; white-space:nowrap;">
                                                <form method="POST" action="admin.php" style="display:inline;" onsubmit="return confirm('Approve account for <?= htmlspecialchars(addslashes($pu['first_name'] . ' ' . $pu['last_name'])) ?>?');">
                                                    <input type="hidden" name="action" value="approve_user">
                                                    <input type="hidden" name="target_user_id" value="<?= $pu['user_id'] ?>">
                                                    <input type="hidden" name="return_tab" value="approvals">
                                                    <button type="submit" class="btn-sm btn-approve" title="Approve this account">
                                                        ✓ Approve
                                                    </button>
                                                </form>
                                                <form method="POST" action="admin.php" style="display:inline; margin-left:4px;" onsubmit="return confirm('Reject and remove registration for <?= htmlspecialchars(addslashes($pu['first_name'] . ' ' . $pu['last_name'])) ?>?');">
                                                    <input type="hidden" name="action" value="reject_user">
                                                    <input type="hidden" name="target_user_id" value="<?= $pu['user_id'] ?>">
                                                    <input type="hidden" name="return_tab" value="approvals">
                                                    <button type="submit" class="btn-sm btn-reject" title="Reject registration">
                                                        ✕ Reject
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>

            <?php elseif ($currentTab === 'users'): ?>
                <!-- ==================== TAB: ALL USERS ==================== -->
                <div class="section-banner">
                    <h2>User Account Management</h2>
                    <p>View, search, and manage all student, faculty, and administrator accounts</p>
                </div>

                <div class="panel-card">
                    <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px; margin-bottom:16px;">
                        <h3 class="panel-card-title" style="margin:0; border:none; padding:0;">
                            All Accounts (<?= count($usersList) ?>)
                        </h3>
                        <form method="GET" action="admin.php" style="display:flex; gap:8px; flex-wrap:wrap;">
                            <input type="hidden" name="tab" value="users">
                            <input type="text" name="user_search" class="input-search" placeholder="Search name or email..." value="<?= htmlspecialchars($userSearch) ?>">
                            <select name="role_filter" class="select-filter" onchange="this.form.submit()">
                                <option value="All" <?= $roleFilter === 'All' ? 'selected' : '' ?>>All Roles</option>
                                <option value="Admin" <?= $roleFilter === 'Admin' ? 'selected' : '' ?>>Admin</option>
                                <option value="Faculty" <?= $roleFilter === 'Faculty' ? 'selected' : '' ?>>Faculty</option>
                                <option value="Student" <?= $roleFilter === 'Student' ? 'selected' : '' ?>>Student</option>
                            </select>
                            <select name="status_filter" class="select-filter" onchange="this.form.submit()">
                                <option value="All" <?= $statusFilter === 'All' ? 'selected' : '' ?>>All Statuses</option>
                                <option value="Active" <?= $statusFilter === 'Active' ? 'selected' : '' ?>>Active Only</option>
                                <option value="Pending" <?= $statusFilter === 'Pending' ? 'selected' : '' ?>>Pending / Inactive</option>
                            </select>
                            <button type="submit" class="btn btn-primary">Filter</button>
                            <?php if (!empty($userSearch) || $roleFilter !== 'All' || $statusFilter !== 'All'): ?>
                                <a href="admin.php?tab=users" class="btn btn-secondary">Reset</a>
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
                                    <th style="text-align:right;">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($usersList)): ?>
                                    <tr>
                                        <td colspan="8" style="text-align:center; padding:32px; color:var(--text-secondary);">
                                            No user accounts match your search filters.
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($usersList as $u): ?>
                                        <tr>
                                            <td><strong>#<?= htmlspecialchars($u['user_id']) ?></strong></td>
                                            <td><?= htmlspecialchars($u['first_name'] . ' ' . $u['last_name']) ?></td>
                                            <td><?= htmlspecialchars($u['ue_email']) ?></td>
                                            <td>
                                                <span class="badge badge-<?= strtolower($u['role']) ?>">
                                                    <?= htmlspecialchars($u['role']) ?>
                                                </span>
                                            </td>
                                            <td><?= htmlspecialchars($u['id_number'] ?: '—') ?></td>
                                            <td><?= htmlspecialchars($u['program_or_dept'] ?: '—') ?></td>
                                            <td>
                                                <?php if ($u['is_active']): ?>
                                                    <span class="badge badge-approved">Active</span>
                                                <?php else: ?>
                                                    <span class="badge badge-pending">Pending / Disabled</span>
                                                <?php endif; ?>
                                            </td>
                                            <td style="text-align:right; white-space:nowrap;">
                                                <?php if ((int)$u['user_id'] !== (int)$user['user_id']): ?>
                                                    <form method="POST" action="admin.php" style="display:inline;">
                                                        <input type="hidden" name="action" value="toggle_active">
                                                        <input type="hidden" name="target_user_id" value="<?= $u['user_id'] ?>">
                                                        <input type="hidden" name="new_status" value="<?= $u['is_active'] ? '0' : '1' ?>">
                                                        <input type="hidden" name="return_tab" value="users">
                                                        <button type="submit" class="btn-sm btn-action-outline" title="<?= $u['is_active'] ? 'Deactivate account' : 'Activate account' ?>">
                                                            <?= $u['is_active'] ? 'Deactivate' : 'Activate' ?>
                                                        </button>
                                                    </form>
                                                    <form method="POST" action="admin.php" style="display:inline; margin-left:4px;" onsubmit="return confirm('Delete account for <?= htmlspecialchars(addslashes($u['first_name'] . ' ' . $u['last_name'])) ?>? This cannot be undone.');">
                                                        <input type="hidden" name="action" value="delete_user">
                                                        <input type="hidden" name="target_user_id" value="<?= $u['user_id'] ?>">
                                                        <input type="hidden" name="return_tab" value="users">
                                                        <button type="submit" class="btn-sm btn-reject" title="Delete account">
                                                            Delete
                                                        </button>
                                                    </form>
                                                <?php else: ?>
                                                    <span style="font-size:12px; color:var(--text-secondary); font-style:italic;">(Current Admin)</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

            <?php elseif ($currentTab === 'appointments'): ?>
                <!-- ==================== TAB: APPOINTMENTS ==================== -->
                <div class="section-banner">
                    <h2>Consultation Appointments</h2>
                    <p>Track all student-to-faculty consultation requests and their resolution status</p>
                </div>

                <div class="panel-card">
                    <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px; margin-bottom:16px;">
                        <h3 class="panel-card-title" style="margin:0; border:none; padding:0;">
                            Appointments List (<?= count($appointmentsList) ?>)
                        </h3>
                        <form method="GET" action="admin.php" style="display:flex; gap:8px; flex-wrap:wrap;">
                            <input type="hidden" name="tab" value="appointments">
                            <input type="text" name="appt_search" class="input-search" placeholder="Search student, faculty, purpose..." value="<?= htmlspecialchars($apptSearch) ?>">
                            <select name="appt_status" class="select-filter" onchange="this.form.submit()">
                                <option value="All" <?= $apptStatus === 'All' ? 'selected' : '' ?>>All Statuses</option>
                                <option value="Pending" <?= $apptStatus === 'Pending' ? 'selected' : '' ?>>Pending</option>
                                <option value="Approved" <?= $apptStatus === 'Approved' ? 'selected' : '' ?>>Approved</option>
                                <option value="Declined" <?= $apptStatus === 'Declined' ? 'selected' : '' ?>>Declined</option>
                                <option value="Completed" <?= $apptStatus === 'Completed' ? 'selected' : '' ?>>Completed</option>
                                <option value="Cancelled" <?= $apptStatus === 'Cancelled' ? 'selected' : '' ?>>Cancelled</option>
                            </select>
                            <button type="submit" class="btn btn-primary">Filter</button>
                            <?php if (!empty($apptSearch) || $apptStatus !== 'All'): ?>
                                <a href="admin.php?tab=appointments" class="btn btn-secondary">Reset</a>
                            <?php endif; ?>
                        </form>
                    </div>

                    <div class="table-responsive">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Student</th>
                                    <th>Faculty</th>
                                    <th>Date & Time</th>
                                    <th>Purpose</th>
                                    <th>Status</th>
                                    <th>Faculty Remarks</th>
                                    <th>Created</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($appointmentsList)): ?>
                                    <tr>
                                        <td colspan="8" style="text-align:center; padding:32px; color:var(--text-secondary);">
                                            No appointments found matching your search.
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($appointmentsList as $apt): ?>
                                        <tr>
                                            <td><strong>#<?= htmlspecialchars($apt['appointment_id']) ?></strong></td>
                                            <td>
                                                <div><strong><?= htmlspecialchars($apt['student_name']) ?></strong></div>
                                                <div style="font-size:12px; color:var(--text-secondary);"><?= htmlspecialchars($apt['student_email']) ?></div>
                                            </td>
                                            <td>
                                                <div><strong><?= htmlspecialchars($apt['faculty_name']) ?></strong></div>
                                                <div style="font-size:12px; color:var(--text-secondary);"><?= htmlspecialchars($apt['faculty_email']) ?></div>
                                            </td>
                                            <td><?= htmlspecialchars($apt['meeting_time_fmt']) ?></td>
                                            <td style="max-width:220px;"><?= htmlspecialchars($apt['purpose']) ?></td>
                                            <td>
                                                <span class="badge badge-<?= strtolower($apt['status']) ?>">
                                                    <?= htmlspecialchars($apt['status']) ?>
                                                </span>
                                            </td>
                                            <td style="max-width:200px;"><?= htmlspecialchars($apt['faculty_remarks'] ?: '—') ?></td>
                                            <td><?= htmlspecialchars($apt['created_fmt']) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </main>
</body>
</html>
