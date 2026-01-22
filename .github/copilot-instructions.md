# TopicMatcher Copilot Instructions

## Project Overview
TopicMatcher is a **mobile-first conference/trade show networking MVP** where visitors post "I'm looking for / I offer" entries moderated by backoffice users. This is a **Symfony 7.4 LTS monolith** following **Domain-Driven Design (DDD)** principles.

## Architecture & Domain Design

### Bounded Contexts (Critical Understanding)
- **`src/Domain/EventManagement/`** - Event lifecycle (Draft→Active→Closed→Archived) with strict business rules
- **`src/Domain/Participation/`** - User posts, categories, and interest declarations  
- **`src/Domain/Backoffice/`** - Admin/Moderator authentication with role-based permissions

### Key Business Rules (Encoded in Domain)
- **Draft Events are ALWAYS empty** (no posts/interests) - see `Event::isDraftAndEmpty()`
- **Event Status controls all capabilities** - check `EventStatus` enum methods before implementing features
- **Duplicate Interest Prevention** - unique constraint on `(post_id, email)` in interests table
- **Email Privacy** - emails are NEVER publicly visible, only names can be shown if moderated
- **GDPR Compliance** - privacy acceptance required for all submissions

## Development Environment

### Docker-First Development
```bash
# Start environment
docker-compose up -d

# Run commands in container (all development happens inside)
docker-compose exec app php bin/console [command]
docker-compose exec app composer [command]
```

### Key Services
- **App**: http://localhost:8080 (Nginx + PHP-FPM)
- **Database**: MariaDB 10.11 on port 3306
- **phpMyAdmin**: http://localhost:8081

## Critical Patterns & Conventions

### Domain Entity Location
Entities live in `src/Domain/*` (NOT `src/Entity`). Doctrine mapping configured to scan Domain folder:
```yaml
# config/packages/doctrine.yaml
mappings:
    App:
        dir: '%kernel.project_dir%/src/Domain'
        prefix: 'App\Domain'
```

### Enums Drive Business Logic
All status management uses PHP 8.2+ enums with behavior methods:
- `EventStatus` - controls what operations are allowed per event state
- `PostStatus` - manages moderation workflow (submitted→approved/rejected)
- `UserRole` - defines backoffice permissions (admin vs moderator)

### Domain-Centric Validation
Business rules are enforced in Domain entities, not just at form level:
```php
// Example: Events control their own state transitions
public function activate(): void {
    if ($this->status === EventStatus::DRAFT) {
        $this->status = EventStatus::ACTIVE;
        $this->touch();
    }
}
```

### Privacy & GDPR Patterns
- Use `showAuthorName` boolean flag to control name visibility
- Store IP address and user agent for GDPR audit trails
- Implement cascade deletes for data cleanup (see Foreign Key constraints)

## Testing & Quality

### Run Tests
```bash
docker-compose exec app php bin/phpunit
```

### Schema Validation
```bash
docker-compose exec app php bin/console doctrine:schema:validate
docker-compose exec app php bin/console doctrine:mapping:info
```

## Key Files to Understand First
1. **`README.md`** - Complete MVP specification with User Stories
2. **`src/Domain/EventManagement/Event.php`** - Core domain aggregate
3. **`src/Domain/EventManagement/EventStatus.php`** - Business rule implementation
4. **`migrations/Version20260122071747.php`** - Complete database schema
5. **`.github/instructions/symfonyCoder.instructions.md`** - Symfony best practices (MUST follow these coding standards)

## Coding Standards & Best Practices
**CRITICAL**: Always follow the Symfony development standards defined in [`.github/instructions/symfonyCoder.instructions.md`](.github/instructions/symfonyCoder.instructions.md). Key points:
- Use attribute-based configuration for entities and routes
- Keep controllers thin - business logic belongs in Domain entities
- Use dependency injection exclusively, prefer constructor injection
- Validate data at application boundaries using Symfony Validator
- Use YAML for service configuration
- Follow Symfony naming conventions (snake_case for templates, etc.)
- Prefer single firewall for security unless multiple systems required

## Mobile-First Frontend
- Bootstrap 5 via Symfony AssetMapper (imported in `assets/app.js`)
- Server-side rendered Twig templates
- No JavaScript framework - keep it simple for MVP

When implementing new features, always check the event status and user permissions first, then follow the existing Domain patterns for state management and privacy controls.