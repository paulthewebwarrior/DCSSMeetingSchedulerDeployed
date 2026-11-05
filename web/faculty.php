<?php
require_once __DIR__ . '/includes/Database.php';
require_once __DIR__ . '/includes/Auth.php';
require_once __DIR__ . '/includes/Flash.php';

$user = Auth::requireRole('Faculty');
$db = Database::getConnection();

$stmt = $db->prepare("SELECT * FROM users WHERE user_id = :id");
$stmt->execute([':id' => $user['user_id']]);
$user = $stmt->fetch();
$_SESSION['user'] = $user;

$currentTab = $_GET['tab'] ?? 'home';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'update_profile') {
        $fullName   = trim($_POST['full_name'] ?? '');
        $department = trim($_POST['department'] ?? '');

        if (empty($fullName) || empty($department)) {
            Flash::set('error', 'Please enter your name and department.');
        } else {
            $firstName = $fullName;
            $lastName  = '';
            $spacePos  = strpos($fullName, ' ');
            if ($spacePos !== false) {
                $firstName = substr($fullName, 0, $spacePos);
                $lastName  = trim(substr($fullName, $spacePos + 1));
            }

            $stmt = $db->prepare("UPDATE users SET first_name = :fname, last_name = :lname, program_or_dept = :dept WHERE user_id = :id");
            $stmt->execute([
                ':fname' => $firstName,
                ':lname' => $lastName,
                ':dept'  => $department,
                ':id'    => $user['user_id']
            ]);
            Flash::set('success', 'Profile updated successfully!');
        }
        header('Location: faculty.php?tab=setting');
        exit;
    }

    if ($action === 'change_password') {
        $currentPass = trim($_POST['current_password'] ?? '');
        $newPass     = trim($_POST['new_password'] ?? '');
        $confirmPass = trim($_POST['confirm_password'] ?? '');

        if (empty($currentPass) || empty($newPass) || empty($confirmPass)) {
            Flash::set('error', 'Please fill in all password fields.');
        } elseif ($currentPass !== $user['user_password']) {
            Flash::set('error', 'Current password is incorrect.');
        } elseif (strlen($newPass) < 6) {
            Flash::set('error', 'New password must be at least 6 characters long.');
        } elseif ($newPass === $currentPass) {
            Flash::set('error', 'New password cannot be the same as your current password.');
        } elseif ($newPass !== $confirmPass) {
            Flash::set('error', 'New password and confirm password do not match.');
        } else {
            $stmt = $db->prepare("UPDATE users SET user_password = :pwd, must_change_password = 0 WHERE user_id = :id");
            $stmt->execute([':pwd' => $newPass, ':id' => $user['user_id']]);
            Flash::set('success', 'Password changed successfully!');
        }
        header('Location: faculty.php?tab=setting');
        exit;
    }

    if ($action === 'save_availability') {
        $availId   = (int)($_POST['availability_id'] ?? -1);
        $day       = trim($_POST['day'] ?? '');
        $startTime = trim($_POST['start_time'] ?? '');
        $endTime   = trim($_POST['end_time'] ?? '');
        $location  = trim($_POST['location'] ?? '');

        if (empty($day)) {
            Flash::set('error', 'Please select a day of the week.');
        } elseif (strtotime($startTime) >= strtotime($endTime)) {
            Flash::set('error', 'Start time must be earlier than end time.');
        } else {
            if ($availId === -1) {
                $stmt = $db->prepare("
                    INSERT INTO faculty_availability (faculty_id, days, start_time, end_time, location)
                    VALUES (:fid, :day, :start, :end, :loc)
                ");
                $stmt->execute([
                    ':fid'   => $user['user_id'],
                    ':day'   => $day,
                    ':start' => $startTime,
                    ':end'   => $endTime,
                    ':loc'   => $location
                ]);
                Flash::set('success', 'Consultation hours added successfully!');
            } else {
                $stmt = $db->prepare("
                    UPDATE faculty_availability
                    SET days = :day, start_time = :start, end_time = :end, location = :loc
                    WHERE availability_id = :id AND faculty_id = :fid
                ");
                $stmt->execute([
                    ':day'   => $day,
                    ':start' => $startTime,
                    ':end'   => $endTime,
                    ':loc'   => $location,
                    ':id'    => $availId,
                    ':fid'   => $user['user_id']
                ]);
                Flash::set('success', 'Consultation hours updated successfully!');
            }
        }
        header('Location: faculty.php?tab=availability');
        exit;
    }

    if ($action === 'delete_availability') {
        $availId = (int)($_POST['availability_id'] ?? -1);
        if ($availId > 0) {
            $stmt = $db->prepare("DELETE FROM faculty_availability WHERE availability_id = :id AND faculty_id = :fid");
            $stmt->execute([':id' => $availId, ':fid' => $user['user_id']]);
            Flash::set('success', 'Consultation hours deleted successfully!');
        }
        header('Location: faculty.php?tab=availability');
        exit;
    }

    if ($action === 'update_request_status') {
        $appId   = (int)($_POST['appointment_id'] ?? -1);
        $status  = $_POST['status'] ?? '';
        $allowed = ['Approved', 'Declined', 'Completed'];

        if ($appId > 0 && in_array($status, $allowed, true)) {
            $stmt = $db->prepare("UPDATE appointments SET status = :status, is_student_notified = 0 WHERE appointment_id = :id AND faculty_id = :fid");
            $stmt->execute([
                ':status' => $status,
                ':id'     => $appId,
                ':fid'    => $user['user_id']
            ]);
            Flash::set('success', "Request marked as {$status}!");
        }
        header('Location: faculty.php?tab=' . urlencode($currentTab));
        exit;
    }
}

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

$searchKeyword = trim($_GET['search'] ?? '');
$statusFilter  = $_GET['status_filter'] ?? 'All';

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
    $reqQuery .= " AND (u.first_name LIKE :s OR u.last_name LIKE :s OR CONCAT(u.first_name, ' ', u.last_name) LIKE :s OR u.program_or_dept LIKE :s OR a.purpose LIKE :s)";
    $params[':s'] = "%{$searchKeyword}%";
}
$reqQuery .= " ORDER BY a.meeting_datetime DESC";

