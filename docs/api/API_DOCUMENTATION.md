# API Documentation

The 1000proxy API provides programmatic access to all system functionality including order management, user authentication, service provisioning, and administrative tasks. The API powers the modern, professional UI built with Livewire 3.x components and Heroicons integration.

## Base Information

- **Base URL**: `https://yourdomain.com/api`
- **API Version**: v1
- **Content Type**: `application/json`
- **Authentication**: Bearer Token (Laravel Sanctum)
- **Rate Limiting**: 60 requests per minute per IP
- **UI Integration**: Powers reactive Livewire components with real-time updates

## Authentication

### Bearer Token Authentication

All API requests require authentication using Bearer tokens:

```http
Authorization: Bearer your_api_token_here
```

### Obtaining API Tokens

#### For Customers
```http
POST /api/auth/login
Content-Type: application/json

{
    "email": "customer@example.com",
    "password": "password"
}
```

Response:
```json
{
    "status": "success",
    "data": {
        "token": "1|abc123...",
        "user": {
            "id": 1,
            "name": "Customer Name",
            "email": "customer@example.com",
            "type": "customer"
        }
    }
}
```

#### For Staff/Admin
```http
POST /api/auth/staff/login
Content-Type: application/json

{
    "email": "admin@example.com",
    "password": "password"
}
```

### Token Management

