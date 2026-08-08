<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class PenggunaAwalSeeder extends Seeder
{
    /**
     * Tiga akun awal. Kata sandi diambil dari .env dan setiap akun
     * dipaksa menggantinya pada login pertama.
     *
     *   SANDI_AWAL="..."   <- isi di .env, jangan dimasukkan ke git
     */
    public function run(): void
    {
        $sandi = Hash::make(env('SANDI_AWAL', 'GantiSaya#2026'));

        $daftar = [
            [
                'nama'    => 'Administrator',
                'sebutan' => null,
                'no_hp'   => '628129055464',
                'peran'   => ['admin'],
            ],
            [
                'nama'    => 'Jennie PS',
                'sebutan' => 'Pnt.',
                'no_hp'   => '6285715060425',
                'peran'   => ['sekretaris', 'anggota'],
            ],
            [
                'nama'    => 'Heru',
                'sebutan' => 'Pnt.',
                'no_hp'   => '6281317763070',
                'peran'   => ['sekretaris', 'anggota'],
            ],
        ];

        foreach ($daftar as $orang) {
            $userId = DB::table('users')->insertGetId([
                'nama'              => $orang['nama'],
                'sebutan'           => $orang['sebutan'],
                'no_hp'             => $orang['no_hp'],
                'password'          => $sandi,
                'harus_ganti_sandi' => true,
                'aktif'             => true,
                'created_at'        => now(),
                'updated_at'        => now(),
            ]);

            foreach ($orang['peran'] as $peran) {
                DB::table('peran_pengguna')->insert([
                    'user_id'    => $userId,
                    'peran'      => $peran,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }
}
