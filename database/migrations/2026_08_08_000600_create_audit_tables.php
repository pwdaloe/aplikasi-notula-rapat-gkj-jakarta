<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Terpisah dari jejak audit umum dan tidak boleh dihapus dari dalam aplikasi.
        Schema::create('log_akses_tertutup', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agenda_id')->constrained('agenda')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->enum('aksi', ['lihat', 'cetak', 'unduh_lampiran'])->default('lihat');
            $table->string('ip', 45)->nullable();
            $table->string('user_agent', 512)->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['agenda_id', 'created_at']);
            $table->index('user_id');
        });

        Schema::create('jejak_audit', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('aksi', 32); // buat | ubah | hapus
            $table->string('model_tipe', 128);
            $table->unsignedBigInteger('model_id');
            $table->json('sebelum')->nullable();
            $table->json('sesudah')->nullable();
            $table->string('ip', 45)->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['model_tipe', 'model_id']);
            $table->index('created_at');
        });

        // Postgres tidak punya tipe integer unsigned — lihat catatan yang sama
        // di 2026_08_08_000300_create_sidang_tables.php.
        DB::statement('ALTER TABLE jejak_audit ADD CONSTRAINT jejak_audit_model_id_check CHECK (model_id >= 0)');
    }

    public function down(): void
    {
        Schema::dropIfExists('jejak_audit');
        Schema::dropIfExists('log_akses_tertutup');
    }
};
