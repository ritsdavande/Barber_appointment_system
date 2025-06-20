<?php
/**
 * Embedded PHPMailer
 * 
 * This file contains embedded minimal versions of the PHPMailer classes
 * to avoid the need for downloading separate files.
 */

/**
 * PHPMailer - PHP email transport class
 */
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

/**
 * SMTP - PHP SMTP class
 */
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
                $host = $_SERVER['SERVER_NAME'];
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