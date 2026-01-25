<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Faker\Factory as Faker;
use App\Models\Usulan;
use App\Models\Spj;
use Illuminate\Support\Facades\Schema;

class DummyDataSeeder extends Seeder
{
    public function run()
    {
        Schema::disableForeignKeyConstraints();

        DB::table('spj_persyaratan')->truncate();
        DB::table('spj')->truncate();
        DB::table('usulan_persyaratan')->truncate();
        Usulan::truncate(); // Bisa pakai Model::truncate() atau DB::table()->truncate()
        DB::table('file_persyaratan')->truncate();

        // Hidupkan kembali pengecekan Foreign Key
        Schema::enableForeignKeyConstraints();

        $this->command->info('Data lama berhasil dihapus/dikosongkan.');

        $faker = Faker::create('id_ID');

        // Nama file dummy yang sudah Anda taruh di storage/app/public/uploads/
        // Pastikan file 'dummy.jpg' benar-benar ada di sana.
        $dummyFile = 'dummy.png';

        // Range ID dari SQL Dump
        $opdCodes = [];
        for ($i = 1; $i <= 44; $i++) {
            $opdCodes[] = 'OPD' . str_pad($i, 3, '0', STR_PAD_LEFT);
        }
        $subJenisIds = [1, 2, 3, 4];
        $kategoriIds = range(1, 12);
        $desaIds = range(1, 107);
        $pengusulIds = range(25, 31);

        // ======================================================
        // 1. SEEDER FILE PERSYARATAN (20 Data)
        // ======================================================
        $filePersyaratanIds = [];
        for ($i = 0; $i < 20; $i++) {
            $id = DB::table('file_persyaratan')->insertGetId([
                'id_opd' => $faker->randomElement($opdCodes),
                'nama_persayaratan' => $faker->randomElement([
                    'KTP Pemohon',
                    'Kartu Keluarga',
                    'Surat Keterangan Domisili',
                    'Proposal Lengkap',
                    'RAB Kegiatan',
                    'Foto Lokasi',
                    'Surat Izin Usaha',
                    'NPWP',
                    'Buku Rekening',
                    'Akta Pendirian'
                ]) . ' - ' . $faker->bothify('##??'),
                'idsubjenisbantuan' => $faker->randomElement($subJenisIds),
            ]);
            $filePersyaratanIds[] = $id;
        }
        $this->command->info('Berhasil membuat 20 File Persyaratan.');

        // ======================================================
        // 2. SEEDER USULAN (20 Data)
        // ======================================================
        $usulanIds = [];
        for ($i = 0; $i < 20; $i++) {
            $status = $faker->randomElement(['diusulkan', 'disetujui', 'diterima', 'ditolak']);

            $anggaranUsulan = $faker->numberBetween(1000000, 50000000);
            $anggaranDisetujui = ($status == 'disetujui' || $status == 'diterima')
                ? $faker->numberBetween(1000000, $anggaranUsulan)
                : null;

            $catatan = ($status == 'ditolak') ? $faker->sentence() : null;

            $usulan = Usulan::create([
                'judul' => 'Permohonan Bantuan ' . $faker->words(3, true),
                'anggaran_usulan' => $anggaranUsulan,
                'email' => 'user' . $faker->unique()->numberBetween(1, 9999) . '@tes.com',
                'nohp' => $faker->numerify('08##########'),
                'idsubjenisbantuan' => $faker->randomElement($subJenisIds),
                'idkategori' => $faker->randomElement($kategoriIds),
                'anggaran_disetujui' => $anggaranDisetujui,
                'kode_opd' => $faker->randomElement($opdCodes),
                'status' => $status,
                'nama' => $faker->name,
                'catatan_ditolak' => $catatan,
                'tahun' => 2026,
                'iddesa' => $faker->randomElement($desaIds),
                'created_at' => $faker->dateTimeBetween('-1 year', 'now'),
                'updated_at' => now(),
            ]);

            $usulanIds[] = $usulan->idusulan;
        }
        $this->command->info('Berhasil membuat 20 Usulan.');

        // ======================================================
        // 3. SEEDER USULAN PERSYARATAN (20 Data)
        // ======================================================
        for ($i = 0; $i < 20; $i++) {
            DB::table('usulan_persyaratan')->insert([
                'idusulan' => $faker->randomElement($usulanIds),
                'id_fp' => $faker->randomElement($filePersyaratanIds),
                'file_persyaratan' => $dummyFile, // MENGGUNAKAN FILE ASLI
            ]);
        }
        $this->command->info('Berhasil membuat 20 Usulan Persyaratan.');

        // ======================================================
        // 4. SEEDER SPJ (20 Data)
        // ======================================================
        $spjIds = [];
        $countUsulan = count($usulanIds);
        $amountToTake = $countUsulan < 20 ? $countUsulan : 20;

        $targetUsulanIds = $faker->randomElements($usulanIds, $amountToTake);

        foreach ($targetUsulanIds as $idUsulan) {
            $usulan = Usulan::find($idUsulan);
            if (!in_array($usulan->status, ['disetujui', 'diterima'])) {
                $usulan->update([
                    'status' => 'disetujui',
                    'anggaran_disetujui' => $usulan->anggaran_usulan
                ]);
            }

            $spj = Spj::create([
                'idusulan' => $idUsulan,
                'foto' => $dummyFile, // MENGGUNAKAN FILE ASLI
                'realisasi' => $usulan->anggaran_disetujui ?? $usulan->anggaran_usulan,
                'status' => $faker->randomElement(['proses', 'selesai']),
                'created_by' => $faker->randomElement($pengusulIds),
                'created_at' => $faker->dateTimeBetween($usulan->created_at, 'now'),
                'updated_at' => now(),
            ]);
            $spjIds[] = $spj->idspj;
        }
        $this->command->info('Berhasil membuat ' . count($spjIds) . ' SPJ.');

        // ======================================================
        // 5. SEEDER SPJ PERSYARATAN (20 Data)
        // ======================================================
        if (!empty($spjIds)) {
            for ($i = 0; $i < 20; $i++) {
                DB::table('spj_persyaratan')->insert([
                    'idspj' => $faker->randomElement($spjIds),
                    'file_persyaratan' => $dummyFile, // MENGGUNAKAN FILE ASLI
                ]);
            }
            $this->command->info('Berhasil membuat 20 SPJ Persyaratan.');
        }
    }
}
