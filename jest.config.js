/**
 * Test Setup and Configuration for JavaScript Testing
 * Sets up testing environment for frontend components
 */

// Jest Configuration
module.exports = {
    // Testing environment
    testEnvironment: 'jsdom',

    // Setup files
    setupFilesAfterEnv: [ '<rootDir>/tests/javascript/setup.js' ],

    // Test file patterns
    testMatch: [
        '<rootDir>/tests/javascript/**/*.test.js',
        '<rootDir>/tests/javascript/**/*.spec.js'
    ],

    // Module paths
    moduleNameMapping: {
        '^@/(.*)$': '<rootDir>/resources/js/$1',
        '^@components/(.*)$': '<rootDir>/resources/js/components/$1',
        '^@services/(.*)$': '<rootDir>/resources/js/services/$1',
        '^@scss/(.*)$': '<rootDir>/resources/scss/$1'
    },

    // Coverage settings
    collectCoverageFrom: [
        'resources/js/**/*.js',
        '!resources/js/vendor/**',
        '!resources/js/plugins/**'
    ],

    coverageDirectory: 'tests/coverage',
    coverageReporters: [ 'text', 'lcov', 'html' ],

    // Transform files
    transform: {
        '^.+\\.js$': 'babel-jest'
    },

    // Module file extensions
    moduleFileExtensions: [ 'js', 'json' ],

    // Ignore patterns
    testPathIgnorePatterns: [
        '/node_modules/',
        '/vendor/',
        '/storage/',
        '/public/'
    ],

    // Global variables available in tests
    globals: {
        'process.env.NODE_ENV': 'test'
    },

    // Timeout for tests
    testTimeout: 10000,

    // Verbose output
    verbose: true,

    // Clear mocks between tests
    clearMocks: true,

    // Restore mocks after each test
    restoreMocks: true
};
