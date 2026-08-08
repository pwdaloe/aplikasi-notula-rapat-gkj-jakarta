# Skema Data & Catatan Migrasi — Fase 1

**Rujukan:** Spesifikasi Fungsional Fase 1 v1.0
**Sasaran:** Laravel 11 · PostgreSQL 16
**Isi:** 6 berkas migrasi, 4 seeder, 26 tabel

> **Catatan revisi (8 Agustus 2026):** target basis data diganti dari MySQL 8 ke PostgreSQL 16 — VPS yang dipakai sudah menjalankan Postgres 16 untuk aplikasi Database-Warga-GKJJ, jadi Notula memakai database baru di server Postgres yang sama alih-alih memasang mesin database kedua. Berkas migrasi/seeder di `database/` **belum diubah kodenya** karena ditulis pakai Eloquent/Schema builder murni tanpa SQL mentah — portabel apa adanya. Dua hal yang tetap perlu diperhatikan saat menjalankannya di Postgres:
> 1. **Tipe `unsignedInteger`/`unsignedBigInteger`/`unsignedSmallInteger`** (dipakai di antara lain pada `sidang.nomor`, `*.urutan`, `*.versi`, `agenda_lampiran.ukuran`, `sidang.butir_aktif_id`) — Postgres tidak punya tipe integer unsigned, Laravel diam-diam membuang batasannya. Perlu ditambah `CHECK (kolom >= 0)` eksplisit di migrasi sebagai gantinya.
> 2. **Pencarian nama/judul** (mis. fitur penyebut `@`) — pakai `ILIKE`, bukan `LIKE`, karena Postgres *case-sensitive* secara default sementara MySQL selama ini kebetulan tidak.

---

## 1. Keputusan Rancangan yang Tidak Lazim

Empat hal berikut bentuknya berbeda dari skema notula pada umumnya, dan masing-masing lahir dari keputusan yang sudah kita ambil.

### 1.1 Nomor sidang bukan kunci utama

```php
$table->enum('deret', ['mpl', 'mph']);
$table->unsignedInteger('nomor');
$table->enum('jenis', ['mpl', 'mph', 'istimewa']);
$table->unique(['deret', 'nomor']);
```

`deret` dan `jenis` adalah dua hal berbeda. Sidang istimewa berjenis sendiri namun mengambil nomor dari deret MPL. Kalau keduanya digabung jadi satu kolom, penomoran akan kacau begitu ada sidang istimewa pertama.

Nomor berikutnya dihitung dengan mengunci baris agar dua sekretaris tidak mendapat nomor sama:

```php
DB::transaction(function () use ($deret) {
    $terakhir = DB::table('sidang')->where('deret', $deret)
        ->lockForUpdate()->max('nomor');
    return ($terakhir ?? Pengaturan::angka("nomor_awal_{$deret}")) + 1;
});
```

### 1.2 Satu butir agenda menyimpan dua judul

```php
$table->string('judul');          // judul sebenarnya
$table->string('judul_tampil');   // "Perkara penggembalaan"
```

Untuk butir tertutup, judul asli sendiri sudah membocorkan identitas orangnya. Versi tersunting hanya boleh memakai `judul_tampil`. Perbedaan keduanya adalah alasan mengapa notula tersunting tidak bisa dihasilkan hanya dengan menyembunyikan kolom.

### 1.3 Setiap poin masukan adalah baris tersendiri

```
masukan (penutur, jenis, waktu)
  └── masukan_poin (urutan, isi, versi)   ← satu baris per poin
        └── masukan_sebutan (user_id)      ← rujukan @, bukan teks
```

Ini konsekuensi langsung dari keputusan menyunting tanpa kunci. Selama dua sekretaris menyentuh baris berbeda, bentrokan tidak pernah terjadi. Kalau notula disimpan sebagai satu bidang teks besar, setiap ketukan keduanya adalah tabrakan.

Penyebut `@` disimpan sebagai rujukan ke orangnya, sehingga tetap benar bila nama atau sebutannya berubah kemudian.

### 1.4 Akses butir tertutup adalah baris, bukan aturan

```php
Schema::create('agenda_akses', ...);
$table->boolean('otomatis'); // true = notulis sidang, bukan penunjukan manusia
```

