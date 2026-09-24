<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Debt;
use App\Services\CustomerAccountService;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class OwnerDashboardController extends Controller
{
    public function __construct(private CustomerAccountService $accounts) {}

    public function index(): View
    {
        $customers = Customer::query()
            ->with(['debts.items', 'debts.payments'])
            ->get()
            ->map(fn (Customer $customer): array => $this->customerRankingData($customer));

        $totalDebtRanking = $customers
            ->sortByDesc('total_debt')
            ->values()
            ->map(function (array $customer, int $index): array {
                $customer['rank'] = $index + 1;

                return $customer;
            });

        $longestOutstandingRanking = $customers
            ->filter(fn (array $customer): bool => $customer['oldest_open_loan_at'] !== null)
            ->sortBy('oldest_open_loan_at')
            ->values()
            ->map(function (array $customer, int $index): array {
                $customer['rank'] = $index + 1;
                $customer['days_outstanding'] = Carbon::parse($customer['oldest_open_loan_at'])->startOfDay()->diffInDays(now()->startOfDay());

                return $customer;
            });

        return view('owner.dashboard', [
            'totalDebtRanking' => $totalDebtRanking,
            'longestOutstandingRanking' => $longestOutstandingRanking,
            'customersInDebt' => $customers->filter(fn (array $customer): bool => (float) $customer['remaining_balance'] > 0)->count(),
            'customerCount' => $customers->count(),
        ]);
    }

    private function customerRankingData(Customer $customer): array
    {
        $debts = $customer->debts;
        $openDebts = $debts->filter(fn (Debt $debt): bool => (float) $this->accounts->remainingBalance($debt) > 0);
        $totalDebt = (float) $this->sumMoney($debts->map(fn (Debt $debt): string => $this->accounts->transactionTotal($debt))->all());
        $paid = (float) $this->sumMoney($debts->map(fn (Debt $debt): string => $this->accounts->paymentsTotal($debt))->all());

        return [
            'name' => $customer->full_name,
            'avatar_path' => $customer->avatar_path,
            'gender' => $customer->gender,
            'total_debt' => $totalDebt,
            'paid' => $paid,
            'remaining_balance' => (float) $this->sumMoney($openDebts->map(fn (Debt $debt): string => $this->accounts->remainingBalance($debt))->all()),
            'paid_progress' => $totalDebt > 0 ? round(($paid / $totalDebt) * 100) : 0,
            'oldest_open_loan_at' => $openDebts->min(fn (Debt $debt) => $debt->loaned_at ?? $debt->created_at)?->toISOString(),
        ];
    }

    private function sumMoney(array $amounts): string
    {
        return number_format(array_sum(array_map('floatval', $amounts)), 2, '.', '');
    }
}
