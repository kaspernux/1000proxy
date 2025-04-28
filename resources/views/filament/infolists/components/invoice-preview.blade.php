<div class="rounded-lg border bg-white shadow-sm p-4">
    @if ($getRecord()->invoice && $getRecord()->invoice->invoice_url)
        <div class="flex flex-col items-center space-y-4">
            <iframe 
                src="{{ asset($getRecord()->invoice->invoice_url) }}" 
                class="w-full h-[600px] rounded-md border" 
                frameborder="0">
            </iframe>

            <a 
                href="{{ asset($getRecord()->invoice->invoice_url) }}" 
                class="inline-flex items-center justify-center px-4 py-2 text-sm font-medium text-white bg-primary-600 rounded-lg hover:bg-primary-700 transition"
                download
                target="_blank"
            >
                📄 Download Invoice PDF
            </a>
        </div>
    @else
        <div class="text-center text-gray-500">
            Invoice not generated yet.<br>
            Your invoice will appear here once payment is completed.
        </div>
    @endif
</div>
