<?php
// Mail Test Script for PHPMailer with Gmail SMTP
require_once 'assets/lib/mailer.php';

// Set to true to display diagnostics
$verbose = true;

echo "<h1>PHPMailer with Gmail SMTP Test</h1>";

// Check if PHPMailer files exist
$phpmailer_file = 'assets/lib/class.phpmailer.php';
$smtp_file = 'assets/lib/class.smtp.php';

if (!file_exists($phpmailer_file)) {
    echo "<p style='color:orange;'>PHPMailer class file not found. It will be downloaded automatically when sending an email.</p>";
} else {
    echo "<p style='color:green;'>PHPMailer class file exists.</p>";
}

if (!file_exists($smtp_file)) {
    echo "<p style='color:orange;'>SMTP class file not found. It will be downloaded automatically when sending an email.</p>";
} else {
    echo "<p style='color:green;'>SMTP class file exists.</p>";
}

// Check log directory
$log_dir = __DIR__ . '/logs';
if (!file_exists($log_dir)) {
    echo "<p style='color:red;'>Log directory does not exist. Creating it now...</p>";
    if (mkdir($log_dir, 0777, true)) {
        echo "<p style='color:green;'>Log directory created successfully.</p>";
    } else {
        echo "<p style='color:red;'>Failed to create log directory!</p>";
    }
} else {
    echo "<p style='color:green;'>Log directory exists.</p>";
    
    // Check if log directory is writable
    if (is_writable($log_dir)) {
        echo "<p style='color:green;'>Log directory is writable.</p>";
    } else {
        echo "<p style='color:red;'>Log directory is not writable!</p>";
    }
}

// Test email form
if (isset($_POST['test_email'])) {
    $to = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    $subject = "Test Email from Barber Shop - Gmail SMTP";
    $message = "This is a test email sent at " . date('Y-m-d H:i:s') . "\n";
    $message .= "If you received this email, your Gmail SMTP configuration is working correctly.";
    
    echo "<h2>Sending Test Email via Gmail SMTP</h2>";
    echo "<p>Sending to: $to</p>";
    
    $result = send_mail($to, $subject, $message);
    
    if ($result['success']) {
        echo "<p style='color:green;'>Test email sent successfully! Check your inbox (and spam folder).</p>";
    } else {
        echo "<p style='color:red;'>Failed to send test email.</p>";
        echo "<p>Error: " . $result['error'] . "</p>";
    }
    
    // Show log contents
    $log_file = $log_dir . '/email_log.txt';
    if (file_exists($log_file)) {
        echo "<h2>Recent Email Log</h2>";
        echo "<pre>";
        $log_content = file_get_contents($log_file);
        // Show last 10 lines
        $lines = explode("\n", $log_content);
        $lines = array_slice($lines, max(0, count($lines) - 10));
        echo implode("\n", $lines);
        echo "</pre>";
    }
}

// Email test form
?>
<!DOCTYPE html>
<html>
<head>
    <title>PHPMailer Gmail SMTP Test</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            line-height: 1.6;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            border: 1px solid #ddd;
            padding: 20px;
            border-radius: 5px;
        }
        .form-group {
            margin-bottom: 15px;
        }
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        input[type="email"] {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        button {
            background-color: #4CAF50;
            color: white;
            padding: 10px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        .note {
            background-color: #ffffcc;
            padding: 10px;
            border-left: 4px solid #ffeb3b;
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Send Test Email via Gmail SMTP</h2>
        <form method="post">
            <div class="form-group">
                <label for="email">Email address to test:</label>
                <input type="email" id="email" name="email" required>
            </div>
            <button type="submit" name="test_email">Send Test Email</button>
        </form>
        
        <div class="note">
            <p><strong>Configuration Information:</strong></p>
            <ul>
                <li>Using Gmail SMTP with your account: ritsdavande@gmail.com</li>
                <li>SMTP Server: smtp.gmail.com</li>
                <li>Port: 587</li>
                <li>Security: TLS</li>
            </ul>
            
            <p><strong>Troubleshooting:</strong></p>
            <ul>
                <li>Make sure your Gmail app password is correct</li>
                <li>Check that PHP has the necessary extensions: openssl, filter</li>
                <li>Allow less secure apps or configure app passwords in your Google account</li>
                <li>You may need to enable outbound SMTP traffic on your server/firewall</li>
            </ul>
        </div>
    </div>
</body>
</html> 