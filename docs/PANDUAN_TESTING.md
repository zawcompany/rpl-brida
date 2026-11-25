# Panduan Testing SIMPIL (Tim 4 Orang)

Target uji: **`http://<IP-PUBLIK>`** (ganti dengan IP server). Semua tes manual dilakukan di link ini, bukan di localhost.

## 0. Pembagian Tugas

| Orang | Tanggung jawab | Bagian di dokumen ini |
|---|---|---|
| **P1 (pemilik kode)** | Tes otomatis (PHPUnit) + smoke test script + rekap hasil | Bagian 1 & 2 |
| **P2** | Role **Author** + halaman publik/reader | Bagian 4, 3 |
| **P3** | Role **Editor** (alur editorial & penerbitan) | Bagian 5 |
| **P4** | Role **Admin** + **Reviewer** | Bagian 6, 7 |
| **Semua** | Uji keamanan, kompatibilitas, dan non-fungsional di akhir | Bagian 8, 9 |

Urutan kerja: P1 menjalankan smoke test dulu (Bagian 2). Kalau lolos, P2/P3/P4 mulai tes manual. Bagian 5 (Editor) butuh naskah dari P2 dan reviewer dari P4, jadi lakukan **alur end-to-end (Bagian 10)** bersama-sama.

## Akun uji (dari seeder, password `password123`)

| Role | Email |
|---|---|
| Admin | admin@example.com |
| Author | author@example.com |
| Editor | editor@example.com |
| Reviewer | reviewer@example.com, reviewer2@..., reviewer3@... |

> Jika server produksi tidak di-seed, minta Admin membuat akun lewat menu *Kelola Pengguna*. Jangan pakai password asli di akun uji.
> Tiga reviewer dibutuhkan karena naskah wajib punya **3 reviewer** (`REQUIRED_REVIEWERS = 3`).

## Format pencatatan

Setiap kasus uji dicatat di sheet bersama dengan kolom: `ID | Skenario | Langkah | Hasil diharapkan | Hasil aktual | Lolos? | Bukti (screenshot) | Penguji`. Bug dicatat dengan langkah reproduksi, URL, role, browser, dan screenshot.

---

## 1. Tes Kode Otomatis (P1)

Sudah ada **229 tes** di `tests/Feature` (204 lama + 25 baru di `QualityGapTest.php`), berjalan di SQLite in-memory dan tidak menyentuh server.

```powershell
php artisan test
```

Cakupan: auth, 4 modul role, alur editorial, multi-jurnal, legal/consent, ekspor metadata, upload file (anti file palsu/macro), throttle login, storage S3/lokal, route smoke (tidak ada 500).

Tes tambahan di `QualityGapTest.php`: matriks akses 4 role x 4 modul, IDOR antar-author, reviewer tanpa penugasan tidak bisa unduh naskah, escaping XSS, validasi registrasi, registrasi tidak bisa eskalasi role, sesi akun suspend ditutup, 404 bukan 500.

Kriteria lolos: semua hijau. Simpan output sebagai bukti.

## 2. Smoke Test ke IP Publik (P1)

Otomatis, hanya GET, tidak mengubah data:

```powershell
powershell -File deploy/smoke-test.ps1 -BaseUrl http://<IP-PUBLIK>
```

Memeriksa: halaman publik 200, area terlindungi mengalihkan tamu, 404 bukan 500, `/dev-login` tidak ada, `.env`/`.git`/log tidak terbuka, header keamanan, tidak ada `X-Powered-By`, metadata JSON valid, beranda < 3 detik.

---

## 3. Halaman Publik / Reader (P2, tanpa login)

