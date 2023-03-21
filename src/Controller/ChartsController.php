<?php declare(strict_types=1);

namespace App\Controller;

use App\Entity\Measurement;
use App\Repository\MeasurementRepository;
use DateTimeImmutable;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

class ChartsController extends AbstractController
{
    public function __construct(
        private CacheInterface $cache
    )
    {
    }

    #[Route(path: 'grafy', name: 'charts')]
    function index(MeasurementRepository $measurementRepository): Response
    {
        $days = 30; //min(14, (new DateTimeImmutable('2022-11-16'))->diff(new DateTimeImmutable())->days);

        $charts = [
            $this->chartData(
                '30d',
                'column',
                sprintf('Posledných %d dní (denné priemery)', $days),
                'date',
                1,
                new DateTimeImmutable('tomorrow midnight 1 minute'),
                ['lg' => 2, 'md' => 3, 'xs' => 5],
                fn() => $measurementRepository->last7DaysByDay(Measurement::LOCATION_BA_ZP, $days)
            )
        ];

        $averages24Hours = $measurementRepository->last24HoursByHour(Measurement::LOCATION_BA_ZP);
        $show24Hours = false;

        foreach ($averages24Hours as $average) {
            $show24Hours = $show24Hours || null !== $average->getValue();
        }

        if ($show24Hours) {
            $charts[] = $this->chartData(
                '24h',
                'column',
                'Posledných 24 hodín (hodinové priemery)',
                'datetime',
                2,
                new DateTimeImmutable('1 minute'),
                ['lg' => 2, 'md' => 4, 'xs' => 6],
                fn() => $measurementRepository->last24HoursByHour(Measurement::LOCATION_BA_ZP)
            );
        }

        return $this->render('Charts/index.html.twig', [
            'charts' => $charts
        ]);
    }

    private function chartData(
        string            $id,
        string            $chartType,
        string            $title,
        string            $labelType,
        int               $decimalPlaces,
        DateTimeImmutable $expiresAt,
        array             $breakpoints,
        callable          $getMeasurements
    ): array
    {
        return $this->cache->get(sprintf('chart-data-%s', $id) . rand(1, 100000) . rand(1, 100000), function (ItemInterface $item) use
            (
                $getMeasurements,
                $decimalPlaces,
                $expiresAt
            ) {
                $item->expiresAt($expiresAt);

                /** @var Measurement[] $measurements */
                $measurements = $getMeasurements();

                $max = -10000;
                $min = 10000;

                foreach ($measurements as $measurement) {
                    $max = max($max, $measurement->getValue());
                    $min = min($min, $measurement->getValue() ?? $min);
                }

                if ($min == $max) {
                    $max = $min + 1000;
                }

                $step = ceil(($max - $min) / 1000) * 1000;
                $step = max(1, $step);

                $yMax = ceil($max / $step) * $step;
                $yMin = floor($min / $step) * $step;

                return [
                    'max' => $yMax,
                    'min' => $yMin,
                    'maxFormatted' => Measurement::formatTemperature($yMax, 1),
                    'minFormatted' => Measurement::formatTemperature($yMin, 1),
                    'measurements' => $measurements,
                ];
            }) + [
                'chart_type' => $chartType,
                'decimal_places' => $decimalPlaces,
                'title' => $title,
                'label_type' => $labelType,
                'breakpoints' => $breakpoints,
            ];
    }
}
