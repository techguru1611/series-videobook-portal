<?php

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;

class UsersTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Get super admin role
        $superAdminRole = Role::where('slug', Config::get('constant.SUPER_ADMIN_SLUG'))->first();

        // Add super admin and attach super admin role
        $superAdmin = new User(array_filter([
            'full_name' => 'The Super Admin',
            'username' => 'superadmin',
            'email' => 'superadmin@gmail.com',
            'password' => 'yWPt8Hw9'
        ]));
        $superAdmin->save();
        $superAdmin->roles()->attach($superAdminRole);
    }
}
