<?php
// email_helper.php
// Provides send_email_notification() using native SMTP with Gmail

function send_email_notification($to, $subject, $body) {
    if (!$to) return false;

    // Load mail config (Gmail SMTP settings)
    $configFile = __DIR__ . '/mail_config.php';
    if (!file_exists($configFile)) {
        error_log("Mail config file not found.");
        return false;
    }
    $config = include $configFile;

    $host     = $config['host'] ?? 'smtp.gmail.com';
    $port     = $config['port'] ?? 587;
    $username = $config['username'] ?? 'zainababbasbajwa17@gmail.com';
    $password = $config['password'] ?? 'fpfqjxlewjjjrswo';
    $from     = $config['from_email'] ?? $username;
    $fromName = $config['from_name'] ?? 'ToDo App';

    // Build remote connection string
    $remote = ($port == 465 ? 'ssl://' : 'tcp://') . $host . ':' . $port;

    // SSL context (use cacert.pem for proper verification)
    $context = stream_context_create([
        'ssl' => [
            'verify_peer'       => true,
            'verify_peer_name'  => true,
            'allow_self_signed' => false,
            'cafile'            => __DIR__ . '/cacert.pem'
        ]
    ]);

    $fp = @stream_socket_client($remote, $errno, $errstr, 30, STREAM_CLIENT_CONNECT, $context);
    if (!$fp) {
        error_log("SMTP connection failed: $errstr ($errno)");
        return false;
    }

    $expect = function($fp, $code) {
        $resp = '';
        while (($line = fgets($fp, 512)) !== false) {
            $resp .= $line;
            if (substr($line, 3, 1) == ' ') break;
        }
        return strpos($resp, (string)$code) === 0 ? $resp : false;
    };

    // Initial handshake
    if ($expect($fp, 220) === false) { fclose($fp); return false; }
    fwrite($fp, "EHLO localhost\r\n");
    if ($expect($fp, 250) === false) { fclose($fp); return false; }

    // STARTTLS if using port 587
    if ($port == 587) {
        fwrite($fp, "STARTTLS\r\n");
        if ($expect($fp, 220) === false) { fclose($fp); return false; }
        if (!stream_socket_enable_crypto($fp, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) { fclose($fp); return false; }
        fwrite($fp, "EHLO localhost\r\n");
        if ($expect($fp, 250) === false) { fclose($fp); return false; }
    }

    // AUTH LOGIN
    fwrite($fp, "AUTH LOGIN\r\n");
    if ($expect($fp, 334) === false) { fclose($fp); return false; }
    fwrite($fp, base64_encode($username) . "\r\n");
    if ($expect($fp, 334) === false) { fclose($fp); return false; }
    fwrite($fp, base64_encode($password) . "\r\n");
    if ($expect($fp, 235) === false) { fclose($fp); return false; }

    // MAIL FROM / RCPT TO
    fwrite($fp, "MAIL FROM:<$from>\r\n");
    if ($expect($fp, 250) === false) { fclose($fp); return false; }
    fwrite($fp, "RCPT TO:<$to>\r\n");
    if ($expect($fp, 250) === false) { fclose($fp); return false; }

    // DATA
    fwrite($fp, "DATA\r\n");
    if ($expect($fp, 354) === false) { fclose($fp); return false; }

    $headers  = "From: $fromName <$from>\r\n";
    $headers .= "To: $to\r\n";
    $headers .= "Subject: $subject\r\n";
    $headers .= "Date: " . date('r') . "\r\n";
    $headers .= "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";

    fwrite($fp, $headers . "\r\n" . $body . "\r\n.\r\n");
    if ($expect($fp, 250) === false) { fclose($fp); return false; }

    fwrite($fp, "QUIT\r\n");
    fclose($fp);
    return true;
}
