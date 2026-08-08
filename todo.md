# TODO — Aplikasi Notula Rapat GKJ Jakarta (Fase 1)

Peta kerja untuk sesi otonom (`/loop`). Setiap sprint memetakan langsung ke bagian spesifikasi fungsional, supaya "selesai" tidak perlu ditebak — tinggal cocokkan ke kriteria terima yang sudah tertulis.

> **Sejak 8 Agustus 2026**: eksekusi sprint sebaiknya lewat skill `/sprint` (di `.claude/commands/sprint.md`, diadaptasi dari skill standar Sunartha) alih-alih mengikuti instruksi manual di bawah kata per kata — `/sprint` sudah membaca `todo.md` ini, cek gerbang manusia Sprint 14/15 otomatis, update checkbox, commit, dan lapor lewat email. `/loop` bisa memanggil `/sprint` tiap putaran. Skill lain yang tersedia: `/pm` (status & standup email), `/devops` (health check dev lokal), `/qa`, `/review`, `/security`, `/docs`, `/release`, `/retro` + `/improve` (self-learning). Lihat `CLAUDE.md` bagian "Sunartha Claude Skills" untuk konfigurasinya.

## Cara pakai file ini (baca ini dulu, setiap putaran)

1. Baca `CLAUDE.md` di root proyek dulu untuk konteks yang tidak berubah antar sesi (stack, keputusan yang sudah dikunci).
2. Cari tugas pertama bertanda `[ ]` dari sprint bernomor terkecil. Jangan lompat ke sprint berikutnya selama masih ada `[ ]` di sprint aktif, kecuali tugas itu ditandai `[blocked]`.
3. Kerjakan **satu tugas** sampai tuntas: implementasi → jalankan `php artisan test` (atau `pest`) yang relevan → cocokkan ke "Kriteria terima" sprint tersebut.
4. Centang `[x]` **hanya** setelah verifikasi lulus nyata (bukan asumsi kode "harusnya benar"). Kalau gagal, jangan centang — tulis alasannya sebagai sub-baris, lanjut ke tugas berikutnya yang tidak bergantung padanya.
5. Kalau tugas butuh keputusan yang bukan wewenang teknis (mis. angka Tata Laksana, salinan resmi teks, kredensial nyata) — tandai `[blocked: <alasan singkat>]`, catat di "Diblokir menunggu manusia" di bawah, lanjut ke tugas lain.
6. Commit git per tugas selesai. Pesan commit menyebut kode tugas, mis. `T3.2 — nomor sidang pakai lockForUpdate per deret`.
7. Sebelum berhenti (baik karena putaran habis atau semua tugas beres), perbarui "Status" di bawah ini.
8. Jangan mulai Sprint N+1 sebelum kriteria terima Sprint N seluruhnya lulus — sprint disusun berurutan karena saling bergantung (mis. Sprint 7 butuh Sprint 5 & 6 selesai).
9. **Sprint 14 dan Sprint 15 punya gerbang wajib** (lihat penanda ⛔ di judul sprint masing-masing). Begitu Sprint 13 selesai dan tugas berikutnya adalah T14.1: **berhenti total, jangan kerjakan apa pun di sprint itu**, tulis `[blocked: menunggu konfirmasi pengguna sebelum menyentuh VPS produksi]` di "Diblokir menunggu manusia" di bawah, dan akhiri putaran `/loop` — jangan dijadwalkan lanjut otomatis. Ini bukan tugas teknis yang bisa diputuskan sendiri; VPS itu juga menjalankan aplikasi lain yang live.

## Status

- **Sprint aktif:** Sprint 0
- **Tugas berikutnya:** T0.1
- **Terakhir dikerjakan:** — (belum mulai)
- **Diblokir menunggu manusia:** — (kosong)
- **Gerbang manusia wajib:** sebelum T14.1 (deploy VPS) dan sebelum T15.1 (UAT di hosting sasaran) — lihat aturan #9 di atas

---

## Aturan main (jangan menyimpang tanpa alasan tertulis)

