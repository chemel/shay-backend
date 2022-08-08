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

# Compilation des assets
npm install
npm run build
```
