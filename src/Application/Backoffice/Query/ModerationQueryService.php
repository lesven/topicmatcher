<?php

declare(strict_types=1);

namespace App\Application\Backoffice\Query;

use App\Domain\Participation\PostStatus;
use App\Infrastructure\Repository\PostRepository;
use App\Infrastructure\Repository\InterestRepository;
use App\Infrastructure\Repository\EventRepository;

readonly class ModerationQueryService
{
    /**
     * ModerationQueryService constructor.
     *
     * @param PostRepository $postRepository
     * @param InterestRepository $interestRepository
     * @param EventRepository $eventRepository
     */
    public function __construct(
        private PostRepository $postRepository,
        private InterestRepository $interestRepository,
        private EventRepository $eventRepository
    ) {
    }

    /**
     * Gather moderation dashboard statistics.
     *
     * @return array<string,int> Associative array with counts for dashboard display
     */
    public function getDashboardStats(): array
    {
        return [
            'totalEvents' => $this->eventRepository->getTotalCount(),
            'totalPosts' => $this->postRepository->getTotalCount(),
            'pendingModeration' => $this->postRepository->getCountByStatus(PostStatus::SUBMITTED),
            'approvedPosts' => $this->postRepository->getCountByStatus(PostStatus::APPROVED),
            'rejectedPosts' => $this->postRepository->getCountByStatus(PostStatus::REJECTED),
            'totalInterests' => $this->interestRepository->getTotalCount(),
        ];
    }

    /**
     * Return a list of pending posts for moderation.
     *
     * @param int $limit Maximum number of posts to return
     * @return array List of pending posts
     */
    public function getPendingPosts(int $limit = 10): array
    {
        return $this->postRepository->findByStatus(PostStatus::SUBMITTED, $limit);
    }

    /**
     * Return recent moderation activity entries for the dashboard.
     *
     * Each entry contains keys: title, description, type, createdAt and post.
     *
     * @param int $limit Maximum number of activity entries to return
     * @return array List of activity arrays
     */
    public function getRecentModerationActivity(int $limit = 10): array
    {
        // Get recently moderated posts (approved/rejected)
        $recentModerated = $this->postRepository->getRecentlyModerated($limit);
        
        $activities = [];
        foreach ($recentModerated as $post) {
            $activities[] = [
                'title' => 'Beitrag moderiert: ' . $post->getTitle(),
                'description' => sprintf('Status: %s in Kategorie "%s"', 
                    $post->getStatus()->value, 
                    $post->getCategory()->getName()
                ),
                'type' => $post->getStatus()->value,
                'createdAt' => $post->getUpdatedAt() ?? $post->getCreatedAt(),
                'post' => $post
            ];
        }
        
        // Sort by date descending
        usort($activities, fn($a, $b) => $b['createdAt'] <=> $a['createdAt']);
        
        return array_slice($activities, 0, $limit);
    }
}