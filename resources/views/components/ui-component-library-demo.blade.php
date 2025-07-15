{{-- Custom UI Component Library Demo --}}
<div x-data="uiComponents" x-init="init()" class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-6">
    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-6">
        <div>
            <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Custom UI Component Library</h2>
            <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">Advanced reusable UI components with interactive features</p>
        </div>
        <div class="flex items-center space-x-3 mt-4 sm:mt-0">
            <div class="flex items-center space-x-2">
                <div class="w-3 h-3 rounded-full bg-green-500"></div>
                <span class="text-sm text-gray-600 dark:text-gray-400">Components Active</span>
            </div>
            <button
                @click="showNotification('success')"
                class="bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded-md text-sm font-medium transition-colors duration-200"
            >
                🔔 Test Notification
            </button>
        </div>
    </div>

    {{-- Tab Navigation --}}
    <div class="border-b border-gray-200 dark:border-gray-700 mb-6">
        <nav class="-mb-px flex space-x-8">
            <button
                @click="activeTab = 'buttons'"
                :class="activeTab === 'buttons' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                class="whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm"
                x-data="{ activeTab: 'buttons' }"
                x-init="$parent.activeTab = 'buttons'"
            >
                🔘 Buttons
            </button>
            <button
                @click="activeTab = 'forms'"
                :class="activeTab === 'forms' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                class="whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm"
            >
                📝 Form Inputs
            </button>
            <button
                @click="activeTab = 'modals'"
                :class="activeTab === 'modals' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                class="whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm"
            >
                🪟 Modals
            </button>
            <button
                @click="activeTab = 'tables'"
                :class="activeTab === 'tables' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                class="whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm"
            >
                📊 Tables
            </button>
            <button
                @click="activeTab = 'notifications'"
                :class="activeTab === 'notifications' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                class="whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm"
            >
                🔔 Notifications
            </button>
        </nav>
    </div>

    {{-- Buttons Tab --}}
    <div x-show="activeTab === 'buttons'" class="space-y-6">
        {{-- Button Variants --}}
        <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Button Variants</h3>
            <div class="space-y-4">
                <div class="flex flex-wrap gap-3">
                    <button
                        id="demo-button-primary"
                        data-ui-button
                        data-variant="primary"
                        data-size="medium"
                        @click="testButton('primary')"
                        class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-md font-medium transition-colors duration-200"
                    >
                        Primary Button
                    </button>
                    <button
                        id="demo-button-secondary"
                        data-ui-button
                        data-variant="secondary"
                        data-size="medium"
                        @click="testButton('secondary')"
                        class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-md font-medium transition-colors duration-200"
                    >
                        Secondary Button
                    </button>
                    <button
                        id="demo-button-success"
                        data-ui-button
                        data-variant="success"
                        data-size="medium"
                        @click="testButton('success')"
                        class="bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded-md font-medium transition-colors duration-200"
                    >
                        Success Button
                    </button>
                    <button
                        id="demo-button-danger"
                        data-ui-button
                        data-variant="danger"
                        data-size="medium"
                        @click="testButton('danger')"
                        class="bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded-md font-medium transition-colors duration-200"
                    >
                        Danger Button
                    </button>
                    <button
                        id="demo-button-warning"
                        data-ui-button
                        data-variant="warning"
                        data-size="medium"
                        @click="testButton('warning')"
                        class="bg-yellow-500 hover:bg-yellow-600 text-white px-4 py-2 rounded-md font-medium transition-colors duration-200"
                    >
                        Warning Button
                    </button>
                    <button
                        id="demo-button-outline"
                        data-ui-button
                        data-variant="outline"
                        data-size="medium"
                        @click="testButton('outline')"
                        class="border-2 border-blue-500 text-blue-500 hover:bg-blue-500 hover:text-white px-4 py-2 rounded-md font-medium transition-colors duration-200"
                    >
                        Outline Button
                    </button>
                </div>
                
                {{-- Button Sizes --}}
                <div class="pt-4 border-t border-gray-200 dark:border-gray-600">
                    <h4 class="text-md font-medium text-gray-900 dark:text-white mb-3">Button Sizes</h4>
                    <div class="flex flex-wrap items-center gap-3">
                        <button
                            id="demo-button-small"
                            data-ui-button
                            data-variant="primary"
                            data-size="small"
                            class="bg-blue-500 hover:bg-blue-600 text-white px-2 py-1 text-sm rounded-md font-medium transition-colors duration-200"
                        >
                            Small
                        </button>
                        <button
                            id="demo-button-medium"
                            data-ui-button
                            data-variant="primary"
                            data-size="medium"
                            class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-md font-medium transition-colors duration-200"
                        >
                            Medium
                        </button>
                        <button
                            id="demo-button-large"
                            data-ui-button
                            data-variant="primary"
                            data-size="large"
                            class="bg-blue-500 hover:bg-blue-600 text-white px-6 py-3 text-lg rounded-md font-medium transition-colors duration-200"
                        >
                            Large
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Forms Tab --}}
    <div x-show="activeTab === 'forms'" class="space-y-6">
        <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Advanced Form Components</h3>
            
            <form @submit.prevent="submitForm()" class="space-y-6 max-w-lg">
                {{-- Name Input --}}
                <div id="demo-input-name" data-ui-input data-type="text" data-validation="required">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Full Name *</label>
                    <input
                        type="text"
                        x-model="formData.name"
                        placeholder="Enter your full name"
                        class="block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-colors duration-200 dark:bg-gray-700 dark:text-white"
                    >
                </div>

                {{-- Email Input --}}
                <div id="demo-input-email" data-ui-input data-type="email" data-validation="email">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Email Address *</label>
                    <input
                        type="email"
                        x-model="formData.email"
                        placeholder="Enter your email"
                        class="block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-colors duration-200 dark:bg-gray-700 dark:text-white"
                    >
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">We'll never share your email with anyone else.</p>
                </div>

                {{-- Phone Input --}}
                <div id="demo-input-phone" data-ui-input data-type="tel" data-validation="phone">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Phone Number</label>
                    <input
                        type="tel"
                        x-model="formData.phone"
                        placeholder="(555) 123-4567"
                        class="block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-colors duration-200 dark:bg-gray-700 dark:text-white"
                    >
                </div>

                {{-- Message Textarea --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Message</label>
                    <textarea
                        x-model="formData.message"
                        rows="4"
                        placeholder="Enter your message..."
                        class="block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-colors duration-200 dark:bg-gray-700 dark:text-white"
                    ></textarea>
                </div>

                {{-- Submit Button --}}
                <div class="flex space-x-3">
                    <button
                        type="submit"
                        class="bg-blue-500 hover:bg-blue-600 text-white px-6 py-2 rounded-md font-medium transition-colors duration-200"
                    >
                        Submit Form
                    </button>
                    <button
                        type="button"
                        @click="formData = { name: '', email: '', phone: '', message: '' }"
                        class="bg-gray-500 hover:bg-gray-600 text-white px-6 py-2 rounded-md font-medium transition-colors duration-200"
                    >
                        Reset
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Modals Tab --}}
    <div x-show="activeTab === 'modals'" class="space-y-6">
        <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Modal Components</h3>
            
            <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                <button
                    @click="showModal('demo-modal-small')"
                    class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-md text-sm font-medium transition-colors duration-200"
                >
                    Small Modal
                </button>
                <button
                    @click="showModal('demo-modal-medium')"
                    class="bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded-md text-sm font-medium transition-colors duration-200"
                >
                    Medium Modal
                </button>
                <button
                    @click="showModal('demo-modal-large')"
                    class="bg-purple-500 hover:bg-purple-600 text-white px-4 py-2 rounded-md text-sm font-medium transition-colors duration-200"
                >
                    Large Modal
                </button>
                <button
                    @click="showModal('demo-modal-full')"
                    class="bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded-md text-sm font-medium transition-colors duration-200"
                >
                    Full Modal
                </button>
            </div>
        </div>
    </div>

    {{-- Tables Tab --}}
    <div x-show="activeTab === 'tables'" class="space-y-6">
        <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-4">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Advanced Data Table</h3>
                <div class="flex space-x-2 mt-2 sm:mt-0">
                    <button
                        @click="addTableRow()"
                        class="bg-green-500 hover:bg-green-600 text-white px-3 py-1 rounded text-sm font-medium transition-colors duration-200"
                    >
                        ➕ Add Row
                    </button>
                    <button
                        @click="clearTable()"
                        class="bg-red-500 hover:bg-red-600 text-white px-3 py-1 rounded text-sm font-medium transition-colors duration-200"
                    >
                        🗑️ Clear
                    </button>
                </div>
            </div>

            {{-- Table Filter --}}
            <div class="mb-4">
                <input
                    type="text"
                    data-ui-filter
                    placeholder="Search table..."
                    class="block w-full max-w-sm px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-600 dark:text-white"
                >
            </div>

            {{-- Data Table --}}
            <div class="overflow-x-auto">
                <table
                    id="demo-table"
                    data-ui-table
                    data-sortable="true"
                    data-filterable="true"
                    data-paginated="true"
                    class="min-w-full bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden"
                >
                    <thead class="bg-gray-50 dark:bg-gray-700">
                        <tr>
                            <th data-sortable="id" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-600">
                                ID
                            </th>
                            <th data-sortable="name" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-600">
                                Name
                            </th>
                            <th data-sortable="email" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-600">
                                Email
                            </th>
                            <th data-sortable="role" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-600">
                                Role
                            </th>
                            <th data-sortable="status" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-600">
                                Status
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                        {{-- Table rows will be populated by the UITable component --}}
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Notifications Tab --}}
    <div x-show="activeTab === 'notifications'" class="space-y-6">
        <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Notification System</h3>
            
            <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                <button
                    @click="showNotification('info')"
                    class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-md text-sm font-medium transition-colors duration-200"
                >
                    ℹ️ Info
                </button>
                <button
                    @click="showNotification('success')"
                    class="bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded-md text-sm font-medium transition-colors duration-200"
                >
                    ✅ Success
                </button>
                <button
                    @click="showNotification('warning')"
                    class="bg-yellow-500 hover:bg-yellow-600 text-white px-4 py-2 rounded-md text-sm font-medium transition-colors duration-200"
                >
                    ⚠️ Warning
                </button>
                <button
                    @click="showNotification('error')"
                    class="bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded-md text-sm font-medium transition-colors duration-200"
                >
                    ❌ Error
                </button>
            </div>

            <div class="mt-6 p-4 bg-blue-50 dark:bg-blue-900 rounded-lg">
                <h4 class="text-sm font-medium text-blue-900 dark:text-blue-100 mb-2">Notification Features</h4>
                <ul class="text-sm text-blue-800 dark:text-blue-200 space-y-1">
                    <li>• Auto-dismiss after 5 seconds</li>
                    <li>• Click to manually dismiss</li>
                    <li>• Smooth slide animations</li>
                    <li>• Multiple notification types</li>
                    <li>• Stacked notifications support</li>
                </ul>
            </div>
        </div>
    </div>

    {{-- Sample Modals --}}
    {{-- Small Modal --}}
    <div id="demo-modal-small" data-ui-modal data-size="small" style="display: none;">
        <div class="p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white">Small Modal</h3>
                <button data-ui-close class="text-gray-400 hover:text-gray-600">×</button>
            </div>
            <p class="text-gray-600 dark:text-gray-400 mb-4">This is a small modal dialog. It's perfect for simple confirmations or quick actions.</p>
            <div class="flex justify-end space-x-3">
                <button
                    @click="hideModal('demo-modal-small')"
                    class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-600 border border-gray-300 dark:border-gray-500 rounded-md hover:bg-gray-50 dark:hover:bg-gray-500"
                >
                    Cancel
                </button>
                <button
                    @click="hideModal('demo-modal-small')"
                    class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-md hover:bg-blue-700"
                >
                    Confirm
                </button>
            </div>
        </div>
    </div>

    {{-- Medium Modal --}}
    <div id="demo-modal-medium" data-ui-modal data-size="medium" style="display: none;">
        <div class="p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white">Medium Modal</h3>
                <button data-ui-close class="text-gray-400 hover:text-gray-600">×</button>
            </div>
            <div class="space-y-4">
                <p class="text-gray-600 dark:text-gray-400">This is a medium-sized modal with more content space.</p>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Name</label>
                        <input type="text" class="block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md dark:bg-gray-700 dark:text-white">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Email</label>
                        <input type="email" class="block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md dark:bg-gray-700 dark:text-white">
                    </div>
                </div>
            </div>
            <div class="flex justify-end space-x-3 mt-6">
                <button
                    @click="hideModal('demo-modal-medium')"
                    class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-600 border border-gray-300 dark:border-gray-500 rounded-md hover:bg-gray-50 dark:hover:bg-gray-500"
                >
                    Cancel
                </button>
                <button
                    @click="hideModal('demo-modal-medium')"
                    class="px-4 py-2 text-sm font-medium text-white bg-green-600 rounded-md hover:bg-green-700"
                >
                    Save
                </button>
            </div>
        </div>
    </div>

    {{-- Large Modal --}}
    <div id="demo-modal-large" data-ui-modal data-size="large" style="display: none;">
        <div class="p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white">Large Modal</h3>
                <button data-ui-close class="text-gray-400 hover:text-gray-600">×</button>
            </div>
            <div class="space-y-6">
                <p class="text-gray-600 dark:text-gray-400">This is a large modal suitable for complex forms or detailed content.</p>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="space-y-4">
                        <h4 class="font-medium text-gray-900 dark:text-white">Personal Information</h4>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">First Name</label>
                            <input type="text" class="block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md dark:bg-gray-700 dark:text-white">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Last Name</label>
                            <input type="text" class="block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md dark:bg-gray-700 dark:text-white">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Email</label>
                            <input type="email" class="block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md dark:bg-gray-700 dark:text-white">
                        </div>
                    </div>
                    
                    <div class="space-y-4">
                        <h4 class="font-medium text-gray-900 dark:text-white">Preferences</h4>
                        <div class="space-y-3">
                            <label class="flex items-center">
                                <input type="checkbox" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">Email notifications</span>
                            </label>
                            <label class="flex items-center">
                                <input type="checkbox" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">SMS notifications</span>
                            </label>
                            <label class="flex items-center">
                                <input type="checkbox" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">Marketing emails</span>
                            </label>
                        </div>
                    </div>
                </div>
            </div>
            <div class="flex justify-end space-x-3 mt-6">
                <button
                    @click="hideModal('demo-modal-large')"
                    class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-600 border border-gray-300 dark:border-gray-500 rounded-md hover:bg-gray-50 dark:hover:bg-gray-500"
                >
                    Cancel
                </button>
                <button
                    @click="hideModal('demo-modal-large')"
                    class="px-4 py-2 text-sm font-medium text-white bg-purple-600 rounded-md hover:bg-purple-700"
                >
                    Save Changes
                </button>
            </div>
        </div>
    </div>

    {{-- Full Modal --}}
    <div id="demo-modal-full" data-ui-modal data-size="full" style="display: none;">
        <div class="p-6 h-full overflow-y-auto">
            <div class="flex items-center justify-between mb-6">
                <h3 class="text-xl font-medium text-gray-900 dark:text-white">Full Screen Modal</h3>
                <button data-ui-close class="text-gray-400 hover:text-gray-600 text-2xl">×</button>
            </div>
            <div class="space-y-8">
                <p class="text-gray-600 dark:text-gray-400 text-lg">This is a full-screen modal that takes up the entire viewport. Perfect for complex workflows or detailed content.</p>
                
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                    <div class="space-y-4">
                        <h4 class="font-semibold text-gray-900 dark:text-white">Section 1</h4>
                        <p class="text-gray-600 dark:text-gray-400">Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.</p>
                        <div class="space-y-2">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Input 1</label>
                                <input type="text" class="block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md dark:bg-gray-700 dark:text-white">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Input 2</label>
                                <input type="text" class="block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md dark:bg-gray-700 dark:text-white">
                            </div>
                        </div>
                    </div>
                    
                    <div class="space-y-4">
                        <h4 class="font-semibold text-gray-900 dark:text-white">Section 2</h4>
                        <p class="text-gray-600 dark:text-gray-400">Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat.</p>
                        <div class="space-y-2">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Select Option</label>
                                <select class="block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md dark:bg-gray-700 dark:text-white">
                                    <option>Option 1</option>
                                    <option>Option 2</option>
                                    <option>Option 3</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Textarea</label>
                                <textarea rows="3" class="block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md dark:bg-gray-700 dark:text-white"></textarea>
                            </div>
                        </div>
                    </div>
                    
                    <div class="space-y-4">
                        <h4 class="font-semibold text-gray-900 dark:text-white">Section 3</h4>
                        <p class="text-gray-600 dark:text-gray-400">Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur.</p>
                        <div class="space-y-3">
                            <label class="flex items-center">
                                <input type="radio" name="radio-group" class="border-gray-300 text-blue-600 focus:ring-blue-500">
                                <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">Radio option 1</span>
                            </label>
                            <label class="flex items-center">
                                <input type="radio" name="radio-group" class="border-gray-300 text-blue-600 focus:ring-blue-500">
                                <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">Radio option 2</span>
                            </label>
                            <label class="flex items-center">
                                <input type="radio" name="radio-group" class="border-gray-300 text-blue-600 focus:ring-blue-500">
                                <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">Radio option 3</span>
                            </label>
                        </div>
                    </div>
                </div>
            </div>
            <div class="flex justify-end space-x-3 mt-8 pt-6 border-t border-gray-200 dark:border-gray-600">
                <button
                    @click="hideModal('demo-modal-full')"
                    class="px-6 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-600 border border-gray-300 dark:border-gray-500 rounded-md hover:bg-gray-50 dark:hover:bg-gray-500"
                >
                    Cancel
                </button>
                <button
                    @click="hideModal('demo-modal-full')"
                    class="px-6 py-2 text-sm font-medium text-white bg-red-600 rounded-md hover:bg-red-700"
                >
                    Save All Changes
                </button>
            </div>
        </div>
    </div>

    {{-- Component Status Indicator --}}
    <div class="fixed bottom-4 left-4 z-40">
        <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-600 rounded-lg p-3 shadow-lg">
            <div class="flex items-center space-x-2">
                <div class="w-2 h-2 rounded-full bg-green-500"></div>
                <span class="text-xs text-gray-600 dark:text-gray-400">UI Components Loaded</span>
            </div>
        </div>
    </div>
</div>
