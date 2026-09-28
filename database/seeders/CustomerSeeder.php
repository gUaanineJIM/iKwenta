<?php

namespace Database\Seeders;

use App\Models\Customer;
use Illuminate\Database\Seeder;

class CustomerSeeder extends Seeder
{
    public function run(): void
    {
        // `customers.customer_code` is unique, so a plain insert aborts the
        // whole seed the second time it runs. Keying on the code keeps the
        // seeder idempotent without disturbing the demo customer ids.
        foreach ([
            '58321' => 'Renesme Moral',
            '74106' => 'Axel Moral',
            '92645' => 'Peter Moral',
        ] as $code => $fullName) {
            Customer::query()->firstOrCreate(
                ['customer_code' => $code],
                ['full_name' => $fullName],
            );
        }
    }
}
