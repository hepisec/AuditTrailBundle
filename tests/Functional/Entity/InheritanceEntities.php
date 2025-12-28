<?php

declare(strict_types=1);

namespace Rcsofttech\AuditTrailBundle\Tests\Functional\Entity;

use Doctrine\ORM\Mapping as ORM;
use Rcsofttech\AuditTrailBundle\Attribute\Auditable;

#[ORM\Entity]
#[ORM\InheritanceType('SINGLE_TABLE')]
#[ORM\DiscriminatorColumn(name: 'discr', type: 'string')]
#[ORM\DiscriminatorMap(['vehicle' => Vehicle::class, 'car' => Car::class, 'truck' => Truck::class])]
#[Auditable]
abstract class Vehicle
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    // @phpstan-ignore property.unusedType
    private ?int $id = null;

    #[ORM\Column]
    private string $model;

    public function __construct(string $model)
    {
        $this->model = $model;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getModel(): string
    {
        return $this->model;
    }

    public function setModel(string $model): void
    {
        $this->model = $model;
    }
}

#[ORM\Entity]
class Car extends Vehicle
{
    #[ORM\Column(nullable: true)]
    private ?int $doors = null;

    public function getDoors(): ?int
    {
        return $this->doors;
    }

    public function setDoors(int $doors): void
    {
        $this->doors = $doors;
    }
}

#[ORM\Entity]
class Truck extends Vehicle
{
    #[ORM\Column(nullable: true)]
    private ?int $capacity = null;

    public function getCapacity(): ?int
    {
        return $this->capacity;
    }

    public function setCapacity(int $capacity): void
    {
        $this->capacity = $capacity;
    }
}

#[ORM\Entity]
#[ORM\InheritanceType('JOINED')]
#[ORM\DiscriminatorColumn(name: 'discr', type: 'string')]
#[ORM\DiscriminatorMap(['animal' => Animal::class, 'dog' => Dog::class])]
#[Auditable]
abstract class Animal
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    // @phpstan-ignore property.unusedType
    private ?int $id = null;

    #[ORM\Column]
    private string $name;

    public function __construct(string $name)
    {
        $this->name = $name;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }
}

#[ORM\Entity]
class Dog extends Animal
{
    #[ORM\Column(nullable: true)]
    private ?string $breed = null;

    public function getBreed(): ?string
    {
        return $this->breed;
    }

    public function setBreed(string $breed): void
    {
        $this->breed = $breed;
    }
}