| ID | Skenario | Hasil diharapkan |
|---|---|---|
| PUB-01 | Buka beranda | Tampil tanpa error, cover jurnal & artikel terbit muncul |
| PUB-02 | `/articles`: cari/filter artikel | Hasil sesuai, pagination jalan |
| PUB-03 | Buka detail artikel terbit | Judul, abstrak, penulis, DOI (jika ada) tampil |
| PUB-04 | Unduh PDF artikel terbit | File terunduh dan terbuka |
| PUB-05 | `/archives` lalu buka satu edisi | Daftar artikel edisi tampil |
| PUB-06 | `/jurnal` lalu halaman satu jurnal | Info jurnal, e-ISSN, cover tampil |
| PUB-07 | `/jurnal/{id}/metadata.xml` dan `.json` | XML/JSON valid (Dublin Core) |
| PUB-08 | Halaman Kebijakan Privasi, Syarat, Etika | Ketiganya terbuka |
| PUB-09 | Buka artikel yang belum terbit (`/articles/<id naskah draft>`) | 404, tidak bocor |
| PUB-10 | Tamu buka `/jurnal/{id}/template` | Diminta login |

## 4. Role Author (P2)

| ID | Skenario | Hasil diharapkan |
|---|---|---|
| AUT-01 | Registrasi akun baru (centang persetujuan privasi) | Masuk ke dashboard Author |
| AUT-02 | Registrasi: password < 8, konfirmasi beda, email dobel, tanpa centang persetujuan | Ditolak dengan pesan jelas |
| AUT-03 | Login benar / salah; 6x salah berturut-turut | Salah = pesan error; ke-6 = diblokir sementara (throttle) |
| AUT-04 | Gabung jurnal lalu unduh template jurnal | Berhasil gabung, template terunduh |
| AUT-05 | Submit naskah valid (PDF/DOCX, ko-penulis, deklarasi) | Naskah masuk "Pending", muncul di *Naskah Saya* |
| AUT-06 | Submit tanpa file / tanpa deklarasi / judul kosong | Ditolak |
| AUT-07 | Upload file bukan PDF/DOCX, file `.exe` diganti nama `.pdf`, DOCX bermakro, file terlalu besar | Ditolak |
| AUT-08 | Lihat detail naskah dan status timeline | Status sesuai alur |
| AUT-09 | Setelah editor minta revisi: lihat catatan reviewer, unggah revisi | Revisi tersimpan, status berubah, editor dapat notifikasi |
| AUT-10 | Notifikasi: buka, tandai baca, baca semua | Counter berkurang |
| AUT-11 | Profil: ubah data, ganti password (salah password lama) | Sukses / ditolak sesuai kasus |
| AUT-12 | Akses URL role lain (`/admin/dashboard`, `/editor/dashboard`) | 403 |
| AUT-13 | Buka naskah milik author lain lewat ubah ID di URL | 403/404 |
| AUT-14 | Keluar jurnal (leave) | Berhasil |
| AUT-15 | Logout, lalu tombol Back browser | Tidak bisa melihat halaman terlindungi |

## 5. Role Editor (P3)

| ID | Skenario | Hasil diharapkan |
|---|---|---|
| EDT-01 | Dashboard: statistik tampil | Angka sesuai data |
| EDT-02 | *Naskah Baru*: buka detail naskah, unduh file | Benar |
| EDT-03 | Assign reviewer: pilih < 3, tepat 3, reviewer dobel, reviewer tak sesuai bidang | Aturan 3 reviewer ditegakkan; status jadi "Sedang Ditinjau" |
| EDT-04 | Direktori reviewer + profil keahlian | Tampil, filter bidang bekerja |
| EDT-05 | *Peninjauan*: kirim pengingat, ganti reviewer | Reviewer dapat notifikasi/email |
| EDT-06 | Keputusan: diterima / revisi minor / revisi mayor / ditolak | Status naskah berubah sesuai pemetaan; author diberi notifikasi |
| EDT-07 | Minta review ulang setelah revisi | Kembali ke tahap review |
| EDT-08 | Buat edisi baru (volume, nomor, tahun, cover) | Edisi tersimpan |
| EDT-09 | Attach naskah disetujui ke edisi, set DOI per artikel | Tersimpan, format DOI valid |
| EDT-10 | Detach naskah, edit, hapus edisi | Sesuai; edisi terbit tidak bisa dihapus sembarangan |
| EDT-11 | Publikasi edisi | Artikel tampil di halaman publik (cek PUB-03/05) |
| EDT-12 | Kelola anggota jurnal (tambah/hapus Author/Reviewer) | Berhasil |
| EDT-13 | Editor jurnal A mengakses data jurnal B | Ditolak (journal.access) |
| EDT-14 | Akses `/admin/*`, `/author/*` | 403 |

