<?php

declare(strict_types=1);

namespace Rcsofttech\AuditTrailBundle\Tests\Functional;

use Rcsofttech\AuditTrailBundle\Contract\AuditTransportInterface;
use Rcsofttech\AuditTrailBundle\Entity\AuditLog;

class ThrowingTransport implements AuditTransportInterface
{
    public function send(AuditLog $log, array $context = []): void
    {
        throw new \RuntimeException('Transport failed intentionally.');
    }

    public function supports(string $phase): bool
    {
        return true;
    }
}
