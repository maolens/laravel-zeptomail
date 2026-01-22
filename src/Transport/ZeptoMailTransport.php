<?php

namespace ZohoMail\LaravelZeptoMail\Transport;

use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Exception\ConnectException as GuzzleConnectException;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\Log;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mailer\Envelope;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\TransportInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\MessageConverter;
use Symfony\Component\Mime\RawMessage;
use ZohoMail\LaravelZeptoMail\Exceptions\ApiException;
use ZohoMail\LaravelZeptoMail\Exceptions\ConfigurationException;
use ZohoMail\LaravelZeptoMail\Exceptions\ConnectionException;

class ZeptoMailTransport implements TransportInterface
{
    protected string $apiKey;
    protected string $endpoint;
    protected int $timeout;
    protected bool $loggingEnabled;
    protected HttpClient $client;
    protected ?LoggerInterface $logger = null;

    /**
     * @throws ConfigurationException
     */
    public function __construct(
        string $apiKey,
        ?string $region = null,
        ?string $customEndpoint = null,
        ?string $apiVersion = null,
        ?int $timeout = null,
        ?bool $loggingEnabled = null,
        ?array $regionDomains = null
    ) {
        $this->validateApiKey($apiKey);

        $this->apiKey = $apiKey;
        $this->endpoint = $this->resolveEndpoint($region, $customEndpoint, $apiVersion, $regionDomains);
        $this->timeout = $timeout ?? 30;
        $this->loggingEnabled = $loggingEnabled ?? false;

        $this->client = new HttpClient([
            'timeout' => $this->timeout,
            'http_errors' => false,
        ]);
    }

    /**
     * Set custom logger instance
     */
    public function setLogger(LoggerInterface $logger): self
    {
        $this->logger = $logger;
        return $this;
    }

    /**
     * Send email message
     *
     * @throws ConnectionException
     * @throws ApiException
     */
    public function send(RawMessage $message, Envelope $envelope = null): ?SentMessage
    {
        $email = MessageConverter::toEmail($message);
        $payload = $this->buildPayload($email, $envelope);

        $this->log('info', 'Sending email via ZeptoMail', [
            'subject' => $email->getSubject(),
            'to' => $this->extractAddresses($email->getTo()),
        ]);

        try {
            $response = $this->client->post($this->endpoint, [
                'headers' => $this->getHeaders(),
                'json' => $payload,
            ]);

            $statusCode = $response->getStatusCode();
            $responseBody = (string) $response->getBody();

            if ($statusCode >= 200 && $statusCode < 300) {
                $this->log('info', 'Email sent successfully', [
                    'status_code' => $statusCode,
                    'response' => $this->parseResponse($responseBody),
                ]);

                return new SentMessage($message, $envelope);
            }

            // API returned error
            $errorData = $this->parseResponse($responseBody);
            $errorMessage = $errorData['message'] ?? $errorData['error'] ?? 'Unknown API error';

            $this->log('error', 'ZeptoMail API error', [
                'status_code' => $statusCode,
                'error' => $errorMessage,
                'response' => $errorData,
            ]);

            throw new ApiException(
                "ZeptoMail API error: {$errorMessage}",
                $statusCode,
                $responseBody
            );
        } catch (GuzzleConnectException $e) {
            $this->log('error', 'Failed to connect to ZeptoMail API', [
                'error' => $e->getMessage(),
                'endpoint' => $this->endpoint,
            ]);

            throw new ConnectionException(
                'Failed to connect to ZeptoMail API: ' . $e->getMessage(),
                0,
                $e
            );
        } catch (RequestException $e) {
            $statusCode = $e->hasResponse() ? $e->getResponse()->getStatusCode() : 0;
            $responseBody = $e->hasResponse() ? (string) $e->getResponse()->getBody() : null;

            $this->log('error', 'ZeptoMail request failed', [
                'status_code' => $statusCode,
                'error' => $e->getMessage(),
                'response' => $responseBody,
            ]);

            throw new ApiException(
                'ZeptoMail request failed: ' . $e->getMessage(),
                $statusCode,
                $responseBody,
                $e
            );
        }
    }

    /**
     * Get transport name
     */
    public function __toString(): string
    {
        return 'zeptomail';
    }

    /**
     * Validate API key
     *
     * @throws ConfigurationException
     */
    protected function validateApiKey(string $apiKey): void
    {
        if (empty($apiKey)) {
            throw new ConfigurationException('ZeptoMail API key is required');
        }

        if (strlen($apiKey) < 10) {
            throw new ConfigurationException('Invalid ZeptoMail API key format');
        }
    }

    /**
     * Resolve endpoint URL from region or custom endpoint
     *
     * @throws ConfigurationException
     */
    protected function resolveEndpoint(
        ?string $region,
        ?string $customEndpoint,
        ?string $apiVersion,
        ?array $regionDomains
    ): string {
        $apiVersion = $apiVersion ?? 'v1.1';

        // Custom endpoint takes precedence
        if ($customEndpoint) {
            return rtrim($customEndpoint, '/') . "/{$apiVersion}/email";
        }

        // Use region mapping
        $region = $region ?? 'us';
        $regionDomains = $regionDomains ?? [
            'us' => 'com',
            'eu' => 'eu',
            'in' => 'in',
            'au' => 'com.au',
            'jp' => 'jp',
            'ca' => 'ca',
            'sa' => 'sa',
            'cn' => 'com.cn',
        ];

        if (!isset($regionDomains[$region])) {
            throw new ConfigurationException("Invalid ZeptoMail region: {$region}");
        }

        $domain = $regionDomains[$region];

        return "https://api.zeptomail.{$domain}/{$apiVersion}/email";
    }

