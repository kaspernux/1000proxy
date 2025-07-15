/**
 * Advanced Interaction Patterns for 1000proxy Platform
 * 
 * Features:
 * - Drag-and-drop functionality with touch support
 * - Comprehensive keyboard shortcuts system
 * - Gesture-based interactions for mobile
 * - Auto-save functionality with conflict resolution
 * - Undo/redo functionality with state management
 * - Contextual menus and actions
 * - Multi-modal interaction support
 * - Accessibility compliance
 */

class AdvancedInteractionPatterns
{
    constructor ()
    {
        this.dragDropInstances = new Map();
        this.keyboardShortcuts = new Map();
        this.gestureHandlers = new Map();
        this.autoSaveInstances = new Map();
        this.undoRedoStacks = new Map();
        this.contextMenus = new Map();
        this.eventListeners = new Map();
        this.isTouch = 'ontouchstart' in window || navigator.maxTouchPoints > 0;

        this.init();
    }

    init ()
    {
        this.setupKeyboardShortcuts();
        this.setupGestureDetection();
        this.initializeAutoSave();
        this.setupContextMenus();
        this.setupEventListeners();

        console.log( '🎯 Advanced Interaction Patterns initialized' );
    }

    // =============================================================================
    // Drag and Drop Functionality
    // =============================================================================

    createDragDropZone ( containerId, options = {} )
    {
        const defaultOptions = {
            dragSelector: '[draggable="true"]',
            dropSelector: '.drop-zone',
            dragClass: 'dragging',
            dragOverClass: 'drag-over',
            validDropClass: 'valid-drop',
            invalidDropClass: 'invalid-drop',
            ghostClass: 'drag-ghost',
            handleSelector: null,
            data: {},
            allowTouch: true,
            allowReorder: false,
            allowCopy: false,
            allowMove: true,
            validator: null,
            onDragStart: null,
            onDragEnd: null,
            onDrop: null,
            onDragOver: null,
            animation: 150
        };

        const config = { ...defaultOptions, ...options };
        const container = document.getElementById( containerId );

        if ( !container )
        {
            throw new Error( `Container "${ containerId }" not found` );
        }

        const dragDropInstance = {
            id: containerId,
            container,
            config,
            currentDragElement: null,
            currentDropTarget: null,
            ghostElement: null,
            originalPosition: null,
            isActive: false
        };

        this.setupDragEvents( dragDropInstance );
        this.dragDropInstances.set( containerId, dragDropInstance );

        return dragDropInstance;
    }

    setupDragEvents ( instance )
    {
        const { container, config } = instance;

        // Mouse events
        container.addEventListener( 'mousedown', ( e ) =>
        {
            this.handleDragStart( e, instance, 'mouse' );
        } );

        // Touch events for mobile
        if ( config.allowTouch && this.isTouch )
        {
            container.addEventListener( 'touchstart', ( e ) =>
            {
                this.handleDragStart( e, instance, 'touch' );
            }, { passive: false } );
        }

        // Keyboard support
        container.addEventListener( 'keydown', ( e ) =>
        {
            this.handleKeyboardDrag( e, instance );
        } );
    }

    handleDragStart ( e, instance, inputType )
    {
        const { config } = instance;
        const target = e.target.closest( config.dragSelector );

        if ( !target ) return;

        // Check if handle is specified and clicked
        if ( config.handleSelector )
        {
            const handle = e.target.closest( config.handleSelector );
            if ( !handle || !target.contains( handle ) ) return;
        }

        e.preventDefault();

        instance.currentDragElement = target;
        instance.originalPosition = this.getElementPosition( target );
        instance.isActive = true;

        // Add drag class
        target.classList.add( config.dragClass );

        // Create ghost element
        this.createGhostElement( target, instance );

        // Set up move and end event listeners
        if ( inputType === 'mouse' )
        {
            this.setupMouseDragEvents( instance );
        } else if ( inputType === 'touch' )
        {
            this.setupTouchDragEvents( instance );
        }

        // Call drag start callback
        if ( config.onDragStart )
        {
            config.onDragStart( target, instance );
        }

        this.emit( 'dragStart', { element: target, instance } );
    }

    createGhostElement ( element, instance )
    {
        const { config } = instance;
        const ghost = element.cloneNode( true );

        ghost.classList.add( config.ghostClass );
        ghost.style.position = 'fixed';
        ghost.style.pointerEvents = 'none';
        ghost.style.zIndex = '9999';
        ghost.style.opacity = '0.8';
        ghost.style.transform = 'rotate(3deg)';

        document.body.appendChild( ghost );
        instance.ghostElement = ghost;
    }

    setupMouseDragEvents ( instance )
    {
        const { config } = instance;

        const handleMouseMove = ( e ) =>
        {
            this.handleDragMove( e, instance, 'mouse' );
        };

        const handleMouseUp = ( e ) =>
        {
            this.handleDragEnd( e, instance );
            document.removeEventListener( 'mousemove', handleMouseMove );
            document.removeEventListener( 'mouseup', handleMouseUp );
        };

        document.addEventListener( 'mousemove', handleMouseMove );
        document.addEventListener( 'mouseup', handleMouseUp );
    }

    setupTouchDragEvents ( instance )
    {
        const { config } = instance;

        const handleTouchMove = ( e ) =>
        {
            e.preventDefault();
            this.handleDragMove( e, instance, 'touch' );
        };

        const handleTouchEnd = ( e ) =>
        {
            this.handleDragEnd( e, instance );
            document.removeEventListener( 'touchmove', handleTouchMove );
            document.removeEventListener( 'touchend', handleTouchEnd );
        };

        document.addEventListener( 'touchmove', handleTouchMove, { passive: false } );
        document.addEventListener( 'touchend', handleTouchEnd );
    }

