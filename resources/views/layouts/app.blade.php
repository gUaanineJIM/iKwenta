<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="iKwenta — Debt & Store Management System. Manage products, customers, debts, payments, and transactions.">

    <title>@yield('title', 'iKwenta — Debt & Store Management')</title>

    <link rel="icon" type="image/svg+xml" href="{{ asset('images/iKwenta-logo.svg') }}">

    <script>
        (function () {
            try {
                var t = localStorage.getItem('ikwenta-theme');
                if (!t && window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) t = 'dark';
                document.documentElement.setAttribute('data-theme', t || 'light');
            } catch (e) {
                document.documentElement.setAttribute('data-theme', 'light');
            }
        })();
    </script>

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    @stack('styles')
</head>

<body>

    @yield('content')

    {{-- Global modals --}}
    @include('modals.customer-login')
    @include('modals.owner-login')
    @include('modals.forgot-password')

    @stack('scripts')

</body>
</html>