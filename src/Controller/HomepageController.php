<?php declare(strict_types=1);

namespace App\Controller;

use App\Entity\Measurement;
use App\Repository\MeasurementRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class HomepageController extends AbstractController
{
    #[Route(path: '/', name: 'homepage')]
    function index(MeasurementRepository $measurementRepository): Response
    {
        return $this->render('Homepage/index.html.twig', [
            'last_measurement' => $measurementRepository->lastMeasurement(Measurement::LOCATION_BA_ZP)
        ]);
    }
}
