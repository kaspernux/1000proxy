<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Collection;
use Illuminate\Http\Request;
use ReflectionClass;
use ReflectionMethod;

class APIDocumentationService
{
    protected $config;
    protected $apiRoutes = [];
    protected $schemas = [];
    protected $securitySchemes = [];

    public function __construct()
    {
        $this->config = Config::get('api-docs', []);
        $this->initializeSecuritySchemes();
    }

    /**
     * Generate complete OpenAPI/Swagger documentation
     */
    public function generateOpenAPISpecification(): array
    {
        $spec = [
            'openapi' => '3.0.3',
            'info' => $this->getAPIInfo(),
            'servers' => $this->getServers(),
            'paths' => $this->generatePaths(),
            'components' => [
                'schemas' => $this->generateSchemas(),
                'securitySchemes' => $this->securitySchemes,
                'responses' => $this->getCommonResponses(),
                'parameters' => $this->getCommonParameters()
            ],
            'tags' => $this->getTags(),
            'security' => $this->getGlobalSecurity()
        ];

        // Cache the generated specification
        Cache::put('api_docs_spec', $spec, now()->addHours(24));

        return $spec;
    }

    /**
     * Get API information
     */
    protected function getAPIInfo(): array
    {
        return [
            'title' => '1000proxy API',
            'description' => 'Comprehensive API for 1000proxy service - proxy server management, user authentication, order processing, and system administration',
            'version' => '2.0.0',
            'contact' => [
                'name' => '1000proxy API Support',
                'email' => 'api-support@1000proxy.io',
                'url' => 'https://docs.1000proxy.io'
            ],
            'license' => [
                'name' => 'Proprietary',
                'url' => 'https://1000proxy.io/license'
            ],
            'termsOfService' => 'https://1000proxy.io/terms'
        ];
    }

    /**
     * Get server configurations
     */
    protected function getServers(): array
    {
        return [
            [
                'url' => config('app.url') . '/api/v1',
                'description' => 'Production API v1'
            ],
            [
                'url' => config('app.url') . '/api/v2',
                'description' => 'Production API v2 (Latest)'
            ],
            [
                'url' => 'https://staging.1000proxy.io/api/v2',
                'description' => 'Staging Environment'
            ],
            [
                'url' => 'http://localhost/api/v2',
                'description' => 'Local Development'
            ]
        ];
    }

    /**
     * Generate API paths from routes
     */
    protected function generatePaths(): array
    {
        $paths = [];
        $routes = $this->getAPIRoutes();

        foreach ($routes as $route) {
            $path = $this->formatPath($route['uri']);
            $method = strtolower($route['methods'][0]);

            if (!isset($paths[$path])) {
                $paths[$path] = [];
            }

            $paths[$path][$method] = $this->generatePathItem($route);
        }

        return $paths;
    }

    /**
     * Get all API routes
     */
    protected function getAPIRoutes(): Collection
    {
        $routes = collect(Route::getRoutes())->filter(function ($route) {
            return str_starts_with($route->uri(), 'api/');
        })->map(function ($route) {
            return [
                'uri' => $route->uri(),
                'methods' => $route->methods(),
                'name' => $route->getName(),
                'action' => $route->getActionName(),
                'middleware' => $route->middleware(),
                'parameters' => $route->parameterNames(),
                'controller' => $this->getControllerInfo($route->getActionName())
            ];
        });

        return $routes;
    }

    /**
     * Generate path item for OpenAPI
     */
    protected function generatePathItem(array $route): array
    {
        $pathItem = [
            'tags' => $this->determinePathTags($route),
            'summary' => $this->generateSummary($route),
            'description' => $this->generateDescription($route),
            'operationId' => $this->generateOperationId($route),
            'parameters' => $this->generateParameters($route),
            'responses' => $this->generateResponses($route),
            'security' => $this->determinePathSecurity($route)
        ];

        // Add request body for POST/PUT/PATCH methods
        $method = strtolower($route['methods'][0]);
        if (in_array($method, ['post', 'put', 'patch'])) {
            $pathItem['requestBody'] = $this->generateRequestBody($route);
        }

        return $pathItem;
    }

    /**
     * Generate request body schema
     */
    protected function generateRequestBody(array $route): array
    {
        $resourceType = $this->extractResourceType($route['uri']);

        return [
            'required' => true,
            'content' => [
                'application/json' => [
                    'schema' => [
                        '$ref' => "#/components/schemas/{$resourceType}Request"
                    ]
                ],
                'multipart/form-data' => [
                    'schema' => [
                        '$ref' => "#/components/schemas/{$resourceType}FormData"
                    ]
                ]
            ]
        ];
    }

