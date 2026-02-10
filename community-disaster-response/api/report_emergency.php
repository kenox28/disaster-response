<?php
header('Content-Type: application/json');

require_once __DIR__ . '/../config/db.php';

// Allow only POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'status' => 'error',
        'message' => 'Method not allowed'
    ]);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

$location = isset($input['location']) ? trim($input['location']) : '';
$emergency_type = isset($input['emergency_type']) ? trim($input['emergency_type']) : '';
$description = isset($input['description']) ? trim($input['description']) : '';

if ($location === '' || $emergency_type === '' || $description === '') {
    echo json_encode([
        'status' => 'error',
        'message' => 'All fields are required'
    ]);
    exit;
}

$status = 'Pending';

$stmt = $conn->prepare(
    'INSERT INTO emergency_reports (location, emergency_type, description, status, created_at)
     VALUES (?, ?, ?, ?, NOW())'
);

if (!$stmt) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Failed to prepare statement'
    ]);
    exit;
}

$stmt->bind_param('ssss', $location, $emergency_type, $description, $status);

if ($stmt->execute()) {
    echo json_encode([
        'status' => 'success',
        'message' => 'Emergency report submitted'
    ]);
} else {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Failed to submit report'
    ]);
}

$stmt->close();
$conn->close();


