using System;
using System.Data;
using System.Drawing;
using System.Windows.Forms;
using DCSSMeetingScheduler;
using DCSSMeetingScheduler.Shared;
using MySqlConnector;

namespace DCSSMeetingScheduler.Faculty
{
    public partial class FacultyForm : Form
    {
        private User? loggedInUser = null;
        private int selectedAvailabilityId = -1;
        private static int defaultTabIndex = 0;

        public FacultyForm()
        {
            InitializeComponent();
        }

        public FacultyForm(User user)
        {
            InitializeComponent();
            loggedInUser = user;
        }

        private void Faculty_Load(object sender, EventArgs e)
        {
            LoadFacultyProfile();
            LoadApplicationInfo();
            InitializePreferences();
            InitializeRequestFilters();
            LoadAvailability();
            LoadRequests();
            LoadHomePage();

            tabFaculty.SelectedIndexChanged += tabFaculty_SelectedIndexChanged;

            if (defaultTabIndex >= 0 && defaultTabIndex < tabFaculty.TabCount)
            {
                tabFaculty.SelectedIndex = defaultTabIndex;
            }
        }

        private void tabFaculty_SelectedIndexChanged(object? sender, EventArgs e)
        {
            if (tabFaculty.SelectedIndex == 0)
            {
                LoadHomePage();
            }
            else if (tabFaculty.SelectedIndex == 1)
            {
                LoadRequests();
            }
            else if (tabFaculty.SelectedIndex == 2)
            {
                LoadAvailability();
            }
        }

        private void LoadHomePage()
        {
            UpdateDashboardCounts();
            LoadRecentMeetings();
            UpdateHomeNotifications();
        }

        private void LoadRecentMeetings()
        {
            if (loggedInUser == null)
            {
                return;
            }

            try
            {
                using (MySqlConnection conn = Connection.GetConnection())
                {
                    string query = "SELECT a.appointment_id, " +
                                   "CONCAT(u.first_name, ' ', u.last_name) AS student_name, " +
                                   "DATE_FORMAT(a.meeting_datetime, '%b %d, %Y') AS meeting_date, " +
                                   "DATE_FORMAT(a.meeting_datetime, '%h:%i %p') AS start_time, " +
                                   "IFNULL(DATE_FORMAT(a.meeting_end_datetime, '%h:%i %p'), 'N/A') AS end_time, " +
                                   "a.status, " +
                                   "IFNULL(a.faculty_remarks, '') AS faculty_remarks " +
                                   "FROM appointments a " +
                                   "INNER JOIN users u ON a.student_id = u.user_id " +
                                   "WHERE a.faculty_id = @facultyId " +
                                   "ORDER BY a.meeting_datetime DESC " +
                                   "LIMIT 10";

                    using (MySqlCommand cmd = new MySqlCommand(query, conn))
                    {
                        cmd.Parameters.AddWithValue("@facultyId", loggedInUser.UserId);

                        using (MySqlDataAdapter da = new MySqlDataAdapter(cmd))
                        {
                            DataTable dt = new DataTable();
                            da.Fill(dt);

                            dataGridView2.AutoGenerateColumns = false;
                            request_id.DataPropertyName = "appointment_id";
                            Stud_Name.DataPropertyName = "student_name";
                            Date.DataPropertyName = "meeting_date";
                            Start.DataPropertyName = "start_time";
                            End.DataPropertyName = "end_time";
                            MStatus.DataPropertyName = "status";
                            FRemarks.DataPropertyName = "faculty_remarks";
                            dataGridView2.DataSource = dt;
                        }
                    }
                }
            }
            catch (Exception ex)
            {
                MessageBox.Show("Error loading recent meetings: " + ex.Message, "Error", MessageBoxButtons.OK, MessageBoxIcon.Error);
            }
        }

