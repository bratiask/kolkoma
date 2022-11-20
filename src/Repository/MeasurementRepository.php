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
        return ($this->last($location)?->getMeasuredAt() ?? new DateTimeImmutable('1970-01-01'))
            ->setTimezone($this->bratislavaTimezone);
    }

    public function last(string $location): ?Measurement
    {
        return $this->findOneBy(['location' => $location], ['measuredAt' => 'desc']);
    }

    /**
     * @param string $location
     * @return Measurement[]
     */
    public function last24HoursByHour(string $location): array
    {
        /** @var Measurement[] $measurements */
        $measurements = $this->createQueryBuilder('m')
            ->andWhere('m.location = :location')
            ->andWhere('m.measuredAt >= :minMeasuredAt')
            ->andWhere('m.measuredAt <= :maxMeasuredAt')
            ->setParameter('location', $location)
            ->setParameter('minMeasuredAt', new DateTimeImmutable('24 hours ago'))
            ->setParameter('maxMeasuredAt', new DateTimeImmutable('now'))
            ->getQuery()
            ->getResult();

        $sums = [];

        foreach ($measurements as $measurement) {
            $hour = $measurement->getMeasuredAt()->format('d-H');

            if (!isset($sums[$hour])) {
                $sums[$hour] = [
                    'value' => 0,
                    'count' => 0
                ];
            }

            $sums[$hour]['value'] += $measurement->getValue();
            $sums[$hour]['count']++;
        }

        $now = new DateTimeImmutable();
        $now = $now->sub(new \DateInterval(sprintf('PT%dM%dS', (int)$now->format('i') + 1, (int)$now->format('s'))));
//        dd($now);
        $result = [];

        foreach (range(0, 23) as $diff) {
            $time = $now->sub(new \DateInterval(sprintf('PT%dH', $diff)));
            $hour = $time->format('d-H');
            $sum = $sums[$hour] ?? null;

            $result[$hour] = (new Measurement())
                ->setLocation($location)
                ->setValue(null === $sum ? null : (int)round($sums[$hour]['value'] / $sums[$hour]['count']))
                ->setMeasuredAt($time);
        }

        return array_reverse($result);
    }

    /**
     * @param string $location
     * @return Measurement[]
     */
    public function last7DaysByDay(string $location): array
    {
        /** @var Measurement[] $measurements */
        $measurements = $this->createQueryBuilder('m')
            ->andWhere('m.location = :location')
            ->andWhere('m.measuredAt >= :miMeasuredAt')
            ->andWhere('m.measuredAt <= :maxMeasuredAt')
            ->setParameter('location', $location)
            ->setParameter('miMeasuredAt', new DateTimeImmutable('7 days ago midnight'))
            ->setParameter('maxMeasuredAt', new DateTimeImmutable('today midnight'))
            ->getQuery()
            ->getResult();

        $sums = [];

        foreach ($measurements as $measurement) {
            $hour = $measurement->getMeasuredAt()->format('d');

            if (!isset($sums[$hour])) {
                $sums[$hour] = [
                    'value' => 0,
                    'count' => 0
                ];
            }

            $sums[$hour]['value'] += $measurement->getValue();
            $sums[$hour]['count']++;
        }

        $now = new DateTimeImmutable('yesterday midnight');
        $result = [];

        foreach (range(0, 6) as $diff) {
            $time = $now->sub(new \DateInterval(sprintf('P%dD', $diff)));
            $hour = $time->format('d');
            $sum = $sums[$hour] ?? null;

            $result[$hour] = (new Measurement())
                ->setLocation($location)
                ->setValue(null === $sum ? null : (int)round($sums[$hour]['value'] / $sums[$hour]['count']))
                ->setMeasuredAt($time);
        }

        return array_reverse($result);
    }
}
