<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class AdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * Idempotent for deploys (e.g. Docker CMD / Render): key by username so we never
     * insert a second row when an admin already exists with username "admin".
     */
    public function run(): void
    {
        $username = env('ADMIN_USERNAME', 'admin');
        $email = env('ADMIN_EMAIL', 'admin@example.com');

        $user = User::firstOrCreate(
            ['username' => $username],
            [
                'email' => $email,
                'password' => env('ADMIN_PASSWORD', 'password123'),
                'role' => 'admin',
            ]
        );

        if ($user->wasRecentlyCreated || $user->email_verified_at === null) {
            $user->forceFill(['email_verified_at' => now()])->save();
        }

        if ($user->role !== 'admin') {
            $user->forceFill(['role' => 'admin'])->save();
        }
    }
}
