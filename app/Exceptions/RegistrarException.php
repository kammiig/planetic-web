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
    /**
     * @param  bool  $fallbackEligible  True when the *provider* cannot service this
     *                                  request at all (e.g. Cloudflare's API beta
     *                                  does not yet support the TLD) rather than
     *                                  the request being invalid. FallbackRegistrar
     *                                  retries these on the secondary registrar;
     *                                  everything else fails fast.
     */
    public function __construct(
        string $message,
        public readonly string $safeMessage = 'We could not check this domain right now. Please try again in a few moments.',
        public readonly ?string $registrar = null,
        public readonly mixed $context = null,
        public readonly bool $fallbackEligible = false,
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }
}
