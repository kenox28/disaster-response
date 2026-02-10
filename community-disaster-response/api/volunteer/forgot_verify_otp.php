<?php
header('Content-Type: application/json');

require_once __DIR__ . '/../../config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method not allowed', 'data' => []]);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$email = isset($input['email']) ? trim($input['email']) : '';
$otp   = isset($input['otp']) ? trim($input['otp']) : '';

if ($email === '' || $otp === '') {
    echo json_encode(['status' => 'error', 'message' => 'Email and OTP are required', 'data' => []]);
    exit;
}

$now = (new DateTime())->format('Y-m-d H:i:s');
$stmt = $conn->prepare('SELECT id FROM volunteer_password_resets WHERE email = ? AND otp_code = ? AND otp_expires_at >= ? LIMIT 1');
$stmt->bind_param('sss', $email, $otp, $now);
$stmt->execute();
$res = $stmt->get_result();
$row = $res->fetch_assoc();
$stmt->close();

if (!$row) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid or expired OTP', 'data' => []]);
    exit;
}

echo json_encode(['status' => 'success', 'message' => 'OTP verified', 'data' => []]);

