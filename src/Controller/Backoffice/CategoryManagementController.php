<?php

declare(strict_types=1);

namespace App\Controller\Backoffice;

use App\Domain\Participation\Category;
use App\Domain\EventManagement\Event;
use App\Form\Backoffice\CategoryType;
use App\Infrastructure\Repository\CategoryRepository;
use App\Infrastructure\Repository\EventRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Controller for managing categories within an event (create/edit/delete/reorder).
 */
#[Route('/admin/events/{slug}/categories')]
#[IsGranted('ROLE_ADMIN')]
class CategoryManagementController extends AbstractController
{
    /**
     * Konstruktor – injiziert die benötigten Repositories und den EntityManager für Kategorien.
     *
     * @param EventRepository $eventRepository Event-Repository
     * @param CategoryRepository $categoryRepository Category-Repository
     * @param EntityManagerInterface $entityManager Entity Manager
     */
    public function __construct(
        private readonly EventRepository $eventRepository,
        private readonly CategoryRepository $categoryRepository,
        private readonly EntityManagerInterface $entityManager
    ) {}

    #[Route('', name: 'backoffice_categories_index', methods: ['GET'])]
    /**
     * Listet Kategorien eines Events.
     *
     * @param string $slug Event-Slug
     * @return Response Seite mit Kategorien
     */
    public function index(string $slug): Response
    {
        $event = $this->getEventBySlug($slug);
        $categories = $this->categoryRepository->findByEvent($event);

        return $this->render('backoffice/categories/index.html.twig', [
            'event' => $event,
            'categories' => $categories,
        ]);
    }

    #[Route('/create', name: 'backoffice_categories_create', methods: ['GET', 'POST'])]
    /**
     * Erstellt eine neue Kategorie für ein Event (Formular GET/POST).
     *
     * @param Request $request Request mit Formulardaten
     * @param string $slug Event-Slug
     * @return Response Rendered Template oder Redirect bei Erfolg
     */
    public function create(Request $request, string $slug): Response
    {
        $event = $this->getEventBySlug($slug);
        
        // Create new category with default sort order
        $category = new Category(
            $event, 
            '', 
            '#3498db', // Default blue color
            null
        );
        $category->setSortOrder($this->categoryRepository->getNextSortOrder($event));

        $form = $this->createForm(CategoryType::class, $category);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Check for duplicate name within the event
            $existing = $this->categoryRepository->findByEventAndName($event, $category->getName());
            if ($existing) {
                $this->addFlash('error', 'Eine Kategorie mit diesem Namen existiert bereits in diesem Event.');
                return $this->render('backoffice/categories/create.html.twig', [
                    'form' => $form,
                    'event' => $event,
                ]);
            }

            $this->categoryRepository->save($category, true);
            
            $this->addFlash('success', sprintf('Kategorie "%s" wurde erfolgreich erstellt.', $category->getName()));
            return $this->redirectToRoute('backoffice_categories_index', ['slug' => $event->getSlug()]);
        }

