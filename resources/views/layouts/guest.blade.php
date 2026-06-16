<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=poppins:300,400,500,600,700&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    @php($isLoginPage = request()->routeIs('login'))
    <body @class([
        'font-sans text-gray-900 antialiased',
        'bg-slate-100' => ! $isLoginPage,
        'bg-[#0a5e71] lg:h-screen lg:overflow-hidden' => $isLoginPage,
    ])>
        @if ($isLoginPage)
            <div class="relative min-h-screen overflow-hidden lg:h-screen">
                <div class="ids-login-grid absolute inset-0"></div>
                <div class="ids-login-orb ids-login-orb-one"></div>
                <div class="ids-login-orb ids-login-orb-two"></div>
                <div class="ids-login-orb ids-login-orb-three"></div>

                <main class="relative z-10 flex min-h-screen flex-col lg:h-screen">
                    {{ $slot }}
                </main>
            </div>
        @else
            <div class="min-h-screen flex flex-col sm:justify-center items-center pt-6 sm:pt-0 bg-gray-100">
                <div>
                    <a href="/">
                        <x-application-logo class="w-20 h-20 fill-current text-gray-500" />
                    </a>
                </div>

                <div class="w-full sm:max-w-md mt-6 px-6 py-4 bg-white shadow-md overflow-hidden sm:rounded-lg">
                    {{ $slot }}
                </div>
            </div>
        @endif
    </body>
</html>
