<!doctype html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />

    <title>Owner Portal | iKwenta</title>

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

<body class="owner-page">
    {{-- ========================= Shell ========================== --}}
    <div class="owner-shell" data-owner-shell>
        {{-- Sidebar --}}
        <aside class="owner-sidebar" id="owner-sidebar" data-owner-sidebar aria-label="Owner dashboard navigation">
            <x-owner.sidebar :active="$activeSection ?? 'overview'" />
        </aside>

        {{-- Main content (owner sections will load here) --}}
        <main class="owner-main" id="owner-main">
            {{-- =================== Header =================== --}}
            <header class="owner-header">
                <div class="owner-header__inner">
                    <div class="owner-header__left">
                        <button type="button" class="sidebar-toggle" data-owner-sidebar-toggle
                            aria-controls="owner-sidebar" aria-expanded="false" aria-label="Toggle sidebar">
                            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                <path d="M4 7H20M4 12H20M4 17H20" stroke="currentColor" stroke-width="2"
                                    stroke-linecap="round" />
                            </svg>
                        </button>

                    </div>

                    <div class="owner-header__right">
                        <div class="owner-welcome">
                            <span class="owner-welcome__label"> Owner Portal </span>

                            <strong> Store Owner </strong>
                        </div>

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
                    </div>
                </div>
            </header>

            <section class="owner-section" id="owner-section" data-owner-section aria-busy="false">
                {{-- Top containers — component content pending. --}}
                <div class="owner-top-grid" aria-label="Owner overview">
                    <article class="owner-top-card" aria-label="Container 1">
                        <div class="rounded-lg border border-gray-200 bg-white p-6">
                            <h2 class="text-sm font-medium text-gray-900 ">Debts per day this week</h2>

                            <div class="mt-4 h-64">
                                <canvas id="weekly-orders-bar-chart" role="img"
                                    aria-label="Orders this week, bar chart"></canvas>
                            </div>

                            <table class="sr-only">
                                <caption>
                                    Debts per day this week
                                </caption>
                                <thead>
                                    <tr>
                                        <th scope="col">Day</th>
                                        <th scope="col">Orders</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <th scope="row">Mon</th>
                                        <td>12</td>
                                    </tr>
                                    <tr>
                                        <th scope="row">Tue</th>
                                        <td>19</td>
                                    </tr>
                                    <tr>
                                        <th scope="row">Wed</th>
                                        <td>14</td>
                                    </tr>
                                    <tr>
                                        <th scope="row">Thu</th>
                                        <td>22</td>
                                    </tr>
                                    <tr>
                                        <th scope="row">Fri</th>
                                        <td>27</td>
                                    </tr>
                                    <tr>
                                        <th scope="row">Sat</th>
                                        <td>32</td>
                                    </tr>
                                    <tr>
                                        <th scope="row">Sun</th>
                                        <td>18</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                    </article>

                    <article class="owner-top-card" aria-label="Container 2"></article>

                    <article class="owner-top-card" aria-label="Container 3"></article>
                </div>
            </section>



        </main>

        <div class="owner-backdrop" data-owner-backdrop hidden aria-hidden="true"></div>
    </div>
</body>

</html>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.5.1/dist/chart.umd.min.js"></script>
<script>
    const weeklyOrdersBarChartCanvas = document.getElementById('weekly-orders-bar-chart')

    new Chart(weeklyOrdersBarChartCanvas, {
        type: 'bar',
        data: {
            labels: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
            datasets: [
                {
                    label: 'Orders',
                    data: [12, 19, 14, 22, 27, 32, 18],
                    backgroundColor: '#6366f1',
                    hoverBackgroundColor: '#4338ca',
                    borderRadius: 4,
                    maxBarThickness: 32,
                },
            ],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: {
                mode: 'index',
                intersect: false,
            },
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        label: (tooltipItem) => `${tooltipItem.formattedValue} orders`,
                    },
                },
            },
            scales: {
                x: {
                    grid: { display: false },
                    ticks: { color: '#4b5563' },
                },
                y: {
                    beginAtZero: true,
                    grid: { color: '#e5e7eb' },
                    ticks: { color: '#4b5563' },
                },
            },
        },
    })
</script>