@props([
    'payments' => null,
    'title' => 'Payment History',
    'collapsible' => false,
    'collapsed' => true,
    'showViewAll' => false,
    'id' => 'payment-history',
])

@php
    $payments = $payments ?? collect();
    $isPaginated = $payments instanceof \Illuminate\Pagination\AbstractPaginator;
    $total = $isPaginated ? $payments->total() : $payments->count();
@endphp

<section class="customer-block payment-block" aria-labelledby="{{ $id }}-heading">

    @if ($collapsible)

        <button
            type="button"
            class="collapse-toggle"
            data-collapse-toggle
            aria-expanded="{{ $collapsed ? 'false' : 'true' }}"
            aria-controls="{{ $id }}-panel"
        >
            <span class="collapse-toggle__label"> {{ $title }} </span>

            <span class="debt-count" aria-hidden="true"> {{ $total }} </span>

            <svg class="collapse-toggle__chevron" width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                <path d="M6 9L12 15L18 9" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
            </svg>
        </button>

        <h2 id="{{ $id }}-heading" class="sr-only">{{ $title }}</h2>

        <div id="{{ $id }}-panel" class="collapse-panel" data-collapse-panel @if ($collapsed) hidden @endif>
            @include('components.customer.partials.payment-list', ['payments' => $payments])

            @if ($showViewAll)
                <a
                    class="section-link"
                    href="{{ route('customer.dashboard.section', ['section' => 'payments']) }}"
                    data-section-link
                    data-nav="payments"
                >
                    View all payments <span aria-hidden="true">→</span>
                </a>
            @endif

            @if ($isPaginated)
                {{ $payments->onEachSide(1)->links() }}
            @endif
        </div>

    @else

        <div class="section-heading">
            <div>
                <span class="eyebrow"> Account History </span>

                <h2 id="{{ $id }}-heading" tabindex="-1" data-section-title>{{ $title }}</h2>

                <p class="section-heading__sub">Every payment applied to your debts.</p>
            </div>

            <span class="debt-count">
                {{ $total }} {{ $total === 1 ? 'payment' : 'payments' }}
            </span>
        </div>

        @include('components.customer.partials.payment-list', ['payments' => $payments])

        @if ($isPaginated)
            {{ $payments->onEachSide(1)->links() }}
        @endif

    @endif
</section>