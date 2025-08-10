<x-filament-panels::page class="fi-dashboard-page">
	<!-- Hero Header -->
	<div class="fi-section-content-ctn">
		<div class="fi-section-header mb-10 pb-4">
			<div class="fi-section-header-wrapper">
				<div class="flex flex-col space-y-4 md:flex-row md:items-center md:justify-between md:space-y-0">
					<div class="flex-1 min-w-0">
						<h1 class="fi-section-header-heading text-2xl font-bold tracking-tight text-gray-950 dark:text-white sm:text-3xl">
							<div class="flex items-center">
								<div class="flex h-10 w-10 items-center justify-center rounded-lg bg-primary-50 dark:bg-primary-900/20 mr-3 flex-shrink-0">
									<x-heroicon-s-home-modern class="h-6 w-6 text-primary-600 dark:text-primary-400" />
								</div>
								<span class="truncate">Customer Dashboard</span>
							</div>
						</h1>
						<p class="fi-section-header-description mt-2 text-sm text-gray-500 dark:text-gray-400 leading-6">
							Overview of your account health, orders, referrals, and quick actions
						</p>
					</div>
					<div class="flex items-center gap-2">
						<x-filament::button tag="a" href="{{ route('filament.customer.pages.order-management') }}" icon="heroicon-o-receipt-refund" color="primary">Orders</x-filament::button>
						<x-filament::button tag="a" href="{{ route('filament.customer.pages.configuration-guides') }}" icon="heroicon-o-book-open" color="info">Setup Guides</x-filament::button>
						<x-filament::button tag="a" href="{{ route('filament.customer.pages.referral-system') }}" icon="heroicon-o-gift" color="success">Referrals</x-filament::button>
					</div>
				</div>
			</div>
		</div>

		<!-- Quick Actions Row -->
		<div class="grid gap-4 md:gap-6 grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 mb-10">
			<x-filament::section class="group relative overflow-hidden border-0 shadow-lg bg-gradient-to-br from-primary-500 to-blue-600 text-white">
				<div class="p-4 flex items-start gap-4">
					<div class="p-2 bg-white/20 rounded-lg">
						<x-heroicon-s-banknotes class="h-6 w-6 text-white" />
					</div>
					<div class="flex-1">
						<h3 class="text-sm font-medium text-primary-100">Wallet Balance</h3>
						<p class="text-2xl font-bold">${{ number_format(auth()->guard('customer')->user()->wallet?->balance ?? 0, 2) }}</p>
						<a href="{{ route('filament.customer.pages.wallet-management') }}" class="mt-2 inline-flex items-center text-xs text-primary-100 hover:text-white transition">
							Manage Wallet <x-heroicon-s-arrow-right class="h-3 w-3 ml-1" />
						</a>
					</div>
				</div>
			</x-filament::section>

			<x-filament::section class="group relative overflow-hidden border-0 shadow-lg bg-gradient-to-br from-success-500 to-emerald-600 text-white">
				<div class="p-4 flex items-start gap-4">
					<div class="p-2 bg-white/20 rounded-lg">
						<x-heroicon-s-server-stack class="h-6 w-6 text-white" />
					</div>
					<div class="flex-1">
						<h3 class="text-sm font-medium text-emerald-100">Active Services</h3>
						<p class="text-2xl font-bold">{{ \App\Models\ServerClient::where('customer_id', auth()->guard('customer')->id())->where('enable', true)->count() }}</p>
						<a href="{{ route('filament.customer.pages.my-active-servers') }}" class="mt-2 inline-flex items-center text-xs text-emerald-100 hover:text-white transition">
							View Services <x-heroicon-s-arrow-right class="h-3 w-3 ml-1" />
						</a>
					</div>
				</div>
			</x-filament::section>

			<x-filament::section class="group relative overflow-hidden border-0 shadow-lg bg-gradient-to-br from-warning-500 to-orange-500 text-white">
				<div class="p-4 flex items-start gap-4">
					<div class="p-2 bg-white/20 rounded-lg">
						<x-heroicon-s-gift class="h-6 w-6 text-white" />
					</div>
					<div class="flex-1">
						<h3 class="text-sm font-medium text-orange-100">Referrals</h3>
						<p class="text-2xl font-bold">{{ auth()->guard('customer')->user()->referrals()->count() }}</p>
						<a href="{{ route('filament.customer.pages.referral-system') }}" class="mt-2 inline-flex items-center text-xs text-orange-100 hover:text-white transition">
							Manage Referrals <x-heroicon-s-arrow-right class="h-3 w-3 ml-1" />
						</a>
					</div>
				</div>
			</x-filament::section>

			<x-filament::section class="group relative overflow-hidden border-0 shadow-lg bg-gradient-to-br from-purple-500 to-indigo-600 text-white">
				<div class="p-4 flex items-start gap-4">
					<div class="p-2 bg-white/20 rounded-lg">
						<x-heroicon-s-bolt class="h-6 w-6 text-white" />
					</div>
					<div class="flex-1">
						<h3 class="text-sm font-medium text-indigo-100">Quick Start</h3>
						<p class="text-2xl font-bold">Setup</p>
						<a href="{{ route('filament.customer.pages.configuration-guides') }}#builder" class="mt-2 inline-flex items-center text-xs text-indigo-100 hover:text-white transition">
							Configure Client <x-heroicon-s-arrow-right class="h-3 w-3 ml-1" />
						</a>
					</div>
				</div>
			</x-filament::section>
		</div>
	</div>

	<!-- Core Widgets Grid -->
	<x-filament-widgets::widgets
		:columns="$this->getColumns()"
		:data="$this->getWidgetData()"
		:widgets="$this->getVisibleWidgets()"
	/>

	<!-- Support / Help Panel -->
	<div class="mt-14 mb-8 px-2">
		<div class="grid gap-6 md:grid-cols-3">
			<x-filament::section class="md:col-span-2">
				<x-slot name="heading">
					<div class="flex items-center gap-2">
						<x-heroicon-o-question-mark-circle class="w-5 h-5 text-primary-600" />
						Need Help?
					</div>
				</x-slot>
				<div class="text-sm text-gray-600 dark:text-gray-400 space-y-2">
					<p>Explore the setup guides to configure your client on any platform or reach out for support.</p>
					<div class="flex flex-wrap gap-2">
						<x-filament::button tag="a" href="{{ route('filament.customer.pages.configuration-guides') }}" size="sm" icon="heroicon-o-book-open">Guides</x-filament::button>
						<x-filament::button tag="a" href="{{ route('filament.customer.pages.referral-system') }}" size="sm" icon="heroicon-o-gift">Referral Program</x-filament::button>
						<x-filament::button tag="a" href="{{ route('filament.customer.pages.wallet-management') }}" size="sm" icon="heroicon-o-banknotes">Wallet</x-filament::button>
					</div>
				</div>
			</x-filament::section>
			<x-filament::section>
				<x-slot name="heading">
					<div class="flex items-center gap-2">
						<x-heroicon-o-lifebuoy class="w-5 h-5 text-success-600" />
						Support
					</div>
				</x-slot>
				<div class="text-sm text-gray-600 dark:text-gray-400 space-y-2">
					<p class="flex items-center gap-2"><x-heroicon-o-chat-bubble-left class="h-4 w-4 text-primary-500" /> Live chat coming soon</p>
					<p class="flex items-center gap-2"><x-heroicon-o-envelope class="h-4 w-4 text-primary-500" /> support@1000proxy.io</p>
					<p class="flex items-center gap-2"><x-heroicon-o-clock class="h-4 w-4 text-primary-500" /> 24/7 automated monitoring</p>
				</div>
			</x-filament::section>
		</div>
	</div>
</x-filament-panels::page>
