<?php
require_once __DIR__ . '/Flash.php';

final class Actions {
    public static function handle(PDO $db, array $user, string $page, string $tab): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return;
        }
        $action = is_string($_POST['action'] ?? null) ? $_POST['action'] : '';
        $tabs = $page === 'student' ? [
            'book_appointment' => 'book', 'cancel_request' => 'requests',
            'update_profile' => 'settings', 'change_password' => 'settings',
        ] : [
            'save_availability' => 'availability', 'delete_availability' => 'availability',
            'update_request_status' => 'request', 'update_profile' => 'setting', 'change_password' => 'setting',
        ];
        $target = $tabs[$action] ?? $tab;
        try {
            App::verifyPost();
            if (!isset($tabs[$action])) {
                throw new ValidationException('Unknown action. Refresh the page and try again.');
            }
            // Lock participating accounts in ID order to serialize scheduling changes.
            $ids = [(int)$user['user_id']];
            if ($action === 'book_appointment') {
                $ids[] = Validation::id($_POST, 'faculty_id');
            }
            if ($action === 'update_request_status') {
                $stmt = $db->prepare('SELECT student_id FROM appointments WHERE appointment_id = ? AND faculty_id = ?');
                $stmt->execute([Validation::id($_POST, 'appointment_id'), $user['user_id']]);
                $studentId = $stmt->fetchColumn();
                if ($studentId !== false) {
                    $ids[] = (int)$studentId;
                }
            }
            $db->beginTransaction();
            sort($ids, SORT_NUMERIC);
            foreach (array_unique($ids) as $id) {
                $lock = $db->prepare('SELECT * FROM users WHERE user_id = ? FOR UPDATE');
                $lock->execute([$id]);
                $account = $lock->fetch();
                if (!$account || !$account['is_active']) {
                    throw new ValidationException('The selected account is no longer available.');
                }
                if ($id === (int)$user['user_id']) {
                    if ($account['role'] !== ($page === 'student' ? 'Student' : 'Faculty')) {
                        throw new ValidationException('Your account role has changed. Refresh the page and try again.');
                    }
                    $user = $account;
                }
            }
            switch ($action) {
                case 'update_profile':
                    if ($page === 'student') {
                        $first = Validation::text($_POST, 'first_name', 'First name', 50);
                        $last = Validation::text($_POST, 'last_name', 'Last name', 50);
                        $idNumber = Validation::text($_POST, 'id_number', 'ID number', 20, false);
                        $dept = Validation::text($_POST, 'program', 'Program', 50, false);
                        $stmt = $db->prepare('UPDATE users SET first_name = ?, last_name = ?, id_number = ?, program_or_dept = ? WHERE user_id = ?');
                        $stmt->execute([$first, $last, $idNumber, $dept, $user['user_id']]);
                    } else {
                        $name = Validation::text($_POST, 'full_name', 'Full name', 101);
                        $parts = preg_split('/\s+/u', $name, 2);
                        $first = Validation::text(['first' => $parts[0]], 'first', 'First name', 50);
                        $last = Validation::text(['last' => $parts[1] ?? ''], 'last', 'Last name', 50);
                        $dept = Validation::text($_POST, 'department', 'Department', 50);
                        $stmt = $db->prepare('UPDATE users SET first_name = ?, last_name = ?, program_or_dept = ? WHERE user_id = ?');
                        $stmt->execute([$first, $last, $dept, $user['user_id']]);
                    }
                    $message = 'Profile saved successfully.';
                    break;
                case 'change_password':
                    $current = Validation::password($_POST, 'current_password', 'Current password');
                    $new = Validation::password($_POST, 'new_password', 'New password', true);
                    $confirm = Validation::password($_POST, 'confirm_password', 'Confirm password');
                    if (!App::verifyPassword($current, $user['user_password'])) {
                        throw new ValidationException('Current password is incorrect.');
                    }
                    if ($current === $new) {
                        throw new ValidationException('Choose a new password different from your current password.');
                    }
                    if ($new !== $confirm) {
                        throw new ValidationException('New password and confirm password do not match.');
                    }
                    $stmt = $db->prepare('UPDATE users SET user_password = ?, must_change_password = 0 WHERE user_id = ?');
                    $stmt->execute([$new, $user['user_id']]);
                    $message = 'Password changed successfully.';
                    break;
                case 'book_appointment':
                    $facultyId = Validation::id($_POST, 'faculty_id');
                    [$start, $end, $day] = Validation::meeting($_POST);
                    $purpose = Validation::text($_POST, 'purpose', 'Consultation purpose', 255);
                    $stmt = $db->prepare("SELECT user_id FROM users WHERE user_id = ? AND role = 'Faculty' AND is_active = 1");
                    $stmt->execute([$facultyId]);
                    if (!$stmt->fetch()) {
                        throw new ValidationException('Select an active faculty member.');
                    }
                    $stmt = $db->prepare('SELECT availability_id FROM faculty_availability WHERE faculty_id = ? AND days = ? AND start_time <= TIME(?) AND end_time >= TIME(?) LIMIT 1');
                    $stmt->execute([$facultyId, $day, $start, $end]);
                    if (!$stmt->fetch()) {
                        throw new ValidationException('Choose a meeting entirely within the faculty member\'s available hours for that day.');
                    }
                    $stmt = $db->prepare("SELECT appointment_id FROM appointments WHERE (faculty_id = ? OR student_id = ?) AND status IN ('Pending', 'Approved') AND meeting_datetime < ? AND COALESCE(meeting_end_datetime, DATE_ADD(meeting_datetime, INTERVAL 30 MINUTE)) > ? LIMIT 1");
                    $stmt->execute([$facultyId, $user['user_id'], $end, $start]);
                    if ($stmt->fetch()) {
                        throw new ValidationException('This time conflicts with an existing request for you or the faculty member. Choose another time.');
                    }
                    $stmt = $db->prepare("INSERT INTO appointments (student_id, faculty_id, meeting_datetime, meeting_end_datetime, purpose, status, is_student_notified, is_faculty_notified) VALUES (?, ?, ?, ?, ?, 'Pending', 1, 0)");
                    $stmt->execute([$user['user_id'], $facultyId, $start, $end, $purpose]);
                    $target = 'requests';
                    $message = 'Consultation request submitted successfully.';
                    break;
                case 'cancel_request':
                    $id = Validation::id($_POST, 'appointment_id');
                    $stmt = $db->prepare("UPDATE appointments SET status = 'Cancelled', is_faculty_notified = 0 WHERE appointment_id = ? AND student_id = ? AND status IN ('Pending', 'Approved') AND meeting_datetime > NOW()");
                    $stmt->execute([$id, $user['user_id']]);
                    self::requireChange($stmt, 'This request cannot be cancelled. It may have changed, already started, or be unavailable.');
                    $message = 'Meeting request cancelled successfully.';
                    break;
                case 'save_availability':
                    $id = ($_POST['availability_id'] ?? '') === '-1' ? -1 : Validation::id($_POST, 'availability_id');
                    $day = Validation::choice($_POST, 'day', ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday']);
                    $start = Validation::time($_POST, 'start_time', 'Start time');
                    $end = Validation::time($_POST, 'end_time', 'End time');
                    $location = Validation::text($_POST, 'location', 'Location', 100, false);
                    if ($start >= $end) {
                        throw new ValidationException('End time must be later than start time.');
                    }
                    if ($id !== -1) {
                        $stmt = $db->prepare('SELECT availability_id FROM faculty_availability WHERE availability_id = ? AND faculty_id = ?');
                        $stmt->execute([$id, $user['user_id']]);
                        if (!$stmt->fetch()) {
                            throw new ValidationException('These consultation hours no longer exist or are unavailable.');
                        }
                    }
                    $stmt = $db->prepare('SELECT availability_id FROM faculty_availability WHERE faculty_id = ? AND days = ? AND availability_id <> ? AND start_time < ? AND end_time > ? LIMIT 1');
                    $stmt->execute([$user['user_id'], $day, $id, $end, $start]);
                    if ($stmt->fetch()) {
                        throw new ValidationException('These hours overlap another availability slot. Edit that slot or choose a different time.');
                    }
                    if ($id === -1) {
                        $stmt = $db->prepare('INSERT INTO faculty_availability (faculty_id, days, start_time, end_time, location) VALUES (?, ?, ?, ?, ?)');
                        $stmt->execute([$user['user_id'], $day, $start, $end, $location]);
                    } else {
                        $stmt = $db->prepare('UPDATE faculty_availability SET days = ?, start_time = ?, end_time = ?, location = ? WHERE availability_id = ? AND faculty_id = ?');
                        $stmt->execute([$day, $start, $end, $location, $id, $user['user_id']]);
                    }
                    $message = 'Consultation hours saved successfully. Existing appointments keep their scheduled times.';
                    break;
                case 'delete_availability':
                    $id = Validation::id($_POST, 'availability_id');
                    $stmt = $db->prepare('DELETE FROM faculty_availability WHERE availability_id = ? AND faculty_id = ?');
                    $stmt->execute([$id, $user['user_id']]);
                    self::requireChange($stmt, 'These consultation hours have already been deleted or are unavailable.');
                    $message = 'Consultation hours deleted. Existing appointments keep their scheduled times.';
                    break;
                case 'update_request_status':
                    $id = Validation::id($_POST, 'appointment_id');
                    $status = Validation::choice($_POST, 'status', ['Approved', 'Declined', 'Completed']);
                    $stmt = $db->prepare('SELECT * FROM appointments WHERE appointment_id = ? AND faculty_id = ? FOR UPDATE');
                    $stmt->execute([$id, $user['user_id']]);
                    $appointment = $stmt->fetch();
                    $expected = $status === 'Completed' ? 'Approved' : 'Pending';
                    if (!$appointment || $appointment['status'] !== $expected) {
                        throw new ValidationException('This request has changed or is unavailable. Refresh the list before trying again.');
                    }
                    if ($status === 'Completed' && strtotime($appointment['meeting_end_datetime'] ?? $appointment['meeting_datetime']) > time()) {
                        throw new ValidationException('A meeting can only be marked completed after its scheduled end.');
                    }
                    if ($status === 'Approved') {
                        if (strtotime($appointment['meeting_datetime']) <= time()) {
                            throw new ValidationException('Past meetings cannot be approved. Ask the student to submit a new request.');
                        }
                        $stmt = $db->prepare("SELECT appointment_id FROM appointments WHERE appointment_id <> ? AND (faculty_id = ? OR student_id = ?) AND status = 'Approved' AND meeting_datetime < ? AND COALESCE(meeting_end_datetime, DATE_ADD(meeting_datetime, INTERVAL 30 MINUTE)) > ? LIMIT 1");
                        $stmt->execute([$id, $user['user_id'], $appointment['student_id'], $appointment['meeting_end_datetime'] ?? date('Y-m-d H:i:s', strtotime($appointment['meeting_datetime']) + 1800), $appointment['meeting_datetime']]);
                        if ($stmt->fetch()) {
                            throw new ValidationException('This request conflicts with an approved meeting for you or the student.');
                        }
                    }
                    $stmt = $db->prepare('UPDATE appointments SET status = ?, is_student_notified = 0 WHERE appointment_id = ? AND faculty_id = ? AND status = ?');
                    $stmt->execute([$status, $id, $user['user_id'], $expected]);
                    self::requireChange($stmt, 'The request changed. Refresh the page and try again.');
                    $message = "Request marked as {$status}.";
                    break;
            }
            $db->commit();
            unset($_SESSION['old_input']);
            Flash::set('success', $message);
        } catch (ValidationException | PDOException $error) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            App::rememberInput();
            Flash::set('error', $error instanceof ValidationException ? $error->getMessage() : App::databaseError($error));
        }
        header('Location: ' . $page . '.php?tab=' . urlencode($target), true, 303);
        exit;
    }

    private static function requireChange(PDOStatement $stmt, string $message): void {
        if ($stmt->rowCount() !== 1) {
            throw new ValidationException($message);
        }
    }
}
