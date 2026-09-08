<?php
/**
 * AJAX Trek Search Endpoint
 */
if (!headers_sent()) {
    header('Content-Type: application/json');
}

// Disable HTML error display to keep JSON responses clean of warnings/notices
ini_set('display_errors', 0);
error_reporting(E_ALL);

try {
    require_once __DIR__ . '/../includes/database.php';

    $query = isset($_GET['q']) ? trim($_GET['q']) : '';

    if (strlen($query) < 1) {
        echo json_encode([]);
        return;
    }

    $db = Database::connect();
    
    // Select active treks whose title or slug contains the query string, up to 10 results
    $stmt = $db->prepare("SELECT id, title, slug FROM treks WHERE status = 'Active' AND (title LIKE :query_title OR slug LIKE :query_slug) LIMIT 10");
    $stmt->execute([
        'query_title' => '%' . $query . '%',
        'query_slug' => '%' . $query . '%'
    ]);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Make sure we output only valid JSON
    echo json_encode($results);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => true,
        'message' => $e->getMessage()
    ]);
}