Kolom `otomatis` memisahkan dua hal yang tampak sama: baris yang ditambahkan sistem karena orang itu notulis, dan baris hasil penunjukan sadar. Saat notulis sidang berganti, hanya baris `otomatis` yang boleh disesuaikan; penunjukan manusia tidak boleh disentuh sistem.

---

## 2. Daftar Tabel

| Kelompok | Tabel |
|---|---|
| Master | `pengaturan` · `profil_gereja` · `periode_kemajelisan` · `unit_pelayanan` · `wilayah` |
| Pengguna | `users` · `peran_pengguna` · `anggota_majelis` · `anggota_unit` · `tautan_pemulihan` · `sessions` |
| Sidang | `artikel_template` · `artikel_template_baris` · `sidang` · `artikel` · `agenda` · `agenda_lampiran` · `agenda_akses` |
| Jalannya sidang | `presensi` · `peserta_manual` · `masukan` · `masukan_poin` · `masukan_sebutan` · `kehadiran_sunting` |
| Notula | `notula` · `notula_koreksi` · `notula_pembacaan` · `notula_adendum` |
| WhatsApp | `wa_template` · `wa_kirim_log` |
| Audit | `log_akses_tertutup` · `jejak_audit` |

**Tanpa paket peran pihak ketiga.** Peran disimpan di `peran_pengguna` milik sendiri. Kebutuhannya sederhana — lima peran tetap tanpa izin bertingkat — dan setiap paket tambahan berarti satu hal lagi yang bisa patah saat memperbarui di shared hosting.

---

## 3. Aturan yang Harus Ditegakkan Aplikasi

Basis data tidak dapat menjaga hal-hal berikut. Semuanya perlu diletakkan di *model observer* atau *form request*, bukan hanya di tampilan.

| # | Aturan | Tempat penegakan |
|---|---|---|
| 1 | `masukan` wajib punya tepat satu penutur — `penutur_user_id` atau `peserta_manual_id` | Observer `saving` |
| 2 | Balasan hanya satu tingkat: baris ber-`induk_id` tidak boleh menjadi induk | Observer `saving` |
| 3 | `level = tertutup` memaksa `tayang = jangan` dan mewajibkan `judul_tampil` | Observer `saving` |
| 4 | Butir tertutup tidak boleh punya `agenda_akses` kosong | Observer `saved` + validasi |
| 5 | Notulis sidang masuk otomatis ke `agenda_akses` setiap butir tertutup | Observer pada `agenda` dan `sidang` |
| 6 | Notulis hanya boleh dikeluarkan bila `pencatat_pengganti_id` terisi | Validasi |
| 7 | `sidang.butir_aktif_id` wajib butir milik sidang itu sendiri | Validasi |
| 8 | Notula `sah` mengunci presensi, masukan, agenda, dan artikel | Policy `update` |
| 9 | Koreksi ditolak setelah `batas_koreksi_at` terlewat | Policy `create` |
| 10 | Butir tertutup disaring lewat *global scope*, bukan disembunyikan di Blade | Global scope pada `Agenda` |

Aturan 3, 4, dan 10 adalah yang paling berat akibatnya bila terlewat, karena kegagalannya berupa kebocoran yang tidak terlihat dari layar.

PostgreSQL menegakkan `CHECK` dengan andal dan sudah lama begitu — tidak seperti MySQL yang baru serius menegakkannya sejak 8.0.16, atau MariaDB yang perlakuannya pernah berbeda. Karena itu aturan 1 dan 3 layak ditambahkan sungguhan sebagai jaring kedua di tingkat basis data, bukan sekadar "boleh kalau sempat".

---

## 4. Kunci Optimistis

Setiap baris yang mungkin disentuh dua sekretaris punya kolom `versi`: `sidang`, `agenda`, `masukan`, `masukan_poin`, `notula`.

```php
$terpengaruh = DB::table('masukan_poin')
    ->where('id', $id)
    ->where('versi', $versiYangDibaca)
    ->update(['isi' => $isiBaru, 'versi' => $versiYangDibaca + 1]);

if ($terpengaruh === 0) {
    // Rekan sudah mengubah lebih dulu.
    // Tampilkan pilihan: punya saya / punya rekan / gabungkan.
    // Di mode proyektor: pita tipis saja, tidak boleh kotak dialog.
}
```

Yang menentukan keberhasilannya bukan potongan kode ini, melainkan ukuran barisnya. Satu poin masukan berisi satu kalimat jarang disentuh dua orang sekaligus.