    handleDragMove ( e, instance, inputType )
    {
        if ( !instance.isActive || !instance.ghostElement ) return;

        const clientX = inputType === 'touch' ? e.touches[ 0 ].clientX : e.clientX;
        const clientY = inputType === 'touch' ? e.touches[ 0 ].clientY : e.clientY;

        // Update ghost position
        instance.ghostElement.style.left = `${ clientX + 10 }px`;
        instance.ghostElement.style.top = `${ clientY + 10 }px`;

        // Find drop target
        const elementBelow = document.elementFromPoint( clientX, clientY );
        const dropTarget = elementBelow?.closest( instance.config.dropSelector );

        this.updateDropTarget( dropTarget, instance );

        if ( instance.config.onDragOver )
        {
            instance.config.onDragOver( dropTarget, instance );
        }
    }

    updateDropTarget ( newTarget, instance )
    {
        const { config } = instance;

        // Remove classes from previous target
        if ( instance.currentDropTarget )
        {
            instance.currentDropTarget.classList.remove(
                config.dragOverClass,
                config.validDropClass,
                config.invalidDropClass
            );
        }

        instance.currentDropTarget = newTarget;

        if ( newTarget )
        {
            newTarget.classList.add( config.dragOverClass );

            // Validate drop
            const isValid = config.validator ?
                config.validator( instance.currentDragElement, newTarget, instance ) : true;

            newTarget.classList.add( isValid ? config.validDropClass : config.invalidDropClass );
        }
    }

    handleDragEnd ( e, instance )
    {
        const { config } = instance;

        if ( !instance.isActive ) return;

        // Clean up classes
        if ( instance.currentDragElement )
        {
            instance.currentDragElement.classList.remove( config.dragClass );
        }

        if ( instance.currentDropTarget )
        {
            instance.currentDropTarget.classList.remove(
                config.dragOverClass,
                config.validDropClass,
                config.invalidDropClass
            );
        }

        // Remove ghost element
        if ( instance.ghostElement )
        {
            document.body.removeChild( instance.ghostElement );
            instance.ghostElement = null;
        }

        // Handle drop
        if ( instance.currentDropTarget )
        {
            const isValid = config.validator ?
                config.validator( instance.currentDragElement, instance.currentDropTarget, instance ) : true;

            if ( isValid )
            {
                this.performDrop( instance );
            } else
            {
                this.revertDrag( instance );
            }
        } else
        {
            this.revertDrag( instance );
        }

        // Reset state
        instance.isActive = false;
        instance.currentDragElement = null;
        instance.currentDropTarget = null;
        instance.originalPosition = null;

        if ( config.onDragEnd )
        {
            config.onDragEnd( instance );
        }

        this.emit( 'dragEnd', { instance } );
    }

    performDrop ( instance )
    {
        const { config } = instance;
        const dragElement = instance.currentDragElement;
        const dropTarget = instance.currentDropTarget;

        if ( config.allowMove )
        {
            dropTarget.appendChild( dragElement );
        } else if ( config.allowCopy )
        {
            const copy = dragElement.cloneNode( true );
            dropTarget.appendChild( copy );
        }

        if ( config.onDrop )
        {
            config.onDrop( dragElement, dropTarget, instance );
        }

        this.emit( 'drop', { dragElement, dropTarget, instance } );
    }

    revertDrag ( instance )
    {
        // Animate back to original position if needed
        this.emit( 'dragReverted', { instance } );
    }

    getElementPosition ( element )
    {
        const rect = element.getBoundingClientRect();
        return {
            x: rect.left,
            y: rect.top,
            width: rect.width,
            height: rect.height
        };
    }

    // =============================================================================
    // Keyboard Shortcuts System
    // =============================================================================

    setupKeyboardShortcuts ()
    {
        this.registerShortcut( 'ctrl+s', this.handleSave.bind( this ), 'Save current work' );
        this.registerShortcut( 'ctrl+z', this.handleUndo.bind( this ), 'Undo last action' );
        this.registerShortcut( 'ctrl+y', this.handleRedo.bind( this ), 'Redo last action' );
        this.registerShortcut( 'ctrl+shift+z', this.handleRedo.bind( this ), 'Redo last action' );
        this.registerShortcut( 'escape', this.handleEscape.bind( this ), 'Cancel current operation' );
        this.registerShortcut( 'ctrl+/', this.showShortcutHelp.bind( this ), 'Show keyboard shortcuts' );
        this.registerShortcut( 'alt+f', this.focusSearch.bind( this ), 'Focus search' );
        this.registerShortcut( 'ctrl+shift+d', this.toggleDarkMode.bind( this ), 'Toggle dark mode' );

        document.addEventListener( 'keydown', this.handleKeyboardShortcut.bind( this ) );
    }

    registerShortcut ( combination, handler, description = '' )
    {
        const normalized = this.normalizeShortcut( combination );
        this.keyboardShortcuts.set( normalized, {
            handler,
            description,
            combination: combination
        } );
    }

    normalizeShortcut ( combination )
    {
        return combination.toLowerCase()
            .replace( /\s+/g, '' )
            .split( '+' )
            .sort()
            .join( '+' );
    }

    handleKeyboardShortcut ( e )
    {
        const combination = this.getKeyboardCombination( e );
        const shortcut = this.keyboardShortcuts.get( combination );

        if ( shortcut )
        {
            e.preventDefault();
            shortcut.handler( e );
        }
    }

    getKeyboardCombination ( e )
    {
        const keys = [];

        if ( e.ctrlKey || e.metaKey ) keys.push( 'ctrl' );
        if ( e.altKey ) keys.push( 'alt' );
        if ( e.shiftKey ) keys.push( 'shift' );

        // Special keys
        if ( e.key === 'Escape' ) keys.push( 'escape' );
        else if ( e.key === 'Enter' ) keys.push( 'enter' );
        else if ( e.key === 'Tab' ) keys.push( 'tab' );
        else if ( e.key === ' ' ) keys.push( 'space' );
        else if ( e.key.length === 1 ) keys.push( e.key.toLowerCase() );
        else keys.push( e.key.toLowerCase() );

        return keys.sort().join( '+' );
    }

