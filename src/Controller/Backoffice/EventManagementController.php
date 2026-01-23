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
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/events')]
#[IsGranted('ROLE_ADMIN')]
/**
 * Controller to manage Events in the backoffice (CRUD + lifecycle actions).
 */
class EventManagementController extends AbstractController
{
    /**
     * Konstruktor – injiziert Repositories und den EntityManager.
     *
     * @param EventRepository $eventRepository Event-Repository
     * @param PostRepository $postRepository Post-Repository
     * @param EntityManagerInterface $entityManager Entity Manager
     */
    public function __construct(
        private readonly EventRepository $eventRepository,
        private readonly PostRepository $postRepository,
        private readonly EntityManagerInterface $entityManager
    ) {
    }

    #[Route('/', name: 'backoffice_events_index')]
    /**
     * Zeigt die Event-Übersicht mit optionalem Statusfilter.
     *
     * @param Request $request Request mit optionalem Query-Parameter "status"
     * @return Response Seite mit Eventliste und Statistiken
     */
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
    /**
     * Erstellt ein neues Event (Formular GET/POST).
     *
     * @param Request $request Request mit Formulardaten
     * @return Response Rendered Template oder Redirect nach Erstellung
     */
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
    /**
     * Zeigt Detailansicht eines Events.
     *
     * @param string $slug Event-Slug
     * @return Response Detailseite des Events
     */
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

    #[Route('/{slug}/posts', name: 'backoffice_events_posts')]
    /**
     * Zeigt Posts eines Events mit optionalem Statusfilter.
     *
     * @param string $slug Event-Slug
     * @param Request $request Optionaler Statusfilter als Query-Parameter
     * @return Response Seite mit den Posts
     */
    public function posts(string $slug, Request $request): Response
    {
        $event = $this->eventRepository->findOneBySlug($slug);
        
        if (!$event) {
            throw $this->createNotFoundException('Event nicht gefunden');
        }

        $status = $request->query->get('status');
        $statusFilter = $status ? \App\Domain\Participation\PostStatus::tryFrom($status) : null;

        if ($statusFilter) {
            $posts = $this->postRepository->findByEventAndStatus($event, $statusFilter);
        } else {
            $posts = $this->postRepository->findByEvent($event);
        }

        // Get post statistics for this event
        $postsStats = [
            'total' => $this->postRepository->countByEvent($event),
            'submitted' => $this->postRepository->countByEventAndStatus($event, \App\Domain\Participation\PostStatus::SUBMITTED),
            'approved' => $this->postRepository->countByEventAndStatus($event, \App\Domain\Participation\PostStatus::APPROVED),
            'rejected' => $this->postRepository->countByEventAndStatus($event, \App\Domain\Participation\PostStatus::REJECTED),
        ];

        return $this->render('backoffice/events/posts.html.twig', [
            'event' => $event,
            'posts' => $posts,
            'stats' => $postsStats,
            'currentStatus' => $status,
        ]);
    }

    #[Route('/{slug}/activate', name: 'backoffice_events_activate', methods: ['POST'])]
    /**
     * Aktiviert ein Event (POST, CSRF-geschützt).
     *
     * @param Request $request Request mit CSRF-Token
     * @param string $slug Event-Slug
     * @return Response Redirect zur Event-Detailseite
     */
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
    /**
     * Schließt ein aktives Event (POST).
     *
     * @param Request $request Request mit CSRF-Token
     * @param string $slug Event-Slug
     * @return Response Redirect zur Event-Detailseite
     */
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
    /**
     * Archiviert ein geschlossenes Event (POST).
     *
     * @param Request $request Request mit CSRF-Token
     * @param string $slug Event-Slug
     * @return Response Redirect zur Event-Detailseite
     */
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

    #[Route('/bulk-actions', name: 'backoffice_events_bulk_actions', methods: ['POST'])]
    /**
     * Führt Bulk-Aktionen auf mehreren Events aus (aktivieren, schließen, archivieren, löschen).
     *
     * @param Request $request POST-Request mit 'action' und 'eventIds'
     * @return JsonResponse Ergebnis der Aktion
     */
    public function bulkActions(Request $request): JsonResponse
    {
        $action = $request->request->get('action');
        $eventIds = $request->request->all('eventIds');
        
        if (!$this->isCsrfTokenValid('bulk_actions', $request->request->get('_token'))) {
            return new JsonResponse(['success' => false, 'message' => 'Ungültiger CSRF-Token.'], 400);
        }
        
        if (empty($eventIds) || !is_array($eventIds)) {
            return new JsonResponse(['success' => false, 'message' => 'Keine Events ausgewählt.'], 400);
        }
        
        $events = $this->eventRepository->findBy(['id' => $eventIds]);
        
        if (count($events) !== count($eventIds)) {
            return new JsonResponse(['success' => false, 'message' => 'Einige Events wurden nicht gefunden.'], 400);
        }
        
        try {
            $successCount = 0;
            $errors = [];
            
            switch ($action) {
                case 'activate':
                    foreach ($events as $event) {
                        try {
                            $event->activate();
                            $successCount++;
                        } catch (\Exception $e) {
                            $errors[] = sprintf('Event "%s": %s', $event->getName(), $e->getMessage());
                        }
                    }
                    break;
                    
                case 'close':
                    foreach ($events as $event) {
                        try {
                            $event->close();
                            $successCount++;
                        } catch (\Exception $e) {
                            $errors[] = sprintf('Event "%s": %s', $event->getName(), $e->getMessage());
                        }
                    }
                    break;
                    
                case 'archive':
                    foreach ($events as $event) {
                        try {
                            $event->archive();
                            $successCount++;
                        } catch (\Exception $e) {
                            $errors[] = sprintf('Event "%s": %s', $event->getName(), $e->getMessage());
                        }
                    }
                    break;
                    
                case 'delete':
                    foreach ($events as $event) {
                        try {
                            // Check if event can be deleted (must be draft and empty)
                            if (!$event->isDraftAndEmpty()) {
                                $errors[] = sprintf('Event "%s" kann nicht gelöscht werden (nicht leer oder nicht im Draft-Status).', $event->getName());
                                continue;
                            }
                            
                            $this->entityManager->remove($event);
                            $successCount++;
                        } catch (\Exception $e) {
                            $errors[] = sprintf('Event "%s": %s', $event->getName(), $e->getMessage());
                        }
                    }
                    break;
                    
                default:
                    return new JsonResponse(['success' => false, 'message' => 'Unbekannte Aktion.'], 400);
            }
            
            $this->entityManager->flush();
            
            $message = sprintf('%d Events erfolgreich bearbeitet.', $successCount);
            if (!empty($errors)) {
                $message .= ' Fehler: ' . implode(', ', $errors);
            }
            
            return new JsonResponse([
                'success' => true, 
                'message' => $message,
                'successCount' => $successCount,
                'errorCount' => count($errors)
            ]);
            
        } catch (\Exception $e) {
            return new JsonResponse(['success' => false, 'message' => 'Fehler beim Verarbeiten: ' . $e->getMessage()], 500);
        }
    }

