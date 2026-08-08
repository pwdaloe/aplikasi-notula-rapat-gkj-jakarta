<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class WaTemplateSeeder extends Seeder
{
    /**
     * Teks undangan mengikuti format yang sudah dipakai majelis.
     * Pengganti ditulis dalam kurung kurawal dan diisi saat penyusunan.
     */
    public function run(): void
    {
        $undangan = <<<'TEKS'
{kota}, {tanggal_surat}
Salam dalam kasih Kristus,
Kami mengundang Bapak dan Ibu anggota Majelis Pekerja Harian (MPH) dan Majelis Pekerja Lengkap (MPL) untuk hadir pada {jenis_sidang} ke-{nomor} sbb:

Hari/tanggal: {hari}, {tanggal}
Pukul: {jam} - selesai
Tempat: {tempat}

AGENDA {jenis_singkat} ke-{nomor}
{daftar_agenda}

{catatan_tambahan}
PIC konsumsi: {pic_konsumsi}

Kami tunggu kehadirannya, terimakasih.
{nama_pengundang}
TEKS;

        $daftar = [
            ['undangan',      'Undangan sidang',                 $undangan],
            ['notula_review', 'Draft notula siap dikoreksi',
                "Salam dalam kasih Kristus,\nDraft notula {jenis_sidang} ke-{nomor} sudah dapat dibaca dan dikoreksi di aplikasi.\nBatas koreksi: {batas_koreksi}.\n\n{tautan}\n\nTerimakasih.\n{nama_pengundang}"],
            ['notula_sah',    'Notula sudah disahkan',
                "Salam dalam kasih Kristus,\nNotula {jenis_sidang} ke-{nomor} telah disahkan pada {tanggal_sah}.\n\n{tautan}\n\nTerimakasih.\n{nama_pengundang}"],
            ['pengingat',     'Pengingat sidang',
                "Salam dalam kasih Kristus,\nMengingatkan kembali {jenis_sidang} ke-{nomor} pada {hari}, {tanggal} pukul {jam} di {tempat}.\n\nKami tunggu kehadirannya.\n{nama_pengundang}"],
        ];

        foreach ($daftar as [$kode, $judul, $isi]) {
            DB::table('wa_template')->insert([
                'kode' => $kode,
                'judul' => $judul,
                'isi' => $isi,
                'aktif' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
