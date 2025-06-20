<?php
/**
 * Send appointment confirmation emails
 */

// Include required files
require_once dirname(__FILE__) . '/mailer.php';
require_once dirname(__FILE__) . '/email_templates.php';

/**
 * Send appointment confirmation email to customer
 *
 * @param array $appointment_data Array containing appointment details:
 *                               - name: Customer name
 *                               - email: Customer email address
 *                               - booking_id: Unique booking reference
 *                               - category: Service category
 *                               - date: Appointment date (can be combined with time)
 *                               - time: Appointment time (if separate)
 *                               - mobile_no: Customer phone number
 * @return array Result with success flag and error message if any
 */
function send_appointment_confirmation($appointment_data) {
    // Log directory
    $log_dir = dirname(dirname(__DIR__)) . '/logs';
    if (!file_exists($log_dir)) {
        mkdir($log_dir, 0777, true);
    }
    
    // Validate required fields
    $required_fields = ['name', 'email'];
    foreach ($required_fields as $field) {
        if (empty($appointment_data[$field])) {
            $error = "Missing required field: $field";
            file_put_contents(
                $log_dir . '/email_errors.txt', 
                date('Y-m-d H:i:s') . " - Error: $error\n", 
                FILE_APPEND
            );
            return ['success' => false, 'error' => $error];
        }
    }
    
    // Format date and time for display
    $date = isset($appointment_data['date']) ? $appointment_data['date'] : '';
    $time = isset($appointment_data['time']) ? $appointment_data['time'] : '';
    
    // If date contains time information (combined field)
    if (!empty($date) && empty($time) && strpos($date, ' ') !== false) {
        $parts = explode(' ', $date);
        $date = $parts[0];
        $time = isset($parts[1]) ? $parts[1] : '';
    }
    
    // Format date for display
    $formatted_date = !empty($date) ? date('l, F j, Y', strtotime($date)) : 'Not specified';
    
    // Format time for display
    $formatted_time = !empty($time) ? date('g:i A', strtotime($time)) : 'Not specified';
    
    // Prepare email template data
    $email_data = [
        'name' => $appointment_data['name'],
        'booking_id' => isset($appointment_data['booking_id']) ? $appointment_data['booking_id'] : 'N/A',
        'category' => isset($appointment_data['category']) ? $appointment_data['category'] : 'Not specified',
        'formatted_date' => $formatted_date,
        'formatted_time' => $formatted_time,
        'mobile_no' => isset($appointment_data['mobile_no']) ? $appointment_data['mobile_no'] : 'Not provided'
    ];
    
    // Generate email content
    $message = get_appointment_email($email_data);
    
    // Send the email
    $subject = "Your Appointment Confirmation - Barber Hair Salon";
    $result = send_mail($appointment_data['email'], $subject, $message);
    
    if (!$result['success']) {
        // Log error
        file_put_contents(
            $log_dir . '/email_errors.txt', 
            date('Y-m-d H:i:s') . " - Failed to send email to {$appointment_data['email']}: {$result['error']}\n", 
            FILE_APPEND
        );
    } else {
        // Log success
        file_put_contents(
            $log_dir . '/email_success.txt', 
            date('Y-m-d H:i:s') . " - Successfully sent appointment email to {$appointment_data['email']}\n", 
            FILE_APPEND
        );
    }
    
    return $result;
} 