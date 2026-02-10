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

$email_or_username = isset($input['email_or_username']) ? trim($input['email_or_username']) : '';
$password = isset($input['password']) ? $input['password'] : '';

if ($email_or_username === '' || $password === '') {
    echo json_encode([
        'status' => 'error',
        'message' => 'Email/Username and password are required',
        'data' => []
    ]);
    exit;
}

// Check if input is email or username
$is_email = filter_var($email_or_username, FILTER_VALIDATE_EMAIL);

if ($is_email) {
    // Login by email
    $stmt = $conn->prepare('SELECT id, full_name, password_hash, status FROM volunteers WHERE email = ? LIMIT 1');
} else {
    // Login by username
    $stmt = $conn->prepare('SELECT id, full_name, password_hash, status FROM volunteers WHERE username = ? LIMIT 1');
}

if (!$stmt) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Failed to prepare statement',
        'data' => []
    ]);
    exit;
}

$stmt->bind_param('s', $email_or_username);
$stmt->execute();
$result = $stmt->get_result();
$volunteer = $result->fetch_assoc();

if ($volunteer && $volunteer['status'] === 'Approved' && password_verify($password, $volunteer['password_hash'])) {
    $_SESSION['volunteer_id'] = $volunteer['id'];
    $_SESSION['volunteer_name'] = $volunteer['full_name'];

    echo json_encode([
        'status' => 'success',
        'message' => 'Login successful',
        'data' => [
            'id' => $volunteer['id'],
            'full_name' => $volunteer['full_name']
        ]
    ]);
} else {
    echo json_encode([
        'status' => 'error',
        'message' => 'Invalid credentials or account not approved',
        'data' => []
    ]);
}

$stmt->close();
$conn->close();


