using System.Data;
using DCSSMeetingScheduler.Shared;
using MySqlConnector;

namespace DCSSMeetingScheduler.Forms.Student
{
    public partial class StudentForm : Form {
        private User? loggedUser = null;
        private bool isLoggingOut = false;

        public StudentForm() {
            InitializeComponent();
        }

        public StudentForm(User user) {
            InitializeComponent();
            loggedUser = user;
        }

        private void Student_Load(object sender, EventArgs e) {
            if (loggedUser == null) {
                try {
                    using (MySqlConnection conn = Connection.GetConnection()) {
                        string query = "SELECT * FROM users WHERE role = 'Student' LIMIT 1";
                        using (MySqlCommand cmd = new MySqlCommand(query, conn)) {
                            using (MySqlDataReader reader = cmd.ExecuteReader()) {
                                if (reader.Read()) {
                                    loggedUser = User.FromReader(reader);
                                }
                            }
                        }
                    }
                } catch {
                }
            }

            dtpMeetingDate.MinDate = DateTime.Today;
            dtpMeetingDate.Value = DateTime.Today;
            dtpStartTime.Value = DateTime.Today.AddHours(9);
            dtpEndTime.Value = DateTime.Today.AddHours(10);

            LoadKPI();
            LoadNotifications();
            LoadMeetingRequests();
            LoadFaculty();
            LoadStudentProfile();
            InitializeSettings();
        }

        private void LoadKPI() {
            User? currentUser = loggedUser;
            if (currentUser == null) {
                return;
            }

            string kpiQuery = "SELECT COUNT(*) AS TotalCount, COUNT(CASE WHEN status = 'Approved' THEN 1 END) AS ApprovedCount, COUNT(CASE WHEN status = 'Pending' THEN 1 END) AS PendingCount, COUNT(CASE WHEN status = 'Completed' THEN 1 END) AS CompletedCount FROM appointments WHERE student_id = @StudentId";
            try {
                using (MySqlConnection conn = Connection.GetConnection()) {
                    using (MySqlCommand cmd = new MySqlCommand(kpiQuery, conn)) {
                        cmd.Parameters.AddWithValue("@StudentId", currentUser.UserId);
                        using (MySqlDataReader reader = cmd.ExecuteReader()) {
                            if (reader.Read()) {
                                lblTotalCount.Text = reader["TotalCount"].ToString();
                                lblApprovedCount.Text = reader["ApprovedCount"].ToString();
                                lblPendingCount.Text = reader["PendingCount"].ToString();
                                lblCompletedCount.Text = reader["CompletedCount"].ToString();
                            }
                        }
                    }
                }
            } catch (Exception ex) {
                MessageBox.Show("Error loading statistics: " + ex.Message, "Database Error", MessageBoxButtons.OK, MessageBoxIcon.Error);
            }
        }

        private void LoadNotifications() {
            User? currentUser = loggedUser;
            if (currentUser == null) {
                pnlNotification.Visible = false;
                return;
            }

            string notificationQuery = "SELECT a.appointment_id, CONCAT(u.first_name, ' ', u.last_name) AS faculty_name, a.status, a.faculty_remarks FROM appointments a INNER JOIN users u ON u.user_id = a.faculty_id WHERE a.student_id = @StudentId AND a.is_student_notified = 0 ORDER BY a.updated_at DESC";

            try {
                using (MySqlConnection conn = Connection.GetConnection()) {
                    using (MySqlCommand cmd = new MySqlCommand(notificationQuery, conn)) {
                        cmd.Parameters.AddWithValue("@StudentId", currentUser.UserId);
                        using (MySqlDataReader reader = cmd.ExecuteReader()) {
                            int unreadCount = 0;
                            string latestFacultyName = "";
                            string latestStatus = "";
                            string latestRemarks = "";

                            while (reader.Read()) {
                                if (unreadCount == 0) {
                                    latestFacultyName = reader["faculty_name"].ToString() ?? "";
                                    latestStatus = reader["status"].ToString() ?? "";
                                    int remarksOrdinal = reader.GetOrdinal("faculty_remarks");
                                    if (!reader.IsDBNull(remarksOrdinal)) {
                                        latestRemarks = reader.GetString(remarksOrdinal);
                                    }
                                }
                                unreadCount++;
                            }

                            if (unreadCount > 0) {
                                pnlNotification.Visible = true;
                                btnMarkAsRead.Enabled = true;

                                if (unreadCount == 1) {
                                    string message = "Your meeting request with " + latestFacultyName + " has been " + latestStatus + ".";
                                    if (!string.IsNullOrEmpty(latestRemarks)) {
                                        message += " Remarks: " + latestRemarks;
                                    }
                                    lblNotification.Text = message;
                                } else {
                                    lblNotification.Text = "You have " + unreadCount + " new meeting updates. Latest: Meeting with " + latestFacultyName + " was " + latestStatus + ".";
                                }
                            } else {
                                pnlNotification.Visible = false;
                                btnMarkAsRead.Enabled = false;
                            }
                        }
                    }
                }
            } catch {
                pnlNotification.Visible = false;
            }
        }