        private void UpdateHomeNotifications()
        {
            if (loggedInUser == null)
            {
                return;
            }

            try
            {
                using (MySqlConnection conn = Connection.GetConnection())
                {
                    string query = "SELECT COUNT(*) FROM appointments WHERE faculty_id = @facultyId AND status = 'Pending'";
                    using (MySqlCommand cmd = new MySqlCommand(query, conn))
                    {
                        cmd.Parameters.AddWithValue("@facultyId", loggedInUser.UserId);
                        int pendingCount = Convert.ToInt32(cmd.ExecuteScalar());

                        if (pendingCount > 0)
                        {
                            lblNotifs.Text = "You have " + pendingCount + " pending meeting request(s) awaiting your review.";
                        }
                        else
                        {
                            lblNotifs.Text = "You have no pending meeting requests.";
                        }
                    }
                }
            }
            catch
            {
            }
        }

        private void LoadFacultyProfile()
        {
            if (loggedInUser == null)
            {
                try
                {
                    using (MySqlConnection conn = Connection.GetConnection())
                    {
                        string query = "SELECT * FROM users WHERE role = 'Faculty' LIMIT 1";
                        using (MySqlCommand cmd = new MySqlCommand(query, conn))
                        {
                            using (MySqlDataReader reader = cmd.ExecuteReader())
                            {
                                if (reader.Read())
                                {
                                    loggedInUser = User.FromReader(reader);
                                }
                            }
                        }
                    }
                }
                catch
                {
                }
            }

            if (loggedInUser != null)
            {
                txtFacultyName.Text = loggedInUser.FirstName + " " + loggedInUser.LastName;
                txtFacultyEmail.Text = loggedInUser.UeEmail;
                txtDepartment.Text = loggedInUser.ProgramOrDept;
                txtPosition.Text = loggedInUser.Role.ToString();
            }
        }

        private void btnEditProfile_Click(object sender, EventArgs e)
        {
            if (btnEditProfile.Text == "Edit Profile")
            {
                txtFacultyName.ReadOnly = false;
                txtDepartment.ReadOnly = false;
                txtFacultyName.Focus();

                btnEditProfile.Text = "Save Profile";
                btnEditProfile.BackColor = Color.ForestGreen;
            }
            else if (btnEditProfile.Text == "Save Profile")
            {
                string fullName = txtFacultyName.Text.Trim();
                string department = txtDepartment.Text.Trim();

                if (string.IsNullOrWhiteSpace(fullName))
                {
                    MessageBox.Show("Please enter your name.", "Validation Error", MessageBoxButtons.OK, MessageBoxIcon.Warning);
                    txtFacultyName.Focus();
                    return;
                }

                if (string.IsNullOrWhiteSpace(department))
                {
                    MessageBox.Show("Please enter your department.", "Validation Error", MessageBoxButtons.OK, MessageBoxIcon.Warning);
                    txtDepartment.Focus();
                    return;
                }

                string firstName = fullName;
                string lastName = "";

                int spaceIndex = fullName.IndexOf(' ');
                if (spaceIndex > 0)
                {
                    firstName = fullName.Substring(0, spaceIndex);
                    lastName = fullName.Substring(spaceIndex + 1).Trim();
                }

                try
                {
                    if (loggedInUser != null)
                    {
                        using (MySqlConnection conn = Connection.GetConnection())
                        {
                            string updateQuery = "UPDATE users SET first_name = @firstName, last_name = @lastName, program_or_dept = @department WHERE user_id = @userId";

                            using (MySqlCommand cmd = new MySqlCommand(updateQuery, conn))
                            {
                                cmd.Parameters.AddWithValue("@firstName", firstName);
                                cmd.Parameters.AddWithValue("@lastName", lastName);
                                cmd.Parameters.AddWithValue("@department", department);
                                cmd.Parameters.AddWithValue("@userId", loggedInUser.UserId);

                                cmd.ExecuteNonQuery();
                            }
                        }

                        loggedInUser.FirstName = firstName;
                        loggedInUser.LastName = lastName;
                        loggedInUser.ProgramOrDept = department;
                    }

                    txtFacultyName.ReadOnly = true;
                    txtDepartment.ReadOnly = true;

                    btnEditProfile.Text = "Edit Profile";
                    btnEditProfile.BackColor = Color.SeaGreen;

                    MessageBox.Show("Profile updated successfully!", "Success", MessageBoxButtons.OK, MessageBoxIcon.Information);
                }
                catch (Exception ex)
                {
                    MessageBox.Show("Error updating profile: " + ex.Message, "Database Error", MessageBoxButtons.OK, MessageBoxIcon.Error);
                }
            }
        }

