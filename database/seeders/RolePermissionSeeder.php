<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
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

            // benificiaries
            'benificiaries.view',
            'benificiaries.create',
            'benificiaries.update',
            'benificiaries.delete',

            // Inventory
            'inventory.view',
            'inventory.create',
            'inventory.update',
            'inventory.delete',

            // Scheduling / distributions
            'distributions.view',
            'distributions.create',
            'distributions.update',
            'distributions.delete',

            // Follow-ups
          

            // Treatment Details
      

            // Patient-specific
            'profile.view',
            'profile.update',
            'profile.qr_download',
            'distributions.view_own',
            'reports.download',
            'analytics.dashboard',
            'verify.beneficiary',
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




      

        $barangayofficial->syncPermissions([
            'distributions.view',
            'distributions.create',
            'distributions.update',
            'distributions.delete',

             


            'reports.download',
            'verify.beneficiary',
            'approval.stockverifcation'

        
        ]);

        //this guys is an acting admin
        $lgustaff->syncPermissions([
              'users.route',
            'users.view',
            'users.create',
            'users.update',
            'users.delete',


            'distributions.view',
            'distributions.create',
            'distributions.update',
            'distributions.delete',

             'inventory.view',
            'inventory.create',
            'inventory.update',
            'inventory.delete',

            'benificiaries.view',
            'benificiaries.create',
            'benificiaries.update',
            'benificiaries.delete',

            'reports.download',
            'analytics.dashboard'
        
        ]);

    

        $user=User::find(1);

        $user->assignRole($admin);
    }
}