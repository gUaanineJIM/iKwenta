<?php

namespace Tests\Unit;

use App\Enums\DebtStatus;
use App\Models\Customer;
use App\Models\Debt;
use App\Models\DebtItem;
use App\Models\Payment;
use App\Models\Product;
use App\Services\CustomerAccountService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Tests\TestCase;

class CustomerAccountServiceTest extends TestCase
{
    use RefreshDatabase;

    private CustomerAccountService $service;

    private string $userId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(CustomerAccountService::class);

        $roleId = (string) Str::uuid();
        $this->userId = (string) Str::uuid();

        DB::table('roles')->insert([
            'role_id' => $roleId,
            'role_name' => 'store_owner',
            'description' => 'Store owner',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('users')->insert([
            'user_id' => $this->userId,
            'role_id' => $roleId,
            'full_name' => 'Store Owner',
            'username' => 'owner_'.Str::random(6),
            'password' => bcrypt('secret'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function customer(): Customer
    {
        return Customer::create([
            'customer_id' => (string) Str::uuid(),
            'customer_code' => Str::random(5),
            'full_name' => 'Renesme Moral',
        ]);
    }

    private function debt(Customer $customer): Debt
    {
        return Debt::create([
            'debt_id' => (string) Str::uuid(),
            'customer_id' => $customer->customer_id,
            'created_by' => $this->userId,
        ]);
    }

    private function item(Debt $debt, string $price, int $quantity = 1): DebtItem
    {
        $product = Product::create([
            'product_id' => (string) Str::uuid(),
            'product_name' => 'Goods '.Str::random(4),
            'description' => null,
            'price' => $price,
            'created_by' => $this->userId,
        ]);

        return DebtItem::create([
            'debt_item_id' => (string) Str::uuid(),
            'debt_id' => $debt->debt_id,
            'product_id' => $product->product_id,
            'quantity' => $quantity,
            'unit_price' => $price,
            'subtotal' => number_format($quantity * (float) $price, 2, '.', ''),
        ]);
    }

    public function test_balance_is_credit_minus_payments_across_transactions(): void
    {
        $customer = $this->customer();

        $first = $this->debt($customer);
        $this->item($first, '100.00');
        $this->item($first, '25.00');

        $second = $this->debt($customer);
        $this->item($second, '50.00');

        $this->service->recordPayment($first, '40.00', $this->userId);
        $this->service->recordPayment($first, '60.00', $this->userId);

        $this->assertSame('175.00', $this->service->totalCredit($customer));
        $this->assertSame('100.00', $this->service->totalPaid($customer));
        $this->assertSame('75.00', $this->service->outstandingBalance($customer));
        $this->assertSame('25.00', $this->service->remainingBalance($first));
        $this->assertSame('50.00', $this->service->remainingBalance($second));
    }

    public function test_transaction_total_is_isolated_per_debt(): void
    {
        $customer = $this->customer();

        $first = $this->debt($customer);
        $this->item($first, '100.00');
        $this->item($first, '25.00');

        $second = $this->debt($customer);
        $this->item($second, '50.00');

        $this->assertSame('125.00', $this->service->transactionTotal($first));
        $this->assertSame('50.00', $this->service->transactionTotal($second));
        $this->assertSame('175.00', $this->service->totalCredit($customer));
    }

    public function test_payment_is_attributed_to_the_target_credit_record(): void
    {
        $customer = $this->customer();
        $debt = $this->debt($customer);
        $this->item($debt, '100.00');

        $payment = $this->service->recordPayment($debt, '30.00', $this->userId);

        $this->assertSame($customer->customer_id, $payment->customer_id);
        $this->assertSame($debt->debt_id, $payment->debt_id);
        $this->assertDatabaseHas('payments', [
            'payment_id' => $payment->payment_id,
            'customer_id' => $customer->customer_id,
            'debt_id' => $debt->debt_id,
        ]);
    }

    public function test_partial_payment_sets_partially_paid_status_and_updates_remaining(): void
    {
        $customer = $this->customer();
        $debt = $this->debt($customer);
        $this->item($debt, '100.00');

        $this->service->recordPayment($debt, '30.00', $this->userId);

        $debt->refresh();

        $this->assertSame(DebtStatus::PartiallyPaid, $debt->status);
        $this->assertSame('30.00', $this->service->paymentsTotal($debt));
        $this->assertSame('70.00', $this->service->remainingBalance($debt));
    }

    public function test_payment_equal_to_remaining_marks_record_paid(): void
    {
        $customer = $this->customer();
        $debt = $this->debt($customer);
        $this->item($debt, '100.00');

        $this->service->recordPayment($debt, '100.00', $this->userId);

        $debt->refresh();

        $this->assertSame(DebtStatus::Paid, $debt->status);
        $this->assertFalse($debt->paid_manually);
        $this->assertSame('0.00', $this->service->remainingBalance($debt));
        $this->assertNotNull($debt->paid_at);
    }

    public function test_multiple_partial_payments_accumulate_until_paid(): void
    {
        $customer = $this->customer();
        $debt = $this->debt($customer);
        $this->item($debt, '100.00');

        $this->service->recordPayment($debt, '30.00', $this->userId);

        $debt->refresh();
        $this->assertSame(DebtStatus::PartiallyPaid, $debt->status);

        $this->service->recordPayment($debt, '70.00', $this->userId);

        $debt->refresh();
        $this->assertSame(DebtStatus::Paid, $debt->status);
        $this->assertSame('0.00', $this->service->remainingBalance($debt));
        $this->assertSame('100.00', $this->service->paymentsTotal($debt));
        $this->assertNotNull($debt->paid_at);
    }

    public function test_manually_marking_paid_records_exact_remaining_as_payment(): void
    {
        $customer = $this->customer();
        $debt = $this->debt($customer);
        $this->item($debt, '100.00');

        $this->service->recordPayment($debt, '30.00', $this->userId);

        $this->service->markAsPaid($debt, $this->userId);

        $debt->refresh();

        $this->assertSame(DebtStatus::Paid, $debt->status);
        $this->assertTrue($debt->paid_manually);
        $this->assertSame($this->userId, $debt->paid_manually_by);
        $this->assertNotNull($debt->paid_manually_at);
        $this->assertNotNull($debt->paid_at);

        // The exact remaining balance (70.00) is recorded as a payment.
        $this->assertSame('100.00', $this->service->paymentsTotal($debt));
        $this->assertSame('0.00', $this->service->remainingBalance($debt));
        $this->assertSame('0.00', $this->service->outstandingBalance($customer));

        $this->assertDatabaseHas('payments', [
            'debt_id' => $debt->debt_id,
            'amount_paid' => '70.00',
            'received_by' => $this->userId,
        ]);

        $this->assertDatabaseHas('activity_logs', [
            'action' => 'debt.mark_paid',
            'table_name' => 'debts',
            'record_id' => $debt->debt_id,
            'user_id' => $this->userId,
        ]);
    }

    public function test_mark_as_paid_on_an_already_paid_record_is_rejected(): void
    {
        $customer = $this->customer();
        $debt = $this->debt($customer);
        $this->item($debt, '100.00');

        $this->service->recordPayment($debt, '100.00', $this->userId);

        $this->expectException(InvalidArgumentException::class);

        $this->service->markAsPaid($debt, $this->userId);
    }

    public function test_overpayment_beyond_remaining_balance_is_rejected(): void
    {
        $customer = $this->customer();
        $debt = $this->debt($customer);
        $this->item($debt, '100.00');

        $this->expectException(InvalidArgumentException::class);

        $this->service->recordPayment($debt, '150.00', $this->userId);
    }

    public function test_partial_payment_cannot_exceed_the_remaining_balance(): void
    {
        $customer = $this->customer();
        $debt = $this->debt($customer);
        $this->item($debt, '100.00');

        $this->service->recordPayment($debt, '30.00', $this->userId);

        $this->expectException(InvalidArgumentException::class);

        $this->service->recordPayment($debt, '80.00', $this->userId);
    }

    public function test_payment_on_an_already_paid_record_is_rejected(): void
    {
        $customer = $this->customer();
        $debt = $this->debt($customer);
        $this->item($debt, '100.00');

        $this->service->recordPayment($debt, '100.00', $this->userId);

        $this->expectException(InvalidArgumentException::class);

        $this->service->recordPayment($debt, '50.00', $this->userId);
    }

    public function test_new_credit_record_does_not_reopen_a_paid_record(): void
    {
        $customer = $this->customer();

        $first = $this->debt($customer);
        $this->item($first, '100.00');
        $this->service->recordPayment($first, '30.00', $this->userId);
        $this->service->markAsPaid($first, $this->userId);

        $second = $this->debt($customer);
        $this->item($second, '50.00');

        $this->assertSame(DebtStatus::Paid, $first->refresh()->status);
        $this->assertNotNull($first->paid_at);
        $this->assertSame(DebtStatus::Unpaid, $second->refresh()->status);
        $this->assertSame('50.00', $this->service->outstandingBalance($customer));
        $this->assertSame('100.00', $this->service->totalPaid($customer));
    }

    public function test_paid_at_is_preserved_when_the_paid_status_is_refreshed(): void
    {
        $customer = $this->customer();
        $debt = $this->debt($customer);
        $this->item($debt, '100.00');

        $this->service->markAsPaid($debt, $this->userId);

        $settledAt = $debt->refresh()->paid_at;
        $this->assertNotNull($settledAt);

        $this->service->refreshStatus($debt);

        $this->assertSame(DebtStatus::Paid, $debt->refresh()->status);
        $this->assertSame($settledAt->toDateTimeString(), $debt->paid_at->toDateTimeString());
    }

    public function test_zero_positive_amount_is_rejected(): void
    {
        $customer = $this->customer();
        $debt = $this->debt($customer);
        $this->item($debt, '100.00');

        $this->expectException(InvalidArgumentException::class);

        $this->service->recordPayment($debt, '0.00', $this->userId);
    }

    public function test_negative_amount_is_rejected(): void
    {
        $customer = $this->customer();
        $debt = $this->debt($customer);
        $this->item($debt, '100.00');

        $this->expectException(InvalidArgumentException::class);

        $this->service->recordPayment($debt, '-10.00', $this->userId);
    }

    public function test_empty_account_balances_to_zero(): void
    {
        $customer = $this->customer();

        $this->assertSame('0.00', $this->service->totalCredit($customer));
        $this->assertSame('0.00', $this->service->totalPaid($customer));
        $this->assertSame('0.00', $this->service->outstandingBalance($customer));
    }
}
