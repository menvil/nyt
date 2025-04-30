## Getting Started with Docker

Follow these steps to set up and run the project using Docker after cloning the repository:

1. **Copy the example environment file:**
   ```bash
   cp .env.example .env
   ```

2. **Install Composer dependencies using Docker:**
   ```bash
   docker run --rm \
       -u "$(id -u):$(id -g)" \
       -v "$(pwd):/var/www/html" \
       -w /var/www/html \
       composer install --ignore-platform-reqs
   ```

3. **Generate the Laravel application key:**
   ```bash
   docker run --rm \
       -v "$(pwd):/var/www/html" \
       -w /var/www/html \
       php:8.2-cli \
       php artisan key:generate
   ```

4. **Build and start the Docker containers:**
   ```bash
   docker compose up -d --build
   ```

5. **Verify the container is running:**
   ```bash
   docker compose ps
   ```

6. **Access the application:**
   - Main application: [http://localhost:8080](http://localhost:8080)
   - API endpoint: [http://localhost:8080/api/v1/best-sellers/history](http://localhost:8080/api/v1/best-sellers/history)

### Useful Docker Commands

- **View logs:**
  ```bash
  docker compose logs -f
  ```

- **Stop containers:**
  ```bash
  docker compose down
  ```

- **Run artisan commands:**
  ```bash
  docker compose exec laravel.test php artisan list
  ```

- **Run tests:**
  ```bash
  docker compose exec laravel.test php artisan test
  ```

### Notes

- Ensure Docker and Docker Compose are installed on your system.
- The application uses port `8080` by default (configurable in `.env` with `APP_PORT`).
- Modify environment variables as needed in the `.env` file.
- The project is configured to use PHP 8.2.

If you encounter any issues, please share the output of:
```bash
docker compose ps
docker compose logs
```
