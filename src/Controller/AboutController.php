<?php declare(strict_types=1);

namespace App\Controller;

use App\Entity\Measurement;
use App\Repository\MeasurementRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class AboutController extends AbstractController
{
    #[Route(path: '/o-projekte', name: 'about')]
    function index(): Response
    {
        return $this->render('About/index.html.twig');
    }
}
