<?php

namespace Database\Seeders;

use App\Models\Desa;
use App\Models\Kecamatan;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class KecamatanDesaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Data kecamatan dan desa (realistic data)
        $data = [
            [
                'kecamatan' => 'Pinogaluman',
                'desa' => [
                    'Biontong',
                    'Biontong I',
                    'Bundu',
                    'Dondomon',
                    'Dondomon Utara',
                    'Inobonto',
                    'Inobonto II',
                    'Lobong',
                    'Lobong I',
                    'Matayangan'
                ]
            ],
            [
                'kecamatan' => 'Bolangitang Barat',
                'desa' => [
                    'Binuang',
                    'Biontong',
                    'Biontong I',
                    'Bondar',
                    'Bondar Sobi',
                    'Bulo',
                    'Bungabung',
                    'Dengi',
                    'Dengi II',
                    'Doodolan'
                ]
            ],
            [
                'kecamatan' => 'Bolangitang Timur',
                'desa' => [
                    'Abak',
                    'Babo',
                    'Bungkudolon',
                    'Bungkudolon Timur',
                    'Dumagin',
                    'Dumagin B',
                    'Kanaan',
                    'Motongkad',
                    'Motongkad Selatan',
                    'Motongkad Utara'
                ]
            ],
            [
                'kecamatan' => 'Kaidipang',
                'desa' => [
                    'Bigongang',
                    'Bira',
                    'Bira I',
                    'Bulo',
                    'Dalam',
                    'Dengi',
                    'Dondomon',
                    'Dondomon I',
                    'Duini',
                    'Duwong'
                ]
            ],
            [
                'kecamatan' => 'Bintauna',
                'desa' => [
                    'Bangka',
                    'Bangka I',
                    'Bangka II',
                    'Bangka III',
                    'Bangka IV',
                    'Bangka V',
                    'Bongkudai',
                    'Bongkudai Barat',
                    'Bongkudai Selatan',
                    'Bongkudai Utara'
                ]
            ],
            [
                'kecamatan' => 'Sangkub',
                'desa' => [
                    'Bongkudai',
                    'Bongkudai Barat',
                    'Bongkudai Selatan',
                    'Bongkudai Utara',
                    'Bongkudai Timur',
                    'Momalia',
                    'Momalia I',
                    'Momalia II',
                    'Mongkoinit',
                    'Mongkoinit Selatan'
                ]
            ]
        ];

        $totalKecamatan = 0;
        $totalDesa = 0;

        foreach ($data as $item) {
            // Create kecamatan
            $kecamatan = Kecamatan::create([
                'namakecamatan' => $item['kecamatan']
            ]);
            $totalKecamatan++;

            // Create desa untuk kecamatan ini
            foreach ($item['desa'] as $namaDesa) {
                Desa::create([
                    'idkecamatan' => $kecamatan->idkecamatan,
                    'namadesa' => $namaDesa
                ]);
                $totalDesa++;
            }
        }

        $this->command->info("Seeder KecamatanDesa berhasil ditambahkan!");
        $this->command->info("Total Kecamatan: {$totalKecamatan}");
        $this->command->info("Total Desa: {$totalDesa}");
    }
}
