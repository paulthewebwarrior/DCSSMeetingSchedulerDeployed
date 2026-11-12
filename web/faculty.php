<?php
require_once __DIR__ . '/includes/Database.php';
require_once __DIR__ . '/includes/Auth.php';
require_once __DIR__ . '/includes/Flash.php';
require_once __DIR__ . '/includes/Actions.php';

$user = Auth::requireRole('Faculty');
$db = Database::getConnection();
$currentTab = Validation::choice($_GET, 'tab', ['home', 'request', 'availability', 'setting'], 'home');
Actions::handle($db, $user, 'faculty', $currentTab);

$stmtStats = $db->prepare("
    SELECT 
        COUNT(*) AS total_count,
        COUNT(CASE WHEN status = 'Pending' THEN 1 END) AS pending_count,
        COUNT(CASE WHEN status = 'Approved' THEN 1 END) AS approved_count,
        COUNT(CASE WHEN status = 'Declined' THEN 1 END) AS declined_count,
        COUNT(CASE WHEN status = 'Approved' AND DATE(meeting_datetime) = CURDATE() THEN 1 END) AS today_count,
        COUNT(CASE WHEN status = 'Approved' AND meeting_datetime >= NOW() THEN 1 END) AS upcoming_count
    FROM appointments 
    WHERE faculty_id = :fid
");
$stmtStats->execute([':fid' => $user['user_id']]);
$stats = $stmtStats->fetch() ?: [
    'total_count'    => 0,
    'pending_count'  => 0,
    'approved_count' => 0,
    'declined_count' => 0,
    'today_count'    => 0,
    'upcoming_count' => 0,
];

$stmtRecent = $db->prepare("
    SELECT a.appointment_id,
           CONCAT(u.first_name, ' ', u.last_name) AS student_name,
           DATE_FORMAT(a.meeting_datetime, '%b %d, %Y') AS meeting_date,
           DATE_FORMAT(a.meeting_datetime, '%h:%i %p') AS start_time,
           IFNULL(DATE_FORMAT(a.meeting_end_datetime, '%h:%i %p'), 'N/A') AS end_time,
           a.status,
           IFNULL(a.faculty_remarks, '') AS faculty_remarks
    FROM appointments a
    INNER JOIN users u ON a.student_id = u.user_id
    WHERE a.faculty_id = :fid
    ORDER BY a.meeting_datetime DESC
    LIMIT 10
");
$stmtRecent->execute([':fid' => $user['user_id']]);
$recentMeetings = $stmtRecent->fetchAll();

$searchKeyword = Validation::text($_GET, 'search', 'Search', 100, false);
$statusFilter = Validation::choice($_GET, 'status_filter', ['All', 'Pending', 'Approved', 'Declined', 'Completed', 'Cancelled'], 'All');

$reqQuery = "
    SELECT a.appointment_id,
           CONCAT(u.first_name, ' ', u.last_name) AS student_name,
           IFNULL(u.program_or_dept, 'N/A') AS program_or_dept,
           a.purpose,
           DATE_FORMAT(a.meeting_datetime, '%b %d, %Y %h:%i %p') AS request_datetime,
           a.status,
           IFNULL(a.faculty_remarks, '') AS faculty_remarks
    FROM appointments a
    INNER JOIN users u ON a.student_id = u.user_id
    WHERE a.faculty_id = :fid
";
$params = [':fid' => $user['user_id']];

if (!empty($statusFilter) && $statusFilter !== 'All') {
    $reqQuery .= " AND a.status = :status";
    $params[':status'] = $statusFilter;
}
if (!empty($searchKeyword)) {
    $reqQuery .= " AND (u.first_name LIKE :s1 OR u.last_name LIKE :s2 OR CONCAT(u.first_name, ' ', u.last_name) LIKE :s3 OR u.program_or_dept LIKE :s4 OR a.purpose LIKE :s5)";
    foreach (range(1, 5) as $i) { $params[':s' . $i] = "%{$searchKeyword}%"; }
}
$reqQuery .= " ORDER BY a.meeting_datetime DESC";

$stmtReq = $db->prepare($reqQuery);
$stmtReq->execute($params);
$requestsList = $stmtReq->fetchAll();

$availSearch = Validation::text($_GET, 'avail_search', 'Availability search', 100, false);
$availQuery = "
    SELECT availability_id,
           days,
           TIME_FORMAT(start_time, '%h:%i %p') AS start_fmt,
           TIME_FORMAT(end_time, '%h:%i %p') AS end_fmt,
           start_time,
           end_time,
           location
    FROM faculty_availability
    WHERE faculty_id = :fid
";
$availParams = [':fid' => $user['user_id']];
if (!empty($availSearch)) {
    $availQuery .= " AND (days LIKE :as1 OR location LIKE :as2)";
    $availParams[':as1'] = $availParams[':as2'] = "%{$availSearch}%";
}
$availQuery .= " ORDER BY FIELD(days, 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'), start_time";
$stmtAvail = $db->prepare($availQuery);
$stmtAvail->execute($availParams);
$availabilityList = $stmtAvail->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DCSS - Faculty Portal</title>
    <link rel="stylesheet" href="css/app.css">
</head>
<body>
    <header class="app-header">
        <div class="header-brand">
            <img src="assets/dcss.jpg" alt="DCSS" class="brand-logo" onerror="this.style.display='none'">
            <div>
                <h1 class="brand-title">Faculty Portal</h1>
                <p class="brand-subtitle">Faculty Request Management System</p>
            </div>
        </div>
        <div class="header-user">
            <div class="user-badge">
                <span><?= htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) ?></span>
                <span class="role-tag"><?= htmlspecialchars($user['role']) ?></span>
            </div>
            <form method="POST" action="logout.php" onsubmit="return confirm('Are you sure you want to log out?');" style="margin:0;">
                <?= App::csrfField() ?>
                <button type="submit" class="btn-logout">Log Out</button>
            </form>
        </div>
    </header>

    <main class="main-wrapper">
        <?= Flash::render() ?>

        <nav class="tab-navigation">
            <a href="?tab=home" class="tab-btn <?= $currentTab === 'home' ? 'active' : '' ?>">Home</a>
            <a href="?tab=request" class="tab-btn <?= $currentTab === 'request' ? 'active' : '' ?>">Request</a>
            <a href="?tab=availability" class="tab-btn <?= $currentTab === 'availability' ? 'active' : '' ?>">Availability</a>
            <a href="?tab=setting" class="tab-btn <?= $currentTab === 'setting' ? 'active' : '' ?>">Setting</a>
        </nav>

        <div class="tab-content">
            <?php if ($currentTab === 'home'): ?>
                <div class="section-banner">
                    <h2>Faculty Dashboard</h2>
                    <p>Overview of student consultation requests and upcoming schedules</p>
                </div>

                <div class="kpi-grid">
                    <div class="kpi-card kpi-total">
                        <div class="kpi-title">Total Requests</div>
                        <div class="kpi-value"><?= (int)$stats['total_count'] ?></div>
                    </div>
                    <div class="kpi-card kpi-pending">
                        <div class="kpi-title">Pending</div>
                        <div class="kpi-value"><?= (int)$stats['pending_count'] ?></div>
                    </div>
                    <div class="kpi-card kpi-approved">
                        <div class="kpi-title">Approved</div>
                        <div class="kpi-value"><?= (int)$stats['approved_count'] ?></div>
                    </div>
                    <div class="kpi-card kpi-declined">
                        <div class="kpi-title">Declined</div>
                        <div class="kpi-value"><?= (int)$stats['declined_count'] ?></div>
                    </div>
                    <div class="kpi-card kpi-today">
                        <div class="kpi-title">Today's Meetings</div>
                        <div class="kpi-value"><?= (int)$stats['today_count'] ?></div>
                    </div>
                    <div class="kpi-card kpi-upcoming">
                        <div class="kpi-title">Upcoming Meetings</div>
                        <div class="kpi-value"><?= (int)$stats['upcoming_count'] ?></div>
                    </div>
                </div>

                <div class="notification-box">
                    <div class="notification-message">
                        <?php if ($stats['pending_count'] > 0): ?>
                            🔔 You have <?= (int)$stats['pending_count'] ?> pending meeting request(s) awaiting your review.
                        <?php else: ?>
                            ✓ You have no pending meeting requests.
                        <?php endif; ?>
                    </div>
                    <a href="?tab=home" class="btn btn-primary" style="padding:6px 14px; font-size:13px;">Refresh</a>
                </div>

                <div class="panel-card">
                    <h3 class="panel-card-title">Recent Meetings Request</h3>
                    <div class="table-responsive">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Request ID</th>
                                    <th>Student Name</th>
                                    <th>Date</th>
                                    <th>Start</th>
                                    <th>End</th>
                                    <th>Status</th>
                                    <th>Faculty Remarks</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($recentMeetings)): ?>
                                    <tr>
                                        <td colspan="7" style="text-align:center; padding:24px; color:var(--text-muted);">
                                            No recent meeting requests found.
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($recentMeetings as $m): ?>
                                        <tr>
                                            <td><strong>#<?= htmlspecialchars($m['appointment_id']) ?></strong></td>
                                            <td><?= htmlspecialchars($m['student_name']) ?></td>
                                            <td><?= htmlspecialchars($m['meeting_date']) ?></td>
                                            <td><?= htmlspecialchars($m['start_time']) ?></td>
                                            <td><?= htmlspecialchars($m['end_time']) ?></td>
                                            <td>
                                                <span class="badge badge-<?= strtolower(htmlspecialchars($m['status'])) ?>">
                                                    <?= htmlspecialchars($m['status']) ?>
                                                </span>
                                            </td>
                                            <td><?= htmlspecialchars($m['faculty_remarks'] ?: '-') ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

            <?php elseif ($currentTab === 'request'): ?>
                <div class="section-banner">
                    <h2>Student Requests</h2>
                    <p>Review, approve, decline, or complete consultation appointments</p>
                </div>

                <div class="kpi-grid">
                    <div class="kpi-card kpi-total">
                        <div class="kpi-title">Total Requests</div>
                        <div class="kpi-value"><?= (int)$stats['total_count'] ?></div>
                    </div>
                    <div class="kpi-card kpi-pending">
                        <div class="kpi-title">Pending Requests</div>
                        <div class="kpi-value"><?= (int)$stats['pending_count'] ?></div>
                    </div>
                    <div class="kpi-card kpi-approved">
                        <div class="kpi-title">Approved Requests</div>
                        <div class="kpi-value"><?= (int)$stats['approved_count'] ?></div>
                    </div>
                    <div class="kpi-card kpi-declined">
                        <div class="kpi-title">Declined Requests</div>
                        <div class="kpi-value"><?= (int)$stats['declined_count'] ?></div>
                    </div>
                </div>

                <form method="GET" action="faculty.php" class="toolbar-row">
                    <input type="hidden" name="tab" value="request">
                    <div class="filter-group">
                        <input maxlength="100" type="text" name="search" class="input-search" 
                               placeholder="Search student, program, or purpose..." 
                               value="<?= htmlspecialchars($searchKeyword) ?>" style="min-width:280px;">
                        
                        <label style="font-weight:600; font-size:13px; color:var(--text-secondary);">Status:</label>
                        <select name="status_filter" class="select-filter" onchange="this.form.submit()">
                            <option value="All" <?= $statusFilter === 'All' ? 'selected' : '' ?>>All Statuses</option>
                            <option value="Pending" <?= $statusFilter === 'Pending' ? 'selected' : '' ?>>Pending</option>
                            <option value="Approved" <?= $statusFilter === 'Approved' ? 'selected' : '' ?>>Approved</option>
                            <option value="Declined" <?= $statusFilter === 'Declined' ? 'selected' : '' ?>>Declined</option>
                            <option value="Completed" <?= $statusFilter === 'Completed' ? 'selected' : '' ?>>Completed</option>
                        </select>
                        <button type="submit" class="btn btn-outline">Filter</button>
                        <?php if (!empty($searchKeyword) || $statusFilter !== 'All'): ?>
                            <a href="?tab=request" class="btn btn-secondary">Reset</a>
                        <?php endif; ?>
                    </div>
                </form>

                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Request ID</th>
                                <th>Student Name</th>
                                <th>Subject / Dept</th>
                                <th>Purpose</th>
                                <th>Request Date & Time</th>
                                <th>Status</th>
                                <th>Remarks</th>
                                <th style="text-align:right;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($requestsList)): ?>
                                <tr>
                                    <td colspan="8" style="text-align:center; padding:30px; color:var(--text-muted);">
                                        No student consultation requests match your criteria.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($requestsList as $r): ?>
                                    <tr>
                                        <td><strong>#<?= htmlspecialchars($r['appointment_id']) ?></strong></td>
                                        <td><strong><?= htmlspecialchars($r['student_name']) ?></strong></td>
                                        <td><?= htmlspecialchars($r['program_or_dept']) ?></td>
                                        <td><?= htmlspecialchars($r['purpose']) ?></td>
                                        <td><?= htmlspecialchars($r['request_datetime']) ?></td>
                                        <td>
                                            <span class="badge badge-<?= strtolower(htmlspecialchars($r['status'])) ?>">
                                                <?= htmlspecialchars($r['status']) ?>
                                            </span>
                                        </td>
                                        <td><?= htmlspecialchars($r['faculty_remarks'] ?: '-') ?></td>
                                        <td style="text-align:right;">
                                            <?php if ($r['status'] === 'Pending'): ?>
                                                <form method="POST" style="display:inline-block;" onsubmit="return confirm('Approve this consultation request?');">
                            <?= App::csrfField() ?>
                                                    <input type="hidden" name="action" value="update_request_status">
                                                    <input type="hidden" name="appointment_id" value="<?= (int)$r['appointment_id'] ?>">
                                                    <input type="hidden" name="status" value="Approved">
                                                    <button type="submit" class="btn btn-success" style="padding:4px 10px; font-size:12px;">Approve</button>
                                                </form>
                                                <form method="POST" style="display:inline-block;" onsubmit="return confirm('Decline this consultation request?');">
                            <?= App::csrfField() ?>
                                                    <input type="hidden" name="action" value="update_request_status">
                                                    <input type="hidden" name="appointment_id" value="<?= (int)$r['appointment_id'] ?>">
                                                    <input type="hidden" name="status" value="Declined">
                                                    <button type="submit" class="btn btn-danger" style="padding:4px 10px; font-size:12px;">Decline</button>
                                                </form>
                                            <?php elseif ($r['status'] === 'Approved'): ?>
                                                <form method="POST" style="display:inline-block;" onsubmit="return confirm('Mark this meeting as completed?');">
                            <?= App::csrfField() ?>
                                                    <input type="hidden" name="action" value="update_request_status">
                                                    <input type="hidden" name="appointment_id" value="<?= (int)$r['appointment_id'] ?>">
                                                    <input type="hidden" name="status" value="Completed">
                                                    <button type="submit" class="btn btn-primary" style="padding:4px 10px; font-size:12px;">Complete</button>
                                                </form>
                                            <?php else: ?>
                                                <span style="font-size:12px; color:var(--text-muted);">-</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

            <?php elseif ($currentTab === 'availability'): ?>
                <div class="section-banner">
                    <h2>Manage Consultation Hours</h2>
                    <p>Add, edit, and organize your weekly consultation schedule so students can book slots</p>
                </div>

                <div class="two-column-layout">
                    <div class="panel-card">
                        <h3 class="panel-card-title" id="avail-form-title">Add Consultation Hours</h3>
                        <form method="POST" action="faculty.php?tab=availability" id="avail-form">
                            <?= App::csrfField() ?>
                            <input type="hidden" name="action" value="save_availability">
                            <input type="hidden" name="availability_id" id="avail_id" value="-1">

                            <div class="form-group">
                                <label class="form-label" for="day">Day of Week</label>
                                <select name="day" id="day" class="form-control" required>
                                    <option value="">-- Select Day --</option>
                                    <option value="Monday">Monday</option>
                                    <option value="Tuesday">Tuesday</option>
                                    <option value="Wednesday">Wednesday</option>
                                    <option value="Thursday">Thursday</option>
                                    <option value="Friday">Friday</option>
                                    <option value="Saturday">Saturday</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label class="form-label" for="start_time">Start Time</label>
                                <input type="time" name="start_time" id="start_time" class="form-control" value="08:00" required>
                            </div>

                            <div class="form-group">
                                <label class="form-label" for="end_time">End Time</label>
                                <input type="time" name="end_time" id="end_time" class="form-control" value="09:00" required>
                            </div>

                            <div class="form-group">
                                <label class="form-label" for="location">Location (Optional)</label>
                                <input maxlength="100" type="text" name="location" id="location" class="form-control" placeholder="e.g. Room 101, Online via MS Teams">
                            </div>

                            <div class="form-actions">
                                <button type="submit" class="btn btn-success" id="btn-save-avail" style="flex:1;">
                                    + Add
                                </button>
                                <button type="button" class="btn btn-secondary" onclick="resetAvailForm();">
                                    Clear
                                </button>
                            </div>
                        </form>
                    </div>

                    <div class="panel-card">
                        <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:10px; margin-bottom:14px;">
                            <h3 class="panel-card-title" style="margin-bottom:0; border:none; padding:0;">Weekly Consultation Hours</h3>
                            <form method="GET" action="faculty.php" style="display:flex; gap:8px;">
                                <input type="hidden" name="tab" value="availability">
                                <input maxlength="100" type="text" name="avail_search" class="input-search" placeholder="Search day or location..." value="<?= htmlspecialchars($availSearch) ?>">
                                <button type="submit" class="btn btn-primary" style="padding:6px 12px; font-size:13px;">Search</button>
                                <?php if (!empty($availSearch)): ?>
                                    <a href="?tab=availability" class="btn btn-secondary" style="padding:6px 12px; font-size:13px;">Reset</a>
                                <?php endif; ?>
                            </form>
                        </div>

                        <div class="table-responsive">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>Day</th>
                                        <th>Start Time</th>
                                        <th>End Time</th>
                                        <th>Location</th>
                                        <th style="text-align:right;">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($availabilityList)): ?>
                                        <tr>
                                            <td colspan="5" style="text-align:center; padding:30px; color:var(--text-muted);">
                                                No consultation hours found. Use the form on the left to add slots.
                                            </td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($availabilityList as $av): ?>
                                            <tr>
                                                <td><strong><?= htmlspecialchars($av['days']) ?></strong></td>
                                                <td><?= htmlspecialchars($av['start_fmt']) ?></td>
                                                <td><?= htmlspecialchars($av['end_fmt']) ?></td>
                                                <td><?= htmlspecialchars($av['location'] ?: 'Not Specified') ?></td>
                                                <td style="text-align:right;">
                                                    <button type="button" class="btn btn-primary" style="padding:4px 10px; font-size:12px;"
                                                            onclick='populateAvailForm(<?= json_encode($av, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>)'>
                                                        Edit
                                                    </button>
                                                    <form method="POST" style="display:inline-block;" onsubmit="return confirm('Delete this consultation schedule?');">
                            <?= App::csrfField() ?>
                                                        <input type="hidden" name="action" value="delete_availability">
                                                        <input type="hidden" name="availability_id" value="<?= (int)$av['availability_id'] ?>">
                                                        <button type="submit" class="btn btn-danger" style="padding:4px 10px; font-size:12px;">Delete</button>
                                                    </form>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                        <p style="font-size:12px; color:var(--text-muted); margin-top:10px;">
                            💡 Tip: Click "Edit" on any row to load its details into the form.
                        </p>
                    </div>
                </div>

            <?php elseif ($currentTab === 'setting'): ?>
                <div class="section-banner">
                    <h2>Settings</h2>
                    <p>Manage your account information and application preferences</p>
                </div>

                <div class="settings-grid">
                    <div class="panel-card">
                        <h3 class="panel-card-title">Faculty Profile</h3>
                        <form method="POST" action="faculty.php?tab=setting">
                            <?= App::csrfField() ?>
                            <input type="hidden" name="action" value="update_profile">

                            <div class="form-group">
                                <label class="form-label" for="full_name">Full Name</label>
                                <input maxlength="101" type="text" id="full_name" name="full_name" class="form-control" 
                                       value="<?= htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) ?>" required>
                            </div>

                            <div class="form-group">
                                <label class="form-label" for="email">Email</label>
                                <input type="email" id="email" class="form-control" value="<?= htmlspecialchars($user['ue_email']) ?>" readonly style="background:#f3f4f6;">
                            </div>

                            <div class="form-group">
                                <label class="form-label" for="department">Department</label>
                                <input maxlength="50" type="text" id="department" name="department" class="form-control" 
                                       value="<?= htmlspecialchars($user['program_or_dept'] ?? '') ?>" required>
                            </div>

                            <div class="form-group">
                                <label class="form-label" for="position">Position / Role</label>
                                <input type="text" id="position" class="form-control" value="<?= htmlspecialchars($user['role']) ?>" readonly style="background:#f3f4f6;">
                            </div>

                            <button type="submit" class="btn btn-success" style="width:100%; margin-top:10px;">
                                Save Profile
                            </button>
                        </form>
                    </div>

                    <div class="panel-card">
                        <h3 class="panel-card-title">Change Password</h3>
                        <form method="POST" action="faculty.php?tab=setting">
                            <?= App::csrfField() ?>
                            <input type="hidden" name="action" value="change_password">

                            <div class="form-group">
                                <label class="form-label" for="current_password">Current Password</label>
                                <input autocomplete="current-password" type="password" id="current_password" name="current_password" class="form-control" required>
                            </div>

                            <div class="form-group">
                                <label class="form-label" for="new_password">New Password</label>
                                <input autocomplete="new-password" maxlength="64" type="password" id="new_password" name="new_password" class="form-control" required minlength="6">
                            </div>

                            <div class="form-group">
                                <label class="form-label" for="confirm_password">Confirm New Password</label>
                                <input autocomplete="new-password" maxlength="64" type="password" id="confirm_password" name="confirm_password" class="form-control" required minlength="6">
                            </div>

                            <button type="submit" class="btn btn-primary" style="width:100%; margin-top:10px;">
                                Change Password
                            </button>
                        </form>
                    </div>

                    <div class="panel-card" style="grid-column: 1 / -1;">
                        <h3 class="panel-card-title">Application Info</h3>
                        <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap:16px; font-size:14px;">
                            <div>
                                <span style="color:var(--text-secondary);">Application Version:</span><br>
                                <strong>1.0.0 (Web Edition)</strong>
                            </div>
                            <div>
                                <span style="color:var(--text-secondary);">Database Status:</span><br>
                                <strong style="color:var(--dcss-green);">Connected (dcss_db)</strong>
                            </div>
                            <div>
                                <span style="color:var(--text-secondary);">Current Session:</span><br>
                                <strong><?= date('M d, Y h:i A') ?></strong>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <script>
    function populateAvailForm(item) {
        document.getElementById('avail_id').value = item.availability_id;
        document.getElementById('day').value = item.days;
        document.getElementById('start_time').value = item.start_time ? item.start_time.substring(0, 5) : '08:00';
        document.getElementById('end_time').value = item.end_time ? item.end_time.substring(0, 5) : '09:00';
        document.getElementById('location').value = item.location || '';
        document.getElementById('avail-form-title').innerText = 'Edit Consultation Hours';
        document.getElementById('btn-save-avail').innerText = 'Save Changes';
        document.getElementById('day').scrollIntoView({ behavior: 'smooth' });
    }

    function resetAvailForm() {
        document.getElementById('avail_id').value = -1;
        document.getElementById('avail-form').reset();
        document.getElementById('avail-form-title').innerText = 'Add Consultation Hours';
        document.getElementById('btn-save-avail').innerText = '+ Add';
    }
    </script>
    <?= App::formState() ?>
    <script src="assets/validation.js" defer></script>
</body>
</html>

