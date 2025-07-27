@extends('layouts.app')

@section('content')
<div class="w-full bg-gradient-to-r font-mono from-green-900 to-green-600 py-10 px-4 sm:px-6 lg:px-8 mx-auto">
    <div class="max-w-[85rem] px-4 py-10 sm:px-6 lg:px-8 lg:py-14 mx-auto">
        <div class="grid sm:grid-cols-1 md:grid-cols-2 lg:grid-cols-2 gap-4 sm:gap-6">
            @foreach($categories as $serverCategory)
            <a class="group flex flex-col justify-center items-left border-transparent bg-dark-green hover:bg-green-400 hover:text-green-900 disabled:opacity-50 disabled:pointer-events-none border shadow-sm rounded-xl hover:shadow-md transition duration-300 ease-in-out"
                href="/servers?selected_categories[0]={{ $serverCategory->id }}" wire:key="{{ $serverCategory->id }}">
                <div class="p-4 md:p-5">
                    <div class="flex justify-between items-center">
                        <div class="flex items-center">
                            <img class="h-[5rem] w-[5rem]" src="{{ url('storage/'.$serverCategory->image) }}"
                                alt="{{ $serverCategory->name }}">
                            <div class="ms-3">
                                <h3
                                    class="group-hover:text-green-700 text-2xl px-6 font-semibold text-white tracking-wide">
                                    {{ $serverCategory->name }}
                                </h3>
                            </div>
                        </div>
                        <div class="ps-3">
                            <x-custom-icon name="arrow-right" class="w-5 h-5" />
                        </div>
                    </div>
                </div>
            </a>
            @endforeach
        </div>
    </div>
</div>
@endsection
