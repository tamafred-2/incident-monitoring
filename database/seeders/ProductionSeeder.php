<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Production-safe seeder for a fresh (PostgreSQL) deployment.
 *
 * Unlike SqliteDemoSeeder this is idempotent and non-destructive: it never
 * truncates tables, uses no SQLite-only SQL, and never overwrites an existing
 * admin's password. Run it once after the first deploy:
 *
 *     php artisan db:seed --class=ProductionSeeder --force
 *
 * The admin credentials come from environment variables so nothing is
 * hardcoded in the repo:
 *
 *     ADMIN_EMAIL, ADMIN_PASSWORD  (optionally ADMIN_SURNAME, ADMIN_FIRST_NAME)
 *
 * If ADMIN_PASSWORD is omitted, a strong password is generated, printed once,
 * and the account is flagged to force a password change on first login.
 */
class ProductionSeeder extends Seeder
{
    public function run(): void
    {
        // Roles/permissions must exist for spatie/laravel-permission.
        $this->call(RolesAndPermissionsSeeder::class);

        $email = trim((string) env('ADMIN_EMAIL'));

        if ($email === '') {
            $this->command?->warn(
                'ADMIN_EMAIL is not set — skipping admin creation. '
                . 'Set ADMIN_EMAIL (and optionally ADMIN_PASSWORD) as Railway variables and re-run.'
            );

            return;
        }

        // Never clobber an existing account's password on a re-run.
        $existing = User::withTrashed()->where('email', $email)->first();

        if ($existing) {
            $existing->forceFill([
                'role' => UserRole::Admin->value,
                'subdivision_id' => null,
                'is_active' => true,
            ])->save();

            if ($existing->trashed()) {
                $existing->restore();
            }

            $this->command?->info("Admin {$email} already exists — ensured admin role/active, left password unchanged.");

            return;
        }

        $password = (string) env('ADMIN_PASSWORD');
        $generated = $password === '';

        if ($generated) {
            $password = Str::password(16);
        }

        User::create([
            'surname' => (string) env('ADMIN_SURNAME', 'Administrator'),
            'first_name' => (string) env('ADMIN_FIRST_NAME', 'System'),
            'middle_name' => null,
            'extension' => null,
            'email' => $email,
            'password' => $password, // hashed by the model's 'hashed' cast
            'role' => UserRole::Admin->value,
            'subdivision_id' => null,
            'is_active' => true,
            'requires_password_change' => $generated,
        ]);

        $this->command?->info("Admin account created: {$email}");

        if ($generated) {
            $this->command?->warn("Generated temporary password (shown once): {$password}");
            $this->command?->warn('You will be required to change it on first login.');
        }
    }
}
