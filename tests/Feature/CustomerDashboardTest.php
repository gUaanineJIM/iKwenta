<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Debt;
use App\Models\DebtItem;
use App\Models\Payment;
use App\Models\Product;
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

    private function debt(Customer $customer, string $status = 'unpaid'): Debt
    {
        return Debt::create([
            'debt_id' => (string) Str::uuid(),
            'customer_id' => $customer->customer_id,
            'created_by' => $this->userId,
            'status' => $status,
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

        $debtOne = $this->debt($a, 'unpaid');
        $this->item($debtOne, $this->product('Rice'), 2, '100.00');
        $this->payment($debtOne, '120.00');

        $debtTwo = $this->debt($a, 'partially_paid');
        $this->item($debtTwo, $this->product('Soap'), 1, '25.00');

        $this->withSession(['customer_id' => $a->customer_id])
            ->get('/customer/dashboard')
            ->assertOk()
            ->assertSee('225.00')   // 200 + 25 total debt
            ->assertSee('120.00')   // total paid
            ->assertSee('105.00');  // remaining
    }

    public function test_cancelled_debts_are_excluded_from_the_summary(): void
    {
        $a = $this->customer('11112', 'Renesme Moral');

        $active = $this->debt($a, 'unpaid');
        $this->item($active, $this->product('Bread'), 1, '200.00');
        $this->payment($active, '120.00');

        $cancelled = $this->debt($a, 'cancelled');
        $this->item($cancelled, $this->product('Cancelled Goods'), 1, '500.00');

        $this->withSession(['customer_id' => $a->customer_id])
            ->get('/customer/dashboard')
            ->assertOk()
            ->assertSee('200.00')
            ->assertSee('120.00')
            ->assertSee('80.00')
            ->assertDontSee('700.00');
    }

    public function test_customer_only_sees_their_own_data(): void
    {
        $a = $this->customer('11113', 'Renesme Moral');
        $b = $this->customer('22222', 'Axel Moral');

        $debtA = $this->debt($a, 'unpaid');
        $this->item($debtA, $this->product('Renesme Exclusive'), 1, '10.00');

        $debtB = $this->debt($b, 'unpaid');
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
        $debt = $this->debt($a, 'unpaid');

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

        $debtA = $this->debt($a, 'unpaid');
        $debtB = $this->debt($b, 'unpaid');

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

        $debtA = $this->debt($a, 'partially_paid');
        $debtB = $this->debt($b, 'unpaid');

        foreach (range(1, 10) as $i) {
            $this->payment($debtA, number_format($i * 10, 2, '.', ''), now()->subDays($i)->toDateTimeString());
        }

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

        $debtA = $this->debt($a, 'unpaid');
        $debtB = $this->debt($b, 'cancelled');

        $this->item($debtA, $this->product('My Goods'), 1, '10.00');
        $this->item($debtB, $this->product('Other Goods'), 1, '10.00');

        $this->withSession(['customer_id' => $a->customer_id])
            ->get('/customer/dashboard/section/debts', ['X-Requested-With' => 'XMLHttpRequest'])
            ->assertOk()
            ->assertSee('#', false)
            ->assertSee('My Goods')
            ->assertDontSee('Other Goods');
    }

    public function test_no_js_page_fallback_renders_full_shell_with_section(): void
    {
        $a = $this->customer('11118', 'Renesme Moral');
        $debt = $this->debt($a, 'unpaid');
        $this->item($debt, $this->product('Fallback Item'), 1, '10.00');

        $this->withSession(['customer_id' => $a->customer_id])
            ->get('/customer/dashboard/section/items')
            ->assertOk()
            ->assertSee('Fallback Item')
            ->assertSee('Sign Out');
    }

    public function test_payment_history_is_collapsible_on_overview(): void
    {
        $a = $this->customer('11119', 'Renesme Moral');
        $debt = $this->debt($a, 'partially_paid');
        $this->payment($debt, '50.00');

        $this->withSession(['customer_id' => $a->customer_id])
            ->get('/customer/dashboard')
            ->assertOk()
            ->assertSee('data-collapse-toggle')
            ->assertSee('aria-expanded="false"', false)
            ->assertSee('50.00');
    }

    public function test_shell_has_theme_toggle_and_collapsible_sidebar_controls(): void
    {
        $a = $this->customer('11120', 'Renesme Moral');

        $this->withSession(['customer_id' => $a->customer_id])
            ->get('/customer/dashboard')
            ->assertOk()
            ->assertSee('data-theme-toggle')
            ->assertSee('data-shell', false)
            ->assertSee('data-sidebar-collapse')
            ->assertSee('data-sidebar-toggle')
            ->assertSee('data-sidebar-close')
            ->assertSee('aria-controls="customer-sidebar"', false);
    }
}
