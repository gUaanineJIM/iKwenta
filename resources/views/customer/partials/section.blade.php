@php
    $activeSection = $activeSection ?? 'overview';
@endphp

@if ($activeSection === 'debts')
    <x-customer.debt-overview :debts="$debts ?? null" />

@elseif ($activeSection === 'items')
    <x-customer.items-list
        :items="$items ?? null"
        :summary="$summary ?? ['totalDebt' => '0.00', 'totalPaid' => '0.00', 'remainingBalance' => '0.00']"
    />

@elseif ($activeSection === 'payments')
    <x-customer.payment-history :payments="$payments ?? null" />

@else
    <x-customer.overview
        :customer="$customer"
        :summary="$summary ?? ['totalDebt' => '0.00', 'totalPaid' => '0.00', 'remainingBalance' => '0.00']"
        :recent-items="$recentItems ?? null"
        :payments="$recentPayments ?? null"
    />

@endif