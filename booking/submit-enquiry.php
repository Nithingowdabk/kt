<?php
/**
 * Karnataka Trekkers - Submit Customized Trip Enquiry Handler
 */
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

// Enforce user login to submit customized trip enquiry
if (!is_user_logged_in()) {
    set_flash_message('danger', 'Please login to submit a customized trip enquiry.');
    header('Location: ' . SITE_URL . '/login.php?redirect=' . urlencode($_SERVER['HTTP_REFERER'] ?? ''));
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $db = Database::connect();

    $trek_id = (int)($_POST['trek_id'] ?? 0);
    $preferred_date = sanitize_input($_POST['preferred_date'] ?? '');
    $num_participants = (int)($_POST['num_participants'] ?? 1);
    $pickup_location = sanitize_input($_POST['pickup_location'] ?? '');
    $special_requirements = sanitize_input($_POST['special_requirements'] ?? '');
    $user_id = get_logged_in_user_id();

    // Fetch Trek title to redirect back properly
    $trek_slug = '';
    try {
        $slug_stmt = $db->prepare("SELECT slug FROM treks WHERE id = ? LIMIT 1");
        $slug_stmt->execute([$trek_id]);
        $trek_slug = $slug_stmt->fetchColumn();
    } catch (PDOException $e) {
        // Ignored
    }

    if ($trek_id <= 0 || empty($preferred_date) || $num_participants <= 0 || empty($pickup_location)) {
        set_flash_message('danger', 'Please fill in all required enquiry fields correctly.');
    } else {
        try {
            $stmt = $db->prepare("INSERT INTO custom_trip_enquiries (user_id, trek_id, preferred_date, num_participants, pickup_location, special_requirements, status) VALUES (?, ?, ?, ?, ?, ?, 'Pending')");
            $stmt->execute([
                $user_id,
                $trek_id,
                $preferred_date,
                $num_participants,
                $pickup_location,
                $special_requirements ?: null
            ]);

            log_activity('user_submit_custom_trip_enquiry', [
                'user_id' => $user_id,
                'trek_id' => $trek_id,
                'preferred_date' => $preferred_date,
                'num_participants' => $num_participants
            ]);

            set_flash_message('success', 'Thank you! Your customized trip enquiry has been submitted. Our team will contact you shortly.');
        } catch (PDOException $e) {
            set_flash_message('danger', 'Failed to submit customized enquiry: ' . $e->getMessage());
        }
    }

    if (!empty($trek_slug)) {
        header('Location: ' . SITE_URL . '/treks/' . $trek_slug);
    } else {
        header('Location: ' . SITE_URL . '/treks');
    }
    exit();
} else {
    header('Location: ' . SITE_URL . '/treks');
    exit();
}
