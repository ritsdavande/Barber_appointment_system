<?php
// Simple test script for Gmail SMTP
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Testing Gmail SMTP</h1>";

// Include mailer library
require_once 'assets/lib/mailer.php';

// Test sending an email
$to = "ritsdavande@gmail.com"; // Send to the provided Gmail address
$subject = "Test Email from Barber Shop";
$message = "This is a test email sent at " . date('Y-m-d H:i:s') . "\n";
$message .= "If you received this email, your Gmail SMTP configuration is working correctly.";

echo "<p>Attempting to send test email to: $to</p>";

$result = send_mail($to, $subject, $message);

if ($result['success']) {
    echo "<p style='color:green;'>Test email sent successfully! Check your inbox (and spam folder).</p>";
} else {
    echo "<p style='color:red;'>Failed to send test email.</p>";
    echo "<p>Error: " . $result['error'] . "</p>";
}

// Show log file if it exists
$log_file = __DIR__ . '/logs/email_log.txt';
if (file_exists($log_file)) {
    echo "<h2>Email Log</h2>";
    echo "<pre>" . file_get_contents($log_file) . "</pre>";
} 