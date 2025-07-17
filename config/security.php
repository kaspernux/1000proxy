<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Security Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains all security-related configuration options for the
    | 1000proxy application. These settings help protect against common
    | web vulnerabilities and attacks.
    |
    */

    /*
    |--------------------------------------------------------------------------
    | Password Security
    |--------------------------------------------------------------------------
    |
    | Configuration for password complexity and security requirements.
    |
    */
    'password' => [
        'min_length' => env('PASSWORD_MIN_LENGTH', 8),
        'max_length' => env('PASSWORD_MAX_LENGTH', 128),
        'require_uppercase' => env('PASSWORD_REQUIRE_UPPERCASE', true),
        'require_lowercase' => env('PASSWORD_REQUIRE_LOWERCASE', true),
        'require_numbers' => env('PASSWORD_REQUIRE_NUMBERS', true),
        'require_special_chars' => env('PASSWORD_REQUIRE_SPECIAL_CHARS', true),
        'password_history_limit' => env('PASSWORD_HISTORY_LIMIT', 5),
        'password_expiry_days' => env('PASSWORD_EXPIRY_DAYS', 90),
    ],

    /*
    |--------------------------------------------------------------------------
    | Rate Limiting
    |--------------------------------------------------------------------------
    |
    | Configuration for API rate limiting to prevent abuse and DDoS attacks.
    |
    */
    'rate_limiting' => [
        'enabled' => env('RATE_LIMITING_ENABLED', true),

        'limits' => [
            'default' => [
                'requests' => env('RATE_LIMIT_DEFAULT_REQUESTS', 60),
                'per_minutes' => env('RATE_LIMIT_DEFAULT_MINUTES', 1),
            ],
            'auth' => [
                'requests' => env('RATE_LIMIT_AUTH_REQUESTS', 5),
                'per_minutes' => env('RATE_LIMIT_AUTH_MINUTES', 5),
            ],
            'api' => [
                'requests' => env('RATE_LIMIT_API_REQUESTS', 100),
                'per_minutes' => env('RATE_LIMIT_API_MINUTES', 1),
            ],
            'admin' => [
                'requests' => env('RATE_LIMIT_ADMIN_REQUESTS', 30),
                'per_minutes' => env('RATE_LIMIT_ADMIN_MINUTES', 1),
            ],
            'payment' => [
                'requests' => env('RATE_LIMIT_PAYMENT_REQUESTS', 3),
                'per_minutes' => env('RATE_LIMIT_PAYMENT_MINUTES', 5),
            ],
        ],

        'ip_whitelist' => env('RATE_LIMIT_IP_WHITELIST', ''),
        'bypass_for_testing' => env('RATE_LIMIT_BYPASS_TESTING', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Login Security
    |--------------------------------------------------------------------------
    |
    | Configuration for login attempt monitoring and account lockout.
    |
    */
    'login_security' => [
        'max_attempts' => env('LOGIN_MAX_ATTEMPTS', 5),
        'lockout_duration' => env('LOGIN_LOCKOUT_DURATION', 60), // minutes
        'track_by_ip' => env('LOGIN_TRACK_BY_IP', true),
        'track_by_email' => env('LOGIN_TRACK_BY_EMAIL', true),
        'alert_admins' => env('LOGIN_ALERT_ADMINS', true),
        'log_successful' => env('LOGIN_LOG_SUCCESSFUL', true),
        'log_failed' => env('LOGIN_LOG_FAILED', true),

        // Two-Factor Authentication
        '2fa' => [
            'enabled' => env('TWO_FACTOR_ENABLED', true),
            'required_for_admin' => env('TWO_FACTOR_REQUIRED_ADMIN', true),
            'backup_codes_count' => env('TWO_FACTOR_BACKUP_CODES', 8),
            'qr_code_size' => env('TWO_FACTOR_QR_SIZE', 200),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Session Security
    |--------------------------------------------------------------------------
    |
    | Configuration for secure session management.
    |
    */
    'session' => [
        'timeout' => env('SESSION_TIMEOUT', 120), // minutes
        'regenerate_on_login' => env('SESSION_REGENERATE_LOGIN', true),
        'secure_cookies' => env('SESSION_SECURE_COOKIES', true),
        'same_site' => env('SESSION_SAME_SITE', 'strict'),
        'encrypt_cookies' => env('SESSION_ENCRYPT_COOKIES', true),

        // Session validation
        'validate_ip' => env('SESSION_VALIDATE_IP', true),
        'validate_user_agent' => env('SESSION_VALIDATE_USER_AGENT', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Security Headers
    |--------------------------------------------------------------------------
    |
    | Configuration for HTTP security headers.
    |
    */
    'headers' => [
        'csp' => [
            'enabled' => env('CSP_ENABLED', true),
            'report_only' => env('CSP_REPORT_ONLY', false),
            'report_uri' => env('CSP_REPORT_URI', null),
        ],

        'hsts' => [
            'enabled' => env('HSTS_ENABLED', true),
            'max_age' => env('HSTS_MAX_AGE', 31536000), // 1 year
            'include_subdomains' => env('HSTS_INCLUDE_SUBDOMAINS', true),
            'preload' => env('HSTS_PRELOAD', true),
        ],

        'referrer_policy' => env('REFERRER_POLICY', 'strict-origin-when-cross-origin'),
        'x_frame_options' => env('X_FRAME_OPTIONS', 'DENY'),
        'x_content_type_options' => env('X_CONTENT_TYPE_OPTIONS', 'nosniff'),
        'x_xss_protection' => env('X_XSS_PROTECTION', '1; mode=block'),
    ],

    /*
    |--------------------------------------------------------------------------
    | CSRF Protection
    |--------------------------------------------------------------------------
    |
    | Configuration for Cross-Site Request Forgery protection.
    |
    */
    'csrf' => [
        'enabled' => env('CSRF_ENABLED', true),
        'token_lifetime' => env('CSRF_TOKEN_LIFETIME', 120), // minutes
        'regenerate_on_use' => env('CSRF_REGENERATE_ON_USE', false),
        'exempt_routes' => [
            'webhooks/*',
            'api/public/*',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Input Validation & Sanitization
    |--------------------------------------------------------------------------
    |
    | Configuration for input validation and XSS prevention.
    |
    */
    'input_validation' => [
        'sanitize_html' => env('SANITIZE_HTML', true),
        'allow_html_tags' => ['b', 'i', 'u', 'strong', 'em', 'p', 'br'],
        'max_input_length' => env('MAX_INPUT_LENGTH', 10000),
        'block_suspicious_patterns' => env('BLOCK_SUSPICIOUS_PATTERNS', true),

        // File upload security
        'file_upload' => [
            'max_size' => env('MAX_FILE_SIZE', 10240), // KB
            'allowed_extensions' => ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'txt'],
            'scan_for_malware' => env('SCAN_FILES_MALWARE', false),
            'store_original_name' => env('STORE_ORIGINAL_FILENAME', false),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | IP & Geolocation Security
    |--------------------------------------------------------------------------
    |
    | Configuration for IP-based security and geolocation restrictions.
    |
    */
    'ip_security' => [
        'track_ip_changes' => env('TRACK_IP_CHANGES', true),
        'alert_on_ip_change' => env('ALERT_IP_CHANGE', true),
        'max_ips_per_user' => env('MAX_IPS_PER_USER', 5),

        // IP blocking
        'block_tor' => env('BLOCK_TOR', false),
        'block_vpn' => env('BLOCK_VPN', false),
        'blocked_countries' => env('BLOCKED_COUNTRIES', ''),
        'allowed_countries' => env('ALLOWED_COUNTRIES', ''),

        // IP whitelist/blacklist
        'ip_whitelist' => env('IP_WHITELIST', ''),
        'ip_blacklist' => env('IP_BLACKLIST', ''),
    ],

    /*
    |--------------------------------------------------------------------------
    | Database Security
    |--------------------------------------------------------------------------
    |
    | Configuration for database security and SQL injection prevention.
    |
    */
    'database' => [
        'log_queries' => env('LOG_DB_QUERIES', false),
        'slow_query_threshold' => env('SLOW_QUERY_THRESHOLD', 2000), // ms
        'detect_sql_injection' => env('DETECT_SQL_INJECTION', true),
        'backup_encryption' => env('BACKUP_ENCRYPTION', true),

        // Connection security
        'require_ssl' => env('DB_REQUIRE_SSL', false),
        'verify_ssl_cert' => env('DB_VERIFY_SSL_CERT', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | API Security
    |--------------------------------------------------------------------------
    |
    | Configuration for API security and authentication.
    |
    */
    'api' => [
        'require_https' => env('API_REQUIRE_HTTPS', true),
        'version_header' => env('API_VERSION_HEADER', 'X-API-Version'),
        'rate_limit_header' => env('API_RATE_LIMIT_HEADER', true),

        // API Key security
        'api_key_length' => env('API_KEY_LENGTH', 32),
        'api_key_expiry' => env('API_KEY_EXPIRY', 365), // days
        'rotate_keys' => env('ROTATE_API_KEYS', true),

        // JWT settings
        'jwt_expiry' => env('JWT_EXPIRY', 60), // minutes
        'jwt_refresh_expiry' => env('JWT_REFRESH_EXPIRY', 20160), // minutes (2 weeks)
        'jwt_blacklist_grace_period' => env('JWT_BLACKLIST_GRACE', 30), // seconds
    ],

    /*
    |--------------------------------------------------------------------------
    | Monitoring & Alerting
    |--------------------------------------------------------------------------
    |
    | Configuration for security monitoring and incident alerting.
    |
    */
    'monitoring' => [
        'log_security_events' => env('LOG_SECURITY_EVENTS', true),
        'alert_critical_events' => env('ALERT_CRITICAL_EVENTS', true),
        'admin_notification_email' => env('ADMIN_NOTIFICATION_EMAIL', 'admin@1000proxy.io'),

        // Security metrics
        'track_failed_logins' => env('TRACK_FAILED_LOGINS', true),
        'track_suspicious_requests' => env('TRACK_SUSPICIOUS_REQUESTS', true),
        'track_rate_limits' => env('TRACK_RATE_LIMITS', true),

        // Incident response
        'auto_block_threats' => env('AUTO_BLOCK_THREATS', true),
        'quarantine_suspicious_uploads' => env('QUARANTINE_UPLOADS', true),
        'emergency_lockdown_enabled' => env('EMERGENCY_LOCKDOWN', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Encryption & Hashing
    |--------------------------------------------------------------------------
    |
    | Configuration for encryption and password hashing.
    |
    */
    'encryption' => [
        'algorithm' => env('ENCRYPTION_ALGORITHM', 'AES-256-CBC'),
        'key_rotation_days' => env('KEY_ROTATION_DAYS', 90),

        // Password hashing
        'hash_algorithm' => env('HASH_ALGORITHM', 'bcrypt'),
        'bcrypt_rounds' => env('BCRYPT_ROUNDS', 12),
        'argon2_memory' => env('ARGON2_MEMORY', 1024),
        'argon2_threads' => env('ARGON2_THREADS', 2),
        'argon2_time' => env('ARGON2_TIME', 2),
    ],

    /*
    |--------------------------------------------------------------------------
    | Security Testing
    |--------------------------------------------------------------------------
    |
    | Configuration for security testing and vulnerability scanning.
    |
    */
    'testing' => [
        'enable_security_tests' => env('ENABLE_SECURITY_TESTS', false),
        'vulnerability_scan_schedule' => env('VULN_SCAN_SCHEDULE', 'weekly'),
        'penetration_testing' => env('ENABLE_PENTEST', false),
        'security_audit_log' => env('SECURITY_AUDIT_LOG', true),
    ],
];