    /**
     * Generate response schemas
     */
    protected function generateResponses(array $route): array
    {
        $resourceType = $this->extractResourceType($route['uri']);
        $method = strtolower($route['methods'][0]);

        $responses = [
            '400' => ['$ref' => '#/components/responses/BadRequest'],
            '401' => ['$ref' => '#/components/responses/Unauthorized'],
            '403' => ['$ref' => '#/components/responses/Forbidden'],
            '404' => ['$ref' => '#/components/responses/NotFound'],
            '422' => ['$ref' => '#/components/responses/ValidationError'],
            '500' => ['$ref' => '#/components/responses/InternalError']
        ];

        switch ($method) {
            case 'get':
                if (str_contains($route['uri'], '{id}')) {
                    $responses['200'] = [
                        'description' => "Single {$resourceType} resource",
                        'content' => [
                            'application/json' => [
                                'schema' => [
                                    '$ref' => "#/components/schemas/{$resourceType}Resource"
                                ]
                            ]
                        ]
                    ];
                } else {
                    $responses['200'] = [
                        'description' => "{$resourceType} collection",
                        'content' => [
                            'application/json' => [
                                'schema' => [
                                    '$ref' => "#/components/schemas/{$resourceType}Collection"
                                ]
                            ]
                        ]
                    ];
                }
                break;

            case 'post':
                $responses['201'] = [
                    'description' => "{$resourceType} created successfully",
                    'content' => [
                        'application/json' => [
                            'schema' => [
                                '$ref' => "#/components/schemas/{$resourceType}Resource"
                            ]
                        ]
                    ]
                ];
                break;

            case 'put':
            case 'patch':
                $responses['200'] = [
                    'description' => "{$resourceType} updated successfully",
                    'content' => [
                        'application/json' => [
                            'schema' => [
                                '$ref' => "#/components/schemas/{$resourceType}Resource"
                            ]
                        ]
                    ]
                ];
                break;

            case 'delete':
                $responses['204'] = [
                    'description' => "{$resourceType} deleted successfully"
                ];
                break;
        }

        return $responses;
    }

