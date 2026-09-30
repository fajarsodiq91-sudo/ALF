<x-tutorial.section title="Gambaran singkat">
    <p>Modul <strong>Sales</strong> menyimpan semua customer. Ada tiga tipe customer (bisa diubah di <em>Master Data → Customer Type</em>): <strong>Company</strong>, <strong>Individual</strong>, dan <strong>Government / Institution</strong>. Setiap customer yang disetujui mendapat <strong>ID permanen</strong> berformat <code>YYMMNN</code> (tahun, bulan, urutan), misalnya <code>260901</code> untuk customer pertama di September 2026. Batasnya 99 customer per bulan.</p>
    <p>Status yang muncul di daftar:</p>
    <ul>
        <li><strong>Awaiting customer</strong>: undangan sudah dibuat, customer belum mengisi formulir.</li>
        <li><strong>Pending approval</strong>: customer sudah mengisi formulir dan menunggu tinjauan Anda.</li>
        <li><strong>Rejected</strong>: pendaftaran ditolak.</li>
        <li><strong>Active</strong> / <strong>Inactive</strong>: customer yang sudah disetujui atau dibuat manual.</li>
    </ul>
</x-tutorial.section>

<x-tutorial.section title="Mengundang customer lewat QR code (cara utama)">
    <x-tutorial.steps>
        <li>Buka <strong>Sales</strong>, klik <strong>Add Customer</strong>, lalu pilih tab <strong>Customer fills in (QR code)</strong>.</li>
        <li>Pilih <strong>Type</strong> customer dan klik <strong>Generate QR Code</strong>. Anda tidak perlu mengisi data lain.</li>
        <li>Tunjukkan QR code kepada customer untuk dipindai dengan HP, atau salin tautannya dengan tombol <strong>Copy</strong> lalu kirim lewat WhatsApp atau email.</li>
        <li>Tautan berlaku <strong>7 hari</strong> dan hanya bisa dipakai sekali: setelah customer mengirim formulir, tautan tertutup. Bila kedaluwarsa atau salah kirim, buka lagi QR-nya dari daftar (tautan <strong>QR Code</strong> pada baris customer) dan klik <strong>Generate new link</strong>. QR code lama otomatis mati.</li>
    </x-tutorial.steps>
    <x-tutorial.note>Butuh permission <code>sales.manage</code> untuk membuat undangan.</x-tutorial.note>
</x-tutorial.section>

<x-tutorial.section title="Yang dilakukan customer di formulir pendaftaran">
    <p>Halaman ini publik (tanpa login). Customer akan:</p>
    <ul>
        <li>mengisi nama, email, telepon, kota, alamat, dan foto (opsional);</li>
        <li>memilih satu atau beberapa program (maksimal 5) dari katalog, lengkap dengan foto, harga, dan promo yang sedang berjalan;</li>
        <li>memilih tanggal dan jam untuk <em>setiap</em> pertemuan lewat kalender. Hanya slot hijau (masih kosong dan sesuai jam operasional) yang bisa dipilih;</li>
        <li>memilih skema bayar: lunas di awal atau 50% di awal dan 50% sisanya kemudian (program satu pertemuan wajib lunas di awal, kecuali program sehari penuh 7 jam);</li>
        <li>customer tipe <strong>Individual</strong> pada program yang mendukung harga grup dapat mendaftarkan beberapa orang sekaligus dengan harga per orang yang lebih murah;</li>
        <li>membaca dan menyetujui syarat &amp; ketentuan program, serta teks perjanjian bila Anda sudah mengisinya di <em>Master Data → Agreement</em>.</li>
    </ul>
    <p>Setelah mengirim, customer diarahkan ke halaman <strong>status pendaftaran</strong> yang selalu terbarui (menunggu persetujuan, disetujui beserta ID-nya, atau ditolak) dan menerima email konfirmasi. Pada saat yang sama, <strong>semua user aktif yang punya permission <code>sales.manage</code></strong> menerima email pemberitahuan bahwa ada pendaftaran baru yang menunggu tinjauan.</p>
</x-tutorial.section>

