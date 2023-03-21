<?php declare(strict_types=1);

namespace App\Service;

use App\Entity\Measurement;
use App\Repository\MeasurementRepository;
use DateTimeImmutable;

class LastMeasurement
{
    public function __construct(private MeasurementRepository $measurementRepository)
    {
    }

    function getValue(): ?Measurement
    {
        static $value;

        if (null === $value) {
            $value = $this->measurementRepository->last(Measurement::LOCATION_BA_ZP);
        }

        return $value;
    }

    function isOutdated(): bool
    {
        $lastValue = $this->getValue();

        if (null === $lastValue) {
            return true;
        }

        $diff = (new DateTimeImmutable())->getTimestamp() - $lastValue->getMeasuredAt()->getTimestamp();

        return $diff > 3600 * 4;
    }
}
