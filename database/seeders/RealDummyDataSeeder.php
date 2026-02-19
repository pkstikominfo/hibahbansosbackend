<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\Usulan;
use App\Models\Spj;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Schema;

class RealDummyDataSeeder extends Seeder
{
    public function run()
    {
        // 1. Pembersihan Data
        Schema::disableForeignKeyConstraints();
        DB::table('spj_persyaratan')->truncate();
        DB::table('spj')->truncate();
        DB::table('usulan_persyaratan')->truncate();
        Usulan::truncate();
        DB::table('file_persyaratan')->truncate();
        Schema::enableForeignKeyConstraints();

        $now = Carbon::now();
        $user = User::first();
        $userId = $user ? $user->id : 1;
        $masterFp = [
            'KTP Pemohon',
            'Kartu Keluarga',
            'Proposal',
            'RAB',
            'NIB'
        ];

        $fpIds = [];
        foreach ($masterFp as $nama) {
            $fpIds[] = DB::table('file_persyaratan')->insertGetId([
                'id_opd' => 'OPD005',
                'nama_persyaratan' => $nama,
                'idsubjenisbantuan' => 3,
            ]);
        }

        // Definisi foto per index (hanya untuk 10 data disetujui, index 0-9)
        $fotoSpj = [
            'foto_penjahit_siti.png',
            'foto_lansia_kasman.png',
            'foto_jagung_herman.png',
            'foto_pertukangan_udin.png',
            'foto_nelayan_ruslan.png',
            'foto_pendidikan_andi.png',
            'foto_rehab_syarifuddin.png',
            'foto_ikan_marno.png',
            'foto_sampah_kaling.png',
            'foto_ibadah_masjid.png',
        ];

        $buktiSpj = [
            'bukti_penjahit_siti.pdf',
            'bukti_lansia_kasman.pdf',
            'bukti_jagung_herman.pdf',
            'bukti_pertukangan_udin.pdf',
            'bukti_nelayan_ruslan.pdf',
            'bukti_pendidikan_andi.pdf',
            'bukti_rehab_syarifuddin.pdf',
            'bukti_ikan_marno.pdf',
            'bukti_sampah_kaling.pdf',
            'bukti_ibadah_masjid.pdf',
        ];

        // 3. Definisi 14 Skenario Usulan
        $scenarios = [
            // 10 Usulan Disetujui (Untuk Generate SPJ)
            ['judul' => 'Bantuan Modal Penjahit', 'status' => 'disetujui', 'anggaran' => 5000000, 'nama' => 'Siti Aminah', 'spj_status' => 'selesai'],
            ['judul' => 'Bantuan Lansia Desa A', 'status' => 'disetujui', 'anggaran' => 2400000, 'nama' => 'Bpk. Kasman', 'spj_status' => 'proses'],
            ['judul' => 'Bibit Jagung Poktan Maju', 'status' => 'disetujui', 'anggaran' => 15000000, 'nama' => 'Herman', 'spj_status' => 'selesai'],
            ['judul' => 'Alat Pertukangan Kayu', 'status' => 'disetujui', 'anggaran' => 7500000, 'nama' => 'Udin Syah', 'spj_status' => 'proses'],
            ['judul' => 'Sarana Kelompok Nelayan', 'status' => 'disetujui', 'anggaran' => 25000000, 'nama' => 'Ruslan', 'spj_status' => 'proses'],
            ['judul' => 'Pendidikan Siswa Prestasi', 'status' => 'disetujui', 'anggaran' => 3000000, 'nama' => 'Andi Wijaya', 'spj_status' => 'selesai'],
            ['judul' => 'Rehab Rumah Tak Layak', 'status' => 'disetujui', 'anggaran' => 20000000, 'nama' => 'Syarifuddin', 'spj_status' => 'proses'],
            ['judul' => 'Modal Budidaya Ikan', 'status' => 'disetujui', 'anggaran' => 12000000, 'nama' => 'Marno', 'spj_status' => 'proses'],
            ['judul' => 'Motor Sampah Lingkungan', 'status' => 'disetujui', 'anggaran' => 35000000, 'nama' => 'Kaling I', 'spj_status' => 'selesai'],
            ['judul' => 'Operasional Rumah Ibadah', 'status' => 'disetujui', 'anggaran' => 50000000, 'nama' => 'Masjid Nurul', 'spj_status' => 'proses'],

            // 4 Usulan Lainnya
            ['judul' => 'Permohonan Traktor Tangan', 'status' => 'diusulkan', 'anggaran' => 45000000, 'nama' => 'Poktan Hijau'],
            ['judul' => 'Bantuan Sembako Inflasi', 'status' => 'diterima', 'anggaran' => 10000000, 'nama' => 'Desa Bintauna'],
            ['judul' => 'Modal Usaha Kios', 'status' => 'ditolak', 'anggaran' => 5000000, 'nama' => 'Rini'],
            ['judul' => 'Bibit Durian Unggul', 'status' => 'diusulkan', 'anggaran' => 15000000, 'nama' => 'Poktan Sejahtera'],
        ];

        foreach ($scenarios as $key => $s) {
            // Create Usulan
            $usulan = Usulan::create([
                'judul' => $s['judul'],
                'anggaran_usulan' => $s['anggaran'],
                'anggaran_disetujui' => ($s['status'] == 'disetujui' || $s['status'] == 'diterima') ? $s['anggaran'] : null,
                'email' => "user" . ($key + 1) . "@tes.com",
                'nohp' => "08" . rand(100000000, 999999999),
                'idsubjenisbantuan' => rand(1, 4),
                'idkategori' => rand(1, 12),
                'kode_opd' => 'OPD005',
                'status' => $s['status'],
                'nama' => $s['nama'],
                'tahun' => "2026",
                'iddesa' => rand(1, 100),
                'created_at' => $now->copy()->subMonths(2),
                'updated_at' => $now,
            ]);

            // Create Usulan Persyaratan (Meniru format "usulan_persyaratan": [...])
            foreach (array_slice($fpIds, 0, 2) as $fpId) {
                DB::table('usulan_persyaratan')->insert([
                    'idusulan' => $usulan->idusulan,
                    'id_fp' => $fpId,
                    'file_persyaratan' => 'dummy.png'
                ]);
            }

            // 4. Create SPJ (Hanya untuk 10 data disetujui)
            if ($s['status'] == 'disetujui') {
                $spj = Spj::create([
                    'idusulan' => $usulan->idusulan,
                    'foto' => $fotoSpj[$key],
                    'realisasi' => $s['anggaran'],
                    'status' => $s['spj_status'],
                    'created_by' => $userId,
                    'created_at' => $now->copy()->subMonth(1),
                    'updated_at' => $now,
                ]);

                DB::table('spj_persyaratan')->insert([
                    'idspj' => $spj->idspj,
                    'file_persyaratan' => $buktiSpj[$key],
                ]);
            }
        }

        $this->command->info('Seed Berhasil: 14 Usulan dan 10 SPJ dibuat sesuai format JSON.');
    }
}