$stmtReq = $db->prepare($reqQuery);
$stmtReq->execute($params);
$requestsList = $stmtReq->fetchAll();

$availSearch = trim($_GET['avail_search'] ?? '');
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
    $availQuery .= " AND (days LIKE :as OR location LIKE :as)";
    $availParams[':as'] = "%{$availSearch}%";
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
    <title>Faculty Portal - DCSS Meeting Scheduler</title>
    <link rel="stylesheet" href="css/app.css">
</head>
<body>
    <header class="win-header-panel">
        <div class="win-brand">
            <img src="assets/dcss.jpg" alt="DCSS Logo" class="win-brand-logo" onerror="this.src='Resources/dcss.jpg'">
            <div>
                <div class="win-title">Faculty Portal</div>
                <div class="win-subtitle">Faculty Request Management System</div>
            </div>
        </div>
        <div class="win-user-info">
            <span class="win-badge"><?= htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) ?> (Faculty)</span>
            <a href="logout.php" class="win-btn-logout" onclick="return confirm('Are you sure you want to log out?');">
                Log Out
            </a>
        </div>
    </header>

    <div class="win-container">
        <?= Flash::render() ?>

        <div class="win-tabcontrol">
            <div class="win-tab-strip">
                <a href="?tab=home" class="win-tab-item <?= $currentTab === 'home' ? 'active' : '' ?>">Home</a>
                <a href="?tab=request" class="win-tab-item <?= $currentTab === 'request' ? 'active' : '' ?>">Request</a>
                <a href="?tab=availability" class="win-tab-item <?= $currentTab === 'availability' ? 'active' : '' ?>">Availability</a>
                <a href="?tab=setting" class="win-tab-item <?= $currentTab === 'setting' ? 'active' : '' ?>">Setting</a>
            </div>

            <div class="win-tab-page">
                <?php if ($currentTab === 'home'): ?>
                    <!-- Section Header (panel5) -->
                    <div class="win-section-header">
                        <h2>Faculty Dashboard</h2>
                        <p>Overview of student consultation request</p>
                    </div>

                    <!-- 4 Summary Metric Panels (pnlTotal, pnlPending, pnlApproved, pnlDeclined) -->
                    <div class="win-metric-cards">
                        <div class="win-card-panel card-total">
                            <div class="win-card-label">Total Requests</div>
                            <div class="win-card-value"><?= (int)$stats['total_count'] ?></div>
                        </div>
                        <div class="win-card-panel card-pending">
                            <div class="win-card-label">Pending</div>
                            <div class="win-card-value"><?= (int)$stats['pending_count'] ?></div>
                        </div>
                        <div class="win-card-panel card-approved">
                            <div class="win-card-label">Approved</div>
                            <div class="win-card-value"><?= (int)$stats['approved_count'] ?></div>
                        </div>
                        <div class="win-card-panel card-declined">
                            <div class="win-card-label">Declined</div>
                            <div class="win-card-value"><?= (int)$stats['declined_count'] ?></div>
                        </div>
                    </div>

                    <!-- Today's Overview GroupBox (grpOverview) -->
                    <div class="win-groupbox">
                        <div class="win-groupbox-title">Today's Overview</div>
                        <div class="win-overview-subpanels">
                            <div class="win-subpanel">
                                <span class="win-subpanel-title">Today's Meetings:</span>
                                <span class="win-subpanel-value"><?= (int)$stats['today_count'] ?></span>
                            </div>
                            <div class="win-subpanel">
                                <span class="win-subpanel-title">Upcoming Meetings:</span>
                                <span class="win-subpanel-value"><?= (int)$stats['upcoming_count'] ?></span>
                            </div>
                        </div>
                    </div>

                    <!-- Notification Banner (pnlNotifs) -->
                    <div class="win-notif-bar">
                        <span>
                            <?php if ($stats['pending_count'] > 0): ?>
                                You have <?= (int)$stats['pending_count'] ?> new meeting request updates.
                            <?php else: ?>
                                You have no pending meeting requests.
                            <?php endif; ?>
                        </span>
                        <a href="?tab=home" class="win-btn win-btn-blue">Refresh</a>
                    </div>

                    <!-- Recent Meetings GroupBox (grpRecentMeet + dataGridView2) -->
                    <div class="win-groupbox">
                        <div class="win-groupbox-title">Recent Meetings Request</div>
                        <div class="win-datagrid-wrap">
                            <table class="win-datagrid">
                                <thead>
                                    <tr>
                                        <th>Request ID</th>
                                        <th>Student Name</th>
                                        <th>Meeting Date</th>
                                        <th>Start Time</th>
                                        <th>End Time</th>
                                        <th>Status</th>
                                        <th>Faculty Remarks</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($recentMeetings)): ?>
                                        <tr>
                                            <td colspan="7" style="text-align:center; padding:24px; color:#94a3b8;">
                                                No recent meeting requests.
                                            </td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($recentMeetings as $m): ?>
                                            <tr>
                                                <td>#<?= htmlspecialchars($m['appointment_id']) ?></td>
                                                <td><strong><?= htmlspecialchars($m['student_name']) ?></strong></td>
                                                <td><?= htmlspecialchars($m['meeting_date']) ?></td>
                                                <td><?= htmlspecialchars($m['start_time']) ?></td>
                                                <td><?= htmlspecialchars($m['end_time']) ?></td>
                                                <td>
                                                    <span class="win-status-badge status-<?= strtolower(htmlspecialchars($m['status'])) ?>">
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
                    <!-- Request Dashboard Header (panel4) -->
                    <div class="win-section-header">
                        <h2>Request Dashboard</h2>
                        <p>Manage your requests</p>
                    </div>

                    <!-- 4 Counter Panels (panel6, panel7, panel8, panel9) -->
                    <div class="win-metric-cards">
                        <div class="win-card-panel card-total">
                            <div class="win-card-label">Total Requests</div>
                            <div class="win-card-value"><?= (int)$stats['total_count'] ?></div>
                        </div>
                        <div class="win-card-panel card-pending">
                            <div class="win-card-label">Pending Requests</div>
                            <div class="win-card-value"><?= (int)$stats['pending_count'] ?></div>
                        </div>
                        <div class="win-card-panel card-approved">
                            <div class="win-card-label">Approved Requests</div>
                            <div class="win-card-value"><?= (int)$stats['approved_count'] ?></div>
                        </div>
                        <div class="win-card-panel card-declined">
                            <div class="win-card-label">Declined Requests</div>
                            <div class="win-card-value"><?= (int)$stats['declined_count'] ?></div>
                        </div>
                    </div>

                    <!-- Section Sub-Header (label16: Student Requests) -->
                    <div style="font-size:16px; font-weight:700; color:#004000; margin:16px 0 10px 0;">
                        Student Requests
                    </div>

                    <!-- Toolbar (label17, textBox3, label18, comboBox2, button2, button3, button4) -->
                    <form method="GET" action="faculty.php" class="win-toolbar">
                        <input type="hidden" name="tab" value="request">
                        <div class="win-filter-set">
                            <label style="font-weight:600; font-size:12.5px;">Search (Students / Subject / Purpose):</label>
                            <input type="text" name="search" class="win-textbox" placeholder="Search..." value="<?= htmlspecialchars($searchKeyword) ?>" style="width:260px;">
                            
                            <label style="font-weight:600; font-size:12.5px; margin-left:6px;">Filter Status:</label>
                            <select name="status_filter" class="win-combobox" onchange="this.form.submit()">
                                <option value="All" <?= $statusFilter === 'All' ? 'selected' : '' ?>>All</option>
                                <option value="Pending" <?= $statusFilter === 'Pending' ? 'selected' : '' ?>>Pending</option>
                                <option value="Approved" <?= $statusFilter === 'Approved' ? 'selected' : '' ?>>Approved</option>
                                <option value="Declined" <?= $statusFilter === 'Declined' ? 'selected' : '' ?>>Declined</option>
                                <option value="Completed" <?= $statusFilter === 'Completed' ? 'selected' : '' ?>>Completed</option>
                            </select>
                            <button type="submit" class="win-btn win-btn-blue">Filter</button>
                            <?php if (!empty($searchKeyword) || $statusFilter !== 'All'): ?>
                                <a href="?tab=request" class="win-btn win-btn-gray">Reset</a>
                            <?php endif; ?>
                        </div>
                    </form>

                    <!-- Requests Grid (Requests DataGridView) -->
                    <div class="win-datagrid-wrap">
                        <table class="win-datagrid">
                            <thead>
                                <tr>
                                    <th>Request ID</th>
                                    <th>Student Name</th>
                                    <th>Subject</th>
                                    <th>Purpose</th>
                                    <th>Request Date & Time</th>
                                    <th>Status</th>
                                    <th>Faculty Remarks</th>
                                    <th style="text-align:right;">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($requestsList)): ?>
                                    <tr>
                                        <td colspan="8" style="text-align:center; padding:30px; color:#94a3b8;">
                                            No student requests match your criteria.
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($requestsList as $r): ?>
                                        <tr>
                                            <td>#<?= htmlspecialchars($r['appointment_id']) ?></td>
                                            <td><strong><?= htmlspecialchars($r['student_name']) ?></strong></td>
                                            <td><?= htmlspecialchars($r['program_or_dept']) ?></td>
                                            <td><?= htmlspecialchars($r['purpose']) ?></td>
                                            <td><?= htmlspecialchars($r['request_datetime']) ?></td>
                                            <td>
                                                <span class="win-status-badge status-<?= strtolower(htmlspecialchars($r['status'])) ?>">
                                                    <?= htmlspecialchars($r['status']) ?>
                                                </span>
                                            </td>
                                            <td><?= htmlspecialchars($r['faculty_remarks'] ?: '-') ?></td>
                                            <td style="text-align:right;">
                                                <?php if ($r['status'] === 'Pending'): ?>
                                                    <form method="POST" style="display:inline-block;" onsubmit="return confirm('Approve this request?');">
                                                        <input type="hidden" name="action" value="update_request_status">
                                                        <input type="hidden" name="appointment_id" value="<?= (int)$r['appointment_id'] ?>">
                                                        <input type="hidden" name="status" value="Approved">
                                                        <button type="submit" class="win-btn win-btn-green" style="height:26px; padding:0 8px; font-size:11.5px;">Approve</button>
                                                    </form>
                                                    <form method="POST" style="display:inline-block;" onsubmit="return confirm('Decline this request?');">
                                                        <input type="hidden" name="action" value="update_request_status">
                                                        <input type="hidden" name="appointment_id" value="<?= (int)$r['appointment_id'] ?>">
                                                        <input type="hidden" name="status" value="Declined">
                                                        <button type="submit" class="win-btn win-btn-red" style="height:26px; padding:0 8px; font-size:11.5px;">Decline</button>
                                                    </form>
                                                <?php elseif ($r['status'] === 'Approved'): ?>
                                                    <form method="POST" style="display:inline-block;" onsubmit="return confirm('Mark this meeting as completed?');">
                                                        <input type="hidden" name="action" value="update_request_status">
                                                        <input type="hidden" name="appointment_id" value="<?= (int)$r['appointment_id'] ?>">
                                                        <input type="hidden" name="status" value="Completed">
                                                        <button type="submit" class="win-btn win-btn-forest" style="height:26px; padding:0 8px; font-size:11.5px;">Complete</button>
                                                    </form>
                                                <?php else: ?>
                                                    <span style="color:#94a3b8; font-size:12px;">-</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                <?php elseif ($currentTab === 'availability'): ?>
                    <!-- Section Header (panel3) -->
                    <div class="win-section-header">
                        <h2>Weekly Consultation Hours</h2>
                        <p>Set and manage your consultation schedule</p>
                    </div>

                    <div class="win-two-col">
                        <!-- Left GroupBox (groupBox1: Add / Consultation Hours) -->
                        <div class="win-groupbox">
                            <div class="win-groupbox-title" id="avail-heading">Add / Consultation Hours</div>
                            <form method="POST" action="faculty.php?tab=availability" id="avail-form">
                                <input type="hidden" name="action" value="save_availability">
                                <input type="hidden" name="availability_id" id="avail_id" value="-1">

                                <div class="win-field-group">
                                    <label class="win-field-label" for="day">Day of Week</label>
                                    <select name="day" id="day" class="win-combobox" style="width:100%;" required>
                                        <option value="">Select a day</option>
                                        <option value="Monday">Monday</option>
                                        <option value="Tuesday">Tuesday</option>
                                        <option value="Wednesday">Wednesday</option>
                                        <option value="Thursday">Thursday</option>
                                        <option value="Friday">Friday</option>
                                        <option value="Saturday">Saturday</option>
                                    </select>
                                </div>

                                <div class="win-field-group">
                                    <label class="win-field-label" for="start_time">Start Time</label>
                                    <input type="time" name="start_time" id="start_time" class="win-textbox" style="width:100%;" value="08:00" required>
                                </div>

                                <div class="win-field-group">
                                    <label class="win-field-label" for="end_time">End Time</label>
                                    <input type="time" name="end_time" id="end_time" class="win-textbox" style="width:100%;" value="09:00" required>
                                </div>

                                <div class="win-field-group">
                                    <label class="win-field-label" for="location">Location (Optional)</label>
                                    <input type="text" name="location" id="location" class="win-textbox" style="width:100%;" placeholder="e.g Room 101, Online etc.">
                                </div>

                                <div class="win-form-actions">
                                    <button type="submit" class="win-btn win-btn-green" id="btn-save-avail" style="flex:1; height:36px;">
                                        + Add
                                    </button>
                                    <button type="button" class="win-btn win-btn-gray" onclick="resetAvailForm();" style="flex:1; height:36px;">
                                        Clear
                                    </button>
                                </div>
                            </form>
                        </div>

                        <!-- Right GroupBox (groupBox2: Weekly Consultation Hours) -->
                        <div class="win-groupbox">
                            <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:8px; margin-bottom:12px;">
                                <div class="win-groupbox-title" style="margin-bottom:0; padding-bottom:0; border:none;">
                                    Weekly Consultation Hours
                                </div>
                                <form method="GET" action="faculty.php" style="display:flex; gap:6px;">
                                    <input type="hidden" name="tab" value="availability">
                                    <input type="text" name="avail_search" class="win-textbox" placeholder="Search day or location..." value="<?= htmlspecialchars($availSearch) ?>" style="width:200px;">
                                    <button type="submit" class="win-btn win-btn-blue">Search</button>
                                    <?php if (!empty($availSearch)): ?>
                                        <a href="?tab=availability" class="win-btn win-btn-gray">Reset</a>
                                    <?php endif; ?>
                                </form>
                            </div>

                            <div class="win-datagrid-wrap">
                                <table class="win-datagrid">
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
                                                <td colspan="5" style="text-align:center; padding:24px; color:#94a3b8;">
                                                    No consultation hours scheduled.
                                                </td>
                                            </tr>
                                        <?php else: ?>
                                            <?php foreach ($availabilityList as $av): ?>
                                                <tr>
                                                    <td><strong><?= htmlspecialchars($av['days']) ?></strong></td>
                                                    <td><?= htmlspecialchars($av['start_fmt']) ?></td>
                                                    <td><?= htmlspecialchars($av['end_fmt']) ?></td>
                                                    <td><?= htmlspecialchars($av['location'] ?: 'Faculty Room') ?></td>
                                                    <td style="text-align:right;">
                                                        <button type="button" class="win-btn win-btn-green" style="height:26px; padding:0 10px; font-size:11.5px;"
                                                                onclick='populateAvailForm(<?= json_encode($av) ?>)'>
                                                            Edit
                                                        </button>
                                                        <form method="POST" style="display:inline-block;" onsubmit="return confirm('Are you sure you want to delete this schedule?');">
                                                            <input type="hidden" name="action" value="delete_availability">
                                                            <input type="hidden" name="availability_id" value="<?= (int)$av['availability_id'] ?>">
                                                            <button type="submit" class="win-btn win-btn-red" style="height:26px; padding:0 10px; font-size:11.5px;">Delete</button>
                                                        </form>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>

                            <div style="font-size:12px; color:#64748b; margin-top:10px;">
                                Tips: Click "Edit" to modify an existing schedule, or "+ Add" to create a new one.
                            </div>
                        </div>
                    </div>

                <?php elseif ($currentTab === 'setting'): ?>
                    <!-- Settings Header (pnlHeaderSetting) -->
                    <div class="win-section-header">
                        <h2>Settings</h2>
                        <p>Manage your account information and application preferences</p>
                    </div>

                    <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(360px, 1fr)); gap:16px;">
                        <!-- Faculty Profile Panel (pnlFacultyProfile) -->
                        <div class="win-groupbox">
                            <div class="win-groupbox-title">Faculty Profile</div>
                            <form method="POST" action="faculty.php?tab=setting">
                                <input type="hidden" name="action" value="update_profile">

                                <div class="win-field-group">
                                    <label class="win-field-label" for="full_name">Name</label>
                                    <input type="text" id="full_name" name="full_name" class="win-textbox" style="width:100%;" 
                                           value="<?= htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) ?>" required>
                                </div>

                                <div class="win-field-group">
                                    <label class="win-field-label" for="email">Email</label>
                                    <input type="email" id="email" class="win-textbox" style="width:100%; background:#f1f5f9;" value="<?= htmlspecialchars($user['ue_email']) ?>" readonly>
                                </div>

                                <div class="win-field-group">
                                    <label class="win-field-label" for="department">Department</label>
                                    <input type="text" id="department" name="department" class="win-textbox" style="width:100%;" 
                                           value="<?= htmlspecialchars($user['program_or_dept'] ?? '') ?>" required>
                                </div>

                                <div class="win-field-group">
                                    <label class="win-field-label" for="position">Position</label>
                                    <input type="text" id="position" class="win-textbox" style="width:100%; background:#f1f5f9;" value="<?= htmlspecialchars($user['role']) ?>" readonly>
                                </div>

                                <button type="submit" class="win-btn win-btn-green" style="width:100%; height:36px; margin-top:8px;">
                                    Save Profile
                                </button>
                            </form>
                        </div>

                        <!-- Account Settings Panel (pnlAccountSettings) -->
                        <div class="win-groupbox">
                            <div class="win-groupbox-title">Account Settings</div>
                            <p style="font-size:12px; color:#64748b; margin-bottom:12px;">Update your password and security settings</p>
                            <form method="POST" action="faculty.php?tab=setting">
                                <input type="hidden" name="action" value="change_password">

                                <div class="win-field-group">
                                    <label class="win-field-label" for="current_password">Current Password</label>
                                    <input type="password" id="current_password" name="current_password" class="win-textbox" style="width:100%;" required>
                                </div>

                                <div class="win-field-group">
                                    <label class="win-field-label" for="new_password">New Password</label>
                                    <input type="password" id="new_password" name="new_password" class="win-textbox" style="width:100%;" required minlength="6">
                                </div>

                                <div class="win-field-group">
                                    <label class="win-field-label" for="confirm_password">Confirm Password</label>
                                    <input type="password" id="confirm_password" name="confirm_password" class="win-textbox" style="width:100%;" required minlength="6">
                                </div>

                                <button type="submit" class="win-btn win-btn-blue" style="width:100%; height:36px; margin-top:8px;">
                                    Change Password
                                </button>
                            </form>
                        </div>

                        <!-- App Info Panel (pnlAppInfo) -->
                        <div class="win-groupbox" style="grid-column: 1 / -1;">
                            <div class="win-groupbox-title">Application Info</div>
                            <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap:14px; font-size:13px;">
                                <div>
                                    <span style="color:#64748b;">Application Version:</span><br>
                                    <strong>1.0.0</strong>
                                </div>
                                <div>
                                    <span style="color:#64748b;">Database:</span><br>
                                    <strong style="color:#004000;">Connected (dcss_db)</strong>
                                </div>
                                <div>
                                    <span style="color:#64748b;">Last Login:</span><br>
                                    <strong><?= date('M d, Y h:i A') ?></strong>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
    function populateAvailForm(item) {
        document.getElementById('avail_id').value = item.availability_id;
        document.getElementById('day').value = item.days;
        document.getElementById('start_time').value = item.start_time ? item.start_time.substring(0, 5) : '08:00';
        document.getElementById('end_time').value = item.end_time ? item.end_time.substring(0, 5) : '09:00';
        document.getElementById('location').value = item.location || '';
        document.getElementById('avail-heading').innerText = 'Edit Consultation Hours';
        document.getElementById('btn-save-avail').innerText = 'Save';
        document.getElementById('day').scrollIntoView({ behavior: 'smooth' });
    }

    function resetAvailForm() {
        document.getElementById('avail_id').value = -1;
        document.getElementById('avail-form').reset();
        document.getElementById('avail-heading').innerText = 'Add / Consultation Hours';
        document.getElementById('btn-save-avail').innerText = '+ Add';
    }
    </script>
</body>
</html>
