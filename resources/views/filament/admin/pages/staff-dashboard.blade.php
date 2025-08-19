<x-filament-panels::page>
    <div class="fi-section-content-ctn">
        <section class="space-y-8 max-w-7xl mx-auto px-2 sm:px-4 lg:px-8 py-8">
            {{-- Staff Statistics --}}
            @if($showStats)
            <section aria-labelledby="stats-heading" class="mb-6">
                <h2 id="stats-heading" class="sr-only">Staff Statistics</h2>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                    <div class="bg-white dark:bg-gray-900 rounded-2xl shadow-lg p-6 flex items-center">
                        <div class="flex-shrink-0">
                            <x-heroicon-o-user-group class="h-10 w-10 text-blue-600 dark:text-blue-400" />
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">Total Staff</dt>
                                <dd class="text-2xl font-bold text-gray-900 dark:text-white">{{ $staffStats['total_staff'] }}</dd>
                            </dl>
                        </div>
                    </div>
                    <div class="bg-white dark:bg-gray-900 rounded-2xl shadow-lg p-6 flex items-center">
                        <div class="flex-shrink-0">
                            <x-heroicon-o-check-circle class="h-10 w-10 text-green-600 dark:text-green-400" />
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">Active Staff</dt>
                                <dd class="text-2xl font-bold text-gray-900 dark:text-white">{{ $staffStats['active_staff'] }}</dd>
                            </dl>
                        </div>
                    </div>
                    <div class="bg-white dark:bg-gray-900 rounded-2xl shadow-lg p-6 flex items-center">
                        <div class="flex-shrink-0">
                            <x-heroicon-o-shield-check class="h-10 w-10 text-purple-600 dark:text-purple-400" />
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">Administrators</dt>
                                <dd class="text-2xl font-bold text-gray-900 dark:text-white">{{ $staffStats['admins'] }}</dd>
                            </dl>
                        </div>
                    </div>
                    <div class="bg-white dark:bg-gray-900 rounded-2xl shadow-lg p-6 flex items-center">
                        <div class="flex-shrink-0">
                            <x-heroicon-o-chat-bubble-left-right class="h-10 w-10 text-indigo-600 dark:text-indigo-400" />
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">With Telegram</dt>
                                <dd class="text-2xl font-bold text-gray-900 dark:text-white">{{ $staffStats['with_telegram'] }}</dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </section>
            @endif

            {{-- Search and Filters --}}
            <section aria-labelledby="filters-heading" class="mb-6">
                <h2 id="filters-heading" class="sr-only">Search and Filters</h2>
                <div class="bg-white dark:bg-gray-900 rounded-2xl shadow-lg p-6">
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                        <div>
                            <label for="search" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Search</label>
                            <input
                                type="text"
                                id="search"
                                wire:model.live.debounce.300ms="search"
                                placeholder="Search staff members..."
                                class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                            >
                        </div>
                        <div>
                            <label for="roleFilter" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Role</label>
                            <select
                                id="roleFilter"
                                wire:model.live="roleFilter"
                                class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                            >
                                <option value="">All Roles</option>
                                <option value="admin">Administrator</option>
                                <option value="support_manager">Support Manager</option>
                                <option value="sales_support">Sales Support</option>
                            </select>
                        </div>
                        <div>
                            <label for="statusFilter" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Status</label>
                            <select
                                id="statusFilter"
                                wire:model.live="statusFilter"
                                class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                            >
                                <option value="">All Status</option>
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>
                        <div class="flex items-end">
                            <button
                                wire:click="clearFilters"
                                type="button"
                                class="w-full inline-flex justify-center items-center px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm bg-white dark:bg-gray-800 text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                            >
                                Clear Filters
                            </button>
                        </div>
                    </div>
                </div>
            </section>

            {{-- Staff Members Table (Filament Table renders automatically via $this->table) --}}
            <section>
                {{ $this->table }}
            </section>
        </section>
    </div>
</x-filament-panels::page>