#### Refresh Token
```http
POST /api/auth/refresh
Authorization: Bearer current_token
### Customer Endpoints

#### Logout
```http
POST /api/auth/logout
Authorization: Bearer token_to_revoke
```

## Error Handling

### Standard Error Response

```json
{
    "status": "error",
    "message": "Error description",
    "errors": {
        "field_name": ["Validation error message"]
    },
    "code": "ERROR_CODE"
}
```

### HTTP Status Codes

- `200` - Success
- `201` - Created
- `400` - Bad Request
- `401` - Unauthorized
- `403` - Forbidden
- `404` - Not Found
- `422` - Validation Error
- `429` - Rate Limit Exceeded
- `500` - Internal Server Error

## Pagination

List endpoints support pagination:

```json
{
    "status": "success",
    "data": [...],
    "meta": {
        "current_page": 1,
        "last_page": 10,
        "per_page": 15,
        "total": 150
    },
    "links": {
        "first": "https://api.example.com/resource?page=1",
        "last": "https://api.example.com/resource?page=10",
        "prev": null,
        "next": "https://api.example.com/resource?page=2"
    }
}
```

## API Endpoints

### Authentication Endpoints

#### Customer Authentication

##### Login
```http
POST /api/auth/login
```

**Request Body:**
```json
{
    "email": "customer@example.com",
    "password": "password"
}
```

**Response:**
```json
{
    "status": "success",
    "data": {
        "token": "1|abc123...",
        "user": {
            "id": 1,
            "name": "John Doe",
            "email": "customer@example.com",
            "email_verified_at": "2025-01-01T00:00:00.000000Z",
            "created_at": "2025-01-01T00:00:00.000000Z"
        }
    }
}
```

##### Register
```http
POST /api/auth/register
```

**Request Body:**
```json
{
    "name": "John Doe",
    "email": "customer@example.com",
    "password": "password",
    "password_confirmation": "password"
}
```

##### Forgot Password
```http
POST /api/auth/forgot-password
```

**Request Body:**
```json
{
    "email": "customer@example.com"
}
```

##### Reset Password
```http
POST /api/auth/reset-password
```

**Request Body:**
```json
{
    "token": "reset_token",
    "email": "customer@example.com",
    "password": "new_password",
    "password_confirmation": "new_password"
}
```

### User Management

#### Get Current User
```http
GET /api/user
Authorization: Bearer token
```

**Response:**
```json
{
    "status": "success",
    "data": {
        "id": 1,
        "name": "John Doe",
        "email": "customer@example.com",
        "wallet_balance": "100.00",
        "total_orders": 5,
        "active_services": 3
    }
}
```

#### Update Profile
```http
PUT /api/user/profile
Authorization: Bearer token
```

**Request Body:**
```json
{
    "name": "John Smith",
    "phone": "+1234567890",
    "timezone": "America/New_York"
}
```

#### Change Password
```http
PUT /api/user/password
Authorization: Bearer token
```

**Request Body:**
```json
{
    "current_password": "old_password",
    "password": "new_password",
    "password_confirmation": "new_password"
}
```

### Product Management

#### List Server Plans
```http
GET /api/products/server-plans
```

**Query Parameters:**
- `category_id` - Filter by category
- `brand_id` - Filter by brand
- `protocol` - Filter by protocol type
- `price_min` - Minimum price filter
- `price_max` - Maximum price filter
- `page` - Page number
- `per_page` - Items per page (max 50)

**Response:**
```json
{
    "status": "success",
    "data": [
        {
            "id": 1,
            "name": "Basic VLESS Plan",
            "description": "Entry-level VLESS proxy service",
            "price": "9.99",
            "currency": "USD",
            "protocol": "vless",
            "features": [
                "1 Client Configuration",
                "Reality Support",
                "24/7 Support"
            ],
            "category": {
                "id": 1,
                "name": "Basic Plans"
            },
            "brand": {
                "id": 1,
                "name": "Premium Proxy"
            }
        }
    ],
    "meta": {
        "current_page": 1,
        "last_page": 1,
        "per_page": 15,
        "total": 1
    }
}
```

#### Get Server Plan Details
```http
GET /api/products/server-plans/{id}
```

**Response:**
```json
{
    "status": "success",
    "data": {
        "id": 1,
        "name": "Basic VLESS Plan",
        "description": "Entry-level VLESS proxy service",
        "price": "9.99",
        "currency": "USD",
        "protocol": "vless",
        "features": [
            "1 Client Configuration",
            "Reality Support",
            "24/7 Support"
        ],
        "specifications": {
            "bandwidth": "Unlimited",
            "locations": ["US", "EU", "Asia"],
            "concurrent_connections": 5
        },
        "available_servers": [
            {
                "id": 1,
                "name": "US-East-1",
                "location": "New York, USA",
                "status": "online"
            }
        ]
    }
}
```

### Order Management

#### Create Order
```http
POST /api/orders
Authorization: Bearer token
```

**Request Body:**
```json
{
    "items": [
        {
            "server_plan_id": 1,
            "server_id": 1,
            "quantity": 1,
            "configuration": {
                "client_name": "my-client",
                "protocol_settings": {
                    "flow": "xtls-rprx-vision"
                }
            }
        }
    ],
    "payment_method": "wallet"
}
```

**Response:**
```json
{
    "status": "success",
    "data": {
        "id": "ORD-2025-001",
        "total": "9.99",
        "currency": "USD",
        "status": "pending",
        "payment_method": "wallet",
        "items": [
            {
                "id": 1,
                "server_plan": {
                    "id": 1,
                    "name": "Basic VLESS Plan"
                },
                "quantity": 1,
                "price": "9.99"
            }
        ],
        "created_at": "2025-01-17T00:00:00.000000Z"
    }
}
```

#### List Orders
```http
GET /api/orders
Authorization: Bearer token
```

**Query Parameters:**
- `status` - Filter by order status
- `date_from` - Filter from date (Y-m-d)
- `date_to` - Filter to date (Y-m-d)
- `page` - Page number

**Response:**
```json
{
    "status": "success",
    "data": [
        {
            "id": "ORD-2025-001",
            "total": "9.99",
            "currency": "USD",
            "status": "completed",
            "payment_method": "wallet",
            "items_count": 1,
            "created_at": "2025-01-17T00:00:00.000000Z"
        }
    ]
}
```

#### Get Order Details
```http
GET /api/orders/{orderId}
Authorization: Bearer token
```

**Response:**
```json
{
    "status": "success",
    "data": {
        "id": "ORD-2025-001",
        "total": "9.99",
        "currency": "USD",
        "status": "completed",
        "payment_method": "wallet",
        "items": [
            {
                "id": 1,
                "server_plan": {
                    "id": 1,
                    "name": "Basic VLESS Plan",
                    "protocol": "vless"
                },
                "server": {
                    "id": 1,
                    "name": "US-East-1",
                    "location": "New York, USA"
                },
                "quantity": 1,
                "price": "9.99",
                "client_config": {
                    "id": "abc-123-def",
                    "name": "my-client",
                    "config_url": "vless://...",
                    "qr_code": "data:image/png;base64,..."
                }
            }
        ],
        "created_at": "2025-01-17T00:00:00.000000Z",
        "completed_at": "2025-01-17T00:05:00.000000Z"
    }
}
```

### Service Management

#### List Active Services
```http
GET /api/services
Authorization: Bearer token
```

**Response:**
```json
{
    "status": "success",
    "data": [
        {
            "id": 1,
            "name": "my-client",
            "protocol": "vless",
            "server": {
                "name": "US-East-1",
                "location": "New York, USA"
            },
            "status": "active",
            "traffic_used": "1.2 GB",
            "traffic_limit": "Unlimited",
            "expires_at": "2025-02-17T00:00:00.000000Z",
            "created_at": "2025-01-17T00:00:00.000000Z"
        }
    ]
}
```

#### Get Service Details
```http
GET /api/services/{serviceId}
Authorization: Bearer token
```

**Response:**
```json
{
    "status": "success",
    "data": {
        "id": 1,
        "name": "my-client",
        "protocol": "vless",
        "server": {
            "name": "US-East-1",
            "location": "New York, USA",
            "ip": "1.2.3.4"
        },
        "status": "active",
        "traffic_used": "1.2 GB",
        "traffic_limit": "Unlimited",
        "config_url": "vless://...",
        "qr_code": "data:image/png;base64,...",
        "connection_info": {
            "port": 443,
            "security": "reality",
            "flow": "xtls-rprx-vision"
        },
        "expires_at": "2025-02-17T00:00:00.000000Z",
        "created_at": "2025-01-17T00:00:00.000000Z"
    }
}
```

#### Reset Service Traffic
```http
POST /api/services/{serviceId}/reset-traffic
Authorization: Bearer token
```

#### Regenerate Service Configuration
```http
POST /api/services/{serviceId}/regenerate
Authorization: Bearer token
```

### Wallet Management

#### Get Wallet Balance
```http
GET /api/wallet
Authorization: Bearer token
```

**Response:**
```json
{
    "status": "success",
    "data": {
        "balance": "100.00",
        "currency": "USD",
        "pending_deposits": "0.00",
        "total_spent": "50.00",
        "total_deposited": "150.00"
    }
}
```

#### Get Wallet Transactions
```http
GET /api/wallet/transactions
Authorization: Bearer token
```

**Query Parameters:**
- `type` - Filter by transaction type (deposit, withdrawal, payment)
- `status` - Filter by status
- `date_from` - Filter from date
- `date_to` - Filter to date

**Response:**
```json
{
    "status": "success",
    "data": [
        {
            "id": 1,
            "type": "deposit",
            "amount": "50.00",
            "currency": "USD",
            "status": "completed",
            "description": "PayPal deposit",
            "reference": "PP-123456789",
            "created_at": "2025-01-17T00:00:00.000000Z"
        }
    ]
}
```

#### Create Deposit
```http
POST /api/wallet/deposit
Authorization: Bearer token
```

**Request Body:**
```json
{
    "amount": "50.00",
    "payment_method": "paypal"
}
```

**Response:**
```json
{
    "status": "success",
    "data": {
        "payment_url": "https://paypal.com/checkout/...",
        "transaction_id": "TXN-123456",
        "amount": "50.00",
        "currency": "USD"
    }
}
```

### Payment Methods

#### List Available Payment Methods
```http
GET /api/payment-methods
```

**Response:**
```json
{
    "status": "success",
    "data": [
        {
            "id": "paypal",
            "name": "PayPal",
            "type": "gateway",
            "enabled": true,
            "currencies": ["USD", "EUR", "GBP"]
        },
        {
            "id": "stripe",
            "name": "Credit Card",
            "type": "gateway",
            "enabled": true,
            "currencies": ["USD", "EUR"]
        },
        {
            "id": "bitcoin",
            "name": "Bitcoin",
            "type": "cryptocurrency",
            "enabled": true,
            "currencies": ["BTC"]
        }
    ]
}
```

### Support System

#### Create Support Ticket
```http
POST /api/support/tickets
Authorization: Bearer token
```

**Request Body:**
```json
{
    "subject": "Service not working",
    "category": "technical",
    "priority": "medium",
    "message": "My VLESS configuration is not connecting..."
}
```

#### List Support Tickets
```http
GET /api/support/tickets
Authorization: Bearer token
```

#### Get Ticket Details
```http
GET /api/support/tickets/{ticketId}
Authorization: Bearer token
```

#### Reply to Ticket
```http
POST /api/support/tickets/{ticketId}/replies
Authorization: Bearer token
```

## Webhooks

### Payment Webhooks

The API supports webhooks for payment notifications:

#### Webhook URL Structure
```
POST https://yourdomain.com/api/webhooks/{provider}
```

#### Supported Providers
- `stripe` - Stripe payment webhooks
- `paypal` - PayPal IPN webhooks
- `bitcoin` - Bitcoin payment notifications

#### Webhook Security

All webhooks are verified using provider-specific signature verification:

```php
// Example webhook verification
$signature = $request->header('X-Provider-Signature');
$payload = $request->getContent();
$isValid = $this->verifyWebhookSignature($payload, $signature);
```

## Rate Limiting

### Default Limits
- **Public endpoints**: 60 requests per minute per IP
- **Authenticated endpoints**: 100 requests per minute per user
- **Admin endpoints**: 200 requests per minute per admin

### Rate Limit Headers
```http
X-RateLimit-Limit: 60
X-RateLimit-Remaining: 59
X-RateLimit-Reset: 1642425600
```

### Rate Limit Exceeded Response
```json
{
    "status": "error",
    "message": "Too Many Attempts.",
    "retry_after": 60
}
```

## SDK and Code Examples

### PHP SDK Example

```php
use GuzzleHttp\Client;

