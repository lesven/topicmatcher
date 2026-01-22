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
deploy: down
	@echo "🚀 Deploying TopicMatcher application..."
	git pull || true
	docker-compose build --no-cache
	docker-compose up -d
	@echo "⏳ Waiting for containers to be ready..."
	sleep 15
	@echo "🔧 Setting up environment..."
	docker-compose exec app git config --global --add safe.directory /var/www || true
	docker-compose exec --user root app chown -R topicmatcher:topicmatcher /var/www || true
	docker-compose exec app mkdir -p /var/www/vendor /var/www/var/cache /var/www/var/log || true
	@echo "📦 Installing dependencies..."
	docker-compose exec app composer install --optimize-autoloader --no-interaction
	@echo "🗃️ Running database migrations..."
	docker-compose exec app php bin/console doctrine:migrations:migrate --no-interaction || true
	@echo "🎯 Loading fixtures..."
	docker-compose exec app php bin/console doctrine:fixtures:load --no-interaction || true
	@echo "🧹 Clearing cache..."
	docker-compose exec app php bin/console cache:clear || true
	@echo "✅ Application deployed successfully!"
	@echo "🌐 Application: http://localhost:8080"
	@echo "🗄️  phpMyAdmin: http://localhost:8081"
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
	docker-compose exec app git config --global --add safe.directory /var/www
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

# Deploy for production
deploy-prod: down
	@echo "🚀 Deploying TopicMatcher for PRODUCTION..."
	git pull || true
	docker-compose build --no-cache
	docker-compose up -d
	@echo "⏳ Waiting for containers to be ready..."
	sleep 15
	@echo "🔧 Setting up production environment..."
	docker-compose exec app git config --global --add safe.directory /var/www || true
	docker-compose exec --user root app chown -R topicmatcher:topicmatcher /var/www || true
	docker-compose exec app mkdir -p /var/www/vendor /var/www/var/cache /var/www/var/log || true
	@echo "📦 Installing production dependencies..."
	docker-compose exec app composer install --optimize-autoloader --no-dev --no-interaction
	@echo "🗃️ Running database migrations..."
	docker-compose exec app php bin/console doctrine:migrations:migrate --no-interaction --env=prod || true
	@echo "🧹 Clearing production cache..."
	docker-compose exec app php bin/console cache:clear --env=prod || true
	@echo "📦 Compiling assets for production..."
	docker-compose exec app php bin/console asset-map:compile --env=prod || true
	docker-compose exec app php bin/console cache:warmup --env=prod || true
	@echo "✅ Application deployed in PRODUCTION mode!"
	@echo "🌐 Application: http://localhost:8080"