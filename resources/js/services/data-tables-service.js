/**
 * Data Tables Service
 * Server-side data processing and WebSocket integration for data tables
 */

class DataTablesService {
    constructor() {
        this.tables = new Map();
        this.websockets = new Map();
        this.apiEndpoints = new Map();
    }
    
    /**
     * Register a data table instance
     */
    registerTable(tableId, config) {
        this.tables.set(tableId, {
            config,
            instance: null,
            lastUpdate: null
        });
        
        // Setup API endpoints if configured
        if (config.dataUrl) {
            this.apiEndpoints.set(tableId, {
                data: config.dataUrl,
                update: config.updateUrl,
                delete: config.deleteUrl,
                create: config.createUrl
            });
        }
        
        // Setup WebSocket if configured
        if (config.websocketUrl) {
            this.setupWebSocket(tableId, config.websocketUrl);
        }
        
        console.log(`📊 Data table ${tableId} registered`);
    }
    
    /**
     * Setup WebSocket connection for real-time updates
     */
    setupWebSocket(tableId, url) {
        try {
            const ws = new WebSocket(url);
            
            ws.onopen = () => {
                console.log(`🔌 WebSocket connected for table ${tableId}`);
                this.updateConnectionStatus(tableId, 'connected');
            };
            
            ws.onmessage = (event) => {
                this.handleWebSocketMessage(tableId, JSON.parse(event.data));
            };
            
            ws.onclose = () => {
                console.log(`❌ WebSocket disconnected for table ${tableId}`);
                this.updateConnectionStatus(tableId, 'disconnected');
                
                // Attempt to reconnect after 5 seconds
                setTimeout(() => {
                    this.setupWebSocket(tableId, url);
                }, 5000);
            };
            
            ws.onerror = (error) => {
                console.error(`WebSocket error for table ${tableId}:`, error);
                this.updateConnectionStatus(tableId, 'error');
            };
            
            this.websockets.set(tableId, ws);
            
        } catch (error) {
            console.error(`Failed to setup WebSocket for table ${tableId}:`, error);
        }
    }
    
    /**
     * Handle WebSocket messages
     */
    handleWebSocketMessage(tableId, message) {
        const table = this.tables.get(tableId);
        if (!table || !table.instance) return;
        
        switch (message.type) {
            case 'data_update':
                this.updateTableData(tableId, message.data);
                break;
            case 'data_insert':
                this.insertTableData(tableId, message.data);
                break;
            case 'data_delete':
                this.deleteTableData(tableId, message.id);
                break;
            case 'bulk_update':
                this.bulkUpdateTableData(tableId, message.updates);
                break;
            case 'full_refresh':
                this.refreshTableData(tableId);
                break;
            case 'schema_change':
                this.handleSchemaChange(tableId, message.schema);
                break;
            default:
                console.log(`Unknown message type for table ${tableId}:`, message.type);
        }
    }
    
    /**
     * Update connection status
     */
    updateConnectionStatus(tableId, status) {
        const table = this.tables.get(tableId);
        if (table && table.instance) {
            table.instance.wsConnected = status === 'connected';
            table.instance.connectionStatus = status;
        }
    }
    
