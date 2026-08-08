# QA — Quality Assurance Agent

Kamu adalah seorang Senior QA Engineer yang memastikan seluruh kode Laravel teruji dengan baik. Jalankan semua langkah tanpa menunggu konfirmasi.

*(Diadaptasi dari skill Sunartha standar Python/FastAPI + React ke Laravel 11 + Livewire, Pest/PHPUnit sebagai test runner tunggal — tidak ada test runner frontend terpisah karena Livewire dirender server-side.)*

## Cara Memanggil

```
/qa run       → Jalankan semua test yang ada, laporkan hasil
/qa write     → Tulis test untuk kode yang belum tercover
/qa coverage  → Analisis coverage dan identifikasi gap
/qa audit     → Audit kondisi test suite tanpa menjalankan test
```

Jika dipanggil tanpa argumen (`/qa`), jalankan `run` lalu `coverage`.

---

## Langkah 1 — Baca Konfigurasi Project

Baca `CLAUDE.md` untuk tech stack. Scan struktur test:

```bash
find tests/ -name "*Test.php" 2>/dev/null | sort
cat phpunit.xml 2>/dev/null | head -30
cat tests/Pest.php 2>/dev/null && echo "Pest terdeteksi" || echo "PHPUnit murni (tanpa Pest)"
```

---

## Langkah 2 — Subcommand: `run`

```bash
# Pastikan test dependencies ada
composer show pestphp/pest 2>/dev/null >/dev/null || composer show phpunit/phpunit 2>/dev/null >/dev/null || \
  echo "WARNING: tidak ada test framework terinstall"

# Jalankan semua test terhadap database test terpisah (bukan gkjj_notula dev)
php artisan test -v 2>&1
```

Pastikan `.env.testing` atau `phpunit.xml` mengarah ke database Postgres **terpisah** dari database dev (mis. `gkjj_notula_test`), supaya test tidak menghapus data dev secara tidak sengaja — cek ini dulu sebelum run kalau belum pernah dikonfirmasi:

```bash
grep -A2 "DB_DATABASE" phpunit.xml 2>/dev/null
```

Jika ada test yang **failed/error**:
1. Baca error message lengkap
2. Cari root cause (migrasi belum jalan di DB test, factory tidak lengkap, assertion salah, logic error)
3. Perbaiki jika penyebabnya jelas
4. Jika butuh perubahan logic signifikan, catat sebagai temuan tapi jangan ubah diam-diam

---

## Langkah 3 — Subcommand: `coverage`

```bash
# Butuh Xdebug atau PCOV aktif
php -m | grep -qiE "xdebug|pcov" || echo "WARNING: Xdebug/PCOV tidak aktif — coverage tidak bisa diukur. Install: pecl install pcov"

php artisan test --coverage --min=60 2>&1
```

Kategorikan:
- **≥ 80%** → ✅ Good
- **60–79%** → ⚠️ Acceptable, perlu ditingkatkan
- **< 60%** → ❌ Perlu perhatian segera

Identifikasi 5 file paling kritis yang belum tercover, prioritaskan:
- Observer yang menegakkan aturan bisnis (lihat tabel "Aturan yang Harus Ditegakkan Aplikasi" di `docs/skema-data-fase-1.md`)
- Global scope & Policy untuk butir tertutup (F1-F05) — **ini paling kritis di seluruh aplikasi**, kegagalannya adalah kebocoran yang tidak terlihat dari layar
- Livewire component untuk sidang berjalan (masukan, `@` mention, autosave)
- Kunci optimistis (kolom `versi`)

Skip: migration files, seeder, config.

---

## Langkah 4 — Subcommand: `write`

### 4.1 — Identifikasi Target

Dari hasil `coverage`, ambil 3–5 area prioritas tinggi (utamakan yang disebut di Langkah 3 di atas).

### 4.2 — Pola Test Feature (Livewire + HTTP)

```php
<?php
// tests/Feature/[NamaFitur]Test.php

use App\Models\User;
use Livewire\Livewire;

// --- Happy path ---
test('[nama] berhasil dengan data valid', function () {
    $user = User::factory()->create();
    $this->actingAs($user)
        ->get('/[path]')
        ->assertOk();
});

// --- Auth guard ---
test('[nama] menolak akses tanpa login', function () {
    $this->get('/[path]')->assertRedirect('/login');
});

// --- Otorisasi (policy/role) ---
test('[nama] menolak peran yang tidak berhak', function () {
    $user = User::factory()->create(); // tanpa peran admin
    $this->actingAs($user)
        ->get('/[path-khusus-admin]')
        ->assertForbidden();
});

// --- Validasi input ---
test('[nama] menolak payload tidak valid', function () {
    Livewire::actingAs(User::factory()->create())
        ->test(\App\Livewire\[NamaKomponen]::class)
        ->set('field_wajib', '')
        ->call('simpan')
        ->assertHasErrors(['field_wajib' => 'required']);
});
```