    /**
     * Get request headers
     */
    protected function getHeaders(): array
    {
        return [
            'Authorization' => $this->apiKey,
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
            'User-Agent' => 'Laravel-ZeptoMail/2.0',
        ];
    }

    /**
     * Build API payload from email
     */
    protected function buildPayload(Email $email, ?Envelope $envelope): array
    {
        $recipients = $this->getRecipients($email, $envelope);

        $payload = [
            'from' => $this->formatAddress($email->getFrom()[0] ?? null),
            'to' => $this->filterRecipientsByType($recipients, 'to'),
            'subject' => $email->getSubject(),
        ];

        // Add HTML or text body
        if ($email->getHtmlBody()) {
            $payload['htmlbody'] = $email->getHtmlBody();
        } elseif ($email->getTextBody()) {
            $payload['textbody'] = $email->getTextBody();
        }

        // Add CC recipients
        $cc = $this->filterRecipientsByType($recipients, 'cc');
        if (!empty($cc)) {
            $payload['cc'] = $cc;
        }

        // Add BCC recipients
        $bcc = $this->filterRecipientsByType($recipients, 'bcc');
        if (!empty($bcc)) {
            $payload['bcc'] = $bcc;
        }

        // Add reply-to
        $replyTo = $email->getReplyTo();
        if (!empty($replyTo)) {
            $payload['reply_to'] = $this->formatAddresses($replyTo);
        }

        // Add attachments
        $attachments = $this->buildAttachments($email);
        if (!empty($attachments)) {
            $payload['attachments'] = $attachments;
        }

        return $payload;
    }

    /**
     * Get all recipients with their types
     */
    protected function getRecipients(Email $email, ?Envelope $envelope): array
    {
        $recipients = [];
        $envelopeRecipients = $envelope ? $envelope->getRecipients() : [];

        foreach ($envelopeRecipients as $recipient) {
            $type = 'to';

            if (in_array($recipient, $email->getBcc(), true)) {
                $type = 'bcc';
            } elseif (in_array($recipient, $email->getCc(), true)) {
                $type = 'cc';
            }

            $recipients[] = [
                'address' => $recipient,
                'type' => $type,
            ];
        }

        // Fallback to email recipients if envelope is empty
        if (empty($recipients)) {
            foreach ($email->getTo() as $address) {
                $recipients[] = ['address' => $address, 'type' => 'to'];
            }
            foreach ($email->getCc() as $address) {
                $recipients[] = ['address' => $address, 'type' => 'cc'];
            }
            foreach ($email->getBcc() as $address) {
                $recipients[] = ['address' => $address, 'type' => 'bcc'];
            }
        }

        return $recipients;
    }

    /**
     * Filter recipients by type and format for API
     */
    protected function filterRecipientsByType(array $recipients, string $type): array
    {
        $filtered = array_filter($recipients, fn($r) => $r['type'] === $type);

        return array_map(function ($recipient) {
            return [
                'email_address' => $this->formatAddress($recipient['address']),
            ];
        }, array_values($filtered));
    }

    /**
     * Format single address for API
     */
    protected function formatAddress(?Address $address): array
    {
        if (!$address) {
            return ['address' => '', 'name' => ''];
        }

        $formatted = ['address' => $address->getAddress()];

        if ($name = $address->getName()) {
            $formatted['name'] = $name;
        }

        return $formatted;
    }

    /**
     * Format multiple addresses for API
     */
    protected function formatAddresses(array $addresses): array
    {
        return array_map(fn($addr) => $this->formatAddress($addr), $addresses);
    }

    /**
     * Build attachments array for API
     */
    protected function buildAttachments(Email $email): array
    {
        $attachments = [];

        foreach ($email->getAttachments() as $attachment) {
            $headers = $attachment->getPreparedHeaders();
            $filename = $headers->getHeaderParameter('Content-Disposition', 'filename');
            $contentType = $headers->get('Content-Type')?->getBody() ?? 'application/octet-stream';

            $attachments[] = [
                'content' => base64_encode($attachment->getBody()),
                'name' => $filename ?? 'attachment',
                'mime_type' => $contentType,
            ];
        }

        return $attachments;
    }

    /**
     * Extract email addresses from Address array
     */
    protected function extractAddresses(array $addresses): array
    {
        return array_map(fn(Address $addr) => $addr->getAddress(), $addresses);
    }

    /**
     * Parse API response
     */
    protected function parseResponse(string $responseBody): array
    {
        $decoded = json_decode($responseBody, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return ['raw' => $responseBody];
        }

        return $decoded ?? [];
    }

    /**
     * Log message if logging is enabled
     */
    protected function log(string $level, string $message, array $context = []): void
    {
        if (!$this->loggingEnabled) {
            return;
        }

        if ($this->logger) {
            $this->logger->log($level, "[ZeptoMail] {$message}", $context);
        } elseif (class_exists(Log::class)) {
            Log::log($level, "[ZeptoMail] {$message}", $context);
        }
    }
}
