<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;


class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $datas = [
            [
                'name' => 'User',
                'guard_name' => 'web',
            ],
        ];

        for ($i = 0; $i < count($datas); $i++) {
            $data = $datas[$i];

            if (Role::query()->where('name', $data['name'])->count() == 0)
                Role::create($data);
        }
    }
}
