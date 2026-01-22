<?php

declare(strict_types=1);

namespace App\Controller\Public;

use App\Application\EventManagement\Query\EventQueryService;
use App\Application\Participation\Query\PostQueryService;
use App\Application\Participation\Command\InterestSubmissionService;
use App\Application\Participation\Dto\InterestSubmissionDto;
use App\Domain\Participation\Post;
use App\Form\PostSubmissionType;
use App\Form\InterestSubmissionType;
use App\Infrastructure\Repository\PostRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class EventController extends AbstractController
{
    public function __construct(
        private readonly EventQueryService $eventQueryService,
        private readonly PostQueryService $postQueryService,
        private readonly InterestSubmissionService $interestService,
        private readonly EntityManagerInterface $entityManager,
        private readonly PostRepository $postRepository
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

    #[Route('/{slug}/create', name: 'post_create', requirements: ['slug' => '[a-z0-9\-]+'], methods: ['GET', 'POST'])]
    public function createPost(string $slug, Request $request): Response
    {
        $event = $this->eventQueryService->findBySlug($slug);
        
        if (!$event || !$event->getStatus()->allowsNewPosts()) {
            throw $this->createNotFoundException('Beitrag erstellen nicht möglich');
        }

        $form = $this->createForm(PostSubmissionType::class, null, [
            'event' => $event
        ]);
        $form->handleRequest($request);



        if ($form->isSubmitted()) {
            if (!$form->isValid()) {
                // Form has validation errors - they will be displayed in the template
            } else {
                try {
                    $data = $form->getData();
                    
                    $post = new Post(
                        $event,
                        $data['category'],
                        $data['title'],
                        $data['content'],
                        $data['authorName'],
                        $data['authorEmail'],
                        $data['showAuthorName'] ?? false,
                        $request->getClientIp() ?? '127.0.0.1',
                        $request->headers->get('User-Agent') ?? 'Unknown'
                    );

                    $this->entityManager->persist($post);
                    $this->entityManager->flush();
                    
                    return $this->redirectToRoute('post_create_success', ['slug' => $slug]);
                } catch (\Exception $e) {
                    $this->addFlash('error', 'Ein Fehler ist aufgetreten. Bitte versuchen Sie es erneut.');
                }
            }
        }

        return $this->render('public/post_create.html.twig', [
            'event' => $event,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{slug}/create/success', name: 'post_create_success', requirements: ['slug' => '[a-z0-9\-]+'])]
    public function createPostSuccess(string $slug): Response
    {
        $event = $this->eventQueryService->findBySlug($slug);
        
        if (!$event || !$event->isPubliclyVisible()) {
            throw $this->createNotFoundException('Event nicht gefunden');
        }

        return $this->render('public/post_create_success.html.twig', [
            'event' => $event,
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
        
        if (!$event || !$event->getStatus()->allowsInterests()) {
            throw $this->createNotFoundException('Interessenbekundung nicht möglich');
        }

        $post = $this->postRepository->find($id);
        if (!$post || $post->getEvent() !== $event || !$post->isApproved()) {
            throw $this->createNotFoundException('Beitrag nicht gefunden');
        }

        // Check if already interested
        $form = $this->createForm(InterestSubmissionType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $data = $form->getData();
                
                // Check for duplicate before submission
                if ($this->interestService->isDuplicateInterest($post, $data['email'])) {
                    $this->addFlash('warning', 'Sie haben bereits Interesse an diesem Beitrag bekundet.');
                } else {
                    $this->interestService->submitInterest(
                        $post,
                        $data['name'],
                        $data['email'],
                        $data['privacyAccepted'] ?? false,
                        $data['message'] ?? null
                    );
                    
                    return $this->redirectToRoute('post_interest_success', [
                        'slug' => $slug,
                        'id' => $id
                    ]);
                }
            } catch (\Exception $e) {
                $this->addFlash('error', 'Fehler beim Bekunden des Interesses: ' . $e->getMessage());
            }
        }

        return $this->render('public/post_interest.html.twig', [
            'event' => $event,
            'post' => $post,
            'form' => $form->createView(),
            'interestCount' => $this->interestService->getInterestCount($post)
        ]);
    }

    #[Route('/{slug}/post/{id}/interest/success', name: 'post_interest_success', requirements: ['slug' => '[a-z0-9\-]+', 'id' => '\d+'])]
    public function submitInterestSuccess(string $slug, int $id): Response
    {
        $event = $this->eventQueryService->findBySlug($slug);
        $post = $this->postRepository->find($id);
        
        if (!$event || !$post || $post->getEvent() !== $event) {
            throw $this->createNotFoundException('Beitrag nicht gefunden');
        }

        return $this->render('public/post_interest_success.html.twig', [
            'event' => $event,
            'post' => $post,
        ]);
    }
}