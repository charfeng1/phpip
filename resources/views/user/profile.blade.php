@extends('layouts.app')

@section('content')
<div class="bg-base-200 px-4 py-2 mb-2 rounded-lg">
  <h2 class="text-lg font-semibold">{{ __('My Profile') }}</h2>
</div>

@if (session('success'))
<div x-data="{ show: true }" x-show="show" x-transition class="alert alert-success" role="alert">
  <svg xmlns="http://www.w3.org/2000/svg" class="stroke-current shrink-0 h-6 w-6" fill="none" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
  <span>{{ session('success') }}</span>
  <button type="button" class="btn btn-sm btn-circle btn-ghost" @click="show = false" aria-label="{{ __('Close') }}">✕</button>
</div>
@endif

{{-- Make sure we get the fresh user data --}}
@php
  $userInfo = Auth::user()->fresh();
@endphp

@include('user.show', ['isProfileView' => true, 'userInfo' => $userInfo])
@endsection