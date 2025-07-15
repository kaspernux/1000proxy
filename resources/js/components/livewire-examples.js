/**
 * Example Component using Livewire Framework
 * Demonstrates composition patterns, lifecycle management, and testing
 */

import { ComponentFactory, ComponentTester } from './livewire-framework.js';

// Example: Advanced User Management Component
function createUserManagementComponent ()
{
    return ComponentFactory.create( 'UserManagement', {
        // Default State
        state: {
            users: [],
            selectedUsers: [],
            filters: {
                search: '',
                role: '',
                status: 'active'
            },
            sorting: {
                field: 'name',
                direction: 'asc'
            },
            pagination: {
                currentPage: 1,
                perPage: 10,
                total: 0
            },
            editingUser: null,
            showUserModal: false,
            bulkActions: {
                available: [ 'activate', 'deactivate', 'delete' ],
                selected: ''
            }
        },

        // Apply Mixins
        mixins: [
            window.LivewireFramework.mixins.Loading(),
            window.LivewireFramework.mixins.Validation(),
            window.LivewireFramework.mixins.Api(),
            window.LivewireFramework.mixins.Pagination()
        ],

        // Component Methods
        methods: {
            // User CRUD Operations
            async loadUsers ()
            {
                try
                {
                    const response = await this.withLoading( async () =>
                    {
                        return await this.makeRequest( '/api/users', {
                            method: 'GET',
                            body: JSON.stringify( {
                                page: this.state.pagination.currentPage,
                                per_page: this.state.pagination.perPage,
                                filters: this.state.filters,
                                sorting: this.state.sorting
                            } )
                        } );
                    }, 'Loading users...' );

                    this.setState( {
                        users: response.data,
                        pagination: {
                            ...this.state.pagination,
                            total: response.total,
                            totalPages: Math.ceil( response.total / this.state.pagination.perPage )
                        }
                    } );

                    this.emit( 'usersLoaded', response.data );
                } catch ( error )
                {
                    this.handleError( error, 'loadUsers' );
                }
            },

            async createUser ( userData )
            {
                const validation = this.validate( userData );
                if ( !validation.isValid )
                {
                    this.setState( { validationErrors: validation.errors } );
                    return false;
                }

                try
                {
                    const user = await this.withLoading( async () =>
                    {
                        return await this.makeRequest( '/api/users', {
                            method: 'POST',
                            body: JSON.stringify( userData )
                        } );
                    }, 'Creating user...' );

                    this.setState( {
                        users: [ ...this.state.users, user ],
                        showUserModal: false,
                        editingUser: null
                    } );

                    this.showSuccess( 'User created successfully' );
                    this.emit( 'userCreated', user );
                    return true;
                } catch ( error )
                {
                    this.handleError( error, 'createUser' );
                    return false;
                }
            },

            async updateUser ( userId, userData )
            {
                const validation = this.validate( userData );
                if ( !validation.isValid )
                {
                    this.setState( { validationErrors: validation.errors } );
                    return false;
                }

                try
                {
                    const updatedUser = await this.withLoading( async () =>
                    {
                        return await this.makeRequest( `/api/users/${ userId }`, {
                            method: 'PUT',
                            body: JSON.stringify( userData )
                        } );
                    }, 'Updating user...' );

                    const users = this.state.users.map( user =>
                        user.id === userId ? updatedUser : user
                    );

                    this.setState( {
                        users,
                        showUserModal: false,
                        editingUser: null
                    } );

                    this.showSuccess( 'User updated successfully' );
                    this.emit( 'userUpdated', updatedUser );
                    return true;
                } catch ( error )
                {
                    this.handleError( error, 'updateUser' );
                    return false;
                }
            },

            async deleteUser ( userId )
            {
                if ( !confirm( 'Are you sure you want to delete this user?' ) )
                {
                    return false;
                }

                try
                {
                    await this.withLoading( async () =>
                    {
                        return await this.makeRequest( `/api/users/${ userId }`, {
                            method: 'DELETE'
                        } );
                    }, 'Deleting user...' );

                    const users = this.state.users.filter( user => user.id !== userId );
                    this.setState( { users } );

                    this.showSuccess( 'User deleted successfully' );
                    this.emit( 'userDeleted', userId );
                    return true;
                } catch ( error )
                {
                    this.handleError( error, 'deleteUser' );
                    return false;
                }
            },

            // UI State Management
            openUserModal ( user = null )
            {
                this.setState( {
                    showUserModal: true,
                    editingUser: user,
                    validationErrors: {}
                } );
                this.emit( 'modalOpened', user );
            },

            closeUserModal ()
            {
                this.setState( {
                    showUserModal: false,
                    editingUser: null,
                    validationErrors: {}
                } );
                this.emit( 'modalClosed' );
            },

            // Selection Management
            toggleUserSelection ( userId )
            {
                const selectedUsers = this.state.selectedUsers.includes( userId )
                    ? this.state.selectedUsers.filter( id => id !== userId )
                    : [ ...this.state.selectedUsers, userId ];

                this.setState( { selectedUsers } );
                this.emit( 'selectionChanged', selectedUsers );
            },

            selectAllUsers ()
            {
                const allUserIds = this.state.users.map( user => user.id );
                this.setState( { selectedUsers: allUserIds } );
                this.emit( 'allUsersSelected', allUserIds );
            },

            clearSelection ()
            {
                this.setState( { selectedUsers: [] } );
                this.emit( 'selectionCleared' );
            },

            // Bulk Operations
            async executeBulkAction ( action )
            {
                if ( this.state.selectedUsers.length === 0 )
                {
                    this.handleError( new Error( 'No users selected' ), 'executeBulkAction' );
                    return false;
                }

                const confirmMessage = `Are you sure you want to ${ action } ${ this.state.selectedUsers.length } users?`;
                if ( !confirm( confirmMessage ) )
                {
                    return false;
                }

                try
                {
                    await this.withLoading( async () =>
                    {
                        return await this.makeRequest( '/api/users/bulk', {
                            method: 'POST',
                            body: JSON.stringify( {
                                action,
                                user_ids: this.state.selectedUsers
                            } )
                        } );
                    }, `Executing ${ action } on selected users...` );

                    await this.loadUsers();
                    this.clearSelection();
                    this.showSuccess( `Bulk ${ action } completed successfully` );
                    this.emit( 'bulkActionCompleted', { action, userIds: this.state.selectedUsers } );
                    return true;
                } catch ( error )
                {
                    this.handleError( error, 'executeBulkAction' );
                    return false;
                }
            },

            // Filtering and Sorting
            updateFilters ( filters )
            {
                this.setState( {
                    filters: { ...this.state.filters, ...filters },
                    pagination: { ...this.state.pagination, currentPage: 1 }
                } );
                this.emit( 'filtersChanged', this.state.filters );
                this.loadUsers();
            },

            updateSorting ( field )
            {
                const currentField = this.state.sorting.field;
                const direction = currentField === field && this.state.sorting.direction === 'asc' ? 'desc' : 'asc';

                this.setState( {
                    sorting: { field, direction }
                } );
                this.emit( 'sortingChanged', { field, direction } );
                this.loadUsers();
            },

            clearFilters ()
            {
                this.setState( {
                    filters: {
                        search: '',
                        role: '',
                        status: 'active'
                    },
                    pagination: { ...this.state.pagination, currentPage: 1 }
                } );
                this.emit( 'filtersCleared' );
                this.loadUsers();
            },

            // Utility Methods
            getSelectedUsersData ()
            {
                return this.state.users.filter( user =>
                    this.state.selectedUsers.includes( user.id )
                );
            },

            exportUsers ( format = 'csv' )
            {
                const selectedUsers = this.getSelectedUsersData();
                const usersToExport = selectedUsers.length > 0 ? selectedUsers : this.state.users;

                this.emit( 'exportRequested', { format, users: usersToExport } );
            }
        },

        // Lifecycle Hooks
        lifecycle: {
            afterInit: [
                function ()
                {
                    this.setupValidation();
                    this.loadUsers();
                }
            ],
            beforeDestroy: [
                function ()
                {
                    this.clearCache();
                }
            ]
        },

        // Event Handlers
        events: {
            pageChanged: function ( page )
            {
                this.loadUsers();
            },
            perPageChanged: function ( perPage )
            {
                this.loadUsers();
            }
        }
    } );
}

