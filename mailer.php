<?php
/**
 * PHPMailer implementation for sending emails through Gmail SMTP
 * 
 * This provides a robust mail function using PHPMailer with better error handling
 * and logging than PHP's native mail() function.
 */

// Try to use PHPMailerAutoload first - remove if it causes errors
if (file_exists(dirname(__FILE__) . '/PHPMailerAutoload.php')) {
    @include_once dirname(__FILE__) . '/PHPMailerAutoload.php';
}

// Check for separate PHPMailer files
$phpmailer_class = dirname(__FILE__) . '/class.phpmailer.php';
$smtp_class = dirname(__FILE__) . '/class.smtp.php';

// If PHPMailer class files don't exist, try to include embedded version
if (!file_exists($phpmailer_class) || !file_exists($smtp_class)) {
    // Check if embedded version exists
    $embedded_file = dirname(__FILE__) . '/embedded-phpmailer.php';
    if (file_exists($embedded_file)) {
        require_once $embedded_file;
    } else {
        // Try to download PHPMailer files
        $phpmailer_url = 'https://raw.githubusercontent.com/PHPMailer/PHPMailer/v5.2.28/class.phpmailer.php';
        $smtp_url = 'https://raw.githubusercontent.com/PHPMailer/PHPMailer/v5.2.28/class.smtp.php';
        
        // Only attempt download if file_get_contents with URL is allowed
        if (ini_get('allow_url_fopen')) {
            @file_put_contents($phpmailer_class, @file_get_contents($phpmailer_url));
            @file_put_contents($smtp_class, @file_get_contents($smtp_url));
        }
    }
}

/**
 * Send an email with PHPMailer through Gmail SMTP
 * 
 * @param string $to Recipient email address
 * @param string $subject Email subject
 * @param string $message Email body
 * @param string $from_email Sender email (optional)
 * @param string $from_name Sender name (optional)
 * @return array Result array with success flag and error message if any
 */
function send_mail($to, $subject, $message, $from_email = 'ritsdavande@gmail.com', $from_name = 'Barber Hair Salon') {
    // Create a log directory if it doesn't exist
    $log_dir = dirname(dirname(__DIR__)) . '/logs';
    if (!file_exists($log_dir)) {
        mkdir($log_dir, 0777, true);
    }
    
    // Log file
    $log_file = $log_dir . '/email_log.txt';
    
    // Sanitize inputs
    $to = filter_var($to, FILTER_SANITIZE_EMAIL);
    $subject = htmlspecialchars($subject, ENT_QUOTES, 'UTF-8');
    
    // Log attempt
    $log_message = date('Y-m-d H:i:s') . " - Attempting to send email to: $to\n";
    file_put_contents($log_file, $log_message, FILE_APPEND);
    
    // Initialize result variables
    $success = false;
    $error = '';
    
    try {
        // Check if PHPMailer class exists
        if (!class_exists('PHPMailer')) {
            throw new Exception('PHPMailer class not found. Please run download-phpmailer.php or use manual-download.php.');
        }
        
        // Create a new PHPMailer instance
        $mail = new PHPMailer();
        
        // Set PHPMailer to use SMTP
        $mail->isSMTP();
        
        // Gmail SMTP settings
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'ritsdavande@gmail.com'; // Your Gmail address
        $mail->Password = 'gbko rlkn hmav zsyp'; // Your Gmail app password
        $mail->SMTPSecure = 'tls';
        $mail->Port = 587;
        
        // Set sender
        $mail->setFrom($from_email, $from_name);
        $mail->addReplyTo($from_email, $from_name);
        
        // Add recipient
        $mail->addAddress($to);
        
        // Set email content
        $mail->Subject = $subject;
        $mail->Body = $message;
        $mail->IsHTML(false); // Set to true if you want to send HTML emails
        
        // Send the email
        $success = $mail->send();
        
        if (!$success) {
            $error = 'Mailer Error: ' . $mail->ErrorInfo;
        }
        
    } catch (Exception $e) {
        $error = 'Exception: ' . $e->getMessage();
    }
    
    // Log result
    $result_message = date('Y-m-d H:i:s') . " - Email to $to: " . 
                     ($success ? 'SUCCESS' : 'FAILED - ' . $error) . "\n";
    file_put_contents($log_file, $result_message, FILE_APPEND);
    
    return array(
        'success' => $success,
        'error' => $error
    );
} 