# Spesifikasi Fungsional — Fase 1
## Aplikasi Sidang & Notula Majelis GKJ Jakarta

**Versi:** 1.0
**Tanggal:** 7 Agustus 2026
**Rujukan:** Poin Hasil Diskusi v0.8
**Stack:** Laravel 12 + PostgreSQL 16 + Blade/Livewire, VPS (bukan shared hosting cPanel — direvisi setelah dikonfirmasi hosting sebenarnya adalah VPS 2GB yang sama dengan Database-Warga-GKJJ). Laravel versi dinaikkan dari 11 ke 12 pada 8 Agustus 2026 — Laravel 11 sudah *end-of-life* (rilis terakhir v11.55.0) dengan 6 *security advisory* aktif yang tidak akan ditutup lagi di rentang 11.x manapun.

---

## 1. Sasaran Fase 1

Satu sidang MPL dapat dijalankan seluruhnya di dalam aplikasi, dari penyiapan agenda sampai notula yang disahkan dan tercetak — **tanpa Word dan tanpa menyalin ulang apa pun**.

### Yang termasuk

Akun dan hak akses · master data · penyiapan sidang dengan kerangka artikel · butir agenda dengan dua sumbu pembatasan · butir tertutup dengan akses per butir · presensi manual dan kuorum · pencatatan masukan dengan penyebut `@` · mode proyektor · penyuntingan dua sekretaris · notula draft→review→sah · cetak PDF lengkap dan tersunting · penyusun undangan WhatsApp.

### Yang tidak termasuk

| Ditunda ke | Isi |
|---|---|
| Fase 2 | Check-in mandiri QR, rekap kehadiran per periode, layar serah terima akses antar periode |
| Fase 3 | Kesimpulan terstruktur, tindak lanjut, pengisian otomatis Artikel Tindak Lanjut, dasbor monitoring |
| Fase 4 | SK, petikan keputusan, portal peninjau |
| Di luar lingkup | Keuangan, data jemaat, rekaman audio, notifikasi otomatis |

### Penyesuaian lingkup yang perlu disadari

**Artikel Tindak Lanjut belum terisi otomatis di Fase 1**, karena modul tindak lanjut baru ada di Fase 3. Pada Fase 1 artikel ini berperilaku seperti artikel biasa dan diisi tangan oleh sekretaris. Ini menambah pekerjaan sekretaris untuk sementara, dan sebaiknya dikatakan sejak awal supaya tidak terasa seperti janji yang tidak ditepati.

**Level `umum` belum punya pemakai di Fase 1**, karena portal peninjau baru ada di Fase 4. Penandaannya tetap dikerjakan sejak sekarang agar data lama tidak perlu ditandai ulang, tetapi ringkasan penerbitan pada langkah pengesahan menyusul bersama portalnya.

---

## 2. Peran

| Kode | Peran | Ringkas |
|---|---|---|
| `admin` | Administrator | Akun, master data, pengaturan, cadangan |
| `sekretaris` | Sekretaris majelis | Seluruh pengelolaan sidang dan notula |
| `ketua` | Ketua / pimpinan sidang | Menyetujui pengesahan notula |
| `pendeta` | Pendeta | Anggota dengan akses setara MPL, umumnya penerima akses butir tertutup |
| `anggota` | Anggota majelis (MPH/MPL) | Membaca, mengoreksi notula, mengusulkan agenda |

Penanda keanggotaan MPH disimpan sebagai atribut pada keanggotaan, bukan sebagai peran tersendiri.

---

## 3. Master Data

### F1-B01 Profil gereja
Tabel pengaturan tunggal: nama gereja, alamat, telepon, logo, berkas kop surat, kota penerbitan surat.
Nama dan jabatan penanda tangan notula: Ketua dan Sekretaris, masing-masing dengan berkas tanda tangan hasil pindaian yang boleh dikosongkan.

### F1-B02 Periode kemajelisan
Nama periode, tanggal mulai, tanggal selesai, penanda periode aktif. Hanya satu periode aktif pada satu waktu.

