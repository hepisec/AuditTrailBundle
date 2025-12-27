<?php

declare(strict_types=1);

namespace Rcsofttech\AuditTrailBundle\Tests\Query;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Rcsofttech\AuditTrailBundle\Entity\AuditLog;
use Rcsofttech\AuditTrailBundle\Query\AuditQuery;
use Rcsofttech\AuditTrailBundle\Repository\AuditLogRepository;

#[CoversClass(AuditQuery::class)]
class AuditQueryTest extends TestCase
{
    private AuditLogRepository&MockObject $repository;

    protected function setUp(): void
    {
        $this->repository = $this->createMock(AuditLogRepository::class);
    }

    public function testQueryIsImmutable(): void
    {
        $query1 = new AuditQuery($this->repository);
        $query2 = $query1->entity('App\\Entity\\User');
        $query3 = $query2->action(AuditLog::ACTION_CREATE);

        $this->assertNotSame($query1, $query2);
        $this->assertNotSame($query2, $query3);
    }

    public function testEntityFilter(): void
    {
        $this->repository
            ->expects($this->once())
            ->method('findWithFilters')
            ->with(
                $this->callback(function (array $filters) {
                    return 'App\\Entity\\User' === $filters['entityClass']
                        && '123' === $filters['entityId'];
                }),
                $this->anything()
            )
            ->willReturn([]);

        $query = new AuditQuery($this->repository);
        $query->entity('App\\Entity\\User', '123')->getResults();
    }

    public function testActionFilter(): void
    {
        $this->repository
            ->expects($this->once())
            ->method('findWithFilters')
            ->with(
                $this->callback(function (array $filters) {
                    return AuditLog::ACTION_UPDATE === $filters['action'];
                }),
                $this->anything()
            )
            ->willReturn([]);

        $query = new AuditQuery($this->repository);
        $query->action(AuditLog::ACTION_UPDATE)->getResults();
    }

    public function testUserFilter(): void
    {
        $this->repository
            ->expects($this->once())
            ->method('findWithFilters')
            ->with(
                $this->callback(function (array $filters) {
                    return 42 === $filters['userId'];
                }),
                $this->anything()
            )
            ->willReturn([]);

        $query = new AuditQuery($this->repository);
        $query->user(42)->getResults();
    }

    public function testTransactionFilter(): void
    {
        $this->repository
            ->expects($this->once())
            ->method('findWithFilters')
            ->with(
                $this->callback(function (array $filters) {
                    return 'abc123' === $filters['transactionHash'];
                }),
                $this->anything()
            )
            ->willReturn([]);

        $query = new AuditQuery($this->repository);
        $query->transaction('abc123')->getResults();
    }

    public function testDateFilters(): void
    {
        $from = new \DateTimeImmutable('2024-01-01');
        $to = new \DateTimeImmutable('2024-12-31');

        $this->repository
            ->expects($this->once())
            ->method('findWithFilters')
            ->with(
                $this->callback(function (array $filters) use ($from, $to) {
                    return $filters['from'] == $from && $filters['to'] == $to;
                }),
                $this->anything()
            )
            ->willReturn([]);

        $query = new AuditQuery($this->repository);
        $query->since($from)->until($to)->getResults();
    }

    public function testBetweenHelper(): void
    {
        $from = new \DateTimeImmutable('2024-01-01');
        $to = new \DateTimeImmutable('2024-12-31');

        $this->repository
            ->expects($this->once())
            ->method('findWithFilters')
            ->with(
                $this->callback(function (array $filters) use ($from, $to) {
                    return $filters['from'] == $from && $filters['to'] == $to;
                }),
                $this->anything()
            )
            ->willReturn([]);

        $query = new AuditQuery($this->repository);
        $query->between($from, $to)->getResults();
    }

    public function testLimitFilter(): void
    {
        $this->repository
            ->expects($this->once())
            ->method('findWithFilters')
            ->with(
                $this->anything(),
                50
            )
            ->willReturn([]);

        $query = new AuditQuery($this->repository);
        $query->limit(50)->getResults();
    }

    public function testKeysetPaginationAfter(): void
    {
        $this->repository
            ->expects($this->once())
            ->method('findWithFilters')
            ->with(
                $this->callback(function (array $filters) {
                    return 100 === $filters['afterId'];
                }),
                $this->anything()
            )
            ->willReturn([]);

        $query = new AuditQuery($this->repository);
        $query->after(100)->getResults();
    }

    public function testKeysetPaginationBefore(): void
    {
        $this->repository
            ->expects($this->once())
            ->method('findWithFilters')
            ->with(
                $this->callback(function (array $filters) {
                    return 50 === $filters['beforeId'];
                }),
                $this->anything()
            )
            ->willReturn([]);

        $query = new AuditQuery($this->repository);
        $query->before(50)->getResults();
    }

    public function testCreatesHelper(): void
    {
        $this->repository
            ->expects($this->once())
            ->method('findWithFilters')
            ->with(
                $this->callback(function (array $filters) {
                    return AuditLog::ACTION_CREATE === $filters['action'];
                }),
                $this->anything()
            )
            ->willReturn([]);

        $query = new AuditQuery($this->repository);
        $query->creates()->getResults();
    }

