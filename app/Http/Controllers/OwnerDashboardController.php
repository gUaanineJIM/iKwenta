<?php

namespace App\Http\Controllers;

use App\Enums\DebtStatus;
use App\Models\Customer;
use App\Models\Debt;
use App\Services\CustomerAccountService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class OwnerDashboardController extends Controller
{
    public function __construct(private CustomerAccountService $accounts) {}

    public function index(): View
    {
        $customerModels = Customer::query()
            ->with(['debts' => fn ($query) => $query->with(['items', 'payments'])])
            ->get();

        $customers = $customerModels
            ->map(fn (Customer $customer): array => $this->customerRankingData($customer));

        $recentPaidOffCustomers = $customers
            ->filter(function (array $customer): bool {
                $settledAt = $customer['latest_paid_at'];

                return (float) $customer['remaining_balance'] <= 0
                    && $settledAt instanceof Carbon
                    && $settledAt->gte(now()->subDays(Debt::ARCHIVE_RETENTION_DAYS));
            })
            ->sortByDesc('latest_paid_at')
            ->values();

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

        $debts = $customerModels->flatMap(fn (Customer $customer): Collection => $customer->debts);

        return view('owner.dashboard', [
            'totalDebtRanking' => $totalDebtRanking,
            'longestOutstandingRanking' => $longestOutstandingRanking,
            'customersInDebt' => $customers->filter(fn (array $customer): bool => (float) $customer['remaining_balance'] > 0)->count(),
            'customerCount' => $customers->count(),
            'recentPaidOffCustomers' => $recentPaidOffCustomers,
            'latestPaidOffCustomer' => $recentPaidOffCustomers->first(),
            'weeklyTrend' => $this->weeklyTrend($debts),
        ]);
    }

    /**
     * Debt added and collections received per day for the current Monday-Sunday week.
     *
     * Reuses the already eager-loaded debt graph, so this costs no extra queries.
     *
     * @return array{labels: list<string>, added: list<float>, collected: list<float>, total: string}
     */
    private function weeklyTrend(Collection $debts): array
    {
        $weekStart = now()->startOfWeek(Carbon::MONDAY);
        $days = [];

        for ($offset = 0; $offset < 7; $offset++) {
            $date = $weekStart->copy()->addDays($offset);

            $days[$date->format('Y-m-d')] = [
                'label' => $date->format('D'),
                'added' => 0.0,
                'collected' => 0.0,
            ];
        }

        foreach ($debts as $debt) {
            $key = ($debt->loaned_at ?? $debt->created_at)->format('Y-m-d');

            if (isset($days[$key])) {
                $days[$key]['added'] += (float) $this->accounts->transactionTotal($debt);
            }
        }

        foreach ($debts as $debt) {
            foreach ($debt->payments as $payment) {
                $key = $payment->payment_date->format('Y-m-d');

                if (isset($days[$key])) {
                    $days[$key]['collected'] += (float) $payment->amount_paid;
                }
            }
        }

        $days = array_values($days);

        return [
            'labels' => array_column($days, 'label'),
            'added' => array_column($days, 'added'),
            'collected' => array_column($days, 'collected'),
            'total' => $this->money(array_sum(array_column($days, 'added'))),
        ];
    }

    private function customerRankingData(Customer $customer): array
    {
        $debts = $customer->debts;
        $openDebts = $debts->filter(fn (Debt $debt): bool => (float) $this->accounts->remainingBalance($debt) > 0);
        $latestPaidAt = $debts
            ->filter(fn (Debt $debt): bool => $debt->status === DebtStatus::Paid && $debt->paid_at !== null)
            ->max('paid_at');
        $totalDebt = (float) $this->sumMoney($debts->map(fn (Debt $debt): string => $this->accounts->transactionTotal($debt))->all());
        $paid = (float) $this->sumMoney($debts->map(fn (Debt $debt): string => $this->accounts->paymentsTotal($debt))->all());

        return [
            'customer_id' => $customer->customer_id,
            'name' => $customer->full_name,
            'avatar_path' => $customer->avatar_path,
            'gender' => $customer->gender,
            'latest_paid_at' => $latestPaidAt,
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

    private function money(float $value): string
    {
        return number_format($value, 2, '.', '');
    }
}
