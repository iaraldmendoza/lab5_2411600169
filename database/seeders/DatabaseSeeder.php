<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        foreach ([
            ['manager@facinalhardware.test', 'Store Administrator', 'admin', 'Hardware!2026'],
            ['purchasing.manager@facinalhardware.test', 'Purchasing Manager', 'manager', 'Purchasing!2026'],
            ['buyer@facinalhardware.test', 'Purchasing Officer', 'staff', 'Purchasing!2026'],
            ['clerk@facinalhardware.test', 'Warehouse Clerk', 'warehouse', 'Warehouse!2026'],
        ] as [$email, $name, $role, $password]) {
            $user = User::firstOrCreate(['email' => $email], [
                'name' => $name, 'password' => Hash::make($password), 'email_verified_at' => now(),
            ]);
            $user->forceFill(['role' => $role])->save();
        }

        $this->call([ProductSeeder::class, ProcurementSeeder::class]);
    }
}