        private void btnMarkAsRead_Click(object? sender, EventArgs e) {
            User? currentUser = loggedUser;
            if (currentUser == null) {
                return;
            }

            try {
                using (MySqlConnection conn = Connection.GetConnection()) {
                    string updateQuery = "UPDATE appointments SET is_student_notified = 1 WHERE student_id = @StudentId AND is_student_notified = 0";
                    using (MySqlCommand cmd = new MySqlCommand(updateQuery, conn)) {
                        cmd.Parameters.AddWithValue("@StudentId", currentUser.UserId);
                        cmd.ExecuteNonQuery();
                    }
                }

                LoadNotifications();
                MessageBox.Show("Notifications marked as read.", "Notifications", MessageBoxButtons.OK, MessageBoxIcon.Information);
            } catch (Exception ex) {
                MessageBox.Show("Error updating notifications: " + ex.Message, "Database Error", MessageBoxButtons.OK, MessageBoxIcon.Error);
            }
        }

        private void LoadMeetingRequests() {
            User? currentUser = loggedUser;
            if (currentUser == null) {
                return;
            }

            string loadRequestsQuery = "SELECT a.appointment_id, CONCAT(u.first_name, ' ', u.last_name) AS faculty_name, DATE_FORMAT(a.meeting_datetime, '%b %d, %Y') AS meeting_date, DATE_FORMAT(a.meeting_datetime, '%h:%i %p') AS start_time, IFNULL(DATE_FORMAT(a.meeting_end_datetime, '%h:%i %p'), 'N/A') AS end_time, a.status, IFNULL(a.faculty_remarks, '') AS faculty_remarks FROM appointments a INNER JOIN users u ON u.user_id = a.faculty_id WHERE a.student_id = @StudentId ORDER BY a.meeting_datetime DESC";

            dgvMyRequests.AutoGenerateColumns = false;
            request_id.DataPropertyName = "appointment_id";
            faculty_name.DataPropertyName = "faculty_name";
            meeting_date.DataPropertyName = "meeting_date";
            start_time.DataPropertyName = "start_time";
            end_time.DataPropertyName = "end_time";
            status.DataPropertyName = "status";
            faculty_remarks.DataPropertyName = "faculty_remarks";

            try {
                using (MySqlConnection conn = Connection.GetConnection()) {
                    using (MySqlCommand cmd = new MySqlCommand(loadRequestsQuery, conn)) {
                        cmd.Parameters.AddWithValue("@StudentId", currentUser.UserId);
                        using (MySqlDataReader reader = cmd.ExecuteReader()) {
                            DataTable table = new DataTable();
                            table.Load(reader);
                            dgvMyRequests.DataSource = table;
                        }
                    }
                }
            } catch (Exception ex) {
                MessageBox.Show("Error loading meeting requests: " + ex.Message, "Database Error", MessageBoxButtons.OK, MessageBoxIcon.Error);
            }
        }