        return $this->render('backoffice/categories/create.html.twig', [
            'form' => $form,
            'event' => $event,
        ]);
    }

    #[Route('/{id}/edit', name: 'backoffice_categories_edit', methods: ['GET', 'POST'])]
    /**
     * Bearbeitet eine bestehende Kategorie (Formular GET/POST).
     *
     * @param Request $request Request mit Formulardaten
     * @param string $slug Event-Slug
     * @param int $id Kategorie-ID
     * @return Response Rendered Template oder Redirect bei Erfolg
     */
    public function edit(Request $request, string $slug, int $id): Response
    {
        $event = $this->getEventBySlug($slug);
        $category = $this->getCategoryById($id, $event);

        $form = $this->createForm(CategoryType::class, $category);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Check for duplicate name within the event (excluding current category)
            $existing = $this->categoryRepository->findByEventAndName($event, $category->getName());
            if ($existing && $existing->getId() !== $category->getId()) {
                $this->addFlash('error', 'Eine Kategorie mit diesem Namen existiert bereits in diesem Event.');
                return $this->render('backoffice/categories/edit.html.twig', [
                    'form' => $form,
                    'category' => $category,
                    'event' => $event,
                ]);
            }

            $this->categoryRepository->save($category, true);
            
            $this->addFlash('success', sprintf('Kategorie "%s" wurde erfolgreich aktualisiert.', $category->getName()));
            return $this->redirectToRoute('backoffice_categories_index', ['slug' => $event->getSlug()]);
        }

        return $this->render('backoffice/categories/edit.html.twig', [
            'form' => $form,
            'category' => $category,
            'event' => $event,
        ]);
    }

    #[Route('/{id}/delete', name: 'backoffice_categories_delete', methods: ['POST'])]
    /**
     * Löscht eine Kategorie wenn keine genehmigten Posts vorhanden sind (POST, CSRF-geschützt).
     *
     * @param Request $request Request mit CSRF-Token
     * @param string $slug Event-Slug
     * @param int $id Kategorie-ID
     * @return Response Redirect zur Kategorie-Übersicht
     */
    public function delete(Request $request, string $slug, int $id): Response
    {
        $event = $this->getEventBySlug($slug);
        $category = $this->getCategoryById($id, $event);

        // Check if category has approved posts
        $approvedPostsCount = $category->getApprovedPostsCount();
        if ($approvedPostsCount > 0) {
            $this->addFlash('error', sprintf(
                'Die Kategorie "%s" kann nicht gelöscht werden, da sie %d genehmigte Beiträge enthält.',
                $category->getName(),
                $approvedPostsCount
            ));
            return $this->redirectToRoute('backoffice_categories_index', ['slug' => $event->getSlug()]);
        }

        $token = $request->request->get('_token');
        if ($this->isCsrfTokenValid('delete'.$category->getId(), $token)) {
            $this->categoryRepository->remove($category, true);
            
            $this->addFlash('success', sprintf('Kategorie "%s" wurde erfolgreich gelöscht.', $category->getName()));
        } else {
            $this->addFlash('error', 'Ungültiger CSRF-Token.');
        }

        return $this->redirectToRoute('backoffice_categories_index', ['slug' => $event->getSlug()]);
    }

    #[Route('/reorder', name: 'backoffice_categories_reorder', methods: ['POST'])]
    /**
     * Empfängt neue Sortierreihenfolge für Kategorien (JSON payload) und aktualisiert SortOrder.
     *
     * @param Request $request JSON-Request mit 'categoryOrder'
     * @param string $slug Event-Slug
     * @return Response JSON-Antwort
     */
    public function reorder(Request $request, string $slug): Response
    {
        $event = $this->getEventBySlug($slug);

        $data = json_decode($request->getContent(), true);
        if (!is_array($data) || !isset($data['categoryOrder'])) {
            return $this->json(['success' => false, 'error' => 'Invalid data'], 400);
        }

        try {
            $categoryOrder = $data['categoryOrder'];
            $sortOrderMap = [];
            
            foreach ($categoryOrder as $index => $categoryId) {
                $sortOrderMap[(int) $categoryId] = ($index + 1) * 10; // 10, 20, 30, ...
            }

            $this->categoryRepository->updateSortOrders($sortOrderMap);

            return $this->json(['success' => true]);
        } catch (\Exception $e) {
            return $this->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Hilfsfunktion: Liefert ein Event anhand des Slugs oder wirft 404.
     *
     * @param string $slug Event-Slug
     * @return Event Gefundenes Event
     */
    private function getEventBySlug(string $slug): Event
    {
        $event = $this->eventRepository->findOneBySlug($slug);
        if (!$event) {
            throw $this->createNotFoundException('Event nicht gefunden.');
        }

        return $event;
    }

    /**
     * Hilfsfunktion: Liefert eine Kategorie anhand der ID und prüft Zugehörigkeit zum Event.
     *
     * @param int $id Kategorie-ID
     * @param Event $event Zugehöriges Event
     * @return Category Gefundene Kategorie
     */
    private function getCategoryById(int $id, Event $event): Category
    {
        $category = $this->categoryRepository->find($id);
        if (!$category || $category->getEvent()->getId() !== $event->getId()) {
            throw $this->createNotFoundException('Kategorie nicht gefunden oder gehört nicht zu diesem Event.');
        }

        return $category;
    }
}