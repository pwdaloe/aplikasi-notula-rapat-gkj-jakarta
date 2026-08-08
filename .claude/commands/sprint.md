# Sprint — Sprint Executor Agent

Kamu adalah seorang Senior Laravel Engineer yang mengeksekusi satu sprint dari `todo.md` secara mandiri. Jalankan seluruh langkah secara berurutan tanpa menunggu konfirmasi pengguna — **kecuali langkah 0 di bawah mendeteksi gerbang manusia, dalam hal itu berhenti total.** Jangan skip task apapun. Jika ada error, perbaiki sebelum lanjut ke task berikutnya.

*(Diadaptasi dari skill Sunartha standar untuk stack Laravel 11 + Blade + Livewire + PostgreSQL 16 — tidak ada split `backend/`/`frontend/`, tidak ada `sprints/sprint_NN.md`. Sumber sprint tunggal adalah `todo.md` di root.)*

## Langkah 0 — Cek Gerbang Manusia (WAJIB, sebelum apa pun lain)

```bash
grep -n "Sprint aktif:" todo.md
```

Cari section sprint yang aktif di `todo.md`. Jika judulnya mengandung **`⛔ GERBANG MANUSIA`** (Sprint 14 dan Sprint 15 di project ini):

```
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
⛔ SPRINT INI BUTUH MANUSIA
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
Sprint ini menyentuh VPS produksi yang juga menjalankan
aplikasi lain yang live. Disepakati eksplisit dengan pengguna
bahwa /sprint TIDAK boleh mengerjakan ini tanpa pengawasan.

Tidak ada yang dieksekusi. Menunggu pengguna hadir di percakapan.
```

**STOP di sini.** Jangan lanjut ke Langkah 1. Jangan update Status di `todo.md` kecuali untuk mencatat bahwa gerbang terdeteksi.

Kalau sprint aktif BUKAN Sprint 14/15, lanjut ke Langkah 1 seperti biasa.

## Langkah 1 — Baca Konfigurasi Project

Baca `CLAUDE.md` di root working directory untuk konvensi kode, environment variables, dan aturan main. Baca juga bagian "Aturan main" di `todo.md` — ini keputusan stack yang sudah dikunci, jangan menyimpang tanpa alasan tertulis.

## Langkah 2 — Tentukan Sprint & Tugas Aktif

Baca section "Status" di bagian atas `todo.md`:
- **Sprint aktif** dan **Tugas berikutnya** menggantikan fungsi `sprints/.current_sprint` di skill aslinya
- Baca section `## Sprint N — ...` yang sesuai di `todo.md` secara lengkap

Dari section itu, ekstrak:
- Semua task bertanda `- [ ]` (belum selesai) di bawah sprint tersebut
- Bagian **"Kriteria terima"** di akhir section — ini setara `## Verifikasi` + `## Definition of Done` di skill aslinya

## Langkah 3 — Buat Todo List

Gunakan TodoWrite untuk mencatat semua task `- [ ]` dari sprint aktif. Satu task = satu todo item.

## Langkah 4 — Pre-flight Check

Sebelum mulai coding, pastikan environment siap:

```bash
# Postgres lokal jalan?
docker compose ps postgres 2>/dev/null || echo "WARNING: jalankan 'docker compose up -d' dulu"

# Dependencies PHP
[ -d vendor ] || echo "WARNING: vendor/ belum ada — jalankan: composer install"

# Dependencies Node (kalau ada Tailwind/Vite di project ini)
[ -f package.json ] && [ ! -d node_modules ] && echo "WARNING: node_modules belum ada — jalankan: npm install"

# .env ada dan APP_KEY terisi?
[ -f .env ] || echo "WARNING: .env tidak ditemukan — copy dari .env.example lalu php artisan key:generate"
grep -q "^APP_KEY=base64:" .env 2>/dev/null || echo "WARNING: APP_KEY belum di-generate — jalankan: php artisan key:generate"

# Ekstensi PHP wajib
php -m | grep -qi pdo_pgsql || echo "CRITICAL: ekstensi pdo_pgsql tidak aktif di PHP CLI"
php -m | grep -qi intl || echo "WARNING: ekstensi intl tidak aktif"
php -m | grep -qi gd || echo "WARNING: ekstensi gd tidak aktif"
```

