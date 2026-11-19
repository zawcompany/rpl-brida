@extends('legal.layout')

@section('legal')
<section>
    <h2>1. Ruang lingkup</h2>
    <p>SIMPIL adalah sistem informasi manajemen publikasi ilmiah multijurnal milik BRIDA Kota Makassar. Dengan membuat akun atau menggunakan sistem,
       Anda menyetujui syarat berikut. Membaca artikel terbit tidak memerlukan akun dan tidak dipungut biaya.</p>
</section>

<section>
    <h2>2. Akun pengguna</h2>
    <ul>
        <li>Data yang Anda berikan harus benar dan mutakhir; Anda bertanggung jawab atas kerahasiaan kata sandi.</li>
        <li>Pendaftaran publik menghasilkan akun Penulis. Peran Editor dan Reviewer ditetapkan oleh administrator atau editor jurnal.</li>
        <li>Akun dapat dinonaktifkan jika terbukti menyalahgunakan sistem.</li>
    </ul>
</section>

<section>
    <h2>3. Pengajuan naskah</h2>
    <ul>
        <li>Penulis menyatakan naskah orisinal, belum dipublikasikan, dan tidak sedang diajukan ke penerbit lain.</li>
        <li>Penulis menyatakan memiliki hak atas naskah dan menyetujui penerbitan dengan lisensi terbuka yang ditetapkan jurnal tujuan.</li>
        <li>Penulis mengungkapkan konflik kepentingan yang relevan. Ketentuan lengkap ada pada <a class="text-red-600 hover:underline" href="{{ route('legal.ethics') }}">Etika Publikasi</a>.</li>
    </ul>
</section>

<section>
    <h2>4. Hak cipta dan lisensi</h2>
    <p>Penulis tetap memegang hak cipta atas naskahnya sesuai UU No. 28 Tahun 2014 tentang Hak Cipta. Dengan penerbitan, penulis memberikan hak kepada jurnal untuk
       mempublikasikan dan mendistribusikan artikel di bawah lisensi Creative Commons yang tercantum pada halaman jurnal dan artikel.
       Pembaca boleh menggunakan artikel sesuai ketentuan lisensi tersebut dengan mencantumkan atribusi kepada penulis.</p>
</section>

<section>
    <h2>5. Larangan</h2>
    <ul>
        <li>Mengunggah konten yang melanggar hukum, mengandung kode berbahaya, atau melanggar hak pihak lain.</li>
        <li>Mengakses akun atau data pihak lain tanpa izin, atau mengganggu keamanan dan ketersediaan sistem.</li>
        <li>Membuka identitas reviewer atau menyalahgunakan materi naskah yang belum terbit.</li>
    </ul>
</section>

<section>
    <h2>6. Tanggung jawab</h2>
    <p>Isi artikel merupakan tanggung jawab penulis. BRIDA berupaya menjaga ketersediaan dan keamanan sistem, namun tidak menjamin sistem bebas gangguan.</p>
</section>

<section>
    <h2>7. Data pribadi</h2>
    <p>Pengolahan data pribadi diatur dalam <a class="text-red-600 hover:underline" href="{{ route('legal.privacy') }}">Kebijakan Privasi</a>.</p>
</section>
@endsection
