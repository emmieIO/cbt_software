# CBT Software

Laravel 12 + Vue 3/Inertia application for computer-based testing (CBT) across admin, staff, and student portals.

## Requirements

- PHP 8.4+
- Composer 2+
- Node.js 22+
- npm 10+

## Quick Start

```bash
cp .env.example .env
composer install
npm install
php artisan key:generate
php artisan migrate --force
php artisan db:seed --class=RevampPermissionsSeeder --force
npm run build
php artisan serve
```

## Development

Run full local stack:

```bash
composer dev
```

Useful commands:

```bash
php artisan test
composer lint
npm run lint
npm run format:check
```

## Architecture Notes

- `routes/admin.php`, `routes/staff.php`, `routes/student.php` define portal boundaries.
- `app/Services` contains business orchestration.
- `app/Services/Exam/*` contains focused exam domain logic for selection, attempt lifecycle, and submission.
- `app/Repositories` contains persistence abstractions.
- For fast feature navigation, use `docs/CHANGE_MAP.md`.

## Deployment

- CI workflows are in `.github/workflows/`.
- Production deployment entrypoint is `deploy.sh`.
- Ensure `APP_ENV=production` and `APP_DEBUG=false` in production.

## Security Baseline

- Keep debug/test routes out of production routing.
- Use portal + permission middleware together for sensitive endpoints.
- Rotate credentials and set strong admin environment variables before first production seed.
