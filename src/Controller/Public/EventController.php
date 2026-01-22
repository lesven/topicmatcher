<?php

declare(strict_types=1);

namespace App\Controller\Public;

use App\Application\EventManagement\Query\EventQueryService;
use App\Application\Participation\Query\PostQueryService;
use App\Application\Participation\Command\InterestSubmissionService;
use App\Application\Participation\Dto\InterestSubmissionDto;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class EventController extends AbstractController
{
    public function __construct(
        private readonly EventQueryService $eventQueryService,
        private readonly PostQueryService $postQueryService,
        private readonly InterestSubmissionService $interestService
    ) {
    }

    #[Route('/{slug}', name: 'event_show', requirements: ['slug' => '[a-z0-9\-]+'])]
    public function show(string $slug): Response
    {
        $event = $this->eventQueryService->findBySlug($slug);
        
        if (!$event || !$event->isPubliclyVisible()) {
            throw $this->createNotFoundException('Event nicht gefunden');
        }

        $groupedPosts = $this->postQueryService->getApprovedPostsGroupedByCategory($event);

        return $this->render('public/event_show.html.twig', [
            'event' => $event,
            'posts' => $groupedPosts,
        ]);
    }

    #[Route('/{slug}/post/{id}', name: 'post_show', requirements: ['slug' => '[a-z0-9\-]+', 'id' => '\d+'])]
    public function showPost(string $slug, int $id): Response
    {
        $event = $this->eventQueryService->findBySlug($slug);
        
        if (!$event || !$event->isPubliclyVisible()) {
            throw $this->createNotFoundException('Event nicht gefunden');
        }

        // TODO: Implement post lookup by ID
        // For now, redirect to event
        return $this->redirectToRoute('event_show', ['slug' => $slug]);
    }

    #[Route('/{slug}/post/{id}/interest', name: 'post_interest', requirements: ['slug' => '[a-z0-9\-]+', 'id' => '\d+'], methods: ['GET', 'POST'])]
    public function submitInterest(string $slug, int $id, Request $request): Response
    {
        $event = $this->eventQueryService->findBySlug($slug);
        
        if (!$event || !$event->allowsInterests()) {
            throw $this->createNotFoundException('Interessenbekundung nicht möglich');
        }

        // TODO: Implement interest submission form
        // For now, redirect to event
        $this->addFlash('info', 'Interessenbekundung ist noch nicht implementiert');
        return $this->redirectToRoute('event_show', ['slug' => $slug]);
    }
}