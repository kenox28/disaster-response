<?php
header('Content-Type: application/json');

session_start();

if (!isset($_SESSION['admin_id'])) {
    http_response_code(401);
    echo json_encode([
        'status' => 'error',
        'message' => 'Unauthorized',
        'data' => []
    ]);
    exit;
}

require_once __DIR__ . '/../../config/db.php';

// Get counts with proper WHERE clauses
$stats = [];

// Total volunteers
$stmt = $conn->prepare("SELECT COUNT(*) AS total FROM volunteers");
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$stats['total_volunteers'] = (int)$row['total'];
$stmt->close();

// Approved volunteers
$stmt = $conn->prepare("SELECT COUNT(*) AS total FROM volunteers WHERE status = 'Approved'");
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$stats['approved_volunteers'] = (int)$row['total'];
$stmt->close();

// Pending volunteers
$stmt = $conn->prepare("SELECT COUNT(*) AS total FROM volunteers WHERE status = 'Pending'");
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$stats['pending_volunteers'] = (int)$row['total'];
$stmt->close();

// Total emergency reports
$stmt = $conn->prepare("SELECT COUNT(*) AS total FROM emergency_reports");
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$stats['total_emergencies'] = (int)$row['total'];
$stmt->close();

// Active (Pending) emergency reports
$stmt = $conn->prepare("SELECT COUNT(*) AS total FROM emergency_reports WHERE status = 'Pending'");
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$stats['active_emergencies'] = (int)$row['total'];
$stmt->close();

// Total help requests
$stmt = $conn->prepare("SELECT COUNT(*) AS total FROM help_requests");
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$stats['total_help_requests'] = (int)$row['total'];
$stmt->close();

// Active (Pending) help requests
$stmt = $conn->prepare("SELECT COUNT(*) AS total FROM help_requests WHERE status = 'Pending'");
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$stats['active_help_requests'] = (int)$row['total'];
$stmt->close();

// Total donations
$stmt = $conn->prepare("SELECT COUNT(*) AS total FROM donations");
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$stats['total_donations'] = (int)$row['total'];
$stmt->close();

$data = [
    'stats' => $stats
];

echo json_encode([
    'status' => 'success',
    'message' => 'Dashboard data fetched',
    'data' => $data
]);

$conn->close();


