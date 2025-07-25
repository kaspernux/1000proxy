/**
 * UX Enhancement Components
 *
 * Comprehensive user experience improvements including error handling,
 * contextual help, guided onboarding, empty states, and micro-interactions.
 * Designed to improve user satisfaction and reduce confusion.
 *
 * @version 1.0.0
 * @author ProxyAdmin System
 */

class UXEnhancementManager
{
    constructor ()
    {
        this.tooltips = new Map();
        this.onboardingSteps = [];
        this.currentOnboardingStep = 0;
        this.errorMessageTemplates = new Map();
        this.emptyStateTemplates = new Map();
        this.microInteractions = new Map();

        this.init();
    }

    /**
     * Initialize UX enhancements
     */
    init ()
    {
        this.setupErrorMessageSystem();
        this.setupTooltipSystem();
        this.setupEmptyStates();
        this.setupMicroInteractions();
        this.setupOnboardingSystem();
        this.setupProgressIndicators();
        this.setupContextualHelp();

        // Initialize when DOM is ready
        if ( document.readyState === 'loading' )
        {
            document.addEventListener( 'DOMContentLoaded', () => this.onDOMReady() );
        } else
        {
            this.onDOMReady();
        }
    }

    /**
     * Called when DOM is ready
     */
    onDOMReady ()
    {
        this.scanAndEnhanceElements();
        this.initializeTooltips();
        this.initializeEmptyStates();
        this.startOnboardingIfNeeded();
    }

    /**
     * Setup enhanced error message system
     */
    setupErrorMessageSystem ()
    {
        // Enhanced error message templates
        this.errorMessageTemplates.set( 'validation', {
            email: {
                message: 'Please enter a valid email address',
                suggestion: 'Example: user@example.com',
                action: 'Check for typos and ensure the format is correct'
            },
            password: {
                message: 'Password does not meet requirements',
                suggestion: 'Use 8+ characters with letters, numbers, and symbols',
                action: 'Update your password to meet security standards'
            },
            required: {
                message: 'This field is required',
                suggestion: 'Please provide this information to continue',
                action: 'Fill in the required field'
            },
            minLength: {
                message: 'Input is too short',
                suggestion: 'Minimum {min} characters required',
                action: 'Add more characters to meet the requirement'
            },
            maxLength: {
                message: 'Input is too long',
                suggestion: 'Maximum {max} characters allowed',
                action: 'Reduce the length of your input'
            }
        } );

        this.errorMessageTemplates.set( 'network', {
            timeout: {
                message: 'Request timed out',
                suggestion: 'The server took too long to respond',
                action: 'Check your connection and try again'
            },
            offline: {
                message: 'No internet connection',
                suggestion: 'You appear to be offline',
                action: 'Check your network settings and reconnect'
            },
            server_error: {
                message: 'Server error occurred',
                suggestion: 'Something went wrong on our end',
                action: 'Please try again in a few moments'
            },
            not_found: {
                message: 'Resource not found',
                suggestion: 'The requested item could not be located',
                action: 'Verify the URL or contact support'
            }
        } );

        this.errorMessageTemplates.set( 'permission', {
            unauthorized: {
                message: 'Access denied',
                suggestion: 'You do not have permission for this action',
                action: 'Log in with an authorized account or contact your administrator'
            },
            expired_session: {
                message: 'Session expired',
                suggestion: 'Your login session has timed out',
                action: 'Please log in again to continue'
            }
        } );
    }

