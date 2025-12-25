# Centralized License Service - Complete Solution

## Table of Contents
1. [Problem & Requirements](#problem--requirements)
2. [Architecture & Design](#architecture--design)
3. [Data Model](#data-model)
4. [API Endpoints](#api-endpoints)
5. [Implementation Guide](#implementation-guide)
6. [Setup Instructions](#setup-instructions)
7. [Testing](#testing)
8. [Trade-offs & Decisions](#trade-offs--decisions)

---

## Problem & Requirements

### Overview
group.one needs a centralized License Service that acts as a single source of truth for licenses across multiple brands (WP Rocket, Imagify, RankMath, etc.). The system must:

- Support multi-tenancy (multiple brands)
- Manage license lifecycle (creation, renewal, suspension, cancellation)
- Handle license activations with seat management
- Provide APIs for both brand systems and end-user products
- Scale across the ecosystem

### User Stories Coverage

**Implemented (Core):**
- ✅ US1: Brand can provision a license
- ✅ US3: End-user product can activate a license
- ✅ US4: User can check license status
- ✅ US6: Brands can list licenses by customer email

**Designed (Additional):**
- 📋 US2: Brand can change license lifecycle (architecture provided)
- 📋 US5: End-user product can deactivate a seat (architecture provided)

---

## Architecture & Design

### System Architecture

```
┌─────────────────────────────────────────────────────────────┐
│                    Brand Systems Layer                       │
│  (rankmath.com, wp-rocket.me, imagify.io, etc.)            │
└────────────────────────┬────────────────────────────────────┘
                         │
                         │ Brand API (Authenticated)
                         │
┌────────────────────────▼────────────────────────────────────┐
│              License Service (Laravel API)                   │
│                                                              │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐     │
│  │   Brand      │  │   License    │  │  Activation  │     │
│  │   Service    │  │   Service    │  │   Service    │     │
│  └──────────────┘  └──────────────┘  └──────────────┘     │
│                                                              │
│  ┌──────────────────────────────────────────────────────┐  │
│  │            Data Layer (MySQL/PostgreSQL)              │  │
│  │  brands | products | license_keys | licenses |        │  │
│  │  activations | audit_logs                             │  │
│  └──────────────────────────────────────────────────────┘  │
│                                                              │
│  ┌──────────────────────────────────────────────────────┐  │
│  │     Observability: Logs, Metrics, Health Checks       │  │
│  └──────────────────────────────────────────────────────┘  │
└────────────────────────┬────────────────────────────────────┘
                         │
                         │ Product API (License Key Auth)
                         │
┌────────────────────────▼────────────────────────────────────┐
│              End-User Products Layer                         │
│    (WordPress Plugins, Desktop Apps, CLIs)                  │
└─────────────────────────────────────────────────────────────┘
```

### Multi-Tenancy Design

**Brand Isolation:**
- Each brand has a unique identifier (slug: `rankmath`, `wprocket`, etc.)
- Brand API keys for authentication
- Products belong to brands
- License keys scoped to brands

**Cross-Brand Queries:**
- Email-based lookup spans all brands (US6)
- Proper authorization enforced at API level

### Key Design Decisions

1. **License Key vs License Separation**
    - License Key: Container that groups multiple licenses
    - License: Specific product entitlement with expiration and status
    - Allows multiple products under one key (e.g., RankMath + Content AI)

2. **Activation Model**
    - Activations tied to licenses, not license keys
    - Instance identifier (URL, machine ID) uniqueness enforced
    - Seat counting at license level

3. **API Security**
    - Brand API: API key authentication via middleware
    - Product API: License key validation
    - Separate routes for different audiences

---

## Data Model

### Entity Relationship Diagram

```
brands
├── id
├── name (RankMath, WP Rocket)
├── slug (rankmath, wprocket)
├── api_key (for authentication)
└── timestamps

products
├── id
├── brand_id (FK → brands)
├── name (RankMath Pro, Content AI)
├── slug (rankmath-pro, content-ai)
├── max_seats (nullable, default seat limit)
└── timestamps

license_keys
├── id
├── key (unique, indexed)
├── brand_id (FK → brands)
├── customer_email (indexed)
└── timestamps

licenses
├── id
├── license_key_id (FK → license_keys)
├── product_id (FK → products)
├── status (valid, suspended, cancelled)
├── expires_at (nullable for lifetime)
├── max_seats (override product default)
└── timestamps

activations
├── id
├── license_id (FK → licenses)
├── instance_identifier (site URL, machine ID)
├── activated_at
├── last_checked_at
└── unique(license_id, instance_identifier)

audit_logs
├── id
├── entity_type (license, activation)
├── entity_id
├── action (created, renewed, suspended, etc.)
├── actor (brand_slug or system)
├── metadata (JSON)
└── created_at
```


---

## API Endpoints

### Brand API (Internal - Requires API Key)

**Authentication:** `X-Brand-API-Key` header

#### 1. Provision License (US1)

```http
POST /api/v1/brand/licenses
X-Brand-API-Key: {brand_api_key}
Content-Type: application/json

{
  "customer_email": "user@example.com",
  "product_slug": "rankmath-pro",
  "expires_at": "2025-12-31T23:59:59Z",  // optional, null = lifetime
  "max_seats": 3,  // optional, overrides product default
  "license_key": "EXISTING-KEY"  // optional, adds to existing key
}

Response 201:
{
  "license_key": "RNKM-XXXX-XXXX-XXXX",
  "license_id": 123,
  "product": "rankmath-pro",
  "status": "valid",
  "expires_at": "2025-12-31T23:59:59Z",
  "max_seats": 3,
  "created_at": "2024-12-25T10:00:00Z"
}
```

#### 2. Update License Lifecycle (US2)

```http
PATCH /api/v1/brand/licenses/{licenseId}
X-Brand-API-Key: {brand_api_key}

// Renew
{
  "action": "renew",
  "expires_at": "2026-12-31T23:59:59Z"
}

// Suspend
{
  "action": "suspend"
}

// Resume
{
  "action": "resume"
}

// Cancel
{
  "action": "cancel"
}

Response 200:
{
  "license_id": 123,
  "status": "suspended",
  "updated_at": "2024-12-25T10:30:00Z"
}
```

#### 3. List Licenses by Email (US6)

```http
GET /api/v1/brand/licenses/by-email/{email}
X-Brand-API-Key: {brand_api_key}

Response 200:
{
  "customer_email": "user@example.com",
  "licenses": [
    {
      "brand": "rankmath",
      "license_key": "RNKM-XXXX-XXXX-XXXX",
      "products": [
        {
          "product_slug": "rankmath-pro",
          "license_id": 123,
          "status": "valid",
          "expires_at": "2025-12-31T23:59:59Z",
          "activations": 2,
          "max_seats": 3
        }
      ]
    },
    {
      "brand": "wprocket",
      "license_key": "WPRK-YYYY-YYYY-YYYY",
      "products": [
        {
          "product_slug": "wprocket",
          "license_id": 456,
          "status": "valid",
          "expires_at": null,
          "activations": 1,
          "max_seats": 1
        }
      ]
    }
  ]
}
```

### Product API (External - License Key Auth)

#### 4. Activate License (US3)

```http
POST /api/v1/product/activate
Content-Type: application/json

{
  "license_key": "RNKM-XXXX-XXXX-XXXX",
  "product_slug": "rankmath-pro",
  "instance_identifier": "https://mysite.com"
}

Response 200:
{
  "success": true,
  "license_id": 123,
  "activation_id": 789,
  "status": "valid",
  "expires_at": "2025-12-31T23:59:59Z",
  "seats_used": 3,
  "seats_available": 3
}

Response 409 (Seat limit exceeded):
{
  "success": false,
  "error": "seat_limit_exceeded",
  "message": "No available seats. 3/3 seats in use.",
  "seats_used": 3,
  "seats_available": 3
}
```

#### 5. Check License Status (US4)

```http
GET /api/v1/product/check/{licenseKey}

Response 200:
{
  "license_key": "RNKM-XXXX-XXXX-XXXX",
  "customer_email": "user@example.com",
  "brand": "rankmath",
  "entitlements": [
    {
      "product_slug": "rankmath-pro",
      "license_id": 123,
      "status": "valid",
      "expires_at": "2025-12-31T23:59:59Z",
      "seats_used": 2,
      "seats_available": 3
    },
    {
      "product_slug": "content-ai",
      "license_id": 124,
      "status": "valid",
      "expires_at": "2025-12-31T23:59:59Z",
      "seats_used": 0,
      "seats_available": null
    }
  ]
}
```

#### 6. Deactivate Seat (US5)

```http
DELETE /api/v1/product/activate
Content-Type: application/json

{
  "license_key": "RNKM-XXXX-XXXX-XXXX",
  "product_slug": "rankmath-pro",
  "instance_identifier": "https://mysite.com"
}

Response 200:
{
  "success": true,
  "message": "Activation removed",
  "seats_used": 2,
  "seats_available": 3
}
```

---

## Implementation Guide

### Project Structure

```
license-core/
├── app/
│   ├── Models/
│   │   ├── Brand.php
│   │   ├── Product.php
│   │   ├── LicenseKey.php
│   │   ├── License.php
│   │   ├── Activation.php
│   │   ├── BrandApiKey.php
│   │   └── AuditLog.php
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── BrandLicenseController.php
│   │   │   └── ProductLicenseController.php
│   │   ├── Middleware/
│   │   │   └── BrandApiKeyAuth.php
│   │   └── Requests/
│   │       ├── ProvisionLicenseRequest.php
│   │       └── ActivateLicenseRequest.php
│   ├── Services/
│   │   ├── LicenseService.php
│   │   ├── ActivationService.php
│   │   └── AuditService.php
│   ├── Exceptions/
│   │   ├── SeatLimitExceededException.php
│   │   ├── LicenseNotFoundException.php
│   │   └── InvalidLicenseStatusException.php
│   └── Observers/
│       └── LicenseObserver.php
├── database/
│   ├── migrations/
│   └── seeders/
├── routes/
│   └── api.php
├── tests/
│   ├── Feature/
│   └── Unit/
└── README.md
```
