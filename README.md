# SPK Penilaian Kader PKK Terbaik — Metode MAUT

Sistem Pendukung Keputusan berbasis web untuk menentukan **Kader PKK Terbaik**
pada kegiatan Posyandu Lansia (Kelurahan Pulogebang, Cakung, Jakarta Timur)
menggunakan metode **Multi Attribute Utility Theory (MAUT)**.

Dibangun dengan **PHP Native, MySQL, HTML, CSS, JavaScript (AJAX)** — tanpa framework.

## Fitur

- 🔐 **Autentikasi** (login/logout, password ter-hash bcrypt)
- 🏠 **Beranda** — ringkasan statistik & peringkat sementara
- 🧮 **Perhitungan MAUT** — tahapan lengkap (matriks → normalisasi → utilitas → ranking), 2 mode: Linear & Eksponensial
- 👥 **Manajemen Data Alternatif** — CRUD kader + edit nilai matriks inline (AJAX)
- ⚖️ **Manajemen Kriteria** — CRUD kriteria, atur bobot & jenis (benefit/cost)
- 📜 **Riwayat (Historis)** — simpan & lihat detail hasil perhitungan
- ℹ️ **Tentang** — penjelasan metode & rumus MAUT
- 🖨️ **Cetak Hasil** & **Cetak Data Alternatif** — laporan ber-kop surat resmi (siap PDF)

## Kriteria & Bobot (sesuai paper, Tabel 4.2)

| Kode | Kriteria | Bobot | Jenis |
|------|----------|-------|-------|
| C1 | Kehadiran | 0,30 | Benefit |
| C2 | Pelayanan Kesehatan | 0,25 | Benefit |
| C3 | Kerjasama Tim | 0,20 | Benefit |
| C4 | Tanggung Jawab | 0,15 | Benefit |
| C5 | Inisiatif | 0,10 | Benefit |

## Rumus MAUT

```
Normalisasi (benefit) : r*ij = (rij - min) / (max - min)
Utilitas marjinal      : uij  = (e^(r*ij²) - 1) / 1.71   (mode eksponensial)
Utilitas akhir         : Ui   = Σ (uij × wj)
```

> Catatan: contoh perhitungan pada BAB IV (UA01 = 0,700; A02 juara = 0,734)
> menggunakan utilitas **linear**, sehingga sistem menyediakan kedua mode.

## Cara Instalasi (XAMPP / Laragon)

1. Salin folder `spk_maut` ke `htdocs` (XAMPP) atau `www` (Laragon).
2. Buat database & import struktur:
   - Buka **phpMyAdmin** → menu **Import** → pilih `database/spk_maut.sql`.
   - Atau via terminal: `mysql -u root -p < database/spk_maut.sql`
3. Siapkan konfigurasi database:
   - Salin `config/database.example.php` menjadi `config/database.php`.
   - Sesuaikan kredensial bila perlu (default: host `localhost`, user `root`, password kosong).
   - Catatan: `config/database.php` sengaja tidak ikut di repo (lihat `.gitignore`) agar kredensial tidak ter-upload.
4. Jalankan server, buka: `http://localhost/spk_maut/`

## Akun Demo

| Username | Password |
|----------|----------|
| `admin`  | `admin123` |

## Struktur Folder

```
spk_maut/
├── index.php              → redirect
├── login.php              → halaman autentikasi
├── config/database.php    → koneksi PDO + identitas instansi
├── includes/              → maut.php, functions.php, header, footer, kop_surat
├── actions/               → logout, simpan_nilai (AJAX)
├── pages/                 → dashboard, perhitungan, alternatif, kriteria,
│                            historis, tentang, cetak_hasil, cetak_alternatif
├── assets/css/            → style.css (warm gradient), print.css (kop surat)
├── assets/js/app.js       → AJAX & interaksi
└── database/spk_maut.sql  → skema + data dari paper
```
