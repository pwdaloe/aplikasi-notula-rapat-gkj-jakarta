<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('presensi', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sidang_id')->constrained('sidang')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->enum('status', ['hadir', 'terlambat', 'izin', 'sakit', 'tanpa_keterangan'])
                ->default('tanpa_keterangan');
            $table->string('catatan')->nullable();
            $table->foreignId('dicatat_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['sidang_id', 'user_id']);
            $table->index(['sidang_id', 'status']); // dasar hitungan kuorum
        });

        // Undangan, peninjau, dan pengurus komisi yang hadir tanpa akun.
        // Tidak ikut menghitung kuorum, tetapi boleh menjadi penutur masukan.
        Schema::create('peserta_manual', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sidang_id')->constrained('sidang')->cascadeOnDelete();
            $table->string('nama');
            $table->string('asal')->nullable();
            $table->string('keterangan')->nullable();
            $table->timestamps();
        });

        Schema::create('masukan', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agenda_id')->constrained('agenda')->cascadeOnDelete();

            // Balasan dibatasi satu tingkat. Baris yang punya induk_id
            // tidak boleh menjadi induk bagi baris lain — ditegakkan di lapis aplikasi.
            $table->foreignId('induk_id')->nullable()->constrained('masukan')->cascadeOnDelete();

            // Tepat satu dari dua kolom berikut wajib terisi.
            $table->foreignId('penutur_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('peserta_manual_id')->nullable()->constrained('peserta_manual')->nullOnDelete();

            $table->enum('jenis', ['usulan', 'pertanyaan', 'keberatan', 'dukungan', 'informasi'])
                ->default('informasi');
            $table->dateTime('waktu');
            $table->unsignedSmallInteger('urutan')->default(0);
            $table->unsignedInteger('versi')->default(1);
            $table->foreignId('dicatat_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['agenda_id', 'urutan']);
            $table->index('induk_id');
        });

        // Tiap poin adalah baris tersendiri. Inilah pencegahan bentrokan
        // yang sebenarnya antara dua sekretaris: selama keduanya menyentuh
        // baris berbeda, tabrakan tidak pernah terjadi.
        Schema::create('masukan_poin', function (Blueprint $table) {
            $table->id();
            $table->foreignId('masukan_id')->constrained('masukan')->cascadeOnDelete();
            $table->unsignedSmallInteger('urutan');
            $table->text('isi');
            $table->unsignedInteger('versi')->default(1); // kunci optimistis
            $table->timestamps();

            $table->index(['masukan_id', 'urutan']);
        });

        // Penyebut "@" disimpan sebagai rujukan ke orangnya, bukan sekadar teks,
        // agar tetap benar bila nama atau sebutannya berubah.
        Schema::create('masukan_sebutan', function (Blueprint $table) {
            $table->id();
            $table->foreignId('masukan_poin_id')->constrained('masukan_poin')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->unsignedSmallInteger('posisi')->nullable();
            $table->timestamps();

            $table->index('user_id');
        });

        // Penanda kehadiran dua sekretaris: siapa sedang membuka butir mana.
        // Diperbarui menumpang pada penyegaran berkala yang sudah berjalan.
        Schema::create('kehadiran_sunting', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sidang_id')->constrained('sidang')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('agenda_id')->nullable()->constrained('agenda')->nullOnDelete();
            $table->timestamp('terakhir_at');
            $table->timestamps();

            $table->unique(['sidang_id', 'user_id']);
        });

        // Postgres tidak punya tipe integer unsigned — lihat catatan yang sama
        // di 2026_08_08_000300_create_sidang_tables.php.
        DB::statement('ALTER TABLE masukan ADD CONSTRAINT masukan_urutan_check CHECK (urutan >= 0)');
        DB::statement('ALTER TABLE masukan ADD CONSTRAINT masukan_versi_check CHECK (versi >= 0)');
        DB::statement('ALTER TABLE masukan_poin ADD CONSTRAINT masukan_poin_urutan_check CHECK (urutan >= 0)');
        DB::statement('ALTER TABLE masukan_poin ADD CONSTRAINT masukan_poin_versi_check CHECK (versi >= 0)');
        DB::statement('ALTER TABLE masukan_sebutan ADD CONSTRAINT masukan_sebutan_posisi_check CHECK (posisi IS NULL OR posisi >= 0)');
    }

    public function down(): void
    {
        Schema::dropIfExists('kehadiran_sunting');
        Schema::dropIfExists('masukan_sebutan');
        Schema::dropIfExists('masukan_poin');
        Schema::dropIfExists('masukan');
        Schema::dropIfExists('peserta_manual');
        Schema::dropIfExists('presensi');
    }
};
