<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pengaturan', function (Blueprint $table) {
            $table->string('kunci', 64)->primary();
            $table->text('nilai')->nullable();
            $table->string('tipe', 16)->default('teks'); // teks | angka | pecahan | boolean
            $table->string('keterangan')->nullable();
            $table->timestamps();
        });

        Schema::create('profil_gereja', function (Blueprint $table) {
            $table->id();
            $table->string('nama');
            $table->text('alamat')->nullable();
            $table->string('telepon', 32)->nullable();
            $table->string('kota_surat', 64)->default('Jakarta');
            $table->string('logo_path')->nullable();
            $table->string('kop_path')->nullable();
            $table->string('ketua_nama')->nullable();
            $table->string('ketua_jabatan')->nullable();
            $table->string('ketua_ttd_path')->nullable();
            $table->string('sekretaris_nama')->nullable();
            $table->string('sekretaris_jabatan')->nullable();
            $table->string('sekretaris_ttd_path')->nullable();
            $table->timestamps();
        });

        Schema::create('periode_kemajelisan', function (Blueprint $table) {
            $table->id();
            $table->string('nama');
            $table->date('tgl_mulai');
            $table->date('tgl_selesai')->nullable();
            $table->boolean('aktif')->default(false);
            $table->timestamps();

            $table->index('aktif');
        });

        Schema::create('unit_pelayanan', function (Blueprint $table) {
            $table->id();
            $table->string('nama');
            $table->enum('jenis', [
                'bidang', 'komisi', 'upk', 'panitia', 'tim', 'lembaga', 'ministerium',
            ]);
            $table->date('tgl_mulai')->nullable();
            $table->date('tgl_selesai')->nullable();
            $table->boolean('aktif')->default(true);
            $table->timestamps();

            $table->index(['jenis', 'aktif']);
        });

        Schema::create('wilayah', function (Blueprint $table) {
            $table->id();
            $table->string('kode', 16)->unique();
            $table->string('nama');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wilayah');
        Schema::dropIfExists('unit_pelayanan');
        Schema::dropIfExists('periode_kemajelisan');
        Schema::dropIfExists('profil_gereja');
        Schema::dropIfExists('pengaturan');
    }
};
