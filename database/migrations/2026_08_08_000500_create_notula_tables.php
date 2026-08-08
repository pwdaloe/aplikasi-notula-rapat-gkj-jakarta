<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Notula tidak punya nomor sendiri: ia mengikuti nomor sidangnya.
        Schema::create('notula', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sidang_id')->unique()->constrained('sidang')->cascadeOnDelete();
            $table->enum('status', ['draft', 'review', 'sah', 'adendum'])->default('draft');

            $table->timestamp('diedarkan_at')->nullable();
            $table->timestamp('batas_koreksi_at')->nullable();

            // Pengesahan menuntut dua persetujuan yang berdiri sendiri.
            $table->foreignId('setuju_ketua_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('setuju_ketua_at')->nullable();
            $table->foreignId('setuju_sekretaris_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('setuju_sekretaris_at')->nullable();
            $table->timestamp('disahkan_at')->nullable();

            $table->boolean('tampilkan_daftar_hadir')->default(true);
            $table->unsignedInteger('versi')->default(1);
            $table->timestamps();

            $table->index('status');
        });

        Schema::create('notula_koreksi', function (Blueprint $table) {
            $table->id();
            $table->foreignId('notula_id')->constrained('notula')->cascadeOnDelete();
            $table->foreignId('artikel_id')->nullable()->constrained('artikel')->nullOnDelete();
            $table->foreignId('agenda_id')->nullable()->constrained('agenda')->nullOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->text('isi');
            $table->enum('status', ['baru', 'diterima', 'ditolak'])->default('baru');
            $table->text('tanggapan')->nullable();
            $table->foreignId('ditanggapi_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('ditanggapi_at')->nullable();
            $table->timestamps();

            $table->index(['notula_id', 'status']);
        });

        Schema::create('notula_pembacaan', function (Blueprint $table) {
            $table->id();
            $table->foreignId('notula_id')->constrained('notula')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamp('dibaca_at');
            $table->timestamps();

            $table->unique(['notula_id', 'user_id']);
        });

        // Perubahan setelah pengesahan tidak menyunting notula asli.
        Schema::create('notula_adendum', function (Blueprint $table) {
            $table->id();
            $table->foreignId('notula_id')->constrained('notula')->cascadeOnDelete();
            $table->unsignedSmallInteger('nomor');
            $table->text('isi');
            $table->text('alasan')->nullable();
            $table->foreignId('dibuat_oleh')->constrained('users');
            $table->timestamp('disahkan_at')->nullable();
            $table->timestamps();

            $table->unique(['notula_id', 'nomor']);
        });

        Schema::create('wa_template', function (Blueprint $table) {
            $table->id();
            $table->string('kode', 48)->unique(); // undangan | notula_review | notula_sah | pengingat
            $table->string('judul');
            $table->text('isi');
            $table->boolean('aktif')->default(true);
            $table->timestamps();
        });

        // Aplikasi hanya menyusun teks. Baris ini mencatat penandaan manual
        // "sudah saya tempel ke grup", bukan bukti pengiriman oleh sistem.
        Schema::create('wa_kirim_log', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sidang_id')->nullable()->constrained('sidang')->cascadeOnDelete();
            $table->foreignId('notula_id')->nullable()->constrained('notula')->cascadeOnDelete();
            $table->string('kode_template', 48);
            $table->longText('isi_final');
            $table->foreignId('ditandai_oleh')->constrained('users');
            $table->timestamp('ditandai_at');
            $table->timestamps();

            $table->index('sidang_id');
        });

        // Postgres tidak punya tipe integer unsigned — lihat catatan yang sama
        // di 2026_08_08_000300_create_sidang_tables.php.
        DB::statement('ALTER TABLE notula ADD CONSTRAINT notula_versi_check CHECK (versi >= 0)');
        DB::statement('ALTER TABLE notula_adendum ADD CONSTRAINT notula_adendum_nomor_check CHECK (nomor >= 0)');
    }

    public function down(): void
    {
        Schema::dropIfExists('wa_kirim_log');
        Schema::dropIfExists('wa_template');
        Schema::dropIfExists('notula_adendum');
        Schema::dropIfExists('notula_pembacaan');
        Schema::dropIfExists('notula_koreksi');
        Schema::dropIfExists('notula');
    }
};
