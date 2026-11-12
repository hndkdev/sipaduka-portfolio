<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        $password = config('app.demo_admin_password');
        if (!is_string($password) || strlen($password) < 12) {
            throw new \RuntimeException('Set DEMO_ADMIN_PASSWORD to at least 12 characters in your local .env file.');
        }

        User::updateOrCreate(
            ['username' => 'demo_admin'],
            [
                'password' => Hash::make($password),
                'role' => 'admin',
            ]
        );
    }
}
