<?php

declare(strict_types=1);

namespace App\Security;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\RateLimiter\RateLimiterFactoryInterface;

/**
 * Limits the number of public form submissions per IP address.
 */
final readonly class PublicFormThrottle
{
    public function __construct(private RateLimiterFactoryInterface $publicFormLimiter)
    {
    }

    public function allows(Request $request): bool
    {
        return $this->publicFormLimiter->create($request->getClientIp() ?? 'unknown')->consume()->isAccepted();
    }
}
