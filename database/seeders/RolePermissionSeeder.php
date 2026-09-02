<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permissions = [
            'manage facilities',
            'manage users',
            'manage roles',
            'manage donors',
            'manage donation records',
            'manage bloodletting records',
            'view inventory',
            'manage inventory',
            'view blood releases',
            'manage blood releases',
            'manage schedules',
            'manage locations',
            'view reports',
            'view public portal',
            'review activities',
            'process reservations',
            'monitor reservations',
            'export reports',
            'request summaries',
            'view limited donors',
            'view detailed donors',
        ];

        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        $superAdmin = Role::findOrCreate('Super Administrator', 'web');
        $facilitator = Role::findOrCreate('Facilitator', 'web');
        $medicalStaff = Role::findOrCreate('Medical Staff / Nurse', 'web');
        $qao = Role::findOrCreate('Quality Assurance Officer', 'web');
        $bloodBankStaff = Role::findOrCreate('Blood Bank Staff', 'web');
        $eventFacilitator = Role::findOrCreate('Event Facilitator', 'web');
        $donorRole = Role::findOrCreate('Donor', 'web');
        $patientRole = Role::findOrCreate('Patient', 'web');

        $qao->syncPermissions(['manage facilities', 'manage users', 'manage roles', 'view inventory', 'view blood releases', 'view reports', 'export reports', 'review activities', 'monitor reservations', 'view limited donors', 'manage locations', 'view public portal']);
        $bloodBankStaff->syncPermissions(['manage donors', 'view detailed donors', 'manage donation records', 'manage bloodletting records', 'view inventory', 'manage inventory', 'view blood releases', 'manage blood releases', 'process reservations', 'request summaries', 'view public portal']);
        $eventFacilitator->syncPermissions(['manage schedules', 'view public portal']);
        $donorRole->syncPermissions(['view public portal']);
        $patientRole->syncPermissions(['view public portal']);

        $superAdmin->syncPermissions([
            'manage facilities',
            'manage users',
            'manage roles',
            'view public portal',
        ]);
        $facilitator->syncPermissions([
            'manage users',
            'manage donors',
            'manage donation records',
            'manage bloodletting records',
            'manage schedules',
            'manage locations',
            'view inventory',
            'view blood releases',
            'view public portal',
        ]);
        $medicalStaff->syncPermissions([
            'manage inventory',
            'manage blood releases',
            'view reports',
            'view public portal',
        ]);
        // Convert accounts created under the original prototype role names.
        User::role('Super Administrator')->get()->each(fn (User $user) => $user->syncRoles([$qao]));
        User::role('Facilitator')->get()->each(fn (User $user) => $user->syncRoles([$eventFacilitator]));
        User::role('Medical Staff / Nurse')->get()->each(fn (User $user) => $user->syncRoles([$bloodBankStaff]));

        if (Role::query()->where('name', 'Central Administrator')->exists()) {
            User::role('Central Administrator')->get()->each(function (User $user) use ($superAdmin): void {
                $user->syncRoles([$superAdmin]);
            });

            Role::query()->where('name', 'Central Administrator')->delete();
        }

        if (Role::query()->where('name', 'Facility Admin / Blood Bank Personnel')->exists()) {
            User::role('Facility Admin / Blood Bank Personnel')->get()->each(function (User $user) use ($facilitator): void {
                $user->syncRoles([$facilitator]);
            });

            Role::query()->where('name', 'Facility Admin / Blood Bank Personnel')->delete();
        }

        if (Role::query()->where('name', 'Medical Technologist')->exists()) {
            User::role('Medical Technologist')->get()->each(function (User $user) use ($medicalStaff): void {
                $user->syncRoles([$medicalStaff]);
            });

            Role::query()->where('name', 'Medical Technologist')->delete();
        }

        $admin = User::firstOrCreate(
            ['email' => 'admin@cbis.local'],
            [
                'name' => 'Philippine Red Cross Super Administrator',
                'password' => Hash::make('password'),
                'is_active' => true,
            ]
        );

        $admin->forceFill([
            'name' => 'Philippine Red Cross Super Administrator',
            'is_active' => true,
        ])->save();
        $admin->syncRoles([$qao]);
    }
}
