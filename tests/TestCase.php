<?php

namespace Tests;

use App\Enums\RoleName;
use App\Models\User;
use App\Support\Region;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Region::current() memoises the request's storefront in a static. A
        // test that visits an /int/… URL would otherwise leak USD into every
        // test that ran after it, so the suite would pass or fail on ordering
        // alone — and a GBP-only price would resolve to null out of nowhere.
        Region::flush();
    }

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
