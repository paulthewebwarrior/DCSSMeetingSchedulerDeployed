namespace DCSSMeetingScheduler.Forms.Student
{
    partial class StudentForm
    {
        /// <summary>
        /// Required designer variable.
        /// </summary>
        private System.ComponentModel.IContainer components = null;

        /// <summary>
        /// Clean up any resources being used.
        /// </summary>
        /// <param name="disposing">true if managed resources should be disposed; otherwise, false.</param>
        protected override void Dispose(bool disposing)
        {
            if (disposing && (components != null))
            {
                components.Dispose();
            }
            base.Dispose(disposing);
        }

        #region Windows Form Designer generated code

        /// <summary>
        /// Required method for Designer support - do not modify
        /// the contents of this method with the code editor.
        /// </summary>
        private void InitializeComponent() {
            pblHeader = new Panel();
            btnLogout = new Button();
            label1 = new Label();
            lblPortalTitle = new Label();
            tabPage3 = new TabPage();
            pnlRequestsHeader = new Panel();
            btnCancelRequest = new Button();
            dgvMyRequests = new DataGridView();
            request_id = new DataGridViewTextBoxColumn();
            faculty_name = new DataGridViewTextBoxColumn();
            meeting_date = new DataGridViewTextBoxColumn();
            start_time = new DataGridViewTextBoxColumn();
            end_time = new DataGridViewTextBoxColumn();
            status = new DataGridViewTextBoxColumn();
            faculty_remarks = new DataGridViewTextBoxColumn();
            label3 = new Label();
            pnlNotification = new Panel();
            btnMarkAsRead = new Button();
            lblNotification = new Label();
            tableLayoutPanel1 = new TableLayoutPanel();
            panel4 = new Panel();
            lblCompletedCount = new Label();
            lblCompletedTitle = new Label();
            panel3 = new Panel();
            lblPendingCount = new Label();
            lblPendingTitle = new Label();
            panel2 = new Panel();
            lblApprovedCount = new Label();
            lblApprovedTitle = new Label();
            panel1 = new Panel();
            lblTotalCount = new Label();
            lblTotalTitle = new Label();
            pnlHomeTitle = new Panel();
            label2 = new Label();
            lblHomeTitle = new Label();
            tabStudent = new TabControl();
            tabPage1 = new TabPage();
            txtPurposeOfTheMeeting = new RichTextBox();
            btnSubmitRequest = new Button();
            lbPurposeofMeeting = new Label();
            dtpEndTime = new DateTimePicker();
            lblEndTime = new Label();
            dtpStartTime = new DateTimePicker();
            lblStartTime = new Label();
            dtpMeetingDate = new DateTimePicker();
            lblMeetingDate = new Label();
            dgvFacultyHours = new DataGridView();
            colDay = new DataGridViewTextBoxColumn();
            colStartTime = new DataGridViewTextBoxColumn();
            colEndTime = new DataGridViewTextBoxColumn();
            colLocation = new DataGridViewTextBoxColumn();
            label5 = new Label();
            cmbFaculty = new ComboBox();
            lblFaculty = new Label();
            pnlRequestTitle = new Panel();
            label4 = new Label();
            lblRequestTitle = new Label();
            tabPage2 = new TabPage();
            tlpSettingsLayout = new TableLayoutPanel();
            pnlStudentProfile = new Panel();
            lblProfilSub = new Label();
            btnCancelSettings = new Button();
            btnSaveSettings = new Button();
            cmbYearLevel = new ComboBox();
            textBox1 = new TextBox();
            txtStudentProgram = new TextBox();
            txtStudentEmail = new TextBox();
            txtStudentId = new TextBox();
            txtStudentName = new TextBox();
            label11 = new Label();
            label10 = new Label();
            label9 = new Label();
            label8 = new Label();
            label7 = new Label();
            label6 = new Label();
            lblStudentProfileTitle = new Label();
            pnlAccountSettings = new Panel();
            btnChangePassword = new Button();
            txtConfirmNewPassword = new TextBox();
            txtNewPassword = new TextBox();
            label14 = new Label();
            label13 = new Label();
            txtCurrentPassword = new TextBox();
            label12 = new Label();
            lblAccountSub = new Label();
            lblAccountSettingsTitle = new Label();
            pnlPreferences = new Panel();
            btnResetPreferences = new Button();
            label15 = new Label();
            chkShowRecentRequests = new CheckBox();
            chkShowUpcomingMeetings = new CheckBox();
            lblNotifications = new Label();
            btnSavePreferences = new Button();
            chkStatusNotifications = new CheckBox();
            chkConsultationReminders = new CheckBox();
            chkEmailNotifications = new CheckBox();
            lblDefaultTab = new Label();
            lblPreferencesSub = new Label();
            cmbDefaultTab = new ComboBox();
            lblPreferencesTitle = new Label();
            panel5 = new Panel();
            lblLastLogin = new Label();
            lblVersion = new Label();
            lblAppInfoSub = new Label();
            lblAppInforHeading = new Label();
            pnlSettingsHeader = new Panel();
            lblSettingsSubtitle = new Label();
            lblSettingsTitle = new Label();
            dateTimePicker2 = new DateTimePicker();
            pblHeader.SuspendLayout();
            tabPage3.SuspendLayout();
            pnlRequestsHeader.SuspendLayout();
            ((System.ComponentModel.ISupportInitialize)dgvMyRequests).BeginInit();
            pnlNotification.SuspendLayout();
            tableLayoutPanel1.SuspendLayout();
            panel4.SuspendLayout();
            panel3.SuspendLayout();
            panel2.SuspendLayout();
            panel1.SuspendLayout();
            pnlHomeTitle.SuspendLayout();
            tabStudent.SuspendLayout();
            tabPage1.SuspendLayout();
            ((System.ComponentModel.ISupportInitialize)dgvFacultyHours).BeginInit();
            pnlRequestTitle.SuspendLayout();
            tabPage2.SuspendLayout();
            tlpSettingsLayout.SuspendLayout();
            pnlStudentProfile.SuspendLayout();
            pnlAccountSettings.SuspendLayout();
            pnlPreferences.SuspendLayout();
            panel5.SuspendLayout();
            pnlSettingsHeader.SuspendLayout();
            SuspendLayout();
            // 
            // pblHeader
            // 
            pblHeader.BackColor = Color.DarkGreen;
            pblHeader.Controls.Add(btnLogout);
            pblHeader.Controls.Add(label1);
            pblHeader.Controls.Add(lblPortalTitle);
            pblHeader.Dock = DockStyle.Top;
            pblHeader.Location = new Point(0, 0);
            pblHeader.Name = "pblHeader";
            pblHeader.Size = new Size(1270, 100);
            pblHeader.TabIndex = 0;
            pblHeader.Paint += pblHeader_Paint;
            // 
            // btnLogout
            // 
            btnLogout.BackColor = Color.Red;
            btnLogout.FlatStyle = FlatStyle.Flat;
            btnLogout.Font = new Font("Segoe UI", 9F, FontStyle.Bold);
            btnLogout.ForeColor = Color.White;
            btnLogout.Location = new Point(1144, 33);
            btnLogout.Name = "btnLogout";
            btnLogout.Size = new Size(95, 35);
            btnLogout.TabIndex = 3;
            btnLogout.Text = "Logout";
            btnLogout.UseVisualStyleBackColor = false;
            btnLogout.Click += btnLogout_Click;
            // 
            // label1
            // 
            label1.AutoSize = true;
            label1.Font = new Font("Segoe UI Semibold", 9.25F, FontStyle.Bold);
            label1.ForeColor = Color.Silver;
            label1.Location = new Point(12, 37);
            label1.Name = "label1";
            label1.Size = new Size(270, 17);
            label1.TabIndex = 2;
            label1.Text = "Student Consultation Management System";
            label1.Click += label1_Click;
            // 
            // lblPortalTitle
            // 
            lblPortalTitle.AutoSize = true;
            lblPortalTitle.Font = new Font("Segoe UI", 20.25F, FontStyle.Bold, GraphicsUnit.Point, 0);
            lblPortalTitle.ForeColor = Color.White;
            lblPortalTitle.Location = new Point(7, 0);
            lblPortalTitle.Name = "lblPortalTitle";
            lblPortalTitle.Size = new Size(203, 37);
            lblPortalTitle.TabIndex = 1;
            lblPortalTitle.Text = "Student Portal";
            // 
            // tabPage3
            // 
            tabPage3.Controls.Add(pnlRequestsHeader);
            tabPage3.Controls.Add(pnlNotification);
            tabPage3.Controls.Add(tableLayoutPanel1);
            tabPage3.Controls.Add(pnlHomeTitle);
            tabPage3.Location = new Point(4, 24);
            tabPage3.Name = "tabPage3";
            tabPage3.Padding = new Padding(3);
            tabPage3.Size = new Size(1262, 509);
            tabPage3.TabIndex = 2;
            tabPage3.Text = "Home";
            tabPage3.UseVisualStyleBackColor = true;
            tabPage3.Click += tabPage3_Click_1;
            // 
            // pnlRequestsHeader
            // 
            pnlRequestsHeader.BorderStyle = BorderStyle.FixedSingle;
            pnlRequestsHeader.Controls.Add(btnCancelRequest);
            pnlRequestsHeader.Controls.Add(dgvMyRequests);
            pnlRequestsHeader.Controls.Add(label3);
            pnlRequestsHeader.Location = new Point(3, 275);
            pnlRequestsHeader.Name = "pnlRequestsHeader";
            pnlRequestsHeader.Size = new Size(1256, 302);
            pnlRequestsHeader.TabIndex = 4;
            // 
            // btnCancelRequest
            // 
            btnCancelRequest.BackColor = Color.White;
            btnCancelRequest.FlatStyle = FlatStyle.Flat;
            btnCancelRequest.Font = new Font("Segoe UI", 9F, FontStyle.Bold, GraphicsUnit.Point, 0);
            btnCancelRequest.ForeColor = Color.Red;
            btnCancelRequest.Location = new Point(1113, 11);
            btnCancelRequest.Name = "btnCancelRequest";
            btnCancelRequest.Size = new Size(140, 35);
            btnCancelRequest.TabIndex = 2;
            btnCancelRequest.Text = "Cancel Request";
            btnCancelRequest.UseVisualStyleBackColor = false;
            btnCancelRequest.Click += btnCancelRequest_Click;
            // 
            // dgvMyRequests
            // 
            dgvMyRequests.AllowUserToAddRows = false;
            dgvMyRequests.AllowUserToDeleteRows = false;
            dgvMyRequests.AutoSizeColumnsMode = DataGridViewAutoSizeColumnsMode.Fill;
            dgvMyRequests.BackgroundColor = Color.White;
            dgvMyRequests.ColumnHeadersHeightSizeMode = DataGridViewColumnHeadersHeightSizeMode.AutoSize;
            dgvMyRequests.Columns.AddRange(new DataGridViewColumn[] { request_id, faculty_name, meeting_date, start_time, end_time, status, faculty_remarks });
            dgvMyRequests.Location = new Point(3, 52);
            dgvMyRequests.Name = "dgvMyRequests";
            dgvMyRequests.ReadOnly = true;
            dgvMyRequests.RowHeadersVisible = false;
            dgvMyRequests.RowHeadersWidth = 62;
            dgvMyRequests.SelectionMode = DataGridViewSelectionMode.FullRowSelect;
            dgvMyRequests.Size = new Size(1250, 246);
            dgvMyRequests.TabIndex = 1;
            dgvMyRequests.CellContentClick += dgvMyRequests_CellContentClick;
            // 
            // request_id
            // 
            request_id.HeaderText = "Request ID";
            request_id.MinimumWidth = 8;
            request_id.Name = "request_id";
            request_id.ReadOnly = true;
            // 
            // faculty_name
            // 
            faculty_name.HeaderText = "Faculty Name";
            faculty_name.MinimumWidth = 8;
            faculty_name.Name = "faculty_name";
            faculty_name.ReadOnly = true;
            // 
            // meeting_date
            // 
            meeting_date.HeaderText = "Date";
            meeting_date.MinimumWidth = 8;
            meeting_date.Name = "meeting_date";
            meeting_date.ReadOnly = true;
            // 
            // start_time
            // 
            start_time.HeaderText = "Start Time";
            start_time.MinimumWidth = 8;
            start_time.Name = "start_time";
            start_time.ReadOnly = true;
            // 
            // end_time
            // 
            end_time.HeaderText = "End Time";
            end_time.MinimumWidth = 8;
            end_time.Name = "end_time";
            end_time.ReadOnly = true;
            // 
            // status
            // 
            status.HeaderText = "Status";
            status.MinimumWidth = 8;
            status.Name = "status";
            status.ReadOnly = true;
            // 
            // faculty_remarks
            // 
            faculty_remarks.HeaderText = "Faculty Remarks";
            faculty_remarks.MinimumWidth = 8;
            faculty_remarks.Name = "faculty_remarks";
            faculty_remarks.ReadOnly = true;
            // 
            // label3
            // 
            label3.AutoSize = true;
            label3.Font = new Font("Segoe UI", 14.25F, FontStyle.Bold, GraphicsUnit.Point, 0);
            label3.Location = new Point(0, 14);
            label3.Name = "label3";
            label3.Size = new Size(195, 25);
            label3.TabIndex = 0;
            label3.Text = "My Meeting Request";
            // 
            // pnlNotification
            // 
            pnlNotification.BackColor = Color.LightYellow;
            pnlNotification.BorderStyle = BorderStyle.FixedSingle;
            pnlNotification.Controls.Add(btnMarkAsRead);
            pnlNotification.Controls.Add(lblNotification);
            pnlNotification.Location = new Point(6, 218);
            pnlNotification.Name = "pnlNotification";
            pnlNotification.Size = new Size(1250, 51);
            pnlNotification.TabIndex = 3;
            // 
            // btnMarkAsRead
            // 
            btnMarkAsRead.BackColor = Color.White;
            btnMarkAsRead.FlatStyle = FlatStyle.Flat;
            btnMarkAsRead.Font = new Font("Segoe UI", 8.25F, FontStyle.Bold, GraphicsUnit.Point, 0);
            btnMarkAsRead.Location = new Point(1116, 10);
            btnMarkAsRead.Name = "btnMarkAsRead";
            btnMarkAsRead.Size = new Size(120, 30);
            btnMarkAsRead.TabIndex = 1;
            btnMarkAsRead.Text = "Mark as Read";
            btnMarkAsRead.UseVisualStyleBackColor = false;
            btnMarkAsRead.Click += btnMarkAsRead_Click;
            // 
            // lblNotification
            // 
            lblNotification.AutoSize = true;
            lblNotification.Font = new Font("Segoe UI", 9.75F, FontStyle.Regular, GraphicsUnit.Point, 0);
            lblNotification.Location = new Point(12, 16);
            lblNotification.Name = "lblNotification";
            lblNotification.Size = new Size(130, 17);
            lblNotification.TabIndex = 0;
            lblNotification.Text = "No new notifications.";
            // 
            // tableLayoutPanel1
            // 
            tableLayoutPanel1.ColumnCount = 4;
            tableLayoutPanel1.ColumnStyles.Add(new ColumnStyle(SizeType.Percent, 25F));
            tableLayoutPanel1.ColumnStyles.Add(new ColumnStyle(SizeType.Percent, 25F));
            tableLayoutPanel1.ColumnStyles.Add(new ColumnStyle(SizeType.Percent, 25F));
            tableLayoutPanel1.ColumnStyles.Add(new ColumnStyle(SizeType.Percent, 25F));
            tableLayoutPanel1.Controls.Add(panel4, 3, 0);
            tableLayoutPanel1.Controls.Add(panel3, 2, 0);
            tableLayoutPanel1.Controls.Add(panel2, 1, 0);
            tableLayoutPanel1.Controls.Add(panel1, 0, 0);
            tableLayoutPanel1.Location = new Point(3, 85);
            tableLayoutPanel1.Name = "tableLayoutPanel1";
            tableLayoutPanel1.RowCount = 1;
            tableLayoutPanel1.RowStyles.Add(new RowStyle(SizeType.Percent, 100F));
            tableLayoutPanel1.Size = new Size(1256, 127);
            tableLayoutPanel1.TabIndex = 2;
            // 
            // panel4
            // 
            panel4.BackColor = Color.White;
            panel4.BorderStyle = BorderStyle.FixedSingle;
            panel4.Controls.Add(lblCompletedCount);
            panel4.Controls.Add(lblCompletedTitle);
            panel4.Dock = DockStyle.Fill;
            panel4.Location = new Point(945, 3);
            panel4.Name = "panel4";
            panel4.Size = new Size(308, 121);
            panel4.TabIndex = 3;
            // 
            // lblCompletedCount
            // 
            lblCompletedCount.AutoSize = true;
            lblCompletedCount.Font = new Font("Segoe UI", 26.25F, FontStyle.Bold, GraphicsUnit.Point, 0);
            lblCompletedCount.Location = new Point(2, 49);
            lblCompletedCount.Name = "lblCompletedCount";
            lblCompletedCount.Size = new Size(40, 47);
            lblCompletedCount.TabIndex = 3;
            lblCompletedCount.Text = "0";
            // 
            // lblCompletedTitle
            // 
            lblCompletedTitle.AutoSize = true;
            lblCompletedTitle.Font = new Font("Segoe UI", 12F, FontStyle.Bold, GraphicsUnit.Point, 0);
            lblCompletedTitle.ForeColor = Color.DarkSlateGray;
            lblCompletedTitle.Location = new Point(2, 11);
            lblCompletedTitle.Name = "lblCompletedTitle";
            lblCompletedTitle.Size = new Size(162, 21);
            lblCompletedTitle.TabIndex = 3;
            lblCompletedTitle.Text = "Completed Meeting";
            // 
            // panel3
            // 
            panel3.BackColor = Color.White;
            panel3.BorderStyle = BorderStyle.FixedSingle;
            panel3.Controls.Add(lblPendingCount);
            panel3.Controls.Add(lblPendingTitle);
            panel3.Dock = DockStyle.Fill;
            panel3.Location = new Point(631, 3);
            panel3.Name = "panel3";
            panel3.Size = new Size(308, 121);
            panel3.TabIndex = 2;
            // 
            // lblPendingCount
            // 
            lblPendingCount.AutoSize = true;
            lblPendingCount.Font = new Font("Segoe UI", 26.25F, FontStyle.Bold, GraphicsUnit.Point, 0);
            lblPendingCount.ForeColor = Color.Orange;
            lblPendingCount.Location = new Point(3, 49);
            lblPendingCount.Name = "lblPendingCount";
            lblPendingCount.Size = new Size(40, 47);
            lblPendingCount.TabIndex = 2;
            lblPendingCount.Text = "0";
            // 
            // lblPendingTitle
            // 
            lblPendingTitle.AutoSize = true;
            lblPendingTitle.Font = new Font("Segoe UI", 12F, FontStyle.Bold, GraphicsUnit.Point, 0);
            lblPendingTitle.ForeColor = Color.DarkOrange;
            lblPendingTitle.Location = new Point(3, 11);
            lblPendingTitle.Name = "lblPendingTitle";
            lblPendingTitle.Size = new Size(142, 21);
            lblPendingTitle.TabIndex = 2;
            lblPendingTitle.Text = "Pending Meeting";
            // 
            // panel2
            // 
            panel2.BackColor = Color.White;
            panel2.BorderStyle = BorderStyle.FixedSingle;
            panel2.Controls.Add(lblApprovedCount);
            panel2.Controls.Add(lblApprovedTitle);
            panel2.Dock = DockStyle.Fill;
            panel2.Location = new Point(317, 3);
            panel2.Name = "panel2";
            panel2.Size = new Size(308, 121);
            panel2.TabIndex = 1;
            // 
            // lblApprovedCount
            // 
            lblApprovedCount.AutoSize = true;
            lblApprovedCount.Font = new Font("Segoe UI", 26.25F, FontStyle.Bold, GraphicsUnit.Point, 0);
            lblApprovedCount.ForeColor = Color.DarkGreen;
            lblApprovedCount.Location = new Point(3, 49);
            lblApprovedCount.Name = "lblApprovedCount";
            lblApprovedCount.Size = new Size(40, 47);
            lblApprovedCount.TabIndex = 1;
            lblApprovedCount.Text = "0";
            lblApprovedCount.Click += label4_Click;
            // 
            // lblApprovedTitle
            // 
            lblApprovedTitle.AutoSize = true;
            lblApprovedTitle.Font = new Font("Segoe UI", 12F, FontStyle.Bold, GraphicsUnit.Point, 0);
            lblApprovedTitle.ForeColor = Color.Green;
            lblApprovedTitle.Location = new Point(3, 11);
            lblApprovedTitle.Name = "lblApprovedTitle";
            lblApprovedTitle.Size = new Size(153, 21);
            lblApprovedTitle.TabIndex = 1;
            lblApprovedTitle.Text = "Approved Meeting";
            // 
            // panel1
            // 
            panel1.BackColor = Color.White;
            panel1.BorderStyle = BorderStyle.FixedSingle;
            panel1.Controls.Add(lblTotalCount);
            panel1.Controls.Add(lblTotalTitle);
            panel1.Dock = DockStyle.Fill;
            panel1.Location = new Point(3, 3);
            panel1.Name = "panel1";
            panel1.Size = new Size(308, 121);
            panel1.TabIndex = 0;
            // 
            // lblTotalCount
            // 
            lblTotalCount.AutoSize = true;
            lblTotalCount.Font = new Font("Segoe UI", 26.25F, FontStyle.Bold, GraphicsUnit.Point, 0);
            lblTotalCount.Location = new Point(2, 49);
            lblTotalCount.Name = "lblTotalCount";
            lblTotalCount.Size = new Size(40, 47);
            lblTotalCount.TabIndex = 1;
            lblTotalCount.Text = "0";
            lblTotalCount.Click += label3_Click_1;
            // 
            // lblTotalTitle
            // 
            lblTotalTitle.AutoSize = true;
            lblTotalTitle.Font = new Font("Segoe UI", 12F, FontStyle.Bold, GraphicsUnit.Point, 0);
            lblTotalTitle.ForeColor = Color.Navy;
            lblTotalTitle.Location = new Point(2, 11);
            lblTotalTitle.Name = "lblTotalTitle";
            lblTotalTitle.Size = new Size(116, 21);
            lblTotalTitle.TabIndex = 0;
            lblTotalTitle.Text = "Total Meeting";
            lblTotalTitle.Click += label3_Click;
            // 
            // pnlHomeTitle
            // 
            pnlHomeTitle.BackColor = Color.White;
            pnlHomeTitle.BorderStyle = BorderStyle.FixedSingle;
            pnlHomeTitle.Controls.Add(label2);
            pnlHomeTitle.Controls.Add(lblHomeTitle);
            pnlHomeTitle.Dock = DockStyle.Top;
            pnlHomeTitle.Location = new Point(3, 3);
            pnlHomeTitle.Name = "pnlHomeTitle";
            pnlHomeTitle.Size = new Size(1256, 77);
            pnlHomeTitle.TabIndex = 0;
            pnlHomeTitle.Paint += pnlHomeTitle_Paint;
            // 
            // label2
            // 
            label2.AutoSize = true;
            label2.Font = new Font("Segoe UI", 9.75F, FontStyle.Regular, GraphicsUnit.Point, 0);
            label2.ForeColor = Color.DimGray;
            label2.Location = new Point(8, 42);
            label2.Name = "label2";
            label2.Size = new Size(326, 17);
            label2.TabIndex = 1;
            label2.Text = "Welcome to your student dashboard and status center";
            // 
            // lblHomeTitle
            // 
            lblHomeTitle.AutoSize = true;
            lblHomeTitle.Font = new Font("Segoe UI", 18F, FontStyle.Bold, GraphicsUnit.Point, 0);
            lblHomeTitle.ForeColor = Color.DarkGreen;
            lblHomeTitle.Location = new Point(3, 10);
            lblHomeTitle.Name = "lblHomeTitle";
            lblHomeTitle.Size = new Size(216, 32);
            lblHomeTitle.TabIndex = 0;
            lblHomeTitle.Text = "Student Overview";
            // 
            // tabStudent
            // 
            tabStudent.Controls.Add(tabPage3);
            tabStudent.Controls.Add(tabPage1);
            tabStudent.Controls.Add(tabPage2);
            tabStudent.Dock = DockStyle.Fill;
            tabStudent.Location = new Point(0, 100);
            tabStudent.Name = "tabStudent";
            tabStudent.SelectedIndex = 0;
            tabStudent.Size = new Size(1270, 537);
            tabStudent.TabIndex = 1;
            // 
            // tabPage1
            // 
            tabPage1.Controls.Add(txtPurposeOfTheMeeting);
            tabPage1.Controls.Add(btnSubmitRequest);
            tabPage1.Controls.Add(lbPurposeofMeeting);
            tabPage1.Controls.Add(dtpEndTime);
            tabPage1.Controls.Add(lblEndTime);
            tabPage1.Controls.Add(dtpStartTime);
            tabPage1.Controls.Add(lblStartTime);
            tabPage1.Controls.Add(dtpMeetingDate);
            tabPage1.Controls.Add(lblMeetingDate);
            tabPage1.Controls.Add(dgvFacultyHours);
            tabPage1.Controls.Add(label5);
            tabPage1.Controls.Add(cmbFaculty);
            tabPage1.Controls.Add(lblFaculty);
            tabPage1.Controls.Add(pnlRequestTitle);
            tabPage1.Font = new Font("Segoe UI", 9.75F, FontStyle.Bold, GraphicsUnit.Point, 0);
            tabPage1.Location = new Point(4, 24);
            tabPage1.Name = "tabPage1";
            tabPage1.Padding = new Padding(3);
            tabPage1.Size = new Size(1262, 509);
            tabPage1.TabIndex = 3;
            tabPage1.Text = "Request Meeting";
            tabPage1.UseVisualStyleBackColor = true;
            // 
            // txtPurposeOfTheMeeting
            // 
            txtPurposeOfTheMeeting.Location = new Point(11, 420);
            txtPurposeOfTheMeeting.Name = "txtPurposeOfTheMeeting";
            txtPurposeOfTheMeeting.Size = new Size(1243, 89);
            txtPurposeOfTheMeeting.TabIndex = 14;
            txtPurposeOfTheMeeting.Text = "";
            // 
            // btnSubmitRequest
            // 
            btnSubmitRequest.BackColor = Color.SeaGreen;
            btnSubmitRequest.ForeColor = Color.White;
            btnSubmitRequest.Location = new Point(11, 521);
            btnSubmitRequest.Name = "btnSubmitRequest";
            btnSubmitRequest.Size = new Size(1243, 40);
            btnSubmitRequest.TabIndex = 13;
            btnSubmitRequest.Text = "Submit Request";
            btnSubmitRequest.UseVisualStyleBackColor = false;
            btnSubmitRequest.Click += btnSubmitRequest_Click;
            // 
            // lbPurposeofMeeting
            // 
            lbPurposeofMeeting.AutoSize = true;
            lbPurposeofMeeting.Location = new Point(11, 401);
            lbPurposeofMeeting.Name = "lbPurposeofMeeting";
            lbPurposeofMeeting.Size = new Size(130, 17);
            lbPurposeofMeeting.TabIndex = 11;
            lbPurposeofMeeting.Text = "Purpose of Meeting";
            // 
            // dtpEndTime
            // 
            dtpEndTime.CustomFormat = "hh:mm tt";
            dtpEndTime.Format = DateTimePickerFormat.Custom;
            dtpEndTime.Location = new Point(443, 361);
            dtpEndTime.Name = "dtpEndTime";
            dtpEndTime.ShowUpDown = true;
            dtpEndTime.Size = new Size(200, 25);
            dtpEndTime.TabIndex = 10;
            // 
            // lblEndTime
            // 
            lblEndTime.AutoSize = true;
            lblEndTime.Location = new Point(443, 343);
            lblEndTime.Name = "lblEndTime";
            lblEndTime.Size = new Size(66, 17);
            lblEndTime.TabIndex = 9;
            lblEndTime.Text = "End Time";
            // 
            // dtpStartTime
            // 
            dtpStartTime.CustomFormat = "hh:mm tt";
            dtpStartTime.Format = DateTimePickerFormat.Custom;
            dtpStartTime.Location = new Point(228, 361);
            dtpStartTime.Name = "dtpStartTime";
            dtpStartTime.ShowUpDown = true;
            dtpStartTime.Size = new Size(200, 25);
            dtpStartTime.TabIndex = 8;
            dtpStartTime.ValueChanged += dtpStartTime_ValueChanged;
            // 
            // lblStartTime
            // 
            lblStartTime.AutoSize = true;
            lblStartTime.Font = new Font("Segoe UI", 9.75F, FontStyle.Bold, GraphicsUnit.Point, 0);
            lblStartTime.Location = new Point(228, 343);
            lblStartTime.Name = "lblStartTime";
            lblStartTime.Size = new Size(72, 17);
            lblStartTime.TabIndex = 0;
            lblStartTime.Text = "Start Time";
            // 
            // dtpMeetingDate
            // 
            dtpMeetingDate.Format = DateTimePickerFormat.Short;
            dtpMeetingDate.Location = new Point(13, 361);
            dtpMeetingDate.MinDate = new DateTime(2026, 10, 20, 0, 0, 0, 0);
            dtpMeetingDate.Name = "dtpMeetingDate";
            dtpMeetingDate.Size = new Size(200, 25);
            dtpMeetingDate.TabIndex = 7;
            // 
            // lblMeetingDate
            // 
            lblMeetingDate.AutoSize = true;
            lblMeetingDate.Font = new Font("Segoe UI", 10F, FontStyle.Bold);
            lblMeetingDate.Location = new Point(9, 343);
            lblMeetingDate.Name = "lblMeetingDate";
            lblMeetingDate.Size = new Size(99, 19);
            lblMeetingDate.TabIndex = 6;
            lblMeetingDate.Text = "Meeting Date";
            // 
            // dgvFacultyHours
            // 
            dgvFacultyHours.AllowUserToAddRows = false;
            dgvFacultyHours.AllowUserToDeleteRows = false;
            dgvFacultyHours.AutoSizeColumnsMode = DataGridViewAutoSizeColumnsMode.Fill;
            dgvFacultyHours.ColumnHeadersBorderStyle = DataGridViewHeaderBorderStyle.Single;
            dgvFacultyHours.ColumnHeadersHeightSizeMode = DataGridViewColumnHeadersHeightSizeMode.AutoSize;
            dgvFacultyHours.Columns.AddRange(new DataGridViewColumn[] { colDay, colStartTime, colEndTime, colLocation });
            dgvFacultyHours.Location = new Point(11, 157);
            dgvFacultyHours.Name = "dgvFacultyHours";
            dgvFacultyHours.ReadOnly = true;
            dgvFacultyHours.RowHeadersVisible = false;
            dgvFacultyHours.RowHeadersWidth = 62;
            dgvFacultyHours.SelectionMode = DataGridViewSelectionMode.FullRowSelect;
            dgvFacultyHours.Size = new Size(1243, 183);
            dgvFacultyHours.TabIndex = 5;
            dgvFacultyHours.CellClick += dgvFacultyHours_CellClick;
            // 
            // colDay
            // 
            colDay.HeaderText = "Day";
            colDay.MinimumWidth = 8;
            colDay.Name = "colDay";
            colDay.ReadOnly = true;
            // 
            // colStartTime
            // 
            colStartTime.HeaderText = "Start Time";
            colStartTime.MinimumWidth = 8;
            colStartTime.Name = "colStartTime";
            colStartTime.ReadOnly = true;
            // 
            // colEndTime
            // 
            colEndTime.HeaderText = "End Time";
            colEndTime.MinimumWidth = 8;
            colEndTime.Name = "colEndTime";
            colEndTime.ReadOnly = true;
            // 
            // colLocation
            // 
            colLocation.HeaderText = "Location";
            colLocation.MinimumWidth = 8;
            colLocation.Name = "colLocation";
            colLocation.ReadOnly = true;
            // 
            // label5
            // 
            label5.AutoSize = true;
            label5.Font = new Font("Segoe UI", 10F, FontStyle.Bold);
            label5.Location = new Point(8, 136);
            label5.Name = "label5";
            label5.Size = new Size(189, 19);
            label5.TabIndex = 4;
            label5.Text = "Available Consultation Day";
            // 
            // cmbFaculty
            // 
            cmbFaculty.DropDownStyle = ComboBoxStyle.DropDownList;
            cmbFaculty.FormattingEnabled = true;
            cmbFaculty.Location = new Point(11, 107);
            cmbFaculty.Name = "cmbFaculty";
            cmbFaculty.Size = new Size(1243, 25);
            cmbFaculty.TabIndex = 3;
            cmbFaculty.SelectedIndexChanged += cmbFaculty_SelectedIndexChanged;
            // 
            // lblFaculty
            // 
            lblFaculty.AutoSize = true;
            lblFaculty.Font = new Font("Segoe UI", 10F, FontStyle.Bold);
            lblFaculty.Location = new Point(8, 88);
            lblFaculty.Name = "lblFaculty";
            lblFaculty.Size = new Size(161, 19);
            lblFaculty.TabIndex = 2;
            lblFaculty.Text = "Select Faculty Member";
            // 
            // pnlRequestTitle
            // 
            pnlRequestTitle.BackColor = Color.White;
            pnlRequestTitle.BorderStyle = BorderStyle.FixedSingle;
            pnlRequestTitle.Controls.Add(label4);
            pnlRequestTitle.Controls.Add(lblRequestTitle);
            pnlRequestTitle.Dock = DockStyle.Top;
            pnlRequestTitle.Location = new Point(3, 3);
            pnlRequestTitle.Name = "pnlRequestTitle";
            pnlRequestTitle.Size = new Size(1256, 71);
            pnlRequestTitle.TabIndex = 1;
            // 
            // label4
            // 
            label4.AutoSize = true;
            label4.Font = new Font("Segoe UI", 10F, FontStyle.Bold);
            label4.Location = new Point(5, 42);
            label4.Name = "label4";
            label4.Size = new Size(487, 19);
            label4.TabIndex = 1;
            label4.Text = "Select a faculty member and choose an available consultation schedule.";
            // 
            // lblRequestTitle
            // 
            lblRequestTitle.AutoSize = true;
            lblRequestTitle.Font = new Font("Segoe UI", 18F, FontStyle.Bold, GraphicsUnit.Point, 0);
            lblRequestTitle.ForeColor = Color.DarkGreen;
            lblRequestTitle.Location = new Point(3, 10);
            lblRequestTitle.Name = "lblRequestTitle";
            lblRequestTitle.Size = new Size(227, 32);
            lblRequestTitle.TabIndex = 0;
            lblRequestTitle.Text = "Request a Meeting";
            // 
            // tabPage2
            // 
            tabPage2.Controls.Add(tlpSettingsLayout);
            tabPage2.Controls.Add(pnlSettingsHeader);
            tabPage2.Location = new Point(4, 24);
            tabPage2.Name = "tabPage2";
            tabPage2.Size = new Size(1262, 509);
            tabPage2.TabIndex = 0;
            tabPage2.Text = "Settings";
            tabPage2.UseVisualStyleBackColor = true;
            tabPage2.Click += tabPage2_Click_1;
            // 
            // tlpSettingsLayout
            // 
            tlpSettingsLayout.ColumnCount = 2;
            tlpSettingsLayout.ColumnStyles.Add(new ColumnStyle(SizeType.Percent, 30F));
            tlpSettingsLayout.ColumnStyles.Add(new ColumnStyle(SizeType.Percent, 70F));
            tlpSettingsLayout.Controls.Add(pnlStudentProfile, 0, 0);
            tlpSettingsLayout.Controls.Add(pnlAccountSettings, 1, 0);
            tlpSettingsLayout.Controls.Add(pnlPreferences, 1, 1);
            tlpSettingsLayout.Controls.Add(panel5, 0, 1);
            tlpSettingsLayout.Dock = DockStyle.Fill;
            tlpSettingsLayout.Location = new Point(0, 86);
            tlpSettingsLayout.Name = "tlpSettingsLayout";
            tlpSettingsLayout.Padding = new Padding(8);
            tlpSettingsLayout.RowCount = 2;
            tlpSettingsLayout.RowStyles.Add(new RowStyle(SizeType.Percent, 64.64435F));
            tlpSettingsLayout.RowStyles.Add(new RowStyle(SizeType.Percent, 35.355648F));
            tlpSettingsLayout.Size = new Size(1262, 423);
            tlpSettingsLayout.TabIndex = 13;
            // 
            // pnlStudentProfile
            // 
            pnlStudentProfile.BackColor = Color.White;
            pnlStudentProfile.BorderStyle = BorderStyle.FixedSingle;
            pnlStudentProfile.Controls.Add(lblProfilSub);
            pnlStudentProfile.Controls.Add(btnCancelSettings);
            pnlStudentProfile.Controls.Add(btnSaveSettings);
            pnlStudentProfile.Controls.Add(cmbYearLevel);
            pnlStudentProfile.Controls.Add(textBox1);
            pnlStudentProfile.Controls.Add(txtStudentProgram);
            pnlStudentProfile.Controls.Add(txtStudentEmail);
            pnlStudentProfile.Controls.Add(txtStudentId);
            pnlStudentProfile.Controls.Add(txtStudentName);
            pnlStudentProfile.Controls.Add(label11);
            pnlStudentProfile.Controls.Add(label10);
            pnlStudentProfile.Controls.Add(label9);
            pnlStudentProfile.Controls.Add(label8);
            pnlStudentProfile.Controls.Add(label7);
            pnlStudentProfile.Controls.Add(label6);
            pnlStudentProfile.Controls.Add(lblStudentProfileTitle);
            pnlStudentProfile.Dock = DockStyle.Fill;
            pnlStudentProfile.Font = new Font("Segoe UI", 9F, FontStyle.Bold);
            pnlStudentProfile.Location = new Point(11, 11);
            pnlStudentProfile.Name = "pnlStudentProfile";
            pnlStudentProfile.Padding = new Padding(10);
            pnlStudentProfile.Size = new Size(367, 257);
            pnlStudentProfile.TabIndex = 0;
            pnlStudentProfile.Paint += pnlStudentProfile_Paint;
            // 
            // lblProfilSub
            // 
            lblProfilSub.AutoSize = true;
            lblProfilSub.Font = new Font("Segoe UI", 9F);
            lblProfilSub.Location = new Point(13, 41);
            lblProfilSub.Name = "lblProfilSub";
            lblProfilSub.Size = new Size(239, 15);
            lblProfilSub.TabIndex = 15;
            lblProfilSub.Text = "View and update your personal information.";
            // 
            // btnCancelSettings
            // 
            btnCancelSettings.BackColor = Color.SeaGreen;
            btnCancelSettings.FlatStyle = FlatStyle.Flat;
            btnCancelSettings.Font = new Font("Segoe UI", 9F);
            btnCancelSettings.ForeColor = Color.White;
            btnCancelSettings.Location = new Point(217, 262);
            btnCancelSettings.Name = "btnCancelSettings";
            btnCancelSettings.Size = new Size(80, 30);
            btnCancelSettings.TabIndex = 14;
            btnCancelSettings.Text = "Cancel";
            btnCancelSettings.UseVisualStyleBackColor = false;
            btnCancelSettings.Click += btnCancelSettings_Click;
            // 
            // btnSaveSettings
            // 
            btnSaveSettings.BackColor = Color.SeaGreen;
            btnSaveSettings.FlatStyle = FlatStyle.Flat;
            btnSaveSettings.Font = new Font("Segoe UI", 9F);
            btnSaveSettings.ForeColor = Color.White;
            btnSaveSettings.Location = new Point(60, 262);
            btnSaveSettings.Name = "btnSaveSettings";
            btnSaveSettings.Size = new Size(100, 30);
            btnSaveSettings.TabIndex = 13;
            btnSaveSettings.Text = "Save Changes";
            btnSaveSettings.UseVisualStyleBackColor = false;
            btnSaveSettings.Click += btnSaveSettings_Click;
            // 
            // cmbYearLevel
            // 
            cmbYearLevel.DropDownStyle = ComboBoxStyle.DropDownList;
            cmbYearLevel.FormattingEnabled = true;
            cmbYearLevel.Items.AddRange(new object[] { "1st Year", "2nd Year", "3rd Year", "4th Year" });
            cmbYearLevel.Location = new Point(124, 190);
            cmbYearLevel.Name = "cmbYearLevel";
            cmbYearLevel.Size = new Size(150, 23);
            cmbYearLevel.TabIndex = 12;
            // 
            // textBox1
            // 
            textBox1.Location = new Point(124, 221);
            textBox1.Name = "textBox1";
            textBox1.Size = new Size(150, 23);
            textBox1.TabIndex = 11;
            // 
            // txtStudentProgram
            // 
            txtStudentProgram.Location = new Point(124, 160);
            txtStudentProgram.Name = "txtStudentProgram";
            txtStudentProgram.Size = new Size(150, 23);
            txtStudentProgram.TabIndex = 10;
            // 
            // txtStudentEmail
            // 
            txtStudentEmail.Location = new Point(124, 130);
            txtStudentEmail.Name = "txtStudentEmail";
            txtStudentEmail.Size = new Size(150, 23);
            txtStudentEmail.TabIndex = 9;
            // 
            // txtStudentId
            // 
            txtStudentId.Location = new Point(124, 100);
            txtStudentId.Name = "txtStudentId";
            txtStudentId.Size = new Size(150, 23);
            txtStudentId.TabIndex = 8;
            // 
            // txtStudentName
            // 
            txtStudentName.Location = new Point(124, 71);
            txtStudentName.Name = "txtStudentName";
            txtStudentName.Size = new Size(150, 23);
            txtStudentName.TabIndex = 7;
            // 
            // label11
            // 
            label11.AutoSize = true;
            label11.Font = new Font("Segoe UI", 9F, FontStyle.Bold);
            label11.Location = new Point(30, 225);
            label11.Name = "label11";
            label11.Size = new Size(49, 15);
            label11.TabIndex = 6;
            label11.Text = "Section";
            // 
            // label10
            // 
            label10.AutoSize = true;
            label10.Font = new Font("Segoe UI", 9F, FontStyle.Bold);
            label10.Location = new Point(30, 195);
            label10.Name = "label10";
            label10.Size = new Size(64, 15);
            label10.TabIndex = 5;
            label10.Text = "Year Level";
            label10.Click += label10_Click;
            // 
            // label9
            // 
            label9.AutoSize = true;
            label9.Font = new Font("Segoe UI", 9F, FontStyle.Bold);
            label9.Location = new Point(30, 164);
            label9.Name = "label9";
            label9.Size = new Size(55, 15);
            label9.TabIndex = 4;
            label9.Text = "Program";
            label9.Click += label9_Click;
            // 
            // label8
            // 
            label8.AutoSize = true;
            label8.Font = new Font("Segoe UI", 9F, FontStyle.Bold);
            label8.Location = new Point(30, 133);
            label8.Name = "label8";
            label8.Size = new Size(36, 15);
            label8.TabIndex = 3;
            label8.Text = "Email";
            label8.Click += label8_Click;
            // 
            // label7
            // 
            label7.AutoSize = true;
            label7.Font = new Font("Segoe UI", 9F, FontStyle.Bold);
            label7.Location = new Point(30, 104);
            label7.Name = "label7";
            label7.Size = new Size(68, 15);
            label7.TabIndex = 2;
            label7.Text = "Student ID";
            label7.Click += label7_Click;
            // 
            // label6
            // 
            label6.AutoSize = true;
            label6.Font = new Font("Segoe UI", 9F, FontStyle.Bold, GraphicsUnit.Point, 0);
            label6.Location = new Point(30, 74);
            label6.Name = "label6";
            label6.Size = new Size(40, 15);
            label6.TabIndex = 1;
            label6.Text = "Name";
            // 
            // lblStudentProfileTitle
            // 
            lblStudentProfileTitle.AutoSize = true;
            lblStudentProfileTitle.Font = new Font("Segoe UI Semibold", 17.25F, FontStyle.Bold, GraphicsUnit.Point, 0);
            lblStudentProfileTitle.ForeColor = Color.DarkGreen;
            lblStudentProfileTitle.Location = new Point(13, 10);
            lblStudentProfileTitle.Name = "lblStudentProfileTitle";
            lblStudentProfileTitle.Size = new Size(168, 31);
            lblStudentProfileTitle.TabIndex = 0;
            lblStudentProfileTitle.Text = "Student Profile";
            // 
            // pnlAccountSettings
            // 
            pnlAccountSettings.BackColor = Color.White;
            pnlAccountSettings.BorderStyle = BorderStyle.FixedSingle;
            pnlAccountSettings.Controls.Add(btnChangePassword);
            pnlAccountSettings.Controls.Add(txtConfirmNewPassword);
            pnlAccountSettings.Controls.Add(txtNewPassword);
            pnlAccountSettings.Controls.Add(label14);
            pnlAccountSettings.Controls.Add(label13);
            pnlAccountSettings.Controls.Add(txtCurrentPassword);
            pnlAccountSettings.Controls.Add(label12);
            pnlAccountSettings.Controls.Add(lblAccountSub);
            pnlAccountSettings.Controls.Add(lblAccountSettingsTitle);
            pnlAccountSettings.Dock = DockStyle.Fill;
            pnlAccountSettings.Location = new Point(384, 11);
            pnlAccountSettings.Name = "pnlAccountSettings";
            pnlAccountSettings.Size = new Size(867, 257);
            pnlAccountSettings.TabIndex = 1;
            pnlAccountSettings.Paint += pnlAccountSettings_Paint;
            // 
            // btnChangePassword
            // 
            btnChangePassword.BackColor = Color.SeaGreen;
            btnChangePassword.FlatStyle = FlatStyle.Flat;
            btnChangePassword.ForeColor = Color.White;
            btnChangePassword.Location = new Point(697, 180);
            btnChangePassword.Name = "btnChangePassword";
            btnChangePassword.Size = new Size(140, 35);
            btnChangePassword.TabIndex = 15;
            btnChangePassword.Text = "Change Password";
            btnChangePassword.UseVisualStyleBackColor = false;
            btnChangePassword.Click += ChangePassword;
            // 
            // txtConfirmNewPassword
            // 
            txtConfirmNewPassword.Location = new Point(222, 147);
            txtConfirmNewPassword.Name = "txtConfirmNewPassword";
            txtConfirmNewPassword.Size = new Size(617, 23);
            txtConfirmNewPassword.TabIndex = 14;
            // 
            // txtNewPassword
            // 
            txtNewPassword.Location = new Point(222, 110);
            txtNewPassword.Name = "txtNewPassword";
            txtNewPassword.Size = new Size(617, 23);
            txtNewPassword.TabIndex = 13;
            // 
            // label14
            // 
            label14.AutoSize = true;
            label14.Font = new Font("Segoe UI", 9F, FontStyle.Bold);
            label14.Location = new Point(37, 152);
            label14.Name = "label14";
            label14.Size = new Size(136, 15);
            label14.TabIndex = 12;
            label14.Text = "Confirm New Password";
            // 
            // label13
            // 
            label13.AutoSize = true;
            label13.Font = new Font("Segoe UI", 9F, FontStyle.Bold);
            label13.Location = new Point(37, 115);
            label13.Name = "label13";
            label13.Size = new Size(88, 15);
            label13.TabIndex = 11;
            label13.Text = "New Password";
            // 
            // txtCurrentPassword
            // 
            txtCurrentPassword.Location = new Point(222, 73);
            txtCurrentPassword.Name = "txtCurrentPassword";
            txtCurrentPassword.Size = new Size(617, 23);
            txtCurrentPassword.TabIndex = 10;
            // 
            // label12
            // 
            label12.AutoSize = true;
            label12.Font = new Font("Segoe UI", 9F, FontStyle.Bold);
            label12.Location = new Point(37, 78);
            label12.Name = "label12";
            label12.Size = new Size(105, 15);
            label12.TabIndex = 9;
            label12.Text = "Current Password";
            // 
            // lblAccountSub
            // 
            lblAccountSub.AutoSize = true;
            lblAccountSub.Location = new Point(15, 40);
            lblAccountSub.Name = "lblAccountSub";
            lblAccountSub.Size = new Size(236, 15);
            lblAccountSub.TabIndex = 8;
            lblAccountSub.Text = "Update your password and security settings";
            // 
            // lblAccountSettingsTitle
            // 
            lblAccountSettingsTitle.AutoSize = true;
            lblAccountSettingsTitle.Font = new Font("Segoe UI Semibold", 15.75F, FontStyle.Bold, GraphicsUnit.Point, 0);
            lblAccountSettingsTitle.ForeColor = Color.DarkGreen;
            lblAccountSettingsTitle.Location = new Point(15, 10);
            lblAccountSettingsTitle.Name = "lblAccountSettingsTitle";
            lblAccountSettingsTitle.Size = new Size(174, 30);
            lblAccountSettingsTitle.TabIndex = 0;
            lblAccountSettingsTitle.Text = "Account Security";
            // 
            // pnlPreferences
            // 
            pnlPreferences.AutoScroll = true;
            pnlPreferences.BackColor = Color.White;
            pnlPreferences.BorderStyle = BorderStyle.FixedSingle;
            pnlPreferences.Controls.Add(btnResetPreferences);
            pnlPreferences.Controls.Add(label15);
            pnlPreferences.Controls.Add(chkShowRecentRequests);
            pnlPreferences.Controls.Add(chkShowUpcomingMeetings);
            pnlPreferences.Controls.Add(lblNotifications);
            pnlPreferences.Controls.Add(btnSavePreferences);
            pnlPreferences.Controls.Add(chkStatusNotifications);
            pnlPreferences.Controls.Add(chkConsultationReminders);
            pnlPreferences.Controls.Add(chkEmailNotifications);
            pnlPreferences.Controls.Add(lblDefaultTab);
            pnlPreferences.Controls.Add(lblPreferencesSub);
            pnlPreferences.Controls.Add(cmbDefaultTab);
            pnlPreferences.Controls.Add(lblPreferencesTitle);
            pnlPreferences.Dock = DockStyle.Fill;
            pnlPreferences.Location = new Point(384, 274);
            pnlPreferences.Name = "pnlPreferences";
            pnlPreferences.Size = new Size(867, 138);
            pnlPreferences.TabIndex = 2;
            pnlPreferences.Paint += pnlPreferences_Paint;
            // 
            // btnResetPreferences
            // 
            btnResetPreferences.BackColor = Color.CornflowerBlue;
            btnResetPreferences.FlatStyle = FlatStyle.Flat;
            btnResetPreferences.ForeColor = Color.White;
            btnResetPreferences.Location = new Point(167, 112);
            btnResetPreferences.Name = "btnResetPreferences";
            btnResetPreferences.Size = new Size(90, 35);
            btnResetPreferences.TabIndex = 22;
            btnResetPreferences.Text = "Refresh";
            btnResetPreferences.UseVisualStyleBackColor = false;
            btnResetPreferences.Click += btnResetPreferences_Click;
            // 
            // label15
            // 
            label15.AutoSize = true;
            label15.Font = new Font("Segoe UI", 9F, FontStyle.Bold, GraphicsUnit.Point, 0);
            label15.Location = new Point(579, 61);
            label15.Name = "label15";
            label15.Size = new Size(95, 15);
            label15.TabIndex = 21;
            label15.Text = "Display Settings";
            // 
            // chkShowRecentRequests
            // 
            chkShowRecentRequests.AutoSize = true;
            chkShowRecentRequests.Location = new Point(592, 102);
            chkShowRecentRequests.Name = "chkShowRecentRequests";
            chkShowRecentRequests.Size = new Size(185, 19);
            chkShowRecentRequests.TabIndex = 20;
            chkShowRecentRequests.Text = "Show recent meeting requests";
            chkShowRecentRequests.UseVisualStyleBackColor = true;
            // 
            // chkShowUpcomingMeetings
            // 
            chkShowUpcomingMeetings.AutoSize = true;
            chkShowUpcomingMeetings.Location = new Point(592, 81);
            chkShowUpcomingMeetings.Name = "chkShowUpcomingMeetings";
            chkShowUpcomingMeetings.Size = new Size(218, 19);
            chkShowUpcomingMeetings.TabIndex = 19;
            chkShowUpcomingMeetings.Text = "Show upcoming meetings on Home";
            chkShowUpcomingMeetings.UseVisualStyleBackColor = true;
            // 
            // lblNotifications
            // 
            lblNotifications.AutoSize = true;
            lblNotifications.Font = new Font("Segoe UI Semibold", 9.75F, FontStyle.Bold, GraphicsUnit.Point, 0);
            lblNotifications.Location = new Point(297, 57);
            lblNotifications.Name = "lblNotifications";
            lblNotifications.Size = new Size(84, 17);
            lblNotifications.TabIndex = 16;
            lblNotifications.Text = "Notifications";
            // 
            // btnSavePreferences
            // 
            btnSavePreferences.BackColor = Color.SeaGreen;
            btnSavePreferences.FlatStyle = FlatStyle.Flat;
            btnSavePreferences.ForeColor = Color.White;
            btnSavePreferences.Location = new Point(33, 111);
            btnSavePreferences.Name = "btnSavePreferences";
            btnSavePreferences.Size = new Size(110, 35);
            btnSavePreferences.TabIndex = 7;
            btnSavePreferences.Text = "Save Changes";
            btnSavePreferences.UseVisualStyleBackColor = false;
            btnSavePreferences.Click += btnSavePreferences_Click;
            // 
            // chkStatusNotifications
            // 
            chkStatusNotifications.AutoSize = true;
            chkStatusNotifications.Location = new Point(302, 123);
            chkStatusNotifications.Name = "chkStatusNotifications";
            chkStatusNotifications.Size = new Size(240, 19);
            chkStatusNotifications.TabIndex = 6;
            chkStatusNotifications.Text = "Notifications for meeting status changes";
            chkStatusNotifications.UseVisualStyleBackColor = true;
            // 
            // chkConsultationReminders
            // 
            chkConsultationReminders.AutoSize = true;
            chkConsultationReminders.Location = new Point(302, 102);
            chkConsultationReminders.Name = "chkConsultationReminders";
            chkConsultationReminders.Size = new Size(232, 19);
            chkConsultationReminders.TabIndex = 5;
            chkConsultationReminders.Text = "Reminders for upcoming consultations";
            chkConsultationReminders.UseVisualStyleBackColor = true;
            // 
            // chkEmailNotifications
            // 
            chkEmailNotifications.AutoSize = true;
            chkEmailNotifications.Location = new Point(302, 81);
            chkEmailNotifications.Name = "chkEmailNotifications";
            chkEmailNotifications.Size = new Size(234, 19);
            chkEmailNotifications.TabIndex = 4;
            chkEmailNotifications.Text = "Email notifications for meeting updates";
            chkEmailNotifications.UseVisualStyleBackColor = true;
            // 
            // lblDefaultTab
            // 
            lblDefaultTab.AutoSize = true;
            lblDefaultTab.Font = new Font("Segoe UI", 9F, FontStyle.Bold, GraphicsUnit.Point, 0);
            lblDefaultTab.Location = new Point(21, 63);
            lblDefaultTab.Name = "lblDefaultTab";
            lblDefaultTab.Size = new Size(71, 15);
            lblDefaultTab.TabIndex = 3;
            lblDefaultTab.Text = "Default Tab";
            // 
            // lblPreferencesSub
            // 
            lblPreferencesSub.AutoSize = true;
            lblPreferencesSub.Font = new Font("Segoe UI", 9F);
            lblPreferencesSub.Location = new Point(17, 37);
            lblPreferencesSub.Name = "lblPreferencesSub";
            lblPreferencesSub.Size = new Size(211, 15);
            lblPreferencesSub.TabIndex = 2;
            lblPreferencesSub.Text = "Customize your application experience";
            // 
            // cmbDefaultTab
            // 
            cmbDefaultTab.DropDownStyle = ComboBoxStyle.DropDownList;
            cmbDefaultTab.FormattingEnabled = true;
            cmbDefaultTab.Location = new Point(26, 81);
            cmbDefaultTab.Name = "cmbDefaultTab";
            cmbDefaultTab.Size = new Size(242, 23);
            cmbDefaultTab.TabIndex = 1;
            // 
            // lblPreferencesTitle
            // 
            lblPreferencesTitle.AutoSize = true;
            lblPreferencesTitle.Font = new Font("Segoe UI Semibold", 15.75F, FontStyle.Bold, GraphicsUnit.Point, 0);
            lblPreferencesTitle.ForeColor = Color.DarkGreen;
            lblPreferencesTitle.Location = new Point(17, 11);
            lblPreferencesTitle.Name = "lblPreferencesTitle";
            lblPreferencesTitle.Size = new Size(123, 30);
            lblPreferencesTitle.TabIndex = 0;
            lblPreferencesTitle.Text = "Preferences";
            // 
            // panel5
            // 
            panel5.BackColor = Color.White;
            panel5.BorderStyle = BorderStyle.FixedSingle;
            panel5.Controls.Add(lblLastLogin);
            panel5.Controls.Add(lblVersion);
            panel5.Controls.Add(lblAppInfoSub);
            panel5.Controls.Add(lblAppInforHeading);
            panel5.Dock = DockStyle.Fill;
            panel5.Location = new Point(11, 274);
            panel5.Name = "panel5";
            panel5.Size = new Size(367, 138);
            panel5.TabIndex = 3;
            // 
            // lblLastLogin
            // 
            lblLastLogin.AutoSize = true;
            lblLastLogin.Font = new Font("Segoe UI Semibold", 10F, FontStyle.Bold);
            lblLastLogin.Location = new Point(30, 92);
            lblLastLogin.Name = "lblLastLogin";
            lblLastLogin.Size = new Size(73, 19);
            lblLastLogin.TabIndex = 5;
            lblLastLogin.Text = "Last Login";
            // 
            // lblVersion
            // 
            lblVersion.AutoSize = true;
            lblVersion.Font = new Font("Segoe UI Semibold", 10F, FontStyle.Bold);
            lblVersion.Location = new Point(30, 73);
            lblVersion.Name = "lblVersion";
            lblVersion.Size = new Size(55, 19);
            lblVersion.TabIndex = 3;
            lblVersion.Text = "Version";
            // 
            // lblAppInfoSub
            // 
            lblAppInfoSub.AutoSize = true;
            lblAppInfoSub.Font = new Font("Segoe UI", 9F, FontStyle.Regular, GraphicsUnit.Point, 0);
            lblAppInfoSub.Location = new Point(18, 38);
            lblAppInfoSub.Name = "lblAppInfoSub";
            lblAppInfoSub.Size = new Size(127, 15);
            lblAppInfoSub.TabIndex = 2;
            lblAppInfoSub.Text = "Current system details.";
            // 
            // lblAppInforHeading
            // 
            lblAppInforHeading.AutoSize = true;
            lblAppInforHeading.Font = new Font("Segoe UI Semibold", 14.25F, FontStyle.Bold, GraphicsUnit.Point, 0);
            lblAppInforHeading.ForeColor = Color.DarkGreen;
            lblAppInforHeading.Location = new Point(18, 13);
            lblAppInforHeading.Name = "lblAppInforHeading";
            lblAppInforHeading.Size = new Size(218, 25);
            lblAppInforHeading.TabIndex = 1;
            lblAppInforHeading.Text = "Application Information";
            // 
            // pnlSettingsHeader
            // 
            pnlSettingsHeader.BackColor = Color.White;
            pnlSettingsHeader.BorderStyle = BorderStyle.FixedSingle;
            pnlSettingsHeader.Controls.Add(lblSettingsSubtitle);
            pnlSettingsHeader.Controls.Add(lblSettingsTitle);
            pnlSettingsHeader.Dock = DockStyle.Top;
            pnlSettingsHeader.Location = new Point(0, 0);
            pnlSettingsHeader.Name = "pnlSettingsHeader";
            pnlSettingsHeader.Size = new Size(1262, 86);
            pnlSettingsHeader.TabIndex = 0;
            // 
            // lblSettingsSubtitle
            // 
            lblSettingsSubtitle.AutoSize = true;
            lblSettingsSubtitle.ForeColor = Color.DimGray;
            lblSettingsSubtitle.Location = new Point(16, 42);
            lblSettingsSubtitle.Name = "lblSettingsSubtitle";
            lblSettingsSubtitle.Size = new Size(189, 15);
            lblSettingsSubtitle.TabIndex = 1;
            lblSettingsSubtitle.Text = "Manage your account information";
            // 
            // lblSettingsTitle
            // 
            lblSettingsTitle.AutoSize = true;
            lblSettingsTitle.Font = new Font("Segoe UI", 18F, FontStyle.Bold, GraphicsUnit.Point, 0);
            lblSettingsTitle.ForeColor = Color.DarkGreen;
            lblSettingsTitle.Location = new Point(11, 10);
            lblSettingsTitle.Name = "lblSettingsTitle";
            lblSettingsTitle.Size = new Size(106, 32);
            lblSettingsTitle.TabIndex = 1;
            lblSettingsTitle.Text = "Settings";
            lblSettingsTitle.Click += label6_Click;
            // 
            // dateTimePicker2
            // 
            dateTimePicker2.CustomFormat = "YYYY-MM-DD, HH:MM";
            dateTimePicker2.Location = new Point(381, 355);
            dateTimePicker2.Name = "dateTimePicker2";
            dateTimePicker2.Size = new Size(200, 23);
            dateTimePicker2.TabIndex = 16;
            // 
            // StudentForm
            // 
            AutoScaleDimensions = new SizeF(7F, 15F);
            AutoScaleMode = AutoScaleMode.Font;
            BackColor = Color.White;
            ClientSize = new Size(1270, 637);
            Controls.Add(tabStudent);
            Controls.Add(pblHeader);
            Name = "StudentForm";
            StartPosition = FormStartPosition.CenterScreen;
            Text = "Student Portal";
            FormClosed += StudentForm_FormClosed;
            Load += Student_Load;
            pblHeader.ResumeLayout(false);
            pblHeader.PerformLayout();
            tabPage3.ResumeLayout(false);
            pnlRequestsHeader.ResumeLayout(false);
            pnlRequestsHeader.PerformLayout();
            ((System.ComponentModel.ISupportInitialize)dgvMyRequests).EndInit();
            pnlNotification.ResumeLayout(false);
            pnlNotification.PerformLayout();
            tableLayoutPanel1.ResumeLayout(false);
            panel4.ResumeLayout(false);
            panel4.PerformLayout();
            panel3.ResumeLayout(false);
            panel3.PerformLayout();
            panel2.ResumeLayout(false);
            panel2.PerformLayout();
            panel1.ResumeLayout(false);
            panel1.PerformLayout();
            pnlHomeTitle.ResumeLayout(false);
            pnlHomeTitle.PerformLayout();
            tabStudent.ResumeLayout(false);
            tabPage1.ResumeLayout(false);
            tabPage1.PerformLayout();
            ((System.ComponentModel.ISupportInitialize)dgvFacultyHours).EndInit();
            pnlRequestTitle.ResumeLayout(false);
            pnlRequestTitle.PerformLayout();
            tabPage2.ResumeLayout(false);
            tlpSettingsLayout.ResumeLayout(false);
            pnlStudentProfile.ResumeLayout(false);
            pnlStudentProfile.PerformLayout();
            pnlAccountSettings.ResumeLayout(false);
            pnlAccountSettings.PerformLayout();
            pnlPreferences.ResumeLayout(false);
            pnlPreferences.PerformLayout();
            panel5.ResumeLayout(false);
            panel5.PerformLayout();
            pnlSettingsHeader.ResumeLayout(false);
            pnlSettingsHeader.PerformLayout();
            ResumeLayout(false);
        }

        #endregion

        private Panel pblHeader;
        private Label lblPortalTitle;
        private Label label1;
        private TabPage tabPage3;
        private Panel pnlRequestsHeader;
        private Button btnCancelRequest;
        private DataGridView dgvMyRequests;
        private DataGridViewTextBoxColumn request_id;
        private DataGridViewTextBoxColumn faculty_name;
        private DataGridViewTextBoxColumn meeting_date;
        private DataGridViewTextBoxColumn start_time;
        private DataGridViewTextBoxColumn end_time;
        private DataGridViewTextBoxColumn status;
        private DataGridViewTextBoxColumn faculty_remarks;
        private Label label3;
        private Panel pnlNotification;
        private Button btnMarkAsRead;
        private Label lblNotification;
        private TableLayoutPanel tableLayoutPanel1;
        private Panel panel4;
        private Label lblCompletedCount;
        private Label lblCompletedTitle;
        private Panel panel3;
        private Label lblPendingCount;
        private Label lblPendingTitle;
        private Panel panel2;
        private Label lblApprovedCount;
        private Label lblApprovedTitle;
        private Panel panel1;
        private Label lblTotalCount;
        private Label lblTotalTitle;
        private Label label2;
        private TabControl tabStudent;
        private Panel pnlHomeTitle;
        private Label lblHomeTitle;
        private TabPage tabPage1;
        private Label lblRequestTitle;
        private Panel pnlRequestTitle;
        private ComboBox cmbFaculty;
        private Label lblFaculty;
        private Label label4;
        private DataGridView dgvFacultyHours;
        private DataGridViewTextBoxColumn colDay;
        private DataGridViewTextBoxColumn colStartTime;
        private DataGridViewTextBoxColumn colEndTime;
        private DataGridViewTextBoxColumn colLocation;
        private Label label5;
        private DateTimePicker dtpMeetingDate;
        private Label lblMeetingDate;
        private Label lblEndTime;
        private DateTimePicker dtpStartTime;
        private Label lblStartTime;
        private DateTimePicker dtpEndTime;
        private Label lbPurposeofMeeting;
        private Button btnSubmitRequest;
        private RichTextBox txtPurposeOfTheMeeting;
        private DateTimePicker dateTimePicker2;
        private TabPage tabPage2;
        private TableLayoutPanel tlpSettingsLayout;
        private Panel pnlSettingsHeader;
        private Label lblSettingsSubtitle;
        private Label lblSettingsTitle;
        private Panel pnlStudentProfile;
        private Label lblStudentProfileTitle;
        private TextBox textBox1;
        private TextBox txtStudentProgram;
        private TextBox txtStudentEmail;
        private TextBox txtStudentId;
        private TextBox txtStudentName;
        private Label label11;
        private Label label10;
        private Label label9;
        private Label label8;
        private Label label7;
        private Label label6;
        private ComboBox cmbYearLevel;
        private Button btnSaveSettings;
        private Button btnCancelSettings;
        private Button btnLogout;
        private Label lblProfilSub;
        private Panel pnlAccountSettings;
        private Label lblAccountSettingsTitle;
        private TextBox txtCurrentPassword;
        private Label label12;
        private Label lblAccountSub;
        private TextBox txtConfirmNewPassword;
        private TextBox txtNewPassword;
        private Label label14;
        private Label label13;
        private Button btnChangePassword;
        private Panel pnlPreferences;
        private Label lblPreferencesTitle;
        private ComboBox cmbDefaultTab;
        private Label lblPreferencesSub;
        private Label lblDefaultTab;
        private CheckBox chkConsultationReminders;
        private CheckBox chkEmailNotifications;
        private CheckBox chkStatusNotifications;
        private Button btnSavePreferences;
        private Label lblNotifications;
        private CheckBox chkShowRecentRequests;
        private CheckBox chkShowUpcomingMeetings;
        private Label label15;
        private Button btnResetPreferences;
        private Panel panel5;
        private Label lblAppInforHeading;
        private Label lblAppInfoSub;
        private Label lblVersion;
        private Label lblLastLogin;
    }
}
