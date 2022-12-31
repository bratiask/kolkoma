<?php declare(strict_types=1);

namespace App\Controller;

use App\Entity\Measurement;
use App\Entity\User;
use App\Form\Type\RegisterType;
use App\Repository\MeasurementRepository;
use App\Repository\UserRepository;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
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

    #[Route(path: '/register', name: 'register')]
    function register(
        Request                  $request,
        JWTTokenManagerInterface $JWTTokenManager,
        UserRepository           $userRepository,
        MailerInterface          $mailer
    ): Response
    {
        $form = $this->createForm(RegisterType::class, new User());
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var User $formUser */
            $formUser = $form->getData();

            $user = $userRepository->findOneBy(['email' => $formUser->getEmail()]);

            if (null === $user) {
                $user = $formUser->setApiKey($JWTTokenManager->createFromPayload($formUser, ['exp' => 0]));
                $userRepository->save($formUser, true);
            }

            $mailer->send((new TemplatedEmail())
                ->from('ahoj@kolkoma.sk')
                ->to($user->getEmail())
                ->subject('kolkoma.sk: Tvoj API kľúč')
                ->htmlTemplate('email/registration.html.twig')
                ->context([
                    'user' => $user
                ])
            );
        }

        return $this->render('Api/register.html.twig', [
            'form' => $form,
        ]);
    }
}