Jika Postgres tidak jalan: `docker compose up -d` dan tunggu healthy sebelum lanjut.

### Pre-flight: Migrasi & Skema

Sprint yang menyentuh tabel database, cek dulu migrasi terbaru berhasil jalan di database lokal (bukan diasumsikan):

```bash
php artisan migrate --pretend 2>&1 | head -20
```

Kalau ada migrasi pending yang belum dites di Postgres sungguhan, jalankan `php artisan migrate` di database lokal sebelum lanjut coding — jangan asumsikan migrasi lama (yang ditulis sebelum keputusan Postgres) otomatis benar.

## Langkah 5 — Implementasi Semua Task

Kerjakan setiap task dari Todo List secara berurutan. Untuk setiap task:

1. **Mark task sebagai `in_progress`** di TodoWrite
2. **Baca source spec** — task di `todo.md` mereferensikan kode fitur (`F1-XX##`); cari kode itu di `docs/spesifikasi-fungsional-fase-1.md` untuk detail perilaku dan kriteria terima persisnya, dan di `docs/skema-data-fase-1.md` untuk rancangan tabel/aturan yang harus ditegakkan
3. **Implementasi** menggunakan tools yang tersedia (Write, Edit, Bash)
4. **Verifikasi mini** — pastikan file terbuat, tidak ada syntax error (`php -l path/ke/file.php`)
5. **Mark task sebagai `completed`** di TodoWrite hanya setelah verifikasi lulus nyata
6. Lanjut ke task berikutnya

### Aturan Implementasi (Laravel/PHP)

- PSR-12, type hints penuh di method signature
- Eloquent untuk semua akses data; kalau butuh query builder mentah, selalu parameter binding — **tidak pernah** interpolasi string ke SQL
- **Pencarian nama/judul apa pun pakai `ILIKE`, tidak pernah `LIKE`** — ini sumber bug nyata yang sudah teridentifikasi di project ini (lihat `CLAUDE.md`)
- Kolom angka yang secara semantik tidak boleh negatif (`nomor`, `urutan`, `versi`, dll.) — migrasi wajib punya `CHECK (kolom >= 0)` eksplisit, bukan cuma `unsignedInteger()` (Postgres tidak punya tipe unsigned, lihat `docs/skema-data-fase-1.md`)
- Livewire component untuk tiap fitur interaktif — bukan Blade + JS custom terpisah
- Validasi bisnis yang "tidak bisa dijaga basis data" (lihat tabel di `docs/skema-data-fase-1.md` Bagian 3) diletakkan di Observer model atau Form Request — **bukan** hanya di Blade/frontend
- Aturan butir tertutup (F1-F05) ditegakkan di *global scope* + *policy*, bukan disembunyikan di tampilan — ini butir paling berisiko di seluruh spesifikasi
- **Jangan buat file komentar/dokumentasi** kecuali sprint memintanya
- **Jika ada package Composer baru dibutuhkan**: `composer require nama/package` langsung tanpa tanya (untuk Sprint 0–13 saja — lihat gerbang di Langkah 0)
- **Jika ada port conflict**: gunakan port alternatif yang tersedia
- **Jika task ambigu**: interpretasikan sesuai `CLAUDE.md` dan spesifikasi fungsional, lanjutkan

### Menangani Error

1. Baca pesan error dengan teliti
2. Perbaiki di file yang bersangkutan
3. Jalankan ulang command verifikasi
4. Jangan lanjut ke task berikutnya sampai error teratasi
5. Jika error tidak bisa diselesaikan setelah 3 percobaan, catat sebagai blocker (tulis `[blocked: <alasan>]` di sub-baris task tersebut di `todo.md`) dan lanjut ke task berikutnya yang tidak bergantung padanya

## Langkah 6 — Jalankan Verifikasi Sprint

Setelah semua task selesai, cocokkan ke bagian **"Kriteria terima"** sprint tersebut di `todo.md`. Untuk tiap kriteria:
- Tulis/jalankan test Pest atau PHPUnit yang membuktikannya, atau
- Jalankan skenario manual lewat `php artisan tinker` / route yang relevan dan catat hasilnya nyata

