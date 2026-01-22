<?php

declare(strict_types=1);

namespace App\Controller\Backoffice;

use App\Application\Backoffice\Query\ModerationQueryService;
use App\Domain\Participation\PostStatus;
use App\Infrastructure\Repository\PostRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/moderation')]
#[IsGranted('ROLE_MODERATOR')]
class ModerationController extends AbstractController
{
    public function __construct(
        private readonly ModerationQueryService $moderationQueryService,
        private readonly PostRepository $postRepository,
        private readonly EntityManagerInterface $entityManager
    ) {
    }

    #[Route('/', name: 'backoffice_moderation_index')]
    public function index(Request $request): Response
    {
        $status = $request->query->get('status', 'submitted');
        $postStatus = PostStatus::tryFrom($status) ?? PostStatus::SUBMITTED;
        
        $posts = $this->postRepository->findByStatus($postStatus);

        return $this->render('backoffice/moderation/index.html.twig', [
            'posts' => $posts,
            'currentStatus' => $postStatus,
            'stats' => $this->moderationQueryService->getDashboardStats(),
        ]);
    }

    #[Route('/post/{id}', name: 'backoffice_moderation_post_detail', requirements: ['id' => '\d+'])]
    public function postDetail(int $id): Response
    {
        $post = $this->postRepository->find($id);
        
        if (!$post) {
            throw $this->createNotFoundException('Post nicht gefunden');
        }

        return $this->render('backoffice/moderation/post_detail.html.twig', [
            'post' => $post,
        ]);
    }

    #[Route('/post/{id}/approve', name: 'backoffice_moderation_approve', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function approvePost(int $id, Request $request): Response
    {
        $post = $this->postRepository->find($id);
        
        if (!$post) {
            throw $this->createNotFoundException('Post nicht gefunden');
        }

        if (!$post->getStatus()->canBeModerated()) {
            $this->addFlash('error', 'Post kann in seinem aktuellen Status nicht moderiert werden.');
            return $this->redirectToRoute('backoffice_moderation_post_detail', ['id' => $id]);
        }

        try {
            $post->approve();
            $this->entityManager->flush();
            
            $this->addFlash('success', sprintf('Post "%s" wurde erfolgreich freigegeben.', $post->getTitle()));
        } catch (\Exception $e) {
            $this->addFlash('error', 'Fehler beim Freigeben des Posts: ' . $e->getMessage());
        }

        // Redirect based on referer or default to moderation index
        $referer = $request->headers->get('referer');
        if ($referer && str_contains($referer, 'moderation')) {
            return $this->redirect($referer);
        }

        return $this->redirectToRoute('backoffice_moderation_index');
    }

    #[Route('/post/{id}/reject', name: 'backoffice_moderation_reject', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function rejectPost(int $id, Request $request): Response
    {
        $post = $this->postRepository->find($id);
        
        if (!$post) {
            throw $this->createNotFoundException('Post nicht gefunden');
        }

        if (!$post->getStatus()->canBeModerated()) {
            $this->addFlash('error', 'Post kann in seinem aktuellen Status nicht moderiert werden.');
            return $this->redirectToRoute('backoffice_moderation_post_detail', ['id' => $id]);
        }

        try {
            $post->reject();
            $this->entityManager->flush();
            
            $this->addFlash('warning', sprintf('Post "%s" wurde abgelehnt.', $post->getTitle()));
        } catch (\Exception $e) {
            $this->addFlash('error', 'Fehler beim Ablehnen des Posts: ' . $e->getMessage());
        }

        // Redirect based on referer or default to moderation index
        $referer = $request->headers->get('referer');
        if ($referer && str_contains($referer, 'moderation')) {
            return $this->redirect($referer);
        }

        return $this->redirectToRoute('backoffice_moderation_index');
    }
}