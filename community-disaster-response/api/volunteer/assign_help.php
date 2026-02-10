<?php
header('Content-Type: application/json');

session_start();

if (!isset($_SESSION['volunteer_id'])) {
    http_response_code(401);
    echo json_encode([
        'status' => 'error',
        'message' => 'Unauthorized',
        'data' => []
    ]);
    exit;
}

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
$volunteer_id = $_SESSION['volunteer_id'];
$request_type = isset($input['request_type']) ? $input['request_type'] : '';
$request_id = isset($input['request_id']) ? (int)$input['request_id'] : 0;
$action = isset($input['action']) ? $input['action'] : 'assign'; // 'assign' or 'unassign'

if ($request_type === '' || $request_id === 0) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Invalid request type or ID',
        'data' => []
    ]);
    exit;
}

if (!in_array($request_type, ['EmergencyReport', 'HelpRequest'])) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Invalid request type',
        'data' => []
    ]);
    exit;
}

// Verify volunteer is approved
$stmt = $conn->prepare('SELECT status FROM volunteers WHERE id = ?');
$stmt->bind_param('i', $volunteer_id);
$stmt->execute();
$result = $stmt->get_result();
$volunteer = $result->fetch_assoc();
$stmt->close();

if (!$volunteer || $volunteer['status'] !== 'Approved') {
    echo json_encode([
        'status' => 'error',
        'message' => 'Only approved volunteers can assign to requests',
        'data' => []
    ]);
    exit;
}

if ($action === 'assign') {
    // Ensure request exists and is still active (Pending)
    if ($request_type === 'EmergencyReport') {
        $stmt = $conn->prepare('SELECT status FROM emergency_reports WHERE id = ? LIMIT 1');
    } else {
        $stmt = $conn->prepare('SELECT status FROM help_requests WHERE id = ? LIMIT 1');
    }
    if ($stmt) {
        $stmt->bind_param('i', $request_id);
        $stmt->execute();
        $res = $stmt->get_result();
        $req = $res->fetch_assoc();
        $stmt->close();
        if (!$req || ($req['status'] ?? '') !== 'Pending') {
            echo json_encode([
                'status' => 'error',
                'message' => 'Request is no longer available for assignment',
                'data' => []
            ]);
            exit;
        }
    }

    // Check if already assigned
    $stmt = $conn->prepare('SELECT id FROM volunteer_assignments WHERE volunteer_id = ? AND request_type = ? AND request_id = ?');
    $stmt->bind_param('isi', $volunteer_id, $request_type, $request_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $stmt->close();
        echo json_encode([
            'status' => 'error',
            'message' => 'Already assigned to this request',
            'data' => []
        ]);
        exit;
    }
    $stmt->close();

    // Insert assignment
    $stmt = $conn->prepare('INSERT INTO volunteer_assignments (volunteer_id, request_type, request_id) VALUES (?, ?, ?)');
    $stmt->bind_param('isi', $volunteer_id, $request_type, $request_id);
    
    if ($stmt->execute()) {
        echo json_encode([
            'status' => 'success',
            'message' => 'Successfully assigned to request',
            'data' => [
                'volunteer_id' => $volunteer_id,
                'request_type' => $request_type,
                'request_id' => $request_id
            ]
        ]);
    } else {
        http_response_code(500);
        echo json_encode([
            'status' => 'error',
            'message' => 'Failed to assign volunteer',
            'data' => []
        ]);
    }
    $stmt->close();
} else if ($action === 'unassign') {
    // Remove assignment
    $stmt = $conn->prepare('DELETE FROM volunteer_assignments WHERE volunteer_id = ? AND request_type = ? AND request_id = ?');
    $stmt->bind_param('isi', $volunteer_id, $request_type, $request_id);
    
    if ($stmt->execute()) {
        echo json_encode([
            'status' => 'success',
            'message' => 'Successfully unassigned from request',
            'data' => []
        ]);
    } else {
        http_response_code(500);
        echo json_encode([
            'status' => 'error',
            'message' => 'Failed to unassign volunteer',
            'data' => []
        ]);
    }
    $stmt->close();
} else {
    echo json_encode([
        'status' => 'error',
        'message' => 'Invalid action',
        'data' => []
    ]);
}

$conn->close();

