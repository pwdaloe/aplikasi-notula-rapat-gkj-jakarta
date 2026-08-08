# Aplikasi Sidang & Notula Majelis GKJ Jakarta

Aplikasi internal untuk menjalankan satu sidang MPL/MPH sepenuhnya di dalam sistem — dari penyiapan agenda sampai notula yang disahkan dan tercetak — tanpa Word dan tanpa menyalin ulang apa pun.

## Overview

Dipakai oleh Majelis Pekerja Lengkap (MPL) dan Majelis Pekerja Harian (MPH) Gereja Kristen Jawa Jakarta untuk: penyiapan sidang dengan kerangka artikel, butir agenda dengan dua sumbu pembatasan (level keterbacaan & ketertayangan), butir tertutup dengan akses per orang, presensi manual dan kuorum, pencatatan masukan real-time dengan penyebut `@`, mode proyektor, penyuntingan dua sekretaris tanpa saling menimpa, daur notula draft→review→sah→adendum, cetak PDF lengkap dan tersunting, dan penyusun undangan WhatsApp. Rincian lengkap ada di [`docs/spesifikasi-fungsional-fase-1.md`](docs/spesifikasi-fungsional-fase-1.md).

Ini Fase 1 dari rencana bertahap — lihat spesifikasi Bagian 1 untuk yang termasuk dan yang ditunda ke fase berikutnya.

## Tech Stack

| Layer | Technology | Versi |
|---|---|---|
| Framework | Laravel | 12 |
| UI | Blade + Livewire | 4 |
| Database | PostgreSQL | 16 |
| Cetak PDF | dompdf (`barryvdh/laravel-dompdf`) | 3 |
| Session/Queue/Cache | Driver `database` (bukan Redis) | — |
| Aset frontend | Vite + Tailwind CSS, di-build lokal | — |
| Hosting | VPS 2GB, dipakai bersama Database-Warga-GKJJ | — |

> Spesifikasi asli menulis Laravel 11 dan MySQL 8 — keduanya direvisi (Laravel 11 sudah *end-of-life*; database disesuaikan dengan yang sudah berjalan di VPS produksi). Lihat `CLAUDE.md` untuk daftar lengkap keputusan yang direvisi dan alasannya.

## Prasyarat

- PHP 8.2+ dengan ekstensi `pdo_pgsql`, `pgsql`, `intl`, `gd`, `zip`, `mbstring`
- Composer 2
- Docker (untuk PostgreSQL 16 lokal — lihat `docker-compose.yml`)
- Node.js + npm (build aset lokal — **tidak pernah dijalankan di server produksi**, lihat `docs/spesifikasi-fungsional-fase-1.md` Bagian 16)
- Akses cron (hanya untuk produksi/VPS, Sprint 14)

## Quick Start

```bash
git clone https://github.com/pwdaloe/aplikasi-notula-rapat-gkj-jakarta.git
cd aplikasi-notula-rapat-gkj-jakarta
composer install && npm install
cp .env.example .env   # lalu sesuaikan DB_* dan SANDI_AWAL — lihat tabel di bawah
php artisan key:generate
docker compose up -d   # Postgres 16 lokal, port 5437
php artisan migrate --seed
npm run build
php artisan serve
```

Tiga akun awal (lihat `database/seeders/PenggunaAwalSeeder.php`) dipaksa mengganti kata sandi pada login pertama.

## Struktur Folder

```
app/
├── Http/Controllers/
├── Models/
└── Providers/
database/
├── factories/
├── migrations/     ← skema kustom (bukan bawaan Laravel), lihat docs/skema-data-fase-1.md
└── seeders/
docs/
├── mockups/                                  ← mockup tata letak notula (HTML)
├── spesifikasi-fungsional-fase-1.md          ← sumber kebenaran perilaku & kriteria terima
├── skema-data-fase-1.md                      ← rancangan tabel & catatan migrasi
└── poin-diskusi-aplikasi-notula-majelis-gkj-jakarta.md
resources/views/
├── components/     ← Livewire single-file components (prefiks ⚡)
└── layouts/
```

## Environment Variables

| Variabel | Contoh | Wajib |
|---|---|---|
| `DB_CONNECTION` | `pgsql` | Ya |
| `DB_HOST` / `DB_PORT` | `127.0.0.1` / `5437` (lokal, lihat `docker-compose.yml`) | Ya |
| `DB_DATABASE` | `gkjj_notula` | Ya |
| `DB_USERNAME` / `DB_PASSWORD` | — | Ya |
| `SESSION_DRIVER` | `database` — **jangan `file`**, akan mengunci dan memblokir penyegaran berkala saat sidang berjalan | Ya |
| `QUEUE_CONNECTION` | `database` | Ya |
| `CACHE_STORE` | `database` | Ya |
| `APP_TIMEZONE` | `Asia/Jakarta` | Ya |
| `APP_LOCALE` | `id` | Ya |
| `SANDI_AWAL` | kata sandi awal 3 akun pertama (dev) | Ya, jangan commit |

Rincian lengkap dan alasan tiap pilihan: [`docs/skema-data-fase-1.md`](docs/skema-data-fase-1.md) Bagian 5.1.

## Dokumen Rujukan

- [`docs/spesifikasi-fungsional-fase-1.md`](docs/spesifikasi-fungsional-fase-1.md) — perilaku aplikasi & kriteria terima per fitur (kode `F1-XX##`)
- [`docs/skema-data-fase-1.md`](docs/skema-data-fase-1.md) — rancangan tabel, aturan yang harus ditegakkan aplikasi, catatan migrasi ke Postgres
- [`todo.md`](todo.md) — rencana kerja 16 sprint, status sprint aktif, gerbang manusia sebelum deploy produksi
- [`CLAUDE.md`](CLAUDE.md) — keputusan stack yang sudah dikunci dan konfigurasi skill development

## Testing

```bash
php artisan test
```

Test terhadap database Postgres **terpisah** dari dev (`gkjj_notula_test`, lihat `phpunit.xml`) — bukan SQLite bawaan Laravel, karena migrasi project ini memakai `CHECK` constraint Postgres-spesifik yang tidak jalan di SQLite.

## Deployment

Produksi berjalan di VPS 2GB yang **sama** dengan Database-Warga-GKJJ (bukan shared hosting cPanel) — database Postgres baru di server yang sudah ada, bukan mesin database kedua. Sprint 14 (kesiapan hosting) dan Sprint 15 (UAT produksi) di `todo.md` punya **gerbang manusia eksplisit**: sesi otonom (`/loop`, `/sprint`) berhenti dan menunggu pengguna hadir sebelum menyentuh VPS produksi.

## Skill Development

Project ini memakai skill Sunartha yang diadaptasi ke stack Laravel/Livewire/PostgreSQL — `/sprint`, `/pm`, `/devops`, `/qa`, `/review`, `/security`, `/docs`, `/release`, `/retro`, `/improve`, `/ux`. Lihat `.claude/commands/` dan bagian "Sunartha Claude Skills" di `CLAUDE.md`.
