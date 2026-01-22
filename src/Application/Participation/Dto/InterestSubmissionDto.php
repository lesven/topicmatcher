<?php

declare(strict_types=1);

namespace App\Application\Participation\Dto;

use Symfony\Component\Validator\Constraints as Assert;

class InterestSubmissionDto
{
    #[Assert\NotBlank(message: 'Name ist erforderlich')]
    #[Assert\Length(max: 100, maxMessage: 'Name darf maximal {{ limit }} Zeichen lang sein')]
    public string $name = '';

    #[Assert\NotBlank(message: 'E-Mail ist erforderlich')]
    #[Assert\Email(message: 'Bitte geben Sie eine gültige E-Mail-Adresse ein')]
    #[Assert\Length(max: 255, maxMessage: 'E-Mail darf maximal {{ limit }} Zeichen lang sein')]
    public string $email = '';

    #[Assert\IsTrue(message: 'Die Datenschutzbestimmungen müssen akzeptiert werden')]
    public bool $privacyAccepted = false;
}