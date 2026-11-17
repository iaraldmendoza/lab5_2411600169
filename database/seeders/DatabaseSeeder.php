<?php
namespace Database\Seeders;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
class DatabaseSeeder extends Seeder {
 public function run():void{
  $u=User::firstOrCreate(['email'=>'manager@facinalhardware.test'],['name'=>'Inventory Manager','password'=>Hash::make('Hardware!2026'),'email_verified_at'=>now()]);
  $u->forceFill(['role'=>'admin'])->save();
  User::firstOrCreate(['email'=>'clerk@facinalhardware.test'],['name'=>'Warehouse Clerk','password'=>Hash::make('Warehouse!2026'),'email_verified_at'=>now()]);
  $this->call(ProductSeeder::class);
 }
}
