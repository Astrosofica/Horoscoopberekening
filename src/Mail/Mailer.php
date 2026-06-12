<?php

namespace Astro\Mail;

class Mailer
{
    private string $host;
    private int $port;
    private string $username;
    private string $password;
    private string $fromEmail;
    private string $fromName;
    private int $timeout = 30;
    private $socket = null;
    private string $lastError = '';

    public function __construct(
        ?string $host = null,
        ?int $port = null,
        ?string $username = null,
        ?string $password = null,
        ?string $fromEmail = null,
        ?string $fromName = null
    ) {
        $this->host = $host ?? $_ENV['SMTP_HOST'] ?? 'localhost';
        $this->port = $port ?? (int)($_ENV['SMTP_PORT'] ?? 587);
        $this->username = $username ?? $_ENV['SMTP_USER'] ?? '';
        $this->password = $password ?? $_ENV['SMTP_PASS'] ?? '';
        $this->fromEmail = $fromEmail ?? $_ENV['SMTP_FROM_EMAIL'] ?? 'noreply@localhost';
        $this->fromName = $fromName ?? $_ENV['SMTP_FROM_NAME'] ?? 'Tijd';
    }

    public function send(string $to, string $subject, string $body, bool $isHtml = true): bool
    {
        $this->lastError = '';

        if (!$this->connect()) {
            return false;
        }

        if (!$this->startTls()) {
            $this->disconnect();
            return false;
        }

        if (!$this->authenticate()) {
            $this->disconnect();
            return false;
        }

        if (!$this->sendMail($to, $subject, $body, $isHtml)) {
            $this->disconnect();
            return false;
        }

        $this->disconnect();
        return true;
    }

    public function getLastError(): string
    {
        return $this->lastError;
    }

    private function connect(): bool
    {
        $this->socket = @fsockopen($this->host, $this->port, $errno, $errstr, $this->timeout);

        if (!$this->socket) {
            $this->lastError = "Connection failed: {$errstr} ({$errno})";
            return false;
        }

        $response = $this->read();
        if (!$this->isSuccess($response, '220')) {
            $this->lastError = "Server not ready: {$response}";
            return false;
        }

        $hostname = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'localhost';
        $response = $this->command("EHLO {$hostname}");
        
        if (!$this->isSuccess($response, '250')) {
            $this->lastError = "EHLO failed: {$response}";
            return false;
        }

        return true;
    }

    private function startTls(): bool
    {
        $response = $this->command("STARTTLS");
        
        if (!$this->isSuccess($response, '220')) {
            $this->lastError = "STARTTLS failed: {$response}";
            return false;
        }

        if (!@stream_socket_enable_crypto($this->socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
            $this->lastError = "TLS negotiation failed";
            return false;
        }

        $hostname = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'localhost';
        $response = $this->command("EHLO {$hostname}");
        
        if (!$this->isSuccess($response, '250')) {
            $this->lastError = "EHLO after TLS failed: {$response}";
            return false;
        }

        return true;
    }

    private function authenticate(): bool
    {
        $response = $this->command("AUTH LOGIN");
        
        if (!$this->isSuccess($response, '334')) {
            $this->lastError = "AUTH LOGIN failed: {$response}";
            return false;
        }

        $response = $this->command(base64_encode($this->username));
        
        if (!$this->isSuccess($response, '334')) {
            $this->lastError = "Username rejected: {$response}";
            return false;
        }

        $response = $this->command(base64_encode($this->password));
        
        if (!$this->isSuccess($response, '235')) {
            $this->lastError = "Authentication failed: {$response}";
            return false;
        }

        return true;
    }

    private function sendMail(string $to, string $subject, string $body, bool $isHtml): bool
    {
        $response = $this->command("MAIL FROM:<{$this->fromEmail}>");
        
        if (!$this->isSuccess($response, '250')) {
            $this->lastError = "MAIL FROM failed: {$response}";
            return false;
        }

        $response = $this->command("RCPT TO:<{$to}>");
        
        if (!$this->isSuccess($response, '250')) {
            $this->lastError = "RCPT TO failed: {$response}";
            return false;
        }

        $response = $this->command("DATA");
        
        if (!$this->isSuccess($response, '354')) {
            $this->lastError = "DATA failed: {$response}";
            return false;
        }

        $headers = $this->buildHeaders($to, $subject, $isHtml);
        $encodedBody = $this->encodeBody($body);
        $message = $headers . "\r\n\r\n" . $encodedBody . "\r\n.";

        $response = $this->command($message);
        
        if (!$this->isSuccess($response, '250')) {
            $this->lastError = "Message sending failed: {$response}";
            return false;
        }

        return true;
    }

    private function buildHeaders(string $to, string $subject, bool $isHtml): string
    {
        $headers = [
            "From: {$this->fromName} <{$this->fromEmail}>",
            "To: {$to}",
            "Subject: {$subject}",
            "MIME-Version: 1.0",
            "Content-Type: " . ($isHtml ? "text/html; charset=UTF-8" : "text/plain; charset=UTF-8"),
            "Content-Transfer-Encoding: base64",
        ];

        return implode("\r\n", $headers);
    }

    private function encodeBody(string $body): string
    {
        return chunk_split(base64_encode($body));
    }

    private function command(string $cmd): string
    {
        fwrite($this->socket, $cmd . "\r\n");
        return $this->read();
    }

    private function read(): string
    {
        $response = '';
        while ($line = fgets($this->socket, 512)) {
            $response .= $line;
            if (substr($line, 3, 1) === ' ') {
                break;
            }
        }
        return trim($response);
    }

    private function isSuccess(string $response, string $code): bool
    {
        return strpos($response, $code) === 0;
    }

    private function disconnect(): void
    {
        if ($this->socket) {
            $this->command("QUIT");
            fclose($this->socket);
            $this->socket = null;
        }
    }

    public function __destruct()
    {
        $this->disconnect();
    }
}