    /**
     * Generate comprehensive schemas
     */
    protected function generateSchemas(): array
    {
        $schemas = [
            // Authentication schemas
            'LoginRequest' => [
                'type' => 'object',
                'required' => ['email', 'password'],
                'properties' => [
                    'email' => ['type' => 'string', 'format' => 'email', 'example' => 'user@example.com'],
                    'password' => ['type' => 'string', 'minLength' => 8, 'example' => 'SecurePass123!'],
                    'remember' => ['type' => 'boolean', 'default' => false]
                ]
            ],
            'RegisterRequest' => [
                'type' => 'object',
                'required' => ['name', 'email', 'password', 'password_confirmation'],
                'properties' => [
                    'name' => ['type' => 'string', 'minLength' => 2, 'example' => 'John Doe'],
                    'email' => ['type' => 'string', 'format' => 'email', 'example' => 'john@example.com'],
                    'password' => ['type' => 'string', 'minLength' => 8, 'example' => 'SecurePass123!'],
                    'password_confirmation' => ['type' => 'string', 'example' => 'SecurePass123!'],
                    'telegram_username' => ['type' => 'string', 'nullable' => true, 'example' => '@johndoe']
                ]
            ],
            'AuthResponse' => [
                'type' => 'object',
                'properties' => [
                    'access_token' => ['type' => 'string', 'example' => 'eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9...'],
                    'token_type' => ['type' => 'string', 'example' => 'Bearer'],
                    'expires_in' => ['type' => 'integer', 'example' => 3600],
                    'user' => ['$ref' => '#/components/schemas/UserResource']
                ]
            ],

            // User schemas
            'UserResource' => [
                'type' => 'object',
                'properties' => [
                    'id' => ['type' => 'integer', 'example' => 1],
                    'name' => ['type' => 'string', 'example' => 'John Doe'],
                    'email' => ['type' => 'string', 'example' => 'john@example.com'],
                    'role' => ['type' => 'string', 'enum' => ['admin', 'support_manager', 'sales_support'], 'example' => 'admin'],
                    'is_active' => ['type' => 'boolean', 'example' => true],
                    'last_login_at' => ['type' => 'string', 'format' => 'date-time', 'nullable' => true],
                    'created_at' => ['type' => 'string', 'format' => 'date-time'],
                    'updated_at' => ['type' => 'string', 'format' => 'date-time']
                ]
            ],

            // Customer schemas
            'CustomerResource' => [
                'type' => 'object',
                'properties' => [
                    'id' => ['type' => 'integer', 'example' => 1],
                    'name' => ['type' => 'string', 'example' => 'Jane Smith'],
                    'email' => ['type' => 'string', 'example' => 'jane@example.com'],
                    'telegram_id' => ['type' => 'string', 'nullable' => true, 'example' => '123456789'],
                    'telegram_username' => ['type' => 'string', 'nullable' => true, 'example' => '@janesmith'],
                    'wallet_balance' => ['type' => 'number', 'format' => 'decimal', 'example' => 50.00],
                    'total_orders' => ['type' => 'integer', 'example' => 15],
                    'total_spent' => ['type' => 'number', 'format' => 'decimal', 'example' => 750.00],
                    'status' => ['type' => 'string', 'enum' => ['active', 'suspended', 'inactive'], 'example' => 'active'],
                    'created_at' => ['type' => 'string', 'format' => 'date-time'],
                    'updated_at' => ['type' => 'string', 'format' => 'date-time']
                ]
            ],

            // Server schemas
            'ServerResource' => [
                'type' => 'object',
                'properties' => [
                    'id' => ['type' => 'integer', 'example' => 1],
                    'name' => ['type' => 'string', 'example' => 'US-East-01'],
                    'host' => ['type' => 'string', 'example' => 'us-east-01.1000proxy.io'],
                    'port' => ['type' => 'integer', 'example' => 54321],
                    'username' => ['type' => 'string', 'example' => 'admin'],
                    'location' => ['type' => 'string', 'example' => 'New York, USA'],
                    'country_code' => ['type' => 'string', 'example' => 'US'],
                    'is_active' => ['type' => 'boolean', 'example' => true],
                    'health_status' => ['type' => 'string', 'enum' => ['healthy', 'warning', 'critical', 'offline'], 'example' => 'healthy'],
                    'cpu_usage' => ['type' => 'number', 'format' => 'float', 'example' => 45.2],
                    'memory_usage' => ['type' => 'number', 'format' => 'float', 'example' => 67.8],
                    'bandwidth_usage' => ['type' => 'number', 'format' => 'float', 'example' => 1024.5],
                    'client_count' => ['type' => 'integer', 'example' => 25],
                    'max_clients' => ['type' => 'integer', 'example' => 100],
                    'brand' => ['$ref' => '#/components/schemas/ServerBrandResource'],
                    'category' => ['$ref' => '#/components/schemas/ServerCategoryResource'],
                    'created_at' => ['type' => 'string', 'format' => 'date-time'],
                    'updated_at' => ['type' => 'string', 'format' => 'date-time']
                ]
            ],

            // Order schemas
            'OrderResource' => [
                'type' => 'object',
                'properties' => [
                    'id' => ['type' => 'integer', 'example' => 1],
                    'order_number' => ['type' => 'string', 'example' => 'ORD-20250714-001'],
                    'customer_id' => ['type' => 'integer', 'example' => 5],
                    'total_amount' => ['type' => 'number', 'format' => 'decimal', 'example' => 29.99],
                    'status' => ['type' => 'string', 'enum' => ['pending', 'processing', 'completed', 'cancelled', 'failed'], 'example' => 'completed'],
                    'payment_status' => ['type' => 'string', 'enum' => ['pending', 'paid', 'failed', 'refunded'], 'example' => 'paid'],
                    'payment_method' => ['type' => 'string', 'example' => 'stripe'],
                    'expires_at' => ['type' => 'string', 'format' => 'date-time'],
                    'customer' => ['$ref' => '#/components/schemas/CustomerResource'],
                    'items' => [
                        'type' => 'array',
                        'items' => ['$ref' => '#/components/schemas/OrderItemResource']
                    ],
                    'created_at' => ['type' => 'string', 'format' => 'date-time'],
                    'updated_at' => ['type' => 'string', 'format' => 'date-time']
                ]
            ],

            // Payment schemas
            'PaymentResource' => [
                'type' => 'object',
                'properties' => [
                    'id' => ['type' => 'integer', 'example' => 1],
                    'order_id' => ['type' => 'integer', 'example' => 5],
                    'amount' => ['type' => 'number', 'format' => 'decimal', 'example' => 29.99],
                    'currency' => ['type' => 'string', 'example' => 'USD'],
                    'gateway' => ['type' => 'string', 'example' => 'stripe'],
                    'gateway_transaction_id' => ['type' => 'string', 'example' => 'pi_1234567890'],
                    'status' => ['type' => 'string', 'enum' => ['pending', 'processing', 'completed', 'failed', 'refunded'], 'example' => 'completed'],
                    'processed_at' => ['type' => 'string', 'format' => 'date-time'],
                    'created_at' => ['type' => 'string', 'format' => 'date-time']
                ]
            ],

            // Collection schemas
            'UserCollection' => [
                'type' => 'object',
                'properties' => [
                    'data' => [
                        'type' => 'array',
                        'items' => ['$ref' => '#/components/schemas/UserResource']
                    ],
                    'meta' => ['$ref' => '#/components/schemas/PaginationMeta'],
                    'links' => ['$ref' => '#/components/schemas/PaginationLinks']
                ]
            ],
            'ServerCollection' => [
                'type' => 'object',
                'properties' => [
                    'data' => [
                        'type' => 'array',
                        'items' => ['$ref' => '#/components/schemas/ServerResource']
                    ],
                    'meta' => ['$ref' => '#/components/schemas/PaginationMeta'],
                    'links' => ['$ref' => '#/components/schemas/PaginationLinks']
                ]
            ],

            // Pagination schemas
            'PaginationMeta' => [
                'type' => 'object',
                'properties' => [
                    'current_page' => ['type' => 'integer', 'example' => 1],
                    'from' => ['type' => 'integer', 'example' => 1],
                    'last_page' => ['type' => 'integer', 'example' => 5],
                    'per_page' => ['type' => 'integer', 'example' => 15],
                    'to' => ['type' => 'integer', 'example' => 15],
                    'total' => ['type' => 'integer', 'example' => 75]
                ]
            ],
            'PaginationLinks' => [
                'type' => 'object',
                'properties' => [
                    'first' => ['type' => 'string', 'example' => 'https://api.1000proxy.io/v2/servers?page=1'],
                    'last' => ['type' => 'string', 'example' => 'https://api.1000proxy.io/v2/servers?page=5'],
                    'prev' => ['type' => 'string', 'nullable' => true, 'example' => null],
                    'next' => ['type' => 'string', 'example' => 'https://api.1000proxy.io/v2/servers?page=2']
                ]
            ],

            // Error schemas
            'ErrorResponse' => [
                'type' => 'object',
                'properties' => [
                    'error' => [
                        'type' => 'object',
                        'properties' => [
                            'code' => ['type' => 'string', 'example' => 'VALIDATION_ERROR'],
                            'message' => ['type' => 'string', 'example' => 'The given data was invalid.'],
                            'details' => [
                                'type' => 'object',
                                'additionalProperties' => [
                                    'type' => 'array',
                                    'items' => ['type' => 'string']
                                ]
                            ],
                            'timestamp' => ['type' => 'string', 'format' => 'date-time'],
                            'request_id' => ['type' => 'string', 'example' => 'req_1234567890']
                        ]
                    ]
                ]
            ]
        ];

        return array_merge($schemas, $this->generateModelSchemas());
    }

