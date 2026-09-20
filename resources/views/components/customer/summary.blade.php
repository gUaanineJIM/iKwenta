@props([
    'summary' => [
        'totalDebt' => '0.00',
        'totalPaid' => '0.00',
        'remainingBalance' => '0.00',
    ],
])

<section class="summary-grid" aria-label="Financial summary">
    {{-- Total Debt --}}
    <article class="summary-card summary-card--debt">
        <div class="summary-card__icon" aria-hidden="true"> ₱ </div>

        <div class="summary-card__content">
            <span class="summary-card__label"> Total Owed </span>

            <strong class="summary-card__amount"> ₱{{ number_format((float) $summary['totalDebt'], 2) }} </strong>
        </div>
    </article>

    {{-- Total Paid --}}
    <article class="summary-card summary-card--paid">
        <div class="summary-card__icon" aria-hidden="true"> ✓ </div>

        <div class="summary-card__content">
            <span class="summary-card__label"> Total Paid </span>

            <strong class="summary-card__amount"> ₱{{ number_format((float) $summary['totalPaid'], 2) }} </strong>
        </div>
    </article>

    {{-- Remaining Balance --}}
    <article class="summary-card summary-card--balance">
        <div class="summary-card__icon" aria-hidden="true"> ₱ </div>

        <div class="summary-card__content">
            <span class="summary-card__label"> Remaining Balance </span>

            <strong class="summary-card__amount"> ₱{{ number_format((float) $summary['remainingBalance'], 2) }} </strong>
        </div>
    </article>
</section>