## 6. Role Admin (P4)

| ID | Skenario | Hasil diharapkan |
|---|---|---|
| ADM-01 | Dashboard: statistik dan 5 log aktivitas terbaru | Tampil |
| ADM-02 | Tambah pengguna (tiap role), email dobel, password lemah | Sukses / ditolak |
| ADM-03 | Edit pengguna, ubah role | Role baru berlaku saat login ulang |
| ADM-04 | Suspend pengguna yang sedang login di browser lain | Pengguna langsung dikeluarkan di request berikutnya |
| ADM-05 | Reset password pengguna | Password lama tidak berlaku lagi |
| ADM-06 | Kelola jurnal: buat, ubah (e-ISSN, cover), kelola anggota | Tersimpan, tampil di `/jurnal` |
| ADM-07 | Bidang keahlian: tambah, ubah, nonaktifkan | Muncul di form naskah dan reviewer |
| ADM-08 | Halaman Role & Hak Akses | Informasi sesuai |
| ADM-09 | Admin tidak bisa menonaktifkan/menurunkan dirinya sendiri (jika ada aturan) | Sesuai aturan |
| ADM-10 | Akses `/author/*`, `/editor/*`, `/reviewer/*` sebagai admin | 403 |

## 7. Role Reviewer (P4)

| ID | Skenario | Hasil diharapkan |
|---|---|---|
| REV-01 | Dashboard dan *Naskah Ditugaskan* | Hanya penugasan miliknya |
| REV-02 | Buka detail penugasan, unduh naskah (anonim, tanpa identitas author bila aturan blind review) | Sesuai |
| REV-03 | Terima penugasan | Status review "diterima" |
| REV-04 | Tolak penugasan (dengan alasan) | Editor mendapat notifikasi |
| REV-05 | Submit hasil review (rekomendasi, catatan, lampiran) | Tersimpan, pindah ke *Naskah Selesai* |
| REV-06 | Submit tanpa isi wajib / lampiran tidak valid | Ditolak |
| REV-07 | Edit hasil review yang sudah dikirim | Sesuai aturan (boleh/tidak sebelum keputusan) |
| REV-08 | Buka review milik reviewer lain lewat ubah ID | 403/404 |
| REV-09 | Reviewer tanpa penugasan mengunduh `/berkas/{id}/original` | 403 |
| REV-10 | Profil: atur bidang keahlian | Tersimpan, tampil di direktori editor |

---

## 8. Uji Keamanan di IP Publik (semua, bagi rata: P2 = 8.1-8.3, P3 = 8.4-8.5, P4 = 8.6-8.8)

| ID | Skenario | Hasil diharapkan |
|---|---|---|
| SEC-01 | Brute force login (6+ percobaan cepat) | Diblokir 429 |
| SEC-02 | Isi nama/judul/abstrak dengan `<script>alert(1)</script>` dan `"><img src=x onerror=alert(1)>` | Tampil sebagai teks, tidak dieksekusi |
| SEC-03 | Ubah ID di URL (naskah, review, notifikasi, pengguna) milik orang lain | 403/404 |
| SEC-04 | Registrasi dengan menambah field `role=Administrator` (DevTools) | Tetap Author |
| SEC-05 | Hapus token CSRF / kirim form dari halaman lain | 419 |
| SEC-06 | Buka `/.env`, `/.git/config`, `/storage/logs/laravel.log`, `/vendor/` | 403/404 |
| SEC-07 | Cek cookie sesi di DevTools: `HttpOnly`, `SameSite`, dan `Secure` jika HTTPS | Flag terpasang |
| SEC-08 | Setelah logout, tekan Back dan salin URL dashboard ke tab baru | Dialihkan ke login |
| SEC-09 | Unggah file palsu (exe diberi ekstensi .pdf, DOCX berisi makro) | Ditolak |
| SEC-10 | `/dev-login/admin` | 404 |
| SEC-11 | Halaman error (URL ngawur) tidak menampilkan stack trace / path server | Halaman 404 bersih (APP_DEBUG=false) |
| SEC-12 | Cek HTTPS: apakah IP/domain memakai TLS? | Catat sebagai temuan jika masih HTTP |

