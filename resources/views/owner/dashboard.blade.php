@extends('layouts.owner')

@section('title', 'Owner Portal | iKwenta')

@push('head')
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.5.1/dist/chart.umd.min.js" defer></script>
@endpush

@php
    $weeklyPayload = [
        'labels' => $weeklyTrend['labels'],
        'added' => $weeklyTrend['added'],
        'collected' => $weeklyTrend['collected'],
    ];

    $quickActions = [
        [
            'label' => 'Add customer',
            'href' => route('owner.customers'),
            'description' => 'Create a new customer account',
        ],
        [
            'label' => 'View customers',
            'href' => route('owner.customers'),
            'description' => 'Review customer balances and activity',
        ],
        [
            'label' => 'View payments',
            'href' => route('owner.payments'),
            'description' => 'Track recent collections and checks',
        ],
        [
            'label' => 'View activity logs',
            'href' => route('owner.activity-logs'),
            'description' => 'Inspect account changes and audit trail',
        ],
    ];
@endphp

@section('content')
    <section class="owner-section" id="owner-section" data-owner-section aria-busy="false">
        <div class="owner-quick-actions" aria-label="Owner quick actions">
            <div class="section-heading">
                <div>
                    <span class="owner-eyebrow">Operations</span>
                    <h2>Quick actions</h2>
                </div>
            </div>

            <div class="owner-quick-actions__grid">
                @foreach ($quickActions as $action)
                    <a href="{{ $action['href'] }}" class="owner-action-card">
                        <span class="owner-action-card__label">{{ $action['label'] }}</span>
                        <span class="owner-action-card__description">{{ $action['description'] }}</span>
                    </a>
                @endforeach
            </div>
        </div>

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

                        <strong class="owner-stat-card__value"> ₱{{ number_format($weeklyTrend['total'], 2) }} </strong>
                    </div>
                </div>

                <div class="owner-chart">
                    <canvas id="owner-weekly-debts-chart" role="img"
                        aria-label="Debt added and collections received per day this week, grouped bar chart"></canvas>
                </div>
            </article>

            {{-- Card 2: recent debt payoff celebration --}}
            <button type="button" class="owner-stat-card owner-stat-card--paid-off"
                data-open-modal="paid-off-customers-modal" aria-haspopup="dialog"
                aria-controls="paid-off-customers-modal">
                @if ($latestPaidOffCustomer)
                    @if ($latestPaidOffCustomer['avatar_path'])
                        <img class="owner-stat-card__profile" src="{{ asset('storage/'.$latestPaidOffCustomer['avatar_path']) }}" alt="">
                    @else
                        <img class="owner-stat-card__profile" src="{{ asset($latestPaidOffCustomer['gender'] === 'female' ? 'images/avatar-girl.svg' : 'images/avatar-boy.svg') }}" alt="">
                    @endif

                    <div class="owner-stat-card__meta">
                        <span class="owner-stat-card__label">Wow!</span>
                        <strong class="owner-stat-card__value">{{ $latestPaidOffCustomer['name'] }}</strong>
                        <span class="owner-stat-card__hint">paid their debt off!</span>
                    </div>
                @else
                <span class="owner-stat-card__icon" aria-hidden="true">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                        stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M20 6L9 17l-5-5" />
                    </svg>
                </span>

                <div class="owner-stat-card__meta">
                    <span class="owner-stat-card__label">A debt-free moment</span>
                    <strong class="owner-stat-card__value">No recent payoffs yet</strong>
                    <span class="owner-stat-card__hint">Check back after a customer settles</span>
                </div>
                @endif
            </button>

            {{-- Card 3: customers in debt --}}
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

                    <strong class="owner-stat-card__value"> {{ $customersInDebt }} </strong>

                    <span class="owner-stat-card__hint"> out of {{ $customerCount }} customers </span>
                </div>
            </article>

            {{--
                Pending Debt Items — parked until the intended metric is settled.
                Two readings fit the label: open debt records, or the sum of line
                items across those records. The card used to show a hard-coded "46",
                which was removed rather than replaced with a guess.
            --}}
            {{--
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

                    <span class="owner-stat-card__hint"> open debt transactions </span>
                </div>
            </article>
            --}}
        </div>

        {{-- =================== Total debt ranking =================== --}}
        <section class="owner-ranking" aria-labelledby="owner-total-ranking-title">
            <div class="section-heading">
                <div>
                    <span class="owner-eyebrow"> Customer Insights </span>

                    <h2 id="owner-total-ranking-title" tabindex="-1">Total Debt Ranking</h2>

                    <p class="section-heading__description">
                        Customers ranked by total debt recorded in the Customers tab.
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
                            <th scope="col">Remaining</th>
                        </tr>
                    </thead>

                    <tbody data-total-debt-ranking>
                        @forelse ($totalDebtRanking as $entry)
                            <tr class="owner-rank-row" data-customer-id="{{ $entry['customer_id'] }}" tabindex="0" role="button" aria-label="View {{ $entry['name'] }} details">
                                <td data-label="Rank">
                                    <span class="owner-rank-badge {{ $loop->iteration <= 3 ? 'owner-rank-badge--top' : '' }}">
                                        {{ $entry['rank'] }}
                                    </span>
                                </td>

                                <td data-label="Customer">
                                    <div class="owner-customer-cell">
                                        @if ($entry['avatar_path'])
                                            <img class="owner-ranking-avatar" src="{{ asset('storage/'.$entry['avatar_path']) }}" alt="{{ $entry['name'] }}">
                                        @else
                                            <img class="owner-ranking-avatar" src="{{ asset($entry['gender'] === 'female' ? 'images/avatar-girl.svg' : 'images/avatar-boy.svg') }}" alt="{{ ucfirst($entry['gender']) }} avatar">
                                        @endif
                                        <div>
                                        <strong> {{ $entry['name'] }} </strong>
                                        </div>
                                    </div>
                                </td>

                                <td data-label="Total Debt" class="owner-amount">
                                    ₱{{ number_format($entry['total_debt'], 2) }}
                                </td>

                                <td data-label="Paid" class="owner-amount owner-amount--paid">
                                    <span class="owner-paid-cell">
                                        <span>₱{{ number_format($entry['paid'], 2) }}</span>
                                        <span class="owner-progress" role="img"
                                            aria-label="{{ (int) $entry['paid_progress'] }} percent of recorded debt repaid">
                                            <span class="owner-progress__bar"
                                                style="width: {{ max(0, min(100, (int) $entry['paid_progress'])) }}%"></span>
                                        </span>
                                    </span>
                                </td>

                                <td data-label="Unpaid" class="owner-amount owner-amount--unpaid">
                                    ₱{{ number_format($entry['remaining_balance'], 2) }}
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5">No customer debt records yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <section class="owner-ranking owner-ranking--longest" aria-labelledby="owner-longest-ranking-title">
            <div class="section-heading">
                <div>
                    <span class="owner-eyebrow"> Aging Accounts </span>
                    <h2 id="owner-longest-ranking-title">Longest Outstanding Debt</h2>
                    <p class="section-heading__description">Customers with an unpaid balance for the longest number of days.</p>
                </div>
            </div>
            <div class="owner-table-wrap">
                <table class="owner-rank-table">
                    <thead><tr><th>Rank</th><th>Customer</th><th>Oldest loaned</th><th>Days outstanding</th><th>Remaining</th></tr></thead>
                    <tbody>
                        @forelse ($longestOutstandingRanking as $entry)
                            <tr>
                                <td data-label="Rank"><span class="owner-rank-badge {{ $loop->iteration <= 3 ? 'owner-rank-badge--top' : '' }}">{{ $entry['rank'] }}</span></td>
                                <td data-label="Customer"><div class="owner-customer-cell">
                                    @if ($entry['avatar_path'])
                                        <img class="owner-ranking-avatar" src="{{ asset('storage/'.$entry['avatar_path']) }}" alt="{{ $entry['name'] }}">
                                    @else
                                        <img class="owner-ranking-avatar" src="{{ asset($entry['gender'] === 'female' ? 'images/avatar-girl.svg' : 'images/avatar-boy.svg') }}" alt="{{ ucfirst($entry['gender']) }} avatar">
                                    @endif
                                    <strong>{{ $entry['name'] }}</strong>
                                </div></td>
                                    <td data-label="Oldest loaned">{{ \Illuminate\Support\Carbon::parse($entry['oldest_open_loan_at'])->format('M d, Y') }}</td>
                                <td data-label="Days outstanding" class="owner-amount owner-amount--unpaid">{{ number_format((int) $entry['days_outstanding'], 0) }} days</td>
                                <td data-label="Remaining" class="owner-amount">₱{{ number_format($entry['remaining_balance'], 2) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5">No outstanding customer debts.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </section>

    <div class="modal owner-paid-off-modal" id="paid-off-customers-modal" aria-hidden="true">
        <div class="modal-backdrop" data-modal-close></div>
        <div class="modal-dialog" role="dialog" aria-modal="true" aria-labelledby="paid-off-customers-title">
            <button type="button" class="modal-close" data-modal-close aria-label="Close">×</button>
            <span class="modal-badge">Debt-free customers</span>
            <h2 class="modal-title" id="paid-off-customers-title">Recent payoffs</h2>
            <p class="modal-subtitle">Customers who cleared their full balance in the last {{ \App\Models\Debt::ARCHIVE_RETENTION_DAYS }} days.</p>

            <ul class="owner-paid-off-list">
                @forelse ($recentPaidOffCustomers as $customer)
                    <li class="owner-paid-off-list__item">
                        @if ($customer['avatar_path'])
                            <img class="owner-paid-off-list__avatar" src="{{ asset('storage/'.$customer['avatar_path']) }}" alt="">
                        @else
                            <img class="owner-paid-off-list__avatar" src="{{ asset($customer['gender'] === 'female' ? 'images/avatar-girl.svg' : 'images/avatar-boy.svg') }}" alt="">
                        @endif
                        <div class="owner-paid-off-list__details">
                            <strong>{{ $customer['name'] }}</strong>
                            <time datetime="{{ $customer['latest_paid_at']->toIso8601String() }}">
                                {{ $customer['latest_paid_at']->timezone(config('app.timezone'))->format('M d, Y \a\t g:i A') }}
                            </time>
                        </div>
                    </li>
                @empty
                    <li class="owner-paid-off-list__empty">No customers have paid off their full balance in the last {{ \App\Models\Debt::ARCHIVE_RETENTION_DAYS }} days.</li>
                @endforelse
            </ul>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        (function () {
            const table = document.querySelector('[data-total-debt-ranking]');
            if (!table) return;

            const openCustomer = (id) => {
                window.location.href = '{{ route('owner.customers') }}?id=' + encodeURIComponent(id);
            };

            table.addEventListener('click', (event) => {
                const row = event.target.closest('[data-customer-id]');
                if (row) openCustomer(row.dataset.customerId);
            });

            table.addEventListener('keydown', (event) => {
                const row = event.target.closest('[data-customer-id]');
                if (row && (event.key === 'Enter' || event.key === ' ')) {
                    event.preventDefault();
                    openCustomer(row.dataset.customerId);
                }
            });
        })();
    </script>

    <script>
        (function () {
            const canvas = document.getElementById('owner-weekly-debts-chart');
            if (!canvas) return;

            const cssVar = (name) => getComputedStyle(document.documentElement).getPropertyValue(name).trim();

            const formatPeso = (value) =>
                '₱' + Number(value).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

            const weeklyData = @json($weeklyPayload);

            const buildChart = () => {
                const theme = document.documentElement.getAttribute('data-theme') === 'dark' ? 'dark' : 'light';

                return new Chart(canvas, {
                    type: 'bar',
                    data: {
                        labels: weeklyData.labels,
                        datasets: [
                            {
                                label: 'Debt added',
                                data: weeklyData.added,
                                backgroundColor: cssVar('--teal'),
                                hoverBackgroundColor: cssVar('--indigo'),
                                borderRadius: 6,
                                maxBarThickness: 28,
                            },
                            {
                                label: 'Collections',
                                data: weeklyData.collected,
                                backgroundColor: cssVar('--indigo'),
                                hoverBackgroundColor: cssVar('--teal'),
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
                            legend: { position: 'bottom', labels: { color: cssVar('--text-muted'), boxHeight: 3, boxWidth: 18, font: { size: 12 } } },
                            tooltip: {
                                callbacks: {
                                    label: (item) => item.dataset.label + ': ' + formatPeso(item.parsed.y),
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