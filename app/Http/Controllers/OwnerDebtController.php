<?php

namespace App\Http\Controllers;

use App\Enums\DebtStatus;
use App\Models\Debt;
use App\Models\Payment;
use App\Services\CustomerAccountService;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class OwnerDebtController extends Controller
{
    private const TREND_MONTHS = 6;

    private const TOP_DEBTORS_LIMIT = 5;

    private const RECENT_DEBTS_LIMIT = 8;

    public function __construct(private CustomerAccountService $accounts) {}

    public function index(): View
    {
        $debts = Debt::query()
            ->with(['customer', 'items'])
            ->latest('loaned_at')
            ->get();

        $debts->each(function (Debt $debt) {
            $debt->items_total = $this->accounts->transactionTotal($debt);
            $debt->paid_total = $this->accounts->paymentsTotal($debt);
            $debt->remaining_total = $this->accounts->remainingBalance($debt);
        });

        $openDebts = $debts->filter(fn (Debt $debt): bool => ! $this->isPaid($debt));

        return view('owner.debts', [
            'summary' => $this->summary($debts, $openDebts),
            'statusBreakdown' => $this->statusBreakdown($debts),
            'aging' => $this->aging($openDebts),
            'monthlyTrend' => $this->monthlyTrend($debts),
            'topDebtors' => $this->topDebtors($openDebts),
            'recentDebts' => $debts->take(self::RECENT_DEBTS_LIMIT),
        ]);
    }

    private function summary(Collection $debts, Collection $openDebts): array
    {
        $recorded = $debts->sum(fn (Debt $debt): float => (float) $debt->items_total);
        $collected = $debts->sum(fn (Debt $debt): float => (float) $debt->paid_total);
        $outstanding = $openDebts->sum(fn (Debt $debt): float => (float) $debt->remaining_total);

        return [
            'collected_total' => $this->money($collected),
            'outstanding_total' => $this->money($outstanding),
            'total_unpaid' => $this->money($outstanding),
            'collections_rate' => $recorded > 0 ? round(($collected / $recorded) * 100) : 0,
            'open_debts_count' => $openDebts->count(),
            'customers_in_debt' => $openDebts->unique('customer_id')->count(),
        ];
    }

    private function statusBreakdown(Collection $debts): array
    {
        $statuses = DebtStatus::cases();

        return collect($statuses)->map(function (DebtStatus $status) use ($debts): array {
            $records = $debts->filter(fn (Debt $debt): bool => ($debt->status instanceof DebtStatus ? $debt->status : DebtStatus::from($debt->status)) === $status);
            $total = $records->sum(fn (Debt $debt): float => (float) $debt->items_total);
            $remaining = $records->sum(fn (Debt $debt): float => (float) $debt->remaining_total);

            return [
                'key' => $status->value,
                'label' => $status->label(),
                'count' => $records->count(),
                'total' => $this->money($total),
                'remaining' => $this->money($remaining),
            ];
        })->all();
    }

    private function aging(Collection $openDebts): array
    {
        $buckets = [
            'current' => ['label' => '0–30 days', 'count' => 0, 'total' => 0.0],
            'soon' => ['label' => '31–60 days', 'count' => 0, 'total' => 0.0],
            'late' => ['label' => '61–90 days', 'count' => 0, 'total' => 0.0],
            'overdue' => ['label' => 'Over 90 days', 'count' => 0, 'total' => 0.0],
        ];

        foreach ($openDebts as $debt) {
            $loanedAt = $debt->loaned_at ?? $debt->created_at;
            $days = $loanedAt->startOfDay()->diffInDays(now()->startOfDay());
            $key = $days <= 30 ? 'current' : ($days <= 60 ? 'soon' : ($days <= 90 ? 'late' : 'overdue'));
            $buckets[$key]['count'] += 1;
            $buckets[$key]['total'] += (float) $debt->remaining_total;
        }

        return array_map(fn (array $bucket): array => [
            ...$bucket,
            'total' => $this->money($bucket['total']),
        ], $buckets);
    }

    private function monthlyTrend(Collection $debts): array
    {
        $months = [];
        $trendStart = now()->startOfMonth()->subMonths(self::TREND_MONTHS - 1);

        for ($i = self::TREND_MONTHS - 1; $i >= 0; $i--) {
            $month = now()->startOfMonth()->subMonths($i);
            $months[$month->format('Y-m')] = [
                'label' => $month->format('M Y'),
                'added' => 0.0,
                'collected' => 0.0,
            ];
        }

        foreach ($debts as $debt) {
            $loanedAt = $debt->loaned_at ?? $debt->created_at;
            $key = $loanedAt->format('Y-m');
            if (isset($months[$key])) {
                $months[$key]['added'] += (float) $debt->items_total;
            }
        }

        Payment::query()
            ->where('payment_date', '>=', $trendStart)
            ->get(['payment_date', 'amount_paid'])
            ->each(function (Payment $payment) use (&$months) {
                $key = $payment->payment_date->format('Y-m');
                if (isset($months[$key])) {
                    $months[$key]['collected'] += (float) $payment->amount_paid;
                }
            });

        return $months;
    }

    private function topDebtors(Collection $openDebts): array
    {
        return $openDebts
            ->groupBy('customer_id')
            ->map(function (Collection $group): array {
                /** @var Debt $first */
                $first = $group->first();
                $customer = $first->customer;

                return [
                    'customer_id' => $customer->customer_id,
                    'name' => $customer->full_name,
                    'avatar_path' => $customer->avatar_path,
                    'gender' => $customer->gender,
                    'open_debts' => $group->count(),
                    'total' => $this->money($group->sum(fn (Debt $debt): float => (float) $debt->items_total)),
                    'outstanding' => $this->money($group->sum(fn (Debt $debt): float => (float) $debt->remaining_total)),
                ];
            })
            ->sortByDesc('outstanding')
            ->values()
            ->take(self::TOP_DEBTORS_LIMIT)
            ->all();
    }

    private function isPaid(Debt $debt): bool
    {
        $status = $debt->status instanceof DebtStatus ? $debt->status : DebtStatus::from($debt->status ?: 'unpaid');

        return $status->isFullyPaid();
    }

    private function money(float $value): string
    {
        return number_format($value, 2, '.', '');
    }
}
