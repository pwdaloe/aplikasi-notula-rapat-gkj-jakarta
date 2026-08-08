# Poin Hasil Diskusi — Aplikasi Notula Rapat Majelis GKJ Jakarta

**Versi:** 0.8 (draft diskusi — seluruh percabangan utama sudah diputuskan)
**Tanggal:** 7 Agustus 2026
**Status:** belum ada pengembangan; dokumen ini rangkuman kesepakatan dan daftar hal terbuka
**Stack:** Laravel + MySQL, Blade + Livewire, tanpa layanan pihak ketiga

---

## 0. Revisi terhadap v0.1

Setelah membaca dokumen nyata, empat asumsi awal saya perlu diganti:

| v0.1 (keliru) | v0.2 (sesuai praktik) |
|---|---|
| Nomor keputusan berformat `012/MPL/GKJ-JKT/VIII/2026` | **Nomor sidang berurutan kontinu**: MPL ke-1.019, ke-1.027 — deret sejarah, bukan reset per tahun |
| Agenda sebagai daftar datar bernomor | **Kerangka "Artikel" I–VIII** yang tetap tiap sidang, agenda menjadi butir di dalam artikel |
| Masukan peserta bersifat datar | Masukan punya **balasan berjenjang** ("Respon Pdt Neny: …") |
| Hasil disebut "keputusan" | Praktiknya memakai **Kesimpulan / Kesepakatan / Keputusan** — tiga istilah berbeda |

Temuan tambahan yang penting: banyak keputusan bermuara pada **penerbitan SK**, dengan kesepakatan tertulis bahwa "SK paling lambat di MPL selanjutnya sudah terealisasi". Inilah wujud konkret "keputusan untuk eksternal" — modulnya perlu mencakup penerbitan dan pemantauan SK.

---

## 1. Ruang Lingkup

| Aspek | Kesepakatan |
|---|---|
| Pengguna | Hanya GKJ Jakarta (satu gereja, bukan multi-tenant) |
| Jenis rapat | Sidang MPL, rapat MPH, sidang istimewa |
| Penomoran | Dua deret: **deret MPL** (memuat sidang MPL dan sidang istimewa) dan **deret MPH** |
| Frekuensi | Sekitar satu sidang MPL per bulan (1.019 Des 2025 → 1.027 Agu 2026) |
| Yang login | Seluruh anggota majelis |
| Kanal pemberitahuan | WhatsApp, dikirim manual oleh sekretaris (aplikasi hanya menyusun teks) |
| Rekaman & transkrip | Tidak masuk lingkup |
| Hosting | Shared hosting cPanel |
| Ruang sidang | Dua laptop sekretaris, dua infocus: satu menayangkan materi, satu menayangkan notulensi secara langsung |
| Penyuntingan | Dua sekretaris menyunting sidang yang sama secara bersamaan |

### Sengaja di luar lingkup
Modul keuangan/anggaran, data keanggotaan jemaat, warta jemaat, rekaman audio, transkrip otomatis, notifikasi otomatis, aplikasi mobile native, penggunaan oleh gereja lain.

---

## 2. Kerangka Artikel — struktur inti aplikasi

Setiap sidang mengikuti kerangka artikel bernomor Romawi. Susunannya **tidak selalu sama** antar sidang (Des 2025 memakai 8 artikel dengan Materi MPH terpisah; Agu 2026 memakai 7 artikel tanpa Materi MPH), maka:

- Sediakan **template artikel per jenis rapat**, dapat disesuaikan saat menyusun sidang
- Artikel dapat ditambah, dihapus, dan diurutkan ulang; nomor Romawi dihitung otomatis
- Artikel bertipe khusus punya perilaku sendiri

| Artikel | Tipe | Perilaku |
|---|---|---|
| Pembukaan | `pembukaan` | Tanpa butir agenda |
| Presensi peserta sidang | `presensi` | Terhubung ke modul presensi |
| Laporan & Presentasi | `agenda` | Butir dengan pelapor/penyaji dan lampiran |
| Tindak lanjut keputusan MPH & sidang MPL sebelumnya | `tindak_lanjut` | **Terisi otomatis** dari tindak lanjut belum tuntas, ditarik dari **kedua deret** (MPH dan MPL) |
| Materi MPH | `agenda` | Umumnya berlevel kerahasiaan lebih tinggi |
| Materi Ministerium | `agenda` | — |
| Warnasari | `agenda` | Butir bebas, sering muncul dadakan saat sidang |
| Penutup | `penutup` | Unsur tetap: Sensoramorun & Doa Penutup |

