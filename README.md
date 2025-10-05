# Foodics Pay - Online Wallet Application

A Laravel-based online wallet application that handles receiving money from banks via webhooks and sending money through standardized XML format.

## Table of Contents
- [Overview](#overview)
- [Architecture](#architecture)
- [Features](#features)
- [Setup Instructions](#setup-instructions)
- [API Endpoints](#api-endpoints)
- [Testing](#testing)
- [Design Decisions](#design-decisions)
- [Performance Considerations](#performance-considerations)

## Overview

This application solves two main challenges:
1. **Receiving Money**: Process webhooks from multiple banks with different formats
2. **Sending Money**: Generate standardized XML for payment transfers to any bank

## Architecture

### Key Components

```
app/
├── Exceptions/
│   └── UnsupportedBankException.php      # Custom exception for unknown banks
├── Http/
│   ├── Controllers/
│   │   ├── PaymentController.php         # Handles payment transfer requests
│   │   └── WebhookController.php         # Receives bank webhooks
│   └── Requests/
│       └── PaymentTransferRequest.php    # Validates payment transfer data
├── Jobs/
│   └── ProcessWebhookJob.php             # Queued job for async processing
├── Models/
│   └── Transaction.php                   # Transaction model
├── Repositories/
│   └── TransactionRepository.php         # Data access layer with bulk operations
└── Services/
    ├── BankParsers/
    │   ├── BankParserInterface.php       # Contract for bank parsers
    │   ├── FoodicsBankParser.php         # Foodics Bank format parser
    │   └── AcmeBankParser.php            # Acme Bank format parser
    ├── PaymentXmlService.php             # Generates payment XML
    └── TransactionImportService.php      # Orchestrates transaction import
```

### Design Patterns Used

1. **Strategy Pattern**: Bank parsers implement a common interface, allowing easy addition of new banks
2. **Repository Pattern**: Abstracts database operations for better testability
3. **Queue Pattern**: Webhooks are processed asynchronously to prevent data loss
4. **Service Layer**: Business logic separated from controllers

## Features

### 1. Receiving Money (Webhook Processing)

- **Multi-bank Support**: Extensible parser system for different bank formats
- **Idempotency**: Duplicate transactions are automatically ignored based on unique references
- **Asynchronous Processing**: Webhooks are queued immediately and processed in background
- **Bulk Processing**: Efficiently handles webhooks with multiple transactions
- **Metadata Support**: Stores additional transaction information as JSON

**Supported Banks:**
- **Foodics Bank**: Format `YYYYMMDDAMOUNT#REFERENCE#key/value`
- **Acme Bank**: Format `AMOUNT//REFERENCE//YYYYMMDD`

### 2. Sending Money (XML Generation)

- **Standardized Output**: All banks use the same XML format
- **Conditional Elements**: 
  - `<Notes>` excluded if empty
  - `<PaymentType>` excluded if value is 99
  - `<ChargeDetails>` excluded if value is SHA
- **Validation**: Request validation ensures all required fields are present

### 3. Performance & Reliability

- **Queue System**: Redis-backed queues prevent webhook loss during high load
- **Bulk Inserts**: Optimized database operations for large transaction batches
- **Retry Logic**: Failed jobs are retried up to 3 times
- **Comprehensive Logging**: All operations are logged for debugging

## Setup Instructions

### Prerequisites
- Docker Desktop installed and running
- Git installed
- Basic terminal knowledge

### Installation Steps

1. **Clone or create project directory**
```bash
mkdir foodics-pay
cd foodics-pay
```

2. **Create Docker configuration files**

Create `docker-compose.yml`, `Dockerfile`, and `docker/nginx/default.conf` as provided in the project.

3. **Start Docker containers**
```bash
docker-compose up -d --build
```

4. **Install Laravel and dependencies**
```bash
# Enter app container
docker-compose exec app bash

# Inside container, install Laravel
composer create-project laravel/laravel .

# Exit and install project dependencies
exit
docker-compose exec app composer install
```

5. **Configure environment**
```bash
# Copy environment file
docker-compose exec app cp .env.example .env

# Generate application key
docker-compose exec app php artisan key:generate
```

Update `.env` with these settings:
```env
DB_CONNECTION=mysql
DB_HOST=db
DB_PORT=3306
DB_DATABASE=foodics_pay
DB_USERNAME=foodics
DB_PASSWORD=secret

QUEUE_CONNECTION=redis
REDIS_HOST=redis
REDIS_PASSWORD=null
REDIS_PORT=6379
```

6. **Run migrations**
```bash
docker-compose exec app php artisan migrate
```

7. **Start queue worker** (in separate terminal)
```bash
docker-compose exec app php artisan queue:work --tries=3 --timeout=120
```

## API Endpoints

### 1. Receive Money (Webhook)

**Endpoint:** `POST /api/webhooks/{bankType}`

**Parameters:**
- `bankType`: Bank identifier (e.g., `foodics`, `acme`)
- Query param `client_id`: Client identifier (optional, defaults to 1)
- Body: Raw webhook data (text/plain)

**Example - Foodics Bank:**
```bash
curl -X POST "http://localhost:8000/api/webhooks/foodics?client_id=1" \
  -H "Content-Type: text/plain" \
  -d "20250615156,50#202506159000001#note/debt payment"
```

**Example - Acme Bank:**
```bash
curl -X POST "http://localhost:8000/api/webhooks/acme?client_id=1" \
  -H "Content-Type: text/plain" \
  -d "156,50//202506159000001//20250615"
```

**Response (202 Accepted):**
```json
{
  "success": true,
  "message": "Webhook received and queued for processing"
}
```

### 2. Send Money (Generate XML)

**Endpoint:** `POST /api/payments/transfer`

**Request Body:**
```json
{
  "reference": "e0f4763d-28ea-42d4-ac1c-c4013c242105",
  "date": "2025-02-25 06:33:00+03",
  "amount": 177.39,
  "currency": "SAR",
  "sender_account": "SA6980000204608016212908",
  "receiver_bank_code": "FDCSSARI",
  "receiver_account": "SA6980000204608016211111",
  "beneficiary_name": "Jane Doe",
  "notes": ["Lorem Epsum", "Dolor Sit Amet"],
  "payment_type": 421,
  "charge_details": "RB"
}
```

**Response (200 OK):**
```xml
<?xml version="1.0" encoding="utf-8"?>
<PaymentRequestMessage>
  <TransferInfo>
    <Reference>e0f4763d-28ea-42d4-ac1c-c4013c242105</Reference>
    <Date>2025-02-25 06:33:00+03</Date>
    <Amount>177.39</Amount>
    <Currency>SAR</Currency>
  </TransferInfo>
  <SenderInfo>
    <AccountNumber>SA6980000204608016212908</AccountNumber>
  </SenderInfo>
  <ReceiverInfo>
    <BankCode>FDCSSARI</BankCode>
    <AccountNumber>SA6980000204608016211111</AccountNumber>
    <BeneficiaryName>Jane Doe</BeneficiaryName>
  </ReceiverInfo>
  <Notes>
    <Note>Lorem Epsum</Note>
    <Note>Dolor Sit Amet</Note>
  </Notes>
  <PaymentType>421</PaymentType>
  <ChargeDetails>RB</ChargeDetails>
</PaymentRequestMessage>
```

## Testing

### Run All Tests
```bash
docker-compose exec app php artisan test
```

### Run Specific Test Suites
```bash
# Webhook tests
docker-compose exec app php artisan test --filter=WebhookTest

# Payment XML tests
docker-compose exec app php artisan test --filter=PaymentXmlServiceTest

# Bank parser tests
docker-compose exec app php artisan test tests/Unit/BankParsers
```

### Test Coverage

The application includes comprehensive tests:

**Feature Tests:**
- Webhook queue handling
- Transaction creation from webhooks
- Duplicate transaction prevention
- Large batch processing (1000 transactions)
- Empty webhook body handling
- Metadata storage

**Unit Tests:**
- Foodics Bank parser
- Acme Bank parser
- Payment XML generation
- Conditional XML element inclusion

### Performance Test

The application includes a performance test that processes 1000 transactions:

```bash
docker-compose exec app php artisan test --filter=test_handles_large_webhook_with_1000_transactions_efficiently
```

Expected: Processing completes in under 10 seconds.

## Design Decisions

### 1. Why Queue-Based Processing?

**Problem:** Banks send webhooks expecting immediate 200 responses. If processing takes too long, banks may retry, causing duplicates.

**Solution:** Immediately queue webhooks (202 response) and process asynchronously.

**Benefits:**
- Prevents webhook loss during high load
- Allows application restarts without dropping data
- Enables horizontal scaling of workers

### 2. Why Strategy Pattern for Bank Parsers?

**Problem:** Each bank has a different webhook format. Hard-coding parsers leads to tight coupling.

**Solution:** `BankParserInterface` with concrete implementations per bank.

**Benefits:**
- Easy to add new banks (Open/Closed Principle)
- Parsers are independently testable
- No modification to core service when adding banks

### 3. Why Bulk Inserts with Idempotency?

**Problem:** Processing 1000 transactions with individual inserts is slow. Banks may send duplicates.

**Solution:** Bulk insert with unique constraint on `reference` field, catching duplicate key exceptions.

**Benefits:**
- 10x faster than individual inserts
- Database-level deduplication
- No race conditions

### 4. Why Repository Pattern?

**Problem:** Direct database access in services makes testing difficult and violates separation of concerns.

**Solution:** `TransactionRepository` abstracts data access.

**Benefits:**
- Services can be tested without database
- Easier to switch storage mechanisms
- Centralized query optimization

### 5. Why Generic XML Service?

**Problem:** Challenge states "All banks will use a standard XML format."

**Solution:** Single `PaymentXmlService` that works for all banks.

**Benefits:**
- No duplication of XML generation logic
- Consistent output format
- Easier to maintain and test

## Performance Considerations

### Database Optimizations

1. **Indexes:**
   - `reference` (unique) - Fast duplicate detection
   - `client_id` - Fast client transaction lookups
   - `transaction_date` - Fast date range queries
   - Composite index on `(client_id, transaction_date)` - Optimized client history queries

2. **Bulk Operations:**
   - Transactions are inserted in batches
   - Single database transaction per webhook
   - Minimal query overhead

### Queue Configuration

1. **Redis Backend:**
   - In-memory queue for fast enqueue/dequeue
   - Persistent storage for reliability
   - Supports multiple workers

2. **Job Configuration:**
   - 3 retry attempts
   - 120-second timeout for large batches
   - Comprehensive error logging

### Scaling Strategy

**Horizontal Scaling:**
- Run multiple queue workers
- Load balance webhook endpoints
- Database read replicas for queries

**Vertical Scaling:**
- Increase PHP memory limit for larger batches
- Adjust MySQL buffer pool size
- Increase Redis max memory

## Adding a New Bank

To add support for a new bank format:

1. **Create Parser Class:**
```php
namespace App\Services\BankParsers;

class NewBankParser implements BankParserInterface
{
    public function parse(string $webhookBody): array
    {
        // Implement parsing logic
        return $transactions;
    }
}
```

2. **Register in Service:**
```php
// In TransactionImportService::getParser()
return match (strtolower($bankType)) {
    'foodics' => new FoodicsBankParser(),
    'acme' => new AcmeBankParser(),
    'newbank' => new NewBankParser(), // Add here
    default => throw new UnsupportedBankException(...)
};
```

3. **Add Tests:**
```php
namespace Tests\Unit\BankParsers;

class NewBankParserTest extends TestCase
{
    public function test_parses_correctly(): void
    {
        // Test implementation
    }
}
```

4. **Update Routes:**
```php
Route::post('/webhooks/newbank', [WebhookController::class, 'handle']);
```

## Common Commands

```bash
# Start all services
docker-compose up -d

# Stop all services
docker-compose down

# View logs
docker-compose logs -f app

# Access container
docker-compose exec app bash

# Run migrations
docker-compose exec app php artisan migrate

# Clear caches
docker-compose exec app php artisan cache:clear
docker-compose exec app php artisan config:clear

# Queue management
docker-compose exec app php artisan queue:work
docker-compose exec app php artisan queue:failed
docker-compose exec app php artisan queue:retry all

# Database access
docker-compose exec db mysql -u foodics -psecret foodics_pay
```

## Troubleshooting

### Webhooks Not Processing
**Check:** Is queue worker running?
```bash
docker-compose exec app php artisan queue:work
```

### Duplicate Transactions Not Prevented
**Check:** Migration created unique constraint?
```bash
docker-compose exec db mysql -u foodics -psecret foodics_pay -e "SHOW CREATE TABLE transactions;"
```

### Performance Issues
**Check:** Database indexes and queue configuration
```bash
docker-compose exec app php artisan queue:monitor
```

## Future Enhancements

- [ ] OAuth 2.0 authentication for webhook endpoints
- [ ] Webhook signature verification
- [ ] Rate limiting per client
- [ ] Transaction status tracking
- [ ] Webhook replay functionality
- [ ] Admin dashboard for monitoring
- [ ] Prometheus metrics export
- [ ] Multi-tenancy support

## License

This project was created as a coding challenge for Foodics.