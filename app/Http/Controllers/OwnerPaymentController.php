<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class OwnerPaymentController extends Controller
{
    private const PER_PAGE = 20;

    public function index(Request $request): View
    {
        return view('owner.payments', [
            ...$this->viewData($request),
            'receivers' => $this->receivers(),
        ]);
    }

    public function list(Request $request): View
    {
        return view('components.owner.payments-list', $this->viewData($request));
    }

    /**
     * @return array<string, mixed>
     */
    private function viewData(Request $request): array
    {
        $filters = $this->filters($request);

        return [
            'payments' => $this->payments($filters),
            'summary' => $this->summary($filters),
            'activeFilterCount' => $this->activeFilterCount($filters),
            ...$filters,
        ];
    }

    /**
     * How many filters are currently narrowing the list — drives the toggle badge.
     *
     * @param  array{q: string, from: string, to: string, min: string, max: string, receivedBy: string}  $filters
     */
    private function activeFilterCount(array $filters): int
    {
        return collect($filters)->filter()->count();
    }

    /**
     * Normalize the incoming filter values.
     *
     * @return array{q: string, from: string, to: string, min: string, max: string, receivedBy: string}
     */
    private function filters(Request $request): array
    {
        return [
            'q' => trim($request->string('q')->toString()),
            'from' => trim($request->string('from')->toString()),
            'to' => trim($request->string('to')->toString()),
            'min' => trim($request->string('min')->toString()),
            'max' => trim($request->string('max')->toString()),
            'receivedBy' => trim($request->string('received_by')->toString()),
        ];
    }

    /**
     * @param  array{q: string, from: string, to: string, min: string, max: string, receivedBy: string}  $filters
     * @return LengthAwarePaginator<Payment>
     */
    private function payments(array $filters): LengthAwarePaginator
    {
        return $this->filtered($filters)
            ->with(['customer', 'receivedBy'])
            ->latest('payment_date')
            ->latest('payment_id')
            ->paginate(self::PER_PAGE)
            ->withQueryString();
    }

    /**
     * Roll-up of the payments currently matching the active filters.
     *
     * @param  array{q: string, from: string, to: string, min: string, max: string, receivedBy: string}  $filters
     * @return array{count: int, total: string, average: string, largest: string}
     */
    private function summary(array $filters): array
    {
        $count = $this->filtered($filters)->count();
        $total = (float) $this->filtered($filters)->sum('amount_paid');
        $largest = (float) $this->filtered($filters)->max('amount_paid');

        return [
            'count' => $count,
            'total' => $this->money($total),
            'average' => $this->money($count > 0 ? $total / $count : 0.0),
            'largest' => $this->money($largest),
        ];
    }

    /**
     * @param  array{q: string, from: string, to: string, min: string, max: string, receivedBy: string}  $filters
     */
    private function filtered(array $filters): Builder
    {
        return Payment::query()
            ->when($filters['q'] !== '', function ($builder) use ($filters) {
                $pattern = '%'.addcslashes($filters['q'], '%_').'%';

                $builder->where(function ($sub) use ($pattern) {
                    $sub->whereHas('customer', fn ($customer) => $customer
                        ->where('full_name', 'like', $pattern)
                        ->orWhere('customer_code', 'like', $pattern))
                        ->orWhereHas('receivedBy', fn ($user) => $user
                            ->where('full_name', 'like', $pattern)
                            ->orWhere('username', 'like', $pattern));
                });
            })
            ->when($filters['from'] !== '', fn ($builder) => $builder->whereDate('payment_date', '>=', $filters['from']))
            ->when($filters['to'] !== '', fn ($builder) => $builder->whereDate('payment_date', '<=', $filters['to']))
            ->when($filters['min'] !== '', fn ($builder) => $builder->where('amount_paid', '>=', (float) $filters['min']))
            ->when($filters['max'] !== '', fn ($builder) => $builder->where('amount_paid', '<=', (float) $filters['max']))
            ->when($filters['receivedBy'] !== '', fn ($builder) => $builder->where('received_by', $filters['receivedBy']));
    }

    /**
     * Staff members who have actually received at least one payment.
     *
     * @return Collection<int, User>
     */
    private function receivers(): Collection
    {
        return User::query()
            ->whereIn('user_id', Payment::query()->select('received_by')->distinct())
            ->orderBy('full_name')
            ->get(['user_id', 'full_name', 'username']);
    }

    private function money(float $value): string
    {
        return number_format($value, 2, '.', '');
    }
}
