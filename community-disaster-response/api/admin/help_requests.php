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
    $requests = [];
    $stmt = $conn->prepare('
        SELECT 
            hr.id, 
            hr.help_type, 
            hr.description, 
            hr.status, 
            hr.created_at,
            COUNT(va.id) AS assigned_count
        FROM help_requests hr
        LEFT JOIN volunteer_assignments va ON va.request_type = "HelpRequest" AND va.request_id = hr.id
        GROUP BY hr.id
        ORDER BY hr.created_at DESC
    ');
    if ($stmt && $stmt->execute()) {
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $request_id = $row['id'];
            
            // Get assigned volunteers
            $vol_stmt = $conn->prepare('
                SELECT v.id, v.full_name, v.skills, va.date_assigned
                FROM volunteer_assignments va
                JOIN volunteers v ON v.id = va.volunteer_id
                WHERE va.request_type = "HelpRequest" AND va.request_id = ?
                ORDER BY va.date_assigned DESC
            ');
            $vol_stmt->bind_param('i', $request_id);
            $vol_stmt->execute();
            $vol_result = $vol_stmt->get_result();
            $assigned_volunteers = [];
            while ($vol_row = $vol_result->fetch_assoc()) {
                $assigned_volunteers[] = [
                    'id' => $vol_row['id'],
                    'full_name' => $vol_row['full_name'],
                    'skills' => $vol_row['skills'],
                    'date_assigned' => $vol_row['date_assigned']
                ];
            }
            $vol_stmt->close();
            
            $requests[] = [
                'id' => $row['id'],
                'help_type' => $row['help_type'],
                'description' => $row['description'],
                'status' => $row['status'],
                'created_at' => $row['created_at'],
                'assigned_count' => (int)$row['assigned_count'],
                'assigned_volunteers' => $assigned_volunteers
            ];
        }
        $stmt->close();
    }

    echo json_encode([
        'status' => 'success',
        'message' => 'Help requests fetched',
        'data' => $requests
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
        // Remove assignments first
        $stmt = $conn->prepare('DELETE FROM volunteer_assignments WHERE request_type = "HelpRequest" AND request_id = ?');
        if ($stmt) {
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $stmt->close();
        }

        $stmt = $conn->prepare('DELETE FROM help_requests WHERE id = ?');
        if (!$stmt) {
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => 'Failed to prepare statement', 'data' => []]);
            $conn->close();
            exit;
        }
        $stmt->bind_param('i', $id);
        if ($stmt->execute()) {
            echo json_encode(['status' => 'success', 'message' => 'Help request deleted', 'data' => []]);
        } else {
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => 'Failed to delete help request', 'data' => []]);
        }
        $stmt->close();
        $conn->close();
        exit;
    }

    if ($action === 'update') {
        $status = isset($input['status']) ? trim($input['status']) : '';
        $allowed = ['Pending', 'Approved', 'Rejected', 'Done'];
        if (!in_array($status, $allowed, true)) {
            echo json_encode(['status' => 'error', 'message' => 'Invalid status', 'data' => []]);
            $conn->close();
            exit;
        }
        $stmt = $conn->prepare('UPDATE help_requests SET status = ? WHERE id = ?');
        $stmt->bind_param('si', $status, $id);
        if ($stmt->execute()) {
            echo json_encode(['status' => 'success', 'message' => 'Help request updated', 'data' => []]);
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

    $stmt = $conn->prepare('UPDATE help_requests SET status = ? WHERE id = ?');
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
            'message' => 'Help request updated',
            'data' => []
        ]);
    } else {
        http_response_code(500);
        echo json_encode([
            'status' => 'error',
            'message' => 'Failed to update help request',
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


