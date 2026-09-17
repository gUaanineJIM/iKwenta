@props([
    'debts' => null,
])

@php
    $debts = $debts ?? collect();
@endphp

<section class="customer-block" aria-labelledby="customer-debts-heading">
    <div class="section-heading">
        <div>
            <span class="eyebrow"> Account History </span>

            <h2 id="customer-debts-heading" tabindex="-1" data-section-title>My Debts</h2>
        </div>

        <span class="debt-count">
            {{ $debts->count() }} {{ $debts->count() === 1 ? 'Debt' : 'Debts' }}
        </span>
    </div>

    {{-- Debt List --}}
    @forelse ($debts as $debt)
        @php
            $debtTotal = $debt->items_total ?? '0.00';
            $debtPaid = $debt->paid_total ?? '0.00';
            $debtRemaining = $debt->remaining_total ?? '0.00';
        @endphp

        <article class="debt-card">
            {{-- Debt Header --}}
            <div class="debt-card__header">
                <div class="debt-card__info">
                    <span class="debt-card__label"> Debt Record </span>

                    <h3>#{{ substr($debt->debt_id, 0, 8) }}</h3>

                    <span class="debt-card__date"> Created {{ $debt->created_at?->format('M d, Y') }} </span>
                </div>

                <div class="debt-card__summary">
                    <div>
                        <span>Total</span>

                        <strong> ₱{{ number_format((float) $debtTotal, 2) }} </strong>
                    </div>

                    <div>
                        <span>Remaining</span>

                        <strong> ₱{{ number_format((float) $debtRemaining, 2) }} </strong>
                    </div>

                    <span class="status-badge status-badge--{{ $debt->status }}">
                        {{ ucfirst($debt->status) }}
                    </span>
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
                                    </tr>
                                </thead>

                                <tbody>
                                    @foreach ($debt->items as $item)
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
                                        </tr>
                                    @endforeach
                                </tbody>

                                <tfoot>
                                    <tr>
                                        <td colspan="3">Total Debt</td>

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
                </div>

                {{-- Payments --}}
                <div class="detail-section">
                    <div class="detail-section__heading">
                        <div>
                            <h4>Payment History</h4>

                            <span>
                                {{ $debt->payments->count() }} {{ $debt->payments->count() === 1 ? 'payment' : 'payments' }}
                            </span>
                        </div>
                    </div>

                    @if ($debt->payments->isNotEmpty())

                        <div class="payments-list">
                            @foreach ($debt->payments->sortByDesc('payment_date') as $payment)
                                <div class="payment-row">
                                    <div class="payment-row__icon" aria-hidden="true"> ✓ </div>

                                    <div class="payment-row__info">
                                        <strong> ₱{{ number_format((float) $payment->amount_paid, 2) }} </strong>

                                        <span> Payment received </span>
                                    </div>

                                    <time datetime="{{ $payment->payment_date?->toIso8601String() }}">
                                        {{ $payment->payment_date?->format('M d, Y h:i A') }}
                                    </time>
                                </div>
                            @endforeach
                        </div>

                    @else

                        <x-customer.empty-state
                            icon="₱"
                            title="No payments yet"
                            message="No payment has been recorded for this debt."
                        />

                    @endif
                </div>

                {{-- Debt Footer --}}
                <div class="debt-card__footer">
                    <div>
                        <span> Total Paid </span>

                        <strong> ₱{{ number_format((float) $debtPaid, 2) }} </strong>
                    </div>

                    <div>
                        <span> Remaining Balance </span>

                        <strong> ₱{{ number_format((float) $debtRemaining, 2) }} </strong>
                    </div>
                </div>
            </div>
        </article>

    @empty

        <div class="empty-debts">
            <div class="empty-debts__icon" aria-hidden="true"> ₱ </div>

            <h3>No debts found</h3>

            <p>You currently don't have any debt records.</p>
        </div>

    @endforelse
</section>