<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database. Safe to run repeatedly: every seeder
     * uses updateOrCreate / firstOrNew so re-seeding does not duplicate data.
     */
    public function run(): void
    {
        $this->call([
            RoleSeeder::class,
            ProductSeeder::class,
            HostingPackageSeeder::class,
            AdminUserSeeder::class,
        ]);
    }
}
