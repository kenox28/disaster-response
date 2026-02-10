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
$otp   = isset($input['otp']) ? trim($input['otp']) : '';
$password = isset($input['password']) ? $input['password'] : '';

if ($email === '' || $otp === '' || $password === '') {
    echo json_encode(['status' => 'error', 'message' => 'Email, OTP and new password are required', 'data' => []]);
    exit;
}

// Password strength: at least 8 chars, one number, one special character
if (strlen($password) < 8 || !preg_match('/[0-9]/', $password) || !preg_match('/[^A-Za-z0-9]/', $password)) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Password must be at least 8 characters and include a number and a special character',
        'data' => []
    ]);
    exit;
}

$now = (new DateTime())->format('Y-m-d H:i:s');
$stmt = $conn->prepare('SELECT id FROM volunteer_password_resets WHERE email = ? AND otp_code = ? AND otp_expires_at >= ? LIMIT 1');
$stmt->bind_param('sss', $email, $otp, $now);
$stmt->execute();
$res = $stmt->get_result();
$reset = $res->fetch_assoc();
$stmt->close();

if (!$reset) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid or expired OTP', 'data' => []]);
    exit;
}

// Update password
$hash = password_hash($password, PASSWORD_DEFAULT);
$stmt = $conn->prepare('UPDATE volunteers SET password_hash = ? WHERE email = ?');
$stmt->bind_param('ss', $hash, $email);
if (!$stmt->execute()) {
    $stmt->close();
    echo json_encode(['status' => 'error', 'message' => 'Failed to update password', 'data' => []]);
    exit;
}
$stmt->close();

// Delete reset request
$stmt = $conn->prepare('DELETE FROM volunteer_password_resets WHERE email = ?');
$stmt->bind_param('s', $email);
$stmt->execute();
$stmt->close();

// Confirmation email (non-fatal)
$stmt = $conn->prepare('SELECT full_name FROM volunteers WHERE email = ? LIMIT 1');
$stmt->bind_param('s', $email);
$stmt->execute();
$r = $stmt->get_result();
$v = $r->fetch_assoc();
$stmt->close();

cdr_send_mail(
    $email,
    $v['full_name'] ?? $email,
    'Password Reset Successful',
    '<p>Your password has been reset successfully.</p>',
    'Your password has been reset successfully.'
);

echo json_encode(['status' => 'success', 'message' => 'Password reset successful', 'data' => []]);

