<?php
/**
 * All-in-One PHPMailer Test
 * 
 * This file contains everything needed to test sending emails via Gmail SMTP.
 * No external files are required.
 */
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo '<h1>All-in-One PHPMailer Gmail SMTP Test</h1>';

// Define embedded PHPMailer classes
if (!class_exists('PHPMailer')) {
    class PHPMailer {
        public $Version = '5.2.28-embedded';
        public $Priority;
        public $CharSet = 'iso-8859-1';
        public $ContentType = 'text/plain';
        public $Encoding = '8bit';
        public $ErrorInfo = '';
        public $From = 'root@localhost';
        public $FromName = 'Root User';
        public $Sender = '';
        public $ReturnPath = '';
        public $Subject = '';
        public $Body = '';
        public $AltBody = '';
        public $Mailer = 'smtp';
        public $Host = 'localhost';
        public $Port = 25;
        public $Helo = '';
        public $SMTPSecure = '';
        public $SMTPAuth = false;
        public $Username = '';
        public $Password = '';
        public $SMTPOptions = array();
        public $SMTPKeepAlive = false;
        public $SingleTo = false;
        public $SingleToArray = array();
        public $do_verp = false;
        public $AllowEmpty = false;
        public $DKIM_selector = '';
        public $DKIM_identity = '';
        public $DKIM_passphrase = '';
        public $DKIM_domain = '';
        public $DKIM_private = '';
        public $DKIM_private_string = '';
        public $action_function = '';
        public $XMailer = '';
        protected $to = array();
        protected $cc = array();
        protected $bcc = array();
        protected $ReplyTo = array();
        protected $all_recipients = array();
        protected $attachment = array();
        protected $CustomHeader = array();
        protected $message_type = '';
        protected $boundary = array();
        protected $language = array();
        protected $error_count = 0;
        protected $sign_cert_file = '';
        protected $sign_key_file = '';
        protected $sign_key_pass = '';
        protected $exceptions = false;
        protected $smtp = null;

        public function __construct($exceptions = false) {
            $this->exceptions = (boolean)$exceptions;
        }

        public function setFrom($address, $name = '', $auto = true) {
            $this->From = $address;
            $this->FromName = $name;
            return true;
        }

        public function addAddress($address, $name = '') {
            $this->to[] = array($address, $name);
            return true;
        }

        public function addReplyTo($address, $name = '') {
            $this->ReplyTo[] = array($address, $name);
            return true;
        }

        public function isHTML($isHtml = true) {
            $this->ContentType = $isHtml ? 'text/html' : 'text/plain';
        }

        public function isSMTP() {
            $this->Mailer = 'smtp';
        }

        public function send() {
            try {
                if (!isset($this->smtp)) {
                    $this->smtp = new SMTP;
                }
                
                // Connect to the SMTP server
                if (!$this->smtp->connect($this->Host, $this->Port)) {
                    throw new Exception('Failed to connect to SMTP server');
                }
                
                // Say hello
                if (!$this->smtp->hello($this->Helo)) {
                    throw new Exception('SMTP EHLO failed');
                }
                
                // Start TLS if needed
                if ($this->SMTPSecure == 'tls') {
                    if (!$this->smtp->startTLS()) {
                        throw new Exception('SMTP StartTLS failed');
                    }
                    // Resend EHLO after TLS
                    if (!$this->smtp->hello($this->Helo)) {
                        throw new Exception('SMTP EHLO after TLS failed');
                    }
                }
                
                // Authenticate if required
                if ($this->SMTPAuth) {
                    if (!$this->smtp->authenticate($this->Username, $this->Password)) {
                        throw new Exception('SMTP authentication failed');
                    }
                }
                
                // Set the sender
                if (!$this->smtp->mail($this->From)) {
                    throw new Exception('SMTP MAIL FROM failed');
                }
                
                // Set the recipients
                foreach ($this->to as $toaddr) {
                    if (!$this->smtp->recipient($toaddr[0])) {
                        throw new Exception('SMTP RCPT TO failed');
                    }
                }
                
                // Send the message
                if (!$this->smtp->data($this->createHeader() . $this->createBody())) {
                    throw new Exception('SMTP DATA failed');
                }
                
                // Disconnect from the server
                $this->smtp->quit();
                $this->smtp->close();
                
                return true;
            } catch (Exception $e) {
                $this->ErrorInfo = $e->getMessage();
                return false;
            }
        }
        
        protected function createHeader() {
            $header = "From: " . $this->FromName . " <" . $this->From . ">\r\n";
            $header .= "Reply-To: " . $this->From . "\r\n";
            $header .= "Subject: " . $this->Subject . "\r\n";
            $header .= "To: " . $this->to[0][0] . "\r\n";
            $header .= "MIME-Version: 1.0\r\n";
            $header .= "Content-Type: " . $this->ContentType . "; charset=" . $this->CharSet . "\r\n";
            $header .= "Content-Transfer-Encoding: " . $this->Encoding . "\r\n";
            $header .= "\r\n";
            return $header;
        }
        
        protected function createBody() {
            return $this->Body;
        }
    }
}

