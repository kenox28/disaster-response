<?php
header('Content-Type: application/json');

session_start();

if (!isset($_SESSION['admin_id'])) {
    http_response_code(401);
    echo json_encode([
        'status' => 'error',
        'message' => 'Unauthorized'
    ]);
    exit;
}

require_once __DIR__ . '/../../config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $centers = [];
    $stmt = $conn->prepare('SELECT id, name, address, capacity, status FROM evacuation_centers ORDER BY name ASC');
    if ($stmt && $stmt->execute()) {
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $centers[] = $row;
        }
        $stmt->close();
    }

    echo json_encode([
        'status' => 'success',
        'message' => 'Evacuation centers fetched',
        'data' => $centers
    ]);
    $conn->close();
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $action = isset($input['action']) ? trim($input['action']) : 'update';

    // Add new center
    if ($action === 'add') {
        $name = isset($input['name']) ? trim($input['name']) : '';
        $address = isset($input['address']) ? trim($input['address']) : '';
        $capacity = isset($input['capacity']) ? (int)$input['capacity'] : 0;
        $status = isset($input['status']) ? trim($input['status']) : 'Available';
        if (!in_array($status, ['Available', 'Full', 'Closed', 'Open'])) $status = 'Available';
        if ($name === '' || $address === '') {
            echo json_encode(['status' => 'error', 'message' => 'Name and address required']);
            $conn->close();
            exit;
        }
        $stmt = $conn->prepare('INSERT INTO evacuation_centers (name, address, capacity, status) VALUES (?, ?, ?, ?)');
        $stmt->bind_param('ssis', $name, $address, $capacity, $status);
        if ($stmt->execute()) {
            echo json_encode(['status' => 'success', 'message' => 'Evacuation center added', 'data' => ['id' => $conn->insert_id]]);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Failed to add center']);
        }
        $stmt->close();
        $conn->close();
        exit;
    }

    // Delete center
    if ($action === 'delete') {
        $id = isset($input['id']) ? (int)$input['id'] : 0;
        if ($id <= 0) {
            echo json_encode(['status' => 'error', 'message' => 'Invalid id']);
            $conn->close();
            exit;
        }
        $stmt = $conn->prepare('DELETE FROM evacuation_centers WHERE id = ?');
        $stmt->bind_param('i', $id);
        if ($stmt->execute()) {
            echo json_encode(['status' => 'success', 'message' => 'Evacuation center removed']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Failed to delete']);
        }
        $stmt->close();
        $conn->close();
        exit;
    }

    // Update center
    $id = isset($input['id']) ? (int)$input['id'] : 0;
    $status = isset($input['status']) ? trim($input['status']) : '';
    $capacity = isset($input['capacity']) ? (int)$input['capacity'] : null;
    $name = isset($input['name']) ? trim($input['name']) : null;
    $address = isset($input['address']) ? trim($input['address']) : null;

    if ($id <= 0) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid data']);
        $conn->close();
        exit;
    }

    $allowed_status = ['Available', 'Full', 'Closed', 'Open'];
    if ($status !== '' && !in_array($status, $allowed_status)) $status = 'Available';

    if ($name !== null && $name !== '' && $address !== null && $address !== '') {
        $cap = $capacity !== null ? $capacity : 0;
        $stmt = $conn->prepare('UPDATE evacuation_centers SET name = ?, address = ?, capacity = ?, status = ? WHERE id = ?');
        $stmt->bind_param('ssisi', $name, $address, $cap, $status, $id);
    } elseif ($status !== '') {
        if ($capacity !== null) {
            $stmt = $conn->prepare('UPDATE evacuation_centers SET status = ?, capacity = ? WHERE id = ?');
            $stmt->bind_param('sii', $status, $capacity, $id);
        } else {
            $stmt = $conn->prepare('UPDATE evacuation_centers SET status = ? WHERE id = ?');
            $stmt->bind_param('si', $status, $id);
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Status required']);
        $conn->close();
        exit;
    }

    if ($stmt && $stmt->execute()) {
        echo json_encode(['status' => 'success', 'message' => 'Evacuation center updated']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to update']);
    }
    if ($stmt) $stmt->close();
    $conn->close();
    exit;
}

http_response_code(405);
echo json_encode([
    'status' => 'error',
    'message' => 'Method not allowed'
]);


