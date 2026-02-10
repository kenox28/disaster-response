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

$donor_name = isset($input['donor_name']) ? trim($input['donor_name']) : '';
$donation_type = isset($input['donation_type']) ? trim($input['donation_type']) : '';
$quantity = isset($input['quantity']) ? trim($input['quantity']) : '';

if ($donor_name === '' || $donation_type === '' || $quantity === '') {
    echo json_encode([
        'status' => 'error',
        'message' => 'All fields are required'
    ]);
    exit;
}

$status = 'Pending';

$stmt = $conn->prepare(
    'INSERT INTO donations (donor_name, donation_type, quantity, status, created_at)
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

$stmt->bind_param('ssss', $donor_name, $donation_type, $quantity, $status);

if ($stmt->execute()) {
    echo json_encode([
        'status' => 'success',
        'message' => 'Donation pledge submitted'
    ]);
} else {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Failed to submit donation pledge'
    ]);
}

$stmt->close();
$conn->close();