    /**
     * Setup tooltip system
     */
    setupTooltipSystem ()
    {
        // Tooltip themes and configurations
        this.tooltipConfig = {
            default: {
                delay: 500,
                duration: 300,
                placement: 'top',
                theme: 'dark'
            },
            help: {
                delay: 200,
                duration: 300,
                placement: 'right',
                theme: 'light',
                interactive: true
            },
            warning: {
                delay: 100,
                duration: 500,
                placement: 'top',
                theme: 'warning'
            },
            error: {
                delay: 0,
                duration: 500,
                placement: 'top',
                theme: 'error'
            }
        };

        // Contextual tooltip content
        this.tooltipContent = new Map( [
            [ 'server-status', 'Current operational status of the server. Green indicates healthy, yellow indicates warnings, red indicates critical issues.' ],
            [ 'proxy-config', 'Configure proxy settings including protocol, port, and authentication methods. Changes take effect immediately.' ],
            [ 'user-permissions', 'Control what actions this user can perform. Admin users have full access, while regular users have limited permissions.' ],
            [ 'billing-cycle', 'Your current billing period and renewal date. Auto-renewal can be managed in account settings.' ],
            [ 'api-key', 'Secure token for API access. Keep this private and regenerate if compromised.' ],
            [ 'backup-schedule', 'Automated backup frequency. Daily backups are recommended for production environments.' ],
            [ 'ssl-certificate', 'Security certificate status. Valid certificates ensure encrypted connections.' ],
            [ 'load-balancer', 'Distributes traffic across multiple servers for better performance and reliability.' ],
            [ 'firewall-rules', 'Security rules that control network access. Review regularly for optimal protection.' ],
            [ 'monitoring-alerts', 'Configure notifications for system events. Set appropriate thresholds to avoid spam.' ]
        ] );
    }

    /**
     * Setup empty state templates
     */
    setupEmptyStates ()
    {
        this.emptyStateTemplates.set( 'servers', {
            icon: 'server',
            title: 'No servers configured',
            description: 'Get started by adding your first server to begin managing your proxy infrastructure.',
            primaryAction: {
                text: 'Add Server',
                action: 'showAddServerModal',
                variant: 'primary'
            },
            secondaryAction: {
                text: 'Import Configuration',
                action: 'showImportModal',
                variant: 'secondary'
            }
        } );

        this.emptyStateTemplates.set( 'users', {
            icon: 'users',
            title: 'No users found',
            description: 'Start building your team by inviting users and assigning appropriate permissions.',
            primaryAction: {
                text: 'Invite User',
                action: 'showInviteUserModal',
                variant: 'primary'
            },
            secondaryAction: {
                text: 'Bulk Import',
                action: 'showBulkImportModal',
                variant: 'secondary'
            }
        } );

        this.emptyStateTemplates.set( 'orders', {
            icon: 'shopping-cart',
            title: 'No orders yet',
            description: 'Your order history will appear here once customers start making purchases.',
            primaryAction: {
                text: 'View Products',
                action: 'navigateToProducts',
                variant: 'primary'
            },
            helpLink: {
                text: 'Learn about order management',
                url: '/docs/orders'
            }
        } );

        this.emptyStateTemplates.set( 'logs', {
            icon: 'file-text',
            title: 'No log entries',
            description: 'System logs and activity will be displayed here as events occur.',
            helpText: 'Logs are automatically generated when users perform actions or system events occur.',
            refreshAction: {
                text: 'Refresh',
                action: 'refreshLogs',
                variant: 'secondary'
            }
        } );

        this.emptyStateTemplates.set( 'search', {
            icon: 'search',
            title: 'No results found',
            description: 'Try adjusting your search terms or filters to find what you\'re looking for.',
            suggestions: [
                'Check your spelling',
                'Use fewer or different keywords',
                'Try broader search terms',
                'Clear filters to see all results'
            ],
            secondaryAction: {
                text: 'Clear Search',
                action: 'clearSearch',
                variant: 'secondary'
            }
        } );
    }

    /**
     * Setup micro-interactions
     */
    setupMicroInteractions ()
    {
        this.microInteractions.set( 'button-hover', {
            trigger: 'hover',
            animation: 'subtle-lift',
            duration: 200,
            easing: 'ease-out'
        } );

        this.microInteractions.set( 'card-hover', {
            trigger: 'hover',
            animation: 'shadow-lift',
            duration: 300,
            easing: 'ease-out'
        } );

        this.microInteractions.set( 'form-focus', {
            trigger: 'focus',
            animation: 'border-glow',
            duration: 200,
            easing: 'ease-in-out'
        } );

        this.microInteractions.set( 'success-feedback', {
            trigger: 'success',
            animation: 'check-bounce',
            duration: 600,
            easing: 'ease-out'
        } );

        this.microInteractions.set( 'error-shake', {
            trigger: 'error',
            animation: 'shake',
            duration: 400,
            easing: 'ease-in-out'
        } );

        this.microInteractions.set( 'loading-pulse', {
            trigger: 'loading',
            animation: 'pulse',
            duration: 1000,
            easing: 'ease-in-out',
            infinite: true
        } );
    }

