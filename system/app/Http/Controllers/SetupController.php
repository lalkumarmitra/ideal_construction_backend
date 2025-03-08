<?php

namespace App\Http\Controllers;


class SetupController extends Controller
{
    public function setup() {
        $this->createRoles();
        $this->createAdmin();
    }

    private function createRoles() {
        $roles = [
            ['name' => 'Admin', 'type' => 'admin', 'priority' => 1],
            ['name' => 'Manager', 'type' => 'manager', 'priority' => 2],
            ['name' => 'Staff', 'type' => 'staff', 'priority' => 3],
            ['name' => 'Driver', 'type' => 'driver', 'priority' => 4],
        ];
        foreach($roles as $role) {
            \App\Models\Role::create($role);
        }
    }

    private function createAdmin() {
        $admin = [
            'name' => 'Pintu Rabindra',
            'email' => 'admin@admin.com',
            'gender'=>'male',
            'dob'=>'1990-01-01',
            'phone'=>'7858856423',
            'password'=>'123456',
            'avatar'=>'',
            'role_id'=>1,
            'is_active'=>true,
            'is_blocked'=>false,
        ];
        \App\Models\User::create($admin);
    }
}
