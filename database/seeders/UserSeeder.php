<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $roles = [
            'user',
        ];
        $roleName = [
            'user' => 'User',
        ];

        $users = [];
        foreach ($roles as $role) {
            $roleId = Role::query()->where('name', $roleName[$role])->first(['id'])->id;
            for ($i = 0; $i < 10; ++$i) {
                $name = $role . ($i > 0 ? $i : '');
                $users[] = [
                    'name' => $name,
                    'id' => (string)Str::orderedUuid(),
                    'email' => "$name@change.me",
                    'password' => bcrypt('password'),
                    'roles' => [$roleId,],
                ];
            }
        }

        for ($i = 0; $i < count($users); $i++) {
            $data = $users[$i];
            $inst = User::where('email', $data['email'])->first();

            if (!$inst) {
                $roles = $data['roles'];
                unset($data['roles']);

                $user = User::create($data);
                $user->syncRoles($roles);

            } else if (config('app.env') != 'production') {
                unset($data['id']);

                $roles = $data['roles'];
                unset($data['roles']);

                $inst->update($data);
                $inst->syncRoles($roles);
            }
        }

    }
}