    /**
     * Setup onboarding system
     */
    setupOnboardingSystem ()
    {
        this.onboardingSteps = [
            {
                target: '[data-tour="dashboard"]',
                title: 'Welcome to ProxyAdmin',
                content: 'This is your dashboard where you can monitor all your proxy servers and get an overview of your system status.',
                placement: 'bottom',
                actions: {
                    next: 'Got it, what\'s next?'
                }
            },
            {
                target: '[data-tour="servers"]',
                title: 'Server Management',
                content: 'Here you can add, configure, and monitor all your proxy servers. Click on any server to view detailed information.',
                placement: 'right',
                actions: {
                    next: 'Show me more',
                    skip: 'Skip tour'
                }
            },
            {
                target: '[data-tour="users"]',
                title: 'User Management',
                content: 'Manage your team members and their permissions. You can invite new users and control what they can access.',
                placement: 'right',
                actions: {
                    next: 'Continue',
                    skip: 'Skip tour'
                }
            },
            {
                target: '[data-tour="settings"]',
                title: 'System Settings',
                content: 'Configure your system preferences, security settings, and integration options from here.',
                placement: 'left',
                actions: {
                    next: 'Almost done',
                    skip: 'Skip tour'
                }
            },
            {
                target: '[data-tour="help"]',
                title: 'Need Help?',
                content: 'Access documentation, tutorials, and support resources anytime. We\'re here to help you succeed!',
                placement: 'bottom',
                actions: {
                    finish: 'Finish tour'
                }
            }
        ];
    }

    /**
     * Show enhanced error message
     */
    showEnhancedError ( element, errorType, category = 'validation', context = {} )
    {
        const template = this.errorMessageTemplates.get( category )?.[ errorType ];
        if ( !template )
        {
            console.warn( `Error template not found: ${ category }.${ errorType }` );
            return;
        }

        // Process template placeholders
        let message = template.message;
        let suggestion = template.suggestion;
        let action = template.action;

        Object.keys( context ).forEach( key =>
        {
            const placeholder = `{${ key }}`;
            message = message.replace( placeholder, context[ key ] );
            suggestion = suggestion.replace( placeholder, context[ key ] );
            action = action.replace( placeholder, context[ key ] );
        } );

        // Create enhanced error element
        const errorElement = document.createElement( 'div' );
        errorElement.className = 'enhanced-error';
        errorElement.innerHTML = `
            <div class="enhanced-error-content">
                <div class="enhanced-error-icon">
                    <svg width="16" height="16" viewBox="0 0 16 16" fill="currentColor">
                        <path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14zm0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16z"/>
                        <path d="M7.002 11a1 1 0 1 1 2 0 1 1 0 0 1-2 0zM7.1 4.995a.905.905 0 1 1 1.8 0l-.35 3.507a.552.552 0 0 1-1.1 0L7.1 4.995z"/>
                    </svg>
                </div>
                <div class="enhanced-error-text">
                    <div class="enhanced-error-message">${ message }</div>
                    <div class="enhanced-error-suggestion">${ suggestion }</div>
                    <div class="enhanced-error-action">${ action }</div>
                </div>
            </div>
        `;

        // Remove existing error messages
        const existingError = element.parentNode.querySelector( '.enhanced-error' );
        if ( existingError )
        {
            existingError.remove();
        }

        // Insert error after element
        element.parentNode.insertBefore( errorElement, element.nextSibling );

        // Animate in
        errorElement.style.opacity = '0';
        errorElement.style.transform = 'translateY(-10px)';

        requestAnimationFrame( () =>
        {
            errorElement.style.transition = 'opacity 0.3s ease, transform 0.3s ease';
            errorElement.style.opacity = '1';
            errorElement.style.transform = 'translateY(0)';
        } );

        // Auto-remove after delay
        setTimeout( () =>
        {
            if ( errorElement.parentNode )
            {
                this.removeEnhancedError( errorElement );
            }
        }, 5000 );
    }

