@extends('layouts.app')

@section('content')
<div class="w-full bg-gradient-to-r from-green-900 to-green-600 py-12 px-6 sm:px-6 lg:px-10 mx-auto max-w-[auto] flex justify-center">
    <section class="flex items-center font-mono ">
        <div
            class="justify-center flex-1 max-w-6xl px-4 py-4 mx-auto bg-yellow-600 border rounded-md md:py-10 md:px-10 shadow-lg dark:bg-yellow-600">
            <div>
                <h1 class="px-4 text-center text-2xl font-semibold tracking-wide text-red-500 uppercase md:text-3xl">
                    <i class="fas fa-exclamation-triangle"></i>
                    Payment Failed ! Order Cancelled!
                </h1>
            </div>
        </div>
    </section>
</div>
@endsection
