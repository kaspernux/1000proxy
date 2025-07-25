/**
 * Test Setup File
 * Global test configuration and utilities
 */

// Import testing libraries
import '@testing-library/jest-dom';
import { TextEncoder, TextDecoder } from 'util';

// Setup Alpine.js for testing
global.Alpine = {
    data: jest.fn(),
    store: jest.fn(),
    start: jest.fn(),
    plugin: jest.fn()
};

// Setup global utilities
global.TextEncoder = TextEncoder;
global.TextDecoder = TextDecoder;

// Mock fetch API
global.fetch = jest.fn();

// Mock WebSocket
global.WebSocket = jest.fn().mockImplementation(() => ({
    send: jest.fn(),
    close: jest.fn(),
    addEventListener: jest.fn(),
    removeEventListener: jest.fn(),
    readyState: WebSocket.CONNECTING
}));

// Mock localStorage
const localStorageMock = {
    getItem: jest.fn(),
    setItem: jest.fn(),
    removeItem: jest.fn(),
    clear: jest.fn()
};
global.localStorage = localStorageMock;

// Mock sessionStorage
const sessionStorageMock = {
    getItem: jest.fn(),
    setItem: jest.fn(),
    removeItem: jest.fn(),
    clear: jest.fn()
};
global.sessionStorage = sessionStorageMock;

// Mock window.location
delete window.location;
window.location = {
    href: 'http://localhost:8000',
    pathname: '/',
    search: '',
    hash: '',
    reload: jest.fn(),
    assign: jest.fn(),
    replace: jest.fn()
};

// Mock window.scrollTo
window.scrollTo = jest.fn();

// Mock window.matchMedia
window.matchMedia = jest.fn().mockImplementation(query => ({
    matches: false,
    media: query,
    onchange: null,
    addListener: jest.fn(),
    removeListener: jest.fn(),
    addEventListener: jest.fn(),
    removeEventListener: jest.fn(),
    dispatchEvent: jest.fn()
}));

// Mock intersection observer
global.IntersectionObserver = jest.fn().mockImplementation(() => ({
    observe: jest.fn(),
    unobserve: jest.fn(),
    disconnect: jest.fn()
}));

// Mock resize observer
global.ResizeObserver = jest.fn().mockImplementation(() => ({
    observe: jest.fn(),
    unobserve: jest.fn(),
    disconnect: jest.fn()
}));

// Mock CSRF token
global.csrfToken = 'mock-csrf-token';

// Mock Laravel Echo
global.Echo = {
    channel: jest.fn().mockReturnThis(),
    private: jest.fn().mockReturnThis(),
    listen: jest.fn().mockReturnThis(),
    leave: jest.fn().mockReturnThis(),
    connector: {
        socket: {
            id: 'mock-socket-id'
        }
    }
};

// Test utilities
global.testUtils = {
    // Create mock Alpine component
    createMockAlpineComponent: (data = {}) => ({
        $data: data,
        $el: document.createElement('div'),
        $refs: {},
        $watch: jest.fn(),
        $nextTick: jest.fn(callback => callback()),
        $dispatch: jest.fn()
    }),
    
    // Create mock HTTP response
    createMockResponse: (data, status = 200) => ({
        ok: status >= 200 && status < 300,
        status,
        statusText: status === 200 ? 'OK' : 'Error',
        json: jest.fn().mockResolvedValue(data),
        text: jest.fn().mockResolvedValue(JSON.stringify(data)),
        headers: new Map([['content-type', 'application/json']])
    }),
    
    // Create mock WebSocket
    createMockWebSocket: () => {
        const mockWS = {
            send: jest.fn(),
            close: jest.fn(),
            addEventListener: jest.fn(),
            removeEventListener: jest.fn(),
            readyState: WebSocket.OPEN,
            url: 'ws://localhost:6001',
            protocol: '',
            extensions: '',
            bufferedAmount: 0,
            binaryType: 'blob',
            onopen: null,
            onclose: null,
            onmessage: null,
            onerror: null,
            // Simulate WebSocket events
            triggerOpen: function() {
                if (this.onopen) this.onopen(new Event('open'));
            },
            triggerMessage: function(data) {
                if (this.onmessage) {
                    this.onmessage(new MessageEvent('message', { data: JSON.stringify(data) }));
                }
            },
            triggerClose: function() {
                if (this.onclose) this.onclose(new CloseEvent('close'));
            },
            triggerError: function() {
                if (this.onerror) this.onerror(new Event('error'));
            }
        };
        return mockWS;
    },
    
    // Wait for Alpine.js to initialize
    waitForAlpine: async (timeout = 1000) => {
        return new Promise((resolve) => {
            const checkAlpine = () => {
                if (window.Alpine && window.Alpine.start) {
                    resolve();
                } else {
                    setTimeout(checkAlpine, 50);
                }
            };
            setTimeout(() => resolve(), timeout); // Fallback timeout
            checkAlpine();
        });
    },
    
    // Simulate user event
    simulateEvent: (element, eventType, options = {}) => {
        const event = new Event(eventType, {
            bubbles: true,
            cancelable: true,
            ...options
        });
        element.dispatchEvent(event);
        return event;
    },
    
    // Simulate keyboard event
    simulateKeyEvent: (element, key, eventType = 'keydown', options = {}) => {
        const event = new KeyboardEvent(eventType, {
            key,
            bubbles: true,
            cancelable: true,
            ...options
        });
        element.dispatchEvent(event);
        return event;
    },
    
    // Create test data
    createTestData: (count = 10, template = {}) => {
        return Array.from({ length: count }, (_, index) => ({
            id: index + 1,
            name: `Test Item ${index + 1}`,
            email: `test${index + 1}@example.com`,
            status: index % 2 === 0 ? 'active' : 'inactive',
            created_at: new Date(2023, 0, index + 1).toISOString(),
            ...template
        }));
    },
    
    // Mock API response
    mockApiResponse: (endpoint, response, status = 200) => {
        global.fetch.mockImplementation((url) => {
            if (url.includes(endpoint)) {
                return Promise.resolve(testUtils.createMockResponse(response, status));
            }
            return Promise.reject(new Error(`No mock for ${url}`));
        });
    },
    
    // Reset all mocks
    resetMocks: () => {
        jest.clearAllMocks();
        global.fetch.mockClear();
        global.localStorage.getItem.mockClear();
        global.localStorage.setItem.mockClear();
        global.sessionStorage.getItem.mockClear();
        global.sessionStorage.setItem.mockClear();
    }
};

// Setup global error handler for tests
window.addEventListener('error', (event) => {
    console.error('Global error in test:', event.error);
});

// Setup unhandled promise rejection handler
window.addEventListener('unhandledrejection', (event) => {
    console.error('Unhandled promise rejection in test:', event.reason);
});

// Clean up after each test
afterEach(() => {
    // Clean up DOM
    document.body.innerHTML = '';
    
    // Reset mocks
    testUtils.resetMocks();
    
    // Clear timers
    jest.clearAllTimers();
});

console.log('✅ Test setup loaded');
