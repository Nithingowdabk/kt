<?php
/**
 * WhatsApp Cloud API Integration Service Layer
 */
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/functions.php';

class WhatsAppService {
    private $api_url;
    private $token;
    private $phone_number_id;
    private $enabled;

    public function __construct() {
        // Load settings dynamically from the database settings table
        $this->phone_number_id = get_setting('whatsapp_phone_number_id', '1234567890');
        $this->token = get_setting('whatsapp_access_token', 'mock_meta_whatsapp_token');
        $this->api_url = "https://graph.facebook.com/v19.0/" . $this->phone_number_id . "/messages";
        $this->enabled = (bool)get_setting('whatsapp_enable', '1');
    }

    /**
     * Sends a raw message to a phone number (free-form text)
     * 
     * @param string $phone
     * @param string $message
     * @return bool
     */
    public function sendRawMessage($phone, $message) {
        if (!$this->enabled) {
            return false;
        }

        // Clean phone number (add country code +91 for India if 10-digit number)
        $clean_phone = preg_replace('/[^0-9]/', '', $phone);
        if (strlen($clean_phone) === 10) {
            $clean_phone = '91' . $clean_phone;
        }

        // Log locally first
        $log_message = "[" . date('Y-m-d H:i:s') . "] TO: " . $clean_phone . "\nMESSAGE: " . $message . "\n--------------------------------------------------\n";
        $log_file = __DIR__ . '/log.txt';
        file_put_contents($log_file, $log_message, FILE_APPEND);

        // Fallback checks for testing (if mock credentials, auto-pass after logging)
        if ($this->token === 'mock_meta_whatsapp_token' || empty($this->token)) {
            return true; 
        }

        // Send actual HTTP POST using cURL to Meta Cloud API (free-form message)
        $payload = json_encode([
            'messaging_product' => 'whatsapp',
            'recipient_type' => 'individual',
            'to' => '+' . $clean_phone,
            'type' => 'text',
            'text' => [
                'preview_url' => false,
                'body' => $message
            ]
        ]);

        return $this->executeCurl($payload);
    }

    /**
     * Sends a template message to a phone number using Meta Cloud API
     * 
     * @param string $phone
     * @param string $template_name
     * @param array $parameters
     * @param string $language
     * @return bool
     */
    public function sendTemplateMessage($phone, $template_name, $parameters = [], $language = 'en_US') {
        if (!$this->enabled) {
            return false;
        }

        $clean_phone = preg_replace('/[^0-9]/', '', $phone);
        if (strlen($clean_phone) === 10) {
            $clean_phone = '91' . $clean_phone;
        }

        // Log template dispatch details locally
        $log_message = "[" . date('Y-m-d H:i:s') . "] TO: " . $clean_phone . "\nTEMPLATE: " . $template_name . "\nPARAMS: " . json_encode($parameters) . "\n--------------------------------------------------\n";
        $log_file = __DIR__ . '/log.txt';
        file_put_contents($log_file, $log_message, FILE_APPEND);

        if ($this->token === 'mock_meta_whatsapp_token' || empty($this->token)) {
            return true;
        }

        // Build parameters structure for Meta template
        $components_parameters = [];
        foreach ($parameters as $param) {
            $components_parameters[] = [
                'type' => 'text',
                'text' => (string)$param
            ];
        }

        $payload = json_encode([
            'messaging_product' => 'whatsapp',
            'recipient_type' => 'individual',
            'to' => '+' . $clean_phone,
            'type' => 'template',
            'template' => [
                'name' => $template_name,
                'language' => [
                    'code' => $language
                ],
                'components' => [
                    [
                        'type' => 'body',
                        'parameters' => $components_parameters
                    ]
                ]
            ]
        ]);

        return $this->executeCurl($payload);
    }