        private void btnChangePassword_Click(object sender, EventArgs e)
        {
            string currentPassword = txtCurrentPassword.Text.Trim();
            string newPassword = txtNewPassword.Text.Trim();
            string confirmPassword = txtConfirmPassword.Text.Trim();

            if (string.IsNullOrWhiteSpace(currentPassword) ||
                string.IsNullOrWhiteSpace(newPassword) ||
                string.IsNullOrWhiteSpace(confirmPassword))
            {
                MessageBox.Show("Please fill in all password fields.", "Validation", MessageBoxButtons.OK, MessageBoxIcon.Warning);
                return;
            }

            if (loggedInUser != null && currentPassword != loggedInUser.UserPassword)
            {
                MessageBox.Show("Current password is incorrect.", "Validation", MessageBoxButtons.OK, MessageBoxIcon.Error);
                txtCurrentPassword.Focus();
                return;
            }

            if (newPassword.Length < 6)
            {
                MessageBox.Show("New password must be at least 6 characters long.", "Validation", MessageBoxButtons.OK, MessageBoxIcon.Warning);
                txtNewPassword.Focus();
                return;
            }

            if (newPassword == currentPassword)
            {
                MessageBox.Show("New password cannot be the same as your current password.", "Validation", MessageBoxButtons.OK, MessageBoxIcon.Warning);
                txtNewPassword.Focus();
                return;
            }

            if (newPassword != confirmPassword)
            {
                MessageBox.Show("New password and confirm password do not match.", "Validation", MessageBoxButtons.OK, MessageBoxIcon.Warning);
                txtConfirmPassword.Focus();
                return;
            }

            try
            {
                if (loggedInUser != null)
                {
                    using (MySqlConnection conn = Connection.GetConnection())
                    {
                        string updatePasswordQuery = "UPDATE users SET user_password = @password, must_change_password = 0 WHERE user_id = @userId";

                        using (MySqlCommand cmd = new MySqlCommand(updatePasswordQuery, conn))
                        {
                            cmd.Parameters.AddWithValue("@password", newPassword);
                            cmd.Parameters.AddWithValue("@userId", loggedInUser.UserId);

                            cmd.ExecuteNonQuery();
                        }
                    }

                    loggedInUser.UserPassword = newPassword;
                    loggedInUser.MustChangePassword = false;
                }

                MessageBox.Show("Password changed successfully!", "Success", MessageBoxButtons.OK, MessageBoxIcon.Information);

                txtCurrentPassword.Clear();
                txtNewPassword.Clear();
                txtConfirmPassword.Clear();
            }
            catch (Exception ex)
            {
                MessageBox.Show("Database Error: " + ex.Message, "Error", MessageBoxButtons.OK, MessageBoxIcon.Error);
            }
        }

        private void LoadApplicationInfo()
        {
            lblVersionValue.Text = "1.0.0";
            lblDatabaseValue.Text = "Connected (dcss_db)";
            lblLastLoginValue.Text = DateTime.Now.ToString("MMM dd, yyyy hh:mm tt");
        }

        private void InitializePreferences()
        {
            cmbDefaultTab.DropDownStyle = ComboBoxStyle.DropDownList;
            cmbDefaultTab.Items.Clear();
            cmbDefaultTab.Items.Add("Home");
            cmbDefaultTab.Items.Add("Request");
            cmbDefaultTab.Items.Add("Availability");
            cmbDefaultTab.Items.Add("Setting");

            if (defaultTabIndex >= 0 && defaultTabIndex < cmbDefaultTab.Items.Count)
            {
                cmbDefaultTab.SelectedIndex = defaultTabIndex;
            }
            else
            {
                cmbDefaultTab.SelectedIndex = 0;
            }

            chkEmailNotif.Checked = true;
            chkDesktopNotif.Checked = true;
            chkConsultationReminder.Checked = true;

            chkShowRequestDashboard.Checked = true;
            chkShowRecentRequestDashboard.Checked = true;
        }

