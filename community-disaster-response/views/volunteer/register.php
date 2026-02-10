<?php
// Volunteer registration page
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Volunteer Registration</title>
    <link rel="stylesheet" href="../../assets/css/main.css">
    <link rel="stylesheet" href="../../assets/css/volunteer.css">
</head>
<body>
    <header>
        <h1>Community Disaster Response - Volunteer</h1>
    </header>

    <main>
        <div class="register-container">
            <div class="register-card">
                <h2>Volunteer Registration</h2>
                <!-- Step 1: Registration form -->
                <form id="volunteerRegisterForm">
                    <div class="form-group">
                        <label for="reg_username">Username</label>
                        <input type="text" id="reg_username" name="username" required>
                    </div>
                    <div class="form-group">
                        <label for="reg_password">Password</label>
                        <input type="password" id="reg_password" name="password" required>
                        <small id="passwordStrength" class="password-strength">
                            • Minimum 8 characters<br>
                            • Must include a number<br>
                            • Must include a special character
                        </small>
                    </div>
                    <div class="form-group">
                        <label for="reg_email">Email</label>
                        <input type="email" id="reg_email" name="email" required>
                    </div>
                    <div class="form-group">
                        <label for="reg_full_name">Full Name</label>
                        <input type="text" id="reg_full_name" name="full_name" required>
                    </div>
                    <div class="form-group">
                        <label for="reg_skills">Skills</label>
                        <input type="text" id="reg_skills" name="skills" required>
                    </div>
                    <div class="form-group">
                        <label for="reg_availability">Availability</label>
                        <input type="text" id="reg_availability" name="availability" required>
                    </div>
                    <button type="submit" class="button button-primary button-block">Register</button>
                </form>

                <!-- Step 2: OTP verification form -->
                <form id="volunteerOtpForm" style="display:none; margin-top:1rem;">
                    <div class="form-group">
                        <label for="otp_email">Email</label>
                        <input type="email" id="otp_email" name="email" readonly>
                    </div>
                    <div class="form-group">
                        <label for="otp_code">Enter OTP sent to your email</label>
                        <input type="text" id="otp_code" name="otp" maxlength="6" required>
                    </div>
                    <button type="submit" class="button button-primary button-block">Verify OTP</button>
                </form>

                <div class="register-link">
                    <a href="login.php">Already have an account? Login</a> |
                    <a href="../index.php">Back to Homepage</a>
                </div>
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