// Advanced State Management Component
function createAdvancedStateComponent ()
{
    return ComponentFactory.create( 'AdvancedState', {
        state: {
            preferences: {
                theme: 'light',
                language: 'en',
                timezone: 'UTC',
                notifications: {
                    email: true,
                    push: false,
                    sms: false
                }
            },
            history: [],
            undoStack: [],
            redoStack: []
        },

        methods: {
            // State Persistence
            saveToLocalStorage ()
            {
                const stateToSave = {
                    preferences: this.state.preferences,
                    timestamp: Date.now()
                };
                localStorage.setItem( 'user_preferences', JSON.stringify( stateToSave ) );
                this.emit( 'preferencesSaved', stateToSave );
            },

            loadFromLocalStorage ()
            {
                try
                {
                    const saved = localStorage.getItem( 'user_preferences' );
                    if ( saved )
                    {
                        const data = JSON.parse( saved );
                        this.setState( { preferences: data.preferences } );
                        this.emit( 'preferencesLoaded', data );
                    }
                } catch ( error )
                {
                    this.handleError( error, 'loadFromLocalStorage' );
                }
            },

            // Undo/Redo Functionality
            saveStateToUndo ()
            {
                this.state.undoStack.push( JSON.stringify( this.state.preferences ) );
                if ( this.state.undoStack.length > 20 )
                {
                    this.state.undoStack.shift();
                }
                this.state.redoStack = [];
            },

            undo ()
            {
                if ( this.state.undoStack.length === 0 ) return false;

                this.state.redoStack.push( JSON.stringify( this.state.preferences ) );
                const previousState = this.state.undoStack.pop();

                this.setState( {
                    preferences: JSON.parse( previousState )
                } );

                this.emit( 'undoPerformed' );
                return true;
            },

            redo ()
            {
                if ( this.state.redoStack.length === 0 ) return false;

                this.state.undoStack.push( JSON.stringify( this.state.preferences ) );
                const nextState = this.state.redoStack.pop();

                this.setState( {
                    preferences: JSON.parse( nextState )
                } );

                this.emit( 'redoPerformed' );
                return true;
            },

            // Preference Management
            updatePreference ( key, value )
            {
                this.saveStateToUndo();

                const preferences = { ...this.state.preferences };
                this.setNestedValue( preferences, key, value );

                this.setState( { preferences } );
                this.saveToLocalStorage();
                this.emit( 'preferenceChanged', { key, value } );
            },

            setNestedValue ( obj, path, value )
            {
                const keys = path.split( '.' );
                let current = obj;

                for ( let i = 0; i < keys.length - 1; i++ )
                {
                    if ( !( keys[ i ] in current ) )
                    {
                        current[ keys[ i ] ] = {};
                    }
                    current = current[ keys[ i ] ];
                }

                current[ keys[ keys.length - 1 ] ] = value;
            },

            getNestedValue ( obj, path )
            {
                return path.split( '.' ).reduce( ( current, key ) => current?.[ key ], obj );
            },

            // Theme Management
            setTheme ( theme )
            {
                this.updatePreference( 'theme', theme );
                document.documentElement.setAttribute( 'data-theme', theme );
                this.emit( 'themeChanged', theme );
            },

            // Synchronization
            async syncWithServer ()
            {
                try
                {
                    const response = await this.makeRequest( '/api/user/preferences', {
                        method: 'PUT',
                        body: JSON.stringify( this.state.preferences )
                    } );

                    this.showSuccess( 'Preferences synchronized' );
                    this.emit( 'syncCompleted', response );
                } catch ( error )
                {
                    this.handleError( error, 'syncWithServer' );
                }
            }
        },

        lifecycle: {
            afterInit: [
                function ()
                {
                    this.loadFromLocalStorage();
                }
            ]
        }
    } );
}

