# Centralized License Service - Explanation & Documentation

## 1. Problem and Requirements
**Context:** group.one operates a multi-brand ecosystem (WP Rocket, RankMath, etc.) where license management is currently fragmented.
**Problem:** There is no single source of truth for what products a person can access. Brands need a way to provision licenses centrally, and products need a standardized way to validate them.
[cite_start]**Goal:** Build a multi-tenant License Service that acts as the authority for license lifecycles, exposing APIs for Brands (provisioning) and Products (activation/checking).

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
    * **Transmission:** They are only returned in plaintext once (upon creation) and sent to the customer's email (upon creation).
    * **Validation:** Incoming keys are encrypted and matched against the database.

### Integration Points
* **Brand API:** Used by backend systems (e.g., RankMath, WP Rocket) to create/update licenses.
* **Product API:** Used by the software itself (e.g., WP Plugins) to activate seats and check validity.

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

1.  **Read Replicas:** The "Check License" endpoint (US4) will likely have a 100:1 read/write ratio compared to Provisioning. Directing these reads to DB replicas will prevent locking.
2.  **Caching:** Implement Redis caching for the `/check` endpoint. Valid licenses can be cached for short windows (e.g., 20-30 minutes) to reduce DB load.
3.  **Horizontal Scaling:** The API is stateless (PHP/Laravel). We can run many instances behind a Load Balancer.

## 6. User Story Satisfaction

| Story | Status          | Implementation Details                                                                             |
| :--- |:----------------|:---------------------------------------------------------------------------------------------------|
| **US1: Brand can provision** | **Implemented** | `POST /api/v1/brand/licenses`. Accepts customer email + product list. Generates encrypted key.     |
| **US2: Brand lifecycle** | **Implemented**    | API endpoint defined (`PATCH /api/product/licenses/{id}`) to handle `suspend`, `renew`, e.t.c..    |
| **US3: Product activation** | **Implemented** | `POST /api/v1/product/licenses/activate`. Enforces `max_seats` and locks `fingerprint` to license. |
| **US4: Check status** | **Implemented** | `GET /api/v1/product/licenses/check`. Returns validity boolean and seat usage counts.              |
| **US5: Deactivate seat** | **Implemented** | `POST /api/v1/product/licenses/deactivate`                                                         |
| **US6: List by Email** | **Implemented** | `GET /api/v1/brand/licenses?email=...`.                                                            |


## 7. How to Run Locally

### Prerequisites
* Docker & Docker Compose
* *Or:* PHP 8.2+, Composer, MySQL

### Steps
1.  **Clone and Setup**
    ```bash
    git clone https://github.com/Lowkey1729/license-core.git
    cd license-core
    cp .env.example .env
    ```
2. **Install Dependencies**
    ```bash
    composer install --ignore-platform-reqs
    ```

3. **Start Environment (Docker)**
    ```bash
    docker compose up --build
    ```

4. **Setup DB**
    ```bash
    docker compose exec app php artisan migrate
    docker compose exec app php artisan configure-app
    ```
   *The configure-app command creates default brands (`rankmath`, `wprocket`), products, and X-BRAND-API-KEYs.*
   <img width="1125" height="265" alt="generated-license-keys-sample" src="https://github.com/user-attachments/assets/b9df725f-d412-4071-8a5f-d9d4080bb9f8" />

5. **Test the API**
    * **Provision a License:**
        ```bash
        curl -X POST http://localhost/api/v1/brand/licenses \
        -H "X-BRAND-API-KEY: test-brand-key" \
        -d '{"email":"customer@example.com", "products": [{"slug": "rank-math-pro"}]}'
        ```

## 8. Known Limitations & Next Steps
1.  **Concurrency:** Currently, a race condition could theoretically allow over-activation if two requests hit the server at the exact same microsecond. *Next Step:* Implement Atomic Locks (Redis) on the `activate` endpoint.
2.  **Audit Log Growth:** The `audit_logs` table will grow rapidly. *Next Step:* Implement an async worker to offload logs to Elasticsearch.
3.  **Webhooks:** Brands currently have to pull data. *Next Step:* Implement webhooks to notify Brands when a user activates a license.