        private void btnSavePreferences_Click(object sender, EventArgs e)
        {
            if (cmbDefaultTab.SelectedIndex >= 0)
            {
                defaultTabIndex = cmbDefaultTab.SelectedIndex;
                tabFaculty.SelectedIndex = defaultTabIndex;
            }

            grpRecentMeet.Visible = chkShowRecentRequestDashboard.Checked;
            grpOverview.Visible = chkShowRequestDashboard.Checked;

            MessageBox.Show("Preferences saved successfully!", "Preferences", MessageBoxButtons.OK, MessageBoxIcon.Information);
        }

        private void btnRefreshPreferences_Click(object sender, EventArgs e)
        {
            defaultTabIndex = 0;
            cmbDefaultTab.SelectedIndex = 0;
            tabFaculty.SelectedIndex = 0;

            chkEmailNotif.Checked = true;
            chkDesktopNotif.Checked = true;
            chkConsultationReminder.Checked = true;

            chkShowRequestDashboard.Checked = true;
            chkShowRecentRequestDashboard.Checked = true;

            grpRecentMeet.Visible = true;
            grpOverview.Visible = true;

            MessageBox.Show("Preferences reset to default values.", "Preferences", MessageBoxButtons.OK, MessageBoxIcon.Information);
        }

        private void cmbDefaultTab_SelectedIndexChanged(object? sender, EventArgs e)
        {
            if (cmbDefaultTab.SelectedIndex >= 0)
            {
                defaultTabIndex = cmbDefaultTab.SelectedIndex;
            }
        }

        private void InitializeRequestFilters()
        {
            comboBox2.DropDownStyle = ComboBoxStyle.DropDownList;
            comboBox2.Items.Clear();
            comboBox2.Items.Add("All");
            comboBox2.Items.Add("Pending");
            comboBox2.Items.Add("Approved");
            comboBox2.Items.Add("Declined");
            comboBox2.Items.Add("Completed");
            comboBox2.SelectedIndex = 0;

            button2.Click += button2_Click;
            button3.Click += button3_Click;
            button4.Click += button4_Click;
            btnRefresh.Click += btnRefresh_Click;
            textBox3.TextChanged += textBox3_TextChanged;
            comboBox2.SelectedIndexChanged += comboBox2_SelectedIndexChanged;
        }

        private void LoadRequests()
        {
            if (loggedInUser == null)
            {
                return;
            }

            string search = textBox3.Text.Trim();
            string statusFilter = comboBox2.SelectedItem != null ? comboBox2.SelectedItem.ToString()! : "All";

            try
            {
                using (MySqlConnection conn = Connection.GetConnection())
                {
                    string query = "SELECT a.appointment_id, " +
                                   "CONCAT(u.first_name, ' ', u.last_name) AS student_name, " +
                                   "IFNULL(u.program_or_dept, 'N/A') AS program_or_dept, " +
                                   "a.purpose, " +
                                   "DATE_FORMAT(a.meeting_datetime, '%b %d, %Y %h:%i %p') AS request_datetime, " +
                                   "a.status, " +
                                   "IFNULL(a.faculty_remarks, '') AS faculty_remarks " +
                                   "FROM appointments a " +
                                   "INNER JOIN users u ON a.student_id = u.user_id " +
                                   "WHERE a.faculty_id = @facultyId";

                    if (!string.IsNullOrWhiteSpace(statusFilter) && statusFilter != "All")
                    {
                        query += " AND a.status = @status";
                    }

                    if (!string.IsNullOrWhiteSpace(search))
                    {
                        query += " AND (u.first_name LIKE @search OR u.last_name LIKE @search OR CONCAT(u.first_name, ' ', u.last_name) LIKE @search OR u.program_or_dept LIKE @search OR a.purpose LIKE @search)";
                    }

                    query += " ORDER BY a.meeting_datetime DESC";

                    using (MySqlCommand cmd = new MySqlCommand(query, conn))
                    {
                        cmd.Parameters.AddWithValue("@facultyId", loggedInUser.UserId);

                        if (!string.IsNullOrWhiteSpace(statusFilter) && statusFilter != "All")
                        {
                            cmd.Parameters.AddWithValue("@status", statusFilter);
                        }

                        if (!string.IsNullOrWhiteSpace(search))
                        {
                            cmd.Parameters.AddWithValue("@search", "%" + search + "%");
                        }

                        using (MySqlDataAdapter da = new MySqlDataAdapter(cmd))
                        {
                            DataTable dt = new DataTable();
                            da.Fill(dt);

                            Requests.AutoGenerateColumns = false;
                            Req_ID.DataPropertyName = "appointment_id";
                            Sname.DataPropertyName = "student_name";
                            Subj.DataPropertyName = "program_or_dept";
                            Purp.DataPropertyName = "purpose";
                            D_and_T.DataPropertyName = "request_datetime";
                            Stat.DataPropertyName = "status";
                            FacultyRem.DataPropertyName = "faculty_remarks";
                            Requests.DataSource = dt;
                        }
                    }
                }

                UpdateDashboardCounts();
            }
            catch (Exception ex)
            {
                MessageBox.Show("Error loading requests: " + ex.Message, "Error", MessageBoxButtons.OK, MessageBoxIcon.Error);
            }
        }