        private void btnCancelRequest_Click(object? sender, EventArgs e) {
            User? currentUser = loggedUser;
            if (currentUser == null) {
                MessageBox.Show("Please log in first.", "Validation", MessageBoxButtons.OK, MessageBoxIcon.Warning);
                return;
            }

            DataGridViewRow? selectedRow = dgvMyRequests.CurrentRow;
            if (selectedRow == null || selectedRow.Index < 0) {
                MessageBox.Show("Please select a meeting request to cancel.", "Selection Required", MessageBoxButtons.OK, MessageBoxIcon.Warning);
                return;
            }

            DataGridViewCell? requestIdCell = selectedRow.Cells["request_id"];
            if (requestIdCell == null || requestIdCell.Value == null) {
                MessageBox.Show("Invalid request selection.", "Error", MessageBoxButtons.OK, MessageBoxIcon.Error);
                return;
            }
            int appointmentId = Convert.ToInt32(requestIdCell.Value);

            DataGridViewCell? statusCell = selectedRow.Cells["status"];
            string currentStatus = statusCell != null && statusCell.Value != null ? statusCell.Value.ToString()! : "";

            if (currentStatus == "Cancelled") {
                MessageBox.Show("This meeting request has already been cancelled.", "Cannot Cancel", MessageBoxButtons.OK, MessageBoxIcon.Information);
                return;
            }

            if (currentStatus == "Completed") {
                MessageBox.Show("Completed meetings cannot be cancelled.", "Cannot Cancel", MessageBoxButtons.OK, MessageBoxIcon.Warning);
                return;
            }

            if (currentStatus == "Declined") {
                MessageBox.Show("Declined meetings cannot be cancelled.", "Cannot Cancel", MessageBoxButtons.OK, MessageBoxIcon.Warning);
                return;
            }

            DialogResult confirmation = MessageBox.Show("Are you sure you want to cancel this meeting request?", "Confirm Cancellation", MessageBoxButtons.YesNo, MessageBoxIcon.Question);
            if (confirmation != DialogResult.Yes) {
                return;
            }

            try {
                using (MySqlConnection conn = Connection.GetConnection()) {
                    string cancelQuery = "UPDATE appointments SET status = 'Cancelled' WHERE appointment_id = @appointmentId AND student_id = @studentId";
                    using (MySqlCommand cmd = new MySqlCommand(cancelQuery, conn)) {
                        cmd.Parameters.AddWithValue("@appointmentId", appointmentId);
                        cmd.Parameters.AddWithValue("@studentId", currentUser.UserId);
                        cmd.ExecuteNonQuery();
                    }
                }

                MessageBox.Show("Meeting request cancelled successfully.", "Success", MessageBoxButtons.OK, MessageBoxIcon.Information);
                LoadKPI();
                LoadMeetingRequests();
                LoadNotifications();
            } catch (Exception ex) {
                MessageBox.Show("Error cancelling meeting request: " + ex.Message, "Database Error", MessageBoxButtons.OK, MessageBoxIcon.Error);
            }
        }

        private void LoadFaculty() {
            string facultyQuery = "SELECT user_id, CONCAT(first_name, ' ', last_name) AS faculty_name FROM users WHERE role = 'Faculty' AND is_active = 1 ORDER BY last_name, first_name, user_id";
            DataTable facultyTable = new DataTable();

            try {
                using (MySqlConnection conn = Connection.GetConnection()) {
                    using (MySqlCommand cmd = new MySqlCommand(facultyQuery, conn)) {
                        using (MySqlDataReader reader = cmd.ExecuteReader()) {
                            facultyTable.Load(reader);
                        }
                    }
                }

                cmbFaculty.DisplayMember = "faculty_name";
                cmbFaculty.ValueMember = "user_id";
                cmbFaculty.DataSource = facultyTable;

                if (facultyTable.Rows.Count > 0) {
                    LoadAvailabilityHours();
                }
            } catch (Exception ex) {
                MessageBox.Show("Error loading faculty members: " + ex.Message, "Database Error", MessageBoxButtons.OK, MessageBoxIcon.Error);
            }
        }

        private void cmbFaculty_SelectedIndexChanged(object? sender, EventArgs e) {
            LoadAvailabilityHours();
        }

