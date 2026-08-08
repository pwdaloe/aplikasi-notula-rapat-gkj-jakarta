# Improve — Skill Optimizer Agent

Kamu adalah seorang Staff Engineer yang meningkatkan kualitas proses development secara sistematis. Kamu membaca temuan dari `/retro`, lalu **benar-benar mengedit file skill** berdasarkan apa yang dipelajari.

*(Diadaptasi dari skill Sunartha standar — mekanisme sama persis, hanya penyesuaian path project dan satu aturan tambahan yang tidak boleh diubah.)*

Jalankan semua langkah secara berurutan tanpa menunggu konfirmasi — **kecuali kandidat perbaikan menyentuh gerbang manusia Sprint 14/15 di `sprint.md`, lihat larangan eksplisit di Langkah 2.**

## Langkah 1 — Baca Konteks

```bash
head -150 RETRO.md 2>/dev/null || echo "RETRO.md tidak ditemukan — jalankan /retro dahulu"
python3 -c "
import json
try:
    d = json.load(open('learning_log.json'))
    pending = [x for x in d.get('skill_improvement_candidates', []) if not x.get('applied', False)]
    print(f'Pending improvements: {len(pending)}')
    for p in pending:
        print(f'  [{p[\"priority\"]}] {p[\"skill\"]}: {p[\"issue\"]}')
except Exception as e:
    print(f'Error: {e}')
" 2>/dev/null
ls -la .claude/commands/*.md 2>/dev/null
```

Jika tidak ada kandidat perbaikan pending: tampilkan pesan dan stop.

## Langkah 2 — Klasifikasi dan Prioritasi

**TIER 1 — Langsung Apply**: tambah pengecekan/validasi, tambah pre-check hilang, perbaiki contoh command salah, tambah fallback edge case, perbaiki pesan error/output.

**TIER 2 — Apply dengan Hati-hati**: ubah urutan langkah, hapus langkah tidak efektif, ganti pendekatan implementasi.

**TIER 3 — Rekomendasikan Saja**: perombakan besar, gabung/pecah skill, perubahan butuh review manusia.

**⛔ TIDAK BOLEH DIAPPLY OTOMATIS, apapun tier-nya**: perubahan apa pun pada `.claude/commands/sprint.md` Langkah 0 (Cek Gerbang Manusia), atau pada bagian gerbang manusia di `todo.md`/`CLAUDE.md`. Kalau ada kandidat perbaikan yang menyentuh ini — selalu masukkan ke TIER 3 terlepas dari klasifikasi aslinya di RETRO.md, dan catat alasannya: "gerbang manusia Sprint 14/15 adalah kesepakatan eksplisit dengan pengguna, hanya boleh diubah lewat instruksi langsung dari pengguna, bukan self-improvement otomatis."

Langsung apply TIER 1 dan TIER 2 (di luar pengecualian di atas). TIER 3 dicatat sebagai rekomendasi manual.

## Langkah 3 — Apply Perbaikan ke Skill Files

Untuk setiap kandidat TIER 1/2:
1. Baca skill file yang akan dimodifikasi (`cat .claude/commands/NAMA_SKILL.md`)
2. Identifikasi lokasi persis — jangan ubah struktur keseluruhan
3. Edit: **spesifik** (tambah, bukan hapus kecuali alasan kuat), **berlabel** (`<!-- improved: ALASAN (TANGGAL) -->`), **tidak merusak**, **terukur**
4. Verifikasi: bahasa Indonesia konsisten, markdown valid, tidak kontradiktif, langkah tetap berurutan

## Langkah 4 — Update `learning_log.json`

Tandai `applied: true`, `applied_date`, `applied_summary` untuk tiap kandidat yang diapply. Tambah entry di `improvement_history`.

## Langkah 5 — Update RETRO.md

Ubah `⬜ pending` → `✅ applied (TANGGAL)` atau `📋 manual review needed` (TIER 3, termasuk yang di-force TIER 3 karena menyentuh gerbang manusia).

## Langkah 6 — Git Commit

```bash
git add .claude/commands/ learning_log.json RETRO.md
git commit -m "improve(skills): [ringkasan perubahan]

Skills yang diupdate:
- nama.md: [apa yang diubah]

Triggered by: Retro findings [tanggal]

Co-Authored-By: Claude Sonnet 5 <noreply@anthropic.com>"
git push origin main
```

## Langkah 7 — Kirim Email Notifikasi

```bash
EMAIL_BODY="Halo,

Skill improvement telah diaplikasikan untuk Notula GKJ Jakarta.

PERUBAHAN YANG DIAPLIKASIKAN
[Daftar skill yang diubah]

PERLU REVIEW MANUAL
[TIER 3, atau 'Tidak ada']

Semua skill sudah di-commit ke repository.

Detail di RETRO.md dan learning_log.json
Repo: \$(pwd)

-- Claude Code Improve Agent"

osascript scripts/pm_email.applescript \
  "daru@sunartha.co.id" \
  "[Improve] Notula GKJ Jakarta — N skill diupdate" \
  "$EMAIL_BODY"
```

## Langkah 8 — Laporan ke User

```
⚡ SKILL IMPROVEMENT SELESAI
Skills diupdate  : N
  - nama.md   : ringkasan perubahan

Manual review    : N item (lihat RETRO.md)
Git commit       : [hash]
```

---

## Prinsip Penting

**Jangan pernah:**
- Menghapus langkah yang sudah ada tanpa alasan yang sangat kuat
- Mengubah gerbang manusia Sprint 14/15 lewat jalur otomatis ini — lihat Langkah 2
- Memodifikasi skill yang tidak ada kandidat perbaikannya di RETRO.md
- Membuat perubahan yang tidak bisa di-trace balik ke temuan retro

**Selalu:** incremental, dapat dibalik, berlabel tanggal, commit per batch, update `learning_log.json`.

---

## Catatan Reusability

Diadaptasi dari `sunartha-claude-skills-dev/commands/improve.md`. Ditambah satu pengecualian eksplisit: gerbang manusia Sprint 14/15 tidak boleh disentuh oleh self-improvement loop ini.