        private void UpdateDashboardCounts()
        {
            if (loggedInUser == null)
            {
                return;
            }

            try
            {
                using (MySqlConnection conn = Connection.GetConnection())
                {
                    string query = "SELECT " +
                                   "COUNT(*) AS total_count, " +
                                   "COUNT(CASE WHEN status = 'Pending' THEN 1 END) AS pending_count, " +
                                   "COUNT(CASE WHEN status = 'Approved' THEN 1 END) AS approved_count, " +
                                   "COUNT(CASE WHEN status = 'Declined' THEN 1 END) AS declined_count, " +
                                   "COUNT(CASE WHEN status = 'Approved' AND DATE(meeting_datetime) = CURDATE() THEN 1 END) AS today_count, " +
                                   "COUNT(CASE WHEN status = 'Approved' AND meeting_datetime >= NOW() THEN 1 END) AS upcoming_count " +
                                   "FROM appointments WHERE faculty_id = @facultyId";

                    using (MySqlCommand cmd = new MySqlCommand(query, conn))
                    {
                        cmd.Parameters.AddWithValue("@facultyId", loggedInUser.UserId);
                        using (MySqlDataReader reader = cmd.ExecuteReader())
                        {
                            if (reader.Read())
                            {
                                string total = reader["total_count"].ToString() ?? "0";
                                string pending = reader["pending_count"].ToString() ?? "0";
                                string approved = reader["approved_count"].ToString() ?? "0";
                                string declined = reader["declined_count"].ToString() ?? "0";
                                string today = reader["today_count"].ToString() ?? "0";
                                string upcoming = reader["upcoming_count"].ToString() ?? "0";

                                lblTotal.Text = total;
                                lblPendingTotal.Text = pending;
                                lblApprovedTotal.Text = approved;
                                lblDeclinedTotal.Text = declined;
                                lblTotalMeeting.Text = today;
                                lblTotalUpMeet.Text = upcoming;

                                label12.Text = total;
                                label13.Text = pending;
                                label14.Text = approved;
                                label15.Text = declined;
                            }
                        }
                    }
                }
            }
            catch
            {
            }
        }

        private int GetSelectedRequestId()
        {
            if (Requests.CurrentRow != null && Requests.CurrentRow.Index >= 0)
            {
                object? val = Requests.CurrentRow.Cells["Req_ID"].Value;
                if (val != null && int.TryParse(val.ToString(), out int id))
                {
                    return id;
                }
            }

            if (dataGridView2.CurrentRow != null && dataGridView2.CurrentRow.Index >= 0)
            {
                object? val = dataGridView2.CurrentRow.Cells["request_id"].Value;
                if (val != null && int.TryParse(val.ToString(), out int id))
                {
                    return id;
                }
            }

            return -1;
        }

        private void UpdateRequestStatus(int appointmentId, string status)
        {
            if (loggedInUser == null)
            {
                return;
            }

            try
            {
                using (MySqlConnection conn = Connection.GetConnection())
                {
                    string query = "UPDATE appointments SET status = @status, is_student_notified = 0 WHERE appointment_id = @id AND faculty_id = @facultyId";
                    using (MySqlCommand cmd = new MySqlCommand(query, conn))
                    {
                        cmd.Parameters.AddWithValue("@status", status);
                        cmd.Parameters.AddWithValue("@id", appointmentId);
                        cmd.Parameters.AddWithValue("@facultyId", loggedInUser.UserId);
                        cmd.ExecuteNonQuery();
                    }
                }

                MessageBox.Show("Request status updated to " + status + "!", "Success", MessageBoxButtons.OK, MessageBoxIcon.Information);
                LoadRequests();
                LoadHomePage();
            }
            catch (Exception ex)
            {
                MessageBox.Show("Error updating request: " + ex.Message, "Error", MessageBoxButtons.OK, MessageBoxIcon.Error);
            }
        }

