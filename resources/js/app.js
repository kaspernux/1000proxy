import 'preline';

// Import component libraries
import './components/interactive-data-tables.js';
import './components/interactive-data-table.js';
import './components/advanced-data-tables.js';
import './components/color-theme-manager.js';
import './components/theme-switcher.js';
import './components/accessibility-manager.js';
import './services/data-tables-service.js';


// Import XUI Integration Interface
import './components/xui-integration.js';

// Import Telegram Bot Integration
import './components/telegram-integration.js';

document.addEventListener( 'livewire:navigated', () =>
{
    window.HSStaticMethods.autoInit();
} );

// Initialize Alpine.js



console.log( '✅ 1000proxy application initialized with Interactive Data Tables, Advanced Data Table Component, Advanced Color System, Enhanced Theme System, Accessibility Improvements, XUI Integration Interface, and Telegram Bot Integration UI' );