    /**
     * Generate model schemas dynamically
     */
    protected function generateModelSchemas(): array
    {
        $models = [
            'ServerBrand', 'ServerCategory', 'ServerPlan', 'ServerInbound',
            'ServerClient', 'OrderItem', 'WalletTransaction'
        ];

        $schemas = [];
        foreach ($models as $model) {
            $schemas[$model . 'Resource'] = $this->generateModelSchema($model);
        }

        return $schemas;
    }

    /**
     * Generate schema for specific model
     */
    protected function generateModelSchema(string $model): array
    {
        // This would introspect the actual model to generate schema
        // For now, returning basic structure
        return [
            'type' => 'object',
            'properties' => [
                'id' => ['type' => 'integer'],
                'name' => ['type' => 'string'],
                'created_at' => ['type' => 'string', 'format' => 'date-time'],
                'updated_at' => ['type' => 'string', 'format' => 'date-time']
            ]
        ];
    }

    /**
     * Initialize security schemes
     */
    protected function initializeSecuritySchemes(): void
    {
        $this->securitySchemes = [
            'BearerAuth' => [
                'type' => 'http',
                'scheme' => 'bearer',
                'bearerFormat' => 'JWT',
                'description' => 'JWT token obtained from /auth/login endpoint'
            ],
            'ApiKeyAuth' => [
                'type' => 'apiKey',
                'in' => 'header',
                'name' => 'X-API-Key',
                'description' => 'API key for server-to-server authentication'
            ],
            'BasicAuth' => [
                'type' => 'http',
                'scheme' => 'basic',
                'description' => 'Basic HTTP authentication for legacy endpoints'
            ]
        ];
    }