        private void button2_Click(object? sender, EventArgs e)
        {
            int id = GetSelectedRequestId();
            if (id == -1)
            {
                MessageBox.Show("Please select a request first.", "Notice", MessageBoxButtons.OK, MessageBoxIcon.Information);
                return;
            }

            DialogResult result = MessageBox.Show("Are you sure you want to approve this request?", "Confirm", MessageBoxButtons.YesNo, MessageBoxIcon.Question);
            if (result == DialogResult.Yes)
            {
                UpdateRequestStatus(id, "Approved");
            }
        }

        private void button3_Click(object? sender, EventArgs e)
        {
            int id = GetSelectedRequestId();
            if (id == -1)
            {
                MessageBox.Show("Please select a request first.", "Notice", MessageBoxButtons.OK, MessageBoxIcon.Information);
                return;
            }

            DialogResult result = MessageBox.Show("Are you sure you want to decline this request?", "Confirm", MessageBoxButtons.YesNo, MessageBoxIcon.Question);
            if (result == DialogResult.Yes)
            {
                UpdateRequestStatus(id, "Declined");
            }
        }

        private void button4_Click(object? sender, EventArgs e)
        {
            int id = GetSelectedRequestId();
            if (id == -1)
            {
                MessageBox.Show("Please select a request first.", "Notice", MessageBoxButtons.OK, MessageBoxIcon.Information);
                return;
            }

            DialogResult result = MessageBox.Show("Mark this meeting as completed?", "Confirm", MessageBoxButtons.YesNo, MessageBoxIcon.Question);
            if (result == DialogResult.Yes)
            {
                UpdateRequestStatus(id, "Completed");
            }
        }

        private void textBox3_TextChanged(object? sender, EventArgs e)
        {
            LoadRequests();
        }

        private void comboBox2_SelectedIndexChanged(object? sender, EventArgs e)
        {
            LoadRequests();
        }

        private void btnRefresh_Click(object? sender, EventArgs e)
        {
            LoadHomePage();
            LoadRequests();
        }

        private void LoadAvailability(string search = "")
        {
            if (loggedInUser == null)
            {
                return;
            }

            try
            {
                using (MySqlConnection conn = Connection.GetConnection())
                {
                    string query = "SELECT availability_id, " +
                                   "days AS 'Day', " +
                                   "TIME_FORMAT(start_time, '%h:%i %p') AS 'Start Time', " +
                                   "TIME_FORMAT(end_time, '%h:%i %p') AS 'End Time', " +
                                   "location AS 'Location' " +
                                   "FROM faculty_availability " +
                                   "WHERE faculty_id = @facultyId";

                    if (!string.IsNullOrWhiteSpace(search))
                    {
                        query += " AND (days LIKE @search OR location LIKE @search)";
                    }

                    query += " ORDER BY FIELD(days, 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'), start_time";

                    using (MySqlCommand cmd = new MySqlCommand(query, conn))
                    {
                        cmd.Parameters.AddWithValue("@facultyId", loggedInUser.UserId);

                        if (!string.IsNullOrWhiteSpace(search))
                        {
                            cmd.Parameters.AddWithValue("@search", "%" + search.Trim() + "%");
                        }

                        using (MySqlDataAdapter da = new MySqlDataAdapter(cmd))
                        {
                            DataTable dt = new DataTable();
                            da.Fill(dt);
                            dataGridView1.DataSource = dt;

                            DataGridViewColumn? idColumn = dataGridView1.Columns["availability_id"];
                            if (idColumn != null)
                            {
                                idColumn.Visible = false;
                            }
                        }
                    }
                }
            }
            catch (Exception ex)
            {
                MessageBox.Show("Error loading availability: " + ex.Message, "Error", MessageBoxButtons.OK, MessageBoxIcon.Error);
            }
        }

