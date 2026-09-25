@props([
    'active' => 'overview',
])

@php
    $items = [
        'overview' => [
            'label' => 'Dashboard',
            'href' => route('owner.dashboard'),
            'icon' => '
                <svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <rect x="3" y="3" width="7.5" height="9" rx="1.5" />
                    <rect x="13.5" y="3" width="7.5" height="5.5" rx="1.5" />
                    <rect x="13.5" y="11.5" width="7.5" height="9.5" rx="1.5" />
                    <rect x="3" y="15.5" width="7.5" height="5.5" rx="1.5" />
                </svg>
            ',
        ],
        'customers' => [
            'label' => 'Customers',
            'href' => route('owner.customers'),
            'icon' => '
                <svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <circle cx="9" cy="8" r="3.5" />
                    <path d="M2.5 20C3.5 16.5 6 15 9 15C12 15 14.5 16.5 15.5 20" />
                    <circle cx="17" cy="9" r="2.5" />
                    <path d="M16.5 15C19 15 21 16.5 21.5 20" />
                </svg>
            ',
        ],
        'debts' => [
            'label' => 'Debts',
            'href' => route('owner.debts'),
            'icon' => '
                <svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M21 12H14L12 16L9 8L7 12H3" />
                    <circle cx="12" cy="12" r="9" />
                </svg>
            ',
        ],
        'payments' => [
            'label' => 'Payments',
            'icon' => '
                <svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <rect x="2.5" y="5" width="19" height="14" rx="2" />
                    <path d="M2.5 10H21.5" />
                    <circle cx="5.5" cy="14.5" r="1.2" fill="currentColor" />
                </svg>
            ',
        ],
        'activity' => [
            'label' => 'Activity Logs',
            'href' => route('owner.activity-logs'),
            'icon' => '
                <svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M3 12L7 12L9 6L13 18L15 10L17 12L21 12" />
                    <circle cx="12" cy="12" r="9" />
                </svg>
            ',
        ],
    ];
@endphp

<nav class="sidebar-nav" aria-label="Owner dashboard navigation">
    <div class="sidebar-brand">
        @include('partials.logo', ['href' => route('owner.dashboard')])
    </div>

    <ul class="sidebar-nav__list">
        @foreach ($items as $key => $item)
            <li>
                <a
                    class="owner-nav__link {{ $active === $key ? 'is-active' : '' }}"
                    href="{{ $item['href'] ?? '#' }}"
                    title="{{ $item['label'] }}"
                    @if ($active === $key) aria-current="page" @endif
                >
                    {!! $item['icon'] !!}

                    <span> {{ $item['label'] }} </span>
                </a>
            </li>
        @endforeach
    </ul>

    <div class="sidebar-logout">
        <a class="sidebar-logout__btn" href="{{ route('landing', '#login') }}">
            <svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4" />
                <path d="M16 17L21 12L16 7" />
                <path d="M21 12H9" />
            </svg>

            <span>Sign Out</span>
        </a>
    </div>
</nav>