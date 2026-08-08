# DevOps — Infrastructure & Environment Agent

Kamu adalah seorang DevOps Engineer yang melakukan health check menyeluruh terhadap infrastruktur project ini. Jalankan semua pengecekan secara sistematis, catat hasilnya, alert kalau ada masalah kritis.

*(Diadaptasi dari skill Sunartha standar: tanpa Redis, tanpa split backend/frontend, Postgres bukan lewat `docker-compose.yml` di produksi — hanya untuk dev lokal.)*

## Langkah 1 — Baca Konfigurasi Project

Baca `CLAUDE.md`: nama project, `# userEmail`, tech stack, Docker services (dev lokal saja).

## Langkah 2 — Cek Postgres Lokal (Docker)

```bash
docker compose ps postgres 2>/dev/null || docker ps --format "table {{.Names}}\t{{.Status}}\t{{.Ports}}" 2>/dev/null | grep postgres
```

- ✅ `healthy`/`Up` — normal
- ⚠️ `starting` — tunggu
- ❌ `Exit`/`unhealthy`/`restarting` — coba restart: `docker compose up -d postgres`, `sleep 5`, cek ulang

**Catatan**: ini HANYA untuk dev lokal. Produksi (VPS, Sprint 14+) tidak pakai `docker-compose.yml` — Postgres jalan native di VPS, dan pengecekan produksi tidak pernah dilakukan otomatis oleh skill ini (lihat gerbang manusia di `CLAUDE.md`).

## Langkah 3 — Cek Konektivitas Database

```bash
DB_PORT=$(grep -A5 "postgres:" docker-compose.yml 2>/dev/null | grep -o '"\([0-9]*\):5432"' | cut -d'"' -f2 | cut -d: -f1)
DB_PORT=${DB_PORT:-5432}

pg_isready -h localhost -p $DB_PORT 2>/dev/null || \
  docker compose exec postgres pg_isready 2>/dev/null || \
  echo "WARNING: PostgreSQL tidak bisa direach di port $DB_PORT"
```

**Laravel app (server dev):**
```bash
curl -sf http://localhost:8000/up 2>/dev/null && echo "Laravel health route OK ✅" || \
  echo "INFO: 'php artisan serve' tidak running, atau route /up (Laravel 11 health check bawaan) belum ada — normal kalau belum distart manual"
```

## Langkah 4 — Cek Port Conflicts

```bash
grep -E "^\s+- \"[0-9]+:" docker-compose.yml 2>/dev/null | grep -o '[0-9]*:[0-9]*' | while read mapping; do
  host_port=$(echo $mapping | cut -d: -f1)
  owner=$(lsof -ti:$host_port 2>/dev/null | head -1)
  if [ -n "$owner" ]; then
    process=$(ps -p $owner -o comm= 2>/dev/null)
    echo "Port $host_port: IN USE by $process (PID $owner)"
  else
    echo "Port $host_port: FREE"
  fi
done

# Port default php artisan serve
lsof -ti:8000 2>/dev/null && echo "Port 8000: IN USE" || echo "Port 8000: FREE"
```

## Langkah 5 — Validasi Environment Variables

```bash
if [ ! -f .env ]; then
  echo "CRITICAL: File .env tidak ditemukan!"
else
  echo "--- Kosong (blocker) ---"
  grep -E "^[A-Z_]+=\s*$" .env 2>/dev/null || echo "Tidak ada yang kosong ✅"
  echo "--- Terisi ---"
  grep -E "^[A-Z_]+=.+" .env 2>/dev/null | sed 's/=.*/=***/' || echo "Tidak ada"
fi
```

Severity untuk variabel kosong (spesifik project ini):
- `HIGH`: `DB_CONNECTION`, `DB_DATABASE`, `DB_HOST`, `APP_KEY`
- `HIGH`: `SESSION_DRIVER` kalau nilainya bukan `database` — file-session akan mengunci dan memblokir penyegaran berkala saat sidang berjalan (lihat `CLAUDE.md`)
- `MED`: `SANDI_AWAL` (dibutuhkan seeder akun awal)
- `LOW`: variabel lain

## Langkah 6 — Cek Dependencies

**PHP (Composer):**
```bash
if [ -f composer.json ]; then
  if [ -d vendor ]; then
    echo "vendor/: ✅ ada"
    composer outdated --direct 2>/dev/null | head -10
  else
    echo "vendor/: ❌ belum ada — jalankan: composer install"
  fi
fi
```

**Ekstensi PHP wajib (dari spesifikasi Bagian 16):**
```bash
for ext in pdo_pgsql pgsql intl gd zip mbstring; do
  php -m | grep -qi "^${ext}$" && echo "$ext ✅" || echo "WARNING: ekstensi $ext tidak aktif"
done
```

**Node (kalau project pakai Tailwind/Vite untuk build aset):**
```bash
if [ -f package.json ]; then
  if [ -d node_modules ]; then
    echo "node_modules: ✅ ada"
  else
    echo "node_modules: ❌ belum ada — jalankan: npm install"
  fi
fi
```

## Langkah 7 — Cek Disk & Memory

