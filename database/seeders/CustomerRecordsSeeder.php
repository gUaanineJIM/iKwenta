<?php

namespace Database\Seeders;

use App\Enums\DebtStatus;
use App\Models\Customer;
use App\Models\Debt;
use App\Models\DebtItem;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Role;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class CustomerRecordsSeeder extends Seeder
{
    private const CUSTOMER_CODE = '58321';

    private const STAFF_USERNAME = 'store.owner';

    /**
     * Seed one customer (58321 - Renesme Moral) with a full ledger:
     * products, debts, debt items, and payments.
     */
    public function run(): void
    {
        $customer = Customer::firstOrCreate(
            ['customer_code' => self::CUSTOMER_CODE],
            ['full_name' => 'Renesme Moral']
        );

        $userId = $this->ensureStaffUser();

        $products = $this->ensureProducts($userId);

        $alreadySeeded = DebtItem::query()
            ->whereHas('debt', fn ($query) => $query->where('customer_id', $customer->customer_id))
            ->exists();

        if ($alreadySeeded) {
            $this->command?->info('Customer '.self::CUSTOMER_CODE.' already has ledger records; skipping.');

            return;
        }

        $this->seedLedger($customer, $userId, $products);

        $this->command?->info('Seeded debts, debt items, and payments for '.$customer->full_name.' ('.self::CUSTOMER_CODE.').');
    }

    /**
     * @param  array<string, Product>  $products
     */
    private function seedLedger(Customer $customer, string $userId, array $products): void
    {
        $debtOneDate = now()->subDays(20);
        $debtOne = $this->createDebt($customer, $userId, $debtOneDate);

        $this->createItem($debtOne, $products['Rice (25kg sack)'], 2, $debtOneDate, 'Delivered to store.');
        $this->createItem($debtOne, $products['Cooking Oil (1L)'], 3, $debtOneDate);
        $this->createItem($debtOne, $products['White Sugar (1kg)'], 5, $debtOneDate);

        $debtTwoDate = now()->subDays(6);
        $debtTwo = $this->createDebt($customer, $userId, $debtTwoDate);

        $this->createItem($debtTwo, $products['Instant Noodles (pack)'], 12, $debtTwoDate);
        $this->createItem($debtTwo, $products['Canned Sardines (155g)'], 6, $debtTwoDate);
        $this->createItem($debtTwo, $products['Laundry Detergent (1kg)'], 2, $debtTwoDate);

        $debtThreeDate = now()->subDays(30);
        $debtThree = $this->createDebt($customer, $userId, $debtThreeDate);
        $this->createItem($debtThree, $products['Rice (25kg sack)'], 1, $debtThreeDate);

        // Payments are attributed to the credit record they settle.
        $this->createPayment($debtOne, '1000.00', now()->subDays(15), $userId);
        $this->createPayment($debtOne, '500.00', now()->subDays(8), $userId);
        $this->createPayment($debtOne, '700.00', now()->subDays(2), $userId);
        $this->createPayment($debtThree, '1250.00', now()->subDays(25), $userId);

        $debtOne->status = DebtStatus::PartiallyPaid;
        $debtOne->save();

        $debtTwo->status = DebtStatus::Unpaid;
        $debtTwo->save();

        $debtThree->status = DebtStatus::Paid;
        $debtThree->save();
    }

    private function createDebt(Customer $customer, string $userId, Carbon $createdAt): Debt
    {
        $debt = new Debt([
            'customer_id' => $customer->customer_id,
            'created_by' => $userId,
        ]);
        $debt->created_at = $createdAt;
        $debt->updated_at = $createdAt;
        $debt->save();

        return $debt;
    }

    private function createItem(Debt $debt, Product $product, int $quantity, Carbon $createdAt, ?string $notes = null): DebtItem
    {
        $item = new DebtItem([
            'debt_id' => $debt->debt_id,
            'product_id' => $product->product_id,
            'quantity' => $quantity,
            'unit_price' => $product->price,
            'subtotal' => bcmul((string) $quantity, (string) $product->price, 2),
            'notes' => $notes,
        ]);
        $item->created_at = $createdAt;
        $item->updated_at = $createdAt;
        $item->save();

        return $item;
    }

    private function createPayment(Debt $debt, string $amount, Carbon $paidAt, string $userId): Payment
    {
        $payment = new Payment([
            'customer_id' => $debt->customer_id,
            'debt_id' => $debt->debt_id,
            'amount_paid' => $amount,
            'payment_date' => $paidAt,
            'received_by' => $userId,
        ]);
        $payment->created_at = $paidAt;
        $payment->updated_at = $paidAt;
        $payment->save();

        return $payment;
    }

    /**
     * debts.created_by, products.created_by, and payments.received_by all
     * reference users, so ensure a staff account exists to attribute records to.
     */
    private function ensureStaffUser(): string
    {
        $existing = DB::table('users')->where('username', self::STAFF_USERNAME)->value('user_id');

        if ($existing) {
            return (string) $existing;
        }

        $roleId = Role::firstOrCreate(
            ['role_name' => 'store_owner'],
            ['description' => 'Store owner who manages products, customers, debts, and payments.']
        )->role_id;

        $userId = (string) Str::uuid();

        DB::table('users')->insert([
            'user_id' => $userId,
            'role_id' => $roleId,
            'full_name' => 'Store Owner',
            'username' => self::STAFF_USERNAME,
            'password' => Hash::make('password'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $userId;
    }

    /**
     * @return array<string, Product>
     */
    private function ensureProducts(string $userId): array
    {
        $catalog = [
            ['Rice (25kg sack)', 'Premium milled rice, 25 kilogram sack.', '1250.00'],
            ['Cooking Oil (1L)', 'Refined cooking oil, 1 liter bottle.', '95.00'],
            ['White Sugar (1kg)', 'Refined white sugar, 1 kilogram pack.', '85.00'],
            ['Instant Noodles (pack)', 'Beef-flavored instant noodles, per pack.', '15.00'],
            ['Canned Sardines (155g)', 'Sardines in tomato sauce, 155 gram can.', '28.00'],
            ['Laundry Detergent (1kg)', 'Powdered laundry detergent, 1 kilogram pack.', '145.00'],
        ];

        $products = [];

        foreach ($catalog as [$name, $description, $price]) {
            $products[$name] = Product::firstOrCreate(
                ['product_name' => $name],
                ['description' => $description, 'price' => $price, 'created_by' => $userId]
            );
        }

        return $products;
    }
}
