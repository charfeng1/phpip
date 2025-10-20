<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>{{ config('app.name', 'phpIP') }}</title>
        @vite(['resources/js/app.js'])
    </head>
    <body class="welcome-page">
        <div class="welcome-shell">
            @if (Route::has('login'))
                <div class="welcome-links top-right">
                    @auth
                        <a href="{{ url('/home') }}">Home</a>
                    @else
                        <a href="{{ route('login') }}">Login</a>

                        @if (Route::has('register'))
                            <a href="{{ route('register') }}">Register</a>
                        @endif
                    @endauth
                </div>
            @endif

            <div class="welcome-content">
                <div class="welcome-title">
                    {{ config('app.name', 'phpIP') }}
                </div>
                <div class="welcome-subtitle">
                    {{ __('IP rights portfolio manager and docketing system') }}
                </div>

                <div class="welcome-links">
                    <a href="https://github.com/jjdejong/phpip/wiki">{{ __('Documentation') }}</a>
                    <a href="https://github.com/jjdejong/phpip/issues">{{ __('Submit bugs') }}</a>
                    <a href="https://github.com/jjdejong/phpip">{{ __('Retrieve sources on GitHub') }}</a>
                </div>
            </div>
        </div>
    </body>
</html>
