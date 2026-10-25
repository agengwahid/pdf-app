# PDF Studio — Offline & Local PDF Manipulation Suite

Aplikasi web manipulasi PDF mandiri yang berjalan **100% secara lokal / offline** tanpa ketergantungan API pihak ketiga maupun layanan cloud eksternal.

Dibangun menggunakan:
- **Backend:** Laravel 13 (PHP 8.3)
- **Database:** PostgreSQL (`pdf_app`)
- **Frontend:** Vue 3 + Inertia.js
- **Styling:** Tailwind CSS 4 (Desain utilitarian presisi, anti AI-slop)
- **PDF Engine Lokal:** Ghostscript 10, Poppler (`pdftoppm`, `pdfinfo`), dan FPDI

---

## Fitur Utama

### 1. Merge PDF
- Unggah multi-file PDF secara langsung maupun drag-and-drop.
- Pengaturan urutan file (reorder) interaktif via drag-handle atau tombol panah naik/turun.
- Hapus file dari antrean penggabungan.
- Kustomisasi nama file output.
- Penggabungan secepat kilat dengan Ghostscript engine lokal.

### 2. Compress PDF
- Pilihan 3 tingkat kompresi:
  - **Low (Ringan / 300 DPI):** Kualitas cetak maksimal, cocok untuk arsip dan dokumen fisik.
  - **Medium (Sedang / 150 DPI):** Keseimbangan optimal untuk presentasi dan berbagi digital.
  - **High (Tinggi / 72 DPI):** Ukuran paling hemat untuk lampiran email berkapasitas ketat.
- Perbandingan ukuran **sebelum** dan **sesudah**.
- Penghitungan persentase pengurangan ukuran file secara langsung (real-time).
- Unduh dokumen PDF hasil kompresi.

### 3. Split PDF
- Pemisahan halaman berdasarkan visual grid thumbnail halaman (dihasilkan oleh Poppler lokal).
- Pemilihan halaman interaktif lewat klik thumbnail atau input string rentang (range) seperti `1-3, 5, 8-10` dengan sinkronisasi dua arah.
- Tombol aksi seleksi cepat: *Semua*, *Hapus Pilihan*, *Halaman Ganjil*, *Halaman Genap*, dan *Balik Pilihan*.
- Dua opsi output:
  - **Satu Dokumen PDF:** Mengekstrak seluruh halaman terpilih ke dalam satu PDF baru.
  - **Pisah Per Halaman (.ZIP):** Mengekstrak setiap halaman ke file PDF terpisah yang dibungkus dalam arsip ZIP.

### 4. Manage PDF
- Preview visual seluruh halaman dokumen.
- **Reorder Halaman:** Susun ulang urutan halaman dokumen melalui drag & drop visual atau panah kiri/kanan.
- **Rotate Halaman:** Putar halaman individual atau putar seluruh halaman sekaligus (0°, 90°, 180°, 270°) dengan visual feedback real-time.
- **Delete Halaman:** Hapus halaman yang tidak diinginkan dengan fitur *Undo / Restore* instan.
- Simpan dan unduh dokumen hasil penataan.

### 5. Word to PDF (Konversi Dokumen)
- Konversi file dokumen Microsoft Word (`.docx`, `.doc`, `.odt`, `.rtf`) menjadi PDF beresolusi tinggi.
- Menggunakan **LibreOffice 24 Headless Engine** lokal tanpa membutuhkan akses internet.
- Mempertahankan font, tata letak tabel, header, footer, dan format dokumen asli.
- Tampilan durasi konversi (ms), jumlah halaman, dan ukuran file hasil.

### 6. Riwayat Operasi Lokal (PostgreSQL)
- Setiap operasi (`merge`, `compress`, `split`, `manage`, `word_to_pdf`) dicatat pada tabel `pdf_histories` di database PostgreSQL lokal.
- Drawer riwayat untuk mengunduh ulang hasil olahan sebelumnya atau menghapus riwayat.

---

## Menjalankan Aplikasi Secara Lokal

1. **Database PostgreSQL:**
   Pastikan PostgreSQL berjalan. Konfigurasi default di `.env`:
   ```env
   DB_CONNECTION=pgsql
   DB_HOST=127.0.0.1
   DB_PORT=5432
   DB_DATABASE=pdf_app
   DB_USERNAME=pdfapp
   DB_PASSWORD=pdfapp
   ```

2. **Jalankan Server Laravel:**
   ```bash
   php artisan serve
   ```
   Aplikasi dapat diakses di: `http://localhost:8000`

3. **Kompilasi Frontend (Vite):**
   ```bash
   npm run build
   # atau untuk mode live reload development:
   npm run dev
   ```
