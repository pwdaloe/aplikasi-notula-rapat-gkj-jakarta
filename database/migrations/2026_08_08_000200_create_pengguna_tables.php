<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('nama');
            // Sebutan disimpan terpisah agar penulisan "Pnt. Haryanto" pada notula
            // dapat dirakit otomatis dan diubah tanpa menyunting nama.
            $table->enum('sebutan', ['Pdt.', 'Pnt.', 'Dkn.', 'Vik.'])->nullable();
            // Nomor HP ternormalkan ke bentuk 62xxxxxxxxxx sebelum disimpan.
            $table->string('no_hp', 20)->unique();
            $table->string('email')->nullable()->unique();
            $table->string('password');
            $table->boolean('harus_ganti_sandi')->default(true);
            $table->boolean('aktif')->default(true);
            $table->timestamp('terakhir_masuk_at')->nullable();
            $table->rememberToken();
            $table->timestamps();

            $table->index('aktif');
        });

        Schema::create('peran_pengguna', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->enum('peran', ['admin', 'sekretaris', 'ketua', 'pendeta', 'anggota']);
            $table->timestamps();

            $table->unique(['user_id', 'peran']);
        });

        Schema::create('anggota_majelis', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('periode_id')->constrained('periode_kemajelisan')->cascadeOnDelete();
            $table->string('jabatan')->nullable();
            $table->foreignId('wilayah_id')->nullable()->constrained('wilayah')->nullOnDelete();
            $table->boolean('anggota_mph')->default(false);
            $table->boolean('aktif')->default(true);
            $table->date('tgl_mulai')->nullable();
            $table->date('tgl_selesai')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'periode_id']);
            $table->index(['periode_id', 'aktif']);
            $table->index(['periode_id', 'anggota_mph', 'aktif']); // dasar hitungan kuorum MPH
        });

        Schema::create('anggota_unit', function (Blueprint $table) {
            $table->id();
            $table->foreignId('anggota_majelis_id')->constrained('anggota_majelis')->cascadeOnDelete();
            $table->foreignId('unit_pelayanan_id')->constrained('unit_pelayanan')->cascadeOnDelete();
            $table->string('peran_unit')->nullable();
            $table->timestamps();

            $table->unique(['anggota_majelis_id', 'unit_pelayanan_id'], 'anggota_unit_unik');
        });

        // Pemulihan kata sandi lewat tautan sekali pakai yang dibuat admin,
        // lalu dikirim manual ke WhatsApp. Bukan alur reset lewat surel.
        Schema::create('tautan_pemulihan', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('token', 64)->unique();
            $table->timestamp('kadaluarsa_at');
            $table->timestamp('dipakai_at')->nullable();
            $table->foreignId('dibuat_oleh')->constrained('users');
            $table->timestamps();
        });

        // Wajib: session driver `database`. Session berbasis berkas akan
        // mengunci berkas dan memblokir penyegaran berkala saat sidang berjalan.
        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('tautan_pemulihan');
        Schema::dropIfExists('anggota_unit');
        Schema::dropIfExists('anggota_majelis');
        Schema::dropIfExists('peran_pengguna');
        Schema::dropIfExists('users');
    }
};