Keputusan berikut sudah dikunci lewat diskusi sebelumnya — lihat `CLAUDE.md` dan `docs/` untuk rincian dan alasannya. Jangan diubah begitu saja saat coding:

- Laravel 11, Blade + Livewire, **PostgreSQL 16** (bukan MySQL — spesifikasi asli sudah direvisi, lihat `docs/skema-data-fase-1.md`)
- Hosting: **VPS** 2GB bersama dengan Database-Warga-GKJJ (bukan shared hosting cPanel), database Postgres baru di server yang sama, bukan mesin database kedua
- PDF: **dompdf saja** — dilarang Browsershot/Puppeteer
- Session, queue, cache: driver `database`
- Aset frontend: build lokal, upload manual — **tanpa `npm` di server**
- Pencarian nama/judul apa pun: pakai `ILIKE`, bukan `LIKE`
- Kolom bekas `unsignedInteger`/`unsignedBigInteger`/`unsignedSmallInteger`: tambahkan `CHECK (kolom >= 0)` eksplisit di migrasi (Postgres tidak punya tipe unsigned)
- `kuorum_mpl` = **3/4** (36 dari 48 anggota), `kuorum_mph` masih dugaan `1/2` — belum dipastikan
- 48 anggota majelis total; migrasi & seeder di `database/` sudah ada dari sesi sebelumnya, jangan ditulis ulang dari nol — sesuaikan/perluas

---

## Sprint 0 — Fondasi Proyek & Lingkungan

**Tujuan:** proyek Laravel jalan lokal, tersambung Postgres, migrasi & seeder yang sudah ada berhasil dijalankan.

- [ ] T0.1 — `git init`, `.gitignore` standar Laravel, commit awal (docs/, database/ yang sudah ada)
- [ ] T0.2 — `composer create-project laravel/laravel:^11.0` di root proyek. **Jangan timpa** `database/migrations` dan `database/seeders` yang sudah ada — pindahkan isi hasil scaffold bawaan Laravel yang bentrok, satukan dengan yang sudah ada
- [ ] T0.3 — `composer require livewire/livewire barryvdh/laravel-dompdf`
- [ ] T0.4 — `docker-compose.yml` lokal: service `postgres:16`, database `gkjj_notula`, port map ke 5432, volume persisten
- [ ] T0.5 — `.env`: `DB_CONNECTION=pgsql`, host/port sesuai docker-compose, `SESSION_DRIVER=database`, `QUEUE_CONNECTION=database`, `CACHE_STORE=database`, `APP_TIMEZONE=Asia/Jakarta`, `APP_LOCALE=id`, `SANDI_AWAL=` (isi nilai dev sendiri, jangan commit)
- [ ] T0.6 — Tambahkan `CHECK (>= 0)` eksplisit ke migrasi yang punya kolom bekas unsigned (lihat daftar di `docs/skema-data-fase-1.md` catatan revisi): `sidang.nomor`, semua `*.urutan`, semua `*.versi`, `agenda_lampiran.ukuran`, `notula_adendum.nomor`, `jejak_audit.model_id`
- [ ] T0.7 — `php artisan migrate --seed` berhasil tanpa error di Postgres lokal (verifikasi nyata — jalankan sungguhan, jangan diasumsikan)
- [ ] T0.8 — Layout dasar Blade + Livewire terpasang, `php artisan serve` menampilkan halaman kosong tanpa error
- [ ] T0.9 — Setup testing: Pest atau PHPUnit jalan (`php artisan test` lulus dengan test bawaan)

**Kriteria terima:** `php artisan migrate:fresh --seed` bersih dari nol, 3 akun awal (Administrator, Pnt. Jennie PS, Pnt. Heru) ada di tabel `users` dengan peran benar.

---

## Sprint 1 — Autentikasi & Akun (F1-C01–C05, Bagian 4 spesifikasi)

