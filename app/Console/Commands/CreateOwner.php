<?php

namespace App\Console\Commands;

use App\Models\Role;
use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

#[Signature('owner:create {username? : The owner login username} {--name= : The owner display name} {--password= : The owner password, prompted for when omitted}')]
#[Description('Create a store owner account that can sign in to the owner portal.')]
class CreateOwner extends Command
{
    private const ROLE_NAME = 'store_owner';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $username = (string) $this->argument('username');
        $name = (string) ($this->option('name') ?: $username);
        $password = (string) ($this->option('password') ?: $this->secret('Password'));

        $validator = Validator::make([
            'username' => $username,
            'name' => $name,
            'password' => $password,
        ], [
            'username' => ['required', 'string', 'max:100', 'unique:users,username'],
            'name' => ['required', 'string', 'max:150'],
            'password' => ['required', 'string', Password::min(8)],
        ]);

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->components->error($error);
            }

            return self::FAILURE;
        }

        $owner = User::create([
            'role_id' => $this->storeOwnerRoleId(),
            'full_name' => $name,
            'username' => $username,
            'password' => $password,
        ]);

        $this->components->info("Store owner [{$owner->username}] created.");

        return self::SUCCESS;
    }

    /**
     * Resolve the store owner role, seeding it when the roles table has not
     * been populated yet so the command works on a bare install.
     */
    private function storeOwnerRoleId(): string
    {
        return Role::query()->firstOrCreate(
            ['role_name' => self::ROLE_NAME],
            ['description' => 'Store owner who manages products, customers, debts, and payments.'],
        )->role_id;
    }
}