        private void LoadAvailabilityHours() {
            dgvFacultyHours.Rows.Clear();

            DataRowView? selectedFaculty = cmbFaculty.SelectedItem as DataRowView;
            if (selectedFaculty == null) {
                return;
            }

            string query = "SELECT days, start_time, end_time, location FROM faculty_availability WHERE faculty_id = @FacultyId ORDER BY FIELD(days, 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'), start_time";

            try {
                using (MySqlConnection conn = Connection.GetConnection()) {
                    using (MySqlCommand cmd = new MySqlCommand(query, conn)) {
                        cmd.Parameters.AddWithValue("@FacultyId", selectedFaculty["user_id"]);
                        using (MySqlDataReader reader = cmd.ExecuteReader()) {
                            while (reader.Read()) {
                                DateTime startTime = DateTime.Today.Add(reader.GetTimeSpan("start_time"));
                                DateTime endTime = DateTime.Today.Add(reader.GetTimeSpan("end_time"));
                                string location = "";
                                int locationOrdinal = reader.GetOrdinal("location");
                                if (!reader.IsDBNull(locationOrdinal)) {
                                    location = reader.GetString(locationOrdinal);
                                }

                                dgvFacultyHours.Rows.Add(
                                    reader.GetString("days"),
                                    startTime.ToString("hh:mm tt"),
                                    endTime.ToString("hh:mm tt"),
                                    location
                                );
                            }
                        }
                    }
                }
            } catch (MySqlException ex) {
                dgvFacultyHours.Rows.Clear();
                MessageBox.Show("Unable to load consultation hours: " + ex.Message, "Database Error", MessageBoxButtons.OK, MessageBoxIcon.Error);
            }
        }

        private void dgvFacultyHours_CellClick(object? sender, DataGridViewCellEventArgs e) {
            if (e.RowIndex < 0 || e.RowIndex >= dgvFacultyHours.Rows.Count) {
                return;
            }

            DataGridViewRow selectedRow = dgvFacultyHours.Rows[e.RowIndex];
            if (selectedRow == null) {
                return;
            }

            DataGridViewCell? dayCell = selectedRow.Cells["colDay"];
            string dayName = dayCell != null && dayCell.Value != null ? dayCell.Value.ToString()! : "";

            DataGridViewCell? startCell = selectedRow.Cells["colStartTime"];
            string startTimeText = startCell != null && startCell.Value != null ? startCell.Value.ToString()! : "";

            DataGridViewCell? endCell = selectedRow.Cells["colEndTime"];
            string endTimeText = endCell != null && endCell.Value != null ? endCell.Value.ToString()! : "";

            DateTime parsedStartTime;
            if (DateTime.TryParse(startTimeText, out parsedStartTime)) {
                dtpStartTime.Value = parsedStartTime;
            }

            DateTime parsedEndTime;
            if (DateTime.TryParse(endTimeText, out parsedEndTime)) {
                dtpEndTime.Value = parsedEndTime;
            }

            if (!string.IsNullOrEmpty(dayName)) {
                DateTime nextDate = DateTime.Today;
                for (int i = 0; i < 7; i++) {
                    DateTime checkDate = DateTime.Today.AddDays(i);
                    if (checkDate.DayOfWeek.ToString().Equals(dayName, StringComparison.OrdinalIgnoreCase)) {
                        if (i == 0 && parsedStartTime.TimeOfDay <= DateTime.Now.TimeOfDay) {
                            nextDate = DateTime.Today.AddDays(7);
                        } else {
                            nextDate = checkDate;
                        }
                        break;
                    }
                }

                if (nextDate >= dtpMeetingDate.MinDate) {
                    dtpMeetingDate.Value = nextDate;
                }
            }
        }

