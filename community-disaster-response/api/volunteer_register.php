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

$full_name = isset($input['full_name']) ? trim($input['full_name']) : '';
$skills = isset($input['skills']) ? trim($input['skills']) : '';
$availability = isset($input['availability']) ? trim($input['availability']) : '';

if ($full_name === '' || $skills === '' || $availability === '') {
    echo json_encode([
        'status' => 'error',
        'message' => 'All fields are required'
    ]);
    exit;
}

$status = 'Pending';

$stmt = $conn->prepare(
    'INSERT INTO volunteers (full_name, skills, availability, status, created_at)
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

$stmt->bind_param('ssss', $full_name, $skills, $availability, $status);

if ($stmt->execute()) {
    echo json_encode([
        'status' => 'success',
        'message' => 'Volunteer registration submitted'
    ]);
} else {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Failed to submit volunteer registration'
    ]);
}

$stmt->close();
$conn->close();


