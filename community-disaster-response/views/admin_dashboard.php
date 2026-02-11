<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header('Location: admin_login.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="../assets/css/main.css?v=1.0.1">
    <link rel="stylesheet" href="../assets/css/admin.css?v=1.0.1">
</head>
<body>
    <header>
        <div style="display: flex; justify-content: space-between; align-items: center;">
            <h1>Admin Dashboard</h1>
            <button id="logoutButton" class="button button-secondary">Logout</button>
        </div>
    </header>

    <main style="padding-top: 1rem;">
        
        <img src="../assets/bg2.jpg" alt="Community Disaster Response" style="width: 100%; height: 100%; object-fit: cover; position:fixed; top: 0; left: 0; z-index: -1; opacity: 0.5; filter: blur(10px); ">
        <!-- <div style="margin-bottom: 1rem;">
            <p>Welcome, <strong>?php echo htmlspecialchars($_SESSION['admin_username']); ?></strong></p>
        </div> -->

        <button class="mobile-sidebar-toggle" id="mobileSidebarToggle">☰ Menu</button>
        <div class="mobile-sidebar" id="mobileSidebar">
            <ul class="mobile-sidebar-nav">
                <li><a href="#" data-target="dashboardSection" class="sidebar-link active">Dashboard</a></li>
                <li><a href="#" data-target="volunteersSection" class="sidebar-link">Volunteers</a></li>
                <li><a href="#" data-target="reportsSection" class="sidebar-link">Reports</a></li>
                <li><a href="#" data-target="helpRequestsSection" class="sidebar-link">Help Requests</a></li>
                <li><a href="#" data-target="evacuationCentersSection" class="sidebar-link">Evacuation Centers</a></li>
                <li><a href="#" data-target="donationsSection" class="sidebar-link">Donations</a></li>
                <li><a href="#" data-target="guidesSection" class="sidebar-link">Preparedness Guides</a></li>
            </ul>
        </div>

        <div class="dashboard-layout">
            <aside class="sidebar">
                <ul class="sidebar-nav">
                    <li><a href="#" data-target="dashboardSection" class="sidebar-link active">Dashboard</a></li>
                    <li><a href="#" data-target="volunteersSection" class="sidebar-link">Volunteers</a></li>
                    <li><a href="#" data-target="reportsSection" class="sidebar-link">Reports</a></li>
                    <li><a href="#" data-target="helpRequestsSection" class="sidebar-link">Help Requests</a></li>
                    <li><a href="#" data-target="evacuationCentersSection" class="sidebar-link">Evacuation Centers</a></li>
                    <li><a href="#" data-target="donationsSection" class="sidebar-link">Donations</a></li>
                    <li><a href="#" data-target="guidesSection" class="sidebar-link">Preparedness Guides</a></li>
                </ul>
            </aside>

            <div class="dashboard-content">
                <!-- Dashboard Overview Section -->
                <section id="dashboardSection" class="dashboard-section active">
                    <h2>Dashboard Overview</h2>
                    <div class="stats-grid" id="statsGrid">
                        <!-- Stats will be loaded via AJAX -->
                    </div>
                </section>

                <!-- Volunteers Section -->
                <section id="volunteersSection" class="dashboard-section">
                    <h2>Volunteer Applications</h2>
                    <div class="card">
                        <div class="table-container">
                            <div id="volunteersContainer">
                                <!-- Volunteers will be loaded here via AJAX -->
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Emergency Reports Section -->
                <section id="reportsSection" class="dashboard-section">
                    <h2>Emergency Reports</h2>
                    <div class="card">
                        <div class="table-container">
                            <div id="emergencyReportsContainer">
                                <!-- Emergency reports will be loaded here via AJAX -->
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Help Requests Section -->
                <section id="helpRequestsSection" class="dashboard-section">
                    <h2>Help Requests</h2>
                    <div class="card">
                        <div class="table-container">
                            <div id="helpRequestsContainer">
                                <!-- Help requests will be loaded here via AJAX -->
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Evacuation Centers Section -->
                <section id="evacuationCentersSection" class="dashboard-section">
                    <h2>Evacuation Centers</h2>
                    <div class="card">
                        <p><button type="button" id="addEvacuationCenterBtn" class="button button-primary">Add Evacuation Center</button></p>
                        <div class="table-container">
                            <div id="evacuationCentersContainer">
                                <!-- Loaded via AJAX -->
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Preparedness Guides Section -->
                <section id="guidesSection" class="dashboard-section">
                    <h2>Preparedness Guides</h2>
                    <div class="card">
                        <p><button type="button" id="addGuideBtn" class="button button-primary">Add Guide</button></p>
                        <div class="table-container">
                            <div id="guidesContainer"></div>
                        </div>
                    </div>
                </section>

                <!-- Donations Section -->
                <section id="donationsSection" class="dashboard-section">
                    <h2>Donations</h2>
                    <div class="card">
                        <div class="table-container">
                            <div id="donationsContainer">
                                <!-- Donations will be loaded here via AJAX -->
                            </div>
                        </div>
                    </div>
                </section>
            </div>
        </div>
    </main>

    <!-- Modal: Add/Edit Evacuation Center -->
    <div id="evacCenterModal" class="modal" style="display: none;">
        <div class="modal-content">
            <span class="modal-close" id="evacCenterModalClose">&times;</span>
            <h3 id="evacCenterModalTitle">Add Evacuation Center</h3>
            <form id="evacCenterForm">
                <input type="hidden" id="evacCenterId" name="id" value="">
                <div class="form-group">
                    <label for="evacCenterName">Center Name</label>
                    <input type="text" id="evacCenterName" name="name" required>
                </div>
                <div class="form-group">
                    <label for="evacCenterAddress">Address / Location</label>
                    <input type="text" id="evacCenterAddress" name="address" required>
                </div>
                <div class="form-group">
                    <label for="evacCenterCapacity">Capacity</label>
                    <input type="number" id="evacCenterCapacity" name="capacity" min="0" value="0">
                </div>
                <div class="form-group">
                    <label for="evacCenterStatus">Status</label>
                    <select id="evacCenterStatus" name="status">
                        <option value="Available">Available</option>
                        <option value="Full">Full</option>
                        <option value="Closed">Closed</option>
                        <option value="Open">Open</option>
                    </select>
                </div>
                <button type="submit" class="button button-primary">Save</button>
                <button type="button" class="button button-secondary" id="evacCenterModalCancel">Cancel</button>
            </form>
        </div>
    </div>

    <!-- Generic Edit Modal (for Volunteers, Reports, Help, Donations) -->
    <div id="genericEditModal" class="modal" style="display: none;">
        <div class="modal-content">
            <span class="modal-close" id="genericEditModalClose">&times;</span>
            <h3 id="genericEditModalTitle">Edit</h3>
            <div id="genericEditModalBody"></div>
            <p style="margin-top: 1rem;">
                <button type="button" class="button button-primary" id="genericEditModalSave">Save</button>
                <button type="button" class="button button-secondary" id="genericEditModalCancel">Cancel</button>
            </p>
        </div>
    </div>

    <!-- Modal: Add/Edit Preparedness Guide -->
    <div id="guideModal" class="modal" style="display: none;">
        <div class="modal-content">
            <span class="modal-close" id="guideModalClose">&times;</span>
            <h3 id="guideModalTitle">Add Guide</h3>
            <form id="guideForm">
                <input type="hidden" id="guideId" value="">
                <div class="form-group">
                    <label for="guideTitle">Title</label>
                    <input type="text" id="guideTitle" required>
                </div>
                <div class="form-group">
                    <label for="guideCategory">Category</label>
                    <select id="guideCategory">
                        <option value="Earthquake Preparedness">Earthquake Preparedness</option>
                        <option value="Flood Preparedness">Flood Preparedness</option>
                        <option value="Typhoon Readiness">Typhoon Readiness</option>
                        <option value="Fire Safety">Fire Safety</option>
                        <option value="Emergency Go-Bag Checklist">Emergency Go-Bag Checklist</option>
                        <option value="Other">Other</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="guideContent">Content</label>
                    <textarea id="guideContent" rows="6" required></textarea>
                </div>
                <button type="submit" class="button button-primary">Save</button>
                <button type="button" class="button button-secondary" id="guideModalCancel">Cancel</button>
            </form>
        </div>
    </div>

    <footer>
        <p>&copy; <?php echo date('Y'); ?> Community Disaster Response</p>
    </footer>
    <!-- SweetAlert v1 from CDN -->
    <script src="https://unpkg.com/sweetalert/dist/sweetalert.min.js"></script>
    <script src="../assets/libs/sweetalert.min.js"></script>
    <script src="../assets/js/admin.js"></script>
</body>
</html>
