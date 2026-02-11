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

$volunteer_id = $_SESSION['volunteer_id'];

// Get all pending emergency reports with assignment info
$emergency_reports = [];
$stmt = $conn->prepare("
    SELECT 
        er.id,
        er.location,
        er.emergency_type,
        er.description,
        er.status,
        er.created_at,
        er.image_path,
        COUNT(va.id) AS assigned_count,
        MAX(CASE WHEN va.volunteer_id = ? THEN 1 ELSE 0 END) AS is_assigned
    FROM emergency_reports er
    LEFT JOIN volunteer_assignments va ON va.request_type = 'EmergencyReport' AND va.request_id = er.id
    WHERE er.status = 'Pending'
    GROUP BY er.id
    ORDER BY er.created_at DESC
");

if ($stmt) {
    $stmt->bind_param('i', $volunteer_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $emergency_reports[] = [
            'id' => $row['id'],
            'type' => 'EmergencyReport',
            'location' => $row['location'],
            'emergency_type' => $row['emergency_type'],
            'description' => $row['description'],
            'status' => $row['status'],
            'created_at' => $row['created_at'],
            'image_path' => $row['image_path'],
            'image_url' => $row['image_path'] ? '../../' . $row['image_path'] : '',
            'assigned_count' => (int)$row['assigned_count'],
            'is_assigned' => (bool)$row['is_assigned']
        ];
    }
    $stmt->close();
}

// Get all pending help requests with assignment info
$help_requests = [];
$stmt = $conn->prepare("
    SELECT 
        hr.id,
        hr.help_type,
        hr.description,
        hr.status,
        hr.created_at,
        hr.image_path,
        COUNT(va.id) AS assigned_count,
        MAX(CASE WHEN va.volunteer_id = ? THEN 1 ELSE 0 END) AS is_assigned
    FROM help_requests hr
    LEFT JOIN volunteer_assignments va ON va.request_type = 'HelpRequest' AND va.request_id = hr.id
    WHERE hr.status = 'Pending'
    GROUP BY hr.id
    ORDER BY hr.created_at DESC
");

if ($stmt) {
    $stmt->bind_param('i', $volunteer_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $help_requests[] = [
            'id' => $row['id'],
            'type' => 'HelpRequest',
            'help_type' => $row['help_type'],
            'description' => $row['description'],
            'status' => $row['status'],
            'created_at' => $row['created_at'],
            'image_path' => $row['image_path'],
            'image_url' => $row['image_path'] ? '../../' . $row['image_path'] : '',
            'assigned_count' => (int)$row['assigned_count'],
            'is_assigned' => (bool)$row['is_assigned']
        ];
    }
    $stmt->close();
}

// Combine and sort by date
$feed = array_merge($emergency_reports, $help_requests);
usort($feed, function($a, $b) {
    return strtotime($b['created_at']) - strtotime($a['created_at']);
});

echo json_encode([
    'status' => 'success',
    'message' => 'Feed loaded successfully',
    'data' => $feed
]);

$conn->close();

