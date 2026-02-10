<?php
header('Content-Type: application/json');

session_start();

if (!isset($_SESSION['volunteer_id'])) {
    http_response_code(401);
    echo json_encode([
        'status' => 'error',
        'message' => 'Unauthorized',
        'data' => []
    ]);
    exit;
}

require_once __DIR__ . '/../../config/db.php';

$volunteerId = (int) $_SESSION['volunteer_id'];

// Fetch basic volunteer info including totals
$info = null;
$stmt = $conn->prepare('SELECT id, full_name, skills, availability, username, email, status, profile_picture, total_emergency_help, total_help_requests FROM volunteers WHERE id = ?');
if ($stmt) {
    $stmt->bind_param('i', $volunteerId);
    $stmt->execute();
    $result = $stmt->get_result();
    $info = $result->fetch_assoc();
    $stmt->close();
}

// Achievements based on totals from volunteers table and history
$achievements = [
    'total_emergency_help' => (int)($info['total_emergency_help'] ?? 0),
    'total_help_requests' => (int)($info['total_help_requests'] ?? 0),
    'total_donations' => 0
];

// Get donations count from history
$stmt = $conn->prepare("
    SELECT COUNT(*) AS total
    FROM volunteer_history
    WHERE volunteer_id = ? AND type = 'Donation'
");

if ($stmt) {
    $stmt->bind_param('i', $volunteerId);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $achievements['total_donations'] = (int)($row['total'] ?? 0);
    $stmt->close();
}

echo json_encode([
    'status' => 'success',
    'message' => 'Dashboard data fetched',
    'data' => [
        'info' => $info,
        'achievements' => $achievements
    ]
]);

$conn->close();


