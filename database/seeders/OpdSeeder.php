<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\Opd;

class OpdSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Hapus data lama jika ada
        DB::table('opd')->truncate();

        $opdData = [
            ['kode_opd' => 'OPD001', 'nama_opd' => 'Dinas Pendidikan'],
            ['kode_opd' => 'OPD002', 'nama_opd' => 'Dinas Kesehatan'],
            ['kode_opd' => 'OPD003', 'nama_opd' => 'Dinas Pekerjaan Umum dan Penataan Ruang'],
            ['kode_opd' => 'OPD004', 'nama_opd' => 'Dinas Perumahan Rakyat dan Kawasan Permukiman'],
            ['kode_opd' => 'OPD005', 'nama_opd' => 'Dinas Sosial'],
            ['kode_opd' => 'OPD006', 'nama_opd' => 'Dinas Tenaga Kerja'],
            ['kode_opd' => 'OPD007', 'nama_opd' => 'Dinas Pemberdayaan Perempuan dan Perlindungan Anak'],
            ['kode_opd' => 'OPD008', 'nama_opd' => 'Dinas Pangan'],
            ['kode_opd' => 'OPD009', 'nama_opd' => 'Dinas Lingkungan Hidup'],
            ['kode_opd' => 'OPD010', 'nama_opd' => 'Dinas Kependudukan dan Pencatatan Sipil'],
            ['kode_opd' => 'OPD011', 'nama_opd' => 'Dinas Pemberdayaan Masyarakat dan Desa'],
            ['kode_opd' => 'OPD012', 'nama_opd' => 'Dinas Perhubungan'],
            ['kode_opd' => 'OPD013', 'nama_opd' => 'Dinas Komunikasi dan Informatika'],
            ['kode_opd' => 'OPD014', 'nama_opd' => 'Dinas Koperasi, Usaha Kecil dan Menengah'],
            ['kode_opd' => 'OPD015', 'nama_opd' => 'Dinas Penanaman Modal dan Pelayanan Terpadu Satu Pintu'],
            ['kode_opd' => 'OPD016', 'nama_opd' => 'Dinas Kepemudaan dan Olahraga'],
            ['kode_opd' => 'OPD017', 'nama_opd' => 'Dinas Perpustakaan dan Kearsipan'],
            ['kode_opd' => 'OPD018', 'nama_opd' => 'Dinas Kelautan dan Perikanan'],
            ['kode_opd' => 'OPD019', 'nama_opd' => 'Dinas Pariwisata'],
            ['kode_opd' => 'OPD020', 'nama_opd' => 'Dinas Pertanian'],
            ['kode_opd' => 'OPD021', 'nama_opd' => 'Dinas Perdagangan'],
            ['kode_opd' => 'OPD022', 'nama_opd' => 'Dinas Perindustrian'],
            ['kode_opd' => 'OPD023', 'nama_opd' => 'Sekretariat Daerah'],
            ['kode_opd' => 'OPD024', 'nama_opd' => 'Sekretariat DPRD'],
            ['kode_opd' => 'OPD025', 'nama_opd' => 'Inspektorat'],
            ['kode_opd' => 'OPD026', 'nama_opd' => 'Badan Perencanaan Pembangunan Daerah'],
            ['kode_opd' => 'OPD027', 'nama_opd' => 'Badan Pengelolaan Keuangan dan Aset Daerah'],
            ['kode_opd' => 'OPD028', 'nama_opd' => 'Badan Kepegawaian Daerah'],
            ['kode_opd' => 'OPD029', 'nama_opd' => 'Badan Pendapatan Daerah'],
            ['kode_opd' => 'OPD030', 'nama_opd' => 'Badan Penanggulangan Bencana Daerah'],
            ['kode_opd' => 'OPD031', 'nama_opd' => 'Badan Kesatuan Bangsa dan Politik'],
            ['kode_opd' => 'OPD032', 'nama_opd' => 'Satuan Polisi Pamong Praja'],
            ['kode_opd' => 'OPD033', 'nama_opd' => 'Dinas Kebudayaan'],
            ['kode_opd' => 'OPD034', 'nama_opd' => 'Kecamatan Malalayang'],
            ['kode_opd' => 'OPD035', 'nama_opd' => 'Kecamatan Sario'],
            ['kode_opd' => 'OPD036', 'nama_opd' => 'Kecamatan Wanea'],
            ['kode_opd' => 'OPD037', 'nama_opd' => 'Kecamatan Wenang'],
            ['kode_opd' => 'OPD038', 'nama_opd' => 'Kecamatan Tikala'],
            ['kode_opd' => 'OPD039', 'nama_opd' => 'Kecamatan Paal Dua'],
            ['kode_opd' => 'OPD040', 'nama_opd' => 'Kecamatan Mapanget'],
            ['kode_opd' => 'OPD041', 'nama_opd' => 'Kecamatan Singkil'],
            ['kode_opd' => 'OPD042', 'nama_opd' => 'Kecamatan Tuminting'],
            ['kode_opd' => 'OPD043', 'nama_opd' => 'Kecamatan Bunaken'],
            ['kode_opd' => 'OPD044', 'nama_opd' => 'Kecamatan Bunaken Kepulauan'],
        ];

        // Insert data menggunakan foreach untuk menghindari error duplikasi
        foreach ($opdData as $opd) {
            Opd::updateOrCreate(
                ['kode_opd' => $opd['kode_opd']],
                ['nama_opd' => $opd['nama_opd']]
            );
        }

        $this->command->info('✓ ' . count($opdData) . ' OPD berhasil di-seed');
    }
}