        private void button1_Click(object sender, EventArgs e)
        {
            if (loggedInUser == null)
            {
                MessageBox.Show("Please log in first.", "Error", MessageBoxButtons.OK, MessageBoxIcon.Warning);
                return;
            }

            if (comboBox1.SelectedIndex == -1 || comboBox1.SelectedItem == null)
            {
                MessageBox.Show("Please select a day of week.", "Validation Error", MessageBoxButtons.OK, MessageBoxIcon.Warning);
                comboBox1.Focus();
                return;
            }

            TimeSpan startTime = dateTimePicker1.Value.TimeOfDay;
            TimeSpan endTime = dateTimePicker3.Value.TimeOfDay;

            if (startTime >= endTime)
            {
                MessageBox.Show("Start time must be earlier than end time.", "Validation Error", MessageBoxButtons.OK, MessageBoxIcon.Warning);
                return;
            }

            string day = comboBox1.SelectedItem.ToString() ?? "";
            string location = textBox1.Text.Trim();
            if (location == "e.g Room 101, Online etc.")
            {
                location = "";
            }

            try
            {
                using (MySqlConnection conn = Connection.GetConnection())
                {
                    if (selectedAvailabilityId == -1)
                    {
                        string insertQuery = "INSERT INTO faculty_availability (faculty_id, days, start_time, end_time, location) " +
                                             "VALUES (@facultyId, @days, @startTime, @endTime, @location)";

                        using (MySqlCommand cmd = new MySqlCommand(insertQuery, conn))
                        {
                            cmd.Parameters.AddWithValue("@facultyId", loggedInUser.UserId);
                            cmd.Parameters.AddWithValue("@days", day);
                            cmd.Parameters.AddWithValue("@startTime", startTime.ToString(@"hh\:mm\:ss"));
                            cmd.Parameters.AddWithValue("@endTime", endTime.ToString(@"hh\:mm\:ss"));
                            cmd.Parameters.AddWithValue("@location", location);

                            cmd.ExecuteNonQuery();
                        }

                        MessageBox.Show("Consultation hours added successfully!", "Success", MessageBoxButtons.OK, MessageBoxIcon.Information);
                    }
                    else
                    {
                        string updateQuery = "UPDATE faculty_availability " +
                                             "SET days = @days, start_time = @startTime, end_time = @endTime, location = @location " +
                                             "WHERE availability_id = @id AND faculty_id = @facultyId";

                        using (MySqlCommand cmd = new MySqlCommand(updateQuery, conn))
                        {
                            cmd.Parameters.AddWithValue("@days", day);
                            cmd.Parameters.AddWithValue("@startTime", startTime.ToString(@"hh\:mm\:ss"));
                            cmd.Parameters.AddWithValue("@endTime", endTime.ToString(@"hh\:mm\:ss"));
                            cmd.Parameters.AddWithValue("@location", location);
                            cmd.Parameters.AddWithValue("@id", selectedAvailabilityId);
                            cmd.Parameters.AddWithValue("@facultyId", loggedInUser.UserId);

                            cmd.ExecuteNonQuery();
                        }

                        MessageBox.Show("Consultation hours updated successfully!", "Success", MessageBoxButtons.OK, MessageBoxIcon.Information);
                    }
                }

                ClearAvailabilityInputs();
                LoadAvailability();
            }
            catch (Exception ex)
            {
                MessageBox.Show("Error saving consultation hours: " + ex.Message, "Database Error", MessageBoxButtons.OK, MessageBoxIcon.Error);
            }
        }

        private void button1_Click_1(object sender, EventArgs e)
        {
            ClearAvailabilityInputs();
        }

        private void ClearAvailabilityInputs()
        {
            comboBox1.SelectedIndex = -1;
            dateTimePicker1.Value = DateTime.Today.AddHours(8);
            dateTimePicker3.Value = DateTime.Today.AddHours(9);
            textBox1.Text = "  e.g Room 101, Online etc.";
            selectedAvailabilityId = -1;
            AddBTN.Text = "+ Add";
        }