```bash
echo "--- Disk ---"
df -h . | tail -1 | awk '{print "Used: "$3" / "$2" ("$5" full)"}'

echo "--- Memory ---"
if command -v vm_stat &>/dev/null; then
  total=$(sysctl -n hw.memsize | awk '{printf "%.0fGB", $1/1024/1024/1024}')
  echo "Total RAM: $total"
else
  free -h | grep Mem | awk '{print "Used: "$3" / "$2}'
fi

echo "--- Docker Volumes ---"
docker system df 2>/dev/null | head -5
```

Tandai WARNING jika disk usage > 85%. **Catatan penting untuk project ini**: RAM produksi (VPS 2GB, dipakai bersama Database-Warga-GKJJ) jauh lebih ketat dari mesin dev manapun — angka di sini tidak mewakili kondisi produksi sama sekali. Jangan simpulkan apa pun soal kesiapan produksi dari langkah ini.

## Langkah 8 — Cek Git Status

```bash
echo "=== Git Status ==="
git branch --show-current 2>/dev/null

UNCOMMITTED=$(git status --porcelain 2>/dev/null | wc -l | tr -d ' ')
if [ "$UNCOMMITTED" -gt 0 ]; then
  echo "⚠️  Ada $UNCOMMITTED file uncommitted"
  git status --short 2>/dev/null | head -10
else
  echo "Working tree clean ✅"
fi

git fetch --quiet 2>/dev/null
AHEAD=$(git rev-list @{u}..HEAD 2>/dev/null | wc -l | tr -d ' ')
BEHIND=$(git rev-list HEAD..@{u} 2>/dev/null | wc -l | tr -d ' ')
[ "$AHEAD" -gt 0 ] 2>/dev/null && echo "⚠️  $AHEAD commit belum dipush ke remote"
[ "$BEHIND" -gt 0 ] 2>/dev/null && echo "⚠️  $BEHIND commit baru di remote, perlu pull"
```

## Langkah 9 — Tulis DEVOPS_STATUS.md

```markdown
# DevOps Status
<!-- Diupdate otomatis oleh DevOps Agent. Jangan edit manual. -->

**Last Check**: TANGGAL JAM WIB
**Checked by**: Claude Code DevOps Agent
**Scope**: Dev lokal saja — TIDAK mewakili kondisi VPS produksi

---

## 🐘 PostgreSQL (lokal, Docker)

| Service | Status | Port |
|---------|--------|------|
| postgres | ✅/❌ | 5432 |

## 🔑 Environment Variables

| Variabel | Status | Severity |
|----------|--------|----------|
| DB_DATABASE | ✅ terisi | — |
| SESSION_DRIVER | ✅ database | — |

## 📦 Dependencies

| Component | Status |
|-----------|--------|
| vendor/ (Composer) | ✅/❌ |
| node_modules (kalau ada) | ✅/❌ |
| Ekstensi PHP wajib | ✅/❌ |

## 💾 System Resources (mesin dev — bukan VPS produksi)

| Resource | Status |
|----------|--------|
| Disk | XX% |
| Memory | XGB total |

## 🔀 Git

| Check | Status |
|-------|--------|
| Working tree | ✅/⚠️ |
| Remote sync | ✅/⚠️ |

## ⚠️ Issues Ditemukan

[Daftar WARNING/CRITICAL, atau "Tidak ada issue ✅"]

## 💡 Rekomendasi

[Action items]
```

## Langkah 10 — Alert Email (jika ada issue kritis)

Kirim hanya jika: Postgres lokal `Exit`/`unhealthy`, `.env` kosong untuk variabel HIGH, `vendor/` tidak ada, ekstensi PHP wajib tidak aktif, atau disk > 85%.

```bash
EMAIL_BODY="Halo,

DevOps Alert dari Aplikasi Notula Rapat GKJ Jakarta.

ISSUES DITEMUKAN
[Daftar setiap issue dengan severity]

ACTION YANG DIBUTUHKAN
[Langkah konkret untuk menyelesaikan setiap issue]

Detail lengkap di DEVOPS_STATUS.md
Repo: $(pwd)

-- Claude Code DevOps Agent"

osascript scripts/pm_email.applescript \
  "daru@sunartha.co.id" \
  "[DevOps 🚨] Notula GKJ Jakarta — Ada issue kritis" \
  "$EMAIL_BODY"
```

Jika semua OK, tidak perlu kirim email — cukup update `DEVOPS_STATUS.md`.

## Langkah 11 — Laporan Ringkas ke User

```
🔍 DEVOPS STATUS (dev lokal) — [TANGGAL JAM]
Postgres  : ✅/❌
.env      : X vars kosong (severity)
Ekstensi  : ✅/❌
Disk      : XX% used
Git       : clean / X uncommitted

Issues    : X critical, Y warning
DEVOPS_STATUS.md diupdate ✓
Email     : terkirim / tidak ada issue kritis

⚠️ Ini status DEV LOKAL. Kondisi VPS produksi baru dicek nyata di Sprint 14.
```

---

## Catatan Reusability

Diadaptasi dari `sunartha-claude-skills-dev/commands/devops.md`. Skill aslinya cek Redis dan backend/frontend Docker service terpisah — dihapus karena project ini tidak memakainya (session/queue/cache semua driver `database`, satu aplikasi Laravel monolith).
