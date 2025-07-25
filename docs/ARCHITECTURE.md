# 1000proxy Architecture Documentation

## System Overview

1000proxy is a Laravel-based proxy client sales platform that automates the process of purchasing, provisioning, and managing VPN/proxy clients through XUI panels. The system integrates with multiple payment gateways and provides comprehensive user management and monitoring capabilities.

## Architecture Diagram

```
                    ┌─────────────────┐
                    │   Load Balancer │
                    │    (Nginx)      │
                    └─────────────────┘
                             │
                    ┌─────────────────┐
                    │   Web Server    │
                    │    (Nginx)      │
                    └─────────────────┘
                             │
                    ┌─────────────────┐
                    │  PHP-FPM Pool   │
                    │   (Laravel)     │
                    └─────────────────┘
                             │
           ┌─────────────────┼─────────────────┐
           │                 │                 │
    ┌─────────────┐  ┌─────────────┐  ┌─────────────┐
    │   Database  │  │    Redis    │  │   Queue     │
    │   (MySQL)   │  │   (Cache)   │  │  Workers    │
    └─────────────┘  └─────────────┘  └─────────────┘
                             │
                    ┌─────────────────┐
                    │  External APIs  │
                    │  (XUI Panels,   │
                    │   Payments)     │
                    └─────────────────┘
```

## Component Architecture

### 1. Application Layer

#### Web Framework
- **Laravel 12.x**: Main application framework
- **Livewire**: For reactive UI components
- **Filament**: Admin panel framework
- **Blade**: Template engine

#### Core Components
```php
app/
├── Console/Commands/          # Artisan commands
├── Http/
│   ├── Controllers/          # Request handling
│   ├── Middleware/           # Request filtering
│   └── Requests/             # Form validation
├── Models/                   # Eloquent models
├── Services/                 # Business logic
├── Jobs/                     # Background tasks
├── Mail/                     # Email notifications
└── Providers/                # Service providers
```

### 2. Data Layer

#### Database Schema
```sql
┌─────────────────────────────────────────────────────────────┐
│                    Core Tables                              │
├─────────────────────────────────────────────────────────────┤
│ users                                                       │
│ ├── id, name, email, password, role, is_active            │
│ └── created_at, updated_at, last_login_at                  │
├─────────────────────────────────────────────────────────────┤
│ servers                                                     │
│ ├── id, name, host, port, username, password              │
│ ├── server_category_id, server_brand_id                    │
│ └── is_active, location, sort_order                        │
├─────────────────────────────────────────────────────────────┤
│ server_plans                                                │
│ ├── id, name, description, price, currency                 │
│ ├── duration_days, max_connections, bandwidth_limit_gb     │
│ └── server_category_id, server_brand_id, is_active         │
├─────────────────────────────────────────────────────────────┤
│ orders                                                      │
│ ├── id, user_id, status, payment_status                    │
│ └── total_amount, grand_amount, created_at                 │
├─────────────────────────────────────────────────────────────┤
│ order_items                                                 │
│ ├── id, order_id, server_plan_id, server_id               │
│ └── quantity, unit_amount, total_amount                    │
├─────────────────────────────────────────────────────────────┤
│ server_clients                                              │
│ ├── id, user_id, server_id, order_id, uuid                │
│ ├── email, subscription_link, qr_code                      │
│ └── is_active, expires_at, bandwidth_used                  │
└─────────────────────────────────────────────────────────────┘
```

#### Database Relationships
```php
// User relationships
User::hasMany(Order::class)
User::hasMany(ServerClient::class)
User::hasMany(WalletTransaction::class)

// Order relationships
Order::belongsTo(User::class)
Order::hasMany(OrderItem::class)
Order::hasOne(Invoice::class)
Order::hasMany(ServerClient::class)

// Server relationships
Server::hasMany(ServerClient::class)
Server::hasMany(OrderItem::class)
Server::belongsTo(ServerCategory::class)
Server::belongsTo(ServerBrand::class)

// ServerPlan relationships
ServerPlan::hasMany(OrderItem::class)
ServerPlan::belongsTo(ServerCategory::class)
ServerPlan::belongsTo(ServerBrand::class)
```

### 3. Service Layer

#### Core Services
```php
app/Services/
├── XUIService.php            # XUI panel integration
├── PaymentService.php        # Payment processing
├── CacheService.php          # Caching operations
├── EmailService.php          # Email notifications
├── QRCodeService.php         # QR code generation
└── SubscriptionService.php   # Subscription management
```

