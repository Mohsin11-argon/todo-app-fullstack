<?php
function smtp_send_mail($to, $subject, $message) {
    $smtpServer = "smtp.gmail.com";
    $port = 587;
    $username = "zainababbasbajwa17@gmail.com";        // YOUR GMAIL
    $password = "fpfqjxlewjjjrswo";          // 16-char App Password
    $from = $username;

    $socket = fsockopen($smtpServer, $port, $errno, $errstr, 30);
    if (!$socket) {
        error_log("SMTP Connect failed: $errstr ($errno)");
        return false;
    }

    function smtp_cmd($socket, $cmd) {
        fwrite($socket, $cmd . "\r\n");
        return fgets($socket, 512);
    }

    fgets($socket, 512); // Server greeting
    smtp_cmd($socket, "EHLO localhost");
    smtp_cmd($socket, "STARTTLS");

    stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);

    smtp_cmd($socket, "EHLO localhost");
    smtp_cmd($socket, "AUTH LOGIN");
    smtp_cmd($socket, base64_encode($username));
    smtp_cmd($socket, base64_encode($password));

    smtp_cmd($socket, "MAIL FROM:<$from>");
    smtp_cmd($socket, "RCPT TO:<$to>");
    smtp_cmd($socket, "DATA");

    $headers  = "From: ToDo App <$from>\r\n";
    $headers .= "To: <$to>\r\n";
    $headers .= "Subject: $subject\r\n";
    $headers .= "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/plain; charset=UTF-8\r\n\r\n";

    fwrite($socket, $headers . $message . "\r\n.\r\n");
    smtp_cmd($socket, "QUIT");

    fclose($socket);
    return true;
}
