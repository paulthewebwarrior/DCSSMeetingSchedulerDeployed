<?php
require_once __DIR__ . '/includes/Database.php';
require_once __DIR__ . '/includes/Auth.php';
require_once __DIR__ . '/includes/Flash.php';

$user = Auth::requireRole('Student');
$db = Database::getConnection();

$stmt = $db->prepare("SELECT * FROM users WHERE user_id = :id");
$stmt->execute([':id' => $user['user_id']]);
$user = $stmt->fetch();
$_SESSION['user'] = $user;

$currentTab = $_GET['tab'] ?? 'dashboard';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'book_appointment') {
        $facultyId = (int)($_POST['faculty_id'] ?? 0);
        $date      = trim($_POST['meeting_date'] ?? '');
        $startTime = trim($_POST['start_time'] ?? '');
        $endTime   = trim($_POST['end_time'] ?? '');
        $purpose   = trim($_POST['purpose'] ?? '');

        if ($facultyId <= 0 || empty($date) || empty($startTime) || empty($purpose)) {
            Flash::set('error', 'Please fill in all required fields.');
        } else {
            $meetingStart = "{$date} {$startTime}:00";
            $meetingEnd   = !empty($endTime) ? "{$date} {$endTime}:00" : null;

            if (strtotime($meetingStart) <= time()) {
                Flash::set('error', 'Please select a future meeting date and time.');
            } else {
                $stmt = $db->prepare("
                    INSERT INTO appointments (student_id, faculty_id, meeting_datetime, meeting_end_datetime, purpose, status, is_student_notified, is_faculty_notified)
                    VALUES (:sid, :fid, :start, :end, :purpose, 'Pending', 1, 0)
                ");
                $stmt->execute([
                    ':sid'     => $user['user_id'],
                    ':fid'     => $facultyId,
                    ':start'   => $meetingStart,
                    ':end'     => $meetingEnd,
                    ':purpose' => $purpose
                ]);
                Flash::set('success', 'Consultation request submitted successfully!');
                header('Location: student.php?tab=requests');
                exit;
            }
        }
        header('Location: student.php?tab=book');
        exit;
    }

    if ($action === 'cancel_request') {
        $appId = (int)($_POST['appointment_id'] ?? 0);
        if ($appId > 0) {
            $stmt = $db->prepare("
                UPDATE appointments 
                SET status = 'Cancelled' 
                WHERE appointment_id = :id AND student_id = :sid AND status IN ('Pending', 'Approved')
            ");
            $stmt->execute([':id' => $appId, ':sid' => $user['user_id']]);
            Flash::set('success', 'Meeting request cancelled successfully.');
        }
        header('Location: student.php?tab=requests');
        exit;
    }

    if ($action === 'update_profile') {
        $firstName = trim($_POST['first_name'] ?? '');
        $lastName  = trim($_POST['last_name'] ?? '');
        $idNum     = trim($_POST['id_number'] ?? '');
        $program   = trim($_POST['program'] ?? '');

        if (empty($firstName) || empty($lastName)) {
            Flash::set('error', 'First name and last name are required.');
        } else {
            $stmt = $db->prepare("UPDATE users SET first_name = :fn, last_name = :ln, id_number = :idn, program_or_dept = :prog WHERE user_id = :id");
            $stmt->execute([
                ':fn'   => $firstName,
                ':ln'   => $lastName,
                ':idn'  => $idNum,
                ':prog' => $program,
                ':id'   => $user['user_id']
            ]);
            Flash::set('success', 'Profile updated successfully!');
        }
        header('Location: student.php?tab=settings');
        exit;
    }

    if ($action === 'change_password') {
        $currentPass = trim($_POST['current_password'] ?? '');
        $newPass     = trim($_POST['new_password'] ?? '');
        $confirmPass = trim($_POST['confirm_password'] ?? '');

        if ($currentPass !== $user['user_password']) {
            Flash::set('error', 'Current password is incorrect.');
        } elseif (strlen($newPass) < 6) {
            Flash::set('error', 'New password must be at least 6 characters long.');
        } elseif ($newPass !== $confirmPass) {
            Flash::set('error', 'New passwords do not match.');
        } else {
            $stmt = $db->prepare("UPDATE users SET user_password = :pwd WHERE user_id = :id");
            $stmt->execute([':pwd' => $newPass, ':id' => $user['user_id']]);
            Flash::set('success', 'Password updated successfully!');
        }
        header('Location: student.php?tab=settings');
        exit;
    }
}

$stmtStats = $db->prepare("
    SELECT 
        COUNT(*) AS total_count,
        COUNT(CASE WHEN status = 'Pending' THEN 1 END) AS pending_count,
        COUNT(CASE WHEN status = 'Approved' THEN 1 END) AS approved_count,
        COUNT(CASE WHEN status = 'Completed' THEN 1 END) AS completed_count
    FROM appointments 
    WHERE student_id = :sid
");
$stmtStats->execute([':sid' => $user['user_id']]);
$stats = $stmtStats->fetch() ?: ['total_count' => 0, 'pending_count' => 0, 'approved_count' => 0, 'completed_count' => 0];

$stmtReq = $db->prepare("
    SELECT a.appointment_id,
           CONCAT(u.first_name, ' ', u.last_name) AS faculty_name,
           DATE_FORMAT(a.meeting_datetime, '%b %d, %Y') AS meeting_date,
           DATE_FORMAT(a.meeting_datetime, '%h:%i %p') AS start_time,
           IFNULL(DATE_FORMAT(a.meeting_end_datetime, '%h:%i %p'), 'N/A') AS end_time,
           a.purpose,
           a.status,
           IFNULL(a.faculty_remarks, '') AS faculty_remarks
    FROM appointments a
    INNER JOIN users u ON a.faculty_id = u.user_id
    WHERE a.student_id = :sid
    ORDER BY a.meeting_datetime DESC
");
$stmtReq->execute([':sid' => $user['user_id']]);
$myRequests = $stmtReq->fetchAll();

$facultyList = $db->query("SELECT user_id, CONCAT(first_name, ' ', last_name) AS name, program_or_dept FROM users WHERE role = 'Faculty' AND is_active = 1 ORDER BY last_name, first_name")->fetchAll();

$selectedFacultyId = (int)($_GET['faculty_select'] ?? ($facultyList[0]['user_id'] ?? 0));
$stmtAvail = $db->prepare("
    SELECT availability_id, days, 
           TIME_FORMAT(start_time, '%h:%i %p') AS start_fmt,
           TIME_FORMAT(end_time, '%h:%i %p') AS end_fmt,
           start_time, end_time, location
    FROM faculty_availability
    WHERE faculty_id = :fid
    ORDER BY FIELD(days, 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'), start_time
");
$stmtAvail->execute([':fid' => $selectedFacultyId]);
$facultySchedules = $stmtAvail->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Portal - DCSS Meeting Scheduler</title>
    <link rel="stylesheet" href="css/app.css">
</head>
<body>
    <header class="win-header-panel">
        <div class="win-brand">
            <img src="assets/dcss.jpg" alt="DCSS Logo" class="win-brand-logo" onerror="this.src='Resources/dcss.jpg'">
            <div>
                <div class="win-title">Student Portal</div>
                <div class="win-subtitle">Student Consultation & Appointment System</div>
            </div>
        </div>
        <div class="win-user-info">
            <span class="win-badge"><?= htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) ?> (Student)</span>
            <a href="logout.php" class="win-btn-logout" onclick="return confirm('Are you sure you want to log out?');">
                Log Out
            </a>
        </div>
    </header>

    <div class="win-container">
        <?= Flash::render() ?>

        <div class="win-tabcontrol">
            <div class="win-tab-strip">
                <a href="?tab=dashboard" class="win-tab-item <?= $currentTab === 'dashboard' ? 'active' : '' ?>">Dashboard</a>
                <a href="?tab=book" class="win-tab-item <?= $currentTab === 'book' ? 'active' : '' ?>">Book Consultation</a>
                <a href="?tab=requests" class="win-tab-item <?= $currentTab === 'requests' ? 'active' : '' ?>">My Requests</a>
                <a href="?tab=settings" class="win-tab-item <?= $currentTab === 'settings' ? 'active' : '' ?>">Profile & Settings</a>
            </div>

            <div class="win-tab-page">
                <?php if ($currentTab === 'dashboard'): ?>
                    <div class="win-section-header">
                        <h2>Student Dashboard</h2>
                        <p>Welcome back, <?= htmlspecialchars($user['first_name']) ?>! Overview of your consultations</p>
                    </div>

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
                        <div class="win-card-panel card-total">
                            <div class="win-card-label">Completed</div>
                            <div class="win-card-value"><?= (int)$stats['completed_count'] ?></div>
                        </div>
                    </div>

                    <div class="win-groupbox">
                        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:10px;">
                            <div class="win-groupbox-title" style="margin-bottom:0; border:none; padding:0;">
                                Recent Meeting Requests
                            </div>
                            <a href="?tab=book" class="win-btn win-btn-green">+ Book Consultation</a>
                        </div>

                        <div class="win-datagrid-wrap">
                            <table class="win-datagrid">
                                <thead>
                                    <tr>
                                        <th>Faculty Member</th>
                                        <th>Date</th>
                                        <th>Time</th>
                                        <th>Purpose</th>
                                        <th>Status</th>
                                        <th>Faculty Remarks</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($myRequests)): ?>
                                        <tr>
                                            <td colspan="6" style="text-align:center; padding:24px; color:#94a3b8;">
                                                No consultation requests yet.
                                            </td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach (array_slice($myRequests, 0, 5) as $req): ?>
                                            <tr>
                                                <td><strong><?= htmlspecialchars($req['faculty_name']) ?></strong></td>
                                                <td><?= htmlspecialchars($req['meeting_date']) ?></td>
                                                <td><?= htmlspecialchars($req['start_time']) ?></td>
                                                <td><?= htmlspecialchars($req['purpose']) ?></td>
                                                <td>
                                                    <span class="win-status-badge status-<?= strtolower(htmlspecialchars($req['status'])) ?>">
                                                        <?= htmlspecialchars($req['status']) ?>
                                                    </span>
                                                </td>
                                                <td><?= htmlspecialchars($req['faculty_remarks'] ?: '-') ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                <?php elseif ($currentTab === 'book'): ?>
                    <div class="win-section-header">
                        <h2>Book Consultation</h2>
                        <p>Select a faculty member and choose an available consultation schedule</p>
                    </div>

                    <div class="win-two-col">
                        <div class="win-groupbox">
                            <div class="win-groupbox-title">Consultation Request Form</div>
                            <form method="POST" action="student.php?tab=book">
                                <input type="hidden" name="action" value="book_appointment">

                                <div class="win-field-group">
                                    <label class="win-field-label" for="faculty_id">Select Faculty Member</label>
                                    <select name="faculty_id" id="faculty_id" class="win-combobox" style="width:100%;" 
                                            onchange="window.location.href='?tab=book&faculty_select=' + this.value" required>
                                        <?php foreach ($facultyList as $f): ?>
                                            <option value="<?= (int)$f['user_id'] ?>" <?= $selectedFacultyId === (int)$f['user_id'] ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($f['name']) ?> (<?= htmlspecialchars($f['program_or_dept'] ?: 'DCSS') ?>)
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="win-field-group">
                                    <label class="win-field-label" for="meeting_date">Meeting Date</label>
                                    <input type="date" name="meeting_date" id="meeting_date" class="win-textbox" style="width:100%;" 
                                           min="<?= date('Y-m-d') ?>" required>
                                </div>

                                <div style="display:grid; grid-template-columns:1fr 1fr; gap:8px;">
                                    <div class="win-field-group">
                                        <label class="win-field-label" for="start_time">Start Time</label>
                                        <input type="time" name="start_time" id="start_time" class="win-textbox" style="width:100%;" required>
                                    </div>
                                    <div class="win-field-group">
                                        <label class="win-field-label" for="end_time">End Time</label>
                                        <input type="time" name="end_time" id="end_time" class="win-textbox" style="width:100%;">
                                    </div>
                                </div>

                                <div class="win-field-group">
                                    <label class="win-field-label" for="purpose">Purpose / Subject</label>
                                    <textarea name="purpose" id="purpose" class="win-textbox" style="width:100%; height:75px; resize:vertical; padding:6px 10px;" 
                                              placeholder="e.g. Project inquiry, thesis consultation..." required></textarea>
                                </div>

                                <button type="submit" class="win-btn win-btn-green" style="width:100%; height:36px; margin-top:8px;">
                                    Submit Request
                                </button>
                            </form>
                        </div>

                        <div class="win-groupbox">
                            <div class="win-groupbox-title">Available Consultation Day</div>
                            <p style="font-size:12.5px; color:#64748b; margin-bottom:10px;">
                                Schedule set by the selected faculty member:
                            </p>

                            <div class="win-datagrid-wrap">
                                <table class="win-datagrid">
                                    <thead>
                                        <tr>
                                            <th>Day</th>
                                            <th>Hours</th>
                                            <th>Location</th>
                                            <th></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($facultySchedules)): ?>
                                            <tr>
                                                <td colspan="4" style="text-align:center; padding:24px; color:#94a3b8;">
                                                    No consultation hours posted by this faculty member.
                                                </td>
                                            </tr>
                                        <?php else: ?>
                                            <?php foreach ($facultySchedules as $sch): ?>
                                                <tr>
                                                    <td><strong><?= htmlspecialchars($sch['days']) ?></strong></td>
                                                    <td><?= htmlspecialchars($sch['start_fmt']) ?> - <?= htmlspecialchars($sch['end_fmt']) ?></td>
                                                    <td><?= htmlspecialchars($sch['location'] ?: 'Faculty Room') ?></td>
                                                    <td style="text-align:right;">
                                                        <button type="button" class="win-btn win-btn-blue" style="height:24px; padding:0 8px; font-size:11px;"
                                                                onclick="document.getElementById('start_time').value='<?= substr($sch['start_time'], 0, 5) ?>'; document.getElementById('end_time').value='<?= substr($sch['end_time'], 0, 5) ?>';">
                                                            Apply Time
                                                        </button>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                <?php elseif ($currentTab === 'requests'): ?>
                    <div class="win-section-header">
                        <h2>My Meeting Requests</h2>
                        <p>View all submitted appointment requests and check their current status</p>
                    </div>

                    <div class="win-datagrid-wrap">
                        <table class="win-datagrid">
                            <thead>
                                <tr>
                                    <th>Request ID</th>
                                    <th>Faculty Member</th>
                                    <th>Date</th>
                                    <th>Time</th>
                                    <th>Purpose</th>
                                    <th>Status</th>
                                    <th>Faculty Remarks</th>
                                    <th style="text-align:right;">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($myRequests)): ?>
                                    <tr>
                                        <td colspan="8" style="text-align:center; padding:30px; color:#94a3b8;">
                                            No consultation requests found.
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($myRequests as $r): ?>
                                        <tr>
                                            <td>#<?= htmlspecialchars($r['appointment_id']) ?></td>
                                            <td><strong><?= htmlspecialchars($r['faculty_name']) ?></strong></td>
                                            <td><?= htmlspecialchars($r['meeting_date']) ?></td>
                                            <td><?= htmlspecialchars($r['start_time']) ?> - <?= htmlspecialchars($r['end_time']) ?></td>
                                            <td><?= htmlspecialchars($r['purpose']) ?></td>
                                            <td>
                                                <span class="win-status-badge status-<?= strtolower(htmlspecialchars($r['status'])) ?>">
                                                    <?= htmlspecialchars($r['status']) ?>
                                                </span>
                                            </td>
                                            <td><?= htmlspecialchars($r['faculty_remarks'] ?: '-') ?></td>
                                            <td style="text-align:right;">
                                                <?php if (in_array($r['status'], ['Pending', 'Approved'], true)): ?>
                                                    <form method="POST" onsubmit="return confirm('Are you sure you want to cancel this request?');">
                                                        <input type="hidden" name="action" value="cancel_request">
                                                        <input type="hidden" name="appointment_id" value="<?= (int)$r['appointment_id'] ?>">
                                                        <button type="submit" class="win-btn win-btn-red" style="height:24px; padding:0 8px; font-size:11px;">Cancel</button>
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

                <?php elseif ($currentTab === 'settings'): ?>
                    <div class="win-section-header">
                        <h2>Profile & Settings</h2>
                        <p>Manage your account credentials and student details</p>
                    </div>

                    <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(360px, 1fr)); gap:16px;">
                        <div class="win-groupbox">
                            <div class="win-groupbox-title">Student Profile</div>
                            <form method="POST" action="student.php?tab=settings">
                                <input type="hidden" name="action" value="update_profile">

                                <div class="win-field-group">
                                    <label class="win-field-label" for="first_name">First Name</label>
                                    <input type="text" id="first_name" name="first_name" class="win-textbox" style="width:100%;" value="<?= htmlspecialchars($user['first_name']) ?>" required>
                                </div>
                                <div class="win-field-group">
                                    <label class="win-field-label" for="last_name">Last Name</label>
                                    <input type="text" id="last_name" name="last_name" class="win-textbox" style="width:100%;" value="<?= htmlspecialchars($user['last_name']) ?>" required>
                                </div>
                                <div class="win-field-group">
                                    <label class="win-field-label" for="id_number">Student ID Number</label>
                                    <input type="text" id="id_number" name="id_number" class="win-textbox" style="width:100%;" value="<?= htmlspecialchars($user['id_number'] ?? '') ?>">
                                </div>
                                <div class="win-field-group">
                                    <label class="win-field-label" for="program">Program / Degree</label>
                                    <input type="text" id="program" name="program" class="win-textbox" style="width:100%;" value="<?= htmlspecialchars($user['program_or_dept'] ?? 'BSCS') ?>">
                                </div>
                                <button type="submit" class="win-btn win-btn-green" style="width:100%; height:36px; margin-top:8px;">Save Profile</button>
                            </form>
                        </div>

                        <div class="win-groupbox">
                            <div class="win-groupbox-title">Change Password</div>
                            <form method="POST" action="student.php?tab=settings">
                                <input type="hidden" name="action" value="change_password">

                                <div class="win-field-group">
                                    <label class="win-field-label" for="current_password">Current Password</label>
                                    <input type="password" id="current_password" name="current_password" class="win-textbox" style="width:100%;" required>
                                </div>
                                <div class="win-field-group">
                                    <label class="win-field-label" for="new_password">New Password</label>
                                    <input type="password" id="new_password" name="new_password" class="win-textbox" style="width:100%;" minlength="6" required>
                                </div>
                                <div class="win-field-group">
                                    <label class="win-field-label" for="confirm_password">Confirm New Password</label>
                                    <input type="password" id="confirm_password" name="confirm_password" class="win-textbox" style="width:100%;" minlength="6" required>
                                </div>
                                <button type="submit" class="win-btn win-btn-blue" style="width:100%; height:36px; margin-top:8px;">Change Password</button>
                            </form>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>