<x-tutorial.section title="Meninjau dan menyetujui pendaftaran">
    <x-tutorial.steps>
        <li>Buka kartu <strong>Customer registrations awaiting approval</strong> di Dashboard, atau filter <strong>Sales</strong> dengan status <strong>Pending approval</strong>. Klik <strong>Review</strong>.</li>
        <li>Periksa data customer dan apakah syarat &amp; ketentuan sudah disetujui. Ada yang salah? Klik <strong>Edit the details</strong> lebih dulu.</li>
        <li>Program dan jadwal yang diminta customer sudah terisi otomatis. Sesuaikan tanggal/jam (hanya slot yang kosong), <strong>delivery mode</strong>, tempat, instruktur, <strong>fee</strong>, dan skema bayar. Anda bisa membuang program yang tidak bisa dipenuhi atau menambah program lain. Pratinjau pembagian pembayaran tampil langsung.</li>
        <li>Klik <strong>Approve &amp; Send Email</strong>. Minimal satu program dengan satu pertemuan harus ada. Gunakan <strong>Remove program</strong>, <strong>+ Add another program</strong>, dan <strong>+ Add meeting</strong> untuk menyusun daftarnya.</li>
    </x-tutorial.steps>
    <p>Saat disetujui, sistem otomatis:</p>
    <ul>
        <li>membuat <strong>ID customer</strong> permanen dan password awal portal yang <em>sama dengan ID</em> tersebut (customer wajib menggantinya saat login pertama);</li>
        <li>membuat <strong>sesi training</strong> beserta semua pertemuannya dan jadwal pembayarannya (lihat topik <em>Sesi Training</em>);</li>
        <li>mengirim <strong>email persetujuan</strong> berisi detail dan tautan login portal, lalu invoice yang sudah jatuh tempo.</li>
    </ul>
    <p>Untuk <strong>menolak</strong>, gunakan formulir di bagian bawah halaman review: isi alasan (opsional) lalu klik <strong>Reject Registration</strong>. Customer menerima email penolakan. Pendaftaran yang ditolak tetap ada di daftar dengan status <strong>Rejected</strong>.</p>
    <x-tutorial.note type="warning">Jika email gagal terkirim, pesan merah muncul tetapi persetujuan atau penolakan tetap tersimpan. Hubungi customer secara manual dan periksa pengaturan email server.</x-tutorial.note>
</x-tutorial.section>

<x-tutorial.section title="Menambah customer secara manual">
    <p>Jika Anda ingin mengisi sendiri datanya (misalnya customer lama), klik <strong>Add Customer</strong> dan tetap di tab <strong>I fill in the details</strong>. Isi nama, tipe, kontak, alamat, foto, dan catatan, lalu simpan. ID customer dibuat otomatis. Customer manual langsung berstatus aktif dan tidak melewati proses persetujuan, tetapi program dan jadwalnya tetap dibuat lewat <strong>Training → Sessions</strong>. Mereka <strong>tidak mendapat login portal</strong> (kolom <em>Portal login</em> di menu Customer Portal menampilkan "No login (added by staff)"); login portal hanya dibuat lewat pendaftaran yang disetujui atau tautan peserta.</p>
    <p>Mengedit customer berstatus <strong>Awaiting customer</strong> dan menyimpannya sama artinya dengan menyelesaikan pendaftarannya sendiri.</p>
</x-tutorial.section>

<x-tutorial.section title="Mengelola data customer">
    <ul>
        <li><strong>Cari dan filter</strong>: kolom pencarian (ID, nama, telepon, email), tipe, dan status.</li>
        <li><strong>Halaman detail</strong> (klik nama customer) memuat data kontak, tabel <strong>Programs</strong> (tanggal, pertemuan selesai, jumlah terbayar dibanding fee, status, tautan <strong>Manage</strong> ke sesinya), dan <strong>Projects uploaded by the customer</strong>.</li>
        <li><strong>Projects</strong>: unduh file atau buka tautan proyek yang diunggah customer. Klik <strong>Mark as added</strong> bila proyek dimasukkan ke portofolio; proyek itu lalu tampil di halaman portofolio publik customer (lihat topik <em>Customer Portal</em>).</li>
        <li><strong>Edit</strong> mengubah data, foto, dan kotak <strong>Active</strong>.</li>
        <li><strong>Delete</strong> hanya bisa untuk customer yang belum punya sesi training atau proyek. Selain itu, tandai <strong>Inactive</strong> saja.</li>
    </ul>
    <x-tutorial.note type="info">Karyawan perusahaan yang bergabung lewat tautan peserta tetap berada di bawah perusahaannya dan tidak muncul di daftar ini. Sebaliknya, anggota grup dari customer <strong>Individual</strong> muncul sebagai customer masing-masing dengan ID sendiri.</x-tutorial.note>
</x-tutorial.section>