    /**
     * Server-side data processing
     */
    async processServerRequest(tableId, params) {
        const endpoints = this.apiEndpoints.get(tableId);
        if (!endpoints || !endpoints.data) {
            throw new Error('No data endpoint configured');
        }
        
        try {
            const url = new URL(endpoints.data);
            
            // Add query parameters
            Object.entries(params).forEach(([key, value]) => {
                if (value !== null && value !== undefined && value !== '') {
                    url.searchParams.append(key, value);
                }
            });
            
            const response = await fetch(url.toString(), {
                method: 'GET',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': this.getCSRFToken()
                }
            });
            
            if (!response.ok) {
                throw new Error(`Server request failed: ${response.status} ${response.statusText}`);
            }
            
            const data = await response.json();
            
            // Expected server response format:
            // {
            //     data: [...],
            //     meta: {
            //         total: number,
            //         per_page: number,
            //         current_page: number,
            //         last_page: number,
            //         from: number,
            //         to: number
            //     }
            // }
            
            return {
                data: data.data || data,
                pagination: data.meta || this.calculatePagination(data.data || data, params),
                filters: data.filters || {},
                sorting: data.sorting || {}
            };
            
        } catch (error) {
            console.error('Server request failed:', error);
            throw error;
        }
    }
    
    /**
     * Calculate pagination info for client-side data
     */
    calculatePagination(data, params) {
        const total = data.length;
        const perPage = parseInt(params.per_page) || 25;
        const currentPage = parseInt(params.page) || 1;
        const lastPage = Math.ceil(total / perPage);
        const from = (currentPage - 1) * perPage + 1;
        const to = Math.min(currentPage * perPage, total);
        
        return {
            total,
            per_page: perPage,
            current_page: currentPage,
            last_page: lastPage,
            from,
            to
        };
    }
    
    /**
     * Create new record
     */
    async createRecord(tableId, data) {
        const endpoints = this.apiEndpoints.get(tableId);
        if (!endpoints || !endpoints.create) {
            throw new Error('No create endpoint configured');
        }
        
        try {
            const response = await fetch(endpoints.create, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': this.getCSRFToken()
                },
                body: JSON.stringify(data)
            });
            
            if (!response.ok) {
                const error = await response.json();
                throw new Error(error.message || 'Failed to create record');
            }
            
            const result = await response.json();
            
            // Notify other clients via WebSocket if available
            this.broadcastChange(tableId, 'data_insert', result.data || result);
            
            return result;
            
        } catch (error) {
            console.error('Create request failed:', error);
            throw error;
        }
    }
    
    /**
     * Update existing record
     */
    async updateRecord(tableId, id, data) {
        const endpoints = this.apiEndpoints.get(tableId);
        if (!endpoints || !endpoints.update) {
            throw new Error('No update endpoint configured');
        }
        
        try {
            const response = await fetch(`${endpoints.update}/${id}`, {
                method: 'PATCH',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': this.getCSRFToken()
                },
                body: JSON.stringify(data)
            });
            
            if (!response.ok) {
                const error = await response.json();
                throw new Error(error.message || 'Failed to update record');
            }
            
            const result = await response.json();
            
            // Notify other clients via WebSocket if available
            this.broadcastChange(tableId, 'data_update', result.data || result);
            
            return result;
            
        } catch (error) {
            console.error('Update request failed:', error);
            throw error;
        }
    }
    
    /**
     * Delete record
     */
    async deleteRecord(tableId, id) {
        const endpoints = this.apiEndpoints.get(tableId);
        if (!endpoints || !endpoints.delete) {
            throw new Error('No delete endpoint configured');
        }
        
        try {
            const response = await fetch(`${endpoints.delete}/${id}`, {
                method: 'DELETE',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': this.getCSRFToken()
                }
            });
            
            if (!response.ok) {
                const error = await response.json();
                throw new Error(error.message || 'Failed to delete record');
            }
            
            // Notify other clients via WebSocket if available
            this.broadcastChange(tableId, 'data_delete', { id });
            
            return { success: true };
            
        } catch (error) {
            console.error('Delete request failed:', error);
            throw error;
        }
    }
    
    /**
     * Bulk operations
     */
    async bulkUpdate(tableId, updates) {
        const promises = updates.map(update => 
            this.updateRecord(tableId, update.id, update.data)
        );
        
        try {
            const results = await Promise.allSettled(promises);
            
            const successful = results.filter(r => r.status === 'fulfilled').length;
            const failed = results.filter(r => r.status === 'rejected').length;
            
            return {
                successful,
                failed,
                results: results.map((result, index) => ({
                    id: updates[index].id,
                    status: result.status,
                    data: result.status === 'fulfilled' ? result.value : null,
                    error: result.status === 'rejected' ? result.reason.message : null
                }))
            };
            
        } catch (error) {
            console.error('Bulk update failed:', error);
            throw error;
        }
    }
    
    async bulkDelete(tableId, ids) {
        const promises = ids.map(id => this.deleteRecord(tableId, id));
        
        try {
            const results = await Promise.allSettled(promises);
            
            const successful = results.filter(r => r.status === 'fulfilled').length;
            const failed = results.filter(r => r.status === 'rejected').length;
            
            return {
                successful,
                failed,
                results: results.map((result, index) => ({
                    id: ids[index],
                    status: result.status,
                    error: result.status === 'rejected' ? result.reason.message : null
                }))
            };
            
        } catch (error) {
            console.error('Bulk delete failed:', error);
            throw error;
        }
    }
    
    /**
     * Export data
     */
    async exportData(tableId, format, options = {}) {
        const endpoints = this.apiEndpoints.get(tableId);
        if (!endpoints) {
            throw new Error('No endpoints configured for export');
        }
        
        try {
            const url = new URL(endpoints.data);
            url.searchParams.append('export', format);
            url.searchParams.append('export_all', options.all ? '1' : '0');
            
            if (options.columns) {
                url.searchParams.append('columns', options.columns.join(','));
            }
            
            if (options.filters) {
                Object.entries(options.filters).forEach(([key, value]) => {
                    url.searchParams.append(`filter[${key}]`, value);
                });
            }
            
            const response = await fetch(url.toString(), {
                method: 'GET',
                headers: {
                    'Accept': this.getAcceptHeader(format),
                    'X-CSRF-TOKEN': this.getCSRFToken()
                }
            });
            
            if (!response.ok) {
                throw new Error(`Export failed: ${response.status} ${response.statusText}`);
            }
            
            // Handle different response types
            if (format === 'json') {
                return await response.json();
            } else {
                const blob = await response.blob();
                this.downloadBlob(blob, this.getFilename(tableId, format));
                return { success: true };
            }
            
        } catch (error) {
            console.error('Export failed:', error);
            throw error;
        }
    }
    
    /**
     * Data table operations
     */
    updateTableData(tableId, data) {
        const table = this.tables.get(tableId);
        if (table && table.instance) {
            table.instance.updateDataItem(data);
        }
    }
    
    insertTableData(tableId, data) {
        const table = this.tables.get(tableId);
        if (table && table.instance) {
            table.instance.insertDataItem(data);
        }
    }
    
    deleteTableData(tableId, id) {
        const table = this.tables.get(tableId);
        if (table && table.instance) {
            table.instance.deleteDataItem(id);
        }
    }
    
    bulkUpdateTableData(tableId, updates) {
        const table = this.tables.get(tableId);
        if (table && table.instance) {
            updates.forEach(update => {
                table.instance.updateDataItem(update);
            });
        }
    }
    
    refreshTableData(tableId) {
        const table = this.tables.get(tableId);
        if (table && table.instance) {
            table.instance.refreshData();
        }
    }
    
    handleSchemaChange(tableId, schema) {
        const table = this.tables.get(tableId);
        if (table && table.instance) {
            // Update columns if schema changed
            table.instance.columns = schema.columns || table.instance.columns;
            table.instance.initializeColumns();
        }
    }
    
    /**
     * Broadcast changes to other clients
     */
    broadcastChange(tableId, type, data) {
        const ws = this.websockets.get(tableId);
        if (ws && ws.readyState === WebSocket.OPEN) {
            ws.send(JSON.stringify({
                type,
                table: tableId,
                data,
                timestamp: Date.now()
            }));
        }
    }
    
    /**
     * Utility methods
     */
    getCSRFToken() {
        return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    }
    
    getAcceptHeader(format) {
        const headers = {
            'csv': 'text/csv',
            'json': 'application/json',
            'excel': 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'pdf': 'application/pdf'
        };
        
        return headers[format] || 'application/json';
    }
    
    getFilename(tableId, format) {
        const timestamp = new Date().toISOString().slice(0, 19).replace(/:/g, '-');
        return `${tableId}-export-${timestamp}.${format}`;
    }
    
    downloadBlob(blob, filename) {
        const url = URL.createObjectURL(blob);
        const link = document.createElement('a');
        
        link.href = url;
        link.download = filename;
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        URL.revokeObjectURL(url);
    }
    
    /**
     * Advanced filtering
     */
    buildFilterQuery(filters) {
        const query = {};
        
        Object.entries(filters).forEach(([column, filter]) => {
            if (!filter || filter.trim() === '') return;
            
            // Parse filter operators
            if (filter.startsWith('>=')) {
                query[`${column}[gte]`] = filter.slice(2).trim();
            } else if (filter.startsWith('<=')) {
                query[`${column}[lte]`] = filter.slice(2).trim();
            } else if (filter.startsWith('>')) {
                query[`${column}[gt]`] = filter.slice(1).trim();
            } else if (filter.startsWith('<')) {
                query[`${column}[lt]`] = filter.slice(1).trim();
            } else if (filter.startsWith('=')) {
                query[`${column}[eq]`] = filter.slice(1).trim();
            } else if (filter.startsWith('!=')) {
                query[`${column}[ne]`] = filter.slice(2).trim();
            } else if (filter.includes('|')) {
                query[`${column}[in]`] = filter.split('|').map(v => v.trim());
            } else if (filter.includes('..')) {
                const [min, max] = filter.split('..').map(v => v.trim());
                query[`${column}[range]`] = `${min},${max}`;
            } else {
                query[`${column}[like]`] = filter.trim();
            }
        });
        
        return query;
    }
    
    /**
     * Cleanup
     */
    cleanup(tableId) {
        // Close WebSocket connection
        const ws = this.websockets.get(tableId);
        if (ws) {
            ws.close();
            this.websockets.delete(tableId);
        }
        
        // Remove table reference
        this.tables.delete(tableId);
        this.apiEndpoints.delete(tableId);
        
        console.log(`🧹 Cleaned up table ${tableId}`);
    }
    
    cleanupAll() {
        this.websockets.forEach((ws, tableId) => {
            ws.close();
        });
        
        this.tables.clear();
        this.websockets.clear();
        this.apiEndpoints.clear();
        
        console.log('🧹 Cleaned up all data tables');
    }
}

// Create global service instance
window.dataTablesService = new DataTablesService();

// Auto-cleanup on page unload
window.addEventListener('beforeunload', () => {
    window.dataTablesService.cleanupAll();
});

console.log('✅ Data Tables Service initialized');