    /**
     * Remove enhanced error message
     */
    removeEnhancedError ( errorElement )
    {
        errorElement.style.transition = 'opacity 0.3s ease, transform 0.3s ease';
        errorElement.style.opacity = '0';
        errorElement.style.transform = 'translateY(-10px)';

        setTimeout( () =>
        {
            if ( errorElement.parentNode )
            {
                errorElement.parentNode.removeChild( errorElement );
            }
        }, 300 );
    }

    /**
     * Create and show tooltip
     */
    createTooltip ( element, content, config = {} )
    {
        const finalConfig = { ...this.tooltipConfig.default, ...config };

        const tooltipId = `tooltip-${ Date.now() }`;
        const tooltip = document.createElement( 'div' );
        tooltip.id = tooltipId;
        tooltip.className = `tooltip tooltip-${ finalConfig.theme }`;
        tooltip.innerHTML = `
            <div class="tooltip-arrow"></div>
            <div class="tooltip-content">${ content }</div>
        `;

        document.body.appendChild( tooltip );
        this.tooltips.set( element, tooltip );

        // Position tooltip
        this.positionTooltip( tooltip, element, finalConfig.placement );

        // Animate in
        tooltip.style.opacity = '0';
        tooltip.style.transform = 'scale(0.8) translateY(10px)';

        setTimeout( () =>
        {
            tooltip.style.transition = `opacity ${ finalConfig.duration }ms ease, transform ${ finalConfig.duration }ms ease`;
            tooltip.style.opacity = '1';
            tooltip.style.transform = 'scale(1) translateY(0)';
        }, finalConfig.delay );

        return tooltip;
    }

    /**
     * Position tooltip relative to element
     */
    positionTooltip ( tooltip, element, placement )
    {
        const elementRect = element.getBoundingClientRect();
        const tooltipRect = tooltip.getBoundingClientRect();
        const margin = 8;

        let top, left;

        switch ( placement )
        {
            case 'top':
                top = elementRect.top - tooltipRect.height - margin;
                left = elementRect.left + ( elementRect.width - tooltipRect.width ) / 2;
                break;
            case 'bottom':
                top = elementRect.bottom + margin;
                left = elementRect.left + ( elementRect.width - tooltipRect.width ) / 2;
                break;
            case 'left':
                top = elementRect.top + ( elementRect.height - tooltipRect.height ) / 2;
                left = elementRect.left - tooltipRect.width - margin;
                break;
            case 'right':
                top = elementRect.top + ( elementRect.height - tooltipRect.height ) / 2;
                left = elementRect.right + margin;
                break;
            default:
                top = elementRect.top - tooltipRect.height - margin;
                left = elementRect.left + ( elementRect.width - tooltipRect.width ) / 2;
        }

        // Ensure tooltip stays within viewport
        const viewportWidth = window.innerWidth;
        const viewportHeight = window.innerHeight;

        if ( left < 0 ) left = margin;
        if ( left + tooltipRect.width > viewportWidth )
        {
            left = viewportWidth - tooltipRect.width - margin;
        }
        if ( top < 0 ) top = margin;
        if ( top + tooltipRect.height > viewportHeight )
        {
            top = viewportHeight - tooltipRect.height - margin;
        }

        tooltip.style.position = 'fixed';
        tooltip.style.top = `${ top + window.scrollY }px`;
        tooltip.style.left = `${ left + window.scrollX }px`;
        tooltip.style.zIndex = '10000';
    }

    /**
     * Hide tooltip
     */
    hideTooltip ( element )
    {
        const tooltip = this.tooltips.get( element );
        if ( tooltip )
        {
            tooltip.style.transition = 'opacity 0.2s ease, transform 0.2s ease';
            tooltip.style.opacity = '0';
            tooltip.style.transform = 'scale(0.8) translateY(10px)';

            setTimeout( () =>
            {
                if ( tooltip.parentNode )
                {
                    tooltip.parentNode.removeChild( tooltip );
                }
                this.tooltips.delete( element );
            }, 200 );
        }
    }

