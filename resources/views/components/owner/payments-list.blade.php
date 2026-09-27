<div class="owner-payments__summary">
    <div class="owner-payments__summary-item">
        <span class="owner-payments__summary-label">Payments</span>
        <strong class="owner-payments__summary-value">{{ number_format($summary['count']) }}</strong>
    </div>

    <div class="owner-payments__summary-item">
        <span class="owner-payments__summary-label">Total collected</span>
        <strong class="owner-payments__summary-value owner-amount owner-amount--paid">₱{{ $summary['total'] }}</strong>
    </div>

    <div class="owner-payments__summary-item">
        <span class="owner-payments__summary-label">Average payment</span>
        <strong class="owner-payments__summary-value">₱{{ $summary['average'] }}</strong>
    </div>

    <div class="owner-payments__summary-item">
        <span class="owner-payments__summary-label">Largest payment</span>
        <strong class="owner-payments__summary-value">₱{{ $summary['largest'] }}</strong>
    </div>
</div>

@if ($payments->isEmpty())
    <div class="owner-products-empty">
        <span class="owner-products-empty__icon" aria-hidden="true">
            <svg width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"
                stroke-linecap="round" stroke-linejoin="round">
                <rect x="2.5" y="5" width="19" height="14" rx="2"></rect>
                <path d="M2.5 10H21.5"></path>
                <circle cx="5.5" cy="14.5" r="1.2" fill="currentColor"></circle>
            </svg>
        </span>

        <strong>{{ $q !== '' ? 'No matches for "'.$q.'"' : 'No payments yet' }}</strong>

        <p>{{ $q !== '' ? 'Try a different keyword — customers, codes, and receivers are searched.' : 'Payments recorded in the Customers tab will appear here.' }}</p>
    </div>
@else
    <p class="owner-products-count">
        {{ number_format($summary['count']) }} {{ $summary['count'] === 1 ? 'payment' : 'payments' }}
    </p>

    <div class="owner-table-wrap">
        <table class="owner-rank-table owner-payments-table">
            <thead>
                <tr>
                    <th scope="col">Date</th>
                    <th scope="col">Customer</th>
                    <th scope="col">Reference</th>
                    <th scope="col">Received by</th>
                    <th scope="col">Amount</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($payments as $payment)
                    <tr>
                        <td data-label="Date">
                            <span class="owner-payments-time">
                                {{ $payment->payment_date->timezone('Asia/Manila')->format('M d, Y h:i A') }}
                            </span>
                        </td>

                        <td data-label="Customer">
                            <span class="owner-payments-customer">
                                <strong>{{ $payment->customer?->full_name ?? 'Deleted customer' }}</strong>
                                <span class="owner-payments-code">#{{ $payment->customer?->customer_code ?? '—' }}</span>
                            </span>
                        </td>

                        <td data-label="Reference">
                            <span class="owner-payments-reference">#{{ substr($payment->debt_id, 0, 8) }}</span>
                        </td>

                        <td data-label="Received by">
                            <span class="owner-payments-receiver">
                                {{ $payment->receivedBy?->full_name ?? 'Unknown' }}
                                @if ($payment->receivedBy?->username)
                                    <span class="owner-payments-code">{{ '@'.$payment->receivedBy->username }}</span>
                                @endif
                            </span>
                        </td>

                        <td data-label="Amount" class="owner-payments-amount">
                            <span class="owner-amount owner-amount--paid">₱{{ number_format((float) $payment->amount_paid, 2) }}</span>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    {{ $payments->onEachSide(1)->links() }}
@endif