if (!class_exists('SMTP')) {
    class SMTP {
        const VERSION = '5.2.28-embedded';
        const CRLF = "\r\n";
        const DEFAULT_SMTP_PORT = 25;
        public $do_debug = 0;
        public $Debugoutput = 'echo';
        public $do_verp = false;
        public $Timeout = 300;
        public $Timelimit = 300;
        protected $smtp_conn;
        protected $error = array();
        protected $helo_rply = null;
        protected $server_caps = null;
        protected $last_reply = '';

        public function connect($host, $port = null) {
            $this->error = null;
            
            if (empty($port)) {
                $port = self::DEFAULT_SMTP_PORT;
            }
            
            $this->smtp_conn = @fsockopen($host, $port, $errno, $errstr, $this->Timeout);
            
            if (empty($this->smtp_conn)) {
                $this->error = array('error' => "Failed to connect to server", 'errno' => $errno, 'errstr' => $errstr);
                return false;
            }
            
            // Receive greeting
            $announce = $this->get_lines();
            
            return true;
        }
        
        public function startTLS() {
            if (!$this->sendCommand('STARTTLS', 'STARTTLS', 220)) {
                return false;
            }
            
            // Begin encrypted connection
            if (!stream_socket_enable_crypto(
                $this->smtp_conn,
                true,
                STREAM_CRYPTO_METHOD_TLS_CLIENT
            )) {
                return false;
            }
            
            return true;
        }
        
        public function authenticate($username, $password) {
            // Send AUTH command
            if (!$this->sendCommand('AUTH LOGIN', 'AUTH LOGIN', 334)) {
                return false;
            }
            
            // Send username
            if (!$this->sendCommand(base64_encode($username), '', 334)) {
                return false;
            }
            
            // Send password
            if (!$this->sendCommand(base64_encode($password), '', 235)) {
                return false;
            }
            
            return true;
        }
        
        public function hello($host = '') {
            if (empty($host)) {
                // Use your system hostname by default
                $host = isset($_SERVER['SERVER_NAME']) ? $_SERVER['SERVER_NAME'] : 'localhost';
            }
            
            // Send EHLO command
            if (!$this->sendCommand('EHLO', "EHLO $host", 250)) {
                // If EHLO fails, try HELO
                if (!$this->sendCommand('HELO', "HELO $host", 250)) {
                    return false;
                }
            }
            
            return true;
        }
        
        public function mail($from) {
            return $this->sendCommand('MAIL FROM', "MAIL FROM:<$from>", 250);
        }
        
        public function recipient($to) {
            return $this->sendCommand('RCPT TO', "RCPT TO:<$to>", 250);
        }
        
        public function data($msg) {
            if (!$this->sendCommand('DATA', 'DATA', 354)) {
                return false;
            }
            
            $msg = str_replace("\r\n", "\n", $msg);
            $msg = str_replace("\r", "\n", $msg);
            $lines = explode("\n", $msg);
            
            // Add a period if the last line doesn't have one
            $msg = preg_replace('/\r\n$/', '', $msg);
            $msg = $msg . self::CRLF . '.';
            
            return $this->sendCommand('DATA END', $msg, 250, true);
        }
        
        public function quit() {
            return $this->sendCommand('QUIT', 'QUIT', 221);
        }
        
        public function close() {
            if (!empty($this->smtp_conn)) {
                fclose($this->smtp_conn);
                $this->smtp_conn = null;
            }
        }
        
        protected function sendCommand($command, $commandstring, $expect, $noresponse = false) {
            if (!$this->connected()) {
                $this->error = array('error' => "Called $command without being connected");
                return false;
            }
            
            fputs($this->smtp_conn, $commandstring . self::CRLF);
            
            if ($noresponse) {
                return true;
            }
            
            $reply = $this->get_lines();
            $code = substr($reply, 0, 3);
            
            if ($code != $expect) {
                $this->error = array('error' => "$command failed", 'smtp_code' => $code, 'smtp_msg' => substr($reply, 4));
                return false;
            }
            
            $this->last_reply = $reply;
            return true;
        }
        
        protected function connected() {
            if (!empty($this->smtp_conn)) {
                $status = socket_get_status($this->smtp_conn);
                if ($status['eof']) {
                    // Close the connection
                    $this->close();
                    return false;
                }
                return true;
            }
            return false;
        }
        
        protected function get_lines() {
            if (!$this->smtp_conn) {
                return '';
            }
            
            $data = '';
            while ($str = fgets($this->smtp_conn, 515)) {
                $data .= $str;
                if (substr($str, 3, 1) == ' ') {
                    break;
                }
            }
            
            return $data;
        }
    }
}

// Create a log directory if it doesn't exist
$log_dir = __DIR__ . '/logs';
if (!file_exists($log_dir)) {
    mkdir($log_dir, 0777, true);
}