- [ ] T1.1 — Login pakai nomor HP + kata sandi; normalisasi `08…`/`62…` ke bentuk sama sebelum dicocokkan
- [ ] T1.2 — Middleware: `harus_ganti_sandi = true` selalu dialihkan ke halaman ganti sandi, tidak bisa buka apa pun sebelum itu
- [ ] T1.3 — Admin CRUD akun. Tidak ada pendaftaran mandiri, tidak ada tautan reset di halaman masuk
- [ ] T1.4 — Halaman masuk memuat nama & nomor sekretaris sebagai jalan keluar
- [ ] T1.5 — Tautan pemulihan sekali pakai, berlaku 24 jam, hangus setelah dipakai (tabel `tautan_pemulihan` sudah ada)
- [ ] T1.6 — 5x gagal berturut-turut → kunci akun 15 menit

**Kriteria terima (dari spesifikasi):**
- [ ] Halaman masuk memuat kontak sekretaris, bukan tautan reset
- [ ] Pengguna belum ganti sandi awal selalu diarahkan ke halaman ganti sandi
- [ ] Nomor `081290457731` dan `6281290457731` mengarah ke akun yang sama (buat test otomatis untuk ini)

---

## Sprint 2 — Master Data (F1-B01–B06, Bagian 3)

- [ ] T2.1 — CRUD (policy dibatasi admin): Profil Gereja (singleton), Periode Kemajelisan, Anggota Majelis, Unit Pelayanan, Wilayah/Rama, Pengaturan
- [ ] T2.2 — Observer: hanya satu periode boleh `aktif = true` pada satu waktu
- [ ] T2.3 — Observer: menonaktifkan anggota tidak menghapus jejaknya pada sidang lampau (verifikasi lewat query, bukan cuma soft-delete asumsi)
- [ ] T2.4 — Ubah sebutan: notula belum `sah` ikut berubah tampilannya; notula `sah` tidak berubah — perlu keputusan desain (snapshot nama di notula sah, atau baca langsung dari relasi terkunci?) — dokumentasikan pendekatannya di `CLAUDE.md`
- [ ] T2.5 — Validasi: aplikasi menolak membuat sidang bila `nomor_awal_mpl`/`nomor_awal_mph` (untuk deret yang relevan) belum diisi

**Kriteria terima:** 3 butir di F1-B06 spesifikasi, semua diverifikasi dengan test.

---

## Sprint 3 — Penyiapan Sidang (F1-D01–D04, Bagian 5)

- [ ] T3.1 — Nomor sidang: usul otomatis (nomor tertinggi per `deret` + 1) pakai `lockForUpdate()` transaksional, boleh dikoreksi tangan, unik gabungan `(deret, nomor)`
- [ ] T3.2 — Sidang istimewa (`jenis = istimewa`) mengambil nomor dari deret `mpl`, bukan deret sendiri
- [ ] T3.3 — CRUD Sidang: jenis, deret, nomor, hari/tanggal, jam rencana, tempat, pemimpin, notulis, catatan undangan, PIC konsumsi; status `draft → diedarkan → berjalan → selesai`
- [ ] T3.4 — Saat sidang dibuat: artikel disalin dari `artikel_template` terkait, berdiri sendiri sejak itu (ubah template tidak memengaruhi sidang yang sudah dibuat)
- [ ] T3.5 — Susun artikel: tambah/hapus/ubah judul/urutan ulang; nomor Romawi dihitung dari urutan saat tampil, tidak disimpan; artikel `presensi` tidak bisa dihapus
- [ ] T3.6 — Menghapus artikel berisi butir → minta konfirmasi dulu

**Kriteria terima:** 3 butir di Bagian 5 spesifikasi.

---

## Sprint 4 — Butir Agenda & Dua Sumbu Pembatasan (F1-E01–E03, Bagian 6)

- [ ] T4.1 — CRUD butir dengan seluruh field F1-E01; `judul_tampil` wajib bila `level = tertutup`
- [ ] T4.2 — Observer aturan dua sumbu: `level = tertutup` memaksa `tayang = jangan` (tak bisa diubah manual); `pra_mpl` menyala → `tayang` bawaan `jangan` tapi boleh dilepas; `umum`/`majelis` → `tayang` bawaan `boleh`
- [ ] T4.3 — Upload lampiran per butir, validasi jenis & ukuran dari `pengaturan` (`jenis_lampiran`, `maks_lampiran_mb`); lampiran butir `tertutup` mewarisi pembatasan butirnya

