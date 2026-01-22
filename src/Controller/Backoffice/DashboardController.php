<?php

declare(strict_types=1);

namespace App\Controller\Backoffice;

use App\Application\Backoffice\Query\ModerationQueryService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin')]
#[IsGranted('ROLE_MODERATOR')]
class DashboardController extends AbstractController
{
    /**
     * Constructor - injects ModerationQueryService.
     *
     * @param ModerationQueryService $moderationQueryService Service für Moderationsstatistiken
     */
    public function __construct(
        private readonly ModerationQueryService $moderationQueryService
    ) {
    }

    #[Route('/', name: 'backoffice_dashboard')]
    /**
     * Dashboard-Übersicht für Moderatoren/Admins.
     *
     * @return Response Dashboard-Template mit Statistiken und Aktivitäten
     */
    public function index(): Response
    {
        $stats = $this->moderationQueryService->getDashboardStats();
        $recentActivities = $this->moderationQueryService->getRecentModerationActivity();
        $pendingPosts = $this->moderationQueryService->getPendingPosts(5);

        return $this->render('backoffice/dashboard.html.twig', [
            'user' => $this->getUser(),
            'stats' => $stats,
            'recentActivities' => $recentActivities,
            'pendingPosts' => $pendingPosts,
        ]);
    }
}