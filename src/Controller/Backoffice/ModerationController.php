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
    /**
     * Constructor - injects moderation service, post repository and entity manager.
     *
     * @param ModerationQueryService $moderationQueryService Service für Moderationsstatistiken
     * @param PostRepository $postRepository Repository für Posts
     * @param EntityManagerInterface $entityManager Entity Manager
     */
    public function __construct(
        private readonly ModerationQueryService $moderationQueryService,
        private readonly PostRepository $postRepository,
        private readonly EntityManagerInterface $entityManager
    ) {
    }

    #[Route('/', name: 'backoffice_moderation_index')]
    /**
     * Moderationsübersicht mit optionalem Statusfilter.
     *
     * @param Request $request Optionaler Status als Query-Parameter
     * @return Response Seite mit Posts und Stats
     */
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
    /**
     * Zeigt Detailansicht eines zu moderierenden Posts.
     *
     * @param int $id Post-ID
     * @return Response Detailseite des Posts
     */
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
    /**
     * Genehmigt einen Post (POST, prüft Moderierbarkeit und CSRF).
     *
     * @param int $id Post-ID
     * @param Request $request Request-Objekt
     * @return Response Redirect basierend auf Referer oder zur Moderationsübersicht
     */
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
            $post->approve($this->getUser()->getEmail());
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
    /**
     * Lehnt einen Post ab (POST, prüft Moderierbarkeit und CSRF).
     *
     * @param int $id Post-ID
     * @param Request $request Request-Objekt
     * @return Response Redirect basierend auf Referer oder zur Moderationsübersicht
     */
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
            $post->reject($this->getUser()->getEmail());
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