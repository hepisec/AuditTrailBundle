<?php

declare(strict_types=1);

namespace Rcsofttech\AuditTrailBundle\Contract;

interface DiffGeneratorInterface
{
    /**
     * @param array<string, mixed>|null $oldValues
     * @param array<string, mixed>|null $newValues
     * @param array<string, mixed>      $options
     *
     * @return array<string, array{old: mixed, new: mixed}>
     */
    public function generate(?array $oldValues, ?array $newValues, array $options = []): array;
}
