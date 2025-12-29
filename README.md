# Centralized License Service

Multi-tenant license management system for group.one's ecosystem (WP Rocket, RankMath, etc.).

## Requirements

- Docker & Docker Compose
- *OR* PHP 8.2+, Composer, MySQL 8.0+, MongoDB 5.0+

## Quick Start

```bash
# Clone and setup
git clone https://github.com/Lowkey1729/license-core.git
cd license-core
cp .env.example .env
cp .env.example.testing .env.testing

# Generate keys
php artisan key:generate
php artisan key:generate --env=testing

# Install dependencies
composer install --ignore-platform-reqs

# Start services
docker compose up --build -d

# Setup database
docker exec -it license_app php artisan migrate
docker exec -it license_app php artisan configure-app
```

**Save the API keys displayed** - they're only shown once!

## Environment Configuration

Edit `.env` with your settings:

```env
APP_URL=http://localhost:29001

DB_CONNECTION=mysql
DB_HOST=mysql
DB_DATABASE=license_service

MONGODB_HOST=mongodb
MONGODB_DATABASE=license_audit

MONGODB_DATABASE=
MONGODB_USER
MONGODB_PASSWORD
MONGODB_URI
```

## API Endpoints

| Endpoint | Method | Purpose | Auth |
|----------|--------|---------|------|
| `/api/v1/brand/licenses` | POST | Provision license | Brand API Key |
| `/api/v1/brand/licenses/{id}` | PATCH | Update status | Brand API Key |
| `/api/v1/brand/licenses` | GET | List by email | Brand API Key |
| `/api/v1/product/licenses/activate` | POST | Activate seat | Public |
| `/api/v1/product/licenses/deactivate` | POST | Deactivate seat | Public |
| `/api/v1/product/licenses/check` | GET | Check validity | Public |

**Full API Docs**: https://documenter.getpostman.com/view/14195862/2sBXVbFtCz

## Usage Examples

**Provision License:**
```bash
curl -X POST http://localhost:29001/api/v1/brand/licenses \
  -H "X-BRAND-API-KEY: your-key" \
  -H "Content-Type: application/json" \
  -d '{
    "customer_email": "user@example.com",
    "products": [{
        "product_slug": "wp_rocket_core_plugin",
        "expires_at": "2026-02-11",
        "max_seats": 3
    }]
}'
```

**Activate License:**
```bash
curl -X POST http://localhost:29001/api/v1/product/licenses/activate \
  -H "Content-Type: application/json" \
  -d '{
    "license_key": "XXXX-XXXX-XXXX-XXXX",
    "product_slug": "wp_rocket_core_plugin",
    "fingerprint": "device-id"
}'
```

**Check Status:**
```bash
curl "http://localhost:29001/api/v1/product/licenses/check?license_key=XXXX-XXXX&product_slug=wp_rocket_core_plugin"
```

## Testing

```bash
# Run tests
docker exec -it license_app php artisan test

# Code analysis
docker compose exec app composer analyze

# Code style
docker compose exec app composer pint
```


## Explanation

[View explanation.md](https://github.com/Lowkey1729/license-core/blob/develop/explanation.md)

