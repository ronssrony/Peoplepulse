#!/bin/bash

# PeoplePulse Docker Setup Script
# This script sets up the Docker environment for the first time

set -e

echo "=========================================="
echo "  PeoplePulse Docker Setup"
echo "=========================================="

# Check if Docker is running
if ! docker info > /dev/null 2>&1; then
    echo "Error: Docker is not running. Please start Docker and try again."
    exit 1
fi

# Copy environment file if it doesn't exist
if [ ! -f .env ]; then
    echo "Creating .env file from .env.docker..."
    cp .env.docker .env
fi

# Build containers
echo "Building Docker containers..."
docker compose build

# Start containers
echo "Starting containers..."
docker compose up -d

# Wait for MySQL to be ready
echo "Waiting for MySQL to be ready..."
sleep 10

# Check if MySQL is actually ready
until docker compose exec -T mysql mysqladmin ping -h "localhost" --silent; do
    echo "Waiting for MySQL..."
    sleep 2
done

echo "MySQL is ready!"

# Generate application key if not set
if grep -q "APP_KEY=$" .env || grep -q "APP_KEY=\"\"" .env; then
    echo "Generating application key..."
    docker compose exec -T app php artisan key:generate
fi

# Run migrations
echo "Running migrations..."
docker compose exec -T app php artisan migrate --force

# Run seeders (optional - uncomment if you want to seed data)
# echo "Seeding database..."
# docker compose exec -T app php artisan db:seed --force

# Create storage link
echo "Creating storage link..."
docker compose exec -T app php artisan storage:link || true

# Set permissions
echo "Setting permissions..."
docker compose exec -T app chmod -R 775 storage bootstrap/cache

echo ""
echo "=========================================="
echo "  Setup Complete!"
echo "=========================================="
echo ""
echo "Application URL: http://localhost:8000"
echo "Vite Dev Server: http://localhost:5173"
echo "MySQL Port: 3306"
echo ""
echo "Useful commands:"
echo "  docker compose up -d      # Start all containers"
echo "  docker compose down       # Stop all containers"
echo "  docker compose logs -f    # View logs"
echo "  docker compose exec app php artisan tinker  # Laravel tinker"
echo ""