    /**
     * Get common responses
     */
    protected function getCommonResponses(): array
    {
        return [
            'BadRequest' => [
                'description' => 'Bad request - invalid parameters',
                'content' => [
                    'application/json' => [
                        'schema' => ['$ref' => '#/components/schemas/ErrorResponse']
                    ]
                ]
            ],
            'Unauthorized' => [
                'description' => 'Unauthorized - authentication required',
                'content' => [
                    'application/json' => [
                        'schema' => ['$ref' => '#/components/schemas/ErrorResponse']
                    ]
                ]
            ],
            'Forbidden' => [
                'description' => 'Forbidden - insufficient permissions',
                'content' => [
                    'application/json' => [
                        'schema' => ['$ref' => '#/components/schemas/ErrorResponse']
                    ]
                ]
            ],
            'NotFound' => [
                'description' => 'Resource not found',
                'content' => [
                    'application/json' => [
                        'schema' => ['$ref' => '#/components/schemas/ErrorResponse']
                    ]
                ]
            ],
            'ValidationError' => [
                'description' => 'Validation error - check request format',
                'content' => [
                    'application/json' => [
                        'schema' => ['$ref' => '#/components/schemas/ErrorResponse']
                    ]
                ]
            ],
            'InternalError' => [
                'description' => 'Internal server error',
                'content' => [
                    'application/json' => [
                        'schema' => ['$ref' => '#/components/schemas/ErrorResponse']
                    ]
                ]
            ]
        ];
    }

    /**
     * Get common parameters
     */
    protected function getCommonParameters(): array
    {
        return [
            'PageParameter' => [
                'name' => 'page',
                'in' => 'query',
                'schema' => ['type' => 'integer', 'minimum' => 1, 'default' => 1],
                'description' => 'Page number for pagination'
            ],
            'PerPageParameter' => [
                'name' => 'per_page',
                'in' => 'query',
                'schema' => ['type' => 'integer', 'minimum' => 1, 'maximum' => 100, 'default' => 15],
                'description' => 'Number of items per page'
            ],
            'SortParameter' => [
                'name' => 'sort',
                'in' => 'query',
                'schema' => ['type' => 'string'],
                'description' => 'Sort field (prefix with - for descending)'
            ],
            'FilterParameter' => [
                'name' => 'filter',
                'in' => 'query',
                'schema' => ['type' => 'object'],
                'description' => 'Filter criteria as key-value pairs'
            ]
        ];
    }

    /**
     * Get API tags
     */
    protected function getTags(): array
    {
        return [
            ['name' => 'Authentication', 'description' => 'User authentication and authorization'],
            ['name' => 'Users', 'description' => 'User management operations'],
            ['name' => 'Customers', 'description' => 'Customer management operations'],
            ['name' => 'Servers', 'description' => 'Server management and monitoring'],
            ['name' => 'Orders', 'description' => 'Order processing and management'],
            ['name' => 'Payments', 'description' => 'Payment processing and transactions'],
            ['name' => 'Proxy', 'description' => 'Proxy configuration and management'],
            ['name' => 'System', 'description' => 'System monitoring and administration'],
            ['name' => 'Mobile', 'description' => 'Mobile app specific endpoints']
        ];
    }

    /**
     * Get global security requirements
     */
    protected function getGlobalSecurity(): array
    {
        return [
            ['BearerAuth' => []],
            ['ApiKeyAuth' => []]
        ];
    }

    /**
     * Helper methods for path generation
     */
    protected function formatPath(string $uri): string
    {
        return '/' . str_replace(['api/v1/', 'api/v2/', '{', '}'], ['', '', '{', '}'], $uri);
    }

    protected function extractResourceType(string $uri): string
    {
        $parts = explode('/', $uri);
        $resourcePart = $parts[count($parts) - 1];

        // Remove {id} pattern
        $resourcePart = preg_replace('/\{.*?\}/', '', $resourcePart);

        // Convert plural to singular manually since str_singular is not available
        $resourcePart = rtrim($resourcePart, 's');

        return ucfirst($resourcePart ?: 'Resource');
    }

    protected function determinePathTags(array $route): array
    {
        $uri = $route['uri'];

        if (str_contains($uri, 'auth')) return ['Authentication'];
        if (str_contains($uri, 'users')) return ['Users'];
        if (str_contains($uri, 'customers')) return ['Customers'];
        if (str_contains($uri, 'servers')) return ['Servers'];
        if (str_contains($uri, 'orders')) return ['Orders'];
        if (str_contains($uri, 'payments')) return ['Payments'];
        if (str_contains($uri, 'proxy')) return ['Proxy'];
        if (str_contains($uri, 'mobile')) return ['Mobile'];

        return ['System'];
    }

    protected function generateSummary(array $route): string
    {
        $method = strtoupper($route['methods'][0]);
        $resource = $this->extractResourceType($route['uri']);

        switch ($method) {
            case 'GET':
                return str_contains($route['uri'], '{id}')
                    ? "Get single {$resource}"
                    : "List {$resource} resources";
            case 'POST':
                return "Create new {$resource}";
            case 'PUT':
            case 'PATCH':
                return "Update {$resource}";
            case 'DELETE':
                return "Delete {$resource}";
            default:
                return "{$method} {$resource}";
        }
    }

