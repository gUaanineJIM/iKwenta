<!doctype html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />

    <title>My Account | iKwenta</title>

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

<body class="customer-page">
    {{-- ========================= Header ========================== --}}
    <header class="customer-header">
        <div class="customer-header__inner">
            <a href="{{ route('customer.dashboard') }}" class="brand">
                <span class="brand__mark">i</span>
                <span class="brand__name">Kwenta</span>
            </a>

            <div class="customer-header__right">

                <button
                    type="button"
                    class="sidebar-toggle"
                    data-sidebar-toggle
                    aria-controls="customer-sidebar"
                    aria-expanded="false"
                    aria-label="Open menu"
                >
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <path d="M4 7H20M4 12H20M4 17H20" stroke="currentColor" stroke-width="2" stroke-linecap="round" />
                    </svg>
                </button>

                <button type="button" class="theme-toggle" data-theme-toggle aria-label="Toggle dark mode">
                    <span class="icon-sun" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="4"></circle>
                            <path d="M12 2v2"></path>
                            <path d="M12 20v2"></path>
                            <path d="m4.93 4.93 1.41 1.41"></path>
                            <path d="m17.66 17.66 1.41 1.41"></path>
                            <path d="M2 12h2"></path>
                            <path d="M20 12h2"></path>
                            <path d="m6.34 17.66-1.41 1.41"></path>
                            <path d="m19.07 4.93-1.41 1.41"></path>
                        </svg>
                    </span>

                    <span class="icon-moon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"></path>
                        </svg>
                    </span>
                </button>

                <div class="customer-welcome">
                    <span class="customer-welcome__label"> Welcome </span>

                    <strong> {{ $customer->full_name }} </strong>
                </div>

                <form method="POST" action="{{ route('customer.logout') }}">
                    @csrf

                    <button type="submit" class="btn btn--outline">Sign Out</button>
                </form>
            </div>
        </div>
    </header>

    {{-- ========================= Shell ========================== --}}
    <div class="customer-shell" data-shell>
        {{-- Sidebar --}}
        <aside class="customer-sidebar" id="customer-sidebar" data-sidebar aria-label="Dashboard navigation">
            <x-customer.sidebar :active="$activeSection ?? 'overview'" />
        </aside>

        <div class="customer-backdrop" data-sidebar-backdrop hidden aria-hidden="true"></div>

        {{-- Main content (dashboard sections load here) --}}
        <main class="customer-main" id="customer-main">
            <section
                class="customer-section"
                id="customer-section"
                data-section
                data-login-url="{{ route('landing') }}"
                aria-busy="false"
            >
                @include('customer.partials.section')
            </section>
        </main>
    </div>
</body>

</html>