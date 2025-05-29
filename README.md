# Shay

Shay is a modern RSS reader built with Angular and Symfony.

## Development Installation Guide

Follow these steps in order to set up the development environment:

### 1. Clone the repository
```bash
git clone https://github.com/chemel/shay-backend.git
cd shay-backend
```

### 2. Install Dependencies
Install PHP dependencies using Composer:
```bash
composer install
```

### 3. Start Docker Services
Launch the required Docker containers:
```bash
docker compose up -d
```

### 4. Database Setup
Execute the following commands to set up and populate the database:

```bash
# Create the database
php bin/console doctrine:database:create

# Update database schema
php bin/console doctrine:schema:update --force

# Load initial data fixtures
php bin/console doctrine:fixtures:load

# Fetch initial RSS feeds
php bin/console app:feed:fetch

# Create a user account
php bin/console app:user-create
```

### 5. JWT Configuration
Generate JWT keypair for authentication:
```bash
php bin/console lexik:jwt:generate-keypair
```

### 6. Start Development Server
Launch the Symfony development server:
```bash
symfony serve
```

The application should now be running at `http://localhost:8000`

## Additional Information

- Make sure you have PHP 8.x installed
- Docker and Docker Compose must be installed on your system
- Composer is required for dependency management
- The Symfony CLI should be installed for the development server

## Debugging and API Testing

You can use the following curl commands to test the API endpoints:

### Authentication

To obtain a JWT token:
```bash
curl -s -X POST \
  -H 'Accept: application/json' \
  -H 'Content-Type: application/json' \
  --data '{"username":"admin","password":"admin"}' \
  http://127.0.0.1:8000/api/login
```

Note: Replace `admin`/`admin` with your actual credentials.

### Testing API Endpoints

To fetch the list of feeds (authenticated request):
```bash
curl -H 'Accept: application/json' \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  http://127.0.0.1:8000/api/feeds
```

Note: Replace `YOUR_JWT_TOKEN` with the token received from the authentication request.
