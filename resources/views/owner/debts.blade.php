@extends('layouts.owner', ['activeSection' => 'debts'])

@section('title', 'Debts | iKwenta')

@push('head')
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.5.1/dist/chart.umd.min.js" defer></script>
@endpush

@php
    use App\Enums\DebtStatus;

    $trendLabels = array_values(array_column($monthlyTrend, 'label'));
    $trendAdded = array_values(array_column($monthlyTrend, 'added'));
    $trendCollected = array_values(array_column($monthlyTrend, 'collected'));
    $statusLabels = array_column($statusBreakdown, 'label');
    $statusValues = array_map(fn (array $bucket): float => (float) $bucket['total'], $statusBreakdown);
    $trendPayload = ['labels' => $trendLabels, 'added' => $trendAdded, 'collected' => $trendCollected];
    $statusPayload = ['labels' => $statusLabels, 'values' => $statusValues, 'keys' => array_column($statusBreakdown, 'key')];
@endphp

@section('content')
    <section class="owner-section owner-debts" data-owner-debts>
        {{-- =================== Heading =================== --}}
        <div class="page-heading owner-debts__heading">
            <div>
                <span class="owner-eyebrow"> Debt Analytics </span>

                <h1>Debts</h1>

                <p>Live overview of what customers owe, how much has been reclaimed, and how old each balance is.</p>
            </div>
        </div>

        {{-- =================== Summary cards =================== --}}
        <div class="owner-debt-grid" aria-label="Debt summary">
            <article class="owner-stat-card owner-debt-card owner-debt-card--outstanding">
                <span class="owner-stat-card__icon" aria-hidden="true">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M12 2V22" />
                        <path d="M17 6.5C17 4.5 14.9 3.5 12 3.5C9.3 3.5 7 4.6 7 6.5C7 9 12 8.5 12 10.5C12 12 16.5 12.5 16.5 13.5C16.5 15.5 14.4 16.5 12 16.5C9.3 16.5 7 15.5 7 13.5" />
                    </svg>
                </span>

                <div class="owner-stat-card__meta">
                    <span class="owner-stat-card__label"> Outstanding Balance </span>

                    <strong class="owner-stat-card__value" data-outstanding-total>
                        ₱{{ number_format($summary['outstanding_total'], 2) }}
                    </strong>

                    <span class="owner-stat-card__hint">
                        {{ $summary['customers_in_debt'] }} {{ $summary['customers_in_debt'] === 1 ? 'customer' : 'customers' }} still owing
                    </span>
                </div>
            </article>

            <article class="owner-stat-card owner-debt-card owner-debt-card--recorded">
                <span class="owner-stat-card__icon" aria-hidden="true">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M21 8L12 3L3 8V16L12 21L21 16V8Z" />
                        <path d="M3 8L12 13L21 8" />
                        <path d="M12 13V21" />
                    </svg>
                </span>

                <div class="owner-stat-card__meta">
                    <span class="owner-stat-card__label"> Total Unpaid Debt </span>

                    <strong class="owner-stat-card__value" data-total-unpaid>
                        ₱{{ number_format($summary['total_unpaid'], 2) }}
                    </strong>

                    <span class="owner-stat-card__hint"> across all customers </span>
                </div>
            </article>

            <article class="owner-stat-card owner-debt-card owner-debt-card--collected">
                <span class="owner-stat-card__icon" aria-hidden="true">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="2.5" y="5" width="19" height="14" rx="2" />
                        <path d="M2.5 10H21.5" />
                        <circle cx="5.8" cy="14.5" r="1.1" fill="currentColor" />
                        <path d="M17 13L14 16L12.5 14.5" />
                    </svg>
                </span>

                <div class="owner-stat-card__meta">
                    <span class="owner-stat-card__label"> Collected </span>

                    <strong class="owner-stat-card__value" data-collected-total>
                        ₱{{ number_format($summary['collected_total'], 2) }}
                    </strong>

                    <span class="owner-stat-card__hint"> {{ $summary['collections_rate'] }}% of debt recorded </span>
                </div>
            </article>

            <article class="owner-stat-card owner-debt-card owner-debt-card--open">
                <span class="owner-stat-card__icon" aria-hidden="true">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M3 12H7L9 6L13 18L15 10L17 12H21" />
                        <circle cx="12" cy="12" r="9" />
                    </svg>
                </span>

                <div class="owner-stat-card__meta">
                    <span class="owner-stat-card__label"> Open Debt Records </span>

                    <strong class="owner-stat-card__value" data-open-count>{{ $summary['open_debts_count'] }}</strong>

                    <span class="owner-stat-card__hint"> records awaiting settlement </span>
                </div>
            </article>
        </div>

        {{-- =================== Charts =================== --}}
        <div class="owner-debt-analytics" aria-label="Debt analytics charts">
            <article class="owner-debt-panel" aria-labelledby="trend-title">
                <div class="section-heading">
                    <div>
                        <span class="owner-eyebrow"> Cash Flow </span>

                        <h2 id="trend-title">Monthly Debt vs Collections</h2>

                        <p class="section-heading__description">
                            Value of credit given and payments received across the last {{ count($trendLabels) }} months.
                        </p>
                    </div>
                </div>

                <div class="owner-debt-panel__chart">
                    <canvas id="owner-debt-trend-chart" role="img"
                        aria-label="Monthly debt added and collections bar chart"></canvas>
                </div>
            </article>

            <article class="owner-debt-panel" aria-labelledby="status-title">
                <div class="section-heading">
                    <div>
                        <span class="owner-eyebrow"> Breakdown </span>

                        <h2 id="status-title">By Status</h2>

                        <p class="section-heading__description">Recorded value in each settlement state.</p>
                    </div>
                </div>

                <div class="owner-debt-status">
                    <div class="owner-debt-donut">
                        <canvas id="owner-debt-status-chart" role="img"
                            aria-label="Debt value by status doughnut chart"></canvas>
                    </div>

                    <ul class="owner-debt-legend">
                        @foreach ($statusBreakdown as $bucket)
                            <li class="owner-debt-legend__item">
                                <span class="owner-debt-legend__dot owner-debt-legend__dot--{{ $bucket['key'] }}"
                                    aria-hidden="true"></span>

                                <span class="owner-debt-legend__label">{{ $bucket['label'] }}</span>

                                <strong class="owner-debt-legend__value">
                                    ₱{{ number_format($bucket['total'], 2) }}
                                </strong>

                                <small class="owner-debt-legend__count">{{ $bucket['count'] }} {{ $bucket['count'] === 1 ? 'record' : 'records' }}</small>
                            </li>
                        @endforeach
                    </ul>
                </div>
            </article>
        </div>

        {{-- =================== Aging =================== --}}
        <section class="owner-debt-aging" aria-labelledby="aging-title">
            <div class="section-heading">
                <div>
                    <span class="owner-eyebrow"> A/R Aging </span>

                    <h2 id="aging-title">Outstanding by Age</h2>

                    <p class="section-heading__description">
                        Remaining balances grouped by how long they have been owed.
                    </p>
                </div>
            </div>

            <div class="owner-debt-aging__grid">
                @foreach ($aging as $key => $bucket)
                    <article class="owner-debt-aging__card {{ $key === 'overdue' ? 'owner-debt-aging__card--overdue' : '' }}">
                        <span class="owner-debt-aging__label">{{ $bucket['label'] }}</span>

                        <strong class="owner-debt-aging__value">₱{{ number_format($bucket['total'], 2) }}</strong>

                        <small class="owner-debt-aging__count">{{ $bucket['count'] }} {{ $bucket['count'] === 1 ? 'record' : 'records' }}</small>
                    </article>
                @endforeach
            </div>
        </section>

        {{-- =================== Top debtors =================== --}}
        <section class="owner-debt-section" aria-labelledby="top-debtors-title">
            <div class="section-heading">
                <div>
                    <span class="owner-eyebrow"> Customer Insights </span>

                    <h2 id="top-debtors-title">Top Debtors</h2>

                    <p class="section-heading__description">Customers with the largest outstanding balances.</p>
                </div>
            </div>

            <div class="owner-table-wrap">
                <table class="owner-rank-table">
                    <thead>
                        <tr>
                            <th scope="col">Customer</th>
                            <th scope="col">Open records</th>
                            <th scope="col">Total Debt</th>
                            <th scope="col">Outstanding</th>
                        </tr>
                    </thead>

                    <tbody>
                        @forelse ($topDebtors as $entry)
                            <tr class="owner-rank-row" data-customer-id="{{ $entry['customer_id'] }}" tabindex="0" role="button" aria-label="View {{ $entry['name'] }} details">
                                <td data-label="Customer">
                                    <div class="owner-customer-cell">
                                        @if ($entry['avatar_path'])
                                            <img class="owner-ranking-avatar" src="{{ asset('storage/'.$entry['avatar_path']) }}" alt="{{ $entry['name'] }}">
                                        @else
                                            <img class="owner-ranking-avatar" src="{{ asset($entry['gender'] === 'female' ? 'images/avatar-girl.svg' : 'images/avatar-boy.svg') }}" alt="{{ ucfirst($entry['gender']) }} avatar">
                                        @endif

                                        <strong>{{ $entry['name'] }}</strong>
                                    </div>
                                </td>

                                <td data-label="Open records">{{ $entry['open_debts'] }}</td>

                                <td data-label="Total Debt" class="owner-amount">₱{{ number_format($entry['total'], 2) }}</td>

                                <td data-label="Outstanding" class="owner-amount owner-amount--unpaid">₱{{ number_format($entry['outstanding'], 2) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4">No outstanding balances right now.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        {{-- =================== Recent debts =================== --}}
        <section class="owner-debt-section" aria-labelledby="recent-debts-title">
            <div class="section-heading">
                <div>
                    <span class="owner-eyebrow"> Activity </span>

                    <h2 id="recent-debts-title">Recent Debt Records</h2>

                    <p class="section-heading__description">The latest credit records entered on the Customers tab.</p>
                </div>
            </div>

            <div class="owner-table-wrap">
                <table class="owner-rank-table">
                    <thead>
                        <tr>
                            <th scope="col">Customer</th>
                            <th scope="col">Loaned On</th>
                            <th scope="col">Items</th>
                            <th scope="col">Paid</th>
                            <th scope="col">Remaining</th>
                            <th scope="col">Status</th>
                        </tr>
                    </thead>

                    <tbody>
                        @forelse ($recentDebts as $debt)
                            <tr data-customer-id="{{ $debt->customer_id }}">
                                <td data-label="Customer">
                                    <div class="owner-customer-cell">
                                        @if ($debt->customer->avatar_path)
                                            <img class="owner-ranking-avatar" src="{{ asset('storage/'.$debt->customer->avatar_path) }}" alt="{{ $debt->customer->full_name }}">
                                        @else
                                            <img class="owner-ranking-avatar" src="{{ asset($debt->customer->gender === 'female' ? 'images/avatar-girl.svg' : 'images/avatar-boy.svg') }}" alt="{{ ucfirst($debt->customer->gender) }} avatar">
                                        @endif

                                        <strong>{{ $debt->customer->full_name }}</strong>
                                    </div>
                                </td>

                                <td data-label="Loaned On">{{ ($debt->loaned_at ?? $debt->created_at)->format('M d, Y') }}</td>

                                <td data-label="Items">{{ $debt->items->count() === 0 ? '—' : $debt->items->count() }} @if ($debt->items->count() <= 1) item @endif</td>

                                <td data-label="Paid" class="owner-amount owner-amount--paid">₱{{ number_format($debt->paid_total, 2) }}</td>

                                <td data-label="Remaining" class="owner-amount owner-amount--unpaid">₱{{ number_format($debt->remaining_total, 2) }}</td>

                                <td data-label="Status">
                                    <span class="owner-status-badge owner-status-badge--{{ $debt->status?->value ?? 'unpaid' }}">
                                        {{ $debt->status?->label() ?? 'Unpaid' }}
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6">No debt records yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </section>
@endsection

@push('scripts')
    <script>
        (function () {
            const page = document.querySelector('[data-owner-debts]');
            if (!page) return;

            const cssVar = (name) => getComputedStyle(document.documentElement).getPropertyValue(name).trim();
            const formatPeso = (value) =>
                '₱' + Number(value).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

            const openCustomer = (id) => {
                window.location.href = '{{ route('owner.customers') }}?id=' + encodeURIComponent(id);
            };

            const makeRowNavigable = (table) => table.addEventListener('click', (event) => {
                const row = event.target.closest('[data-customer-id]');
                if (row) openCustomer(row.dataset.customerId);
            });

            const makeRowKeyboardNavigable = (table) => table.addEventListener('keydown', (event) => {
                const row = event.target.closest('[data-customer-id]');
                if (row && (event.key === 'Enter' || event.key === ' ')) {
                    event.preventDefault();
                    openCustomer(row.dataset.customerId);
                }
            });

            document.querySelectorAll('.owner-rank-table').forEach((table) => {
                makeRowNavigable(table);
                makeRowKeyboardNavigable(table);
            });

            const trendData = @json($trendPayload);

            const statusData = @json($statusPayload);

            const theme = () => document.documentElement.getAttribute('data-theme') === 'dark' ? 'dark' : 'light';
            const gridColor = () =>
                theme() === 'dark' ? 'rgba(166, 174, 230, 0.14)' : 'rgba(70, 75, 113, 0.10)';

            let trendChart = null;
            let statusChart = null;

            const buildTrend = () => {
                return new Chart(document.getElementById('owner-debt-trend-chart'), {
                    type: 'bar',
                    data: {
                        labels: trendData.labels,
                        datasets: [
                            {
                                label: 'Debt added',
                                data: trendData.added,
                                backgroundColor: cssVar('--teal'),
                                borderRadius: 6,
                                maxBarThickness: 22,
                            },
                            {
                                label: 'Collections',
                                data: trendData.collected,
                                backgroundColor: cssVar('--indigo'),
                                borderRadius: 6,
                                maxBarThickness: 22,
                            },
                        ],
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        interaction: { mode: 'index', intersect: false },
                        plugins: {
                            legend: { position: 'bottom', labels: { color: cssVar('--text-muted'), boxHeight: 3, boxWidth: 18, font: { size: 12 } } },
                            tooltip: { callbacks: { label: (item) => item.dataset.label + ': ' + formatPeso(item.parsed.y) } },
                        },
                        scales: {
                            x: { grid: { display: false }, ticks: { color: cssVar('--text-muted') } },
                            y: {
                                beginAtZero: true,
                                grid: { color: gridColor() },
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

            const statusColors = statusData.keys.map((key) => {
                const map = { unpaid: '#D64550', partially_paid: '#118AB2', paid: '#7CD5C7' };
                return map[key] || cssVar('--indigo');
            });

            const buildStatus = () => {
                return new Chart(document.getElementById('owner-debt-status-chart'), {
                    type: 'doughnut',
                    data: {
                        labels: statusData.labels,
                        datasets: [{
                            data: statusData.values,
                            backgroundColor: statusColors,
                            borderColor: cssVar('--surface'),
                            borderWidth: 3,
                            hoverOffset: 5,
                        }],
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        cutout: '64%',
                        plugins: {
                            legend: { display: false },
                            tooltip: {
                                callbacks: {
                                    label: (item) => item.label + ': ' + formatPeso(item.parsed),
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

                trendChart = buildTrend();
                statusChart = buildStatus();

                document.querySelectorAll('[data-theme-toggle]').forEach((button) => {
                    button.addEventListener('click', () => {
                        trendChart?.destroy();
                        statusChart?.destroy();
                        trendChart = buildTrend();
                        statusChart = buildStatus();
                    });
                });
            };

            init();
        })();
    </script>
@endpush