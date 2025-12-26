<?php

namespace Rcsofttech\AuditTrailBundle\Tests\Unit\Transport;

use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Rcsofttech\AuditTrailBundle\Transport\HttpAuditTransport;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class HttpAuditTransportIssueTest extends TestCase
{
    public function testSupportsMethod(): void
    {
        $client = $this->createStub(HttpClientInterface::class);
        $logger = $this->createStub(LoggerInterface::class);
        $transport = new HttpAuditTransport($client, 'http://example.com', $logger);

        $this->assertFalse($transport->supports('on_flush'), 'Should not support on_flush');
        $this->assertTrue($transport->supports('post_flush'), 'Should support post_flush');
    }
}
