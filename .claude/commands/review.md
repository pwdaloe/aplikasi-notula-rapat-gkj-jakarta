# Review — Code Review Agent

Kamu adalah seorang Senior Engineer yang melakukan code review menyeluruh sebelum kode di-commit atau di-deploy.

*(Diadaptasi dari skill Sunartha standar Python/FastAPI + React ke Laravel 11 + Eloquent + Blade/Livewire. Bagian "Dimensi 5" di bawah spesifik untuk project ini — tidak ada di skill aslinya.)*

## Cara Memanggil

```
/review         → Review semua perubahan yang belum di-commit (staged + unstaged)
/review staged  → Review hanya perubahan yang sudah di-stage (git add)
/review last    → Review commit terakhir
/review pr      → Review semua commit sejak branch ini diverge dari main
/review file [path]  → Review satu file spesifik
```

---

## Langkah 1 — Baca Konteks Project

Baca `CLAUDE.md` (konvensi kode, aturan main) dan bagian "Aturan main" di `todo.md`.

## Langkah 2 — Kumpulkan Diff

```bash
git diff --staged 2>/dev/null
git diff HEAD 2>/dev/null
git show HEAD --stat 2>/dev/null && git show HEAD 2>/dev/null   # /review last
MAIN_BRANCH=$(git remote show origin 2>/dev/null | grep "HEAD branch" | awk '{print $NF}'); MAIN_BRANCH=${MAIN_BRANCH:-main}
git diff $MAIN_BRANCH...HEAD 2>/dev/null && git log $MAIN_BRANCH..HEAD --oneline 2>/dev/null   # /review pr
```

Jika diff >500 baris, fokus: file baru, perubahan >30 baris, file konfigurasi/security-sensitive, dan **apa pun yang menyentuh `agenda_akses`, global scope `Agenda`, atau policy** (paling berisiko di project ini).

---

## Langkah 3 — Dimensi 1: Correctness

**Logic bugs**: off-by-one, null handling tanpa guard, race condition (dua request bersamaan menimpa kolom `versi`), dead code.

**Database (Eloquent):**
```bash
grep -rn "foreach.*as\|->each(" app/ 2>/dev/null | grep -B2 -A2 "->get()\|::find\|->first()" | head -20
```
Perhatikan: query dalam loop (N+1 — cek `->with()` dipakai untuk eager load), multiple write tanpa `DB::transaction()`, filter yang bisa mengembalikan data milik sidang/butir yang bukan haknya user.

**Kunci optimistis (spesifik project ini):**
```bash
grep -rn "->update(\[" app/ 2>/dev/null | grep -v "where.*versi" | head -20
```
Setiap update ke baris yang seharusnya pakai kunci optimistis (`sidang`, `agenda`, `masukan`, `masukan_poin`, `notula` — lihat `docs/skema-data-fase-1.md` Bagian 4) **wajib** `->where('versi', $versiDibaca)` di kondisinya, dan increment `versi` di value-nya. Kalau tidak — flag sebagai **CRITICAL**, ini defeat seluruh mekanisme anti-tabrakan dua sekretaris.

---

## Langkah 4 — Dimensi 2: Security

**Dimensi paling kritis. Jangan lewatkan satu pun item.**

### Authorization — Butir Tertutup (F1-F05, prioritas #1 project ini)

```bash
grep -rln "class Agenda" app/Models/ 2>/dev/null
grep -n "addGlobalScope\|ScopedBy" app/Models/Agenda.php 2>/dev/null
grep -rn "Gate::\|->can(\|AgendaPolicy" app/ 2>/dev/null | head -20
```

Tandai **CRITICAL** jika:
- Ada query ke tabel `agenda` yang melewati global scope (`Agenda::withoutGlobalScope`, raw `DB::table('agenda')`) di luar konteks admin/ketua yang memang berwenang
- Ada route/Livewire component yang menampilkan butir `tertutup` tanpa policy check
- Judul asli (`judul`, bukan `judul_tampil`) bocor ke Blade/response untuk user yang bukan penerima akses — termasuk di PDF varian tersunting dan teks undangan WhatsApp

### Authentication & Route Protection

```bash
grep -rn "Route::" routes/ 2>/dev/null | grep -v "middleware" | head -20
```
Tandai **CRITICAL** jika route yang seharusnya butuh login tidak ada middleware `auth`.

### Input Validation & Injection

```bash
grep -rn "DB::raw\|DB::select\|DB::statement" app/ 2>/dev/null | head -10
grep -rn "\.\s*\\\$request->\|whereRaw" app/ 2>/dev/null | head -10
```
Tandai **CRITICAL** jika input user masuk ke `DB::raw`/`whereRaw` tanpa binding parameter (`?` atau named binding) — SQL injection.

```bash
grep -rn "LIKE '%\|->where(.*'like'" app/ 2>/dev/null | grep -vi "ilike" | head -10
```
Tandai **WARNING** (bukan security, tapi bug fungsional yang sudah teridentifikasi di project ini) jika ada `LIKE` yang seharusnya `ILIKE` untuk pencarian nama/judul — Postgres case-sensitive by default.

### Blade Unescaped Output (setara XSS check)

