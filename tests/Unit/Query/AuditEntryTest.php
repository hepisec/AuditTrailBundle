<?php

declare(strict_types=1);

namespace Rcsofttech\AuditTrailBundle\Tests\Unit\Query;

use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Rcsofttech\AuditTrailBundle\Entity\AuditLog;
use Rcsofttech\AuditTrailBundle\Query\AuditEntry;

#[CoversClass(AuditEntry::class)]
#[AllowMockObjectsWithoutExpectations()]
class AuditEntryTest extends TestCase
{
    public function testGettersReturnAuditLogValues(): void
    {
        $log = $this->createAuditLog();
        $entry = new AuditEntry($log);

        $this->assertSame(1, $entry->getId());
        $this->assertSame('App\\Entity\\User', $entry->getEntityClass());
        $this->assertSame('User', $entry->getEntityShortName());
        $this->assertSame('123', $entry->getEntityId());
        $this->assertSame(AuditLog::ACTION_UPDATE, $entry->getAction());
        $this->assertSame(42, $entry->getUserId());
        $this->assertSame('admin', $entry->getUsername());
        $this->assertSame('127.0.0.1', $entry->getIpAddress());
        $this->assertSame('abc123', $entry->getTransactionHash());
    }

    public function testActionHelpers(): void
    {
        $createLog = $this->createAuditLog(AuditLog::ACTION_CREATE);
        $updateLog = $this->createAuditLog(AuditLog::ACTION_UPDATE);
        $deleteLog = $this->createAuditLog(AuditLog::ACTION_DELETE);
        $softDeleteLog = $this->createAuditLog(AuditLog::ACTION_SOFT_DELETE);
        $restoreLog = $this->createAuditLog(AuditLog::ACTION_RESTORE);

        $this->assertTrue((new AuditEntry($createLog))->isCreate());
        $this->assertFalse((new AuditEntry($createLog))->isUpdate());

        $this->assertTrue((new AuditEntry($updateLog))->isUpdate());
        $this->assertFalse((new AuditEntry($updateLog))->isDelete());

        $this->assertTrue((new AuditEntry($deleteLog))->isDelete());
        $this->assertTrue((new AuditEntry($softDeleteLog))->isSoftDelete());
        $this->assertTrue((new AuditEntry($restoreLog))->isRestore());
    }

    public function testGetDiffReturnsOldAndNewValues(): void
    {
        $log = $this->createAuditLog();
        $entry = new AuditEntry($log);

        $diff = $entry->getDiff();

        $this->assertArrayHasKey('name', $diff);
        $this->assertSame('John', $diff['name']['old']);
        $this->assertSame('Jane', $diff['name']['new']);

        $this->assertArrayHasKey('email', $diff);
        $this->assertSame('john@example.com', $diff['email']['old']);
        $this->assertSame('jane@example.com', $diff['email']['new']);
    }

    public function testGetChangedFields(): void
    {
        $log = $this->createAuditLog();
        $entry = new AuditEntry($log);

        $changedFields = $entry->getChangedFields();

        $this->assertContains('name', $changedFields);
        $this->assertContains('email', $changedFields);
    }

    public function testHasFieldChanged(): void
    {
        $log = $this->createAuditLog();
        $entry = new AuditEntry($log);

        $this->assertTrue($entry->hasFieldChanged('name'));
        $this->assertTrue($entry->hasFieldChanged('email'));
        $this->assertFalse($entry->hasFieldChanged('password'));
    }

    public function testGetOldAndNewValue(): void
    {
        $log = $this->createAuditLog();
        $entry = new AuditEntry($log);

        $this->assertSame('John', $entry->getOldValue('name'));
        $this->assertSame('Jane', $entry->getNewValue('name'));
        $this->assertNull($entry->getOldValue('nonexistent'));
        $this->assertNull($entry->getNewValue('nonexistent'));
    }

    public function testGetAuditLogReturnsUnderlyingEntity(): void
    {
        $log = $this->createAuditLog();
        $entry = new AuditEntry($log);

        $this->assertSame($log, $entry->getAuditLog());
    }

    public function testGetEntityShortNameWithSimpleClass(): void
    {
        $log = new AuditLog();
        $log->setEntityClass('User');
        $log->setEntityId('1');
        $log->setAction(AuditLog::ACTION_CREATE);

        $entry = new AuditEntry($log);

        $this->assertSame('User', $entry->getEntityShortName());
    }

    private function createAuditLog(string $action = AuditLog::ACTION_UPDATE): AuditLog
    {
        $log = new AuditLog();
        $log->setEntityClass('App\\Entity\\User');
        $log->setEntityId('123');
        $log->setAction($action);
        $log->setUserId(42);
        $log->setUsername('admin');
        $log->setIpAddress('127.0.0.1');
        $log->setTransactionHash('abc123');
        $log->setOldValues(['name' => 'John', 'email' => 'john@example.com']);
        $log->setNewValues(['name' => 'Jane', 'email' => 'jane@example.com']);
        $log->setChangedFields(['name', 'email']);

        // Use reflection to set the ID
        $reflection = new \ReflectionClass($log);
        $idProperty = $reflection->getProperty('id');
        $idProperty->setValue($log, 1);

        return $log;
    }
}