**Kriteria terima:** 3 butir di Bagian 6 spesifikasi.

---

## Sprint 5 — Butir Tertutup & Pemberian Akses (F1-F01–F07, Bagian 7)

**Ini sprint paling berisiko kalau meleset — kegagalannya berupa kebocoran yang tidak terlihat dari layar. Kerjakan pelan, uji eksplisit.**

- [ ] T5.1 — CRUD `agenda_akses` per butir tertutup, tidak mengikuti jabatan
- [ ] T5.2 — Observer: notulis sidang otomatis masuk daftar setiap butir `tertutup` pada sidang itu (`otomatis = true`), tanpa penunjukan manual
- [ ] T5.3 — Perkecualian notulis: hanya bisa dikeluarkan bila `pencatat_pengganti_id` sudah ditunjuk untuk butir itu — tolak kalau belum
- [ ] T5.4 — Validasi: menyimpan butir `tertutup` dengan daftar pembaca kosong ditolak
- [ ] T5.5 — Global scope pada model `Agenda`: butir `tertutup` yang bukan hak pengguna tidak muncul di listing/pencarian/hitungan apa pun — bukan disembunyikan di Blade
- [ ] T5.6 — Policy yang menegakkan hal yang sama di titik akses langsung (URL langsung ke butir tertutup)
- [ ] T5.7 — Log akses: setiap buka butir tertutup dicatat siapa/kapan/IP/cara (layar atau cetak)
- [ ] T5.8 — Laporan butir tertutup tanpa pembaca aktif, tersedia untuk `admin` dan `ketua`

**Kriteria terima (uji eksplisit, tulis test untuk masing-masing):**
- [ ] Anggota di luar daftar TIDAK bisa menemukan butir tertutup lewat pencarian mana pun (coba beberapa jalur: listing, search bar, API/endpoint langsung)
- [ ] Menotulakan butir tertutup berjalan tanpa langkah tambahan bagi notulis sidang
- [ ] Membuka butir tertutup dua kali menghasilkan dua baris log

---

## Sprint 6 — Presensi & Kuorum (F1-G01–G04, Bagian 8)

- [ ] T6.1 — Pencatatan presensi manual oleh sekretaris; bawaan seluruh anggota `tanpa_keterangan`
- [ ] T6.2 — Peserta tanpa akun (`peserta_manual`): nama bebas + keterangan asal, bisa jadi penutur masukan, tidak ikut hitung kuorum
- [ ] T6.3 — Hitung kuorum live: `hadir + terlambat` dibanding ambang dari `pengaturan` (kuorum sesuai jenis sidang, dibulatkan sesuai `kuorum_pembulatan`); tampil terus-menerus selama sidang berjalan beserta angka ambangnya
- [ ] T6.4 — Kunci presensi begitu notula `sah`; perubahan sesudahnya hanya lewat adendum

**Kriteria terima:** 3 butir di Bagian 8 spesifikasi.

---

## Sprint 7 — Sidang Berjalan: Masukan & Penyebut `@` (F1-H01–H06, Bagian 9)

*Bergantung pada Sprint 5 (butir tertutup) dan Sprint 6 (presensi/status kehadiran untuk daftar `@`).*

