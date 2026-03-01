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

$username     = isset($input['username']) ? trim($input['username']) : '';
$password     = isset($input['password']) ? $input['password'] : '';
$email        = isset($input['email']) ? trim($input['email']) : '';
$full_name    = isset($input['full_name']) ? trim($input['full_name']) : '';
$skills       = isset($input['skills']) ? trim($input['skills']) : '';
$availability = isset($input['availability']) ? trim($input['availability']) : '';
$gender       = isset($input['gender']) ? trim($input['gender']) : '';
$birthday     = isset($input['birthday']) ? trim($input['birthday']) : '';

// Basic validation
if ($username === '' || $password === '' || $email === '' || $full_name === '' || $skills === '' || $availability === '' || $gender === '' || $birthday === '') {
    echo json_encode([
        'status' => 'error',
        'message' => 'All fields are required',
        'data' => []
    ]);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Invalid email format',
        'data' => []
    ]);
    exit;
}

// Normalize gender
$genderLower = strtolower($gender);
if ($genderLower === 'male') {
    $gender = 'Male';
} elseif ($genderLower === 'female') {
    $gender = 'Female';
} else {
    echo json_encode([
        'status' => 'error',
        'message' => 'Invalid gender value',
        'data' => []
    ]);
    exit;
}

// Birthday / age validation (must be at least 18)
$birthdayDate = DateTime::createFromFormat('Y-m-d', $birthday);
if (!$birthdayDate) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Invalid birthday format',
        'data' => []
    ]);
    exit;
}
$today = new DateTime();
$ageDiff = $today->diff($birthdayDate);
$age = (int)$ageDiff->y;
if ($age < 18) {
    echo json_encode([
        'status' => 'error',
        'message' => 'You must be at least 18 years old to register as a volunteer.',
        'data' => []
    ]);
    exit;
}

if (strlen($username) < 3) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Username must be at least 3 characters',
        'data' => []
    ]);
    exit;
}

// Password strength
if (strlen($password) < 8 || !preg_match('/[0-9]/', $password) || !preg_match('/[^A-Za-z0-9]/', $password)) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Password must be at least 8 characters and include a number and a special character',
        'data' => []
    ]);
    exit;
}

// Check existing username/email
$stmt = $conn->prepare('SELECT id FROM volunteers WHERE username = ? LIMIT 1');
if (!$stmt) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Prepare failed (username check): ' . $conn->error,
        'data' => []
    ]);
    exit;
}
$stmt->bind_param('s', $username);
if (!$stmt->execute()) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Execute failed (username check): ' . $stmt->error,
        'data' => []
    ]);
    exit;
}
$res = $stmt->get_result();
if ($res->num_rows > 0) {
    $stmt->close();
    echo json_encode(['status' => 'error', 'message' => 'Username already exists', 'data' => []]);
    exit;
}
$stmt->close();

$stmt = $conn->prepare('SELECT id FROM volunteers WHERE email = ? LIMIT 1');
if (!$stmt) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Prepare failed (email check): ' . $conn->error,
        'data' => []
    ]);
    exit;
}
$stmt->bind_param('s', $email);
if (!$stmt->execute()) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Execute failed (email check): ' . $stmt->error,
        'data' => []
    ]);
    exit;
}
$res = $stmt->get_result();
if ($res->num_rows > 0) {
    $stmt->close();
    echo json_encode(['status' => 'error', 'message' => 'Email already registered', 'data' => []]);
    exit;
}
$stmt->close();

// Delete any pending registration
$stmt = $conn->prepare('DELETE FROM volunteer_pending_registrations WHERE email = ?');
if (!$stmt) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Prepare failed (delete pending): ' . $conn->error,
        'data' => []
    ]);
    exit;
}
$stmt->bind_param('s', $email);
$stmt->execute();
$stmt->close();

// Hash password
$password_hash = password_hash($password, PASSWORD_DEFAULT);

// Generate OTP
$otp = str_pad((string)random_int(0, 999999), 6, '0', STR_PAD_LEFT);
$expires = (new DateTime('+10 minutes'))->format('Y-m-d H:i:s');

// Insert pending registration with debug info
$stmt = $conn->prepare('
    INSERT INTO volunteer_pending_registrations 
    (email, username, password_hash, full_name, skills, availability, gender, birthday, age, otp_code, otp_expires_at)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
');
if (!$stmt) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Prepare failed (insert): ' . $conn->error,
        'data' => []
    ]);
    exit;
}
$stmt->bind_param('ssssssssiss', $email, $username, $password_hash, $full_name, $skills, $availability, $gender, $birthday, $age, $otp, $expires);

if (!$stmt->execute()) {
    $stmt->close();
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Execute failed (insert): ' . $stmt->error,
        'data' => []
    ]);
    exit;
}
$stmt->close();

// Send OTP
$send = cdr_send_mail(
    $email,
    $full_name,
    'Your Volunteer Registration OTP',
    '<p>Your OTP code is: <strong>' . htmlspecialchars($otp) . '</strong></p><p>This code will expire in 10 minutes.</p>',
    'Your OTP code is: ' . $otp . ' (valid for 10 minutes).'
);
if (!$send['ok']) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Failed to send OTP email: ' . ($send['error'] ?? 'Unknown error'),
        'data' => []
    ]);
    exit;
}

echo json_encode([
    'status' => 'success',
    'message' => 'OTP sent to your email. Please enter it to complete registration.',
    'data' => ['email' => $email]
]);