    #[Route('/templates', name: 'backoffice_events_templates', methods: ['GET'])]
    /**
     * Listet Templates und reguläre Events für die Template-Verwaltung.
     *
     * @return Response Template-Übersicht
     */
    public function templates(): Response
    {
        $templates = $this->eventRepository->findTemplates();
        $regularEvents = $this->eventRepository->findNonTemplates();
        
        return $this->render('backoffice/events/templates.html.twig', [
            'templates' => $templates,
            'regularEvents' => $regularEvents,
        ]);
    }

    #[Route('/{slug}/duplicate', name: 'backoffice_events_duplicate', methods: ['GET', 'POST'])]
    /**
     * Dupliziert ein Event (mit optionaler Kopie der Kategorien) oder erstellt ein Template.
     *
     * @param Request $request Request mit Duplikationsdaten
     * @param string $slug Slug des Quell-Events
     * @return Response Rendered Template oder Redirect nach Duplikation
     */
    public function duplicate(Request $request, string $slug): Response
    {
        $sourceEvent = $this->eventRepository->findOneBySlug($slug);
        if (!$sourceEvent) {
            throw $this->createNotFoundException('Event nicht gefunden.');
        }

        if ($request->isMethod('POST')) {
            try {
                $newName = trim($request->request->get('name', ''));
                $copyCategories = (bool) $request->request->get('copyCategories', false);
                $makeTemplate = (bool) $request->request->get('makeTemplate', false);

                if (empty($newName)) {
                    throw new \Exception('Name ist erforderlich.');
                }

                // Generate unique slug
                $baseSlug = strtolower(preg_replace('/[^A-Za-z0-9]+/', '-', $newName));
                $baseSlug = trim($baseSlug, '-');
                $newSlug = $this->eventRepository->generateUniqueSlug($baseSlug);

                // Create duplicate
                $newEvent = $sourceEvent->createDuplicate($newName, $newSlug, $copyCategories);
                
                if ($makeTemplate) {
                    $newEvent->setTemplate(true);
                }

                $this->entityManager->persist($newEvent);
                $this->entityManager->flush();

                $this->addFlash('success', sprintf('Event "%s" wurde erfolgreich %s erstellt.', $newName, $makeTemplate ? 'als Template' : 'dupliziert'));
                
                return $this->redirectToRoute('backoffice_events_detail', ['slug' => $newEvent->getSlug()]);
                
            } catch (\Exception $e) {
                $this->addFlash('error', 'Fehler beim Duplizieren: ' . $e->getMessage());
            }
        }

        return $this->render('backoffice/events/duplicate.html.twig', [
            'sourceEvent' => $sourceEvent,
        ]);
    }

    #[Route('/{slug}/toggle-template', name: 'backoffice_events_toggle_template', methods: ['POST'])]
    /**
     * Schaltet das Template-Flag eines Events um (POST).
     *
     * @param Request $request Request mit CSRF-Token
     * @param string $slug Event-Slug
     * @return Response Redirect zur Event-Detailseite
     */
    public function toggleTemplate(Request $request, string $slug): Response
    {
        $event = $this->eventRepository->findOneBySlug($slug);
        if (!$event) {
            throw $this->createNotFoundException('Event nicht gefunden.');
        }

        if (!$this->isCsrfTokenValid('toggle_template'.$event->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Ungültiger CSRF-Token.');
            return $this->redirectToRoute('backoffice_events_detail', ['slug' => $event->getSlug()]);
        }

        try {
            $event->setTemplate(!$event->isTemplate());
            $this->entityManager->flush();
            
            $message = $event->isTemplate() 
                ? sprintf('Event "%s" wurde als Template markiert.', $event->getName())
                : sprintf('Event "%s" ist kein Template mehr.', $event->getName());
                
            $this->addFlash('success', $message);
            
        } catch (\Exception $e) {
            $this->addFlash('error', 'Fehler beim Ändern des Template-Status: ' . $e->getMessage());
        }

        return $this->redirectToRoute('backoffice_events_detail', ['slug' => $event->getSlug()]);
    }
}