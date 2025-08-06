<x-filament-panels::page>
    <div class="space-y-6">
        <!-- Hero Section with Filament Card -->
        <x-filament::section>
            <x-slot name="heading">
                <div class="flex items-center space-x-3">
                    <x-filament::icon icon="heroicon-o-cog-6-tooth" class="h-8 w-8 text-primary-500" />
                    <span class="text-2xl font-bold">Configuration Guides</span>
                </div>
            </x-slot>
            <x-slot name="description">
                Configure your proxy clients quickly with our comprehensive guides for all platforms and protocols.
            </x-slot>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow rounded-lg">
                    <div class="p-5">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <x-filament::icon icon="heroicon-o-server" class="h-6 w-6 text-success-500" />
                            </div>
                            <div class="ml-5 w-0 flex-1">
                                <dl>
                                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">
                                        Active Configurations
                                    </dt>
                                    <dd class="text-lg font-medium text-gray-900 dark:text-white">
                                        {{ count($this->getUserConfigurations()) }}
                                    </dd>
                                </dl>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow rounded-lg">
                    <div class="p-5">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <x-filament::icon icon="heroicon-o-device-phone-mobile" class="h-6 w-6 text-info-500" />
                            </div>
                            <div class="ml-5 w-0 flex-1">
                                <dl>
                                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">
                                        Supported Platforms
                                    </dt>
                                    <dd class="text-lg font-medium text-gray-900 dark:text-white">
                                        {{ count($this->getAvailableConfigurations()['platforms']) }}
                                    </dd>
                                </dl>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow rounded-lg">
                    <div class="p-5">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <x-filament::icon icon="heroicon-o-shield-check" class="h-6 w-6 text-warning-500" />
                            </div>
                            <div class="ml-5 w-0 flex-1">
                                <dl>
                                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">
                                        Available Protocols
                                    </dt>
                                    <dd class="text-lg font-medium text-gray-900 dark:text-white">
                                        {{ count($this->getAvailableConfigurations()['protocols']) }}
                                    </dd>
                                </dl>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </x-filament::section>

        <!-- Platform & Client Selection -->
        <x-filament::section>
            <x-slot name="heading">
                <div class="flex items-center space-x-3">
                    <x-filament::icon icon="heroicon-o-adjustments-horizontal" class="h-6 w-6 text-primary-500" />
                    <span>Setup Configuration</span>
                </div>
            </x-slot>
            
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Platform Selection -->
                <div class="space-y-4">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white">
                        <x-filament::icon icon="heroicon-o-computer-desktop" class="h-5 w-5 inline mr-2 text-primary-500" />
                        Select Platform
                    </h3>
                    
                    <div class="space-y-2">
                        @foreach($this->getAvailableConfigurations()['platforms'] as $platform => $info)
                            <button
                                wire:click="updatePlatform('{{ $platform }}')"
                                class="w-full text-left p-4 rounded-lg border transition-all duration-200 
                                       {{ $selectedPlatform === $platform 
                                           ? 'border-primary-500 bg-primary-50 dark:bg-primary-950' 
                                           : 'border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700' }}"
                            >
                                <div class="flex items-center space-x-3">
                                    @switch($platform)
                                        @case('windows')
                                            <x-filament::icon icon="heroicon-o-computer-desktop" class="h-5 w-5 text-primary-500" />
                                            @break
                                        @case('android')
                                            <x-filament::icon icon="heroicon-o-device-phone-mobile" class="h-5 w-5 text-primary-500" />
                                            @break
                                        @case('ios')
                                            <x-filament::icon icon="heroicon-o-device-tablet" class="h-5 w-5 text-primary-500" />
                                            @break
                                        @case('macos')
                                            <x-filament::icon icon="heroicon-o-computer-desktop" class="h-5 w-5 text-primary-500" />
                                            @break
                                        @default
                                            <x-filament::icon icon="heroicon-o-device-phone-mobile" class="h-5 w-5 text-primary-500" />
                                    @endswitch
                                    
                                    <div class="flex-1">
                                        <div class="font-medium text-gray-900 dark:text-white">{{ $info['name'] }}</div>
                                        <div class="text-sm text-gray-500 dark:text-gray-400">{{ count($info['recommended_clients']) }} clients available</div>
                                    </div>
                                    
                                    @if($selectedPlatform === $platform)
                                        <x-filament::icon icon="heroicon-s-check" class="h-5 w-5 text-primary-500" />
                                    @endif
                                </div>
                            </button>
                        @endforeach
                    </div>
                </div>

                <!-- Client Selection -->
                <div class="space-y-4">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white">
                        <x-filament::icon icon="heroicon-o-arrow-down-tray" class="h-5 w-5 inline mr-2 text-success-500" />
                        Choose Client
                    </h3>
                    
                    <div class="space-y-2">
                        @foreach($this->getAvailableConfigurations()['platforms'][$selectedPlatform]['recommended_clients'] as $client)
                            <button
                                wire:click="updateClient('{{ $client }}')"
                                class="w-full text-left p-4 rounded-lg border transition-all duration-200 
                                       {{ $selectedClient === $client 
                                           ? 'border-success-500 bg-success-50 dark:bg-success-950' 
                                           : 'border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700' }}"
                            >
                                <div class="flex items-center space-x-3">
                                    <x-filament::icon icon="heroicon-o-cpu-chip" class="h-5 w-5 text-success-500" />
                                    
                                    <div class="flex-1">
                                        <div class="font-medium text-gray-900 dark:text-white">{{ $client }}</div>
                                        @if($selectedClient === $client)
                                            <div class="text-sm text-success-600 dark:text-success-400">✓ Selected</div>
                                        @endif
                                    </div>
                                    
                                    @if($selectedClient === $client)
                                        <x-filament::icon icon="heroicon-s-check" class="h-5 w-5 text-success-500" />
                                    @endif
                                </div>
                            </button>
                        @endforeach
                    </div>
                </div>

                <!-- Protocol Selection -->
                <div class="space-y-4">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white">
                        <x-filament::icon icon="heroicon-o-shield-check" class="h-5 w-5 inline mr-2 text-warning-500" />
                        Select Protocol
                    </h3>
                    
                    <div class="space-y-2">
                        @foreach($this->getAvailableConfigurations()['protocols'] as $protocol => $info)
                            <button
                                wire:click="updateProtocol('{{ $protocol }}')"
                                class="w-full text-left p-4 rounded-lg border transition-all duration-200 
                                       {{ $selectedProtocol === $protocol 
                                           ? 'border-warning-500 bg-warning-50 dark:bg-warning-950' 
                                           : 'border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700' }}"
                            >
                                <div class="flex items-center space-x-3">
                                    <x-filament::icon icon="heroicon-o-lock-closed" class="h-5 w-5 text-warning-500" />
                                    
                                    <div class="flex-1">
                                        <div class="font-medium text-gray-900 dark:text-white">{{ $info['name'] }}</div>
                                        <div class="text-sm text-gray-500 dark:text-gray-400">{{ $info['description'] }}</div>
                                    </div>
                                    
                                    @if($selectedProtocol === $protocol)
                                        <x-filament::icon icon="heroicon-s-check" class="h-5 w-5 text-warning-500" />
                                    @endif
                                </div>
                            </button>
                        @endforeach
                    </div>
                </div>
            </div>
        </x-filament::section>

        <!-- Recommended Client Downloads -->
        <x-filament::section>
            <x-slot name="heading">
                <div class="flex items-center space-x-3">
                    <x-filament::icon icon="heroicon-o-arrow-down-tray" class="h-6 w-6 text-success-500" />
                    <span>Download Recommended Clients</span>
                </div>
            </x-slot>
            <x-slot name="description">
                Get started quickly with our recommended proxy clients for each platform. These clients offer the best performance and user experience.
            </x-slot>
            
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                <!-- Windows - V2RayN -->
                <div class="group bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 hover:shadow-lg hover:border-blue-300 dark:hover:border-blue-600 transition-all duration-300 overflow-hidden">
                    <div class="p-6">
                        <div class="text-center space-y-4">
                            <div class="mx-auto w-16 h-16 bg-gradient-to-br from-blue-100 to-blue-200 dark:from-blue-900 dark:to-blue-800 rounded-xl flex items-center justify-center shadow-sm group-hover:scale-105 transition-transform duration-300">
                                <x-filament::icon icon="heroicon-o-computer-desktop" class="h-8 w-8 text-blue-600 dark:text-blue-400" />
                            </div>
                            
                            <div>
                                <h4 class="font-bold text-lg text-gray-900 dark:text-white">Windows</h4>
                                <p class="text-sm text-gray-600 dark:text-gray-400 mb-1">Recommended Client</p>
                                <div class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">
                                    V2RayN
                                </div>
                            </div>
                            
                            <div class="space-y-2">
                                <p class="text-xs text-gray-500 dark:text-gray-400">
                                    Popular Windows client with support for VMess, VLESS, and Trojan protocols
                                </p>
                                
                                <a
                                    href="https://github.com/2dust/v2rayN/releases"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    class="group/btn w-full inline-flex items-center justify-center px-4 py-2.5 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition-all duration-200 transform hover:scale-[1.02]"
                                >
                                    <x-filament::icon icon="heroicon-o-arrow-down-tray" class="h-4 w-4 mr-2 group-hover/btn:scale-110 transition-transform duration-200" />
                                    Download V2RayN
                                    <x-filament::icon icon="heroicon-o-arrow-top-right-on-square" class="h-3 w-3 ml-2" />
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Android - V2Box -->
                <div class="group bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 hover:shadow-lg hover:border-green-300 dark:hover:border-green-600 transition-all duration-300 overflow-hidden">
                    <div class="p-6">
                        <div class="text-center space-y-4">
                            <div class="mx-auto w-16 h-16 bg-gradient-to-br from-green-100 to-green-200 dark:from-green-900 dark:to-green-800 rounded-xl flex items-center justify-center shadow-sm group-hover:scale-105 transition-transform duration-300">
                                <x-filament::icon icon="heroicon-o-device-phone-mobile" class="h-8 w-8 text-green-600 dark:text-green-400" />
                            </div>
                            
                            <div>
                                <h4 class="font-bold text-lg text-gray-900 dark:text-white">Android</h4>
                                <p class="text-sm text-gray-600 dark:text-gray-400 mb-1">Recommended Client</p>
                                <div class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                                    V2Box
                                </div>
                            </div>
                            
                            <div class="space-y-2">
                                <p class="text-xs text-gray-500 dark:text-gray-400">
                                    High-performance Android client with redesigned UI and enhanced features
                                </p>
                                
                                <a
                                    href="https://play.google.com/store/apps/details?id=dev.hexasoftware.v2box"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    class="group/btn w-full inline-flex items-center justify-center px-4 py-2.5 bg-green-600 hover:bg-green-700 text-white font-medium rounded-lg transition-all duration-200 transform hover:scale-[1.02]"
                                >
                                    <x-filament::icon icon="heroicon-o-arrow-down-tray" class="h-4 w-4 mr-2 group-hover/btn:scale-110 transition-transform duration-200" />
                                    Download V2Box
                                    <x-filament::icon icon="heroicon-o-arrow-top-right-on-square" class="h-3 w-3 ml-2" />
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- iOS - V2Box -->
                <div class="group bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 hover:shadow-lg hover:border-purple-300 dark:hover:border-purple-600 transition-all duration-300 overflow-hidden">
                    <div class="p-6">
                        <div class="text-center space-y-4">
                            <div class="mx-auto w-16 h-16 bg-gradient-to-br from-purple-100 to-purple-200 dark:from-purple-900 dark:to-purple-800 rounded-xl flex items-center justify-center shadow-sm group-hover:scale-105 transition-transform duration-300">
                                <x-filament::icon icon="heroicon-o-device-tablet" class="h-8 w-8 text-purple-600 dark:text-purple-400" />
                            </div>
                            
                            <div>
                                <h4 class="font-bold text-lg text-gray-900 dark:text-white">iOS</h4>
                                <p class="text-sm text-gray-600 dark:text-gray-400 mb-1">Recommended Client</p>
                                <div class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200">
                                    V2Box
                                </div>
                            </div>
                            
                            <div class="space-y-2">
                                <p class="text-xs text-gray-500 dark:text-gray-400">
                                    Premium iOS app with focus on performance and user-friendly interface
                                </p>
                                
                                <a
                                    href="https://apps.apple.com/app/v2box/id6451244957"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    class="group/btn w-full inline-flex items-center justify-center px-4 py-2.5 bg-purple-600 hover:bg-purple-700 text-white font-medium rounded-lg transition-all duration-200 transform hover:scale-[1.02]"
                                >
                                    <x-filament::icon icon="heroicon-o-arrow-down-tray" class="h-4 w-4 mr-2 group-hover/btn:scale-110 transition-transform duration-200" />
                                    Download V2Box
                                    <x-filament::icon icon="heroicon-o-arrow-top-right-on-square" class="h-3 w-3 ml-2" />
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- macOS - V2Box -->
                <div class="group bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 hover:shadow-lg hover:border-orange-300 dark:hover:border-orange-600 transition-all duration-300 overflow-hidden">
                    <div class="p-6">
                        <div class="text-center space-y-4">
                            <div class="mx-auto w-16 h-16 bg-gradient-to-br from-orange-100 to-orange-200 dark:from-orange-900 dark:to-orange-800 rounded-xl flex items-center justify-center shadow-sm group-hover:scale-105 transition-transform duration-300">
                                <x-filament::icon icon="heroicon-o-computer-desktop" class="h-8 w-8 text-orange-600 dark:text-orange-400" />
                            </div>
                            
                            <div>
                                <h4 class="font-bold text-lg text-gray-900 dark:text-white">macOS</h4>
                                <p class="text-sm text-gray-600 dark:text-gray-400 mb-1">Recommended Client</p>
                                <div class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-200">
                                    V2Box
                                </div>
                            </div>
                            
                            <div class="space-y-2">
                                <p class="text-xs text-gray-500 dark:text-gray-400">
                                    Native macOS client with advanced features and seamless integration
                                </p>
                                
                                <a
                                    href="https://apps.apple.com/app/v2box/id6451244957"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    class="group/btn w-full inline-flex items-center justify-center px-4 py-2.5 bg-orange-600 hover:bg-orange-700 text-white font-medium rounded-lg transition-all duration-200 transform hover:scale-[1.02]"
                                >
                                    <x-filament::icon icon="heroicon-o-arrow-down-tray" class="h-4 w-4 mr-2 group-hover/btn:scale-110 transition-transform duration-200" />
                                    Download V2Box
                                    <x-filament::icon icon="heroicon-o-arrow-top-right-on-square" class="h-3 w-3 ml-2" />
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Alternative Clients Section -->
            <div class="mt-8 bg-gray-50 dark:bg-gray-900 rounded-xl p-6">
                <div class="text-center mb-6">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">Alternative Clients</h3>
                    <p class="text-sm text-gray-600 dark:text-gray-400">
                        Additional client options for advanced users or specific requirements
                    </p>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                    <!-- Windows Alternatives -->
                    <div class="bg-white dark:bg-gray-800 rounded-lg p-4 border border-gray-200 dark:border-gray-700">
                        <h4 class="font-medium text-gray-900 dark:text-white mb-3 flex items-center">
                            <x-filament::icon icon="heroicon-o-computer-desktop" class="h-4 w-4 mr-2 text-blue-500" />
                            Windows
                        </h4>
                        <ul class="space-y-2 text-sm">
                            <li><a href="https://github.com/Qv2ray/Qv2ray/releases" target="_blank" class="text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300">Qv2ray</a> - Cross-platform with plugins</li>
                            <li><a href="https://github.com/netchx/netch/releases" target="_blank" class="text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300">Netch</a> - Simple and lightweight</li>
                            <li><a href="https://github.com/TheMRLL/winxray/releases" target="_blank" class="text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300">WinXray</a> - Auto server switching</li>
                        </ul>
                    </div>

                    <!-- Android Alternatives -->
                    <div class="bg-white dark:bg-gray-800 rounded-lg p-4 border border-gray-200 dark:border-gray-700">
                        <h4 class="font-medium text-gray-900 dark:text-white mb-3 flex items-center">
                            <x-filament::icon icon="heroicon-o-device-phone-mobile" class="h-4 w-4 mr-2 text-green-500" />
                            Android
                        </h4>
                        <ul class="space-y-2 text-sm">
                            <li><a href="https://github.com/2dust/v2rayNG/releases" target="_blank" class="text-green-600 hover:text-green-800 dark:text-green-400 dark:hover:text-green-300">V2RayNG</a> - Popular and user-friendly</li>
                            <li><a href="https://github.com/eycorsican/BifrostV/releases" target="_blank" class="text-green-600 hover:text-green-800 dark:text-green-400 dark:hover:text-green-300">BifrostV</a> - V2Ray-based client</li>
                            <li><a href="https://github.com/SagerNet/SagerNet/releases" target="_blank" class="text-green-600 hover:text-green-800 dark:text-green-400 dark:hover:text-green-300">SagerNet</a> - Plugin support</li>
                        </ul>
                    </div>

                    <!-- iOS Alternatives -->
                    <div class="bg-white dark:bg-gray-800 rounded-lg p-4 border border-gray-200 dark:border-gray-700">
                        <h4 class="font-medium text-gray-900 dark:text-white mb-3 flex items-center">
                            <x-filament::icon icon="heroicon-o-device-tablet" class="h-4 w-4 mr-2 text-purple-500" />
                            iOS
                        </h4>
                        <ul class="space-y-2 text-sm">
                            <li><a href="https://apps.apple.com/app/kitsunebi/id1446584073" target="_blank" class="text-purple-600 hover:text-purple-800 dark:text-purple-400 dark:hover:text-purple-300">Kitsunebi</a> - Config import/export</li>
                            <li><a href="https://apps.apple.com/app/i2ray/id1445270056" target="_blank" class="text-purple-600 hover:text-purple-800 dark:text-purple-400 dark:hover:text-purple-300">i2Ray</a> - User-friendly interface</li>
                        </ul>
                    </div>

                    <!-- macOS Alternatives -->
                    <div class="bg-white dark:bg-gray-800 rounded-lg p-4 border border-gray-200 dark:border-gray-700">
                        <h4 class="font-medium text-gray-900 dark:text-white mb-3 flex items-center">
                            <x-filament::icon icon="heroicon-o-computer-desktop" class="h-4 w-4 mr-2 text-orange-500" />
                            macOS
                        </h4>
                        <ul class="space-y-2 text-sm">
                            <li><a href="https://github.com/Cenmrev/V2RayX/releases" target="_blank" class="text-orange-600 hover:text-orange-800 dark:text-orange-400 dark:hover:text-orange-300">V2RayX</a> - Native macOS client</li>
                            <li><a href="https://github.com/yichengchen/clashX/releases" target="_blank" class="text-orange-600 hover:text-orange-800 dark:text-orange-400 dark:hover:text-orange-300">ClashX</a> - Multi-protocol support</li>
                            <li><a href="https://github.com/Qv2ray/Qv2ray/releases" target="_blank" class="text-orange-600 hover:text-orange-800 dark:text-orange-400 dark:hover:text-orange-300">Qv2ray</a> - Cross-platform option</li>
                        </ul>
                    </div>
                </div>
            </div>
        </x-filament::section>

        <!-- Setup Steps -->
        <x-filament::section>
            <x-slot name="heading">
                <div class="flex items-center space-x-3">
                    <x-filament::icon icon="heroicon-o-list-bullet" class="h-6 w-6 text-primary-500" />
                    <span>Setup Steps</span>
                </div>
            </x-slot>
            
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                @foreach($this->getSetupSteps() as $step)
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 hover:shadow-lg transition-shadow duration-200">
                        <div class="text-center space-y-4">
                            <div class="mx-auto w-12 h-12 bg-primary-100 dark:bg-primary-900 rounded-full flex items-center justify-center">
                                @if($step['icon'])
                                    <x-filament::icon :icon="$step['icon']" class="h-6 w-6 text-primary-600 dark:text-primary-400" />
                                @endif
                            </div>
                            
                            <div>
                                <div class="inline-flex items-center justify-center w-6 h-6 bg-primary-600 text-white rounded-full text-sm font-bold mb-2">
                                    {{ $step['step'] }}
                                </div>
                                <h4 class="font-semibold text-gray-900 dark:text-white">{{ $step['title'] }}</h4>
                                <p class="text-sm text-gray-600 dark:text-gray-400 mt-2">{{ $step['description'] }}</p>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </x-filament::section>

        <!-- Your Configurations -->
        @if(count($this->getUserConfigurations()) > 0)
            <x-filament::section>
                <x-slot name="heading">
                    <div class="flex items-center justify-between w-full">
                        <div class="flex items-center space-x-3">
                            <x-filament::icon icon="heroicon-o-server" class="h-6 w-6 text-success-500" />
                            <span>Your Active Configurations</span>
                        </div>
                        <div class="flex items-center space-x-2 text-sm text-gray-500 dark:text-gray-400">
                            <div class="w-2 h-2 bg-success-500 rounded-full animate-pulse"></div>
                            <span>{{ count($this->getUserConfigurations()) }} Active</span>
                        </div>
                    </div>
                </x-slot>
                
                <div class="grid grid-cols-1 lg:grid-cols-2 xl:grid-cols-3 gap-6">
                    @foreach($this->getUserConfigurations() as $config)
                        <div class="group bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 hover:shadow-lg hover:border-gray-300 dark:hover:border-gray-600 transition-all duration-300 overflow-hidden">
                            <!-- Server Header with Status -->
                            <div class="relative p-6 pb-4">
                                <!-- Status Indicator -->
                                <div class="absolute top-4 right-4">
                                    <div class="flex items-center space-x-2">
                                        <div class="w-2 h-2 bg-success-500 rounded-full animate-pulse"></div>
                                        <span class="text-xs text-success-600 dark:text-success-400 font-medium">Online</span>
                                    </div>
                                </div>
                                
                                <!-- Server Info -->
                                <div class="flex items-start space-x-4">
                                    <div class="w-14 h-14 bg-gradient-to-br from-success-100 to-success-200 dark:from-success-900 dark:to-success-800 rounded-xl flex items-center justify-center shadow-sm">
                                        <x-filament::icon icon="heroicon-o-server" class="h-7 w-7 text-success-600 dark:text-success-400" />
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <h3 class="font-bold text-lg text-gray-900 dark:text-white truncate">
                                            {{ $config['server_name'] }}
                                        </h3>
                                        <div class="flex items-center space-x-2 mt-1">
                                            <x-filament::icon icon="heroicon-o-map-pin" class="h-4 w-4 text-gray-400" />
                                            <p class="text-sm text-gray-600 dark:text-gray-400">{{ $config['location'] }}</p>
                                        </div>
                                        <div class="flex items-center space-x-4 mt-3">
                                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-success-100 text-success-800 dark:bg-success-900 dark:text-success-200">
                                                {{ strtoupper($config['protocol']) }}
                                            </span>
                                            <div class="flex items-center space-x-1 text-xs text-gray-500 dark:text-gray-400">
                                                <x-filament::icon icon="heroicon-o-signal" class="h-3 w-3" />
                                                <span>High Speed</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Server Stats -->
                            <div class="px-6 py-4 bg-gray-50 dark:bg-gray-700/50 border-y border-gray-200 dark:border-gray-600">
                                <div class="grid grid-cols-3 gap-4 text-center">
                                    <div>
                                        <div class="text-sm font-semibold text-gray-900 dark:text-white">99.9%</div>
                                        <div class="text-xs text-gray-500 dark:text-gray-400">Uptime</div>
                                    </div>
                                    <div>
                                        <div class="text-sm font-semibold text-gray-900 dark:text-white">< 5ms</div>
                                        <div class="text-xs text-gray-500 dark:text-gray-400">Latency</div>
                                    </div>
                                    <div>
                                        <div class="text-sm font-semibold text-gray-900 dark:text-white">1Gbps</div>
                                        <div class="text-xs text-gray-500 dark:text-gray-400">Speed</div>
                                    </div>
                                </div>
                            </div>
                                
                            <!-- Action Buttons -->
                            <div class="p-6 pt-4 space-y-3">
                                <!-- Primary Actions -->
                                <div class="grid grid-cols-2 gap-3">
                                    <button
                                        onclick="showQRCode('{{ $config['qr_code'] }}')"
                                        class="group/btn inline-flex items-center justify-center px-4 py-2.5 border border-transparent text-sm font-medium rounded-lg text-white bg-primary-600 hover:bg-primary-700 focus:ring-4 focus:ring-primary-200 dark:focus:ring-primary-800 transition-all duration-200 transform hover:scale-[1.02]"
                                    >
                                        <x-filament::icon icon="heroicon-o-qr-code" class="h-4 w-4 mr-2 group-hover/btn:scale-110 transition-transform duration-200" />
                                        QR Code
                                    </button>
                                    
                                    <button
                                        onclick="copyToClipboard('{{ $config['config_url'] }}')"
                                        class="group/btn inline-flex items-center justify-center px-4 py-2.5 border border-transparent text-sm font-medium rounded-lg text-white bg-success-600 hover:bg-success-700 focus:ring-4 focus:ring-success-200 dark:focus:ring-success-800 transition-all duration-200 transform hover:scale-[1.02]"
                                    >
                                        <x-filament::icon icon="heroicon-o-clipboard" class="h-4 w-4 mr-2 group-hover/btn:scale-110 transition-transform duration-200" />
                                        Copy Link
                                    </button>
                                </div>
                                
                                <!-- Secondary Actions -->
                                <div class="grid grid-cols-2 gap-3">
                                    <button
                                        onclick="copyToClipboard('{{ $config['subscription_url'] }}')"
                                        class="group/btn inline-flex items-center justify-center px-4 py-2.5 border border-gray-300 dark:border-gray-600 text-sm font-medium rounded-lg text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 focus:ring-4 focus:ring-gray-200 dark:focus:ring-gray-700 transition-all duration-200"
                                    >
                                        <x-filament::icon icon="heroicon-o-link" class="h-4 w-4 mr-2 group-hover/btn:scale-110 transition-transform duration-200" />
                                        Subscription
                                    </button>
                                    
                                    <button
                                        onclick="showManualConfig({{ json_encode($config['manual_config']) }})"
                                        class="group/btn inline-flex items-center justify-center px-4 py-2.5 border border-gray-300 dark:border-gray-600 text-sm font-medium rounded-lg text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 focus:ring-4 focus:ring-gray-200 dark:focus:ring-gray-700 transition-all duration-200"
                                    >
                                        <x-filament::icon icon="heroicon-o-cog-6-tooth" class="h-4 w-4 mr-2 group-hover/btn:scale-110 transition-transform duration-200" />
                                        Manual Setup
                                    </button>
                                </div>

                                <!-- Quick Actions -->
                                <div class="pt-2 border-t border-gray-200 dark:border-gray-600">
                                    <div class="flex items-center justify-between text-xs">
                                        <div class="flex items-center space-x-3">
                                            <button
                                                onclick="testConnection('{{ $config['server_name'] }}')"
                                                class="flex items-center space-x-1 text-gray-500 dark:text-gray-400 hover:text-info-600 dark:hover:text-info-400 transition-colors duration-200"
                                            >
                                                <x-filament::icon icon="heroicon-o-signal" class="h-3 w-3" />
                                                <span>Test Connection</span>
                                            </button>
                                        </div>
                                        <div class="flex items-center space-x-1 text-gray-400">
                                            <x-filament::icon icon="heroicon-o-clock" class="h-3 w-3" />
                                            <span>Last used 2h ago</span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Hover Effect Overlay -->
                            <div class="absolute inset-0 opacity-0 group-hover:opacity-100 transition-opacity duration-300 pointer-events-none">
                                <div class="absolute inset-0 bg-gradient-to-r from-primary-500/5 to-success-500/5 rounded-xl"></div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <!-- Configuration Summary -->
                <div class="mt-8 grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div class="bg-gradient-to-r from-primary-50 to-primary-100 dark:from-primary-950 dark:to-primary-900 rounded-lg p-4 border border-primary-200 dark:border-primary-800">
                        <div class="flex items-center space-x-3">
                            <div class="w-10 h-10 bg-primary-500 rounded-lg flex items-center justify-center">
                                <x-filament::icon icon="heroicon-o-server" class="h-5 w-5 text-white" />
                            </div>
                            <div>
                                <div class="text-lg font-bold text-primary-900 dark:text-primary-100">{{ count($this->getUserConfigurations()) }}</div>
                                <div class="text-sm text-primary-700 dark:text-primary-300">Active Servers</div>
                            </div>
                        </div>
                    </div>

                    <div class="bg-gradient-to-r from-success-50 to-success-100 dark:from-success-950 dark:to-success-900 rounded-lg p-4 border border-success-200 dark:border-success-800">
                        <div class="flex items-center space-x-3">
                            <div class="w-10 h-10 bg-success-500 rounded-lg flex items-center justify-center">
                                <x-filament::icon icon="heroicon-o-shield-check" class="h-5 w-5 text-white" />
                            </div>
                            <div>
                                <div class="text-lg font-bold text-success-900 dark:text-success-100">100%</div>
                                <div class="text-sm text-success-700 dark:text-success-300">Uptime</div>
                            </div>
                        </div>
                    </div>

                    <div class="bg-gradient-to-r from-warning-50 to-warning-100 dark:from-warning-950 dark:to-warning-900 rounded-lg p-4 border border-warning-200 dark:border-warning-800">
                        <div class="flex items-center space-x-3">
                            <div class="w-10 h-10 bg-warning-500 rounded-lg flex items-center justify-center">
                                <x-filament::icon icon="heroicon-o-bolt" class="h-5 w-5 text-white" />
                            </div>
                            <div>
                                <div class="text-lg font-bold text-warning-900 dark:text-warning-100">High</div>
                                <div class="text-sm text-warning-700 dark:text-warning-300">Performance</div>
                            </div>
                        </div>
                    </div>

                    <div class="bg-gradient-to-r from-info-50 to-info-100 dark:from-info-950 dark:to-info-900 rounded-lg p-4 border border-info-200 dark:border-info-800">
                        <div class="flex items-center space-x-3">
                            <div class="w-10 h-10 bg-info-500 rounded-lg flex items-center justify-center">
                                <x-filament::icon icon="heroicon-o-globe-alt" class="h-5 w-5 text-white" />
                            </div>
                            <div>
                                <div class="text-lg font-bold text-info-900 dark:text-info-100">Global</div>
                                <div class="text-sm text-info-700 dark:text-info-300">Coverage</div>
                            </div>
                        </div>
                    </div>
                </div>
            </x-filament::section>
        @else
            <x-filament::section>
                <div class="text-center py-12">
                    
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">No Active Configurations</h3>
                    <p class="text-gray-600 dark:text-gray-400 mb-6 max-w-md mx-auto">
                        Purchase a proxy server plan to get started with your configurations and access our comprehensive setup guides.
                    </p>
                    <a
                        href="/servers"
                        class="inline-flex items-center px-6 py-3 border border-transparent text-base font-medium rounded-md text-white bg-primary-600 hover:bg-primary-700 transition-colors duration-200"
                    >
                        <x-filament::icon icon="heroicon-o-shopping-cart" class="h-5 w-5 mr-2" />
                        Browse Server Plans
                        <x-filament::icon icon="heroicon-o-arrow-right" class="h-4 w-4 ml-2" />
                    </a>
                </div>
            </x-filament::section>
        @endif

        <!-- Troubleshooting Tips -->
        <x-filament::section>
            <x-slot name="heading">
                <div class="flex items-center space-x-3">
                    <x-filament::icon icon="heroicon-o-exclamation-triangle" class="h-6 w-6 text-danger-500" />
                    <span>Troubleshooting Tips</span>
                </div>
            </x-slot>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                @foreach($this->getTroubleshootingTips() as $tip)
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                        <div class="space-y-4">
                            <div class="flex items-start space-x-4">
                                <div class="w-10 h-10 bg-danger-100 dark:bg-danger-900 rounded-lg flex items-center justify-center flex-shrink-0">
                                    <x-filament::icon icon="heroicon-o-exclamation-triangle" class="h-5 w-5 text-danger-600 dark:text-danger-400" />
                                </div>
                                <div class="flex-1">
                                    <h4 class="font-semibold text-gray-900 dark:text-white mb-2">{{ $tip['title'] }}</h4>
                                    <p class="text-sm text-gray-600 dark:text-gray-400">{{ $tip['description'] }}</p>
                                    @if(isset($tip['solution']))
                                        <div class="mt-3 p-3 bg-info-50 dark:bg-info-950 border border-info-200 dark:border-info-800 rounded-lg">
                                            <div class="flex items-start space-x-2">
                                                <x-filament::icon icon="heroicon-o-information-circle" class="h-4 w-4 text-info-600 dark:text-info-400 mt-0.5 flex-shrink-0" />
                                                <div>
                                                    <div class="text-xs font-medium text-info-800 dark:text-info-200 uppercase tracking-wide">Solution</div>
                                                    <div class="text-sm text-info-700 dark:text-info-300 mt-1">{{ $tip['solution'] }}</div>
                                                </div>
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </x-filament::section>

        <!-- Support Section -->
        <x-filament::section>
            <div class="bg-primary-50 dark:bg-primary-950 rounded-lg p-6">
                <div class="flex items-center space-x-6">
                    <div class="w-16 h-16 bg-primary-100 dark:bg-primary-900 rounded-lg flex items-center justify-center flex-shrink-0">
                        <x-filament::icon icon="heroicon-o-chat-bubble-left-right" class="h-8 w-8 text-primary-600 dark:text-primary-400" />
                    </div>
                    <div class="flex-1">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">Need Additional Help?</h3>
                        <p class="text-gray-600 dark:text-gray-400">
                            Our support team is available 24/7 to assist you with any configuration issues or questions.
                        </p>
                    </div>
                    <div class="flex-shrink-0">
                        <button class="inline-flex items-center px-6 py-3 border border-transparent text-base font-medium rounded-md text-white bg-primary-600 hover:bg-primary-700 transition-colors duration-200">
                            <x-filament::icon icon="heroicon-o-chat-bubble-left-ellipsis" class="h-5 w-5 mr-2" />
                            Contact Support
                        </button>
                    </div>
                </div>
            </div>
        </x-filament::section>
    </div>

    <!-- Enhanced Modals with Simple HTML -->
    <!-- QR Code Modal -->
    <div id="qrModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden items-center justify-center p-4">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-md w-full p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center">
                    <x-filament::icon icon="heroicon-o-qr-code" class="h-6 w-6 mr-2" />
                    Scan QR Code
                </h3>
                <button onclick="closeQRModal()" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                    <x-filament::icon icon="heroicon-o-x-mark" class="h-6 w-6" />
                </button>
            </div>
            
            <div class="text-center space-y-4">
                <div class="inline-block p-4 bg-white rounded-lg shadow-sm">
                    <img id="qrImage" src="" alt="QR Code" class="w-48 h-48 mx-auto" />
                </div>
                <p class="text-sm text-gray-600 dark:text-gray-400">
                    Scan with your mobile proxy client to automatically configure your connection.
                </p>
                <div class="bg-info-50 dark:bg-info-950 border border-info-200 dark:border-info-800 rounded-lg p-4">
                    <div class="flex items-start space-x-2">
                        <x-filament::icon icon="heroicon-o-information-circle" class="h-5 w-5 text-info-600 dark:text-info-400 flex-shrink-0" />
                        <p class="text-sm text-info-700 dark:text-info-300">
                            Open your proxy app and scan this QR code to automatically configure your connection.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Manual Config Modal -->
    <div id="configModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden items-center justify-center p-4">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-4xl w-full max-h-[80vh] overflow-y-auto">
            <div class="p-6">
                <div class="flex items-center justify-between mb-6">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center">
                        <x-filament::icon icon="heroicon-o-cog-6-tooth" class="h-6 w-6 mr-2" />
                        Manual Configuration
                    </h3>
                    <button onclick="closeConfigModal()" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                        <x-filament::icon icon="heroicon-o-x-mark" class="h-6 w-6" />
                    </button>
                </div>
                
                <div class="space-y-6">
                    <div id="configContent" class="space-y-4">
                        <!-- Config content will be inserted here -->
                    </div>
                    
                    <div class="bg-info-50 dark:bg-info-950 border border-info-200 dark:border-info-800 rounded-lg p-4">
                        <div class="flex items-start space-x-2">
                            <x-filament::icon icon="heroicon-o-information-circle" class="h-5 w-5 text-info-600 dark:text-info-400 flex-shrink-0" />
                            <div>
                                <div class="text-sm font-medium text-info-800 dark:text-info-200">Instructions</div>
                                <div class="text-sm text-info-700 dark:text-info-300 mt-1">
                                    Copy these settings into your proxy client's manual configuration section.
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        function copyToClipboard(text) {
            if (navigator.clipboard && window.isSecureContext) {
                navigator.clipboard.writeText(text).then(function() {
                    // Simple notification - you can customize this
                    showNotification('Copied to clipboard! 📋', 'success');
                }).catch(function() {
                    fallbackCopyToClipboard(text);
                });
            } else {
                fallbackCopyToClipboard(text);
            }
        }

        function fallbackCopyToClipboard(text) {
            const textArea = document.createElement('textarea');
            textArea.value = text;
            textArea.style.position = 'fixed';
            textArea.style.left = '-999999px';
            textArea.style.top = '-999999px';
            document.body.appendChild(textArea);
            textArea.focus();
            textArea.select();
            
            try {
                document.execCommand('copy');
                showNotification('Copied to clipboard! 📋', 'success');
            } catch (err) {
                showNotification('Failed to copy to clipboard', 'error');
            }
            
            document.body.removeChild(textArea);
        }

        function showNotification(message, type) {
            // Remove existing notifications
            const existingNotifications = document.querySelectorAll('.notification-toast');
            existingNotifications.forEach(n => n.remove());

            // Simple notification system
            const notification = document.createElement('div');
            notification.className = `notification-toast fixed top-4 right-4 z-50 px-4 py-3 rounded-lg shadow-lg max-w-sm transition-all duration-300 transform translate-x-full`;
            
            if (type === 'success') {
                notification.className += ' bg-green-500 text-white';
            } else if (type === 'info') {
                notification.className += ' bg-blue-500 text-white';
            } else {
                notification.className += ' bg-red-500 text-white';
            }
            
            notification.innerHTML = `
                <div class="flex items-center space-x-2">
                    <span class="font-medium">${message}</span>
                    <button onclick="this.parentElement.parentElement.remove()" class="ml-2 text-white hover:text-gray-200">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
            `;
            
            document.body.appendChild(notification);
            
            // Animate in
            setTimeout(() => {
                notification.classList.remove('translate-x-full');
            }, 100);
            
            // Auto remove
            setTimeout(() => {
                notification.classList.add('translate-x-full');
                setTimeout(() => {
                    if (notification.parentElement) {
                        notification.remove();
                    }
                }, 300);
            }, type === 'info' ? 5000 : 3000); // Info messages stay longer
        }

        function showQRCode(qrUrl) {
            const qrImage = document.getElementById('qrImage');
            const modal = document.getElementById('qrModal');
            
            if (!qrImage || !modal) return;
            
            qrImage.src = qrUrl;
            qrImage.onerror = function() {
                showNotification('Failed to load QR code', 'error');
                closeQRModal();
            };
            
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        }

        function closeQRModal() {
            const modal = document.getElementById('qrModal');
            if (modal) {
                modal.classList.add('hidden');
                modal.classList.remove('flex');
            }
        }

        function showManualConfig(config) {
            const content = document.getElementById('configContent');
            const modal = document.getElementById('configModal');
            
            if (!content || !modal) return;
            
            content.innerHTML = '';

            if (!config || Object.keys(config).length === 0) {
                content.innerHTML = '<p class="text-gray-400 text-center">No configuration data available</p>';
            } else {
                Object.entries(config).forEach(([protocol, settings]) => {
                    const protocolCard = document.createElement('div');
                    protocolCard.className = 'border border-gray-200 dark:border-gray-700 rounded-lg p-6 bg-white dark:bg-gray-800';
                    
                    protocolCard.innerHTML = `
                        <div class="flex items-center space-x-3 mb-4">
                            <div class="w-10 h-10 bg-primary-100 dark:bg-primary-900 rounded-lg flex items-center justify-center">
                                <span class="text-primary-600 dark:text-primary-400 font-bold text-sm">${protocol.charAt(0).toUpperCase()}</span>
                            </div>
                            <h4 class="text-lg font-semibold text-gray-900 dark:text-white">${protocol.toUpperCase()} Configuration</h4>
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-4">
                            ${Object.entries(settings).map(([key, value]) => `
                                <div class="bg-gray-50 dark:bg-gray-900 rounded-lg p-3">
                                    <div class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-1">${key.replace(/_/g, ' ')}</div>
                                    <div class="font-mono text-sm text-gray-900 dark:text-white break-all bg-white dark:bg-gray-800 px-2 py-1 rounded border">${value}</div>
                                </div>
                            `).join('')}
                        </div>
                        <button onclick="copyToClipboard('${JSON.stringify(settings, null, 2).replace(/'/g, "\\'")}'); closeConfigModal();" 
                                class="w-full inline-flex items-center justify-center px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white font-medium rounded-lg transition-colors duration-200">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                            </svg>
                            Copy ${protocol.toUpperCase()} Config
                        </button>
                    `;
                    
                    content.appendChild(protocolCard);
                });
            }

            modal.classList.remove('hidden');
            modal.classList.add('flex');
        }

        function closeConfigModal() {
            const modal = document.getElementById('configModal');
            if (modal) {
                modal.classList.add('hidden');
                modal.classList.remove('flex');
            }
        }

        function testConnection(serverName) {
            showNotification(`Testing connection to ${serverName}...`, 'info');
            
            // Simulate connection test
            setTimeout(() => {
                const isSuccessful = Math.random() > 0.2; // 80% success rate
                if (isSuccessful) {
                    showNotification(`✅ Connection to ${serverName} successful! Latency: ${Math.floor(Math.random() * 10) + 1}ms`, 'success');
                } else {
                    showNotification(`❌ Connection to ${serverName} failed. Please check your settings.`, 'error');
                }
            }, 2000);
        }

        // Close modals when clicking outside
        document.addEventListener('click', function(event) {
            if (event.target.id === 'qrModal') {
                closeQRModal();
            }
            if (event.target.id === 'configModal') {
                closeConfigModal();
            }
        });

        // Close modals with escape key
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                closeQRModal();
                closeConfigModal();
            }
        });
    </script>
    @endpush
</x-filament-panels::page>
