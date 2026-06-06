<?php

namespace Tests;

use App\Enums\RoleName;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /** Ensure the role catalogue exists for role-aware tests. */
    protected function seedRoles(): void
    {
        $this->seed(RoleSeeder::class);
    }

    /** Create a user, optionally assigning a role (verified + active by default). */
    protected function createUser(?RoleName $role = null, array $attributes = []): User
    {
        $this->seedRoles();

        $user = User::factory()->create(array_merge([
            'email_verified_at' => now(),
            'status' => 'active',
        ], $attributes));

        if ($role !== null) {
            $user->assignRole($role);
            $user->load('roles');
        }

        return $user;
    }
}