    public function testUpdatesHelper(): void
    {
        $this->repository
            ->expects($this->once())
            ->method('findWithFilters')
            ->with(
                $this->callback(function (array $filters) {
                    return AuditLog::ACTION_UPDATE === $filters['action'];
                }),
                $this->anything()
            )
            ->willReturn([]);

        $query = new AuditQuery($this->repository);
        $query->updates()->getResults();
    }

    public function testGetResultsReturnsCollection(): void
    {
        $log = new AuditLog();
        $log->setEntityClass('App\\Entity\\User');
        $log->setEntityId('1');
        $log->setAction(AuditLog::ACTION_CREATE);

        $this->repository
            ->expects($this->once())
            ->method('findWithFilters')
            ->willReturn([$log]);

        $query = new AuditQuery($this->repository);
        $results = $query->getResults();

        $this->assertCount(1, $results);
        $first = $results->first();
        $this->assertNotNull($first);
        $this->assertTrue($first->isCreate());
    }

    public function testGetFirstResult(): void
    {
        $log = new AuditLog();
        $log->setEntityClass('App\\Entity\\User');
        $log->setEntityId('1');
        $log->setAction(AuditLog::ACTION_CREATE);

        $this->repository
            ->expects($this->once())
            ->method('findWithFilters')
            ->with($this->anything(), 1)
            ->willReturn([$log]);

        $query = new AuditQuery($this->repository);
        $result = $query->getFirstResult();

        $this->assertNotNull($result);
        $this->assertTrue($result->isCreate());
    }

    public function testGetFirstResultReturnsNullWhenEmpty(): void
    {
        $this->repository
            ->expects($this->once())
            ->method('findWithFilters')
            ->willReturn([]);

        $query = new AuditQuery($this->repository);
        $result = $query->getFirstResult();

        $this->assertNull($result);
    }

    public function testExists(): void
    {
        $log = new AuditLog();
        $log->setEntityClass('App\\Entity\\User');
        $log->setEntityId('1');
        $log->setAction(AuditLog::ACTION_CREATE);

        $this->repository
            ->expects($this->once())
            ->method('findWithFilters')
            ->willReturn([$log]);

        $query = new AuditQuery($this->repository);

        $this->assertTrue($query->exists());
    }

    public function testExistsReturnsFalseWhenEmpty(): void
    {
        $this->repository
            ->expects($this->once())
            ->method('findWithFilters')
            ->willReturn([]);

        $query = new AuditQuery($this->repository);

        $this->assertFalse($query->exists());
    }

    public function testChangedFieldFilter(): void
    {
        $log1 = new AuditLog();
        $log1->setEntityClass('App\\Entity\\User');
        $log1->setEntityId('1');
        $log1->setAction(AuditLog::ACTION_UPDATE);
        $log1->setChangedFields(['name', 'email']);

        $log2 = new AuditLog();
        $log2->setEntityClass('App\\Entity\\User');
        $log2->setEntityId('2');
        $log2->setAction(AuditLog::ACTION_UPDATE);
        $log2->setChangedFields(['password']);

        $this->repository
            ->expects($this->once())
            ->method('findWithFilters')
            ->willReturn([$log1, $log2]);

        $query = new AuditQuery($this->repository);
        $results = $query->changedField('email')->getResults();

        // Only log1 has 'email' in changed fields
        $this->assertCount(1, $results);
        $first = $results->first();
        $this->assertNotNull($first);
        $this->assertSame('1', $first->getEntityId());
    }

    public function testChainedFilters(): void
    {
        $this->repository
            ->expects($this->once())
            ->method('findWithFilters')
            ->with(
                $this->callback(function (array $filters) {
                    return 'App\\Entity\\User' === $filters['entityClass']
                        && AuditLog::ACTION_UPDATE === $filters['action']
                        && 42 === $filters['userId'];
                }),
                50
            )
            ->willReturn([]);

        $query = new AuditQuery($this->repository);
        $query
            ->entity('App\\Entity\\User')
            ->updates()
            ->user(42)
            ->limit(50)
            ->getResults();
    }

    public function testGetNextCursor(): void
    {
        $log = new AuditLog();
        $log->setEntityClass('App\\Entity\\User');
        $log->setEntityId('1');
        $log->setAction(AuditLog::ACTION_CREATE);

        // Use reflection to set the ID
        $reflection = new \ReflectionClass($log);
        $idProperty = $reflection->getProperty('id');
        $idProperty->setValue($log, 42);

        $this->repository
            ->expects($this->once())
            ->method('findWithFilters')
            ->willReturn([$log]);

        $query = new AuditQuery($this->repository);
        $cursor = $query->getNextCursor();

        $this->assertSame(42, $cursor);
    }

    public function testGetNextCursorReturnsNullWhenEmpty(): void
    {
        $this->repository
            ->expects($this->once())
            ->method('findWithFilters')
            ->willReturn([]);

        $query = new AuditQuery($this->repository);
        $cursor = $query->getNextCursor();

        $this->assertNull($cursor);
    }
}
