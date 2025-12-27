# Centralized License Service - Explanation & Documentation

## 1. Problem and Requirements
**Context:** group.one operates a multi-brand ecosystem (WP Rocket, RankMath, etc.) where license management is currently fragmented.

**Problem:** There is no single source of truth for what products a person can access. Brands need a way to provision licenses centrally, and products need a standardized way to validate them.

**Goal:** Build a multi-tenant License Service that acts as the authority for license lifecycles, exposing APIs for Brands (provisioning) and Products (activation/checking).

## 2. Architecture and Design

### Data Model & Multi-Tenancy
I chose a **Shared Database, Shared Schema** approach for multi-tenancy.
* **Tenant Identification:** Brands are the tenants. All core entities (`products`, `license_keys`, and `brand_api_keys`) are scoped by `brand_id`.
* **Isolation:** Application-level scoping is enforced via Middleware and Service layers to ensure Brands can only manipulate their own data.
* **Justification:** This allows for easier aggregation of data, specifically satisfying **US6** without complex cross-database queries.

### Security & Cryptography
* **Brand Authentication:** Uses `X-BRAND-API-KEY`. Keys are stored encrypted using `AES-256-CBC` (via `App\Helpers\BrandApiKeyAESEncryption`).
* **License Keys:** License keys are opaque tokens generated with high entropy (`random_bytes`).
    * **Storage:** They are stored **encrypted** in the database.
    * **Transmission:** They are only returned in plaintext once (upon creation) and sent to the customer's email.
    * **Validation:** Incoming keys are encrypted and matched against the database.

### Integration Points
* **Brand API:** Used by backend systems (e.g., RankMath, WP Rocket) to create/update licenses.
* **Product API:** Used by the software itself (e.g., WP Plugins) to activate seats and check validity.

### Pessimistic Locking
* Implemented pessimistic locking to prevent race condition at the point of activating/deactivating a product license.

### Rate Limiting
* Implemented rate limiting to prevent the abuse of activate/deactivate endpoint for product license.

### NoSQL (MongoDB)
* Audit logging is write-heavy. Offloading these high-volume inserts to a dedicated NoSQL store prevents locking or bloating the primary transactional database (MySQL).

## 3. Trade-offs and Decisions

### Encryption vs. Hashing for Keys
* **Decision:** I chose **Symmetric Encryption** (AES) over Hashing (bcrypt/argon2) for API and License Keys.
* **Trade-off:** Hashing is generally more secure for authentication secrets. However, in this domain, Brands may need to retrieve or audit keys. Encryption offers a balance between security (at rest) and recoverability.

## 4. Alternatives Considered

### Database-per-Tenant
* **Alternative:** Creating a separate database for each Brand.
* **Why Rejected:** While offering superior isolation, it complicates **US6** (Listing licenses across all brands for a specific email). A shared schema with strict `brand_id` indexing was deemed more efficient for the "ecosystem" view required by group.one.

## 5. Scaling Plan

To move this from a test case to a high-scale production system:

1. **Read Replicas:** The "Check License" endpoint (US4) will likely have a 100:1 read/write ratio compared to Provisioning. Directing these reads to DB replicas will prevent locking.
2. **Caching:** Implement Redis caching for the `/check` endpoint. Valid licenses can be cached for short windows (e.g., 20-30 minutes) to reduce DB load.
3. **Horizontal Scaling:** The API is stateless (PHP/Laravel). We can run many instances behind a Load Balancer.

## 6. User Story Satisfaction

| Story | Status          | Implementation Details                                                                             |
| :--- |:----------------|:---------------------------------------------------------------------------------------------------|
| **US1: Brand can provision** | **Implemented** | `POST /api/v1/brand/licenses`. Accepts customer email + product list. Generates encrypted key.     |
| **US2: Brand lifecycle** | **Implemented**    | API endpoint defined (`PATCH /api/v1/brand/licenses/{id}`) to handle `suspend`, `renew`, etc.    |
| **US3: Product activation** | **Implemented** | `POST /api/v1/product/licenses/activate`. Enforces `max_seats` and locks `fingerprint` to license. |
| **US4: Check status** | **Implemented** | `GET /api/v1/product/licenses/check`. Returns validity boolean and seat usage counts.              |
| **US5: Deactivate seat** | **Implemented** | `POST /api/v1/product/licenses/deactivate`                                                         |
| **US6: List by Email** | **Implemented** | `GET /api/v1/brand/licenses?email=...`.                                                            |

## 7. How to Run Locally

### Prerequisites
* Docker & Docker Compose
* *Or:* PHP 8.2+, Composer, MySQL, MongoDB

### Steps
1. **Clone and Setup**
    ```bash
    git clone https://github.com/Lowkey1729/license-core.git
    cd license-core
    cp .env.example .env
    cp .env.example.testing .env.testing
    ```

2. **Install Dependencies**
    ```bash
    composer install --ignore-platform-reqs
    ```

3. **Start Environment (Docker)**
    ```bash
    docker compose up --build -d
    ```

4. **Setup Database**
    ```bash
    docker exec -it license_app php artisan migrate
    docker exec -it license_app php artisan configure-app
    ```
   *The `configure-app` command creates default brands (`rankmath`, `wprocket`), products, and X-BRAND-API-KEYs.*
   <img width="1125" height="265" alt="generated-license-keys-sample" src="https://github.com/user-attachments/assets/b9df725f-d412-4071-8a5f-d9d4080bb9f8" />

5. **Run Tests**
    ```bash
    docker exec -it license_app php artisan test
    ```

## 8. API Testing Examples

Below are comprehensive API test examples for each user story:

### US1: Brand Provision License
**Provision a new license for a customer:**
```bash
curl -X POST http://localhost:29001/api/v1/brand/licenses \
  -H "X-BRAND-API-KEY: your-brand-key-here" \
  -H "Content-Type: application/json" \
  -d '{
    "customer_email": "user1@gmail.com",
    "products": [
        {
            "product_slug": "wp_rocket_core_plugin",
            "expires_at": "2026-02-11",
            "max_seats": 2
        },
        {
            "product_slug": "rocketcdn",
            "expires_at": "2026-02-11",
            "max_seats": 2
        },
        {
            "product_slug": "advanced_caching_features",
            "expires_at": "2026-02-13",
            "max_seats": 1
        }
    ]
}'
```

**Expected Response:**
```json
{
    "status": "success",
    "message": "License(s) provisioned successfully",
    "data": {
        "licenseKey": "PRNH-UMXJ-TCXQ-HUUJ-3KLD-77IP-VE",
        "customerEmail": "user1@gmail.com",
        "productNames": [
            "WP Rocket Core Plugin",
            "RocketCDN",
            "Advanced Caching Features"
        ]
    }
}
```

### US2: Manage License Lifecycle
**Suspend a license:**
```bash
curl -X PATCH http://localhost:29001/api/v1/brand/licenses/{license_id} \
  -H "X-BRAND-API-KEY: your-brand-key-here" \
  -H "Content-Type: application/json" \
  -d '{
    "action": "suspend"
  }'
```

**Renew a license:**
```bash
curl -X PATCH http://localhost:29001/api/v1/brand/licenses/{license_id} \
  -H "X-BRAND-API-KEY: your-brand-key-here" \
  -H "Content-Type: application/json" \
  -d '{
    "expires_at": "2026-12-31",
    "action": "renew"
  }'
```

### US3: Product Activation
**Activate a license on a device:**
```bash
curl -X POST http://localhost:29001/api/v1/product/licenses/activate \
  -H "Content-Type: application/json" \
  -d '{
    "license_key": "QFQB-LKUJ-HPRS-V557-ZJN2-DIGY-7Q",
    "product_slug": "rocketcdn",
    "fingerprint": "unique-device-1"
}'
```

**Expected Response (Success):**
```json
{
    "status": "success",
    "message": "Product license activated successfully.",
    "data": []
}
```

**Expected Response (Max Seats Reached):**
```json
{
    "status": "failed",
    "message": "You have reached the maximum number of activations for this license"
}
```

### US4: Check License Status
**Check if a license is valid:**
```bash
curl -X GET "http://localhost:29001/api/v1/product/licenses/check?license_key=RM-XXXX-XXXX-XXXX-XXXX&product_slug=rank-math-pro"
```

**Expected Response:**
```json
{
    "status": "success",
    "message": "License checked successfully.",
    "data": {
        "customer": "user1@gmail.com",
        "activations": [
            {
                "product": "WP Rocket Core Plugin",
                "slug": "wp_rocket_core_plugin",
                "is_valid": true,
                "status": "active",
                "expires_at": "2026-02-11T00:00:00.000000Z",
                "max_seats": 2,
                "seats_used": 0,
                "seats_left": 2
            },
            {
                "product": "RocketCDN",
                "slug": "rocketcdn",
                "is_valid": true,
                "status": "active",
                "expires_at": "2026-02-11T00:00:00.000000Z",
                "max_seats": 2,
                "seats_used": 0,
                "seats_left": 2
            },
            {
                "product": "Advanced Caching Features",
                "slug": "advanced_caching_features",
                "is_valid": true,
                "status": "active",
                "expires_at": "2026-02-13T00:00:00.000000Z",
                "max_seats": 1,
                "seats_used": 0,
                "seats_left": 1
            }
        ]
    }
}
```

### US5: Deactivate Seat
**Deactivate a specific device/seat:**
```bash
curl -X POST http://localhost:29001/api/v1/product/licenses/deactivate \
  -H "Content-Type: application/json" \
  -d '{
    "license_key": "QFQB-LKUJ-HPRS-V557-ZJN2-DIGY-7Q",
    "product_slug": "rocketcdn",
    "fingerprint": "mojeed2"
}'
```

**Expected Response:**
```json
{
  "success": true,
  "data": {
    "deactivated": true,
    "seats_used": 0,
    "seats_available": 3
  }
}
```

### US6: List Licenses by Email
**Retrieve all licenses for a customer email:**
```bash
curl -X GET "http://localhost:29001/api/v1/brand/licenses?email=customer@example.com" \
  -H "X-BRAND-API-KEY: your-brand-key-here"
```

**Expected Response:**
```json
{
  "success": true,
  "data": [
    {
      "license_key": "RM-XXXX-XXXX-XXXX-XXXX",
      "email": "customer@example.com",
      "brand": "rankmath",
      "products": ["rank-math-pro"],
      "status": "active",
      "seats_used": 1,
      "max_seats": 3,
      "expires_at": "2025-12-31T23:59:59Z"
    },
    {
      "license_key": "WP-YYYY-YYYY-YYYY-YYYY",
      "email": "customer@example.com",
      "brand": "wprocket",
      "products": ["wp-rocket"],
      "status": "active",
      "seats_used": 2,
      "max_seats": 5,
      "expires_at": "2026-06-30T23:59:59Z"
    }
  ]
}
```

**List with pagination:**
```bash
curl -X GET "http://localhost:29001/api/v1/brand/licenses?email=customer@example.com&page=1&per_page=10" \
  -H "X-BRAND-API-KEY: your-brand-key-here"
```

## 9. Known Limitations & Next Steps
1. **Webhooks:** Brands currently have to pull data. *Next Step:* Implement webhooks to notify Brands when a user activates or deactivates a license.