// Process form submission
if (isset($_POST['send'])) {
    $to = filter_var($_POST['to_email'], FILTER_SANITIZE_EMAIL);
    $subject = "Test Email from Barber Shop";
    $message = "This is a test email sent at " . date('Y-m-d H:i:s') . "\n";
    $message .= "If you received this email, your Gmail SMTP configuration is working correctly.";
    
    // Gmail credentials
    $gmail_username = 'ritsdavande@gmail.com';
    $gmail_password = 'gbko rlkn hmav zsyp';
    $from_name = 'Barber Hair Salon';
    
    // Log attempt
    $log_file = $log_dir . '/email_log.txt';
    $log_message = date('Y-m-d H:i:s') . " - Attempting to send email to: $to\n";
    file_put_contents($log_file, $log_message, FILE_APPEND);
    
    try {
        // Create a new PHPMailer instance
        $mail = new PHPMailer();
        
        // Set PHPMailer to use SMTP
        $mail->isSMTP();
        
        // Gmail SMTP settings
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = $gmail_username;
        $mail->Password = $gmail_password;
        $mail->SMTPSecure = 'tls';
        $mail->Port = 587;
        
        // Set sender
        $mail->setFrom($gmail_username, $from_name);
        $mail->addReplyTo($gmail_username, $from_name);
        
        // Add recipient
        $mail->addAddress($to);
        
        // Set email content
        $mail->Subject = $subject;
        $mail->Body = $message;
        
        // Send the email
        $success = $mail->send();
        
        if ($success) {
            $result = '<div style="color: green; padding: 10px; background: #e8f5e9; border-radius: 5px; margin: 10px 0;">Email sent successfully! Check your inbox (and spam folder).</div>';
        } else {
            $result = '<div style="color: red; padding: 10px; background: #ffebee; border-radius: 5px; margin: 10px 0;">Failed to send email: ' . $mail->ErrorInfo . '</div>';
        }
        
        // Log result
        $result_message = date('Y-m-d H:i:s') . " - Email to $to: " . 
                         ($success ? 'SUCCESS' : 'FAILED - ' . $mail->ErrorInfo) . "\n";
        file_put_contents($log_file, $result_message, FILE_APPEND);
        
    } catch (Exception $e) {
        $result = '<div style="color: red; padding: 10px; background: #ffebee; border-radius: 5px; margin: 10px 0;">Exception: ' . $e->getMessage() . '</div>';
        
        // Log error
        $error_message = date('Y-m-d H:i:s') . " - Exception: " . $e->getMessage() . "\n";
        file_put_contents($log_file, $error_message, FILE_APPEND);
    }
}

// Display form
?>
<!DOCTYPE html>
<html>
<head>
    <title>Gmail SMTP Test</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }
        .form-container {
            background: #f9f9f9;
            padding: 20px;
            border-radius: 5px;
            border: 1px solid #ddd;
        }
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        input[type="email"] {
            width: 100%;
            padding: 8px;
            margin-bottom: 20px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        button {
            background: #4CAF50;
            color: white;
            border: none;
            padding: 10px 15px;
            border-radius: 4px;
            cursor: pointer;
        }
        button:hover {
            background: #45a049;
        }
        .info {
            background: #ffffcc;
            padding: 15px;
            border-radius: 5px;
            margin-top: 20px;
            border: 1px solid #e6e6b8;
        }
        pre {
            background: #f8f8f8;
            padding: 10px;
            border-radius: 4px;
            overflow: auto;
            max-height: 200px;
            border: 1px solid #ddd;
        }
    </style>
</head>
<body>
    <?php if(isset($result)) echo $result; ?>
    
    <div class="form-container">
        <h2>Send Test Email via Gmail SMTP</h2>
        <form method="post">
            <label for="to_email">Email address to test:</label>
            <input type="email" id="to_email" name="to_email" required value="<?php echo isset($_POST['to_email']) ? htmlspecialchars($_POST['to_email']) : ''; ?>">
            
            <button type="submit" name="send">Send Test Email</button>
        </form>
    </div>
    
    <div class="info">
        <h3>Gmail SMTP Configuration</h3>
        <ul>
            <li>SMTP Server: smtp.gmail.com</li>
            <li>Port: 587</li>
            <li>Security: TLS</li>
            <li>Username: ritsdavande@gmail.com</li>
            <li>Password: [App Password]</li>
        </ul>
        
        <h3>Requirements</h3>
        <ul>
            <li>PHP must have OpenSSL extension enabled</li>
            <li>PHP must be able to make outbound connections on port 587</li>
            <li>Gmail account must have "Less secure apps" enabled or an App Password configured</li>
        </ul>
    </div>
    
    <?php if(file_exists($log_file)): ?>
    <div style="margin-top: 20px;">
        <h3>Recent Email Log</h3>
        <pre><?php
        $log_content = file_get_contents($log_file);
        $lines = explode("\n", $log_content);
        $lines = array_slice($lines, max(0, count($lines) - 10));
        echo implode("\n", $lines);
        ?></pre>
    </div>
    <?php endif; ?>
</body>
</html> 