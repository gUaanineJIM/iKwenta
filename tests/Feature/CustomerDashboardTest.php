<?php

namespace Tests\Feature;

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
use Tests\TestCase;

class CustomerDashboardTest extends TestCase
{
    use RefreshDatabase;

    private string $userId;

    protected function setUp(): void
    {
        parent::setUp();

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

    private function customer(string $code, string $name): Customer
    {
        return Customer::create([
            'customer_id' => (string) Str::uuid(),
            'customer_code' => $code,
            'full_name' => $name,
        ]);
    }

    private function product(string $name, string $price = '50.00'): Product
    {
        return Product::create([
            'product_id' => (string) Str::uuid(),
            'product_name' => $name,
            'description' => null,
            'price' => $price,
            'created_by' => $this->userId,
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

    private function item(Debt $debt, Product $product, int $quantity = 1, string $unitPrice = '50.00', int $daysAgo = 1): DebtItem
    {
        $item = DebtItem::create([
            'debt_item_id' => (string) Str::uuid(),
            'debt_id' => $debt->debt_id,
            'product_id' => $product->product_id,
            'quantity' => $quantity,
            'unit_price' => $unitPrice,
            'subtotal' => number_format($quantity * (float) $unitPrice, 2, '.', ''),
        ]);

        $item->created_at = now()->subDays($daysAgo);
        $item->save();

        return $item;
    }

    private function payment(Debt $debt, string $amount, ?string $date = null): Payment
    {
        return Payment::create([
            'payment_id' => (string) Str::uuid(),
            'customer_id' => $debt->customer_id,
            'debt_id' => $debt->debt_id,
            'amount_paid' => $amount,
            'payment_date' => $date ?: now()->toDateTimeString(),
            'received_by' => $this->userId,
        ]);
    }

    public function test_guest_is_redirected_away_from_dashboard(): void
    {
        $this->get('/customer/dashboard')
            ->assertRedirect('/')
            ->assertSessionHasErrors('code');
    }

    public function test_guest_ajax_section_request_returns_unauthenticated(): void
    {
        $this->get('/customer/dashboard/section/items', ['X-Requested-With' => 'XMLHttpRequest'])
            ->assertStatus(401)
            ->assertJson(['message' => 'Unauthenticated.']);
    }

    public function test_summary_totals_and_remaining_balance_are_correct(): void
    {
        $a = $this->customer('11111', 'Renesme Moral');

        $debtOne = $this->debt($a);
        $this->item($debtOne, $this->product('Rice'), 2, '100.00');
        $this->payment($debtOne, '120.00');

        $debtTwo = $this->debt($a);
        $this->item($debtTwo, $this->product('Soap'), 1, '25.00');

        $this->withSession(['customer_id' => $a->customer_id])
            ->get('/customer/dashboard')
            ->assertOk()
            ->assertSee('225.00')   // 200 + 25 total debt
            ->assertSee('120.00')   // total paid
            ->assertSee('105.00');  // remaining
    }

    public function test_summary_accounts_for_payments_across_transactions(): void
    {
        $a = $this->customer('11112', 'Renesme Moral');
        $service = app(CustomerAccountService::class);

        $first = $this->debt($a);
        $this->item($first, $this->product('Bread'), 1, '200.00');

        $second = $this->debt($a);
        $this->item($second, $this->product('Milk'), 1, '100.00');
        $service->recordPayment($second, '40.00', $this->userId);

        $third = $this->debt($a);
        $this->item($third, $this->product('Coffee'), 1, '50.00');
        $service->recordPayment($third, '50.00', $this->userId);

        $this->withSession(['customer_id' => $a->customer_id])
            ->get('/customer/dashboard')
            ->assertOk()
            ->assertSee('350.00')   // total debt: 200 + 100 + 50
            ->assertSee('90.00')    // total paid: 40 + 50
            ->assertSee('260.00');  // remaining balance: 200 + 60 + 0
    }

    public function test_debt_transaction_renders_status_total_paid_and_remaining(): void
    {
        $a = $this->customer('11123', 'Renesme Moral');
        $debt = $this->debt($a);
        $this->item($debt, $this->product('Rice'), 1, '10.00');

        $this->withSession(['customer_id' => $a->customer_id])
            ->get('/customer/dashboard/section/debts', ['X-Requested-With' => 'XMLHttpRequest'])
            ->assertOk()
            ->assertSee('10.00')
            ->assertSee('status-badge', false)
            ->assertSee('Unpaid')
            ->assertSee('Active Credits')
            ->assertSee('Remaining');
    }

    public function test_debts_section_splits_active_and_history(): void
    {
        $a = $this->customer('11125', 'Renesme Moral');

        $active = $this->debt($a);
        $this->item($active, $this->product('Rice'), 1, '100.00');

        $paid = $this->debt($a);
        $this->item($paid, $this->product('Soap'), 1, '50.00');

        $service = app(CustomerAccountService::class);
        $service->recordPayment($active, '30.00', $this->userId);
        $service->recordPayment($paid, '50.00', $this->userId);

        $response = $this->withSession(['customer_id' => $a->customer_id])
            ->get('/customer/dashboard/section/debts', ['X-Requested-With' => 'XMLHttpRequest']);

        $response->assertOk()
            ->assertSee('Active Credits')
            ->assertSee('Partially Paid')
            ->assertSee('History')
            ->assertSee('status-badge--paid', false)
            ->assertSee('70.00');   // active record's remaining
    }

    public function test_manually_marked_record_shows_manual_badge(): void
    {
        $a = $this->customer('11126', 'Renesme Moral');

        $debt = $this->debt($a);
        $this->item($debt, $this->product('Rice'), 1, '100.00');
        $this->payment($debt, '30.00');

        $debt->status = DebtStatus::Paid;
        $debt->paid_manually = true;
        $debt->paid_manually_by = $this->userId;
        $debt->paid_manually_at = now();
        $debt->save();

        $this->withSession(['customer_id' => $a->customer_id])
            ->get('/customer/dashboard/section/debts', ['X-Requested-With' => 'XMLHttpRequest'])
            ->assertOk()
            ->assertSee('History')
            ->assertSee('status-badge--manual', false)
            ->assertSee('Paid — Manually Marked');
    }

    public function test_customer_only_sees_their_own_data(): void
    {
        $a = $this->customer('11113', 'Renesme Moral');
        $b = $this->customer('22222', 'Axel Moral');

        $debtA = $this->debt($a);
        $this->item($debtA, $this->product('Renesme Exclusive'), 1, '10.00');

        $debtB = $this->debt($b);
        $this->item($debtB, $this->product('Axel Exclusive'), 1, '10.00');

        $this->withSession(['customer_id' => $a->customer_id])
            ->get('/customer/dashboard')
            ->assertOk()
            ->assertSee('Renesme Exclusive')
            ->assertDontSee('Axel Exclusive');
    }

    public function test_overview_only_contains_five_recent_items(): void
    {
        $a = $this->customer('11114', 'Renesme Moral');
        $debt = $this->debt($a);

        foreach (range(1, 8) as $i) {
            $this->item($debt, $this->product('Item '.$i), 1, '10.00', $i);
        }

        $response = $this->withSession(['customer_id' => $a->customer_id])
            ->get('/customer/dashboard');

        $response->assertOk();

        foreach (range(1, 5) as $i) {
            $response->assertSee('Item '.$i);
        }

        foreach (range(6, 8) as $i) {
            $response->assertDontSee('Item '.$i);
        }

        $response->assertSee('/customer/dashboard/section/items');
    }

    public function test_items_section_is_scoped_and_paginated(): void
    {
        $a = $this->customer('11115', 'Renesme Moral');
        $b = $this->customer('33333', 'Axel Moral');

        $debtA = $this->debt($a);
        $debtB = $this->debt($b);

        foreach (range(1, 15) as $i) {
            $this->item($debtA, $this->product('Mine '.str_pad((string) $i, 2, '0', STR_PAD_LEFT)), 1, '10.00', $i);
        }

        $this->item($debtB, $this->product('Not Yours'), 1, '10.00');

        $session = ['customer_id' => $a->customer_id];

        $pageOne = $this->withSession($session)
            ->get('/customer/dashboard/section/items', ['X-Requested-With' => 'XMLHttpRequest'])
            ->assertOk();

        foreach (range(1, 12) as $i) {
            $pageOne->assertSee('Mine '.str_pad((string) $i, 2, '0', STR_PAD_LEFT));
        }

        $pageOne
            ->assertDontSee('Mine 13')
            ->assertDontSee('Mine 14')
            ->assertDontSee('Mine 15')
            ->assertDontSee('Not Yours');

        $pageTwo = $this->withSession($session)
            ->get('/customer/dashboard/section/items?page=2', ['X-Requested-With' => 'XMLHttpRequest'])
            ->assertOk();

        foreach (range(13, 15) as $i) {
            $pageTwo->assertSee('Mine '.$i);
        }

        $pageTwo
            ->assertDontSee('Mine 01')
            ->assertDontSee('Mine 12')
            ->assertDontSee('Not Yours');
    }

    public function test_payments_section_is_scoped_and_paginated(): void
    {
        $a = $this->customer('11116', 'Renesme Moral');
        $b = $this->customer('44444', 'Axel Moral');

        $debtA = $this->debt($a);
        foreach (range(1, 10) as $i) {
            $this->payment($debtA, number_format($i * 10, 2, '.', ''), now()->subDays($i)->toDateTimeString());
        }

        $debtB = $this->debt($b);
        $this->payment($debtB, '999.00');

        $session = ['customer_id' => $a->customer_id];

        $pageOne = $this->withSession($session)
            ->get('/customer/dashboard/section/payments', ['X-Requested-With' => 'XMLHttpRequest'])
            ->assertOk()
            ->assertDontSee('999.00');

        foreach (range(1, 8) as $i) {
            $pageOne->assertSee(number_format($i * 10, 2, '.', ''));
        }

        $pageTwo = $this->withSession($session)
            ->get('/customer/dashboard/section/payments?page=2', ['X-Requested-With' => 'XMLHttpRequest'])
            ->assertOk();

        foreach (range(9, 10) as $i) {
            $pageTwo->assertSee(number_format($i * 10, 2, '.', ''));
        }
    }

    public function test_debts_section_only_shows_own_debts(): void
    {
        $a = $this->customer('11117', 'Renesme Moral');
        $b = $this->customer('55555', 'Axel Moral');

        $debtA = $this->debt($a);
        $debtB = $this->debt($b);

        $this->item($debtA, $this->product('My Goods'), 1, '10.00');
        $this->item($debtB, $this->product('Other Goods'), 1, '10.00');

        $this->withSession(['customer_id' => $a->customer_id])
            ->get('/customer/dashboard/section/debts', ['X-Requested-With' => 'XMLHttpRequest'])
            ->assertOk()
            ->assertSee('My Goods')
            ->assertDontSee('Other Goods');
    }

    public function test_debt_item_notes_are_shown_in_a_notes_column(): void
    {
        $a = $this->customer('11124', 'Renesme Moral');
        $debt = $this->debt($a);

        $withNote = $this->item($debt, $this->product('Rice'), 1, '10.00');
        $withNote->notes = 'Handle with care';
        $withNote->save();

        $this->item($debt, $this->product('Soap'), 1, '5.00');

        $this->withSession(['customer_id' => $a->customer_id])
            ->get('/customer/dashboard/section/items', ['X-Requested-With' => 'XMLHttpRequest'])
            ->assertOk()
            ->assertSee('Notes')
            ->assertSee('Handle with care');
    }

    public function test_no_js_page_fallback_renders_full_shell_with_section(): void
    {
        $a = $this->customer('11118', 'Renesme Moral');
        $debt = $this->debt($a);
        $this->item($debt, $this->product('Fallback Item'), 1, '10.00');

        $this->withSession(['customer_id' => $a->customer_id])
            ->get('/customer/dashboard/section/items')
            ->assertOk()
            ->assertSee('Fallback Item')
            ->assertSee('Sign Out');
    }

    public function test_payment_history_is_expanded_on_overview_by_default(): void
    {
        $a = $this->customer('11119', 'Renesme Moral');
        $debt = $this->debt($a);
        $this->payment($debt, '50.00');

        $this->withSession(['customer_id' => $a->customer_id])
            ->get('/customer/dashboard')
            ->assertOk()
            ->assertSee('data-collapse-toggle')
            ->assertSee('collapse-toggle is-open', false)
            ->assertSee('aria-expanded="true"', false)
            ->assertSee('50.00');
    }

    public function test_items_section_shows_items_total_and_amount_payable(): void
    {
        $a = $this->customer('11121', 'Renesme Moral');
        $debt = $this->debt($a);

        $this->item($debt, $this->product('Rice'), 1, '100.00');
        $this->item($debt, $this->product('Soap'), 1, '200.00');
        $this->payment($debt, '120.00');

        $this->withSession(['customer_id' => $a->customer_id])
            ->get('/customer/dashboard/section/items', ['X-Requested-With' => 'XMLHttpRequest'])
            ->assertOk()
            ->assertSee('Total of debt items')
            ->assertSee('300.00')            // sum of item subtotals
            ->assertSee('Less: partial payment')
            ->assertSee('&minus;', false)    // payments shown as a deduction
            ->assertSee('Amount payable')
            ->assertSee('180.00');           // 300 - 120
    }

    public function test_items_section_hides_deduction_when_nothing_paid(): void
    {
        $a = $this->customer('11122', 'Renesme Moral');
        $debt = $this->debt($a);

        $this->item($debt, $this->product('Rice'), 1, '150.00');

        $this->withSession(['customer_id' => $a->customer_id])
            ->get('/customer/dashboard/section/items', ['X-Requested-With' => 'XMLHttpRequest'])
            ->assertOk()
            ->assertSee('Total of debt items')
            ->assertSee('Amount payable')
            ->assertSee('150.00')
            ->assertDontSee('Less:')
            ->assertDontSee('&minus;', false);
    }

    public function test_shell_has_theme_toggle_and_sidebar_controls(): void
    {
        $a = $this->customer('11120', 'Renesme Moral');

        $this->withSession(['customer_id' => $a->customer_id])
            ->get('/customer/dashboard')
            ->assertOk()
            ->assertSee('data-theme-toggle')
            ->assertSee('data-shell', false)
            ->assertSee('sidebar-brand')
            ->assertSee('logo-mark')
            ->assertSee('data-sidebar-toggle')
            ->assertSee('aria-controls="customer-sidebar"', false);
    }
}
