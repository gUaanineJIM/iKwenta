<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Illuminate\View\View;

class OwnerActivityLogController extends Controller
{
    private const PER_PAGE = 25;

    private const TYPE_LABELS = [
        'customers' => 'Customer',
        'products' => 'Product',
        'debts' => 'Debt',
        'payments' => 'Payment',
        'users' => 'Sign-in',
    ];

    private const FIELD_LABELS = [
        'full_name' => 'Name',
        'customer_code' => 'Portal code',
        'debt_id' => 'Debt',
        'product_name' => 'Product',
        'description' => 'Description',
        'price' => 'Price',
        'status' => 'Status',
        'paid_manually' => 'Marked paid manually',
        'paid_manually_by' => 'Marked paid by',
        'paid_manually_at' => 'Marked paid at',
        'amount_paid' => 'Amount paid',
        'money_amount' => 'Money owed',
        'payment_date' => 'Payment date',
        'loaned_at' => 'Loaned on',
        'username' => 'Username',
        'customer_name' => 'Customer',
        'payments' => 'Payments',
        'settled_total' => 'Settled total',
    ];

    public function index(Request $request): View|RedirectResponse
    {
        if (! $this->requiresOwner($request)) {
            return redirect()->route('landing', ['#login']);
        }

        return view('owner.activity-logs', $this->viewData($request));
    }

    public function list(Request $request): View|JsonResponse|RedirectResponse
    {
        if (! $this->requiresOwner($request)) {
            if ($request->ajax()) {
                return response()->json(['message' => 'Unauthenticated.'], 401);
            }

            return redirect()->route('landing', ['#login']);
        }

        return view('components.owner.activity-logs-list', $this->viewData($request));
    }

    private function viewData(Request $request): array
    {
        $paginator = $this->activityLogs($request);

        $entries = collect($paginator->items())
            ->map(fn (ActivityLog $log): array => [
                'time' => $log->created_at?->timezone('Asia/Manila')->format('M d, Y h:i A'),
                'actor' => $log->user?->full_name ?? 'Unknown',
                'type_label' => $this->typeLabel($log->table_name),
                'type_key' => $log->table_name,
                'action_label' => $this->actionLabel($log),
                'record' => $this->recordLabel($log),
                'changes' => $this->changes($log),
            ]);

        return [
            'logs' => $paginator,
            'entries' => $entries,
            'q' => $request->string('q')->toString(),
            'filter' => $request->string('filter')->toString(),
        ];
    }

    private function requiresOwner(Request $request): bool
    {
        return $request->session()->has('owner_id');
    }

    private function activityLogs(Request $request)
    {
        $search = trim($request->string('q')->toString());
        $filter = trim($request->string('filter')->toString());

        return ActivityLog::query()
            ->with('user')
            ->when($search !== '', function ($builder) use ($search) {
                $pattern = '%'.addcslashes($search, '%_').'%';

                $builder->where(function ($sub) use ($pattern) {
                    $sub->where('action', 'like', $pattern)
                        ->orWhere('table_name', 'like', $pattern)
                        ->orWhere('new_values->full_name', 'like', $pattern)
                        ->orWhere('new_values->customer_name', 'like', $pattern)
                        ->orWhere('new_values->product_name', 'like', $pattern)
                        ->orWhere('new_values->username', 'like', $pattern)
                        ->orWhereHas('user', fn ($user) => $user->where('full_name', 'like', $pattern));
                });
            })
            ->when($filter !== '', fn ($builder) => $builder->where('table_name', $this->filterToTable($filter)))
            ->latest()
            ->paginate(self::PER_PAGE)
            ->withQueryString();
    }

    private function filterToTable(string $filter): string
    {
        return $filter === 'auth' ? 'users' : $filter;
    }

    private function actionLabel(ActivityLog $log): string
    {
        $action = $log->action;
        $type = $this->typeLabel($log->table_name);

        return match (true) {
            in_array($action, ['create', 'customer.create'], true) => "{$type} created",
            in_array($action, ['update', 'customer.update'], true) => "{$type} updated",
            in_array($action, ['delete', 'customer.delete'], true) => "{$type} deleted",
            $action === 'debt.mark_paid' => 'Debt marked as paid',
            $action === 'payment.create' => 'Payment recorded',
            $action === 'payment.pay_in_full' => 'Account settled in full',
            $action === 'auth.login' => 'Owner signed in',
            $action === 'auth.logout' => 'Owner signed out',
            default => Str::headline($action),
        };
    }

    private function typeLabel(string $tableName): string
    {
        return self::TYPE_LABELS[$tableName] ?? ucfirst($tableName);
    }

    private function recordLabel(ActivityLog $log): string
    {
        $new = $log->new_values ?? [];
        $old = $log->old_values ?? [];

        return match ($log->table_name) {
            'customers' => $new['full_name'] ?? $old['full_name'] ?? 'Customer',
            'products' => $new['product_name'] ?? $old['product_name'] ?? 'Product',
            'payments' => $new['customer_name'] ?? $old['customer_name'] ?? 'Payment',
            'debts' => 'Debt · '.substr((string) $log->record_id, 0, 8),
            'users' => $new['username'] ?? 'Owner',
            default => 'Record',
        };
    }

    private function changes(ActivityLog $log): array
    {
        $old = $log->old_values ?? [];
        $new = $log->new_values ?? [];

        $keys = array_values(array_unique(array_merge(array_keys($old), array_keys($new))));
        $changes = [];

        foreach ($keys as $key) {
            if (($old[$key] ?? null) === ($new[$key] ?? null)) {
                continue;
            }

            $changes[] = [
                'field' => self::FIELD_LABELS[$key] ?? Str::headline($key),
                'old' => $this->formatValue($key, $old[$key] ?? null),
                'new' => $this->formatValue($key, $new[$key] ?? null, $log),
            ];
        }

        return $changes;
    }

    private function formatValue(string $key, mixed $value, ?ActivityLog $log = null): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (in_array($key, ['price', 'amount_paid', 'money_amount', 'settled_total'], true)) {
            return '₱'.number_format((float) $value, 2);
        }

        if ($key === 'status') {
            return match ((string) $value) {
                'unpaid' => 'Unpaid',
                'partially_paid' => 'Partially paid',
                'paid' => 'Paid',
                default => (string) $value,
            };
        }

        if (in_array($key, ['debt_id', 'payment_id'], true) && Str::isUuid((string) $value)) {
            return '#'.substr((string) $value, 0, 8);
        }

        if ($key === 'paid_manually_by' && $log !== null && $value === $log->user_id) {
            return $log->user?->full_name ?? (string) $value;
        }

        if (in_array($key, ['payment_date', 'paid_manually_at', 'loaned_at'], true)) {
            return Carbon::parse($value)->timezone('Asia/Manila')->format('M d, Y h:i A');
        }

        if (is_bool($value)) {
            return $value ? 'Yes' : 'No';
        }

        return (string) $value;
    }
}
