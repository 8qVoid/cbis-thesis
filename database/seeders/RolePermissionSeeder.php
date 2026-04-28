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
            'manage inventory',
            'manage blood releases',
            'manage schedules',
            'manage locations',
            'view reports',
            'view public portal',
        ];

        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        $superAdmin = Role::findOrCreate('Super Administrator', 'web');
        $facilitator = Role::findOrCreate('Facilitator', 'web');
        $medicalStaff = Role::findOrCreate('Medical Staff / Nurse', 'web');
        $public = Role::findOrCreate('Public User', 'web');

        $superAdmin->syncPermissions([
            'manage facilities',
            'manage roles',
            'manage locations',
            'view reports',
            'view public portal',
        ]);
        $facilitator->syncPermissions([
            'manage users',
            'manage donors',
            'manage donation records',
            'manage bloodletting records',
            'manage schedules',
            'view public portal',
        ]);
        $medicalStaff->syncPermissions([
            'manage inventory',
            'view reports',
            'view public portal',
        ]);
        $public->syncPermissions(['view public portal']);

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
        $admin->syncRoles([$superAdmin]);
    }
}