    handleSave ( e )
    {
        this.emit( 'keyboardShortcut', { action: 'save', event: e } );
        // Trigger auto-save for all instances
        this.autoSaveInstances.forEach( instance =>
        {
            this.performAutoSave( instance );
        } );
    }

    handleUndo ( e )
    {
        this.emit( 'keyboardShortcut', { action: 'undo', event: e } );
        this.performUndo();
    }

    handleRedo ( e )
    {
        this.emit( 'keyboardShortcut', { action: 'redo', event: e } );
        this.performRedo();
    }

    handleEscape ( e )
    {
        this.emit( 'keyboardShortcut', { action: 'escape', event: e } );
        // Close any open modals, menus, etc.
        this.closeAllContextMenus();
        document.querySelectorAll( '.modal.show' ).forEach( modal =>
        {
            modal.classList.remove( 'show' );
        } );
    }

    focusSearch ( e )
    {
        const searchInput = document.querySelector( 'input[type="search"], input[placeholder*="search" i], .search-input' );
        if ( searchInput )
        {
            searchInput.focus();
            searchInput.select();
        }
    }

    toggleDarkMode ( e )
    {
        document.documentElement.classList.toggle( 'dark' );
        this.emit( 'themeToggle', { isDark: document.documentElement.classList.contains( 'dark' ) } );
    }

    showShortcutHelp ()
    {
        this.createShortcutHelpModal();
    }

    createShortcutHelpModal ()
    {
        // Remove existing modal if present
        const existingModal = document.getElementById( 'shortcut-help-modal' );
        if ( existingModal )
        {
            existingModal.remove();
        }

        const modal = document.createElement( 'div' );
        modal.id = 'shortcut-help-modal';
        modal.className = 'fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50';

        const shortcuts = Array.from( this.keyboardShortcuts.entries() );

        modal.innerHTML = `
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-md w-full mx-4 max-h-96 overflow-y-auto">
                <div class="p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Keyboard Shortcuts</h3>
                        <button class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300" onclick="this.closest('.fixed').remove()">×</button>
                    </div>
                    <div class="space-y-2">
                        ${ shortcuts.map( ( [ key, shortcut ] ) => `
                            <div class="flex items-center justify-between">
                                <span class="text-sm text-gray-600 dark:text-gray-400">${ shortcut.description }</span>
                                <kbd class="px-2 py-1 text-xs font-semibold text-gray-800 bg-gray-100 border border-gray-200 rounded dark:bg-gray-700 dark:text-gray-200 dark:border-gray-600">${ shortcut.combination }</kbd>
                            </div>
                        `).join( '' ) }
                    </div>
                </div>
            </div>
        `;

        document.body.appendChild( modal );

        // Close on outside click
        modal.addEventListener( 'click', ( e ) =>
        {
            if ( e.target === modal )
            {
                modal.remove();
            }
        } );

        // Close on escape
        const handleEscape = ( e ) =>
        {
            if ( e.key === 'Escape' )
            {
                modal.remove();
                document.removeEventListener( 'keydown', handleEscape );
            }
        };
        document.addEventListener( 'keydown', handleEscape );
    }

    // =============================================================================
    // Gesture-based Interactions
    // =============================================================================

    setupGestureDetection ()
    {
        if ( !this.isTouch ) return;

        this.gestureData = {
            startX: 0,
            startY: 0,
            currentX: 0,
            currentY: 0,
            startTime: 0,
            isGesturing: false
        };

        document.addEventListener( 'touchstart', this.handleGestureStart.bind( this ), { passive: false } );
        document.addEventListener( 'touchmove', this.handleGestureMove.bind( this ), { passive: false } );
        document.addEventListener( 'touchend', this.handleGestureEnd.bind( this ), { passive: false } );
    }

    registerGesture ( elementId, gestureType, handler, options = {} )
    {
        const element = document.getElementById( elementId );
        if ( !element )
        {
            throw new Error( `Element "${ elementId }" not found` );
        }

        const gestureConfig = {
            type: gestureType,
            handler,
            element,
            options: {
                threshold: 50,
                maxTime: 1000,
                ...options
            }
        };

        this.gestureHandlers.set( elementId, gestureConfig );
    }

    handleGestureStart ( e )
    {
        if ( e.touches.length !== 1 ) return;

        const touch = e.touches[ 0 ];
        this.gestureData.startX = touch.clientX;
        this.gestureData.startY = touch.clientY;
        this.gestureData.currentX = touch.clientX;
        this.gestureData.currentY = touch.clientY;
        this.gestureData.startTime = Date.now();
        this.gestureData.isGesturing = true;
    }

    handleGestureMove ( e )
    {
        if ( !this.gestureData.isGesturing || e.touches.length !== 1 ) return;

        const touch = e.touches[ 0 ];
        this.gestureData.currentX = touch.clientX;
        this.gestureData.currentY = touch.clientY;
    }

    handleGestureEnd ( e )
    {
        if ( !this.gestureData.isGesturing ) return;

        const deltaX = this.gestureData.currentX - this.gestureData.startX;
        const deltaY = this.gestureData.currentY - this.gestureData.startY;
        const deltaTime = Date.now() - this.gestureData.startTime;
        const distance = Math.sqrt( deltaX * deltaX + deltaY * deltaY );

        // Detect gesture type
        const gestureType = this.detectGestureType( deltaX, deltaY, distance, deltaTime );

        if ( gestureType )
        {
            this.handleGesture( e.target, gestureType, { deltaX, deltaY, distance, deltaTime } );
        }

        this.gestureData.isGesturing = false;
    }

    detectGestureType ( deltaX, deltaY, distance, deltaTime )
    {
        const threshold = 50;
        const maxTime = 1000;

        if ( deltaTime > maxTime ) return null;

        // Tap
        if ( distance < 10 && deltaTime < 300 )
        {
            return 'tap';
        }

        // Swipe gestures
        if ( distance > threshold )
        {
            const absX = Math.abs( deltaX );
            const absY = Math.abs( deltaY );

            if ( absX > absY )
            {
                return deltaX > 0 ? 'swipeRight' : 'swipeLeft';
            } else
            {
                return deltaY > 0 ? 'swipeDown' : 'swipeUp';
            }
        }

        return null;
    }

