<?php

namespace App\Exceptions;

use RuntimeException;
use Throwable;

/**
 * Thrown when a WHM/cPanel API call fails. `manualReview` signals that the
 * failure has duplicate-account risk and must NOT be blindly retried.
 */
class WhmException extends RuntimeException
{
    public function __construct(
        string $message,
        public readonly bool $manualReview = false,
        public readonly mixed $context = null,
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }
}
