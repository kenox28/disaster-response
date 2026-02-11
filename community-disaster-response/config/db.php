<?php
// Database connection using MySQLi
$db_host = 'localhost';
$db_user = 'root';
$db_pass = '';
$db_name = 'community_disaster_response'; // Change to your actual DB name

$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);

if ($conn->connect_error) {
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode([
        'status' => 'error',
        'message' => 'Database connection failed',
        'data' => []
    ]);
    exit;
}

// Set charset
$conn->set_charset('utf8mb4');

// Auto-create tables if they do not exist

// Admins table
$conn->query("
    CREATE TABLE IF NOT EXISTS admins (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(50) NOT NULL UNIQUE,
        password_hash VARCHAR(255) NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
");

// Emergency reports
$conn->query("
    CREATE TABLE IF NOT EXISTS emergency_reports (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        location VARCHAR(255) NOT NULL,
        emergency_type VARCHAR(100) NOT NULL,
        description TEXT NOT NULL,
        status VARCHAR(50) NOT NULL DEFAULT 'Pending',
        image_path VARCHAR(255) DEFAULT '',
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
");
@$conn->query("ALTER TABLE emergency_reports ADD COLUMN image_path VARCHAR(255) DEFAULT ''");

// Help requests
$conn->query("
    CREATE TABLE IF NOT EXISTS help_requests (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        help_type VARCHAR(100) NOT NULL,
        description TEXT NOT NULL,
        status VARCHAR(50) NOT NULL DEFAULT 'Pending',
        image_path VARCHAR(255) DEFAULT '',
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
");
@$conn->query("ALTER TABLE help_requests ADD COLUMN image_path VARCHAR(255) DEFAULT ''");

// Volunteers
$conn->query("
    CREATE TABLE IF NOT EXISTS volunteers (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        full_name VARCHAR(255) NOT NULL,
        skills VARCHAR(255) NOT NULL,
        availability VARCHAR(255) NOT NULL,
        username VARCHAR(100) UNIQUE,
        password_hash VARCHAR(255),
        email VARCHAR(255),
        profile_picture VARCHAR(255),
        gender VARCHAR(20),
        birthday DATE,
        age TINYINT UNSIGNED DEFAULT 0,
        total_emergency_help INT DEFAULT 0,
        total_help_requests INT DEFAULT 0,
        status ENUM('Pending','Approved','Rejected') NOT NULL DEFAULT 'Pending',
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
");

// Ensure new volunteer columns exist in case table was created earlier
@$conn->query("ALTER TABLE volunteers ADD COLUMN username VARCHAR(100) UNIQUE");
@$conn->query("ALTER TABLE volunteers ADD COLUMN password_hash VARCHAR(255)");
@$conn->query("ALTER TABLE volunteers ADD COLUMN email VARCHAR(255)");
@$conn->query("ALTER TABLE volunteers ADD COLUMN profile_picture VARCHAR(255)");
@$conn->query("ALTER TABLE volunteers ADD COLUMN gender VARCHAR(20)");
@$conn->query("ALTER TABLE volunteers ADD COLUMN birthday DATE");
@$conn->query("ALTER TABLE volunteers ADD COLUMN age TINYINT UNSIGNED DEFAULT 0");
@$conn->query("ALTER TABLE volunteers ADD COLUMN total_emergency_help INT DEFAULT 0");
@$conn->query("ALTER TABLE volunteers ADD COLUMN total_help_requests INT DEFAULT 0");
@$conn->query("ALTER TABLE volunteers MODIFY COLUMN status ENUM('Pending','Approved','Rejected') NOT NULL DEFAULT 'Pending'");

// Donations
$conn->query("
    CREATE TABLE IF NOT EXISTS donations (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        donor_name VARCHAR(255) NOT NULL,
        donation_type VARCHAR(100) NOT NULL,
        quantity VARCHAR(50) NOT NULL,
        status VARCHAR(50) NOT NULL DEFAULT 'Pending',
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
");

// Alerts
$conn->query("
    CREATE TABLE IF NOT EXISTS alerts (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(255) NOT NULL,
        message TEXT NOT NULL,
        severity VARCHAR(50) NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
");

// Evacuation centers (status: Available, Full, Closed)
$conn->query("
    CREATE TABLE IF NOT EXISTS evacuation_centers (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        address VARCHAR(255) NOT NULL,
        capacity INT UNSIGNED DEFAULT 0,
        status VARCHAR(50) NOT NULL DEFAULT 'Available'
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
");

// Preparedness guides
$conn->query("
    CREATE TABLE IF NOT EXISTS preparedness_guides (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(255) NOT NULL,
        content TEXT NOT NULL,
        category VARCHAR(100),
        is_archived TINYINT(1) DEFAULT 0
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
");
@$conn->query("ALTER TABLE preparedness_guides ADD COLUMN category VARCHAR(100)");
@$conn->query("ALTER TABLE preparedness_guides ADD COLUMN is_archived TINYINT(1) DEFAULT 0");

// Pending volunteer registrations for OTP verification
$conn->query("
    CREATE TABLE IF NOT EXISTS volunteer_pending_registrations (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        email VARCHAR(255) NOT NULL,
        username VARCHAR(100) NOT NULL,
        password_hash VARCHAR(255) NOT NULL,
        full_name VARCHAR(255) NOT NULL,
        skills VARCHAR(255) NOT NULL,
        availability VARCHAR(255) NOT NULL,
        gender VARCHAR(20) NOT NULL,
        birthday DATE NOT NULL,
        age TINYINT UNSIGNED NOT NULL,
        otp_code VARCHAR(10) NOT NULL,
        otp_expires_at DATETIME NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
");
$conn->query("
    CREATE TABLE IF NOT EXISTS volunteer_password_resets (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        email VARCHAR(255) NOT NULL,
        otp_code VARCHAR(10) NOT NULL,
        otp_expires_at DATETIME NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
");

// Volunteer history
$conn->query("
    CREATE TABLE IF NOT EXISTS volunteer_history (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        volunteer_id INT UNSIGNED NOT NULL,
        type ENUM('EmergencyReport','HelpRequest','Donation') NOT NULL,
        description TEXT NOT NULL,
        date_submitted DATETIME DEFAULT CURRENT_TIMESTAMP,
        CONSTRAINT fk_volunteer_history_volunteer
            FOREIGN KEY (volunteer_id) REFERENCES volunteers(id)
            ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
");

// Volunteer assignments (track which volunteers commit to help)
$conn->query("
    CREATE TABLE IF NOT EXISTS volunteer_assignments (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        volunteer_id INT UNSIGNED NOT NULL,
        request_type ENUM('EmergencyReport','HelpRequest') NOT NULL,
        request_id INT UNSIGNED NOT NULL,
        date_assigned DATETIME DEFAULT CURRENT_TIMESTAMP,
        CONSTRAINT fk_volunteer_assignments_volunteer
            FOREIGN KEY (volunteer_id) REFERENCES volunteers(id)
            ON DELETE CASCADE,
        UNIQUE KEY unique_assignment (volunteer_id, request_type, request_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
");