    handleGesture ( target, gestureType, gestureData )
    {
        // Find registered gesture handlers
        this.gestureHandlers.forEach( ( config, elementId ) =>
        {
            if ( config.element.contains( target ) && config.type === gestureType )
            {
                config.handler( gestureData, target );
            }
        } );

        this.emit( 'gesture', { target, gestureType, gestureData } );
    }

    // =============================================================================
    // Auto-save Functionality
    // =============================================================================

    initializeAutoSave ()
    {
        // Auto-initialize based on data attributes
        document.querySelectorAll( '[data-auto-save]' ).forEach( element =>
        {
            const config = {
                interval: parseInt( element.dataset.autoSaveInterval ) || 30000,
                key: element.dataset.autoSaveKey || element.id,
                storage: element.dataset.autoSaveStorage || 'localStorage'
            };
            this.createAutoSave( element.id, config );
        } );
    }

    createAutoSave ( elementId, options = {} )
    {
        const defaultOptions = {
            interval: 30000, // 30 seconds
            storage: 'localStorage', // localStorage or sessionStorage
            key: elementId,
            conflictResolution: 'merge', // merge, overwrite, prompt
            onSave: null,
            onLoad: null,
            onConflict: null,
            validator: null
        };

        const config = { ...defaultOptions, ...options };
        const element = document.getElementById( elementId );

        if ( !element )
        {
            throw new Error( `Element "${ elementId }" not found` );
        }

        const autoSaveInstance = {
            id: elementId,
            element,
            config,
            intervalId: null,
            lastSaved: null,
            hasUnsavedChanges: false
        };

        // Load saved data
        this.loadAutoSavedData( autoSaveInstance );

        // Start auto-save interval
        this.startAutoSave( autoSaveInstance );

        // Listen for changes
        this.setupAutoSaveListeners( autoSaveInstance );

        this.autoSaveInstances.set( elementId, autoSaveInstance );

        return autoSaveInstance;
    }

    setupAutoSaveListeners ( instance )
    {
        const { element } = instance;

        // Form elements
        if ( element.tagName === 'FORM' )
        {
            element.addEventListener( 'input', () =>
            {
                instance.hasUnsavedChanges = true;
            } );
            element.addEventListener( 'change', () =>
            {
                instance.hasUnsavedChanges = true;
            } );
        }

        // Content editable
        if ( element.contentEditable === 'true' )
        {
            element.addEventListener( 'input', () =>
            {
                instance.hasUnsavedChanges = true;
            } );
        }

        // Textarea
        if ( element.tagName === 'TEXTAREA' )
        {
            element.addEventListener( 'input', () =>
            {
                instance.hasUnsavedChanges = true;
            } );
        }
    }

    startAutoSave ( instance )
    {
        const { config } = instance;

        instance.intervalId = setInterval( () =>
        {
            if ( instance.hasUnsavedChanges )
            {
                this.performAutoSave( instance );
            }
        }, config.interval );
    }

    performAutoSave ( instance )
    {
        const { element, config } = instance;
        const data = this.extractElementData( element );

        try
        {
            // Validate data if validator provided
            if ( config.validator && !config.validator( data ) )
            {
                return;
            }

            // Save to storage
            const storage = config.storage === 'sessionStorage' ? sessionStorage : localStorage;
            const saveData = {
                data,
                timestamp: Date.now(),
                version: 1
            };

            storage.setItem( `autosave_${ config.key }`, JSON.stringify( saveData ) );

            instance.lastSaved = Date.now();
            instance.hasUnsavedChanges = false;

            // Call save callback
            if ( config.onSave )
            {
                config.onSave( data, instance );
            }

            this.emit( 'autoSave', { data, instance } );

        } catch ( error )
        {
            console.error( 'Auto-save failed:', error );
            this.emit( 'autoSaveError', { error, instance } );
        }
    }

    loadAutoSavedData ( instance )
    {
        const { config } = instance;

        try
        {
            const storage = config.storage === 'sessionStorage' ? sessionStorage : localStorage;
            const savedData = storage.getItem( `autosave_${ config.key }` );

            if ( savedData )
            {
                const parsed = JSON.parse( savedData );

                // Check for conflicts
                const currentData = this.extractElementData( instance.element );
                const hasConflict = this.detectDataConflict( currentData, parsed.data );

                if ( hasConflict )
                {
                    this.handleAutoSaveConflict( instance, currentData, parsed.data );
                } else
                {
                    this.restoreElementData( instance.element, parsed.data );
                }

                if ( config.onLoad )
                {
                    config.onLoad( parsed.data, instance );
                }

                this.emit( 'autoLoad', { data: parsed.data, instance } );
            }
        } catch ( error )
        {
            console.error( 'Auto-load failed:', error );
            this.emit( 'autoLoadError', { error, instance } );
        }
    }

    extractElementData ( element )
    {
        if ( element.tagName === 'FORM' )
        {
            const formData = new FormData( element );
            const data = {};
            for ( const [ key, value ] of formData.entries() )
            {
                data[ key ] = value;
            }
            return data;
        }

        if ( element.contentEditable === 'true' )
        {
            return { content: element.innerHTML };
        }

        if ( element.tagName === 'TEXTAREA' || element.tagName === 'INPUT' )
        {
            return { value: element.value };
        }

        return { content: element.textContent };
    }

    restoreElementData ( element, data )
    {
        if ( element.tagName === 'FORM' && typeof data === 'object' )
        {
            Object.entries( data ).forEach( ( [ key, value ] ) =>
            {
                const field = element.querySelector( `[name="${ key }"]` );
                if ( field )
                {
                    field.value = value;
                }
            } );
        } else if ( element.contentEditable === 'true' && data.content )
        {
            element.innerHTML = data.content;
        } else if ( ( element.tagName === 'TEXTAREA' || element.tagName === 'INPUT' ) && data.value )
        {
            element.value = data.value;
        }
    }

