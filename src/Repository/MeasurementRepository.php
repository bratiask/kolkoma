<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Measurement;
use DateTimeImmutable;
use DateTimeZone;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Measurement|null find($id, $lockMode = null, $lockVersion = null)
 * @method Measurement|null findOneBy(array $criteria, array $orderBy = null)
 * @method Measurement[]    findAll()
 * @method Measurement[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class MeasurementRepository extends ServiceEntityRepository
{
    private DateTimeZone $bratislavaTimezone;

    public function __construct(ManagerRegistry $registry)
    {
        $this->bratislavaTimezone = new DateTimeZone('Europe/Bratislava');

        parent::__construct($registry, Measurement::class);
    }

    public function lastMeasuredAt(string $location): DateTimeImmutable
    {
        return ($this->lastMeasurement($location)?->getMeasuredAt() ?? new DateTimeImmutable('1970-01-01'))
            ->setTimezone($this->bratislavaTimezone);
    }

    public function lastMeasurement(string $location): ?Measurement
    {
        return $this->findOneBy(['location' => $location], ['measuredAt' => 'desc']);
    }
}
