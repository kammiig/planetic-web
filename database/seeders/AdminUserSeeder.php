<?php

namespace Database\Seeders;

use App\Enums\RoleName;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AdminUserSeeder extends Seeder
{
    /**
     * Creates the initial Super Admin. The password is taken from the
     * ADMIN_PASSWORD env var; if unset, a strong random password is generated
     * and printed once so it can be changed immediately. No password is ever
     * hardcoded in the codebase.
     */
    public function run(): void
    {
        $email = config('billing.admin_email', 'admin@planeticweb.com');
        $envPassword = env('ADMIN_PASSWORD');
        $password = blank($envPassword) ? Str::password(16) : $envPassword;

        $user = User::firstOrNew(['email' => $email]);
        $isNew = ! $user->exists;

        $user->fill([
            'name' => $user->name ?: 'Platform Admin',
            'is_admin' => true,
            'status' => 'active',
            'email_verified_at' => $user->email_verified_at ?? now(),
        ]);

        // Set the password for new users, or whenever ADMIN_PASSWORD is provided.
        // Never silently overwrite an existing admin's password on re-seed.
        if ($isNew || ! blank($envPassword)) {
            $user->password = Hash::make($password);
        }

        $user->save();
        $user->assignRole(RoleName::SuperAdmin);

        if ($isNew && blank($envPassword)) {
            $this->command?->warn("Super Admin created: {$email}");
            $this->command?->warn("Generated password (change immediately): {$password}");
        } else {
            $this->command?->info("Super Admin ensured: {$email}");
        }
    }
}
