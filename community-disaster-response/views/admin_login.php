<?php
// Admin login page
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login</title>
    <link rel="stylesheet" href="../assets/css/main.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body>
    <header>
        <h1>Community Disaster Response - Admin</h1>
        <nav id="desktopNav">
            <ul>
                <li><a href="index.php" class="active">Home</a></li>
                <li><a href="#about">About</a></li>
                <li><a href="#contact">Contact</a></li>
                <li><a href="volunteer/login.php">Volunteer Login</a></li>
                <li><a href="admin_login.php">Admin Login</a></li>
            </ul>
        </nav>  
        <nav class="mobile-nav" id="mobileNav">
            <a href="index.php">Home</a>
            <a href="#about">About</a>
            <a href="#contact">Contact</a>
            <a href="volunteer/login.php">Volunteer Login</a>
            <a href="admin_login.php">Admin Login</a>
        </nav>
    </header>

    <main>
        <div class="login-container">
            <div class="login-card">
                <h2>Admin Login</h2>
                <form id="adminLoginForm">
                    <div class="form-group">
                        <label for="username">Username</label>
                        <input type="text" id="username" name="username" required>
                    </div>
                    <div class="form-group">
                        <label for="password">Password</label>
                        <input type="password" id="password" name="password" required>
                    </div>
                    <button type="submit" class="button button-primary button-block">Login</button>
                </form>
                <div class="login-link">
                    <a href="index.php">Back to Homepage</a>
                </div>
            </div>
        </div>
    </main>

    <footer>
        <p>&copy; <?php echo date('Y'); ?> Community Disaster Response</p>
    </footer>

    <script src="../assets/libs/sweetalert.min.js"></script>
    <script src="../assets/js/admin.js"></script>
</body>
</html>