class ProxyAPIClient
{
    private $client;
    private $token;

    public function __construct($baseUrl, $token)
    {
        $this->client = new Client(['base_uri' => $baseUrl]);
        $this->token = $token;
    }

    public function getServerPlans()
    {
        $response = $this->client->get('/api/products/server-plans', [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->token,
                'Accept' => 'application/json'
            ]
        ]);

        return json_decode($response->getBody(), true);
    }

    public function createOrder($items)
    {
        $response = $this->client->post('/api/orders', [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->token,
                'Accept' => 'application/json',
                'Content-Type' => 'application/json'
            ],
            'json' => ['items' => $items]
        ]);

        return json_decode($response->getBody(), true);
    }
}
```

### JavaScript/Node.js Example

```javascript
class ProxyAPIClient {
    constructor(baseUrl, token) {
        this.baseUrl = baseUrl;
        this.token = token;
    }

    async request(method, endpoint, data = null) {
        const config = {
            method,
            headers: {
                'Authorization': `Bearer ${this.token}`,
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            }
        };

        if (data) {
            config.body = JSON.stringify(data);
        }

        const response = await fetch(`${this.baseUrl}${endpoint}`, config);
        return await response.json();
    }

    async getServerPlans() {
        return await this.request('GET', '/api/products/server-plans');
    }

