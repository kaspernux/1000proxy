{{-- Telegram-safe, compact help content (kept well under 4096 chars) --}}
<b>🤖 {{ config('app.name', '1K PROXY') }} — {{ __('telegram_help.title') }}</b><br><br>

<b>{{ __('telegram_help.dashboard_label') }}</b><br>
<a href="{{ rtrim(config('app.url'), '/') }}/account">{{ rtrim(config('app.url'), '/') }}/account</a><br>{{-- Telegram /help content: Commands + Setup Guides --}}

<b>{{ __('telegram_help.commands_heading') }}</b><br>
• <code>/menu</code> — {{ __('telegram.commands.menu') }}<br>
• <code>/help</code> — {{ __('telegram.commands.help') }}<br>
• <code>/login</code> — {{ __('telegram.commands.login') }}<br>
• <code>/signup</code> — {{ __('telegram.commands.signup') }}<br>
• <code>/balance</code> — {{ __('telegram.commands.balance') }}<br>
• <code>/topup</code> — {{ __('telegram.commands.topup') }}<br>
• <code>/plans</code> — {{ __('telegram.commands.plans') }}<br>
• <code>/orders</code> — {{ __('telegram.commands.orders') }}<br>
• <code>/myproxies</code> — {{ __('telegram.commands.myproxies') }}<br>
• <code>/config</code> <i>[client_id]</i> — {{ __('telegram.commands.config') }}<br>
• <code>/status</code> <i>[client_id]</i> — {{ __('telegram.commands.status') }}<br>
• <code>/support</code> <i>[message]</i> — {{ __('telegram.commands.support') }}<br><br>
<b>{{ __('telegram_help.tips_heading') }}</b><br>
{!! __('telegram_help.tips_block', ['plans' => __('telegram.buttons.plans'), 'link' => __('telegram.buttons.link_account')]) !!}<br>

<b>{{ __('telegram_help.where_heading') }}</b><br>
{!! __('telegram_help.where_block', ['dash' => rtrim(config('app.url'), '/') . '/account/my-active-servers']) !!}<br>

<b>{{ __('telegram_help.guides_heading') }}</b><br>
{!! __('telegram_help.guide_ios_v2box') !!}<br>
{!! __('telegram_help.guide_macos_v2box') !!}<br>
{!! __('telegram_help.guide_android_v2box') !!}<br>
{!! __('telegram_help.guide_windows_v2rayn') !!}<br>
{!! __('telegram_help.guide_android_v2rayng') !!}<br>
{!! __('telegram_help.guide_android_clash') !!}<br>
{!! __('telegram_help.guide_windows_cfw') !!}<br>

<b>{{ __('telegram_help.subs_heading') }}</b><br>
{!! __('telegram_help.subs_block') !!}<br>

<b>{{ __('telegram_help.troubles_heading') }}</b><br>
{!! __('telegram_help.troubles_block') !!}<br>

<b>{{ __('telegram_help.links_heading') }}</b><br>
• {{ __('telegram_help.link_dashboard') }}: <a href="{{ rtrim(config('app.url'), '/') }}/account">{{ rtrim(config('app.url'), '/') }}/account</a><br>
• {{ __('telegram_help.link_orders') }}: <a href="{{ rtrim(config('app.url'), '/') }}/account/order-management">{{ __('telegram_help.orders_label') }}</a><br>
• {{ __('telegram_help.link_wallet') }}: <a href="{{ rtrim(config('app.url'), '/') }}/wallet/usd/top-up">{{ __('telegram_help.wallet_label') }}</a>