    /**
     * Create empty state
     */
    createEmptyState ( container, type, customData = {} )
    {
        const template = this.emptyStateTemplates.get( type );
        if ( !template )
        {
            console.warn( `Empty state template not found: ${ type }` );
            return;
        }

        const data = { ...template, ...customData };

        const emptyState = document.createElement( 'div' );
        emptyState.className = 'empty-state';

        let actionsHtml = '';
        if ( data.primaryAction )
        {
            actionsHtml += `<button class="btn btn-${ data.primaryAction.variant } btn-lg" onclick="${ data.primaryAction.action }()">${ data.primaryAction.text }</button>`;
        }
        if ( data.secondaryAction )
        {
            actionsHtml += `<button class="btn btn-${ data.secondaryAction.variant } btn-lg" onclick="${ data.secondaryAction.action }()">${ data.secondaryAction.text }</button>`;
        }
        if ( data.refreshAction )
        {
            actionsHtml += `<button class="btn btn-${ data.refreshAction.variant }" onclick="${ data.refreshAction.action }()">${ data.refreshAction.text }</button>`;
        }

        let suggestionsHtml = '';
        if ( data.suggestions )
        {
            suggestionsHtml = `
                <div class="empty-state-suggestions">
                    <p>Try:</p>
                    <ul>
                        ${ data.suggestions.map( suggestion => `<li>${ suggestion }</li>` ).join( '' ) }
                    </ul>
                </div>
            `;
        }

        let helpHtml = '';
        if ( data.helpLink )
        {
            helpHtml = `<a href="${ data.helpLink.url }" class="empty-state-help-link">${ data.helpLink.text }</a>`;
        }
        if ( data.helpText )
        {
            helpHtml = `<p class="empty-state-help-text">${ data.helpText }</p>`;
        }

        emptyState.innerHTML = `
            <div class="empty-state-content">
                <div class="empty-state-icon">
                    <i class="icon-${ data.icon } icon-4x"></i>
                </div>
                <h3 class="empty-state-title">${ data.title }</h3>
                <p class="empty-state-description">${ data.description }</p>
                ${ suggestionsHtml }
                ${ actionsHtml ? `<div class="empty-state-actions">${ actionsHtml }</div>` : '' }
                ${ helpHtml }
            </div>
        `;

        container.innerHTML = '';
        container.appendChild( emptyState );

        // Animate in
        emptyState.style.opacity = '0';
        emptyState.style.transform = 'translateY(20px)';

        requestAnimationFrame( () =>
        {
            emptyState.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
            emptyState.style.opacity = '1';
            emptyState.style.transform = 'translateY(0)';
        } );
    }

    /**
     * Apply micro-interaction
     */
    applyMicroInteraction ( element, interactionType )
    {
        const interaction = this.microInteractions.get( interactionType );
        if ( !interaction ) return;

        element.classList.add( `micro-${ interaction.animation }` );

        if ( !interaction.infinite )
        {
            setTimeout( () =>
            {
                element.classList.remove( `micro-${ interaction.animation }` );
            }, interaction.duration );
        }
    }

    /**
     * Start onboarding tour
     */
    startOnboarding ()
    {
        if ( this.onboardingSteps.length === 0 ) return;

        this.currentOnboardingStep = 0;
        this.showOnboardingStep();
    }

    /**
     * Show current onboarding step
     */
    showOnboardingStep ()
    {
        const step = this.onboardingSteps[ this.currentOnboardingStep ];
        if ( !step ) return;

        const target = document.querySelector( step.target );
        if ( !target )
        {
            this.nextOnboardingStep();
            return;
        }

        // Create onboarding overlay
        this.createOnboardingOverlay( target, step );
    }