    async createOrder(items) {
        return await this.request('POST', '/api/orders', { items });
    }
}
```

### Python Example

```python
import requests

class ProxyAPIClient:
    def __init__(self, base_url, token):
        self.base_url = base_url
        self.token = token
        self.session = requests.Session()
        self.session.headers.update({
            'Authorization': f'Bearer {token}',
            'Content-Type': 'application/json',
            'Accept': 'application/json'
        })

    def get_server_plans(self):
        response = self.session.get(f'{self.base_url}/api/products/server-plans')
        return response.json()

    def create_order(self, items):
        data = {'items': items}
        response = self.session.post(f'{self.base_url}/api/orders', json=data)
        return response.json()
```

## Testing

### Postman Collection

A complete Postman collection is available for testing all API endpoints:

```bash
# Download Postman collection
curl -O https://raw.githubusercontent.com/your-repo/1000proxy/main/api-collection.json
```

### API Testing Environment

```bash
# Test API with curl
curl -X GET \
  https://yourdomain.com/api/products/server-plans \
  -H 'Accept: application/json' \
  -H 'Authorization: Bearer your_token_here'
```

## Versioning

The API uses URL versioning:
- Current version: `v1`
- Future versions: `v2`, `v3`, etc.

Deprecated versions will be supported for at least 6 months after a new version is released.

## Support

For API support and questions:
- Documentation: This document
- Email: api-support@yourdomain.com
- GitHub Issues: Repository issue tracker
- Status Page: https://status.yourdomain.com

---

This API documentation provides comprehensive coverage of all available endpoints and functionality in the 1000proxy system.