    detectDataConflict ( current, saved )
    {
        return JSON.stringify( current ) !== JSON.stringify( saved );
    }

    handleAutoSaveConflict ( instance, currentData, savedData )
    {
        const { config } = instance;

        if ( config.conflictResolution === 'overwrite' )
        {
            this.restoreElementData( instance.element, savedData );
        } else if ( config.conflictResolution === 'merge' )
        {
            const merged = this.mergeData( currentData, savedData );
            this.restoreElementData( instance.element, merged );
        } else if ( config.conflictResolution === 'prompt' )
        {
            this.showConflictResolutionDialog( instance, currentData, savedData );
        }

        if ( config.onConflict )
        {
            config.onConflict( currentData, savedData, instance );
        }
    }

    mergeData ( current, saved )
    {
        // Simple merge strategy - can be customized
        return { ...saved, ...current };
    }

    showConflictResolutionDialog ( instance, currentData, savedData )
    {
        // Create conflict resolution dialog
        const dialog = document.createElement( 'div' );
        dialog.className = 'fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50';
        dialog.innerHTML = `
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-lg w-full mx-4">
                <div class="p-6">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Data Conflict Detected</h3>
                    <p class="text-gray-600 dark:text-gray-400 mb-4">
                        Auto-saved data conflicts with current content. How would you like to resolve this?
                    </p>
                    <div class="flex space-x-3">
                        <button class="use-current bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md">Use Current</button>
                        <button class="use-saved bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-md">Use Saved</button>
                        <button class="merge bg-purple-600 hover:bg-purple-700 text-white px-4 py-2 rounded-md">Merge</button>
                    </div>
                </div>
            </div>
        `;

        document.body.appendChild( dialog );

        dialog.querySelector( '.use-current' ).addEventListener( 'click', () =>
        {
            dialog.remove();
        } );

        dialog.querySelector( '.use-saved' ).addEventListener( 'click', () =>
        {
            this.restoreElementData( instance.element, savedData );
            dialog.remove();
        } );

        dialog.querySelector( '.merge' ).addEventListener( 'click', () =>
        {
            const merged = this.mergeData( currentData, savedData );
            this.restoreElementData( instance.element, merged );
            dialog.remove();
        } );
    }

    // =============================================================================
    // Undo/Redo Functionality
    // =============================================================================

    createUndoRedo ( contextId, options = {} )
    {
        const defaultOptions = {
            maxStates: 50,
            captureInterval: 1000,
            onUndo: null,
            onRedo: null,
            onStateChange: null
        };

        const config = { ...defaultOptions, ...options };

        const undoRedoStack = {
            id: contextId,
            config,
            undoStack: [],
            redoStack: [],
            currentState: null,
            lastCapture: 0
        };

        this.undoRedoStacks.set( contextId, undoRedoStack );
        return undoRedoStack;
    }

    captureState ( contextId, state, description = '' )
    {
        const stack = this.undoRedoStacks.get( contextId );
        if ( !stack )
        {
            throw new Error( `Undo/Redo context "${ contextId }" not found` );
        }

        const now = Date.now();
        if ( now - stack.lastCapture < stack.config.captureInterval )
        {
            return; // Too soon since last capture
        }

        // Add current state to undo stack
        if ( stack.currentState !== null )
        {
            stack.undoStack.push( {
                state: stack.currentState,
                description: stack.currentDescription || '',
                timestamp: stack.lastCapture
            } );

            // Limit stack size
            if ( stack.undoStack.length > stack.config.maxStates )
            {
                stack.undoStack.shift();
            }
        }

        // Clear redo stack when new state is captured
        stack.redoStack = [];

        // Set new current state
        stack.currentState = this.deepClone( state );
        stack.currentDescription = description;
        stack.lastCapture = now;

        if ( stack.config.onStateChange )
        {
            stack.config.onStateChange( stack );
        }

        this.emit( 'stateCaptured', { contextId, state, description, stack } );
    }

    performUndo ( contextId = 'global' )
    {
        const stack = this.undoRedoStacks.get( contextId );
        if ( !stack || stack.undoStack.length === 0 )
        {
            return null;
        }

        // Move current state to redo stack
        if ( stack.currentState !== null )
        {
            stack.redoStack.push( {
                state: stack.currentState,
                description: stack.currentDescription || '',
                timestamp: stack.lastCapture
            } );
        }

        // Get previous state from undo stack
        const previousState = stack.undoStack.pop();
        stack.currentState = previousState.state;
        stack.currentDescription = previousState.description;

        if ( stack.config.onUndo )
        {
            stack.config.onUndo( previousState.state, stack );
        }

        if ( stack.config.onStateChange )
        {
            stack.config.onStateChange( stack );
        }

        this.emit( 'undo', { contextId, state: previousState.state, stack } );
        return previousState.state;
    }

    performRedo ( contextId = 'global' )
    {
        const stack = this.undoRedoStacks.get( contextId );
        if ( !stack || stack.redoStack.length === 0 )
        {
            return null;
        }

        // Move current state to undo stack
        if ( stack.currentState !== null )
        {
            stack.undoStack.push( {
                state: stack.currentState,
                description: stack.currentDescription || '',
                timestamp: stack.lastCapture
            } );
        }

        // Get next state from redo stack
        const nextState = stack.redoStack.pop();
        stack.currentState = nextState.state;
        stack.currentDescription = nextState.description;

        if ( stack.config.onRedo )
        {
            stack.config.onRedo( nextState.state, stack );
        }

        if ( stack.config.onStateChange )
        {
            stack.config.onStateChange( stack );
        }

        this.emit( 'redo', { contextId, state: nextState.state, stack } );
        return nextState.state;
    }

    canUndo ( contextId = 'global' )
    {
        const stack = this.undoRedoStacks.get( contextId );
        return stack && stack.undoStack.length > 0;
    }

