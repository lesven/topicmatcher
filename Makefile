# TopicMatcher Development Makefile

.PHONY: help deploy up down logs test clean install

# Default target
help:
	@echo "TopicMatcher Development Commands:"
	@echo ""
	@echo "  make deploy    - Deploy application (build, start containers, install dependencies, run migrations)"
	@echo "  make up        - Start all containers"
	@echo "  make down      - Stop all containers"
	@echo "  make logs      - Show logs from all containers"
	@echo "  make test      - Run PHPUnit tests"
	@echo "  make install   - Install composer dependencies"
	@echo "  make clean     - Clean up containers and volumes"
	@echo "  make help      - Show this help message"
	@echo ""

# Deploy application (complete setup)
deploy:
	@echo "🚀 Deploying TopicMatcher application..."
	docker-compose build --no-cache
	docker-compose up -d
	@echo "⏳ Waiting for containers to be ready..."
	sleep 10
	docker-compose exec app composer install --no-dev --optimize-autoloader
	docker-compose exec app php bin/console doctrine:migrations:migrate --no-interaction
	docker-compose exec app php bin/console cache:clear --env=prod
	@echo "✅ Application deployed successfully!"
	@echo "🌐 Application: http://localhost:8080"
	@echo "🗄️  phpMyAdmin: http://localhost:8081"

# Start containers
up:
	@echo "🔄 Starting containers..."
	docker-compose up -d
	@echo "✅ Containers started!"
	@echo "🌐 Application: http://localhost:8080"
	@echo "🗄️  phpMyAdmin: http://localhost:8081"

# Stop containers
down:
	@echo "🛑 Stopping containers..."
	docker-compose down
	@echo "✅ Containers stopped!"

# Show logs
logs:
	@echo "📋 Showing container logs..."
	docker-compose logs -f

# Show logs for specific service
logs-app:
	docker-compose logs -f app

logs-web:
	docker-compose logs -f webserver

logs-db:
	docker-compose logs -f db

# Run tests
test:
	@echo "🧪 Running tests..."
	docker-compose exec app php bin/phpunit

# Install dependencies
install:
	@echo "📦 Installing dependencies..."
	docker-compose exec app composer install

# Clean up
clean:
	@echo "🧹 Cleaning up containers and volumes..."
	docker-compose down -v
	docker system prune -f
	@echo "✅ Cleanup completed!"

# Development helpers
shell:
	@echo "🐚 Opening shell in app container..."
	docker-compose exec app bash

console:
	@echo "⚡ Opening Symfony console..."
	docker-compose exec app php bin/console

cache-clear:
	@echo "🗑️  Clearing cache..."
	docker-compose exec app php bin/console cache:clear

migration:
	@echo "📝 Creating new migration..."
	docker-compose exec app php bin/console make:migration

migrate:
	@echo "⬆️  Running migrations..."
	docker-compose exec app php bin/console doctrine:migrations:migrate --no-interaction

fixtures:
	@echo "🎯 Loading fixtures..."
	docker-compose exec app php bin/console doctrine:fixtures:load --no-interaction