<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->enum('role', ['admin', 'security', 'staff', 'resident'])->nullable()->change();
        });

        // Retain reporter identities for existing incidents, but permanently retire their logins.
        $accounts = DB::table('users')->where('role', 'resident')->get(['user_id', 'email']);
        $ids = $accounts->pluck('user_id');
        DB::table('users')->whereIn('user_id', $ids)->update([
            'role' => null,
            'is_active' => false,
            'deleted_at' => now(),
            'password' => Hash::make(Str::random(64)),
            'remember_token' => null,
            'temporary_password' => null,
            'requires_password_change' => false,
        ]);
        DB::table('sessions')->whereIn('user_id', $ids)->delete();
        DB::table('password_reset_tokens')->whereIn('email', $accounts->pluck('email'))->delete();
        DB::table('password_reset_requests')->whereIn('user_id', $ids)->delete();
        DB::table('model_has_roles')->where('model_type', User::class)->whereIn('model_id', $ids)->delete();
        DB::table('model_has_permissions')->where('model_type', User::class)->whereIn('model_id', $ids)->delete();

        $roleIds = DB::table('roles')->where('name', 'resident')->pluck('id');
        DB::table('model_has_roles')->whereIn('role_id', $roleIds)->delete();
        DB::table('role_has_permissions')->whereIn('role_id', $roleIds)->delete();
        DB::table('roles')->whereIn('id', $roleIds)->delete();

        Schema::table('users', function (Blueprint $table) {
            $table->enum('role', ['admin', 'security', 'staff'])->nullable()->change();
        });

        foreach (Schema::getIndexes('users') as $index) {
            if (in_array('resident_id', $index['columns'], true)) {
                Schema::table('users', fn (Blueprint $table) => $table->dropIndex($index['name']));
            }
        }
        Schema::table('users', fn (Blueprint $table) => $table->dropColumn('resident_id'));

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->enum('role', ['admin', 'security', 'staff', 'resident'])->nullable()->change();
            $table->unsignedInteger('resident_id')->nullable()->index();
        });
        // Retired credentials and portal access are intentionally not restored.
    }
};
