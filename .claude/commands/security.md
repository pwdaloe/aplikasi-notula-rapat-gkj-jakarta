# Security — Audit Keamanan Aplikasi

Kamu adalah Security Engineer yang mengaudit keamanan aplikasi Laravel ini. Kamu hanya melakukan defensive security analysis — tidak membuat exploit, tidak menyerang sistem eksternal.

*(Diadaptasi dari skill Sunartha standar Python/FastAPI ke Laravel 11 + Eloquent + Blade/Livewire.)*

## Cara Memanggil
```
/security [quick|full|deps|fix]
```
- `quick` — Scan cepat: secrets, auth, butir tertutup (5 menit)
- `full` — Audit lengkap semua kategori (default)
- `deps` — Hanya cek dependency vulnerabilities
- `fix` — Jalankan `full` lalu auto-fix temuan SAFE

---

## Langkah 1 — Baca Konfigurasi Project

Baca `CLAUDE.md`. Catat: ini aplikasi internal majelis gereja (bukan publik), data yang dilindungi termasuk isi rapat tertutup (perkara penggembalaan dsb.) — sensitivitasnya tinggi meski skalanya kecil.

## Langkah 2 — Audit Secrets & Konfigurasi

### 2a. Hardcoded Secrets

```bash
grep -rn --include="*.php" \
  -E "(password|secret|api_key|apikey|token)\s*=\s*['\"][^'\"]{8,}" \
  . --exclude-dir=vendor --exclude-dir=node_modules --exclude-dir=.git 2>/dev/null | \
  grep -v "test\|placeholder\|example" | head -50
```

### 2b. `.env` dan `.gitignore`

```bash
git ls-files .env 2>/dev/null && echo "PERINGATAN: .env tracked by git!"
grep "\.env" .gitignore 2>/dev/null || echo "PERINGATAN: .env tidak ada di .gitignore"
ls .env.example 2>/dev/null || echo "INFO: .env.example tidak ada"
git ls-files .env.example 2>/dev/null | xargs grep -l "SANDI_AWAL=.\+" 2>/dev/null && echo "PERINGATAN: .env.example berisi nilai SANDI_AWAL nyata, bukan placeholder"
```

### 2c. Validasi Config

Baca `config/session.php`, `config/queue.php`, `config/cache.php` — pastikan driver `database` sesuai `CLAUDE.md`, bukan default `file`/`sync` yang lolos tanpa disadari.

```bash
grep "APP_KEY" .env 2>/dev/null | grep -v "base64:.\{20,\}" && echo "CRITICAL: APP_KEY belum di-generate atau lemah"
```

---

## Langkah 3 — Audit Authentication & Authorization

Baca `app/Http/Controllers/Auth/` atau `app/Livewire/Auth/` (login berbasis nomor HP, F1-C01–C05).

**Periksa:**

| Check | Aman | Tidak Aman |
|-------|------|------------|
| Password hashing | `Hash::make()` (bcrypt/argon2, default Laravel) | md5/sha1/plaintext |
| Rate limit percobaan login | Ada throttle 5x → kunci 15 menit (F1-C05) | Tidak ada |
| Tautan pemulihan | Token acak, kadaluarsa 24 jam, hangus setelah dipakai (F1-C04) | Token bisa ditebak/reusable |
| Normalisasi nomor HP | `08…`/`62…` disamakan **sebelum** query, bukan dua kolom terpisah | Bisa duplikat akun |

**Endpoint/route tanpa auth:**
```bash
grep -B2 -A5 "Route::" routes/web.php 2>/dev/null | grep -v "middleware.*auth\|Route::get('/login'\|Route::post('/login'"
```

**Butir tertutup — ini bukan authorization generik, ini fitur inti keamanan aplikasi (F1-F05):**
```bash
grep -rn "addGlobalScope\|ScopedBy" app/Models/Agenda.php 2>/dev/null
find app/Policies -iname "*Agenda*" 2>/dev/null
```
Kalau tidak ada global scope ATAU tidak ada policy untuk model `Agenda` — **CRITICAL**, ini satu-satunya lapis yang mencegah anggota biasa membaca perkara penggembalaan/disipliner yang bukan haknya.

---

## Langkah 4 — Audit Input Validation & Injection

### 4a. SQL Injection

```bash
grep -rn --include="*.php" \
  -E "DB::raw\(.*\\\$|DB::statement\(.*\\\$|whereRaw\(.*\\\$" \
  app/ 2>/dev/null | grep -v "\?" | head -20
```
Kalau memakai Eloquent/query builder standar dengan parameter binding, ini aman. Flag hanya raw string interpolation ke SQL.

### 4b. Path Traversal (unggah lampiran, F1-E03)

```bash
grep -rn "Storage::\|->store(\|->storeAs(" app/ 2>/dev/null | grep -i "lampiran\|attachment"
```
Pastikan nama file asli tidak dipakai langsung sebagai path, dan validasi jenis file (whitelist dari `pengaturan.jenis_lampiran`) dicek di server, bukan cuma `accept=""` di HTML.

