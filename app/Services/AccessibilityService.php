<?php

namespace App\Services;

use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

/**
 * Accessibility Enhancement Service
 *
 * Provides comprehensive accessibility improvements including:
 * - ARIA label management
 * - Keyboard navigation support
 * - Screen reader optimization
 * - Color contrast validation
 * - Focus management
 */
class AccessibilityService
{
    protected array $ariaLabels = [];
    protected array $keyboardShortcuts = [];
    protected array $focusManagement = [];
    protected array $colorContrast = [];

    public function __construct()
    {
        $this->loadDefaultConfiguration();
    }

    /**
     * Load default accessibility configuration
     */
    protected function loadDefaultConfiguration(): void
    {
        // Default ARIA labels for common elements
        $this->ariaLabels = [
            'navigation' => [
                'main-nav' => 'Main navigation',
                'breadcrumb' => 'Breadcrumb navigation',
                'pagination' => 'Pagination navigation',
                'user-menu' => 'User account menu',
                'mobile-menu' => 'Mobile navigation menu',
            ],
            'forms' => [
                'search' => 'Search form',
                'login' => 'Login form',
                'registration' => 'Registration form',
                'contact' => 'Contact form',
                'checkout' => 'Checkout form',
            ],
            'buttons' => [
                'submit' => 'Submit form',
                'cancel' => 'Cancel action',
                'delete' => 'Delete item',
                'edit' => 'Edit item',
                'save' => 'Save changes',
                'close' => 'Close dialog',
                'menu-toggle' => 'Toggle navigation menu',
            ],
            'tables' => [
                'data-table' => 'Data table',
                'sort-asc' => 'Sort ascending',
                'sort-desc' => 'Sort descending',
                'filter' => 'Filter table data',
                'export' => 'Export table data',
            ],
            'status' => [
                'loading' => 'Content is loading',
                'error' => 'Error occurred',
                'success' => 'Action completed successfully',
                'warning' => 'Warning message',
                'info' => 'Information message',
            ]
        ];

        // Default keyboard shortcuts
        $this->keyboardShortcuts = [
            'global' => [
                'alt+m' => 'Open main menu',
                'alt+s' => 'Focus search',
                'alt+h' => 'Go to homepage',
                'alt+l' => 'Go to login',
                'esc' => 'Close modal/dropdown',
            ],
            'tables' => [
                'arrow-up' => 'Move to previous row',
                'arrow-down' => 'Move to next row',
                'arrow-left' => 'Move to previous column',
                'arrow-right' => 'Move to next column',
                'home' => 'Move to first column',
                'end' => 'Move to last column',
                'page-up' => 'Move to first row',
                'page-down' => 'Move to last row',
                'enter' => 'Activate selected item',
                'space' => 'Select/deselect item',
            ],
            'forms' => [
                'tab' => 'Move to next field',
                'shift+tab' => 'Move to previous field',
                'enter' => 'Submit form',
                'esc' => 'Cancel form',
            ]
        ];

        // Color contrast requirements (WCAG 2.1 AA)
        $this->colorContrast = [
            'normal_text' => 4.5,      // 4.5:1 for normal text
            'large_text' => 3.0,       // 3:1 for large text (18pt+ or 14pt+ bold)
            'ui_components' => 3.0,    // 3:1 for UI components
            'graphics' => 3.0,         // 3:1 for graphics
        ];
    }

    /**
     * Add ARIA attributes to HTML elements
     */
    public function addAriaAttributes(string $html, array $options = []): string
    {
        // Add role attributes
        $html = $this->addRoleAttributes($html);

        // Add aria-label attributes
        $html = $this->addAriaLabels($html);

        // Add aria-describedby attributes
        $html = $this->addAriaDescribedBy($html);

        // Add aria-expanded for dropdowns
        $html = $this->addAriaExpanded($html);

        // Add aria-live regions
        $html = $this->addLiveRegions($html);

        return $html;
    }

    /**
     * Add role attributes to semantic elements
     */
    protected function addRoleAttributes(string $html): string
    {
        $roleMap = [
            '<nav' => '<nav role="navigation"',
            '<main' => '<main role="main"',
            '<aside' => '<aside role="complementary"',
            '<section' => '<section role="region"',
            '<article' => '<article role="article"',
            '<header' => '<header role="banner"',
            '<footer' => '<footer role="contentinfo"',
        ];

        foreach ($roleMap as $tag => $replacement) {
            // Only add role if not already present
            if (strpos($html, $tag) !== false && strpos($html, 'role=') === false) {
                $html = str_replace($tag, $replacement, $html);
            }
        }

        return $html;
    }

