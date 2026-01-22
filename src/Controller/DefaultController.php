<?php

declare(strict_types=1);

namespace App\Controller;

use App\Application\EventManagement\Query\EventQueryService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class DefaultController extends AbstractController
{
    /**
     * Constructor - injects EventQueryService.
     *
     * @param EventQueryService $eventQueryService Service to query events
     */
    public function __construct(
        private readonly EventQueryService $eventQueryService
    ) {
    }

    #[Route('/', name: 'homepage')]
    /**
     * Startseite mit öffentlich sichtbaren Events.
     *
     * @return Response Rendered homepage template
     */
    public function index(): Response
    {
        $events = $this->eventQueryService->findPubliclyVisible();

        return $this->render('public/homepage.html.twig', [
            'events' => $events,
        ]);
    }

    #[Route('/default', name: 'app_default_index')]
    /**
     * Legacy route for default controller (kept for compatibility).
     *
     * @return Response Rendered legacy template
     */
    public function legacy(): Response
    {
        return $this->render('default/index.html.twig', [
            'controller_name' => 'DefaultController',
        ]);
    }
}
