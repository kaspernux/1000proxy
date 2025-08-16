<?php

namespace Tests\Feature\Frontend;

use App\Models\Customer;
use App\Models\Order;
use App\Models\Server;
use App\Models\ServerPlan;
use App\Models\ServerCategory;
use App\Models\ServerBrand;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class AccessibilityTestSuite extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected Customer $customer;
    protected ServerCategory $category;
    protected ServerBrand $brand;
    protected Server $server;
    protected ServerPlan $serverPlan;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test users
        $this->admin = User::factory()->create([
            'email' => 'admin@test.com',
            'name' => 'Admin User',
        ]);

        $this->customer = Customer::factory()->create([
            'email' => 'customer@test.com',
            'name' => 'Test Customer',
        ]);

        // Create test data
        $this->createTestData();
    }

    protected function createTestData(): void
    {
        // Create server categories
        $this->category = ServerCategory::create([
            'name' => 'Gaming',
            'description' => 'Gaming servers',
            'is_active' => true,
        ]);

        // Create server brands
        $this->brand = ServerBrand::create([
            'name' => 'ProxyTitan',
            'description' => 'Premium proxy provider',
            'is_active' => true,
        ]);

        // Create server
        $this->server = Server::create([
            'name' => 'Test Server',
            'location' => 'US',
            'ip_address' => '192.168.1.100',
            'panel_port' => '2053',
            'panel_username' => 'admin',
            'panel_password' => 'password',
            'is_active' => true,
        ]);

        // Create server plan
        $this->serverPlan = ServerPlan::create([
            'server_id' => $this->server->id,
            'category_id' => $this->category->id,
            'brand_id' => $this->brand->id,
            'name' => 'Gaming Pro',
            'price' => 9.99,
            'is_active' => true,
        ]);
    }

    #[Test]
    public function page_has_proper_heading_structure()
    {
        $response = $this->get('/');

        $content = $response->getContent();

        // Check for h1 tag
        $this->assertStringContainsString('<h1', $content);

        // Check heading hierarchy (should not skip levels)
        preg_match_all('/<h([1-6])[^>]*>/i', $content, $matches);

        if (!empty($matches[1])) {
            $headingLevels = array_map('intval', $matches[1]);
            $firstLevel = $headingLevels[0];

            // First heading should typically be h1
            $this->assertEquals(1, $firstLevel, 'First heading should be h1');
        }

        $response->assertOk();
    }

    #[Test]
    public function images_have_alt_attributes()
    {
        $response = $this->get('/');

        $content = $response->getContent();

        // Find all img tags
        preg_match_all('/<img[^>]+>/i', $content, $imgTags);

        foreach ($imgTags[0] as $imgTag) {
            // Check if img has alt attribute
            $this->assertStringContainsString('alt=', $imgTag, 'All images should have alt attributes');
        }

        $response->assertOk();
    }

    #[Test]
    public function forms_have_proper_labels()
    {
        $response = $this->get('/register');

        $content = $response->getContent();

        // Check for input elements
        preg_match_all('/<input[^>]+>/i', $content, $inputs);

        foreach ($inputs[0] as $input) {
            // Skip hidden inputs and buttons
            if (strpos($input, 'type="hidden"') !== false ||
                strpos($input, 'type="submit"') !== false ||
                strpos($input, 'type="button"') !== false) {
                continue;
            }

            // Check for id attribute to match with label
            preg_match('/id=["\']([^"\']+)["\']/', $input, $idMatch);

            if (!empty($idMatch[1])) {
                $inputId = $idMatch[1];
                // Look for corresponding label
                $this->assertStringContainsString('for="' . $inputId . '"', $content,
                    'Input with id "' . $inputId . '" should have a corresponding label');
            }
        }

        $response->assertOk();
    }

    #[Test]
    public function buttons_have_accessible_text()
    {
        $response = $this->get('/products');

        $content = $response->getContent();

        // Check for buttons without text content
        preg_match_all('/<button[^>]*>(.*?)<\/button>/is', $content, $buttons);

        foreach ($buttons[0] as $index => $button) {
            $buttonText = strip_tags($buttons[1][$index]);

            // If button has no text, it should have aria-label
            if (empty(trim($buttonText))) {
                $this->assertStringContainsString('aria-label=', $button,
                    'Buttons without text should have aria-label');
            }
        }

        $response->assertOk();
    }

    #[Test]
    public function links_have_descriptive_text()
    {
        $response = $this->get('/');

        $content = $response->getContent();

        // Check for links with non-descriptive text
        preg_match_all('/<a[^>]*>(.*?)<\/a>/is', $content, $links);

        foreach ($links[1] as $linkText) {
            $text = trim(strip_tags($linkText));

            // Check for generic link text
            $genericTexts = ['click here', 'read more', 'more', 'here', 'link'];

            if (!empty($text)) {
                $this->assertNotContains(strtolower($text), $genericTexts,
                    'Links should have descriptive text, not generic text like "' . $text . '"');
            }
        }

        $response->assertOk();
    }

    #[Test]
    public function page_has_skip_navigation_link()
    {
        $response = $this->get('/');

        $content = $response->getContent();

        // Look for skip navigation link
        $this->assertStringContainsString('skip', strtolower($content));
        $this->assertStringContainsString('main', strtolower($content));

        $response->assertOk();
    }

    #[Test]
    public function color_contrast_meets_standards()
    {
        $response = $this->get('/');

        $content = $response->getContent();

        // Check for CSS that might indicate proper contrast
        // This is a basic check - full contrast testing requires specialized tools
        $this->assertStringContainsString('contrast', strtolower($content));

        $response->assertOk();
    }

    #[Test]
    public function focus_indicators_are_visible()
    {
        $response = $this->get('/');

        $content = $response->getContent();

        // Check for focus-related CSS classes
        $focusClasses = ['focus:', 'focus-visible', 'focus-within'];

        $hasFocusStyles = false;
        foreach ($focusClasses as $focusClass) {
            if (strpos($content, $focusClass) !== false) {
                $hasFocusStyles = true;
                break;
            }
        }

        $this->assertTrue($hasFocusStyles, 'Page should have focus indicator styles');

        $response->assertOk();
    }

    #[Test]
    public function keyboard_navigation_is_supported()
    {
        $response = $this->get('/products');

        $content = $response->getContent();

        // Check for tabindex attributes and keyboard event handlers
        $keyboardSupport = [
            'tabindex',
            'onkeydown',
            'onkeyup',
            'onkeypress',
            'accesskey'
        ];

        $hasKeyboardSupport = false;
        foreach ($keyboardSupport as $attr) {
            if (strpos(strtolower($content), $attr) !== false) {
                $hasKeyboardSupport = true;
                break;
            }
        }

        $this->assertTrue($hasKeyboardSupport, 'Page should support keyboard navigation');

        $response->assertOk();
    }

    #[Test]
    public function aria_landmarks_are_present()
    {
        $response = $this->get('/');

        $content = $response->getContent();

        // Check for ARIA landmarks
        $landmarks = [
            'role="banner"',
            'role="main"',
            'role="navigation"',
            'role="contentinfo"',
            'role="search"',
            '<main',
            '<header',
            '<nav',
            '<footer'
        ];

        $landmarkFound = false;
        foreach ($landmarks as $landmark) {
            if (strpos(strtolower($content), strtolower($landmark)) !== false) {
                $landmarkFound = true;
                break;
            }
        }

        $this->assertTrue($landmarkFound, 'Page should have ARIA landmarks');

        $response->assertOk();
    }

    #[Test]
    public function form_errors_are_accessible()
    {
        $response = $this->post('/register', [
            'name' => '',
            'email' => 'invalid-email',
            'password' => '123',
        ]);

        $content = $response->getContent();

        // Check for ARIA error attributes
        $errorAttributes = [
            'aria-invalid',
            'aria-describedby',
            'role="alert"',
            'aria-live'
        ];

        $hasErrorSupport = false;
        foreach ($errorAttributes as $attr) {
            if (strpos(strtolower($content), $attr) !== false) {
                $hasErrorSupport = true;
                break;
            }
        }

        $this->assertTrue($hasErrorSupport, 'Form errors should be accessible');
    }

    #[Test]
    public function tables_have_proper_headers()
    {
        $this->actingAs($this->customer, 'customer');

        $response = $this->get('/customer/my-orders');

        $content = $response->getContent();

        // Check for table header elements
        if (strpos($content, '<table') !== false) {
            $this->assertStringContainsString('<th', $content, 'Tables should have header cells');
            $this->assertStringContainsString('scope=', $content, 'Table headers should have scope attributes');
        }

        $response->assertOk();
    }

    #[Test]
    public function modals_are_accessible()
    {
        $response = $this->get('/products');

        $content = $response->getContent();

        // Check for modal accessibility attributes
        $modalAttributes = [
            'role="dialog"',
            'aria-modal',
            'aria-labelledby',
            'aria-describedby'
        ];

        // Only check if modals are present
        if (strpos(strtolower($content), 'modal') !== false) {
            $hasModalSupport = false;
            foreach ($modalAttributes as $attr) {
                if (strpos(strtolower($content), $attr) !== false) {
                    $hasModalSupport = true;
                    break;
                }
            }

            $this->assertTrue($hasModalSupport, 'Modals should have accessibility attributes');
        }

        $response->assertOk();
    }

    #[Test]
    public function page_language_is_declared()
    {
        $response = $this->get('/');

        $content = $response->getContent();

        // Check for lang attribute on html element
        $this->assertStringContainsString('lang=', $content, 'HTML element should have lang attribute');

        $response->assertOk();
    }

    #[Test]
    public function page_title_is_descriptive()
    {
        $response = $this->get('/');

        $content = $response->getContent();

        // Check for title tag
        preg_match('/<title[^>]*>(.*?)<\/title>/is', $content, $titleMatch);

        if (!empty($titleMatch[1])) {
            $title = trim(strip_tags($titleMatch[1]));
            $this->assertNotEmpty($title, 'Page should have a non-empty title');
            $this->assertGreaterThan(10, strlen($title), 'Page title should be descriptive');
        }

        $response->assertOk();
    }

    #[Test]
    public function dropdown_menus_are_accessible()
    {
        $response = $this->get('/');

        $content = $response->getContent();

        // Check for dropdown accessibility
        if (strpos(strtolower($content), 'dropdown') !== false) {
            $dropdownAttributes = [
                'aria-expanded',
                'aria-haspopup',
                'role="menu"',
                'role="menuitem"'
            ];

            $hasDropdownSupport = false;
            foreach ($dropdownAttributes as $attr) {
                if (strpos(strtolower($content), $attr) !== false) {
                    $hasDropdownSupport = true;
                    break;
                }
            }

            $this->assertTrue($hasDropdownSupport, 'Dropdown menus should be accessible');
        }

        $response->assertOk();
    }

    #[Test]
    public function loading_states_are_announced()
    {
        $response = $this->get('/products');

        $content = $response->getContent();

        // Check for loading state accessibility
        $loadingAttributes = [
            'aria-live',
            'aria-busy',
            'role="status"',
            'aria-label'
        ];

        if (strpos(strtolower($content), 'loading') !== false) {
            $hasLoadingSupport = false;
            foreach ($loadingAttributes as $attr) {
                if (strpos(strtolower($content), $attr) !== false) {
                    $hasLoadingSupport = true;
                    break;
                }
            }

            $this->assertTrue($hasLoadingSupport, 'Loading states should be announced to screen readers');
        }

        $response->assertOk();
    }

    #[Test]
    public function error_messages_are_announced()
    {
        $response = $this->post('/contact', [
            'name' => '',
            'email' => 'invalid',
            'message' => '',
        ]);

        $content = $response->getContent();

        // Check for error announcement attributes
        $errorAttributes = [
            'role="alert"',
            'aria-live="assertive"',
            'aria-atomic="true"'
        ];

        if (strpos(strtolower($content), 'error') !== false) {
            $hasErrorAnnouncement = false;
            foreach ($errorAttributes as $attr) {
                if (strpos(strtolower($content), $attr) !== false) {
                    $hasErrorAnnouncement = true;
                    break;
                }
            }

            $this->assertTrue($hasErrorAnnouncement, 'Error messages should be announced to screen readers');
        }
    }

    #[Test]
    public function interactive_elements_have_sufficient_size()
    {
        $response = $this->get('/products');

        $content = $response->getContent();

        // Check for minimum touch target sizes (44px recommendation)
        $sizeClasses = [
            'min-h-11',   // 44px minimum height
            'min-w-11',   // 44px minimum width
            'h-11',       // 44px height
            'w-11',       // 44px width
            'p-3',        // Adequate padding
            'py-3',       // Vertical padding
            'px-3'        // Horizontal padding
        ];

        $hasSufficientSizing = false;
        foreach ($sizeClasses as $sizeClass) {
            if (strpos($content, $sizeClass) !== false) {
                $hasSufficientSizing = true;
                break;
            }
        }

        $this->assertTrue($hasSufficientSizing, 'Interactive elements should have sufficient size');

        $response->assertOk();
    }

    #[Test]
    public function content_is_readable_without_css()
    {
        $response = $this->get('/');

        $content = $response->getContent();

        // Remove all style tags and check if content is still meaningful
        $contentWithoutCSS = preg_replace('/<style[^>]*>.*?<\/style>/is', '', $content);
        $contentWithoutCSS = preg_replace('/<link[^>]*rel=["\']stylesheet["\'][^>]*>/i', '', $contentWithoutCSS);

        // Check that essential content is still present
        $textContent = strip_tags($contentWithoutCSS);
        $this->assertGreaterThan(100, strlen(trim($textContent)),
            'Page should have meaningful content without CSS');

        $response->assertOk();
    }

    #[Test]
    public function navigation_has_current_page_indicator()
    {
        $response = $this->get('/products');

        $content = $response->getContent();

        // Check for current page indicators
        $currentIndicators = [
            'aria-current="page"',
            'current',
            'active'
        ];

        $hasCurrentIndicator = false;
        foreach ($currentIndicators as $indicator) {
            if (strpos(strtolower($content), strtolower($indicator)) !== false) {
                $hasCurrentIndicator = true;
                break;
            }
        }

        $this->assertTrue($hasCurrentIndicator, 'Navigation should indicate current page');

        $response->assertOk();
    }

    #[Test]
    public function form_instructions_are_clear()
    {
        $response = $this->get('/register');

        $content = $response->getContent();

        // Check for form instructions and help text
        $instructionIndicators = [
            'aria-describedby',
            'help-text',
            'instruction',
            'hint',
            'required'
        ];

        $hasInstructions = false;
        foreach ($instructionIndicators as $indicator) {
            if (strpos(strtolower($content), $indicator) !== false) {
                $hasInstructions = true;
                break;
            }
        }

        $this->assertTrue($hasInstructions, 'Forms should have clear instructions');

        $response->assertOk();
    }

    #[Test]
    public function videos_have_captions_or_transcripts()
    {
        $response = $this->get('/');

        $content = $response->getContent();

        // Check for video elements
        if (strpos($content, '<video') !== false) {
            $videoAccessibility = [
                '<track',
                'captions',
                'subtitles',
                'transcript'
            ];

            $hasVideoAccessibility = false;
            foreach ($videoAccessibility as $feature) {
                if (strpos(strtolower($content), $feature) !== false) {
                    $hasVideoAccessibility = true;
                    break;
                }
            }

            $this->assertTrue($hasVideoAccessibility, 'Videos should have captions or transcripts');
        }

        $response->assertOk();
    }

    #[Test]
    public function page_structure_is_logical()
    {
        $response = $this->get('/');

        $content = $response->getContent();

        // Check for logical page structure
        $structureElements = [
            '<header',
            '<main',
            '<footer',
            '<nav'
        ];

        $foundElements = 0;
        foreach ($structureElements as $element) {
            if (strpos(strtolower($content), $element) !== false) {
                $foundElements++;
            }
        }

        $this->assertGreaterThanOrEqual(2, $foundElements,
            'Page should have logical structural elements');

        $response->assertOk();
    }
}