    /**
     * Add appropriate aria-label attributes
     */
    protected function addAriaLabels(string $html): string
    {
        // Add aria-labels to buttons without text content
        $html = preg_replace_callback(
            '/<button([^>]*?)>(\s*<[^>]+>\s*)*<\/button>/i',
            function ($matches) {
                $attributes = $matches[1];
                $content = $matches[2] ?? '';

                // If button has no text content and no aria-label
                if (empty(strip_tags($content)) && strpos($attributes, 'aria-label') === false) {
                    // Try to determine purpose from classes or icons
                    $label = $this->guessButtonLabel($attributes, $content);
                    if ($label) {
                        $attributes .= ' aria-label="' . htmlspecialchars($label) . '"';
                    }
                }

                return "<button{$attributes}>{$content}</button>";
            },
            $html
        );

        // Add aria-labels to form inputs without labels
        $html = preg_replace_callback(
            '/<input([^>]*?)>/i',
            function ($matches) {
                $attributes = $matches[1];

                // If input has no aria-label and no associated label
                if (strpos($attributes, 'aria-label') === false) {
                    $label = $this->guessInputLabel($attributes);
                    if ($label) {
                        $attributes .= ' aria-label="' . htmlspecialchars($label) . '"';
                    }
                }

                return "<input{$attributes}>";
            },
            $html
        );

        return $html;
    }

    /**
     * Guess button label from attributes and content
     */
    protected function guessButtonLabel(string $attributes, string $content): ?string
    {
        // Check for common icon classes
        if (strpos($attributes, 'close') !== false || strpos($content, 'close') !== false) {
            return 'Close';
        }
        if (strpos($attributes, 'delete') !== false || strpos($content, 'delete') !== false) {
            return 'Delete';
        }
        if (strpos($attributes, 'edit') !== false || strpos($content, 'edit') !== false) {
            return 'Edit';
        }
        if (strpos($attributes, 'save') !== false || strpos($content, 'save') !== false) {
            return 'Save';
        }
        if (strpos($attributes, 'submit') !== false) {
            return 'Submit';
        }
        if (strpos($attributes, 'search') !== false || strpos($content, 'search') !== false) {
            return 'Search';
        }
        if (strpos($attributes, 'menu') !== false || strpos($content, 'menu') !== false) {
            return 'Toggle menu';
        }

        return null;
    }

    /**
     * Guess input label from attributes
     */
    protected function guessInputLabel(string $attributes): ?string
    {
        // Check for type and name attributes
        if (preg_match('/type=["\']?email["\']?/i', $attributes)) {
            return 'Email address';
        }
        if (preg_match('/type=["\']?password["\']?/i', $attributes)) {
            return 'Password';
        }
        if (preg_match('/type=["\']?search["\']?/i', $attributes)) {
            return 'Search';
        }
        if (preg_match('/name=["\']?username["\']?/i', $attributes)) {
            return 'Username';
        }
        if (preg_match('/name=["\']?phone["\']?/i', $attributes)) {
            return 'Phone number';
        }

        return null;
    }

    /**
     * Add aria-describedby attributes for form validation
     */
    protected function addAriaDescribedBy(string $html): string
    {
        // This would be enhanced to link form fields with their error messages
        return $html;
    }

    /**
     * Add aria-expanded for dropdown elements
     */
    protected function addAriaExpanded(string $html): string
    {
        // Add aria-expanded to dropdown triggers
        $html = preg_replace(
            '/<button([^>]*?dropdown[^>]*?)>/i',
            '<button$1 aria-expanded="false">',
            $html
        );

        return $html;
    }

    /**
     * Add live regions for dynamic content
     */
    protected function addLiveRegions(string $html): string
    {
        // Add aria-live to alert containers
        $html = preg_replace(
            '/<div([^>]*?alert[^>]*?)>/i',
            '<div$1 aria-live="polite" role="alert">',
            $html
        );

        // Add aria-live to status containers
        $html = preg_replace(
            '/<div([^>]*?status[^>]*?)>/i',
            '<div$1 aria-live="polite" role="status">',
            $html
        );

        return $html;
    }

    /**
     * Generate keyboard navigation JavaScript
     */
    public function generateKeyboardNavigation(): string
    {
        return view('accessibility.keyboard-navigation', [
            'shortcuts' => $this->keyboardShortcuts
        ])->render();
    }

