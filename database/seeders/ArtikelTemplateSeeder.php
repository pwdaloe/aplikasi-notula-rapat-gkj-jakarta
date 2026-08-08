<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ArtikelTemplateSeeder extends Seeder
{
    /**
     * Kerangka artikel diambil dari undangan Sidang MPL ke-1.027.
     * Susunan MPH masih dugaan dan perlu dicocokkan dengan notula MPH yang asli.
     */
    public function run(): void
    {
        $this->buat('Kerangka Sidang MPL', 'mpl', true, [
            ['Pembukaan',                                                  'pembukaan'],
            ['Presensi peserta sidang',                                    'presensi'],
            ['Laporan & presentasi',                                       'agenda'],
            ['Tindak lanjut keputusan MPH & sidang MPL sebelumnya',        'tindak_lanjut'],
            ['Materi Ministerium',                                         'agenda'],
            ['Warnasari',                                                  'agenda'],
            ['Penutup',                                                    'penutup'],
        ]);

        $this->buat('Kerangka Sidang MPL (dengan Materi MPH)', 'mpl', false, [
            ['Pembukaan',                                                  'pembukaan'],
            ['Presensi peserta sidang',                                    'presensi'],
            ['Laporan & presentasi',                                       'agenda'],
            ['Tindak lanjut keputusan MPH & sidang MPL sebelumnya',        'tindak_lanjut'],
            ['Materi MPH',                                                 'agenda'],
            ['Materi Ministerium',                                         'agenda'],
            ['Warnasari',                                                  'agenda'],
            ['Penutup',                                                    'penutup'],
        ]);

        $this->buat('Kerangka Rapat MPH', 'mph', true, [
            ['Pembukaan',                                                  'pembukaan'],
            ['Presensi peserta rapat',                                     'presensi'],
            ['Tindak lanjut rapat MPH sebelumnya',                         'tindak_lanjut'],
            ['Pembahasan materi untuk sidang MPL',                         'agenda'],
            ['Warnasari',                                                  'agenda'],
            ['Penutup',                                                    'penutup'],
        ]);
    }

    private function buat(string $nama, string $jenis, bool $bawaan, array $baris): void
    {
        $templateId = DB::table('artikel_template')->insertGetId([
            'nama'        => $nama,
            'jenis_rapat' => $jenis,
            'bawaan'      => $bawaan,
            'created_at'  => now(),
            'updated_at'  => now(),
        ]);

        foreach ($baris as $i => [$judul, $tipe]) {
            DB::table('artikel_template_baris')->insert([
                'template_id' => $templateId,
                'urutan'      => $i + 1,
                'judul'       => $judul,
                'tipe'        => $tipe,
                'created_at'  => now(),
                'updated_at'  => now(),
            ]);
        }
    }
}
