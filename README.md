# newsreader-backend
RSS News Reader back-end build with Symfony

# Installation

```console
# Installation des vendors
composer install

# Création de la base de données
php bin/console doctrine:database:create
php bin/console doctrine:schema:update --force
php bin/console doctrine:fixtures:load

# Création d'une paire de clés pour JWT
php bin/console lexik:jwt:generate-keypair

# Compilation des assets
npm install
npm run build
```

# Usage

## Get a JWT Token

```console
curl -s -X POST -H 'Accept: application/json' -H 'Content-Type: application/json' --data '{"username":"admin","password":"admin"}' http://127.0.0.1:8000/authentication_token
```

Replace admin / admin with your credentials

## Make a API request to get feeds

```console
curl -H 'Accept: application/json' -H "Authorization: Bearer ${TOKEN}" http://127.0.0.1:8000/api/feeds
```

Replace ${TOKEN} with the JWT token