### F1-B03 Anggota majelis
| Bidang | Aturan |
|---|---|
| Nama lengkap | Wajib |
| Sebutan | Wajib — `Pdt.` / `Pnt.` / `Dkn.` / `Vik.` / kosong |
| Nomor HP | Wajib, unik, disimpan ternormalkan `62…` |
| Email | Opsional |
| Jabatan | Teks bebas, mis. "Bendahara", "Sekretaris Majelis" |
| Unit pelayanan | Relasi, boleh lebih dari satu |
| Wilayah/Rama | Relasi tunggal, boleh kosong |
| Anggota MPH | Ya/tidak |
| Periode | Relasi ke periode kemajelisan |
| Aktif | Ya/tidak |

Nama yang ditampilkan di seluruh aplikasi adalah gabungan sebutan dan nama: `Pnt. Haryanto`.

### F1-B04 Unit pelayanan
Nama, jenis (`bidang`, `komisi`, `upk`, `panitia`, `tim`, `lembaga`, `ministerium`), tanggal mulai, tanggal selesai, aktif. Unit berjenis panitia dan tim wajib punya tanggal mulai.

### F1-B05 Wilayah / Rama
Nama dan kode. Dipakai sebagai penanggung jawab tugas, mis. PIC konsumsi.

### F1-B06 Pengaturan aplikasi

| Kunci | Bawaan | Keterangan |
|---|---|---|
| `nomor_awal_mpl` | — | Wajib diisi saat pemasangan |
| `nomor_awal_mph` | — | Wajib diisi saat pemasangan |
| `kuorum_mpl` | 3/4 | Dari jumlah anggota aktif — dipastikan dari Tata Gereja/Tata Laksana |
| `kuorum_mph` | 1/2 | Dari jumlah anggota MPH aktif — masih dugaan, belum dicocokkan |
| `kuorum_pembulatan` | atas | Cara pecahan ambang dibulatkan |
| `batas_koreksi_jam` | 72 | Lama masa koreksi notula |
| `selang_segar_detik` | 5 | Selang penyegaran saat sidang berjalan |
| `maks_lampiran_mb` | 8 | Disesuaikan batas hosting |
| `jenis_lampiran` | pdf, docx, xlsx, pptx, jpg, png | Daftar putih |

**Kriteria terima**
- Aplikasi menolak dipakai membuat sidang bila nomor awal kedua deret belum diisi
- Menonaktifkan anggota tidak menghapus jejaknya pada sidang yang sudah lewat
- Mengubah sebutan seseorang ikut mengubah tampilannya pada notula yang belum disahkan, dan tidak mengubah notula yang sudah sah

---

## 4. Akun dan Autentikasi

### F1-C01 Masuk
Dengan nomor HP dan kata sandi. Nomor HP diterima dalam bentuk `08…` maupun `62…` dan diperlakukan sama.

### F1-C02 Pembuatan akun
Hanya oleh `admin`. Tidak ada pendaftaran mandiri dan tidak ada tautan reset kata sandi di halaman masuk.

### F1-C03 Kata sandi awal
Admin membuat kata sandi awal. Pada login pertama pengguna wajib menggantinya sebelum bisa membuka apa pun.

### F1-C04 Pemulihan kata sandi
Admin membuat tautan sekali pakai berlaku 24 jam, disalin, lalu dikirim lewat WhatsApp secara manual. Tautan hangus setelah dipakai.

### F1-C05 Pembatasan percobaan
Lima kali gagal berturut-turut mengunci akun selama 15 menit.

**Kriteria terima**
- Halaman masuk memuat nama dan nomor sekretaris sebagai jalan keluar, bukan tautan reset
- Pengguna yang belum mengganti kata sandi awal selalu dialihkan ke halaman ganti sandi
- Nomor `081290457731` dan `6281290457731` mengarah ke akun yang sama

---

## 5. Penyiapan Sidang

### F1-D01 Deret dan nomor
Dua deret terpisah: `mpl` diisi jenis `mpl` dan `istimewa`, `mph` diisi jenis `mph`.
Nomor diusulkan dari nomor tertinggi pada deret yang sama ditambah satu, dan boleh dikoreksi tangan. Indeks unik gabungan pada `(deret, nomor)`.

