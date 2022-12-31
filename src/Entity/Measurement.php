<?php declare(strict_types=1);

namespace App\Entity;

use ApiPlatform\Api\UrlGeneratorInterface;
use ApiPlatform\Doctrine\Common\Filter\DateFilterInterface;
use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Doctrine\Orm\Filter\DateFilter;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GetCollection;
use App\Repository\MeasurementRepository;
use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\UniqueConstraint;
use Symfony\Component\Serializer\Annotation\Groups;

#[Entity(repositoryClass: MeasurementRepository::class), UniqueConstraint(columns: ["location", "measured_at"])]
#[ApiResource(
    description: 'Temperature at given location and time',
    operations: [
        new GetCollection(openapiContext: ["summary" => "", "description" => "Retrieves the collection of temperature measurements (in Celsius degrees) for given date range."]),
    ],
    urlGenerationStrategy: UrlGeneratorInterface::ABS_URL,
    normalizationContext: ['groups' => ['read']],
    order: ['measuredAt' => 'DESC'],
    paginationItemsPerPage: 240,
    paginationPartial: true
)]
#[ApiFilter(DateFilter::class, properties: ['measuredAt' => DateFilterInterface::EXCLUDE_NULL])]
class Measurement
{
    const LOCATION_BA_ZP = 'BA.ZP';

    #[Column, GeneratedValue, Id]
    private ?int $id = null;

    /**
     * Only one code (BA.ZP) is supported for now.
     */
    #[Groups('read')]
    #[Column(type: Types::STRING)]
    private string $location;

    /**
     * Time of measurement.
     */
    #[Groups('read')]
    #[Column(type: Types::DATETIME_IMMUTABLE)]
    private DateTimeImmutable $measuredAt;

    #[Column(type: Types::INTEGER, nullable: true)]
    private ?int $value = null;

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

    public function getValue(): ?int
    {
        return $this->value;
    }

    public function setValue(?int $value): self
    {
        $this->value = $value;
        return $this;
    }

    /**
     * Measurement value in Celsius degrees.
     */
    #[Groups('read')]
    public function getTemperature(): ?float
    {
        return null === $this->value ? null : round($this->value / 1000, 2);
    }

    public function compareValue(int $decimalPlaces = 2): ?int
    {
        return null === $this->value ? null : (int)round($this->value / pow(10, 3 - $decimalPlaces));
    }

    public function getFormattedValue(int $precision = 1): ?string
    {
        return self::formatTemperature($this->value, $precision);
    }

    public static function formatTemperature(
        ?float $value,
        int    $precision = 0
    ): ?string
    {
        return null === $value ? null : preg_replace('/,0*$/', '', number_format(round($value / 1000, $precision), $precision, ',', ''));
    }
}
