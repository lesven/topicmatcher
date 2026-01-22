<?php

declare(strict_types=1);

namespace App\Controller\Backoffice;

use App\Application\Backoffice\Query\ModerationQueryService;
use App\Domain\EventManagement\Event;
use App\Domain\EventManagement\EventStatus;
use App\Infrastructure\Repository\EventRepository;
use App\Infrastructure\Repository\PostRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/events')]
#[IsGranted('ROLE_ADMIN')]
class EventManagementController extends AbstractController
{
    public function __construct(
        private readonly EventRepository $eventRepository,
        private readonly PostRepository $postRepository,
        private readonly EntityManagerInterface $entityManager
    ) {
    }

    #[Route('/', name: 'backoffice_events_index')]
    public function index(Request $request): Response
    {
        $status = $request->query->get('status');
        $eventStatus = $status ? EventStatus::tryFrom($status) : null;
        
        if ($eventStatus) {
            $events = $this->eventRepository->findByStatus($eventStatus);
        } else {
            $events = $this->eventRepository->findAllOrderedByCreated();
        }

        // Get statistics
        $stats = [
            'total' => $this->eventRepository->getTotalCount(),
            'draft' => $this->eventRepository->getCountByStatus(EventStatus::DRAFT),
            'active' => $this->eventRepository->getCountByStatus(EventStatus::ACTIVE),
            'closed' => $this->eventRepository->getCountByStatus(EventStatus::CLOSED),
            'archived' => $this->eventRepository->getCountByStatus(EventStatus::ARCHIVED),
        ];

        return $this->render('backoffice/events/index.html.twig', [
            'events' => $events,
            'stats' => $stats,
            'currentStatus' => $eventStatus,
        ]);
    }

    #[Route('/create', name: 'backoffice_events_create', methods: ['GET', 'POST'])]
    public function create(Request $request): Response
    {
        if ($request->isMethod('POST')) {
            try {
                $name = trim($request->request->get('name', ''));
                $slug = trim($request->request->get('slug', ''));
                $description = trim($request->request->get('description', ''));
                $location = trim($request->request->get('location', ''));
                $eventDate = $request->request->get('event_date');

                // Basic validation
                if (empty($name)) {
                    throw new \InvalidArgumentException('Event-Name ist erforderlich.');
                }
                if (empty($slug)) {
                    throw new \InvalidArgumentException('URL-Slug ist erforderlich.');
                }

                // Check slug uniqueness
                if ($this->eventRepository->findBySlug($slug)) {
                    throw new \InvalidArgumentException('Dieser URL-Slug ist bereits vergeben.');
                }

                // Parse date
                $parsedDate = null;
                if ($eventDate) {
                    $parsedDate = new \DateTime($eventDate);
                }

                $event = new Event($name, $slug, $description, $parsedDate, $location);
                $this->entityManager->persist($event);
                $this->entityManager->flush();

                $this->addFlash('success', sprintf('Event "%s" wurde erfolgreich erstellt.', $name));
                return $this->redirectToRoute('backoffice_events_detail', ['slug' => $event->getSlug()]);
            } catch (\Exception $e) {
                $this->addFlash('error', 'Fehler beim Erstellen: ' . $e->getMessage());
            }
        }

        return $this->render('backoffice/events/create.html.twig');
    }

    #[Route('/{slug}', name: 'backoffice_events_detail')]
    public function detail(string $slug): Response
    {
        $event = $this->eventRepository->findOneBySlug($slug);
        
        if (!$event) {
            throw $this->createNotFoundException('Event nicht gefunden');
        }

        // Get event statistics
        $postsStats = [
            'total' => $this->postRepository->countByEvent($event),
            'submitted' => $this->postRepository->countByEventAndStatus($event, \App\Domain\Participation\PostStatus::SUBMITTED),
            'approved' => $this->postRepository->countByEventAndStatus($event, \App\Domain\Participation\PostStatus::APPROVED),
            'rejected' => $this->postRepository->countByEventAndStatus($event, \App\Domain\Participation\PostStatus::REJECTED),
        ];

        return $this->render('backoffice/events/detail.html.twig', [
            'event' => $event,
            'postsStats' => $postsStats,
        ]);
    }

    #[Route('/{slug}/activate', name: 'backoffice_events_activate', methods: ['POST'])]
    public function activate(Request $request, string $slug): Response
    {
        $event = $this->eventRepository->findOneBySlug($slug);
        
        if (!$event) {
            throw $this->createNotFoundException('Event nicht gefunden');
        }

        if (!$this->isCsrfTokenValid('activate'.$event->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Ungültiger CSRF-Token.');
            return $this->redirectToRoute('backoffice_events_detail', ['slug' => $event->getSlug()]);
        }

        try {
            $event->activate();
            $this->entityManager->flush();
            
            $this->addFlash('success', sprintf('Event "%s" wurde aktiviert.', $event->getName()));
        } catch (\Exception $e) {
            $this->addFlash('error', 'Fehler beim Aktivieren: ' . $e->getMessage());
        }

        return $this->redirectToRoute('backoffice_events_detail', ['slug' => $event->getSlug()]);
    }

    #[Route('/{slug}/close', name: 'backoffice_events_close', methods: ['POST'])]
    public function close(Request $request, string $slug): Response
    {
        $event = $this->eventRepository->findOneBySlug($slug);
        
        if (!$event) {
            throw $this->createNotFoundException('Event nicht gefunden');
        }

        if (!$this->isCsrfTokenValid('close'.$event->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Ungültiger CSRF-Token.');
            return $this->redirectToRoute('backoffice_events_detail', ['slug' => $event->getSlug()]);
        }

        try {
            $event->close();
            $this->entityManager->flush();
            
            $this->addFlash('success', sprintf('Event "%s" wurde geschlossen.', $event->getName()));
        } catch (\Exception $e) {
            $this->addFlash('error', 'Fehler beim Schließen: ' . $e->getMessage());
        }

        return $this->redirectToRoute('backoffice_events_detail', ['slug' => $event->getSlug()]);
    }

    #[Route('/{slug}/archive', name: 'backoffice_events_archive', methods: ['POST'])]
    public function archive(Request $request, string $slug): Response
    {
        $event = $this->eventRepository->findOneBySlug($slug);
        
        if (!$event) {
            throw $this->createNotFoundException('Event nicht gefunden');
        }

        if (!$this->isCsrfTokenValid('archive'.$event->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Ungültiger CSRF-Token.');
            return $this->redirectToRoute('backoffice_events_detail', ['slug' => $event->getSlug()]);
        }

        try {
            $event->archive();
            $this->entityManager->flush();
            
            $this->addFlash('success', sprintf('Event "%s" wurde archiviert.', $event->getName()));
        } catch (\Exception $e) {
            $this->addFlash('error', 'Fehler beim Archivieren: ' . $e->getMessage());
        }

        return $this->redirectToRoute('backoffice_events_detail', ['slug' => $event->getSlug()]);
    }
}