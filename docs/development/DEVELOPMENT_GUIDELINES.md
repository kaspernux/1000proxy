# Development Guidelines

This document provides comprehensive guidelines for developers working on the 1000proxy platform.

## Table of Contents

1. [Development Environment Setup](#development-environment-setup)
2. [Coding Standards](#coding-standards)
3. [Git Workflow](#git-workflow)
4. [Testing Guidelines](#testing-guidelines)
5. [Code Review Process](#code-review-process)
6. [Database Guidelines](#database-guidelines)
7. [API Development](#api-development)
8. [Security Guidelines](#security-guidelines)
9. [Performance Optimization](#performance-optimization)
10. [Documentation Standards](#documentation-standards)

## Development Environment Setup

### Prerequisites

#### System Requirements

```bash
# Minimum requirements for development
- OS: Windows 10/11, macOS 10.15+, or Ubuntu 20.04+
# Required software
- PHP 8.3+

#### Development Tools

```bash
# Code Editors (choose one)
- Visual Studio Code (recommended)
- PHPStorm
- Sublime Text
- Vim/Neovim

# Required VS Code Extensions
- PHP Intelephense
- Laravel Extension Pack
- GitLens
- ESLint
- Prettier
- Thunder Client (for API testing)
```

### Local Environment Setup

#### Using Laravel Sail (Recommended)

```bash
# Clone repository
git clone https://github.com/your-org/1000proxy.git
cd 1000proxy

# Install dependencies
composer install

# Copy environment file
cp .env.example .env

# Generate application key
php artisan key:generate

# Start Sail environment
./vendor/bin/sail up -d

# Run migrations and seeders
./vendor/bin/sail artisan migrate:fresh --seed

# Install frontend dependencies
./vendor/bin/sail npm install
./vendor/bin/sail npm run dev

# Access application at http://localhost
```

#### Manual Setup

```bash
# Install PHP dependencies
composer install
In cache tagging, prefer `customer.{id}` namespaces for customer-scoped caches.
# Copy and configure environment
cp .env.example .env
php artisan key:generate

# Configure database in .env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=proxy_dev
DB_USERNAME=root
DB_PASSWORD=

# Run migrations
php artisan migrate:fresh --seed

# Install frontend dependencies
npm install
npm run dev

# Start development server
php artisan serve
```

### IDE Configuration

#### VS Code Settings

Create `.vscode/settings.json`:

```json
{
    "php.validate.executablePath": "/usr/bin/php",
    "php.suggest.basic": false,
    "intelephense.files.maxSize": 5000000,
    "editor.formatOnSave": true,
    "editor.codeActionsOnSave": {
        "source.fixAll.eslint": true
    },
    "files.associations": {
        "*.blade.php": "blade"
    },
    "emmet.includeLanguages": {
        "blade": "html"
    }
}
```

#### PHPStorm Configuration

```xml
<!-- Code Style: PSR-12 -->
<component name="PhpCodeStyleSettings">
    <option name="ALIGN_KEY_VALUE_PAIRS" value="true"/>
    <option name="ALIGN_PHPDOC_PARAM_NAMES" value="true"/>
    <option name="ALIGN_PHPDOC_COMMENTS" value="true"/>
    <option name="COMMA_AFTER_LAST_ARRAY_ELEMENT" value="true"/>
    <option name="PHPDOC_BLANK_LINE_BEFORE_TAGS" value="true"/>
    <option name="PHPDOC_BLANK_LINES_AROUND_PARAMETERS" value="true"/>
    <option name="PHPDOC_WRAP_LONG_LINES" value="true"/>
</component>
```

## Coding Standards

### PHP Standards (PSR-12)

#### Basic Formatting

```php
<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Collection;

/**
 * User service for managing user operations
 */
class UserService
{
    public function __construct(
        private readonly User $userModel,
        private readonly NotificationService $notificationService
    ) {}

    /**
     * Create a new user
     */
    public function createUser(array $data): User
    {
        $user = $this->userModel->create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => bcrypt($data['password']),
        ]);

        $this->notificationService->sendWelcomeEmail($user);

        return $user;
    }
}
```

#### Naming Conventions

```php
// Classes: PascalCase
class UserService {}
class PaymentController {}

// Methods and Variables: camelCase
public function getUserById(int $userId): ?User {}
private $userRepository;

// Constants: SCREAMING_SNAKE_CASE
const DEFAULT_TIMEOUT = 30;
const API_VERSION = 'v1';

// Database Tables: snake_case (plural)
users, proxy_services, payment_transactions

// Model Properties: snake_case
$user->created_at
$service->expires_at
```

#### Method Documentation

```php
/**
 * Create a new proxy service for the user
 *
 * @param User $user The user to create service for
 * @param array $serviceData Service configuration data
 * @return ProxyService The created service
 * 
 * @throws InvalidServiceDataException When service data is invalid
 * @throws InsufficientFundsException When user has insufficient balance
 */
public function createProxyService(User $user, array $serviceData): ProxyService
{
    $this->validateServiceData($serviceData);
    $this->checkUserBalance($user, $serviceData['price']);
    
    return $this->proxyServiceRepository->create([
        'user_id' => $user->id,
        'type' => $serviceData['type'],
        'configuration' => $serviceData['configuration'],
        'expires_at' => now()->addDays($serviceData['duration']),
    ]);
}
```

### Laravel Best Practices

#### Model Guidelines

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProxyService extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'type',
        'configuration',
        'status',
        'expires_at',
    ];

    protected $casts = [
        'configuration' => 'json',
        'expires_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    // Relationships
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function usageLogs(): HasMany
    {
        return $this->hasMany(UsageLog::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 'active')
                    ->where('expires_at', '>', now());
    }

    // Accessors/Mutators
    public function getIsExpiredAttribute(): bool
    {
        return $this->expires_at < now();
    }

    // Business Logic Methods
    public function suspend(): void
    {
        $this->update(['status' => 'suspended']);
    }

    public function reactivate(): void
    {
        if (!$this->is_expired) {
            $this->update(['status' => 'active']);
        }
    }
}
```

#### Controller Guidelines

```php
<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreateServiceRequest;
use App\Http\Resources\ServiceResource;
use App\Services\ProxyServiceManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ServiceController extends Controller
{
    public function __construct(
        private readonly ProxyServiceManager $serviceManager
    ) {}

    /**
     * Get customer's services
     */
    public function index(): AnonymousResourceCollection
    {
    $services = auth('customer')->user()->proxyServices()->active()->get();
        
        return ServiceResource::collection($services);
    }

    /**
     * Create new service
     */
    public function store(CreateServiceRequest $request): JsonResponse
    {
        $service = $this->serviceManager->createService(
            auth('customer')->user(),
            $request->validated()
        );

        return response()->json([
            'message' => 'Service created successfully',
            'data' => new ServiceResource($service),
        ], 201);
    }

    /**
     * Get specific service
     */
    public function show(int $id): JsonResponse
    {
        $service = auth()->user()
            ->proxyServices()
            ->findOrFail($id);

        return response()->json([
            'data' => new ServiceResource($service),
        ]);
    }
}
```

#### Request Validation

```php
<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateServiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'type' => ['required', 'string', 'in:vless,vmess,trojan,shadowsocks'],
            'duration' => ['required', 'integer', 'min:1', 'max:365'],
            'data_limit' => ['nullable', 'integer', 'min:1'],
            'server_location' => ['required', 'string', 'exists:servers,location'],
        ];
    }

    public function messages(): array
    {
        return [
            'type.in' => 'The selected service type is invalid.',
            'duration.max' => 'Service duration cannot exceed 365 days.',
            'server_location.exists' => 'The selected server location is not available.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'type' => strtolower($this->type),
        ]);
    }
}
```

### JavaScript/TypeScript Standards

#### Vue.js Components

```vue
<template>
  <div class="service-card">
    <div class="service-header">
      <h3 class="service-title">{{ service.name }}</h3>
      <span 
        class="service-status" 
        :class="statusClass"
      >
        {{ service.status }}
      </span>
    </div>
    
    <div class="service-details">
      <p class="service-type">Type: {{ service.type.toUpperCase() }}</p>
      <p class="service-expires">Expires: {{ formattedExpiryDate }}</p>
    </div>
    
    <div class="service-actions">
      <button 
        @click="handleRenew"
        class="btn btn-primary"
        :disabled="isRenewing"
      >
        {{ isRenewing ? 'Renewing...' : 'Renew' }}
      </button>
      
      <button 
        @click="handleSuspend"
        class="btn btn-secondary"
        v-if="service.status === 'active'"
      >
        Suspend
      </button>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed, ref } from 'vue'
import { format } from 'date-fns'
import type { Service } from '@/types/service'

interface Props {
  service: Service
}

const props = defineProps<Props>()
const emit = defineEmits<{
  renew: [serviceId: number]
  suspend: [serviceId: number]
}>()

const isRenewing = ref(false)

const statusClass = computed(() => ({
  'status-active': props.service.status === 'active',
  'status-suspended': props.service.status === 'suspended',
  'status-expired': props.service.status === 'expired',
}))

const formattedExpiryDate = computed(() => 
  format(new Date(props.service.expires_at), 'PPP')
)

const handleRenew = async (): Promise<void> => {
  isRenewing.value = true
  try {
    emit('renew', props.service.id)
  } finally {
    isRenewing.value = false
  }
}

const handleSuspend = (): void => {
  emit('suspend', props.service.id)
}
</script>

<style scoped>
.service-card {
  @apply bg-white rounded-lg shadow-md p-6 border border-gray-200;
}

.service-header {
  @apply flex justify-between items-center mb-4;
}

.service-title {
  @apply text-lg font-semibold text-gray-900;
}

.service-status {
  @apply px-2 py-1 rounded-full text-xs font-medium;
}

.status-active {
  @apply bg-green-100 text-green-800;
}

.status-suspended {
  @apply bg-yellow-100 text-yellow-800;
}

.status-expired {
  @apply bg-red-100 text-red-800;
}
</style>
```

#### TypeScript Interfaces

```typescript
// types/service.ts
export interface Service {
  id: number
  user_id: number
  type: ServiceType
  name: string
  status: ServiceStatus
  configuration: ServiceConfiguration
  created_at: string
  updated_at: string
  expires_at: string
}

export type ServiceType = 'vless' | 'vmess' | 'trojan' | 'shadowsocks'
export type ServiceStatus = 'active' | 'suspended' | 'expired' | 'pending'

export interface ServiceConfiguration {
  server: string
  port: number
  uuid: string
  security: string
  network: string
  [key: string]: unknown
}

export interface CreateServiceRequest {
  type: ServiceType
  duration: number
  data_limit?: number
  server_location: string
}

export interface ServiceResponse {
  data: Service
  message?: string
}

export interface ServicesResponse {
  data: Service[]
  meta: {
    current_page: number
    total: number
    per_page: number
  }
}
```

### CSS/Styling Standards

#### Tailwind CSS Usage

```css
/* Use Tailwind utility classes */
.service-card {
  @apply bg-white rounded-lg shadow-md p-6 border border-gray-200
         hover:shadow-lg transition-shadow duration-200;
}

/* Component-specific styles */
.btn {
  @apply px-4 py-2 rounded-md font-medium text-sm
         focus:outline-none focus:ring-2 focus:ring-offset-2
         disabled:opacity-50 disabled:cursor-not-allowed
         transition-colors duration-200;
}

.btn-primary {
  @apply bg-blue-600 text-white hover:bg-blue-700
         focus:ring-blue-500;
}

.btn-secondary {
  @apply bg-gray-200 text-gray-900 hover:bg-gray-300
         focus:ring-gray-500;
}

/* Custom components when needed */
.loading-spinner {
  @apply animate-spin rounded-full h-4 w-4 border-b-2 border-blue-600;
}
```

## Git Workflow

### Branch Strategy

```bash
# Main branches
main        # Production-ready code
develop     # Integration branch for features

# Supporting branches
feature/*   # New features
bugfix/*    # Bug fixes
hotfix/*    # Critical production fixes
release/*   # Release preparation
```

### Branch Naming

```bash
# Feature branches
feature/user-authentication
feature/payment-integration
feature/service-management

# Bug fix branches
bugfix/login-validation-error
bugfix/payment-calculation-issue

# Hotfix branches
hotfix/security-vulnerability
hotfix/critical-service-outage

# Release branches
release/v1.2.0
release/v1.2.1
```

### Commit Message Format

```bash
# Format: <type>(<scope>): <description>
# 
# <body>
# 
# <footer>

# Types
feat:     # New feature
fix:      # Bug fix
docs:     # Documentation
style:    # Formatting, missing semicolons, etc.
refactor: # Code change that neither fixes a bug nor adds a feature
test:     # Adding tests
chore:    # Updating build tasks, package manager configs, etc.

# Examples
feat(auth): add two-factor authentication support

fix(payment): resolve PayPal webhook validation issue

docs(api): update authentication endpoint documentation

refactor(service): improve service creation performance

test(user): add unit tests for user model

chore(deps): update Laravel to version 10.x
```

### Pull Request Guidelines

```markdown
## Pull Request Template

### Description
Brief description of the changes made.

### Type of Change
- [ ] Bug fix (non-breaking change which fixes an issue)
- [ ] New feature (non-breaking change which adds functionality)
- [ ] Breaking change (fix or feature that would cause existing functionality to not work as expected)
- [ ] Documentation update

### How Has This Been Tested?
Describe the tests that you ran to verify your changes.

### Checklist:
- [ ] My code follows the style guidelines of this project
- [ ] I have performed a self-review of my own code
- [ ] I have commented my code, particularly in hard-to-understand areas
- [ ] I have made corresponding changes to the documentation
- [ ] My changes generate no new warnings
- [ ] I have added tests that prove my fix is effective or that my feature works
- [ ] New and existing unit tests pass locally with my changes

### Screenshots (if applicable):
Add screenshots to help explain your changes.

### Related Issues:
Fixes #(issue number)
```

## Testing Guidelines

### Test Structure

```bash
tests/
├── Feature/           # Integration tests
│   ├── Auth/
│   ├── Api/
│   └── Services/
├── Unit/             # Unit tests
│   ├── Models/
│   ├── Services/
│   └── Helpers/
└── Browser/          # Browser tests (Dusk)
    ├── Auth/
    └── Dashboard/
```

### Unit Testing

```php
<?php

namespace Tests\Unit\Services;

use App\Models\User;
use App\Services\ProxyServiceManager;
use App\Exceptions\InsufficientFundsException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProxyServiceManagerTest extends TestCase
{
    use RefreshDatabase;

    private ProxyServiceManager $serviceManager;

    protected function setUp(): void
    {
        parent::setUp();
        $this->serviceManager = app(ProxyServiceManager::class);
    }

    /** @test */
    public function it_creates_service_for_user_with_sufficient_balance(): void
    {
        // Arrange
        $user = User::factory()->create(['balance' => 100.00]);
        $serviceData = [
            'type' => 'vless',
            'duration' => 30,
            'price' => 50.00,
        ];

        // Act
        $service = $this->serviceManager->createService($user, $serviceData);

        // Assert
        $this->assertDatabaseHas('proxy_services', [
            'user_id' => $user->id,
            'type' => 'vless',
        ]);
        
        $this->assertEquals(50.00, $user->fresh()->balance);
        $this->assertEquals('active', $service->status);
    }

    /** @test */
    public function it_throws_exception_when_user_has_insufficient_balance(): void
    {
        // Arrange
        $user = User::factory()->create(['balance' => 25.00]);
        $serviceData = [
            'type' => 'vless',
            'duration' => 30,
            'price' => 50.00,
        ];

        // Assert
        $this->expectException(InsufficientFundsException::class);

        // Act
        $this->serviceManager->createService($user, $serviceData);
    }
}
```

### Feature Testing

```php
<?php

namespace Tests\Feature\Api;

use App\Models\User;
use App\Models\ProxyService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ServiceControllerTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function authenticated_user_can_view_their_services(): void
    {
        // Arrange
        $user = User::factory()->create();
        $services = ProxyService::factory(3)->create(['user_id' => $user->id]);
        
        Sanctum::actingAs($user);

        // Act
        $response = $this->getJson('/api/services');

        // Assert
        $response->assertOk()
                ->assertJsonCount(3, 'data')
                ->assertJsonStructure([
                    'data' => [
                        '*' => [
                            'id',
                            'type',
                            'status',
                            'expires_at',
                        ]
                    ]
                ]);
    }

    /** @test */
    public function user_can_create_new_service(): void
    {
        // Arrange
        $user = User::factory()->create(['balance' => 100.00]);
        Sanctum::actingAs($user);

        $serviceData = [
            'type' => 'vless',
            'duration' => 30,
            'server_location' => 'us-east-1',
        ];

        // Act
        $response = $this->postJson('/api/services', $serviceData);

        // Assert
        $response->assertCreated()
                ->assertJsonStructure([
                    'message',
                    'data' => [
                        'id',
                        'type',
                        'status',
                        'expires_at',
                    ]
                ]);

        $this->assertDatabaseHas('proxy_services', [
            'user_id' => $user->id,
            'type' => 'vless',
            'status' => 'active',
        ]);
    }
}
```

### Browser Testing (Laravel Dusk)

```php
<?php

namespace Tests\Browser;

use App\Models\User;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class ServiceManagementTest extends DuskTestCase
{
    /** @test */
    public function user_can_create_service_through_dashboard(): void
    {
        $user = User::factory()->create(['balance' => 100.00]);

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user)
                   ->visit('/admin')
                   ->clickLink('Create Service')
                   ->select('type', 'vless')
                   ->select('duration', '30')
                   ->select('server_location', 'us-east-1')
                   ->click('@create-service-button')
                   ->waitForText('Service created successfully')
                   ->assertSee('Service created successfully');
        });
    }
}
```

### Test Data Factories

```php
<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProxyServiceFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'type' => $this->faker->randomElement(['vless', 'vmess', 'trojan']),
            'status' => 'active',
            'configuration' => [
                'server' => $this->faker->ipv4,
                'port' => $this->faker->numberBetween(1000, 9999),
                'uuid' => $this->faker->uuid,
            ],
            'expires_at' => now()->addDays(30),
        ];
    }

    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'expired',
            'expires_at' => now()->subDays(1),
        ]);
    }

    public function suspended(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'suspended',
        ]);
    }
}
```

## Code Review Process

### Review Checklist

#### Functionality
- [ ] Code meets requirements
- [ ] Edge cases handled appropriately
- [ ] Error handling implemented
- [ ] Performance considerations addressed

#### Code Quality
- [ ] Code follows project standards
- [ ] Functions/methods are single-purpose
- [ ] Naming is clear and descriptive
- [ ] Comments explain complex logic

#### Security
- [ ] Input validation implemented
- [ ] SQL injection prevention
- [ ] XSS protection in place
- [ ] Authentication/authorization checked

#### Testing
- [ ] Unit tests included
- [ ] Integration tests where appropriate
- [ ] Test coverage is adequate
- [ ] Tests are meaningful and comprehensive

### Review Comments Guidelines

```markdown
# Good Review Comments

## Suggestion
**Consider using a more descriptive variable name:**
```php
// Instead of $data
$serviceConfiguration = $request->validated();
```

## Question
**Is there a reason we're not using the existing UserService here?**
It seems like this logic duplicates what's already in UserService::updateProfile().

## Approval with Minor Issue
**LGTM! Just one small suggestion:**
Consider extracting the validation logic into a FormRequest class for better organization.

## Blocking Issue
**This needs to be addressed before merging:**
The current implementation is vulnerable to SQL injection. Please use parameter binding or Eloquent ORM.
```

### Code Review Tools

```bash
# Pre-commit hooks
composer require --dev brianium/paratest
composer require --dev squizlabs/php_codesniffer
composer require --dev phpstan/phpstan

# .pre-commit-config.yaml
repos:
  - repo: local
    hooks:
      - id: php-syntax
        name: PHP Syntax Check
        entry: php -l
        language: system
        files: \.php$
      
      - id: phpcs
        name: PHP Code Sniffer
        entry: ./vendor/bin/phpcs
        language: system
        files: \.php$
        args: [--standard=PSR12]
      
      - id: phpstan
        name: PHPStan
        entry: ./vendor/bin/phpstan
        language: system
        files: \.php$
        args: [analyse, --no-progress]
```

## Database Guidelines

### Migration Best Practices

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('proxy_services', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('type', 20); // vless, vmess, trojan, etc.
            $table->string('status', 20)->default('pending');
            $table->json('configuration');
            $table->decimal('price', 10, 2);
            $table->unsignedBigInteger('data_limit')->nullable(); // bytes
            $table->unsignedBigInteger('data_used')->default(0); // bytes
            $table->timestamp('expires_at');
            $table->timestamps();
            $table->softDeletes();

            // Indexes for performance
            $table->index(['user_id', 'status']);
            $table->index(['expires_at']);
            $table->index(['type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('proxy_services');
    }
};
```

### Query Optimization

```php
// Bad: N+1 Query Problem
$services = ProxyService::all();
foreach ($services as $service) {
    echo $service->user->name; // Additional query for each service
}

// Good: Eager Loading
$services = ProxyService::with('user')->get();
foreach ($services as $service) {
    echo $service->user->name; // No additional queries
}

// Good: Specific Columns
$services = ProxyService::select(['id', 'type', 'status', 'user_id'])
    ->with('user:id,name,email')
    ->where('status', 'active')
    ->get();

// Good: Chunking for Large Datasets
ProxyService::where('status', 'expired')
    ->chunk(100, function ($services) {
        foreach ($services as $service) {
            $service->delete();
        }
    });
```

### Database Seeding

```php
<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\ProxyService;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Create admin user
        User::factory()->create([
            'name' => 'Admin User',
            'email' => 'admin@1000proxy.io',
            'email_verified_at' => now(),
            'is_admin' => true,
        ]);

        // Create test users with services
        User::factory(50)
            ->has(ProxyService::factory(2))
            ->create();

        // Create specific test scenarios
        $this->call([
            ServiceTypeSeeder::class,
            ServerLocationSeeder::class,
            PaymentMethodSeeder::class,
        ]);
    }
}
```

## API Development

### API Resource Structure

```php
<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ServiceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'status' => $this->status,
            'configuration' => $this->when(
                $request->user()->can('view', $this->resource),
                $this->configuration
            ),
            'data_used' => $this->formatBytes($this->data_used),
            'data_limit' => $this->when(
                $this->data_limit,
                $this->formatBytes($this->data_limit)
            ),
            'expires_at' => $this->expires_at,
            'is_expired' => $this->is_expired,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            
            // Relationships
            'user' => new UserResource($this->whenLoaded('user')),
            'usage_logs' => UsageLogResource::collection(
                $this->whenLoaded('usageLogs')
            ),
        ];
    }

    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $power = $bytes > 0 ? floor(log($bytes, 1024)) : 0;
        
        return number_format(
            $bytes / pow(1024, $power),
            2,
            '.',
            ','
        ) . ' ' . $units[$power];
    }
}
```

### API Error Handling

```php
<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;

class Handler extends ExceptionHandler
{
    public function render($request, Throwable $e): JsonResponse
    {
        if ($request->is('api/*')) {
            return $this->handleApiException($request, $e);
        }

        return parent::render($request, $e);
    }

    private function handleApiException(Request $request, Throwable $e): JsonResponse
    {
        if ($e instanceof ValidationException) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        }

        if ($e instanceof NotFoundHttpException) {
            return response()->json([
                'message' => 'Resource not found',
            ], 404);
        }

        // Log the error
        logger()->error('API Exception', [
            'exception' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'request' => $request->all(),
        ]);

        return response()->json([
            'message' => 'Internal server error',
            'error' => config('app.debug') ? $e->getMessage() : null,
        ], 500);
    }
}
```

## Security Guidelines

### Input Validation

```php
// Always validate and sanitize input
public function store(CreateServiceRequest $request): JsonResponse
{
    $validated = $request->validated();
    
    // Additional validation if needed
    if ($validated['type'] === 'premium' && !auth()->user()->isPremium()) {
        throw new UnauthorizedException('Premium service requires premium account');
    }
    
    $service = $this->serviceManager->createService(
        auth()->user(),
        $validated
    );
    
    return response()->json(['data' => new ServiceResource($service)], 201);
}
```

### SQL Injection Prevention

```php
// Good: Using Eloquent ORM
$services = ProxyService::where('user_id', auth()->id())
    ->where('type', $request->type)
    ->get();

// Good: Using Query Builder with bindings
$services = DB::table('proxy_services')
    ->where('user_id', '=', auth()->id())
    ->where('type', '=', $request->type)
    ->get();

// Bad: Raw SQL without bindings
$services = DB::select("SELECT * FROM proxy_services WHERE user_id = {$userId}");
```

### XSS Prevention

```blade
{{-- Good: Escaped output --}}
<p>{{ $user->name }}</p>
<p>{{ $service->description }}</p>

{{-- Use {!! !!} only for trusted content --}}
<div class="content">
    {!! $trustedHtmlContent !!}
</div>

{{-- For JSON data --}}
<script>
    const config = @json($serviceConfig);
</script>
```

### Authentication & Authorization

```php
// Policy-based authorization
class ServicePolicy
{
    public function view(User $user, ProxyService $service): bool
    {
        return $user->id === $service->user_id || $user->isAdmin();
    }

    public function update(User $user, ProxyService $service): bool
    {
        return $user->id === $service->user_id && $service->status !== 'expired';
    }
}

// Controller usage
public function show(ProxyService $service): JsonResponse
{
    $this->authorize('view', $service);
    
    return response()->json(['data' => new ServiceResource($service)]);
}
```

## Performance Optimization

### Caching Strategies

```php
// Model caching
class User extends Model
{
    public function getActiveServicesAttribute()
    {
        return Cache::remember(
            "customer.{$this->id}.active_services",
            now()->addMinutes(10),
            fn () => $this->proxyServices()->active()->get()
        );
    }
}

// Query result caching
public function getPopularServices(): Collection
{
    return Cache::remember('popular_services', now()->addHour(), function () {
        return ProxyService::select('type', DB::raw('count(*) as count'))
            ->where('created_at', '>=', now()->subDays(30))
            ->groupBy('type')
            ->orderByDesc('count')
            ->limit(10)
            ->get();
    });
}

// Cache tags for selective invalidation
Cache::tags(['customer.' . $userId, 'services'])
    ->put('customer.services.' . $userId, $services, now()->addMinutes(30));

// Invalidate specific tags
Cache::tags(['customer.' . $userId])->flush();
```

### Database Optimization

```php
// Efficient pagination
public function index(Request $request): JsonResponse
{
    $services = ProxyService::with(['user:id,name'])
        ->select(['id', 'user_id', 'type', 'status', 'expires_at'])
        ->when($request->type, fn ($q) => $q->where('type', $request->type))
        ->when($request->status, fn ($q) => $q->where('status', $request->status))
        ->orderByDesc('created_at')
        ->paginate(20);

    return ServiceResource::collection($services);
}

// Efficient counting
public function getServiceStats(): array
{
    $stats = ProxyService::selectRaw('
        COUNT(*) as total,
        COUNT(CASE WHEN status = "active" THEN 1 END) as active,
        COUNT(CASE WHEN status = "expired" THEN 1 END) as expired,
        COUNT(CASE WHEN created_at >= ? THEN 1 END) as new_this_month
    ', [now()->startOfMonth()])->first();

    return $stats->toArray();
}
```

### Queue Optimization

```php
// Efficient job processing
class ProcessServiceUsage implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 300;
    public $tries = 3;
    public $backoff = [60, 120, 300];

    public function __construct(
        private readonly int $serviceId,
        private readonly array $usageData
    ) {}

    public function handle(): void
    {
        $service = ProxyService::find($this->serviceId);
        
        if (!$service) {
            $this->fail(new ModelNotFoundException('Service not found'));
            return;
        }

        DB::transaction(function () use ($service) {
            $service->increment('data_used', $this->usageData['bytes']);
            
            if ($service->data_used >= $service->data_limit) {
                $service->update(['status' => 'suspended']);
                
                // Dispatch notification
                SendServiceSuspendedNotification::dispatch($service);
            }
        });
    }
}
```

## Documentation Standards

### Code Documentation

```php
/**
 * Manage proxy service operations including creation, suspension, and renewal
 * 
 * This service handles all business logic related to proxy services,
 * including payment processing, server provisioning, and usage tracking.
 * 
 * @author Your Name <your.email@company.com>
 * @version 1.0.0
 * @since 2024-01-01
 */
class ProxyServiceManager
{
    /**
     * Create a new proxy service for the specified user
     * 
     * This method validates the service data, checks user balance,
     * processes payment, and provisions the service on available servers.
     * 
     * @param User $user The user requesting the service
     * @param array $serviceData Configuration data for the service
     * @param array $serviceData.type Service type (vless, vmess, trojan, shadowsocks)
     * @param array $serviceData.duration Service duration in days
     * @param array $serviceData.data_limit Optional data limit in bytes
     * 
     * @return ProxyService The created and provisioned service
     * 
     * @throws InsufficientFundsException When user doesn't have enough balance
     * @throws InvalidServiceDataException When service data is invalid
     * @throws ServerUnavailableException When no servers are available
     * 
     * @example
     * $service = $manager->createService($user, [
     *     'type' => 'vless',
     *     'duration' => 30,
     *     'data_limit' => 107374182400, // 100GB in bytes
     * ]);
     */
    public function createService(User $user, array $serviceData): ProxyService
    {
        // Implementation...
    }
}
```

### API Documentation

```yaml
# OpenAPI specification
openapi: 3.0.3
info:
  title: 1000proxy API
  description: API for managing proxy services
  version: 1.0.0
  contact:
    name: API Support
    email: api-support@1000proxy.io

paths:
  /api/services:
    get:
    summary: Get customer's services
    description: Retrieve a paginated list of the authenticated customer's proxy services
      tags:
        - Services
      security:
        - bearerAuth: []
      parameters:
        - name: type
          in: query
          description: Filter by service type
          schema:
            type: string
            enum: [vless, vmess, trojan, shadowsocks]
        - name: status
          in: query
          description: Filter by service status
          schema:
            type: string
            enum: [active, suspended, expired, pending]
        - name: page
          in: query
          description: Page number for pagination
          schema:
            type: integer
            minimum: 1
            default: 1
      responses:
        '200':
          description: Services retrieved successfully
          content:
            application/json:
              schema:
                type: object
                properties:
                  data:
                    type: array
                    items:
                      $ref: '#/components/schemas/Service'
                  meta:
                    $ref: '#/components/schemas/PaginationMeta'
        '401':
          $ref: '#/components/responses/Unauthorized'

components:
  schemas:
    Service:
      type: object
      properties:
        id:
          type: integer
          description: Unique service identifier
        type:
          type: string
          enum: [vless, vmess, trojan, shadowsocks]
          description: Service protocol type
        status:
          type: string
          enum: [active, suspended, expired, pending]
          description: Current service status
        expires_at:
          type: string
          format: date-time
          description: Service expiration date
        configuration:
          type: object
          description: Service configuration details
```

This comprehensive development guide provides all the necessary standards and practices for maintaining high-quality code in the 1000proxy project. Follow these guidelines to ensure consistency, security, and maintainability across the entire codebase.
