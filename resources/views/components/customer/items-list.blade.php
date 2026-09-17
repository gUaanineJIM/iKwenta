@props([
    'items' => null,
])

@php
    $isPaginated = $items instanceof \Illuminate\Pagination\AbstractPaginator;
    $items = $items ?? collect();
    $recordCount = $isPaginated ? $items->total() : $items->count();
@endphp

<section class="customer-block" aria-labelledby="customer-items-heading">
    <div class="section-heading">
        <div>
            <span class="eyebrow"> Inventory Activity </span>

            <h2 id="customer-items-heading" tabindex="-1" data-section-title>Debt Items</h2>

            <p class="section-heading__sub">All items loaned to you, with date and time.</p>
        </div>

        <span class="debt-count">
            {{ $recordCount }} {{ $recordCount === 1 ? 'item' : 'items' }}
        </span>
    </div>

    @if ($items->isEmpty())

        <x-customer.empty-state
            icon="₱"
            title="No debt items"
            message="Items you have been loaned on credit will appear here."
        />

    @else

        <div class="table-wrapper">
            <table class="debt-table items-table">
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>Qty.</th>
                        <th>Unit Price</th>
                        <th>Subtotal</th>
                        <th>Date &amp; Time</th>
                        <th>Debt</th>
                    </tr>
                </thead>

                <tbody>
                    @foreach ($items as $item)
                        <tr>
                            <td data-label="Product">
                                <div class="product-cell">
                                    <span class="product-cell__icon" aria-hidden="true">
                                        {{ strtoupper(substr($item->product->product_name ?? 'P', 0, 1)) }}
                                    </span>

                                    <div>
                                        <strong>
                                            {{ $item->product->product_name ?? 'Unknown Product' }}
                                        </strong>

                                        @if ($item->notes)
                                            <small> {{ $item->notes }} </small>
                                        @endif
                                    </div>
                                </div>
                            </td>

                            <td data-label="Quantity">{{ $item->quantity }}</td>

                            <td data-label="Unit Price">₱{{ number_format((float) $item->unit_price, 2) }}</td>

                            <td data-label="Subtotal">
                                <strong> ₱{{ number_format((float) $item->subtotal, 2) }} </strong>
                            </td>

                            <td data-label="Date &amp; Time">
                                <span class="cell-stack">
                                    {{ $item->created_at?->format('M d, Y') }}
                                    <small> {{ $item->created_at?->format('h:i A') }} </small>
                                </span>
                            </td>

                            <td data-label="Debt">
                                @if ($item->debt)
                                    <div class="debt-cell">
                                        <span class="debt-ref"> #{{ substr($item->debt->debt_id, 0, 8) }} </span>

                                        <span class="status-badge status-badge--{{ $item->debt->status }}">
                                            {{ ucfirst($item->debt->status) }}
                                        </span>
                                    </div>
                                @else
                                    <span class="debt-ref"> — </span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        @if ($isPaginated)
            {{ $items->onEachSide(1)->links() }}
        @endif

    @endif
</section>