    canRedo ( contextId = 'global' )
    {
        const stack = this.undoRedoStacks.get( contextId );
        return stack && stack.redoStack.length > 0;
    }

    getUndoRedoStatus ( contextId = 'global' )
    {
        const stack = this.undoRedoStacks.get( contextId );
        if ( !stack ) return null;

        return {
            canUndo: this.canUndo( contextId ),
            canRedo: this.canRedo( contextId ),
            undoCount: stack.undoStack.length,
            redoCount: stack.redoStack.length,
            currentDescription: stack.currentDescription
        };
    }

    deepClone ( obj )
    {
        return JSON.parse( JSON.stringify( obj ) );
    }

    // =============================================================================
    // Contextual Menus and Actions
    // =============================================================================

    setupContextMenus ()
    {
        document.addEventListener( 'contextmenu', this.handleContextMenu.bind( this ) );
        document.addEventListener( 'click', this.handleDocumentClick.bind( this ) );
    }

    createContextMenu ( elementId, menuItems, options = {} )
    {
        const defaultOptions = {
            trigger: 'rightClick', // rightClick, click, hover
            position: 'auto', // auto, fixed
            hideOnClick: true,
            hideOnOutsideClick: true,
            animation: true,
            className: ''
        };

        const config = { ...defaultOptions, ...options };
        const element = document.getElementById( elementId );

        if ( !element )
        {
            throw new Error( `Element "${ elementId }" not found` );
        }

        const contextMenu = {
            id: elementId,
            element,
            menuItems,
            config,
            isVisible: false,
            menuElement: null
        };

        this.setupContextMenuTrigger( contextMenu );
        this.contextMenus.set( elementId, contextMenu );

        return contextMenu;
    }

    setupContextMenuTrigger ( contextMenu )
    {
        const { element, config } = contextMenu;

        if ( config.trigger === 'rightClick' )
        {
            element.addEventListener( 'contextmenu', ( e ) =>
            {
                e.preventDefault();
                this.showContextMenu( contextMenu, { x: e.clientX, y: e.clientY } );
            } );
        } else if ( config.trigger === 'click' )
        {
            element.addEventListener( 'click', ( e ) =>
            {
                e.preventDefault();
                const rect = element.getBoundingClientRect();
                this.showContextMenu( contextMenu, {
                    x: rect.left + rect.width / 2,
                    y: rect.bottom
                } );
            } );
        } else if ( config.trigger === 'hover' )
        {
            element.addEventListener( 'mouseenter', () =>
            {
                const rect = element.getBoundingClientRect();
                this.showContextMenu( contextMenu, {
                    x: rect.right,
                    y: rect.top
                } );
            } );
            element.addEventListener( 'mouseleave', () =>
            {
                setTimeout( () =>
                {
                    if ( !contextMenu.menuElement?.matches( ':hover' ) )
                    {
                        this.hideContextMenu( contextMenu );
                    }
                }, 100 );
            } );
        }
    }

    showContextMenu ( contextMenu, position )
    {
        // Hide other context menus
        this.closeAllContextMenus();

        const { menuItems, config } = contextMenu;

        // Create menu element
        const menu = document.createElement( 'div' );
        menu.className = `context-menu ${ config.className }`;
        menu.style.cssText = `
            position: fixed;
            z-index: 10000;
            background: white;
            border: 1px solid #ccc;
            border-radius: 4px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            padding: 4px 0;
            min-width: 150px;
            ${ config.animation ? 'opacity: 0; transform: scale(0.95); transition: all 0.15s ease;' : '' }
        `;

        // Add menu items
        menuItems.forEach( item =>
        {
            if ( item.separator )
            {
                const separator = document.createElement( 'div' );
                separator.style.cssText = 'height: 1px; background: #eee; margin: 4px 0;';
                menu.appendChild( separator );
            } else
            {
                const menuItem = document.createElement( 'div' );
                menuItem.className = 'context-menu-item';
                menuItem.style.cssText = `
                    padding: 8px 16px;
                    cursor: pointer;
                    display: flex;
                    align-items: center;
                    gap: 8px;
                    ${ item.disabled ? 'opacity: 0.5; cursor: not-allowed;' : 'hover:background: #f0f0f0;' }
                `;

                if ( item.icon )
                {
                    menuItem.innerHTML = `<span>${ item.icon }</span><span>${ item.label }</span>`;
                } else
                {
                    menuItem.textContent = item.label;
                }

                if ( !item.disabled )
                {
                    menuItem.addEventListener( 'click', () =>
                    {
                        if ( item.handler )
                        {
                            item.handler( contextMenu );
                        }
                        if ( config.hideOnClick )
                        {
                            this.hideContextMenu( contextMenu );
                        }
                    } );
                }

                menu.appendChild( menuItem );
            }
        } );

        document.body.appendChild( menu );
        contextMenu.menuElement = menu;

        // Position menu
        this.positionContextMenu( menu, position );

        // Show with animation
        if ( config.animation )
        {
            requestAnimationFrame( () =>
            {
                menu.style.opacity = '1';
                menu.style.transform = 'scale(1)';
            } );
        }

        contextMenu.isVisible = true;

        // Setup hover behavior for hover trigger
        if ( config.trigger === 'hover' )
        {
            menu.addEventListener( 'mouseenter', () =>
            {
                clearTimeout( contextMenu.hideTimeout );
            } );
            menu.addEventListener( 'mouseleave', () =>
            {
                this.hideContextMenu( contextMenu );
            } );
        }

        this.emit( 'contextMenuShow', { contextMenu, position } );
    }

    positionContextMenu ( menu, position )
    {
        const menuRect = menu.getBoundingClientRect();
        const viewportWidth = window.innerWidth;
        const viewportHeight = window.innerHeight;

        let { x, y } = position;

        // Adjust horizontal position
        if ( x + menuRect.width > viewportWidth )
        {
            x = viewportWidth - menuRect.width - 10;
        }
        if ( x < 10 )
        {
            x = 10;
        }

        // Adjust vertical position
        if ( y + menuRect.height > viewportHeight )
        {
            y = viewportHeight - menuRect.height - 10;
        }
        if ( y < 10 )
        {
            y = 10;
        }

        menu.style.left = `${ x }px`;
        menu.style.top = `${ y }px`;
    }