Butir agenda di dalam artikel: nomor urut, judul, pelapor/penyaji, bidang atau unit terkait, lampiran, level kerahasiaan, status.

---

## 3. Penomoran

Ada **dua deret nomor terpisah**, keduanya berurutan kontinu sepanjang sejarah gereja dan tidak direset per tahun:

| Deret | Diisi oleh |
|---|---|
| `mpl` | Sidang MPL **dan sidang istimewa** |
| `mph` | Rapat MPH |

- Nomor diisi otomatis dari nomor terakhir pada deret yang sama + 1, tetap dapat dikoreksi manual
- Perlu **penetapan nomor awal untuk kedua deret** saat aplikasi dipasang
- Penomoran SK: dikelola aplikasi, format masih terbuka
- Penomoran petikan keputusan: masih terbuka

**Konsekuensi teknis penting:** `deret` bukan hal yang sama dengan `jenis rapat`, karena sidang istimewa berjenis sendiri namun ikut deret MPL. Tabel `sidang` menyimpan keduanya sebagai kolom terpisah, dengan indeks unik gabungan pada `(deret, nomor)` — bukan pada jenis, dan bukan satu kolom auto-increment.

---

## 4. Peran dan Hak Akses

| Peran | Kewenangan |
|---|---|
| Admin | Akun, profil gereja, periode kemajelisan, template artikel & pesan, cadangan data |
| Sekretaris (notulis) | Menyusun sidang, artikel, agenda, presensi, masukan, kesimpulan, notula, SK/petikan |
| Ketua/Pimpinan sidang | Menyetujui pengesahan notula, akses penuh sesuai level kerahasiaan |
| Anggota MPH | Akses level `umum`, `majelis`, dan `terbatas` |
| Anggota MPL | Akses level `umum` dan `majelis` |
| Sekretaris pendamping | Menyunting bersamaan dari laptop kedua; tidak mengendalikan layar tayang |
| Peninjau — komisi, UPK, panitia, jemaat | **Tidak masuk aplikasi notula.** Hanya membuka portal keputusan majelis |

### Level kerahasiaan agenda — **direvisi**

Asumsi awal bahwa MPH membahas materi yang tidak boleh dilihat MPL ternyata keliru. **MPH membahas topik yang sama, hanya lebih dahulu**; keputusan tetap hanya diambil di MPL. Maka `terbatas` bukan soal kerahasiaan isi, melainkan **tahap**.

| Level | Makna |
|---|---|
| `umum` | Boleh keluar dari majelis; **hanya level ini yang muncul di portal peninjau dan bisa dijadikan petikan** |
| `majelis` | Seluruh anggota majelis yang login — level bawaan |
| `tertutup` | Perkara yang menyangkut pribadi, terutama **penggembalaan**. Pembacanya **ditunjuk satu per satu untuk tiap butir**, setiap akses dicatat |

Adapun tahap MPH ditandai terpisah sebagai `pra-MPL` — bukan level kerahasiaan, melainkan penanda bahwa pembahasan belum matang untuk ditayangkan.

Konsekuensinya, pembatasan terbelah menjadi dua sumbu yang berdiri sendiri:

| Sumbu | Menjawab | Nilai |
|---|---|---|
| **Keterbacaan** | Siapa boleh membuka di aplikasi | `umum` / `majelis` / `tertutup` |
| **Ketertayangan** | Boleh muncul di layar proyektor | `boleh tayang` / `jangan tayang` |

Butir `tertutup` selalu `jangan tayang`, tanpa bisa diubah. Butir `pra-MPL` bawaannya `jangan tayang`, namun boleh dilepas.

Pemisahan ini lahir dari kenyataan ruang sidang: proyektor melewati seluruh kendali akun, karena siapa pun yang duduk di ruangan ikut membaca — termasuk peninjau yang bukan anggota majelis. Sebuah butir bisa saja boleh dibaca semua anggota namun belum layak ditayangkan.

