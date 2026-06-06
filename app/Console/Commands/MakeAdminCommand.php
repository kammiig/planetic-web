<?php

namespace App\Console\Commands;

use App\Enums\RoleName;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Illuminate\Support\Facades\Validator;

use function Laravel\Prompts\password as promptPassword;
use function Laravel\Prompts\select;
use function Laravel\Prompts\text;

class MakeAdminCommand extends Command
{
    protected $signature = 'planetic:make-admin
        {--email= : Email address for the admin user}
        {--name= : Display name}
        {--role=super_admin : Role to assign}
        {--password= : Password (omit to be prompted or auto-generated)}';

    protected $description = 'Create or promote a Planetic Web staff/admin user with a given role.';

    public function handle(): int
    {
        $email = $this->option('email') ?: text('Email address', required: true);
        $name = $this->option('name') ?: text('Display name', default: 'Platform Admin');

        $roleValue = $this->option('role');
        $validRoles = array_map(fn (RoleName $r) => $r->value, RoleName::staffRoles());

        if (! in_array($roleValue, $validRoles, true)) {
            $roleValue = select('Role', array_combine($validRoles, array_map(
                fn (string $v) => RoleName::from($v)->label(),
                $validRoles,
            )), 'super_admin');
        }

        $password = $this->option('password');
        $generated = false;
        if (blank($password)) {
            if ($this->input->isInteractive()) {
                $password = promptPassword('Password (leave blank to auto-generate)');
            }
            if (blank($password)) {
                $password = Str::password(16);
                $generated = true;
            }
        }

        $validator = Validator::make(
            ['email' => $email, 'password' => $password],
            ['email' => 'required|email', 'password' => ['required', Password::min(10)->mixedCase()->numbers()->symbols()]],
        );

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }

            return self::FAILURE;
        }

        $user = User::firstOrNew(['email' => $email]);
        $user->fill([
            'name' => $name,
            'is_admin' => true,
            'status' => 'active',
            'password' => Hash::make($password),
            'email_verified_at' => $user->email_verified_at ?? now(),
        ])->save();

        $user->assignRole(RoleName::from($roleValue));

        $this->info("✔ {$user->email} is now a ".RoleName::from($roleValue)->label().'.');
        if ($generated) {
            $this->warn("Generated password (change immediately): {$password}");
        }

        return self::SUCCESS;
    }
}
