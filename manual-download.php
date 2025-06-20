<?php
/**
 * Manual PHPMailer file creation
 * This script provides the content of PHPMailer files for manual copy-paste
 */

// URLs for the source files
$urls = [
    'class.phpmailer.php' => 'https://raw.githubusercontent.com/PHPMailer/PHPMailer/v5.2.28/class.phpmailer.php',
    'class.smtp.php' => 'https://raw.githubusercontent.com/PHPMailer/PHPMailer/v5.2.28/class.smtp.php'
];

// Get file content and display for copy-paste
$file_contents = [];
foreach ($urls as $filename => $url) {
    // Try to fetch content
    $content = @file_get_contents($url);
    if ($content !== false) {
        $file_contents[$filename] = $content;
    } else {
        $file_contents[$filename] = "Could not retrieve content for $filename.";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Manual PHPMailer Download</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 900px;
            margin: 0 auto;
            padding: 20px;
        }
        h1, h2 {
            color: #333;
        }
        .file {
            margin-bottom: 30px;
        }
        .filename {
            background: #f1f1f1;
            padding: 10px;
            font-weight: bold;
            border-radius: 5px 5px 0 0;
            border: 1px solid #ddd;
            border-bottom: none;
        }
        pre {
            background: #f8f8f8;
            padding: 15px;
            border: 1px solid #ddd;
            overflow: auto;
            max-height: 400px;
            margin: 0;
            border-radius: 0 0 5px 5px;
        }
        .instructions {
            background: #ffffcc;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            border: 1px solid #e6e6b8;
        }
        .copy-btn {
            background: #4CAF50;
            color: white;
            border: none;
            padding: 8px 12px;
            border-radius: 4px;
            cursor: pointer;
            float: right;
        }
        .copy-btn:hover {
            background: #45a049;
        }
    </style>
</head>
<body>
    <h1>Manual PHPMailer File Creation</h1>
    
    <div class="instructions">
        <h2>Instructions:</h2>
        <ol>
            <li>For each file below, copy the entire content</li>
            <li>Create a new file with the exact filename shown in your <code>assets/lib/</code> directory</li>
            <li>Paste the content into the file and save it</li>
            <li>Once both files are created, you can test the PHPMailer setup with <a href="test-gmail.php">test-gmail.php</a></li>
        </ol>
    </div>
    
    <?php foreach ($file_contents as $filename => $content): ?>
    <div class="file">
        <div class="filename">
            <?php echo htmlspecialchars($filename); ?>
            <button class="copy-btn" onclick="copyToClipboard('content-<?php echo md5($filename); ?>')">Copy Content</button>
        </div>
        <pre id="content-<?php echo md5($filename); ?>"><?php echo htmlspecialchars($content); ?></pre>
    </div>
    <?php endforeach; ?>
    
    <script>
        function copyToClipboard(elementId) {
            const element = document.getElementById(elementId);
            const textarea = document.createElement('textarea');
            textarea.value = element.textContent;
            document.body.appendChild(textarea);
            textarea.select();
            document.execCommand('copy');
            document.body.removeChild(textarea);
            
            // Change button text temporarily
            const button = event.target;
            const originalText = button.textContent;
            button.textContent = 'Copied!';
            setTimeout(() => {
                button.textContent = originalText;
            }, 2000);
        }
    </script>
</body>
</html> 