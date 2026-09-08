<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

require_admin_login();

header('Content-Type: application/json');

$action = $_POST['action'] ?? ($_GET['action'] ?? '');
$db = Database::connect();

try {
    if ($action === 'add') {
        $trek_id = (int)($_POST['trek_id'] ?? 0);
        $session_token = sanitize_input($_POST['session_token'] ?? '');
        $url = sanitize_input($_POST['youtube_url'] ?? '');
        $title = sanitize_input($_POST['title'] ?? '');

        if (($trek_id <= 0 && empty($session_token)) || empty($url)) {
            throw new Exception("Missing required fields");
        }

        // Basic YouTube URL Validation
        if (!preg_match('%(?:youtube(?:-nocookie)?\.com/(?:[^/]+/.+/|(?:v|e(?:mbed)?)/|.*[?&]v=)|youtu\.be/)([^"&?/\s]{11})%i', $url, $match)) {
            throw new Exception("Invalid YouTube URL");
        }
        // Save the cleaned embed/watch url or just keep the original valid url
        $clean_url = "https://www.youtube.com/watch?v=" . $match[1];

        $t_id = $trek_id > 0 ? $trek_id : null;
        $s_token = !empty($session_token) ? $session_token : null;

        // Get max sort_order
        if ($t_id) {
            $stmt = $db->prepare("SELECT MAX(sort_order) FROM trek_videos WHERE trek_id = ?");
            $stmt->execute([$t_id]);
        } else {
            $stmt = $db->prepare("SELECT MAX(sort_order) FROM trek_videos WHERE session_token = ?");
            $stmt->execute([$s_token]);
        }
        $max_sort = (int)$stmt->fetchColumn();

        $stmt = $db->prepare("INSERT INTO trek_videos (trek_id, session_token, youtube_url, title, sort_order) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$t_id, $s_token, $clean_url, $title, $max_sort + 1]);
        
        echo json_encode(['status' => 'success', 'id' => $db->lastInsertId()]);
        exit;
    }

    if ($action === 'list') {
        $trek_id = (int)($_GET['trek_id'] ?? 0);
        $session_token = sanitize_input($_GET['session_token'] ?? '');
        
        if ($trek_id > 0) {
            $stmt = $db->prepare("SELECT * FROM trek_videos WHERE trek_id = ? ORDER BY sort_order ASC");
            $stmt->execute([$trek_id]);
        } else if (!empty($session_token)) {
            $stmt = $db->prepare("SELECT * FROM trek_videos WHERE session_token = ? ORDER BY sort_order ASC");
            $stmt->execute([$session_token]);
        } else {
            echo json_encode(['status' => 'success', 'data' => []]);
            exit;
        }
        
        echo json_encode(['status' => 'success', 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
        exit;
    }

    if ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        $db->prepare("DELETE FROM trek_videos WHERE id = ?")->execute([$id]);
        echo json_encode(['status' => 'success']);
        exit;
    }

    if ($action === 'reorder') {
        $orders = $_POST['orders'] ?? [];
        if (is_array($orders)) {
            $stmt = $db->prepare("UPDATE trek_videos SET sort_order = ? WHERE id = ?");
            foreach ($orders as $id => $order) {
                $stmt->execute([(int)$order, (int)$id]);
            }
        }
        echo json_encode(['status' => 'success']);
        exit;
    }

    throw new Exception("Invalid action");

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