### F1-D02 Data sidang
Jenis, deret, nomor, hari dan tanggal, jam mulai rencana, tempat, pemimpin sidang, notulis, catatan tambahan undangan, PIC konsumsi.
Status sidang: `draft` → `diedarkan` → `berjalan` → `selesai`.

### F1-D03 Template artikel
Template tersimpan per jenis rapat. Saat sidang dibuat, artikel disalin dari template dan sejak itu berdiri sendiri — mengubah template tidak mengubah sidang yang sudah dibuat.

Tipe artikel: `pembukaan`, `presensi`, `agenda`, `tindak_lanjut`, `penutup`.

### F1-D04 Menyusun artikel
Artikel dapat ditambah, dihapus, diganti judul, dan diurutkan ulang. Nomor Romawi dihitung dari urutan, tidak disimpan.
Artikel bertipe `presensi` tidak dapat dihapus.

**Kriteria terima**
- Membuat sidang istimewa mengambil nomor berikutnya dari deret MPL, bukan deret tersendiri
- Menyisipkan artikel di posisi ketiga menggeser penomoran artikel sesudahnya tanpa menyentuh isinya
- Menghapus artikel yang masih berisi butir meminta penegasan lebih dulu

---

## 6. Butir Agenda

### F1-E01 Bidang butir

| Bidang | Aturan |
|---|---|
| Judul | Wajib |
| Judul tampil | Wajib bila level `tertutup`, selain itu diabaikan |
| Urutan | Dalam artikel |
| Pelapor | Anggota atau teks bebas untuk peninjau |
| Unit terkait | Relasi, boleh kosong |
| Level keterbacaan | `umum` / `majelis` / `tertutup` — bawaan `majelis` |
| Ketertayangan | `boleh` / `jangan` |
| Penanda pra-MPL | Ya/tidak |
| Status | `baru` / `dibahas` / `ditunda` / `dikembalikan` / `selesai` |
| Lampiran | Banyak berkas |

### F1-E02 Aturan dua sumbu

| Keadaan | Akibat |
|---|---|
| Level `tertutup` | Ketertayangan dipaksa `jangan` dan tidak dapat diubah |
| Penanda pra-MPL menyala | Ketertayangan bawaannya `jangan`, boleh dilepas |
| Level `umum` atau `majelis` | Ketertayangan bawaannya `boleh` |

### F1-E03 Lampiran
Diunggah per butir. Divalidasi menurut jenis dan ukuran dari pengaturan. Lampiran pada butir `tertutup` mewarisi pembatasan butirnya.

**Kriteria terima**
- Menyimpan butir `tertutup` tanpa judul tampil ditolak dengan pesan yang menyebut sebabnya
- Mengubah level butir dari `majelis` menjadi `tertutup` seketika memaksa ketertayangannya menjadi `jangan`
- Butir dengan ketertayangan `jangan` tidak pernah tampil di mode proyektor

---

## 7. Butir Tertutup dan Pemberian Akses

### F1-F01 Daftar pembaca
Disimpan sebagai pasangan butir dan orang. Disusun sendiri untuk tiap butir, tidak mengikuti jabatan.

### F1-F02 Notulis ikut otomatis
Notulis yang tercatat pada sidang tersebut dimasukkan otomatis ke daftar setiap butir `tertutup` pada sidang itu, tanpa perlu ditunjuk. Yang ikut otomatis hanya notulis sidang tersebut.

### F1-F03 Perkecualian notulis
Bila notulis adalah pihak dalam perkara, ia dapat dikeluarkan dari daftar **dengan syarat** ada pencatat pengganti yang ditunjuk untuk butir itu. Sistem menolak mengeluarkan notulis bila penggantinya belum ada.

### F1-F04 Daftar tidak boleh kosong
Menyimpan butir `tertutup` dengan daftar pembaca kosong ditolak.

### F1-F05 Penyaringan
Butir `tertutup` disaring di lapis *policy* dan *global scope*, bukan disembunyikan di tampilan. Butir yang tidak boleh dibaca tidak muncul dalam daftar, tidak muncul dalam pencarian, dan tidak terhitung dalam jumlah butir.

### F1-F06 Log akses
Setiap pembukaan butir `tertutup` dicatat: siapa, kapan, alamat IP, cara membuka (layar atau cetak).

### F1-F07 Laporan butir tanpa pembaca aktif
Daftar butir `tertutup` yang seluruh pembacanya sudah tidak aktif. Tersedia bagi `admin` dan `ketua`.

**Kriteria terima**
- Anggota di luar daftar tidak dapat menemukan butir tertutup lewat pencarian mana pun
- Menotulakan butir tertutup berjalan tanpa langkah tambahan bagi notulis sidang
- Membuka butir tertutup dua kali menghasilkan dua baris log

---

## 8. Presensi dan Kuorum

### F1-G01 Pencatatan
Manual oleh sekretaris. Status: `hadir`, `terlambat`, `izin`, `sakit`, `tanpa_keterangan`. Bawaan seluruh anggota `tanpa_keterangan` sampai diubah.

### F1-G02 Peserta tanpa akun
Undangan, peninjau, dan pengurus komisi dicatat sebagai nama bebas beserta keterangan asalnya. Mereka dapat menjadi penutur masukan tetapi tidak punya akun.

### F1-G03 Kuorum
Dihitung dari jumlah `hadir` ditambah `terlambat`, dibandingkan ambang dari pengaturan menurut jenis sidang. Ditampilkan terus-menerus selama sidang berjalan beserta angka ambangnya.

### F1-G04 Penguncian
Presensi terkunci begitu notula berstatus `sah`. Perubahan sesudahnya hanya lewat adendum.

**Kriteria terima**
- Angka kuorum berubah seketika ketika satu orang diubah statusnya
- Jumlah peserta tanpa akun tidak ikut menghitung kuorum
- Rekap presensi yang tercetak pada notula sama persis dengan yang tampil di layar

---

## 9. Sidang Berjalan

### F1-H01 Butir aktif
Sidang punya satu butir aktif. Hanya notulis yang memindahkannya. Sekretaris pendamping bebas membuka butir mana pun tanpa mengubah butir aktif.

### F1-H02 Mencatat masukan

| Bidang | Aturan |
|---|---|
| Butir | Wajib |
| Penutur | Anggota atau peserta tanpa akun, wajib |
| Jenis | `usulan` / `pertanyaan` / `keberatan` / `dukungan` / `informasi` |
| Isi | Satu atau beberapa poin |
| Waktu | Terisi otomatis |
| Induk | Kosong untuk masukan utama |

### F1-H03 Penyebut `@`
Mengetik `@` diikuti beberapa huruf memunculkan daftar peserta sidang beserta jabatan dan status kehadirannya. Nama yang dipilih tersimpan sebagai rujukan ke orangnya, bukan sekadar teks, sehingga tetap benar bila namanya berubah.
Penyebut pertama pada sebuah masukan menetapkan penuturnya.

### F1-H04 Balasan
Satu tingkat saja. Balasan atas balasan ditolak. Tampil sebagai `Respon — <nama>` mengikuti kebiasaan notula yang berlaku.

### F1-H05 Simpan otomatis
Isi tersimpan berkala tanpa perlu menekan tombol, dengan penanda waktu simpan terakhir yang terlihat.

### F1-H06 Butir Warnasari dadakan
Butir baru dapat ditambahkan ke artikel mana pun selagi sidang berjalan.

**Kriteria terima**
- Mengetik `@har` memunculkan `Pnt. Haryanto` dalam satu ketukan berikutnya
- Menyebut orang yang berstatus izin tetap diperbolehkan, tetapi statusnya terlihat pada daftar pilihan
- Sekretaris pendamping membuka butir lain tanpa mengubah apa yang tampil di proyektor

---

## 10. Mode Proyektor

### F1-I01 Perilaku
Menyembunyikan navigasi, panel samping, tulang punggung artikel, dan seluruh tombol penyunting. Ukuran huruf membesar. Kotak pencatat tetap ada di dasar layar.

### F1-I02 Penyaringan tayang
Butir dengan ketertayangan `jangan` tidak ditampilkan. Bila butir aktif tergolong demikian, layar menampilkan pemberitahuan bahwa isinya tidak ditayangkan, tanpa memuat isinya.

### F1-I03 Larangan kotak dialog
Tidak ada kotak dialog, peringatan bentrok, atau pesan galat yang muncul menutupi layar. Semuanya diwakili pita tipis di tepi.

### F1-I04 Perubahan halus
Isi baru dari sekretaris lain masuk tanpa memindahkan posisi gulir dan tanpa membuat layar berkedip.

### F1-I05 Keluar
Lewat tombol atau tombol Esc.

**Kriteria terima**
- Butir tertutup tidak pernah tampil di mode proyektor dalam keadaan apa pun
- Teks masukan terbaca dari jarak belakang ruangan pada proyektor 1280×720
- Bentrokan penyuntingan tidak memunculkan apa pun yang menutupi layar

---

## 11. Penyuntingan Dua Sekretaris

### F1-J01 Tanpa kunci
Tidak ada penguncian catatan. Keduanya menyunting bebas.

### F1-J02 Butir data kecil
Setiap masukan, balasan, dan poin adalah baris tersendiri. Notula tidak pernah disimpan sebagai satu bidang teks besar. Inilah pencegahan bentrokan yang sebenarnya.

### F1-J03 Penguncian optimistis
Setiap baris punya kolom `versi` bertipe integer yang naik setiap penyimpanan. Penyimpanan membawa versi yang dibacanya; bila sudah berbeda, perubahan tidak ditulis dan pengguna diberi tiga pilihan: pakai punya saya, pakai punya rekan, atau gabungkan tangan.

### F1-J04 Penyegaran berkala
Setiap `selang_segar_detik` selama sidang berjalan. Titik akhir penyegaran tidak melewati session berbasis berkas.

### F1-J05 Penanda kehadiran
Menampilkan siapa sedang membuka butir mana, ditumpangkan pada penyegaran yang sama.

**Kriteria terima**
- Dua sekretaris mencatat masukan pada butir yang sama secara bersamaan tanpa satu pun hilang
- Menyunting poin yang sama menghasilkan pilihan penyelesaian, bukan penimpaan diam-diam
- Sepuluh menit penyegaran berdua tidak menyentuh batas *entry process* hosting

---

## 12. Notula

### F1-K01 Daur status

```
draft  →  review  →  sah  →  adendum
```

| Status | Arti |
|---|---|
| `draft` | Sedang disusun, hanya sekretaris yang melihat |
| `review` | Diedarkan untuk dikoreksi, masa koreksi berjalan |
| `sah` | Disetujui Ketua dan Sekretaris, terkunci |
| `adendum` | Ada perubahan setelah pengesahan, tercatat terpisah |

### F1-K02 Masa koreksi
Dihitung dari saat diedarkan, selama `batas_koreksi_jam`. Setelah lewat, koreksi baru ditolak dan notula siap disahkan.

### F1-K03 Koreksi
Berupa komentar yang menempel pada butir, bukan penyuntingan langsung. Sekretaris menandai tiap koreksi `diterima` atau `ditolak` beserta tanggapan. Koreksi yang diterima diterapkan sendiri oleh sekretaris.

### F1-K04 Catatan pembacaan
Tercatat siapa sudah membuka notula dalam masa koreksi. Ditampilkan bagi sekretaris sebagai daftar sederhana.

### F1-K05 Pengesahan
Memerlukan dua persetujuan: `ketua` dan `sekretaris`. Setelah keduanya, notula terkunci beserta presensi dan seluruh masukan.

### F1-K06 Adendum
Perubahan setelah pengesahan dicatat sebagai adendum bernomor, memuat apa yang berubah dan alasannya. Notula asli tetap utuh.

**Kriteria terima**
- Notula tidak dapat disahkan bila baru satu pihak menyetujui
- Koreksi yang masuk setelah masa koreksi habis ditolak dengan pesan yang menyebut waktunya
- Setelah sah, mengubah presensi hanya mungkin lewat adendum

---

## 13. Cetak PDF

### F1-L01 Mesin
dompdf. Tidak boleh memakai Browsershot atau Puppeteer, yang tidak dapat berjalan di shared hosting.

### F1-L02 Susunan halaman

| Bagian | Isi |
|---|---|
| Kepala | Kop gereja, `Notula Sidang MPL/MPH ke-<nomor>`, hari dan tanggal, jam mulai dan selesai, tempat |
| Pejabat | Pemimpin sidang dan notulis |
| Rekap presensi | Jumlah tiap status dan pernyataan kuorum |
| Daftar hadir | Nama bersebutan, dikelompokkan MPH, MPL, undangan dan peninjau — dapat disembunyikan lewat saklar |
| Isi | Artikel berurutan dengan angka Romawi, butir, masukan, balasan |
| Kaki | Blok tanda tangan Ketua dan Sekretaris beserta tanggal pengesahan |

### F1-L03 Dua varian

| Varian | Butir tertutup |
|---|---|
| Lengkap | Judul asli dan isi penuh — hanya bagi penerima akses butirnya |
| Tersunting | Judul tampil saja, isi disembunyikan |

### F1-L04 Penomoran halaman
Halaman ke-n dari m pada setiap lembar.

**Kriteria terima**
- Varian tersunting tidak memuat judul asli butir tertutup di mana pun, termasuk daftar isi
- Notula berstatus `draft` tercetak dengan tanda air `DRAF`
- Berkas notula sepanjang 12 halaman terbentuk di bawah 10 detik pada hosting sasaran

---

## 14. Penyusun Undangan WhatsApp

### F1-M01 Template
Tersimpan dan dapat disunting sekretaris, dengan pengganti untuk nomor sidang, hari, tanggal, jam, tempat, daftar agenda, catatan tambahan, PIC konsumsi, dan nama pengundang.

### F1-M02 Daftar agenda otomatis
Tersusun dari artikel dan butir yang sudah dientri, dengan aturan:

| Butir | Muncul di undangan |
|---|---|
| `umum`, `majelis` | Judul muncul |
| Penanda pra-MPL | Judul muncul |
| `tertutup` | **Tidak muncul sama sekali**, termasuk judul tampilnya |

### F1-M03 Keluaran
Teks siap tempel, tombol salin, dan tautan `wa.me`. Tidak ada pengiriman otomatis.

### F1-M04 Penanda pengiriman
Sekretaris menandai "sudah dikirim ke grup" beserta waktunya. Status ini yang mengubah sidang menjadi `diedarkan`.

**Kriteria terima**
- Teks yang tersalin sama persis dengan yang tampil di pratinjau, termasuk baris kosongnya
- Butir tertutup tidak menyisakan jejak apa pun di teks undangan, termasuk nomor urut yang bolong
- Menyunting judul butir langsung terlihat di pratinjau undangan tanpa perlu memuat ulang

---

## 15. Jejak Audit

### F1-N01 Perubahan tercatat
Pembuatan, penyuntingan, dan penghapusan pada: sidang, artikel, butir, masukan, presensi, notula, akun, dan daftar akses butir tertutup. Tercatat siapa, kapan, dan nilai sebelum-sesudah untuk bidang penting.

### F1-N02 Log akses tertutup
Terpisah dari jejak audit umum dan tidak dapat dihapus dari dalam aplikasi.

---

## 16. Batasan Teknis

| Hal | Ketentuan |
|---|---|
| Session | Driver `database`, bukan berkas — penguncian session akan memblokir penyegaran berkala |
| Queue | Driver `database`, dijalankan cron `queue:work --stop-when-empty` |
| Scheduler | Satu cron per menit menjalankan `schedule:run` |
| PDF | dompdf |
| Aset | Di-build lokal lalu diunggah; tanpa `npm` di server |
| Realtime | Tidak ada websocket; penyegaran berkala saja |
| Cadangan | Cron `pg_dump` harian ditambah tombol ekspor manual |
| Prasyarat | PHP 8.2+, PostgreSQL 16, akses cron, ekstensi `intl`, `gd`, `zip`, `pdo_pgsql` |

