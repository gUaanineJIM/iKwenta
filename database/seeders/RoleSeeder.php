<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        Role::insert([
            [
                'role_id' => (string) Str::uuid(),
                'role_name' => 'developer',
                'description' => 'System developer with access to system-level functions.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'role_id' => (string) Str::uuid(),
                'role_name' => 'store_owner',
                'description' => 'Store owner who manages products, customers, debts, and payments.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
