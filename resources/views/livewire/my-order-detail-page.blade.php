<div class="w-full bg-gradient-to-r from-green-900 to-green-600 py-12 px-6 sm:px-6 lg:px-10 mx-auto max-w-[auto] flex justify-center">
    <div class="max-w-7xl">
        <h1 class="text-4xl font-bold text-white text-center">Order Details</h1>

        <!-- Grid -->
        <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-6 mt-5">
            <!-- Card -->
            <div class="flex flex-col bg-white border shadow-sm rounded-xl dark:bg-green-900 dark:border-green-800">
                <div class="p-4 md:p-5 flex gap-x-4">
                    <div
                        class="flex-shrink-0 flex justify-center items-center size-[46px] bg-yellow-600 rounded-lg dark:bg-green-800">
                        <svg class="flex-shrink-0 size-5 text-green-600 dark:text-green-400"
                            xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"
                            stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2" />
                            <circle cx="9" cy="7" r="4" />
                            <path d="M22 21v-2a4 4 0 0 0-3-3.87" />
                            <path d="M16 3.13a4 4 0 0 1 0 7.75" />
                        </svg>
                    </div>

                    <div class="grow">
                        <div class="flex items-center gap-x-2">
                            <p class="text-xs uppercase tracking-wide text-green-600">
                                Customer
                            </p>
                        </div>
                        <div class="mt-1 flex items-center gap-x-2">
                            <div>{{$order->customer->name}}</div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- End Card -->

            <!-- Card -->
            <div class="flex flex-col bg-white border shadow-sm rounded-xl dark:bg-green-900 dark:border-green-800">
                <div class="p-4 md:p-5 flex gap-x-4">
                    <div
                        class="flex-shrink-0 flex justify-center items-center size-[46px] bg-yellow-600 rounded-lg dark:bg-green-800">
                        <svg class="flex-shrink-0 size-5 text-green-600 dark:text-green-400"
                            xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"
                            stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M5 22h14" />
                            <path d="M5 2h14" />
                            <path d="M17 22v-4.172a2 2 0 0 0-.586-1.414L12 12l-4.414 4.414A2 2 0 0 0 7 17.828V22" />
                            <path d="M7 2v4.172a2 2 0 0 0 .586 1.414L12 12l4.414-4.414A2 2 0 0 0 17 6.172V2" />
                        </svg>
                    </div>

                    <div class="grow">
                        <div class="flex items-center gap-x-2">
                            <p class="text-xs uppercase tracking-wide text-green-600">
                                Order Date
                            </p>
                        </div>
                        <div class="mt-1 flex items-center gap-x-2">
                            <h3 class="text-xl font-medium text-green-800 dark:text-green-400">
                                {{$order_items[0]->created_at->format('d-m-Y')}}
                            </h3>
                        </div>
                    </div>
                </div>
            </div>
            <!-- End Card -->

            <!-- Card -->
            <div class="flex flex-col bg-white border shadow-sm rounded-xl dark:bg-green-900 dark:border-green-800">
                <div class="p-4 md:p-5 flex gap-x-4">
                    <div
                        class="flex-shrink-0 flex justify-center items-center size-[46px] bg-yellow-600 rounded-lg dark:bg-green-800">
                        <svg class="flex-shrink-0 size-5 text-green-600 dark:text-green-400"
                            xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"
                            stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M21 11V5a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h6" />
                            <path d="m12 12 4 10 1.7-4.3L22 16Z" />
                        </svg>
                    </div>

                    <div class="grow">
                        <div class="flex items-center gap-x-2">
                            <p class="text-xs uppercase tracking-wide text-green-600">
                                Order Status
                            </p>
                        </div>
                        <div class="mt-1 flex items-center gap-x-2">
                            @php
                            $order_status = '';

                            if ($order->order_status == 'new') {
                            $order_status = '<span class="bg-blue-500 py-1 px-3 rounded text-white shadow">New</span>';
                            }
                            if ($order->order_status == 'processing') {
                            $order_status = '<span class="bg-orange-800 py-1 px-3 rounded text-white shadow">Processing</span>';
                            }
                            if ($order->order_status == 'completed') {
                            $order_status = '<span class="bg-green-700 py-1 px-3 rounded text-white shadow">Completed</span>';
                            }
                            if ($order->order_status == 'dispute') {
                            $order_status = '<span class="bg-red-700 py-1 px-3 rounded text-white shadow">Dispute</span>';
                            }
                            @endphp

                            {!!$order_status!!}
                        </div>
                    </div>
                </div>
            </div>
            <!-- End Card -->

            <!-- Card -->
            <div class="flex flex-col bg-white border shadow-sm rounded-xl dark:bg-green-900 dark:border-green-800">
                <div class="p-4 md:p-5 flex gap-x-4">
                    <div
                        class="flex-shrink-0 flex justify-center items-center size-[46px] bg-yellow-600 rounded-lg dark:bg-green-800">
                        <svg class="flex-shrink-0 size-5 text-green-600 dark:text-green-400"
                            xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"
                            stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M5 12s2.545-5 7-5c4.454 0 7 5 7 5s-2.546 5-7 5c-4.455 0-7-5-7-5z" />
                            <path d="M12 13a1 1 0 1 0 0-2 1 1 0 0 0 0 2z" />
                            <path d="M21 17v2a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-2" />
                            <path d="M21 7V5a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v2" />
                        </svg>
                    </div>

                    <div class="grow">
                        <div class="flex items-center gap-x-2">
                            <p class="text-xs uppercase tracking-wide text-green-600">
                                Payment Status
                            </p>
                        </div>
                        <div class="mt-1 flex items-center gap-x-2">
                            @php
                                $payment_status = '';

                                if ($order->payment_status == 'pending') {
                                $payment_status = '<span class="bg-orange-800 py-1 px-3 rounded text-white shadow">Pending</span>';
                                }
                                if ($order->payment_status == 'paid') {
                                $payment_status = '<span class="bg-green-700 py-1 px-3 rounded text-white shadow">Paid</span>';
                                }
                                if ($order->payment_status == 'failed') {
                                $payment_status = '<span class="bg-red-700 py-1 px-3 rounded text-white shadow">Failed</span>';
                                }
                            @endphp

                            {!!$payment_status!!}
                        </div>
                    </div>
                </div>
            </div>
            <!-- End Card -->
             <!-- Transaction Summary Card -->
                <!-- Transaction Summary Card -->
                @if($order->invoice && $order->invoice->walletTransaction)
                <div class="flex flex-col bg-white border shadow-sm rounded-xl dark:bg-green-900 dark:border-green-800">
                    <div class="p-4 md:p-5 flex gap-x-4">
                        <div class="flex-shrink-0 flex justify-center items-center size-[46px] bg-yellow-600 rounded-lg dark:bg-green-800">
                            <svg class="flex-shrink-0 size-5 text-green-600 dark:text-green-400" xmlns="http://www.w3.org/2000/svg" fill="none"
                                viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path d="M3 3h18v4H3z" />
                                <path d="M3 10h18v11H3z" />
                            </svg>
                        </div>
                        <div class="grow">
                            <div class="flex items-center gap-x-2">
                                <p class="text-xs uppercase tracking-wide text-green-600">Transaction</p>
                            </div>
                            <div class="mt-1 text-green-800 dark:text-green-400">
                                <div class="text-sm font-semibold">
                                    Ref: {{ $order->invoice->walletTransaction->reference }}
                                </div>
                                <div class="text-xs text-green-600 dark:text-green-300 mt-1">
                                    {{ ucfirst($order->invoice->walletTransaction->status) }} • {{ $order->invoice->walletTransaction->created_at->format('d M Y, H:i') }}
                                </div>
                                @php
                                $filamentUrl = App\Filament\Customer\Clusters\MyWallet\Resources\WalletTransactionResource::getUrl(
                                    name: 'view',
                                    parameters: ['record' => $order->invoice->walletTransaction->getKey()],
                                    panel: 'customer'
                                );
                                @endphp
                                <a href="{{ $filamentUrl }}" class="text-sm font-bold text-yellow-500 hover:underline">
                                    View Transaction →
                                </a>

                                <div class="text-sm font-semibold mt-1">
                                    Amount: {{ Number::currency($order->invoice->walletTransaction->amount) }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                @endif

            </div>
            <!-- End Card -->
        <!-- End Grid -->

        <div class="flex flex-col md:flex-row gap-4 mt-4">
            <div class="md:w-3/4">
                <div class="bg-white overflow-x-auto rounded-lg shadow-md p-6 mb-4">
                    <table class="w-full">
                        <thead>
                            <tr>
                                <th class="text-left font-semibold">Product</th>
                                <th class="text-left font-semibold">Price</th>
                                <th class="text-left font-semibold">Quantity</th>
                                <th class="text-left font-semibold">Total</th>
                            </tr>
                        </thead>
                        <tbody>

                            @foreach ($order_items as $item)
                                <tr wire:key="{{$item->id}}">
                                    <td class="py-4">
                                        <div class="flex items-center">
                                            <img class="h-16 w-16 mr-4" src="{{ url('storage/' .$item->serverPlan->product_image) }}"
                                                alt="Product image">
                                            <span class="font-semibold">{{$item->name}}</span>
                                        </div>
                                    </td>
                                    <td class="py-4">{{Number::currency($item->unit_amount)}}</td>
                                    <td class="py-4">
                                        <span class="text-center w-8">{{$item->quantity}}</span>
                                    </td>
                                    <td class="py-4">{{Number::currency($item->total_amount)}}</td>
                                </tr>
                            @endforeach

                        </tbody>
                    </table>
                </div>

                {{-- <div class="bg-white overflow-x-auto rounded-lg shadow-md p-6 mb-4">
                    <h1 class="font-3xl font-bold text-slate-500 mb-3">Shipping Address</h1>
                    <div class="flex justify-between items-center">
                        <div>
                            <p>42227 Zoila Glens, Oshkosh, Michigan, 55928</p>
                        </div>
                        <div>
                            <p class="font-semibold">Phone:</p>
                            <p>023-509-0009</p>
                        </div>
                    </div>
                </div> --}}

            </div>
            <div class="md:w-1/4">
                <div class="bg-white rounded-lg shadow-md p-6">
                    <h2 class="text-lg font-semibold mb-4">Summary</h2>
                    <div class="flex justify-between mb-2">
                        <span>Subtotal</span>
                        <span>{{Number::currency($item->order->grand_amount)}}</span>
                    </div>
                    <div class="flex justify-between mb-2">
                        <span>Taxes</span>
                        <span>{{Number::currency(0)}}</span>
                    </div>
                    <div class="flex justify-between mb-2">
                        <span>Shipping</span>
                        <span>{{Number::currency(0)}}</span>
                    </div>
                    <hr class="my-2">
                    <div class="flex justify-between mb-2">
                        <span class="font-semibold">Grand Total</span>
                        <span class="font-semibold">{{Number::currency($item->order->grand_amount)}}</span>
                    </div>

                </div>
            </div>
        </div>
    </div>
</div>
