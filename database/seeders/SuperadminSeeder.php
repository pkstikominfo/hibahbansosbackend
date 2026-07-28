<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class SuperadminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Ganti 'admin_nip_disini' dengan NIP/Username yang sebenarnya
        $nipAdmin = '199903262025041004';
        
        $admin = User::where('username', $nipAdmin)->first();
        if (!$admin) {
            User::create([
                'username' => $nipAdmin,
                'password' => Hash::make('password123'), // Sebagai dummy, tidak akan dipakai karena login via SSO
                'name' => 'Superadmin Utama',
                'email' => 'admin.utama@example.com',
                'nohp' => '081234567890',
                'peran' => 'admin',
                'status' => 'active',
            ]);
            $this->command->info("Superadmin dengan NIP/Username {$nipAdmin} berhasil dibuat!");
        } else {
            $this->command->info("Superadmin dengan NIP/Username {$nipAdmin} sudah ada.");
        }
    }
}