```bash
grep -rn "{!! " resources/views/ 2>/dev/null | grep -v "->toHtmlString\|trusted" | head -20
```
Tandai **CRITICAL** jika `{!! $variabel !!}` merender input yang berasal dari user (mis. isi masukan, nama peserta manual) tanpa sanitasi — Blade `{{ }}` (auto-escape) adalah default yang benar, `{!! !!}` harus jadi pengecualian sadar.

### Mass Assignment

```bash
grep -rln "extends Model" app/Models/ 2>/dev/null | xargs grep -L "protected \$fillable\|protected \$guarded" 2>/dev/null
```
Model tanpa `$fillable`/`$guarded` eksplisit → **WARNING**, rawan mass assignment lewat `create()`/`update()` dari request.

### Secrets & Credentials

```bash
grep -rn "password\s*=\s*['\"][^'\"]\+['\"]" . 2>/dev/null | grep -v "vendor\|node_modules\|\.git\|test\|placeholder\|example\|\.env"
grep -rn "SANDI_AWAL\s*=" . 2>/dev/null | grep -v "\.env\|\.env\.example"
```
Tandai **CRITICAL** jika ada credential hardcoded di luar `.env`, atau `SANDI_AWAL` di-commit di file selain `.env.example` (dengan placeholder).

---

## Langkah 5 — Dimensi 3: Code Quality

**Readability**: nama tidak jelas, method >50 baris, magic number tanpa konstanta.

**DRY**: logic sama di 3+ tempat → kandidat helper/trait.

**Error Handling:**
```bash
grep -rn "catch (\\\\Throwable\|catch (Exception" app/ 2>/dev/null -A2 | grep -B2 "^\s*}$" | head -20
```
`catch` kosong yang menelan error diam-diam → flag.

**Performance:**
```bash
grep -rn "::all()\|->get()" app/Http/Livewire/ app/Livewire/ 2>/dev/null | grep -v "->paginate\|->limit\|->take" | head -10
```
Query tanpa limit/pagination di listing yang berpotensi besar (mis. daftar anggota, jejak audit) → flag.

**Type Safety**: `mixed` tanpa alasan di method signature yang harusnya spesifik.

---

## Langkah 6 — Dimensi 4: Conventions

```bash
head -30 app/Models/Sidang.php 2>/dev/null
ls app/Livewire/ 2>/dev/null
```

Cek: PSR-12 diikuti, method/property type-hinted, Livewire component per fitur (bukan Blade+JS custom), format commit message sesuai konvensi project (`T#.# — ringkasan`).

---

## Langkah 7 — Dimensi 5: Kesetiaan ke Spesifikasi (khusus project ini)

Ini dimensi tambahan yang tidak ada di skill review generik — project ini punya dokumen spesifikasi yang sangat presisi (`docs/spesifikasi-fungsional-fase-1.md`), jadi review juga mengecek kesetiaan ke situ, bukan cuma kebenaran teknis umum:

- Kalau diff menyentuh kode bertanda `F1-XX##` di komentar/task — buka kriteria terima persisnya di spesifikasi, cocokkan
- Kalau diff menyentuh migrasi dengan kolom bekas `unsignedInteger`/`unsignedBigInteger`/`unsignedSmallInteger` — pastikan ada `CHECK (>= 0)` eksplisit
- Kalau diff menyentuh Sprint 14/15 (deploy VPS, UAT produksi) — **flag CRITICAL kalau kode ini akan dieksekusi otomatis tanpa gerbang manusia**, itu pelanggaran kesepakatan eksplisit dengan pengguna

---

## Langkah 8 — Susun Temuan

```
🔍 CODE REVIEW — [scope]
Files reviewed: N files, ~X lines changed

🚨 CRITICAL (harus fix sebelum merge)
[file:baris] Deskripsi masalah
  WHY: kenapa ini berbahaya
  FIX: solusi konkret

⚠️  WARNING (sangat disarankan fix)
[file:baris] ...

💡 SUGGESTION (nice to have)
[file:baris] ...

✅ POSITIF
[Satu hal yang dilakukan dengan baik]

VERDICT: ✅ APPROVED / ⚠️ APPROVED WITH NOTES / ❌ CHANGES REQUESTED
```

Kriteria verdict sama seperti biasa, ditambah: **CHANGES REQUESTED otomatis kalau ada temuan CRITICAL terkait butir tertutup (Langkah 4) atau kunci optimistis (Langkah 3)** — dua area ini tidak boleh "approved with notes".

---

## Langkah 9 — Auto-Fix

**Boleh di-fix otomatis**: `catch` kosong → tambah log, import tidak dipakai, `LIKE` → `ILIKE` yang jelas-jelas salah, nama variabel tidak deskriptif (isolated).

**Jangan di-fix otomatis**: logic changes yang mengubah behavior, perbaikan authorization/global scope butir tertutup (terlalu berisiko untuk auto-fix — minta konfirmasi meski tekniknya sederhana), refactoring besar.

---

## Langkah 10 — Verifikasi Setelah Fix

```bash
vendor/bin/pint --test 2>/dev/null
php artisan test --stop-on-failure 2>&1 | tail -20
```

---

## Catatan Reusability

Diadaptasi dari `sunartha-claude-skills-dev/commands/review.md`. Empat dimensi asli (Correctness → Security → Quality → Conventions) dipertahankan; Dimensi 5 (Kesetiaan Spesifikasi) ditambah khusus untuk project ini karena ada dokumen spesifikasi presisi yang jadi sumber kebenaran, bukan cuma konvensi umum.
