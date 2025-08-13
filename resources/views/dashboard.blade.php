@extends('layouts.app')

@section('content')
<div class="max-w-5xl mx-auto py-12 px-6">
    <h1 class="text-4xl font-extrabold mb-6 bg-gradient-to-r from-blue-400 to-emerald-400 bg-clip-text text-transparent">Welcome to {{ config('app.name') }}</h1>
    <p class="text-slate-300 text-lg max-w-2xl">Unified experience across desktop and mobile using the same Livewire-driven layout.</p>
</div>
@endsection