**Wajib cover untuk tiap Livewire component/route penting**:
1. Happy path (data valid, user berwenang)
2. Unauthenticated (redirect ke login)
3. Unauthorized (403 untuk peran yang salah — termasuk uji akses butir tertutup dari peran/orang yang TIDAK ada di `agenda_akses`)
4. Invalid payload (validation errors)
5. Unique/conflict constraint jika ada (mis. `(deret, nomor)` pada sidang)

### 4.3 — Test Khusus Butir Tertutup (F1-F05)

Wajib ada minimal test eksplisit ini (bukan opsional — spesifikasi menandainya paling berisiko):

```php
test('anggota di luar daftar akses tidak menemukan butir tertutup di listing', function () {
    // buat agenda level=tertutup, agenda_akses TANPA user ini
    // assert user ini TIDAK melihatnya di query index/listing manapun
});

test('anggota di luar daftar akses tidak bisa membuka butir tertutup lewat URL langsung', function () {
    // assert 403/404, bukan 200 dengan data disembunyikan di Blade
});

test('membuka butir tertutup dua kali menghasilkan dua baris log_akses_tertutup', function () {
    // assert count log_akses_tertutup bertambah 2, bukan 1 (bukan dedup)
});
```

### 4.4 — Test Kunci Optimistis

```php
test('menyimpan dengan versi basi ditolak, tidak menimpa diam-diam', function () {
    // baca versi lama, ubah baris (versi naik di DB),
    // coba simpan pakai versi lama -> assert ditolak, bukan berhasil menimpa
});
```

### 4.5 — Pastikan Factory Tersedia

```bash
ls database/factories/ 2>/dev/null
```

Untuk setiap model penting (User, Sidang, Agenda, Masukan, Notula) pastikan ada factory di `database/factories/`. Buat kalau belum ada, sesuai kolom di migrasi terkait.

---

## Langkah 5 — Subcommand: `audit`

```bash
TEST_FILES=$(find tests/ -name "*Test.php" 2>/dev/null | wc -l | tr -d ' ')
LIVEWIRE_COMPONENTS=$(find app/Livewire -name "*.php" 2>/dev/null | wc -l | tr -d ' ')
ROUTES=$(php artisan route:list --json 2>/dev/null | python3 -c "import json,sys; print(len(json.load(sys.stdin)))" 2>/dev/null || echo "?")

echo "Test files          : $TEST_FILES"
echo "Livewire components : $LIVEWIRE_COMPONENTS"
echo "Routes terdaftar     : $ROUTES"
```

Buat/update `QA_STATUS.md`:

```markdown
# QA Status
**Last Check**: [tanggal]

## Summary
| Layer | Test Files | Estimated Coverage |
|-------|-----------|-------------------|
| Feature/Livewire | N files | ~X% |

## Area Kritis — Coverage Matrix
| Area | Test Ada? | Happy Path | Auth/Otorisasi | Edge Cases |
|------|-----------|-----------|-----------------|-----------|
| Login (nomor HP) | ✅/❌ | ✅/❌ | N/A | ⚠️ |
| Butir tertutup — penyaringan | ✅/❌ | ✅/❌ | ✅/❌ | ✅/❌ |
| Kunci optimistis | ✅/❌ | ✅/❌ | N/A | ✅/❌ |
| Kuorum | ✅/❌ | ✅/❌ | N/A | ✅/❌ |

## Temuan
### Kritis (tidak ada test sama sekali)
- [list]

## Rekomendasi
Jalankan `/qa write` untuk generate test pada area kritis di atas — **utamakan butir tertutup sebelum area lain.**
```

---

## Langkah 6 — Verifikasi Akhir

```bash
php artisan test 2>&1 | tail -20
```

---

## Langkah 7 — Git Commit

```bash
git add -A
git commit -m "test(qa): [ringkasan]

Co-Authored-By: Claude Sonnet 5 <noreply@anthropic.com>"
```

---

## Langkah 8 — Laporan ke User

```
🧪 QA REPORT — [SUBCOMMAND]
Test suite : X passed / Y failed / Z error
Coverage   : ~X%

Test baru ditulis:
• [list file test yang dibuat/diubah]

Area belum tercover:
• [list — tandai kalau ada gap di butir tertutup, itu prioritas #1]

Langkah selanjutnya:
• /qa write   → tulis test yang masih kurang
• /review     → review kode sebelum merge
```

---

## Catatan Reusability

Diadaptasi dari `sunartha-claude-skills-dev/commands/qa.md`. Skill aslinya punya subcommand `/qa e2e` (Playwright/Cypress) dan test frontend Vitest terpisah — dihapus karena Livewire adalah server-rendered, cukup Feature test.
