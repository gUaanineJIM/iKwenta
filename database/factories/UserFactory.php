<?php

namespace Database\Factories;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * The role every user is attached to unless a state says otherwise.
     */
    protected ?string $roleName = 'developer';

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => (string) Str::uuid(),
            'role_id' => $this->roleFor($this->roleName),
            'full_name' => fake()->name(),
            'username' => fake()->unique()->userName(),
            'password' => static::$password ??= Hash::make('password'),
        ];
    }

    /**
     * A user holding the `store_owner` role, which is the only role the
     * owner portal middleware accepts.
     */
    public function storeOwner(): static
    {
        return $this->state(fn (array $attributes): array => [
            'role_id' => $this->roleFor('store_owner'),
        ]);
    }

    /**
     * Resolve a role id by name, creating the role when it is missing so a
     * test never has to seed roles just to build a user.
     */
    private function roleFor(string $roleName): string
    {
        return Role::query()->firstOrCreate(
            ['role_name' => $roleName],
            ['description' => ucfirst(str_replace('_', ' ', $roleName)).' role'],
        )->role_id;
    }
}