#### XUI Service Architecture
```php
class XUIService
{
    // Authentication with XUI panels
    public function authenticate(string $username, string $password, string $panelUrl): array
    
    // Client management
    public function createClient(array $clientData, string $session): array
    public function updateClient(string $uuid, array $updateData, string $session): array
    public function deleteClient(int $inboundId, string $uuid, string $session): array
    
    // Subscription management
    public function generateSubscriptionLink(array $serverConfig): string
    public function generateQRCode(string $subscriptionLink): string
    
    // Statistics and monitoring
    public function getClientStats(string $uuid, string $session): array
    public function getInboundStats(int $inboundId, string $session): array
}
```

### 4. Queue System

#### Job Processing
```php
app/Jobs/
├── ProcessXuiOrder.php       # Process completed orders
├── UpdateClientStats.php     # Update client statistics
├── SendEmailNotification.php # Send email notifications
├── CleanupExpiredClients.php # Clean up expired clients
└── SyncServerStats.php       # Sync server statistics
```

#### Queue Configuration
```php
// config/queue.php
'connections' => [
    'redis' => [
        'driver' => 'redis',
        'connection' => 'default',
        'queue' => env('REDIS_QUEUE', 'default'),
        'retry_after' => 90,
        'block_for' => null,
    ],
]

// Queue priorities
'high' => ProcessXuiOrder::class,      // Immediate processing
'default' => UpdateClientStats::class,  // Regular processing
'low' => CleanupExpiredClients::class, // Background processing
```

### 5. Caching Strategy

#### Cache Layers
```php
// Application cache (Redis)
Cache::remember('active_servers', 1800, function() {
    return Server::where('is_active', true)->get();
});

// Database query cache
Cache::remember('user_orders_' . $userId, 300, function() use ($userId) {
    return Order::where('user_id', $userId)->with('orderItems')->get();
});

// Session cache
Cache::remember('xui_session_' . $serverId, 1800, function() use ($serverId) {
    return $this->authenticateWithXUI($serverId);
});
```

#### Cache Invalidation
```php
// Cache invalidation strategies
class CacheService
{
    public function invalidateUserCaches(int $userId): void
    public function invalidateServerCaches(int $serverId): void
    public function invalidateDashboardCaches(): void
}
```

### 6. Security Architecture

#### Authentication & Authorization
```php
// Multi-guard authentication
'guards' => [
    'web' => [
        'driver' => 'session',
        'provider' => 'users',
    ],
    'api' => [
        'driver' => 'sanctum',
        'provider' => 'users',
    ],
    'admin' => [
        'driver' => 'session',
        'provider' => 'users',
    ],
]

// Role-based access control
class User extends Model
{
    public function hasRole(string $role): bool
    public function canAccessPanel(): bool
    public function can(string $ability, $arguments = []): bool
}
```

#### Security Middleware Stack
```php
// Middleware pipeline
protected $middleware = [
    \App\Http\Middleware\TrustProxies::class,
    \App\Http\Middleware\SecurityHeaders::class,
    \App\Http\Middleware\ApiRateLimit::class,
    \App\Http\Middleware\AuditLogger::class,
];
```

### 7. Payment Integration

#### Payment Gateway Architecture
```php
// Payment service abstraction
interface PaymentGatewayInterface
{
    public function createPayment(array $data): array;
    public function getPaymentStatus(string $paymentId): array;
    public function processWebhook(array $data): bool;
}

// NowPayments implementation
class NowPaymentsGateway implements PaymentGatewayInterface
{
    public function createPayment(array $data): array
    {
        return $this->client->post('/v1/payment', $data);
    }
    
    public function getPaymentStatus(string $paymentId): array
    {
        return $this->client->get("/v1/payment/{$paymentId}");
    }
    
    public function processWebhook(array $data): bool
    {
        // Webhook signature verification
        // Payment status update
        // Order processing trigger
    }
}
```

#### Payment Flow
```
Customer Order → Payment Gateway → Webhook → Order Processing → Client Creation
      ↓               ↓               ↓            ↓              ↓
   Order Created   Payment Link    Status Update  XUI API Call  Client Delivered
```

### 8. XUI Panel Integration

