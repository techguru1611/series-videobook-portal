<?php

use Illuminate\Database\Seeder;
use Carbon\Carbon;

class RoleTableSeeder extends Seeder {

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run() {
        try {
            \DB::beginTransaction();
            \DB::statement('SET FOREIGN_KEY_CHECKS=0');
            \DB::table('role')->truncate();

            \DB::table('role')->insert([
                0 => [
                    'name' => 'The Super Admin',
                    'slug' => 'superadmin',
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now()
                ],
                1 => [
                    'name' => 'An App User',
                    'slug' => 'appuser',
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now()
                ]
            ]);
            \DB::commit();
            \DB::statement('SET FOREIGN_KEY_CHECKS=1');
        } catch (Exception $e) {
            \DB::rollback();
        }
    }

}
