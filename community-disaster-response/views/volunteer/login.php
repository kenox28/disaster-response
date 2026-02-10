<?php
// Volunteer login page
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Volunteer Login</title>
    <link rel="stylesheet" href="../../assets/css/main.css">
    <link rel="stylesheet" href="../../assets/css/volunteer.css">
</head>
<body>
    <header>
        <h1>Community Disaster Response - Volunteer</h1>
    </header>

    <main>
        <div class="login-container">
            <div class="login-card">
                <h2>Volunteer Login</h2>
                <!-- LOGIN FORM -->
                <form id="volunteerLoginForm">
                    <div class="form-group">
                        <label for="email_or_username">Email or Username</label>
                        <input type="text" id="email_or_username" name="email_or_username" required>
                    </div>
                    <div class="form-group">
                        <label for="password">Password</label>
                        <input type="password" id="password" name="password" required>
                    </div>
                    <button type="submit" class="button button-primary button-block">Login</button>

                </form>
                <div class="login-link">
                    <a href="#" id="forgotPasswordLink">Forgot Password?</a> | 
                    <a href="register.php">Don't have an account? Register</a>
                    <a href="../index.php">Back to Homepage</a>
                </div>
                
                <!-- FORGOT PASSWORD FLOW (Email + OTP) -->
                <form id="forgotPasswordEmailForm" style="display:none; margin-top:1rem;">
                    <h3>Forgot Password</h3>
                    <div class="form-group">
                        <label for="forgot_email">Enter your registered email</label>
                        <input type="email" id="forgot_email" required>
                    </div>
                    <button type="submit" class="button button-primary button-block">Send OTP</button>
                    <button type="button" class="button button-secondary button-block" id="forgotBackToLogin1" style="margin-top:0.5rem;">Back to Login</button>
                </form>

                <form id="forgotPasswordOtpForm" style="display:none; margin-top:1rem;">
                    <h3>Verify OTP</h3>
                    <div class="form-group">
                        <label for="forgot_otp_email">Email</label>
                        <input type="email" id="forgot_otp_email" readonly>
                    </div>
                    <div class="form-group">
                        <label for="forgot_otp">Enter OTP</label>
                        <input type="text" id="forgot_otp" maxlength="6" required>
                    </div>
                    <button type="submit" class="button button-primary button-block">Verify OTP</button>
                    <button type="button" class="button button-secondary button-block" id="forgotBackToLogin2" style="margin-top:0.5rem;">Back to Login</button>
                </form>

                <form id="forgotPasswordResetForm" style="display:none; margin-top:1rem;">
                    <h3>Reset Password</h3>
                    <div class="form-group">
                        <label for="reset_email">Email</label>
                        <input type="email" id="reset_email" readonly>
                    </div>
                    <div class="form-group">
                        <label for="reset_otp">OTP</label>
                        <input type="text" id="reset_otp" readonly>
                    </div>
                    <div class="form-group">
                        <label for="reset_password">New Password</label>
                        <input type="password" id="reset_password" required>
                        <small id="resetPasswordStrength" class="password-strength">
                            • Minimum 8 characters<br>
                            • Must include a number<br>
                            • Must include a special character
                        </small>
                    </div>
                    <div class="form-group">
                        <label for="reset_password_confirm">Confirm Password</label>
                        <input type="password" id="reset_password_confirm" required>
                    </div>
                    <button type="submit" class="button button-primary button-block">Save New Password</button>
                    <button type="button" class="button button-secondary button-block" id="forgotBackToLogin3" style="margin-top:0.5rem;">Back to Login</button>
                </form>
            </div>
        </div>
    </main>

    <footer>
        <p>&copy; <?php echo date('Y'); ?> Community Disaster Response</p>
    </footer>

    <script src="../../assets/libs/sweetalert.min.js"></script>
    <script src="../../assets/js/volunteer.js"></script>
    
</body>
</html>
