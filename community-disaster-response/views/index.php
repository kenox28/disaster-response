<?php
// Public homepage
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Community Disaster Response</title>
    <link rel="stylesheet" href="../assets/css/main.css?v=1.0.2">
</head>
<body>
    <header>
        <div style="display: flex; justify-content: space-between; align-items: center;">
            <h1>Community Disaster Response</h1>
            <button class="hamburger" id="hamburgerMenu">☰</button>
        </div>
        <nav id="desktopNav">
            <ul>
                <li><a href="index.php" class="active">Home</a></li>
                <li><a href="#about">About</a></li>
                <li><a href="#contact">Contact</a></li>
                <li><a href="volunteer/login.php">Volunteer</a></li>
                <li><a href="admin_login.php">Admin</a></li>
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
        <img src="../assets/bg2.jpg" alt="Community Disaster Response" style="width: 100%; height: 100%; object-fit: cover; position:fixed; top: 0; left: 0; z-index: -1; opacity: 0.5; filter: blur(10px); ">
        <!-- Emergency Report / Request Help - Quick actions -->
        <section class="hero">
            <div class="hero-content">
                <h2>Welcome to Community Disaster Response</h2>
                <p>Your trusted platform for emergency reporting, assistance requests, and community support during disasters.</p>
                <div style="display: flex; gap: 1rem; flex-wrap: wrap;">
                    <a href="#reportEmergency" class="button button-primary">Report Emergency</a>
                    <a href="#requestHelp" class="button button-secondary">Request Help</a>
                    <a href="volunteer/register.php" class="button button-secondary">Volunteer Register</a>
                    <!-- <a href="admin_login.php" class="button button-secondary">Admin Login</a> -->
                </div>
            </div>
            <div class="hero-image"></div>
        </section>

        <!-- Real-Time Weather Forecast (Philippines) -->
        <section class="section weather-panel" id="weatherPanel">
            <h2>Live Weather – Philippines </h2>
            <div class="card weather-card">
                <div id="weatherForecastContainer">
                    <p>Loading weather data...</p>
                </div>
            </div>
        </section>

        <!-- Real-Time Disaster Alert Panel (API-Based) - Philippines only -->
        <section class="section disaster-alert-panel" id="disasterAlertPanel">
            <h2>Real-Time Disaster Alerts</h2>
            <div class="card alert-panel-card">
                <div id="disasterAlertsContainer">
                    <p>Loading disaster alerts...</p>
                </div>
                <p id="disasterAlertsLastUpdated" class="alert-last-updated"></p>
            </div>
        </section>

        <!-- Report Emergency Form -->
        <section class="section" id="reportEmergency">
            <div class="card">
                <h2>Report Emergency</h2>
                <form id="reportEmergencyForm" enctype="multipart/form-data">
                    <div class="form-group">
                        <label for="location">Location:</label>
                        <input type="text" id="location" name="location" required>
                    </div>
                    <div class="form-group">
                        <label for="emergency_type">Emergency Type:</label>
                        <select id="emergency_type" name="emergency_type" required>
                            <option value="">Select type</option>
                            <option value="Fire">Fire</option>
                            <option value="Flood">Flood</option>
                            <option value="Earthquake">Earthquake</option>
                            <option value="Typhoon">Typhoon</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="description">Description:</label>
                        <textarea id="description" name="description" required></textarea>
                    </div>
                    <div class="form-group">
                        <label for="emergency_photo">Photo (optional, JPG/PNG, max 2MB):</label>
                        <div class="file-upload" id="emergencyPhotoWrapper">
                            <div class="file-upload-placeholder">
                                <span class="file-upload-icon">📷</span>
                                <div class="file-upload-text-group">
                                    <span class="file-upload-title">Click to upload image</span>
                                    <span class="file-upload-subtitle">Supported formats: JPG, PNG (max 2MB)</span>
                                    <span class="file-upload-filename" id="emergencyPhotoFilename"></span>
                                </div>
                            </div>
                            <div class="file-upload-preview" id="emergencyPhotoPreview"></div>
                            <input type="file" id="emergency_photo" name="photo" accept="image/jpeg,image/png">
                        </div>
                    </div>
                    <button type="submit" class="button button-primary button-block">Submit Emergency Report</button>
                </form>
                <div id="reportEmergencyMessage"></div>
            </div>
        </section>

        <!-- Request Help Form -->
        <!-- UPDATED REQUEST HELP SECTION - Copy and paste this to replace your existing Request Help section -->

        <section class="section" id="requestHelp">
            <div class="card">
                <h2>Request Help</h2>
                <form id="requestHelpForm" enctype="multipart/form-data">
                    <div class="form-group">
                        <label for="help_type">Help Type:</label>
                        <select id="help_type" name="help_type" required>
                            <option value="">Select help type</option>
                            <option value="Medical">Medical</option>
                            <option value="Food">Food</option>
                            <option value="Rescue">Rescue</option>
                            <option value="Shelter">Shelter</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="help_description">Description:</label>
                        <textarea id="help_description" name="help_description" required></textarea>
                    </div>
                    
                    <!-- UPDATED PHOTO UPLOAD SECTION -->
                    <div class="form-group">
                        <label for="help_photo">Photo (optional, JPG/PNG, max 2MB):</label>
                        <div class="file-upload" id="helpPhotoWrapper">
                            <div class="file-upload-placeholder">
                                <span class="file-upload-icon">📷</span>
                                <div class="file-upload-text-group">
                                    <span class="file-upload-title">Click to upload image</span>
                                    <span class="file-upload-subtitle">Supported formats: JPG, PNG (max 2MB)</span>
                                    <span class="file-upload-filename" id="helpPhotoFilename"></span>
                                </div>
                            </div>
                            <div class="file-upload-preview" id="helpPhotoPreview"></div>
                            <input type="file" id="help_photo" name="photo" accept="image/jpeg,image/png">
                        </div>
                    </div>
                    
                    <button type="submit" class="button button-primary button-block">Submit Help Request</button>
                </form>
                <div id="requestHelpMessage"></div>
            </div>
        </section>

        <!-- Volunteer Registration Form -->
        <!-- <section class="section">
            <div class="card">
                <h2>Volunteer Registration</h2>
                <form id="volunteerForm">
                    <div class="form-group">
                        <label for="full_name">Full Name:</label>
                        <input type="text" id="full_name" name="full_name" required>
                    </div>
                    <div class="form-group">
                        <label for="skills">Skills:</label>
                        <input type="text" id="skills" name="skills" required>
                    </div>
                    <div class="form-group">
                        <label for="availability">Availability:</label>
                        <input type="text" id="availability" name="availability" required>
                    </div>
                    <button type="submit" class="button button-primary button-block">Register as Volunteer</button>
                </form>
                <div id="volunteerMessage"></div>
            </div>
        </section> -->

        <!-- Donation Form -->
        <section class="section">
            <div class="card">
                <h2>Donation Form</h2>
                <form id="donationForm">
                    <div class="form-group">
                        <label for="donor_name">Donor Name:</label>
                        <input type="text" id="donor_name" name="donor_name" required>
                    </div>
                    <div class="form-group">
                        <label for="donation_type">Donation Type:</label>
                        <select id="donation_type" name="donation_type" required>
                            <option value="">Select donation type</option>
                            <option value="Food">Food</option>
                            <option value="Clothes">Clothes</option>
                            <option value="Medicine">Medicine</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="quantity">Quantity:</label>
                        <input type="text" id="quantity" name="quantity" required>
                    </div>
                    <button type="submit" class="button button-primary button-block">Submit Donation</button>
                </form>
                <div id="donationMessage"></div>
            </div>
        </section>

        <!-- Evacuation Centers Preview -->
        <section class="section" id="evacuationCenters">
            <h2>Evacuation Centers</h2>
            <div class="card">
                <p>Find safe locations during emergencies. Sorted by availability.</p>
                <div id="evacuationCentersContainer">
                    <!-- Loaded via AJAX -->
                </div>
            </div>
        </section>

        <!-- Preparedness Guides Preview -->
        <section class="section" id="preparednessGuides">
            <h2>Preparedness Guides</h2>
            <div class="card">
                <p>Learn how to prepare before, during, and after disasters. Click a guide to expand.</p>
                <div id="preparednessGuidesContainer">
                    <!-- Loaded via AJAX -->
                </div>
            </div>
        </section>

        <!-- Info Sections -->
        <section class="section" id="about">
            <h2>How to Help</h2>
            <div class="info-section">
                <div class="info-card">
                    <h3>Report Emergencies</h3>
                    <p>Help keep the community safe by reporting emergencies as soon as they occur.</p>
                </div>
                <div class="info-card">
                    <h3>Request Assistance</h3>
                    <p>If you need help during a disaster, submit a help request and our team will respond.</p>
                </div>
                <div class="info-card">
                    <h3>Volunteer</h3>
                    <p>Join our volunteer network and make a difference in your community.</p>
                </div>
                <div class="info-card">
                    <h3>Donate</h3>
                    <p>Contribute essential supplies to support disaster relief efforts.</p>
                </div>
            </div>
        </section>

        <section class="section" id="contact">
            <h2>Contact</h2>
            <div class="card">
                <p>For emergencies, please call 911 or your local emergency services.</p>
                <p>For general inquiries, please contact us through the forms above.</p>
            </div>
        </section>
    </main>

    <footer>
        <p>&copy; <?php echo date('Y'); ?> Community Disaster Response. All rights reserved.</p>
        <p>
            <a href="#about">About</a> |
            <a href="#contact">Contact</a> |
            <a href="volunteer/login.php">Volunteer Portal</a> |
            <a href="admin_login.php">Admin Portal</a>
        </p>
    </footer>
    <!-- SweetAlert v1 from CDN -->
    <script src="https://unpkg.com/sweetalert/dist/sweetalert.min.js"></script>
    <script src="../assets/js/main.js?v=1.0.3"></script>
</body>
</html>
