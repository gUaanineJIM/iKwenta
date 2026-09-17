@php
    $payments = $payments ?? collect();
@endphp

@if ($payments->isEmpty())

    <x-customer.empty-state
        icon="✓"
        title="No payments yet"
        message="Payments you make will appear here."
    />

@else

    <div class="payments-list">
        @foreach ($payments as $payment)
            <div class="payment-row">
                <div class="payment-row__icon" aria-hidden="true"> ✓ </div>

                <div class="payment-row__info">
                    <strong> ₱{{ number_format((float) $payment->amount_paid, 2) }} </strong>

                    <span>
                        Payment received
                        @if ($payment->debt)
                            · #{{ substr($payment->debt->debt_id, 0, 8) }}
                        @endif
                    </span>
                </div>

                <time datetime="{{ $payment->payment_date?->toIso8601String() }}">
                    {{ $payment->payment_date?->format('M d, Y h:i A') }}
                </time>
            </div>
        @endforeach
    </div>

@endif