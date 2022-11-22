<?php declare(strict_types=1);

namespace App\Controller;

use App\Entity\Measurement;
use App\Repository\MeasurementRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ApiController extends AbstractController
{
    #[Route(path: '/api/txt/temperature/last')]
    function lastTemperature(MeasurementRepository $measurementRepository): Response
    {
        return new Response($measurementRepository->last(Measurement::LOCATION_BA_ZP)->getFormattedValue(), Response::HTTP_OK, [
            'Content-type' => 'text/plain'
        ]);
    }
}
