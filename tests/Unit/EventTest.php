<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Domain\EventManagement\Event;
use App\Domain\Participation\Category;
use App\Domain\Participation\Post;
use App\Domain\Participation\PostStatus;
use PHPUnit\Framework\TestCase;

class EventTest extends TestCase
{
    public function testIsDraftAndEmptyReturnsTrueWhenDraftAndNoCategories(): void
    {
        $event = new Event('Name', 'slug');
        // No categories added
        $this->assertTrue($event->isDraftAndEmpty());
    }

    public function testIsDraftAndEmptyReturnsTrueWhenDraftAndCategoriesButNoPosts(): void
    {
        $event = new Event('Name', 'slug');
        $category = new Category($event, 'Cat', '#ffffff');
        $event->addCategory($category);

        $this->assertTrue($event->isDraftAndEmpty());
    }

    public function testIsDraftAndEmptyReturnsFalseWhenCategoryHasPosts(): void
    {
        $event = new Event('Name', 'slug');
        $category = new Category($event, 'Cat', '#ffffff');
        $event->addCategory($category);

        $post = new Post(
            $event,
            $category,
            'Title',
            'Content',
            'Author',
            'author@example.com',
            true,
            '127.0.0.1',
            'UA'
        );

        // Associate post to category
        $category->addPost($post);

        $this->assertFalse($event->isDraftAndEmpty());
    }

    public function testIsDraftAndEmptyReturnsFalseWhenNotDraft(): void
    {
        $event = new Event('Name', 'slug');
        $event->activate();

        $this->assertFalse($event->isDraftAndEmpty());
    }
}
