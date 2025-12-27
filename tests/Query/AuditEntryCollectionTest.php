<?php

declare(strict_types=1);

namespace Rcsofttech\AuditTrailBundle\Tests\Query;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Rcsofttech\AuditTrailBundle\Entity\AuditLog;
use Rcsofttech\AuditTrailBundle\Query\AuditEntry;
use Rcsofttech\AuditTrailBundle\Query\AuditEntryCollection;

#[CoversClass(AuditEntryCollection::class)]
class AuditEntryCollectionTest extends TestCase
{
    public function testCountReturnsNumberOfEntries(): void
    {
        $collection = new AuditEntryCollection([
            $this->createEntry(AuditLog::ACTION_CREATE),
            $this->createEntry(AuditLog::ACTION_UPDATE),
        ]);

        $this->assertCount(2, $collection);
    }

    public function testIsEmpty(): void
    {
        $emptyCollection = new AuditEntryCollection([]);
        $nonEmptyCollection = new AuditEntryCollection([
            $this->createEntry(AuditLog::ACTION_CREATE),
        ]);

        $this->assertTrue($emptyCollection->isEmpty());
        $this->assertFalse($nonEmptyCollection->isEmpty());
    }

    public function testFirstAndLast(): void
    {
        $first = $this->createEntry(AuditLog::ACTION_CREATE);
        $last = $this->createEntry(AuditLog::ACTION_DELETE);

        $collection = new AuditEntryCollection([$first, $last]);

        $this->assertSame($first, $collection->first());
        $this->assertSame($last, $collection->last());
    }

    public function testFirstAndLastReturnNullForEmptyCollection(): void
    {
        $collection = new AuditEntryCollection([]);

        $this->assertNull($collection->first());
        $this->assertNull($collection->last());
    }

    public function testFilter(): void
    {
        $create = $this->createEntry(AuditLog::ACTION_CREATE);
        $update = $this->createEntry(AuditLog::ACTION_UPDATE);
        $delete = $this->createEntry(AuditLog::ACTION_DELETE);

        $collection = new AuditEntryCollection([$create, $update, $delete]);

        $filtered = $collection->filter(fn (AuditEntry $e) => $e->isUpdate());

        $this->assertCount(1, $filtered);
        $first = $filtered->first();
        $this->assertNotNull($first);
        $this->assertTrue($first->isUpdate());
    }

    public function testMap(): void
    {
        $collection = new AuditEntryCollection([
            $this->createEntry(AuditLog::ACTION_CREATE),
            $this->createEntry(AuditLog::ACTION_UPDATE),
        ]);

        $actions = $collection->map(fn (AuditEntry $e) => $e->getAction());

        $this->assertSame([AuditLog::ACTION_CREATE, AuditLog::ACTION_UPDATE], $actions);
    }

    public function testGroupByAction(): void
    {
        $create1 = $this->createEntry(AuditLog::ACTION_CREATE);
        $create2 = $this->createEntry(AuditLog::ACTION_CREATE);
        $update = $this->createEntry(AuditLog::ACTION_UPDATE);

        $collection = new AuditEntryCollection([$create1, $create2, $update]);

        $grouped = $collection->groupByAction();

        $this->assertArrayHasKey(AuditLog::ACTION_CREATE, $grouped);
        $this->assertArrayHasKey(AuditLog::ACTION_UPDATE, $grouped);
        $this->assertCount(2, $grouped[AuditLog::ACTION_CREATE]);
        $this->assertCount(1, $grouped[AuditLog::ACTION_UPDATE]);
    }

    public function testGroupByEntity(): void
    {
        $user1 = $this->createEntry(AuditLog::ACTION_CREATE, 'App\\Entity\\User');
        $user2 = $this->createEntry(AuditLog::ACTION_UPDATE, 'App\\Entity\\User');
        $product = $this->createEntry(AuditLog::ACTION_CREATE, 'App\\Entity\\Product');

        $collection = new AuditEntryCollection([$user1, $user2, $product]);

        $grouped = $collection->groupByEntity();

        $this->assertArrayHasKey('App\\Entity\\User', $grouped);
        $this->assertArrayHasKey('App\\Entity\\Product', $grouped);
        $this->assertCount(2, $grouped['App\\Entity\\User']);
        $this->assertCount(1, $grouped['App\\Entity\\Product']);
    }

    public function testGetCreatesUpdatesDeletes(): void
    {
        $create = $this->createEntry(AuditLog::ACTION_CREATE);
        $update = $this->createEntry(AuditLog::ACTION_UPDATE);
        $delete = $this->createEntry(AuditLog::ACTION_DELETE);
        $softDelete = $this->createEntry(AuditLog::ACTION_SOFT_DELETE);

        $collection = new AuditEntryCollection([$create, $update, $delete, $softDelete]);

        $this->assertCount(1, $collection->getCreates());
        $this->assertCount(1, $collection->getUpdates());
        $this->assertCount(2, $collection->getDeletes()); // Includes soft delete
    }

    public function testToArray(): void
    {
        $entry = $this->createEntry(AuditLog::ACTION_CREATE);
        $collection = new AuditEntryCollection([$entry]);

        $array = $collection->toArray();

        $this->assertCount(1, $array);
        $this->assertSame($entry, $array[0]);
    }

    public function testIterable(): void
    {
        $entry1 = $this->createEntry(AuditLog::ACTION_CREATE);
        $entry2 = $this->createEntry(AuditLog::ACTION_UPDATE);

        $collection = new AuditEntryCollection([$entry1, $entry2]);

        $entries = [];
        foreach ($collection as $entry) {
            $entries[] = $entry;
        }

        $this->assertCount(2, $entries);
        $this->assertSame($entry1, $entries[0]);
        $this->assertSame($entry2, $entries[1]);
    }

    private function createEntry(string $action, string $entityClass = 'App\\Entity\\User'): AuditEntry
    {
        $log = new AuditLog();
        $log->setEntityClass($entityClass);
        $log->setEntityId('1');
        $log->setAction($action);

        return new AuditEntry($log);
    }
}
