@extends('layouts.app')

@section('content')
<main class="min-h-screen bg-gradient-to-br from-green-900 to-green-600 py-12 px-2 sm:px-6 lg:px-8 flex flex-col items-center">
    <section class="w-full max-w-7xl mx-auto">
        <header class="mb-10 text-center">
            <h1 class="text-3xl md:text-4xl font-extrabold text-white mb-2">Browse Categories</h1>
            <p class="text-lg text-green-100">Find the perfect server plan by category</p>
        </header>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @foreach($categories as $serverCategory)
            <a href="/servers?selected_categories[0]={{ $serverCategory->id }}" wire:key="{{ $serverCategory->id }}"
               class="group flex flex-col items-center bg-white/10 hover:bg-yellow-100/80 hover:text-green-900 border-2 border-green-800 rounded-2xl shadow-lg hover:shadow-2xl transition-all duration-300 ease-in-out p-6 cursor-pointer">
                <div class="flex flex-col items-center gap-4 w-full">
                    <img class="h-20 w-20 object-cover rounded-full border-4 border-yellow-600 shadow-md group-hover:scale-105 transition-transform duration-200"
                         src="{{ url('storage/'.$serverCategory->image) }}" alt="{{ $serverCategory->name }}">
                    <h3 class="text-2xl font-bold text-white group-hover:text-green-900 text-center tracking-wide">{{ $serverCategory->name }}</h3>
                </div>
                <div class="flex justify-end w-full mt-4">
                    <span class="inline-flex items-center gap-2 text-yellow-400 group-hover:text-green-900 font-semibold">
                        <span>View Plans</span>
                        <x-custom-icon name="arrow-right" class="w-5 h-5" />
                    </span>
                </div>
            </a>
            @endforeach
        </div>
    </section>
</main>
@endsection
