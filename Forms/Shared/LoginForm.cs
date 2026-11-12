using System.Data;
using DCSSMeetingScheduler.Admin;
using DCSSMeetingScheduler.Faculty;
using DCSSMeetingScheduler.Forms.Student;
using DCSSMeetingScheduler.Shared;
using MySqlConnector;

namespace DCSSMeetingScheduler
{
    public partial class LoginForm : Form
    {
        public LoginForm()
        {
            InitializeComponent();
        }

        private void btnLogin_Click(object? sender, EventArgs e)
        {
            loginErrorProvider.Clear();
            lblErrorMessage.Text = "";
            string email = txtEmail.Text.Trim();
            bool valid = true;
            if (!LoginUtils.ValidateEmail(email))
            {
                loginErrorProvider.SetError(txtEmail, "Please enter a valid UE email.");
                valid = false;
            }
            if (!LoginUtils.ValidatePassword(txtPassword.Text))
            {
                loginErrorProvider.SetError(txtPassword, "Please enter a password of at least 6 characters.");
                valid = false;
            }
            if (!valid)
            {
                if (loginErrorProvider.GetError(txtEmail).Length > 0) txtEmail.Focus();
                else txtPassword.Focus();
                return;
            }

            User? user;
            try
            {
                using var conn = Connection.GetConnection();
                using var cmd = new MySqlCommand("SELECT * FROM users WHERE ue_email = @email AND user_password = @password", conn);
                cmd.Parameters.AddWithValue("@email", email);
                cmd.Parameters.AddWithValue("@password", txtPassword.Text);
                using var reader = cmd.ExecuteReader(CommandBehavior.SingleRow);
                user = reader.Read() ? User.FromReader(reader) : null;
            }
            catch (MySqlException)
            {
                MessageBox.Show(this, "Unable to connect to the database. Please try again.", "Login Error", MessageBoxButtons.OK, MessageBoxIcon.Error);
                return;
            }

            if (user == null || !user.UeEmail.Equals(email, StringComparison.OrdinalIgnoreCase) || user.UserPassword != txtPassword.Text)
            {
                loginErrorProvider.SetError(txtPassword, "Invalid email or password.");
                MessageBox.Show(this, "Invalid email or password. Please try again.", "Login Failed", MessageBoxButtons.OK, MessageBoxIcon.Error);
                txtPassword.SelectAll();
                txtPassword.Focus();
                return;
            }

            if (!user.IsActive)
            {
                if (user.Role == UserRole.Faculty)
                {
                    loginErrorProvider.SetError(txtEmail, "Faculty account pending administrator approval.");
                    MessageBox.Show(this, "Your faculty account is pending administrator approval. Please wait for an administrator to verify and activate your account.", "Pending Approval", MessageBoxButtons.OK, MessageBoxIcon.Information);
                }
                else
                {
                    loginErrorProvider.SetError(txtEmail, "Account disabled.");
                    MessageBox.Show(this, "Your account is currently disabled. Please contact the administrator.", "Account Disabled", MessageBoxButtons.OK, MessageBoxIcon.Warning);
                }
                return;
            }

            Form? dashboard = user.Role switch
            {
                UserRole.Admin => new AdminForm(user),
                UserRole.Faculty => new FacultyForm(user),
                UserRole.Student => new StudentForm(user),
                _ => null
            };
            if (dashboard == null)
            {
                MessageBox.Show(this, "This account has an unsupported role.", "Login Failed", MessageBoxButtons.OK, MessageBoxIcon.Error);
                return;
            }
            dashboard.Owner = this;
            Hide();
            dashboard.Show();
        }

        private void LoginInput_TextChanged(object? sender, EventArgs e)
        {
            if (sender is Control input)
                loginErrorProvider.SetError(input, "");
        }

        internal void DashboardClosed(Form dashboard)
        {
            dashboard.Owner = null;
            if (IsDisposed || Disposing)
                return;

            if (dashboard.DialogResult == DialogResult.Retry)
            {
                loginErrorProvider.Clear();
                txtPassword.Clear();
                chkShowPassword.Checked = false;
                lblErrorMessage.Text = "";
                Show();
                Activate();
                txtPassword.Focus();
            }
            else
            {
                Close();
            }
        }

        private void btnCancel_Click(object? sender, EventArgs e)
        {
            DialogResult = DialogResult.Cancel;
            Close();
        }

        private void chkShowPassword_CheckedChanged(object? sender, EventArgs e)
        {
            if (chkShowPassword.Checked)
            {
                txtPassword.UseSystemPasswordChar = false;
            }
            else
            {
                txtPassword.UseSystemPasswordChar = true;
            }
        }

        private void btnChangePassword_Click(object? sender, EventArgs e)
        {
            ChangePassword.ChangePasswordForm changePasswordForm = new ChangePassword.ChangePasswordForm();
            changePasswordForm.ShowDialog();
        }

        private void lnkForgotPassword_LinkClicked(object? sender, LinkLabelLinkClickedEventArgs e)
        {
            ChangePassword.ChangePasswordForm changePasswordForm = new ChangePassword.ChangePasswordForm();
            changePasswordForm.ShowDialog();
        }

        private void lnkSignUp_LinkClicked(object? sender, LinkLabelLinkClickedEventArgs e)
        {
            SignUpForm signUpForm = new SignUpForm();
            Hide();
            DialogResult result = signUpForm.ShowDialog();
            if (result == DialogResult.OK && !string.IsNullOrEmpty(signUpForm.RegisteredEmail))
            {
                txtEmail.Text = signUpForm.RegisteredEmail;
                txtPassword.Clear();
                txtPassword.Focus();
            }
            Show();
        }
    }
}
