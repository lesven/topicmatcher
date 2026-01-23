<?php

declare(strict_types=1);

namespace App\Controller;

use App\Application\EventManagement\Query\EventQueryService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Public default controller (homepage and legacy routes).
 */
class DefaultController extends AbstractController
{
    /**
     * Konstruktor – injiziert den EventQueryService.
     *
     * @param EventQueryService $eventQueryService Service zum Abruf von Events
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
