@props([
    'items' => null,
    'summary' => [
        'totalDebt' => '0.00',
        'totalPaid' => '0.00',
        'remainingBalance' => '0.00',
    ],
])

@php
    $isPaginated = $items instanceof \Illuminate\Pagination\AbstractPaginator;
    $items = $items ?? collect();
    $recordCount = $isPaginated ? $items->total() : $items->count();

    $itemsTotal = (float) ($summary['totalDebt'] ?? 0);
    $paidTotal = (float) ($summary['totalPaid'] ?? 0);
    $amountPayable = (float) ($summary['remainingBalance'] ?? 0);
    $hasPayment = $paidTotal > 0;
    $hasPartialPayment = $hasPayment && $paidTotal < $itemsTotal;
@endphp

<section class="customer-block" aria-labelledby="customer-items-heading">
    <div class="section-heading">
        <div>
            <span class="customer-eyebrow"> Inventory Activity </span>

            <h2 id="customer-items-heading" tabindex="-1" data-section-title>Debt Items</h2>

            <p class="section-heading__description">All items loaned to you, with date and time.</p>
        </div>

        <span class="count-badge">
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
                        <th>Notes</th>
                        <th>Date &amp; Time</th>
                    </tr>
                </thead>

                <tbody>
                    @foreach ($items as $item)
                        <tr>
                            <td data-label="Product">
                                <div class="product-cell">
                                    <span class="product-cell__icon" aria-hidden="true">
                                        <span aria-hidden="true">₱</span>
                                    </span>

                                    <div>
                                        <strong>
                                            {{ $item->product_name ?? $item->product->product_name ?? 'Unknown Product' }}
                                        </strong>
                                    </div>
                                </div>
                            </td>

                            <td data-label="Quantity">{{ $item->quantity }}</td>

                            <td data-label="Unit Price">₱{{ number_format((float) $item->unit_price, 2) }}</td>

                            <td data-label="Subtotal">
                                <strong> ₱{{ number_format((float) $item->subtotal, 2) }} </strong>
                            </td>

                            <td data-label="Notes" class="cell-notes">
                                {{ $item->notes ?: '—' }}
                            </td>

                            <td data-label="Date &amp; Time">
                                <span class="cell-stack">
                                    {{ $item->debt?->loaned_at?->format('M d, Y') }}
                                    <small> {{ $item->debt?->loaned_at?->format('h:i A') }} </small>
                                </span>
                            </td>

                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="items-summary" aria-label="Debt items total and amount payable">
            <div class="items-summary__row">
                <span class="items-summary__label"> Total of debt items </span>

                <strong class="items-summary__amount">
                    ₱{{ number_format($itemsTotal, 2) }}
                </strong>
            </div>

            @if ($hasPayment)
                <div class="items-summary__row items-summary__row--deduction">
                    <span class="items-summary__label">
                        Less: {{ $hasPartialPayment ? 'partial payment' : 'payment' }}
                    </span>

                    <strong class="items-summary__amount">
                        &minus;₱{{ number_format($paidTotal, 2) }}
                    </strong>
                </div>
            @endif

            <div class="items-summary__row items-summary__row--payable">
                <span class="items-summary__label"> Amount payable </span>

                <strong class="items-summary__amount">
                    ₱{{ number_format($amountPayable, 2) }}
                </strong>
            </div>
        </div>

        @if ($isPaginated)
            {{ $items->onEachSide(1)->links() }}
        @endif

    @endif
</section>