    protected function generateDescription(array $route): string
    {
        $summary = $this->generateSummary($route);
        $middleware = implode(', ', $route['middleware']);

        return $summary . ($middleware ? " (Middleware: {$middleware})" : "");
    }

    protected function generateOperationId(array $route): string
    {
        $method = strtolower($route['methods'][0]);
        $resource = strtolower($this->extractResourceType($route['uri']));
        $hasId = str_contains($route['uri'], '{id}');

        if ($method === 'get' && !$hasId) {
            return "list{$resource}s";
        } elseif ($method === 'get' && $hasId) {
            return "get{$resource}";
        } else {
            return "{$method}{$resource}";
        }
    }

    protected function generateParameters(array $route): array
    {
        $parameters = [];

        // Add path parameters
        foreach ($route['parameters'] as $param) {
            $parameters[] = [
                'name' => $param,
                'in' => 'path',
                'required' => true,
                'schema' => ['type' => 'integer'],
                'description' => "ID of the {$param}"
            ];
        }

        // Add query parameters for GET requests
        if ($route['methods'][0] === 'GET' && !str_contains($route['uri'], '{id}')) {
            $parameters[] = ['$ref' => '#/components/parameters/PageParameter'];
            $parameters[] = ['$ref' => '#/components/parameters/PerPageParameter'];
            $parameters[] = ['$ref' => '#/components/parameters/SortParameter'];
            $parameters[] = ['$ref' => '#/components/parameters/FilterParameter'];
        }

        return $parameters;
    }

    protected function determinePathSecurity(array $route): array
    {
        $middleware = $route['middleware'];

        if (in_array('auth:api', $middleware) || in_array('auth:sanctum', $middleware)) {
            return [['BearerAuth' => []]];
        }

        if (in_array('api.key', $middleware)) {
            return [['ApiKeyAuth' => []]];
        }

        // Public endpoints
        if (str_contains($route['uri'], 'public') || str_contains($route['uri'], 'auth/login')) {
            return [];
        }

        return [['BearerAuth' => []]];
    }

    protected function getControllerInfo(string $action): array
    {
        if (str_contains($action, '@')) {
            [$controller, $method] = explode('@', $action);
            return ['class' => $controller, 'method' => $method];
        }

        return ['class' => $action, 'method' => '__invoke'];
    }

    /**
     * Generate API rate limiting documentation
     */
    public function generateRateLimitingDocs(): array
    {
        return [
            'rate_limits' => [
                'authentication' => [
                    'description' => 'Authentication endpoints',
                    'limit' => '5 requests per minute per IP',
                    'headers' => [
                        'X-RateLimit-Limit' => 'Request limit for the time window',
                        'X-RateLimit-Remaining' => 'Remaining requests in current window',
                        'X-RateLimit-Reset' => 'Time when rate limit resets (Unix timestamp)'
                    ]
                ],
                'api_general' => [
                    'description' => 'General API endpoints',
                    'limit' => '100 requests per minute per user',
                    'headers' => [
                        'X-RateLimit-Limit' => 'Request limit for the time window',
                        'X-RateLimit-Remaining' => 'Remaining requests in current window',
                        'X-RateLimit-Reset' => 'Time when rate limit resets (Unix timestamp)'
                    ]
                ],
                'mobile_api' => [
                    'description' => 'Mobile app specific endpoints',
                    'limit' => '200 requests per minute per device',
                    'headers' => [
                        'X-RateLimit-Limit' => 'Request limit for the time window',
                        'X-RateLimit-Remaining' => 'Remaining requests in current window',
                        'X-RateLimit-Reset' => 'Time when rate limit resets (Unix timestamp)'
                    ]
                ],
                'admin_api' => [
                    'description' => 'Admin panel API endpoints',
                    'limit' => '500 requests per minute per admin user',
                    'headers' => [
                        'X-RateLimit-Limit' => 'Request limit for the time window',
                        'X-RateLimit-Remaining' => 'Remaining requests in current window',
                        'X-RateLimit-Reset' => 'Time when rate limit resets (Unix timestamp)'
                    ]
                ]
            ],
            'throttling_behavior' => [
                'exceeded_limit' => [
                    'status_code' => 429,
                    'response' => [
                        'error' => [
                            'code' => 'RATE_LIMIT_EXCEEDED',
                            'message' => 'Too many requests. Please try again later.',
                            'retry_after' => 60
                        ]
                    ]
                ],
                'progressive_delays' => 'Repeated violations result in longer throttling periods',
                'ip_blocking' => 'Severe abuse may result in temporary IP blocking'
            ]
        ];
    }

