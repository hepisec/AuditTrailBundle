<?php

namespace Rcsofttech\AuditTrailBundle\EventSubscriber;

use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Event\PostFlushEventArgs;
use Doctrine\ORM\Events;
use Rcsofttech\AuditTrailBundle\Contract\AuditTransportInterface;
use Rcsofttech\AuditTrailBundle\Entity\AuditLog;
use Rcsofttech\AuditTrailBundle\Service\AuditService;

#[AsDoctrineListener(event: Events::onFlush, priority: 1000)]
#[AsDoctrineListener(event: Events::postFlush, priority: 1000)]
final class AuditSubscriber
{
    /**
     * @var array<array{entity: object, audit: AuditLog}>
     */
    private array $scheduledAudits = [];

    /**
     * @var array<array{entity: object, data: array<string, mixed>}>
     */
    private array $pendingDeletions = [];

    private bool $isFlushing = false;

    public function __construct(
        private readonly AuditService $auditService,
        private readonly AuditTransportInterface $transport,
        private readonly bool $enableSoftDelete = true,
        private readonly bool $enableHardDelete = true,
        private readonly string $softDeleteField = 'deletedAt',
    ) {
    }

    public function onFlush(OnFlushEventArgs $args): void
    {
        if ($this->isFlushing) {
            return;
        }

        $em = $args->getObjectManager();
        $uow = $em->getUnitOfWork();

        // INSERT - Store for postFlush ID update
        foreach ($uow->getScheduledEntityInsertions() as $entity) {
            if (!$this->shouldProcessEntity($entity)) {
                continue;
            }

            $audit = $this->auditService->createAuditLog(
                $entity,
                AuditLog::ACTION_CREATE,
                null,
                $this->auditService->getEntityData($entity)
            );

            $this->transport->send($audit, [
                'phase' => 'on_flush',
                'em' => $em,
                'uow' => $uow,
            ]);

            $this->scheduledAudits[] = ['entity' => $entity, 'audit' => $audit];
        }

        // UPDATE
        foreach ($uow->getScheduledEntityUpdates() as $entity) {
            if (!$this->shouldProcessEntity($entity)) {
                continue;
            }

            $changeSet = $uow->getEntityChangeSet($entity);
            [$old, $new] = $this->extractChanges($changeSet);

            $action = AuditLog::ACTION_UPDATE;

            // Detect Restore (Manual updates)
            if ($this->enableSoftDelete && \array_key_exists($this->softDeleteField, $changeSet)) {
                $deletedAtChange = $changeSet[$this->softDeleteField];
                if (null !== $deletedAtChange[0] && null === $deletedAtChange[1]) {
                    $action = AuditLog::ACTION_RESTORE;
                }
            }

            $audit = $this->auditService->createAuditLog($entity, $action, $old, $new);

            $this->transport->send($audit, [
                'phase' => 'on_flush',
                'em' => $em,
                'uow' => $uow,
            ]);

            $this->scheduledAudits[] = ['entity' => $entity, 'audit' => $audit];
        }

        // DELETE - Defer processing to postFlush to detect Soft Deletes (Gedmo interception)
        foreach ($uow->getScheduledEntityDeletions() as $entity) {
            if (!$this->shouldProcessEntity($entity)) {
                continue;
            }
            // Capture original data now
            $this->pendingDeletions[] = [
                'entity' => $entity,
                'data' => $this->auditService->getEntityData($entity),
            ];
        }
    }

    public function postFlush(PostFlushEventArgs $args): void
    {
        if ($this->isFlushing) {
            return;
        }

        $em = $args->getObjectManager();
        $hasNewAudits = false;

        // Process Deferred Deletions
        foreach ($this->pendingDeletions as $pending) {
            $entity = $pending['entity'];
            $oldData = $pending['data'];
            $action = null;

            if (!$em->contains($entity)) {
                // Entity is detached -> Hard Delete
                if ($this->enableHardDelete) {
                    $action = AuditLog::ACTION_DELETE;
                }
            } else {
                // Entity is still managed -> Soft Delete
                // If it was scheduled for deletion but is now managed, it was intercepted (e.g., by Gedmo)
                if ($this->enableSoftDelete) {
                    $action = AuditLog::ACTION_SOFT_DELETE;
                }
            }

            if ($action) {
                $newData = (AuditLog::ACTION_SOFT_DELETE === $action) ? $this->auditService->getEntityData($entity) : null;

                $audit = $this->auditService->createAuditLog(
                    $entity,
                    $action,
                    $oldData,
                    $newData
                );

                // Explicitly persist the new audit log because the main transaction is closed
                $em->persist($audit);
                $hasNewAudits = true;

                $this->transport->send($audit, [
                    'phase' => 'post_flush',
                    'em' => $em,
                    'entity' => $entity,
                ]);
            }
        }
        $this->pendingDeletions = [];

        // Process Scheduled Audits (ID updates for inserts)
        foreach ($this->scheduledAudits as $scheduled) {
            $this->transport->send($scheduled['audit'], [
                'phase' => 'post_flush',
                'em' => $em,
                'entity' => $scheduled['entity'],
            ]);
        }
        $this->scheduledAudits = [];

        if ($hasNewAudits) {
            $this->isFlushing = true;
            try {
                $em->flush();
            } finally {
                $this->isFlushing = false;
            }
        }
    }

    private function shouldProcessEntity(object $entity): bool
    {
        if ($entity instanceof AuditLog) {
            return false;
        }

        return $this->auditService->shouldAudit($entity);
    }

    /**
     * @param array<string, mixed> $changeSet
     *
     * @return array{0: array<string, mixed>, 1: array<string, mixed>}
     */
    private function extractChanges(array $changeSet): array
    {
        $old = [];
        $new = [];

        foreach ($changeSet as $field => $change) {
            if (!\is_array($change) || !\array_key_exists(0, $change) || !\array_key_exists(1, $change)) {
                continue;
            }

            [$oldValue, $newValue] = $change;

            if ($oldValue === $newValue) {
                continue;
            }
            $old[$field] = $oldValue;
            $new[$field] = $newValue;
        }

        return [$old, $new];
    }
}
