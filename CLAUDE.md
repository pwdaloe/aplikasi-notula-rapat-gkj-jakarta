# Aplikasi Sidang & Notula Majelis GKJ Jakarta — Fase 1

Baca `todo.md` untuk tahu tugas berikutnya yang harus dikerjakan. Berkas ini hanya untuk konteks yang tidak berubah antar sesi.

## Dokumen rujukan (baca sebelum menebak apa pun)

- `docs/spesifikasi-fungsional-fase-1.md` — perilaku aplikasi & kriteria terima per fitur (kode `F1-XX##`)
- `docs/skema-data-fase-1.md` — rancangan tabel, aturan yang harus ditegakkan aplikasi, catatan migrasi ke Postgres
- `docs/poin-diskusi-aplikasi-notula-majelis-gkj-jakarta.md` — latar belakang diskusi awal
- `docs/mockups/` — mockup tata letak notula (HTML)

## Stack & keputusan yang sudah dikunci

Jangan mengubah ini tanpa keputusan eksplisit dari pengguna — semuanya hasil diskusi, bukan tebakan:

| Hal | Keputusan | Kenapa |
|---|---|---|
| Framework | Laravel 11, Blade + Livewire | Ditulis di spesifikasi asli |
| Database | **PostgreSQL 16** | VPS sudah menjalankan Postgres 16 untuk app sebelah (Database-Warga-GKJJ); direvisi dari MySQL 8 yang tertulis di spesifikasi v1.0 asli |
| Hosting | **VPS** 2GB (Domainesia), bareng Database-Warga-GKJJ, bukan shared hosting cPanel | Dikonfirmasi pengguna — repo https://github.com/pwdaloe/Database-Warga-GKJJ jalan di VPS yang sama |
| PDF | dompdf saja | Browsershot/Puppeteer dilarang eksplisit di spesifikasi (dan tetap berisiko RAM di VPS 2GB meski secara teknis sekarang boleh) |
| Session/Queue/Cache | Driver `database` | Hindari file-session locking yang memblokir polling saat sidang berjalan |
| Aset frontend | Build lokal, upload manual | Tanpa `npm` di server — dipertahankan meski VPS sebenarnya punya npm (app sebelah pakai itu), demi hemat RAM |
| Realtime | Tidak ada websocket, polling `selang_segar_detik` (bawaan 5 detik) | Sesuai spesifikasi |
| Pencarian | `ILIKE`, bukan `LIKE`, di seluruh aplikasi | Postgres case-sensitive by default, tidak seperti collation bawaan MySQL |
| Kolom angka non-negatif | `unsignedInteger` dkk di migrasi Laravel tidak berlaku benar di Postgres (tidak ada tipe unsigned) — tambahkan `CHECK (kolom >= 0)` eksplisit | Ditemukan saat tinjauan migrasi, lihat `docs/skema-data-fase-1.md` |
| `kuorum_mpl` | **3/4** (36 dari 48 anggota) | Dikonfirmasi pengguna, sesuai Tata Gereja/Tata Laksana — spesifikasi awal salah tulis 2/3 |
| `kuorum_mph` | 1/2 — masih dugaan, **belum dipastikan** | Menunggu rujukan Tata Laksana untuk MPH |
| Jumlah anggota majelis | 48 total | Dikonfirmasi pengguna |

## Berkas yang sudah ada dari sesi sebelumnya

`database/migrations/` (6 berkas) dan `database/seeders/` (5 berkas) sudah ditulis dan ditinjau — portabel ke Postgres tanpa perubahan kode besar (Eloquent/Schema builder murni, tanpa SQL mentah). Saat scaffold Laravel dijalankan (Sprint 0 di `todo.md`), berkas ini **dipindahkan ke dalam struktur hasil scaffold, bukan ditulis ulang**.

## Kontak & identitas proyek (bukan rahasia, tapi jangan salah rujuk)

- Gereja: Gereja Kristen Jawa Jakarta (GKJ Jakarta)
- Sidang MPL terakhir sebelum aplikasi dipakai: **ke-1027**, 7 Agustus 2026 — sidang berikutnya ke-1028
- Tiga akun awal: Administrator, Pnt. Jennie PS (sekretaris), Pnt. Heru (sekretaris)

## Akses (untuk Sprint 14 — belum dipakai sebelum itu)

- Kunci publik SSH pemilik project untuk akses VPS nanti: `ssh-ed25519 AAAAC3NzaC1lZDI1NTE5AAAAILtjx8Nj4JNJFfo21yyyzW4VBowbFGAhZCwcmICj6leV daru@sunartha.co.id`. Ini kunci publik, aman disimpan di repo — bukan rahasia. Dipakai untuk menambahkan akses SSH ke VPS sasaran saat Sprint 14 benar-benar dikerjakan bersama pengguna, bukan sebelumnya.

---

# Sunartha Claude Skills — konfigurasi

Project ini memakai skill standar Sunartha (`.claude/commands/`, diambil & diadaptasi dari `gitlab.sunartha.co.id/products/sunartha-claude-skills-dev` pada 8 Agustus 2026) untuk `/pm`, `/sprint`, `/devops`, `/qa`, `/review`, `/security`, `/docs`, `/release`, `/retro`, `/improve`, `/ux`. Field di bawah ini dibaca oleh skill-skill tersebut — jangan dihapus.

