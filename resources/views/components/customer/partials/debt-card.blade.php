@props([
    'debt' => null,
])

@php
    use App\Enums\DebtStatus;

    $debtTotal = $debt->items_total ?? '0.00';
    $debtPaid = $debt->paid_total ?? '0.00';
    $debtRemaining = $debt->remaining_total ?? '0.00';

    $status = $debt->status ?? DebtStatus::Unpaid;
    $isManuallyPaid = (bool) ($debt->paid_manually ?? false);

    if ($isManuallyPaid && $status->isFullyPaid()) {
        $badgeLabel = 'Paid — Manually Marked';
        $badgeClass = 'status-badge--manual';
    } elseif ($status->isFullyPaid()) {
        $badgeLabel = 'Paid';
        $badgeClass = 'status-badge--paid';
    } elseif ($status === DebtStatus::PartiallyPaid) {
        $badgeLabel = 'Partially Paid';
        $badgeClass = 'status-badge--partial';
    } else {
        $badgeLabel = 'Unpaid';
        $badgeClass = 'status-badge--unpaid';
    }
@endphp

<article class="debt-card">
    {{-- Debt Header --}}
    <div class="debt-card__header">
        <div class="debt-card__title">
            <span class="debt-card__label"> Debt Record </span>

            <h3>Loaned {{ $debt->loaned_at?->format('M d, Y h:i A') }}</h3>

            <span class="status-badge {{ $badgeClass }}">{{ $badgeLabel }}</span>
        </div>

        <div class="debt-card__summary">
            <div>
                <span>Total</span>

                <strong> ₱{{ number_format((float) $debtTotal, 2) }} </strong>
            </div>

            <div>
                <span>Paid</span>

                <strong> ₱{{ number_format((float) $debtPaid, 2) }} </strong>
            </div>

            <div>
                <span>Remaining</span>

                <strong> ₱{{ number_format((float) $debtRemaining, 2) }} </strong>
            </div>
        </div>

        {{-- Collapse Button --}}
        <button type="button" class="debt-toggle" aria-expanded="false"
            aria-controls="debt-{{ $debt->debt_id }}" data-debt-toggle>
            <span> View Details </span>

            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                <path d="M6 9L12 15L18 9" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                    stroke-linejoin="round" />
            </svg>
        </button>
    </div>

    {{-- Collapsible Content --}}
    <div id="debt-{{ $debt->debt_id }}" class="debt-card__content" data-debt-content hidden>
        {{-- Items --}}
        <div class="detail-section">
            <div class="detail-section__heading">
                <div>
                    <h4>Purchased Items</h4>

                    <span>
                        {{ $debt->items->count() }} {{ $debt->items->count() === 1 ? 'item' : 'items' }}
                    </span>
                </div>
            </div>

            @if ($debt->items->isNotEmpty())

                <div class="table-wrapper">
                    <table class="debt-table">
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th>Qty.</th>
                                <th>Unit Price</th>
                                <th>Subtotal</th>
                                <th>Notes</th>
                            </tr>
                        </thead>

                        <tbody>
                            @foreach ($debt->items as $item)
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
                                </tr>
                            @endforeach
                        </tbody>

                        <tfoot>
                            <tr>
                                <td colspan="3">Total Debt</td>
                                <td></td>
                                <td>₱{{ number_format((float) $debtTotal, 2) }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

            @else

                <x-customer.empty-state
                    icon="₱"
                    title="No items recorded"
                    message="This debt has no recorded items."
                />

            @endif

            @if ((float) ($debt->money_amount ?? 0) > 0)
                <div class="detail-section__heading">
                    <div>
                        <h4>Money owed</h4>
                        <span>Cash balance included in this debt record</span>
                    </div>
                    <strong>₱{{ number_format((float) $debt->money_amount, 2) }}</strong>
                </div>
            @endif
        </div>

        {{-- Payments applied to this credit record --}}
        <div class="detail-section">
            <div class="detail-section__heading">
                <div>
                    <h4>Recorded Payments</h4>

                    <span>
                        @if ($isManuallyPaid)
                            Includes the remaining balance the owner marked as paid.
                        @else
                            Payments applied to this credit record.
                        @endif
                    </span>
                </div>
            </div>

            <x-customer.partials.payment-list :payments="$debt->payments ?? collect()" />
        </div>
    </div>
</article>