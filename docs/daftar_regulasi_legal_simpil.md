# Daftar Regulasi Evaluasi Legal — SIMPIL (Sistem Informasi Manajemen Publikasi Ilmiah Multijurnal BRIDA)

**Karakteristik sistem penentu regulasi:**
1. Dikelola instansi pemerintah daerah (BRIDA) → Penyelenggara Sistem Elektronik (PSE) **Lingkup Publik**.
2. Proses inti: submission → peer-review → penerbitan jurnal elektronik (multijurnal).
3. Open access, tanpa biaya (tanpa APC), tetapi menyimpan data pribadi akun (penulis, reviewer, editor).
4. Hosting AWS region `ap-southeast-3` (Jakarta); ada backup dan `activity_logs`.

Legenda status verifikasi: ✔ = regulasi dikenal/nyata, tetap cek teks resmi di JDIH · ⚠ = **wajib diverifikasi** sebelum dipresentasikan.
Legenda status fitur: **Ada** (ditemukan di kode) · **Belum** (tidak ditemukan di kode; tulis sebagai rencana) · **Dugaan** (belum dicek penuh).

---

## A. ✅ Hukum yang BERHUBUNGAN

### A1. Klaster Tata Kelola Pemerintahan & Data
| Regulasi | Verif. | Keterkaitan ke sistem | Status fitur |
|---|---|---|---|
| Perpres 95/2018 tentang SPBE | ✔ | Sistem dimiliki instansi pemerintah daerah → tunduk tata kelola layanan digital pemerintah | Dasar umum |
| UU 14/2008 tentang Keterbukaan Informasi Publik | ✔ | Artikel terbit & profil jurnal dibuka untuk publik tanpa login | Ada (halaman publik/reader) |
| Perpres 39/2019 tentang Satu Data Indonesia | ✔ | Metadata artikel seragam agar bisa dipertukarkan | Metadata ada; ekspor OAI-PMH **Belum** |
| Permendagri tentang penyelenggaraan litbang di daerah (cari nomor & tahun yang berlaku) | ⚠ | Dasar hukum kelembagaan BRIDA | Rujukan |
| Peraturan BRIN 5/2023 tentang Tata Kelola Riset dan Inovasi di Daerah, **Pasal 34–36** | ✔ (diverifikasi dari PDF resmi) | Ps. 34: BRIDA menyelenggarakan sistem informasi riset & inovasi daerah yang **diintegrasikan ke SPBE**. Ps. 35: dimanfaatkan untuk (a) wajib serah & wajib simpan, (b) koordinasi dan pelaporan. Ps. 36: wajib serah-simpan dilaksanakan sesuai peraturan perundangan. Pasal ini **tidak** menyebut jurnal, DOI, atau OAI-PMH — jangan klaim lebih dari itu | Integrasi SPBE & ekspor metadata untuk serah-simpan: **Belum** |

### A2. Klaster Publikasi Ilmiah & Kekayaan Intelektual
| Regulasi | Verif. | Keterkaitan ke sistem | Status fitur |
|---|---|---|---|
| UU 28/2014 tentang Hak Cipta | ✔ | Hak moral & ekonomi penulis; perlu pernyataan keaslian, copyright notice, lisensi | Lisensi CC & copyright notice **Belum** |
| **Permendiktisaintek No. 9 Tahun 2026** tentang Akreditasi Jurnal Ilmiah (BN 2026 No. 453) | ✔ (disebut di konsiderans Kepdirjen 374/2026) | Peraturan induk akreditasi jurnal. **Nama yang benar bukan "Permenristekdikti"** (itu nomenklatur 2018 yang sudah tidak dipakai); isi pasalnya belum saya baca | Multijurnal, peer-review, DOI opsional **Ada** |
| **Kepdirjen Saintek No. 374/DST/D.D1/HM.01.01/2026** tentang Petunjuk Teknis Akreditasi Jurnal Ilmiah | ✔ (diverifikasi dari salinan PDF resmi, 58 hlm.) | Juknis pelaksana Permendiktisaintek 9/2026 (Ps. 6, 7, 9, 11, 15); **menggantikan** Kepdirjen Risbang 134/E/KPT/2021. Persyaratan teknis rinci ada di Lampiran (belum dibaca) | Sesuaikan setelah Lampiran dibaca |
| ~~Permenristekdikti 9/2018~~ | dicoret | Diganti aturan di atas; jangan dicantumkan | — |
| UU 11/2019 tentang Sisnas Iptek (Pasal 40 menurut Gemini) | ⚠ | Cek isi pasal sebelum dikutip | — |

