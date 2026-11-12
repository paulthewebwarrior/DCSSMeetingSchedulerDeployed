<?php
require_once __DIR__ . '/includes/Database.php';
require_once __DIR__ . '/includes/Auth.php';
require_once __DIR__ . '/includes/Flash.php';
require_once __DIR__ . '/includes/Actions.php';

$user = Auth::requireRole('Student');
$db = Database::getConnection();
$currentTab = Validation::choice($_GET, 'tab', ['dashboard', 'book', 'requests', 'settings'], 'dashboard');
Actions::handle($db, $user, 'student', $currentTab);

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

$oldFaculty = $_SESSION['old_input']['faculty_id'] ?? '';
$defaultFaculty = is_string($oldFaculty) && ctype_digit($oldFaculty) && in_array((int)$oldFaculty, array_map('intval', array_column($facultyList, 'user_id')), true)
    ? (int)$oldFaculty : (int)($facultyList[0]['user_id'] ?? 0);
$selectedFacultyId = isset($_GET['faculty_select']) ? Validation::id($_GET, 'faculty_select') : $defaultFaculty;
if ($selectedFacultyId && !in_array($selectedFacultyId, array_map('intval', array_column($facultyList, 'user_id')), true)) {
    throw new ValidationException('Select an active faculty member.');
}
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
    <title>DCSS - Student Portal</title>
    <link rel="stylesheet" href="css/app.css">
