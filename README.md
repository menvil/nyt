# NYT Best Sellers API Wrapper

This project is a Laravel-based API wrapper for the New York Times Best Sellers API. It provides a caching layer and standardized endpoints for accessing NYT Best Sellers data.

## Requirements

- PHP 8.1 or higher
- Composer
- Laravel 10.x
- Valid New York Times API Key

## Installation

1. Clone the repository:
```bash
git clone https://github.com/menvil/nyt.git
cd nyt
```

2. Install dependencies:
```bash
composer install
```

3. Copy the environment file:
```bash
cp .env.example .env
```

4. Generate application key:
```bash
php artisan key:generate
```

5. Configure your NYT API credentials in `.env`:
```env
NYT_API_KEY=your_api_key_here
NYT_BASE_URL=https://api.nytimes.com/svc/books/v3
NYT_CACHE_TTL=3600
```

## Configuration

The application uses the following configuration values which can be found in `config/nyt.php`:

```php
return [
    'api_key' => env('NYT_API_KEY'),
    'base_url' => env('NYT_BASE_URL', 'https://api.nytimes.com/svc/books/v3'),
    'cache_ttl' => env('NYT_CACHE_TTL', 3600),
    'endpoints' => [
        'best_sellers_history' => '/lists/best-sellers/history.json'
    ]
];
```

## Running Tests

To run the test suite:

```bash
php artisan test
```

##Running App
```bash
php artisan serve --port=8080 
```

## API Endpoints

### Get Best Sellers History
http://127.0.0.1:8080/api/v1/best-sellers/history