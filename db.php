<?php
// SAFE session start
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$DB_HOST = "localhost";
$DB_USER = "root";
$DB_PASS = "";

// Try multiple database names
$DB_CANDIDATES = ['todo_db', 'todo-list'];
$conn = null;
$lastErr = '';
foreach ($DB_CANDIDATES as $candidate) {
    try {
        $tmp = new mysqli($DB_HOST, $DB_USER, $DB_PASS, $candidate);
        if ($tmp->connect_errno === 0) {
            $conn = $tmp;
            $DB_NAME = $candidate;
            break;
        }
        $lastErr = $tmp->connect_error ?: 'Unknown connection error';
    } catch (mysqli_sql_exception $e) {
        $lastErr = $e->getMessage();
    }
}

if (!$conn) {
    die("Database connection failed: " . $lastErr . " (tried: " . implode(', ', $DB_CANDIDATES) . ")");
}

// Helpers
function require_login() {
    if (empty($_SESSION['user_id'])) {
        header("Location: login.php");
        exit;
    }
}

function require_role($role) {
    require_login();
    if (empty($_SESSION['role']) || $_SESSION['role'] !== $role) {
        header("Location: user.php");
        exit;
    }
}

function e($str) {
    return htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8');
}

// Ensure messages table exists
global $conn;
$conn->query("CREATE TABLE IF NOT EXISTS messages (
  id INT AUTO_INCREMENT PRIMARY KEY,
  sender_role VARCHAR(10) NOT NULL,
  sender_id INT DEFAULT NULL,
  recipient_role VARCHAR(10) DEFAULT NULL,
  recipient_user_id INT DEFAULT NULL,
  message TEXT NOT NULL,
  is_read TINYINT(1) NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

// Ensure due_date column exists
$res = $conn->query("SHOW COLUMNS FROM tasks LIKE 'due_date'");
if ($res && $res->num_rows === 0) {
    $conn->query("ALTER TABLE tasks ADD COLUMN due_date DATE NULL AFTER assigned_to");
}

// Email helpers
function get_admin_email() {
    global $conn;
    $email = 'admin@example.com';
    $stmt = $conn->prepare("SELECT email FROM admins ORDER BY id ASC LIMIT 1");
    if ($stmt) {
        $stmt->execute();
        $res = $stmt->get_result();
        if ($r = $res->fetch_assoc()) $email = $r['email'] ?: $email;
        $stmt->close();
    }
    return $email;
}

function get_user_email($user_id) {
    global $conn;
    $email = null;
    $stmt = $conn->prepare("SELECT email FROM users WHERE id = ? LIMIT 1");
    if ($stmt) {
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($r = $res->fetch_assoc()) $email = $r['email'];
        $stmt->close();
    }
    return $email;
}

// ✅ Fixed SMTP method with proper error handling
function send_email_notification($to, $subject, $body) {
    if (!$to) {
        error_log("Email send failed: No recipient email address");
        return false;
    }

    // Load Gmail SMTP configuration from mail_config.php
    $configPath = __DIR__ . '/mail_config.php';
    if (!file_exists($configPath)) {
        error_log("Email send failed: mail_config.php not found at $configPath");
        return false;
    }
    
    $config = include $configPath;
    if (!$config || !is_array($config)) {
        error_log("Email send failed: Invalid mail config");
        return false;
    }

    $host = $config['host'] ?? 'smtp.gmail.com';
    $port = $config['port'] ?? 587;
    $username = $config['username'] ?? '';
    $password = $config['password'] ?? '';
    $from = $config['from_email'] ?? $username;
    $fromName = $config['from_name'] ?? 'ToDo App';

    if (empty($username) || empty($password)) {
        error_log("Email send failed: Missing username or password in config");
        return false;
    }

    // Helper function to read SMTP response
    $expect = function($fp, $code, $step = '') use (&$to) {
        $resp = '';
        $timeout = 10;
        $start = time();
        while (time() - $start < $timeout) {
            $line = @fgets($fp, 512);
            if ($line === false) {
                if (feof($fp)) break;
                continue;
            }
            $resp .= $line;
            // Check if this is the last line of response (space at position 3)
            if (strlen($line) > 3 && $line[3] == ' ') {
                break;
            }
        }
        $codeStr = (string)$code;
        $success = (strlen($resp) >= strlen($codeStr) && substr($resp, 0, strlen($codeStr)) === $codeStr);
        if (!$success && !empty($step)) {
            error_log("SMTP error at $step: Expected $code, got: " . trim($resp));
        }
        return $success ? $resp : false;
    };

    // Connect to SMTP server
    $remote = ($port == 465 ? 'ssl://' : 'tcp://') . $host . ':' . $port;
    
    // Relaxed SSL context for better compatibility
    $context = stream_context_create([
        'ssl' => [
            'verify_peer' => false,
            'verify_peer_name' => false,
            'allow_self_signed' => true
        ]
    ]);

    $errno = 0;
    $errstr = '';
    $fp = @stream_socket_client($remote, $errno, $errstr, 30, STREAM_CLIENT_CONNECT, $context);
    
    if (!$fp) {
        error_log("SMTP connection failed to $remote: $errstr ($errno)");
        return false;
    }

    // Set timeout
    stream_set_timeout($fp, 30);

    // Read initial server greeting (220)
    $greeting = $expect($fp, 220, 'greeting');
    if ($greeting === false) {
        fclose($fp);
        return false;
    }

    // Send EHLO
    fwrite($fp, "EHLO localhost\r\n");
    if ($expect($fp, 250, 'EHLO') === false) {
        fclose($fp);
        return false;
    }

    // STARTTLS for port 587
    if ($port == 587) {
        fwrite($fp, "STARTTLS\r\n");
        if ($expect($fp, 220, 'STARTTLS') === false) {
            fclose($fp);
            return false;
        }
        
        $crypto = @stream_socket_enable_crypto($fp, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
        if (!$crypto) {
            error_log("SMTP TLS encryption failed");
            fclose($fp);
            return false;
        }
        
        // Send EHLO again after TLS
        fwrite($fp, "EHLO localhost\r\n");
        if ($expect($fp, 250, 'EHLO after TLS') === false) {
            fclose($fp);
            return false;
        }
    }

    // AUTH LOGIN
    fwrite($fp, "AUTH LOGIN\r\n");
    if ($expect($fp, 334, 'AUTH LOGIN') === false) {
        fclose($fp);
        return false;
    }
    
    fwrite($fp, base64_encode($username) . "\r\n");
    if ($expect($fp, 334, 'Username') === false) {
        fclose($fp);
        return false;
    }
    
    fwrite($fp, base64_encode($password) . "\r\n");
    if ($expect($fp, 235, 'Password') === false) {
        error_log("SMTP authentication failed for user: $username");
        fclose($fp);
        return false;
    }

    // MAIL FROM
    fwrite($fp, "MAIL FROM:<$from>\r\n");
    if ($expect($fp, 250, 'MAIL FROM') === false) {
        fclose($fp);
        return false;
    }
    
    // RCPT TO
    fwrite($fp, "RCPT TO:<$to>\r\n");
    if ($expect($fp, 250, 'RCPT TO') === false) {
        fclose($fp);
        return false;
    }

    // DATA
    fwrite($fp, "DATA\r\n");
    if ($expect($fp, 354, 'DATA') === false) {
        fclose($fp);
        return false;
    }

    // Email headers and body
    $headers = "From: $fromName <$from>\r\n";
    $headers .= "To: $to\r\n";
    $headers .= "Subject: $subject\r\n";
    $headers .= "Date: " . date('r') . "\r\n";
    $headers .= "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
    $headers .= "\r\n";

    fwrite($fp, $headers . $body . "\r\n.\r\n");
    if ($expect($fp, 250, 'Message data') === false) {
        fclose($fp);
        return false;
    }

    // QUIT
    fwrite($fp, "QUIT\r\n");
    fclose($fp);
    
    error_log("Email sent successfully to: $to");
    return true;
}

function notify_recipient_by_email($recipient_role, $recipient_user_id, $subject, $body) {
    $to = null;
    
    if ($recipient_role === 'admin') {
        $to = get_admin_email();
        if (!$to || $to === 'admin@example.com') {
            error_log("Email notification failed: Admin email not found or using default");
            // Still try to send to default admin email from config
            $configPath = __DIR__ . '/mail_config.php';
            if (file_exists($configPath)) {
                $config = include $configPath;
                $to = $config['from_email'] ?? $config['username'] ?? null;
            }
        }
    } elseif ($recipient_role === 'user' && $recipient_user_id) {
        $to = get_user_email($recipient_user_id);
        if (!$to) {
            error_log("Email notification failed: User email not found for user_id: $recipient_user_id");
        }
    } else {
        error_log("Email notification failed: Invalid recipient_role: $recipient_role or missing user_id");
    }
    
    if (!$to) {
        error_log("Email notification skipped: No valid email address for recipient_role=$recipient_role, user_id=$recipient_user_id");
        return false;
    }
    
    error_log("Sending email notification to: $to (role: $recipient_role, subject: $subject)");
    return send_email_notification($to, $subject, $body);
}
