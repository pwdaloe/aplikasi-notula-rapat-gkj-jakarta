# Release — Versi & Changelog Manager

Kamu adalah Release Manager yang mengotomasi versioning, changelog, dan tagging. Semantic Versioning (SemVer) dan Conventional Commits.

*(Diadaptasi dari skill Sunartha standar: deteksi versi dari `composer.json`, bukan `package.json`/`pyproject.toml`.)*

## Cara Memanggil
```
/release [patch|minor|major|preview]
```
Tanpa argumen, tampilkan status release terkini lalu tanya versi apa yang ingin di-bump.

---

## Langkah 1 — Baca Konfigurasi Project

Baca `CLAUDE.md`: nama project, `# userEmail`.

## Langkah 2 — Deteksi Versi Saat Ini

```bash
cat composer.json 2>/dev/null | python3 -c "import sys,json; d=json.load(sys.stdin); print(d.get('version','0.0.0'))" 2>/dev/null
cat VERSION 2>/dev/null || echo "0.0.0"
```

Jika tidak ada file versi sama sekali, buat `VERSION` di root dengan isi `0.1.0`.

**Catatan project ini**: Fase 1 sendiri adalah unit rilis yang lebih besar dari SemVer biasa — lihat "Definisi Selesai" di `docs/spesifikasi-fungsional-fase-1.md` Bagian 19. `/release` cocok dipakai untuk milestone di dalam Fase 1 (mis. setelah Sprint 5 butir tertutup selesai dan stabil), bukan cuma di ujung Fase 1.

## Langkah 3 — Analisis Git Log Sejak Tag Terakhir

```bash
git describe --tags --abbrev=0 2>/dev/null || echo "(belum ada tag)"
git log $(git describe --tags --abbrev=0 2>/dev/null)..HEAD --oneline --no-merges 2>/dev/null || git log --oneline --no-merges -30
```

Kategorikan: **Breaking Changes** (`!` atau `BREAKING CHANGE:`), **Features** (`feat:`), **Fixes** (`fix:`), **Improvements** (`refactor:`, `perf:`, `chore:`, `docs:`). Di project ini juga kategorikan commit `T#.#` per sprint dari `todo.md`.

## Langkah 4 — Hitung Versi Baru

- `patch` → 0.1.0 → 0.1.1 — fix/chore
- `minor` → 0.1.0 → 0.2.0 — feat (biasanya = satu sprint selesai)
- `major` → 0.1.0 → 1.0.0 — breaking change, atau **Fase 1 dinyatakan selesai** (Sprint 15 lulus semua, lihat Definisi Selesai)
- `preview` → append `-preview.1`

Jika ada Breaking Changes tapi argumen bukan `major`, tampilkan peringatan.

## Langkah 5 — Update File Versi

```bash
# VERSION
echo "VERSI_BARU" > VERSION
```

Edit field `"version"` di `composer.json` kalau ada.

## Langkah 6 — Update CHANGELOG.md

```markdown
## [VERSI_BARU] — TANGGAL_HARI_INI

### Breaking Changes
- ...

### Fitur Baru
- ...

### Perbaikan
- ...

### Improvements
- ...
```

Hapus section kosong. Tambahkan di **atas** entry sebelumnya.

## Langkah 7 — Commit, Tag, Push

```bash
git add VERSION composer.json CHANGELOG.md 2>/dev/null
git commit -m "chore(release): bump version to VERSI_BARU"
git tag -a "vVERSI_BARU" -m "Release vVERSI_BARU"
git push && git push --tags
```

Jika push gagal (tidak ada remote), tampilkan perintah manual dan lanjutkan — jangan blokir.

## Langkah 8 — Generate Release Notes

`releases/RELEASE_NOTES_vVERSI.md`:

```markdown
# Release vVERSI_BARU — TANGGAL

## Ringkasan
[1-2 kalimat]

## Yang Baru
[fitur, tautkan ke sprint di todo.md]

## Perbaikan
[bug fix]

## Cara Upgrade
[instruksi migrasi kalau ada — mis. "jalankan php artisan migrate"]
```

## Langkah 9 — Kirim Notifikasi Email

```bash
osascript scripts/pm_email.applescript "daru@sunartha.co.id" "Release vVERSI_BARU — Notula GKJ Jakarta" "$(cat releases/RELEASE_NOTES_vVERSI_BARU.md | head -30)"
```

Jika `pm_email.applescript` tidak ada, skip tanpa error.

## Langkah 10 — Laporan Akhir

```
╔══════════════════════════════════════════╗
║         RELEASE SELESAI                  ║
╠══════════════════════════════════════════╣
║ Versi    : vLAMA → vBARU                 ║
║ Tag      : vVERSI_BARU                   ║
║ Commits  : N sejak tag sebelumnya        ║
╠══════════════════════════════════════════╣
║ CHANGELOG.md     ✓ Updated               ║
║ RELEASE_NOTES    ✓ releases/vVERSI.md    ║
║ Git Tag          ✓ Pushed                ║
║ Email            ✓ Terkirim              ║
╚══════════════════════════════════════════╝
```

---

## Catatan Reusability

Diadaptasi dari `sunartha-claude-skills-dev/commands/release.md`. Deteksi versi dari `composer.json` alih-alih `package.json`/`pyproject.toml`.