    /**
     * Generate API versioning documentation
     */
    public function generateVersioningDocs(): array
    {
        return [
            'versioning_strategy' => [
                'type' => 'URL Path Versioning',
                'pattern' => '/api/v{version}/',
                'current_version' => 'v2',
                'supported_versions' => ['v1', 'v2'],
                'deprecation_policy' => 'Versions supported for minimum 12 months after new version release'
            ],
            'version_details' => [
                'v1' => [
                    'status' => 'Deprecated',
                    'deprecation_date' => '2024-12-01',
                    'end_of_life' => '2025-12-01',
                    'description' => 'Initial API version with basic functionality',
                    'major_limitations' => [
                        'Limited authentication options',
                        'Basic error handling',
                        'No real-time capabilities'
                    ]
                ],
                'v2' => [
                    'status' => 'Current',
                    'release_date' => '2025-01-01',
                    'description' => 'Enhanced API with improved authentication, error handling, and real-time features',
                    'new_features' => [
                        'JWT authentication',
                        'Enhanced error responses',
                        'Real-time WebSocket support',
                        'Mobile-optimized endpoints',
                        'Advanced filtering and pagination',
                        'Comprehensive rate limiting',
                        'API key authentication'
                    ]
                ]
            ],
            'migration_guide' => [
                'v1_to_v2' => [
                    'authentication' => 'Update to use JWT tokens instead of basic auth',
                    'error_handling' => 'Update error response parsing for new format',
                    'endpoints' => 'Some endpoint paths have changed - see migration mapping',
                    'rate_limiting' => 'New rate limiting headers - update client logic',
                    'pagination' => 'Enhanced pagination with meta information'
                ]
            ]
        ];
    }

