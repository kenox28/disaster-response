<?php
header('Content-Type: application/json');

session_start();

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../config/mailer.php';

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
$email = isset($input['email']) ? trim($input['email']) : '';
$otp   = isset($input['otp']) ? trim($input['otp']) : '';

if ($email === '' || $otp === '') {
    echo json_encode([
        'status' => 'error',
        'message' => 'Email and OTP are required',
        'data' => []
    ]);
    exit;
}

$now = new DateTime();

// Find pending registration
$stmt = $conn->prepare('
    SELECT * FROM volunteer_pending_registrations
    WHERE email = ? AND otp_code = ? AND otp_expires_at >= ?
    LIMIT 1
');
if (!$stmt) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Failed to prepare statement', 'data' => []]);
    exit;
}
$currentTime = $now->format('Y-m-d H:i:s');
$stmt->bind_param('sss', $email, $otp, $currentTime);
$stmt->execute();
$result = $stmt->get_result();
$pending = $result->fetch_assoc();
$stmt->close();

if (!$pending) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Invalid or expired OTP',
        'data' => []
    ]);
    exit;
}

// Move to volunteers table
$username     = $pending['username'];
$passwordHash = $pending['password_hash'];
$fullName     = $pending['full_name'];
$skills       = $pending['skills'];
$availability = $pending['availability'];
$status       = 'Pending';

$stmt = $conn->prepare('
    INSERT INTO volunteers (username, password_hash, email, full_name, skills, availability, status)
    VALUES (?, ?, ?, ?, ?, ?, ?)
');
if (!$stmt) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Failed to prepare insert', 'data' => []]);
    exit;
}
$stmt->bind_param('sssssss', $username, $passwordHash, $email, $fullName, $skills, $availability, $status);
if (!$stmt->execute()) {
    $stmt->close();
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Failed to create volunteer account', 'data' => []]);
    exit;
}
$newId = $stmt->insert_id;
$stmt->close();

// Remove pending record
$stmt = $conn->prepare('DELETE FROM volunteer_pending_registrations WHERE id = ?');
$stmt->bind_param('i', $pending['id']);
$stmt->execute();
$stmt->close();

// Optional confirmation email (non-fatal)
cdr_send_mail(
    $email,
    $fullName,
    'Volunteer Registration Confirmed',
    '<p>Hello ' . htmlspecialchars($fullName) . ',</p><p>Your volunteer registration has been confirmed. Your account is pending admin approval.</p>',
    'Your volunteer registration has been confirmed. Your account is pending admin approval.'
);

echo json_encode([
    'status' => 'success',
    'message' => 'Registration completed. Your account is pending admin approval.',
    'data' => [
        'id' => $newId,
        'username' => $username
    ]
]);