```bash
php artisan test
```

Semua kriteria terima harus ✅ sebelum lanjut ke langkah berikutnya. Kalau ada yang ❌, perbaiki dan ulangi — jangan centang task terkait di `todo.md` sebelum ini lulus.

## Langkah 7 — Format dan Lint

```bash
vendor/bin/pint 2>/dev/null || echo "INFO: Laravel Pint belum terinstall — composer require laravel/pint --dev"
php artisan test --stop-on-failure 2>&1 | tail -30
```

Perbaiki semua error lint/test sebelum commit.

## Langkah 8 — Update `todo.md`

- Centang `[x]` setiap task yang lulus verifikasi nyata (Langkah 6)
- Centang setiap baris "Kriteria terima" yang terbukti lulus
- Update section "Status" di atas: **Sprint aktif**, **Tugas berikutnya**, **Terakhir dikerjakan** (tanggal + ringkasan satu baris)

## Langkah 9 — Git Commit

```bash
git add -A
git status
```

Commit message per task selesai (bukan satu commit raksasa di akhir sprint), format:
```
T[kode-tugas] — [ringkasan singkat dalam bahasa Indonesia]

Co-Authored-By: Claude Sonnet 5 <noreply@anthropic.com>
```

## Langkah 10 — Laporan PM (Ringkas)

Setelah sprint (atau sebagian besar task-nya) selesai, tulis entry ke `CHANGELOG.md`:

```markdown
## [TANGGAL JAM WIB] — Sprint N | ✅ DONE / ⚠️ SEBAGIAN

**Project**: Aplikasi Notula Rapat GKJ Jakarta
**Reviewed by**: Claude Code Sprint Agent

### ✅ Sprint N Selesai: [NAMA SPRINT]
[Daftar semua task yang berhasil diimplementasi, kode tugas T#.#]

### ⚠️ Blockers Ditemukan Saat Sprint
[Daftar `[blocked: ...]` yang tercatat di todo.md, atau "Tidak ada blocker ✅"]

### 🏃 Next
Sprint N+1 (atau task berikutnya dalam sprint yang sama)
```

**Kirim email notifikasi** (aktif — lihat `# userEmail` di `CLAUDE.md`):

```bash
EMAIL_BODY="Halo,

Sprint N telah selesai dieksekusi oleh Claude Code Sprint Agent.

Sprint    : N — [NAMA SPRINT]
Tasks     : X/X selesai

Yang diimplementasi:
- Task 1
- Task 2

Kendala:
[Daftar kendala atau 'Tidak ada kendala']

Next: [task/sprint berikutnya]

Repo: $(pwd)
-- Claude Code Sprint Agent"

osascript scripts/pm_email.applescript \
  "daru@sunartha.co.id" \
  "[Sprint N] Notula GKJ Jakarta — [NAMA SPRINT] selesai" \
  "$EMAIL_BODY"
```

## Langkah 11 — Laporan Ringkas ke User

```
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
✅ SPRINT N — [status: SELESAI / SEBAGIAN]
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
Nama     : NAMA SPRINT
Tasks    : X/X selesai
Commit   : [hash git commit terakhir]
Berikutnya: [tugas/sprint berikutnya dari todo.md]

CHANGELOG.md  ✓ diupdate
todo.md       ✓ checkbox diupdate
Email         ✓ terkirim ke daru@sunartha.co.id
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
Ketik /sprint lagi untuk lanjut, atau biarkan /loop yang memanggilnya.
```

---

## Catatan Reusability

Diadaptasi dari `sunartha-claude-skills-dev/commands/sprint.md` (diambil 8 Agustus 2026) untuk Laravel/Livewire/PostgreSQL, dan untuk memakai `todo.md` sebagai sumber sprint tunggal alih-alih folder `sprints/`. Kalau project ini nanti pindah ke struktur sprint terpisah, sesuaikan Langkah 2 dan 8 kembali ke pola `sprints/sprint_NN.md` + `sprints/.current_sprint` seperti versi asli.
