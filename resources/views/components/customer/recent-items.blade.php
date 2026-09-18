@props([
    'items' => null,
])

@php
    $items = $items ?? collect();
@endphp

@if ($items->isEmpty())

    <x-customer.empty-state
        icon="₱"
        title="No debt items yet"
        message="Products loaned to you on credit will appear here."
    />

@else

    <ol class="recent-items">
        @foreach ($items as $item)
            <li class="recent-item">
                <span class="recent-item__icon" aria-hidden="true">
                    {{ strtoupper(substr($item->product->product_name ?? 'P', 0, 1)) }}
                </span>

                <div class="recent-item__main">
                    <strong> {{ $item->product->product_name ?? 'Unknown Product' }} </strong>

                    <span class="recent-item__details">
                        {{ $item->quantity }} × ₱{{ number_format((float) $item->unit_price, 2) }}
                    </span>

                    @if ($item->notes)
                        <small> {{ $item->notes }} </small>
                    @endif
                </div>

                <div class="recent-item__total">
                    <strong> ₱{{ number_format((float) $item->subtotal, 2) }} </strong>

                    <time class="recent-item__date" datetime="{{ $item->created_at?->toIso8601String() }}">
                        {{ $item->created_at?->format('M d, Y') }} · {{ $item->created_at?->format('h:i A') }}
                    </time>
                </div>
            </li>
        @endforeach
    </ol>

    <a
        class="section-link"
        href="{{ route('customer.dashboard.section', ['section' => 'items']) }}"
        data-section-link
        data-nav="items"
    >
        View all items <span aria-hidden="true">→</span>
    </a>

@endif