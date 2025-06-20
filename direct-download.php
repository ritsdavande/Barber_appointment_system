<?php
/**
 * Direct download for PHPMailer files
 * This script manually downloads PHPMailer files instead of using file_get_contents
 */
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "Starting direct download of PHPMailer files...<br>";

// Target directory
$lib_dir = __DIR__ . '/assets/lib';

// Files to download
$files = [
    'class.phpmailer.php' => 'https://raw.githubusercontent.com/PHPMailer/PHPMailer/v5.2.28/class.phpmailer.php',
    'class.smtp.php' => 'https://raw.githubusercontent.com/PHPMailer/PHPMailer/v5.2.28/class.smtp.php'
];

// Make sure lib directory exists
if (!file_exists($lib_dir)) {
    if (!mkdir($lib_dir, 0777, true)) {
        die("Failed to create directory: $lib_dir");
    }
}

// Download each file using cURL
foreach ($files as $filename => $url) {
    $file_path = $lib_dir . '/' . $filename;
    echo "Downloading $filename... ";
    
    // Create cURL handle
    $ch = curl_init($url);
    
    // Open file for writing
    $fp = fopen($file_path, 'w');
    
    if ($fp === false) {
        echo "Failed to open file for writing.<br>";
        continue;
    }
    
    // Set cURL options
    curl_setopt($ch, CURLOPT_FILE, $fp);
    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    
    // Execute cURL session
    curl_exec($ch);
    
    // Check for errors
    if (curl_errno($ch)) {
        echo "Error: " . curl_error($ch) . "<br>";
    } else {
        echo "Success!<br>";
    }
    
    // Close cURL session and file
    curl_close($ch);
    fclose($fp);
    
    // Verify file was downloaded correctly
    if (file_exists($file_path) && filesize($file_path) > 0) {
        echo "Verified: $filename was downloaded successfully.<br>";
    } else {
        echo "Warning: $filename may not have downloaded correctly.<br>";
    }
}

echo "<br>Download process complete!<br>";
echo "<a href='test-gmail.php'>Test the PHPMailer integration</a>"; 