    /**
     * Create onboarding overlay
     */
    createOnboardingOverlay ( target, step )
    {
        // Remove existing overlay
        const existingOverlay = document.querySelector( '.onboarding-overlay' );
        if ( existingOverlay )
        {
            existingOverlay.remove();
        }

        const overlay = document.createElement( 'div' );
        overlay.className = 'onboarding-overlay';

        const targetRect = target.getBoundingClientRect();

        overlay.innerHTML = `
            <div class="onboarding-backdrop"></div>
            <div class="onboarding-highlight" style="
                top: ${ targetRect.top + window.scrollY - 8 }px;
                left: ${ targetRect.left + window.scrollX - 8 }px;
                width: ${ targetRect.width + 16 }px;
                height: ${ targetRect.height + 16 }px;
            "></div>
            <div class="onboarding-popup" style="
                top: ${ targetRect.bottom + window.scrollY + 16 }px;
                left: ${ targetRect.left + window.scrollX }px;
            ">
                <div class="onboarding-content">
                    <h4 class="onboarding-title">${ step.title }</h4>
                    <p class="onboarding-text">${ step.content }</p>
                    <div class="onboarding-progress">
                        <span>${ this.currentOnboardingStep + 1 } of ${ this.onboardingSteps.length }</span>
                    </div>
                    <div class="onboarding-actions">
                        ${ step.actions.skip ? `<button class="btn btn-ghost btn-sm" onclick="uxManager.skipOnboarding()">${ step.actions.skip }</button>` : '' }
                        ${ step.actions.next ? `<button class="btn btn-primary btn-sm" onclick="uxManager.nextOnboardingStep()">${ step.actions.next }</button>` : '' }
                        ${ step.actions.finish ? `<button class="btn btn-primary btn-sm" onclick="uxManager.finishOnboarding()">${ step.actions.finish }</button>` : '' }
                    </div>
                </div>
                <div class="onboarding-arrow"></div>
            </div>
        `;

        document.body.appendChild( overlay );

        // Animate in
        overlay.style.opacity = '0';
        requestAnimationFrame( () =>
        {
            overlay.style.transition = 'opacity 0.3s ease';
            overlay.style.opacity = '1';
        } );
    }

    /**
     * Next onboarding step
     */
    nextOnboardingStep ()
    {
        this.currentOnboardingStep++;
        if ( this.currentOnboardingStep < this.onboardingSteps.length )
        {
            this.showOnboardingStep();
        } else
        {
            this.finishOnboarding();
        }
    }

    /**
     * Skip onboarding
     */
    skipOnboarding ()
    {
        this.finishOnboarding();
    }

    /**
     * Finish onboarding
     */
    finishOnboarding ()
    {
        const overlay = document.querySelector( '.onboarding-overlay' );
        if ( overlay )
        {
            overlay.style.transition = 'opacity 0.3s ease';
            overlay.style.opacity = '0';
            setTimeout( () =>
            {
                overlay.remove();
            }, 300 );
        }

        // Mark onboarding as completed
        localStorage.setItem( 'onboarding_completed', 'true' );
    }

    /**
     * Check if onboarding should start
     */
    startOnboardingIfNeeded ()
    {
        const isCompleted = localStorage.getItem( 'onboarding_completed' );
        const hasOnboardingElements = document.querySelector( '[data-tour]' );

        if ( !isCompleted && hasOnboardingElements )
        {
            // Delay to ensure page is fully loaded
            setTimeout( () =>
            {
                this.startOnboarding();
            }, 1000 );
        }
    }

    /**
     * Scan and enhance existing elements
     */
    scanAndEnhanceElements ()
    {
        // Enhance buttons with micro-interactions
        const buttons = document.querySelectorAll( 'button, .btn' );
        buttons.forEach( button =>
        {
            if ( !button.hasAttribute( 'data-micro-enhanced' ) )
            {
                button.addEventListener( 'mouseenter', () =>
                {
                    this.applyMicroInteraction( button, 'button-hover' );
                } );
                button.setAttribute( 'data-micro-enhanced', 'true' );
            }
        } );

        // Enhance cards with hover effects
        const cards = document.querySelectorAll( '.card' );
        cards.forEach( card =>
        {
            if ( !card.hasAttribute( 'data-micro-enhanced' ) )
            {
                card.addEventListener( 'mouseenter', () =>
                {
                    this.applyMicroInteraction( card, 'card-hover' );
                } );
                card.setAttribute( 'data-micro-enhanced', 'true' );
            }
        } );

        // Enhance form inputs
        const inputs = document.querySelectorAll( 'input, textarea, select' );
        inputs.forEach( input =>
        {
            if ( !input.hasAttribute( 'data-micro-enhanced' ) )
            {
                input.addEventListener( 'focus', () =>
                {
                    this.applyMicroInteraction( input, 'form-focus' );
                } );
                input.setAttribute( 'data-micro-enhanced', 'true' );
            }
        } );
    }

