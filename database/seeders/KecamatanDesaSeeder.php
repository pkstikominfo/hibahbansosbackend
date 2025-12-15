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

        Desa::truncate();
        Kecamatan::truncate();

        $data = [
            [
                'kecamatan' => 'Bintauna',
                'desa' => [
                    'Batulintik',
                    'Bintauna Pantai',
                    'Bunia',
                    'Bunong',
                    'Huntuk',
                    'Kopi',
                    'Kuhanga',
                    'Minanga',
                    'Mome',
                    'Padang',
                    'Padang Barat',
                    'Pimpi',
                    'Talaga',
                    'Voa A',
                    'Vahuta',
                    'Bintauna' // Kelurahan
                ]
            ],
            [
                'kecamatan' => 'Bolangitang Barat',
                'desa' => [
                    'Bolangitang',
                    'Bolangitang I',
                    'Bolangitang II',
                    'Jambusarang',
                    'Talaga Tomoagu',
                    'Iyok',
                    'Keimanga',
                    'Langi',
                    'Ollot',
                    'Ollot I',
                    'Ollot II',
                    'Paku',
                    'Paku Selatan',
                    'Sonuo',
                    'Talaga',
                    'Tanjung Buaya',
                    'Tote',
                    'Wakat'
                ]
            ],
            [
                'kecamatan' => 'Bolangitang Timur',
                'desa' => [
                    'Binjeita',
                    'Binjeita I',
                    'Binjeita II',
                    'Binuanga',
                    'Binuni',
                    'Biontong',
                    'Biontong I',
                    'Biontong II',
                    'Bohabak I',
                    'Bohabak II',
                    'Bohabak III',
                    'Bohabak IV',
                    'Lipu Bogu',
                    'Mokoditek',
                    'Mokoditek I',
                    'Nagara',
                    'Nunuka',
                    'Saleo',
                    'Saleo Satu',
                    'Tanjung Labou'
                ]
            ],
            [
                'kecamatan' => 'Kaidipang',
                'desa' => [
                    'Bigo',
                    'Bigo Selatan',
                    'Boroko',
                    'Boroko Timur',
                    'Boroko Utara',
                    'Gihang',
                    'Inomunga',
                    'Inomunga Utara',
                    'Komus II',
                    'Komus Dua Timur',
                    'Kuala',
                    'Kuala Utara',
                    'Pontak',
                    'Soligir',
                    'Solo'
                ]
            ],
            [
                'kecamatan' => 'Pinogaluman',
                'desa' => [
                    'Batu Batayo',
                    'Batutajam',
                    'Buko',
                    'Buko Selatan',
                    'Buko Utara',
                    'Busato',
                    'Dalapuli',
                    'Dalapuli Barat',
                    'Dalapuli Timur',
                    'Dengi',
                    'Duini',
                    'Kayuogu',
                    'Komus Satu',
                    'Padango',
                    'Tanjung Sidupa',
                    'Tambulang Pantai',
                    'Tambulang Timur',
                    'Tombulang',
                    'Tontulow',
                    'Tuntung',
                    'Tuntung Timur',
                    'Tuntulow Utara'
                ]
            ],
            [
                'kecamatan' => 'Sangkub',
                'desa' => [
                    'Ampeng Sembeka',
                    'Busisingo',
                    'Busisingo Utara',
                    'Mokusato',
                    'Monompia',
                    'Pangkusa',
                    'Sampiro',
                    'Sangkub I',
                    'Sangkub II',
                    'Sangkub III',
                    'Sangkub IV',
                    'Sangkub Timur',
                    'Sangtombolang',
                    'Sidodadi',
                    'Suka Makmur',
                    'Tombolango'
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
