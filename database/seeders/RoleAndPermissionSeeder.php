<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RoleAndPermissionSeeder extends Seeder
{
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // 1. Create permissions
        $permissions = [
            'pos.access',
            'orders.create',
            'orders.view',
            'orders.discount.apply',
            'orders.pay',
            'orders.open_bill',
            'orders.void.request',
            'orders.void.approve',
            'orders.delete',
            'production.bar',
            'production.kitchen',
            'menu.view',
            'menu.manage',
            'menu.toggle_availability',
            'discounts.view',
            'discounts.manage',
            'inventory.view',
            'inventory.update',
            'inventory.opname',
            'expenses.manage',
            'shifts.manage',
            'schedule.view',
            'schedule.manage',
            'attendance.self',
            'attendance.manage',
            'reports.view',
            'users.manage',
            'partners.manage',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        // 2. Create roles and assign permissions
        $roleManager = Role::firstOrCreate(['name' => 'manager', 'guard_name' => 'web']);
        $roleBumk = Role::firstOrCreate(['name' => 'bumk_staff', 'guard_name' => 'web']);
        $roleCashier = Role::firstOrCreate(['name' => 'cashier', 'guard_name' => 'web']);
        $roleBarista = Role::firstOrCreate(['name' => 'barista', 'guard_name' => 'web']);
        $roleKitchen = Role::firstOrCreate(['name' => 'kitchen', 'guard_name' => 'web']);
        $roleHelper = Role::firstOrCreate(['name' => 'helper', 'guard_name' => 'web']);

        // Manager and BUMK have all permissions
        $roleManager->syncPermissions(Permission::all());
        $roleBumk->syncPermissions(Permission::all());

        // Cashier permissions
        $roleCashier->syncPermissions([
            'pos.access',
            'orders.create',
            'orders.view',
            'orders.discount.apply',
            'orders.pay',
            'orders.open_bill',
            'orders.void.request',
            'menu.view',
            'menu.toggle_availability',
            'discounts.view',
            'inventory.view',
            'inventory.update',
            'expenses.manage',
            'shifts.manage',
            'schedule.view',
            'attendance.self',
            'reports.view',
        ]);

        // Barista permissions (KF-13/14: diizinkan input kasir per konfirmasi pengelola Bagian 13)
        $roleBarista->syncPermissions([
            'pos.access',
            'orders.create',
            'orders.pay',
            'orders.open_bill',
            'orders.discount.apply',
            'orders.void.request',
            'production.bar',
            'orders.view',
            'menu.view',
            'menu.toggle_availability',
            'inventory.view',
            'inventory.update',
            'schedule.view',
            'attendance.self',
        ]);

        // Kitchen permissions
        $roleKitchen->syncPermissions([
            'production.kitchen',
            'orders.view',
            'menu.view',
            'menu.toggle_availability',
            'inventory.view',
            'inventory.update',
            'schedule.view',
            'attendance.self',
        ]);

        // Helper permissions
        $roleHelper->syncPermissions([
            'inventory.view',
            'attendance.self',
        ]);

        // 3. Create default users with hashed PIN
        $defaultUsers = [
            [
                'name' => 'Manajer UMCO',
                'username' => 'manager',
                'email' => 'manager@umco.test',
                'role' => 'manager',
                'pin' => '123456',
            ],
            [
                'name' => 'Staff BUMK',
                'username' => 'bumk',
                'email' => 'bumk@umco.test',
                'role' => 'bumk_staff',
                'pin' => '123456',
            ],
            [
                'name' => 'Kasir Shift 1',
                'username' => 'kasir',
                'email' => 'kasir@umco.test',
                'role' => 'cashier',
                'pin' => '111111',
            ],
            [
                'name' => 'Barista Utama',
                'username' => 'barista',
                'email' => 'barista@umco.test',
                'role' => 'barista',
                'pin' => '222222',
            ],
            [
                'name' => 'Tim Dapur UMC',
                'username' => 'kitchen',
                'email' => 'kitchen@umco.test',
                'role' => 'kitchen',
                'pin' => '333333',
            ],
            [
                'name' => 'Helper Logistik',
                'username' => 'helper',
                'email' => 'helper@umco.test',
                'role' => 'helper',
                'pin' => '444444',
            ],
        ];

        foreach ($defaultUsers as $userData) {
            $user = User::updateOrCreate(
                ['email' => $userData['email']],
                [
                    'name' => $userData['name'],
                    'username' => $userData['username'],
                    'password' => Hash::make('password'),
                    'pin_hash' => Hash::make($userData['pin']),
                    'is_active' => true,
                ]
            );

            $user->syncRoles([$userData['role']]);
        }
    }
}
