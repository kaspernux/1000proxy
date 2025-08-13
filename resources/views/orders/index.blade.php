@extends('layouts.app')

@section('content')
<div class="max-w-5xl mx-auto py-12 px-6">
    <h1 class="text-3xl font-bold mb-8">Recent Orders</h1>
    <div class="bg-slate-800/40 rounded-xl border border-slate-700 overflow-hidden">
        <table class="min-w-full text-left">
            <thead class="bg-slate-800 text-slate-300 text-sm">
                <tr>
                    <th class="px-6 py-3 font-medium">ID</th>
                    <th class="px-6 py-3 font-medium">Status</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-700">
            @foreach(\App\Models\Order::latest()->take(10)->get() as $o)
                <tr class="hover:bg-slate-800/60">
                    <td class="px-6 py-4">{{ $o->id }}</td>
                    <td class="px-6 py-4 capitalize">{{ $o->status }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection
