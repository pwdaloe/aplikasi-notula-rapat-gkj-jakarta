# UX — User Experience Enhancement Agent

Kamu adalah seorang Senior UX Engineer yang memperbaiki kualitas pengalaman pengguna secara sistematis di aplikasi Blade + Livewire ini.

*(Diadaptasi total dari skill Sunartha standar — versi asli ditulis untuk React SPA dengan hooks/localStorage/client components. Livewire server-rendered tidak punya konsep itu; polanya diganti seluruhnya. Subcommand `bilingual` diganti `proyektor` karena project ini satu bahasa (Indonesia) tapi punya kebutuhan UX nyata yang tidak ada di skill aslinya: keterbacaan mode proyektor, F1-I01–I05.)*

## Cara Memanggil

```
/ux onboard    → First-run experience untuk sekretaris baru
/ux empty      → Perbaiki empty states di semua listing
/ux loading    → Tambah wire:loading di semua aksi async
/ux error      → Error handling + pesan error ramah user (bukan stack trace Laravel)
/ux proyektor  → Audit keterbacaan mode proyektor (F1-I01–I05) — KHUSUS project ini
/ux audit      → Laporan UX tanpa mengubah kode
```

Tanpa argumen, jalankan `audit` dulu.

---

## Langkah 1 — Baca Konteks Project

Baca `CLAUDE.md` dan `docs/spesifikasi-fungsional-fase-1.md`. Scan struktur:

```bash
find app/Livewire -name "*.php" 2>/dev/null | sort
find resources/views/livewire -name "*.blade.php" 2>/dev/null | sort
```

---

## Langkah 2 — Deteksi State UX Saat Ini

```bash
grep -rl "count(\|isEmpty\|@if.*empty\|belum ada" resources/views/ 2>/dev/null | wc -l
grep -rl "wire:loading" resources/views/ 2>/dev/null | wc -l
grep -rl "@error\|validate()" app/Livewire/ 2>/dev/null | wc -l
```

---

## Langkah 3A — Subcommand: `onboard`

**Tujuan**: sekretaris baru (yang "terbiasa bekerja di Word", lihat spesifikasi Bagian 18 risiko #7) langsung paham cara memakai app tanpa training panjang.

- Baca `docs/spesifikasi-fungsional-fase-1.md` untuk memahami alur inti: siapkan sidang → susun artikel → isi butir → sidang berjalan → notula → cetak
- Buat Livewire component `app/Livewire/Onboarding/Checklist.php` + view: daftar langkah pertama ("Buat sidang pertama", "Isi presensi", dst.), status tersimpan di kolom `users` atau tabel kecil terpisah (bukan `localStorage` — ini server-rendered, state harus di database supaya persisten lintas device)
- Tampilkan hanya untuk peran `sekretaris` yang baru login pertama kali (`terakhir_masuk_at` masih null atau sangat baru)
- Sertakan link langsung ke `docs/mockups/` sebagai referensi visual notula hasil akhir, supaya sekretaris tahu bentuk keluarannya sejak awal — ini penting karena format notula ini beda dari kebiasaan lama (spesifikasi Bagian 18 risiko #1)

---

## Langkah 3B — Subcommand: `empty`

```bash
grep -rln "@foreach" resources/views/livewire/ 2>/dev/null
```

Untuk tiap `@foreach` di listing (sidang, agenda, anggota, dll.) yang belum ada `@forelse ... @empty` — ganti ke pola:

```blade
@forelse ($items as $item)
    {{-- baris data --}}
@empty
    <div class="flex flex-col items-center justify-center py-12 text-center">
        <p class="text-gray-500 font-medium">Belum ada [nama item]</p>
        <p class="text-sm text-gray-400 mt-1">[hint cara menambah — mis. "Klik Tambah Sidang untuk mulai"]</p>
    </div>
@endforelse
```

Prioritaskan listing yang paling sering dilihat sekretaris: daftar sidang, daftar butir per artikel, daftar masukan.

---

## Langkah 3C — Subcommand: `loading`

Livewire punya `wire:loading` bawaan — pola ini menggantikan skeleton screen React sepenuhnya:

```blade
<button wire:click="simpan" wire:loading.attr="disabled">
    <span wire:loading.remove wire:target="simpan">Simpan</span>
    <span wire:loading wire:target="simpan">Menyimpan…</span>
</button>
```

Untuk komponen dengan polling (mode proyektor, dua-sekretaris — `wire:poll.5s`), pastikan `wire:loading` **tidak** dipasang di elemen yang di-refresh oleh polling (ini akan bikin kedip tiap 5 detik, melanggar F1-I04 "perubahan halus" dan F1-J04). Loading indicator hanya untuk aksi eksplisit user (klik tombol), bukan untuk refresh otomatis di background.

```bash
grep -rn "wire:poll" resources/views/livewire/ 2>/dev/null
```
Cek tiap komponen dengan `wire:poll` — pastikan tidak ada `wire:loading` yang menutupi konten saat poll jalan.

---

## Langkah 3D — Subcommand: `error`

**Tujuan**: sekretaris tidak pernah melihat halaman error Laravel mentah (stack trace, "500 Server Error") — terutama saat sidang sedang berjalan dan dua orang menyunting bersamaan.

- Pastikan `APP_DEBUG=false` di `.env` produksi (cek ini dulu, bug UX terbesar adalah stack trace bocor ke user)
- Buat exception handler custom di `bootstrap/app.php` (Laravel 11) untuk render pesan ramah, bukan default Laravel
- Untuk konflik kunci optimistis (F1-J03) — **bukan error biasa**, ini alur normal yang harus tampil sebagai 3 pilihan (pakai punya saya/rekan/gabungkan), bukan pesan error generik. Jangan tangani lewat exception handler umum.
- Mode proyektor (F1-I03): **tidak ada** dialog/alert/pesan galat yang menutupi layar sama sekali — kalau ada error di komponen proyektor, harus jadi pita tipis di tepi, bukan modal. Cek implementasi ini secara eksplisit:

```bash
grep -rln "proyektor\|projector" resources/views/livewire/ 2>/dev/null | xargs grep -l "SweetAlert\|confirm(\|alert(" 2>/dev/null
```
Kalau ketemu — **flag**, ini pelanggaran langsung kriteria terima F1-I03.

---

## Langkah 3E — Subcommand: `proyektor` (khusus project ini, bukan di skill aslinya)

**Tujuan**: verifikasi F1-I01–I05 terpenuhi secara UX, bukan cuma fungsional.

```bash
find resources/views/livewire -iname "*proyektor*" -o -iname "*projector*" 2>/dev/null
```

Cek untuk komponen proyektor:
- Ukuran huruf jauh lebih besar dari halaman biasa (target: terbaca dari belakang ruangan pada layar 1280×720 — kriteria terima eksplisit) → cek class Tailwind `text-2xl`/`text-3xl` atau lebih dipakai untuk konten utama, bukan `text-base`/`text-sm`
- Navigasi, panel samping, tulang punggung artikel, tombol edit — semua `@if(!$mode proyektor)` atau ada Blade layout terpisah khusus proyektor (bukan CSS `hidden` saja, karena elemen yang cuma disembunyikan CSS tetap termuat dan bisa membocorkan info butir tertutup ke DOM)
- Butir dengan `tayang = jangan` **tidak boleh** isinya termuat di HTML sama sekali (cek server-side filtering, bukan `x-show`/CSS) — kalau isi butir tertutup ada di HTML response meski disembunyikan CSS, itu kebocoran nyata lewat "View Source"
- Keluar via tombol DAN tombol Esc (`wire:keydown.escape`)

Ini pengecekan yang **paling penting** dari seluruh skill `/ux` di project ini — kegagalannya adalah kebocoran data gerejawi sensitif, bukan sekadar UX kurang mulus.

---

## Langkah 3F — Subcommand: `audit`

Jalankan semua scan 3B–3E tanpa mengubah kode. Buat `UX_AUDIT.md`:

```markdown
# UX Audit Report — Notula GKJ Jakarta
**Date**: [tanggal]

## Summary Score
| Area | Score | Status |
|------|-------|--------|
| Onboarding sekretaris | ?/5 | |
| Empty States | N/M listing | |
| Loading States (wire:loading) | N/M aksi | |
| Error Handling | | |
| **Proyektor — keterbacaan & kebocoran DOM** | | **prioritas tertinggi** |

## Detail Temuan
[per area]

## Prioritas Perbaikan
**High** (berdampak ke sidang berjalan nyata):
1. Proyektor — kebocoran DOM butir tertutup (kalau ada)
2. ...
```

---

## Langkah 4 — Verifikasi

```bash
vendor/bin/pint --test 2>/dev/null
php artisan test --filter=Proyektor 2>&1 | tail -20
```

## Langkah 5 — Git Commit

```bash
git add -A
git commit -m "feat(ux): [subcommand] — [ringkasan]

Co-Authored-By: Claude Sonnet 5 <noreply@anthropic.com>"
```

## Langkah 6 — Laporan ke User

```
✨ UX ENHANCEMENT — [SUBCOMMAND]
Files diubah  : N files
Perubahan utama:
• [bullet ringkas]

⚠️ Kalau subcommand ini menyentuh proyektor: tegaskan hasil pengecekan
   kebocoran DOM butir tertutup secara eksplisit, jangan cuma bilang "aman".
```

---

## Catatan Reusability

Diadaptasi berat dari `sunartha-claude-skills-dev/commands/ux.md` — versi asli untuk React SPA (hooks, localStorage, lucide-react) sama sekali tidak berlaku untuk Livewire server-rendered. Subcommand `bilingual` dihapus (app ini satu bahasa), diganti `proyektor` yang spesifik risiko project ini.
