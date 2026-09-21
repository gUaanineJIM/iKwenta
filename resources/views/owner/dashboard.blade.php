@extends('layouts.owner')

@section('title', 'Owner Portal | iKwenta')

@push('head')
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.5.1/dist/chart.umd.min.js" defer></script>
@endpush

@section('content')
    @php
        // Frontend-only mock ranking: customers sorted by highest unpaid debt.
        $rankingSeed = [
            ['name' => 'Maria Santos', 'code' => 'C-1021', 'total' => 25400.00, 'paid' => 4000.00],
            ['name' => 'Juan Dela Cruz', 'code' => 'C-1004', 'total' => 18750.00, 'paid' => 2500.00],
            ['name' => 'Ana Reyes', 'code' => 'C-1033', 'total' => 12300.00, 'paid' => 1800.00],
            ['name' => 'Carlos Mendoza', 'code' => 'C-1015', 'total' => 9850.00, 'paid' => 3200.00],
            ['name' => 'Liza Fernandez', 'code' => 'C-1009', 'total' => 7400.00, 'paid' => 3900.00],
            ['name' => 'Ramon Garcia', 'code' => 'C-1027', 'total' => 5600.00, 'paid' => 4100.00],
        ];

        $ranking = [];

        foreach ($rankingSeed as $index => $row) {
            $row['rank'] = $index + 1;
            $row['unpaid'] = $row['total'] - $row['paid'];
            $row['progress'] = $row['total'] > 0 ? round(($row['paid'] / $row['total']) * 100) : 0;
            $ranking[] = $row;
        }
    @endphp

    <section class="owner-section" id="owner-section" data-owner-section aria-busy="false">
        {{-- =================== Top statistic cards =================== --}}
        <div class="owner-top-grid" aria-label="Owner overview">
            {{-- Card 1: weekly debts chart --}}
            <article class="owner-stat-card owner-stat-card--chart">
                <div class="owner-stat-card__header">
                    <span class="owner-stat-card__icon" aria-hidden="true">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                            stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M3 3v18h18" />
                            <path d="M7 14l3-4 4 4 5-7" />
                        </svg>
                    </span>

                    <div class="owner-stat-card__meta">
                        <span class="owner-stat-card__label"> Debts Added This Week </span>

                        <strong class="owner-stat-card__value"> ₱53,590 </strong>
                    </div>
                </div>

                <div class="owner-chart">
                    <canvas id="owner-weekly-debts-chart" role="img"
                        aria-label="Total debt added per day this week, bar chart"></canvas>
                </div>
            </article>

            {{-- Card 2: customers in debt --}}
            <article class="owner-stat-card owner-stat-card--customers">
                <span class="owner-stat-card__icon" aria-hidden="true">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                        stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="9" cy="8" r="3.5" />
                        <path d="M2.5 20C3.5 16.5 6 15 9 15c3 0 5.5 1.5 6.5 5" />
                        <circle cx="17" cy="9" r="2.5" />
                        <path d="M16.5 15c2.5 0 4.5 1.5 5 5" />
                    </svg>
                </span>

                <div class="owner-stat-card__meta">
                    <span class="owner-stat-card__label"> Customers in Debt </span>

                    <strong class="owner-stat-card__value"> 28 </strong>

                    <span class="owner-stat-card__hint"> out of 42 active customers </span>
                </div>
            </article>

            {{-- Card 3: pending debt items --}}
            <article class="owner-stat-card owner-stat-card--debts">
                <span class="owner-stat-card__icon" aria-hidden="true">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                        stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M21 8L12 3L3 8V16L12 21L21 16V8Z" />
                        <path d="M3 8L12 13L21 8" />
                        <path d="M12 13V21" />
                    </svg>
                </span>

                <div class="owner-stat-card__meta">
                    <span class="owner-stat-card__label"> Pending Debt Items </span>

                    <strong class="owner-stat-card__value"> 46 </strong>

                    <span class="owner-stat-card__hint"> open debt transactions </span>
                </div>
            </article>
        </div>

        {{-- =================== Debt ranking table =================== --}}
        <section class="owner-ranking" aria-labelledby="owner-ranking-title">
            <div class="section-heading">
                <div>
                    <span class="owner-eyebrow"> Customer Insights </span>

                    <h2 id="owner-ranking-title" tabindex="-1">Debt Ranking</h2>

                    <p class="section-heading__description">
                        Customers with the highest unpaid debt, top to bottom.
                    </p>
                </div>
            </div>

            <div class="owner-table-wrap">
                <table class="owner-rank-table">
                    <thead>
                        <tr>
                            <th scope="col">Rank</th>
                            <th scope="col">Customer</th>
                            <th scope="col">Total Debt</th>
                            <th scope="col">Paid</th>
                            <th scope="col">Unpaid</th>
                        </tr>
                    </thead>

                    <tbody>
                        @foreach ($ranking as $entry)
                            <tr>
                                <td data-label="Rank">
                                    <span class="owner-rank-badge {{ $loop->iteration <= 3 ? 'owner-rank-badge--top' : '' }}">
                                        {{ $entry['rank'] }}
                                    </span>
                                </td>

                                <td data-label="Customer">
                                    <div class="owner-customer-cell">
                                        <strong> {{ $entry['name'] }} </strong>

                                        <small> {{ $entry['code'] }} </small>

                                        <span class="owner-progress" aria-hidden="true">
                                            <span class="owner-progress__bar" style="width: {{ $entry['progress'] }}%;"></span>
                                        </span>
                                    </div>
                                </td>

                                <td data-label="Total Debt" class="owner-amount">
                                    ₱{{ number_format($entry['total'], 2) }}
                                </td>

                                <td data-label="Paid" class="owner-amount owner-amount--paid">
                                    ₱{{ number_format($entry['paid'], 2) }}
                                </td>

                                <td data-label="Unpaid" class="owner-amount owner-amount--unpaid">
                                    ₱{{ number_format($entry['unpaid'], 2) }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>
    </section>
@endsection

@push('scripts')
    <script>
        (function () {
            const canvas = document.getElementById('owner-weekly-debts-chart');
            if (!canvas) return;

            const cssVar = (name) => getComputedStyle(document.documentElement).getPropertyValue(name).trim();

            const formatPeso = (value) =>
                '₱' + Number(value).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

            const buildChart = () => {
                const theme = document.documentElement.getAttribute('data-theme') === 'dark' ? 'dark' : 'light';

                return new Chart(canvas, {
                    type: 'bar',
                    data: {
                        labels: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
                        datasets: [
                            {
                                label: 'Debt added',
                                data: [4250, 6870, 5400, 8120, 9350, 12900, 6100],
                                backgroundColor: cssVar('--teal'),
                                hoverBackgroundColor: cssVar('--indigo'),
                                borderRadius: 6,
                                maxBarThickness: 28,
                            },
                        ],
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        interaction: { mode: 'index', intersect: false },
                        plugins: {
                            legend: { display: false },
                            tooltip: {
                                callbacks: {
                                    label: (item) => 'Debt: ' + formatPeso(item.parsed.y),
                                },
                            },
                        },
                        scales: {
                            x: {
                                grid: { display: false },
                                ticks: { color: cssVar('--text-muted') },
                            },
                            y: {
                                beginAtZero: true,
                                grid: {
                                    color: theme === 'dark' ? 'rgba(166, 174, 230, 0.14)' : 'rgba(70, 75, 113, 0.10)',
                                },
                                ticks: {
                                    color: cssVar('--text-muted'),
                                    callback: (value) => {
                                        if (value >= 1000) return '₱' + Number(value / 1000).toLocaleString() + 'k';
                                        return '₱' + value;
                                    },
                                },
                            },
                        },
                    },
                });
            };

            const init = () => {
                if (typeof Chart === 'undefined') {
                    setTimeout(init, 60);
                    return;
                }

                let chart = buildChart();

                document.querySelectorAll('[data-theme-toggle]').forEach((button) => {
                    button.addEventListener('click', () => {
                        if (!chart) return;
                        chart.destroy();
                        chart = buildChart();
                    });
                });
            };

            init();
        })();
    </script>
@endpush