---

## 5. Pemasangan

### 5.1 Berkas `.env` yang wajib

```env
DB_CONNECTION=pgsql
DB_DATABASE=gkjj_notula

# Wajib. Session berbasis berkas akan mengunci berkas dan
# memblokir penyegaran berkala saat sidang berjalan.
SESSION_DRIVER=database

QUEUE_CONNECTION=database
CACHE_STORE=database

APP_TIMEZONE=Asia/Jakarta
APP_LOCALE=id

# Kata sandi awal tiga akun pertama. Jangan dimasukkan ke git.
SANDI_AWAL=
```

### 5.2 Urutan perintah

```bash
composer install --no-dev --optimize-autoloader
php artisan key:generate
php artisan migrate
php artisan db:seed
php artisan config:cache && php artisan route:cache
```

### 5.3 Cron di VPS

Bukan cron cPanel — crontab biasa milik user aplikasi di VPS (mengikuti konvensi path `/var/www/` yang sudah dipakai Database-Warga-GKJJ):

```cron
* * * * * cd /var/www/notula && php artisan schedule:run >> /dev/null 2>&1
* * * * * cd /var/www/notula && php artisan queue:work --stop-when-empty >> /dev/null 2>&1
0 2 * * * cd /var/www/notula && php artisan backup:jalankan >> /dev/null 2>&1
```

`backup:jalankan` memanggil `pg_dump`, bukan `mysqldump`.

### 5.4 Akun awal

| Nama | Nomor HP tersimpan | Peran |
|---|---|---|
| Administrator | `628129055464` | `admin` |
| Pnt. Jennie PS | `6285715060425` | `sekretaris`, `anggota` |
| Pnt. Heru | `6281317763070` | `sekretaris`, `anggota` |

Ketiganya dipaksa mengganti kata sandi pada login pertama.

### 5.5 Nomor awal

| Deret | Nilai | Sidang berikutnya |
|---|---|---|
| `mpl` | **1027** — Sidang MPL 7 Agustus 2026 | ke-1.028 |
| `mph` | *belum diisi* | — |

Penolakan dibuat **per deret**, bukan menyeluruh: pekerjaan sidang MPL dapat dimulai sekarang, sementara rapat MPH baru bisa dibuat setelah nomor terakhirnya diketahui. Pengaman ini penting karena penomoran yang telanjur salah sudah tersebar di grup WhatsApp sebelum sempat diperbaiki.

---

## 6. Data yang Belum Bisa Di-seed

| Data | Sebab |
|---|---|
| 46 anggota majelis lainnya | Perlu daftar lengkap dengan sebutan, jabatan, unit, wilayah, nomor HP — dari 48 total, 2 (Pnt. Jennie PS, Pnt. Heru) sudah ter-seed lewat `PenggunaAwalSeeder`; Administrator bukan anggota majelis |
| Unit pelayanan | Perlu daftar resmi yang aktif |
| Wilayah/Rama | Baru diketahui ada Rama A, B, dan C |
| Nomor awal kedua deret | Perlu nomor sidang terakhir yang sudah terpakai |
| Kop surat, logo, tanda tangan | Berkas gambar |
| Ambang kuorum MPH | Ambang MPL sudah pasti 3/4 (36 dari 48). Ambang MPH belum diketahui |

Seeder untuk anggota sebaiknya menerima berkas CSV agar pemasukan 46 orang tidak dikerjakan satu per satu lewat layar.

---

## 7. Catatan Verifikasi

Berkas migrasi ini **belum pernah dijalankan**, dan belum pernah dijalankan terhadap PostgreSQL sama sekali (ditulis dengan sasaran awal MySQL 8, lihat catatan revisi di atas). Sebelum dipakai, jalankan pada basis data kosong:

```bash
php artisan migrate:fresh --seed
php artisan migrate:rollback --step=6
```

Perhatian khusus pada migrasi ketiga: kolom `sidang.butir_aktif_id` dibuat lebih dulu sebagai kolom biasa, lalu kunci asingnya dipasang setelah tabel `agenda` terbentuk, karena keduanya saling merujuk. Pembatalan migrasi melepas kunci asing itu terlebih dahulu. PostgreSQL umumnya konsisten untuk pola ini, tapi bila urutannya tetap bermasalah, pemisahannya menjadi migrasi tersendiri adalah jalan keluar yang aman.
