{{-- Advanced Layout System Demo --}}
<div x-data="advancedLayoutDemo" x-init="init()" class="bg-white dark:bg-gray-900 min-h-screen">
    {{-- Demo Controls --}}
    <div class="bg-gray-50 dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700 p-4">
        <div class="max-w-7xl mx-auto">
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between">
                <div class="mb-4 lg:mb-0">
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Advanced Layout System</h1>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                        Flexible CSS Grid-based layouts with responsive breakpoints and dynamic switching
                    </p>
                </div>

                {{-- Layout Controls --}}
                <div class="flex flex-wrap items-center gap-3">
                    {{-- Layout Selector --}}
                    <div class="flex items-center space-x-2">
                        <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Layout:</label>
                        <select 
                            x-model="currentLayout" 
                            @change="switchLayout(currentLayout)"
                            class="bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-md px-3 py-1 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 dark:text-white"
                        >
                            <template x-for="layout in availableLayouts" :key="layout.type">
                                <option :value="layout.type" x-text="layout.name"></option>
                            </template>
                        </select>
                    </div>

                    {{-- Customization Toggle --}}
                    <button
                        @click="isCustomizing = !isCustomizing"
                        :class="isCustomizing ? 'bg-blue-600' : 'bg-gray-600'"
                        class="text-white px-3 py-1 rounded-md text-sm font-medium transition-colors duration-200 hover:opacity-90"
                    >
                        🎨 Customize
                    </button>

                    {{-- Sidebar Toggle --}}
                    <button
                        @click="toggleSidebar()"
                        class="bg-green-600 hover:bg-green-700 text-white px-3 py-1 rounded-md text-sm font-medium transition-colors duration-200"
                    >
                        📱 Toggle Sidebar
                    </button>

                    {{-- Reset Button --}}
                    <button
                        @click="resetCustomizations()"
                        class="bg-red-600 hover:bg-red-700 text-white px-3 py-1 rounded-md text-sm font-medium transition-colors duration-200"
                    >
                        🔄 Reset
                    </button>
                </div>
            </div>

            {{-- Customization Panel --}}
            <div x-show="isCustomizing" x-transition class="mt-4 p-4 bg-blue-50 dark:bg-blue-900 rounded-lg">
                <h3 class="text-lg font-semibold text-blue-900 dark:text-blue-100 mb-3">Layout Customization</h3>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-blue-800 dark:text-blue-200 mb-1">Grid Columns</label>
                        <input
                            type="text"
                            x-model="customizations.columns"
                            placeholder="e.g., 250px 1fr 300px"
                            class="block w-full px-3 py-2 border border-blue-300 dark:border-blue-600 rounded-md text-sm dark:bg-blue-800 dark:text-blue-100"
                        >
                        <p class="text-xs text-blue-600 dark:text-blue-300 mt-1">CSS Grid template columns</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-blue-800 dark:text-blue-200 mb-1">Grid Rows</label>
                        <input
                            type="text"
                            x-model="customizations.rows"
                            placeholder="e.g., auto 1fr auto"
                            class="block w-full px-3 py-2 border border-blue-300 dark:border-blue-600 rounded-md text-sm dark:bg-blue-800 dark:text-blue-100"
                        >
                        <p class="text-xs text-blue-600 dark:text-blue-300 mt-1">CSS Grid template rows</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-blue-800 dark:text-blue-200 mb-1">Grid Gap</label>
                        <input
                            type="text"
                            x-model="customizations.gap"
                            placeholder="e.g., 1rem 0.5rem"
                            class="block w-full px-3 py-2 border border-blue-300 dark:border-blue-600 rounded-md text-sm dark:bg-blue-800 dark:text-blue-100"
                        >
                        <p class="text-xs text-blue-600 dark:text-blue-300 mt-1">Grid gap (row column)</p>
                    </div>
                </div>
                <div class="flex justify-end mt-4 space-x-2">
                    <button
                        @click="isCustomizing = false"
                        class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-md text-sm font-medium transition-colors duration-200"
                    >
                        Cancel
                    </button>
                    <button
                        @click="applyCustomizations()"
                        class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md text-sm font-medium transition-colors duration-200"
                    >
                        Apply Changes
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Main Layout Container --}}
    <div 
        id="demo-layout-container" 
        :data-layout="currentLayout"
        class="advanced-layout-demo"
        style="min-height: calc(100vh - 120px);"
    >
        {{-- Header Area --}}
        <header 
            data-grid-area="header" 
            class="bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700 px-6 py-4 flex items-center justify-between"
        >
            <div class="flex items-center space-x-4">
                <div class="flex items-center space-x-2">
                    <div class="w-8 h-8 bg-blue-600 rounded-lg flex items-center justify-center">
                        <span class="text-white font-bold text-sm">LP</span>
                    </div>
                    <span class="font-semibold text-gray-900 dark:text-white">Layout Pro</span>
                </div>
                <nav class="hidden md:flex space-x-6">
                    <a href="#" class="text-gray-600 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white">Dashboard</a>
                    <a href="#" class="text-gray-600 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white">Layouts</a>
                    <a href="#" class="text-gray-600 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white">Components</a>
                </nav>
            </div>
            <div class="flex items-center space-x-3">
                <button class="text-gray-600 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white">🔔</button>
                <button class="text-gray-600 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white">⚙️</button>
                <div class="w-8 h-8 bg-gray-300 rounded-full"></div>
            </div>
        </header>

        {{-- Sidebar/Navigation Area --}}
        <aside 
            data-grid-area="sidebar" 
            :class="sidebarCollapsed ? 'collapsed' : ''"
            class="bg-gray-50 dark:bg-gray-800 border-r border-gray-200 dark:border-gray-700 p-4"
        >
            <div class="space-y-2">
                <h3 class="text-sm font-semibold text-gray-900 dark:text-white uppercase tracking-wider mb-3">Navigation</h3>
                <template x-for="item in ['Dashboard', 'Analytics', 'Users', 'Orders', 'Settings']" :key="item">
                    <a 
                        href="#" 
                        @click.prevent="setActiveNavItem(item)"
                        :class="activeNavItem === item ? 'bg-blue-100 dark:bg-blue-900 text-blue-900 dark:text-blue-100' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700'"
                        class="block px-3 py-2 rounded-md text-sm font-medium transition-colors duration-200"
                        x-text="item"
                    ></a>
                </template>
            </div>
            
            <div class="mt-8 pt-4 border-t border-gray-200 dark:border-gray-600">
                <h4 class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-2">Tools</h4>
                <div class="space-y-1">
                    <a href="#" class="text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white text-sm block">Layout Builder</a>
                    <a href="#" class="text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white text-sm block">Theme Editor</a>
                    <a href="#" class="text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white text-sm block">Component Library</a>
                </div>
            </div>
        </aside>

        {{-- Toolbar Area (Dashboard layout) --}}
        <div 
            data-grid-area="toolbar"
            class="bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700 px-6 py-3 flex items-center justify-between"
            x-show="currentLayout === 'dashboard'"
        >
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Dashboard</h2>
            <div class="flex items-center space-x-2">
                <button class="bg-blue-600 hover:bg-blue-700 text-white px-3 py-1 rounded text-sm">New</button>
                <button class="bg-gray-500 hover:bg-gray-600 text-white px-3 py-1 rounded text-sm">Export</button>
            </div>
        </div>

        {{-- Stats Area (Dashboard layout) --}}
        <div 
            data-grid-area="stats"
            class="bg-white dark:bg-gray-800 p-6"
            x-show="currentLayout === 'dashboard'"
        >
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Statistics</h3>
            <div class="grid grid-cols-2 gap-4">
                <div class="bg-blue-50 dark:bg-blue-900 p-4 rounded-lg">
                    <div class="text-2xl font-bold text-blue-600 dark:text-blue-400">1,234</div>
                    <div class="text-sm text-blue-800 dark:text-blue-200">Total Users</div>
                </div>
                <div class="bg-green-50 dark:bg-green-900 p-4 rounded-lg">
                    <div class="text-2xl font-bold text-green-600 dark:text-green-400">$5,678</div>
                    <div class="text-sm text-green-800 dark:text-green-200">Revenue</div>
                </div>
            </div>
        </div>

        {{-- Main Content Area --}}
        <main 
            data-grid-area="content" 
            class="bg-white dark:bg-gray-800 p-6"
        >
            <div class="max-w-4xl">
                <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-4">Main Content Area</h2>
                
                <div class="space-y-6">
                    {{-- Layout Information --}}
                    <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
                        <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-3">Current Layout: <span x-text="currentLayout" class="font-mono bg-blue-100 dark:bg-blue-900 px-2 py-1 rounded"></span></h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <h4 class="font-medium text-gray-700 dark:text-gray-300 mb-2">Features:</h4>
                                <ul class="text-sm text-gray-600 dark:text-gray-400 space-y-1">
                                    <li>✅ CSS Grid-based layout system</li>
                                    <li>✅ Responsive breakpoint management</li>
                                    <li>✅ Dynamic layout switching</li>
                                    <li>✅ Customizable grid areas</li>
                                    <li>✅ Sticky elements support</li>
                                    <li>✅ Layout persistence</li>
                                </ul>
                            </div>
                            <div>
                                <h4 class="font-medium text-gray-700 dark:text-gray-300 mb-2">Available Layouts:</h4>
                                <ul class="text-sm text-gray-600 dark:text-gray-400 space-y-1">
                                    <li>• Application Layout (header, sidebar, main, aside, footer)</li>
                                    <li>• Dashboard Layout (toolbar, stats, content, widgets)</li>
                                    <li>• Admin Panel Layout (header, nav, content, aside)</li>
                                    <li>• Documentation Layout (header, sidebar, content)</li>
                                    <li>• Blog Layout (header, sidebar, content, related, footer)</li>
                                    <li>• Split Layout (left, right)</li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    {{-- Sample Content --}}
                    <div class="space-y-4">
                        <div class="bg-white dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-lg p-4">
                            <h4 class="font-medium text-gray-900 dark:text-white mb-2">Sample Content Block 1</h4>
                            <p class="text-gray-600 dark:text-gray-400 text-sm">
                                This is sample content to demonstrate how the layout system handles different types of content.
                                The layout automatically adjusts based on the selected template and current breakpoint.
                            </p>
                        </div>
                        
                        <div class="bg-white dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-lg p-4">
                            <h4 class="font-medium text-gray-900 dark:text-white mb-2">Sample Content Block 2</h4>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                <div class="bg-gray-50 dark:bg-gray-600 p-3 rounded">
                                    <div class="font-medium text-gray-900 dark:text-white text-sm">Metric 1</div>
                                    <div class="text-lg font-bold text-blue-600 dark:text-blue-400">42</div>
                                </div>
                                <div class="bg-gray-50 dark:bg-gray-600 p-3 rounded">
                                    <div class="font-medium text-gray-900 dark:text-white text-sm">Metric 2</div>
                                    <div class="text-lg font-bold text-green-600 dark:text-green-400">89%</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>

        {{-- Widgets/Aside Area --}}
        <aside 
            data-grid-area="widgets"
            class="bg-gray-50 dark:bg-gray-800 p-4"
            x-show="['dashboard', 'app'].includes(currentLayout)"
        >
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Widgets</h3>
            <div class="space-y-4">
                <div class="bg-white dark:bg-gray-700 p-4 rounded-lg border border-gray-200 dark:border-gray-600">
                    <h4 class="font-medium text-gray-900 dark:text-white mb-2">Quick Stats</h4>
                    <div class="space-y-2">
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-600 dark:text-gray-400">Active Users</span>
                            <span class="font-medium text-gray-900 dark:text-white">156</span>
                        </div>
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-600 dark:text-gray-400">Sessions</span>
                            <span class="font-medium text-gray-900 dark:text-white">2,341</span>
                        </div>
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-600 dark:text-gray-400">Bounce Rate</span>
                            <span class="font-medium text-gray-900 dark:text-white">23.4%</span>
                        </div>
                    </div>
                </div>
                
                <div class="bg-white dark:bg-gray-700 p-4 rounded-lg border border-gray-200 dark:border-gray-600">
                    <h4 class="font-medium text-gray-900 dark:text-white mb-2">Recent Activity</h4>
                    <div class="space-y-2">
                        <div class="text-sm text-gray-600 dark:text-gray-400">
                            <span class="font-medium">User123</span> logged in
                        </div>
                        <div class="text-sm text-gray-600 dark:text-gray-400">
                            <span class="font-medium">Order #456</span> completed
                        </div>
                        <div class="text-sm text-gray-600 dark:text-gray-400">
                            <span class="font-medium">Payment</span> received
                        </div>
                    </div>
                </div>
            </div>
        </aside>

        {{-- Aside Area (App/Admin layout) --}}
        <aside 
            data-grid-area="aside"
            class="bg-gray-50 dark:bg-gray-800 p-4"
            x-show="['app', 'admin'].includes(currentLayout)"
        >
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Sidebar</h3>
            <div class="space-y-3">
                <div class="bg-white dark:bg-gray-700 p-3 rounded border border-gray-200 dark:border-gray-600">
                    <div class="font-medium text-gray-900 dark:text-white text-sm mb-1">Status</div>
                    <div class="flex items-center space-x-2">
                        <div class="w-2 h-2 bg-green-500 rounded-full"></div>
                        <span class="text-sm text-gray-600 dark:text-gray-400">All systems operational</span>
                    </div>
                </div>
                
                <div class="bg-white dark:bg-gray-700 p-3 rounded border border-gray-200 dark:border-gray-600">
                    <div class="font-medium text-gray-900 dark:text-white text-sm mb-2">Quick Actions</div>
                    <div class="space-y-1">
                        <button class="w-full text-left text-sm text-blue-600 dark:text-blue-400 hover:underline">Create New</button>
                        <button class="w-full text-left text-sm text-blue-600 dark:text-blue-400 hover:underline">Import Data</button>
                        <button class="w-full text-left text-sm text-blue-600 dark:text-blue-400 hover:underline">Export Report</button>
                    </div>
                </div>
            </div>
        </aside>

        {{-- Related Area (Blog layout) --}}
        <aside 
            data-grid-area="related"
            class="bg-gray-50 dark:bg-gray-800 p-4"
            x-show="currentLayout === 'blog'"
        >
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Related Articles</h3>
            <div class="space-y-3">
                <div class="bg-white dark:bg-gray-700 p-3 rounded border border-gray-200 dark:border-gray-600">
                    <h4 class="font-medium text-gray-900 dark:text-white text-sm mb-1">Getting Started with Layouts</h4>
                    <p class="text-xs text-gray-600 dark:text-gray-400">Learn the basics of our layout system...</p>
                </div>
                
                <div class="bg-white dark:bg-gray-700 p-3 rounded border border-gray-200 dark:border-gray-600">
                    <h4 class="font-medium text-gray-900 dark:text-white text-sm mb-1">Advanced Customization</h4>
                    <p class="text-xs text-gray-600 dark:text-gray-400">Customize layouts to fit your needs...</p>
                </div>
                
                <div class="bg-white dark:bg-gray-700 p-3 rounded border border-gray-200 dark:border-gray-600">
                    <h4 class="font-medium text-gray-900 dark:text-white text-sm mb-1">Responsive Design Tips</h4>
                    <p class="text-xs text-gray-600 dark:text-gray-400">Make your layouts work on all devices...</p>
                </div>
            </div>
        </aside>

        {{-- Navigation Area (Admin layout) --}}
        <nav 
            data-grid-area="nav"
            class="bg-gray-100 dark:bg-gray-700 p-4"
            x-show="currentLayout === 'admin'"
        >
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Admin Navigation</h3>
            <div class="space-y-2">
                <a href="#" class="block px-3 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600 rounded">🏠 Dashboard</a>
                <a href="#" class="block px-3 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600 rounded">👥 Users</a>
                <a href="#" class="block px-3 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600 rounded">📦 Orders</a>
                <a href="#" class="block px-3 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600 rounded">🛠️ Settings</a>
                <a href="#" class="block px-3 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600 rounded">📊 Analytics</a>
            </div>
        </nav>

        {{-- Left Panel (Split layout) --}}
        <div 
            data-grid-area="left"
            class="bg-blue-50 dark:bg-blue-900 p-6"
            x-show="currentLayout === 'split'"
        >
            <h3 class="text-xl font-semibold text-blue-900 dark:text-blue-100 mb-4">Left Panel</h3>
            <div class="space-y-4">
                <p class="text-blue-800 dark:text-blue-200">This is the left side of a split layout. Perfect for comparisons, forms, or any side-by-side content.</p>
                <div class="bg-white dark:bg-blue-800 p-4 rounded-lg">
                    <h4 class="font-medium text-blue-900 dark:text-blue-100 mb-2">Features</h4>
                    <ul class="text-sm text-blue-700 dark:text-blue-200 space-y-1">
                        <li>• Equal width columns</li>
                        <li>• Responsive stacking</li>
                        <li>• Customizable gap</li>
                    </ul>
                </div>
            </div>
        </div>

        {{-- Right Panel (Split layout) --}}
        <div 
            data-grid-area="right"
            class="bg-green-50 dark:bg-green-900 p-6"
            x-show="currentLayout === 'split'"
        >
            <h3 class="text-xl font-semibold text-green-900 dark:text-green-100 mb-4">Right Panel</h3>
            <div class="space-y-4">
                <p class="text-green-800 dark:text-green-200">This is the right side of the split layout. Great for displaying related information or alternative views.</p>
                <div class="bg-white dark:bg-green-800 p-4 rounded-lg">
                    <h4 class="font-medium text-green-900 dark:text-green-100 mb-2">Use Cases</h4>
                    <ul class="text-sm text-green-700 dark:text-green-200 space-y-1">
                        <li>• Before/After comparisons</li>
                        <li>• Form and preview</li>
                        <li>• Code and output</li>
                    </ul>
                </div>
            </div>
        </div>

        {{-- Footer Area --}}
        <footer 
            data-grid-area="footer" 
            class="bg-gray-100 dark:bg-gray-700 border-t border-gray-200 dark:border-gray-600 px-6 py-4"
            x-show="['app', 'blog'].includes(currentLayout)"
        >
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between">
                <p class="text-sm text-gray-600 dark:text-gray-400">
                    © 2024 Layout Pro. Advanced Layout System Demo.
                </p>
                <div class="flex items-center space-x-4 mt-2 sm:mt-0">
                    <a href="#" class="text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white">Privacy</a>
                    <a href="#" class="text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white">Terms</a>
                    <a href="#" class="text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white">Support</a>
                </div>
            </div>
        </footer>
    </div>

    {{-- Layout Status Indicator --}}
    <div class="fixed bottom-4 right-4 z-50">
        <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-600 rounded-lg p-3 shadow-lg">
            <div class="flex items-center space-x-2">
                <div class="w-2 h-2 rounded-full bg-green-500"></div>
                <span class="text-xs text-gray-600 dark:text-gray-400">Layout System Active</span>
            </div>
            <div class="text-xs text-gray-500 dark:text-gray-500 mt-1">
                Current: <span x-text="currentLayout" class="font-mono"></span>
            </div>
        </div>
    </div>

    {{-- Keyboard Shortcuts Info --}}
    <div class="fixed bottom-4 left-4 z-50">
        <div class="bg-gray-800 text-white rounded-lg p-3 text-xs">
            <div class="font-medium mb-1">Keyboard Shortcuts:</div>
            <div>Ctrl/Cmd + [ : Toggle left sidebar</div>
            <div>Ctrl/Cmd + ] : Toggle right sidebar</div>
        </div>
    </div>
</div>