        private void btnSubmitRequest_Click(object sender, EventArgs e) {
            User? currentUser = loggedUser;
            if (currentUser == null) {
                MessageBox.Show("Please log in before submitting a request.", "Validation", MessageBoxButtons.OK, MessageBoxIcon.Warning);
                return;
            }

            DataRowView? selectedFaculty = cmbFaculty.SelectedItem as DataRowView;
            if (selectedFaculty == null) {
                MessageBox.Show("Please select a faculty member.", "Validation", MessageBoxButtons.OK, MessageBoxIcon.Warning);
                cmbFaculty.Focus();
                return;
            }

            string purpose = txtPurposeOfTheMeeting.Text.Trim();
            if (purpose.Length == 0 || purpose.Length > 255) {
                MessageBox.Show("Please enter a meeting purpose of 1 to 255 characters.", "Validation", MessageBoxButtons.OK, MessageBoxIcon.Warning);
                txtPurposeOfTheMeeting.Focus();
                return;
            }

            DateTime meetingDate = dtpMeetingDate.Value.Date;
            DateTime meetingStart = meetingDate.AddHours(dtpStartTime.Value.Hour).AddMinutes(dtpStartTime.Value.Minute);
            DateTime meetingEnd = meetingDate.AddHours(dtpEndTime.Value.Hour).AddMinutes(dtpEndTime.Value.Minute);

            if (meetingStart <= DateTime.Now) {
                MessageBox.Show("Please select a future meeting date and start time.", "Validation", MessageBoxButtons.OK, MessageBoxIcon.Warning);
                dtpMeetingDate.Focus();
                return;
            }

            if (meetingEnd <= meetingStart) {
                MessageBox.Show("End time must be after start time on the same day.", "Validation", MessageBoxButtons.OK, MessageBoxIcon.Warning);
                dtpEndTime.Focus();
                return;
            }

            int facultyId = Convert.ToInt32(selectedFaculty["user_id"]);
            btnSubmitRequest.Enabled = false;

            try {
                using (MySqlConnection conn = Connection.GetConnection()) {
                    using (MySqlTransaction transaction = conn.BeginTransaction()) {
                        string lockQuery = "SELECT user_id FROM users WHERE user_id IN (@StudentId, @FacultyId) ORDER BY user_id FOR UPDATE";
                        using (MySqlCommand cmd = new MySqlCommand(lockQuery, conn, transaction)) {
                            cmd.Parameters.AddWithValue("@StudentId", currentUser.UserId);
                            cmd.Parameters.AddWithValue("@FacultyId", facultyId);
                            using (MySqlDataReader reader = cmd.ExecuteReader()) {
                                while (reader.Read()) {
                                }
                            }
                        }

                        string availabilityQuery = "SELECT COUNT(*) FROM faculty_availability f INNER JOIN users u ON u.user_id = f.faculty_id WHERE f.faculty_id = @FacultyId AND u.role = 'Faculty' AND u.is_active = 1 AND f.days = @Day AND f.start_time <= @StartTime AND f.end_time >= @EndTime";
                        using (MySqlCommand cmd = new MySqlCommand(availabilityQuery, conn, transaction)) {
                            cmd.Parameters.AddWithValue("@FacultyId", facultyId);
                            cmd.Parameters.AddWithValue("@Day", meetingDate.DayOfWeek.ToString());
                            cmd.Parameters.AddWithValue("@StartTime", meetingStart.TimeOfDay);
                            cmd.Parameters.AddWithValue("@EndTime", meetingEnd.TimeOfDay);
                            if (Convert.ToInt64(cmd.ExecuteScalar()) == 0) {
                                MessageBox.Show("Select a time range within the faculty member's consultation hours.", "Unavailable Time", MessageBoxButtons.OK, MessageBoxIcon.Warning);
                                return;
                            }
                        }

                        string conflictQuery = "SELECT COUNT(*) FROM appointments WHERE (faculty_id = @FacultyId OR student_id = @StudentId) AND status IN ('Pending', 'Approved') AND meeting_datetime < @MeetingEnd AND (meeting_end_datetime > @MeetingStart OR (meeting_end_datetime IS NULL AND meeting_datetime >= @MeetingStart))";
                        using (MySqlCommand cmd = new MySqlCommand(conflictQuery, conn, transaction)) {
                            cmd.Parameters.AddWithValue("@FacultyId", facultyId);
                            cmd.Parameters.AddWithValue("@StudentId", currentUser.UserId);
                            cmd.Parameters.AddWithValue("@MeetingStart", meetingStart);
                            cmd.Parameters.AddWithValue("@MeetingEnd", meetingEnd);
                            if (Convert.ToInt64(cmd.ExecuteScalar()) > 0) {
                                MessageBox.Show("You or the faculty member already have a pending or approved meeting during this time.", "Scheduling Conflict", MessageBoxButtons.OK, MessageBoxIcon.Warning);
                                return;
                            }
                        }

                        string insertQuery = "INSERT INTO appointments (student_id, faculty_id, meeting_datetime, meeting_end_datetime, purpose, status, is_student_notified, is_faculty_notified) VALUES (@StudentId, @FacultyId, @MeetingStart, @MeetingEnd, @Purpose, 'Pending', 1, 0)";
                        using (MySqlCommand cmd = new MySqlCommand(insertQuery, conn, transaction)) {
                            cmd.Parameters.AddWithValue("@StudentId", currentUser.UserId);
                            cmd.Parameters.AddWithValue("@FacultyId", facultyId);
                            cmd.Parameters.AddWithValue("@MeetingStart", meetingStart);
                            cmd.Parameters.AddWithValue("@MeetingEnd", meetingEnd);
                            cmd.Parameters.AddWithValue("@Purpose", purpose);
                            cmd.ExecuteNonQuery();
                        }

                        transaction.Commit();
                    }
                }
            } catch (MySqlException ex) {
                MessageBox.Show("Unable to save the meeting request: " + ex.Message, "Database Error", MessageBoxButtons.OK, MessageBoxIcon.Error);
                return;
            } finally {
                btnSubmitRequest.Enabled = true;
            }

            txtPurposeOfTheMeeting.Clear();
            MessageBox.Show("Meeting request submitted successfully. It is pending faculty approval.", "Request Submitted", MessageBoxButtons.OK, MessageBoxIcon.Information);

            try {
                LoadKPI();
                LoadMeetingRequests();
                LoadNotifications();
                tabStudent.SelectedIndex = 0;
            } catch (MySqlException ex) {
                MessageBox.Show("Your request was saved, but the dashboard could not be refreshed: " + ex.Message, "Refresh Error", MessageBoxButtons.OK, MessageBoxIcon.Warning);
            }
        }

