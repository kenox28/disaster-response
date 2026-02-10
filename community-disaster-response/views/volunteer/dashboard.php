<?php
session_start();
if (!isset($_SESSION['volunteer_id'])) {
    header('Location: login.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Volunteer Dashboard</title>
    <link rel="stylesheet" href="../../assets/css/main.css">
    <link rel="stylesheet" href="../../assets/css/volunteer.css?v=1.0.2">
</head>
<body>
    <header>
        <div style="display: flex; justify-content: space-between; align-items: center; gap: 1rem;">
            <div style="display:flex; align-items:center; gap:0.75rem;">
                <img id="profileAvatar" src="../../assets/img/default-avatar.svg" alt="Profile" style="width:44px;height:44px;border-radius:999px;object-fit:cover;border:1px solid #ddd;">
                <div>
                    <h1 style="margin:0;">Volunteer Dashboard</h1>
                    <small>Signed in as <strong id="volunteerNameHeader"><?php echo htmlspecialchars($_SESSION['volunteer_name']); ?></strong></small>
                </div>
            </div>
            <div style="display:flex; align-items:center; gap:0.5rem; flex-wrap:wrap;">
                <nav class="dashboard-nav" style="display:flex; gap:0.5rem; flex-wrap:wrap;">
                    <button class="nav-button active" data-target="feedSection" type="button">Volunteer Feed</button>
                    <button class="nav-button" data-target="historySection" type="button">History</button>
                    <button class="nav-button" data-target="profileSection" type="button">Profile</button>
                </nav>
                <button id="volunteerLogoutButton" class="button button-secondary" type="button">Logout</button>
            </div>
        </div>
    </header>

    <main>
        <section id="feedSection" class="dashboard-section active">
            <div class="card">
                <h2>Volunteer Feed</h2>
                <p style="margin-top:0;">Active emergency reports and help requests you can assist with.</p>
                <div id="feedContainer"><!-- Filled by AJAX --></div>
            </div>
        </section>

        <section id="historySection" class="dashboard-section">
            <div class="card">
                <h2>History</h2>
                <div id="historyContent">
                    <!-- Filled by AJAX -->
                </div>
            </div>
        </section>

        <section id="profileSection" class="dashboard-section">
            <div class="card">
                <h2>Profile</h2>

                <div class="card" style="margin-top: 1rem;">
                    <h3 style="margin-top:0;">Achievements</h3>
                    <ul style="margin:0; padding-left: 1.25rem;">
                        <li>Emergency Help Completed: <strong id="ach_emergency_help">0</strong></li>
                        <li>Help Requests Completed: <strong id="ach_help_requests">0</strong></li>
                        <li>Total Donations: <strong id="ach_donations">0</strong></li>
                    </ul>
                </div>

                <div class="card" style="margin-top: 1rem;">
                    <h3 style="margin-top:0;">Profile Picture</h3>
                    <form id="profilePictureForm" enctype="multipart/form-data">
                        <div class="form-group">
                            <label for="profile_picture">Upload (JPG/PNG/WEBP, max 2MB)</label>
                            <input type="file" id="profile_picture" name="profile_picture" accept="image/jpeg,image/png,image/webp" required>
                        </div>
                        <button type="submit" class="button button-primary">Upload Picture</button>
                    </form>
                </div>

                <h3 style="margin-top: 1.5rem;">Edit Details</h3>
                <form id="profileForm" class="profile-form">
                    <div class="form-group">
                        <label for="profile_full_name">Full Name</label>
                        <input type="text" id="profile_full_name" name="full_name" required>
                    </div>
                    <div class="form-group">
                        <label for="profile_skills">Skills</label>
                        <input type="text" id="profile_skills" name="skills" required>
                    </div>
                    <div class="form-group">
                        <label for="profile_availability">Availability</label>
                        <input type="text" id="profile_availability" name="availability" required>
                    </div>
                    <div class="form-group">
                        <label for="profile_password">Password (leave blank to keep current)</label>
                        <input type="password" id="profile_password" name="password">
                    </div>
                    <button type="submit" class="button button-primary">Save Profile</button>
                </form>
            </div>
        </section>
    </main>

    <footer>
        <p>&copy; <?php echo date('Y'); ?> Community Disaster Response</p>
    </footer>

    <script src="../../assets/libs/sweetalert.min.js"></script>
    <script src="../../assets/js/volunteer.js"></script>
</body>
</html>
