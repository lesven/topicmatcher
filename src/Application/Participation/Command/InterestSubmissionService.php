<?php

declare(strict_types=1);

namespace App\Application\Participation\Command;

use App\Domain\Participation\Interest;
use App\Domain\Participation\Post;
use App\Infrastructure\Repository\InterestRepository;
use Symfony\Component\HttpFoundation\RequestStack;

readonly class InterestSubmissionService
{
    /**
     * InterestSubmissionService constructor.
     *
     * @param InterestRepository $interestRepository
     * @param RequestStack $requestStack
     */
    public function __construct(
        private InterestRepository $interestRepository,
        private RequestStack $requestStack
    ) {
    }

    /**
     * Submit a new interest for a post.
     *
     * @param Post $post The post to attach the interest to
     * @param string $name Submitter name
     * @param string $email Submitter email
     * @param bool $privacyAccepted Whether privacy terms were accepted
     * @param string|null $message Optional message
     * @return Interest The created Interest entity
     * @throws \InvalidArgumentException When a duplicate interest is detected
     */
    public function submitInterest(
        Post $post,
        string $name,
        string $email,
        bool $privacyAccepted,
        ?string $message = null
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
            $message,
            $ipAddress,
            $userAgent
        );

        $post->addInterest($interest);
        $this->interestRepository->save($interest);

        return $interest;
    }

    /**
     * Check whether an interest with the same email already exists for the given post.
     *
     * @param Post $post The post to check
     * @param string $email The email to check for duplicates
     * @return bool True when a duplicate exists
     */
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

    /**
     * Get the number of interests for a given post.
     *
     * @param Post $post The post to count interests for
     * @return int The number of interests
     */
    public function getInterestCount(Post $post): int
    {
        return $this->interestRepository->countByPost($post);
    }
}