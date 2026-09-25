@props([
    'items' => null,
    'archivedItems' => null,
    'summary' => [
        'totalDebt' => '0.00',
        'totalPaid' => '0.00',
        'remainingBalance' => '0.00',
    ],
    'archiveSummary' => [
        'totalDebt' => '0.00',
        'totalPaid' => '0.00',
    ],
])

@php
    use App\Models\Debt;

    $isPaginated = $items instanceof \Illuminate\Pagination\AbstractPaginator;
    $items = $items ?? collect();
    $archivedItems = $archivedItems ?? collect();
    $recordCount = $isPaginated ? $items->total() : $items->count();

    $itemsTotal = (float) ($summary['totalDebt'] ?? 0);
    $paidTotal = (float) ($summary['totalPaid'] ?? 0);
    $amountPayable = (float) ($summary['remainingBalance'] ?? 0);
    $hasPayment = $paidTotal > 0;
    $hasPartialPayment = $hasPayment && $paidTotal < $itemsTotal;

    $archiveTotal = (float) ($archiveSummary['totalDebt'] ?? 0);
    $archivePaid = (float) ($archiveSummary['totalPaid'] ?? 0);
    $archiveHasSettlement = $archivePaid > 0;
    $archivePayable = max(0.0, $archiveTotal - $archivePaid);
@endphp

<section class="customer-block" aria-labelledby="customer-items-heading">
    <div class="section-heading">
        <div>
            <span class="customer-eyebrow"> Inventory Activity </span>

            <h2 id="customer-items-heading" tabindex="-1" data-section-title>Debt Items</h2>

            <p class="section-heading__description">Items loaned to you on credit, with date and time.</p>
        </div>

        <span class="count-badge">
            {{ $recordCount }} {{ $recordCount === 1 ? 'item' : 'items' }}
        </span>
    </div>

    @if ($items->isEmpty() && $archivedItems->isEmpty())

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
                    @forelse ($items as $item)
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
                    @empty
                        <tr>
                            <td colspan="6" class="cell-empty">
                                No current debt items — your settled items are archived below.
                            </td>
                        </tr>
                    @endforelse
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

        {{-- Archived items from fully-paid debts --}}
        @if ($archivedItems->isNotEmpty())
            <div class="debt-archive">
                <button type="button" class="debt-archive__toggle" data-collapse-toggle
                    aria-expanded="false" aria-controls="customer-items-archive">
                    <h3 class="debt-group debt-group--archive">
                        <span> Paid Items — Archive </span>

                        <span class="debt-group__count">{{ $archivedItems->count() }}</span>
                    </h3>

                    <svg class="debt-archive__chevron" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M6 9l6 6 6-6" />
                    </svg>
                </button>

                <div id="customer-items-archive" class="debt-archive__panel" data-debt-archive hidden>
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
                                @foreach ($archivedItems as $item)
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

                    <div class="items-summary items-summary--archive" aria-label="Archived paid items total">
                        <div class="items-summary__row">
                            <span class="items-summary__label"> Total of paid items </span>

                            <strong class="items-summary__amount">
                                ₱{{ number_format($archiveTotal, 2) }}
                            </strong>
                        </div>

                        @if ($archiveHasSettlement)
                            <div class="items-summary__row items-summary__row--settled">
                                <span class="items-summary__label"> Settled by payments </span>

                                <strong class="items-summary__amount">
                                    ₱{{ number_format($archivePaid, 2) }}
                                </strong>
                            </div>
                        @endif

                        <div class="items-summary__row items-summary__row--payable">
                            <span class="items-summary__label"> Amount payable </span>

                            <strong class="items-summary__amount">
                                ₱{{ number_format($archivePayable, 2) }}
                            </strong>
                        </div>
                    </div>

                    <p class="debt-archive__note">
                        Items from settled debts are archived here for {{ Debt::ARCHIVE_RETENTION_DAYS }} days after full payment.
                    </p>
                </div>
            </div>
        @endif

        @if ($isPaginated)
            {{ $items->onEachSide(1)->links() }}
        @endif

    @endif
</section>