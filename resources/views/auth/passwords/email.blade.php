@extends('layouts.app')

@section('content')
<div class="min-h-[70vh] flex items-center justify-center">
  <div class="card bg-base-100 shadow-xl w-full max-w-md border border-base-300">
    <div class="card-body">
      <div class="text-center mb-6">
        <div class="flex justify-center mb-4">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z" />
          </svg>
        </div>
        <h2 class="text-2xl font-bold">{{ __('Reset Password') }}</h2>
        <p class="text-base-content/60 mt-1">{{ __('Enter your email to receive a reset link') }}</p>
      </div>

      @if (session('status'))
        <div class="alert alert-success mb-4">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
          </svg>
          <span>{{ session('status') }}</span>
        </div>
      @endif

      <form method="POST" action="{{ route('password.email') }}">
        @csrf

        <div class="form-control w-full mb-4">
          <label class="label" for="email">
            <span class="label-text font-medium">{{ __('E-Mail Address') }}</span>
          </label>
          <input
            id="email"
            type="email"
            class="input input-bordered w-full @error('email') input-error @enderror"
            name="email"
            value="{{ old('email') }}"
            required
            autocomplete="email"
            autofocus
          >
          @error('email')
            <label class="label">
              <span class="label-text-alt text-error">{{ $message }}</span>
            </label>
          @enderror
        </div>

        <div class="form-control mt-6">
          <button type="submit" class="btn btn-primary w-full">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
            </svg>
            {{ __('Send Password Reset Link') }}
          </button>
        </div>

        <div class="text-center mt-4">
          <a href="{{ route('login') }}" class="link link-primary text-sm">
            {{ __('Back to Login') }}
          </a>
        </div>
      </form>
    </div>
  </div>
</div>
@endsection
