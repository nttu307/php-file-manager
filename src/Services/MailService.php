<?php

namespace Src\Services;

use RuntimeException;

class MailService
{
    private const CRLF = "\r\n";

    public static function sendPasswordReset(string $to, string $name, string $resetUrl): bool
    {
        $subject = 'Reset your password';
        $message = "Hello {$name},\n\n"
            . "We received a request to reset your password.\n\n"
            . "Open this link to create a new password:\n{$resetUrl}\n\n"
            . "If you did not request this, you can ignore this email.";

        return self::send($to, $subject, $message);
    }

    public static function send(string $to, string $subject, string $message): bool
    {
        global $config;

        $mail = $config['mail'];
        if (empty($mail['host'])) {
            throw new RuntimeException('MAIL_HOST is not configured.');
        }

        $fromEmail = self::sanitizeEmail($mail['from_email']);
        $fromName = self::sanitizeHeader($mail['from_name']);
        $to = self::sanitizeEmail($to);
        $subject = self::sanitizeHeader($subject);

        $headers = [
            'From: ' . self::formatAddress($fromEmail, $fromName),
            'To: ' . $to,
            'Subject: ' . $subject,
            'MIME-Version: 1.0',
            'Content-Type: text/plain; charset=UTF-8',
            'Content-Transfer-Encoding: 8bit',
            'Date: ' . date(DATE_RFC2822),
        ];

        $data = implode(self::CRLF, $headers)
            . self::CRLF . self::CRLF
            . self::normalizeBody($message);

        self::sendSmtp($mail, $fromEmail, $to, $data);
        return true;
    }

    private static function sendSmtp(array $mail, string $fromEmail, string $to, string $data): void
    {
        $host = $mail['host'];
        $port = (int) ($mail['port'] ?? 587);
        $encryption = strtolower((string) ($mail['encryption'] ?? 'tls'));
        $remote = $encryption === 'ssl' ? "ssl://{$host}:{$port}" : "tcp://{$host}:{$port}";
        $socket = @stream_socket_client($remote, $errorCode, $errorMessage, 20, STREAM_CLIENT_CONNECT);

        if (!$socket) {
            throw new RuntimeException("Could not connect to SMTP server: {$errorMessage} ({$errorCode}).");
        }

        stream_set_timeout($socket, 20);

        try {
            self::expect($socket, 220);
            self::command($socket, 'EHLO localhost', 250);

            if ($encryption === 'tls') {
                self::command($socket, 'STARTTLS', 220);
                if (!stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
                    throw new RuntimeException('Could not start TLS encryption for SMTP connection.');
                }
                self::command($socket, 'EHLO localhost', 250);
            }

            if (!empty($mail['username'])) {
                self::command($socket, 'AUTH LOGIN', 334);
                self::command($socket, base64_encode((string) $mail['username']), 334);
                self::command($socket, base64_encode((string) $mail['password']), 235);
            }

            self::command($socket, 'MAIL FROM:<' . $fromEmail . '>', 250);
            self::command($socket, 'RCPT TO:<' . $to . '>', [250, 251]);
            self::command($socket, 'DATA', 354);
            self::write($socket, self::dotStuff($data) . self::CRLF . '.');
            self::expect($socket, 250);
            self::command($socket, 'QUIT', 221);
        } finally {
            fclose($socket);
        }
    }

    private static function command($socket, string $command, int|array $expected): string
    {
        self::write($socket, $command);
        return self::expect($socket, $expected);
    }

    private static function write($socket, string $line): void
    {
        fwrite($socket, $line . self::CRLF);
    }

    private static function expect($socket, int|array $expected): string
    {
        $expectedCodes = (array) $expected;
        $response = '';

        do {
            $line = fgets($socket, 515);
            if ($line === false) {
                throw new RuntimeException('SMTP server did not respond.');
            }
            $response .= $line;
        } while (isset($line[3]) && $line[3] === '-');

        $code = (int) substr($response, 0, 3);
        if (!in_array($code, $expectedCodes, true)) {
            throw new RuntimeException('Unexpected SMTP response: ' . trim($response));
        }

        return $response;
    }

    private static function normalizeBody(string $message): string
    {
        return str_replace(["\r\n", "\r", "\n"], self::CRLF, $message);
    }

    private static function dotStuff(string $data): string
    {
        return preg_replace('/^\./m', '..', $data) ?? $data;
    }

    private static function formatAddress(string $email, string $name): string
    {
        return $name === '' ? $email : '"' . addcslashes($name, '"\\') . '" <' . $email . '>';
    }

    private static function sanitizeHeader(string $value): string
    {
        return trim(str_replace(["\r", "\n"], '', $value));
    }

    private static function sanitizeEmail(string $email): string
    {
        $email = self::sanitizeHeader($email);
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new RuntimeException('Invalid email address.');
        }

        return $email;
    }
}
