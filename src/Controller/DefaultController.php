<?php

declare(strict_types=1);

namespace App\Controller;

use App\Application\EventManagement\Query\EventQueryService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class DefaultController extends AbstractController
{
    public function __construct(
        private readonly EventQueryService $eventQueryService
    ) {
    }

    #[Route('/', name: 'homepage')]
    public function index(): Response
    {
        $events = $this->eventQueryService->findPubliclyVisible();

        return $this->render('public/homepage.html.twig', [
            'events' => $events,
        ]);
    }

    #[Route('/default', name: 'app_default_index')]
    public function legacy(): Response
    {
        return $this->render('default/index.html.twig', [
            'controller_name' => 'DefaultController',
        ]);
    }
}
