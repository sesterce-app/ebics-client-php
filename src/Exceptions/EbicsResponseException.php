<?php

namespace AndrewSvirin\Ebics\Exceptions;

use AndrewSvirin\Ebics\Contracts\EbicsResponseExceptionInterface;
use AndrewSvirin\Ebics\Models\Http\Request;
use AndrewSvirin\Ebics\Models\Http\Response;

abstract class EbicsResponseException extends EbicsException implements EbicsResponseExceptionInterface
{
    private string $responseCode;
    private ?Request $request;
    private ?Response $response;
    private ?string $meaning;

    /**
     * EbicsResponseException constructor.
     *
     * @param string $responseCode
     * @param string|null $responseMessage
     * @param string|null $meaning
     */
    public function __construct(string $responseCode, ?string $responseMessage, ?string $meaning = null)
    {
        $message = $responseMessage ?: $meaning;

        parent::__construct((string)$message, (int)$responseCode);

        $this->responseCode = $responseCode;
        $this->meaning = $meaning;
    }

    /**
     * @inheritDoc
     */
    public function getRequest(): ?Request
    {
        return $this->request;
    }

    /**
     * @inheritDoc
     */
    public function getResponse(): ?Response
    {
        return $this->response;
    }

    /**
     * @inheritDoc
     */
    public function getMeaning(): ?string
    {
        return $this->meaning;
    }

    /**
     * @inheritDoc
     */
    public function getResponseCode(): string
    {
        return $this->responseCode;
    }

    public function setRequest(Request $request): void
    {
        $this->request = $request;
    }

    public function setResponse(Response $response): void
    {
        $this->response = $response;
    }
}
