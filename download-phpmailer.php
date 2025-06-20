<?php
/**
 * PHPMailer Downloader
 * 
 * This script downloads the necessary PHPMailer files for Gmail SMTP integration
 */

$lib_dir = __DIR__ . '/assets/lib';

// Files to download
$files = [
    'class.phpmailer.php' => 'https://raw.githubusercontent.com/PHPMailer/PHPMailer/v5.2.28/class.phpmailer.php',
    'class.smtp.php' => 'https://raw.githubusercontent.com/PHPMailer/PHPMailer/v5.2.28/class.smtp.php',
    'class.pop3.php' => 'https://raw.githubusercontent.com/PHPMailer/PHPMailer/v5.2.28/class.pop3.php',
    'class.phpmaileroauth.php' => 'https://raw.githubusercontent.com/PHPMailer/PHPMailer/v5.2.28/class.phpmaileroauth.php',
    'class.phpmaileroauthgoogle.php' => 'https://raw.githubusercontent.com/PHPMailer/PHPMailer/v5.2.28/class.phpmaileroauthgoogle.php'
];

echo "<!DOCTYPE html>
<html>
<head>
    <title>PHPMailer Downloader</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
            line-height: 1.6;
        }
        h1 {
            color: #333;
            border-bottom: 2px solid #eee;
            padding-bottom: 10px;
        }
        .success {
            color: green;
            background: #e8f5e9;
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 10px;
        }
        .error {
            color: red;
            background: #ffebee;
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 10px;
        }
        .warning {
            color: #856404;
            background: #fff3cd;
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 10px;
        }
        pre {
            background: #f5f5f5;
            padding: 10px;
            border-radius: 4px;
            overflow: auto;
        }
    </style>
</head>
<body>
    <h1>PHPMailer Downloader</h1>";

// Check if the lib directory exists
if (!file_exists($lib_dir)) {
    echo "<div class='error'>Library directory does not exist at: $lib_dir</div>";
    echo "<p>Please create the directory first, or check your installation.</p>";
    exit;
}

echo "<p>Downloading PHPMailer files to: $lib_dir</p>";

$all_success = true;

// Download each file
foreach ($files as $filename => $url) {
    $file_path = $lib_dir . '/' . $filename;
    
    echo "<h3>Processing $filename:</h3>";
    
    // Check if file already exists
    if (file_exists($file_path)) {
        echo "<div class='warning'>File already exists. Skipping download.</div>";
        continue;
    }
    
    // Try to download the file
    try {
        $file_content = @file_get_contents($url);
        
        if ($file_content === false) {
            echo "<div class='error'>Failed to download file from: $url</div>";
            $all_success = false;
            continue;
        }
        
        // Save the file
        $result = @file_put_contents($file_path, $file_content);
        
        if ($result === false) {
            echo "<div class='error'>Failed to save file to: $file_path</div>";
            $all_success = false;
        } else {
            echo "<div class='success'>Successfully downloaded and saved: $filename</div>";
        }
    } catch (Exception $e) {
        echo "<div class='error'>Error: " . $e->getMessage() . "</div>";
        $all_success = false;
    }
}

if ($all_success) {
    echo "<div class='success'><strong>All PHPMailer files have been downloaded successfully!</strong></div>";
    echo "<p>You can now use the Gmail SMTP email functionality in your application.</p>";
} else {
    echo "<div class='warning'><strong>Some files could not be downloaded.</strong></div>";
    echo "<p>Please check the errors above and try again, or download the files manually.</p>";
}

echo "<h3>Next Steps:</h3>
<ol>
    <li>Test sending emails with <a href='mail-test.php'>mail-test.php</a></li>
    <li>Make sure your Gmail account settings allow less secure apps or create an app password</li>
    <li>Update your email configuration in assets/lib/mailer.php if needed</li>
</ol>
</body>
</html>"; 