### A3. Klaster Keamanan Informasi & Data Pribadi
| Regulasi | Verif. | Keterkaitan ke sistem | Status fitur |
|---|---|---|---|
| UU 27/2022 tentang Pelindungan Data Pribadi | ✔ | Menyimpan email, riwayat penilaian, identitas reviewer | Peran/akses ada; consent saat registrasi **Belum**; hapus/ubah data akun **Dugaan** |
| PP 71/2019 tentang PSTE | ✔ | PSE publik: keandalan, backup, audit trail, lokasi data | Backup **Ada**; audit trail **Ada**; data di Jakarta **Ada** |
| UU 11/2008 jo. UU 19/2016 jo. UU 1/2024 (ITE) | ✔ | Sistem & dokumen elektronik, keamanan, larangan akses ilegal — **belum ada di dokumen Gemini, tambahkan** | Dasar umum |
| Peraturan BSSN 4/2021 tentang Pedoman Manajemen Keamanan Informasi SPBE | ✔ | RBAC, enkripsi transmisi, integritas file | Peran (admin/editor/reviewer/author) **Ada**; HTTPS tergantung deployment — **cek** |

---

## B. ❌ Hukum yang TIDAK berhubungan (beserta alasan)

| Regulasi | Alasan tidak berlaku |
|---|---|
| Permenkominfo 5/2020 (PSE Lingkup Privat) | BRIDA instansi pemerintah → rezim PSE Lingkup Publik (PP 71/2019, SPBE), bukan pendaftaran PSE privat |
| Aturan sistem pembayaran, fintech, pajak digital (PBI/PMK) | Tidak ada biaya publikasi dan tidak ada fitur pembayaran/transaksi (tidak ditemukan kode pembayaran) |
| UU 13/2016 tentang Paten | Sistem hanya menerbitkan karya tulis ilmiah (Hak Cipta), bukan pendaftaran invensi |
| Regulasi kaji etik penelitian lapangan/klinis | Sistem hanya mengelola naskah & review, bukan mengawasi proses penelitian. Tanggung jawab etik ada pada penulis dan lembaga asalnya |

---

## C. Tindak lanjut sebelum presentasi
1. Cari dokumen asli semua rujukan bertanda ⚠ di jdih.go.id / laman resmi; hapus yang tidak ketemu.
2. Tambahkan UU ITE (sudah di tabel A3) dan Permendagri litbang daerah.
3. Untuk fitur berstatus **Belum**, tulis sebagai "rencana pengembangan" atau implementasikan (lisensi CC, consent registrasi, OAI-PMH).
4. Cek HTTPS dan fitur hapus/ubah data akun di sistem sebelum diklaim.

## C2. Update implementasi (19 Nov 2026)
Sudah **Ada** di kode (200 tes lolos):
- Consent privasi saat registrasi + bukti waktu dan versi kebijakan (UU PDP).
- Halaman Kebijakan Privasi, Syarat Penggunaan, dan Etika Publikasi (tautan di footer publik).
- Lisensi Creative Commons per jurnal, disalin ke tiap naskah saat diajukan, tampil di halaman artikel beserta copyright notice (UU Hak Cipta).
- Pernyataan penulis saat submit: keaslian, hak cipta/lisensi, konflik kepentingan.
- Kolom e-ISSN jurnal (validasi format), tampil di halaman jurnal dan artikel.
- Ekspor metadata Dublin Core (XML dan JSON) per jurnal: `/jurnal/{slug}/metadata.xml|json`. Ini ekspor berkas untuk repositori nasional, **bukan** endpoint OAI-PMH penuh.

Masih **Belum**: hapus/unduh data akun mandiri, pengecekan plagiarisme otomatis, ekspor OAI-PMH penuh, integrasi SPBE.
Catatan: teks kebijakan perlu ditinjau unit hukum BRIDA; `FORCE_HTTPS=true` harus diaktifkan di produksi.

## D. Antisipasi pertanyaan dosen
- **Mengapa UU PDP berlaku padahal nama & afiliasi penulis dipublikasikan?** Itu atas persetujuan penulis untuk sitasi. Yang dilindungi adalah email, kata sandi, riwayat penolakan, dan identitas reviewer yang disimpan di database.
- **PP 71/2019 vs Permenkominfo 5/2020?** PP 71/2019 mengatur PSE umum termasuk lingkup publik; Permenkominfo 5/2020 khusus PSE lingkup privat. BRIDA instansi pemerintah, jadi yang berlaku yang pertama.
