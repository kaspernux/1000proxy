<div
    class="w-full font-mono bg-gradient-to-r from-green-900 to-green-600 py-12 px-6 sm:px-8 lg:px-10 mx-auto max-w-[auto] flex justify-center">
    <div class="max-w-7xl">
        <h1 class="text-5xl font-bold text-white text-center">My Orders</h1>
        <div class="flex flex-col bg-white p-6 rounded mt-6 shadow-xl">
            <div class="-m-1.5 overflow-x-auto">
                <div class="p-1.5 min-w-full inline-block align-middle">
                    <div class="overflow-hidden">
                        <table class="min-w-full divide-y divide-green-400 dark:divide-green-700">
                            <thead>
                                <tr>
                                    <th scope="col"
                                        class="px-6 py-4 text-start text-sm font-bold text-green-900 uppercase">Order
                                    </th>
                                    <th scope="col"
                                        class="px-6 py-4 text-start text-sm font-bold text-green-900 uppercase">Date
                                    </th>
                                    <th scope="col"
                                        class="px-6 py-4 text-start text-sm font-bold text-green-900 uppercase">Order
                                        Status</th>
                                    <th scope="col"
                                        class="px-6 py-4 text-start text-sm font-bold text-green-900 uppercase">Payment
                                        Status</th>
                                    <th scope="col"
                                        class="px-6 py-4 text-start text-sm font-bold text-green-900 uppercase">Order
                                        Amount</th>
                                    <th scope="col"
                                        class="px-6 py-4 text-start text-sm font-bold text-green-900 uppercase">
                                        Action</th>
                                </tr>
                            </thead>
                            <tbody>

                                @foreach ($orders as $order)
                                    @php
                                        $order_status = '';
                                        $payment_status = '';

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

                                    <tr class="odd:bg-white even:bg-green-400 dark:odd:bg-white dark:even:bg-green-400 rounded-md even:rounded-md">
                                        <td class="px-6 py-4 whitespace-nowrap text-base font-bold text-green-800 dark:text-green-400">
                                            {{$order->id}}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-base text-green-800 dark:text-green-400">
                                            {{$order->created_at->format('d-m-Y')}}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-base text-green-800 dark:text-green-400">
                                            {!! $order_status !!}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-base text-green-800 dark:text-green-400">
                                            {!! $payment_status !!}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-base text-green-800 dark:text-green-400">
                                            {{Number::currency($order->grand_amount)}}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-end font-bold">
                                            <a href="/my-orders/{{$order->id}}" class="bg-accent-yellow text-white py-3 px-3 rounded-md hover:bg-green-700">View
                                                Details</a>
                                        </td>
                                    </tr>
                                @endforeach

                            </tbody>
                        </table>
                    </div>
                </div>
                {{$orders->links()}}
            </div>
        </div>
    </div>
</div>
