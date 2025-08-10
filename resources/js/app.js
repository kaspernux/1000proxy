import 'preline';
import './echo';

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

// Simple toast handler for custom 'notify' events (used by export ready broadcast)
window.addEventListener( 'notify', ( e ) =>
{
    const message = e.detail?.message || 'Notification';
    const toast = document.createElement( 'div' );
    toast.className = 'fixed z-[9999] top-4 right-4 bg-emerald-600 text-white px-4 py-2 rounded shadow text-sm animate-fade-in';
    toast.textContent = message;
    document.body.appendChild( toast );
    setTimeout( () =>
    {
        toast.classList.add( 'opacity-0', 'transition-opacity', 'duration-500' );
        setTimeout( () => toast.remove(), 600 );
    }, 4000 );
} );
