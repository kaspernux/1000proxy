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
import Alpine from 'alpinejs';
window.Alpine = Alpine;
Alpine.start();

// Initialize Alpine.js components
document.addEventListener( 'alpine:init', () =>
{
    // Register data table components
    Alpine.data( 'dataTable', window.dataTable );
    Alpine.data( 'interactiveDataTable', window.interactiveDataTable );
    Alpine.data( 'editableDataTable', window.editableDataTable );
    Alpine.data( 'serversDataTable', window.serversDataTable );
    Alpine.data( 'serverClientsDataTable', window.serverClientsDataTable );
    Alpine.data( 'ordersDataTable', window.ordersDataTable );
    Alpine.data( 'usersDataTable', window.usersDataTable );

    // Register color theme manager
    Alpine.data( 'colorThemeManager', window.colorThemeManager );

    // Register theme switcher
    Alpine.data( 'themeSwitcher', window.themeSwitcher );

    // Register accessibility manager
    Alpine.data( 'accessibilityManager', window.accessibilityManager );

    // Register XUI Integration components
    Alpine.data( 'xuiIntegrationManager', window.xuiIntegrationManager );

    // Register Telegram Bot Integration components
    Alpine.data( 'telegramBotControlPanel', window.telegramBotControlPanel );
    Alpine.data( 'userTelegramLinking', window.userTelegramLinking );
    Alpine.data( 'telegramNotificationCenter', window.telegramNotificationCenter );
} );



console.log( '✅ 1000proxy application initialized with Interactive Data Tables, Advanced Data Table Component, Advanced Color System, Enhanced Theme System, Accessibility Improvements, XUI Integration Interface, and Telegram Bot Integration UI' );
