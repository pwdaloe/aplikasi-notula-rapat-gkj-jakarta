<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PengaturanSeeder extends Seeder
{
    public function run(): void
    {
        $pengaturan = [
            // Sidang MPL ke-1.027 berlangsung 7 Agustus 2026 — sidang terakhir
            // sebelum aplikasi dipakai. Sidang berikutnya bernomor 1.028.
            ['nomor_awal_mpl',     '1027', 'angka',  'Nomor sidang MPL terakhir yang sudah terpakai'],
            // Masih kosong. Aplikasi menolak membuat rapat MPH sampai diisi,
            // tanpa menghalangi pembuatan sidang MPL.
            ['nomor_awal_mph',     null,  'angka',   'Nomor rapat MPH terakhir yang sudah terpakai'],

            // Tata Gereja dan Tata Laksana: tiga perempat dari seluruh anggota
            // majelis. Dengan 48 anggota, ambangnya 36 orang.
            ['kuorum_mpl',         '0.75', 'pecahan', 'Bagian dari anggota majelis aktif'],
            // Belum dipastikan. Wajib diisi sebelum rapat MPH pertama dibuat.
            ['kuorum_mph',         null,   'pecahan', 'Bagian dari anggota MPH aktif'],
            ['kuorum_pembulatan',  'atas', 'teks',    'Pecahan ambang dibulatkan ke atas'],
            ['batas_koreksi_jam',  '72',   'angka',   'Lama masa koreksi notula'],
            ['selang_segar_detik', '5',    'angka',   'Selang penyegaran saat sidang berjalan'],
            ['maks_lampiran_mb',   '8',    'angka',   'Sesuaikan dengan batas hosting'],
            ['jenis_lampiran',     'pdf,docx,xlsx,pptx,jpg,jpeg,png', 'teks', 'Daftar putih'],
        ];

        foreach ($pengaturan as [$kunci, $nilai, $tipe, $ket]) {
            DB::table('pengaturan')->insert([
                'kunci'      => $kunci,
                'nilai'      => $nilai,
                'tipe'       => $tipe,
                'keterangan' => $ket,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        DB::table('profil_gereja')->insert([
            'nama'                => 'Gereja Kristen Jawa Jakarta',
            'kota_surat'          => 'Jakarta',
            'ketua_jabatan'       => 'Ketua Majelis',
            'sekretaris_nama'     => 'Pnt. Jennie PS',
            'sekretaris_jabatan'  => 'Sekretaris Majelis',
            'created_at'          => now(),
            'updated_at'          => now(),
        ]);
    }
}
