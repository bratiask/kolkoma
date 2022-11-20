<?php declare(strict_types=1);

namespace App\Controller;

use App\Entity\Measurement;
use App\Repository\MeasurementRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ChartsController extends AbstractController
{
    #[Route(path: 'grafy', name: 'charts')]
    function index(MeasurementRepository $measurementRepository): Response
    {
        return $this->render('Charts/index.html.twig', [
            'last_24h' => $this->chartsData($measurementRepository->last24HoursByHour(Measurement::LOCATION_BA_ZP))
        ]);
    }

    /**
     * @param Measurement[] $measurements
     * @return array
     */
    private function chartsData(array $measurements): array {

        $max = -10000;
        $min = 10000;

        foreach ($measurements as $measurement) {
            $max = max($max, $measurement->compareValue());
            $min = min($min, $measurement->compareValue() ?? $min);
        }

        return [
            'max' => $max,
            'min' => $min,
            'measurements' => $measurements,
        ];
    }
}
