<?php declare(strict_types=1);

namespace App\Service;

use App\Entity\Measurement;
use App\Repository\MeasurementRepository;

class LastMeasurement
{
    public function __construct(private MeasurementRepository $measurementRepository)
    {
    }

    function getValue(): ?Measurement
    {
        return $this->measurementRepository->last(Measurement::LOCATION_BA_ZP);
    }
}
