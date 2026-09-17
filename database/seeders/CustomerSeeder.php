<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CustomerSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('customers')->insert([
            [
                'customer_id' => (string) Str::uuid(),
                'customer_code' => '58321',
                'full_name' => 'Renesme Moral',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'customer_id' => (string) Str::uuid(),
                'customer_code' => '74106',
                'full_name' => 'Axel Moral',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'customer_id' => (string) Str::uuid(),
                'customer_code' => '92645',
                'full_name' => 'Peter Moral',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
