<?php declare(strict_types=1);

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ChartsController extends AbstractController
{
    #[Route(path: 'grafy', name: 'charts')]
    function index(): Response
    {
        return $this->render('Charts/index.html.twig');
    }
}
