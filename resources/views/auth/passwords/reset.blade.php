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
        <p class="text-base-content/60 mt-1">{{ __('Enter your new password') }}</p>
      </div>

      <form method="POST" action="{{ route('password.update') }}">
        @csrf
        <input type="hidden" name="token" value="{{ $token }}">

        <div class="form-control w-full mb-4">
          <label class="label" for="email">
            <span class="label-text font-medium">{{ __('E-Mail Address') }}</span>
          </label>
          <input
            id="email"
            type="email"
            class="input input-bordered w-full @error('email') input-error @enderror"
            name="email"
            value="{{ $email ?? old('email') }}"
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
            autocomplete="new-password"
          >
          @error('password')
            <label class="label">
              <span class="label-text-alt text-error">{{ $message }}</span>
            </label>
          @enderror
        </div>

        <div class="form-control w-full mb-6">
          <label class="label" for="password-confirm">
            <span class="label-text font-medium">{{ __('Confirm Password') }}</span>
          </label>
          <input
            id="password-confirm"
            type="password"
            class="input input-bordered w-full"
            name="password_confirmation"
            required
            autocomplete="new-password"
          >
        </div>

        <div class="form-control">
          <button type="submit" class="btn btn-primary w-full">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
            </svg>
            {{ __('Reset Password') }}
          </button>
        </div>
      </form>
    </div>
  </div>
</div>
@endsection
