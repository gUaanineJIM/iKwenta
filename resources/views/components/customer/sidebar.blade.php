@props([
    'active' => 'overview',
])

@php
    $items = [
        'overview' => [
            'label' => 'Dashboard',
            'icon' => '
                <svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <rect x="3" y="3" width="7.5" height="9" rx="1.5" />
                    <rect x="13.5" y="3" width="7.5" height="5.5" rx="1.5" />
                    <rect x="13.5" y="11.5" width="7.5" height="9.5" rx="1.5" />
                    <rect x="3" y="15.5" width="7.5" height="5.5" rx="1.5" />
                </svg>
            ',
        ],
        'debts' => [
            'label' => 'My Debts',
            'icon' => '
                <svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M21 12H14L12 16L9 8L7 12H3" />
                    <circle cx="12" cy="12" r="9" />
                </svg>
            ',
        ],
        'items' => [
            'label' => 'Debt Items',
            'icon' => '
                <svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M21 8L12 3L3 8V16L12 21L21 16V8Z" />
                    <path d="M3 8L12 13L21 8" />
                    <path d="M12 13V21" />
                </svg>
            ',
        ],
        'payments' => [
            'label' => 'Payment History',
            'icon' => '
                <svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <rect x="2.5" y="5" width="19" height="14" rx="2" />
                    <path d="M2.5 10H21.5" />
                    <circle cx="5.5" cy="14.5" r="1.2" fill="currentColor" />
                </svg>
            ',
        ],
    ];
@endphp

<nav class="sidebar-nav" aria-label="Dashboard navigation">
    <button
        type="button"
        class="sidebar-nav__close"
        data-sidebar-close
        aria-label="Close menu"
    >
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" aria-hidden="true">
            <path d="M18 6L6 18M6 6L18 18" stroke="currentColor" stroke-width="2" stroke-linecap="round" />
        </svg>
    </button>

    <span class="sidebar-nav__label"> Menu </span>

    <ul class="sidebar-nav__list">
        @foreach ($items as $key => $item)
            <li>
                <a
                    class="customer-nav__link {{ $active === $key ? 'is-active' : '' }}"
                    href="{{ route('customer.dashboard.section', ['section' => $key]) }}"
                    data-section-link
                    data-nav="{{ $key }}"
                    title="{{ $item['label'] }}"
                    @if ($active === $key) aria-current="page" @endif
                >
                    {!! $item['icon'] !!}

                    <span> {{ $item['label'] }} </span>
                </a>
            </li>
        @endforeach
    </ul>

    <button
        type="button"
        class="sidebar-collapse"
        data-sidebar-collapse
        aria-expanded="true"
        aria-label="Collapse sidebar"
    >
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true">
            <path d="M15 6L9 12L15 18" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
        </svg>

        <span> Collapse </span>
    </button>
</nav>