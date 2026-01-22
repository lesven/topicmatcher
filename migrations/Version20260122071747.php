<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260122071747 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Initial TopicMatcher Domain Entities Setup';
    }

    public function up(Schema $schema): void
    {
        // Create Domain Entities for TopicMatcher MVP
        
        // Events table - Core entity for event lifecycle management
        $this->addSql('CREATE TABLE events (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(100) NOT NULL, description LONGTEXT DEFAULT NULL, slug VARCHAR(50) NOT NULL, status VARCHAR(20) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, UNIQUE INDEX UNIQ_5387574A989D9B62 (slug), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        
        // Categories table - 2-5 categories per event
        $this->addSql('CREATE TABLE categories (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(100) NOT NULL, description VARCHAR(500) DEFAULT NULL, color VARCHAR(7) DEFAULT NULL, sort_order INT NOT NULL, created_at DATETIME NOT NULL, event_id INT NOT NULL, INDEX IDX_3AF3466871F7E88B (event_id), UNIQUE INDEX unique_category_per_event (event_id, name), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        
        // Posts table - User submissions with moderation
        $this->addSql('CREATE TABLE posts (id INT AUTO_INCREMENT NOT NULL, content LONGTEXT NOT NULL, author_name VARCHAR(100) NOT NULL, author_email VARCHAR(255) NOT NULL, show_author_name TINYINT NOT NULL, privacy_accepted TINYINT NOT NULL, status VARCHAR(20) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, moderated_at DATETIME DEFAULT NULL, moderated_by VARCHAR(100) DEFAULT NULL, moderation_notes LONGTEXT DEFAULT NULL, category_id INT NOT NULL, INDEX IDX_885DBAFA12469DE2 (category_id), INDEX idx_posts_status (status), INDEX idx_posts_created_at (created_at), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        
        // Interests table - Interest declarations with duplicate prevention
        $this->addSql('CREATE TABLE interests (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(100) NOT NULL, email VARCHAR(255) NOT NULL, privacy_accepted TINYINT NOT NULL, created_at DATETIME NOT NULL, ip_address VARCHAR(45) DEFAULT NULL, user_agent VARCHAR(500) DEFAULT NULL, post_id INT NOT NULL, INDEX IDX_C8B405EA4B89032C (post_id), INDEX idx_interests_created_at (created_at), UNIQUE INDEX unique_interest_per_post_email (post_id, email), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        
        // Backoffice Users table - Admin/Moderator authentication
        $this->addSql('CREATE TABLE backoffice_users (id INT AUTO_INCREMENT NOT NULL, email VARCHAR(255) NOT NULL, name VARCHAR(100) NOT NULL, password VARCHAR(255) NOT NULL, role VARCHAR(20) NOT NULL, is_active TINYINT NOT NULL, must_change_password TINYINT NOT NULL, created_at DATETIME NOT NULL, last_login_at DATETIME DEFAULT NULL, password_changed_at DATETIME DEFAULT NULL, UNIQUE INDEX unique_email (email), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        
        // Symfony Messenger
        $this->addSql('CREATE TABLE messenger_messages (id BIGINT AUTO_INCREMENT NOT NULL, body LONGTEXT NOT NULL, headers LONGTEXT NOT NULL, queue_name VARCHAR(190) NOT NULL, created_at DATETIME NOT NULL, available_at DATETIME NOT NULL, delivered_at DATETIME DEFAULT NULL, INDEX IDX_75EA56E0FB7336F0E3BD61CE16BA31DBBF396750 (queue_name, available_at, delivered_at, id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        
        // Foreign Key Constraints
        $this->addSql('ALTER TABLE categories ADD CONSTRAINT FK_3AF3466871F7E88B FOREIGN KEY (event_id) REFERENCES events (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE posts ADD CONSTRAINT FK_885DBAFA12469DE2 FOREIGN KEY (category_id) REFERENCES categories (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE interests ADD CONSTRAINT FK_C8B405EA4B89032C FOREIGN KEY (post_id) REFERENCES posts (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // Drop Foreign Key Constraints first
        $this->addSql('ALTER TABLE categories DROP FOREIGN KEY FK_3AF3466871F7E88B');
        $this->addSql('ALTER TABLE posts DROP FOREIGN KEY FK_885DBAFA12469DE2');
        $this->addSql('ALTER TABLE interests DROP FOREIGN KEY FK_C8B405EA4B89032C');
        
        // Drop Domain Tables
        $this->addSql('DROP TABLE interests');
        $this->addSql('DROP TABLE posts');
        $this->addSql('DROP TABLE categories');
        $this->addSql('DROP TABLE events');
        $this->addSql('DROP TABLE backoffice_users');
        
        // Drop Symfony Tables
        $this->addSql('DROP TABLE messenger_messages');
    }
}
