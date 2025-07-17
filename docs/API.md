# 1000proxy API Documentation

## Overview
The 1000proxy API provides comprehensive endpoints for managing proxy clients, payments, and server configurations through XUI panels. This API follows RESTful principles and includes authentication, rate limiting, and comprehensive error handling.

## Authentication
All API endpoints require authentication using Laravel Sanctum tokens.

### Login
```http
POST /api/login
Content-Type: application/json

{
    "email": "user@example.com",
    "password": "password"
}
```

### Response
```json
{
    "success": true,
    "user": {
        "id": 1,
        "name": "John Doe",
        "email": "user@example.com",
        "role": "user"
    },
    "token": "sanctum_token_here"
}
```

## Rate Limiting
- Authentication endpoints: 5 requests per minute
- Payment endpoints: 60 requests per minute
- General API endpoints: 60 requests per minute
- Webhooks: 100 requests per minute

## Error Handling
All endpoints return consistent error responses:

```json
{
    "success": false,
    "error": "Error message",
    "errors": {
        "field": ["Validation error message"]
    }
}
```

## Payment Endpoints

### Create Crypto Payment
Creates a new cryptocurrency payment for an order.

```http
POST /api/payments/crypto
Authorization: Bearer {token}
Content-Type: application/json

{
    "order_id": 123,
    "payment_method": "crypto",
    "currency": "BTC",
    "amount": 99.99
}
```

#### Response
```json
{
    "success": true,
    "data": {
        "payment_id": "payment_123",
        "payment_status": "waiting",
        "pay_address": "bc1qxy2kgdygjrsqtzq2n0yrf2493p83kkfjhx0wlh",
        "price_amount": 99.99,
        "price_currency": "usd",
        "pay_amount": 0.00234,
        "pay_currency": "btc"
    }
}
```

### Create Invoice
Creates a payment invoice for an order.

```http
POST /api/payments/invoice
Authorization: Bearer {token}
Content-Type: application/json

{
    "order_id": 123,
    "payment_method": "crypto",
    "currency": "BTC",
    "amount": 99.99
}
```

#### Response
```json
{
    "success": true,
    "data": {
        "invoice_id": "invoice_123",
        "invoice_url": "https://nowpayments.io/payment/invoice_123",
        "payment_id": "payment_123",
        "order_id": "123"
    }
}
```

### Get Payment Status
Retrieves the current status of a payment.

```http
GET /api/payments/status/{payment_id}
Authorization: Bearer {token}
```

#### Response
```json
{
    "success": true,
    "data": {
        "payment_id": "payment_123",
        "payment_status": "confirmed",
        "pay_amount": 0.00234,
        "actually_paid": 0.00234,
        "created_at": "2025-07-08T12:00:00Z",
        "updated_at": "2025-07-08T12:30:00Z"
    }
}
```

### Get Price Estimate
Get price estimate for currency conversion.

```http
POST /api/payments/estimate
Authorization: Bearer {token}
Content-Type: application/json

{
    "amount": 100,
    "currency_from": "USD",
    "currency_to": "BTC"
}
```

#### Response
```json
{
    "success": true,
    "data": {
        "currency_from": "usd",
        "amount_from": 100,
        "currency_to": "btc",
        "estimated_amount": 0.00234
    }
}
```

### Get Available Currencies
Returns list of supported currencies.

```http
GET /api/payments/currencies
Authorization: Bearer {token}
```

#### Response
```json
{
    "success": true,
    "data": {
        "currencies": ["BTC", "ETH", "XMR", "LTC", "USD", "EUR", "GBP"]
    }
}
```

## Server Management Endpoints

### Get Server Plans
Returns all available server plans.

```http
GET /api/server-plans
Authorization: Bearer {token}
```

#### Response
```json
{
    "success": true,
    "data": [
        {
            "id": 1,
            "name": "Premium Plan",
            "description": "High-performance proxy plan",
            "price": 99.99,
            "currency": "USD",
            "duration_days": 30,
            "max_connections": 5,
            "bandwidth_limit_gb": 100,
            "features": ["High speed", "24/7 support", "Multiple protocols"],
            "server_category": {
                "id": 1,
                "name": "Premium Servers"
            },
            "server_brand": {
                "id": 1,
                "name": "CloudFlare"
            }
        }
    ]
}
```

### Create Server Plan
Creates a new server plan (Admin only).

```http
POST /api/server-plans
Authorization: Bearer {token}
Content-Type: application/json

{
    "name": "Basic Plan",
    "description": "Entry-level proxy plan",
    "price": 49.99,
    "currency": "USD",
    "duration_days": 30,
    "max_connections": 2,
    "bandwidth_limit_gb": 50,
    "server_category_id": 1,
    "server_brand_id": 1,
    "features": ["High speed", "Basic support"],
    "is_active": true,
    "is_featured": false
}
```

### Update Server Plan
Updates an existing server plan (Admin only).

```http
PUT /api/server-plans/{id}
Authorization: Bearer {token}
Content-Type: application/json

{
    "name": "Updated Plan Name",
    "price": 79.99,
    "max_connections": 3
}
```

### Delete Server Plan
Deletes a server plan (Admin only).

```http
DELETE /api/server-plans/{id}
Authorization: Bearer {token}
```

