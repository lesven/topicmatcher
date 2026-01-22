<?php

declare(strict_types=1);

namespace App\Application\Participation\Command;

use App\Domain\Participation\Interest;
use App\Domain\Participation\Post;
use App\Infrastructure\Repository\InterestRepository;
use Symfony\Component\HttpFoundation\RequestStack;

readonly class InterestSubmissionService
{
    public function __construct(
        private InterestRepository $interestRepository,
        private RequestStack $requestStack
    ) {
    }

    public function submitInterest(
        Post $post,
        string $name,
        string $email,
        bool $privacyAccepted
    ): Interest {
        // Check for duplicates (business rule enforcement)
        if ($this->interestRepository->isDuplicateInterest($post, $email)) {
            throw new \InvalidArgumentException('Duplicate interest not allowed');
        }

        // Get request data for GDPR audit trail
        $request = $this->requestStack->getCurrentRequest();
        $ipAddress = $request?->getClientIp();
        $userAgent = $request?->headers->get('User-Agent');

        $interest = new Interest(
            $name,
            $email,
            $privacyAccepted,
            $ipAddress,
            $userAgent
        );

        $post->addInterest($interest);
        $this->interestRepository->save($interest);

        return $interest;
    }

    public function isDuplicateInterest(Post $post, string $email): bool
    {
        return $this->interestRepository->isDuplicateInterest($post, $email);
    }

    /**
     * @return Interest[]
     */
    public function getInterestsByPost(Post $post): array
    {
        return $this->interestRepository->findByPost($post);
    }

    public function getInterestCount(Post $post): int
    {
        return $this->interestRepository->countByPost($post);
    }
}