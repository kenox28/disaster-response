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
    $donations = [];
    $stmt = $conn->prepare('SELECT id, donor_name, donation_type, quantity, status, created_at FROM donations ORDER BY created_at DESC');
    if ($stmt && $stmt->execute()) {
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $donations[] = $row;
        }
        $stmt->close();
    }

    echo json_encode([
        'status' => 'success',
        'message' => 'Donations fetched',
        'data' => $donations
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
        $stmt = $conn->prepare('DELETE FROM donations WHERE id = ?');
        if (!$stmt) {
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => 'Failed to prepare statement', 'data' => []]);
            $conn->close();
            exit;
        }
        $stmt->bind_param('i', $id);
        if ($stmt->execute()) {
            echo json_encode(['status' => 'success', 'message' => 'Donation deleted', 'data' => []]);
        } else {
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => 'Failed to delete donation', 'data' => []]);
        }
        $stmt->close();
        $conn->close();
        exit;
    }

    if ($action === 'update') {
        $status = isset($input['status']) ? trim($input['status']) : '';
        $allowed = ['Pending', 'Approved', 'Rejected'];
        if (!in_array($status, $allowed, true)) {
            echo json_encode(['status' => 'error', 'message' => 'Invalid status', 'data' => []]);
            $conn->close();
            exit;
        }
        $stmt = $conn->prepare('UPDATE donations SET status = ? WHERE id = ?');
        $stmt->bind_param('si', $status, $id);
        if ($stmt->execute()) {
            echo json_encode(['status' => 'success', 'message' => 'Donation updated', 'data' => []]);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Failed to update', 'data' => []]);
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

    $stmt = $conn->prepare('UPDATE donations SET status = ? WHERE id = ?');
    if (!$stmt) {
        http_response_code(500);
        echo json_encode([
            'status' => 'error',
            'message' => 'Failed to prepare statement',
            'data' => []
        ]);
        $conn->close();
        exit;
    }

    $stmt->bind_param('si', $status, $id);
    if ($stmt->execute()) {
        echo json_encode([
            'status' => 'success',
            'message' => 'Donation updated',
            'data' => []
        ]);
    } else {
        http_response_code(500);
        echo json_encode([
            'status' => 'error',
            'message' => 'Failed to update donation',
            'data' => []
        ]);
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


