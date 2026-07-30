<?php

namespace App\Exceptions;

use Exception;

class GroqUnavailableException extends Exception
{
    public static function requestFailed(int $status): self
    {
        return new self("Groq models endpoint request failed with status {$status}.");
    }
}
