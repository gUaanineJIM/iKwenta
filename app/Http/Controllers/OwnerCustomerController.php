<?php

namespace App\Http\Controllers;

use App\Enums\DebtStatus;
use App\Models\ActivityLog;
use App\Models\Customer;
use App\Models\Debt;
use App\Models\DebtItem;
use App\Models\User;
use App\Services\CustomerAccountService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class OwnerCustomerController extends Controller
{
    public function __construct(private CustomerAccountService $accounts) {}

    public function index(Request $request): View
    {
        return view('owner.customers', [
            'customerGroups' => $this->customerGroups($request->string('q')->toString()),
            'q' => trim($request->string('q')->toString()),
        ]);
    }

    public function list(Request $request): View
    {
        return view('components.owner.customers-list', [
            'customerGroups' => $this->customerGroups($request->string('q')->toString()),
            'q' => trim($request->string('q')->toString()),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'full_name' => ['required', 'string', 'max:150'],
            'gender' => ['nullable', 'in:male,female'],
            'avatar' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'debt_types' => ['required', 'array', 'min:1'],
            'debt_types.*' => ['in:product,money'],
            'products' => ['nullable', 'array', 'max:50'],
            'products.*.product_name' => ['required', 'string', 'max:150'],
            'products.*.quantity' => ['required', 'integer', 'min:1', 'max:1000000'],
            'products.*.amount' => ['required', 'numeric', 'gt:0', 'max:9999999999.99'],
            'products.*.notes' => ['nullable', 'string', 'max:500'],
            'money_amount' => ['nullable', 'numeric', 'gt:0', 'max:9999999999.99'],
            'loaned_at' => ['nullable', 'date_format:Y-m-d\\TH:i'],
        ]);

        $this->validateDebtParts($request, $validated);
        $owner = $this->requiredStoreOwner();

        $customer = DB::transaction(function () use ($validated, $owner, $request) {
            $customer = Customer::create([
                'customer_code' => $this->uniqueCustomerCode(),
                'full_name' => trim($validated['full_name']),
                'gender' => $validated['gender'] ?? 'male',
                'avatar_path' => $request->hasFile('avatar')
                    ? $request->file('avatar')->store('customer-avatars', 'public')
                    : null,
            ]);

            $debt = Debt::create([
                'customer_id' => $customer->customer_id,
                'created_by' => $owner->user_id,
                'status' => DebtStatus::Unpaid,
                'money_amount' => $validated['money_amount'] ?? '0.00',
                'loaned_at' => isset($validated['loaned_at'])
                    ? Carbon::createFromFormat('Y-m-d\\TH:i', $validated['loaned_at'], 'Asia/Manila')
                    : now('Asia/Manila'),
            ]);

            foreach ($validated['products'] ?? [] as $product) {
                DebtItem::create([
                    'debt_id' => $debt->debt_id,
                    'product_name' => trim($product['product_name']),
                    'quantity' => $product['quantity'],
                    'unit_price' => $product['amount'],
                    'subtotal' => $product['quantity'] * $product['amount'],
                    'notes' => ! empty($product['notes']) ? trim($product['notes']) : null,
                ]);
            }

            $this->log($owner->user_id, 'create', $customer->customer_id, null, [
                'full_name' => $customer->full_name,
                'customer_code' => $customer->customer_code,
                'debt_id' => $debt->debt_id,
            ]);

            return $customer;
        });

        return response()->json([
            'message' => 'Customer added.',
            'customer' => $this->customerPayload($customer->fresh()),
        ], 201);
    }

    public function update(Request $request, Customer $customer): JsonResponse
    {
        $validated = $request->validate([
            'full_name' => ['required', 'string', 'max:150'],
            'gender' => ['nullable', 'in:male,female'],
            'avatar' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'debt_types' => ['nullable', 'array', 'min:1'],
            'debt_types.*' => ['in:product,money'],
            'products' => ['nullable', 'array', 'max:50'],
            'products.*.product_name' => ['required', 'string', 'max:150'],
            'products.*.quantity' => ['required', 'integer', 'min:1', 'max:1000000'],
            'products.*.amount' => ['required', 'numeric', 'gt:0', 'max:9999999999.99'],
            'products.*.notes' => ['nullable', 'string', 'max:500'],
            'money_amount' => ['nullable', 'numeric', 'gt:0', 'max:9999999999.99'],
            'loaned_at' => ['nullable', 'date_format:Y-m-d\\TH:i'],
        ]);

        if (! empty($validated['debt_types'])) {
            $this->validateDebtParts($request, $validated);
        }

        $owner = $this->requiredStoreOwner();
        $oldValues = $customer->only(['full_name']);
        DB::transaction(function () use ($customer, $validated, $owner, $oldValues, $request) {
            $customer->update([
                'full_name' => trim($validated['full_name']),
                'gender' => $validated['gender'] ?? $customer->gender,
                'avatar_path' => $request->hasFile('avatar')
                    ? $request->file('avatar')->store('customer-avatars', 'public')
                    : $customer->avatar_path,
            ]);

            $newValues = $customer->only(['full_name']);

            if (! empty($validated['debt_types'])) {
                $debt = $this->createDebt($customer, $validated, $owner);
                $newValues['debt_id'] = $debt->debt_id;
            }

            $this->log($owner->user_id, 'update', $customer->customer_id, $oldValues, $newValues);
        });

        return response()->json(['message' => 'Customer updated.', 'customer' => $this->customerPayload($customer->fresh())]);
    }

    private function createDebt(Customer $customer, array $validated, User $owner): Debt
    {
        $debt = Debt::create([
            'customer_id' => $customer->customer_id,
            'created_by' => $owner->user_id,
            'status' => DebtStatus::Unpaid,
            'money_amount' => $validated['money_amount'] ?? '0.00',
            'loaned_at' => isset($validated['loaned_at'])
                ? Carbon::createFromFormat('Y-m-d\\TH:i', $validated['loaned_at'], 'Asia/Manila')
                : now('Asia/Manila'),
        ]);

        foreach ($validated['products'] ?? [] as $product) {
            DebtItem::create([
                'debt_id' => $debt->debt_id,
                'product_name' => trim($product['product_name']),
                'quantity' => $product['quantity'],
                'unit_price' => $product['amount'],
                'subtotal' => $product['quantity'] * $product['amount'],
                'notes' => ! empty($product['notes']) ? trim($product['notes']) : null,
            ]);
        }

        return $debt;
    }

    public function destroy(Customer $customer): JsonResponse
    {
        if ($customer->debts()->exists()) {
            return response()->json(['message' => 'Customers with debt history cannot be deleted.'], 409);
        }

        $owner = $this->requiredStoreOwner();
        $this->log($owner->user_id, 'delete', $customer->customer_id, $customer->only(['full_name', 'customer_code']), null);
        $customer->delete();

        return response()->json(['message' => 'Customer deleted.']);
    }

    public function payment(Request $request, Customer $customer): JsonResponse
    {
        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'gt:0', 'max:9999999999.99'],
            'debt_id' => ['nullable', 'uuid'],
            'pay_in_full' => ['nullable', 'boolean'],
        ]);
        $owner = $this->requiredStoreOwner();

        if ($request->boolean('pay_in_full')) {
            $payments = [];
            $openDebts = $customer->debts()
                ->where('status', '!=', DebtStatus::Paid->value)
                ->oldest()
                ->get();

            foreach ($openDebts as $openDebt) {
                $remaining = $this->accounts->remainingBalance($openDebt);

                if ((float) $remaining > 0) {
                    $payments[] = $this->accounts->recordPayment($openDebt, $remaining, $owner->user_id);
                }
            }

            return response()->json([
                'message' => 'Customer balance paid in full.',
                'payments' => $payments,
                'remaining_balance' => $this->accounts->outstandingBalance($customer->fresh()),
            ]);
        }

        $debt = ($validated['debt_id'] ?? null)
            ? $customer->debts()->whereKey($validated['debt_id'])->firstOrFail()
            : $customer->debts()->where('status', '!=', DebtStatus::Paid->value)->oldest()->first();

        if (! $debt) {
            return response()->json(['message' => 'This customer has no open debt.'], 422);
        }

        try {
            $payment = $this->accounts->recordPayment($debt, (string) $validated['amount'], $owner->user_id);
        } catch (\InvalidArgumentException $exception) {
            throw ValidationException::withMessages(['amount' => $exception->getMessage()]);
        }

        return response()->json([
            'message' => 'Payment recorded and balance updated.',
            'payment' => $payment,
            'remaining_balance' => $this->accounts->outstandingBalance($customer->fresh()),
        ]);
    }

    private function customerGroups(string $query = ''): array
    {
        $customers = Customer::query()
            ->when(trim($query) !== '', fn ($builder) => $builder->where('full_name', 'like', '%'.addcslashes(trim($query), '%_').'%'))
            ->with(['debts.items.product', 'debts.payments'])
            ->latest()
            ->limit(200)
            ->get()
            ->each(function (Customer $customer) {
                $customer->debts->each(function (Debt $debt) {
                    $debt->items_total = $this->accounts->transactionTotal($debt);
                    $debt->paid_total = $this->accounts->paymentsTotal($debt);
                    $debt->remaining_total = $this->accounts->remainingBalance($debt);
                });
                $customer->total_debt = $this->sumMoney($customer->debts->map(fn (Debt $debt) => $this->accounts->transactionTotal($debt))->all());
                $customer->remaining_balance = $this->sumMoney($customer->debts->map(fn (Debt $debt) => $this->accounts->remainingBalance($debt))->all());
            });

        return [
            'owing' => $customers->filter(fn (Customer $customer): bool => (float) $customer->remaining_balance > 0)->values(),
            'paid' => $customers->filter(fn (Customer $customer): bool => (float) $customer->remaining_balance <= 0)->values(),
        ];
    }

    private function customerPayload(Customer $customer): array
    {
        $customer->load('debts.items');

        return [
            'customer_id' => $customer->customer_id,
            'customer_code' => $customer->customer_code,
            'full_name' => $customer->full_name,
            'total_debt' => $this->sumMoney($customer->debts->map(fn (Debt $debt) => $this->accounts->transactionTotal($debt))->all()),
            'remaining_balance' => $this->sumMoney($customer->debts->map(fn (Debt $debt) => $this->accounts->remainingBalance($debt))->all()),
        ];
    }

    private function validateDebtParts(Request $request, array $validated): void
    {
        $types = $validated['debt_types'];
        if (in_array('product', $types, true) && empty($validated['products'])) {
            throw ValidationException::withMessages(['products' => 'Add at least one product owed.']);
        }
        if (in_array('money', $types, true) && empty($validated['money_amount'])) {
            throw ValidationException::withMessages(['money_amount' => 'Enter the money owed.']);
        }
    }

    private function uniqueCustomerCode(): string
    {
        do {
            $code = (string) random_int(10000, 99999);
        } while (Customer::where('customer_code', $code)->exists());

        return $code;
    }

    private function sumMoney(array $amounts): string
    {
        return number_format(array_sum(array_map('floatval', $amounts)), 2, '.', '');
    }

    private function requiredStoreOwner(): User
    {
        $sessionUserId = session('owner_id');
        if ($sessionUserId && ($user = User::find($sessionUserId))) {
            return $user;
        }
        $roleId = DB::table('roles')->where('role_name', 'store_owner')->value('role_id');
        if ($roleId && ($user = User::where('role_id', $roleId)->first())) {
            return $user;
        }
        abort(503, 'No store owner account is configured.');
    }

    private function log(string $userId, string $action, string $recordId, ?array $oldValues, ?array $newValues): void
    {
        ActivityLog::create([
            'user_id' => $userId,
            'action' => 'customer.'.$action,
            'table_name' => 'customers',
            'record_id' => $recordId,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'created_at' => now(),
        ]);
    }
}