    hideContextMenu ( contextMenu )
    {
        if ( !contextMenu.isVisible || !contextMenu.menuElement ) return;

        const { config } = contextMenu;

        if ( config.animation )
        {
            contextMenu.menuElement.style.opacity = '0';
            contextMenu.menuElement.style.transform = 'scale(0.95)';
            setTimeout( () =>
            {
                if ( contextMenu.menuElement )
                {
                    document.body.removeChild( contextMenu.menuElement );
                    contextMenu.menuElement = null;
                }
            }, 150 );
        } else
        {
            document.body.removeChild( contextMenu.menuElement );
            contextMenu.menuElement = null;
        }

        contextMenu.isVisible = false;

        this.emit( 'contextMenuHide', { contextMenu } );
    }

    closeAllContextMenus ()
    {
        this.contextMenus.forEach( contextMenu =>
        {
            if ( contextMenu.isVisible )
            {
                this.hideContextMenu( contextMenu );
            }
        } );
    }

    handleContextMenu ( e )
    {
        // Prevent default context menu if we have custom context menus
        const target = e.target;
        for ( const [ elementId, contextMenu ] of this.contextMenus )
        {
            if ( contextMenu.element.contains( target ) && contextMenu.config.trigger === 'rightClick' )
            {
                e.preventDefault();
                return;
            }
        }
    }

    handleDocumentClick ( e )
    {
        // Close context menus on outside click
        this.contextMenus.forEach( contextMenu =>
        {
            if ( contextMenu.isVisible && contextMenu.config.hideOnOutsideClick )
            {
                if ( !contextMenu.menuElement?.contains( e.target ) )
                {
                    this.hideContextMenu( contextMenu );
                }
            }
        } );
    }

    // =============================================================================
    // Event System
    // =============================================================================

    setupEventListeners ()
    {
        // Prevent default context menu on elements with custom context menus
        document.addEventListener( 'contextmenu', ( e ) =>
        {
            // This is handled in handleContextMenu
        } );

        // Cleanup on page unload
        window.addEventListener( 'beforeunload', () =>
        {
            this.destroy();
        } );
    }

    on ( eventName, callback )
    {
        if ( !this.eventListeners.has( eventName ) )
        {
            this.eventListeners.set( eventName, [] );
        }
        this.eventListeners.get( eventName ).push( callback );
    }

    off ( eventName, callback )
    {
        if ( this.eventListeners.has( eventName ) )
        {
            const listeners = this.eventListeners.get( eventName );
            const index = listeners.indexOf( callback );
            if ( index > -1 )
            {
                listeners.splice( index, 1 );
            }
        }
    }

    emit ( eventName, data )
    {
        if ( this.eventListeners.has( eventName ) )
        {
            this.eventListeners.get( eventName ).forEach( callback =>
            {
                try
                {
                    callback( data );
                } catch ( error )
                {
                    console.error( `Error in event listener for ${ eventName }:`, error );
                }
            } );
        }
    }

    // =============================================================================
    // Utility Methods
    // =============================================================================

    destroy ()
    {
        // Clean up intervals
        this.autoSaveInstances.forEach( instance =>
        {
            if ( instance.intervalId )
            {
                clearInterval( instance.intervalId );
            }
        } );

        // Clean up event listeners
        document.removeEventListener( 'keydown', this.handleKeyboardShortcut );

        // Clear all data
        this.dragDropInstances.clear();
        this.keyboardShortcuts.clear();
        this.gestureHandlers.clear();
        this.autoSaveInstances.clear();
        this.undoRedoStacks.clear();
        this.contextMenus.clear();
        this.eventListeners.clear();

        console.log( '🗑️ Advanced Interaction Patterns destroyed' );
    }

    getStats ()
    {
        return {
            dragDropInstances: this.dragDropInstances.size,
            keyboardShortcuts: this.keyboardShortcuts.size,
            gestureHandlers: this.gestureHandlers.size,
            autoSaveInstances: this.autoSaveInstances.size,
            undoRedoStacks: this.undoRedoStacks.size,
            contextMenus: this.contextMenus.size,
            isTouch: this.isTouch
        };
    }
}

// =============================================================================
// Alpine.js Integration
// =============================================================================

