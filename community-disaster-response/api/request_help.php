<?php
header('Content-Type: application/json');

require_once __DIR__ . '/../config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'status' => 'error',
        'message' => 'Method not allowed'
    ]);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

$help_type = isset($input['help_type']) ? trim($input['help_type']) : '';
$description = isset($input['description']) ? trim($input['description']) : '';

if ($help_type === '' || $description === '') {
    echo json_encode([
        'status' => 'error',
        'message' => 'All fields are required'
    ]);
    exit;
}

$status = 'Pending';

$stmt = $conn->prepare(
    'INSERT INTO help_requests (help_type, description, status, created_at)
     VALUES (?, ?, ?, NOW())'
);

if (!$stmt) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Failed to prepare statement'
    ]);
    exit;
}

$stmt->bind_param('sss', $help_type, $description, $status);

if ($stmt->execute()) {
    echo json_encode([
        'status' => 'success',
        'message' => 'Help request submitted'
    ]);
} else {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Failed to submit help request'
    ]);
}

$stmt->close();
$conn->close();