#### Panel Communication
```php
// XUI API integration
class XUIService
{
    private function makeRequest(string $endpoint, array $data = [], string $method = 'POST'): array
    {
        $response = Http::withHeaders([
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
            'Cookie' => $this->getSessionCookie($serverId),
        ])->$method($this->buildUrl($endpoint), $data);
        
        return $response->json();
    }
    
    public function createClient(array $clientData, string $session): array
    {
        $endpoint = '/panel/api/inbounds/addClient';
        return $this->makeRequest($endpoint, $clientData, 'POST');
    }
}
```

#### Protocol Support
```php
// Supported protocols configuration
'protocols' => [
    'vless' => [
        'default_port' => 443,
        'encryption' => 'none',
        'flow' => 'xtls-rprx-vision',
    ],
    'vmess' => [
        'default_port' => 443,
        'encryption' => 'auto',
        'security' => 'tls',
    ],
    'trojan' => [
        'default_port' => 443,
        'encryption' => 'none',
        'security' => 'tls',
    ],
    'shadowsocks' => [
        'default_port' => 443,
        'encryption' => 'aes-256-gcm',
        'security' => 'none',
    ],
]
```

### 9. Monitoring & Logging

#### Log Channels
```php
// Specialized log channels
'channels' => [
    'audit' => [
        'driver' => 'daily',
        'path' => storage_path('logs/audit.log'),
        'level' => 'info',
        'days' => 90,
    ],
    'security' => [
        'driver' => 'daily',
        'path' => storage_path('logs/security.log'),
        'level' => 'warning',
        'days' => 365,
    ],
    'payments' => [
        'driver' => 'daily',
        'path' => storage_path('logs/payments.log'),
        'level' => 'info',
        'days' => 90,
    ],
    'xui' => [
        'driver' => 'daily',
        'path' => storage_path('logs/xui.log'),
        'level' => 'debug',
        'days' => 30,
    ],
]
```

#### Performance Monitoring
```php
// Performance metrics collection
class PerformanceMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $startTime = microtime(true);
        $response = $next($request);
        $duration = microtime(true) - $startTime;
        
        Log::channel('performance')->info('Request processed', [
            'url' => $request->fullUrl(),
            'method' => $request->method(),
            'duration_ms' => round($duration * 1000, 2),
            'memory_usage' => memory_get_peak_usage(true),
            'status_code' => $response->getStatusCode(),
        ]);
        
        return $response;
    }
}
```

### 10. API Architecture

#### RESTful API Design
```php
// API route structure
Route::group(['prefix' => 'api', 'middleware' => ['auth:sanctum']], function() {
    Route::apiResource('orders', OrderController::class);
    Route::apiResource('server-plans', ServerPlanController::class);
    Route::apiResource('payments', PaymentController::class);
    Route::apiResource('server-clients', ServerClientController::class);
});

// Consistent API response format
class ApiResponse
{
    public static function success($data = null, string $message = 'Success', int $code = 200): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data,
        ], $code);
    }
    
    public static function error(string $message, $errors = null, int $code = 400): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'errors' => $errors,
        ], $code);
    }
}
```

## Deployment Architecture

### Production Environment
```
                Internet
                    │
            ┌───────────────┐
            │ Load Balancer │
            │   (Nginx)     │
            └───────────────┘
                    │
        ┌───────────────────────────┐
        │      Web Servers          │
        │  ┌─────────┐ ┌─────────┐  │
        │  │ Server1 │ │ Server2 │  │
        │  └─────────┘ └─────────┘  │
        └───────────────────────────┘
                    │
        ┌───────────────────────────┐
        │     Database Cluster      │
        │  ┌─────────┐ ┌─────────┐  │
        │  │ Primary │ │ Replica │  │
        │  └─────────┘ └─────────┘  │
        └───────────────────────────┘
                    │
        ┌───────────────────────────┐
        │      Redis Cluster        │
        │  ┌─────────┐ ┌─────────┐  │
        │  │ Master  │ │ Replica │  │
        │  └─────────┘ └─────────┘  │
        └───────────────────────────┘
```

### Scalability Considerations

#### Horizontal Scaling
- **Web Servers**: Multiple PHP-FPM instances behind load balancer
- **Database**: Read replicas for query distribution
- **Cache**: Redis cluster with sharding
- **Queue Workers**: Multiple worker processes with job distribution

#### Vertical Scaling
- **Memory**: 8GB+ RAM for high-traffic environments
- **CPU**: Multi-core processors for PHP-FPM processes
- **Storage**: SSD storage for database and cache performance

