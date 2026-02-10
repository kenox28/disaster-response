<?php
header('Content-Type: application/json');

session_start();

if (!isset($_SESSION['admin_id'])) {
    http_response_code(401);
    echo json_encode([
        'status' => 'error',
        'message' => 'Unauthorized',
        'data' => []
    ]);
    exit;
}

require_once __DIR__ . '/../../config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $volunteers = [];
    $stmt = $conn->prepare('SELECT id, full_name, skills, availability, status, created_at FROM volunteers ORDER BY created_at DESC');
    if ($stmt && $stmt->execute()) {
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $volunteers[] = $row;
        }
        $stmt->close();
    }

    echo json_encode([
        'status' => 'success',
        'message' => 'Volunteers fetched',
        'data' => $volunteers
    ]);
    $conn->close();
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $id = isset($input['id']) ? (int)$input['id'] : 0;
    $action = isset($input['action']) ? trim($input['action']) : '';

    if ($id <= 0) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid id', 'data' => []]);
        $conn->close();
        exit;
    }

    if ($action === 'delete') {
        $stmt = $conn->prepare('DELETE FROM volunteers WHERE id = ?');
        if (!$stmt) {
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => 'Failed to prepare statement', 'data' => []]);
            $conn->close();
            exit;
        }
        $stmt->bind_param('i', $id);
        if ($stmt->execute()) {
            echo json_encode(['status' => 'success', 'message' => 'Volunteer deleted', 'data' => []]);
        } else {
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => 'Failed to delete volunteer', 'data' => []]);
        }
        $stmt->close();
        $conn->close();
        exit;
    }

    if ($action === 'update') {
        $full_name = isset($input['full_name']) ? trim($input['full_name']) : '';
        $skills = isset($input['skills']) ? trim($input['skills']) : '';
        $availability = isset($input['availability']) ? trim($input['availability']) : '';
        if ($full_name === '' || $skills === '' || $availability === '') {
            echo json_encode(['status' => 'error', 'message' => 'Full name, skills, and availability required', 'data' => []]);
            $conn->close();
            exit;
        }
        $stmt = $conn->prepare('UPDATE volunteers SET full_name = ?, skills = ?, availability = ? WHERE id = ?');
        $stmt->bind_param('sssi', $full_name, $skills, $availability, $id);
        if ($stmt->execute()) {
            echo json_encode(['status' => 'success', 'message' => 'Volunteer updated', 'data' => []]);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Failed to update volunteer', 'data' => []]);
        }
        $stmt->close();
        $conn->close();
        exit;
    }

    $allowed_actions = ['approve', 'reject'];
    if (!in_array($action, $allowed_actions, true)) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid action', 'data' => []]);
        $conn->close();
        exit;
    }

    $status = $action === 'approve' ? 'Approved' : 'Rejected';
    $stmt = $conn->prepare('UPDATE volunteers SET status = ? WHERE id = ?');
    $stmt->bind_param('si', $status, $id);
    if ($stmt->execute()) {
        echo json_encode(['status' => 'success', 'message' => 'Volunteer updated', 'data' => []]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to update volunteer', 'data' => []]);
    }
    $stmt->close();
    $conn->close();
    exit;
}

http_response_code(405);
echo json_encode([
    'status' => 'error',
    'message' => 'Method not allowed',
    'data' => []
]);


