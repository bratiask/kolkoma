<?php declare(strict_types=1);

namespace App\Entity;

use App\Repository\MeasurementRepository;
use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\UniqueConstraint;

#[Entity(repositoryClass: MeasurementRepository::class), UniqueConstraint(columns: ["location", "measured_at"])]
class Measurement
{
    const LOCATION_BA_ZP = 'BA.ZP';

    #[Column, GeneratedValue, Id]
    private ?int $id = null;

    #[Column(type: Types::STRING)]
    private string $location;

    #[Column(type: Types::DATETIME_IMMUTABLE)]
    private DateTimeImmutable $measuredAt;

    #[Column(type: Types::INTEGER)]
    private int $value;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function intId(): int
    {
        return $this->id;
    }

    public function setId(?int $id): static
    {
        $this->id = $id;
        return $this;
    }

    public function getLocation(): string
    {
        return $this->location;
    }

    public function setLocation(string $location): self
    {
        $this->location = $location;
        return $this;
    }

    public function getMeasuredAt(): DateTimeImmutable
    {
        return $this->measuredAt;
    }

    public function setMeasuredAt(DateTimeImmutable $measuredAt): self
    {
        $this->measuredAt = $measuredAt;
        return $this;
    }

    public function getValue(): int
    {
        return $this->value;
    }

    public function setValue(int $value): self
    {
        $this->value = $value;
        return $this;
    }

    public function getFormattedValue(): string {
        return number_format(round($this->value / 1000, 1), 1, ',', '');
    }
}
