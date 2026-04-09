<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class PlatformAdminSeeder extends Seeder
{
    public function run(): void
    {
        $this->ensurePlatformScopeIsSupported();

        $platformRole = Role::updateOrCreate(
            ['code' => 'platform_admin'],
            [
                'label' => 'Administrateur Plateforme',
                'description' => 'Accès global à toute la plateforme. Peut gérer toutes les institutions, annexes, utilisateurs et statistiques.',
                'scope' => 'platform',
            ]
        );

        $allPermissionIds = Permission::query()->pluck('id')->toArray();
        if (! empty($allPermissionIds)) {
            $platformRole->permissions()->sync($allPermissionIds);
        }

        $platformUser = User::updateOrCreate(
            ['email' => 'platform@splitpay.test'],
            [
                'annexe_id' => null,
                'name' => 'SplitPay Platform Admin',
                'password' => Hash::make('password'),
                'phone' => '+229 90 00 00 00',
                'is_active' => true,
                'scope' => 'platform',
            ]
        );

        if (! $platformUser->hasRole('platform_admin')) {
            // Le rôle est stocké via la relation roles/user_annexes uniquement pour les utilisateurs tenant.
            // Le super admin plateforme est reconnu via son scope + rôle global côté auth.
        }

        $this->command->info('Compte plateforme prêt : platform@splitpay.test / password');
    }

    private function ensurePlatformScopeIsSupported(): void
    {
        $driver = DB::getDriverName();

        if (! in_array($driver, ['mysql', 'mariadb'], true)) {
            return;
        }

        DB::statement("ALTER TABLE users MODIFY scope ENUM('platform', 'institution', 'annexe') NOT NULL DEFAULT 'annexe'");
        DB::statement("ALTER TABLE roles MODIFY scope ENUM('platform', 'institution', 'annexe') NOT NULL");
    }
}
