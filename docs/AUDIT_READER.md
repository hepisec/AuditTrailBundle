# AuditReader API Documentation

The **AuditReader** provides a fluent, immutable, and expressive API to query audit logs programmatically in your Symfony application.

It is designed for:
- Entity-level audit history
- Advanced filtering (actions, users, fields, transactions)
- Diff inspection
- Pagination and collections
- Read-only, side-effect-free querying

All query methods are chainable and return **new immutable query instances**.

---

## 1. Injecting the AuditReader

```php
use Rcsofttech\AuditTrailBundle\Contract\AuditReaderInterface;

class UserController extends AbstractController
{
    public function __construct(
        private readonly AuditReaderInterface $auditReader
    ) {}
}

```

## Get complete audit history for a specific entity instance

```php
$user = $userRepository->find(123);

$history = $this->auditReader->getHistoryFor($user);

foreach ($history as $entry) {
    printf(
        "%s: %s by %s at %s\n",
        $entry->getAction(),          // create, update, delete
        $entry->getEntityShortName(), // User
        $entry->getUsername(),        // admin@example.com
        $entry->getCreatedAt()->format('Y-m-d H:i:s')
    );
}

```

## Building Custom Queries

```php

//Find all updates to User entities in the last 30 days

$recentUpdates = $this->auditReader
    ->forEntity(User::class)
    ->updates()
    ->since(new \DateTimeImmutable('-30 days'))
    ->limit(50)
    ->getResults();

//Find all deletions by a specific admin

use Rcsofttech\AuditTrailBundle\Entity\AuditLog;

$deletions = $this->auditReader
    ->byUser(adminUserId: 1)
    ->action(
        AuditLog::ACTION_DELETE,
        AuditLog::ACTION_SOFT_DELETE
    )
    ->getResults();
```
## Find everything that happened in a single transaction
```php
$transactionAudits = $this->auditReader
    ->byTransaction('019b5aca-60ed-70bf-b139-255aa96c96cb')
    ->getResults();

```

## Find updates where a specific field was changed
```php
$emailChanges = $this->auditReader
    ->forEntity(User::class)
    ->updates()
    ->changedField('email')
    ->getResults();

```

## Working with Diffs

```php
$entry = $this->auditReader
    ->forEntity(User::class, '123')
    ->updates()
    ->getFirstResult();

if ($entry) {
    $diff = $entry->getDiff();
    /*
     * Example:
     * [
     *   'name'  => ['old' => 'John', 'new' => 'Jane'],
     *   'email' => ['old' => 'a@x.com', 'new' => 'b@x.com']
     * ]
     */

    if ($entry->hasFieldChanged('email')) {
        $oldEmail = $entry->getOldValue('email');
        $newEmail = $entry->getNewValue('email');

        echo "Email changed from $oldEmail to $newEmail";
    }

    $changedFields = $entry->getChangedFields(); // ['name', 'email']
}


```
## Working with Collections

```
$audits = $this->auditReader
    ->forEntity(Order::class)
    ->between(
        new \DateTimeImmutable('2024-01-01'),
        new \DateTimeImmutable('2024-12-31')
    )
    ->getResults();
```

## Filter results in memory

```php
$priceChanges = $audits->filter(
    fn ($entry) => $entry->hasFieldChanged('price')
);

```

## Grouping results

```php
$byAction = $audits->groupByAction();
// ['create' => [...], 'update' => [...], 'delete' => [...]

$byEntity = $audits->groupByEntity();
// ['App\Entity\Order' => [...]]


```
## Collection utilities

```php
$first = $audits->first();
$last  = $audits->last();
$count = $audits->count();
$empty = $audits->isEmpty();
```

## Pagination

```php
$page1 = $this->auditReader
    ->forEntity(Product::class)
    ->limit(25)
    ->offset(0)
    ->getResults();
```

## Check if matching records exist

```php
$hasDeletes = $this->auditReader
    ->forEntity(User::class)
    ->deletes()
    ->exists();

```
## Check if an entity has any audit history

```php
$hasHistory = $this->auditReader->hasHistoryFor($entity);
```