## Project Overview

Aplikasi Sidang & Notula Majelis GKJ Jakarta — Fase 1.
- **Stack**: Laravel 11 + Blade + Livewire + PostgreSQL 16
- **PDF**: dompdf (`barryvdh/laravel-dompdf`) — dilarang Browsershot/Puppeteer
- **Hosting**: VPS 2GB bareng Database-Warga-GKJJ, deploy manual (bukan Docker di produksi)
- **Dev lokal**: PostgreSQL 16 via `docker-compose.yml` (bukan Redis — project ini tidak pakai cache/queue eksternal, semua driver `database`)
- **Tidak ada split backend/frontend terpisah** — satu aplikasi Laravel monolith. Livewire component = "frontend" dan "backend" sekaligus (server-rendered, bukan SPA React/TS). Kalau skill mana pun menyebut `backend/` atau `frontend/`, itu keliru untuk project ini — sudah disesuaikan di file `.claude/commands/`.

## Source Documentation
- Spesifikasi & skema: `docs/spesifikasi-fungsional-fase-1.md`, `docs/skema-data-fase-1.md`, `docs/poin-diskusi-aplikasi-notula-majelis-gkj-jakarta.md`
- Mockup: `docs/mockups/`

## Sprint Tracker

**Beda dari skill aslinya**: project ini TIDAK memakai folder `sprints/sprint_NN.md` + `sprints/.current_sprint`. Sumber tunggal rencana kerja adalah **`todo.md`** di root — satu berkas berisi semua 16 sprint dengan checkbox, bagian "Status" di atasnya menggantikan fungsi `.current_sprint`. `.claude/commands/sprint.md`, `pm.md`, `retro.md` sudah diadaptasi untuk baca `todo.md`, bukan folder `sprints/`.

**Gerbang manusia wajib** (jangan pernah dilewati sesi otonom): sebelum Sprint 14 (deploy VPS) dan Sprint 15 (UAT produksi), lihat penanda ⛔ di `todo.md`. VPS sasaran juga menjalankan aplikasi lain yang live (Database-Warga-GKJJ) — disepakati eksplisit dengan pengguna bahwa dua sprint ini butuh kehadiran manusia, bukan `/loop` tanpa pengawasan.

## Konvensi Kode

- PHP 8.2+, ikuti PSR-12. Format & lint: `vendor/bin/pint` (Laravel Pint, bukan `ruff`)
- Eloquent untuk seluruh akses data — tidak ada raw SQL kecuali benar-benar perlu, dan kalau perlu, selalu parameter binding, tidak pernah interpolasi string
- Livewire component per fitur interaktif, bukan halaban Blade + JS terpisah
- **Pencarian nama/judul apa pun pakai `ILIKE`, tidak pernah `LIKE`** (Postgres case-sensitive by default) — lihat `docs/skema-data-fase-1.md`
- Kolom bekas `unsignedInteger`/`unsignedBigInteger`/`unsignedSmallInteger` wajib punya `CHECK (kolom >= 0)` eksplisit di migrasi — Postgres tidak punya tipe unsigned
- Test: Pest atau PHPUnit, jalankan `php artisan test`
- Package manager PHP: Composer. Aset frontend (kalau ada Tailwind/JS terkompilasi): `npm`, tapi **hanya dijalankan lokal**, tidak pernah di server produksi (lihat Batasan Teknis di spesifikasi)

## Environment Variables

Buat file `.env` di root project (contoh lengkap ada di `docs/skema-data-fase-1.md` Bagian 5.1):
```
DB_CONNECTION=pgsql
DB_DATABASE=gkjj_notula
SESSION_DRIVER=database
QUEUE_CONNECTION=database
CACHE_STORE=database
APP_TIMEZONE=Asia/Jakarta
APP_LOCALE=id
SANDI_AWAL=
```

## Docker Services (dev lokal saja — produksi tidak pakai Docker)

```bash
docker compose up -d   # menjalankan postgres:16 saja
```

## Penting

- **Bukan** "semua permission di-allow, tidak perlu tanya konfirmasi untuk operasi apapun" seperti bawaan template Sunartha. Project ini punya gerbang eksplisit di Sprint 14/15 (lihat di atas) — sesi otonom BOLEH jalan bebas untuk Sprint 0–13 (kode, test, commit lokal), tapi WAJIB berhenti dan minta manusia sebelum menyentuh VPS produksi.
- Kalau ada package Composer/npm yang belum terinstall untuk sprint yang sedang aktif (Sprint 0–13), install langsung tanpa tanya.
- Kalau ada port conflict di lokal, ganti ke port alternatif yang tersedia.

# userEmail
daru@sunartha.co.id

<!-- Catatan pengiriman email skill (/pm, /devops, /retro, /release, langkah akhir /sprint):
     Dikonfirmasi pengguna 8 Agustus 2026: kirim aktif ke alamat di atas,
     TANPA CC ke daftar tim Sunartha yang ada di scripts/pm_email.applescript versi asli
     (CC sudah dikosongkan di salinan project ini). -->