**Verifikasi hosting wajib dilakukan sebelum baris kode pertama.** Bila cron tidak tersedia atau versi PHP di bawah 8.2, sebagian rancangan ini perlu ditinjau ulang.

---

## 17. Skenario Uji Terima

Satu alur utuh yang harus berhasil sebelum Fase 1 dinyatakan selesai.

| # | Langkah | Hasil yang diharapkan |
|---|---|---|
| 1 | Admin memasang data: profil gereja, periode, 48 anggota, unit, wilayah, nomor awal kedua deret | Data lengkap, seluruh anggota punya akun |
| 2 | Sekretaris membuat Sidang MPL baru | Nomor terisi otomatis satu di atas nomor terakhir deret MPL |
| 3 | Menyusun tujuh artikel dari template, mengisi butir | Nomor Romawi runtut, butir tersusun |
| 4 | Menandai satu butir `tertutup`, mengisi judul tampil, menunjuk tiga pembaca | Ketertayangan terkunci `jangan`, notulis ikut otomatis menjadi empat orang |
| 5 | Menyusun undangan WhatsApp, menyalin, menandai sudah dikirim | Butir tertutup tidak muncul; status sidang menjadi `diedarkan` |
| 6 | Anggota di luar daftar mencari judul asli butir tertutup | Tidak ditemukan sama sekali |
| 7 | Hari-H: sekretaris mengisi presensi 38 hadir (ambang 36 dari 48) | Kuorum dinyatakan terpenuhi dengan ambang tertera |
| 8 | Notulis masuk mode proyektor, mencatat masukan dengan `@` | Nama tersisip, teks terbaca dari belakang ruangan |
| 9 | Sekretaris pendamping menyunting butir lain bersamaan | Tidak ada yang hilang, layar proyektor tidak berpindah |
| 10 | Keduanya menyunting poin yang sama | Muncul pilihan penyelesaian, bukan penimpaan |
| 11 | Sidang masuk ke butir tertutup | Layar proyektor tidak menampilkan isinya; notulis tetap dapat mencatat |
| 12 | Sidang selesai, notula diedarkan untuk koreksi | Masa koreksi berjalan, anggota dapat berkomentar per butir |
| 13 | Sekretaris menanggapi koreksi, Ketua dan Sekretaris mengesahkan | Notula dan presensi terkunci |
| 14 | Mencetak dua varian PDF | Varian tersunting tidak memuat judul asli butir tertutup |
| 15 | Mengubah presensi setelah pengesahan | Ditolak, diarahkan ke mekanisme adendum |

---

## 18. Asumsi dan Risiko

| # | Hal | Catatan |
|---|---|---|
| 1 | Format notula berubah dari kebiasaan lama | Daftar hadir dan blok tanda tangan adalah tambahan. Tunjukkan contohnya ke majelis sebelum berlaku |
| 2 | Artikel Tindak Lanjut masih manual | Sampai Fase 3. Perlu dikatakan sejak awal |
| 3 | Level `umum` belum punya pemakai | Penandaan tetap dikerjakan agar data lama tidak perlu ditandai ulang |
| 4 | Kemampuan hosting belum diverifikasi | Penyegaran berkala dan pembuatan PDF perlu diuji beban lebih dulu |
| 5 | Akses butir tertutup melekat pada orang | Serah terima antar periode baru ada di Fase 2. Untuk sementara, pengalihan dikerjakan admin secara langsung |
| 6 | Ambang kuorum MPH belum dipastikan | Ambang MPL sudah dipastikan 3/4 dari Tata Gereja/Tata Laksana (36 dari 48). Ambang MPH menyusul sebelum rapat MPH pertama dibuat |
| 7 | Dua sekretaris terbiasa bekerja di Word | Perlu satu sidang percobaan berdampingan dengan cara lama sebelum beralih penuh |

---

## 19. Definisi Selesai

Fase 1 dinyatakan selesai bila seluruh 15 langkah skenario uji terima berhasil pada lingkungan hosting yang sebenarnya, bukan di komputer pengembang, dan satu sidang MPL nyata telah dijalankan penuh di dalam aplikasi dengan notula yang disahkan dan tercetak.
