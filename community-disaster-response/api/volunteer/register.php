<?php
header('Content-Type: application/json');

session_start();

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

$username = isset($input['username']) ? trim($input['username']) : '';
$password = isset($input['password']) ? $input['password'] : '';
$email = isset($input['email']) ? trim($input['email']) : '';
$full_name = isset($input['full_name']) ? trim($input['full_name']) : '';
$skills = isset($input['skills']) ? trim($input['skills']) : '';
$availability = isset($input['availability']) ? trim($input['availability']) : '';

// Validation
if ($username === '' || $password === '' || $email === '' || $full_name === '' || $skills === '' || $availability === '') {
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

if (strlen($username) < 3) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Username must be at least 3 characters',
        'data' => []
    ]);
    exit;
}

if (strlen($password) < 6) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Password must be at least 6 characters',
        'data' => []
    ]);
    exit;
}

// Check if username already exists
$stmt = $conn->prepare('SELECT id FROM volunteers WHERE username = ? LIMIT 1');
if (!$stmt) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Failed to prepare statement',
        'data' => []
    ]);
    exit;
}

$stmt->bind_param('s', $username);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $stmt->close();
    echo json_encode([
        'status' => 'error',
        'message' => 'Username already exists',
        'data' => []
    ]);
    exit;
}
$stmt->close();

// Hash password
$password_hash = password_hash($password, PASSWORD_DEFAULT);

// Check if email already exists
$stmt = $conn->prepare('SELECT id FROM volunteers WHERE email = ? LIMIT 1');
$stmt->bind_param('s', $email);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows > 0) {
    $stmt->close();
    echo json_encode([
        'status' => 'error',
        'message' => 'Email already registered',
        'data' => []
    ]);
    exit;
}
$stmt->close();

// Insert new volunteer
$stmt = $conn->prepare('INSERT INTO volunteers (username, password_hash, email, full_name, skills, availability, status) VALUES (?, ?, ?, ?, ?, ?, ?)');
if (!$stmt) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Failed to prepare insert statement',
        'data' => []
    ]);
    exit;
}

$status = 'Pending';
$stmt->bind_param('sssssss', $username, $password_hash, $email, $full_name, $skills, $availability, $status);

if ($stmt->execute()) {
    echo json_encode([
        'status' => 'success',
        'message' => 'Registration successful. Your account is pending approval.',
        'data' => [
            'id' => $conn->insert_id,
            'username' => $username
        ]
    ]);
} else {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Registration failed. Please try again.',
        'data' => []
    ]);
}

$stmt->close();
$conn->close();

