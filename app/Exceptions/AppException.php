<?php

namespace App\Exceptions;

use App\Enums\ResponseCode;
use Exception;

class AppException extends Exception
{
    protected ResponseCode $responseCode;
    protected ?array $errors = null;
    protected int $httpStatusCode;

    public function __construct(
        ResponseCode $responseCode,
        ?string $message = null,
        ?array $errors = null,
        ?\Throwable $previous = null
    ) {
        $this->responseCode = $responseCode;
        $this->errors = $errors;
        $this->httpStatusCode = $responseCode->getHttpStatusCode();

        parent::__construct(
            $message ?? __($responseCode->getMessage()),
            $this->httpStatusCode,
            $previous
        );
    }

    public function getResponseCode(): ResponseCode
    {
        return $this->responseCode;
    }

    public function getErrors(): ?array
    {
        return $this->errors;
    }

    public function getHttpStatusCode(): int
    {
        return $this->httpStatusCode;
    }

    public static function notFound(?string $message = null): self
    {
        return new self(ResponseCode::NOT_FOUND, $message);
    }

    public static function unauthorized(?string $message = null): self
    {
        return new self(ResponseCode::UNAUTHORIZED, $message);
    }

    public static function forbidden(?string $message = null): self
    {
        return new self(ResponseCode::FORBIDDEN, $message);
    }

    public static function validationError(array $errors, ?string $message = null): self
    {
        return new self(ResponseCode::VALIDATION_ERROR, $message, $errors);
    }

    public static function invalidInput(?string $message = null): self
    {
        return new self(ResponseCode::INVALID_INPUT, $message);
    }

    public static function authenticationFailed(?string $message = null): self
    {
        return new self(ResponseCode::AUTHENTICATION_FAILED, $message);
    }

    public static function tokenExpired(?string $message = null): self
    {
        return new self(ResponseCode::TOKEN_EXPIRED, $message);
    }

    public static function insufficientBalance(?string $message = null): self
    {
        return new self(ResponseCode::INSUFFICIENT_BALANCE, $message);
    }

    public static function invalidPaymentMethod(?string $message = null): self
    {
        return new self(ResponseCode::INVALID_PAYMENT_METHOD, $message);
    }

    public static function paymentFailed(?string $message = null): self
    {
        return new self(ResponseCode::PAYMENT_FAILED, $message);
    }

    public static function transactionNotFound(?string $message = null): self
    {
        return new self(ResponseCode::TRANSACTION_NOT_FOUND, $message);
    }

    public static function clientNotFound(?string $message = null): self
    {
        return new self(ResponseCode::CLIENT_NOT_FOUND, $message);
    }

    public static function userNotFound(?string $message = null): self
    {
        return new self(ResponseCode::USER_NOT_FOUND, $message);
    }

    public static function merchantNotFound(?string $message = null): self
    {
        return new self(ResponseCode::MERCHANT_NOT_FOUND, $message);
    }

    public static function duplicateTransaction(?string $message = null): self
    {
        return new self(ResponseCode::DUPLICATE_TRANSACTION, $message);
    }

    public static function invalidAmount(?string $message = null): self
    {
        return new self(ResponseCode::INVALID_AMOUNT, $message);
    }

    public static function clientSuspended(?string $message = null): self
    {
        return new self(ResponseCode::CLIENT_SUSPENDED, $message);
    }

    public static function merchantSuspended(?string $message = null): self
    {
        return new self(ResponseCode::MERCHANT_SUSPENDED, $message);
    }

    public static function kybPending(?string $message = null): self
    {
        return new self(ResponseCode::KYB_PENDING, $message);
    }

    public static function kybRejected(?string $message = null): self
    {
        return new self(ResponseCode::KYB_REJECTED, $message);
    }
}
