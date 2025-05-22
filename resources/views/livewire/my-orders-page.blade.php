<div class="w-full font-mono bg-gradient-to-r from-green-900 to-green-600 py-12 px-4 sm:px-8 lg:px-10 flex justify-center">
    <div class="max-w-7xl w-full">
        <h1 class="text-3xl sm:text-4xl md:text-5xl font-bold text-white text-center mb-6">My Orders</h1>

        <div class="flex flex-col bg-white p-4 sm:p-6 rounded shadow-xl">
            <div class="overflow-x-auto -mx-4 sm:-mx-6 lg:-mx-8">
                <div class="inline-block min-w-full align-middle">
                    <div class="overflow-hidden">
                        <table class="min-w-full divide-y divide-green-400 dark:divide-green-700 text-sm sm:text-base">
                            <thead>
                                <tr class="bg-green-100">
                                    <th scope="col"
                                        class="px-4 sm:px-6 py-3 text-left font-bold text-green-900 uppercase whitespace-nowrap">
                                        Order
                                    </th>
                                    <th scope="col"
                                        class="px-4 sm:px-6 py-3 text-left font-bold text-green-900 uppercase whitespace-nowrap">
                                        Date
                                    </th>
                                    <th scope="col"
                                        class="px-4 sm:px-6 py-3 text-left font-bold text-green-900 uppercase whitespace-nowrap">
                                        Order Status
                                    </th>
                                    <th scope="col"
                                        class="px-4 sm:px-6 py-3 text-left font-bold text-green-900 uppercase whitespace-nowrap">
                                        Payment Status
                                    </th>
                                    <th scope="col"
                                        class="px-4 sm:px-6 py-3 text-left font-bold text-green-900 uppercase whitespace-nowrap">
                                        Order Amount
                                    </th>
                                    <th scope="col"
                                        class="px-4 sm:px-6 py-3 text-right font-bold text-green-900 uppercase whitespace-nowrap">
                                        Action
                                    </th>
                                </tr>
                            </thead>

                            <tbody class="divide-y divide-green-200">
                                @foreach ($orders as $order)
                                    @php
                                        $status_classes = [
                                            'new' => 'bg-blue-500',
                                            'processing' => 'bg-orange-800',
                                            'completed' => 'bg-green-700',
                                            'dispute' => 'bg-red-700',
                                            'pending' => 'bg-orange-800',
                                            'paid' => 'bg-green-700',
                                            'failed' => 'bg-red-700',
                                        ];

                                        $order_status = '<span class="' . ($status_classes[$order->order_status] ?? 'bg-gray-500') . ' py-1 px-3 rounded text-white shadow text-xs sm:text-sm">' . ucfirst($order->order_status) . '</span>';

                                        $payment_status = '<span class="' . ($status_classes[$order->payment_status] ?? 'bg-gray-500') . ' py-1 px-3 rounded text-white shadow text-xs sm:text-sm">' . ucfirst($order->payment_status) . '</span>';
                                    @endphp

                                    <tr class="odd:bg-white even:bg-green-100 text-green-800">
                                        <td class="px-4 sm:px-6 py-3 font-bold whitespace-nowrap">
                                            {{ $order->id }}
                                        </td>
                                        <td class="px-4 sm:px-6 py-3 whitespace-nowrap">
                                            {{ $order->created_at->format('d-m-Y') }}
                                        </td>
                                        <td class="px-4 sm:px-6 py-3 whitespace-nowrap">
                                            {!! $order_status !!}
                                        </td>
                                        <td class="px-4 sm:px-6 py-3 whitespace-nowrap">
                                            {!! $payment_status !!}
                                        </td>
                                        <td class="px-4 sm:px-6 py-3 whitespace-nowrap">
                                            {{ Number::currency($order->grand_amount) }}
                                        </td>
                                        <td class="px-4 sm:px-6 py-3 text-right whitespace-nowrap">
                                            <a href="/my-orders/{{ $order->id }}"
                                               class="bg-accent-yellow text-white text-xs sm:text-sm py-2 px-3 rounded hover:bg-green-700 transition duration-200">
                                                View Details
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="mt-4 px-4 sm:px-6">
                    {{ $orders->links() }}
                </div>
            </div>
        </div>
    </div>
</div>
