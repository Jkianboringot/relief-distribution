<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        // Clear Spatie's cache so re-running the seeder always takes effect.
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        /*
        |--------------------------------------------------------------------------
        | Permissions
        |--------------------------------------------------------------------------
        */

        $permissions = [
            // Users
            'users.route',
            'users.view',
            'users.create',
            'users.update',
            'users.delete',

            // Barangays
            'barangays.view',
            'barangays.create',
            'barangays.update',
            'barangays.delete',

            // Beneficiaries
            'beneficiaries.view',
            'beneficiaries.create',
            'beneficiaries.update',
            'beneficiaries.delete',

            // Inventory (relief packs + pack receipts)
            'inventory.view',
            'inventory.create',
            'inventory.update',
            'inventory.delete',

            // Scheduling / distributions
            'distributions.view',
            'distributions.create',
            'distributions.update',
            'distributions.delete',
            'distributions.status',   // change schedule status (BRGY can, but only to "ongoing" -> controller)

            // Reports / dashboard
            'reports.view',       // view reports (BRGY: own barangay only -> controller)
            'reports.download',   // export CSV / print (ADMIN only)
            'analytics.dashboard',

            // Scan / claim
            'verify.Benificiary',
            'approval.claim',

            // Not assigned to any role yet
            'profile.view',
            'profile.update',
            'profile.qr_download',
            'distributions.view_own',

            'verify.allocation'
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate([
                'name' => $permission,
                'guard_name' => 'web',
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | Roles
        |--------------------------------------------------------------------------
        */

        $lgustaff = Role::firstOrCreate([
            'name' => 'lgustaff',
            'guard_name' => 'web',
        ]);

        $barangayofficial = Role::firstOrCreate([
            'name' => 'barangayofficial',
            'guard_name' => 'web',
        ]);

        // BRGY: view/create schedules, change schedule status (ongoing only), view
        //       beneficiaries + QR, scan/verify + claim, limited dashboard, view reports.
        // CANNOT: users, barangays, inventory, edit/delete schedules, allocations,
        //         delete transactions, export reports.
        // (Own-barangay filtering must be done in the controllers.)
        $barangayofficial->syncPermissions([
            'distributions.view',
            'distributions.status',
            'beneficiaries.view',
            'analytics.dashboard',
            'reports.view',
            'verify.Benificiary',
            'approval.claim',
        ]);

        // this guys is an acting admin
        $lgustaff->syncPermissions([
            'users.route',
            'users.view',
            'users.create',
            'users.update',
            'users.delete',

            'barangays.view',
            'barangays.create',
            'barangays.update',
            'barangays.delete',

            'beneficiaries.view',
            'beneficiaries.create',
            'beneficiaries.update',
            'beneficiaries.delete',

            'inventory.view',
            'inventory.create',
            'inventory.update',
            'inventory.delete',

            'distributions.view',
            'distributions.create',
            'distributions.update',
            'distributions.delete',
            'distributions.status',

            'reports.view',
            'reports.download',
            'analytics.dashboard',

            'verify.allocation',
            'verify.Benificiary',


        ]);

        // user 1 = LGU/MSWDO admin
        User::find(1)?->assignRole($lgustaff);

        // user 2 = barangay official
        User::find(2)?->assignRole($barangayofficial);
    }
}