<?php

namespace ZohoMail\LaravelZeptoMail\Exceptions;

class ApiException extends ZeptoMailException
{
    protected int $httpStatusCode;
    protected ?string $responseBody;

    public function __construct(
        string $message = '',
        int $httpStatusCode = 0,
        ?string $responseBody = null,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, $httpStatusCode, $previous, [
            'http_status' => $httpStatusCode,
            'response_body' => $responseBody,
        ]);

        $this->httpStatusCode = $httpStatusCode;
        $this->responseBody = $responseBody;
    }

    public function getHttpStatusCode(): int
    {
        return $this->httpStatusCode;
    }

    public function getResponseBody(): ?string
    {
        return $this->responseBody;
    }
}
