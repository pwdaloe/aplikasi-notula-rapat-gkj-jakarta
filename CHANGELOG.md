# Changelog — PM Log
<!-- Dikelola otomatis oleh PM Agent. Jangan edit manual. -->

---

## [2026-08-08 09:45 WIB] — Sprint 0 | ✅ DONE

**Project**: Aplikasi Notula Rapat GKJ Jakarta
**Reviewed by**: Claude Code Sprint Agent

### ✅ Sprint 0 Selesai: Fondasi Proyek & Lingkungan
- T0.1 — git init, remote GitHub, commit awal
- T0.2 — scaffold Laravel **12** (dinaikkan dari 11 — EOL, lihat Blockers), digabung dengan `database/` kustom
- T0.3 — Livewire v4.3.5 + laravel-dompdf v3.1.2 terpasang
- T0.4 — Postgres 16 lokal via Docker (port 5437, hindari bentrok)
- T0.5 — `.env` terkonfigurasi, koneksi Postgres terverifikasi (`php artisan db:show`)
- T0.6 — `CHECK (>= 0)` eksplisit di 15 kolom bekas unsigned (Postgres tidak punya tipe unsigned)
- T0.7 — `migrate:fresh --seed` bersih dari nol; CHECK constraint terbukti menolak nilai negatif sungguhan
- T0.8 — Layout Livewire + route `Route::livewire('/', 'beranda')`, HTTP 200 terverifikasi. Ditemukan & diperbaiki: `.gitignore` bertingkat Laravel sempat tidak ikut ter-copy saat merge scaffold, cache runtime sempat ter-commit
- T0.9 — `php artisan test` lulus 4 test; `phpunit.xml` dialihkan ke database Postgres test terpisah (`gkjj_notula_test`) karena SQLite bawaan tidak kompatibel dengan CHECK constraint T0.6

### ⚠️ Blockers Ditemukan Saat Sprint
| Severity | Item | Resolusi |
|----------|------|----------|
| HIGH | Laravel 11 sudah EOL (rilis terakhir v11.55.0, 6 security advisory aktif) — Composer menolak instal | Dikonfirmasi ke pengguna, dinaikkan ke Laravel 12 (masih dipatch, upgrade major historisnya ringan) |
| MED | Port 5432 bentrok dengan container Postgres project lain di mesin dev | Dipindah ke port 5437 |
| MED | `.gitignore` bertingkat Laravel ikut terkecualikan saat merge scaffold (T0.2), cache runtime sempat ter-commit | Ditemukan saat T0.8, diperbaiki di commit terpisah |

Semua blocker teratasi — tidak ada yang terbawa ke Sprint 1.

### 🏃 Next
Sprint 1 — Autentikasi & Akun (F1-C01–C05)

---
