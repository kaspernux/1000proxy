{{-- XUI Inbound Manager with Drag-and-Drop --}}
<div x-data="xuiInboundManager()" x-init="init()" class="xui-inbound-manager">
    {{-- Header --}}
    <div class="inbound-manager-header bg-surface border border-border-primary rounded-lg p-6 mb-6">
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
            <div class="header-info">
                <h2 class="text-xl font-semibold text-primary mb-2">
                    <i class="fas fa-network-wired mr-2"></i>
                    Inbound Manager
                </h2>
                <p class="text-muted text-sm">
                    Drag and drop to reorder • Click to configure • Real-time status updates
                </p>
            </div>

            <div class="header-actions">
                <button
                    @click="openCreateModal()"
                    class="interactive-primary px-4 py-2 rounded-lg text-sm font-medium transition-colors"
                >
                    <i class="fas fa-plus mr-2"></i>
                    Create Inbound
                </button>
            </div>
        </div>
    </div>

    {{-- Inbound List with Drag and Drop --}}
    <div class="inbound-list space-y-4">
        <template x-if="isLoading">
            <div class="loading-state text-center py-12">
                <i class="fas fa-spinner fa-spin text-3xl text-muted mb-4"></i>
                <p class="text-muted">Loading inbounds...</p>
            </div>
        </template>

        <template x-if="!isLoading && inbounds.length === 0">
            <div class="empty-state text-center py-12">
                <i class="fas fa-network-wired text-3xl text-muted mb-4"></i>
                <p class="text-muted mb-4">No inbounds configured</p>
                <button
                    @click="openCreateModal()"
                    class="interactive-primary px-4 py-2 rounded-lg text-sm font-medium"
                >
                    Create Your First Inbound
                </button>
            </div>
        </template>

        <template x-for="(inbound, index) in inbounds" :key="inbound.id">
            <div
                draggable="true"
                @dragstart="startDrag($event, inbound)"
                @dragover="allowDrop($event)"
                @drop="drop($event, index)"
                :class="{
                    'ring-2 ring-primary-500': selectedInbound?.id === inbound.id,
                    'opacity-50': draggedInbound?.id === inbound.id
                }"
                class="inbound-card bg-surface border border-border-primary rounded-lg p-6 cursor-move transition-all duration-200 hover:shadow-md"
            >
                <div class="inbound-header flex items-center justify-between mb-4">
                    <div class="inbound-info flex items-center space-x-4">
                        {{-- Drag Handle --}}
                        <div class="drag-handle text-muted hover:text-primary cursor-move">
                            <i class="fas fa-grip-vertical text-lg"></i>
                        </div>

                        {{-- Protocol Icon --}}
                        <div class="protocol-icon">
                            <i :class="getProtocolIcon(inbound.protocol)" class="text-primary text-xl"></i>
                        </div>

                        {{-- Inbound Details --}}
                        <div class="inbound-details">
                            <h3 class="font-semibold text-primary" x-text="inbound.remark || `Inbound ${inbound.port}`"></h3>
                            <p class="text-sm text-muted">
                                <span x-text="inbound.protocol.toUpperCase()"></span> •
                                Port <span x-text="inbound.port"></span> •
                                <span x-text="inbound.network.toUpperCase()"></span>
                            </p>
                        </div>
                    </div>

                    {{-- Status and Actions --}}
                    <div class="inbound-actions flex items-center space-x-3">
                        {{-- Status Toggle --}}
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input
                                type="checkbox"
                                :checked="inbound.enable"
                                @change="toggleInboundStatus(inbound)"
                                class="sr-only peer"
                            >
                            <div class="w-11 h-6 bg-secondary-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-primary-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-secondary-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-primary-600"></div>
                        </label>

                        {{-- Quick Actions --}}
                        <button
                            @click="selectInbound(inbound)"
                            class="p-2 text-muted hover:text-primary transition-colors"
                            title="Select"
                        >
                            <i class="fas fa-mouse-pointer"></i>
                        </button>

                        <button
                            @click="openEditModal(inbound)"
                            class="p-2 text-muted hover:text-primary transition-colors"
                            title="Edit"
                        >
                            <i class="fas fa-edit"></i>
                        </button>

                        <button
                            @click="deleteInbound(inbound)"
                            class="p-2 text-muted hover:text-error-500 transition-colors"
                            title="Delete"
                        >
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </div>

                {{-- Inbound Statistics --}}
                <div class="inbound-stats grid grid-cols-3 gap-4 mt-4 pt-4 border-t border-border-secondary">
                    <div class="stat-item text-center">
                        <div class="stat-value text-lg font-semibold text-primary" x-text="inbound.clientCount || 0"></div>
                        <div class="stat-label text-xs text-muted">Clients</div>
                    </div>

                    <div class="stat-item text-center">
                        <div class="stat-value text-lg font-semibold text-success-600" x-text="formatBytes(inbound.upload || 0)"></div>
                        <div class="stat-label text-xs text-muted">Upload</div>
                    </div>

                    <div class="stat-item text-center">
                        <div class="stat-value text-lg font-semibold text-info-600" x-text="formatBytes(inbound.download || 0)"></div>
                        <div class="stat-label text-xs text-muted">Download</div>
                    </div>
                </div>

                {{-- Network Configuration Preview --}}
                <div class="network-config mt-4 pt-4 border-t border-border-secondary">
                    <div class="flex items-center space-x-4 text-sm">
                        <span class="flex items-center space-x-2">
                            <i :class="getNetworkIcon(inbound.network)" class="text-muted"></i>
                            <span class="text-secondary" x-text="inbound.network.toUpperCase()"></span>
                        </span>

                        <template x-if="inbound.security && inbound.security !== 'none'">
                            <span class="flex items-center space-x-2">
                                <i class="fas fa-shield-alt text-success-500"></i>
                                <span class="text-secondary" x-text="inbound.security.toUpperCase()"></span>
                            </span>
                        </template>

                        <template x-if="inbound.path">
                            <span class="flex items-center space-x-2">
                                <i class="fas fa-route text-muted"></i>
                                <span class="text-secondary font-mono text-xs" x-text="inbound.path"></span>
                            </span>
                        </template>
                    </div>
                </div>
            </div>
        </template>
    </div>

    {{-- Create/Edit Modal --}}
    <div
        x-show="showCreateModal || showEditModal"
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50"
        @click.self="showCreateModal = false; showEditModal = false"
    >
        <div
            class="bg-surface rounded-lg shadow-xl max-w-md w-full mx-4 max-h-[90vh] overflow-y-auto"
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0 transform scale-95"
            x-transition:enter-end="opacity-100 transform scale-100"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100 transform scale-100"
            x-transition:leave-end="opacity-0 transform scale-95"
        >
            <div class="modal-header bg-primary-50 px-6 py-4 border-b border-border-primary">
                <h3 class="text-lg font-semibold text-primary">
                    <span x-show="showCreateModal">Create New Inbound</span>
                    <span x-show="showEditModal">Edit Inbound</span>
                </h3>
            </div>

            <div class="modal-body p-6 space-y-4">
                {{-- Port --}}
                <div class="form-group">
                    <label class="block text-sm font-medium text-secondary mb-2">Port</label>
                    <input
                        x-model="editingInbound.port"
                        type="number"
                        min="1"
                        max="65535"
                        class="w-full px-3 py-2 border border-border-primary rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                        placeholder="8080"
                    >
                </div>

                {{-- Protocol --}}
                <div class="form-group">
                    <label class="block text-sm font-medium text-secondary mb-2">Protocol</label>
                    <select x-model="editingInbound.protocol" class="w-full px-3 py-2 border border-border-primary rounded-lg focus:ring-2 focus:ring-primary-500">
                        <template x-for="protocol in protocols" :key="protocol">
                            <option :value="protocol" x-text="protocol.toUpperCase()"></option>
                        </template>
                    </select>
                </div>

                {{-- Network --}}
                <div class="form-group">
                    <label class="block text-sm font-medium text-secondary mb-2">Network</label>
                    <select x-model="editingInbound.network" class="w-full px-3 py-2 border border-border-primary rounded-lg focus:ring-2 focus:ring-primary-500">
                        <template x-for="network in networks" :key="network">
                            <option :value="network" x-text="network.toUpperCase()"></option>
                        </template>
                    </select>
                </div>

                {{-- Remark --}}
                <div class="form-group">
                    <label class="block text-sm font-medium text-secondary mb-2">Remark</label>
                    <input
                        x-model="editingInbound.remark"
                        type="text"
                        class="w-full px-3 py-2 border border-border-primary rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                        placeholder="My Inbound"
                    >
                </div>

                {{-- Enable Toggle --}}
                <div class="form-group">
                    <label class="flex items-center space-x-3">
                        <input
                            x-model="editingInbound.enable"
                            type="checkbox"
                            class="w-4 h-4 text-primary-600 bg-bg-surface border-border-primary rounded focus:ring-primary-500 focus:ring-2"
                        >
                        <span class="text-sm font-medium text-secondary">Enable this inbound</span>
                    </label>
                </div>
            </div>

            <div class="modal-footer bg-secondary-50 px-6 py-4 border-t border-border-primary flex justify-end space-x-3">
                <button
                    @click="showCreateModal = false; showEditModal = false"
                    class="px-4 py-2 text-secondary-600 bg-white border border-border-primary rounded-lg hover:bg-secondary-50 transition-colors"
                >
                    Cancel
                </button>

                <button
                    @click="saveInbound()"
                    class="interactive-primary px-4 py-2 rounded-lg text-sm font-medium"
                >
                    <span x-show="showCreateModal">Create Inbound</span>
                    <span x-show="showEditModal">Update Inbound</span>
                </button>
            </div>
        </div>
    </div>
</div>

<style>
.xui-inbound-manager .inbound-card:hover {
    transform: translateY(-1px);
}

.xui-inbound-manager .drag-handle:hover {
    cursor: grab;
}

.xui-inbound-manager .drag-handle:active {
    cursor: grabbing;
}

.xui-inbound-manager .inbound-card[draggable="true"]:hover {
    cursor: move;
}

.xui-inbound-manager .peer:checked + div {
    background-color: var(--color-primary-600);
}

.xui-inbound-manager .peer:checked + div:after {
    transform: translateX(100%);
    border-color: white;
}
</style>

<script>
// Add formatBytes utility function
window.formatBytes = function(bytes, decimals = 2) {
    if (bytes === 0) return '0 B';
    const k = 1024;
    const dm = decimals < 0 ? 0 : decimals;
    const sizes = ['B', 'KB', 'MB', 'GB', 'TB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(dm)) + ' ' + sizes[i];
};
</script>