        private void LoadStudentProfile() {
            User? currentUser = loggedUser;
            if (currentUser == null) {
                return;
            }

            txtStudentName.Text = currentUser.FirstName + " " + currentUser.LastName;
            txtStudentId.Text = currentUser.IdNumber;
            txtStudentEmail.Text = currentUser.UeEmail;
            txtStudentProgram.Text = currentUser.ProgramOrDept;
            txtStudentEmail.ReadOnly = true;

            if (cmbYearLevel.SelectedIndex < 0) {
                cmbYearLevel.SelectedIndex = 0;
            }
        }

        private void btnSaveSettings_Click(object? sender, EventArgs e) {
            User? currentUser = loggedUser;
            if (currentUser == null) {
                MessageBox.Show("Please log in first.", "Validation", MessageBoxButtons.OK, MessageBoxIcon.Warning);
                return;
            }

            string fullName = txtStudentName.Text.Trim();
            string idNumber = txtStudentId.Text.Trim();
            string program = txtStudentProgram.Text.Trim();

            if (string.IsNullOrWhiteSpace(fullName)) {
                MessageBox.Show("Please enter your name.", "Validation Error", MessageBoxButtons.OK, MessageBoxIcon.Warning);
                txtStudentName.Focus();
                return;
            }

            if (string.IsNullOrWhiteSpace(idNumber)) {
                MessageBox.Show("Please enter your student ID.", "Validation Error", MessageBoxButtons.OK, MessageBoxIcon.Warning);
                txtStudentId.Focus();
                return;
            }

            if (string.IsNullOrWhiteSpace(program)) {
                MessageBox.Show("Please enter your program or course.", "Validation Error", MessageBoxButtons.OK, MessageBoxIcon.Warning);
                txtStudentProgram.Focus();
                return;
            }

            string firstName = fullName;
            string lastName = "";

            int spaceIndex = fullName.IndexOf(' ');
            if (spaceIndex > 0) {
                firstName = fullName.Substring(0, spaceIndex);
                lastName = fullName.Substring(spaceIndex + 1).Trim();
            }

            try {
                using (MySqlConnection conn = Connection.GetConnection()) {
                    string updateQuery = "UPDATE users SET first_name = @firstName, last_name = @lastName, id_number = @idNumber, program_or_dept = @program WHERE user_id = @userId";
                    using (MySqlCommand cmd = new MySqlCommand(updateQuery, conn)) {
                        cmd.Parameters.AddWithValue("@firstName", firstName);
                        cmd.Parameters.AddWithValue("@lastName", lastName);
                        cmd.Parameters.AddWithValue("@idNumber", idNumber);
                        cmd.Parameters.AddWithValue("@program", program);
                        cmd.Parameters.AddWithValue("@userId", currentUser.UserId);
                        cmd.ExecuteNonQuery();
                    }
                }

                currentUser.FirstName = firstName;
                currentUser.LastName = lastName;
                currentUser.IdNumber = idNumber;
                currentUser.ProgramOrDept = program;

                MessageBox.Show("Profile updated successfully!", "Success", MessageBoxButtons.OK, MessageBoxIcon.Information);
            } catch (Exception ex) {
                MessageBox.Show("Error updating profile: " + ex.Message, "Database Error", MessageBoxButtons.OK, MessageBoxIcon.Error);
            }
        }

