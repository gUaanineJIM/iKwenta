@props([
    'customer',
    'summary' => [
        'totalDebt' => '0.00',
        'totalPaid' => '0.00',
        'remainingBalance' => '0.00',
    ],
    'recentItems' => null,
    'payments' => null,
])

<div class="customer-section-inner" data-panel="overview">
    {{-- Page Heading --}}
    <section class="page-heading">
        <div>
            <span class="customer-eyebrow"> Customer Portal </span>

            <h1 tabindex="-1" data-section-title>My Account</h1>

            <p>View your debts, payments, and remaining balance.</p>
        </div>

        <div class="customer-code-card">
            <span>Customer Code</span>

            <strong> {{ $customer->customer_code }} </strong>
        </div>
    </section>

    {{-- Financial Summary --}}
    <x-customer.summary :summary="$summary" />

    {{-- Recent Debt Items --}}
    <section class="customer-block" aria-labelledby="recent-items-heading">
        <div class="section-heading">
            <div>
                <span class="customer-eyebrow"> Recent Activity </span>

                <h2 id="recent-items-heading">Recent Debt Items</h2>
            </div>
        </div>

        <x-customer.recent-items :items="$recentItems" />
    </section>

    {{-- Payment History (collapsible) --}}
    <x-customer.payment-history
        :payments="$payments"
        title="Payment History"
        collapsible
        :collapsed="false"
        show-view-all
        id="overview-payment-history"
    />
</div>