# Docker Deployment

This setup assumes Nginx and MySQL already run on the host server. Docker exposes the Laravel HTTP port, and host Nginx reverse-proxies to it.

## Services

- `app`: Laravel app served by Apache on container port `80`, exposed on host `127.0.0.1:${APP_HTTP_PORT:-8080}`.
- `queue`: Laravel queue worker using the database queue driver.
- `scheduler`: Laravel scheduler runner for tasks like automatic payroll disbursement.

## First Deploy

Copy the Docker environment template and fill in production values:

```bash
cp .env.docker.example .env
php artisan key:generate --show
```

Put the generated key into `APP_KEY` in `.env`.

The containers connect to MySQL installed on the host. For Docker Desktop, `host.docker.internal` points to the host machine:

```env
DB_CONNECTION=mysql
DB_HOST=host.docker.internal
DB_PORT=3306
DB_DATABASE=jjm_hrms
DB_USERNAME=root
DB_PASSWORD=
```

The old `DOCKER_DB_*`, `PHPMYADMIN_*`, and `PMA_*` variables are no longer used by this compose file.

Build and start:

```bash
docker compose up -d --build
```

Run migrations and seeders when needed:

```bash
docker compose exec app php artisan migrate --force
docker compose exec app php artisan db:seed --force
```

For a one-time automatic migration during container start, set this in `.env` before starting:

```env
RUN_MIGRATIONS=true
```

Turn it back to `false` after the first successful deploy.

## Host Nginx Example

Main Laravel app:

```nginx
server {
    server_name domain.com;

    location / {
        proxy_pass http://127.0.0.1:8080;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
    }

}
```

## Payroll Auto-Disbursement

The scheduler container runs:

```bash
php artisan schedule:work --verbose --no-interaction
```

That executes the scheduled payroll command from `routes/console.php`:

```php
Schedule::command('payroll:mark-disbursed')->dailyAt('06:00');
```

## Queue Worker

The queue container runs:

```bash
php artisan queue:work --tries=3 --timeout=90 --sleep=3
```

The project currently uses:

```env
QUEUE_CONNECTION=database
```

So Redis or RabbitMQ is not required.
