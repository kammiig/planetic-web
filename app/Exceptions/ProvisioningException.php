<?php

namespace App\Exceptions;

use RuntimeException;
use Throwable;

/**
 * Thrown when a provisioning step cannot complete. `manualReview` marks the
 * step for human review rather than automatic retry (e.g. duplicate risk).
 */
class ProvisioningException extends RuntimeException
{
    public function __construct(
        string $message,
        public readonly bool $manualReview = false,
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }
}
