@extends('layouts.app')

@section('content')
<div class="min-h-[70vh] flex items-center justify-center">
  <div class="card bg-base-100 shadow-xl w-full max-w-md border border-base-300">
    <div class="card-body">
      <div class="text-center mb-6">
        <div class="flex justify-center mb-4">
          <svg width="48" height="48" viewBox="0 0 32 32" fill="none" class="text-primary">
            <rect width="32" height="32" rx="8" fill="currentColor"/>
            <path d="M8 12L16 8L24 12L16 16L8 12Z" fill="white" opacity="0.9"/>
            <path d="M8 20L16 16L24 20L16 24L8 20Z" fill="white" opacity="0.7"/>
          </svg>
        </div>
        <h2 class="text-2xl font-bold">{{ __('Welcome back') }}</h2>
        <p class="text-base-content/60 mt-1">{{ __('Sign in to your account') }}</p>
      </div>

      <form method="POST" action="{{ route('login') }}">
        @csrf

        <div class="form-control w-full mb-4">
          <label class="label" for="login">
            <span class="label-text font-medium">{{ __('User name') }}</span>
          </label>
          <input
            id="login"
            type="text"
            class="input input-bordered w-full @error('login') input-error @enderror"
            name="login"
            value="{{ old('login') }}"
            required
            autocomplete="login"
            autofocus
          >
          @error('login')
            <label class="label">
              <span class="label-text-alt text-error">{{ $message }}</span>
            </label>
          @enderror
        </div>

        <div class="form-control w-full mb-4">
          <label class="label" for="password">
            <span class="label-text font-medium">{{ __('Password') }}</span>
          </label>
          <input
            id="password"
            type="password"
            class="input input-bordered w-full @error('password') input-error @enderror"
            name="password"
            required
            autocomplete="current-password"
          >
          @error('password')
            <label class="label">
              <span class="label-text-alt text-error">{{ $message }}</span>
            </label>
          @enderror
        </div>

        <div class="form-control mb-6">
          <label class="label cursor-pointer justify-start gap-3">
            <input
              type="checkbox"
              class="checkbox checkbox-primary checkbox-sm"
              name="remember"
              id="remember"
              {{ old('remember') ? 'checked' : '' }}
            >
            <span class="label-text">{{ __('Remember Me') }}</span>
          </label>
        </div>

        <div class="form-control">
          <button type="submit" class="btn btn-primary w-full">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1" />
            </svg>
            {{ __('Login') }}
          </button>
        </div>

        @if (Route::has('password.request'))
          <div class="text-center mt-4">
            <a href="{{ route('password.request') }}" class="link link-primary text-sm">
              {{ __('Forgot Your Password?') }}
            </a>
          </div>
        @endif
      </form>
    </div>
  </div>
</div>
@endsection
