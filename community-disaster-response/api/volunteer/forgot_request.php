<?php
header('Content-Type: application/json');

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../config/mailer.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method not allowed', 'data' => []]);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$email = isset($input['email']) ? trim($input['email']) : '';

if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['status' => 'error', 'message' => 'Valid email is required', 'data' => []]);
    exit;
}

// Ensure account exists
$stmt = $conn->prepare('SELECT id, full_name FROM volunteers WHERE email = ? LIMIT 1');
$stmt->bind_param('s', $email);
$stmt->execute();
$res = $stmt->get_result();
$v = $res->fetch_assoc();
$stmt->close();

if (!$v) {
    echo json_encode(['status' => 'error', 'message' => 'Email not found', 'data' => []]);
    exit;
}

// Delete existing reset requests for email
$stmt = $conn->prepare('DELETE FROM volunteer_password_resets WHERE email = ?');
$stmt->bind_param('s', $email);
$stmt->execute();
$stmt->close();

// Create OTP
$otp = str_pad((string)random_int(0, 999999), 6, '0', STR_PAD_LEFT);
$expires = (new DateTime('+10 minutes'))->format('Y-m-d H:i:s');

$stmt = $conn->prepare('INSERT INTO volunteer_password_resets (email, otp_code, otp_expires_at) VALUES (?, ?, ?)');
$stmt->bind_param('sss', $email, $otp, $expires);
if (!$stmt->execute()) {
    $stmt->close();
    echo json_encode(['status' => 'error', 'message' => 'Failed to create reset request', 'data' => []]);
    exit;
}
$stmt->close();

// Send OTP email
$send = cdr_send_mail(
    $email,
    $v['full_name'] ?? $email,
    'Password Reset OTP',
    '<p>Your password reset OTP is: <strong>' . htmlspecialchars($otp) . '</strong></p><p>This code expires in 10 minutes.</p>',
    'Your password reset OTP is: ' . $otp . ' (valid for 10 minutes).'
);
if (!$send['ok']) {
    echo json_encode(['status' => 'error', 'message' => 'Failed to send OTP email: ' . ($send['error'] ?? ''), 'data' => []]);
    exit;
}

echo json_encode(['status' => 'success', 'message' => 'OTP sent to your email', 'data' => ['email' => $email]]);