- [ ] T7.1 — Sidang punya satu `butir_aktif_id`; hanya notulis yang memindahkannya; sekretaris pendamping bebas buka butir lain tanpa mengubah butir aktif
- [ ] T7.2 — Form masukan: penutur wajib tepat satu (anggota **atau** peserta manual — tegakkan lewat observer `saving`), jenis, isi (banyak poin), waktu otomatis, `induk_id` kosong untuk masukan utama
- [ ] T7.3 — Autocomplete `@`: livewire component, cari peserta sidang pakai `ILIKE`, tampilkan jabatan + status kehadiran; nama terpilih tersimpan sebagai `masukan_sebutan` (rujukan `user_id`, bukan teks)
- [ ] T7.4 — Penyebut pertama pada sebuah masukan menetapkan penuturnya
- [ ] T7.5 — Balasan satu tingkat saja (tolak balasan atas balasan di observer `saving`); tampil sebagai `Respon — <nama>`
- [ ] T7.6 — Autosave berkala tanpa tombol, dengan penanda waktu simpan terakhir
- [ ] T7.7 — Butir dadakan bisa ditambah ke artikel mana pun selagi sidang berjalan

**Kriteria terima:** 3 butir di Bagian 9 spesifikasi, termasuk uji `@har` → `Pnt. Haryanto` dalam satu ketukan.

---

## Sprint 8 — Mode Proyektor (F1-I01–I05, Bagian 10)

*Bergantung pada Sprint 5 dan Sprint 7.*

- [ ] T8.1 — Layout proyektor terpisah: sembunyikan navigasi/panel samping/tulang punggung artikel/tombol sunting; huruf besar; kotak pencatat tetap di dasar layar
- [ ] T8.2 — Penyaringan tayang: butir `tayang = jangan` tak ditampilkan; kalau butir aktif termasuk itu, tampilkan pemberitahuan saja tanpa memuat isinya
- [ ] T8.3 — Tanpa kotak dialog/alert/pesan galat yang menutupi layar — semua lewat pita tipis di tepi
- [ ] T8.4 — Isi baru dari sekretaris lain masuk tanpa memindah posisi gulir dan tanpa kedip (livewire `wire:poll`, jaga posisi scroll klien)
- [ ] T8.5 — Keluar lewat tombol atau tombol Esc

**Kriteria terima:** 3 butir di Bagian 10 spesifikasi — **uji eksplisit** butir tertutup tidak pernah tampil di mode proyektor dalam keadaan apa pun.

---

## Sprint 9 — Penyuntingan Dua Sekretaris & Kunci Optimistis (F1-J01–J05, Bagian 11)

- [ ] T9.1 — Tanpa penguncian catatan — pastikan tidak ada lock eksklusif di level aplikasi
- [ ] T9.2 — Penyegaran berkala tiap `selang_segar_detik` selama sidang berjalan (livewire polling, endpoint tidak melewati session berbasis berkas — pastikan `SESSION_DRIVER=database` benar dipakai di titik ini)
- [ ] T9.3 — Kunci optimistis: setiap baris (`sidang`, `agenda`, `masukan`, `masukan_poin`, `notula`) pakai kolom `versi`; penyimpanan membawa versi yang dibaca, kalau beda: tidak ditulis + 3 pilihan (pakai punya saya / pakai punya rekan / gabungkan tangan)
- [ ] T9.4 — Penanda kehadiran sunting: siapa sedang membuka butir mana, ditumpangkan pada penyegaran yang sama (`kehadiran_sunting`)

**Kriteria terima:** 3 butir di Bagian 11 spesifikasi — termasuk simulasi dua "pengguna" menyunting poin yang sama dan memverifikasi muncul pilihan penyelesaian, bukan penimpaan diam-diam.

---

## Sprint 10 — Notula: Daur Status & Pengesahan (F1-K01–K06, Bagian 12)

*Bergantung pada Sprint 6 (presensi terkunci) dan Sprint 9 (versi baris notula).*

- [ ] T10.1 — Daur status `draft → review → sah → adendum`, hanya sekretaris lihat saat `draft`
- [ ] T10.2 — Masa koreksi dihitung dari `diedarkan_at` selama `batas_koreksi_jam`; lewat itu, koreksi baru ditolak
- [ ] T10.3 — Koreksi sebagai komentar menempel pada butir (bukan sunting langsung); sekretaris tandai `diterima`/`ditolak` + tanggapan; koreksi diterima diterapkan manual oleh sekretaris
- [ ] T10.4 — Catatan pembacaan: siapa sudah buka notula dalam masa koreksi, daftar sederhana untuk sekretaris
- [ ] T10.5 — Pengesahan: 2 persetujuan berdiri sendiri (`ketua` dan `sekretaris`); setelah keduanya, kunci notula + presensi + seluruh masukan
- [ ] T10.6 — Adendum bernomor: apa yang berubah + alasan; notula asli tetap utuh