## Order Management Endpoints

### Create Order
Creates a new order for server plans.

```http
POST /api/orders
Authorization: Bearer {token}
Content-Type: application/json

{
    "items": [
        {
            "server_plan_id": 1,
            "server_id": 1,
            "quantity": 1
        }
    ]
}
```

#### Response
```json
{
    "success": true,
    "data": {
        "id": 123,
        "user_id": 1,
        "status": "pending",
        "payment_status": "pending",
        "total_amount": 99.99,
        "grand_amount": 99.99,
        "created_at": "2025-07-08T12:00:00Z",
        "items": [
            {
                "id": 1,
                "server_plan_id": 1,
                "server_id": 1,
                "quantity": 1,
                "unit_amount": 99.99,
                "total_amount": 99.99
            }
        ]
    }
}
```

### Get User Orders
Returns all orders for the authenticated user.

```http
GET /api/orders
Authorization: Bearer {token}
```

#### Query Parameters
- `status`: Filter by order status (pending, processing, completed, cancelled)
- `payment_status`: Filter by payment status (pending, paid, failed)
- `page`: Page number for pagination
- `per_page`: Number of items per page (default: 15)

### Get Order Details
Returns details of a specific order.

```http
GET /api/orders/{id}
Authorization: Bearer {token}
```

## User Management Endpoints

### Get User Profile
Returns the authenticated user's profile.

```http
GET /api/user/profile
Authorization: Bearer {token}
```

#### Response
```json
{
    "success": true,
    "data": {
        "id": 1,
        "name": "John Doe",
        "email": "user@example.com",
        "role": "user",
        "is_active": true,
        "created_at": "2025-01-01T00:00:00Z",
        "last_login_at": "2025-07-08T12:00:00Z"
    }
}
```

### Update User Profile
Updates the authenticated user's profile.

```http
PUT /api/user/profile
Authorization: Bearer {token}
Content-Type: application/json

{
    "name": "Updated Name",
    "email": "updated@example.com"
}
```

### Get User Server Clients
Returns all server clients for the authenticated user.

```http
GET /api/user/server-clients
Authorization: Bearer {token}
```

#### Response
```json
{
    "success": true,
    "data": [
        {
            "id": 1,
            "uuid": "550e8400-e29b-41d4-a716-446655440000",
            "email": "user@example.com",
            "subscription_link": "vless://550e8400-e29b-41d4-a716-446655440000@server.com:443?path=/path&security=tls",
            "qr_code": "data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAA...",
            "is_active": true,
            "expires_at": "2025-08-08T12:00:00Z",
            "bandwidth_used": 1024000000,
            "bandwidth_limit": 107374182400,
            "server": {
                "id": 1,
                "name": "Premium Server",
                "host": "server.com",
                "location": "US East"
            }
        }
    ]
}
```

## Webhook Endpoints

### NowPayments Webhook
Handles payment status updates from NowPayments.

```http
POST /api/webhooks/nowpayments
Content-Type: application/json
X-Nowpayments-Sig: {signature}

{
    "payment_id": "payment_123",
    "order_id": "123",
    "payment_status": "finished",
    "price_amount": 99.99,
    "price_currency": "usd",
    "pay_amount": 0.00234,
    "pay_currency": "btc",
    "created_at": "2025-07-08T12:00:00Z",
    "updated_at": "2025-07-08T12:30:00Z"
}
```

## HTTP Status Codes

### Success Codes
- `200 OK`: Request successful
- `201 Created`: Resource created successfully
- `204 No Content`: Request successful, no content to return

### Error Codes
- `400 Bad Request`: Invalid request format or parameters
- `401 Unauthorized`: Authentication required or invalid credentials
- `403 Forbidden`: Access denied
- `404 Not Found`: Resource not found
- `422 Unprocessable Entity`: Validation errors
- `429 Too Many Requests`: Rate limit exceeded
- `500 Internal Server Error`: Server error

## Security Headers
All API responses include security headers:
- `X-Content-Type-Options: nosniff`
- `X-Frame-Options: DENY`
- `X-XSS-Protection: 1; mode=block`
- `Strict-Transport-Security: max-age=31536000; includeSubDomains`

## Supported Protocols
- VLESS
- VMESS
- TROJAN
- SHADOWSOCKS
- HTTP/HTTPS Proxy
- SOCKS5 Proxy

## Supported Currencies
### Fiat Currencies
- USD (US Dollar)
- EUR (Euro)
- GBP (British Pound)
- CAD (Canadian Dollar)
- AUD (Australian Dollar)
- JPY (Japanese Yen)

### Cryptocurrencies
- BTC (Bitcoin)
- ETH (Ethereum)
- XMR (Monero)
- LTC (Litecoin)
- SOL (Solana)

## Client Libraries
Official client libraries are available for:
- PHP
- JavaScript/Node.js
- Python
- Go

## Support
For API support and questions:
- Email: support@1000proxy.io
- Documentation: https://docs.1000proxy.io
- Status Page: https://status.1000proxy.io

## Changelog
### v1.0.0 (2025-07-08)
- Initial API release
- Payment processing endpoints
- Server management endpoints
- User management endpoints
- Webhook support
- Comprehensive error handling
- Rate limiting implementation
- Security enhancements
