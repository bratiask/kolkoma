<?php declare(strict_types=1);

namespace App\Controller;

use App\Entity\Measurement;
use App\Repository\MeasurementRepository;
use DateTimeImmutable;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
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
    function index(
        Request               $request,
        MeasurementRepository $measurementRepository
    ): Response
    {
        return $this->render('Charts/index.html.twig', [
            'charts' => [
                $this->chartData(
                    '24h',
                    'Posledných 24 hodín (hodinové priemery, °C)',
                    'datetime',
                    2,
                    new DateTimeImmutable('1 minute'),
                    ['lg' => 2, 'md' => 4, 'xs' => 6],
                    fn() => $measurementRepository->last24HoursByHour(Measurement::LOCATION_BA_ZP)
                ),
                ...(null === $request->query->get('d') ? [] : [
                    $this->chartData(
                        '7d',
                        'Posledných 7 dní (denné priemery, °C)',
                        'date',
                        1,
                        new DateTimeImmutable('tomorrow midnight 1 minute'),
                        ['lg' => 1, 'md' => 1, 'xs' => 1],
                        fn() => $measurementRepository->last7DaysByDay(Measurement::LOCATION_BA_ZP)
                    )
                ])
            ]
        ]);
    }

    private function chartData(
        string            $id,
        string            $title,
        string            $labelType,
        int               $decimalPlaces,
        DateTimeImmutable $expiresAt,
        array             $breakpoints,
        callable          $getMeasurements
    ): array
    {
        return $this->cache->get(sprintf('chart-datas-%s', $id), function (ItemInterface $item) use
            (
                $getMeasurements,
                $decimalPlaces,
                $expiresAt
            ) {
                $item->expiresAt($expiresAt);

                $measurements = $getMeasurements();

                $max = -10000;
                $min = 10000;

                foreach ($measurements as $measurement) {
                    $max = max($max, $measurement->compareValue($decimalPlaces));
                    $min = min($min, $measurement->compareValue($decimalPlaces) ?? $min);
                }

                return [
                    'max' => $max,
                    'min' => $min,
                    'measurements' => $measurements,
                ];
            }) + [
                'decimal_places' => $decimalPlaces,
                'title' => $title,
                'label_type' => $labelType,
                'breakpoints' => $breakpoints,
            ];
    }
}
