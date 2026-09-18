@props([
    'debts' => null,
])

@php
    use App\Enums\DebtStatus;

    $debts = $debts ?? collect();

    $activeCredits = $debts->filter(fn ($debt) => ! ($debt->status?->isFullyPaid() ?? false));
    $paidCredits = $debts->filter(fn ($debt) => $debt->status?->isFullyPaid() ?? false);
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

    {{-- History (paid credit records) --}}
    @if ($paidCredits->isNotEmpty())
        <h3 class="debt-group"> History </h3>

        @foreach ($paidCredits as $debt)
            @include('components.customer.partials.debt-card', ['debt' => $debt])
        @endforeach
    @endif

    {{-- Empty state --}}
    @if ($activeCredits->isEmpty() && $paidCredits->isEmpty())
        <div class="empty-debts">
            <div class="empty-debts__icon" aria-hidden="true"> ₱ </div>

            <h3>No debts found</h3>

            <p>You currently don't have any debt records.</p>
        </div>
    @endif
</section>