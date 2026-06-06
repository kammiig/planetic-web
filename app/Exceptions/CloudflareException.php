<?php

namespace App\Exceptions;

use RuntimeException;
use Throwable;

/**
 * Thrown when a Cloudflare API call fails. `zoneExists` lets callers reuse an
 * already-present zone instead of treating it as a hard error.
 */
class CloudflareException extends RuntimeException
{
    public function __construct(
        string $message,
        public readonly bool $zoneExists = false,
        public readonly mixed $context = null,
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }
}
