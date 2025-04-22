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

Without docker
```bash
composer install
```

If you are using docker start the containers:
```bash
sail up -d
sail composer install
```

3. Copy the environment file:
```bash
cp .env.example .env
```


4. Generate application key:
```bash
php artisan key:generate
```

If you are using docker start the containers:
```bash
sail artisan key:generate
```

5. Configure your NYT API credentials in `.env` if you want to use your own:
```env
NYT_API_KEY=your_api_key_here
NYT_BASE_URL=https://api.nytimes.com/svc/books/v3
NYT_CACHE_TTL=3600
```
Currenlty .env has valid data

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
If you are using docker start the containers:
```bash
sail php artisan test
```

To run the test suite with coverage report:

```bash
XDEBUG_MODE=coverage php artisan test --coverage
```
If you are using docker start the containers:
```bash
sail php artisan test --coverage
```

## Running App

To run application without docker

```bash
php artisan serve --port=8080 
```
If you are using docker you don't need to do anything in this step. Go to next step

## Accessing the Application

1. Start the application:
```bash
sail up -d
```

2. The application will be available at:
- Main URL: `http://localhost:8080`
- API Endpoint: `http://localhost:8080/api/v1/best-sellers/history`

Example API requests for docker usage:
```bash
# Get best sellers history
curl http://localhost:8080/api/v1/best-sellers/history

# Filter by author
curl http://localhost:8080/api/v1/best-sellers/history?author=Stephen+King

# Filter by ISBN
curl http://localhost:8080/api/v1/best-sellers/history?isbn[]=0399169274
```

Note: The port (8080) can be configured in your .env file using the APP_PORT variable.

## API Endpoints

### Get Best Sellers History is you are not using docker
http://127.0.0.1:8080/api/v1/best-sellers/history