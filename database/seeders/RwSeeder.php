<?php

namespace Database\Seeders;

use App\Models\rw;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class RwSeeder extends Seeder
{
    public function run(): void
    {
        $password = config('app.demo_rw_password');
        if (!is_string($password) || strlen($password) < 12) {
            throw new \RuntimeException('Set DEMO_RW_PASSWORD to at least 12 characters in your local .env file.');
        }

        $rw01 = rw::updateOrCreate([
            'nomor_rw' => '001',
        ], [
            'nama' => 'Demo RW 01',
        ]);

        $rw02 = rw::updateOrCreate([
            'nomor_rw' => '002',
        ], [
            'nama' => 'Demo RW 02',
        ]);

        $user1 = User::updateOrCreate(
            ['username' => 'demo_rw_01'],
            [
                'password' => Hash::make($password),
                'role' => 'rw',
            ]
        );

        $rw01->update(['userid' => $user1->id]);

        $user2 = User::updateOrCreate(
            ['username' => 'demo_rw_02'],
            [
                'password' => Hash::make($password),
                'role' => 'rw',
            ]
        );

        $rw02->update(['userid' => $user2->id]);
    }
}