<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoUsersSeeder extends Seeder
{
    public function run(): void
    {
        // Demo accounts ONLY. Password "1234" must not be used on a public site.
        $accounts = [
            ['user_id' => 'STU-202300666', 'name' => 'Raheem', 'email' => '202300666@zu.edu.jo', 'role' => 'student'],
            ['user_id' => 'INS-HEBATULLAH', 'name' => 'Hebatullah', 'email' => 'hebatullah@zu.edu.jo', 'role' => 'instructor'],
            ['user_id' => 'ADM-001', 'name' => 'Admin', 'email' => 'admin@zu.edu.jo', 'role' => 'manager'],
        ];

        foreach ($accounts as $account) {
            User::updateOrCreate(
                ['email' => $account['email']],
                [
                    'user_id' => $account['user_id'],
                    'name' => $account['name'],
                    'role' => $account['role'],
                    'password' => Hash::make('1234'),
                ]
            );
        }
    }
}
