<?php

declare(strict_types=1);

namespace Rcsofttech\AuditTrailBundle\Query;

use Rcsofttech\AuditTrailBundle\Entity\AuditLog;
use Rcsofttech\AuditTrailBundle\Repository\AuditLogRepository;

/**
 * Fluent, immutable query builder for audit logs.
 *
 * Each method returns a new instance, preserving immutability.
 * Execute the query with getResults(), count(), or getFirstResult().
 *
 * Uses keyset (cursor) pagination for efficient large dataset traversal.
 */
readonly class AuditQuery
{
    private const int DEFAULT_LIMIT = 30;

    /**
     * @param array<string> $actions
     * @param array<string> $changedFields
     */
    public function __construct(
        private AuditLogRepository $repository,
        private ?string $entityClass = null,
        private ?string $entityId = null,
        private array $actions = [],
        private ?int $userId = null,
        private ?string $transactionHash = null,
        private ?\DateTimeInterface $since = null,
        private ?\DateTimeInterface $until = null,
        private array $changedFields = [],
        private int $limit = self::DEFAULT_LIMIT,
        private ?int $afterId = null,
        private ?int $beforeId = null,
    ) {
    }

    /**
     * Filter by entity class and optional ID.
     */
    public function entity(string $class, ?string $id = null): self
    {
        return new self(
            repository: $this->repository,
            entityClass: $class,
            entityId: $id,
            actions: $this->actions,
            userId: $this->userId,
            transactionHash: $this->transactionHash,
            since: $this->since,
            until: $this->until,
            changedFields: $this->changedFields,
            limit: $this->limit,
            afterId: $this->afterId,
            beforeId: $this->beforeId,
        );
    }

    /**
     * Filter by entity ID (requires entity class to be set).
     */
    public function entityId(string $id): self
    {
        return new self(
            repository: $this->repository,
            entityClass: $this->entityClass,
            entityId: $id,
            actions: $this->actions,
            userId: $this->userId,
            transactionHash: $this->transactionHash,
            since: $this->since,
            until: $this->until,
            changedFields: $this->changedFields,
            limit: $this->limit,
            afterId: $this->afterId,
            beforeId: $this->beforeId,
        );
    }

    /**
     * Filter by one or more action types.
     */
    public function action(string ...$actions): self
    {
        return new self(
            repository: $this->repository,
            entityClass: $this->entityClass,
            entityId: $this->entityId,
            actions: $actions,
            userId: $this->userId,
            transactionHash: $this->transactionHash,
            since: $this->since,
            until: $this->until,
            changedFields: $this->changedFields,
            limit: $this->limit,
            afterId: $this->afterId,
            beforeId: $this->beforeId,
        );
    }

    /**
     * Filter for create actions only.
     */
    public function creates(): self
    {
        return $this->action(AuditLog::ACTION_CREATE);
    }

    /**
     * Filter for update actions only.
     */
    public function updates(): self
    {
        return $this->action(AuditLog::ACTION_UPDATE);
    }

    /**
     * Filter for delete actions only.
     */
    public function deletes(): self
    {
        return $this->action(AuditLog::ACTION_DELETE, AuditLog::ACTION_SOFT_DELETE);
    }

    /**
     * Filter by user ID.
     */
    public function user(int $userId): self
    {
        return new self(
            repository: $this->repository,
            entityClass: $this->entityClass,
            entityId: $this->entityId,
            actions: $this->actions,
            userId: $userId,
            transactionHash: $this->transactionHash,
            since: $this->since,
            until: $this->until,
            changedFields: $this->changedFields,
            limit: $this->limit,
            afterId: $this->afterId,
            beforeId: $this->beforeId,
        );
    }

    /**
     * Filter by transaction hash.
     */
    public function transaction(string $hash): self
    {
        return new self(
            repository: $this->repository,
            entityClass: $this->entityClass,
            entityId: $this->entityId,
            actions: $this->actions,
            userId: $this->userId,
            transactionHash: $hash,
            since: $this->since,
            until: $this->until,
            changedFields: $this->changedFields,
            limit: $this->limit,
            afterId: $this->afterId,
            beforeId: $this->beforeId,
        );
    }

    /**
     * Filter for logs created on or after the given date.
     */
    public function since(\DateTimeInterface $from): self
    {
        return new self(
            repository: $this->repository,
            entityClass: $this->entityClass,
            entityId: $this->entityId,
            actions: $this->actions,
            userId: $this->userId,
            transactionHash: $this->transactionHash,
            since: $from,
            until: $this->until,
            changedFields: $this->changedFields,
            limit: $this->limit,
            afterId: $this->afterId,
            beforeId: $this->beforeId,
        );
    }

    /**
     * Filter for logs created on or before the given date.
     */
    public function until(\DateTimeInterface $to): self
    {
        return new self(
            repository: $this->repository,
            entityClass: $this->entityClass,
            entityId: $this->entityId,
            actions: $this->actions,
            userId: $this->userId,
            transactionHash: $this->transactionHash,
            since: $this->since,
            until: $to,
            changedFields: $this->changedFields,
            limit: $this->limit,
            afterId: $this->afterId,
            beforeId: $this->beforeId,
        );
    }

    /**
     * Filter for logs within a date range.
     */
    public function between(\DateTimeInterface $from, \DateTimeInterface $to): self
    {
        return $this->since($from)->until($to);
    }

    /**
     * Filter for logs that changed specific fields.
     */
    public function changedField(string ...$fields): self
    {
        return new self(
            repository: $this->repository,
            entityClass: $this->entityClass,
            entityId: $this->entityId,
            actions: $this->actions,
            userId: $this->userId,
            transactionHash: $this->transactionHash,
            since: $this->since,
            until: $this->until,
            changedFields: $fields,
            limit: $this->limit,
            afterId: $this->afterId,
            beforeId: $this->beforeId,
        );
    }

    /**
     * Limit the number of results.
     */
    public function limit(int $limit): self
    {
        return new self(
            repository: $this->repository,
            entityClass: $this->entityClass,
            entityId: $this->entityId,
            actions: $this->actions,
            userId: $this->userId,
            transactionHash: $this->transactionHash,
            since: $this->since,
            until: $this->until,
            changedFields: $this->changedFields,
            limit: $limit,
            afterId: $this->afterId,
            beforeId: $this->beforeId,
        );
    }

    /**
     * Keyset pagination: Get results after a specific audit log ID.
     * Use this for "next page" navigation.
     *
     * @param int $id The last ID from the previous page
     */
    public function after(int $id): self
    {
        return new self(
            repository: $this->repository,
            entityClass: $this->entityClass,
            entityId: $this->entityId,
            actions: $this->actions,
            userId: $this->userId,
            transactionHash: $this->transactionHash,
            since: $this->since,
            until: $this->until,
            changedFields: $this->changedFields,
            limit: $this->limit,
            afterId: $id,
            beforeId: null,
        );
    }

    /**
     * Keyset pagination: Get results before a specific audit log ID.
     * Use this for "previous page" navigation.
     *
     * @param int $id The first ID from the current page
     */
    public function before(int $id): self
    {
        return new self(
            repository: $this->repository,
            entityClass: $this->entityClass,
            entityId: $this->entityId,
            actions: $this->actions,
            userId: $this->userId,
            transactionHash: $this->transactionHash,
            since: $this->since,
            until: $this->until,
            changedFields: $this->changedFields,
            limit: $this->limit,
            afterId: null,
            beforeId: $id,
        );
    }

    /**
     * Execute the query and return results.
     */
    public function getResults(): AuditEntryCollection
    {
        $filters = $this->buildFilters();
        $logs = $this->repository->findWithFilters($filters, $this->limit);



        // Apply post-fetch filters (changedFields requires PHP filtering)
        if ([] !== $this->changedFields) {
            $logs = $this->filterByChangedFields($logs);
        }

        $entries = array_values(array_map(
            fn (AuditLog $log) => new AuditEntry($log),
            $logs
        ));

        return new AuditEntryCollection($entries);
    }

    /**
     * Count matching results.
     */
    public function count(): int
    {
        // For accurate count with changedFields filter, we need to fetch and filter
        if ([] !== $this->changedFields) {
            return $this->getResults()->count();
        }

        $filters = $this->buildFilters();
        // Remove cursor filters for count
        unset($filters['afterId'], $filters['beforeId']);
        $logs = $this->repository->findWithFilters($filters, PHP_INT_MAX);

        return \count($logs);
    }

    /**
     * Get the first result or null.
     */
    public function getFirstResult(): ?AuditEntry
    {
        return $this->limit(1)->getResults()->first();
    }

    /**
     * Check if any results exist.
     */
    public function exists(): bool
    {
        return null !== $this->getFirstResult();
    }

    /**
     * Get the cursor (last ID) for pagination.
     * Use this to get the ID for the next page.
     */
    public function getNextCursor(): ?int
    {
        $results = $this->getResults();
        $last = $results->last();

        return $last?->getId();
    }

    /**
     * Build filters array for repository.
     *
     * @return array<string, mixed>
     */
    private function buildFilters(): array
    {
        $filters = [];

        if (null !== $this->entityClass) {
            $filters['entityClass'] = $this->entityClass;
        }

        if (null !== $this->entityId) {
            $filters['entityId'] = $this->entityId;
        }

        if ([] !== $this->actions) {
            // Repository uses single action, we'll handle multiple in PHP
            if (1 === \count($this->actions)) {
                $filters['action'] = $this->actions[0];
            }
        }

        if (null !== $this->userId) {
            $filters['userId'] = $this->userId;
        }

        if (null !== $this->transactionHash) {
            $filters['transactionHash'] = $this->transactionHash;
        }

        if (null !== $this->since) {
            $filters['from'] = $this->since instanceof \DateTimeImmutable
                ? $this->since
                : \DateTimeImmutable::createFromInterface($this->since);
        }

        if (null !== $this->until) {
            $filters['to'] = $this->until instanceof \DateTimeImmutable
                ? $this->until
                : \DateTimeImmutable::createFromInterface($this->until);
        }

        // Keyset pagination cursors
        if (null !== $this->afterId) {
            $filters['afterId'] = $this->afterId;
        }

        if (null !== $this->beforeId) {
            $filters['beforeId'] = $this->beforeId;
        }



        return $filters;
    }

    /**
     * Filter logs by changed fields (post-fetch).
     *
     * @param array<AuditLog> $logs
     *
     * @return array<AuditLog>
     */
    private function filterByChangedFields(array $logs): array
    {
        return array_values(array_filter($logs, function (AuditLog $log) {
            $logChangedFields = $log->getChangedFields() ?? [];

            foreach ($this->changedFields as $field) {
                if (\in_array($field, $logChangedFields, true)) {
                    return true;
                }
            }

            return false;
        }));
    }
}