**Kriteria terima:** 3 butir di Bagian 12 spesifikasi.

---

## Sprint 11 — Cetak PDF (F1-L01–L04, Bagian 13)

- [ ] T11.1 — dompdf terpasang (`barryvdh/laravel-dompdf` sudah di-require Sprint 0); **tidak ada** dependency Browsershot/Puppeteer di `composer.json`
- [ ] T11.2 — Susunan halaman lengkap: kepala (kop, nomor, hari/tanggal, jam, tempat) → pejabat → rekap presensi+kuorum → daftar hadir (bersebutan, dikelompokkan MPH/MPL/undangan, saklar sembunyikan) → isi (artikel romawi, butir, masukan, balasan) → kaki (blok TTD + tanggal sah)
- [ ] T11.3 — Dua varian: Lengkap (judul asli + isi penuh, hanya bagi penerima akses) vs Tersunting (judul tampil saja, isi disembunyikan) — **verifikasi judul asli benar-benar tidak muncul di manapun termasuk daftar isi PDF**
- [ ] T11.4 — Penomoran halaman "halaman ke-n dari m"
- [ ] T11.5 — Watermark "DRAF" untuk notula berstatus `draft`

**Kriteria terima:** 3 butir di Bagian 13 spesifikasi. Uji performa (12 halaman < 10 detik) **ditunda ke Sprint 14** — hanya bisa diukur jujur di hosting sasaran.

---

## Sprint 12 — Penyusun Undangan WhatsApp (F1-M01–M04, Bagian 14)

- [ ] T12.1 — Template tersimpan & dapat disunting sekretaris (`wa_template` sudah ada), dengan pengganti sesuai F1-M01
- [ ] T12.2 — Daftar agenda otomatis: `umum`/`majelis`/pra-mpl → judul muncul; `tertutup` → **tidak muncul sama sekali**, termasuk tidak menyisakan nomor urut bolong
- [ ] T12.3 — Keluaran: teks siap tempel, tombol salin, tautan `wa.me` — tidak ada pengiriman otomatis
- [ ] T12.4 — Penanda "sudah dikirim ke grup" + waktunya → mengubah status sidang jadi `diedarkan`

**Kriteria terima:** 3 butir di Bagian 14 spesifikasi, termasuk pratinjau yang update tanpa reload saat judul butir disunting.

---

## Sprint 13 — Jejak Audit (F1-N01–N02, Bagian 15)

- [ ] T13.1 — Observer generik pencatat buat/sunting/hapus untuk: sidang, artikel, butir, masukan, presensi, notula, akun, `agenda_akses` — siapa/kapan/nilai sebelum-sesudah untuk bidang penting
- [ ] T13.2 — Log akses tertutup (Sprint 5) dipastikan terpisah dari jejak audit umum dan tidak ada rute/tombol penghapusannya di aplikasi

---

## Sprint 14 — Kesiapan Hosting & Deploy VPS (Bagian 16 spesifikasi) ⛔ GERBANG MANUSIA

**JANGAN MULAI SPRINT INI TANPA PENGGUNA HADIR DI PERCAKAPAN.** VPS sasaran juga menjalankan Database-Warga-GKJJ yang live — sesi otonom yang sampai di sini harus berhenti, menandai blocked, dan menunggu instruksi eksplisit, bukan jalan terus. Ini disepakati langsung dengan pengguna, bukan aturan yang saya buat sepihak.

**Verifikasi hosting wajib sebelum sprint ini dianggap selesai — jangan diasumsikan dari lingkungan lokal.**

