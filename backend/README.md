# Ballotify API

Laravel 13 REST API behind the Ballotify app.

```bash
composer install
cp .env.example .env && php artisan key:generate
php artisan migrate --seed
php artisan serve --port=8883
php artisan test
```

- Endpoint reference: [docs/API.md](docs/API.md)
- Full setup, configuration and deployment: [main README](../README.md)