    /**
     * Initialize tooltips for existing elements
     */
    initializeTooltips ()
    {
        // Auto-initialize tooltips with data attributes
        const tooltipElements = document.querySelectorAll( '[data-tooltip]' );
        tooltipElements.forEach( element =>
        {
            const content = element.getAttribute( 'data-tooltip' );
            const type = element.getAttribute( 'data-tooltip-type' ) || 'default';
            const placement = element.getAttribute( 'data-tooltip-placement' ) || 'top';

            element.addEventListener( 'mouseenter', () =>
            {
                this.createTooltip( element, content, {
                    ...this.tooltipConfig[ type ],
                    placement
                } );
            } );

            element.addEventListener( 'mouseleave', () =>
            {
                this.hideTooltip( element );
            } );
        } );

        // Initialize contextual help tooltips
        this.tooltipContent.forEach( ( content, key ) =>
        {
            const elements = document.querySelectorAll( `[data-help="${ key }"]` );
            elements.forEach( element =>
            {
                element.addEventListener( 'mouseenter', () =>
                {
                    this.createTooltip( element, content, this.tooltipConfig.help );
                } );

                element.addEventListener( 'mouseleave', () =>
                {
                    this.hideTooltip( element );
                } );
            } );
        } );
    }

    /**
     * Initialize empty states for containers
     */
    initializeEmptyStates ()
    {
        const emptyContainers = document.querySelectorAll( '[data-empty-state]' );
        emptyContainers.forEach( container =>
        {
            if ( container.children.length === 0 || container.textContent.trim() === '' )
            {
                const type = container.getAttribute( 'data-empty-state' );
                this.createEmptyState( container, type );
            }
        } );
    }

    /**
     * Setup progress indicators
     */
    setupProgressIndicators ()
    {
        // Create loading state manager
        this.loadingStates = new Map();
    }

    /**
     * Show loading state
     */
    showLoadingState ( element, message = 'Loading...' )
    {
        const loadingId = `loading-${ Date.now() }`;
        const loading = document.createElement( 'div' );
        loading.className = 'loading-overlay';
        loading.innerHTML = `
            <div class="loading-content">
                <div class="loading-spinner"></div>
                <div class="loading-message">${ message }</div>
            </div>
        `;

        element.style.position = 'relative';
        element.appendChild( loading );
        this.loadingStates.set( element, loading );

        this.applyMicroInteraction( loading, 'loading-pulse' );
    }

    /**
     * Hide loading state
     */
    hideLoadingState ( element )
    {
        const loading = this.loadingStates.get( element );
        if ( loading )
        {
            loading.style.transition = 'opacity 0.3s ease';
            loading.style.opacity = '0';
            setTimeout( () =>
            {
                if ( loading.parentNode )
                {
                    loading.parentNode.removeChild( loading );
                }
                this.loadingStates.delete( element );
            }, 300 );
        }
    }

    /**
     * Setup contextual help system
     */
    setupContextualHelp ()
    {
        // Help system is already integrated with tooltips
        // Additional contextual help features can be added here
    }

    /**
     * Public API methods
     */

    /**
     * Show success feedback
     */
    showSuccessFeedback ( element, message = 'Success!' )
    {
        this.applyMicroInteraction( element, 'success-feedback' );

        const feedback = document.createElement( 'div' );
        feedback.className = 'success-feedback';
        feedback.textContent = message;

        element.appendChild( feedback );

        setTimeout( () =>
        {
            feedback.remove();
        }, 2000 );
    }

    /**
     * Show error feedback
     */
    showErrorFeedback ( element, message = 'Error occurred' )
    {
        this.applyMicroInteraction( element, 'error-shake' );
        this.showEnhancedError( element, 'server_error', 'network', { message } );
    }

    /**
     * Create custom tooltip
     */
    showTooltip ( element, content, config = {} )
    {
        this.createTooltip( element, content, config );
    }

    /**
     * Create custom empty state
     */
    showEmptyState ( container, type, data = {} )
    {
        this.createEmptyState( container, type, data );
    }
}

// Initialize UX enhancement manager
if ( typeof window !== 'undefined' )
{
    window.UXEnhancementManager = UXEnhancementManager;

    // Auto-initialize unless explicitly disabled
    if ( !window.disableUXEnhancements )
    {
        window.uxManager = new UXEnhancementManager();
    }
}

export default UXEnhancementManager;