// Component Composition Example
function createCompositeComponent ()
{
    const userManager = createUserManagementComponent();
    const stateManager = createAdvancedStateComponent();

    // Cross-component communication
    userManager.on( 'userCreated', ( user ) =>
    {
        stateManager.updatePreference( 'lastUserCreated', {
            id: user.id,
            timestamp: Date.now()
        } );
    } );

    stateManager.on( 'themeChanged', ( theme ) =>
    {
        userManager.emit( 'themeUpdated', theme );
    } );

    return {
        userManager,
        stateManager,
        init ()
        {
            userManager.init();
            stateManager.init();
        },
        destroy ()
        {
            userManager.destroy();
            stateManager.destroy();
        }
    };
}

// Testing Examples
function runComponentTests ()
{
    const component = createUserManagementComponent();
    component.init();

    const tester = new ComponentTester( component );

    tester
        .test( 'Initial state is correct', ( comp ) =>
        {
            if ( comp.getState( 'users' ).length !== 0 )
            {
                throw new Error( 'Users array should be empty initially' );
            }
        } )
        .test( 'Selection works correctly', ( comp ) =>
        {
            comp.toggleUserSelection( 1 );
            comp.toggleUserSelection( 2 );

            const selected = comp.getState( 'selectedUsers' );
            if ( selected.length !== 2 || !selected.includes( 1 ) || !selected.includes( 2 ) )
            {
                throw new Error( 'Selection not working correctly' );
            }
        } )
        .test( 'Clear selection works', ( comp ) =>
        {
            comp.clearSelection();
            const selected = comp.getState( 'selectedUsers' );
            if ( selected.length !== 0 )
            {
                throw new Error( 'Clear selection not working' );
            }
        } )
        .asyncTest( 'Modal operations work', async ( comp ) =>
        {
            const eventPromise = tester.expectEvent( 'modalOpened', 500 );
            comp.openUserModal();
            await eventPromise;

            if ( !comp.getState( 'showUserModal' ) )
            {
                throw new Error( 'Modal should be open' );
            }
        } );

    const results = tester.getResults();
    console.log( 'Test Results:', results );

    component.destroy();
    return results;
}

// Export for global use
if ( typeof window !== 'undefined' )
{
    window.LivewireExamples = {
        createUserManagementComponent,
        createAdvancedStateComponent,
        createCompositeComponent,
        runComponentTests
    };
}

export
{
    createUserManagementComponent,
    createAdvancedStateComponent,
    createCompositeComponent,
    runComponentTests
};