</head>
<body>
    <header class="app-header">
        <div class="header-brand">
            <img src="assets/dcss.jpg" alt="DCSS" class="brand-logo" onerror="this.style.display='none'">
            <div>
                <h1 class="brand-title">Student Portal</h1>
                <p class="brand-subtitle">DCSS Meeting & Consultation Scheduler</p>
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
            <a href="?tab=dashboard" class="tab-btn <?= $currentTab === 'dashboard' ? 'active' : '' ?>">Dashboard</a>
            <a href="?tab=book" class="tab-btn <?= $currentTab === 'book' ? 'active' : '' ?>">Book Consultation</a>
            <a href="?tab=requests" class="tab-btn <?= $currentTab === 'requests' ? 'active' : '' ?>">My Requests</a>
            <a href="?tab=settings" class="tab-btn <?= $currentTab === 'settings' ? 'active' : '' ?>">Profile & Settings</a>
        </nav>

        <div class="tab-content">
            <?php if ($currentTab === 'dashboard'): ?>
                <div class="section-banner">
                    <h2>Welcome, <?= htmlspecialchars($user['first_name']) ?>!</h2>
                    <p>Track your faculty consultation requests, status updates, and bookings</p>
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
                    <div class="kpi-card kpi-today">
                        <div class="kpi-title">Completed</div>
                        <div class="kpi-value"><?= (int)$stats['completed_count'] ?></div>
                    </div>
                </div>

                <div class="panel-card">
                    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:12px;">
                        <h3 class="panel-card-title" style="margin:0; border:none; padding:0;">Upcoming & Recent Consultations</h3>
                        <a href="?tab=book" class="btn btn-success">+ Book New Meeting</a>
                    </div>

                    <div class="table-responsive">
                        <table class="data-table">
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
                                        <td colspan="6" style="text-align:center; padding:30px; color:var(--text-muted);">
                                            No consultation requests yet. Click "+ Book New Meeting" to schedule one.
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
                                                <span class="badge badge-<?= strtolower(htmlspecialchars($req['status'])) ?>">
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
                <div class="section-banner">
                    <h2>Book a Consultation</h2>
                    <p>Select a faculty member, check their weekly availability, and submit an appointment request</p>
                </div>

                <div class="two-column-layout">
                    <div class="panel-card">
                        <h3 class="panel-card-title">Schedule Request</h3>
                        <?php if (!$facultyList): ?>
                            <p role="status">No faculty members are currently available. Please contact the administrator or check again later.</p>
                        <?php endif; ?>
                        <p class="form-hint">All times are Philippine time. Choose a start and end time within the faculty member's available hours.</p>
                        <form method="POST" action="student.php?tab=book">
                            <?= App::csrfField() ?>
                            <input type="hidden" name="action" value="book_appointment">

                            <div class="form-group">
                                <label class="form-label" for="faculty_id">Faculty Member</label>
                                <select name="faculty_id" id="faculty_id" class="form-control" 
                                        onchange="window.location.href='?tab=book&faculty_select=' + this.value" required>
                                    <?php foreach ($facultyList as $f): ?>
                                        <option value="<?= (int)$f['user_id'] ?>" <?= $selectedFacultyId === (int)$f['user_id'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($f['name']) ?> (<?= htmlspecialchars($f['program_or_dept'] ?: 'DCSS') ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="form-group">
                                <label class="form-label" for="meeting_date">Meeting Date</label>
                                <input type="date" name="meeting_date" id="meeting_date" class="form-control" 
                                       min="<?= date('Y-m-d') ?>" required>
                            </div>

                            <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px;">
                                <div class="form-group">
                                    <label class="form-label" for="start_time">Start Time</label>
                                    <input type="time" name="start_time" id="start_time" class="form-control" required>
                                </div>
                                <div class="form-group">
                                    <label class="form-label" for="end_time">End Time</label>
                                    <input required type="time" name="end_time" id="end_time" class="form-control">
                                </div>
                            </div>

                            <div class="form-group">
                                <label class="form-label" for="purpose">Consultation Purpose / Topic</label>
                                <textarea maxlength="255" name="purpose" id="purpose" class="form-control" rows="3" 
                                          placeholder="e.g. Thesis topic review, consultation regarding midterm exam, project guidance..." required></textarea>
                            </div>

                            <button type="submit" class="btn btn-success" style="width:100%; padding:11px;">
                                Submit Consultation Request
                            </button>
                        </form>
                    </div>

                    <div class="panel-card">
                        <h3 class="panel-card-title">Available Consultation Hours</h3>
                        <p style="font-size:13px; color:var(--text-secondary); margin-bottom:12px;">
                            Below are the designated office hours for the selected faculty member:
                        </p>

                        <div class="table-responsive">
                            <table class="data-table">
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
                                            <td colspan="4" style="text-align:center; padding:30px; color:var(--text-muted);">
                                                This faculty member has not posted any available consultation hours yet.
                                            </td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($facultySchedules as $sch): ?>
                                            <tr>
                                                <td><strong><?= htmlspecialchars($sch['days']) ?></strong></td>
                                                <td><?= htmlspecialchars($sch['start_fmt']) ?> - <?= htmlspecialchars($sch['end_fmt']) ?></td>
                                                <td><?= htmlspecialchars($sch['location'] ?: 'Faculty Room') ?></td>
                                                <td>
                                                    <button type="button" class="btn btn-outline" style="padding:4px 8px; font-size:11px;"
                                                            onclick="document.getElementById('start_time').value='<?= substr($sch['start_time'], 0, 5) ?>'; document.getElementById('end_time').value='<?= substr($sch['end_time'], 0, 5) ?>';">
                                                        Use Time
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
                <div class="section-banner">
                    <h2>My Consultation Requests</h2>
                    <p>All your submitted appointment requests and their current approval status</p>
                </div>

                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Request ID</th>
                                <th>Faculty</th>
                                <th>Date</th>
                                <th>Time Range</th>
                                <th>Purpose</th>
                                <th>Status</th>
                                <th>Remarks</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($myRequests)): ?>
                                <tr>
                                    <td colspan="8" style="text-align:center; padding:30px; color:var(--text-muted);">
                                        No consultation requests submitted yet.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($myRequests as $r): ?>
                                    <tr>
                                        <td><strong>#<?= htmlspecialchars($r['appointment_id']) ?></strong></td>
                                        <td><strong><?= htmlspecialchars($r['faculty_name']) ?></strong></td>
                                        <td><?= htmlspecialchars($r['meeting_date']) ?></td>
                                        <td><?= htmlspecialchars($r['start_time']) ?> - <?= htmlspecialchars($r['end_time']) ?></td>
                                        <td><?= htmlspecialchars($r['purpose']) ?></td>
                                        <td>
                                            <span class="badge badge-<?= strtolower(htmlspecialchars($r['status'])) ?>">
                                                <?= htmlspecialchars($r['status']) ?>
                                            </span>
                                        </td>
                                        <td><?= htmlspecialchars($r['faculty_remarks'] ?: '-') ?></td>
                                        <td>
                                            <?php if (in_array($r['status'], ['Pending', 'Approved'], true)): ?>
                                                <form method="POST" onsubmit="return confirm('Cancel this meeting request?');">
                            <?= App::csrfField() ?>
                                                    <input type="hidden" name="action" value="cancel_request">
                                                    <input type="hidden" name="appointment_id" value="<?= (int)$r['appointment_id'] ?>">
                                                    <button type="submit" class="btn btn-danger" style="padding:4px 10px; font-size:12px;">Cancel</button>
                                                </form>
                                            <?php else: ?>
                                                <span style="color:var(--text-muted); font-size:12px;">-</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

            <?php elseif ($currentTab === 'settings'): ?>
                <div class="section-banner">
                    <h2>Student Profile & Settings</h2>
                    <p>Manage your account credentials and personal information</p>
                </div>

                <div class="settings-grid">
                    <div class="panel-card">
                        <h3 class="panel-card-title">Personal Profile</h3>
                        <form method="POST" action="student.php?tab=settings">
                            <?= App::csrfField() ?>
                            <input type="hidden" name="action" value="update_profile">

                            <div class="form-group">
                                <label class="form-label" for="first_name">First Name</label>
                                <input maxlength="50" type="text" id="first_name" name="first_name" class="form-control" value="<?= htmlspecialchars($user['first_name']) ?>" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label" for="last_name">Last Name</label>
                                <input maxlength="50" type="text" id="last_name" name="last_name" class="form-control" value="<?= htmlspecialchars($user['last_name']) ?>" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label" for="id_number">Student ID Number</label>
                                <input maxlength="20" type="text" id="id_number" name="id_number" class="form-control" value="<?= htmlspecialchars($user['id_number'] ?? '') ?>">
                            </div>
                            <div class="form-group">
                                <label class="form-label" for="program">Program / Degree</label>
                                <input maxlength="50" type="text" id="program" name="program" class="form-control" value="<?= htmlspecialchars($user['program_or_dept'] ?? 'BSCS') ?>">
                            </div>
                            <button type="submit" class="btn btn-success" style="width:100%;">Save Profile</button>
                        </form>
                    </div>

                    <div class="panel-card">
                        <h3 class="panel-card-title">Change Password</h3>
                        <form method="POST" action="student.php?tab=settings">
                            <?= App::csrfField() ?>
                            <input type="hidden" name="action" value="change_password">

                            <div class="form-group">
                                <label class="form-label" for="current_password">Current Password</label>
                                <input autocomplete="current-password" type="password" id="current_password" name="current_password" class="form-control" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label" for="new_password">New Password</label>
                                <input autocomplete="new-password" maxlength="64" type="password" id="new_password" name="new_password" class="form-control" minlength="6" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label" for="confirm_password">Confirm New Password</label>
                                <input autocomplete="new-password" maxlength="64" type="password" id="confirm_password" name="confirm_password" class="form-control" minlength="6" required>
                            </div>
                            <button type="submit" class="btn btn-primary" style="width:100%;">Change Password</button>
                        </form>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </main>
    <?= App::formState() ?>
    <script src="assets/validation.js" defer></script>
</body>
</html>