        private void btnCancelSettings_Click(object? sender, EventArgs e) {
            LoadStudentProfile();
            RestoreSettings();
            MessageBox.Show("Changes reverted.", "Cancelled", MessageBoxButtons.OK, MessageBoxIcon.Information);
        }

        private void Dashboard_FormClosed(object? sender, FormClosedEventArgs e) {
            if (Owner is LoginForm loginForm)
                loginForm.DashboardClosed(this);
        }

        private void btnLogout_Click(object? sender, EventArgs e) {
            DialogResult result = MessageBox.Show("Are you sure you want to log out?", "Log Out", MessageBoxButtons.YesNo, MessageBoxIcon.Question);
            if (result == DialogResult.Yes) {
                isLoggingOut = true;
                DialogResult = DialogResult.Retry;
                Close();
            }
        }

        private void StudentForm_FormClosed(object? sender, FormClosedEventArgs e) {
            if (isLoggingOut == false) {
                // The login form owns the application lifetime.
            }
        }

        private void tabPage3_Click(object sender, EventArgs e) {
        }

        private void label3_Click(object sender, EventArgs e) {
        }

        private void label3_Click_1(object sender, EventArgs e) {
        }

        private void label4_Click(object sender, EventArgs e) {
        }

        private void label1_Click(object sender, EventArgs e) {
        }

        private void label6_Click(object sender, EventArgs e) {
        }

        private void label7_Click(object sender, EventArgs e) {
        }

        private void label8_Click(object sender, EventArgs e) {
        }

        private void label9_Click(object sender, EventArgs e) {
        }

        private void label10_Click(object sender, EventArgs e) {
        }

        private void dgvMyRequests_CellContentClick(object sender, DataGridViewCellEventArgs e) {
        }

        private void pnlHomeTitle_Paint(object sender, PaintEventArgs e) {
        }

        private void tabPage2_Click(object sender, EventArgs e) {
        }

        private void tabPage2_Click_1(object sender, EventArgs e) {
        }

        private void pblHeader_Paint(object sender, PaintEventArgs e) {
        }

        private void pnlProfile_Paint(object sender, PaintEventArgs e) {
        }

        private void pnlStudentProfile_Paint(object sender, PaintEventArgs e) {
        }

        private void tableLayoutPanel2_Paint(object sender, PaintEventArgs e) {
        }

        private void pnlAccountSettings_Paint(object sender, PaintEventArgs e) {

        }

        private void pnlPreferences_Paint(object sender, PaintEventArgs e) {

        }

        private void dtpStartTime_ValueChanged(object sender, EventArgs e) {

        }

        private void tabPage3_Click_1(object sender, EventArgs e) {

        }
    }
}

