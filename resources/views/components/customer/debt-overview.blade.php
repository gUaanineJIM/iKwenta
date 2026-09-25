@props([
    'debts' => null,
])

@php
    use App\Enums\DebtStatus;
    use App\Models\Debt;

    $debts = $debts ?? collect();

    $activeCredits = $debts->filter(fn ($debt) => ! ($debt->status?->isFullyPaid() ?? false));
    $archivedCredits = $debts->filter(fn ($debt) => $debt->status?->isFullyPaid() ?? false);
@endphp

<section class="customer-block" aria-labelledby="customer-debts-heading">
    <div class="section-heading">
        <div>
            <span class="customer-eyebrow"> Account History </span>

            <h2 id="customer-debts-heading" tabindex="-1" data-section-title>My Debts</h2>
        </div>

        <span class="count-badge">
            {{ $debts->count() }} {{ $debts->count() === 1 ? 'Debt' : 'Debts' }}
        </span>
    </div>

    {{-- Active Credits (unpaid + partially paid) --}}
    @if ($activeCredits->isNotEmpty())
        <h3 class="debt-group"> Active Credits </h3>

        @foreach ($activeCredits as $debt)
            @include('components.customer.partials.debt-card', ['debt' => $debt])
        @endforeach
    @endif

    {{-- Past Debts / Archive (paid credit records kept for 15 days) --}}
    @if ($archivedCredits->isNotEmpty())
        <div class="debt-archive">
            <button type="button" class="debt-archive__toggle" data-collapse-toggle
                aria-expanded="false" aria-controls="customer-debt-archive">
                <h3 class="debt-group debt-group--archive">
                    <span> Past Debts — Archive </span>

                    <span class="debt-group__count">{{ $archivedCredits->count() }}</span>
                </h3>

                <svg class="debt-archive__chevron" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M6 9l6 6 6-6" />
                </svg>
            </button>

            <div id="customer-debt-archive" class="debt-archive__panel" data-debt-archive hidden>
                <p class="debt-archive__note">
                    Settled debts are archived here for {{ Debt::ARCHIVE_RETENTION_DAYS }} days after full payment.
                </p>

                @foreach ($archivedCredits as $debt)
                    @include('components.customer.partials.debt-card', ['debt' => $debt])
                @endforeach
            </div>
        </div>
    @endif

    {{-- Empty state --}}
    @if ($activeCredits->isEmpty() && $archivedCredits->isEmpty())
        <div class="empty-debts">
            <div class="empty-debts__icon" aria-hidden="true"> ₱ </div>

            <h3>No debts found</h3>

            <p>You currently don't have any debt records.</p>
        </div>
    @endif
</section>