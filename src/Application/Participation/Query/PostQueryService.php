<?php

declare(strict_types=1);

namespace App\Application\Participation\Query;

use App\Domain\Participation\Post;
use App\Domain\Participation\PostStatus;
use App\Domain\EventManagement\Event;
use App\Infrastructure\Repository\PostRepository;

readonly class PostQueryService
{
    public function __construct(
        private PostRepository $postRepository
    ) {
    }

    /**
     * @return Post[]
     */
    public function findApprovedByEvent(Event $event): array
    {
        return $this->postRepository->findApprovedByEvent($event);
    }

    /**
     * @return Post[]
     */
    public function findSubmittedForModeration(): array
    {
        return $this->postRepository->findSubmittedForModeration();
    }

    /**
     * @return Post[]
     */
    public function findByEventAndStatus(Event $event, PostStatus $status): array
    {
        return $this->postRepository->findByEventAndStatus($event, $status);
    }

    public function countByEventAndStatus(Event $event, PostStatus $status): int
    {
        return $this->postRepository->countByEventAndStatus($event, $status);
    }

    /**
     * Group approved posts by category for display
     */
    public function getApprovedPostsGroupedByCategory(Event $event): array
    {
        $posts = $this->findApprovedByEvent($event);
        $groupedPosts = [];

        foreach ($posts as $post) {
            $categoryName = $post->getCategory()->getName();
            if (!isset($groupedPosts[$categoryName])) {
                $groupedPosts[$categoryName] = [
                    'category' => $post->getCategory(),
                    'posts' => []
                ];
            }
            $groupedPosts[$categoryName]['posts'][] = $post;
        }

        return $groupedPosts;
    }
}