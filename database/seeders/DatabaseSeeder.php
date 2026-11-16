<?php
namespace Database\Seeders;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
class DatabaseSeeder extends Seeder {
    public function run(): void {
        User::firstOrCreate(['email'=>'student@example.test'],[
            'name'=>'Facinal','password'=>Hash::make('Lab5Demo!2026'),'email_verified_at'=>now()
        ]);
        $this->call(ProductSeeder::class);
    }
}
