<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Opd;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdditionalUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Ambil semua OPD yang ada
        $opds = Opd::all();
        // Data user OPD (8 user)

        $adminUsers = [
            [
                'username' => 'admin_super',
                'name' => 'Administrator Utama',
                'email' => 'admin@example.com',
                'nohp' => '081111111111',
                'peran' => 'admin',
                'kode_opd' => null
            ]
        ];


        $opdUsers = [
            [
                'username' => 'opd_dinkes',
                'name' => 'Dr. Siti Aisyah, M.Kes',
                'email' => 'dinkes@example.com',
                'nohp' => '081234561001',
                'peran' => 'opd',
                'kode_opd' => 'OPD001'
            ],
            [
                'username' => 'opd_diknas',
                'name' => 'Drs. Bambang Sutrisno, M.Pd',
                'email' => 'diknas@example.com',
                'nohp' => '081234561002',
                'peran' => 'opd',
                'kode_opd' => 'OPD002'
            ],
            [
                'username' => 'opd_sosial',
                'name' => 'Diana Sari, S.Sos',
                'email' => 'dinsos@example.com',
                'nohp' => '081234561003',
                'peran' => 'opd',
                'kode_opd' => 'OPD003'
            ],
            [
                'username' => 'opd_pu',
                'name' => 'Ir. Joko Widodo, MT',
                'email' => 'dinaspu@example.com',
                'nohp' => '081234561004',
                'peran' => 'opd',
                'kode_opd' => 'OPD004'
            ],
            [
                'username' => 'opd_dagang',
                'name' => 'Maya Sari, SE',
                'email' => 'dinasdagang@example.com',
                'nohp' => '081234561005',
                'peran' => 'opd',
                'kode_opd' => 'OPD005'
            ],
            [
                'username' => 'opd_pertanian',
                'name' => 'Drs. Ahmad Yani',
                'email' => 'dinaspertanian@example.com',
                'nohp' => '081234561006',
                'peran' => 'opd',
                'kode_opd' => 'OPD006'
            ],
            [
                'username' => 'opd_perikanan',
                'name' => 'Surya Dharma, S.Pi',
                'email' => 'dinasperikanan@example.com',
                'nohp' => '081234561007',
                'peran' => 'opd',
                'kode_opd' => 'OPD007'
            ],
            [
                'username' => 'opd_bappeda',
                'name' => 'Dr. Rina Marlina, M.Si',
                'email' => 'bappeda@example.com',
                'nohp' => '081234561008',
                'peran' => 'opd',
                'kode_opd' => 'OPD008'
            ]
        ];

        // Data user Pengusul (7 user)
        $pengusulUsers = [
            [
                'username' => 'pengusul1',
                'name' => 'Budi Santoso',
                'email' => 'budi.santoso@example.com',
                'nohp' => '081234562001',
                'peran' => 'pengusul',
                'kode_opd' => null
            ],
            [
                'username' => 'pengusul2',
                'name' => 'Siti Rahayu',
                'email' => 'siti.rahayu@example.com',
                'nohp' => '081234562002',
                'peran' => 'pengusul',
                'kode_opd' => null
            ],
            [
                'username' => 'pengusul3',
                'name' => 'Ahmad Fauzi',
                'email' => 'ahmad.fauzi@example.com',
                'nohp' => '081234562003',
                'peran' => 'pengusul',
                'kode_opd' => null
            ],
            [
                'username' => 'pengusul4',
                'name' => 'Maya Sari',
                'email' => 'maya.sari@example.com',
                'nohp' => '081234562004',
                'peran' => 'pengusul',
                'kode_opd' => null
            ],
            [
                'username' => 'pengusul5',
                'name' => 'Rizki Pratama',
                'email' => 'rizki.pratama@example.com',
                'nohp' => '081234562005',
                'peran' => 'pengusul',
                'kode_opd' => null
            ],
            [
                'username' => 'pengusul6',
                'name' => 'Dewi Anggraini',
                'email' => 'dewi.anggraini@example.com',
                'nohp' => '081234562006',
                'peran' => 'pengusul',
                'kode_opd' => null
            ],
            [
                'username' => 'pengusul7',
                'name' => 'Joko Susilo',
                'email' => 'joko.susilo@example.com',
                'nohp' => '081234562007',
                'peran' => 'pengusul',
                'kode_opd' => null
            ]
        ];

        $totalCreated = 0;

        // Gabungkan semua user ke dalam satu array untuk looping yang lebih bersih
        $allUsers = array_merge($adminUsers, $opdUsers, $pengusulUsers);

        foreach ($allUsers as $userData) {
            // Cek apakah user sudah ada berdasarkan username
            $existingUser = User::where('username', $userData['username'])->first();

            if (!$existingUser) {
                User::create(array_merge($userData, [
                    'password' => Hash::make('password123'),
                    'status' => 'active'
                ]));
                $totalCreated++;
                $this->command->info("Created {$userData['peran']} user: {$userData['username']}");
            } else {
                $this->command->warn("User {$userData['username']} already exists, skipping...");
            }
        }

        $this->command->info("======= ADDITIONAL USER SEEDER COMPLETED =======");
        $this->command->info("Total new users created: {$totalCreated}");
        $this->command->info("Admin Users: " . count($adminUsers));
        $this->command->info("OPD Users: " . count($opdUsers));
        $this->command->info("Pengusul Users: " . count($pengusulUsers));
        $this->command->info("================================================");
    }
}
