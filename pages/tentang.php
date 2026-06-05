<?php
require_once __DIR__ . '/../includes/functions.php';
wajibLogin();
$pageTitle = 'Tentang';
$pageDesc  = 'Informasi aplikasi & penjelasan metode MAUT';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="about-hero">
  <h2>Sistem Pendukung Keputusan Penilaian Kader PKK Terbaik</h2>
  <p>Aplikasi ini membantu pengelola Posyandu Lansia di Kelurahan Pulogebang, Kecamatan Cakung, Jakarta Timur dalam menentukan kader PKK terbaik secara objektif dan terukur menggunakan metode <b>Multi Attribute Utility Theory (MAUT)</b>. Penilaian sebelumnya yang manual dan subjektif kini menjadi terstruktur, cepat, dan dapat dipertanggungjawabkan.</p>
</div>

<div class="grid grid-2 mb-2">
  <div class="card">
    <div class="section-head"><h3>Apa itu MAUT?</h3></div>
    <p class="muted" style="line-height:1.7">Multi Attribute Utility Theory adalah metode pengambilan keputusan yang mengubah beberapa kriteria menjadi satu nilai utilitas terukur. Metode ini fleksibel terhadap jenis kriteria (benefit/cost), mampu mengolah banyak kriteria sekaligus, dan menghasilkan perangkingan yang jelas.</p>
  </div>
  <div class="card">
    <div class="section-head"><h3>5 Kriteria Penilaian</h3></div>
    <table class="tbl">
      <tbody>
        <tr><td><span class="badge badge-code">C1</span></td><td>Kehadiran</td><td class="text-right"><b>0,30</b></td></tr>
        <tr><td><span class="badge badge-code">C2</span></td><td>Pelayanan Kesehatan</td><td class="text-right"><b>0,25</b></td></tr>
        <tr><td><span class="badge badge-code">C3</span></td><td>Kerjasama Tim</td><td class="text-right"><b>0,20</b></td></tr>
        <tr><td><span class="badge badge-code">C4</span></td><td>Tanggung Jawab</td><td class="text-right"><b>0,15</b></td></tr>
        <tr><td><span class="badge badge-code">C5</span></td><td>Inisiatif</td><td class="text-right"><b>0,10</b></td></tr>
      </tbody>
    </table>
  </div>
</div>

<div class="card mb-2">
  <div class="section-head"><h3>Tahapan Perhitungan MAUT</h3></div>
  <div class="step-list">
    <div class="step-item"><div class="num"></div><div><b>Membentuk Matriks Keputusan</b><p>Menyusun nilai setiap alternatif (kader) terhadap setiap kriteria ke dalam matriks x<sub>ij</sub> berskala 1&ndash;10, dengan <i>i</i> = alternatif dan <i>j</i> = kriteria.</p></div></div>
    <div class="step-item"><div class="num"></div><div><b>Normalisasi Data</b><p>Menyamakan skala antar kriteria ke rentang 0&ndash;1, dibedakan menurut jenis kriteria.</p>
      <div class="formula-box">
        <span class="eq">
          r*<sub>ij</sub> <span class="op">=</span>
          <span class="frac">
            <span class="fnum">x<sub>ij</sub> &minus; min<sub>j</sub></span>
            <span class="fden">max<sub>j</sub> &minus; min<sub>j</sub></span>
          </span>
          <span class="tag">benefit</span>
        </span>
      </div>
      <div class="formula-box">
        <span class="eq">
          r*<sub>ij</sub> <span class="op">=</span>
          <span class="frac">
            <span class="fnum">max<sub>j</sub> &minus; x<sub>ij</sub></span>
            <span class="fden">max<sub>j</sub> &minus; min<sub>j</sub></span>
          </span>
          <span class="tag">cost</span>
        </span>
      </div>
      <p class="muted small mt-1">min<sub>j</sub> dan max<sub>j</sub> = nilai terkecil dan terbesar pada kolom kriteria <i>j</i>.</p></div></div>
    <div class="step-item"><div class="num"></div><div><b>Perhitungan Utilitas Marjinal</b><p>Mengubah nilai normalisasi menjadi utilitas memakai fungsi eksponensial (opsional; mode linear memakai u<sub>ij</sub> = r*<sub>ij</sub>).</p>
      <div class="formula-box">
        <span class="eq">
          u<sub>ij</sub> <span class="op">=</span>
          <span class="frac">
            <span class="fnum">e<sup>(r*<sub>ij</sub>)<sup>2</sup></sup> &minus; 1</span>
            <span class="fden">1,71</span>
          </span>
        </span>
      </div></div></div>
    <div class="step-item"><div class="num"></div><div><b>Perhitungan Utilitas Akhir</b><p>Menjumlahkan hasil kali utilitas dengan bobot tiap kriteria (<span class="op">&Sigma;</span> untuk seluruh kriteria <i>j</i>).</p>
      <div class="formula-box">
        <span class="eq">
          U<sub>i</sub> <span class="op">=</span>
          <span class="sum">&Sigma;</span><sub>j</sub>
          ( u<sub>ij</sub> <span class="op">&times;</span> w<sub>j</sub> )
        </span>
      </div>
      <p class="muted small mt-1">w<sub>j</sub> = bobot kriteria <i>j</i>, dengan &Sigma; w<sub>j</sub> = 1.</p></div></div>
    <div class="step-item"><div class="num"></div><div><b>Perangkingan</b><p>Mengurutkan nilai U<sub>i</sub> dari tertinggi ke terendah. Alternatif dengan U<sub>i</sub> tertinggi direkomendasikan sebagai kader PKK terbaik.</p></div></div>
  </div>
</div>

<div class="card">
  <div class="section-head"><h3>Informasi Pengembangan</h3></div>
  <div class="grid grid-2">
    <div>
      <p class="muted small">Studi Kasus</p><p><b>Posyandu Lansia Kelurahan Pulogebang</b><br>Kecamatan Cakung, Jakarta Timur</p>
      <p class="muted small mt-2">Metode</p><p><b>Multi Attribute Utility Theory (MAUT)</b></p>
    </div>
    <div>
      <p class="muted small">Teknologi</p><p><b>PHP Native, MySQL, HTML, CSS, JavaScript (AJAX)</b></p>
      <p class="muted small mt-2">Jumlah Data</p><p><b>100 kader PKK &middot; 5 kriteria</b></p>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