    /**
     * Export documentation to various formats
     */
    public function exportDocumentation(string $format = 'json'): mixed
    {
        $spec = $this->generateOpenAPISpecification();

        switch ($format) {
            case 'json':
                return json_encode($spec, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

            case 'yaml':
                // Convert to YAML manually since yaml_emit might not be available
                return $this->arrayToYaml($spec);

            case 'html':
                return $this->generateHTMLDocumentation($spec);

            default:
                throw new \InvalidArgumentException("Unsupported format: {$format}");
        }
    }

    /**
     * Generate HTML documentation
     */
    protected function generateHTMLDocumentation(array $spec): string
    {
        $title = $spec['info']['title'];
        $version = $spec['info']['version'];
        $description = $spec['info']['description'];

        return "
        <!DOCTYPE html>
        <html lang='en'>
        <head>
            <meta charset='UTF-8'>
            <meta name='viewport' content='width=device-width, initial-scale=1.0'>
            <title>{$title} API Documentation</title>
            <link rel='stylesheet' type='text/css' href='https://unpkg.com/swagger-ui-dist@3.52.5/swagger-ui.css' />
        </head>
        <body>
            <div id='swagger-ui'></div>
            <script src='https://unpkg.com/swagger-ui-dist@3.52.5/swagger-ui-bundle.js'></script>
            <script>
                SwaggerUIBundle({
                    url: '/api/docs/spec.json',
                    dom_id: '#swagger-ui',
                    presets: [
                        SwaggerUIBundle.presets.apis,
                        SwaggerUIBundle.presets.standalone
                    ],
                    layout: 'StandaloneLayout'
                });
            </script>
        </body>
        </html>";
    }

    /**
     * Validate API documentation completeness
     */
    public function validateDocumentation(): array
    {
        $spec = $this->generateOpenAPISpecification();
        $issues = [];

        // Check required fields
        $requiredFields = ['openapi', 'info', 'paths'];
        foreach ($requiredFields as $field) {
            if (!isset($spec[$field])) {
                $issues[] = "Missing required field: {$field}";
            }
        }

        // Check paths have proper responses
        foreach ($spec['paths'] as $path => $methods) {
            foreach ($methods as $method => $details) {
                if (!isset($details['responses']) || empty($details['responses'])) {
                    $issues[] = "Missing responses for {$method} {$path}";
                }
            }
        }

        // Check schemas are referenced
        $referencedSchemas = $this->findReferencedSchemas($spec);
        $definedSchemas = array_keys($spec['components']['schemas'] ?? []);

        $unusedSchemas = array_diff($definedSchemas, $referencedSchemas);
        foreach ($unusedSchemas as $schema) {
            $issues[] = "Unused schema definition: {$schema}";
        }

        return [
            'valid' => empty($issues),
            'issues' => $issues,
            'summary' => [
                'total_paths' => count($spec['paths']),
                'total_schemas' => count($spec['components']['schemas'] ?? []),
                'total_endpoints' => $this->countEndpoints($spec['paths']),
                'coverage_score' => $this->calculateCoverageScore($spec)
            ]
        ];
    }

    /**
     * Calculate documentation coverage score
     */
    protected function calculateCoverageScore(array $spec): float
    {
        $totalEndpoints = $this->countEndpoints($spec['paths']);
        $documentedEndpoints = 0;

        foreach ($spec['paths'] as $methods) {
            foreach ($methods as $details) {
                if (isset($details['summary'], $details['description'], $details['responses'])) {
                    $documentedEndpoints++;
                }
            }
        }

        return $totalEndpoints > 0 ? ($documentedEndpoints / $totalEndpoints) * 100 : 0;
    }

    /**
     * Count total endpoints
     */
    protected function countEndpoints(array $paths): int
    {
        $count = 0;
        foreach ($paths as $methods) {
            $count += count($methods);
        }
        return $count;
    }

    /**
     * Find referenced schemas in specification
     */
    protected function findReferencedSchemas(array $spec): array
    {
        $referenced = [];
        $content = json_encode($spec);

        preg_match_all('/"\$ref":\s*"#\/components\/schemas\/([^"]+)"/', $content, $matches);

        return array_unique($matches[1] ?? []);
    }

    /**
     * Convert array to YAML format
     */
    protected function arrayToYaml(array $data, int $indent = 0): string
    {
        $yaml = '';
        $spaces = str_repeat('  ', $indent);

        foreach ($data as $key => $value) {
            if (is_array($value)) {
                if (array_keys($value) === range(0, count($value) - 1)) {
                    // Numeric array
                    $yaml .= "{$spaces}{$key}:\n";
                    foreach ($value as $item) {
                        if (is_array($item)) {
                            $yaml .= "{$spaces}  -\n";
                            $yaml .= $this->arrayToYaml($item, $indent + 2);
                        } else {
                            $yaml .= "{$spaces}  - " . $this->escapeYamlValue($item) . "\n";
                        }
                    }
                } else {
                    // Associative array
                    $yaml .= "{$spaces}{$key}:\n";
                    $yaml .= $this->arrayToYaml($value, $indent + 1);
                }
            } else {
                $yaml .= "{$spaces}{$key}: " . $this->escapeYamlValue($value) . "\n";
            }
        }

        return $yaml;
    }

    /**
     * Escape YAML values
     */
    protected function escapeYamlValue($value): string
    {
        if (is_null($value)) {
            return 'null';
        }

        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        if (is_numeric($value)) {
            return (string) $value;
        }

        if (is_string($value)) {
            // Escape special characters and wrap in quotes if needed
            if (preg_match('/[:\[\]{},"\'`@&*!|>%]/', $value) || trim($value) !== $value) {
                return '"' . str_replace('"', '\"', $value) . '"';
            }
            return $value;
        }

        return (string) $value;
    }

    /**
     * Generate mobile app specific documentation
     */
    public function generateMobileAPIDocs(): array
    {
        return [
            'mobile_endpoints' => [
                'authentication' => [
                    'login' => 'POST /api/v2/mobile/auth/login',
                    'register' => 'POST /api/v2/mobile/auth/register',
                    'refresh' => 'POST /api/v2/mobile/auth/refresh',
                    'logout' => 'POST /api/v2/mobile/auth/logout'
                ],
                'user_profile' => [
                    'profile' => 'GET /api/v2/mobile/profile',
                    'update_profile' => 'PUT /api/v2/mobile/profile',
                    'change_password' => 'POST /api/v2/mobile/profile/password'
                ],
                'servers' => [
                    'list' => 'GET /api/v2/mobile/servers',
                    'details' => 'GET /api/v2/mobile/servers/{id}',
                    'status' => 'GET /api/v2/mobile/servers/{id}/status',
                    'test_connection' => 'POST /api/v2/mobile/servers/{id}/test'
                ],
                'orders' => [
                    'list' => 'GET /api/v2/mobile/orders',
                    'create' => 'POST /api/v2/mobile/orders',
                    'details' => 'GET /api/v2/mobile/orders/{id}',
                    'download_config' => 'GET /api/v2/mobile/orders/{id}/config'
                ],
                'notifications' => [
                    'register_device' => 'POST /api/v2/mobile/notifications/register',
                    'preferences' => 'GET /api/v2/mobile/notifications/preferences',
                    'update_preferences' => 'PUT /api/v2/mobile/notifications/preferences'
                ]
            ],
            'mobile_features' => [
                'push_notifications' => [
                    'order_status_updates',
                    'server_maintenance_alerts',
                    'payment_confirmations',
                    'promotional_offers'
                ],
                'offline_support' => [
                    'cached_server_list',
                    'saved_configurations',
                    'offline_order_history'
                ],
                'device_specific' => [
                    'device_registration',
                    'biometric_authentication',
                    'deep_linking',
                    'in_app_purchases'
                ]
            ]
        ];
    }
}