## Performance Optimization

### Database Optimization
```sql
-- Query optimization indexes
CREATE INDEX idx_orders_user_created ON orders(user_id, created_at);
CREATE INDEX idx_server_clients_user_server ON server_clients(user_id, server_id);
CREATE INDEX idx_server_plans_active_price ON server_plans(is_active, price);

-- Partitioning for large tables
ALTER TABLE client_traffics PARTITION BY RANGE (YEAR(created_at)) (
    PARTITION p2023 VALUES LESS THAN (2024),
    PARTITION p2024 VALUES LESS THAN (2025),
    PARTITION p2025 VALUES LESS THAN (2026),
    PARTITION p_future VALUES LESS THAN MAXVALUE
);
```

### Cache Optimization
```php
// Multi-level caching
class CacheService
{
    // L1: Application cache (Redis)
    // L2: Database query cache
    // L3: HTTP response cache
    // L4: CDN cache for static assets
    
    public function getWithMultiLevel(string $key, callable $callback, int $ttl = 3600)
    {
        // Try L1 cache first
        if ($data = Cache::get($key)) {
            return $data;
        }
        
        // Generate data and cache at all levels
        $data = $callback();
        Cache::put($key, $data, $ttl);
        
        return $data;
    }
}
```

### Queue Optimization
```php
// Queue priority and batching
class OrderProcessingJob implements ShouldQueue
{
    public $queue = 'high';
    public $tries = 3;
    public $maxExceptions = 3;
    public $backoff = [10, 30, 60];
    
    public function handle()
    {
        // Batch processing for efficiency
        $orders = Order::where('status', 'pending')
            ->limit(10)
            ->get();
            
        foreach ($orders as $order) {
            $this->processOrder($order);
        }
    }
}
```

## Security Implementation

### Input Validation
```php
// Form request validation
class CreateOrderRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'items' => 'required|array|min:1|max:10',
            'items.*.server_plan_id' => 'required|integer|exists:server_plans,id',
            'items.*.quantity' => 'required|integer|min:1|max:100',
        ];
    }
    
    public function authorize(): bool
    {
        return auth()->check() && $this->user()->can('create orders');
    }
}
```

### SQL Injection Prevention
```php
// Parameterized queries
$orders = Order::where('user_id', $userId)
    ->where('status', $status)
    ->with(['orderItems' => function($query) {
        $query->select('id', 'order_id', 'server_plan_id', 'quantity');
    }])
    ->get();
```

### XSS Prevention
```php
// Output escaping
{{ $user->name }}                    // Escaped
{!! $trustedHtml !!}                // Unescaped (trusted content only)

// Input sanitization
$cleanInput = strip_tags($request->input('content'));
$cleanInput = htmlspecialchars($cleanInput, ENT_QUOTES, 'UTF-8');
```

## Error Handling

### Exception Handling
```php
// Global exception handler
class Handler extends ExceptionHandler
{
    public function render($request, Throwable $exception)
    {
        if ($request->expectsJson()) {
            return $this->handleApiException($exception);
        }
        
        return parent::render($request, $exception);
    }
    
    private function handleApiException(Throwable $exception): JsonResponse
    {
        // Log exception
        Log::error('API Exception', [
            'message' => $exception->getMessage(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => $exception->getTraceAsString(),
        ]);
        
        // Return user-friendly error
        return ApiResponse::error(
            'An error occurred while processing your request.',
            null,
            500
        );
    }
}
```

## Testing Strategy

### Test Architecture
```php
tests/
├── Unit/
│   ├── Models/              # Model tests
│   ├── Services/            # Service tests
│   └── Jobs/                # Job tests
├── Feature/
│   ├── Api/                 # API endpoint tests
│   ├── Auth/                # Authentication tests
│   └── Payment/             # Payment flow tests
└── Integration/
    ├── XUI/                 # XUI panel integration tests
    └── Database/            # Database integration tests
```

### Test Data Management
```php
// Database factories
class OrderFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'status' => 'pending',
            'payment_status' => 'pending',
            'total_amount' => $this->faker->randomFloat(2, 10, 1000),
            'grand_amount' => $this->faker->randomFloat(2, 10, 1000),
        ];
    }
}
```

This architecture documentation provides a comprehensive view of the 1000proxy system design, implementation patterns, and operational considerations.
