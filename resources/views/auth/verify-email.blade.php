@extends('layouts.app')

@section('content')
    <div class="max-w-xl mx-auto py-12 px-6">
        @if (session('status') === 'verification-link-sent')
            <div class="mb-4 text-sm text-green-600">
                A new verification link has been sent to your email address.
            </div>
        @endif

        @if (session('success'))
            <div class="mb-4 text-sm text-green-600">
                {{ session('success') }}
            </div>
        @endif

        <h1 class="text-2xl font-bold mb-4">Almost there—check your email</h1>
        <p class="text-gray-600 mb-6">
            We sent an activation link to <span class="font-semibold">{{ auth('customer')->user()->email }}</span>.
            Click the link to verify your email and activate your account.
            If you didn't receive the email, you can resend it below.
        </p>

        <form method="POST" action="{{ route('verification.send') }}" class="inline">
            @csrf
            <button class="bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 px-4 rounded">
                Resend Verification Email
            </button>
        </form>

    <a href="/account/logout" class="inline-block ml-3 bg-gray-600 hover:bg-gray-700 text-white font-semibold py-2 px-4 rounded">Logout</a>
    </div>
@endsection