        private void EditBTN_Click(object sender, EventArgs e)
        {
            if (dataGridView1.CurrentRow == null || dataGridView1.CurrentRow.Index < 0)
            {
                MessageBox.Show("Please select an item to edit.", "Notice", MessageBoxButtons.OK, MessageBoxIcon.Information);
                return;
            }

            selectedAvailabilityId = Convert.ToInt32(dataGridView1.CurrentRow.Cells["availability_id"].Value);
            string day = dataGridView1.CurrentRow.Cells["Day"].Value?.ToString() ?? "";
            string startTimeStr = dataGridView1.CurrentRow.Cells["Start Time"].Value?.ToString() ?? "";
            string endTimeStr = dataGridView1.CurrentRow.Cells["End Time"].Value?.ToString() ?? "";
            string location = dataGridView1.CurrentRow.Cells["Location"].Value?.ToString() ?? "";

            comboBox1.SelectedItem = day;

            if (DateTime.TryParse(startTimeStr, out DateTime sTime))
            {
                dateTimePicker1.Value = sTime;
            }

            if (DateTime.TryParse(endTimeStr, out DateTime eTime))
            {
                dateTimePicker3.Value = eTime;
            }

            textBox1.Text = location;
            AddBTN.Text = "Save";
        }

        private void DeleteBTN_Click(object sender, EventArgs e)
        {
            if (loggedInUser == null)
            {
                MessageBox.Show("Please log in first.", "Error", MessageBoxButtons.OK, MessageBoxIcon.Warning);
                return;
            }

            if (dataGridView1.CurrentRow == null || dataGridView1.CurrentRow.Index < 0)
            {
                MessageBox.Show("Please select an item to delete.", "Notice", MessageBoxButtons.OK, MessageBoxIcon.Information);
                return;
            }

            DialogResult result = MessageBox.Show(
                "Are you sure you want to delete this schedule?",
                "Delete Confirmation",
                MessageBoxButtons.YesNo,
                MessageBoxIcon.Question
            );

            if (result == DialogResult.Yes)
            {
                int id = Convert.ToInt32(dataGridView1.CurrentRow.Cells["availability_id"].Value);

                try
                {
                    using (MySqlConnection conn = Connection.GetConnection())
                    {
                        string deleteQuery = "DELETE FROM faculty_availability WHERE availability_id = @id AND faculty_id = @facultyId";

                        using (MySqlCommand cmd = new MySqlCommand(deleteQuery, conn))
                        {
                            cmd.Parameters.AddWithValue("@id", id);
                            cmd.Parameters.AddWithValue("@facultyId", loggedInUser.UserId);

                            cmd.ExecuteNonQuery();
                        }
                    }

                    MessageBox.Show("Consultation hours deleted successfully!", "Success", MessageBoxButtons.OK, MessageBoxIcon.Information);

                    ClearAvailabilityInputs();
                    LoadAvailability();
                }
                catch (Exception ex)
                {
                    MessageBox.Show("Error deleting schedule: " + ex.Message, "Database Error", MessageBoxButtons.OK, MessageBoxIcon.Error);
                }
            }
        }

        private void SearchBTN_Click(object sender, EventArgs e)
        {
            LoadAvailability(textBox2.Text);
        }

        private void textBox2_TextChanged(object sender, EventArgs e)
        {
            LoadAvailability(textBox2.Text);
        }

        private void AvailabilityPage_Click(object sender, EventArgs e)
        {
            LoadAvailability();
        }

        private void btnLogout_Click(object sender, EventArgs e)
        {
            DialogResult result = MessageBox.Show(
                "Are you sure you want to log out?",
                "Log Out",
                MessageBoxButtons.YesNo,
                MessageBoxIcon.Question
            );

            if (result == DialogResult.Yes)
            {
                DialogResult = DialogResult.Retry;
                Close();
            }
        }

        private void Dashboard_FormClosed(object? sender, FormClosedEventArgs e)
        {
            if (Owner is LoginForm loginForm)
            {
                loginForm.DashboardClosed(this);
            }
        }

        private void tabRequest_Click(object sender, EventArgs e)
        {
            LoadRequests();
        }

        private void tabHome_Click(object sender, EventArgs e)
        {
            LoadHomePage();
        }

        private void button2_Click_1(object sender, EventArgs e)
        {
            button2_Click(sender, e);
        }

        private void pnlHeader_Paint(object sender, PaintEventArgs e)
        {

        }
    }
}