document.addEventListener( 'alpine:init', () =>
{
    Alpine.data( 'advancedInteractionDemo', () => ( {
        interactionSystem: null,
        dragItems: [
            { id: 1, text: 'Drag Item 1', category: 'A' },
            { id: 2, text: 'Drag Item 2', category: 'B' },
            { id: 3, text: 'Drag Item 3', category: 'A' },
            { id: 4, text: 'Drag Item 4', category: 'C' }
        ],
        dropZones: [
            { id: 'zone-a', name: 'Zone A', accepts: [ 'A' ] },
            { id: 'zone-b', name: 'Zone B', accepts: [ 'B' ] },
            { id: 'zone-c', name: 'Zone C', accepts: [ 'A', 'C' ] }
        ],
        autoSaveContent: '',
        undoRedoContext: 'demo',
        contextMenuItems: [
            { label: 'Copy', icon: '📋', handler: ( menu ) => this.copyItem() },
            { label: 'Cut', icon: '✂️', handler: ( menu ) => this.cutItem() },
            { separator: true },
            { label: 'Delete', icon: '🗑️', handler: ( menu ) => this.deleteItem() },
            { label: 'Properties', icon: '⚙️', handler: ( menu ) => this.showProperties() }
        ],
        stats: {},

        init ()
        {
            this.interactionSystem = new AdvancedInteractionPatterns();
            this.setupDragAndDrop();
            this.setupAutoSave();
            this.setupUndoRedo();
            this.setupContextMenus();
            this.setupGestures();
            this.setupEventListeners();
            this.updateStats();
        },

        setupDragAndDrop ()
        {
            this.interactionSystem.createDragDropZone( 'drag-demo-container', {
                dragSelector: '.drag-item',
                dropSelector: '.drop-zone',
                validator: ( dragElement, dropZone ) =>
                {
                    const itemCategory = dragElement.dataset.category;
                    const zoneAccepts = JSON.parse( dropZone.dataset.accepts || '[]' );
                    return zoneAccepts.includes( itemCategory );
                },
                onDrop: ( dragElement, dropZone ) =>
                {
                    console.log( 'Item dropped:', dragElement.textContent, 'into', dropZone.dataset.name );
                    this.captureState( 'Moved item' );
                }
            } );
        },

        setupAutoSave ()
        {
            this.interactionSystem.createAutoSave( 'auto-save-demo', {
                interval: 5000, // 5 seconds for demo
                onSave: ( data ) =>
                {
                    console.log( 'Auto-saved:', data );
                }
            } );
        },

        setupUndoRedo ()
        {
            this.interactionSystem.createUndoRedo( this.undoRedoContext, {
                onUndo: ( state ) =>
                {
                    console.log( 'Undo to state:', state );
                },
                onRedo: ( state ) =>
                {
                    console.log( 'Redo to state:', state );
                }
            } );

            // Capture initial state
            this.captureState( 'Initial state' );
        },

        setupContextMenus ()
        {
            this.interactionSystem.createContextMenu( 'context-menu-demo', this.contextMenuItems );
        },

        setupGestures ()
        {
            if ( this.interactionSystem.isTouch )
            {
                this.interactionSystem.registerGesture( 'gesture-demo', 'swipeLeft', () =>
                {
                    console.log( 'Swiped left!' );
                } );

                this.interactionSystem.registerGesture( 'gesture-demo', 'swipeRight', () =>
                {
                    console.log( 'Swiped right!' );
                } );
            }
        },

        setupEventListeners ()
        {
            this.interactionSystem.on( 'dragStart', ( data ) =>
            {
                console.log( 'Drag started:', data );
            } );

            this.interactionSystem.on( 'drop', ( data ) =>
            {
                console.log( 'Drop completed:', data );
            } );

            this.interactionSystem.on( 'autoSave', ( data ) =>
            {
                console.log( 'Auto-save completed:', data );
            } );

            this.interactionSystem.on( 'keyboardShortcut', ( data ) =>
            {
                console.log( 'Keyboard shortcut:', data.action );
            } );
        },

        captureState ( description )
        {
            const state = {
                content: this.autoSaveContent,
                dragItems: [ ...this.dragItems ],
                timestamp: Date.now()
            };

            this.interactionSystem.captureState( this.undoRedoContext, state, description );
            this.updateStats();
        },

        performUndo ()
        {
            const state = this.interactionSystem.performUndo( this.undoRedoContext );
            if ( state )
            {
                this.autoSaveContent = state.content || '';
                this.dragItems = state.dragItems || this.dragItems;
            }
            this.updateStats();
        },

        performRedo ()
        {
            const state = this.interactionSystem.performRedo( this.undoRedoContext );
            if ( state )
            {
                this.autoSaveContent = state.content || '';
                this.dragItems = state.dragItems || this.dragItems;
            }
            this.updateStats();
        },

        updateStats ()
        {
            this.stats = {
                ...this.interactionSystem.getStats(),
                undoRedo: this.interactionSystem.getUndoRedoStatus( this.undoRedoContext )
            };
        },

        copyItem ()
        {
            console.log( 'Copy action triggered' );
        },

        cutItem ()
        {
            console.log( 'Cut action triggered' );
        },

        deleteItem ()
        {
            console.log( 'Delete action triggered' );
        },

        showProperties ()
        {
            console.log( 'Properties dialog would open' );
        },

        triggerAutoSave ()
        {
            const instance = this.interactionSystem.autoSaveInstances.get( 'auto-save-demo' );
            if ( instance )
            {
                this.interactionSystem.performAutoSave( instance );
            }
        }
    } ) );
} );

// Global instance for direct access
window.AdvancedInteractionPatterns = AdvancedInteractionPatterns;

// CSS for styling
const interactionPatternsCSS = `
.drag-item {
    cursor: grab;
    user-select: none;
    transition: transform 0.2s, box-shadow 0.2s;
}

.drag-item:active {
    cursor: grabbing;
}

.drag-item.dragging {
    opacity: 0.8;
    transform: rotate(5deg);
    z-index: 1000;
}

.drop-zone {
    min-height: 100px;
    border: 2px dashed #ccc;
    border-radius: 8px;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    justify-content: center;
    text-align: center;
    background: #f9f9f9;
}

.drop-zone.drag-over {
    border-color: #007bff;
    background: #e3f2fd;
}

.drop-zone.valid-drop {
    border-color: #28a745;
    background: #d4edda;
}

.drop-zone.invalid-drop {
    border-color: #dc3545;
    background: #f8d7da;
}

.drag-ghost {
    opacity: 0.8;
    transform: rotate(3deg);
    box-shadow: 0 4px 8px rgba(0,0,0,0.2);
}

.context-menu {
    font-family: system-ui, -apple-system, sans-serif;
    font-size: 14px;
    color: #333;
}

.context-menu-item:hover:not([disabled]) {
    background: #f0f0f0 !important;
}

.gesture-area {
    touch-action: none;
    user-select: none;
}

@media (prefers-reduced-motion: reduce) {
    .drag-item,
    .drop-zone,
    .context-menu {
        transition: none;
    }
}
`;

// Inject CSS styles
if ( !document.getElementById( 'interaction-patterns-styles' ) )
{
    const style = document.createElement( 'style' );
    style.id = 'interaction-patterns-styles';
    style.textContent = interactionPatternsCSS;
    document.head.appendChild( style );
}

console.log( '🎯 Advanced Interaction Patterns loaded successfully' );
