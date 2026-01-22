<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Domain\EventManagement\Event;
use App\Domain\EventManagement\EventStatus;
use App\Domain\Participation\Category;
use App\Domain\Participation\Post;
use App\Domain\Participation\PostStatus;
use App\Domain\Backoffice\BackofficeUser;
use App\Domain\Backoffice\UserRole;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
    public function __construct(
        private UserPasswordHasherInterface $passwordHasher
    ) {
    }

    public function load(ObjectManager $manager): void
    {
        // Backoffice Users
        $admin = new BackofficeUser('admin@topicmatcher.com', 'Admin User', UserRole::ADMIN);
        $admin->setPassword($this->passwordHasher->hashPassword($admin, 'admin123'));
        $manager->persist($admin);

        $moderator = new BackofficeUser('moderator@topicmatcher.com', 'Moderator User', UserRole::MODERATOR);
        $moderator->setPassword($this->passwordHasher->hashPassword($moderator, 'mod123'));
        $manager->persist($moderator);

        // Events first (needed for categories)
        $activeEvent = new Event(
            'Tech Conference 2025',
            'tech-conference-2025',
            'Die führende Technologiekonferenz des Jahres mit innovativen Trends und Networking-Möglichkeiten.',
            new \DateTime('2025-03-15'),
            'Berlin Congress Center'
        );
        $activeEvent->activate();
        $manager->persist($activeEvent);

        $draftEvent = new Event(
            'Future Summit 2025',
            'future-summit-2025',
            'Ein Blick in die Zukunft der Technologie und Innovation.',
            new \DateTime('2025-06-20'),
            'Hamburg Innovation Hub'
        );
        $manager->persist($draftEvent);

        // Categories (now with event reference)
        $categories = [
            new Category($activeEvent, 'Technologie', '#007bff', 'Technische Expertise und Innovationen'),
            new Category($activeEvent, 'Business', '#28a745', 'Geschäftsentwicklung und Strategien'),
            new Category($activeEvent, 'Design', '#dc3545', 'User Experience und Design'),
            new Category($activeEvent, 'Marketing', '#ffc107', 'Marketing und Kommunikation'),
            new Category($activeEvent, 'Networking', '#6f42c1', 'Persönliche und berufliche Kontakte'),
        ];

        foreach ($categories as $category) {
            $manager->persist($category);
        }

        // Flush vor Posts um IDs zu haben
        $manager->flush();

        // Sample Posts for Active Event
        $posts = [
            [
                'title' => 'Suche Frontend-Entwickler für React Projekt',
                'content' => 'Wir sind ein wachsendes Startup und suchen einen erfahrenen React-Entwickler für ein spannendes E-Commerce-Projekt. Erfahrung mit TypeScript und moderne Testing-Frameworks sind von Vorteil.',
                'authorName' => 'Sarah Mueller',
                'authorEmail' => 'sarah.mueller@example.com',
                'category' => $categories[0], // Technologie
            ],
            [
                'title' => 'Biete Beratung für Agile Transformation',
                'content' => 'Als zertifizierter Scrum Master und Agile Coach helfe ich Unternehmen beim Übergang zu agilen Arbeitsweisen. 10+ Jahre Erfahrung in verschiedenen Branchen.',
                'authorName' => 'Michael Schmidt',
                'authorEmail' => 'michael.schmidt@consulting.com',
                'category' => $categories[1], // Business
            ],
            [
                'title' => 'Suche UX Designer für Mobile App',
                'content' => 'Für unsere innovative Fitness-App suchen wir einen kreativen UX Designer. Du solltest Erfahrung mit User Research und Prototyping-Tools haben.',
                'authorName' => null, // Anonymous
                'authorEmail' => 'hr@fittech.com',
                'category' => $categories[2], // Design
            ],
            [
                'title' => 'Biete Marketing Automation Workshop',
                'content' => 'Lerne, wie du mit HubSpot und anderen Tools deine Marketing-Prozesse automatisierst. Praxisnaher Workshop mit konkreten Use Cases.',
                'authorName' => 'Lisa Weber',
                'authorEmail' => 'lisa.weber@marketing-pro.de',
                'category' => $categories[3], // Marketing
            ],
        ];

        foreach ($posts as $postData) {
            $post = new Post(
                $activeEvent,
                $postData['category'],
                $postData['title'],
                $postData['content'],
                $postData['authorName'],
                $postData['authorEmail'],
                $postData['authorName'] !== null, // showAuthorName
                '127.0.0.1',
                'Mozilla/5.0 Test Browser'
            );
            $post->approve('System'); // Auto-approve for demo
            $manager->persist($post);
        }

        $manager->flush();
    }
}