Opsional (alat bantu gratis): `nmap -sV <IP>` untuk port terbuka (yang boleh terbuka hanya 80/443 dan SSH terbatas), OWASP ZAP baseline scan: `docker run -t ghcr.io/zaproxy/zaproxy:stable zap-baseline.py -t http://<IP>`. **Hanya pada server milik sendiri.**

## 9. Non-Fungsional dan Kompatibilitas (semua)

| ID | Skenario | Cara | Target |
|---|---|---|---|
| NF-01 | Kecepatan halaman | Chrome DevTools Lighthouse pada beranda, `/articles`, login | Performance >= 70, tanpa error konsol |
| NF-02 | Beban ringan | `npx autocannon -c 20 -d 20 http://<IP>/` (P1) | Tidak ada 5xx, p95 < 2 dtk |
| NF-03 | Responsif | DevTools mode HP (375px) dan tablet pada semua halaman utama | Tidak ada layout rusak/scroll horizontal |
| NF-04 | Browser | Chrome, Firefox, Edge (+ Safari/HP bila ada) | Alur login dan submit naskah jalan |
| NF-05 | Upload naskah besar | File mendekati batas ukuran | Berhasil / pesan batas jelas, tidak 413/500 mentah |
| NF-06 | Penyimpanan file | Upload naskah, restart container/aplikasi, unduh lagi | File tetap ada (S3/volume persisten) |
| NF-07 | Backup dan restore | Jalankan skrip `deploy/backup`, restore ke DB uji | Data kembali utuh |
| NF-08 | Pemulihan | Restart server/container | Situs naik sendiri tanpa intervensi |
| NF-09 | Email | Kirim ulang verifikasi email, pengingat reviewer | Email diterima, tautan bertanda tangan valid |
| NF-10 | Aksesibilitas dasar | Navigasi keyboard (Tab), kontras, label form | Form bisa dipakai tanpa mouse |

---

## 10. Skenario End-to-End (dikerjakan bersama, sekali jalan)

Dilakukan di 4 browser/profil berbeda secara berurutan:

1. **P4 (Admin)** buat/pastikan 3 reviewer + 1 editor + 1 jurnal aktif.
2. **P2 (Author)** daftar, gabung jurnal, submit naskah. Cek status *Pending*.
3. **P3 (Editor)** tugaskan 3 reviewer. Cek status *Sedang Ditinjau*.
4. **P4 (Reviewer)** terima, lalu submit review (satu minta revisi). Cek notifikasi ke editor.
5. **P3 (Editor)** putuskan *Revisi Mayor*. **P2** unggah revisi, lalu **P3** minta review ulang, reviewer submit lagi.
6. **P3 (Editor)** putuskan *Diterima*, buat edisi, attach naskah, isi DOI, publikasikan.
7. **P2** buka artikel di halaman publik tanpa login (PUB-03/04/07). Cek metadata XML memuat artikel tersebut.
8. **P4 (Admin)** cek log aktivitas mencatat seluruh langkah di atas.

Lolos jika seluruh transisi status, notifikasi, dan artikel publik benar tanpa error 500.

## 11. Kriteria Selesai

- Tes otomatis: 100% hijau.
- Smoke test IP publik: tanpa FAIL.
- Semua kasus ID di atas berstatus Lolos, atau bug-nya tercatat dengan tingkat keparahan (Kritis/Tinggi/Sedang/Rendah).
- Tidak ada bug Kritis/Tinggi yang terbuka (error 500, kebocoran data, bypass akses).
- Rekap akhir (jumlah kasus, lolos/gagal, daftar bug) dikumpulkan P1 untuk laporan.
