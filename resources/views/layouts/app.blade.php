<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">

  <!-- CSRF Token -->
  <meta name="csrf-token" content="{{ csrf_token() }}">

  <title>{{ config('app.name', 'phpIP') }}</title>

  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

  <!-- Scripts -->
  <script>
    window.appConfig = {
      renewal: {
        invoice: {
          backend: @json(config('renewal.invoice.backend'))
        },
        general: {
          receipt_tabs: @json(config('renewal.general.receipt_tabs'))
        }
      },
      translations: {
        deleteImageConfirm: @json(__('Delete this image?'))
      }
    };
  </script>
  @vite(['resources/js/app.js'])

  <!-- Styles -->
  @yield('style')
</head>

<body class="modern-body @yield('body-class') @cannot('readwrite') role-readonly @endcannot">
  <svg xmlns="http://www.w3.org/2000/svg" style="display: none;">
    <symbol id="check-circle-fill" viewBox="0 0 16 16">
      <path d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0zm-3.97-3.03a.75.75 0 0 0-1.08.022L7.477 9.417 5.384 7.323a.75.75 0 0 0-1.06 1.06L6.97 11.03a.75.75 0 0 0 1.079-.02l3.992-4.99a.75.75 0 0 0-.01-1.05z"/>
    </symbol>
    <symbol id="info-fill" viewBox="0 0 16 16">
      <path d="M8 16A8 8 0 1 0 8 0a8 8 0 0 0 0 16zm.93-9.412-1 4.705c-.07.34.029.533.304.533.194 0 .487-.07.686-.246l-.088.416c-.287.346-.92.598-1.465.598-.703 0-1.002-.422-.808-1.319l.738-3.468c.064-.293.006-.399-.287-.47l-.451-.081.082-.381 2.29-.287zM8 5.5a1 1 0 1 1 0-2 1 1 0 0 1 0 2z"/>
    </symbol>
    <symbol id="exclamation-triangle-fill" viewBox="0 0 16 16">
      <path d="M8.982 1.566a1.13 1.13 0 0 0-1.96 0L.165 13.233c-.457.778.091 1.767.98 1.767h13.713c.889 0 1.438-.99.98-1.767L8.982 1.566zM8 5c.535 0 .954.462.9.995l-.35 3.507a.552.552 0 0 1-1.1 0L7.1 5.995A.905.905 0 0 1 8 5zm.002 6a1 1 0 1 1 0 2 1 1 0 0 1 0-2z"/>
    </symbol>
    <symbol id="trash" viewBox="0 0 16 16">
      <path d="M5.5 5.5A.5.5 0 0 1 6 6v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5Zm2.5 0a.5.5 0 0 1 .5.5v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5Zm3 .5a.5.5 0 0 0-1 0v6a.5.5 0 0 0 1 0V6Z"/>
      <path d="M14.5 3a1 1 0 0 1-1 1H13v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V4h-.5a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1H6a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1h3.5a1 1 0 0 1 1 1v1ZM4.118 4 4 4.059V13a1 1 0 0 0 1 1h6a1 1 0 0 0 1-1V4.059L11.882 4H4.118ZM2.5 3h11V2h-11v1Z"/>
    </symbol>
    <symbol id="trash-fill" viewBox="0 0 16 16">
      <path d="M2.5 1a1 1 0 0 0-1 1v1a1 1 0 0 0 1 1H3v9a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2V4h.5a1 1 0 0 0 1-1V2a1 1 0 0 0-1-1H10a1 1 0 0 0-1-1H7a1 1 0 0 0-1 1H2.5zm3 4a.5.5 0 0 1 .5.5v7a.5.5 0 0 1-1 0v-7a.5.5 0 0 1 .5-.5zM8 5a.5.5 0 0 1 .5.5v7a.5.5 0 0 1-1 0v-7A.5.5 0 0 1 8 5zm3 .5v7a.5.5 0 0 1-1 0v-7a.5.5 0 0 1 1 0z"/>
    </symbol>
    <symbol id="pencil-square" viewBox="0 0 16 16">
      <path d="M15.502 1.94a.5.5 0 0 1 0 .706L14.459 3.69l-2-2L13.502.646a.5.5 0 0 1 .707 0l1.293 1.293zm-1.75 2.456-2-2L4.939 9.21a.5.5 0 0 0-.121.196l-.805 2.414a.25.25 0 0 0 .316.316l2.414-.805a.5.5 0 0 0 .196-.12l6.813-6.814z"/>
      <path fill-rule="evenodd" d="M1 13.5A1.5 1.5 0 0 0 2.5 15h11a1.5 1.5 0 0 0 1.5-1.5v-6a.5.5 0 0 0-1 0v6a.5.5 0 0 1-.5.5h-11a.5.5 0 0 1-.5-.5v-11a.5.5 0 0 1 .5-.5H9a.5.5 0 0 0 0-1H2.5A1.5 1.5 0 0 0 1 2.5v11z"/>
    </symbol>
    <symbol id="intersect" viewBox="0 0 16 16">
      <path d="M0 2a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v2h2a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2v-2H2a2 2 0 0 1-2-2V2zm5 10v2a1 1 0 0 0 1 1h8a1 1 0 0 0 1-1V6a1 1 0 0 0-1-1h-2v5a2 2 0 0 1-2 2H5zm6-8V2a1 1 0 0 0-1-1H2a1 1 0 0 0-1 1v8a1 1 0 0 0 1 1h2V6a2 2 0 0 1 2-2h5z"/>
    </symbol>
    <symbol id="plus-circle-fill" viewBox="0 0 16 16">
      <path d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0zM8.5 4.5a.5.5 0 0 0-1 0v3h-3a.5.5 0 0 0 0 1h3v3a.5.5 0 0 0 1 0v-3h3a.5.5 0 0 0 0-1h-3v-3z"/>
    </symbol>
    <symbol id="folder-plus" viewBox="0 0 16 16">
      <path d="m.5 3 .04.87a1.99 1.99 0 0 0-.342 1.311l.637 7A2 2 0 0 0 2.826 14H9v-1H2.826a1 1 0 0 1-.995-.91l-.637-7A1 1 0 0 1 2.19 4h11.62a1 1 0 0 1 .996 1.09L14.54 8h1.005l.256-2.819A2 2 0 0 0 13.81 3H9.828a2 2 0 0 1-1.414-.586l-.828-.828A2 2 0 0 0 6.172 1H2.5a2 2 0 0 0-2 2Zm5.672-1a1 1 0 0 1 .707.293L7.586 3H2.19c-.24 0-.47.042-.683.12L1.5 2.98a1 1 0 0 1 1-.98h3.672Z"/>
      <path d="M13.5 9a.5.5 0 0 1 .5.5V11h1.5a.5.5 0 1 1 0 1H14v1.5a.5.5 0 1 1-1 0V12h-1.5a.5.5 0 0 1 0-1H13V9.5a.5.5 0 0 1 .5-.5Z"/>
    </symbol>
    <symbol id="person-plus-fill" viewBox="0 0 16 16">
      <path d="M1 14s-1 0-1-1 1-4 6-4 6 3 6 4-1 1-1 1H1zm5-6a3 3 0 1 0 0-6 3 3 0 0 0 0 6z"/>
      <path fill-rule="evenodd" d="M13.5 5a.5.5 0 0 1 .5.5V7h1.5a.5.5 0 0 1 0 1H14v1.5a.5.5 0 0 1-1 0V8h-1.5a.5.5 0 0 1 0-1H13V5.5a.5.5 0 0 1 .5-.5z"/>
    </symbol>
    <symbol id="node-plus-fill" viewBox="0 0 16 16">
      <path d="M11 13a5 5 0 1 0-4.975-5.5H4A1.5 1.5 0 0 0 2.5 6h-1A1.5 1.5 0 0 0 0 7.5v1A1.5 1.5 0 0 0 1.5 10h1A1.5 1.5 0 0 0 4 8.5h2.025A5 5 0 0 0 11 13zm.5-7.5v2h2a.5.5 0 0 1 0 1h-2v2a.5.5 0 0 1-1 0v-2h-2a.5.5 0 0 1 0-1h2v-2a.5.5 0 0 1 1 0z"/>
    </symbol>
    <symbol id="flag-fill" viewBox="0 0 16 16">
      <path d="M14.778.085A.5.5 0 0 1 15 .5V8a.5.5 0 0 1-.314.464L14.5 8l.186.464-.003.001-.006.003-.023.009a12.435 12.435 0 0 1-.397.15c-.264.095-.631.223-1.047.35-.816.252-1.879.523-2.71.523-.847 0-1.548-.28-2.158-.525l-.028-.01C7.68 8.71 7.14 8.5 6.5 8.5c-.7 0-1.638.23-2.437.477A19.626 19.626 0 0 0 3 9.342V15.5a.5.5 0 0 1-1 0V.5a.5.5 0 0 1 1 0v.282c.226-.079.496-.17.79-.26C4.606.272 5.67 0 6.5 0c.84 0 1.524.277 2.121.519l.043.018C9.286.788 9.828 1 10.5 1c.7 0 1.638-.23 2.437-.477a19.587 19.587 0 0 0 1.349-.476l.019-.007.004-.002h.001"/>
    </symbol>
    <symbol id="question-circle-fill" viewBox="0 0 16 16">
      <path d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0zM5.496 6.033h.825c.138 0 .248-.113.266-.25.09-.656.54-1.134 1.342-1.134.686 0 1.314.343 1.314 1.168 0 .635-.374.927-.965 1.371-.673.489-1.206 1.06-1.168 1.987l.003.217a.25.25 0 0 0 .25.246h.811a.25.25 0 0 0 .25-.25v-.105c0-.718.273-.927 1.01-1.486.609-.463 1.244-.977 1.244-2.056 0-1.511-1.276-2.241-2.673-2.241-1.267 0-2.655.59-2.75 2.286a.237.237 0 0 0 .241.247zm2.325 6.443c.61 0 1.029-.394 1.029-.927 0-.552-.42-.94-1.029-.94-.584 0-1.009.388-1.009.94 0 .533.425.927 1.01.927z"/>
    </symbol>
    <symbol id="hourglass-split" viewBox="0 0 16 16">
      <path d="M2.5 15a.5.5 0 1 1 0-1h1v-1a4.5 4.5 0 0 1 2.557-4.06c.29-.139.443-.377.443-.59v-.7c0-.213-.154-.451-.443-.59A4.5 4.5 0 0 1 3.5 3V2h-1a.5.5 0 0 1 0-1h11a.5.5 0 0 1 0 1h-1v1a4.5 4.5 0 0 1-2.557 4.06c-.29.139-.443.377-.443.59v.7c0 .213.154.451.443.59A4.5 4.5 0 0 1 12.5 13v1h1a.5.5 0 0 1 0 1h-11zm2-13v1c0 .537.12 1.045.337 1.5h6.326c.216-.455.337-.963.337-1.5V2h-7zm3 6.35c0 .701-.478 1.236-1.011 1.492A3.5 3.5 0 0 0 4.5 13s.866-1.299 3-1.48V8.35zm1 0v3.17c2.134.181 3 1.48 3 1.48a3.5 3.5 0 0 0-1.989-3.158C8.978 9.586 8.5 9.052 8.5 8.351z"/>
    </symbol>
    <symbol id="envelope" viewBox="0 0 16 16">
      <path d="M0 4a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2V4Zm2-1a1 1 0 0 0-1 1v.217l7 4.2 7-4.2V4a1 1 0 0 0-1-1H2Zm13 2.383-4.708 2.825L15 11.105V5.383Zm-.034 6.876-5.64-3.471L8 9.583l-1.326-.795-5.64 3.47A1 1 0 0 0 2 13h12a1 1 0 0 0 .966-.741ZM1 11.105l4.708-2.897L1 5.383v5.722Z"/>
    </symbol>
    <symbol id="envelope-fill" viewBox="0 0 16 16">
      <path d="M.05 3.555A2 2 0 0 1 2 2h12a2 2 0 0 1 1.95 1.555L8 8.414.05 3.555ZM0 4.697v7.104l5.803-3.558L0 4.697ZM6.761 8.83l-6.57 4.027A2 2 0 0 0 2 14h12a2 2 0 0 0 1.808-1.144l-6.57-4.027L8 9.586l-1.239-.757Zm3.436-.586L16 11.801V4.697l-5.803 3.546Z"/>
    </symbol>
    <symbol id="envelope-plus" viewBox="0 0 16 16">
      <path d="M2 2a2 2 0 0 0-2 2v8.01A2 2 0 0 0 2 14h5.5a.5.5 0 0 0 0-1H2a1 1 0 0 1-.966-.741l5.64-3.471L8 9.583l7-4.2V8.5a.5.5 0 0 0 1 0V4a2 2 0 0 0-2-2H2Zm3.708 6.208L1 11.105V5.383l4.708 2.825ZM1 4.217V4a1 1 0 0 1 1-1h12a1 1 0 0 1 1 1v.217l-7 4.2-7-4.2Z"/>
      <path d="M16 12.5a3.5 3.5 0 1 1-7 0 3.5 3.5 0 0 1 7 0Zm-3.5-2a.5.5 0 0 0-.5.5v1h-1a.5.5 0 0 0 0 1h1v1a.5.5 0 0 0 1 0v-1h1a.5.5 0 0 0 0-1h-1v-1a.5.5 0 0 0-.5-.5Z"/>
    </symbol>
    <symbol id="arrow-repeat" viewBox="0 0 16 16">
      <path d="M11.534 7h3.932a.25.25 0 0 1 .192.41l-1.966 2.36a.25.25 0 0 1-.384 0l-1.966-2.36a.25.25 0 0 1 .192-.41zm-11 2h3.932a.25.25 0 0 0 .192-.41L2.692 6.23a.25.25 0 0 0-.384 0L.342 8.59A.25.25 0 0 0 .534 9z"/>
      <path fill-rule="evenodd" d="M8 3c-1.552 0-2.94.707-3.857 1.818a.5.5 0 1 1-.771-.636A6.002 6.002 0 0 1 13.917 7H12.9A5.002 5.002 0 0 0 8 3zM3.1 9a5.002 5.002 0 0 0 8.757 2.182.5.5 0 1 1 .771.636A6.002 6.002 0 0 1 2.083 9H3.1z"/>
    </symbol>
    <symbol id="folder-symlink-fill" viewBox="0 0 16 16">
      <path d="M13.81 3H9.828a2 2 0 0 1-1.414-.586l-.828-.828A2 2 0 0 0 6.172 1H2.5a2 2 0 0 0-2 2l.04.87a2 2 0 0 0-.342 1.311l.637 7A2 2 0 0 0 2.826 14h10.348a2 2 0 0 0 1.991-1.819l.637-7A2 2 0 0 0 13.81 3M2.19 3q-.362.002-.683.12L1.5 2.98a1 1 0 0 1 1-.98h3.672a1 1 0 0 1 .707.293L7.586 3zm9.608 5.271-3.182 1.97c-.27.166-.616-.036-.616-.372V9.1s-2.571-.3-4 2.4c.571-4.8 3.143-4.8 4-4.8v-.769c0-.336.346-.538.616-.371l3.182 1.969c.27.166.27.576 0 .742"/>
    </symbol>
    <symbol id="image" viewBox="0 0 16 16">
      <path d="M6.002 5.5a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0z"/>
      <path d="M2.002 1a2 2 0 0 0-2 2v10a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V3a2 2 0 0 0-2-2h-12zm12 1a1 1 0 0 1 1 1v6.5l-3.777-1.947a.5.5 0 0 0-.577.093l-3.71 3.71-2.66-1.772a.5.5 0 0 0-.63.062L1.002 12V3a1 1 0 0 1 1-1h12z"/>
    </symbol>
    <symbol id="upload" viewBox="0 0 16 16">
      <path d="M.5 9.9a.5.5 0 0 1 .5.5v2.5a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1v-2.5a.5.5 0 0 1 1 0v2.5a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2v-2.5a.5.5 0 0 1 .5-.5z"/>
      <path d="M7.646 1.146a.5.5 0 0 1 .708 0l3 3a.5.5 0 0 1-.708.708L8.5 2.707V11.5a.5.5 0 0 1-1 0V2.707L5.354 4.854a.5.5 0 1 1-.708-.708l3-3z"/>
    </symbol>
  </svg>

  <div id="app">
    <nav class="navbar navbar-expand-lg navbar-light bg-glass shadow-sm mb-4">
      <div class="container">
        <a class="navbar-brand d-flex align-items-center" href="{{ url('/') }}">
          <svg width="32" height="32" viewBox="0 0 32 32" fill="none" class="me-2">
            <rect width="32" height="32" rx="8" fill="var(--color-primary)"/>
            <path d="M8 12L16 8L24 12L16 16L8 12Z" fill="white" opacity="0.9"/>
            <path d="M8 20L16 16L24 20L16 24L8 20Z" fill="white" opacity="0.7"/>
          </svg>
          <span class="fw-bold">{{ config('app.name', 'phpIP') }}</span>
        </a>

        @auth
        <form method="POST" action="/matter/search" class="ms-auto me-3 d-none d-lg-block">
          @csrf
          <div class="input-group">
            <span class="input-group-text bg-white border-end-0">
              <svg width="16" height="16" fill="var(--text-tertiary)" class="search-icon">
                <path d="M6.5 2a4.5 4.5 0 1 0 0 9 4.5 4.5 0 0 0 0-9zM1 6.5a5.5 5.5 0 1 1 8.5 4.65L15 16l-1-1-5.5-5.5A5.5 5.5 0 0 1 1 6.5z"/>
              </svg>
            </span>
            <input type="search" class="form-control border-start-0 border-end-0" id="matter-search" name="matter_search" placeholder="{{ __('Search matters...') }}" autocomplete="off">
            <select class="form-select" id="matter-option" name="search_field">
              <option value="Ref" selected>{{ __('Case Ref') }}</option>
              <option value="Responsible">{{ __('Responsible') }}</option>
              <option value="Title">{{ __('Title') }}</option>
              <option value="Client">{{ __('Client') }}</option>
              <option value="Applicant">{{ __('Applicant') }}</option>
            </select>
            <button class="btn btn-primary" type="submit">
              <svg width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                <path d="M6.5 2a4.5 4.5 0 1 0 0 9 4.5 4.5 0 0 0 0-9zM1 6.5a5.5 5.5 0 1 1 8.5 4.65L15 16l-1-1-5.5-5.5A5.5 5.5 0 0 1 1 6.5z"/>
              </svg>
            </button>
          </div>
        </form>
        @endauth

        <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="{{ __('Toggle navigation') }}">
          <svg width="24" height="24" fill="var(--text-primary)" viewBox="0 0 24 24">
            <path d="M3 12h18M3 6h18M3 18h18" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
          </svg>
        </button>

        <div class="collapse navbar-collapse" id="navbarNav">
          <!-- Left Side Of Navbar -->
          <ul class="navbar-nav me-auto">
          </ul>

          <!-- Right Side Of Navbar -->
          <ul class="navbar-nav align-items-center">
            @guest
              @if (Route::has('login'))
                <li class="nav-item">
                  <a class="nav-link btn btn-outline-primary btn-sm" href="{{ route('login') }}">{{ __('Login') }}</a>
                </li>
              @endif

              @if (Route::has('register'))
                <li class="nav-item ms-2">
                  <a class="nav-link btn btn-primary btn-sm" href="{{ route('register') }}">{{ __('Register') }}</a>
                </li>
              @endif
            @else
              <li class="nav-item">
                <a class="nav-link d-flex align-items-center" href="{{ route('home') }}">
                  <svg width="18" height="18" fill="currentColor" viewBox="0 0 24 24" class="me-2">
                    <path d="M3 13h8V3H3v10zm0 8h8v-6H3v6zm10 0h8V11h-8v10zm0-18v6h8V3h-8z"/>
                  </svg>
                  {{ __('Dashboard') }}
                </a>
              </li>

              <li class="nav-item dropdown">
                <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                  <svg width="18" height="18" fill="currentColor" viewBox="0 0 24 24" class="me-2">
                    <path d="M4 6h16v2H4zm0 5h16v2H4zm0 5h16v2H4z"/>
                  </svg>
                  {{ __('Matters') }}
                </a>
                <ul class="dropdown-menu dropdown-menu-end">
                  <div class="dropdown-header d-flex align-items-center px-3 py-2">
                    <svg width="16" height="16" fill="var(--color-primary)" viewBox="0 0 24 24" class="me-2">
                      <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8l-6-6z"/>
                    </svg>
                  <span class="fw-semibold">{{ __('Case Management') }}</span>
                  </div>
                  <li><a class="dropdown-item" href="{{ url('/matter') }}">
                    <svg width="16" height="16" fill="currentColor" viewBox="0 0 24 24" class="me-2">
                      <path d="M4 6h16v2H4zm0 5h16v2H4zm0 5h16v2H4z"/>
                    </svg>
                    {{ __('All Matters') }}
                  </a></li>
                  <li><a class="dropdown-item" href="{{ url('/matter?display_with=PAT') }}">
                    <svg width="16" height="16" fill="currentColor" viewBox="0 0 24 24" class="me-2">
                      <path d="M9 2v20l-7-5V7l7-5zm2 0h10v15h-10V2z"/>
                    </svg>
                    {{ __('Patents') }}
                  </a></li>
                  <li><a class="dropdown-item" href="{{ url('/matter?display_with=TM') }}">
                    <svg width="16" height="16" fill="currentColor" viewBox="0 0 24 24" class="me-2">
                      <path d="M12 2L2 7v10c0 5.55 3.84 10.74 9 12 5.16-1.26 9-6.45 9-12V7l-10-5z"/>
                    </svg>
                    {{ __('Trademarks') }}
                  </a></li>
                  @can('readwrite')
                  <li><hr class="dropdown-divider"></li>
                  <li><a class="dropdown-item text-primary" href="/matter/create?operation=new" data-bs-target="#ajaxModal" data-bs-toggle="modal" data-size="modal-sm">
                    <svg width="16" height="16" fill="currentColor" viewBox="0 0 24 24" class="me-2">
                      <path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z"/>
                    </svg>
                    {{ __('Create New Matter') }}
                  </a></li>
                  <li><a class="dropdown-item" href="/matter/create?operation=ops" data-bs-target="#ajaxModal" data-bs-toggle="modal" data-size="modal-sm">
                    <svg width="16" height="16" fill="currentColor" viewBox="0 0 24 24" class="me-2">
                      <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/>
                    </svg>
                    {{ __('Create from OPS') }}
                  </a></li>
                  @endcan
                </ul>
              </li>

              @can('readonly')
              @can('readwrite')
              <li class="nav-item dropdown">
                <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                  <svg width="18" height="18" fill="currentColor" viewBox="0 0 24 24" class="me-2">
                    <path d="M22.7 19l-9.1-9.1c.9-2.3.4-5-1.5-6.9-2-2-5-2.4-7.4-1.3L9 6 6 9 1.6 4.7C.4 7.1.9 10.1 2.9 12.1c1.9 1.9 4.6 2.4 6.9 1.5l9.1 9.1c.4.4 1 .4 1.4 0l2.3-2.3c.5-.4.5-1.1.1-1.4z"/>
                  </svg>
                  {{ __('Tools') }}
                </a>
                <ul class="dropdown-menu dropdown-menu-end">
                  <li><a class="dropdown-item" href="{{ url('/renewal') }}">
                    <svg width="16" height="16" fill="currentColor" viewBox="0 0 24 24" class="me-2">
                      <path d="M12 2C6.5 2 2 6.5 2 12s4.5 10 10 10 10-4.5 10-10S17.5 2 12 2zm4.2 14.2L11 13V7h1.5v5.2l4.5 2.7-.8 1.3z"/>
                    </svg>
                    {{ __('Manage renewals') }}
                  </a></li>
                  <li><a class="dropdown-item" href="{{ url('/fee') }}">
                    <svg width="16" height="16" fill="currentColor" viewBox="0 0 24 24" class="me-2">
                      <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1.41 16.09V20h-2.67v-1.93c-1.71-.36-3.16-1.46-3.27-3.4h1.96c.1.81.45 1.61 1.67 1.61 1.16 0 1.6-.64 1.6-1.46 0-.84-.68-1.22-2.05-1.68-1.65-.54-3.43-1.31-3.43-3.43 0-1.61 1.13-2.85 2.93-3.21V5h2.67v1.95c1.86.45 2.79 1.86 2.85 3.39H14.3c-.05-1.11-.64-1.63-1.63-1.63-1.01 0-1.46.54-1.46 1.34 0 .74.49 1.12 1.84 1.58 1.68.54 3.64 1.24 3.64 3.53.01 1.68-1.12 3.03-3.08 3.43z"/>
                    </svg>
                    {{ __('Renewal fees') }}
                  </a></li>
                  @can('admin')
                  <li><hr class="dropdown-divider"></li>
                  <li><a class="dropdown-item" href="{{ url('/rule') }}">{{ __('Rules') }}</a></li>
                  <li><a class="dropdown-item" href="{{ url('/document') }}">{{ __('Email Templates') }}</a></li>
                  <li><a class="dropdown-item" href="{{ url('/countries') }}">{{ __('Country Management') }}</a></li>
                  <li><a class="dropdown-item" href="{{ url('/template-member') }}">{{ __('Template Members') }}</a></li>
                  @endcan
                </ul>
              </li>
              @endcan
              @endcan

              <li class="nav-item dropdown">
                <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                  <svg width="18" height="18" fill="currentColor" viewBox="0 0 24 24" class="me-2">
                    <path d="M3 18h18v-2H3v2zm0-5h18v-2H3v2zm0-7v2h18V6H3z"/>
                  </svg>
                  {{ __('Tables') }}
                </a>
                <ul class="dropdown-menu dropdown-menu-end">
                  <li><a class="dropdown-item" href="{{ url('/actor') }}">{{ __('Actors') }}</a></li>
                  @can('admin')
                  <li><a class="dropdown-item" href="{{ url('/user') }}">{{ __('Users') }}</a></li>
                  <li><a class="dropdown-item" href="{{ url('/eventname') }}">{{ __('Event Names') }}</a></li>
                  <li><a class="dropdown-item" href="{{ url('/category') }}">{{ __('Categories') }}</a></li>
                  <li><a class="dropdown-item" href="{{ url('/role') }}">{{ __('Actor Roles') }}</a></li>
                  <li><a class="dropdown-item" href="{{ url('/default_actor') }}">{{ __('Default Actors') }}</a></li>
                  <li><a class="dropdown-item" href="{{ url('/type') }}">{{ __('Matter Types') }}</a></li>
                  <li><a class="dropdown-item" href="{{ url('/classifier_type') }}">{{ __('Classifier Types') }}</a></li>
                  @endcan
                </ul>
              </li>

              <li class="nav-item dropdown ms-3">
                <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                  <div class="user-avatar d-flex align-items-center justify-content-center me-2" style="width: 32px; height: 32px; background: var(--color-primary); color: white; border-radius: 50%; font-weight: 600; font-size: 14px;">
                    {{ strtoupper(substr(Auth::user()->login, 0, 2)) }}
                  </div>
                  <span class="d-none d-md-inline">{{ Auth::user()->login }}</span>
                </a>
                <ul class="dropdown-menu dropdown-menu-end">
                  <li class="px-3 py-2">
                    <div class="small text-tertiary">{{ __('Signed in as') }}</div>
                    <div class="fw-semibold">{{ Auth::user()->login }}</div>
                  </li>
                  <li><hr class="dropdown-divider"></li>
                  <li><a class="dropdown-item" href="{{ route('user.profile') }}">
                    <svg width="16" height="16" fill="currentColor" viewBox="0 0 24 24" class="me-2">
                      <path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/>
                    </svg>
                    {{ __('My Profile') }}
                  </a></li>
                  <li><hr class="dropdown-divider"></li>
                  <li><a class="dropdown-item text-danger" href="{{ route('logout') }}" onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                    <svg width="16" height="16" fill="currentColor" viewBox="0 0 24 24" class="me-2">
                      <path d="M17 7l-1.41 1.41L18.17 11H8v2h10.17l-2.58 2.58L17 17l5-5zM4 5h8V3H4c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h8v-2H4V5z"/>
                    </svg>
                    {{ __('Logout') }}
                  </a></li>
                </ul>
              </li>
            @endguest
          </ul>
        </div>
      </div>
    </nav>

    <main class="container-fluid px-4">
      @yield('content')
      <div id="ajaxModal" class="modal fade" role="dialog">
        <div class="modal-dialog">
          <!-- Modal content-->
          <div class="modal-content">
            <div class="modal-header bg-info text-light">
              <h1 class="modal-title fs-2">Ajax title placeholder</h1>
              <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
              <div class="spinner-border" role="status">
                <span class="sr-only">Loading...</span>
              </div>
            </div>
            <div class="modal-footer">
              <span id="footerAlert" class="alert float-start"></span>
              <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Close') }}</button>
            </div>
          </div>
        </div>
      </div>
    </main>
  </div>
  @yield('script')
</body>

</html>
