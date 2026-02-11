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
    <title>Volunteer Feed</title>
    <link rel="stylesheet" href="../../assets/css/main.css">
    <link rel="stylesheet" href="../../assets/css/volunteer.css">
</head>
<body>
    <header>
        <div style="display: flex; justify-content: space-between; align-items: center; gap: 1rem;">
            <div style="display:flex; align-items:center; gap:0.75rem;">
                <img id="profileAvatar" src="../../assets/img/default-avatar.svg" alt="Profile" style="width:44px;height:44px;border-radius:999px;object-fit:cover;border:1px solid #ddd;">
                <div>
                    <h1 style="margin:0;">Volunteer Feed</h1>
                    <small>Signed in as <strong id="volunteerNameHeader"><?php echo htmlspecialchars($_SESSION['volunteer_name']); ?></strong></small>
                </div>
            </div>
            <div style="display: flex; gap: 1rem; align-items: center;">
                <a href="dashboard.php" class="button button-secondary">Dashboard</a>
                <button id="volunteerLogoutButton" class="button button-secondary" type="button">Logout</button>
            </div>
        </div>
    </header>

    <main>
        <img src="../../assets/main.webp" alt="Community Disaster Response" style="width: 100%; height: 100%; object-fit: cover; position:fixed; top: 0; left: 0; z-index: -1; opacity: 0.5; filter: blur(10px); ">
        <div style="margin-bottom: 1rem;">
            <p>Welcome, <strong><?php echo htmlspecialchars($_SESSION['volunteer_name']); ?></strong></p>
            <p>View active emergencies and help requests. Click "I'll Help" to commit to assisting.</p>
        </div>

        <div id="feedContainer">
            <!-- Feed items will be loaded here via AJAX -->
            <p>Loading feed...</p>
        </div>
    </main>

    <footer>
        <p>&copy; <?php echo date('Y'); ?> Community Disaster Response</p>
    </footer>
    <!-- SweetAlert v1 from CDN -->
    <script src="https://unpkg.com/sweetalert/dist/sweetalert.min.js"></script>
    <script src="../../assets/libs/sweetalert.min.js"></script>
    <script src="../../assets/js/volunteer.js"></script>
</body>
</html>

