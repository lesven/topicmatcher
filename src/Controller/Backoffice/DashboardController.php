<?php

declare(strict_types=1);

namespace App\Controller\Backoffice;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin')]
#[IsGranted('ROLE_MODERATOR')]
class DashboardController extends AbstractController
{
    #[Route('/', name: 'backoffice_dashboard')]
    public function index(): Response
    {
        return $this->render('backoffice/dashboard/index.html.twig', [
            'user' => $this->getUser(),
        ]);
    }
}