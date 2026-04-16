# 📘 ALUR KERJA 4 ROLE — CLOUD TICKET MANUFACTURING

Dokumen ini menjelaskan **alur kerja nyata di dunia kerja** untuk 4 role yang saling berkaitan:

- **Superadmin**
- **Admin**
- **Teknisi (Agent)**
- **Customer**

Fokus dokumen ini adalah **proses bisnis end-to-end** (bukan teknis coding), dari tiket dibuat sampai selesai dan dievaluasi.

---

## 1) Tujuan dan Ruang Lingkup

### Tujuan

Memberikan panduan operasional yang jelas agar semua role:

- Paham tugas masing-masing
- Paham kapan harus berkoordinasi
- Paham standar penyelesaian tiket

### Ruang Lingkup

Mencakup alur:

1. Pelaporan masalah oleh Customer
2. Validasi & triase oleh Admin
3. Eksekusi perbaikan oleh Teknisi
4. Monitoring & tata kelola oleh Superadmin
5. Penutupan tiket dan evaluasi berkelanjutan

---

## 2) Peran dan Tanggung Jawab Utama

## A. Superadmin

Peran strategis dan pengawas sistem secara menyeluruh.

**Tanggung jawab utama:**

- Menetapkan kebijakan layanan, prioritas, dan standar kualitas
- Mengelola user level Admin/Teknisi/Customer secara global
- Memantau KPI layanan (respon, penyelesaian, backlog)
- Menyetujui perbaikan proses lintas divisi
- Menangani eskalasi level tinggi

## B. Admin

Peran kontrol operasional harian.

**Tanggung jawab utama:**

- Memvalidasi tiket baru (kelengkapan data)
- Menentukan prioritas, kategori, dan assignment teknisi
- Memantau progres tiket aktif
- Menjembatani komunikasi Customer ↔ Teknisi
- Menutup tiket setelah validasi hasil kerja

## C. Teknisi (Agent)

Peran eksekutor lapangan/teknis.

**Tanggung jawab utama:**

- Menerima assignment dari Admin
- Menganalisis penyebab masalah
- Menjalankan tindakan perbaikan
- Memberi update progres yang jelas dan berkala
- Menyerahkan hasil penyelesaian untuk validasi

## D. Customer

Peran pelapor masalah dan pemberi konfirmasi hasil.

**Tanggung jawab utama:**

- Membuat tiket dengan detail masalah yang akurat
- Menambahkan bukti (foto/video/keterangan) bila ada
- Menanggapi pertanyaan klarifikasi dari Admin/Teknisi
- Mengonfirmasi apakah masalah sudah terselesaikan

---

## 3) Status Tiket dan Arti Operasional

Agar sinkron antar role, setiap tiket melewati status berikut:

1. **Open**  
   Tiket baru dibuat Customer, menunggu validasi/triase.

2. **Assigned**  
   Tiket sudah divalidasi Admin dan ditugaskan ke Teknisi.

3. **In Progress**  
   Teknisi sedang mengerjakan tiket.

4. **Resolved**  
   Solusi teknis selesai, menunggu verifikasi akhir.

5. **Closed**  
   Tiket ditutup setelah hasil dinyatakan selesai (oleh Admin/Customer sesuai kebijakan).

---

## 4) Alur Kerja End-to-End (Sampai Selesai)

## Tahap 1 — Customer Melaporkan Masalah

### Langkah 1.1: Customer membuat tiket

- Mengisi judul masalah, deskripsi, lokasi/unit, dan kategori (jika ada)
- Menyertakan bukti pendukung (opsional namun disarankan)
- Sistem mencatat waktu pelaporan untuk perhitungan SLA

### Langkah 1.2: Tiket masuk ke antrian Admin

- Status awal: **Open**
- Admin menerima notifikasi tiket baru

**Output tahap 1:** Tiket terdokumentasi dan siap diverifikasi.

---

## Tahap 2 — Admin Validasi dan Triase

### Langkah 2.1: Validasi kelengkapan

Admin memeriksa:

- Kejelasan deskripsi masalah
- Kesesuaian kategori
- Dampak terhadap operasional

Jika data kurang:

- Admin meminta klarifikasi ke Customer
- Tiket tetap dipantau sampai siap ditindak

### Langkah 2.2: Penentuan prioritas

Prioritas ditetapkan berdasar dampak bisnis:

- **High:** Menghentikan proses produksi/layanan inti
- **Medium:** Mengganggu proses namun ada workaround
- **Low:** Gangguan minor, tidak menghentikan operasi

### Langkah 2.3: Assignment Teknisi

- Admin memilih Teknisi sesuai jobdesk/kategori
- Status tiket berubah ke **Assigned**
- Teknisi menerima notifikasi assignment

**Output tahap 2:** Tiket tervalidasi, terprioritaskan, dan memiliki penanggung jawab teknis.

---

## Tahap 3 — Teknisi Eksekusi Perbaikan

### Langkah 3.1: Teknisi mulai pekerjaan

- Review detail tiket dan histori komunikasi
- Konfirmasi kebutuhan tambahan jika diperlukan
- Ubah status ke **In Progress**

