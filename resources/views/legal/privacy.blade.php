@extends('legal.layout')

@section('legal')
<section>
    <h2>1. Pengelola dan dasar hukum</h2>
    <p>SIMPIL dikelola oleh Badan Riset dan Inovasi Daerah (BRIDA) Kota Makassar sebagai Penyelenggara Sistem Elektronik lingkup publik.
       Pemrosesan data pribadi di SIMPIL mengacu pada UU No. 27 Tahun 2022 tentang Pelindungan Data Pribadi, PP No. 71 Tahun 2019
       tentang Penyelenggaraan Sistem dan Transaksi Elektronik, dan UU Informasi dan Transaksi Elektronik.</p>
</section>

<section>
    <h2>2. Data yang kami kumpulkan</h2>
    <ul>
        <li><strong>Data akun:</strong> nama, alamat surel, kata sandi (disimpan dalam bentuk hash), institusi, nomor telepon (opsional), dan peran.</li>
        <li><strong>Data publikasi:</strong> naskah, abstrak, kata kunci, nama dan surel penulis pendamping, hasil review, serta riwayat keputusan editorial.</li>
        <li><strong>Data teknis:</strong> catatan aktivitas sistem (log audit) dan data sesi untuk keamanan.</li>
        <li><strong>Bukti persetujuan:</strong> waktu dan versi kebijakan yang Anda setujui saat mendaftar.</li>
    </ul>
</section>

<section>
    <h2>3. Tujuan penggunaan</h2>
    <ul>
        <li>Mengelola pengajuan, peer-review, dan penerbitan naskah ilmiah.</li>
        <li>Mengirim notifikasi proses editorial dan verifikasi akun.</li>
        <li>Menjaga keamanan sistem, audit, dan pelaporan sesuai ketentuan pemerintah daerah.</li>
        <li>Menampilkan nama dan afiliasi penulis pada artikel terbit untuk keperluan sitasi ilmiah.</li>
    </ul>
</section>

<section>
    <h2>4. Data yang bersifat publik dan yang dilindungi</h2>
    <p><strong>Publik:</strong> artikel yang telah terbit beserta nama penulis, afiliasi, dan metadatanya, sesuai prinsip akses terbuka dan keterbukaan informasi publik.</p>
    <p class="mt-2"><strong>Dilindungi:</strong> surel pribadi, nomor telepon, kata sandi, naskah yang belum terbit, riwayat penolakan, dan identitas reviewer.
       Review bersifat anonim: identitas reviewer tidak ditampilkan kepada penulis, dan identitas penulis tidak ditampilkan kepada reviewer.</p>
</section>

<section>
    <h2>5. Penyimpanan dan keamanan</h2>
    <p>Data disimpan pada infrastruktur cloud di wilayah Indonesia (AWS Jakarta, <em>ap-southeast-3</em>), dengan pembatasan akses berbasis peran,
       @if (config('simpil.force_https')) enkripsi transmisi (HTTPS), @endif pencadangan berkala, dan pencatatan audit.</p>
    <p class="mt-2"><strong>Jangka waktu penyimpanan:</strong></p>
    <ul>
        <li><strong>Data akun:</strong> selama akun aktif. Setelah akun dinonaktifkan atas permintaan Anda, data akun tidak lagi diproses kecuali yang wajib disimpan menurut peraturan.</li>
        <li><strong>Artikel terbit dan metadatanya:</strong> disimpan permanen sebagai arsip ilmiah, sekurang-kurangnya 20 tahun sesuai kewajiban simpan hasil riset (UU No. 11 Tahun 2019 Pasal 40).</li>
        <li><strong>Naskah yang tidak terbit, hasil review, dan riwayat keputusan:</strong> disimpan selama diperlukan untuk akuntabilitas proses editorial dan penanganan keberatan.</li>
        <li><strong>Log audit:</strong> disimpan untuk keperluan pengawasan, penyelesaian sengketa, dan pemeriksaan (PP No. 71 Tahun 2019 Pasal 22).</li>
        <li><strong>Cadangan data (backup):</strong> disimpan 14 hari lalu terhapus otomatis.</li>
    </ul>
</section>

<section>
    <h2>6. Hak Anda</h2>
    <p>Anda berhak mengakses dan memperbarui data akun serta mengunduh salinan data pribadi Anda (menu Profil Saya), menarik persetujuan,
       serta meminta penghapusan atau pembatasan pemrosesan data pribadi, dengan pengecualian data yang wajib disimpan berdasarkan
       peraturan perundang-undangan atau telah terbit sebagai arsip ilmiah.
       Permintaan penarikan persetujuan, penghapusan, atau keberatan diajukan melalui menu Pengaduan setelah masuk
       @if ($contact) atau melalui <a class="text-red-600 hover:underline" href="mailto:{{ $contact }}">{{ $contact }}</a>@endif,
       dan ditindaklanjuti paling lambat {{ config('simpil.complaints.response_days') }} hari.</p>
</section>

<section>
    <h2>7. Berbagi data</h2>
    <p>Data pribadi tidak dijual. Data hanya dibagikan kepada pihak yang diperlukan untuk proses editorial (editor dan reviewer jurnal terkait), atau jika diwajibkan
       oleh peraturan perundang-undangan. Metadata artikel terbit dapat diekspor ke repositori nasional untuk kewajiban serah-simpan hasil riset.</p>
</section>

<section>
    <h2>8. Perubahan kebijakan</h2>
    <p>Kebijakan ini dapat diperbarui. Versi yang berlaku tercantum di bagian atas halaman dan dicatat pada persetujuan akun.
       Bila kebijakan berubah, pengguna akan diminta membaca dan menyetujui versi terbaru saat masuk ke SIMPIL.</p>
</section>

<section>
    <h2>9. Kegagalan pelindungan data</h2>
    <p>Bila terjadi kegagalan pelindungan data pribadi, BRIDA akan memberi tahu pengguna yang terdampak secara tertulis paling lambat
       3 × 24 jam, memuat data yang terungkap, waktu dan cara terungkapnya, serta upaya penanganan dan pemulihannya (UU No. 27 Tahun 2022 Pasal 46).</p>
</section>
@endsection
