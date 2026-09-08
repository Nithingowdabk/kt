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
    if ($action === 'upload') {
        $trek_id = (int)($_POST['trek_id'] ?? 0);
        $session_token = sanitize_input($_POST['session_token'] ?? '');
        if ($trek_id <= 0 && empty($session_token)) throw new Exception("Invalid trek ID or session");

        if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
            throw new Exception("File upload error");
        }

        $file_name = $_FILES['file']['name'];
        $file_tmp = $_FILES['file']['tmp_name'];
        $file_size = $_FILES['file']['size'];

        // Max 2MB
        if ($file_size > 2 * 1024 * 1024) throw new Exception("File exceeds 2MB limit");

        // Allowed Extensions
        $ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'webp'];
        if (!in_array($ext, $allowed)) throw new Exception("Only JPG, PNG, and WEBP allowed");

        // Validate MIME type
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $file_tmp);
        finfo_close($finfo);
        if (!in_array($mime, ['image/jpeg', 'image/png', 'image/webp'])) {
            throw new Exception("Invalid image file");
        }

        // Directories
        $upload_dir = __DIR__ . '/../../assets/uploads/gallery/';
        $thumb_dir = __DIR__ . '/../../assets/uploads/gallery/thumbs/';
        if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);
        if (!is_dir($thumb_dir)) mkdir($thumb_dir, 0755, true);

        $base_name = bin2hex(random_bytes(16));
        $has_gd = extension_loaded('gd');
        $dest_filename = $base_name . ($has_gd ? '.webp' : '.' . $ext);

        $full_path = $upload_dir . $dest_filename;
        $thumb_path = $thumb_dir . $dest_filename;

        $processed = false;
        if ($has_gd) {
            if (process_and_optimize_image($file_tmp, $full_path, 1600, 1200, 85)) {
                process_and_optimize_image($full_path, $thumb_path, 400, 300, 75);
                $processed = true;
            }
        } else {
            if (move_uploaded_file($file_tmp, $full_path)) {
                copy($full_path, $thumb_path);
                $processed = true;
            }
        }

        if ($processed) {
            $rel_path = 'assets/uploads/gallery/' . $dest_filename;
            $rel_thumb = 'assets/uploads/gallery/thumbs/' . $dest_filename;

            $t_id = $trek_id > 0 ? $trek_id : null;
            $s_token = !empty($session_token) ? $session_token : null;
            
            if ($t_id) {
                $stmt = $db->prepare("SELECT MAX(sort_order) FROM trek_gallery WHERE trek_id = ?");
                $stmt->execute([$t_id]);
            } else {
                $stmt = $db->prepare("SELECT MAX(sort_order) FROM trek_gallery WHERE session_token = ?");
                $stmt->execute([$s_token]);
            }
            $max_sort = (int)$stmt->fetchColumn();

            // Insert
            $stmt = $db->prepare("INSERT INTO trek_gallery (trek_id, session_token, image_path, thumbnail_path, sort_order) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$t_id, $s_token, $rel_path, $rel_thumb, $max_sort + 1]);
            
            $new_id = $db->lastInsertId();

            echo json_encode([
                'success' => true,
                'uploaded' => 1,
                'status' => 'success',
                'id' => $new_id,
                'path' => SITE_URL . '/' . $rel_path,
                'thumb' => SITE_URL . '/' . $rel_thumb
            ]);
        } else {
            throw new Exception("Failed to process image file.");
        }
        exit;
    }

    if ($action === 'list') {
        $trek_id = (int)($_GET['trek_id'] ?? 0);
        $session_token = sanitize_input($_GET['session_token'] ?? '');
        
        if ($trek_id > 0) {
            $stmt = $db->prepare("SELECT tg.*, c.category_name FROM trek_gallery tg LEFT JOIN gallery_categories c ON tg.category_id = c.id WHERE tg.trek_id = ? ORDER BY tg.sort_order ASC");
            $stmt->execute([$trek_id]);
        } else if (!empty($session_token)) {
            $stmt = $db->prepare("SELECT tg.*, c.category_name FROM trek_gallery tg LEFT JOIN gallery_categories c ON tg.category_id = c.id WHERE tg.session_token = ? ORDER BY tg.sort_order ASC");
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
        $stmt = $db->prepare("SELECT image_path, thumbnail_path FROM trek_gallery WHERE id = ?");
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        if ($row) {
            $path = __DIR__ . '/../../' . $row['image_path'];
            $thumb = __DIR__ . '/../../' . $row['thumbnail_path'];
            if (file_exists($path)) unlink($path);
            if ($row['thumbnail_path'] && file_exists($thumb)) unlink($thumb);
            
            $db->prepare("DELETE FROM trek_gallery WHERE id = ?")->execute([$id]);
            echo json_encode(['status' => 'success']);
        } else {
            throw new Exception("Image not found");
        }
        exit;
    }

    if ($action === 'reorder') {
        $orders = $_POST['orders'] ?? []; // Array of ID => sort_order
        if (is_array($orders)) {
            $stmt = $db->prepare("UPDATE trek_gallery SET sort_order = ? WHERE id = ?");
            foreach ($orders as $id => $order) {
                $stmt->execute([(int)$order, (int)$id]);
            }
        }
        echo json_encode(['status' => 'success']);
        exit;
    }

    if ($action === 'set_featured') {
        $id = (int)($_POST['id'] ?? 0);
        $trek_id = (int)($_POST['trek_id'] ?? 0);
        $session_token = sanitize_input($_POST['session_token'] ?? '');
        
        if ($trek_id > 0) {
            $db->prepare("UPDATE trek_gallery SET is_featured = 0 WHERE trek_id = ?")->execute([$trek_id]);
        } else if (!empty($session_token)) {
            $db->prepare("UPDATE trek_gallery SET is_featured = 0 WHERE session_token = ?")->execute([$session_token]);
        }
        
        $db->prepare("UPDATE trek_gallery SET is_featured = 1 WHERE id = ?")->execute([$id]);
        
        echo json_encode(['status' => 'success']);
        exit;
    }

    if ($action === 'update_meta') {
        $id = (int)($_POST['id'] ?? 0);
        $title = sanitize_input($_POST['title'] ?? '');
        $category_id = (int)($_POST['category_id'] ?? 0);
        $cat_val = $category_id > 0 ? $category_id : null;
        
        $db->prepare("UPDATE trek_gallery SET image_title = ?, category_id = ? WHERE id = ?")->execute([$title, $cat_val, $id]);
        echo json_encode(['status' => 'success']);
        exit;
    }

    throw new Exception("Invalid action");

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'uploaded' => 0,
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
