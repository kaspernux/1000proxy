{{-- Client Configuration Builder with Live Preview --}}
<div x-data="clientConfigurationBuilder()" x-init="init()" class="client-configuration-builder">
    {{-- Header --}}
    <div class="config-builder-header bg-surface border border-border-primary rounded-lg p-6 mb-6">
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
            <div class="header-info">
                <h2 class="text-xl font-semibold text-primary mb-2">
                    <i class="fas fa-tools mr-2"></i>
                    Client Configuration Builder
                </h2>
                <p class="text-muted text-sm">
                    Build and preview client configurations with real-time updates
                </p>
            </div>

            <div class="header-actions flex gap-3">
                <button
                    @click="resetConfiguration()"
                    class="interactive-secondary px-4 py-2 rounded-lg text-sm font-medium transition-colors"
                >
                    <i class="fas fa-undo mr-2"></i>
                    Reset
                </button>

                <button
                    @click="generateUUID()"
                    class="interactive-secondary px-4 py-2 rounded-lg text-sm font-medium transition-colors"
                >
                    <i class="fas fa-sync-alt mr-2"></i>
                    New UUID
                </button>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {{-- Configuration Form --}}
        <div class="config-form bg-surface border border-border-primary rounded-lg p-6">
            <h3 class="text-lg font-semibold text-primary mb-4">
                <i class="fas fa-cog mr-2"></i>
                Configuration Parameters
            </h3>

            <div class="form-grid space-y-4">
                {{-- Protocol Selection --}}
                <div class="form-group">
                    <label class="block text-sm font-medium text-secondary mb-2">Protocol</label>
                    <select x-model="configuration.protocol" class="w-full px-3 py-2 border border-border-primary rounded-lg focus:ring-2 focus:ring-primary-500">
                        <option value="vless">VLESS</option>
                        <option value="vmess">VMESS</option>
                        <option value="trojan">Trojan</option>
                        <option value="shadowsocks">Shadowsocks</option>
                    </select>
                </div>

                {{-- Host and Port --}}
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="form-group">
                        <label class="block text-sm font-medium text-secondary mb-2">Host</label>
                        <input
                            x-model="configuration.host"
                            type="text"
                            class="w-full px-3 py-2 border border-border-primary rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                            placeholder="example.com"
                        >
                    </div>

                    <div class="form-group">
                        <label class="block text-sm font-medium text-secondary mb-2">Port</label>
                        <input
                            x-model="configuration.port"
                            type="number"
                            min="1"
                            max="65535"
                            class="w-full px-3 py-2 border border-border-primary rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                            placeholder="443"
                        >
                    </div>
                </div>

                {{-- UUID --}}
                <div class="form-group">
                    <label class="block text-sm font-medium text-secondary mb-2">UUID</label>
                    <div class="flex space-x-2">
                        <input
                            x-model="configuration.uuid"
                            type="text"
                            class="flex-1 px-3 py-2 border border-border-primary rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 font-mono text-sm"
                            placeholder="xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx"
                        >
                        <button
                            @click="generateUUID()"
                            class="px-3 py-2 bg-primary-50 text-primary-600 rounded-lg hover:bg-primary-100 transition-colors"
                            title="Generate new UUID"
                        >
                            <i class="fas fa-dice"></i>
                        </button>
                    </div>
                </div>

                {{-- Network Configuration --}}
                <div class="form-group">
                    <label class="block text-sm font-medium text-secondary mb-2">Network</label>
                    <select x-model="configuration.network" class="w-full px-3 py-2 border border-border-primary rounded-lg focus:ring-2 focus:ring-primary-500">
                        <option value="tcp">TCP</option>
                        <option value="ws">WebSocket</option>
                        <option value="grpc">gRPC</option>
                        <option value="h2">HTTP/2</option>
                    </select>
                </div>

                {{-- Security --}}
                <div class="form-group">
                    <label class="block text-sm font-medium text-secondary mb-2">Security</label>
                    <select x-model="configuration.security" class="w-full px-3 py-2 border border-border-primary rounded-lg focus:ring-2 focus:ring-primary-500">
                        <option value="none">None</option>
                        <option value="tls">TLS</option>
                        <option value="reality">Reality</option>
                    </select>
                </div>

                {{-- Path (for WebSocket) --}}
                <template x-if="configuration.network === 'ws'">
                    <div class="form-group">
                        <label class="block text-sm font-medium text-secondary mb-2">WebSocket Path</label>
                        <input
                            x-model="configuration.path"
                            type="text"
                            class="w-full px-3 py-2 border border-border-primary rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                            placeholder="/path"
                        >
                    </div>
                </template>

                {{-- Service Name (for gRPC) --}}
                <template x-if="configuration.network === 'grpc'">
                    <div class="form-group">
                        <label class="block text-sm font-medium text-secondary mb-2">gRPC Service Name</label>
                        <input
                            x-model="configuration.serviceName"
                            type="text"
                            class="w-full px-3 py-2 border border-border-primary rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                            placeholder="ServiceName"
                        >
                    </div>
                </template>

                {{-- TLS Settings --}}
                <template x-if="configuration.security === 'tls'">
                    <div class="tls-settings space-y-4">
                        <div class="form-group">
                            <label class="block text-sm font-medium text-secondary mb-2">SNI</label>
                            <input
                                x-model="configuration.sni"
                                type="text"
                                class="w-full px-3 py-2 border border-border-primary rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                                placeholder="example.com"
                            >
                        </div>

                        <div class="form-group">
                            <label class="block text-sm font-medium text-secondary mb-2">ALPN</label>
                            <input
                                x-model="configuration.alpn"
                                type="text"
                                class="w-full px-3 py-2 border border-border-primary rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                                placeholder="h2,http/1.1"
                            >
                        </div>

                        <div class="form-group">
                            <label class="block text-sm font-medium text-secondary mb-2">Fingerprint</label>
                            <select x-model="configuration.fingerprint" class="w-full px-3 py-2 border border-border-primary rounded-lg focus:ring-2 focus:ring-primary-500">
                                <option value="chrome">Chrome</option>
                                <option value="firefox">Firefox</option>
                                <option value="safari">Safari</option>
                                <option value="edge">Edge</option>
                                <option value="random">Random</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label class="flex items-center space-x-3">
                                <input
                                    x-model="configuration.allowInsecure"
                                    type="checkbox"
                                    class="w-4 h-4 text-primary-600 bg-bg-surface border-border-primary rounded focus:ring-primary-500 focus:ring-2"
                                >
                                <span class="text-sm font-medium text-secondary">Allow Insecure</span>
                            </label>
                        </div>
                    </div>
                </template>
            </div>
        </div>

        {{-- Live Preview --}}
        <div class="config-preview bg-surface border border-border-primary rounded-lg p-6">
            <div class="preview-header flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold text-primary">
                    <i class="fas fa-eye mr-2"></i>
                    Live Preview
                </h3>

                <div class="preview-controls flex items-center space-x-2">
                    <button
                        @click="previewMode = 'json'"
                        :class="previewMode === 'json' ? 'bg-primary-100 text-primary-600' : 'bg-secondary-100 text-secondary-600'"
                        class="px-3 py-1 rounded-md text-sm font-medium transition-colors"
                    >
                        JSON
                    </button>

                    <button
                        @click="previewMode = 'url'"
                        :class="previewMode === 'url' ? 'bg-primary-100 text-primary-600' : 'bg-secondary-100 text-secondary-600'"
                        class="px-3 py-1 rounded-md text-sm font-medium transition-colors"
                    >
                        URL
                    </button>

                    <button
                        @click="previewMode = 'qr'"
                        :class="previewMode === 'qr' ? 'bg-primary-100 text-primary-600' : 'bg-secondary-100 text-secondary-600'"
                        class="px-3 py-1 rounded-md text-sm font-medium transition-colors"
                    >
                        QR
                    </button>
                </div>
            </div>

            {{-- Loading State --}}
            <template x-if="isGenerating">
                <div class="preview-loading text-center py-8">
                    <i class="fas fa-spinner fa-spin text-2xl text-muted mb-2"></i>
                    <p class="text-muted text-sm">Generating configuration...</p>
                </div>
            </template>

            {{-- Preview Content --}}
            <template x-if="!isGenerating">
                <div class="preview-content">
                    {{-- JSON Preview --}}
                    <template x-if="previewMode === 'json'">
                        <div class="json-preview">
                            <pre class="bg-secondary-50 p-4 rounded-lg text-sm font-mono overflow-x-auto border border-border-secondary" x-html="getConfigPreview()"></pre>
                        </div>
                    </template>

                    {{-- URL Preview --}}
                    <template x-if="previewMode === 'url'">
                        <div class="url-preview">
                            <div class="bg-secondary-50 p-4 rounded-lg border border-border-secondary">
                                <p class="text-sm font-mono break-all" x-text="generatedConfig"></p>
                            </div>
                        </div>
                    </template>

                    {{-- QR Code Preview --}}
                    <template x-if="previewMode === 'qr'">
                        <div class="qr-preview text-center">
                            <template x-if="qrCode">
                                <img :src="qrCode" alt="QR Code" class="mx-auto max-w-xs">
                            </template>

                            <template x-if="!qrCode">
                                <div class="qr-placeholder bg-secondary-50 border-2 border-dashed border-border-secondary rounded-lg p-8">
                                    <i class="fas fa-qrcode text-3xl text-muted mb-2"></i>
                                    <p class="text-muted text-sm">QR code will appear here</p>
                                </div>
                            </template>
                        </div>
                    </template>
                </div>
            </template>

            {{-- Action Buttons --}}
            <div class="preview-actions mt-6 flex flex-wrap gap-3">
                <button
                    @click="copyConfiguration()"
                    :disabled="!generatedConfig"
                    class="flex-1 interactive-primary px-4 py-2 rounded-lg text-sm font-medium transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                >
                    <i class="fas fa-copy mr-2"></i>
                    Copy Config
                </button>

                <button
                    @click="downloadConfiguration()"
                    :disabled="!generatedConfig"
                    class="flex-1 interactive-secondary px-4 py-2 rounded-lg text-sm font-medium transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                >
                    <i class="fas fa-download mr-2"></i>
                    Download
                </button>
            </div>

            {{-- Subscription URL --}}
            <template x-if="subscriptionUrl">
                <div class="subscription-url mt-4 p-4 bg-accent border border-border-accent rounded-lg">
                    <label class="block text-sm font-medium text-secondary mb-2">Subscription URL</label>
                    <div class="flex space-x-2">
                        <input
                            :value="subscriptionUrl"
                            readonly
                            class="flex-1 px-3 py-2 bg-white border border-border-primary rounded-lg font-mono text-sm"
                        >
                        <button
                            @click="navigator.clipboard.writeText(subscriptionUrl)"
                            class="px-3 py-2 bg-primary-50 text-primary-600 rounded-lg hover:bg-primary-100 transition-colors"
                            title="Copy subscription URL"
                        >
                            <i class="fas fa-copy"></i>
                        </button>
                    </div>
                </div>
            </template>
        </div>
    </div>
</div>

<style>
.client-configuration-builder .json-preview pre {
    max-height: 400px;
}

.client-configuration-builder .url-preview {
    max-height: 200px;
    overflow-y: auto;
}

.client-configuration-builder .qr-preview img {
    border: 1px solid var(--color-border-secondary);
    border-radius: 8px;
    padding: 8px;
    background: white;
}

.client-configuration-builder .form-group label {
    color: var(--color-text-secondary);
}

.client-configuration-builder .preview-controls button {
    transition: all 0.2s ease-in-out;
}

.client-configuration-builder .preview-controls button:hover {
    transform: translateY(-1px);
}
</style>
