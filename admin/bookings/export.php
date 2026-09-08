<?php
/**
 * Admin - Export Bookings to CSV / Excel
 */
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

// Enforce admin login
require_admin_login();

$db = Database::connect();

try {
    // Fetch all bookings with details
    $stmt = $db->query("SELECT b.booking_no, b.name, b.email, b.phone, t.title as trek_title, 
                               d.start_date, d.end_date, b.num_trekkers, b.total_amount, 
                               b.discount_amount, b.payable_amount, b.payment_status, 
                               b.booking_status, b.created_at 
                        FROM bookings b 
                        INNER JOIN treks t ON b.trek_id = t.id 
                        INNER JOIN trek_dates d ON b.trek_date_id = d.id 
                        ORDER BY b.id DESC");
    $bookings = $stmt->fetchAll();

    // Set download headers
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=KarnatakaTrekkers_Bookings_' . date('Y-m-d') . '.csv');

    // Create a file pointer connected to the output stream
    $output = fopen('php://output', 'w');

    // Output the column headings
    fputcsv($output, [
        'Booking No', 
        'Customer Name', 
        'Email Address', 
        'Phone Number', 
        'Trek Destination', 
        'Start Date', 
        'End Date', 
        'Trekkers Qty', 
        'Subtotal Amount (INR)', 
        'Discount Amount (INR)', 
        'Payable Amount (INR)', 
        'Payment Status', 
        'Booking Status', 
        'Created At'
    ]);

    // Loop through the data and write to output
    foreach ($bookings as $b) {
        fputcsv($output, [
            $b['booking_no'],
            $b['name'],
            $b['email'],
            $b['phone'],
            $b['trek_title'],
            $b['start_date'],
            $b['end_date'],
            $b['num_trekkers'],
            $b['total_amount'],
            $b['discount_amount'],
            $b['payable_amount'],
            $b['payment_status'],
            $b['booking_status'],
            $b['created_at']
        ]);
    }
    
    fclose($output);
    exit();

} catch (PDOException $e) {
    die("Export failed: " . $e->getMessage());
}
?>
