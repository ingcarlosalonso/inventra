<?php

namespace App\Enums;

enum MercadoPagoOrderStatus: string
{
    case Created = 'created';
    case AtTerminal = 'at_terminal';
    case Processing = 'processing';
    case Processed = 'processed';
    case Failed = 'failed';
    case Expired = 'expired';
    case Canceled = 'canceled';
    case Refunded = 'refunded';

    public function isFinal(): bool
    {
        return match ($this) {
            self::Processed, self::Failed, self::Expired, self::Canceled, self::Refunded => true,
            default => false,
        };
    }

    public function isSuccessful(): bool
    {
        return $this === self::Processed;
    }
}
