<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        // `roles.role_name` is unique, so a plain insert would abort the whole
        // seed the second time it ran. Keying on `role_name` keeps the seeder
        // idempotent and lets the description be corrected in place.
        foreach ([
            'developer' => 'System developer with access to system-level functions.',
            'store_owner' => 'Store owner who manages products, customers, debts, and payments.',
        ] as $roleName => $description) {
            Role::query()->updateOrCreate(
                ['role_name' => $roleName],
                ['description' => $description],
            );
        }
    }
}
