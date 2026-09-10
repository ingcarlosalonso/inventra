<?php

namespace App\Exceptions;

use Exception;

class MercadoPagoException extends Exception
{
    public static function requestFailed(string $operation, int $status, string $body): self
    {
        return new self(__('mercadopago.error_request_failed', [
            'operation' => $operation,
            'status' => $status,
            'body' => $body,
        ]));
    }

    public static function notConnected(): self
    {
        return new self(__('mercadopago.error_not_connected'));
    }

    public static function amountExceedsPendingBalance(): self
    {
        return new self(__('mercadopago.error_amount_exceeds_pending_balance'));
    }

    public static function terminalNotConfigured(): self
    {
        return new self(__('mercadopago.error_terminal_not_configured'));
    }

    public static function paymentMethodNotConfigured(): self
    {
        return new self(__('mercadopago.error_payment_method_not_configured'));
    }

    public static function invalidWebhookSignature(): self
    {
        return new self(__('mercadopago.error_invalid_webhook_signature'));
    }
}
