<?php

namespace Tests\Feature;

use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Bukti bahwa seluruh migrasi berjalan bersih di database Postgres KHUSUS TEST
 * (gkjj_notula_test, terpisah dari database dev), dan bahwa CHECK constraint
 * eksplisit dari T0.6 sungguhan hidup -- bukan cuma lolos migrasi.
 */
class DatabaseSmokeTest extends TestCase
{
    use RefreshDatabase;

    public function test_migrasi_lengkap_berjalan_bersih_di_postgres(): void
    {
        $this->assertTrue(DB::getSchemaBuilder()->hasTable('sidang'));
        $this->assertTrue(DB::getSchemaBuilder()->hasTable('agenda'));
        $this->assertTrue(DB::getSchemaBuilder()->hasTable('agenda_akses'));
        $this->assertTrue(DB::getSchemaBuilder()->hasTable('notula'));
        $this->assertTrue(DB::getSchemaBuilder()->hasTable('log_akses_tertutup'));
    }

    public function test_check_constraint_menolak_nomor_sidang_negatif(): void
    {
        DB::table('periode_kemajelisan')->insert([
            'nama' => 'Test', 'tgl_mulai' => now(), 'aktif' => true,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $this->expectException(QueryException::class);

        DB::table('sidang')->insert([
            'deret' => 'mpl', 'nomor' => -1, 'jenis' => 'mpl',
            'periode_id' => 1, 'tanggal' => now(), 'versi' => 1,
            'created_at' => now(), 'updated_at' => now(),
        ]);
    }
}
