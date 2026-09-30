<x-tutorial.section title="Apa itu Master Data?">
    <p>Master Data berisi pengaturan yang dipakai di seluruh ERP: pilihan dropdown, jam operasional, slot yang diblokir, dan teks perjanjian pendaftaran. Menu ini butuh permission <code>masterdata.manage</code>. Bagian kirinya berisi daftar pengaturan; klik salah satu untuk mengubahnya.</p>
</x-tutorial.section>

<x-tutorial.section title="Pilihan dropdown">
    <p>Grup yang tersedia: <strong>Customer Type</strong>, <strong>Asset Category</strong>, <strong>Employment Type</strong>, <strong>Leave Type</strong>, <strong>Program Type</strong>, dan <strong>Training Delivery Mode</strong>.</p>
    <ul>
        <li><strong>Menambah</strong>: pilih grup, tulis nama pilihan baru di kolom bawah, lalu klik <strong>Add Option</strong>.</li>
        <li><strong>Mengubah</strong>: ubah <em>Label</em> (nama tampil), <em>Order</em> (urutan), atau kotak <em>Active</em>, lalu klik <strong>Save</strong> pada baris itu. Mengganti label tidak merusak data lama karena kode internalnya tidak berubah.</li>
        <li><strong>Menonaktifkan</strong> menyembunyikan pilihan dari form baru, sementara data lama yang memakainya tetap utuh.</li>
        <li><strong>Menghapus</strong> hanya bisa bila pilihan belum dipakai (lihat kolom <em>Used by</em>). Kalau sudah dipakai, nonaktifkan saja.</li>
        <li>Pilihan bertanda <em>System option</em> (misalnya <em>Annual Leave</em> di Leave Type) dipakai logika sistem sehingga hanya bisa diganti namanya, tidak bisa dinonaktifkan atau dihapus.</li>
    </ul>
    <x-tutorial.note type="warning">Program bertipe <strong>Learning</strong> menentukan penerbitan sertifikat, dan tipe customer <strong>Individual</strong> menentukan harga grup. Karena itu, jangan mengubah arti pilihan yang sudah dipakai; cukup tambahkan pilihan baru.</x-tutorial.note>
</x-tutorial.section>

<x-tutorial.section title="Jam operasional (Operating Hours)">
    <p>Jam operasional menentukan kapan pertemuan training boleh dijadwalkan. Customer melihatnya di kalender pendaftaran dan portalnya, dan tim melihatnya di kalender Dashboard.</p>
    <ul>
        <li><strong>Edit slots</strong>: untuk tiap hari (Senin–Minggu), klik <strong>+ Add slot</strong> lalu isi jam mulai dan selesai. Hari tanpa slot berarti <em>Closed</em>. Klik × untuk menghapus slot.</li>
        <li><strong>Corporate training only</strong>: centang pada slot yang hanya boleh dipakai program bertipe corporate (lihat <em>Program &amp; Kategori Training</em>). Slot ini disembunyikan dari pemesanan lain (tampil ungu di kalender).</li>
        <li>Jam istirahat siang 12:00–13:00 selalu tidak bisa dipesan, sekalipun slot Anda melintasinya (slot akan terbagi dua otomatis).</li>
        <li><strong>Only allow meetings inside these hours</strong>: bila aktif (bawaan), pertemuan harus jatuh di hari buka dan cocok dengan slot. Matikan bila sesekali perlu pengecualian.</li>
        <li>Klik <strong>Save Operating Hours</strong> untuk menyimpan. Bagian <em>Weekly schedule</em> menampilkan pratinjau jadwal mingguan yang ikut berubah saat Anda mengedit.</li>
    </ul>
</x-tutorial.section>

<x-tutorial.section title="Memblokir slot">
    <p>Bila tim sedang punya kegiatan lain, blokir waktunya supaya customer tidak bisa memesannya. Di bagian <strong>Block slots</strong>, klik jendela jam operasional pada hari yang dimaksud, isi rentang waktu (dari–sampai) dan alasan (opsional), lalu simpan. Waktu itu tampil sebagai terisi bagi customer. Klik waktu abu-abu yang diblokir untuk membukanya kembali (<em>Slot is available again</em>).</p>
    <p>Pemblokiran hanya bisa untuk tanggal hari ini atau setelahnya, harus berada di dalam jam operasional hari itu, dan tidak boleh menimpa waktu yang sudah dipesan.</p>
</x-tutorial.section>

<x-tutorial.section title="Agreement (perjanjian pendaftaran)">
    <p>Tulis teks perjanjian yang dibaca customer saat mendaftar di menu <strong>Agreement</strong>, lalu klik <strong>Save</strong>. Formulir pendaftaran menampilkan tautan <em>Read the full agreement</em> di bawah kotak persetujuan yang membuka teks ini di halaman publik. Kosongkan teksnya untuk menyembunyikan tautan tersebut. Persetujuan atas syarat &amp; ketentuan tiap program diatur terpisah di data program.</p>
</x-tutorial.section>
