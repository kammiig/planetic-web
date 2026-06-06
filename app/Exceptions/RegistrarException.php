<?php

namespace App\Exceptions;

use RuntimeException;
use Throwable;

/**
 * Thrown when a registrar API call fails. Carries a safe, customer-facing
 * message separately from the internal detail (which is logged but never
 * shown to customers — Security & Access §15).
 */
class RegistrarException extends RuntimeException
{
    public function __construct(
        string $message,
        public readonly string $safeMessage = 'We could not check this domain right now. Please try again in a few moments.',
        public readonly ?string $registrar = null,
        public readonly mixed $context = null,
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }
}
