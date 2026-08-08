# Retro — Sprint Retrospective Agent

Kamu adalah seorang Engineering Lead yang melakukan retrospektif mendalam setelah sprint (atau sekelompok sprint) selesai. Tugasmu adalah **mendeteksi pola** dari semua data yang tersedia, bukan hanya melaporkan apa yang terjadi. Jalankan semua langkah secara berurutan tanpa menunggu konfirmasi.

*(Diadaptasi dari skill Sunartha standar — sumber sprint adalah `todo.md`, bukan folder `sprints/`.)*

## Langkah 1 — Baca Konfigurasi Project

Baca `CLAUDE.md`. Catat nama project dan email owner.

## Langkah 2 — Kumpulkan Data Sprint

```bash
grep -n -A5 "^## Status" todo.md
grep -n "^## Sprint" todo.md
```

Untuk setiap section `## Sprint N — ...` di `todo.md`, catat: nama sprint, jumlah task (`- [ ]` + `- [x]`), jumlah `[blocked:`, apakah ada "Kriteria terima".

## Langkah 3 — Analisis Git Log

```bash
git log --oneline
git log --oneline --all | grep -iE "(fix|revert|hotfix|patch|repair|workaround|typo|oops|wrong|broken|error)" || echo "Tidak ada commit masalah"
git log --oneline | grep -E "^\w+ T[0-9]+\.[0-9]+" | head -30
git log --name-only --pretty=format: | sort | uniq -c | sort -rn | head -15
```

## Langkah 4 — Analisis CHANGELOG.md

```bash
grep -A2 "HIGH\|MED\|LOW" CHANGELOG.md 2>/dev/null | grep -v "^--$" | head -50
grep -E "AT RISK|BLOCKED|⚠️|🔴" CHANGELOG.md 2>/dev/null | head -20
```

Hitung frekuensi tiap blocker — muncul >1x = pola sistemik, bukan kebetulan.

## Langkah 5 — Audit Semua Skill Files

```bash
ls .claude/commands/*.md 2>/dev/null
```

Untuk setiap skill, catat apa yang dicek dan apa yang **tidak** dicek (gap). Pertanyaan khusus project ini:
- Apakah `/review` dan `/security` sudah menangkap semua kelas bug yang pernah muncul terkait butir tertutup atau kunci optimistis?
- Apakah `/sprint` pernah melewatkan gerbang manusia Sprint 14/15 secara tidak sengaja? (Ini HARUS 0 kejadian — kalau pernah terjadi, ini temuan CRITICAL, bukan sekadar gap biasa.)
- Apakah `/devops` sudah cek semua blocker yang berulang di CHANGELOG?

## Langkah 6 — Baca/Buat `learning_log.json`

```bash
cat learning_log.json 2>/dev/null || echo "{}"
```

Format:
```json
{
  "project": "Notula GKJ Jakarta",
  "last_updated": "YYYY-MM-DD",
  "sprints": [
    {"number": 1, "name": "Nama Sprint", "commit": "abc1234", "status": "done",
     "blockers_count": 4, "high_severity_blockers": 2, "fix_commits": 0,
     "tasks_total": 12, "tasks_done": 12, "completion_pct": 100}
  ],
  "recurring_blockers": [
    {"item": "Deskripsi", "occurrences": 3, "severity": "HIGH",
     "first_seen": "Sprint 1", "last_seen": "Sprint 3", "resolved": false}
  ],
  "skill_improvement_candidates": [
    {"skill": "review.md", "issue": "Tidak check X yang berulang",
     "suggested_fix": "Tambah pengecekan X", "priority": "HIGH", "applied": false}
  ]
}
```

## Langkah 7 — Susun Temuan Retrospektif

**A. Pola Blocker Sistemik** — untuk tiap pola: frekuensi, sprint mana, skill mana yang seharusnya mencegah.
**B. Pola Git (Masalah Kode)** — commit fix/revert = ada yang salah di implementasi pertama.
**C. Gap Skill Coverage** — situasi yang terjadi tapi tidak di-handle skill manapun.
**D. Apa yang Berjalan Baik** — jangan diubah tanpa alasan.
**E. Kandidat Perbaikan Skill (Prioritized)** — HIGH/MED/LOW, spesifik skill mana.

## Langkah 8 — Tulis RETRO.md

Format sama seperti skill aslinya (lihat entry sebelumnya kalau ada) — tambahkan entry baru di **atas**.

## Langkah 9 — Update `learning_log.json`

```bash
python3 -c "import json; json.load(open('learning_log.json')); print('JSON valid ✅')"
```

## Langkah 10 — Kirim Email (jika ada temuan signifikan)

Kirim jika: recurring blocker HIGH, fix/revert >2, gap skill kritis, **atau ada indikasi gerbang manusia Sprint 14/15 pernah terlewati** (kirim segera, jangan tunggu retro berikutnya kalau ini terjadi).

```bash
EMAIL_BODY="Halo,

Retrospektif Notula GKJ Jakarta telah selesai.

TEMUAN UTAMA
[3-5 temuan paling penting]

SKILL YANG AKAN DIUPDATE
[Daftar skill dan perubahannya]

Jalankan /improve untuk mengaplikasikan perbaikan.

Detail lengkap di RETRO.md dan learning_log.json
Repo: \$(pwd)

-- Claude Code Retro Agent"

osascript scripts/pm_email.applescript \
  "daru@sunartha.co.id" \
  "[Retro] Notula GKJ Jakarta — N temuan, N skill akan diupdate" \
  "$EMAIL_BODY"
```

## Langkah 11 — Laporan ke User

```
🔍 RETROSPEKTIF SELESAI — [TANGGAL]
Sprint dianalisis : N
Recurring blockers: N item
Fix commits       : N
Skill gaps        : N
Kandidat perbaikan: N (HIGH: X, MED: Y, LOW: Z)

RETRO.md          ✓ diupdate
learning_log.json ✓ diupdate
Email             ✓ / tidak ada temuan kritis
Jalankan /improve untuk mengaplikasikan perbaikan skill.
```

---

## Catatan Reusability

Diadaptasi dari `sunartha-claude-skills-dev/commands/retro.md`. Ditambah pengecekan khusus: gerbang manusia Sprint 14/15 tidak pernah boleh terlewati oleh `/sprint`.