### Langkah 3.2: Analisis akar masalah

Teknisi melakukan diagnosis:

- Cek gejala dan penyebab
- Cek ketergantungan sistem/perangkat
- Menentukan rencana tindakan

### Langkah 3.3: Tindakan perbaikan

- Menjalankan perbaikan sesuai SOP
- Menghindari perubahan di luar scope tiket
- Melaporkan progres secara periodik ke tiket

### Langkah 3.4: Penyelesaian teknis

- Jika berhasil: status ke **Resolved**
- Teknisi menulis ringkasan tindakan:
    - Penyebab
    - Tindakan
    - Hasil verifikasi teknis

**Output tahap 3:** Solusi teknis selesai dan siap diverifikasi operasional.

---

## Tahap 4 — Verifikasi Hasil dan Penutupan

### Langkah 4.1: Verifikasi Admin

Admin memeriksa:

- Solusi sesuai keluhan awal
- Bukti penyelesaian lengkap
- Tidak ada isu lanjutan yang tertinggal

### Langkah 4.2: Konfirmasi Customer

- Customer mencoba hasil perbaikan
- Jika sudah normal: lanjut penutupan
- Jika belum: tiket dikembalikan ke **In Progress** dengan catatan baru

### Langkah 4.3: Penutupan tiket

- Jika valid: status ke **Closed**
- Admin memastikan dokumentasi akhir rapi

**Output tahap 4:** Tiket resmi selesai dengan jejak audit lengkap.

---

## Tahap 5 — Monitoring, Eskalasi, dan Evaluasi

### Langkah 5.1: Monitoring oleh Superadmin

Superadmin memantau indikator utama:

- Jumlah tiket open/backlog
- Kecepatan respon awal
- Waktu rata-rata penyelesaian
- Kepatuhan SLA per tim/divisi

### Langkah 5.2: Eskalasi kasus kritis

Jika terjadi kasus kritis/berulang:

- Superadmin mengaktifkan eskalasi lintas fungsi
- Menentukan keputusan prioritas dan resource tambahan

### Langkah 5.3: Evaluasi berkala

- Review tren tiket mingguan/bulanan
- Identifikasi akar masalah berulang
- Putuskan tindakan pencegahan permanen

**Output tahap 5:** Proses layanan terus membaik dan risiko gangguan berulang menurun.

---

## 5) Interaksi Antar Role (Siapa Berhubungan Dengan Siapa)

## Customer ↔ Admin

- Customer melaporkan masalah
- Admin melakukan klarifikasi kebutuhan data
- Admin memberi update status secara terstruktur

## Admin ↔ Teknisi

- Admin memberi assignment + prioritas
- Teknisi memberi progres + hasil tindakan
- Admin memvalidasi kualitas hasil sebelum close

## Customer ↔ Teknisi

- Komunikasi teknis dilakukan seperlunya (melalui tiket)
- Fokus pada data fakta dan langkah solusi

## Superadmin ↔ Semua Role

- Superadmin memonitor performa dan kepatuhan proses
- Menyelesaikan hambatan lintas tim
- Menetapkan perbaikan kebijakan

---

## 6) Aturan Operasional agar Alur Berjalan Rapi

1. Satu tiket = satu masalah utama (hindari tiket campuran)
2. Update progres wajib jelas, singkat, dan terukur
3. Perubahan status harus sesuai kondisi nyata lapangan
4. Tidak menutup tiket tanpa validasi hasil
5. Gunakan komentar tiket sebagai sumber komunikasi resmi
6. Semua keputusan penting tercatat untuk audit

---

## 7) Contoh Skenario Nyata (Singkat)

### Kasus

Mesin produksi tidak bisa mencetak label barcode.

### Alur cepat

1. Customer membuat tiket + lampirkan foto error
2. Admin validasi → prioritas High → assign Teknisi A
3. Teknisi A ubah status In Progress, cek koneksi printer & service spooler
4. Teknisi perbaiki konfigurasi driver dan restart service
5. Status Resolved + catatan tindakan
6. Customer uji cetak berhasil
7. Admin menutup tiket (Closed)
8. Superadmin melihat tren: kasus serupa berulang → putuskan standar preventive maintenance

---

## 8) Definisi Selesai (Definition of Done)

Sebuah tiket dianggap **benar-benar selesai** jika:

- Solusi teknis sudah diterapkan
- Customer mengonfirmasi hasil sesuai kebutuhan
- Admin menutup tiket dengan dokumentasi akhir
- Tidak ada tindak lanjut terbuka terkait isu yang sama

---

## 9) Ringkasan Akhir

Cloud Ticket Manufacturing menjadi efektif bila 4 role menjalankan fungsi secara sinkron:

- **Customer** cepat dan jelas melapor
- **Admin** disiplin triase dan kontrol proses
- **Teknisi** fokus eksekusi dan update progres
- **Superadmin** menjaga arah, kualitas, dan perbaikan berkelanjutan

Dengan alur ini, organisasi mendapatkan:

- Respons lebih cepat
- Koordinasi lebih jelas
- Penyelesaian lebih konsisten
- Keputusan manajerial berbasis data
