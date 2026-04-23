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

        $centralAdmin = Role::findOrCreate('Central Administrator', 'web');
        $facilityAdmin = Role::findOrCreate('Facility Admin / Blood Bank Personnel', 'web');
        $public = Role::findOrCreate('Public User', 'web');

        $centralAdmin->syncPermissions($permissions);
        $facilityAdmin->syncPermissions([
            'manage donors',
            'manage donation records',
            'manage bloodletting records',
            'manage inventory',
            'manage blood releases',
            'manage schedules',
            'view reports',
            'view public portal',
        ]);
        $public->syncPermissions(['view public portal']);

        User::role('Medical Technologist')->get()->each(function (User $user) use ($facilityAdmin): void {
            $user->syncRoles([$facilityAdmin]);
        });

        Role::query()->where('name', 'Medical Technologist')->delete();

        $admin = User::firstOrCreate(
            ['email' => 'admin@cbis.local'],
            [
                'name' => 'Philippine Red Cross Central Admin',
                'password' => Hash::make('password'),
                'is_active' => true,
            ]
        );

        $admin->syncRoles([$centralAdmin]);
    }
}