**`umum` adalah bidang paling berisiko di seluruh aplikasi.** Karena setiap peninjau melihat semua keputusan `umum`, salah menandai satu butir berarti menerbitkannya ke seluruh pemegang akses portal. Padahal penandaan itu dilakukan sambil lalu saat menyiapkan agenda. Pengamannya: pada langkah pengesahan notula, tampilkan ringkasan *"sekian butir akan terbit ke portal peninjau"* beserta daftarnya, untuk disetujui Ketua dan Sekretaris — pengesahan notula sekaligus menjadi keputusan penerbitan.

**Aturan tampilan:** pada notula versi tersunting, judul butir tetap terlihat dan isinya disembunyikan. Penyaringan wajib di lapis *policy*/*global scope*.

**Butir `tertutup` menyimpan dua judul.** Aturan "judul tetap terlihat" berbahaya di sini: judul seperti *"Penggembalaan Sdr. …"* sudah membocorkan identitas orangnya meski isinya disembunyikan.

| Bidang | Isi | Terlihat oleh |
|---|---|---|
| `judul_asli` | Judul sebenarnya | Hanya penerima akses butir itu |
| `judul_tampil` | Sebutan umum, mis. *"Perkara penggembalaan"* | Semua orang yang membuka notula tersunting |

Butir `tertutup` juga tidak ikut masuk ke teks undangan WhatsApp dalam bentuk apa pun, dan tidak pernah muncul di portal peninjau.

### Pemberian akses per butir tertutup

Daftar pembaca disusun sendiri untuk tiap butir — bukan mengikuti jabatan. Rancangannya menyimpan pasangan butir dan orang di tabel tersendiri, dengan sejumlah pengaman:

- **Daftar tidak boleh kosong.** Butir tanpa pembaca berarti isinya hilang dari siapa pun, termasuk pembuatnya.
- **Notulis sidang ikut secara otomatis.** Notulis yang tercatat pada sidang itu selalu masuk daftar tanpa perlu ditunjuk, sehingga butir tertutup tidak pernah gagal dinotulakan. Yang ikut otomatis hanya notulis sidang tersebut — bukan setiap orang yang berperan sekretaris.
- **Perkecualian bila notulis adalah pihak dalam perkara.** Perlu jalan keluar untuk menunjuk pencatat pengganti khusus satu butir, dan mengeluarkan notulis tetap dari daftarnya.
- **Sekretaris pendamping boleh sengaja ditinggalkan** — butirnya cukup tidak muncul di layarnya, dan itu memang perilaku yang diinginkan.
- **Setiap pembukaan dicatat**: siapa, kapan, dari mana.
- **Peninjauan akses berkala.** Karena akses diberikan sekali dan bertahan, perlu layar yang menampilkan siapa memegang akses apa, agar dapat ditinjau ulang secara berkala.

#### Serah terima antar periode

Akses melekat pada orang, bukan jabatan. Ketika Ketua atau Sekretaris berganti, akses ke butir tertutup lama **dialihkan manual saat serah terima**. Maka diperlukan layar serah terima yang, untuk pejabat yang digantikan, menampilkan seluruh butir tertutup yang dapat ia baca, lalu memutuskan dua hal sekaligus untuk tiap butir:

1. Apakah penggantinya diberi akses
2. Apakah akses pejabat lama dicabut

Dua pengaman yang menyertainya:

- **Laporan butir tanpa pembaca aktif.** Bila serah terima terlewat, ada butir tertutup yang tidak bisa dibuka siapa pun lagi. Perlu daftar yang menyorot keadaan ini, karena tanpa itu kehilangannya baru ketahuan bertahun-tahun kemudian.
- **Akses lama tidak hilang sendiri.** Tanpa pencabutan yang disengaja, pejabat yang sudah selesai masa tugasnya tetap memegang akses selamanya.

### Portal keputusan untuk peninjau
Peninjau — pengurus komisi, panitia, UPK, dan jemaat — **tidak masuk ke aplikasi notula**. Mereka membuka antarmuka web tersendiri yang hanya memuat **keputusan akhir kemajelisan**:

- Sumber: kesimpulan berlevel `umum` dari notula yang sudah `sah`
- **Setiap peninjau melihat seluruh keputusan `umum`** — tidak disaring menurut unitnya
- Tidak memuat masukan, jalannya pembahasan, presensi, maupun draf
- Dapat dicari dan disaring menurut unit, tahun, dan nomor sidang
- Setiap keputusan menampilkan nomor sidang, tanggal, dan SK terkait bila ada
- Menjadi salah satu saluran distribusi petikan, di samping surat resmi

**Peninjau perlu masuk dengan akun.** Portal tidak terbuka bebas. Rancangannya:

- Satu aplikasi Laravel yang sama, dengan kelompok rute dan tata letak tersendiri — bukan aplikasi kedua
- Satu tabel pengguna; peran yang menentukan ke permukaan mana seseorang diantar setelah masuk
- Akses peninjau **diberikan khusus per orang** atas keputusan majelis, bukan otomatis melekat pada jabatan di unit
- Masa berlakunya terikat pada masa kepengurusan — ketika panitia bubar, aksesnya ikut tutup
- Layar peninjauan akses yang sama dipakai untuk memantau siapa saja yang masih memegang akses portal

### Satu pola yang berulang

Tiga keputusan berturut-turut mengambil bentuk yang sama: pembaca butir tertutup ditunjuk per butir, akses peninjau diberikan per orang, dan penyuntingan bersama dibiarkan bebas alih-alih dikunci sistem. Majelis lebih memilih **keputusan manusia yang tegas daripada aturan otomatis**.

Konsekuensinya bagi aplikasi: yang perlu dibangun bukan mesin aturan, melainkan **mekanisme pemberian akses yang seragam** — satu cara memberi, satu cara mencabut, satu daftar untuk meninjau semuanya. Tanpa itu, akses akan menumpuk diam-diam selama bertahun-tahun karena tidak ada yang otomatis mencabutnya.

### Autentikasi
- Akun dibuat admin; tanpa registrasi mandiri
- Login dengan **nomor HP** + password; email opsional
- Login pertama wajib ganti password
- Reset password: admin membuat tautan sekali pakai, dikirim lewat WA manual
- UX untuk pengguna usia lanjut: tombol besar, teks jelas, maksimal dua ketukan ke tugas utama

---

## 5. Master Data

Struktur organisasi lebih kaya daripada dugaan awal. Yang muncul di dokumen: Ministerium, LP4WG, UPK, komisi (Ibadah, Pemuda, Multimedia, Doa), panitia ad-hoc (Natal, Sidang Istimewa, Bulan Kebudayaan, Tim Pembangunan II, Tim Pendamping Vikariat), wilayah/rama (A, B, C), serta Klasis dan Sinode.

- **Profil gereja** — nama, alamat, logo, kop surat, pejabat penanda tangan (tabel pengaturan tunggal)
- **Periode kemajelisan** — arsip tetap terbaca setelah majelis berganti; menampung peristiwa pisah-sambut majelis
- **Anggota** — nama, **sebutan/gelar (Pdt, Pnt, Dkn, Vik)**, jabatan, bidang/unit, wilayah, penanda keanggotaan MPH
  - Sebutan dipakai otomatis saat menulis masukan di notula: `Pnt Haryanto: …`
- **Unit pelayanan** — bertipe: bidang, komisi, UPK, panitia, tim, lembaga (LP4WG), ministerium
  - Panitia dan tim punya masa berlaku serta dasar SK pembentukan/pemberhentian
- **Wilayah/Rama** — A, B, C; dipakai juga sebagai penanggung jawab tugas (mis. PIC konsumsi, PIC roti & anggur)
- **Entitas eksternal** — Klasis, Sinode, gereja lain, lembaga; menjadi tujuan petikan keputusan

---

## 6. Fitur

### 6.1 Agenda dan artikel
- Penyusunan sidang dari template artikel, lalu isi butir agenda
- Butir agenda: judul, pelapor/penyaji, unit terkait, lampiran, level kerahasiaan, status
- Usulan agenda pra-sidang oleh anggota, dikurasi sekretaris
- **Artikel Tindak Lanjut terisi otomatis** dari tindak lanjut belum tuntas — tidak perlu disalin manual
- Warnasari mengizinkan butir baru ditambahkan saat sidang berlangsung
- Status agenda: `baru`, `dibahas`, `ditunda`, `dikembalikan`, `selesai`

### 6.2 Presensi
- Dua cara: **check-in mandiri** (QR/tautan, token per sidang, berlaku pada rentang waktu sidang) dan **input manual sekretaris**
- Status: hadir, terlambat, izin, sakit, tanpa keterangan
- Bila bentrok, data sekretaris menang
- Peserta tanpa akun (pengurus komisi, panitia, LP4WG, undangan) dicatat manual — praktik nyata menunjukkan mereka ikut berbicara
- Kuorum otomatis, ambang batas dapat diatur per jenis rapat
- Rekap kehadiran per orang per periode
- Undangan sidang MPL ditujukan kepada anggota MPH **dan** MPL sekaligus

### 6.3 Masukan peserta
- Terikat pada satu butir agenda
- Format tampilan mengikuti kebiasaan: `Pnt Haryanto:` diikuti butir bernomor
- **Balasan satu tingkat** — mendukung pola "Respon Pdt Neny: …" yang lazim dipakai
- Penutur bisa anggota berakun atau peserta manual
- Penanda "→ jadikan kesimpulan"
- Masukan tertulis pra-sidang bagi yang berhalangan
- Karena tanpa rekaman, UX notulis diprioritaskan: autosave, pilih nama dari daftar, mode "sidang berjalan" satu layar, tombol tambah balasan cepat

### 6.4 Kesimpulan, kesepakatan, dan keputusan
Tiga istilah berbeda yang dipakai majelis, dipertahankan apa adanya:

| Istilah | Makna praktis |
|---|---|
| **Kesimpulan** | Rangkuman hasil pembahasan satu butir agenda |
| **Kesepakatan** | Hal yang disetujui bersama, mengikat namun tanpa SK |
| **Keputusan** | Ketetapan formal, umumnya berlanjut ke SK atau petikan |

- Pengambilan **secara musyawarah** — tanpa pencatatan voting berangka; tersedia kolom catatan proses
- Status lain: `ditunda`, `dikembalikan ke unit`
- **Tindak lanjut**: penanggung jawab (orang, unit, atau wilayah/rama), tenggat, status, catatan progres
- Dasbor "belum tuntas" menjadi sumber Artikel Tindak Lanjut sidang berikutnya

### 6.5 Keputusan untuk eksternal — SK dan petikan

**Penerbitan SK dikelola penuh di dalam aplikasi**, termasuk penomoran dan tenggat.

#### Kewenangan: MPH mengusulkan, MPL menetapkan

Rapat MPH tidak menerbitkan SK sendiri. Alurnya:

```
Rapat MPH  →  kesimpulan bertanda "usul SK"
           →  masuk otomatis ke agenda sidang MPL berikutnya
           →  MPL menetapkan
           →  SK diterbitkan
```

- Sistem **mencegah** penerbitan SK yang dasarnya hanya rapat MPH
- SK yang lahir dari usulan MPH menyimpan dua rujukan: sidang MPH pengusul dan sidang MPL penetap
- **Tenggat dihitung sejak sidang MPL yang menetapkan**, bukan sejak rapat MPH
- Ini menjelaskan mengapa Artikel Tindak Lanjut berjudul "keputusan MPH **&** sidang MPL sebelumnya" — usulan MPH memang mengalir ke MPL, dan artikel itulah salurannya

#### Atribut dan status

- Sumber: butir agenda berlevel `umum` pada notula berstatus `sah`
- Atribut SK: nomor (otomatis), jenis, perihal, dasar/konsideran, unit atau orang yang dituju, tanggal terbit, masa berlaku, penanda tangan, berkas final
- Jenis SK yang lazim: pengangkatan, pemberhentian, pembentukan panitia/tim, penetapan anggaran
- Status: `usulan MPH` → `ditetapkan MPL` → `draft` → `terbit` → `disampaikan`
  (SK yang lahir langsung dari MPL mulai dari status `ditetapkan MPL`)
- **Tenggat bawaan: terbit paling lambat sebelum sidang MPL berikutnya** — mengikuti kesepakatan majelis; sistem menghitung tanggalnya sendiri dan memberi peringatan bila lewat
- Saat kesimpulan ditandai "perlu SK", entri SK langsung terbentuk agar tidak terlupakan
- **Buku register SK** — daftar lengkap yang dapat dicari dan dicetak per periode
- Keterkaitan dengan master data: SK pembentukan mengaktifkan panitia/tim, SK pemberhentian menutup masa berlakunya — riwayat kepengurusan terbentuk sendiri

**Petikan keputusan** ber-kop surat untuk komisi, wilayah, jemaat, klasis/sinode, pihak luar. Status distribusi: `draft` → `dikirim` → `ditindaklanjuti`.

### 6.6 Notula dan pengesahan
Alur: `draft` → `review` → `sah` → (`adendum` bila ada koreksi setelah pengesahan)

- Batas waktu koreksi dapat diatur (usul: 3×24 jam); lewat batas dianggap tanpa koreksi
- Koreksi berupa komentar per agenda, bukan penyuntingan langsung; sekretaris merapikan
- Catatan: siapa sudah membaca, siapa mengajukan koreksi, koreksi mana diterima/ditolak
- Pengesahan memerlukan **Ketua + Sekretaris**, setelah itu dokumen terkunci
- Presensi ikut terkunci saat notula disahkan
- Dua varian cetak: **lengkap** dan **tersunting**

**Tata letak notula diperluas dari format yang berlaku sekarang.** Unsur berikut ditambahkan:

| Bagian | Isi |
|---|---|
| Kepala | Kop gereja, `Notula Sidang MPL/MPH ke-<nomor>`, hari & tanggal, **jam mulai dan selesai**, tempat |
| Pejabat sidang | **Pemimpin sidang** dan **notulis** |
| Rekap presensi | Jumlah hadir, izin, sakit, tanpa keterangan; **status kuorum** terpenuhi atau tidak |
| Daftar hadir | Nama beserta sebutan, dikelompokkan MPH / MPL / undangan & peninjau |
| Isi | Artikel berurutan seperti sekarang |
| Kaki | **Blok tanda tangan Ketua dan Sekretaris** beserta tanggal pengesahan |

Karena ini mengubah bentuk dokumen yang sudah lama dipakai, sebaiknya format baru ditunjukkan lebih dulu ke majelis sebelum berlaku. Sediakan saklar agar bagian daftar hadir dapat disembunyikan bila dirasa terlalu panjang.

### 6.7 Penyusun pesan WhatsApp
Template undangan mengikuti format yang sudah dipakai:

```
<Kota>, <tanggal surat>
Salam dalam kasih Kristus,
Kami mengundang Bapak dan Ibu anggota MPH dan MPL untuk hadir pada
Sidang MPL ke-<nomor> sbb:
Hari/tanggal: … | Pukul: … | Tempat: …
AGENDA MPL ke-<nomor>
  <daftar artikel dan butir agenda>
<catatan tambahan: waktu kehadiran lebih awal, PIC konsumsi>
Kami tunggu kehadirannya, terimakasih.
<Sebutan dan nama pengundang>
```

- Daftar agenda tersusun otomatis dari artikel dan butir yang sudah dientri — **agenda berlevel `terbatas` hanya tampil judulnya**
- Field bebas untuk catatan tambahan dan PIC konsumsi
- Template lain: pengingat, draft notula siap dikoreksi, notula sah, pengingat tenggat tindak lanjut dan SK
- Keluaran: teks siap pakai + tombol **Salin** + tautan `wa.me`, berorientasi grup
- Penanda manual "sudah dikirim ke grup" beserta waktu

### 6.8 Arsip dan pencarian
- Pencarian lintas tahun: kata kunci, unit, penanggung jawab, jenis sidang, nomor sidang
- Riwayat topik: isu yang muncul di beberapa sidang ditampilkan sebagai satu benang
- Ekspor PDF/Word

---

## 6a. Sidang dengan Dua Sekretaris

Kenyataan ruang sidang: dua laptop, dua infocus, dua sekretaris. Satu menayangkan materi, satu menotulakan, dan layar notulis **ikut tertayang ke ruangan secara langsung**. Keduanya ingin menyunting sidang yang sama pada saat bersamaan.

### Pembagian peran

| Peran | Laptop | Layar | Tugas |
|---|---|---|---|
| **Notulis tayang** | Terhubung ke infocus notulensi | Mode proyektor | Mencatat masukan, menentukan butir aktif |
| **Sekretaris pendamping** | Terhubung ke infocus materi | Tampilan kerja biasa | Merapikan ejaan, mengisi pelapor, melampirkan berkas, menyiapkan butir berikutnya |

Hanya **notulis tayang** yang menentukan butir aktif. Pendamping berpindah bebas tanpa mengubah apa yang terlihat di ruangan — kalau tidak, sidang akan berpindah topik di layar hanya karena rekannya membuka butir lain.

### Cara menghindari tabrakan — tanpa kunci

Keduanya menyunting bebas; sistem hanya memperingatkan bila bentrok. Karena tidak ada penguncian, **pencegahan bertumpu pada perancangan data, bukan pada larangan**.

1. **Butir data dibuat sekecil mungkin.** Setiap masukan, balasan, dan kesimpulan adalah baris tersendiri — notula tidak pernah disimpan sebagai satu bidang teks besar. Selama dua sekretaris menyentuh baris yang berbeda, bentrokan tidak terjadi sama sekali. Inilah pencegahan yang sebenarnya.
2. **Penguncian optimistis per baris.** Setiap penyimpanan membawa cap versi. Bila baris sudah berubah, muncul pilihan *punya Anda / punya rekan / gabungkan* — tidak pernah menimpa diam-diam.
3. **Penanda kehadiran.** Meski tanpa kunci, tetap tampilkan "Pnt. Jennie sedang di Artikel III butir 4". Ini mencegah tabrakan secara sosial, dan ditumpangkan pada penyegaran yang sudah berjalan.
4. **Penyegaran berkala** tiap 5 detik. Untuk dua pengguna, beban ini wajar di shared hosting.
5. **Peringatan bentrok tidak boleh muncul di mode proyektor.** Kotak dialog di depan 50 orang adalah gangguan, bukan bantuan. Di layar tayang cukup pita tipis; penyelesaiannya dikerjakan di laptop pendamping.
6. **Perubahan masuk dengan halus.** Isi baru dari rekan tidak boleh membuat layar tayang melompat — tambahkan dengan lembut dan pertahankan posisi gulir.

### Catatan teknis

- Penyegaran berkala **tidak boleh melewati session PHP berbasis berkas**, karena penguncian session akan memblokir permintaan lain dari pengguna yang sama. Gunakan session driver `database`, atau titik akhir penyegaran tanpa session.
- Cap versi cukup memakai kolom `versi` bertipe integer pada tiap baris, dinaikkan setiap penyimpanan
- Perlu diuji lebih dulu terhadap batas *entry process* dan kuota CPU hosting

---

## 7. Rancangan Data Ringkas

```
settings · periode_kemajelisan · users · anggota_majelis
unit_pelayanan · wilayah · entitas_eksternal

sidang (jenis + nomor, unik gabungan) · artikel_template · artikel
agenda · agenda_lampiran · usulan_agenda
presensi · peserta_manual
masukan (parent_id → balasan berjenjang)
kesimpulan · tindak_lanjut · tindak_lanjut_progres
sk · sk_register_counter · petikan_keputusan
kehadiran_sunting · riwayat_bentrok
notula · notula_koreksi · notula_pembacaan
wa_template · wa_kirim_log · log_akses_terbatas · activity_log
```

Catatan: `agenda.level_kerahasiaan` menjadi kunci penyaringan di hampir semua kueri turunan (masukan, kesimpulan, SK, petikan, teks undangan).

---

## 8. Batasan Teknis Shared Hosting cPanel

- Queue driver `database` lewat cron (`queue:work --stop-when-empty`) — tanpa Supervisor/Redis
- Scheduler satu cron per menit (`schedule:run`)
- PDF wajib **dompdf**; Browsershot/Puppeteer tidak dapat berjalan
- Aset front-end di-build lokal lalu diunggah; jangan mengandalkan `npm` di server
- Batas lampiran mengikuti `upload_max_filesize`; validasi ukuran & jenis file di aplikasi
- Tanpa websocket → hindari fitur realtime; Livewire berbasis HTTP tetap aman
- Cadangan: cron `mysqldump` + arsip `storage`, ditambah tombol ekspor manual
- **Prasyarat sebelum mulai:** PHP 8.2+, MySQL 8, akses cron, `zip`, ekstensi `intl` & `gd`

---

## 9. Rencana Rilis

| Fase | Cakupan |
|---|---|
| **1** | Master data, akun & hak akses, sidang + kerangka artikel, agenda + dua sumbu pembatasan, masukan berjenjang dengan penyebut `@`, **mode proyektor**, **penyuntingan dua sekretaris**, presensi manual + kuorum, notula (draft→review→sah), cetak PDF lengkap & tersunting, penyusun undangan WA |
| **2** | Check-in mandiri via QR/tautan, rekap kehadiran per periode |
| **3** | Kesimpulan/kesepakatan terstruktur, tindak lanjut, artikel tindak lanjut otomatis, dasbor monitoring |
| **4** | SK (penomoran, tenggat, register), petikan keputusan eksternal, status distribusi, **portal keputusan untuk peninjau** |

**Catatan urutan:** presensi manual ditarik maju ke Fase 1 karena tata letak notula yang baru memuat rekap presensi, daftar hadir, dan status kuorum — tanpa itu, keluaran Fase 1 belum lengkap. Check-in mandiri tetap di Fase 2 karena sifatnya kemudahan, bukan prasyarat.

Pada Fase 1 kesimpulan tetap dapat ditulis sebagai teks di dalam artikel agar notula utuh; modul terstrukturnya menyusul di Fase 3.

---

## 9a. Bahan yang Perlu Dikumpulkan Sebelum Perancangan

| # | Bahan | Untuk apa |
|---|---|---|
| 1 | Nomor terakhir deret MPL dan deret MPH | Titik awal penomoran |
| 2 | Contoh SK yang berlaku + nomor terakhir | Tata letak dan format penomoran SK |
| 3 | Contoh petikan keputusan, bila ada | Tata letak petikan |
| 4 | Berkas kop surat, logo, nama & jabatan penanda tangan | Cetak PDF |
| 5 | Daftar anggota MPH dan MPL beserta sebutan, jabatan, unit, wilayah, nomor HP | Data awal & akun |
| 6 | Daftar unit pelayanan aktif: bidang, komisi, UPK, panitia, tim, lembaga | Master data |
| 7 | Daftar wilayah/rama | Master data & penugasan PIC |
| 8 | Pasal kuorum dari Tata Laksana | Aturan kuorum per jenis sidang |
| 9 | Satu notula MPH sebagai contoh | Memastikan kerangka artikel MPH berbeda atau sama dengan MPL |
| 10 | Spesifikasi hosting: versi PHP & MySQL, akses cron | Verifikasi prasyarat teknis |

---

## 10. Hal yang Masih Terbuka

1. Format penomoran SK dan petikan keputusan, beserta nomor terakhir yang sudah terpakai
4. Aturan kuorum tiap jenis sidang, merujuk Tata Gereja/Tata Laksana GKJ
5. Daftar resmi unit pelayanan, wilayah/rama, dan kepanitiaan yang aktif
6. Jumlah anggota MPH dan MPL
7. Siapa admin sistem, dan penggantinya bila berhalangan
8. Apakah ketua/pendeta boleh menyunting notula langsung atau hanya lewat koreksi
9. Apakah usulan agenda pra-sidang terbuka bagi semua anggota atau hanya pengurus
10. Batas ukuran dan jenis lampiran
11. Aset kop surat, logo, nama pejabat penanda tangan; apakah tanda tangan berupa gambar pindaian
12. Kebijakan penyimpanan dan tanggung jawab pencadangan data
13. Ketentuan retensi agenda `terbatas` — apakah ada masa setelah itu boleh dibuka
14. **Nomor terakhir kedua deret** (MPL dan MPH) saat aplikasi mulai dipakai
15. Contoh dokumen SK yang berlaku, untuk meniru tata letaknya