- [ ] T14.1 — Cek nyata di VPS: versi PHP terpasang (≥8.2, pasang PPA `ondrej/php` kalau perlu), ekstensi `intl`/`gd`/`zip`/`pdo_pgsql`, akses cron
- [ ] T14.2 — Buat database `gkjj_notula` baru di server Postgres 16 yang sudah ada, role/user terpisah dari `gkjj_db` milik Database-Warga-GKJJ
- [ ] T14.3 — Nginx server block + subdomain baru untuk Notula (proxy ke php-fpm socket), php-fpm `pm.max_children` diset konservatif mengingat RAM 2GB dipakai bersama
- [ ] T14.4 — Pasang crontab (bukan cron cPanel): `schedule:run` tiap menit, `queue:work --stop-when-empty` tiap menit, backup `pg_dump` harian
- [ ] T14.5 — Build aset lokal, upload manual — pastikan tidak ada langkah `npm install`/`npm run build` di server
- [ ] T14.6 — Uji beban nyata: cetak PDF 12 halaman < 10 detik; polling 5 detik selama simulasi sidang tidak melanggar batas entry-process hosting (pantau bareng proses Warga app yang sudah jalan)

---

## Sprint 15 — UAT Penuh & Definisi Selesai (Bagian 17 & 19 spesifikasi) ⛔ GERBANG MANUSIA

**Sama seperti Sprint 14 — pengguna harus hadir.** Ini uji coba di hosting produksi, sebagian melibatkan sidang MPL nyata. Jangan dijalankan tanpa pengawasan.

Jalankan **di hosting sasaran sungguhan**, satu alur utuh, tanpa lompat langkah. Centang tiap langkah setelah nyata berhasil (nomor mengikuti tabel Skenario Uji Terima):

- [ ] T15.1 — Admin memasang data lengkap: profil gereja, periode, 48 anggota, unit, wilayah, nomor awal kedua deret
- [ ] T15.2 — Sekretaris membuat Sidang MPL baru, nomor terisi otomatis
- [ ] T15.3 — Susun tujuh artikel dari template, isi butir — nomor Romawi runtut
- [ ] T15.4 — Tandai satu butir `tertutup`, isi judul tampil, tunjuk tiga pembaca — notulis ikut otomatis jadi empat
- [ ] T15.5 — Susun undangan WhatsApp, salin, tandai sudah dikirim — sidang jadi `diedarkan`
- [ ] T15.6 — Anggota di luar daftar cari judul asli butir tertutup — tidak ditemukan
- [ ] T15.7 — Isi presensi 38 hadir — kuorum terpenuhi (ambang 36 dari 48)
- [ ] T15.8 — Mode proyektor, catat masukan dengan `@` — nama tersisip, terbaca dari belakang ruangan
- [ ] T15.9 — Sekretaris pendamping sunting butir lain bersamaan — tidak ada yang hilang, proyektor tidak berpindah
- [ ] T15.10 — Dua sekretaris sunting poin yang sama — muncul pilihan penyelesaian
- [ ] T15.11 — Sidang masuk butir tertutup — proyektor tidak tampilkan isinya, notulis tetap bisa mencatat
- [ ] T15.12 — Sidang selesai, notula diedarkan — masa koreksi berjalan
- [ ] T15.13 — Tanggapi koreksi, Ketua+Sekretaris mengesahkan — terkunci
- [ ] T15.14 — Cetak dua varian PDF — tersunting tidak memuat judul asli butir tertutup
- [ ] T15.15 — Coba ubah presensi setelah sah — ditolak, diarahkan ke adendum

**Definisi Selesai:** seluruh 15 langkah lulus di hosting sasaran + satu sidang MPL nyata sudah dijalankan penuh dengan notula disahkan dan tercetak.

---

## Backlog di luar Fase 1 (jangan dikerjakan kecuali diminta eksplisit)

Check-in mandiri QR, rekap kehadiran per periode, serah terima akses antar periode (Fase 2) · kesimpulan terstruktur, tindak lanjut otomatis, dasbor monitoring (Fase 3) · SK, petikan keputusan, portal peninjau (Fase 4) · keuangan, data jemaat, rekaman audio, notifikasi otomatis (di luar lingkup).