    /**
     * Generate screen reader announcements
     */
    public function addScreenReaderAnnouncements(string $html): string
    {
        // Add screen reader only text for important actions
        $srOnlyStyle = 'position: absolute; width: 1px; height: 1px; padding: 0; margin: -1px; overflow: hidden; clip: rect(0,0,0,0); white-space: nowrap; border: 0;';

        // Add screen reader text to icon-only buttons
        $html = preg_replace_callback(
            '/<button([^>]*?)>(\s*<[^>]+>\s*)<\/button>/i',
            function ($matches) use ($srOnlyStyle) {
                $attributes = $matches[1];
                $content = $matches[2];

                // If button only contains icons and no aria-label
                if (strpos($content, '<svg') !== false || strpos($content, '<i ') !== false) {
                    if (strpos($attributes, 'aria-label') === false) {
                        $label = $this->guessButtonLabel($attributes, $content);
                        if ($label) {
                            $content .= '<span style="' . $srOnlyStyle . '">' . htmlspecialchars($label) . '</span>';
                        }
                    }
                }

                return "<button{$attributes}>{$content}</button>";
            },
            $html
        );

        return $html;
    }

    /**
     * Validate color contrast ratios
     */
    public function validateColorContrast(array $colorPairs): array
    {
        $results = [];

        foreach ($colorPairs as $context => $pair) {
            $foreground = $pair['foreground'];
            $background = $pair['background'];
            $ratio = $this->calculateContrastRatio($foreground, $background);

            $results[$context] = [
                'ratio' => $ratio,
                'passes_aa' => $this->passesWCAGAA($ratio, $context),
                'passes_aaa' => $this->passesWCAGAAA($ratio, $context),
                'recommendation' => $this->getContrastRecommendation($ratio, $context)
            ];
        }

        return $results;
    }

    /**
     * Calculate contrast ratio between two colors
     */
    protected function calculateContrastRatio(string $color1, string $color2): float
    {
        $luminance1 = $this->getRelativeLuminance($color1);
        $luminance2 = $this->getRelativeLuminance($color2);

        $lighter = max($luminance1, $luminance2);
        $darker = min($luminance1, $luminance2);

        return ($lighter + 0.05) / ($darker + 0.05);
    }

    /**
     * Get relative luminance of a color
     */
    protected function getRelativeLuminance(string $color): float
    {
        // Convert hex to RGB
        $color = ltrim($color, '#');
        $r = hexdec(substr($color, 0, 2)) / 255;
        $g = hexdec(substr($color, 2, 2)) / 255;
        $b = hexdec(substr($color, 4, 2)) / 255;

        // Apply gamma correction
        $r = $r <= 0.03928 ? $r / 12.92 : pow(($r + 0.055) / 1.055, 2.4);
        $g = $g <= 0.03928 ? $g / 12.92 : pow(($g + 0.055) / 1.055, 2.4);
        $b = $b <= 0.03928 ? $b / 12.92 : pow(($b + 0.055) / 1.055, 2.4);

        // Calculate relative luminance
        return 0.2126 * $r + 0.7152 * $g + 0.0722 * $b;
    }

    /**
     * Check if contrast ratio passes WCAG AA
     */
    protected function passesWCAGAA(float $ratio, string $context): bool
    {
        $requirement = match($context) {
            'large_text' => 3.0,
            'ui_components' => 3.0,
            'graphics' => 3.0,
            default => 4.5
        };

        return $ratio >= $requirement;
    }

    /**
     * Check if contrast ratio passes WCAG AAA
     */
    protected function passesWCAGAAA(float $ratio, string $context): bool
    {
        $requirement = match($context) {
            'large_text' => 4.5,
            'ui_components' => 4.5,
            'graphics' => 4.5,
            default => 7.0
        };

        return $ratio >= $requirement;
    }

    /**
     * Get contrast improvement recommendation
     */
    protected function getContrastRecommendation(float $ratio, string $context): string
    {
        if ($this->passesWCAGAAA($ratio, $context)) {
            return 'Excellent contrast - meets WCAG AAA standards';
        } elseif ($this->passesWCAGAA($ratio, $context)) {
            return 'Good contrast - meets WCAG AA standards';
        } else {
            $required = match($context) {
                'large_text', 'ui_components', 'graphics' => 3.0,
                default => 4.5
            };
            return sprintf('Insufficient contrast (%.2f:1). Minimum required: %.1f:1', $ratio, $required);
        }
    }

    /**
     * Generate focus management JavaScript
     */
    public function generateFocusManagement(): string
    {
        return view('accessibility.focus-management')->render();
    }

    /**
     * Create accessibility audit report
     */
    public function createAuditReport(string $html, array $options = []): array
    {
        $report = [
            'timestamp' => now()->toISOString(),
            'checks' => [],
            'score' => 0,
            'recommendations' => []
        ];

        // Check for missing alt attributes
        $report['checks']['alt_attributes'] = $this->checkAltAttributes($html);

        // Check for heading structure
        $report['checks']['heading_structure'] = $this->checkHeadingStructure($html);

        // Check for form labels
        $report['checks']['form_labels'] = $this->checkFormLabels($html);

        // Check for keyboard navigation
        $report['checks']['keyboard_navigation'] = $this->checkKeyboardNavigation($html);

        // Check for ARIA attributes
        $report['checks']['aria_attributes'] = $this->checkAriaAttributes($html);

        // Calculate overall score
        $totalChecks = count($report['checks']);
        $passedChecks = array_sum(array_column($report['checks'], 'passed'));
        $report['score'] = $totalChecks > 0 ? round(($passedChecks / $totalChecks) * 100) : 0;

        // Generate recommendations
        $report['recommendations'] = $this->generateRecommendations($report['checks']);

        return $report;
    }

