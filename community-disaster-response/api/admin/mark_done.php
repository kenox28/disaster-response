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

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'status' => 'error',
        'message' => 'Method not allowed',
        'data' => []
    ]);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$request_type = isset($input['request_type']) ? $input['request_type'] : '';
$request_id = isset($input['request_id']) ? (int)$input['request_id'] : 0;

if ($request_type === '' || $request_id === 0) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Invalid request type or ID',
        'data' => []
    ]);
    exit;
}

if (!in_array($request_type, ['EmergencyReport', 'HelpRequest'])) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Invalid request type',
        'data' => []
    ]);
    exit;
}

// Get assigned volunteers before marking done
$stmt = $conn->prepare('
    SELECT volunteer_id 
    FROM volunteer_assignments 
    WHERE request_type = ? AND request_id = ?
');
$stmt->bind_param('si', $request_type, $request_id);
$stmt->execute();
$result = $stmt->get_result();
$assigned_volunteers = [];
while ($row = $result->fetch_assoc()) {
    $assigned_volunteers[] = $row['volunteer_id'];
}
$stmt->close();

// Update request status to Done
if ($request_type === 'EmergencyReport') {
    $stmt = $conn->prepare('UPDATE emergency_reports SET status = ? WHERE id = ?');
} else {
    $stmt = $conn->prepare('UPDATE help_requests SET status = ? WHERE id = ?');
}

$status = 'Done';
$stmt->bind_param('si', $status, $request_id);

if (!$stmt->execute()) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Failed to update request status',
        'data' => []
    ]);
    $stmt->close();
    $conn->close();
    exit;
}
$stmt->close();

// Increment volunteer totals and log to history
foreach ($assigned_volunteers as $volunteer_id) {
    if ($request_type === 'EmergencyReport') {
        // Increment total_emergency_help
        $stmt = $conn->prepare('UPDATE volunteers SET total_emergency_help = total_emergency_help + 1 WHERE id = ?');
        $stmt->bind_param('i', $volunteer_id);
        $stmt->execute();
        $stmt->close();
        
        // Get request details for history
        $stmt = $conn->prepare('SELECT location, emergency_type, description FROM emergency_reports WHERE id = ?');
        $stmt->bind_param('i', $request_id);
        $stmt->execute();
        $req_result = $stmt->get_result();
        $req_data = $req_result->fetch_assoc();
        $stmt->close();
        
        $description = 'Emergency: ' . $req_data['emergency_type'] . ' at ' . $req_data['location'] . ' - ' . $req_data['description'];
    } else {
        // Increment total_help_requests
        $stmt = $conn->prepare('UPDATE volunteers SET total_help_requests = total_help_requests + 1 WHERE id = ?');
        $stmt->bind_param('i', $volunteer_id);
        $stmt->execute();
        $stmt->close();
        
        // Get request details for history
        $stmt = $conn->prepare('SELECT help_type, description FROM help_requests WHERE id = ?');
        $stmt->bind_param('i', $request_id);
        $stmt->execute();
        $req_result = $stmt->get_result();
        $req_data = $req_result->fetch_assoc();
        $stmt->close();
        
        $description = 'Help Request: ' . $req_data['help_type'] . ' - ' . $req_data['description'];
    }
    
    // Log to volunteer_history
    $history_type = $request_type === 'EmergencyReport' ? 'EmergencyReport' : 'HelpRequest';
    $stmt = $conn->prepare('INSERT INTO volunteer_history (volunteer_id, type, description) VALUES (?, ?, ?)');
    $stmt->bind_param('iss', $volunteer_id, $history_type, $description);
    $stmt->execute();
    $stmt->close();
}

echo json_encode([
    'status' => 'success',
    'message' => 'Request marked as done. Volunteer totals updated.',
    'data' => [
        'volunteers_updated' => count($assigned_volunteers)
    ]
]);

$conn->close();

