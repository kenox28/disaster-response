<?php
header('Content-Type: application/json');

session_start();

if (!isset($_SESSION['admin_id'])) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized', 'data' => []]);
    exit;
}

require_once __DIR__ . '/../../config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $guides = [];
    $stmt = $conn->prepare('SELECT id, title, content, category, is_archived FROM preparedness_guides ORDER BY category ASC, id ASC');
    if ($stmt && $stmt->execute()) {
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $guides[] = $row;
        }
        $stmt->close();
    }
    echo json_encode(['status' => 'success', 'message' => 'Guides fetched', 'data' => $guides]);
    $conn->close();
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $action = isset($input['action']) ? trim($input['action']) : '';

    $categories = ['Earthquake Preparedness', 'Flood Preparedness', 'Typhoon Readiness', 'Fire Safety', 'Emergency Go-Bag Checklist', 'Other'];

    // Add guide
    if ($action === 'add') {
        $title = isset($input['title']) ? trim($input['title']) : '';
        $content = isset($input['content']) ? trim($input['content']) : '';
        $category = isset($input['category']) ? trim($input['category']) : 'Other';
        if (!in_array($category, $categories)) $category = 'Other';
        if ($title === '' || $content === '') {
            echo json_encode(['status' => 'error', 'message' => 'Title and content required', 'data' => []]);
            $conn->close();
            exit;
        }
        $stmt = $conn->prepare('INSERT INTO preparedness_guides (title, content, category) VALUES (?, ?, ?)');
        $stmt->bind_param('sss', $title, $content, $category);
        if ($stmt->execute()) {
            echo json_encode(['status' => 'success', 'message' => 'Guide added', 'data' => ['id' => $conn->insert_id]]);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Failed to add guide', 'data' => []]);
        }
        $stmt->close();
        $conn->close();
        exit;
    }

    // Edit guide
    if ($action === 'edit') {
        $id = isset($input['id']) ? (int)$input['id'] : 0;
        $title = isset($input['title']) ? trim($input['title']) : '';
        $content = isset($input['content']) ? trim($input['content']) : '';
        $category = isset($input['category']) ? trim($input['category']) : 'Other';
        if (!in_array($category, $categories)) $category = 'Other';
        if ($id <= 0 || $title === '' || $content === '') {
            echo json_encode(['status' => 'error', 'message' => 'Id, title and content required', 'data' => []]);
            $conn->close();
            exit;
        }
        $stmt = $conn->prepare('UPDATE preparedness_guides SET title = ?, content = ?, category = ? WHERE id = ?');
        $stmt->bind_param('sssi', $title, $content, $category, $id);
        if ($stmt->execute()) {
            echo json_encode(['status' => 'success', 'message' => 'Guide updated', 'data' => []]);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Failed to update guide', 'data' => []]);
        }
        $stmt->close();
        $conn->close();
        exit;
    }

    // Archive guide
    if ($action === 'archive') {
        $id = isset($input['id']) ? (int)$input['id'] : 0;
        if ($id <= 0) {
            echo json_encode(['status' => 'error', 'message' => 'Invalid id', 'data' => []]);
            $conn->close();
            exit;
        }
        $stmt = $conn->prepare('UPDATE preparedness_guides SET is_archived = 1 WHERE id = ?');
        $stmt->bind_param('i', $id);
        if ($stmt->execute()) {
            echo json_encode(['status' => 'success', 'message' => 'Guide archived', 'data' => []]);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Failed to archive', 'data' => []]);
        }
        $stmt->close();
        $conn->close();
        exit;
    }

    // Delete guide
    if ($action === 'delete') {
        $id = isset($input['id']) ? (int)$input['id'] : 0;
        if ($id <= 0) {
            echo json_encode(['status' => 'error', 'message' => 'Invalid id', 'data' => []]);
            $conn->close();
            exit;
        }
        $stmt = $conn->prepare('DELETE FROM preparedness_guides WHERE id = ?');
        if (!$stmt) {
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => 'Failed to prepare statement', 'data' => []]);
            $conn->close();
            exit;
        }
        $stmt->bind_param('i', $id);
        if ($stmt->execute()) {
            echo json_encode(['status' => 'success', 'message' => 'Guide deleted', 'data' => []]);
        } else {
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => 'Failed to delete guide', 'data' => []]);
        }
        $stmt->close();
        $conn->close();
        exit;
    }

    echo json_encode(['status' => 'error', 'message' => 'Invalid action', 'data' => []]);
    $conn->close();
    exit;
}

http_response_code(405);
echo json_encode(['status' => 'error', 'message' => 'Method not allowed', 'data' => []]);
