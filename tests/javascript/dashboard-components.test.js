/**
 * Dashboard Components Test Suite
 * Tests for interactive dashboard functionality
 */

describe('Dashboard Components', () => {
    let container;
    
    beforeEach(() => {
        container = document.createElement('div');
        document.body.appendChild(container);
        
        // Mock Chart.js
        global.Chart = {
            register: jest.fn(),
            Chart: jest.fn().mockImplementation(() => ({
                destroy: jest.fn(),
                update: jest.fn(),
                resize: jest.fn(),
                data: { datasets: [] },
                options: {}
            }))
        };
    });
    
    afterEach(() => {
        document.body.removeChild(container);
    });
    
    describe('Real-time Metrics Dashboard', () => {
        let dashboard;
        
        beforeEach(() => {
            const config = {
                wsUrl: 'ws://localhost:6001',
                refreshInterval: 1000,
                metricsEndpoint: '/api/metrics'
            };
            
            dashboard = window.metricsDisplay(config);
            
            // Mock metrics API response
            testUtils.mockApiResponse('/api/metrics', {
                active_users: 1250,
                total_orders: 5680,
                revenue: 125000.50,
                server_status: 'online',
                bandwidth_usage: 75.5
            });
        });
        
        test('should initialize with config', () => {
            expect(dashboard.wsUrl).toBe('ws://localhost:6001');
            expect(dashboard.refreshInterval).toBe(1000);
            expect(dashboard.connected).toBe(false);
        });
        
        test('should fetch initial metrics', async () => {
            await dashboard.fetchMetrics();
            
            expect(dashboard.metrics.active_users).toBe(1250);
            expect(dashboard.metrics.total_orders).toBe(5680);
            expect(dashboard.metrics.revenue).toBe(125000.50);
        });
        
        test('should format currency values', () => {
            expect(dashboard.formatCurrency(125000.50)).toBe('$125,000.50');
            expect(dashboard.formatCurrency(0)).toBe('$0.00');
        });
        
        test('should format numbers with commas', () => {
            expect(dashboard.formatNumber(1250)).toBe('1,250');
            expect(dashboard.formatNumber(1250000)).toBe('1,250,000');
        });
        
        test('should calculate percentage change', () => {
            expect(dashboard.calculatePercentageChange(100, 120)).toBe(20);
            expect(dashboard.calculatePercentageChange(100, 80)).toBe(-20);
            expect(dashboard.calculatePercentageChange(0, 100)).toBe(0);
        });
        
        test('should handle WebSocket connection', () => {
            const mockWS = testUtils.createMockWebSocket();
            dashboard.connectWebSocket(mockWS);
            
            mockWS.triggerOpen();
            expect(dashboard.connected).toBe(true);
            
            mockWS.triggerClose();
            expect(dashboard.connected).toBe(false);
        });
        
        test('should process real-time updates', () => {
            const update = {
                type: 'metric_update',
                data: { active_users: 1300 }
            };
            
            dashboard.processUpdate(update);
            
            expect(dashboard.metrics.active_users).toBe(1300);
        });
    });
    
    describe('Interactive Charts', () => {
        let chartComponent;
        
        beforeEach(() => {
            chartComponent = window.interactiveChart({
                type: 'line',
                data: {
                    labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May'],
                    datasets: [{
                        label: 'Sales',
                        data: [100, 150, 200, 180, 220],
                        borderColor: 'rgb(59, 130, 246)',
                        backgroundColor: 'rgba(59, 130, 246, 0.1)'
                    }]
                }
            });
        });
        
        test('should initialize chart with data', () => {
            expect(chartComponent.chartData.labels.length).toBe(5);
            expect(chartComponent.chartData.datasets[0].data.length).toBe(5);
        });
        
        test('should update chart data', () => {
            const newData = [120, 160, 210, 190, 240];
            chartComponent.updateData(newData);
            
            expect(chartComponent.chartData.datasets[0].data).toEqual(newData);
        });
        
        test('should add data point', () => {
            chartComponent.addDataPoint('Jun', 250);
            
            expect(chartComponent.chartData.labels).toContain('Jun');
            expect(chartComponent.chartData.datasets[0].data).toContain(250);
        });
        
        test('should remove data point', () => {
            chartComponent.removeDataPoint(0);
            
            expect(chartComponent.chartData.labels.length).toBe(4);
            expect(chartComponent.chartData.datasets[0].data.length).toBe(4);
        });
        
        test('should toggle chart type', () => {
            chartComponent.toggleChartType('bar');
            
            expect(chartComponent.chartType).toBe('bar');
        });
        
        test('should export chart data', () => {
            const exported = chartComponent.exportData();
            
            expect(exported.labels).toEqual(chartComponent.chartData.labels);
            expect(exported.datasets).toEqual(chartComponent.chartData.datasets);
        });
    });
    
    describe('Activity Feed', () => {
        let activityFeed;
        
        beforeEach(() => {
            activityFeed = window.activityFeed({
                endpoint: '/api/activities',
                pollInterval: 5000,
                maxItems: 50
            });
            
            // Mock activities API response
            testUtils.mockApiResponse('/api/activities', [
                {
                    id: 1,
                    type: 'user_registered',
                    message: 'New user registered: john@example.com',
                    timestamp: '2023-01-01T10:00:00Z',
                    user: { name: 'John Doe', avatar: '/avatars/john.jpg' }
                },
                {
                    id: 2,
                    type: 'order_created',
                    message: 'New order created: #1234',
                    timestamp: '2023-01-01T10:05:00Z',
                    user: { name: 'Jane Smith', avatar: '/avatars/jane.jpg' }
                }
            ]);
        });
        
        test('should load activities', async () => {
            await activityFeed.loadActivities();
            
            expect(activityFeed.activities.length).toBe(2);
            expect(activityFeed.activities[0].type).toBe('user_registered');
        });
        
        test('should add new activity', () => {
            const newActivity = {
                id: 3,
                type: 'payment_received',
                message: 'Payment received: $99.99',
                timestamp: '2023-01-01T10:10:00Z'
            };
            
            activityFeed.addActivity(newActivity);
            
            expect(activityFeed.activities.length).toBe(1);
            expect(activityFeed.activities[0]).toEqual(newActivity);
        });
        
        test('should format activity timestamp', () => {
            const timestamp = '2023-01-01T10:00:00Z';
            const formatted = activityFeed.formatTimestamp(timestamp);
            
            expect(formatted).toContain('ago');
        });
        
        test('should filter activities by type', () => {
            activityFeed.activities = [
                { type: 'user_registered', message: 'User registered' },
                { type: 'order_created', message: 'Order created' },
                { type: 'user_registered', message: 'Another user registered' }
            ];
            
            activityFeed.filterByType('user_registered');
            
            expect(activityFeed.filteredActivities.length).toBe(2);
            expect(activityFeed.filteredActivities.every(a => a.type === 'user_registered')).toBe(true);
        });
        
        test('should respect max items limit', () => {
            activityFeed.maxItems = 2;
            
            for (let i = 1; i <= 5; i++) {
                activityFeed.addActivity({
                    id: i,
                    type: 'test',
                    message: `Test ${i}`,
                    timestamp: new Date().toISOString()
                });
            }
            
            expect(activityFeed.activities.length).toBe(2);
        });
    });
    
    describe('Stats Cards', () => {
        let statsCard;
        
        beforeEach(() => {
            statsCard = window.statsCard({
                value: 1250,
                label: 'Active Users',
                trend: 'up',
                percentage: 15.5,
                icon: 'users',
                color: 'blue'
            });
        });
        
        test('should initialize with config', () => {
            expect(statsCard.value).toBe(1250);
            expect(statsCard.label).toBe('Active Users');
            expect(statsCard.trend).toBe('up');
            expect(statsCard.percentage).toBe(15.5);
        });
        
        test('should format large numbers', () => {
            statsCard.value = 1250000;
            expect(statsCard.formattedValue).toBe('1.25M');
            
            statsCard.value = 1250;
            expect(statsCard.formattedValue).toBe('1.25K');
            
            statsCard.value = 125;
            expect(statsCard.formattedValue).toBe('125');
        });
        
        test('should determine trend color', () => {
            statsCard.trend = 'up';
            expect(statsCard.trendColor).toBe('text-green-600');
            
            statsCard.trend = 'down';
            expect(statsCard.trendColor).toBe('text-red-600');
            
            statsCard.trend = 'neutral';
            expect(statsCard.trendColor).toBe('text-gray-600');
        });
        
        test('should update value with animation', () => {
            statsCard.updateValue(1500);
            
            expect(statsCard.isAnimating).toBe(true);
            expect(statsCard.targetValue).toBe(1500);
        });
        
        test('should calculate growth percentage', () => {
            const growth = statsCard.calculateGrowth(1000, 1150);
            expect(growth).toBe(15);
        });
    });
    
    describe('Notification System', () => {
        let notifications;
        
        beforeEach(() => {
            notifications = window.notificationSystem({
                position: 'top-right',
                autoClose: true,
                autoCloseDelay: 5000,
                maxNotifications: 5
            });
        });
        
        test('should add notification', () => {
            notifications.add('success', 'Operation completed successfully');
            
            expect(notifications.notifications.length).toBe(1);
            expect(notifications.notifications[0].type).toBe('success');
            expect(notifications.notifications[0].message).toBe('Operation completed successfully');
        });
        
        test('should remove notification', () => {
            notifications.add('info', 'Test notification');
            const id = notifications.notifications[0].id;
            
            notifications.remove(id);
            
            expect(notifications.notifications.length).toBe(0);
        });
        
        test('should respect max notifications limit', () => {
            for (let i = 1; i <= 7; i++) {
                notifications.add('info', `Notification ${i}`);
            }
            
            expect(notifications.notifications.length).toBe(5);
        });
        
        test('should auto-close notifications', (done) => {
            notifications.autoCloseDelay = 100;
            notifications.add('info', 'Auto-close test');
            
            setTimeout(() => {
                expect(notifications.notifications.length).toBe(0);
                done();
            }, 150);
        });
        
        test('should clear all notifications', () => {
            notifications.add('info', 'Test 1');
            notifications.add('warning', 'Test 2');
            notifications.add('error', 'Test 3');
            
            notifications.clearAll();
            
            expect(notifications.notifications.length).toBe(0);
        });
        
        test('should group similar notifications', () => {
            notifications.add('error', 'Network error');
            notifications.add('error', 'Network error');
            notifications.add('error', 'Network error');
            
            expect(notifications.notifications.length).toBe(1);
            expect(notifications.notifications[0].count).toBe(3);
        });
    });
    
    describe('Search and Filter Bar', () => {
        let searchFilter;
        
        beforeEach(() => {
            searchFilter = window.searchFilterBar({
                searchPlaceholder: 'Search...',
                filters: [
                    { key: 'status', label: 'Status', options: ['active', 'inactive', 'pending'] },
                    { key: 'type', label: 'Type', options: ['user', 'admin', 'moderator'] },
                    { key: 'date', label: 'Date Range', type: 'date-range' }
                ]
            });
        });
        
        test('should initialize with filters', () => {
            expect(searchFilter.filters.length).toBe(3);
            expect(searchFilter.searchQuery).toBe('');
        });
        
        test('should update search query', () => {
            searchFilter.updateSearch('test query');
            
            expect(searchFilter.searchQuery).toBe('test query');
        });
        
        test('should add filter', () => {
            searchFilter.addFilter('status', 'active');
            
            expect(searchFilter.activeFilters.status).toBe('active');
        });
        
        test('should remove filter', () => {
            searchFilter.addFilter('status', 'active');
            searchFilter.removeFilter('status');
            
            expect(searchFilter.activeFilters.status).toBeUndefined();
        });
        
        test('should clear all filters', () => {
            searchFilter.addFilter('status', 'active');
            searchFilter.addFilter('type', 'user');
            searchFilter.clearAllFilters();
            
            expect(Object.keys(searchFilter.activeFilters).length).toBe(0);
        });
        
        test('should generate filter query', () => {
            searchFilter.searchQuery = 'john';
            searchFilter.addFilter('status', 'active');
            searchFilter.addFilter('type', 'user');
            
            const query = searchFilter.generateQuery();
            
            expect(query.search).toBe('john');
            expect(query.status).toBe('active');
            expect(query.type).toBe('user');
        });
        
        test('should save and restore state', () => {
            searchFilter.searchQuery = 'test';
            searchFilter.addFilter('status', 'active');
            
            searchFilter.saveState();
            
            searchFilter.searchQuery = '';
            searchFilter.clearAllFilters();
            
            searchFilter.restoreState();
            
            expect(searchFilter.searchQuery).toBe('test');
            expect(searchFilter.activeFilters.status).toBe('active');
        });
    });
});

console.log('✅ Dashboard Components tests loaded');
