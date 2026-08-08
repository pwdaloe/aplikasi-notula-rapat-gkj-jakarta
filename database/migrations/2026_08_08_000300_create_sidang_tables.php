<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('artikel_template', function (Blueprint $table) {
            $table->id();
            $table->string('nama');
            $table->enum('jenis_rapat', ['mpl', 'mph', 'istimewa']);
            $table->boolean('bawaan')->default(false);
            $table->timestamps();
        });

        Schema::create('artikel_template_baris', function (Blueprint $table) {
            $table->id();
            $table->foreignId('template_id')->constrained('artikel_template')->cascadeOnDelete();
            $table->unsignedSmallInteger('urutan');
            $table->string('judul');
            $table->enum('tipe', ['pembukaan', 'presensi', 'agenda', 'tindak_lanjut', 'penutup'])
                ->default('agenda');
            $table->timestamps();

            $table->index(['template_id', 'urutan']);
        });

        Schema::create('sidang', function (Blueprint $table) {
            $table->id();

            // `deret` menentukan penomoran, `jenis` menentukan sifat rapat.
            // Keduanya berbeda: sidang istimewa berjenis sendiri namun ikut deret MPL.
            $table->enum('deret', ['mpl', 'mph']);
            $table->unsignedInteger('nomor');
            $table->enum('jenis', ['mpl', 'mph', 'istimewa']);

            $table->foreignId('periode_id')->constrained('periode_kemajelisan');
            $table->date('tanggal');
            $table->time('jam_mulai_rencana')->nullable();
            $table->time('jam_mulai_nyata')->nullable();
            $table->time('jam_selesai')->nullable();
            $table->string('tempat')->nullable();

            $table->foreignId('pemimpin_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('notulis_id')->nullable()->constrained('users')->nullOnDelete();

            $table->text('catatan_undangan')->nullable();
            $table->string('pic_konsumsi')->nullable();

            $table->enum('status', ['draft', 'diedarkan', 'berjalan', 'selesai'])->default('draft');

            // Ditambahkan sebagai kolom biasa; kunci asingnya dipasang setelah
            // tabel `agenda` terbentuk, di bagian bawah migrasi ini.
            $table->unsignedBigInteger('butir_aktif_id')->nullable();

            $table->unsignedInteger('versi')->default(1);
            $table->timestamps();

            // Inti penomoran: unik per deret, bukan per jenis, bukan auto-increment.
            $table->unique(['deret', 'nomor']);
            $table->index(['status', 'tanggal']);
        });

        Schema::create('artikel', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sidang_id')->constrained('sidang')->cascadeOnDelete();
            // Nomor Romawi dihitung dari urutan saat ditampilkan, tidak disimpan.
            $table->unsignedSmallInteger('urutan');
            $table->string('judul');
            $table->enum('tipe', ['pembukaan', 'presensi', 'agenda', 'tindak_lanjut', 'penutup'])
                ->default('agenda');
            $table->timestamps();

            $table->index(['sidang_id', 'urutan']);
        });

        Schema::create('agenda', function (Blueprint $table) {
            $table->id();
            $table->foreignId('artikel_id')->constrained('artikel')->cascadeOnDelete();
            $table->unsignedSmallInteger('urutan');

            // Dua judul. `judul` adalah judul sebenarnya; `judul_tampil` dipakai
            // pada notula tersunting untuk butir tertutup, mis. "Perkara penggembalaan".
            $table->string('judul');
            $table->string('judul_tampil')->nullable();

            $table->foreignId('pelapor_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('pelapor_nama')->nullable(); // untuk peninjau tanpa akun
            $table->foreignId('unit_pelayanan_id')->nullable()->constrained('unit_pelayanan')->nullOnDelete();

            // Dua sumbu pembatasan yang berdiri sendiri.
            $table->enum('level', ['umum', 'majelis', 'tertutup'])->default('majelis');
            $table->enum('tayang', ['boleh', 'jangan'])->default('boleh');
            $table->boolean('pra_mpl')->default(false);

            $table->enum('status', ['baru', 'dibahas', 'ditunda', 'dikembalikan', 'selesai'])
                ->default('baru');

            // Dipakai bila notulis sidang harus dikeluarkan dari butir tertutup
            // karena ia pihak dalam perkara.
            $table->foreignId('pencatat_pengganti_id')->nullable()->constrained('users')->nullOnDelete();

            $table->unsignedInteger('versi')->default(1);
            $table->timestamps();

            $table->index(['artikel_id', 'urutan']);
            $table->index(['level', 'tayang']);
        });

        Schema::create('agenda_lampiran', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agenda_id')->constrained('agenda')->cascadeOnDelete();
            $table->string('nama_asli');
            $table->string('path');
            $table->string('mime', 128)->nullable();
            $table->unsignedBigInteger('ukuran')->default(0);
            $table->foreignId('diunggah_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        // Daftar pembaca butir tertutup, ditunjuk per butir. Bukan berbasis jabatan.
        Schema::create('agenda_akses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agenda_id')->constrained('agenda')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            // `otomatis` menandai baris yang ditambahkan sistem karena
            // orang tersebut notulis sidang, bukan hasil penunjukan manusia.
            $table->boolean('otomatis')->default(false);
            $table->foreignId('diberi_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['agenda_id', 'user_id']);
            $table->index('user_id');
        });

        Schema::table('sidang', function (Blueprint $table) {
            $table->foreign('butir_aktif_id')->references('id')->on('agenda')->nullOnDelete();
        });

        // Postgres tidak punya tipe integer unsigned — kolom di atas yang
        // ditulis unsignedInteger/unsignedSmallInteger/unsignedBigInteger
        // kehilangan proteksi "tidak boleh negatif" itu secara diam-diam.
        // CHECK berikut mengembalikan proteksinya secara eksplisit.
        // (butir_aktif_id tidak perlu CHECK terpisah — sudah dijaga foreign key di atas.)
        DB::statement('ALTER TABLE artikel_template_baris ADD CONSTRAINT artikel_template_baris_urutan_check CHECK (urutan >= 0)');
        DB::statement('ALTER TABLE sidang ADD CONSTRAINT sidang_nomor_check CHECK (nomor >= 0)');
        DB::statement('ALTER TABLE sidang ADD CONSTRAINT sidang_versi_check CHECK (versi >= 0)');
        DB::statement('ALTER TABLE artikel ADD CONSTRAINT artikel_urutan_check CHECK (urutan >= 0)');
        DB::statement('ALTER TABLE agenda ADD CONSTRAINT agenda_urutan_check CHECK (urutan >= 0)');
        DB::statement('ALTER TABLE agenda ADD CONSTRAINT agenda_versi_check CHECK (versi >= 0)');
        DB::statement('ALTER TABLE agenda_lampiran ADD CONSTRAINT agenda_lampiran_ukuran_check CHECK (ukuran >= 0)');
    }

    public function down(): void
    {
        Schema::table('sidang', function (Blueprint $table) {
            $table->dropForeign(['butir_aktif_id']);
        });

        Schema::dropIfExists('agenda_akses');
        Schema::dropIfExists('agenda_lampiran');
        Schema::dropIfExists('agenda');
        Schema::dropIfExists('artikel');
        Schema::dropIfExists('sidang');
        Schema::dropIfExists('artikel_template_baris');
        Schema::dropIfExists('artikel_template');
    }
};