### 4c. XSS di Blade

```bash
grep -rn "{!! " resources/views/ 2>/dev/null | grep -v "->toHtmlString"
```

### 4d. Mass Assignment

```bash
grep -rln "extends Model" app/Models/ 2>/dev/null | xargs grep -L "protected \$fillable\|protected \$guarded" 2>/dev/null
```

### 4e. Validasi Request

Livewire component dengan `#[Rule]`/`validate()` untuk tiap input dari user — cek terutama form masukan (F1-H02) dan form akun (F1-C02) yang menerima banyak field.

---

## Langkah 5 — Audit CSRF & Session

Laravel punya proteksi CSRF bawaan — cek tidak ada yang menonaktifkannya:

```bash
grep -rn "VerifyCsrfToken\|withoutMiddleware.*csrf" app/Http/ bootstrap/ 2>/dev/null
```

**Session (khusus project ini):**
```bash
grep "SESSION_DRIVER" .env 2>/dev/null
```
Harus `database`. Kalau `file` — **CRITICAL fungsional** (bukan cuma security): akan mengunci dan memblokir penyegaran berkala saat sidang berjalan bersamaan dua sekretaris.

---

## Langkah 6 — Audit Dependencies

```bash
composer audit 2>&1 | head -40
```

Cek juga dependency yang security-critical dikunci versinya (bukan `*` atau range terlalu lebar): `laravel/framework`, `barryvdh/laravel-dompdf`, `livewire/livewire`.

```bash
[ -f package.json ] && cat package.json | python3 -c "
import sys, json
d = json.load(sys.stdin)
deps = {**d.get('dependencies',{}), **d.get('devDependencies',{})}
for k,v in sorted(deps.items()):
    print(f'{k}: {v}')
" 2>/dev/null
```

---

## Langkah 7 — Audit Rate Limiting

```bash
grep -rn "RateLimiter::\|throttle:" app/ routes/ 2>/dev/null | head -10
```

Endpoint login dan pemulihan kata sandi wajib punya rate limit (F1-C05 sudah mensyaratkan ini secara fungsional, bukan cuma security best-practice). Flag **WARNING** kalau tidak ada.

---

## Langkah 8 — Generate Laporan

Buat `SECURITY_AUDIT.md`:

```markdown
# Security Audit Report — Notula GKJ Jakarta

**Tanggal:** TANGGAL_HARI_INI
**Scope:** [quick/full/deps]

## Ringkasan Temuan
| Severity | Jumlah |
|----------|--------|
| 🔴 CRITICAL | N |
| 🟠 WARNING  | N |
| 🟡 INFO     | N |
| ✅ PASSED   | N |

## Temuan Detail

### 🔴 CRITICAL
#### [Judul]
- **File:** `path:LINE`
- **Masalah:** ...
- **Risiko:** ... (kalau terkait butir tertutup, tegaskan: ini kebocoran perkara gerejawi, bukan sekadar bug)
- **Rekomendasi:** ...

### 🟠 WARNING / 🟡 INFO / ✅ PASSED
[sama seperti di atas]

## Quick Wins
1. [Paling kritis dulu — butir tertutup dan session driver selalu diprioritaskan di atas temuan generik]

---
*Report ini dibuat otomatis. Lakukan review manual untuk audit yang lebih mendalam, terutama sebelum Sprint 14 (deploy produksi).*
```

---

## Subcommand: `fix`

**BOLEH auto-fix:** tambahkan `.env` ke `.gitignore`, buat `.env.example` dari `.env` (dengan nilai secret dikosongkan), hapus `{!! !!}` yang nilainya statis (bukan dari user input).

**JANGAN auto-fix:** logika auth/authorization, global scope/policy butir tertutup, query database, perubahan schema. Semua ini butuh review manusia meski fix-nya kelihatan sederhana.

---

## Langkah Akhir — Laporan Terminal

```
╔══════════════════════════════════════════╗
║      SECURITY AUDIT SELESAI               ║
╠══════════════════════════════════════════╣
║ 🔴 CRITICAL : N                          ║
║ 🟠 WARNING  : N                          ║
║ 🟡 INFO     : N                          ║
║ ✅ PASSED   : N                          ║
╠══════════════════════════════════════════╣
║ Report : SECURITY_AUDIT.md               ║
╚══════════════════════════════════════════╝

⚠️  Kalau ada CRITICAL terkait butir tertutup atau session driver,
    prioritaskan sebelum sprint lain lanjut — bukan sebelum "release".
```

---

## Catatan Reusability

Diadaptasi dari `sunartha-claude-skills-dev/commands/security.md`. Ditambah pengecekan spesifik project: global scope/policy butir tertutup (F1-F05) dan session driver `database` — dua hal yang kegagalannya bukan cuma "security issue" generik tapi pelanggaran langsung terhadap janji fungsional aplikasi.