    /**
     * Check for missing alt attributes on images
     */
    protected function checkAltAttributes(string $html): array
    {
        preg_match_all('/<img[^>]*>/i', $html, $images);
        $total = count($images[0]);
        $withAlt = 0;

        foreach ($images[0] as $img) {
            if (strpos($img, 'alt=') !== false) {
                $withAlt++;
            }
        }

        return [
            'name' => 'Image Alt Attributes',
            'total' => $total,
            'passed' => $withAlt,
            'failed' => $total - $withAlt,
            'score' => $total > 0 ? round(($withAlt / $total) * 100) : 100
        ];
    }

    /**
     * Check heading structure (h1, h2, h3, etc.)
     */
    protected function checkHeadingStructure(string $html): array
    {
        preg_match_all('/<h([1-6])[^>]*>/i', $html, $headings);
        $levels = array_map('intval', $headings[1]);

        $issues = 0;
        $hasH1 = in_array(1, $levels);

        if (!$hasH1) {
            $issues++;
        }

        // Check for skipped heading levels
        for ($i = 1; $i < count($levels); $i++) {
            if ($levels[$i] > $levels[$i-1] + 1) {
                $issues++;
            }
        }

        return [
            'name' => 'Heading Structure',
            'total' => count($levels),
            'passed' => count($levels) - $issues,
            'failed' => $issues,
            'score' => count($levels) > 0 ? round(((count($levels) - $issues) / count($levels)) * 100) : 100
        ];
    }

    /**
     * Check for form labels
     */
    protected function checkFormLabels(string $html): array
    {
        preg_match_all('/<input[^>]*>/i', $html, $inputs);
        $total = count($inputs[0]);
        $withLabels = 0;

        foreach ($inputs[0] as $input) {
            if (strpos($input, 'aria-label=') !== false ||
                strpos($input, 'aria-labelledby=') !== false ||
                strpos($input, 'type="hidden"') !== false) {
                $withLabels++;
            }
        }

        return [
            'name' => 'Form Labels',
            'total' => $total,
            'passed' => $withLabels,
            'failed' => $total - $withLabels,
            'score' => $total > 0 ? round(($withLabels / $total) * 100) : 100
        ];
    }

    /**
     * Check keyboard navigation support
     */
    protected function checkKeyboardNavigation(string $html): array
    {
        // Check for tabindex usage
        preg_match_all('/tabindex=["\']?(-?\d+)["\']?/i', $html, $tabindex);
        $negativeTabindex = array_filter($tabindex[1], fn($val) => intval($val) < 0);

        return [
            'name' => 'Keyboard Navigation',
            'total' => count($tabindex[1]),
            'passed' => count($tabindex[1]) - count($negativeTabindex),
            'failed' => count($negativeTabindex),
            'score' => count($tabindex[1]) > 0 ? round(((count($tabindex[1]) - count($negativeTabindex)) / count($tabindex[1])) * 100) : 100
        ];
    }

    /**
     * Check ARIA attributes usage
     */
    protected function checkAriaAttributes(string $html): array
    {
        $ariaCount = preg_match_all('/aria-[a-z]+=/i', $html);
        $roleCount = preg_match_all('/role=/i', $html);

        $total = $ariaCount + $roleCount;

        return [
            'name' => 'ARIA Attributes',
            'total' => $total,
            'passed' => $total,
            'failed' => 0,
            'score' => 100
        ];
    }

    /**
     * Generate accessibility recommendations
     */
    protected function generateRecommendations(array $checks): array
    {
        $recommendations = [];

        foreach ($checks as $check) {
            if ($check['score'] < 100) {
                $recommendations[] = match($check['name']) {
                    'Image Alt Attributes' => 'Add alt attributes to all images. Use empty alt="" for decorative images.',
                    'Heading Structure' => 'Ensure proper heading hierarchy (h1, h2, h3, etc.) and include at least one h1.',
                    'Form Labels' => 'Associate all form inputs with proper labels using aria-label or aria-labelledby.',
                    'Keyboard Navigation' => 'Avoid negative tabindex values and ensure logical tab order.',
                    'ARIA Attributes' => 'Add appropriate ARIA attributes to enhance screen reader compatibility.',
                    default => 'Review and improve ' . $check['name']
                };
            }
        }

        return $recommendations;
    }
}
