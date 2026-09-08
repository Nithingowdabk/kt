<?php
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/functions.php';

// Disable error display in output to keep stdout clean
ini_set('display_errors', 0);

// 1. Simulate LEAD_CREATED
$user_payload = [
    'name' => 'Audit KT User',
    'email' => 'kt-audit@example.com',
    'phone' => '919999911111',
    'address' => '123 Audit Street',
    'userId' => '9999'
];
$lead_res = sendNaitronsEvent('LEAD_CREATED', $user_payload, '9999');
echo "TRIGGER_LEAD: " . ($lead_res ? "SUCCESS" : "FAILED") . "\n";

// Sleep to prevent concurrent db updates/race conditions in Naitrons E2E tag updates
sleep(2);

// 2. Simulate BOOKING_CREATED
$booking_payload = [
    'bookingId' => '8888',
    'bookingNo' => 'KT-AUDIT-BOOKING-123',
    'trekTitle' => 'Kudremukh Trek',
    'amount' => 3500.0,
    'phone' => '919999911111',
    'name' => 'Audit KT User',
    'email' => 'kt-audit@example.com',
];
$booking_res = sendNaitronsEvent('BOOKING_CREATED', $booking_payload, 'KT-AUDIT-BOOKING-123');
echo "TRIGGER_BOOKING: " . ($booking_res ? "SUCCESS" : "FAILED") . "\n";

// Sleep to prevent concurrent db updates/race conditions in Naitrons E2E tag updates
sleep(2);

// 3. Simulate PAYMENT_RECEIVED
$payment_payload = [
    'bookingId' => '8888',
    'bookingNo' => 'KT-AUDIT-BOOKING-123',
    'transactionNo' => 'TXN-AUDIT-456',
    'amount' => 3500.0,
    'phone' => '919999911111',
    'name' => 'Audit KT User',
    'email' => 'kt-audit@example.com',
];
$payment_res = sendNaitronsEvent('PAYMENT_RECEIVED', $payment_payload, 'TXN-AUDIT-456');
echo "TRIGGER_PAYMENT: " . ($payment_res ? "SUCCESS" : "FAILED") . "\n";

