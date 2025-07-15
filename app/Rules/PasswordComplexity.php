<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class PasswordComplexity implements ValidationRule
{
    private array $requirements = [];
    private int $minLength = 8;
    private bool $requireUppercase = true;
    private bool $requireLowercase = true;
    private bool $requireNumbers = true;
    private bool $requireSpecialChars = true;
    private int $maxLength = 128;
    private array $commonPasswords = [];

    public function __construct(array $options = [])
    {
        $this->minLength = $options['min_length'] ?? 8;
        $this->maxLength = $options['max_length'] ?? 128;
        $this->requireUppercase = $options['require_uppercase'] ?? true;
        $this->requireLowercase = $options['require_lowercase'] ?? true;
        $this->requireNumbers = $options['require_numbers'] ?? true;
        $this->requireSpecialChars = $options['require_special_chars'] ?? true;

        $this->loadCommonPasswords();
    }

    /**
     * Run the validation rule.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!is_string($value)) {
            $fail('The :attribute must be a string.');
            return;
        }

        $this->requirements = [];

        // Check length requirements
        if (strlen($value) < $this->minLength) {
            $this->requirements[] = "at least {$this->minLength} characters long";
        }

        if (strlen($value) > $this->maxLength) {
            $this->requirements[] = "no more than {$this->maxLength} characters long";
        }

        // Check character requirements
        if ($this->requireUppercase && !preg_match('/[A-Z]/', $value)) {
            $this->requirements[] = "contain at least one uppercase letter";
        }

        if ($this->requireLowercase && !preg_match('/[a-z]/', $value)) {
            $this->requirements[] = "contain at least one lowercase letter";
        }

        if ($this->requireNumbers && !preg_match('/[0-9]/', $value)) {
            $this->requirements[] = "contain at least one number";
        }

        if ($this->requireSpecialChars && !preg_match('/[^a-zA-Z0-9]/', $value)) {
            $this->requirements[] = "contain at least one special character (!@#$%^&*()_+-=[]{}|;:,.<>?)";
        }

        // Check for common patterns
        if ($this->hasCommonPatterns($value)) {
            $this->requirements[] = "not contain common patterns like '123456', 'qwerty', or repeated characters";
        }

        // Check against common passwords
        if ($this->isCommonPassword($value)) {
            $this->requirements[] = "not be a commonly used password";
        }

        // Check for personal information patterns
        if ($this->containsPersonalInfo($value)) {
            $this->requirements[] = "not contain obvious personal information";
        }

        // If there are any failed requirements, fail validation
        if (!empty($this->requirements)) {
            $requirements = implode(', ', $this->requirements);
            $fail("The :attribute must {$requirements}.");
        }
    }

    /**
     * Check for common password patterns
     */
    private function hasCommonPatterns(string $password): bool
    {
        $patterns = [
            // Sequential numbers
            '/123456789/',
            '/987654321/',
            '/012345678/',

            // Sequential letters
            '/abcdefgh/i',
            '/qwertyui/i',
            '/asdfghjk/i',
            '/zxcvbnm/i',

            // Repeated characters (3 or more in a row)
            '/(.)\1{2,}/',

            // Simple patterns
            '/password/i',
            '/login/i',
            '/admin/i',
            '/user/i',
            '/test/i',
            '/guest/i',

            // Date patterns
            '/19[0-9]{2}/',
            '/20[0-9]{2}/',

            // Simple keyboard patterns
            '/111111/',
            '/aaaaaa/',
            '/000000/',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $password)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if password is in common passwords list
     */
    private function isCommonPassword(string $password): bool
    {
        $lowercasePassword = strtolower($password);
        return in_array($lowercasePassword, $this->commonPasswords);
    }

    /**
     * Check for personal information patterns
     */
    private function containsPersonalInfo(string $password): bool
    {
        // Check for obvious personal info patterns
        $personalPatterns = [
            // Names that might be common
            '/john/i',
            '/jane/i',
            '/admin/i',
            '/user/i',
            '/guest/i',

            // Birth years
            '/19[5-9][0-9]/',
            '/20[0-2][0-9]/',

            // Common email domains
            '/gmail/i',
            '/yahoo/i',
            '/hotmail/i',
            '/outlook/i',

            // Phone number patterns
            '/\d{10}/',
            '/\d{3}-\d{3}-\d{4}/',
        ];

        foreach ($personalPatterns as $pattern) {
            if (preg_match($pattern, $password)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Load common passwords list
     */
    private function loadCommonPasswords(): void
    {
        $this->commonPasswords = [
            'password', 'password123', '123456789', '12345678', '123456',
            'qwerty', 'qwerty123', 'abc123', 'password1', '123123',
            'admin', 'admin123', 'administrator', 'root', 'toor',
            'guest', 'guest123', 'user', 'user123', 'test',
            'test123', 'demo', 'demo123', 'sample', 'example',
            'welcome', 'welcome123', 'hello', 'hello123', 'login',
            'master', 'master123', 'super', 'super123', 'secret',
            '1234567890', '0987654321', 'qwertyuiop', 'asdfghjkl',
            'zxcvbnm', 'letmein', 'monkey', 'dragon', 'mustang',
            'baseball', 'football', 'basketball', 'soccer', 'hockey',
            'jordan', 'michael', 'jennifer', 'joshua', 'matthew',
            'daniel', 'david', 'andrew', 'robert', 'john',
            'iloveyou', 'sunshine', 'princess', 'lovely', 'baby',
            'computer', 'internet', 'service', 'server', 'network',
            'system', 'windows', 'microsoft', 'google', 'facebook',
            'twitter', 'instagram', 'linkedin', 'github', 'stackoverflow'
        ];
    }

    /**
     * Get password strength score (0-100)
     */
    public static function getPasswordStrength(string $password): array
    {
        $score = 0;
        $feedback = [];

        // Length scoring (0-30 points)
        $length = strlen($password);
        if ($length >= 12) {
            $score += 30;
        } elseif ($length >= 8) {
            $score += 20;
        } elseif ($length >= 6) {
            $score += 10;
            $feedback[] = 'Consider using a longer password for better security';
        } else {
            $feedback[] = 'Password is too short - use at least 8 characters';
        }

        // Character variety (0-40 points)
        $hasLower = preg_match('/[a-z]/', $password);
        $hasUpper = preg_match('/[A-Z]/', $password);
        $hasNumber = preg_match('/[0-9]/', $password);
        $hasSpecial = preg_match('/[^a-zA-Z0-9]/', $password);

        $variety = 0;
        if ($hasLower) $variety++;
        if ($hasUpper) $variety++;
        if ($hasNumber) $variety++;
        if ($hasSpecial) $variety++;

        $score += $variety * 10;

        if ($variety < 3) {
            $missing = [];
            if (!$hasLower) $missing[] = 'lowercase letters';
            if (!$hasUpper) $missing[] = 'uppercase letters';
            if (!$hasNumber) $missing[] = 'numbers';
            if (!$hasSpecial) $missing[] = 'special characters';
            $feedback[] = 'Add ' . implode(', ', $missing) . ' to strengthen your password';
        }

        // Pattern detection (0-20 points)
        $rule = new self();
        if (!$rule->hasCommonPatterns($password)) {
            $score += 15;
        } else {
            $feedback[] = 'Avoid common patterns like "123456" or "qwerty"';
        }

        if (!$rule->isCommonPassword($password)) {
            $score += 5;
        } else {
            $feedback[] = 'This is a commonly used password - choose something unique';
        }

        // Uniqueness bonus (0-10 points)
        $uniqueChars = count(array_unique(str_split($password)));
        if ($uniqueChars >= $length * 0.7) {
            $score += 10;
        } elseif ($uniqueChars >= $length * 0.5) {
            $score += 5;
        } else {
            $feedback[] = 'Use more unique characters instead of repeating them';
        }

        // Cap score at 100
        $score = min(100, $score);

        // Determine strength level
        if ($score >= 80) {
            $strength = 'Very Strong';
        } elseif ($score >= 60) {
            $strength = 'Strong';
        } elseif ($score >= 40) {
            $strength = 'Moderate';
        } elseif ($score >= 20) {
            $strength = 'Weak';
        } else {
            $strength = 'Very Weak';
        }

        return [
            'score' => $score,
            'strength' => $strength,
            'feedback' => $feedback
        ];
    }
}