    /**
     * Executes the HTTP request to Meta API
     */
    private function executeCurl($payload) {
        $ch = curl_init($this->api_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $this->token,
            'Content-Type: application/json',
            'Content-Length: ' . strlen($payload)
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return ($http_code === 200 || $http_code === 201);
    }

    /**
     * Send booking confirmation message
     * 
     * @param int $booking_id
     * @return bool
     */
    public function sendBookingConfirmation($booking_id) {
        try {
            $db = Database::connect();
            $stmt = $db->prepare("SELECT b.*, t.title as trek_title, d.start_date, p.location as pickup_loc, p.time as pickup_time FROM bookings b
                                  INNER JOIN treks t ON b.trek_id = t.id
                                  INNER JOIN trek_dates d ON b.trek_date_id = d.id
                                  LEFT JOIN pickup_points p ON b.pickup_point_id = p.id
                                  WHERE b.id = ? LIMIT 1");
            $stmt->execute([$booking_id]);
            $booking = $stmt->fetch();

            if (!$booking) return false;

            $lead_name = $booking['name'];
            $trek_name = $booking['trek_title'];
            $booking_no = $booking['booking_no'];
            $pickup_date = format_date($booking['start_date']);
            $num_t = $booking['num_trekkers'];
            $total_cost = format_price($booking['payable_amount']);
            
            $pickup_spot = 'Own Transport Selected';
            if (($booking['package_type'] ?? 'with_transport') !== 'without_transport') {
                $pickup_spot = ($booking['pickup_loc'] ?? 'N/A') . ' (' . ($booking['pickup_time'] ? date('h:i A', strtotime($booking['pickup_time'])) : 'N/A') . ')';
            }

            // 1. Send free-form text layout
            $text_body = "Hello $lead_name,\n\n🎉 Booking Confirmed! Your adventure with Karnataka Trekkers is booked successfully.\n\n📄 Booking No: $booking_no\n🌄 Trek: $trek_name\n🗓️ Date: $pickup_date\n🎒 Trekkers: $num_t Person(s)\n💵 Amount Paid: $total_cost\n📍 Pick-up Point: $pickup_spot\n\nDownload invoice details: " . SITE_URL . "/booking/invoice.php?booking_no=$booking_no\n\nGet ready for an epic trail experience!\n\nWarm regards,\nTeam Karnataka Trekkers 🏔️";
            $this->sendRawMessage($booking['phone'], $text_body);

            // 2. Send official Meta approved template fallback
            $template_name = get_setting('whatsapp_template_booking_confirm', 'booking_confirmation');
            $params = [
                $lead_name,
                $booking_no,
                $trek_name,
                $pickup_date,
                $num_t . ' Person(s)',
                $total_cost,
                $pickup_spot
            ];
            return $this->sendTemplateMessage($booking['phone'], $template_name, $params);

        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Send payment success notification
     * 
     * @param int $booking_id
     * @param string $payment_id
     * @param float $amount
     * @return bool
     */
    public function sendPaymentSuccess($booking_id, $payment_id, $amount) {
        try {
            $db = Database::connect();
            $stmt = $db->prepare("SELECT b.*, t.title as trek_title FROM bookings b
                                  INNER JOIN treks t ON b.trek_id = t.id
                                  WHERE b.id = ? LIMIT 1");
            $stmt->execute([$booking_id]);
            $booking = $stmt->fetch();

            if (!$booking) return false;

            $lead_name = $booking['name'];
            $trek_name = $booking['trek_title'];
            $total_paid = format_price($amount);

            $text_body = "Hello $lead_name,\n\n💳 Payment Successful! We have received your payment of $total_paid for your trek to $trek_name.\n\nTransaction ID: $payment_id\nBooking No: " . $booking['booking_no'] . "\n\nThank you for choosing Karnataka Trekkers!";
            $this->sendRawMessage($booking['phone'], $text_body);

            $template_name = get_setting('whatsapp_template_payment_success', 'payment_success');
            $params = [
                $lead_name,
                $booking['booking_no'],
                $trek_name,
                $total_paid,
                $payment_id
            ];
            return $this->sendTemplateMessage($booking['phone'], $template_name, $params);

        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Send trek reminder notification
     * 
     * @param int $booking_id
     * @return bool
     */
    public function sendTrekReminder($booking_id) {
        try {
            $db = Database::connect();
            $stmt = $db->prepare("SELECT b.*, t.title as trek_title, d.start_date, p.location as pickup_loc, p.time as pickup_time FROM bookings b
                                  INNER JOIN treks t ON b.trek_id = t.id
                                  INNER JOIN trek_dates d ON b.trek_date_id = d.id
                                  LEFT JOIN pickup_points p ON b.pickup_point_id = p.id
                                  WHERE b.id = ? LIMIT 1");
            $stmt->execute([$booking_id]);
            $booking = $stmt->fetch();

            if (!$booking) return false;

            $lead_name = $booking['name'];
            $trek_name = $booking['trek_title'];
            $pickup_date = format_date($booking['start_date']);
            
            $pickup_spot = 'Own Transport Selected';
            if (($booking['package_type'] ?? 'with_transport') !== 'without_transport') {
                $pickup_spot = ($booking['pickup_loc'] ?? 'N/A') . ' (' . ($booking['pickup_time'] ? date('h:i A', strtotime($booking['pickup_time'])) : 'N/A') . ')';
            }

            $text_body = "Hello $lead_name,\n\n🎒 Trek Reminder! Your upcoming trek to $trek_name is starting on $pickup_date.\n\n📍 Pick-up Location: $pickup_spot\n\nThings to carry:\n- Backpack\n- Good trekking shoes\n- Raincoat/Poncho\n- Water bottle (2L)\n- Personal medicines\n\nEnsure you arrive at the pickup point 15 mins before time. See you on the trail!\n\nTeam Karnataka Trekkers 🏔️";
            $this->sendRawMessage($booking['phone'], $text_body);

            $template_name = get_setting('whatsapp_template_trek_reminder', 'trek_reminder');
            $params = [
                $lead_name,
                $trek_name,
                $pickup_date,
                $pickup_spot
            ];
            return $this->sendTemplateMessage($booking['phone'], $template_name, $params);

        } catch (Exception $e) {
            return false;
        }
    }
}
