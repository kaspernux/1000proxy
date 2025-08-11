@props(['title' => null])
<x-mail::layout>
    {{-- Header --}}
    <x-slot:header>
        <table class="wrapper" width="100%" cellpadding="0" cellspacing="0" role="presentation">
            <tr>
                <td class="content" align="center" style="padding:25px 0;background:#0f172a;">
                    <a href="{{ config('app.url') }}" style="font-size:24px;font-weight:700;color:#fff;text-decoration:none;">
                        1000 <span style="color:#38bdf8;">PROXIES</span>
                    </a>
                </td>
            </tr>
        </table>
    </x-slot:header>

    {{-- Body --}}
    <div style="font-family:ui-sans-serif,system-ui,-apple-system,'Segoe UI',Roboto,'Helvetica Neue',Arial,'Noto Sans',sans-serif;line-height:1.5;color:#0f172a;">
        {{ $slot }}
    </div>

    {{-- Subcopy --}}
    @isset($subcopy)
        <x-slot:subcopy>
            <div style="border-top:1px solid #e2e8f0;margin-top:20px;padding-top:12px;font-size:12px;color:#64748b;">
                {{ $subcopy }}
            </div>
        </x-slot:subcopy>
    @endisset

    {{-- Footer --}}
    <x-slot:footer>
        <table class="wrapper" width="100%" cellpadding="0" cellspacing="0" role="presentation">
            <tr>
                <td class="content" align="center" style="padding:15px 0;background:#f1f5f9;">
                    <p style="margin:0;font-size:12px;color:#64748b;">© {{ date('Y') }} 1000 PROXIES. All rights reserved.</p>
                </td>
            </tr>
        </table>
    </x-slot:footer>
</x-mail::layout>
